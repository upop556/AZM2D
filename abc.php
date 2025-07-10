<?php  
// 11am_2d.php - 11AM only version (no Global Brake) - Adds blood bar for individual brake (သွေးအားတန်း)
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// ----------- User balance fetch from main site -----------
$user_balance = 0;
if (!empty($_SESSION['user_id'])) {
    if (!file_exists(__DIR__ . '/db.php')) {
        die('db.php not found!');
    }
    require_once __DIR__ . '/db.php';
    if (!class_exists('Db')) {
        die('Db class not found in db.php!');
    }
    function getUserBalance($user_id) {
        $pdo = Db::getInstance()->getConnection();
        $stmt = $pdo->prepare('SELECT balance FROM users WHERE id = :uid');
        $stmt->execute([':uid' => $user_id]);
        $row = $stmt->fetch();
        return $row ? (int)$row['balance'] : 0;
    }
    $user_balance = getUserBalance($_SESSION['user_id']);
} else {
    $user_balance = 0;
}

// --- Session Details ---
$time = '11:00:00';
$display_time = '11:00 AM';
$session_time_key = '11:00';

// --- Myanmar Time Detection ---
$mm_timezone = new DateTimeZone('Asia/Yangon');
$dt_mm = new DateTime('now', $mm_timezone);
$current_time_mm = $dt_mm->format('H:i:s');

// --- Target Date and Display Logic ---
$display_date_info = '';
if ($current_time_mm >= '17:00:00') {
    $target_date_dt = (new DateTime('now', $mm_timezone))->modify('+1 day');
    $display_date_info = ' (' . $target_date_dt->format('Y-m-d') . ')';
} else {
    $target_date_dt = new DateTime('now', $mm_timezone);
}
$target_day_of_week = $target_date_dt->format('w'); // 0=Sun, 6=Sat

// --- Session closing time for 11 AM session ---
$closing_time = '10:55:00';

// --- Check if betting is closed ---
$is_betting_day_weekend = ($target_day_of_week == 0 || $target_day_of_week == 6);
$is_past_closing_time_for_today = ($dt_mm->format('Y-m-d') === $target_date_dt->format('Y-m-d')) && ($current_time_mm >= $closing_time);
$is_betting_closed = $is_betting_day_weekend || $is_past_closing_time_for_today;

// --- Get return page (defaults to index) ---
$returnPage = $_GET['return'] ?? 'b/2dnumber.php';

// Create an array of numbers from 0 to 99 with leading zeros  
$numbers = [];
for ($i = 0; $i < 100; $i++) {  
    $numbers[] = str_pad($i, 2, '0', STR_PAD_LEFT);
}
$tops = range(0,9);
$bottoms = range(0,9);
$apuArr = ['00','11','22','33','44','55','66','77','88','99'];
$pawaArr = ['05','50','16','61','27','72','38','83','49','94'];
$nakhaArr = ['18','81','24','42','35','53','69','96','70','07'];

// --- Get closed numbers from database ---
$closed_numbers = [];
$closed_top_digits = [];
try {
    if (class_exists('Db')) {
        $pdo = Db::getInstance()->getConnection();
        $target_date_str = $target_date_dt->format('Y-m-d');
        $stmt = $pdo->prepare("SELECT number FROM closed_numbers WHERE session_time = ? AND close_date = ?");
        $stmt->execute([$session_time_key, $target_date_str]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($result as $row) {
            $closed_numbers[] = $row['number'];
        }
        $stmt = $pdo->prepare("SELECT digit FROM closed_top_digits WHERE session_time = ? AND close_date = ?");
        $stmt->execute([$session_time_key, $target_date_str]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($result as $row) {
            $closed_top_digits[] = $row['digit'];
        }
    }
} catch (PDOException $e) {
    // Silent fail - just continue with empty closed arrays
}

// Create a list of all numbers affected by closed top digits
$affected_by_top = [];
foreach ($closed_top_digits as $digit) {
    for ($i = 0; $i < 10; $i++) {
        $affected_by_top[] = $digit . $i;
    }
}

// --- Individual Brake/Limit Logic (NO Global Brake) ---
$brakes = [];
$current_totals = [];
try {
    if (class_exists('Db')) {
        // Individual brakes
        $stmt_brakes = $pdo->query('SELECT number, brake_amount FROM d_2d_brakes');
        while ($row = $stmt_brakes->fetch()) {
            $brakes[$row['number']] = (float)$row['brake_amount'];
        }

        // Current bet totals for today
        $bet_date_formatted = $target_date_dt->format('Y-m-d');
        $stmt_current = $pdo->prepare('SELECT number, SUM(amount) as total_bet FROM two_d_bets WHERE bet_date = :bet_date GROUP BY number');
        $stmt_current->execute([':bet_date' => $bet_date_formatted]);
        while ($row = $stmt_current->fetch()) {
            $current_totals[$row['number']] = (float)$row['total_bet'];
        }
    }
} catch (PDOException $e) {
    // ignore
}

function progressBarClass($percent) {
    if ($percent >= 90) return 'progress-high';
    if ($percent >= 50) return 'progress-medium';
    return 'progress-low';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>2D ထိုးရန် - <?= htmlspecialchars($display_time) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
body {
    background-image: url('https://amazemm.xyz/images/bg-main.jpg');
    background-size: cover;
    background-repeat: no-repeat;
    background-position: center center;
    background-attachment: fixed;
    font-family: 'Segoe UI', Arial, sans-serif;
    margin: 0;
    padding: 0;
    color: #333;
}
.main-header {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    height: 80px;
    background: linear-gradient(90deg, #1877f2 60%, #1153a6 120%);
    color: #fff;
    z-index: 1100;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 32px;
    border-bottom-left-radius: 18px;
    border-bottom-right-radius: 18px;
    box-shadow: 0 6px 32px rgba(24,119,242,0.13), 0 1.5px 0 #e2e7ef;
    transition: box-shadow 0.18s, height 0.18s;
}
.main-header .header-title {
    font-family: 'Poppins', 'Noto Sans Myanmar', sans-serif;
    font-size: 2.1em;
    font-weight: 700;
    color: #fff;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    display: flex;
    align-items: center;
    gap: 16px;
}
.main-header .header-logo {
    height: 44px;
    width: 44px;
    border-radius: 10px;
    box-shadow: 0 2px 8px #0002;
    background: #fff5;
    object-fit: contain;
    margin-right: 12px;
}
.main-header .header-right {
    display: flex;
    align-items: center;
    height: 100%;
}
.main-header .back-btn {
    display: flex;
    align-items: center;
    color: #1976d2;
    text-decoration: none;
    padding: 8px 15px;
    border-radius: 8px;
    background: #fff;
    font-size: 1.09em;
    font-weight: 600;
    margin-left: 20px;
    margin-right: 0;
    box-shadow: 0 2px 8px #1877f218;
    transition: background 0.18s;
    border: none;
    cursor: pointer;
}
.main-header .back-btn:hover { background: #e3f2fd; }
.main-header .back-btn i { margin-right: 6px; }

.container {
    max-width: 340px;
    margin: 90px auto 0 auto;
    background: #fff;
    border-radius: 18px;
    padding: 18px 8px 18px 8px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.06);
}
h2 {
    text-align: center;
    margin-top: 6px;
    margin-bottom: 10px;
    font-size: 1.2em;
    letter-spacing: 1px;
    color: #222;
}
.time-display {
    text-align: center;
    color: #1976d2;
    font-weight: bold;
    margin: 4px 0 10px 0;
    font-size: 1.1em;
}
.content-area {
    margin-top: 50px;
}
.balance-bar {
    margin: 0 auto 14px auto;
    max-width: 280px;
    display: flex;
    justify-content: flex-end;
    align-items: center;
    background: none;
    border-radius: 0;
    border: none;
    padding: 0;
    font-size: 1.03em;
}
.balance-text {
    color: #bb2222;
    font-weight: bold;
    letter-spacing: 0.02em;
}
.balance-icon {
    margin-right: 7px;
    color: #1976d2;
    font-size: 1.15em;
}
.fastpick-bar {
    margin: 0 auto 10px auto;
    max-width: 300px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}
.fastpick-btn {
    min-width: 70px;
    padding: 5px 14px;
    border: none;
    border-radius: 8px;
    background: #e0e7ef;
    color: #244;
    font-size: 1em;
    font-weight: bold;
    cursor: pointer;
    transition: background 0.13s, color 0.13s;
}
.fastpick-btn.selected,
.fastpick-btn:active {
    background: #4da8ff;
    color: #fff;
}
.bet-controls {
    display: flex;
    align-items: center;
    gap: 8px;
}
.amount-input {
    font-size: 1.02em;
    width: 70px;
    padding: 5px 8px;
    border-radius: 6px;
    border: 1px solid #b7b7b7;
    outline: none;
    background: #f2f5fa;
}
.amount-input:focus {
    border-color: #4da8ff;
    background: #fff;
}
.bet-btn {
    background: linear-gradient(to right, #ff9a44, #ff5e62);
    color: #fff;
    border: none;
    border-radius: 25px;
    font-size: 1em;
    font-weight: bold;
    padding: 10px 20px;
    cursor: pointer;
    transition: background 0.13s;
    width: 100%;
    max-width: 200px;
    margin: 0 auto;
    display: block;
}
.bet-btn:active,
.bet-btn:hover {
    background: linear-gradient(to right, #ff7f00, #e94a36);
}
.control-panel {
    background: #f8f8f8;
    border-radius: 15px;
    padding: 15px;
    margin-bottom: 20px;
    color: #333;
    position: relative;
}
.panel-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}
.panel-label {
    font-weight: bold;
    color: #333;
}
.panel-time {
    color: #1976d2;
    font-size: 1.2em;
    font-weight: bold;
}
.panel-wallet {
    display: flex;
    align-items: center;
}
.wallet-icon {
    background: linear-gradient(to right, #ff9a44, #ff5e62);
    padding: 8px 12px;
    border-radius: 10px;
    margin-right: 10px;
    color: white;
}
.info-icon {
    position: absolute;
    top: 15px;
    right: 15px;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: #1976d2;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-style: normal;
    font-weight: bold;
}

/* Popup */
.popup-mask {
    position: fixed;
    left: 0;
    top: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 9999;
    display: none;
}
.popup-dialog {
    position: fixed;
    left: 0;
    right: 0;
    top: 60px;
    margin: auto;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 6px 36px rgba(60,60,80,0.13);
    max-width: 250px;
    width: 95vw;
    z-index: 10000;
    padding: 12px 5px 10px 5px;
    display: none;
    font-size: 0.97em;
}
.popup-dialog h3 {
    margin-top: 0;
    margin-bottom: 10px;
    font-size: 1em;
    color: #274472;
    letter-spacing: 0.01em;
}
.popup-dialog .popup-content {
    font-size: 1.01em;
    margin-bottom: 10px;
    word-break: break-all;
}
.quickpick-row,
.quickpick-inline-row {
    display: flex;
    gap: 4px;
    justify-content: center;
    margin-bottom: 7px;
    margin-top: -3px;
    flex-wrap: wrap;
}
.quickpick-btn {
    padding: 2px 6px;
    border-radius: 5px;
    border: none;
    background: #e0e7ef;
    color: #23395d;
    font-weight: bold;
    cursor: pointer;
    transition: background 0.13s, color 0.13s;
    font-size: 0.93em;
}
.quickpick-btn:hover,
.quickpick-btn:active,
.quickpick-btn.selected {
    background: #4da8ff;
    color: #fff;
}
.quickpick-section-label {
    font-weight: bold;
    font-size: 0.98em;
    margin: 10px 0 2px 0;
    display: block;
}
.top-pick-row,
.bottom-pick-row {
    display: flex;
    gap: 4px;
    justify-content: center;
    margin-bottom: 7px;
    flex-wrap: wrap;
}
.top-pick-btn,
.bottom-pick-btn {
    min-width: 20px;
    padding: 2px 0;
    font-size: 1em;
    font-weight: bold;
    background: #f1f6ff;
    color: #23395d;
    border: 1px solid #bcd2f7;
    border-radius: 5px;
    cursor: pointer;
    transition: background 0.13s, color 0.13s, border-color 0.13s;
}
.top-pick-btn.selected,
.top-pick-btn:active,
.bottom-pick-btn.selected,
.bottom-pick-btn:active {
    background: #4da8ff;
    color: #fff;
    border-color: #2666a3;
}
.popup-dialog .popup-close {
    display: inline-block;
    margin-top: 4px;
    padding: 3px 12px;
    background: #eee;
    color: #222;
    border: none;
    border-radius: 6px;
    font-size: 0.98em;
    font-weight: bold;
    cursor: pointer;
    transition: background 0.13s;
}
.popup-dialog .popup-close:hover,
.popup-dialog .popup-close:active {
    background: #e0e7ef;
}
.popup-actions-row {
    display: flex;
    gap: 7px;
    justify-content: center;
    margin-top: 2px;
    margin-bottom: 5px;
}
.popup-action-btn {
    padding: 2px 8px;
    border-radius: 6px;
    border: none;
    background: #e0e7ef;
    color: #23395d;
    font-weight: bold;
    cursor: pointer;
    transition: background 0.13s, color 0.13s;
    font-size: 0.98em;
}
.popup-action-btn:hover,
.popup-action-btn:active {
    background: #4da8ff;
    color: #fff;
}
.bet-list-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 7px;
    margin-bottom: 18px;
}
.bet-list-table th,
.bet-list-table td {
    text-align: center;
    font-size: 1.04em;
    padding: 5px 5px;
}
.bet-list-table th {
    font-weight: bold;
    color: #274472;
    background: #f6faff;
}
.bet-list-table td .edit-amt {
    width: 60px;
    font-size: 1em;
    border: 1px solid #b5c7e5;
    border-radius: 4px;
    padding: 2px 5px;
}
.bet-list-table td .del-btn {
    color: #bb2222;
    background: #fff0f0;
    border: 1px solid #fdc0c0;
    border-radius: 5px;
    padding: 2px 8px;
    font-size: 0.98em;
    cursor: pointer;
}
.bet-list-table td .del-btn:hover {
    background: #ffecec;
}
.bet-summary-row {
    font-weight: bold;
    background: #f8f8f8;
}
.numbers-grid {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 10px;
    margin: 0 auto;
    max-width: 350px;
}
.number-cell {
    position: relative;
    background: rgba(79, 70, 112, 0.7);
    border-radius: 15px;
    aspect-ratio: 1/1;
    display: flex;
    justify-content: center;
    align-items: center;
    font-size: 1.3em;
    font-weight: bold;
    color: white;
    cursor: pointer;
    overflow: hidden;
}
.number-cell.selected {
    box-shadow: 0 0 0 2px #1976d2;
}
.number-cell.closed {
    background: rgba(50, 50, 50, 0.8);
    color: rgba(255, 255, 255, 0.5);
    cursor: not-allowed;
}
.number-cell.closed-by-admin {
    background: rgba(122, 0, 0, 0.8);
    color: rgba(255, 255, 255, 0.5);
    cursor: not-allowed;
}
.number-cell.closed-by-top {
    background: rgba(100, 0, 100, 0.8);
    color: rgba(255, 255, 255, 0.5);
    cursor: not-allowed;
}
.closed-notice {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    font-size: 0.5em;
    background: rgba(0,0,0,0.5);
    color: white;
    text-align: center;
    padding: 2px 0;
}
/* ========== FIXED: Progress Bar for Brake/Limit ========== */
.progress-indicator {
    position: absolute;
    left: 0;
    bottom: 0;
    height: 8px;
    background: linear-gradient(to right, #ffae42, #1976d2);
    border-radius: 0 0 15px 15px;
    z-index: 2;
    opacity: 0.7;
    transition: width 0.2s;
}
.progress-low    { background: linear-gradient(to right, #a7e1ff, #1976d2); }
.progress-medium { background: linear-gradient(to right, #ffecb3, #ffae42); }
.progress-high   { background: linear-gradient(to right, #ffd6d6, #e74c3c); }

.legend-info-popup {
    position: fixed;
    left: 0;
    right: 0;
    top: 60px;
    margin: auto;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 6px 36px rgba(60,60,80,0.13);
    max-width: 250px;
    width: 95vw;
    z-index: 10001;
    padding: 12px 5px 10px 5px;
    display: none;
    font-size: 0.97em;
}
.legend-info-title {
    font-size: 1.1em;
    font-weight: bold;
    margin-bottom: 12px;
    color: #274472;
    text-align: center;
}
.legend-info-content {
    margin-bottom: 16px;
    line-height: 1.6;
}
.legend-info-close {
    display: block;
    width: 100%;
    padding: 7px;
    background: #e0e7ef;
    border: none;
    border-radius: 7px;
    color: #23395d;
    font-weight: bold;
    cursor: pointer;
    transition: background 0.13s;
}
.legend-info-close:hover {
    background: #4da8ff;
    color: #fff;
}
.number-cell.disabled {
    background: #ddd !important;
    color: #bbb !important;
    border-color: #eee !important;
    cursor: not-allowed !important;
}

@media (max-width: 600px) {
    .main-header {
        height: 56px;
        padding: 0 10px;
        border-bottom-left-radius: 11px;
        border-bottom-right-radius: 11px;
    }
    .main-header .header-title {
        font-size: 1.15em;
        gap: 7px;
    }
    .main-header .header-logo {
        height: 28px;
        width: 28px;
    }
    .container {
        margin-top: 66px;
    }
}
@media (max-width: 500px) {
    .container { max-width: 99vw; }
    .numbers-grid { gap: 6px; }
    .number-cell { font-size: 1.1em; }
    .content-area { margin-top: 45px; }
    .popup-dialog, .legend-info-popup { max-width: 98vw; }
}
.blood-bar-wrap {
    width: 90%;
    height: 7px;
    background: #ececec;
    border-radius: 4px;
    margin: 0 auto 3px auto;
    position: absolute;
    bottom: 5px;
    left: 5%;
    right: 5%;
    z-index: 6;
    overflow: hidden;
    /* display: flex; align-items: center; */
}
.blood-bar {
    height: 100%;
    border-radius: 4px;
    background: linear-gradient(90deg,#f00 0%,#ffb700 100%);
    transition: width 0.19s cubic-bezier(.65,0,.35,1);
}
.blood-bar-label {
    position: absolute;
    left: 50%;
    top: -14px;
    transform: translateX(-50%);
    color: #c00;
    font-size: 0.7em;
    font-weight: bold;
    letter-spacing: 0.01em;
    text-shadow: 0 0 2px #fff8;
    z-index: 7;
    pointer-events: none;
}
    </style>
</head>
<body>
    <!-- Consistent Main UI Header -->
    <div class="main-header" id="main-header">
        <div class="header-title">
            <img src="/images/azm-logo.png" alt="Logo" class="header-logo" />
            AZM2D Game
        </div>
        <div class="header-right">
            <button class="back-btn" onclick="history.back()"><i class="bi bi-arrow-left"></i> Back</button>
        </div>
    </div>
    <div class="container">
        <div class="content-area">
            <h2>2D ထိုးရန်</h2>
            <div class="time-display"><?= htmlspecialchars($display_time . $display_date_info) ?> အတွက်</div>
            <div class="balance-bar">
                <span class="balance-icon"><i class="bi bi-wallet2"></i></span>
                <span class="balance-text"><?= number_format($user_balance) ?> ကျပ်</span>
            </div>
            <div class="control-panel">
                <div class="info-icon" id="legend-info-btn">i</div>
                <div class="panel-row">
                    <button class="fastpick-btn" id="fastpick-quick">အမြန်ရွေး</button>
                    <button class="fastpick-btn" id="quickpick-r" title="ပြောင်းပြန် (Reverse)"><span style="font-weight:bold;">R</span></button>
                    <input type="number" min="100" step="100" value="100" id="amount-input" class="amount-input" placeholder="ကျပ် 100" />
                </div>
                <div class="panel-row">
                    <div class="panel-label">ထိုးချိန်</div>
                    <div class="panel-time"><?= htmlspecialchars($display_time) ?></div>
                </div>
                <div class="panel-row">
                    <div class="panel-wallet">
                        <div class="wallet-icon">
                            <i class="bi bi-wallet2"></i>
                        </div>
                        <div><?= number_format($user_balance) ?> ကျပ်</div>
                    </div>
                    <button class="bet-btn" id="bet-btn">ထိုးမည်</button>
                </div>
            </div>
            <div class="numbers-grid">
                <?php foreach (range(0, 99) as $i): 
                    $num = str_pad($i, 2, '0', STR_PAD_LEFT);
                    // Existing closed logic
                    $isClosedByAdmin = in_array($num, $closed_numbers);
                    $isClosedByTop = in_array($num, $affected_by_top);
                    $isClosedByBetting = $is_betting_closed || $isClosedByAdmin || $isClosedByTop;

                    // --- Individual brake logic ---
                    $brake_limit = $brakes[$num] ?? -1;
                    $current_total = $current_totals[$num] ?? 0;
                    $is_individual_brake_full = ($brake_limit != -1 && $current_total >= $brake_limit);

                    $percent = ($brake_limit > 0) ? min(100, round(100 * $current_total / $brake_limit)) : 0;
                    $progressClass = progressBarClass($percent);

                    $isDisabled = $is_individual_brake_full || $isClosedByBetting;
                    $class = '';
                    if ($isDisabled) $class .= ' disabled';
                    if ($isClosedByAdmin) $class .= ' closed-by-admin';
                    else if ($isClosedByTop) $class .= ' closed-by-top';
                    else if ($is_betting_closed) $class .= ' closed';
                ?>
                    <div class="number-cell<?= $class ?>" data-num="<?= $num ?>" <?= $isDisabled ? 'data-disabled="1"' : '' ?>>
                        <?= $num ?>
                        <?php if ($is_individual_brake_full): ?>
                            <div class="closed-notice">Individual Brake</div>
                        <?php elseif ($is_betting_closed): ?>
                            <div class="closed-notice">ပိတ်</div>
                        <?php elseif ($isClosedByAdmin): ?>
                            <div class="closed-notice">ပိတ်</div>
                        <?php elseif ($isClosedByTop): ?>
                            <div class="closed-notice">ထိပ်စီးပိတ်</div>
                        <?php endif; ?>
                        <?php if ($brake_limit > 0): ?>
                            <div class="blood-bar-wrap" title="သွေးအားတန်း: <?= number_format($current_total) ?> / <?= number_format($brake_limit) ?>">
                                <div class="blood-bar" style="width:<?= $percent ?>%;"></div>
                                <?php if (!$isDisabled && $brake_limit > 0): ?>
                                  <div class="blood-bar-label"><?= $current_total ?>/<?= $brake_limit ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<div class="popup-mask" id="popup-mask"></div>
    <div class="popup-dialog" id="popup-dialog">
        <h3>အမြန်​ရွေး ခုလုပ်</h3>
        <div class="popup-content" id="popup-content">
            <div class="quickpick-row">
                <button class="quickpick-btn" data-quick="even-even">စုံစုံ</button>
                <button class="quickpick-btn" data-quick="odd-odd">မမ</button>
                <button class="quickpick-btn" data-quick="even-odd">စုံမ</button>
                <button class="quickpick-btn" data-quick="apu">အပူး</button>
                <button class="quickpick-btn" data-quick="pawa">ပါဝါ</button>
                <button class="quickpick-btn" data-quick="nakha">နက္ခ</button>
                <button class="quickpick-btn" data-quick="even-top">စုံထိပ်</button>
                <button class="quickpick-btn" data-quick="odd-top">မထိပ်</button>
            </div>
            <span class="quickpick-section-label">အခွေ (Bottom) စနစ်</span>
            <div class="quickpick-inline-row" id="quick-bottom-row">
                <?php foreach ($bottoms as $b): ?>
                    <button class="quickpick-btn quick-bottom-btn" data-bottom="<?= $b ?>"><?= $b ?></button>
                <?php endforeach; ?>
                <button class="quickpick-btn" id="quick-bottom-clear" style="background:#ffecec;color:#bb2222;">Clear</button>
                <button class="quickpick-btn" id="quick-bottom-apply" style="background:#e2ffe2;color:#007500;">Apply</button>
            </div>
            <span class="quickpick-section-label">Bk (၂လုံးပေါင်းခြင်း)</span>
            <div class="quickpick-inline-row" id="quick-bk-row">
                <?php foreach ($tops as $t): ?>
                    <button class="quickpick-btn quick-bk-btn" data-comb="<?= $t ?>"><?= $t ?></button>
                <?php endforeach; ?>
                <?php for ($t = 10; $t <= 18; $t++): ?>
                    <button class="quickpick-btn quick-bk-btn" data-comb="<?= $t ?>"><?= $t ?></button>
                <?php endfor; ?>
                <button class="quickpick-btn" id="quick-bk-clear" style="background:#ffecec;color:#bb2222;">Clear</button>
                <button class="quickpick-btn" id="quick-bk-apply" style="background:#e2ffe2;color:#007500;">Apply</button>
            </div>
            <div style="margin-bottom:10px;"></div>
            <div style="font-size:0.99em;margin-bottom:2px;">ထိပ် (Top)</div>
            <div class="top-pick-row">
                <?php foreach ($tops as $t): ?>
                    <button class="top-pick-btn" data-top="<?= $t ?>"><?= $t ?></button>
                <?php endforeach; ?>
            </div>
            <div style="font-size:0.99em;margin-bottom:2px;">နောက် (Bottom)</div>
            <div class="bottom-pick-row">
                <?php foreach ($bottoms as $b): ?>
                    <button class="bottom-pick-btn" data-bottom="<?= $b ?>"><?= $b ?></button>
                <?php endforeach; ?>
            </div>
            <div class="popup-actions-row">
                <button class="popup-action-btn" id="top-pick-clear">Clear</button>
                <button class="popup-action-btn" id="top-pick-apply">ထည့်သွင်းရန်</button>
            </div>
        </div>
        <button class="popup-close" id="popup-close">ပယ်ဖြတ်မည်</button>
    </div>
    <!-- Bet List Popup -->
    <div class="popup-mask" id="bet-popup-mask"></div>
    <div class="popup-dialog" id="bet-popup-dialog">
        <h3>ထိုးရန် စာရင်း</h3>
        <div class="popup-content" id="bet-popup-content">
            <table class="bet-list-table" id="bet-list-table">
                <thead>
                    <tr>
                        <th>နံပါတ်</th>
                        <th>ထိုးကြေး</th>
                        <th>ဆ</th>
                        <th>ဖျက်</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Fills with JS -->
                </tbody>
                <tfoot>
                    <tr class="bet-summary-row">
                        <td colspan="1">စုစုပေါင်း</td>
                        <td id="bet-popup-total-amount"></td>
                        <td id="bet-popup-total-bet"></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
            <div style="margin-bottom:8px;text-align:right;">
                <span style="color:#1976d2;font-size:1.05em;">
                    <i class="bi bi-wallet2"></i> လက်ကျန်: <?= number_format($user_balance) ?> ကျပ်
                </span>
            </div>
            <div class="popup-actions-row">
                <button class="popup-action-btn" id="bet-popup-cancel">ပယ်ဖြတ်မည်</button>
                <button class="popup-action-btn" id="bet-popup-submit" style="background:#0bc66b;color:#fff;">အတည်ပြု</button>
            </div>
        </div>
    </div>
    <!-- Legend Info Popup -->
    <div class="popup-mask" id="legend-info-mask"></div>
    <div class="legend-info-popup" id="legend-info-popup">
        <div class="legend-info-title">ရှင်းလင်းချက်</div>
        <div class="legend-info-content">
            <p>ဂဏန်းထိုး အရောင်အခြေအနေများ မပါဝင်တော့ပါ။</p>
            <ul>
                <li><strong style="color:#444;">မီးခိုးရင့်ရောင်</strong> - ထိုးငွေပြည့်သွားပါပြီ</li>
                <li><strong style="color:#7a0000;">အနီရင့်ရောင်</strong> - ဒိုင်ပိတ်ကွက်</li>
                <li><strong style="color:#640064;">ခရမ်းရောင်</strong> - ထိပ်စီးပိတ်</li>
            </ul>
            <p><strong>R</strong> ခလုပ်သည် ရွေးထားသော နံပါတ်များ၏ ပြောင်းပြန် နံပါတ်များကိုပါ ထပ်မံထည့်သွင်းပေးပါသည်။</p>
        </div>
        <button class="legend-info-close" id="legend-info-close">ပိတ်မည်</button>
    </div>
 <script>
    window.sessionTime = "11:00:00";
    window.returnPage = "<?= htmlspecialchars($returnPage) ?>";
    window.isBettingClosed = <?= $is_betting_closed ? 'true' : 'false' ?>;
    window.bettingDate = "<?= $target_date_dt->format('Y-m-d') ?>";
 </script>
 <script>
// --- GLOBALS SETUP ---
window.sessionTime = window.sessionTime || "";
window.returnPage = window.returnPage || "";
window.isBettingClosed = (window.isBettingClosed === true || window.isBettingClosed === 'true');
window.bettingDate = window.bettingDate || "";

// --- NUMBER CELL SELECTION LOGIC ---
const numberCells = document.querySelectorAll('.number-cell');
numberCells.forEach(cell => {
    cell.addEventListener('click', function() {
        if (window.isBettingClosed || cell.classList.contains('closed') || cell.classList.contains('closed-by-admin') || cell.classList.contains('closed-by-top')) {
           return;
        }
        cell.classList.toggle('selected');
    });
});
// --- AMOUNT INPUT VALIDATION ---
const amtInput = document.getElementById('amount-input');
if (amtInput) {
    amtInput.addEventListener('input', function() {
        let val = parseInt(amtInput.value, 10);
        if (isNaN(val) || val < 0) {
            amtInput.value = '100';
        }
    });
}
// --- LEGEND INFO POPUP LOGIC ---
function showLegendInfo() {
    document.getElementById('legend-info-mask').style.display = 'block';
    document.getElementById('legend-info-popup').style.display = 'block';
}
function hideLegendInfo() {
    document.getElementById('legend-info-mask').style.display = 'none';
    document.getElementById('legend-info-popup').style.display = 'none';
}
document.getElementById('legend-info-btn').onclick = showLegendInfo;
document.getElementById('legend-info-close').onclick = hideLegendInfo;
document.getElementById('legend-info-mask').onclick = hideLegendInfo;
// --- BET POPUP LOGIC ---
function showBetPopup() {
    if (window.isBettingClosed) {
        alert('Betting is closed for this session.');
        return;
    }
    const selCells = Array.from(document.querySelectorAll('.number-cell.selected'));
    if (selCells.length === 0) {
        alert('ထိုးရန် နံပါတ်ရွေးပါ။');
        return;
    }
    let defaultAmt = parseInt(document.getElementById('amount-input').value, 10);
    if (isNaN(defaultAmt) || defaultAmt < 0) defaultAmt = 100;
    window.betList = selCells.map(cell => ({
        num: cell.getAttribute('data-num'),
        amt: defaultAmt
    }));
    renderBetListTable();
    document.getElementById('bet-popup-mask').style.display = 'block';
    document.getElementById('bet-popup-dialog').style.display = 'block';
}
function hideBetPopup() {
    document.getElementById('bet-popup-mask').style.display = 'none';
    document.getElementById('bet-popup-dialog').style.display = 'none';
}
document.getElementById('bet-btn').onclick = showBetPopup;
document.getElementById('bet-popup-cancel').onclick = hideBetPopup;
document.getElementById('bet-popup-mask').onclick = hideBetPopup;
// --- BET TABLE RENDER + EDIT LOGIC ---
function renderBetListTable() {
    const MULTIPLIER = 95;
    const tbody = document.querySelector('#bet-list-table tbody');
    tbody.innerHTML = '';
    let totalBet = 0, totalAmt = 0;
    window.betList.forEach((bet, idx) => {
        totalBet++;
        totalAmt += parseInt(bet.amt, 10);
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${bet.num}</td>
            <td>
                <input type="number" min="0" value="${bet.amt}" class="edit-amt" data-idx="${idx}" />
            </td>
            <td>${MULTIPLIER}</td>
            <td><button class="del-btn" data-idx="${idx}">ဖျက်</button></td>
        `;
        tbody.appendChild(tr);
    });
    document.getElementById('bet-popup-total-amount').innerText = totalAmt + ' ကျပ်';
    document.getElementById('bet-popup-total-bet').innerText = totalBet + ' ကွက်';
    // --- Inline editing ---
    tbody.querySelectorAll('.edit-amt').forEach(inp => {
        inp.addEventListener('input', function() {
            let val = parseInt(inp.value, 10);
            if (isNaN(val) || val < 0) val = 0;
            inp.value = val;
            window.betList[inp.getAttribute('data-idx')].amt = val;
            renderBetListTable();
        });
    });
    // --- Delete logic ---
    tbody.querySelectorAll('.del-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            window.betList.splice(btn.getAttribute('data-idx'), 1);
            renderBetListTable();
            if (window.betList.length === 0) {
                hideBetPopup();
            }
        });
    });
}
document.getElementById('bet-popup-submit').onclick = function() {
    const timeValue = window.sessionTime || '';
    if (!timeValue) {
        alert('Session time မဖြည့်ရသေးပါ။');
        return;
    }
    if (!window.betList || !Array.isArray(window.betList) || window.betList.length === 0) {
        alert('ထိုးရန် နံပါတ် မရွေးရသေးပါ။');
        return;
    }
    fetch('bet_submit.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            session_time: timeValue,
            bet_date: window.bettingDate,
            bets: window.betList
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('ထိုးခြင်း အောင်မြင်ပါသည်!');
            hideBetPopup();
            location.reload();
        } else {
            alert(data.message || 'Bet failed!');
        }
    })
    .catch(e => {
        alert("Network or server error: " + e);
    });
};
// --- FASTPICK LOGIC + R BUTTON (Reverse) ---
const fastpickBtn = document.getElementById('fastpick-quick');
const popupMask = document.getElementById('popup-mask');
const popupDialog = document.getElementById('popup-dialog');
const popupClose = document.getElementById('popup-close');
const numberCellSelector = '.number-cell:not(.closed):not(.closed-by-admin):not(.closed-by-top)';
const allNumCells = () => Array.from(document.querySelectorAll(numberCellSelector));
fastpickBtn.onclick = function() {
    if(window.isBettingClosed) return;
    popupMask.style.display = 'block';
    popupDialog.style.display = 'block';
};
popupMask.onclick = popupClose.onclick = function() {
    popupMask.style.display = 'none';
    popupDialog.style.display = 'none';
};
function selectNumbersBy(criteriaCb) {
    allNumCells().forEach(cell => {
        const num = cell.getAttribute('data-num');
        if (criteriaCb(num)) {
            cell.classList.add('selected');
        } else {
            cell.classList.remove('selected');
        }
    });
}
function addNumbersBy(criteriaCb) {
    allNumCells().forEach(cell => {
        const num = cell.getAttribute('data-num');
        if (criteriaCb(num)) {
            cell.classList.add('selected');
        }
    });
}
document.querySelectorAll('.quickpick-btn[data-quick]').forEach(btn => {
    btn.onclick = function() {
        const type = btn.getAttribute('data-quick');
        if (type === 'even-even') {
            selectNumbersBy(num => Number(num[0]) % 2 === 0 && Number(num[1]) % 2 === 0);
        } else if (type === 'odd-odd') {
            selectNumbersBy(num => Number(num[0]) % 2 === 1 && Number(num[1]) % 2 === 1);
        } else if (type === 'even-odd') {
            selectNumbersBy(num => Number(num[0]) % 2 === 0 && Number(num[1]) % 2 === 1);
        } else if (type === 'apu') {
            const apuArr = ['00','11','22','33','44','55','66','77','88','99'];
            selectNumbersBy(num => apuArr.includes(num));
        } else if (type === 'pawa') {
            const pawaArr = ['05','50','16','61','27','72','38','83','49','94'];
            selectNumbersBy(num => pawaArr.includes(num));
        } else if (type === 'nakha') {
            const nakhaArr = ['18','81','24','42','35','53','69','96','70','07'];
            selectNumbersBy(num => nakhaArr.includes(num));
        } else if (type === 'even-top') {
            selectNumbersBy(num => Number(num[0]) % 2 === 0);
        } else if (type === 'odd-top') {
            selectNumbersBy(num => Number(num[0]) % 2 === 1);
        }
        popupMask.style.display = 'none';
        popupDialog.style.display = 'none';
    };
});
document.getElementById('quickpick-r').onclick = function() {
    if(window.isBettingClosed) return;
    const selectedNums = Array.from(document.querySelectorAll('.number-cell.selected'))
        .map(cell => cell.getAttribute('data-num'));
    selectedNums.forEach(num => {
        if (num.length === 2) {
            const reversed = num[1] + num[0];
            if (num !== reversed) {
                const revCell = document.querySelector(`.number-cell[data-num="${reversed}"]:not(.closed):not(.closed-by-admin):not(.closed-by-top)`);
                if (revCell && !revCell.classList.contains('selected')) {
                    revCell.classList.add('selected');
                }
            }
        }
    });
};
// --- Bottom pick section (single digit) ---
const quickBottomBtns = document.querySelectorAll('.quick-bottom-btn');
let quickBottomSelected = [];
quickBottomBtns.forEach(btn => {
    btn.onclick = function() {
        const b = btn.getAttribute('data-bottom');
        if (quickBottomSelected.includes(b)) {
            quickBottomSelected = quickBottomSelected.filter(x => x !== b);
            btn.classList.remove('selected');
        } else {
            quickBottomSelected.push(b);
            btn.classList.add('selected');
        }
    };
});
document.getElementById('quick-bottom-clear').onclick = function() {
    quickBottomSelected = [];
    quickBottomBtns.forEach(btn => btn.classList.remove('selected'));
};
document.getElementById('quick-bottom-apply').onclick = function() {
    if (quickBottomSelected.length === 0) return;
    selectNumbersBy(num => quickBottomSelected.includes(num[1]));
    popupMask.style.display = 'none';
    popupDialog.style.display = 'none';
};
// --- Top pick section (single digit) ---
const topPickBtns = document.querySelectorAll('.top-pick-btn');
let topPickSelected = [];
topPickBtns.forEach(btn => {
    btn.onclick = function() {
        const t = btn.getAttribute('data-top');
        if (topPickSelected.includes(t)) {
            topPickSelected = topPickSelected.filter(x => x !== t);
            btn.classList.remove('selected');
        } else {
            topPickSelected.push(t);
            btn.classList.add('selected');
        }
    };
});
document.getElementById('top-pick-clear').onclick = function() {
    topPickSelected = [];
    topPickBtns.forEach(btn => btn.classList.remove('selected'));
};
document.getElementById('top-pick-apply').onclick = function() {
    if (topPickSelected.length === 0) return;
    selectNumbersBy(num => topPickSelected.includes(num[0]));
    popupMask.style.display = 'none';
    popupDialog.style.display = 'none';
};
// --- BK pick section (sum of two digits) ---
const quickBkBtns = document.querySelectorAll('.quick-bk-btn');
let quickBkSelected = [];
quickBkBtns.forEach(btn => {
    btn.onclick = function() {
        const comb = btn.getAttribute('data-comb');
        if (quickBkSelected.includes(comb)) {
            quickBkSelected = quickBkSelected.filter(x => x !== comb);
            btn.classList.remove('selected');
        } else {
            quickBkSelected.push(comb);
            btn.classList.add('selected');
        }
    };
});
document.getElementById('quick-bk-clear').onclick = function() {
    quickBkSelected = [];
    quickBkBtns.forEach(btn => btn.classList.remove('selected'));
};
document.getElementById('quick-bk-apply').onclick = function() {
    if (quickBkSelected.length === 0) return;
    selectNumbersBy(num => quickBkSelected.includes(String(Number(num[0]) + Number(num[1]))));
    popupMask.style.display = 'none';
    popupDialog.style.display = 'none';
};
// --- Replace "ပယ်ဖြတ်မည်" with "Ok" and close popup ---
const okBtn = document.getElementById('quick-bottom-ok');
if (okBtn) {
    okBtn.onclick = function() {
        popupMask.style.display = 'none';
        popupDialog.style.display = 'none';
    };
}
</script>
</body>
</html>
