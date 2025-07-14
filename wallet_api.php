<?php
// --- CORS HEADERS for cross-domain AJAX ---
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/config.php';

function api_response($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function empty_response($error_msg) {
    return [
        'success' => false,
        'balance' => 0,
        'wallets' => [],
        'deposit_history' => [],
        'withdraw_history' => [],
        'all_history' => [],
        'note' => '',
        'admin_phones' => [],
        'error' => $error_msg
    ];
}

// --- Support both POST and GET (for query string compatibility) ---
$phone = trim($_POST['phone'] ?? $_REQUEST['phone'] ?? '');
if (!$phone) {
    api_response(empty_response('Phone required!'), 400);
}

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    api_response(empty_response('DB error!'), 500);
}

// Get user
$stmt = $conn->prepare("SELECT id, balance FROM users WHERE phone=? LIMIT 1");
if (!$stmt) {
    api_response(empty_response('DB prepare error!'), 500);
}
$stmt->bind_param("s", $phone);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
if (!$user) {
    api_response(empty_response('User not found!'), 404);
}
$user_id = $user['id'];

// ---- Handle deposit ----
if (isset($_POST['action']) && $_POST['action'] === 'deposit') {
    $method = $_POST['method'] ?? '';
    $amount = floatval($_POST['amount'] ?? 0);
    $txid = trim($_POST['txid'] ?? '');
    $created_at = date('Y-m-d H:i:s');
    $screenshot_path = '';

    // Basic validation
    if ($amount < 1000) {
        api_response(['success'=>false, 'error'=>'ငွေသွင်းပမာဏ အနည်းဆုံး ၁၀၀၀ ကျပ် ထည့်ရပါမည်။']);
    }
    if (empty($txid) && (empty($_FILES['screenshot']['name']) || !is_uploaded_file($_FILES['screenshot']['tmp_name']))) {
        api_response(['success'=>false, 'error'=>'Screenshot သို့မဟုတ် လုပ်ငန်းစဉ်နောက် ၆ လုံး တစ်ခုခု တင်ပါ။']);
    }

    // Check if txid already used (only if txid is not empty)
    if (!empty($txid)) {
        $check_txid = $conn->prepare("SELECT id FROM transactions WHERE note = ? AND type = 'deposit' LIMIT 1");
        if ($check_txid) {
            $check_txid->bind_param("s", $txid);
            $check_txid->execute();
            $check_txid->store_result();
            if ($check_txid->num_rows > 0) {
                api_response(['success'=>false, 'error'=>'ဤလုပ်ငန်းစဉ်နံပါတ်ကို သုံးပြီးသားဖြစ်ပါသည်။']);
            }
            $check_txid->close();
        }
    }

    // Handle screenshot upload (optional)
    if (!empty($_FILES['screenshot']['name']) && is_uploaded_file($_FILES['screenshot']['tmp_name'])) {
        $updir = __DIR__ . '/uploads/';
        if (!is_dir($updir)) mkdir($updir, 0777, true);
        $ext = strtolower(pathinfo($_FILES['screenshot']['name'], PATHINFO_EXTENSION));
        $fname = 'dep_' . $user_id . '_' . time() . '.' . $ext;
        $fpath = $updir . $fname;
        if (move_uploaded_file($_FILES['screenshot']['tmp_name'], $fpath)) {
            $screenshot_path = 'uploads/' . $fname;
        } else {
            api_response(['success'=>false, 'error'=>'Screenshot upload fail!']);
        }
    }

    // Insert to DB: status REQUIRED, txid stored in note if you want
    $note = $txid;
    $status = 'pending';
    $to_phone = '';
    $q = $conn->prepare("INSERT INTO transactions (user_id, amount, method, type, status, note, screenshot, to_phone, created_at) VALUES (?, ?, ?, 'deposit', ?, ?, ?, ?, ?)");
    if (!$q) api_response(['success'=>false, 'error'=>'DB Prepare error: '.$conn->error]);
    $q->bind_param("idssssss", $user_id, $amount, $method, $status, $note, $screenshot_path, $to_phone, $created_at);
    if ($q->execute()) {
        // Referral Bonus logic REMOVED here! Deposit action does not give referral bonus.
        api_response(['success'=>true, 'message'=>'ငွေသွင်းတင်ပြပြီးပါပြီ။']);
    } else {
        api_response(['success'=>false, 'error'=>'DB error!']);
    }
}

// ---- Handle withdraw ----
if (isset($_POST['action']) && $_POST['action'] === 'withdraw') {
    $method = $_POST['method'] ?? '';
    $amount = floatval($_POST['amount'] ?? 0);
    $to_phone = trim($_POST['to_phone'] ?? $_POST['phone'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $created_at = date('Y-m-d H:i:s');

    // Basic validation
    if ($amount < 9000) {
        api_response(['success'=>false, 'error'=>'ငွေထုတ်ပမာဏ အနည်းဆုံး ၉၀၀၀ ကျပ် ထုတ်ယူရပါမည်။']);
    }
    if (!preg_match('/^09\d{7,14}$/', $to_phone)) {
        api_response(['success'=>false, 'error'=>'ငွေလက်ခံမည့် ဖုန်းနံပါတ် မှန်ကန်စွာ ထည့်ပါ။']);
    }
    if (strlen($password) < 4) {
        api_response(['success'=>false, 'error'=>'Password မှန်ကန်စွာ ထည့်ပါ။']);
    }

    // (Optional: check user password here if needed)
    // If you want to check password, fetch and verify here.

    // Check if user has enough balance
    if ($user['balance'] < $amount) {
        api_response(['success'=>false, 'error'=>'လက်ကျန်ငွေ မလုံလောက်ပါ။']);
    }

    $conn->begin_transaction();

    // 1. User balance နုတ်
    $stmt_balance = $conn->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
    $stmt_balance->bind_param("di", $amount, $user_id);
    if (!$stmt_balance->execute()) {
        $conn->rollback();
        api_response(['success'=>false, 'error'=>'Balance update error!']);
    }
    $stmt_balance->close();

    // 2. Transaction record ထည့်
    $status = 'pending';
    $note = '';
    $screenshot_path = '';
    $q = $conn->prepare("INSERT INTO transactions (user_id, amount, method, type, status, note, screenshot, to_phone, created_at) VALUES (?, ?, ?, 'withdrawal', ?, ?, ?, ?, ?)");
    if (!$q) {
        $conn->rollback();
        api_response(['success'=>false, 'error'=>'DB Prepare error: '.$conn->error]);
    }
    $q->bind_param("idssssss", $user_id, $amount, $method, $status, $note, $screenshot_path, $to_phone, $created_at);
    if ($q->execute()) {
        $conn->commit();
        api_response(['success'=>true, 'message'=>'ငွေထုတ်တင်ပြပြီးပါပြီ။']);
    } else {
        $conn->rollback();
        api_response(['success'=>false, 'error'=>'DB error!']);
    }
}

// -------- Return wallet info as before (default) ---------

// 2. Get wallets (Kpay, Wave etc)
$wallets = [];
$result = $conn->query("SELECT id, name, logo, history_icon FROM wallets WHERE active=1 ORDER BY id ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $wallets[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'logo' => $row['logo'],
            'history_icon' => $row['history_icon'],
        ];
    }
}

// 2b. Get Admin Phones for Kpay/Wave from bank_accounts table (show_user=1)
$admin_phones = [
    'kpay' => '',
    'wave' => ''
];
$res = $conn->query("SELECT bank_type, account_number FROM bank_accounts WHERE show_user=1");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $type = strtolower($row['bank_type']);
        if ($type === 'kpay' && empty($admin_phones['kpay'])) {
            $admin_phones['kpay'] = (string)$row['account_number'];
        }
        if ($type === 'wave' && empty($admin_phones['wave'])) {
            $admin_phones['wave'] = (string)$row['account_number'];
        }
    }
}

// 3. Deposit/Withdraw (latest 3 each)
$deposit = [];
$withdraw = [];
$q1 = $conn->prepare("SELECT amount, method, created_at, icon FROM transactions WHERE user_id=? AND type='deposit' ORDER BY id DESC LIMIT 3");
if ($q1) {
    $q1->bind_param("i", $user_id);
    $q1->execute();
    $res = $q1->get_result();
    while ($row = $res->fetch_assoc()) {
        if (empty($row['icon'])) {
            $row['icon'] = 'https://amazemm.xyz/images/history.png';
        }
        $deposit[] = $row;
    }
    $q1->close();
}

$q2 = $conn->prepare("SELECT amount, method, created_at, icon FROM transactions WHERE user_id=? AND type='withdrawal' ORDER BY id DESC LIMIT 3");
if ($q2) {
    $q2->bind_param("i", $user_id);
    $q2->execute();
    $res = $q2->get_result();
    while ($row = $res->fetch_assoc()) {
        if (empty($row['icon'])) {
            $row['icon'] = 'https://amazemm.xyz/images/history.png';
        }
        $withdraw[] = $row;
    }
    $q2->close();
}

// 3b. All History (latest 10, both deposit/withdraw, for "History" tab)
$all_history = [];
$q3 = $conn->prepare("SELECT amount, method, type, created_at, icon FROM transactions WHERE user_id=? ORDER BY id DESC LIMIT 10");
if ($q3) {
    $q3->bind_param("i", $user_id);
    $q3->execute();
    $res = $q3->get_result();
    while ($row = $res->fetch_assoc()) {
        if (empty($row['icon'])) {
            if ($row['type'] === 'deposit') {
                $row['icon'] = 'https://amazemm.xyz/images/deposit.png';
            } elseif ($row['type'] === 'withdrawal') {
                $row['icon'] = 'https://amazemm.xyz/images/withdraw.png';
            } else {
                $row['icon'] = 'https://amazemm.xyz/images/history.png';
            }
        }
        $all_history[] = $row;
    }
    $q3->close();
}

// 4. Dai Note
$note = '';
$res3 = $conn->query("SELECT value FROM settings WHERE `key`='wallet_note' LIMIT 1");
if ($res3 && $row = $res3->fetch_assoc()) {
    $note = $row['value'];
}

$stmt->close();
$conn->close();

api_response([
    'success' => true,
    'balance' => floatval($user['balance']),
    'wallets' => $wallets,
    'deposit_history' => $deposit,
    'withdraw_history' => $withdraw,
    'all_history' => $all_history,
    'note' => $note,
    'admin_phones' => $admin_phones,
    'error' => ''
]);