<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Register API endpoint
require_once '../db.php';
require_once 'helpers.php';

// Accept only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Method not allowed', 405);
}

// Detect if JSON or multipart/form-data
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (strpos($contentType, 'application/json') !== false) {
    // JSON input
    $jsonData = file_get_contents('php://input');
    $data = json_decode($jsonData, true);
    $name = $data['name'] ?? '';
    $phone = $data['phone'] ?? '';
    $password = $data['password'] ?? '';
    $referralCode = $data['referral_code'] ?? '';
    $profilePhoto = null;
} else {
    // FormData/multipart input (for file upload)
    $name = $_POST['name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $password = $_POST['password'] ?? '';
    $referralCode = $_POST['referral_code'] ?? '';
    $profilePhoto = $_FILES['profile_photo'] ?? null;
}

// Sanitize input
$name = sanitizeInput($name);
$phone = sanitizeInput($phone);
$password = $password; // Don't sanitize password
$referralCode = sanitizeInput($referralCode);

// Validate input
if (strlen($name) < 2) {
    sendError('Name must be at least 2 characters');
}
if (!preg_match('/^[0-9]{7,15}$/', $phone)) {
    sendError('Invalid phone number format');
}
if (strlen($password) < 6) {
    sendError('Password must be at least 6 characters');
}

try {
    $db = Db::getInstance();
    $conn = $db->getConnection();

    // Check if phone number already exists
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE phone = ?");
    $checkStmt->execute([$phone]);
    if ($checkStmt->fetch()) {
        sendError('Phone number already registered');
    }

    // Handle profile photo upload
    $profile_photo_url = "images/default-avatar.png"; // default
    if ($profilePhoto && isset($profilePhoto['tmp_name']) && $profilePhoto['error'] === UPLOAD_ERR_OK) {
        $uploadDir = "../uploads/profile/";
        if (!is_dir($uploadDir)) { mkdir($uploadDir, 0777, true); }
        $ext = pathinfo($profilePhoto['name'], PATHINFO_EXTENSION);
        $filename = uniqid("profile_") . "." . $ext;
        $target = $uploadDir . $filename;
        if (move_uploaded_file($profilePhoto['tmp_name'], $target)) {
            $profile_photo_url = "uploads/profile/" . $filename; // Web URL path
        }
    }

    // Hash password
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // Welcome bonus
    $welcome_bonus = 1000;

    // Prepare agent_code to be empty at first
    $agentCode = ''; // Will assign a unique code after insert

    // Insert new user (referral code will be recorded if provided)
    $stmt = $conn->prepare("
        INSERT INTO users (name, phone, password_hash, agent_code, referred_by, balance, profile_photo, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$name, $phone, $passwordHash, $agentCode, $referralCode, $welcome_bonus, $profile_photo_url]);
    $userId = $conn->lastInsertId();

    // Generate and assign unique agent_code for this user
    $generatedAgentCode = 'AZM' . str_pad($userId, 4, '0', STR_PAD_LEFT);
    $updateAgentStmt = $conn->prepare("UPDATE users SET agent_code = ? WHERE id = ?");
    $updateAgentStmt->execute([$generatedAgentCode, $userId]);

    // Generate token
    $token = generateToken();
    // Token expiry: 360 days
    $expiry = date('Y-m-d H:i:s', strtotime('+360 days'));

    // Store token in database
    $tokenStmt = $conn->prepare("INSERT INTO user_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
    $tokenStmt->execute([$userId, $token, $expiry]);

    // Send response
    sendResponse([
        'success' => true,
        'message' => 'Registration successful! ကြိုဆိုဘောနပ် 1000 ကျပ် ရရှိပါသည်။',
        'token' => $token,
        'user' => [
            'id' => $userId,
            'name' => $name,
            'phone' => $phone,
            'balance' => $welcome_bonus,
            'agent_code' => $generatedAgentCode,
            'profile_photo' => $profile_photo_url
        ]
    ]);

} catch (PDOException $e) {
    error_log('Registration error: ' . $e->getMessage());
    sendError('Database error', 500);
}
?>