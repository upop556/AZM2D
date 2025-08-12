<?php
// 2D Number UI (00-99) for multiple times, mobile UI, brake/limit control, balance in table, R button = reverse selection + balance refresh

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/referral_commission.php'; // <-- Include referral logic

// --- NEW: Time Configuration based on URL parameter ---
$time_key = $_GET['time'] ?? '11:00AM'; // Default to 11:00AM if not specified

$time_configs = [
    '11:00AM' => [
        'bet_type' => '2D-1100',
        'target_time' => '11:00:00',
        'close_time' => '10:55:00',
        'display_title' => '2D 11:00AM',
        'session_key' => 'selected2d_1100'
    ],
    '12:01PM' => [
        'bet_type' => '2D-1201',
        'target_time' => '12:01:00',
        'close_time' => '11:50:00',
        'display_title' => '2D 12:01PM',
        'session_key' => 'selected2d_1201'
    ],
    '15:00PM' => [
        'bet_type' => '2D-1500',
        'target_time' => '15:00:00',
        'close_time' => '14:55:00',
        'display_title' => '2D 03:00PM',
        'session_key' => 'selected2d_1500'
    ],
    '16:30PM' => [ // Corrected key from '4:30PM' to '16:30PM' for consistency if needed, but using user's key is better. Let's stick to 16:30PM for consistency with database time format.
        'bet_type' => '2D-1630',
        'target_time' => '16:30:00',
        'close_time' => '16:00:00',
        'display_title' => '2D 4:30PM',
        'session_key' => 'selected2d_1630'
    ]
];

if (!isset($time_configs[$time_key])) {
    die("Error: Invalid time specified in URL.");
}

$config = $time_configs[$time_key];
$bet_type = $config['bet_type'];
$target_time_str = $config['target_time'];
$close_time_str = $config['close_time'];
$display_title = $config['display_title'];
$js_session_key = $config['session_key'];
// --- END NEW CONFIGURATION ---

// --- Check Login ---
if (empty($_SESSION['user_id'])) {
    header('Location: /index.html');
    exit;
}
$current_user = $_SESSION['user_id'] ?? null;

// --- Closed session logic (Now Dynamic) ---
require_once __DIR__ . '/closed_session.php';
$closedInfo = getSessionClosedInfo('Asia/Yangon', $target_time_str, $close_time_str);
$is_betting_closed = $closedInfo['is_betting_closed'];
$target_date_dt = $closedInfo['target_date'];
$bet_date = $target_date_dt->format('Y-m-d');
$display_date_info = $closedInfo['display_date_info'];

// ---------- Advance Bet Logic (After 5PM, allow bet for tomorrow) ----------
date_default_timezone_set('Asia/Yangon');
$now_dt = new DateTime('now', new DateTimeZone('Asia/Yangon'));
$is_advance_bet = false;
$advance_cutoff_hour = 17; // 5PM

if ($is_betting_closed) {
    // If time is after 5PM (17:00), enable advance bet for tomorrow
    if ((int)$now_dt->format('H') >= $advance_cutoff_hour) {
        $tomorrow_dt = (clone $now_dt)->modify('+1 day');
        $bet_date = $tomorrow_dt->format('Y-m-d');
        $target_date_dt = $tomorrow_dt; // <-- IMPORTANT: Update target_date_dt as well
        $is_advance_bet = true;
        $is_betting_closed = false; // Allow betting for tomorrow
        $display_date_info = $bet_date . ' (မနက်ဖန်)';
    }
}

// --- NEW: Market Holiday and Weekend Check ---
$is_market_closed = false;
$market_closed_message = '';

// Add specific market holiday dates here
$holidays = [
    '2025-01-01', // New Year's Day
    '2025-02-12', // Union Day
    '2025-03-27', // Armed Forces Day
    '2025-05-01', // Labour Day
    // Add more holidays as needed in 'Y-m-d' format
];

$day_of_week = (int)$target_date_dt->format('N'); // 1 (for Monday) through 7 (for Sunday)

if ($day_of_week == 6) { // Saturday
    $is_market_closed = true;
    $market_closed_message = 'ယနေ့သည် စနေနေ့ (ပိတ်ရက်) ဖြစ်ပါသည်။';
} elseif ($day_of_week == 7) { // Sunday
    $is_market_closed = true;
    $market_closed_message = 'ယနေ့သည် တနင်္ဂနွေနေ့ (ပိတ်ရက်) ဖြစ်ပါသည်။';
} elseif (in_array($bet_date, $holidays)) {
    $is_market_closed = true;
    $market_closed_message = 'ယနေ့သည် ဒိုင်ပိတ်ရက် ဖြစ်ပါသည်။';
}
// --- END NEW ---

// --- Get User Balance ---
function getUserBalance($user_id) {
    $pdo = Db::getInstance()->getConnection();
    $stmt = $pdo->prepare('SELECT balance FROM users WHERE id = :uid');
    $stmt->execute([':uid' => $user_id]);
    $row = $stmt->fetch();
    return $row ? (int)$row['balance'] : 0;
}
$user_balance = getUserBalance($current_user);

// --- Brake Data ---
$pdo = Db::getInstance()->getConnection();
$brakes = [];
$stmt_brakes = $pdo->query('SELECT number, brake_amount FROM d_2d_brakes');
while ($row_brakes = $stmt_brakes->fetch(PDO::FETCH_ASSOC)) {
    $brakes[$row_brakes['number']] = (float)$row_brakes['brake_amount'];
}

// --- Already Bet Totals for Today or Tomorrow (for brake progress) ---
$current_totals = [];
$stmt_current = $pdo->prepare('SELECT number, SUM(amount) as total_bet FROM lottery_bets WHERE bet_type = :type AND bet_date = :bet_date GROUP BY number');
$stmt_current->execute([':type' => $bet_type, ':bet_date' => $bet_date]); // Use dynamic $bet_type
while ($row_current = $stmt_current->fetch(PDO::FETCH_ASSOC)) {
    $current_totals[$row_current['number']] = (float)$row_current['total_bet'];
}

// 2D numbers
$numbers = [];
for ($i = 0; $i <= 99; $i++) {
    $numbers[] = sprintf('%02d', $i);
}

// --- Simple API Output (JSON) ---
if (isset($_GET['api']) && $_GET['api'] == 1) {
    header('Content-Type: application/json');
    echo json_encode($numbers);
    exit;
}

// Handle AJAX request for balance refresh
if (isset($_GET['get_balance']) && $_GET['get_balance'] == 1) {
    header('Content-Type: application/json');
    echo json_encode(['balance' => $user_balance]);
    exit;
}

// --- Bet form submission (POST) ---
$message = '';
$messageType = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_bet'])) {
    $selected_numbers = $_POST['bet_numbers'] ?? [];
    $bet_amount = (int)($_POST['bet_amount'] ?? 0);

    if ($is_market_closed) { // <-- NEW: Check if market is closed
        $message = $market_closed_message;
        $messageType = 'error';
    } elseif (empty($selected_numbers)) {
        header("Location: " . $_SERVER['PHP_SELF'] . '?time=' . urlencode($time_key)); // Keep time parameter
        exit;
    } elseif ($bet_amount < 100) {
        $message = 'အနည်းဆုံး ၁၀၀ ကျပ် ထိုးရပါမည်။';
        $messageType = 'error';
    } elseif ($is_betting_closed) {
        $message = ($is_advance_bet) ? 'Advance mode error: Betting is still closed.' : 'ထိုးချိန် ပြီးသွားပါပြီ။';
        $messageType = 'error';
    } else {
        $pdo->beginTransaction();
        try {
            $total_this_bet = $bet_amount * count($selected_numbers);

            $stmt_user = $pdo->prepare('SELECT balance FROM users WHERE id = :uid FOR UPDATE');
            $stmt_user->execute([':uid' => $current_user]);
            $user = $stmt_user->fetch(PDO::FETCH_ASSOC);

            if (!$user || $user['balance'] < $total_this_bet) {
                throw new Exception('လက်ကျန်ငွေ မလုံလောက်ပါ။');
            }

            foreach ($selected_numbers as $number) {
                $brake_limit = $brakes[$number] ?? -1;
                if ($brake_limit != -1) {
                    $stmt_num_total = $pdo->prepare('SELECT SUM(amount) FROM lottery_bets WHERE bet_type = :type AND bet_date = :bet_date AND number = :number FOR UPDATE');
                    $stmt_num_total->execute([':type'=> $bet_type, ':bet_date' => $bet_date, ':number' => $number]); // Use dynamic $bet_type
                    $current_bet_total_for_num = (float)($stmt_num_total->fetchColumn() ?? 0);

                    if (($current_bet_total_for_num + $bet_amount) > $brake_limit) {
                        throw new Exception("ကွက်နံပါတ် '$number' သည် ဘရိတ်ပြည့်သွားပြီဖြစ်သည်။");
                    }
                }
            }

            $new_balance = $user['balance'] - $total_this_bet;
            $update_stmt = $pdo->prepare('UPDATE users SET balance = :balance WHERE id = :uid');
            $update_stmt->execute([':balance' => $new_balance, ':uid' => $current_user]);

            $insert_stmt = $pdo->prepare('INSERT INTO lottery_bets (user_id, bet_type, number, amount, bet_date, created_at) VALUES (:user_id, :bet_type, :number, :amount, :bet_date, NOW())');
            foreach ($selected_numbers as $number) {
                $insert_stmt->execute([':user_id' => $current_user, ':bet_type' => $bet_type, ':number' => $number, ':amount' => $bet_amount, ':bet_date' => $bet_date]); // Use dynamic $bet_type
            }

            // --- Referral Commission Logic Call ---
            $upline_commission_rate = 0.005; // 0.5%
            processReferralCommission($pdo, $current_user, $total_this_bet, $upline_commission_rate);
            // --- End Referral Commission Logic ---

            $pdo->commit();
            header("Location: " . $_SERVER['PHP_SELF'] . "?time=" . urlencode($time_key) . "&success=1" . ($is_advance_bet ? "&advance=1" : ""));
            exit();

        } catch (Exception $e) {
            $pdo->rollBack();
            $message = $e->getMessage();
            $messageType = 'error';
        }
    }
}

if(isset($_GET['success'])) {
    $message = 'ထိုးပြီးပါပြီ။ သင်၏ ၂လုံးထိုးမှတ်တမ်းကို အောင်မြင်စွာ သိမ်းဆည်းပြီးပါပြီ။';
    $messageType = 'success';
}
?>
<!DOCTYPE html>
<html lang="my">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, maximum-scale=1.0">
    <title><?= htmlspecialchars($display_title) ?> - Bet</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
       body {
            background-image: url('https://global2d.com/images/bg-main.jpg');
            background-size: cover;
            background-repeat: no-repeat;
            background-position: center center;
            background-attachment: fixed;
            min-height: 100vh;
            margin: 0;
            padding: 0;
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }

        /* === UPDATED HEADER STYLE === */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 15px;
            background: linear-gradient(to bottom, #fdd835, #f9a825);
            color: #4e342e;
            border-bottom: 2px solid #f57f17;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            position: sticky;
            top: 0;
            z-index: 1100;
        }
        .header-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.25em; /* Adjusted size */
            font-weight: 700;
            color: inherit; /* Inherit color from .header */
        }
        .header-logo {
            height: 32px;
            width: 32px;
        }
        .header-right {
            display: flex;
            align-items: center;
        }
        /* === UPDATED BACK BUTTON STYLE === */
        .back-btn {
            display: flex;
            align-items: center;
            color: #fff;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 8px;
            background: #4e342e;
            font-size: 1em;
            font-weight: 600;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            transition: background 0.2s;
            border: none;
        }
        .back-btn:hover { background: #3e2723; }
        .back-btn i { margin-right: 7px; }

        /* Sticky balance panel */
        .balance-panel {
            position: sticky;
            top: 62px; /* Adjusted for sticky header height */
            z-index: 1050;
            margin: 0 auto;
            max-width: 440px;
            background-color: #fdd835;
            color: #4e342e;
            border-radius: 13px;
            box-shadow: 0 4px 15px #ffd70066;
            padding: 15px 20px;
            font-size: 1.13em;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            letter-spacing: 0.02em;
            border: 1px solid #f9a825;
            margin-bottom: 10px;
        }
        .container {
            max-width: 440px;
            margin: 0 auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            padding: 20px;
            margin-top: 10px; /* Adjusted */
        }

        /* Sticky action button group */
        .action-btn-group {
            position: sticky;
            top: 128px; /* header + balance panel height */
            z-index: 1000;
            background: #fff;
            padding: 10px 0;
            display: flex;
            justify-content: center;
            gap: 12px;
            margin-bottom: 15px;
            border-bottom: 1px solid #f0f0f0;
        }

        .balance-panel .bi-wallet2 { font-size: 1.3em; margin-right: 10px; }
        .balance-panel .balance-amount {
            font-size: 1.18em;
            font-weight: 700;
            padding-right: 7px;
        }
        .balance-panel .refresh-btn {
            background: #4e342e;
            color: #fff;
            border-radius: 6px;
            border: none;
            font-size: 1em;
            font-weight: 600;
            padding: 3px 8px;
            min-width: 32px;
            min-height: 32px;
            margin-left: 10px;
            cursor: pointer;
            box-shadow: 0 2px 8px #00000028;
            transition: background 0.18s;
        }
        .balance-panel .refresh-btn:hover {
            background: #3e2723;
        }

        .quick-select-btn {
            background: #fffbe6;
            color: #c09000;
            border-radius: 7px;
            border: 1px solid #f9a825;
            font-weight: 600;
            padding: 5px 13px;
            font-size: 1em;
            cursor: pointer;
            transition: background 0.18s, color 0.18s;
        }
        .quick-select-btn:hover {
            background: #ffe266;
            color: #b47b00;
        }
        .bet-form-btn-table {
            background: #f57f17;
            color: #fff;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.18s;
            box-shadow: 0 2px 8px #f57f1733;
            line-height: 1.1;
            min-width: 55px;
            min-height: 32px;
            border-radius: 7px;
            padding: 6px 13px;
            font-size: 0.98em;
        }
        .bet-form-btn-table:hover { background: #ef6c00; }
        .numbers-title {
            font-size: 1.13em;
            color: #f57f17;
            font-weight: bold;
            margin-bottom: 10px;
            text-align: center;
            letter-spacing: 1px;
        }
        .numbers-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 14px 8px;
            margin-top: 8px;
        }
        .number-item {
            background: #fffbe6;
            color: #c09000;
            border: 2px solid #fdd835;
            border-radius: 10px;
            font-size: 1.15em;
            font-weight: 700;
            padding: 12px 0;
            text-align: center;
            user-select: none;
            box-shadow: 0 1px 6px #ffd70008;
            transition: background 0.2s, color 0.2s, box-shadow 0.2s, border 0.2s;
            cursor: pointer;
            outline: none;
            position: relative;
        }
        .number-item.selected {
            background: #f57f17;
            color: #fff;
            border: 2px solid #ef6c00;
            box-shadow: 0 3px 16px #f57f1745;
        }
        .number-item.disabled {
            background: #bdc3c7 !important;
            color: #7f8c8d !important;
            cursor: not-allowed !important;
            border: 2px solid #bdc3c7 !important;
            pointer-events: none;
            opacity: 0.75;
        }
        .number-item:active {
            background: #ef6c00;
            color: #fff;
        }
        .brake-progress-bar {
            width: 85%;
            height: 5px;
            background-color: #e9ecef;
            border-radius: 3px;
            margin: 4px auto 0 auto;
            overflow: hidden;
        }
        .brake-progress-fill {
            height: 100%;
            border-radius: 3px;
            transition: width 0.3s ease-in-out;
        }
        .brake-progress-fill.fill-low { background-color: #2ecc71; }
        .brake-progress-fill.fill-medium { background-color: #f39c12; }
        .brake-progress-fill.fill-high { background-color: #e74c3c; }

        .modal-confirm-bg {
            position: fixed; z-index: 20000; left: 0; top: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5); backdrop-filter: blur(4px);
            display: none; align-items: center; justify-content: center;
        }
        .modal-confirm-bg.active { display: flex; }
        .modal-confirm-box {
            background: #fff; border-radius: 18px; box-shadow: 0 12px 32px #0002;
            max-width: 94vw; min-width: 270px; width: 96vw; max-width: 410px;
            padding: 22px 17px 14px 17px; position: relative;
            animation: showpop .18s cubic-bezier(.43,1.2,.86,1.2);
        }
        @keyframes showpop { from { transform: scale(0.97) translateY(18px); opacity: 0.2; } to { transform: scale(1) translateY(0); opacity: 1; } }
        .modal-confirm-title { font-weight: bold; font-size: 1.13em; color: #4e342e; margin-bottom: 12px; }
        .modal-confirm-list { max-height: 180px; overflow-y: auto; margin: 10px 0 17px 0; background: #fffbe6; border-radius: 9px; padding: 9px 8px 7px 8px; font-size: 1.02em; }
        .modal-confirm-list span { display: inline-block; background: #f57f17; color: #fff; border-radius: 7px; padding: 4px 9px 3px 9px; margin: 2px 4px 2px 0; font-size: 1.02em; }
        .modal-confirm-label { margin: 9px 0 2px 0; font-weight: 600; font-size: .99em; color: #555; }
        .modal-confirm-row { display: flex; align-items: center; gap: 8px; margin-bottom: 10px; }
        .modal-confirm-row input[type="number"] { width: 100px; padding: 7px 5px; border: 1px solid #ccc; border-radius: 6px; font-size: 1.09em; }
        .modal-confirm-total { font-weight: bold; color: #f57f17; margin-bottom: 10px; }
        .modal-confirm-actions { margin-top: 8px; display: flex; justify-content: flex-end; gap: 12px; }
        .modal-confirm-btn { padding: 7px 19px; border-radius: 8px; font-size: 1.08em; font-weight: 600; border: none; cursor: pointer; }
        .modal-confirm-btn.confirm { background: #4e342e; color: #fff; }
        .modal-confirm-btn.cancel { background: #eee; color: #1a222d; }
        .modal-confirm-close { position: absolute; right: 11px; top: 7px; color: #999; font-size: 1.4em; background: none; border: none; cursor: pointer; }
        .modal-confirm-close:hover { color: #4e342e;}

        dialog { border: none; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); padding: 15px; min-width: 140px; width: 90vw; max-width: 340px; text-align: left; box-sizing: border-box; z-index: 21000; margin: auto; }
        dialog[open] { animation: showpop .18s cubic-bezier(.43,1.2,.86,1.2); }
        dialog::backdrop { background: rgba(0, 0, 0, 0.5); }
        .popup-header { font-size: 16px; font-weight: bold; margin: 0 0 10px 0; text-align: center; }
        .popup-label { font-size: 13px; margin-top: 8px; display: block; color: #333; }
        .popup-input { width: 100%; padding: 6px 8px; font-size: 13px; border-radius: 5px; border: 1px solid #ccc; margin-top: 4px; box-sizing: border-box; }
        .popup-btn-group { display: flex; flex-wrap: wrap; gap: 6px; margin: 12px 0; }
        .popup-btn { flex: 1 1 auto; color: white; border: none; padding: 7px 10px; border-radius: 6px; cursor: pointer; font-size: 13px; transition: opacity 0.2s, box-shadow 0.2s; }
        .popup-btn.selected { opacity: 0.7; box-shadow: 0 0 0 2px #333 inset; }
        .popup-footer { margin-top: 15px; text-align: center; display: flex; gap: 10px; }
        .popup-footer button { width: 100%; font-size: 14px; padding: 8px 0; }
        .btn-blue { background: #007bff; } .btn-alt { background: #28a745; } .btn-orange { background: #fd7e14; } .btn-info { background: #17a2b8; } .btn-gray { background: #6c757d; }

        .market-closed-message { text-align: center; padding: 40px 20px; font-size: 1.2em; font-weight: bold; color: #e74c3c; background-color: #f8d7da; border: 1px solid #f5c6cb; border-radius: 8px; margin: 20px; }

        @media (max-width: 600px) {
            .container { max-width: 100vw; border-radius: 0; padding: 10px; margin-top: 5px; box-shadow: none; }
            .balance-panel { top: 57px; max-width: 100vw; padding: 12px 10px; font-size: 1em; }
            .numbers-grid { grid-template-columns: repeat(5, 1fr); gap: 8px 4px; }
            .number-item { font-size: 1em; padding: 8px 0; border-radius: 6px; }
            .header { padding: 10px; }
            .header-title { font-size: 1.1em; }
            .header-logo { height: 28px; width: 28px; }
            .back-btn { padding: 6px 12px; font-size: 0.9em; }
            .action-btn-group { top: 112px; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-title">
            <img src="/images/glo-logo.png" alt="Logo" class="header-logo" />
            <?= htmlspecialchars($display_title) ?>
        </div>
        <div class="header-right">
            <a href="/2dlive/" class="back-btn"><i class="bi bi-arrow-left"></i> နောက်သို့</a>
        </div>
    </div>
    <div class="balance-panel">
        <i class="bi bi-wallet2"></i>
        လက်ကျန်ငွေ: <span class="balance-amount" id="Balance"><?= number_format($user_balance) ?></span> ကျပ်
        <button class="refresh-btn" type="button" id="refreshBtn" title="Add Reversed Numbers & Refresh Balance">R</button>
    </div>
    <div class="container">
        <?php if ($is_market_closed): ?>
            <div class="market-closed-message">
                <i class="bi bi-calendar-x" style="font-size: 1.5em; margin-bottom: 10px; display: block;"></i>
                <?= htmlspecialchars($market_closed_message) ?>
            </div>
        <?php elseif ($is_betting_closed && !$is_advance_bet): ?>
             <div class="market-closed-message">
                <i class="bi bi-clock-history" style="font-size: 1.5em; margin-bottom: 10px; display: block;"></i>
                <?= htmlspecialchars($display_title) ?> အတွက် ထိုးချိန် ပိတ်သွားပါပြီ။
            </div>
        <?php else: ?>
            <div class="action-btn-group">
                <button type="button" id="betTableTrigger" class="bet-form-btn-table"><?= $is_advance_bet ? 'မနက်ဖန်အတွက် ထိုးမည်' : 'ထိုးမည်' ?></button>
                <button type="button" id="showPopupButton" class="quick-select-btn"><i class="bi bi-lightning-charge"></i> အမြန်ရွေး</button>
            </div>
            
            <?php if ($is_advance_bet): ?>
            <div style="text-align:center; color:#e67e22; font-weight:bold; margin-bottom:10px;">
                မနက်ဖန် (<?= htmlspecialchars($bet_date) ?>) အတွက် ကြိုထိုး mode ဖြစ်သည်။
            </div>
            <?php endif; ?>
            
            <?php if ($message): ?>
            <div class="message <?= htmlspecialchars($messageType) ?>" style="text-align:center; padding: 10px; border-radius: 5px; margin-bottom: 15px; background-color: <?= $messageType === 'success' ? '#d4edda' : '#f8d7da' ?>; color: <?= $messageType === 'success' ? '#155724' : '#721c24' ?>;">
                <?= htmlspecialchars($message) ?>
            </div>
            <?php endif; ?>

            <form method="post" id="betFormFinal" style="display:none;">
                </form>
            
            <div class="numbers-grid" id="numbersGrid">
                <?php foreach ($numbers as $num): 
                    $brake_limit = $brakes[$num] ?? -1;
                    $current_total = $current_totals[$num] ?? 0;
                    $is_brake_full = ($brake_limit != -1 && $current_total >= $brake_limit);
                    $class = $is_brake_full ? 'disabled' : '';
                    $progress_bar_html = '';
                    if ($brake_limit > 0) {
                        $percentage = ($current_total / $brake_limit) * 100;
                        if ($percentage > 100) $percentage = 100;
                        $fill_class = 'fill-low';
                        if ($percentage >= 90) $fill_class = 'fill-high';
                        elseif ($percentage >= 50) $fill_class = 'fill-medium';
                        $progress_bar_html = "
                            <div class='brake-progress-bar' title='ဘရိတ်: " . number_format($brake_limit) . " | လက်ရှိ: " . number_format($current_total) . "'>
                                <div class='brake-progress-fill " . $fill_class . "' style='width: " . $percentage . "%;'></div>
                            </div>
                        ";
                    }
                ?>
                    <div class="number-item <?= $class ?>" data-number="<?= htmlspecialchars($num) ?>" tabindex="0">
                        <?= htmlspecialchars($num) ?>
                        <?= $progress_bar_html ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="modal-confirm-bg" id="modalConfirmBg">
        <div class="modal-confirm-box">
            <button type="button" class="modal-confirm-close" id="modalConfirmClose" tabindex="0">&times;</button>
            <div class="modal-confirm-title"><?= $is_advance_bet ? 'မနက်ဖန်အတွက် ကြိုထိုး အတည်ပြုရန်' : 'ထိုးမည် အတည်ပြုရန်' ?></div>
            <div class="modal-confirm-label">ရွေးချယ်ထားသော နံပါတ်များ</div>
            <div class="modal-confirm-list" id="modalConfirmNumbers"></div>
            <div class="modal-confirm-row" style="margin-bottom:4px;">
                <div class="modal-confirm-label" style="margin-bottom:0;">တစ်ကွက်လျှင် ထိုးငွေ</div>
                <input type="number" min="100" value="100" id="modalConfirmAmount" style="margin-left:7px; font-size:1.05em;">
            </div>
            <div class="modal-confirm-total" id="modalConfirmTotal"></div>
            <div class="modal-confirm-label">ဆုကြေး (၉၅ ဆ): <span id="modalConfirmPrize"></span> ကျပ်</div>
            <div class="modal-confirm-actions">
                <button type="button" class="modal-confirm-btn cancel" id="modalConfirmCancel">မလုပ်ပါ</button>
                <button type="button" class="modal-confirm-btn confirm" id="modalConfirmSubmit">အတည်ပြု</button>
            </div>
        </div>
    </div>
    
    <dialog id="choiceDialog">
        <h3 class="popup-header">အမြန်ရွေး</h3>
        <form id="choiceForm" method="dialog">
            <label class="popup-label" for="topInput">ထိပ်:</label>
            <input type="text" id="topInput" class="popup-input" placeholder="ဥပမာ 1,2">

            <label class="popup-label" for="backInput">နောက်:</label>
            <input type="text" id="backInput" class="popup-input" placeholder="ဥပမာ 3,4">

            <label class="popup-label" for="brakeInput">ဘရိတ်:</label>
            <input type="text" id="brakeInput" class="popup-input" placeholder="ဥပမာ 1,11">

            <label class="popup-label" for="singleInput">တစ်လုံး:</label>
            <input type="text" id="singleInput" class="popup-input" placeholder="ဥပမာ 1,5">

            <label class="popup-label" for="caseInput">အခွေ:</label>
            <input type="text" id="caseInput" class="popup-input" placeholder="ဥပမာ 789">

            <div class="popup-btn-group" id="choiceBtnGroup">
                <button type="button" class="popup-btn btn-alt" data-choice="ညီအကို">ညီအကို</button>
                <button type="button" class="popup-btn btn-orange" data-choice="ပါဝါ">ပါဝါ</button>
                <button type="button" class="popup-btn btn-info" data-choice="နက္ခ">နက္ခ</button>
                <button type="button" class="popup-btn btn-gray" data-choice="စုံစုံ">စုံစုံ</button>
                <button type="button" class="popup-btn btn-pink" data-choice="မမ">မမ</button>
                <button type="button" class="popup-btn btn-dark" data-choice="စုံမ">စုံမ</button>
                <button type="button" class="popup-btn" data-choice="မစုံ" style="background:#6f42c1">မစုံ</button>
                <button type="button" class="popup-btn" data-choice="အပူး" style="background:#dc3545">အပူး</button>
            </div>

            <div class="popup-footer">
                <button type="submit" class="popup-btn btn-blue">ရွေးမည်</button>
                <button type="button" id="closePopupButton" class="popup-btn btn-gray">ပိတ်မည်</button>
            </div>
        </form>
    </dialog>

<script>
// --- 2D Number Selection Logic ---
const sessionKey = '<?= $js_session_key ?>';

window.addEventListener('load', function() {
    sessionStorage.removeItem(sessionKey);
    selected = {};
    updateGridSelections();
});

const numbersGrid = document.getElementById('numbersGrid');
let selected = JSON.parse(sessionStorage.getItem(sessionKey) || '{}');

function updateGridSelections() {
    const grid = document.getElementById('numbersGrid');
    if (!grid) return;

    grid.querySelectorAll('.number-item').forEach(item => {
        const num = item.getAttribute('data-number');
        if (item.classList.contains('disabled')) {
            item.classList.remove('selected');
            delete selected[num];
        } else if (selected[num]) {
            item.classList.add('selected');
        } else {
            item.classList.remove('selected');
        }
    });
    sessionStorage.setItem(sessionKey, JSON.stringify(selected));
}

if(numbersGrid){
    numbersGrid.addEventListener('click', function(e) {
        const item = e.target.closest('.number-item');
        if (!item || item.classList.contains('disabled')) return;
        const num = item.getAttribute('data-number');
        if (selected[num]) {
            delete selected[num];
        } else {
            selected[num] = true;
        }
        updateGridSelections();
    });

    numbersGrid.addEventListener('keydown', function(e) {
        if ((e.key === "Enter" || e.key === " ") && e.target.classList.contains('number-item')) {
            e.preventDefault();
            e.target.click();
        }
    });
}

// =========================================================================
// == MODIFIED SECTION: 'R' Button to ADD reversed numbers ==
// =========================================================================
document.getElementById('refreshBtn').addEventListener('click', function() {
    // Part 1: Refresh the balance from the server
    const url = new URL(window.location.href);
    url.searchParams.set('get_balance', '1');

    fetch(url)
        .then(r => r.json())
        .then(data => {
            if (typeof data.balance !== 'undefined') {
                document.getElementById('Balance').textContent = Number(data.balance).toLocaleString();
            }
        });
        
    // Part 2: Add reversed numbers to the current selection
    
    // Get a list of the currently selected numbers before we start adding new ones
    const originalSelection = Object.keys(selected);

    originalSelection.forEach(num => {
        if (num.length === 2) {
            const reversedNum = num[1] + num[0]; // e.g., '54' becomes '45'

            // If the number is a double (e.g., '11'), no need to do anything
            if (num === reversedNum) {
                return; // Skips to the next number in the loop
            }

            const reversedElement = document.querySelector(`.number-item[data-number="${reversedNum}"]`);

            // Check if the reversed number's element exists and is NOT disabled
            if (reversedElement && !reversedElement.classList.contains('disabled')) {
                // Add the reversed number to the selection object.
                // The original number remains selected.
                selected[reversedNum] = true; 
            }
        }
    });
    
    // Update the visual grid to show both original and reversed numbers
    updateGridSelections();
});
// =========================================================================
// == END OF MODIFIED SECTION ==
// =========================================================================


// ---- Confirm Popup Logic ----
const modalBg = document.getElementById('modalConfirmBg');
const modalClose = document.getElementById('modalConfirmClose');
const modalCancel = document.getElementById('modalConfirmCancel');
const modalSubmit = document.getElementById('modalConfirmSubmit');
const modalNumbers = document.getElementById('modalConfirmNumbers');
const modalAmount = document.getElementById('modalConfirmAmount');
const modalTotal = document.getElementById('modalConfirmTotal');
const modalPrize = document.getElementById('modalConfirmPrize');
const betTableTrigger = document.getElementById('betTableTrigger');
const betFormFinal = document.getElementById('betFormFinal');

function showModal(selectedNumbers, betAmount) {
    if (!selectedNumbers.length) return;
    modalNumbers.innerHTML = "";
    selectedNumbers.forEach(num => {
        let span = document.createElement('span');
        span.textContent = num;
        modalNumbers.appendChild(span);
    });
    modalAmount.value = betAmount;
    updatePrizeAndTotal();
    modalBg.classList.add('active');
    modalAmount.focus();
}

function updatePrizeAndTotal() {
    let numCount = modalNumbers.querySelectorAll('span').length;
    let amount = parseInt(modalAmount.value) || 0;
    let total = numCount * amount;
    let prize = amount * 95;
    modalTotal.textContent = `စုစုပေါင်း: ${numCount.toLocaleString()} ကွက် × ${amount.toLocaleString()} = ${(total).toLocaleString()} ကျပ်`;
    modalPrize.textContent = prize.toLocaleString();
}

if(modalAmount) modalAmount.addEventListener('input', updatePrizeAndTotal);

if(modalClose && modalCancel) {
    modalClose.onclick = modalCancel.onclick = function() {
        modalBg.classList.remove('active');
    };
}

if(betTableTrigger){
    betTableTrigger.addEventListener('click', function(e) {
        let nums = Object.keys(selected).filter(num => {
            const btn = document.querySelector(`.number-item[data-number="${num}"]`);
            return btn && !btn.classList.contains('disabled');
        });
        if (nums.length === 0) return;
        showModal(nums.sort(), 100);
    });
}

if(modalSubmit) {
    modalSubmit.addEventListener('click', function() {
        let nums = [];
        modalNumbers.querySelectorAll('span').forEach(span => {
            nums.push(span.textContent);
        });
        let amount = parseInt(modalAmount.value) || 0;

        if (amount < 100) {
            alert('အနည်းဆုံး ၁၀၀ ကျပ် ထိုးရပါမည်။');
            return;
        }

        betFormFinal.innerHTML = '';
        
        let amountInput = document.createElement('input');
        amountInput.type = 'hidden';
        amountInput.name = 'bet_amount';
        amountInput.value = amount;
        betFormFinal.appendChild(amountInput);

        nums.forEach(val => {
            let n = document.createElement('input');
            n.type = 'hidden';
            n.name = 'bet_numbers[]';
            n.value = val;
            betFormFinal.appendChild(n);
        });
        
        let btn = document.createElement('input');
        btn.type = 'hidden';
        btn.name = 'submit_bet';
        btn.value = "1";
        betFormFinal.appendChild(btn);

        modalBg.classList.remove('active');
        betFormFinal.submit();
    });
}

window.addEventListener('keydown', function(e) {
    if (modalBg && modalBg.classList.contains('active') && (e.key === "Escape")) {
        modalBg.classList.remove('active');
    }
});


// --- Quick Select Popup Logic ---
document.addEventListener('DOMContentLoaded', () => {
    const dialog = document.getElementById('choiceDialog');
    const showButton = document.getElementById('showPopupButton');
    
    if (!showButton) return; 

    const closeButton = document.getElementById('closePopupButton');
    const choiceForm = document.getElementById('choiceForm');
    const choiceBtnGroup = document.getElementById('choiceBtnGroup');

    const numberSets = {
        'ညီအကို': ['01','12','23','34','45','56','67','78','89','90', '10','21','32','43','54','65','76','87','98','09'],
        'ပါဝါ': ['05','16','27','38','49', '50','61','72','83','94'],
        'နက္ခ': ['07','18','29','35','46', '70','81','92','53','64'],
        'စုံစုံ': Array.from({length:100}, (_,i)=>i.toString().padStart(2,'0')).filter(n=>n[0]%2==0 && n[1]%2==0),
        'မမ': Array.from({length:100}, (_,i)=>i.toString().padStart(2,'0')).filter(n=>n[0]%2!=0 && n[1]%2!=0),
        'စုံမ': Array.from({length:100}, (_,i)=>i.toString().padStart(2,'0')).filter(n=>n[0]%2==0 && n[1]%2!=0),
        'မစုံ': Array.from({length:100}, (_,i)=>i.toString().padStart(2,'0')).filter(n=>n[0]%2!=0 && n[1]%2==0),
        'အပူး': ['00','11','22','33','44','55','66','77','88','99']
    };

    const getNumbersFromInput = (input) => input.value.trim().split(',').map(s => s.trim().replace(/\D/g, '')).filter(Boolean);

    showButton.addEventListener('click', () => {
        choiceForm.reset();
        choiceBtnGroup.querySelectorAll('.popup-btn').forEach(btn => btn.classList.remove('selected'));
        dialog.showModal();
    });

    closeButton.addEventListener('click', () => dialog.close());
    
    choiceBtnGroup.addEventListener('click', (event) => {
        if (event.target.matches('.popup-btn')) event.target.classList.toggle('selected');
    });

    choiceForm.addEventListener('submit', (event) => {
        event.preventDefault();
        
        let finalNumbers = new Set();
        let activeFilters = [];

        // Collect all active filters
        choiceBtnGroup.querySelectorAll('.popup-btn.selected').forEach(btn => activeFilters.push({type: 'set', value: btn.dataset.choice}));
        if (document.getElementById('topInput').value) activeFilters.push({type: 'top', value: getNumbersFromInput(document.getElementById('topInput'))});
        if (document.getElementById('backInput').value) activeFilters.push({type: 'back', value: getNumbersFromInput(document.getElementById('backInput'))});
        if (document.getElementById('brakeInput').value) activeFilters.push({type: 'brake', value: getNumbersFromInput(document.getElementById('brakeInput'))});
        if (document.getElementById('singleInput').value) activeFilters.push({type: 'single', value: getNumbersFromInput(document.getElementById('singleInput'))});
        if (document.getElementById('caseInput').value) activeFilters.push({type: 'case', value: getNumbersFromInput(document.getElementById('caseInput'))});

        if (activeFilters.length === 0) {
            alert("အနည်းဆုံး filter တစ်ခု ရွေးချယ်ပါ");
            return;
        }

        let initialSet = new Set(Array.from({length: 100}, (_, i) => i.toString().padStart(2, '0')));
        
        activeFilters.forEach(filter => {
            let tempSet = new Set();
            switch (filter.type) {
                case 'set':
                    if (numberSets[filter.value]) numberSets[filter.value].forEach(num => tempSet.add(num));
                    break;
                case 'top':
                    filter.value.forEach(t => {
                        for (let i=0; i<=9; i++) tempSet.add(`${t}${i}`.slice(-2));
                    });
                    break;
                case 'back':
                     filter.value.forEach(b => {
                        for (let i=0; i<=9; i++) tempSet.add(`${i}${b}`.slice(-2));
                    });
                    break;
                case 'brake':
                    for (let i=0; i<=99; i++) {
                        const n = i.toString().padStart(2,'0');
                        const sum = parseInt(n[0]) + parseInt(n[1]);
                        if (filter.value.includes(sum.toString()) || filter.value.includes((sum % 10).toString())) tempSet.add(n);
                    }
                    break;
                case 'single':
                     for (let i=0; i<=99; i++) {
                        const n = i.toString().padStart(2,'0');
                        if (filter.value.some(s => n.includes(s))) tempSet.add(n);
                    }
                    break;
                case 'case':
                    filter.value.forEach(c_str => {
                        const digits = c_str.split('');
                        digits.forEach(d1 => digits.forEach(d2 => tempSet.add(`${d1}${d2}`)));
                    });
                    break;
            }
             initialSet = new Set([...initialSet].filter(x => tempSet.has(x)));
        });

        selected = {}; // Clear previous selections
        initialSet.forEach(numStr => {
            const item = document.querySelector(`.number-item[data-number="${numStr}"]`);
            if (item && !item.classList.contains('disabled')) {
                selected[numStr] = true;
            }
        });

        updateGridSelections();
        dialog.close();
    });
});

// Initial update on page load
updateGridSelections();
</script>
</body>
</html>