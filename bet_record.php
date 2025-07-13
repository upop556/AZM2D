<?php
session_start();
date_default_timezone_set('Asia/Yangon');

// --- Only logged-in users can see ---
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/db.php';
$pdo = Db::getInstance()->getConnection();

$user_id = $_SESSION['user_id'];

// Get current day of week (0=Sunday, 6=Saturday)
$today = date('w');
$today_date = date('Y-m-d');

// Calculate the date of last Monday (start of workweek)
if ($today == 1) {
    $last_monday = $today_date;
} elseif ($today == 0) { // Sunday
    $last_monday = date('Y-m-d', strtotime('-6 days', strtotime($today_date)));
} else { // Tuesday through Saturday
    $last_monday = date('Y-m-d', strtotime('-'.($today - 1).' days', strtotime($today_date)));
}

// Friday of current week
$current_friday = date('Y-m-d', strtotime($last_monday.' +4 days'));

// Check if data cleaning scheduled for this week (runs on Saturday morning)
$next_cleanup = date('Y-m-d', strtotime($current_friday.' +1 day'));

// --- Use lottery_bets table ---
// Map bet_type to session_time for display
$session_map = [
    '2D-1100' => '11:00:00',
    '2D-1201' => '12:01:00',
    '2D-1500' => '15:00:00',
    '2D-1630' => '16:30:00'
];

// Filter only Monday-Friday
$stmt = $pdo->prepare("
    SELECT 
        bet_date,
        bet_type,
        number,
        amount,
        created_at
    FROM lottery_bets
    WHERE user_id = :uid
    AND bet_date >= :monday
    AND bet_date <= :friday
    AND DAYOFWEEK(bet_date) NOT IN (1,7)
    ORDER BY bet_date DESC, bet_type DESC, created_at DESC
");
$stmt->execute([
    ':uid' => $user_id,
    ':monday' => $last_monday,
    ':friday' => $current_friday
]);
$bets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Session type mapping for section separation
function session_type($bet_type) {
    switch ($bet_type) {
        case '2D-1100': return 'မနက် ၁၁ နာရီ (11AM)';
        case '2D-1201': return 'နေ့လယ် ၁၂ နာရီ ၁ မိနစ် (12:01PM)';
        case '2D-1500': return 'ညနေ ၃ နာရီ (3PM)';
        case '2D-1630': return 'ညနေ ၄ နာရီ ၃၀ မိနစ် (4:30PM)';
        default: return $bet_type;
    }
}

// Calculate total bet amount for this week
$total_bet_amount = 0;
foreach ($bets as $bet) {
    $bet_amount = is_numeric($bet['amount']) ? (float)$bet['amount'] : 0;
    $total_bet_amount += $bet_amount;
}

// Group bets by session type
$session_bets = [];
foreach ($bets as $bet) {
    $stype = session_type($bet['bet_type']);
    if (!isset($session_bets[$stype])) $session_bets[$stype] = [];
    $session_bets[$stype][] = $bet;
}

// Helper for pretty session time
function session_label($bet_type) {
    switch ($bet_type) {
        case '2D-1100': return '11:00 AM';
        case '2D-1201': return '12:01 PM';
        case '2D-1500': return '03:00 PM';
        case '2D-1630': return '04:30 PM';
        default: return $bet_type;
    }
}

// Get day name in Myanmar
function day_name_mm($date_string) {
    $days_mm = [
        0 => 'တနင်္ဂနွေ',
        1 => 'တနင်္လာ',
        2 => 'အင်္ဂါ',
        3 => 'ဗုဒ္ဓဟူး',
        4 => 'ကြာသပတေး',
        5 => 'သောကြာ',
        6 => 'စနေ'
    ];
    $day_index = date('w', strtotime($date_string));
    return $days_mm[$day_index];
}
?>
<!DOCTYPE html>
<html lang="my">
<head>
    <meta charset="UTF-8">
    <title>ထိုးမှတ်တမ်း</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            font-family: 'Inter', 'Noto Sans Myanmar', Arial, sans-serif;
            background-image: url('https://amazemm.xyz/images/bg-main.jpg');
            background-size: cover;
            background-repeat: no-repeat;
            background-position: center center;
            background-attachment: fixed;
            margin: 0;
            min-height: 100vh;
        }
        .header {
            position: fixed;
            top: 0; left: 0; right: 0;
            height: 110px;
            background: linear-gradient(90deg, #1877f2 60%, #1153a6 120%);
            color: #fff;
            z-index: 1100;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 36px;
            border-bottom-left-radius: 24px;
            border-bottom-right-radius: 24px;
            box-shadow: 0 6px 32px rgba(24,119,242,0.13), 0 1.5px 0 #e2e7ef;
            transition: box-shadow 0.18s, height 0.18s;
        }
        .header-title {
            font-family: 'Poppins', 'Noto Sans Myanmar', sans-serif;
            font-size: 2.6em;
            font-weight: 700;
            color: #fff;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            padding-left: 18px;
            text-shadow: 0 4px 16px rgba(33, 56, 118, 0.15);
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .header-logo {
            height: 52px;
            width: 52px;
            border-radius: 12px;
            box-shadow: 0 2px 8px #0002;
            background: #fff5;
            object-fit: contain;
            margin-right: 16px;
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
        }
        .back-btn:hover { background: #e3f2fd; }
        .back-btn i { margin-right: 6px; }
        @media (max-width: 600px) {
            .header {
                height: 74px;
                padding: 0 10px;
                border-bottom-left-radius: 14px;
                border-bottom-right-radius: 14px;
            }
            .header-title {
                font-size: 1.35em;
                padding-left: 4px;
                gap: 8px;
            }
            .header-logo {
                height: 36px;
                width: 36px;
            }
            .header-right .back-btn {
                font-size: 0.95em;
                padding: 6px 10px;
                margin-left: 10px;
            }
        }
        .container {
            max-width: 430px;
            margin: 128px auto 20px auto;
            background: #fff;
            border-radius: 13px;
            padding: 24px 12px 17px 12px;
            box-shadow: 0 4px 16px #0001;
        }
        h2 { text-align: center; color: #7533c5; margin-bottom: 18px; }
        
        .week-info {
            background: #edf3ff;
            border-radius: 10px;
            padding: 12px;
            margin-bottom: 20px;
            text-align: center;
            box-shadow: 0 1px 5px rgba(0,0,0,0.05);
        }
        .week-info-title {
            font-size: 1em;
            font-weight: bold;
            color: #4e5faa;
            margin-bottom: 5px;
        }
        .week-info-dates {
            color: #555;
            font-size: 0.95em;
        }
        .cleanup-notice {
            background: #fff4e5;
            border-left: 3px solid #ff9800;
            padding: 8px 12px;
            color: #e65100;
            font-size: 0.9em;
            margin: 12px 0;
            border-radius: 5px;
        }
        .total-amount {
            background: #eaffea;
            border-radius: 8px;
            padding: 10px;
            text-align: center;
            color: #1b5e20;
            font-weight: bold;
            margin-bottom: 15px;
            font-size: 1.1em;
        }
        .session-block {
            background: #e3e6fa;
            border-radius: 8px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px #e2e0f5;
            padding: 15px 10px 11px 10px;
        }
        .session-title {
            font-size: 1.12em;
            font-weight: bold;
            color: #7533c5;
            margin-bottom: 12px;
            padding-left: 5px;
        }
        .record-block {
            background: #faf8ff;
            border-radius: 10px;
            box-shadow: 0 1px 10px #e2e0f5;
            margin-bottom: 19px;
            padding: 13px 10px 13px 10px;
        }
        .date-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 5px;
        }
        .date-label {
            color: #444;
            font-size: 1.09em;
            font-weight: 500;
        }
        .day-label {
            background: #7e5ce8;
            color: white;
            font-size: 0.85em;
            border-radius: 12px;
            padding: 3px 10px;
            font-weight: 500;
        }
        .session-label {
            color: #7e5ce8;
            font-weight: bold;
            font-size: 1.12em;
            display: block;
            margin: 5px 0;
        }
        .numbers-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 7px;
        }
        .numbers-table th, .numbers-table td { padding: 7px 6px; text-align: center; }
        .numbers-table th {
            background: #eee7fa;
            color: #8352d4;
            font-weight: 600;
            font-size: .97em;
        }
        .numbers-table td { color: #333; font-size: 1.05em;}
        .numbers-table tr:nth-child(even) td { background: #f8f6ff;}
        .numbers-table tr:nth-child(odd) td { background: #f3f1fa;}
        .no-records { text-align: center; color: #a476e2; margin: 2.3em 0 1.3em 0; }
    </style>
</head>
<body>
    <div class="header" id="main-header">
        <div class="header-title">
            <img src="/images/azm-logo.png" alt="Logo" class="header-logo" />
            AZM2D Game
        </div>
        <div class="header-right">
            <a href="/b/2dnumber.php" class="back-btn"><i class="bi bi-arrow-left"></i> </a>
        </div>
    </div>
    <div class="container">
        <h2>ထိုးမှတ်တမ်း</h2>
        <div class="week-info">
            <div class="week-info-title">ယခုအပတ် ထိုးမှတ်တမ်း</div>
            <div class="week-info-dates"><?= date('Y-m-d', strtotime($last_monday)) ?> မှ <?= date('Y-m-d', strtotime($current_friday)) ?> ထိ</div>
            <div class="cleanup-notice">
                <i class="bi bi-info-circle"></i> မှတ်ချက် - ဤအပတ်မှတ်တမ်းများကို <?= date('Y-m-d', strtotime($next_cleanup)) ?> တွင် ရှင်းလင်းပါမည်။
            </div>
        </div>
        <?php if ($total_bet_amount > 0): ?>
        <div class="total-amount">
            စုစုပေါင်း ထိုးကြေး: <?= number_format($total_bet_amount) ?> ကျပ်
        </div>
        <?php endif; ?>
        <?php if (!empty($session_bets)): ?>
            <?php foreach ($session_bets as $stype => $betlist): ?>
                <div class="session-block">
                    <div class="session-title"><?= htmlspecialchars($stype) ?></div>
                    <?php
                    // Group bets by bet_date + bet_type
                    $grouped = [];
                    foreach ($betlist as $bet) {
                        $key = $bet['bet_date'] . ' ' . $bet['bet_type'];
                        if (!isset($grouped[$key])) {
                            $grouped[$key] = [
                                'bet_date' => $bet['bet_date'],
                                'bet_type' => $bet['bet_type'],
                                'numbers' => []
                            ];
                        }
                        $grouped[$key]['numbers'][] = [
                            'number' => $bet['number'],
                            'amount' => $bet['amount'],
                            'created_at' => $bet['created_at']
                        ];
                    }
                    ?>
                    <?php foreach ($grouped as $key => $data): ?>
                        <div class="record-block">
                            <div class="date-header">
                                <span class="date-label"><?= htmlspecialchars($data['bet_date']) ?></span>
                                <span class="day-label"><?= day_name_mm($data['bet_date']) ?></span>
                            </div>
                            <span class="session-label"><?= htmlspecialchars(session_label($data['bet_type'])) ?></span>
                            <table class="numbers-table">
                                <thead>
                                    <tr>
                                        <th>နံပါတ်</th>
                                        <th>ထိုးကြေး</th>
                                        <th>ထိုးချိန်</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($data['numbers'] as $n): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($n['number']) ?></td>
                                        <td><?= number_format($n['amount']) ?></td>
                                        <td><?= date('H:i:s', strtotime($n['created_at'])) ?></td>
                                    </tr>
                                    <?php endforeach ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endforeach ?>
                </div>
            <?php endforeach ?>
        <?php else: ?>
            <div class="no-records">ယခုအပတ် ထိုးမှတ်တမ်း မရှိသေးပါ။</div>
        <?php endif ?>
    </div>
</body>
</html>