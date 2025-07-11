<?php
// 2D Number UI (00-99) for 3:00PM (မြန်မာစံတော်ချိန်), mobile UI, brake/limit control, balance in table, R button = reverse selection + balance refresh

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
$closedInfo = getSessionClosedInfo('Asia/Yangon', '15:00:00', '14:55:00'); // 3:00PM session, closed at 2:55PM
$is_betting_closed = $closedInfo['is_betting_closed'];
$target_date_dt = $closedInfo['target_date'];
$bet_date = $target_date_dt->format('Y-m-d');
$display_date_info = $closedInfo['display_date_info'];

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

// --- Already Bet Totals for Today (for brake progress) ---
$current_totals = [];
$stmt_current = $pdo->prepare('SELECT number, SUM(amount) as total_bet FROM lottery_bets WHERE bet_type = :type AND bet_date = :bet_date GROUP BY number');
$stmt_current->execute([':type'=>'2D-1500', ':bet_date' => $bet_date]);
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
        $message = 'ထိုးချိန် ပြီးသွားပါပြီ။';
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
                    $stmt_num_total->execute([':type'=> '2D-1500', ':bet_date' => $bet_date, ':number' => $number]);
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
                $insert_stmt->execute([':user_id' => $current_user, ':bet_type' => '2D-1500', ':number' => $number, ':amount' => $bet_amount, ':bet_date' => $bet_date]);
            }

            $pdo->commit();
            header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
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
    <title>AZM2D3D - 3:00PM</title>
    <link rel="stylesheet" href="/css/variables.css">
    <link rel="stylesheet" href="/css/layout.css">
    <link rel="stylesheet" href="/css/header.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <style>
        body {
            background-image: url('https://amazemm.xyz/images/bg-main.jpg');
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
            height: 74px;
            background: linear-gradient(90deg, #1877f2 60%, #1153a6 120%);
            color: #fff;
            z-index: 1100;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 10px;
            border-bottom-left-radius: 14px;
            border-bottom-right-radius: 14px;
            box-shadow: 0 6px 32px rgba(24,119,242,0.13), 0 1.5px 0 #e2e7ef;
        }
        .header-title {
            font-family: 'Poppins', 'Noto Sans Myanmar', sans-serif;
            font-size: 1.17em;
            font-weight: 700;
            color: #fff;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            padding-left: 4px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .header-logo {
            height: 36px;
            width: 36px;
            border-radius: 12px;
            box-shadow: 0 2px 8px #0002;
            background: #fff5;
            object-fit: contain;
            margin-right: 10px;
        }
        .header-right {
            display: flex;
            align-items: center;
            height: 100%;
        }
        .back-btn {
            display: flex;
            align-items: center;
            color: #1976d2;
            text-decoration: none;
            padding: 6px 10px;
            border-radius: 8px;
            background: #fff;
            font-size: 0.95em;
            font-weight: 600;
            margin-left: 10px;
            margin-right: 0;
            box-shadow: 0 2px 8px #1877f218;
            border: none;
            transition: background 0.18s;
        }
        .back-btn:hover { background: #e3f2fd; }
        .back-btn i { margin-right: 6px; }
        .container {
            max-width: 440px;
            margin: 0 auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            padding: 18px 3vw 20px 3vw;
            margin-top: 90px;
        }
        .balance-table-wrap {
            display: flex;
            justify-content: center;
            margin-bottom: 22px;
        }
        .balance-table {
            background: var(--primary, #3498db);
            color: #fff;
            border-radius: 13px;
            box-shadow: 0 4px 15px rgba(25, 118, 210, 0.15);
            font-size: 1.13em;
            padding: 0;
            border-collapse: separate;
            border-spacing: 0;
            min-width: 240px;
            max-width: 350px;
        }
        .balance-table td {
            padding: 12px 15px 11px 15px;
            font-weight: 500;
            vertical-align: middle;
        }
        .balance-table .wallet {
            font-size: 1.3em;
            padding-right: 10px;
            width: 40px;
        }
        .balance-table .balance-amount {
            font-size: 1.18em;
            font-weight: 700;
            padding-right: 7px;
        }
        .balance-table .refresh-btn {
            background: #fff;
            color: var(--primary, #3498db);
            border: none;
            border-radius: 6px;
            font-size: 1em;
            font-weight: 600;
            padding: 3px 8px;
            min-width: 32px;
            min-height: 32px;
            margin-left: 10px;
            cursor: pointer;
            box-shadow: 0 2px 8px #1877f218;
            transition: background 0.18s, color 0.18s;
        }
        .balance-table .refresh-btn:hover {
            background: #e3f2fd;
            color: #1153a6;
        }
        .action-btn-group {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin-bottom: 15px;
        }
        .quick-select-btn {
            background: #e3f2fd;
            color: #1877f2;
            border-radius: 7px;
            border: none;
            font-weight: 600;
            padding: 5px 13px;
            font-size: 1em;
            cursor: pointer;
            transition: background 0.18s, color 0.18s;
        }
        .quick-select-btn:hover {
            background: #d0e0fa;
            color: #1153a6;
        }
        .bet-form-btn-table {
            background: #1877f2;
            color: #fff;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.18s;
            box-shadow: 0 2px 8px #1877f218;
            line-height: 1.1;
            min-width: 55px;
            min-height: 32px;
            border-radius: 7px;
            padding: 6px 13px;
            font-size: 0.98em;
        }
        .bet-form-btn-table:hover { background: #1153a6; }
        .numbers-title {
            font-size: 1.13em;
            color: var(--primary, #3498db);
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
            background: #f4f9ff;
            color: #1976d2;
            border: 2px solid #e3f2fd;
            border-radius: 10px;
            font-size: 1.15em;
            font-weight: 700;
            padding: 12px 0;
            text-align: center;
            user-select: none;
            box-shadow: 0 1px 6px #1877f208;
            transition: background 0.2s, color 0.2s, box-shadow 0.2s, border 0.2s;
            cursor: pointer;
            outline: none;
            position: relative;
        }
        .number-item.selected {
            background: #1877f2;
            color: #fff;
            border: 2px solid #0a67a3;
            box-shadow: 0 3px 16px #1877f222;
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
            background: #1976d2;
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
        .modal-quick-bg {
            position: fixed;
            z-index: 20100;
            left: 0; top: 0; right: 0; bottom: 0;
            background: rgba(36, 56, 90, 0.18);
            display: none;
            align-items: center;
            justify-content: center;
        }
        .modal-quick-bg.active { display: flex; }
        .modal-quick-box {
            background: #fff;
            border-radius: 17px;
            box-shadow: 0 12px 32px #0002;
            max-width: 98vw;
            min-width: 230px;
            width: 94vw;
            max-width: 360px;
            padding: 17px 14px 12px 14px;
            position: relative;
        }
        .modal-quick-title {
            font-weight: bold;
            font-size: 1.09em;
            color: #1976d2;
            margin-bottom: 10px;
            letter-spacing: .7px;
        }
        .modal-quick-btns {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-bottom: 7px;
        }
        .modal-quick-btn {
            background: #e3f2fd;
            color: #1976d2;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1.09em;
            padding: 8px 0;
            cursor: pointer;
            transition: background 0.18s, color 0.18s;
        }
        .modal-quick-btn:hover {
            background: #1877f2;
            color: #fff;
        }
        .modal-quick-close {
            position: absolute; right: 9px; top: 7px;
            color: #999; font-size: 1.25em; background: none; border: none; cursor: pointer;
        }
        .modal-quick-close:hover { color: #1976d2;}
        .modal-quick-form {
            display: flex;
            gap: 8px;
            margin-bottom: 8px;
        }
        .modal-quick-form input[type="text"] {
            flex: 1;
            font-size: 1em;
            padding: 5px 10px;
            border-radius: 7px;
            border: 1px solid #ccc;
        }
        @media (max-width: 600px) {
            .container { max-width: 100vw; border-radius: 0; padding: 6px 0 10px 0; margin-top: 74px; box-shadow: none; }
            .balance-table td { padding: 9px 7px 8px 7px; }
            .numbers-grid { grid-template-columns: repeat(5, 1fr); grid-template-rows: repeat(20, 1fr); gap: 8px 3px; }
            .number-item { font-size: 1em; padding: 8px 0; border-radius: 6px; }
            .header { height: 74px; }
            .header-title { font-size: 1.02em; padding-left: 4px; gap: 8px; }
            .header-logo { height: 36px; width: 36px; }
            .back-btn { padding: 5px 10px; font-size: 0.97em; left: 7px; top: 7px; }
            .time-badge { font-size: 0.93em; padding: 3px 8px 2px 8px;}
        }
        @media (max-width: 370px) {
            .number-item { font-size: 0.93em; }
        }

.balance-table-wrap {
    position: sticky;
    top: 80px; /* header height + margin */
    z-index: 1200;
    background: #fff;
    padding-top: 3px;
}
.action-btn-group {
    position: sticky;
    top: 140px; /* adjust below balance-table */
    z-index: 1200;
    background: #fff;
    padding-bottom: 8px;
}
    </style>
</head>
<body>
    <div class="header">
        <div class="header-title">
            <img src="/images/azm-logo.png" alt="Logo" class="header-logo" />
            AZM2D3D GAME
        </div>
        <div class="header-right">
            <a href="/b/2dnumber.php" class="back-btn"><i class="bi bi-arrow-left"></i> </a>
        </div>
    </div>
    <div class="container">
        <div class="time-badge">မြန်မာစံတော်ချိန် - ညနေ ၃ နာရီ (3:00PM)<?= htmlspecialchars($display_date_info) ?></div>
        <div class="balance-table-wrap">
            <table class="balance-table" style="width:100%;table-layout:auto;">
                <tr>
                    <td class="wallet"><i class="bi bi-wallet2"></i></td>
                    <td>
                        လက်ကျန်ငွေ:
                        <span class="balance-amount" id="Balance"><?= number_format($user_balance) ?></span>
                        <span>ကျပ်</span>
                        <button class="refresh-btn" type="button" id="refreshBtn" title="Refresh balance & reverse">R</button>
                    </td>
                </tr>
            </table>
        </div>
        <div class="action-btn-group">
            <button type="button" id="betTableTrigger" class="bet-form-btn-table">ထိုးမည်</button>
            <button type="button" id="quickSelectTrigger" class="quick-select-btn"><i class="bi bi-lightning-charge"></i> အမြန်ရွေး</button>
        </div>
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
            <div class="modal-confirm-title">ထိုးမည် အတည်ပြုရန်</div>
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
    <!-- Modal Popup for Quick Select -->
    <div class="modal-quick-bg" id="modalQuickBg">
        <div class="modal-quick-box">
            <button type="button" class="modal-quick-close" id="modalQuickClose" tabindex="0">&times;</button>
            <div class="modal-quick-title">အမြန်ရွေး</div>
            <div class="modal-quick-btns">
                <button type="button" class="modal-quick-btn" data-type="even">စုံ (Even)</button>
                <button type="button" class="modal-quick-btn" data-type="odd">မ (Odd)</button>
                <button type="button" class="modal-quick-btn" data-type="double">အပူး (Double)</button>
                <button type="button" class="modal-quick-btn" data-type="head_even">စုံထိပ် (Head Even)</button>
                <button type="button" class="modal-quick-btn" data-type="tail_even">စုံစွန် (Tail Even)</button>
                <button type="button" class="modal-quick-btn" data-type="power">Power</button>
                <button type="button" class="modal-quick-btn" data-type="nakha">Nakha</button>
                <button type="button" class="modal-quick-btn" data-type="all">အကုန် (All)</button>
                <button type="button" class="modal-quick-btn" data-type="clear">ရှင်း (Clear)</button>
            </div>
            <form class="modal-quick-form" id="modalQuickAkhweForm" autocomplete="off" onsubmit="return false;" style="margin-bottom:0;">
                <input type="text" id="akhweInput" placeholder="အခွေ eg: 56789" maxlength="6" pattern="[0-9]{5,6}">
                <button type="button" class="modal-quick-btn" id="akhweBtn" style="padding:6px 13px;font-size:0.98em;">အခွေ</button>
            </form>
            <div id="modalQuickError" style="color: #e74c3c; font-size: .97em; margin-top: 3px; min-height: 20px;"></div>
        </div>
    </div>
<script>
// --- 2D Number Selection Logic ---

// Clear selection on browser refresh/load
window.addEventListener('load', function() {
    sessionStorage.removeItem('selected2d_1500');
    selected = {};
    updateGridSelections();
});

// DOM references
const numbersGrid = document.getElementById('numbersGrid');
let selected = JSON.parse(sessionStorage.getItem('selected2d_1500') || '{}');

// Update grid UI based on selected numbers
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
}

// Click selection
numbersGrid.addEventListener('click', function(e) {
    const item = e.target.closest('.number-item');
    if (!item || item.classList.contains('disabled')) return;
    const num = item.getAttribute('data-number');
    if (selected[num]) {
        delete selected[num];
    } else {
        selected[num] = true;
    }
    sessionStorage.setItem('selected2d_1500', JSON.stringify(selected));
    updateGridSelections();
});

// Keyboard selection
numbersGrid.addEventListener('keydown', function(e) {
    if ((e.key === "Enter" || e.key === " ") && e.target.classList.contains('number-item')) {
        e.preventDefault();
        e.target.click();
    }
});

// Refresh balance and reverse selection
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
    sessionStorage.setItem('selected2d_1500', JSON.stringify(selected));
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
    if (typeof modalQuickBg !== "undefined" && modalQuickBg.classList.contains('active') && (e.key === "Escape")) {
        modalQuickBg.classList.remove('active');
    }
});

// ---- Quick Select Popup Logic ----
const quickSelectTrigger = document.getElementById('quickSelectTrigger');
const modalQuickBg = document.getElementById('modalQuickBg');
const modalQuickClose = document.getElementById('modalQuickClose');
const modalQuickBtns = document.querySelectorAll('.modal-quick-btn');
const modalQuickError = document.getElementById('modalQuickError');
const akhweInput = document.getElementById('akhweInput');
const akhweBtn = document.getElementById('akhweBtn');

quickSelectTrigger.addEventListener('click', function() {
    modalQuickBg.classList.add('active');
    modalQuickError.textContent = "";
});
modalQuickClose.onclick = function() {
    modalQuickBg.classList.remove('active');
};

function doQuickSelect(type, extra) {
    modalQuickError.textContent = '';
    if (type === 'clear') {
        selected = {};
        sessionStorage.setItem('selected2d_1500', JSON.stringify(selected));
        updateGridSelections();
        modalQuickBg.classList.remove('active');
        return;
    }
    if (type === 'all') {
        fetch('/b/quick_select_api.php?type=even')
            .then(resp => resp.json())
            .then(_data => {
                selected = {};
                document.querySelectorAll('.number-item:not(.disabled)').forEach(item => {
                    let num = item.getAttribute('data-number');
                    selected[num] = true;
                });
                sessionStorage.setItem('selected2d_1500', JSON.stringify(selected));
                updateGridSelections();
                modalQuickBg.classList.remove('active');
            })
            .catch(() => {
                modalQuickError.textContent = "Server error!";
            });
        return;
    }
    let url = '/b/quick_select_api.php?type=' + encodeURIComponent(type);
    if (type === 'akhwe' && extra) url += '&akhwe=' + encodeURIComponent(extra);
    fetch(url)
        .then(resp => resp.json())
        .then(data => {
            if (data.error) {
                modalQuickError.textContent = data.error;
                return;
            }
            if (data.numbers && Array.isArray(data.numbers)) {
                selected = {};
                let braked = Array.isArray(data.brake1) ? new Set(data.brake1) : new Set();
                data.numbers.forEach(num => {
                    if (!braked.has(num)) {
                        selected[num] = true;
                    }
                });
                sessionStorage.setItem('selected2d_1500', JSON.stringify(selected));
                updateGridSelections();
            }
            modalQuickBg.classList.remove('active');
        })
        .catch(() => {
            modalQuickError.textContent = "Server error!";
        });
}

modalQuickBtns.forEach(btn => {
    btn.addEventListener('click', function() {
        const type = this.getAttribute('data-type');
        doQuickSelect(type);
    });
});

akhweBtn.addEventListener('click', function() {
    let val = akhweInput.value.trim();
    if (!/^[0-9]{5,6}$/.test(val)) {
        modalQuickError.textContent = "အခွေ ဂဏန်း ငါးလုံး သို့မဟုတ် ခြောက်လုံးသာ ထည့်ပါ";
        return;
    }
    doQuickSelect('akhwe', val);
});

// Initial update on page load
updateGridSelections();
</script>
</body>
</html>