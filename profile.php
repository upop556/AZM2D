<?php
// profile.php - API endpoint for user profile operations
header("Content-Type: application/json; charset=UTF-8");

// Allow CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Include database connection
require_once 'db.php';

// Function to verify JWT token
function verifyToken($token) {
    if (empty($token)) {
        return false;
    }
    try {
        $db = Db::getInstance();
        $conn = $db->getConnection();
        $stmt = $conn->prepare("SELECT * FROM user_tokens WHERE token = ? AND expires_at > NOW()");
        $stmt->execute([$token]);
        $tokenData = $stmt->fetch();
        if (!$tokenData) {
            return false;
        }
        return $tokenData;
    } catch (PDOException $e) {
        error_log('Token verification error: ' . $e->getMessage());
        return false;
    }
}

// Get authenticated user data (with referral bonus and referred user count)
function getAuthenticatedUser($token) {
    $tokenData = verifyToken($token);
    if (!$tokenData) {
        return null;
    }
    try {
        $db = Db::getInstance();
        $conn = $db->getConnection();
        $stmt = $conn->prepare("SELECT id, name, phone, balance, agent_code, referral_bonus, created_at, profile_photo FROM users WHERE id = ?");
        $stmt->execute([$tokenData['user_id']]);
        $user = $stmt->fetch();
        if (!$user) {
            return null;
        }
        // If profile_photo is empty, assign default avatar
        if (empty($user['profile_photo'])) {
            $user['profile_photo'] = "images/default-avatar.png";
        }
        $stmt2 = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE referred_by = ?");
        $stmt2->execute([$user['agent_code']]);
        $row = $stmt2->fetch();
        $user['referral_count'] = $row ? (int)$row['count'] : 0;
        return $user;
    } catch (PDOException $e) {
        error_log('Authentication error: ' . $e->getMessage());
        return null;
    }
}

// Helper functions for API responses
function sendResponse($data) {
    echo json_encode($data);
    exit;
}
function sendError($message, $statusCode = 400) {
    http_response_code($statusCode);
    sendResponse(['success' => false, 'message' => $message]);
}

// Get authorization header
$headers = getallheaders();
$authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';

// Accept only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Method not allowed', 405);
}

// For file uploads, use $_POST and $_FILES, otherwise use JSON
if (isset($_POST['action']) && $_POST['action'] === 'update_profile_photo') {
    $user = getAuthenticatedUser($authHeader);
    if (!$user) {
        sendError('Unauthorized access', 401);
    }
    // Validate file upload
    if (!isset($_FILES['profile_photo']) || $_FILES['profile_photo']['error'] !== UPLOAD_ERR_OK) {
        sendError('Image upload failed');
    }
    $file = $_FILES['profile_photo'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','gif','webp'];
    if (!in_array($ext, $allowed)) sendError('Unsupported image type');
    // Optionally, limit file size (e.g. 2MB)
    if ($file['size'] > 2 * 1024 * 1024) sendError('Image size too large (max 2MB)');
    // Create uploads/profile if not exists
    $uploadDir = __DIR__ . '/uploads/profile/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    $newName = 'profile_' . $user['id'] . '_' . time() . '.' . $ext;
    $destPath = $uploadDir . $newName;
    $destUrl = 'uploads/profile/' . $newName;
    if (!move_uploaded_file($file['tmp_name'], $destPath)) sendError('Failed to save image');
    // Update DB
    $db = Db::getInstance();
    $conn = $db->getConnection();
    $stmt = $conn->prepare("UPDATE users SET profile_photo = ? WHERE id = ?");
    $stmt->execute([$destUrl, $user['id']]);
    sendResponse(['success' => true, 'profile_photo' => $destUrl]);
    exit;
}

// Get JSON request data
$jsonData = file_get_contents('php://input');
$data = json_decode($jsonData, true);

// Check if we got valid JSON data
if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
    sendError('Invalid JSON data: ' . json_last_error_msg());
}

// Simple request validation
if (!isset($data['action'])) {
    sendError('Missing required parameter: action');
}

// Get user based on token
$user = getAuthenticatedUser($authHeader);

// Check if user is authenticated
if (!$user) {
    sendError('Unauthorized access', 401);
}

// Process different actions
$action = $data['action'];
switch($action) {
    case 'get_profile':
        sendResponse([
            'success' => true,
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'phone' => $user['phone'],
                'balance' => (float) $user['balance'],
                'agent_code' => $user['agent_code'],
                'referral_bonus' => isset($user['referral_bonus']) ? (float) $user['referral_bonus'] : 0,
                'referral_count' => isset($user['referral_count']) ? (int) $user['referral_count'] : 0,
                'created_at' => $user['created_at'],
                'profile_photo' => $user['profile_photo'] ?? null
            ]
        ]);
        break;
    case 'change_password':
        if (!isset($data['current_password']) || !isset($data['new_password'])) {
            sendError('Missing password parameters');
        }
        $currentPassword = $data['current_password'];
        $newPassword = $data['new_password'];
        if (strlen($newPassword) < 6) {
            sendError('New password must be at least 6 characters');
        }
        try {
            $db = Db::getInstance();
            $conn = $db->getConnection();
            $stmt = $conn->prepare("SELECT password_hash FROM users WHERE id = ?");
            $stmt->execute([$user['id']]);
            $result = $stmt->fetch();
            if (!$result) {
                sendError('User not found', 404);
            }
            if (!password_verify($currentPassword, $result['password_hash'])) {
                sendError('Current password is incorrect');
            }
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateStmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $updateStmt->execute([$newHash, $user['id']]);
            if ($updateStmt->rowCount() === 0) {
                sendError('Failed to update password', 500);
            }
            sendResponse([
                'success' => true,
                'message' => 'Password updated successfully'
            ]);
        } catch (PDOException $e) {
            error_log('Password change error: ' . $e->getMessage());
            sendError('Database error', 500);
        }
        break;
    case 'update_profile':
        if (!isset($data['name'])) {
            sendError('Missing profile update parameters');
        }
        $name = trim($data['name']);
        if (strlen($name) < 2) {
            sendError('Name must be at least 2 characters');
        }
        try {
            $db = Db::getInstance();
            $conn = $db->getConnection();
            $stmt = $conn->prepare("UPDATE users SET name = ? WHERE id = ?");
            $stmt->execute([$name, $user['id']]);
            if ($stmt->rowCount() === 0) {
                sendResponse([
                    'success' => true,
                    'message' => 'No changes made to profile'
                ]);
            }
            sendResponse([
                'success' => true,
                'message' => 'Profile updated successfully',
                'user' => [
                    'id' => $user['id'],
                    'name' => $name,
                    'phone' => $user['phone'],
                    'balance' => (float) $user['balance'],
                    'agent_code' => $user['agent_code'],
                    'referral_bonus' => isset($user['referral_bonus']) ? (float) $user['referral_bonus'] : 0,
                    'referral_count' => isset($user['referral_count']) ? (int) $user['referral_count'] : 0,
                    'created_at' => $user['created_at'],
                    'profile_photo' => $user['profile_photo'] ?? null
                ]
            ]);
        } catch (PDOException $e) {
            error_log('Profile update error: ' . $e->getMessage());
            sendError('Database error', 500);
        }
        break;
    default:
        sendError('Unknown action: ' . $action);
}
?>