<?php
// 2D Number UI (00-99) for 11:00AM (မြန်မာစံတော်ချိန်), mobile UI, brake/limit control, balance in table, R button = reverse selection + balance refresh

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/referral_commission.php'; // <-- Include referral logic

// --- Check Login ---
if (empty($_SESSION['user_id'])) {
    header('Location: /index.html');
    exit;
}
$current_user = $_SESSION['user_id'] ?? null;

// --- Closed session logic ---
require_once __DIR__ . '/closed_session.php';
$closedInfo = getSessionClosedInfo('Asia/Yangon', '11:00:00', '10:55:00');
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
        $bet_date = (clone $now_dt)->modify('+1 day')->format('Y-m-d');
        $is_advance_bet = true;
        $is_betting_closed = false; // Allow betting for tomorrow
        $display_date_info = $bet_date . ' (မနက်ဖန်)';
    }
}

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
$stmt_current->execute([':type'=>'2D-1100', ':bet_date' => $bet_date]);
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

    if (empty($selected_numbers)) {
        header("Location: " . $_SERVER['PHP_SELF']);
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
                    $stmt_num_total->execute([':type'=> '2D-1100', ':bet_date' => $bet_date, ':number' => $number]);
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
                $insert_stmt->execute([':user_id' => $current_user, ':bet_type' => '2D-1100', ':number' => $number, ':amount' => $bet_amount, ':bet_date' => $bet_date]);
            }

            $pdo->commit();
            header("Location: " . $_SERVER['PHP_SELF'] . "?success=1" . ($is_advance_bet ? "&advance=1" : ""));
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
    <title>2D 11:00AM - Bet</title>
    <link rel="stylesheet" href="/css/variables.css">
    <link rel="stylesheet" href="/css/layout.css">
    <link rel="stylesheet" href="/css/header.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
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
}
.header {
    position: fixed;
    top: 0; left: 0; right: 0;
    height: 64px;
    background: linear-gradient(90deg, #FFD700 60%, #FFB800 120%);
    color: #222;
    z-index: 1100;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 16px;
    border-bottom-left-radius: 13px;
    border-bottom-right-radius: 13px;
    box-shadow: 0 6px 28px #ffd70033, 0 1.5px 0 #e2e7ef;
}
.header-title {
    display: flex;
    align-items: center;
    gap: 13px;
    font-size: 1.3em;
    font-weight: 700;
    color: #222;
    letter-spacing: 1.1px;
    text-transform: uppercase;
    padding-left: 2px;
}
.header-logo {
    height: 32px;
    width: 32px;
    border-radius: 10px;
    box-shadow: 0 2px 8px #0002;
    background: #fff5;
    object-fit: contain;
    margin-right: 7px;
}
.header-right {
    display: flex;
    align-items: center;
    height: 100%;
}
.back-btn {
    display: flex;
    align-items: center;
    color: #fff;
    text-decoration: none;
    padding: 6px 16px;
    border-radius: 8px;
    background: #1877f2;
    font-size: 1em;
    font-weight: 600;
    margin-left: 10px;
    margin-right: 0;
    box-shadow: 0 2px 8px #ffd70033;
    transition: background 0.18s;
    border: none;
}
.back-btn:hover { background: #1256b3; }
.back-btn i { margin-right: 7px; }
/* Sticky balance panel - moved outside container, fixed below header */
.balance-panel {
    position: fixed;
    top: 64px; /* header height */
    left: 0; right: 0;
    z-index: 1200;
    margin: 0 auto;
    max-width: 440px;
    background: #FFD700;
    color: #222;
    border-radius: 13px;
    box-shadow: 0 4px 15px #ffd70066;
    padding: 15px 20px;
    font-size: 1.13em;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    letter-spacing: 0.02em;
}
.container {
    max-width: 440px;
    margin: 0 auto;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    padding: 18px 3vw 20px 3vw;
    margin-top: 132px; /* header + balance-panel height */
}

/* Sticky action button group */
.action-btn-group {
    position: sticky;
    top: 134px; /* header height + balance panel height + margin */
    z-index: 1200;
    background: #fff;
    padding-bottom: 8px;
    display: flex;
    justify-content: center;
    gap: 12px;
    margin-bottom: 15px;
}

.balance-panel .bi-wallet2 { font-size: 1.3em; margin-right: 10px; }
.balance-panel .balance-amount {
    font-size: 1.18em;
    font-weight: 700;
    padding-right: 7px;
}
.balance-panel .refresh-btn {
    background: #fff;
    color: #FFD700;
    border-radius: 6px;
    border: none;
    font-size: 1em;
    font-weight: 600;
    padding: 3px 8px;
    min-width: 32px;
    min-height: 32px;
    margin-left: 10px;
    cursor: pointer;
    box-shadow: 0 2px 8px #ffd70018;
    transition: background 0.18s, color 0.18s;
}
.balance-panel .refresh-btn:hover {
    background: #ffe266;
    color: #b47b00;
}

.quick-select-btn {
    background: #fffbe6;
    color: #c09000;
    border-radius: 7px;
    border: none;
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
    background: #FFD700;
    color: #222;
    border: none;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.18s;
    box-shadow: 0 2px 8px #ffd70018;
    line-height: 1.1;
    min-width: 55px;
    min-height: 32px;
    border-radius: 7px;
    padding: 6px 13px;
    font-size: 0.98em;
}
.bet-form-btn-table:hover { background: #FFB800; }
.numbers-title {
    font-size: 1.13em;
    color: #FFD700;
    font-weight: bold;
    margin-bottom: 10px;
    text-align: center;
    letter-spacing: 1px;
}
.numbers-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    grid-template-rows: repeat(20, 1fr);
    gap: 14px 8px;
    margin-top: 8px;
}
.number-item {
    background: #fffbe6;
    color: #c09000;
    border: 2px solid #ffe266;
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
    background: #FFD700;
    color: #fff;
    border: 2px solid #c09000;
    box-shadow: 0 3px 16px #ffd70022;
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
    background: #FFB800;
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
.time-badge {
    display: inline-block;
    background: #f39c12;
    color: #fff;
    font-weight: bold;
    font-size: 0.97em;
    border-radius: 8px;
    padding: 4px 16px 3px 16px;
    margin: 0 auto 18px auto;
    text-align: center;
    letter-spacing: 1px;
    box-shadow: 0 1px 4px #f39c1240;
}
.modal-confirm-bg {
    position: fixed;
    z-index: 20000;
    left: 0; top: 0; right: 0; bottom: 0;
    background: rgba(36, 56, 90, 0.18);
    display: none;
    align-items: center;
    justify-content: center;
}
.modal-confirm-bg.active { display: flex; }
.modal-confirm-box {
    background: #fff;
    border-radius: 18px;
    box-shadow: 0 12px 32px #0002;
    max-width: 94vw;
    min-width: 270px;
    width: 96vw;
    max-width: 410px;
    padding: 22px 17px 14px 17px;
    position: relative;
    animation: showpop .18s cubic-bezier(.43,1.2,.86,1.2);
}
@keyframes showpop {
    from { transform: scale(0.97) translateY(18px); opacity: 0.2; }
    to   { transform: scale(1) translateY(0); opacity: 1; }
}
.modal-confirm-title {
    font-weight: bold;
    font-size: 1.13em;
    color: #1976d2;
    margin-bottom: 12px;
    letter-spacing: .7px;
}
.modal-confirm-list {
    max-height: 180px;
    overflow-y: auto;
    margin: 10px 0 17px 0;
    background: #f5f9ff;
    border-radius: 9px;
    padding: 9px 8px 7px 8px;
    font-size: 1.02em;
}
.modal-confirm-list span {
    display: inline-block;
    background: #1877f2;
    color: #fff;
    border-radius: 7px;
    padding: 4px 9px 3px 9px;
    margin: 2px 4px 2px 0;
    font-size: 1.02em;
}
.modal-confirm-label {
    margin: 9px 0 2px 0;
    font-weight: 600;
    font-size: .99em;
    color: #555;
}
.modal-confirm-row {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 10px;
}
.modal-confirm-row input[type="number"] {
    width: 100px;
    padding: 7px 5px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 1.09em;
}
.modal-confirm-edit {
    color: #1877f2; cursor: pointer; margin-left: 8px; font-size: 0.97em;
}
.modal-confirm-total {
    font-weight: bold;
    color: #1877f2;
    margin-bottom: 10px;
}
.modal-confirm-actions {
    margin-top: 8px;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
}
.modal-confirm-btn {
    padding: 7px 19px;
    border-radius: 8px;
    font-size: 1.08em;
    font-weight: 600;
    border: none;
    cursor: pointer;
}
.modal-confirm-btn.confirm {
    background: #1877f2;
    color: #fff;
}
.modal-confirm-btn.cancel {
    background: #eee;
    color: #1a222d;
}
.modal-confirm-close {
    position: absolute; right: 11px; top: 7px;
    color: #999; font-size: 1.4em; background: none; border: none; cursor: pointer;
}
.modal-confirm-close:hover { color: #1976d2;}

/* --- Quick Select Popup Style --- */
dialog {
    border: none;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    padding: 15px;
    min-width: 140px;
    width: 90vw;
    max-width: 340px;
    text-align: left;
    box-sizing: border-box;
    z-index: 21000;
    margin: auto; /* Added for centering */
}
dialog[open] {
    animation: showpop .18s cubic-bezier(.43,1.2,.86,1.2);
}
dialog::backdrop {
    background: rgba(0, 0, 0, 0.5);
}
.popup-header {
    font-size: 16px;
    font-weight: bold;
    margin: 0 0 10px 0;
    text-align: center;
}
.popup-label {
    font-size: 13px;
    margin-top: 8px;
    display: block;
    color: #333;
}
.popup-input {
    width: 100%;
    padding: 6px 8px;
    font-size: 13px;
    border-radius: 5px;
    border: 1px solid #ccc;
    margin-top: 4px;
    box-sizing: border-box;
}
.popup-btn-group {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin: 12px 0;
}
.popup-btn {
    flex: 1 1 auto;
    color: white;
    border: none;
    padding: 7px 10px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 13px;
    transition: opacity 0.2s, box-shadow 0.2s;
}
.popup-btn.selected {
    opacity: 0.7;
    box-shadow: 0 0 0 2px #333 inset;
}
.popup-footer {
    margin-top: 15px;
    text-align: center;
    display: flex;
    gap: 10px;
}
.popup-footer button {
    width: 100%;
    font-size: 14px;
    padding: 8px 0;
}
#resultText {
    margin-top: 12px;
    color: #28a745;
    font-size: 14px;
    text-align: left;
    white-space: pre-line;
    font-weight: bold;
    line-height: 1.6;
}
/* Button Colors */
.btn-blue { background: #007bff; }
.btn-alt { background: #28a745; }
.btn-orange { background: #fd7e14; }
.btn-info { background: #17a2b8; }
.btn-gray { background: #6c757d; }
.btn-pink { background: #e83e8c; }
.btn-dark { background: #343a40; }
.btn-green { background: #20c997; }

@media (max-width: 600px) {
    .container { max-width: 100vw; border-radius: 0; padding: 6px 0 10px 0; margin-top: 108px; box-shadow: none; }
    .balance-panel { top: 56px; max-width: 100vw; padding: 12px 10px; font-size: 1em; }
    .numbers-grid { grid-template-columns: repeat(5, 1fr); grid-template-rows: repeat(20, 1fr); gap: 8px 3px; }
    .number-item { font-size: 1em; padding: 8px 0; border-radius: 6px; }
    .header { height: 56px; padding: 0 6px;}
    .header-title { font-size: 1.02em; }
    .header-logo { height: 28px; width: 28px; }
    .back-btn { padding: 5px 10px; font-size: 0.97em; left: 7px; top: 7px; }
    .time-badge { font-size: 0.93em; padding: 3px 8px 2px 8px;}
}
@media (max-width: 400px) {
    .popup-box { padding: 10px; }
    .popup-btn { font-size: 12px; padding: 6px 8px; }
    .popup-label { font-size: 12px; }
}
@media (max-width: 370px) {
    .number-item { font-size: 0.93em; }
}
    </style>
</head>
<body>
    <div class="header">
        <div class="header-title">
            <img src="/images/glo-logo.png" alt="Logo" class="header-logo" />
            2D 11:00AM
        </div>
        <div class="header-right">
            <a href="/2dlive/" class="back-btn"><i class="bi bi-arrow-left"></i> နောက်သို့</a>
        </div>
    </div>
    <!-- Balance panel moved outside container, just below header -->
    <div class="balance-panel">
        <i class="bi bi-wallet2"></i>
        လက်ကျန်ငွေ: <span class="balance-amount" id="Balance"><?= number_format($user_balance) ?></span> ကျပ်
        <button class="refresh-btn" type="button" id="refreshBtn" title="Refresh balance & reverse">R</button>
    </div>
    <div class="container">
        <div class="action-btn-group">
            <button type="button" id="betTableTrigger" class="bet-form-btn-table"><?= $is_advance_bet ? 'မနက်ဖန်အတွက် ထိုးမည်' : 'ထိုးမည်' ?></button>
            <button type="button" id="showPopupButton" class="quick-select-btn"><i class="bi bi-lightning-charge"></i> အမြန်ရွေး</button>
        </div>
        <?php if ($is_advance_bet): ?>
        <div style="color:#e67e22; font-weight:bold; margin-bottom:10px;">
            မနက်ဖန် (<?= htmlspecialchars($bet_date) ?>) အတွက် ကြိုထိုး mode ဖြစ်သည်။
        </div>
        <?php endif; ?>
        <?php if ($message && $messageType != "success"): ?>
        <div class="message <?= htmlspecialchars($messageType) ?>">
            <?= htmlspecialchars($message) ?>
        </div>
        <?php endif; ?>
        <form method="post" id="betFormFinal" style="display:none;"></form>
        <div class="numbers-title"></div>
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
    </div>
    <!-- Modal Popup for confirm bet -->
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

    <!-- Quick Select Popup Modal -->
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
            <input type="text" id="caseInput" class="popup-input" placeholder="ဥပမာ 789, 49012, 69034">

            <div class="popup-btn-group" id="choiceBtnGroup">
                <button type="button" class="popup-btn btn-alt" data-choice="ညီအကို">ညီအကို</button>
                <button type="button" class="popup-btn btn-orange" data-choice="ပါဝါ">ပါဝါ</button>
                <button type="button" class="popup-btn btn-info" data-choice="နက္ခ">နက္ခ</button>
                <button type="button" class="popup-btn btn-gray" data-choice="စုံစုံ">စုံစုံ</button>
                <button type="button" class="popup-btn btn-pink" data-choice="စုံမ">စုံမ</button>
                <button type="button" class="popup-btn btn-dark" data-choice="မစုံ">မစုံ</button>
                <button type="button" class="popup-btn btn-green" data-choice="အပူး">အပူး</button>
            </div>

            <div class="popup-footer">
                <button type="submit" class="popup-btn btn-blue">ရွေးမည်</button>
                <button type="button" id="closePopupButton" class="popup-btn btn-gray">ပိတ်မည်</button>
            </div>
        </form>
        <div id="resultText"></div>
    </dialog>

<script>
// --- 2D Number Selection Logic ---

window.addEventListener('load', function() {
    sessionStorage.removeItem('selected2d_1100');
    selected = {};
    updateGridSelections();
});

const numbersGrid = document.getElementById('numbersGrid');
let selected = JSON.parse(sessionStorage.getItem('selected2d_1100') || '{}');

function updateGridSelections() {
    document.querySelectorAll('.number-item').forEach(item => {
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
    sessionStorage.setItem('selected2d_1100', JSON.stringify(selected));
}

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

document.getElementById('refreshBtn').addEventListener('click', function() {
    fetch(window.location.pathname + '?get_balance=1')
        .then(r => r.json())
        .then(data => {
            if (typeof data.balance !== 'undefined') {
                document.getElementById('Balance').textContent = Number(data.balance).toLocaleString();
            }
        });
    let newSelected = {};
    Object.keys(selected).forEach(num => {
        if (num.length === 2) {
            let reversed = num[1] + num[0];
            const origElem = document.querySelector(`.number-item[data-number="${num}"]`);
            const revElem = document.querySelector(`.number-item[data-number="${reversed}"]`);
            if (origElem && !origElem.classList.contains('disabled')) newSelected[num] = true;
            if (revElem && !revElem.classList.contains('disabled')) newSelected[reversed] = true;
        }
    });
    selected = newSelected;
    updateGridSelections();
});

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
        span.contentEditable = true;
        span.setAttribute('data-number', num);
        span.title = 'နံပါတ်ပြင်ရန်';
        span.addEventListener('blur', function() {
            let val = span.textContent.replace(/\D/g, '').padStart(2, '0').slice(-2);
            if (!/^\d{2}$/.test(val)) val = num;
            span.textContent = val;
            span.setAttribute('data-number', val);
            updatePrizeAndTotal();
        });
        modalNumbers.appendChild(span);
    });
    modalAmount.value = betAmount;
    updatePrizeAndTotal();
    modalBg.classList.add('active');
    modalAmount.focus();
}

function updatePrizeAndTotal() {
    let nums = [];
    modalNumbers.querySelectorAll('span').forEach(span => {
        let val = span.textContent.replace(/\D/g, '').padStart(2, '0').slice(-2);
        if (/^\d{2}$/.test(val)) nums.push(val);
    });
    let amount = parseInt(modalAmount.value) || 0;
    let total = nums.length * amount;
    let prize = amount * 95;
    modalTotal.textContent = `စုစုပေါင်း: ${nums.length} ကွက် × ${amount.toLocaleString()} = ${(total).toLocaleString()} ကျပ်`;
    modalPrize.textContent = prize.toLocaleString();
}

modalAmount.addEventListener('input', updatePrizeAndTotal);
modalNumbers.addEventListener('input', updatePrizeAndTotal);

modalClose.onclick = modalCancel.onclick = function() {
    modalBg.classList.remove('active');
};

betTableTrigger.addEventListener('click', function(e) {
    let nums = Object.keys(selected).filter(num => {
        const btn = document.querySelector(`.number-item[data-number="${num}"]`);
        return btn && !btn.classList.contains('disabled');
    });
    let amount = 100;
    if (nums.length === 0) return;
    showModal(nums, amount);
});

modalSubmit.addEventListener('click', function() {
    let nums = [];
    modalNumbers.querySelectorAll('span').forEach(span => {
        let val = span.textContent.replace(/\D/g, '').padStart(2, '0').slice(-2);
        if (/^\d{2}$/.test(val)) {
            const btn = document.querySelector(`.number-item[data-number="${val}"]`);
            if (btn && !btn.classList.contains('disabled')) nums.push(val);
        }
    });
    let amount = parseInt(modalAmount.value) || 100;
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
    setTimeout(()=>{betFormFinal.submit();}, 120);
});

window.addEventListener('keydown', function(e) {
    if (modalBg.classList.contains('active') && (e.key === "Escape")) {
        modalBg.classList.remove('active');
    }
});

// --- Quick Select Popup Logic ---
document.addEventListener('DOMContentLoaded', () => {
    const dialog = document.getElementById('choiceDialog');
    const showButton = document.getElementById('showPopupButton');
    const closeButton = document.getElementById('closePopupButton');
    const choiceForm = document.getElementById('choiceForm');
    const choiceBtnGroup = document.getElementById('choiceBtnGroup');
    const resultText = document.getElementById('resultText');

    const topInput = document.getElementById('topInput');
    const backInput = document.getElementById('backInput');
    const brakeInput = document.getElementById('brakeInput');
    const singleInput = document.getElementById('singleInput');
    const caseInput = document.getElementById('caseInput');
    
    const numberSets = {
        'ညီအကို': ['01','12','23','34','45','56','67','78','89','90', '10','21','32','43','54','65','76','87','98','09'],
        'ပါဝါ': ['05','16','27','38','49', '50','61','72','83','94'],
        'နက္ခ': ['07','18','29','35','46', '70','81','92','53','64'],
        'စုံစုံ': ['00','22','44','66','88','02','04','06','08','20','24','26','28','40','42','46','48','60','62','64','68','80','82','84','86'],
        'စုံမ': ['01','03','05','07','09','21','23','25','27','29','41','43','45','47','49','61','63','65','67','69','81','83','85','87','89'],
        'မစုံ': ['10','12','14','16','18','30','32','34','36','38','50','52','54','56','58','70','72','74','76','78','90','92','94','96','98'],
        'အပူး': ['00','11','22','33','44','55','66','77','88','99']
    };

    const getNumbersFromInput = (input) => {
        return input.value.trim().split(',').map(s => s.trim().replace(/\D/g, '')).filter(Boolean);
    };

    showButton.addEventListener('click', () => {
        choiceForm.reset();
        resultText.textContent = '';
        choiceBtnGroup.querySelectorAll('.popup-btn').forEach(btn => btn.classList.remove('selected'));
        dialog.showModal();
    });

    closeButton.addEventListener('click', () => dialog.close());
    
    choiceBtnGroup.addEventListener('click', (event) => {
        if (event.target.matches('.popup-btn')) {
            event.target.classList.toggle('selected');
        }
    });

    choiceForm.addEventListener('submit', (event) => {
        event.preventDefault();
        
        let finalNumbers = new Set();
        let hasFilter = false;

        // Button choices
        choiceBtnGroup.querySelectorAll('.popup-btn.selected').forEach(btn => {
            const choice = btn.dataset.choice;
            if (numberSets[choice]) {
                numberSets[choice].forEach(num => finalNumbers.add(num));
                hasFilter = true;
            }
        });

        // Text input choices
        const tops = getNumbersFromInput(topInput);
        if (tops.length > 0) {
            let tempSet = new Set();
            for (let i = 0; i <= 9; i++) {
                tops.forEach(t => tempSet.add(`${t}${i}`.slice(-2)));
            }
            if (hasFilter) { finalNumbers = new Set([...finalNumbers].filter(x => tempSet.has(x))); } else { finalNumbers = tempSet; }
            hasFilter = true;
        }

        const backs = getNumbersFromInput(backInput);
        if (backs.length > 0) {
            let tempSet = new Set();
            for (let i = 0; i <= 9; i++) {
                backs.forEach(b => tempSet.add(`${i}${b}`.slice(-2)));
            }
            if (hasFilter) { finalNumbers = new Set([...finalNumbers].filter(x => tempSet.has(x))); } else { finalNumbers = tempSet; }
            hasFilter = true;
        }

        const brakes = getNumbersFromInput(brakeInput);
        if (brakes.length > 0) {
            let tempSet = new Set();
            for (let i = 0; i <= 99; i++) {
                const numStr = i.toString().padStart(2, '0');
                const sum = parseInt(numStr[0]) + parseInt(numStr[1]);
                if (brakes.includes(sum.toString()) || brakes.includes((sum % 10).toString())) {
                    tempSet.add(numStr);
                }
            }
            if (hasFilter) { finalNumbers = new Set([...finalNumbers].filter(x => tempSet.has(x))); } else { finalNumbers = tempSet; }
            hasFilter = true;
        }

        const singles = getNumbersFromInput(singleInput);
        if (singles.length > 0) {
            let tempSet = new Set();
            for (let i = 0; i <= 99; i++) {
                const numStr = i.toString().padStart(2, '0');
                if (singles.some(s => numStr.includes(s))) {
                    tempSet.add(numStr);
                }
            }
            if (hasFilter) { finalNumbers = new Set([...finalNumbers].filter(x => tempSet.has(x))); } else { finalNumbers = tempSet; }
            hasFilter = true;
        }
        
        const cases = getNumbersFromInput(caseInput);
        if(cases.length > 0) {
            let tempSet = new Set();
            cases.forEach(c_str => {
                const digits = c_str.split('');
                digits.forEach(d1 => {
                    digits.forEach(d2 => {
                        tempSet.add(`${d1}${d2}`);
                    });
                });
            });
            if (hasFilter) { finalNumbers = new Set([...finalNumbers].filter(x => tempSet.has(x))); } else { finalNumbers = tempSet; }
            hasFilter = true;
        }

        if (!hasFilter) {
            resultText.textContent = "အနည်းဆုံး criteria တစ်ခု ရွေးချယ်ပါ";
            resultText.style.color = 'red';
            return;
        }

        selected = {}; // Clear previous selections
        finalNumbers.forEach(num => {
            const numStr = num.toString().padStart(2, '0');
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