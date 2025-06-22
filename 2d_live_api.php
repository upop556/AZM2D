<?php
// 2d_live_api.php
header('Content-Type: text/html; charset=utf-8');
session_start();

// Determine which page to show
$page = $_GET['page'] ?? 'index';

// Current Date for use across all pages
$current_date = date('Y-m-d');
$current_user = 'upop556';

// Function for API data retrieval (used on index page)
function getLotteryData() {
    $api_url = 'https://api.thaistock2d.com/live';
    $json = @file_get_contents($api_url);
    $data = $json !== false ? json_decode($json, true) : null;
    
    $target_times = [
        "11:00:00" => "11:00 AM",
        "12:01:00" => "12:01 PM",
        "15:00:00" => "03:00 PM",
        "16:30:00" => "04:30 PM"
    ];

    // Get latest 4 result for each time slot (latest one for each time)
    $latest_results = [];
    $latest_twod = null;
    $latest_datetime = null;

    if ($data && !empty($data['result'])) {
        $used_time = [];
        // Go from latest to oldest
        foreach (array_reverse($data['result']) as $row) {
            $open_time = $row['open_time'];
            if (isset($target_times[$open_time]) && !isset($used_time[$open_time])) {
                // Store only the latest for each open_time
                $latest_results[$open_time] = [
                    'label' => $target_times[$open_time],
                    'set' => $row['set'],
                    'value' => $row['value'],
                    'twod' => $row['twod'],
                    'datetime' => $row['stock_datetime']
                ];
                $used_time[$open_time] = true;
            }
            // Find absolutely latest 2D (regardless of time slot)
            if (
                is_null($latest_datetime) ||
                strtotime($row['stock_datetime']) > strtotime($latest_datetime)
            ) {
                $latest_twod = $row['twod'];
                $latest_datetime = $row['stock_datetime'];
            }
            if (count($latest_results) === 4 && $latest_twod !== null) {
                break;
            }
        }
    }
    
    return [
        'latest_results' => $latest_results,
        'latest_twod' => $latest_twod,
        'latest_datetime' => $latest_datetime,
        'target_times' => $target_times,
        'order' => ["11:00:00", "12:01:00", "15:00:00", "16:30:00"]
    ];
}

// Function to get closing dates
function getClosingDates() {
    // File to store closing dates (in real app, use database)
    $dates_file = 'lottery_closing_dates.txt';

    // Read existing closing dates
    $closing_dates = [];
    if (file_exists($dates_file)) {
        $lines = file($dates_file, FILE_IGNORE_NEW_LINES);
        foreach ($lines as $line) {
            $parts = explode('|', $line);
            $date = $parts[0] ?? '';
            $reason = $parts[1] ?? 'အားလပ်ရက်';
            if ($date) {
                $closing_dates[] = [
                    'date' => $date,
                    'reason' => $reason
                ];
            }
        }
    }

    // Sort dates by next upcoming
    usort($closing_dates, function($a, $b) {
        return strtotime($a['date']) - strtotime($b['date']);
    });

    // Filter only future dates (optional)
    $today = date('Y-m-d');
    $upcoming_dates = array_filter($closing_dates, function($item) use ($today) {
        return $item['date'] >= $today;
    });

    // Keep history dates (last 30 days)
    $past_dates = array_filter($closing_dates, function($item) use ($today) {
        $date_diff = (strtotime($today) - strtotime($item['date'])) / (60 * 60 * 24);
        return $item['date'] < $today && $date_diff <= 30;
    });

    // Combine upcoming and past dates
    $display_dates = array_merge($upcoming_dates, $past_dates);
    usort($display_dates, function($a, $b) {
        return strtotime($a['date']) - strtotime($b['date']);
    });
    
    return $display_dates;
}

// Function to get bet records
function getBetRecords($user_id) {
    // Sample: Simulated data for demo (replace with DB query in production)
    $records = [
        [
            'bet_time'    => '08:23:14 AM',
            'numbers'     => ['34', '43', '56', '65', '77', '88'],
            'amount'      => 1000,
            'total'       => 7000,
            'date'        => date('Y-m-d'),
        ],
        [
            'bet_time'    => '11:12:25 AM',
            'numbers'     => ['12', '21', '55', '82'],
            'amount'      => 2000,
            'total'       => 8000,
            'date'        => date('Y-m-d'),
        ],
        [
            'bet_time'    => '01:45:02 PM',
            'numbers'     => ['22', '29', '37'],
            'amount'      => 1500,
            'total'       => 4500,
            'date'        => date('Y-m-d'),
        ],
    ];

    // Filter for today only (not necessary in this mock, but for real DB)
    $today = date('Y-m-d');
    $today_records = array_filter($records, function($rec) use ($today) {
        return $rec['date'] === $today;
    });
    
    return $today_records;
}

// Function to get winners data
function getWinnersData() {
    // Latest draw info (mocked, replace with DB query in prod)
    $latest_draw = [
        'number' => '37',
        'updated_at' => '2025-06-20 16:30', // 24hr format, e.g. 16:30
    ];

    // Sample winners data (replace with DB query in production)
    $winners = [
        [
            'number' => '37',
            'name' => 'မောင်မောင်',
            'phone' => '09*****4545',
            'bet_amount' => 10000,
            'winning_amount' => 950000,
        ],
        [
            'number' => '42',
            'name' => 'မဝါ',
            'phone' => '09*****8721',
            'bet_amount' => 8000,
            'winning_amount' => 760000,
        ],
        [
            'number' => '51',
            'name' => 'ထွန်းထွန်း',
            'phone' => '09*****3325',
            'bet_amount' => 5000,
            'winning_amount' => 475000,
        ],
        [
            'number' => '18',
            'name' => 'သန်းနွယ်',
            'phone' => '09*****2270',
            'bet_amount' => 3000,
            'winning_amount' => 285000,
        ],
        [
            'number' => '26',
            'name' => 'စိုးမြင့်',
            'phone' => '09*****1138',
            'bet_amount' => 2000,
            'winning_amount' => 190000,
        ],
        [
            'number' => '63',
            'name' => 'နန်းခမ်း',
            'phone' => '09*****9753',
            'bet_amount' => 1500,
            'winning_amount' => 142500,
        ],
        [
            'number' => '47',
            'name' => 'စန်းလင်း',
            'phone' => '09*****4469',
            'bet_amount' => 1000,
            'winning_amount' => 95000,
        ],
        [
            'number' => '34',
            'name' => 'မြသီတာ',
            'phone' => '09*****5782',
            'bet_amount' => 500,
            'winning_amount' => 47500,
        ],
        [
            'number' => '29',
            'name' => 'အေးမြ',
            'phone' => '09*****2017',
            'bet_amount' => 200,
            'winning_amount' => 19000,
        ],
        [
            'number' => '75',
            'name' => 'ဇော်လင်း',
            'phone' => '09*****3344',
            'bet_amount' => 100,
            'winning_amount' => 9500,
        ],
    ];

    // Sort winners by winning amount (highest first)
    usort($winners, function($a, $b) {
        return $b['winning_amount'] - $a['winning_amount'];
    });

    // Top 3 winners for highlighting
    $top_3 = array_slice($winners, 0, 3);
    $other_winners = array_slice($winners, 3);

    // For bar: Count how many winners have same number as latest_draw
    $matching_number = $latest_draw['number'];
    $matching_count = 0;
    $total_count = count($winners);
    foreach ($winners as $w) {
        if ($w['number'] === $matching_number) $matching_count++;
    }
    $bar_percent = $total_count > 0 ? round($matching_count * 100 / $total_count) : 0;
    $bar_color = $bar_percent >= 50 ? '#1976d2' : '#b97e13';

    // Format updated_at
    $updated_at_str = '';
    if (!empty($latest_draw['updated_at'])) {
        $dt = DateTime::createFromFormat('Y-m-d H:i', $latest_draw['updated_at']);
        if ($dt) {
            $updated_at_str = $dt->format('Y-m-d g:i A');
        } else {
            $updated_at_str = htmlspecialchars($latest_draw['updated_at']);
        }
    }
    
    return [
        'latest_draw' => $latest_draw,
        'winners' => $winners,
        'top_3' => $top_3,
        'other_winners' => $other_winners,
        'matching_number' => $matching_number,
        'matching_count' => $matching_count,
        'total_count' => $total_count,
        'bar_percent' => $bar_percent,
        'bar_color' => $bar_color,
        'updated_at_str' => $updated_at_str
    ];
}

// Common CSS styles used across pages
$common_styles = '
    body { font-family: \'Segoe UI\', Arial, sans-serif; background: #f6fafd; margin: 0; padding-bottom: 70px; }
    .container { max-width: 370px; margin: 22px auto; background: #fff; border-radius: 13px; box-shadow: 0 1px 9px rgba(60,60,90,0.09); padding: 18px 10px 16px 10px; }
    .header { text-align: center; font-size: 1.65em; color: #1976d2; font-weight: bold; margin-bottom: 4px; letter-spacing:1px;}
    .sub-header { text-align: center; color: #888; font-size: 1em; margin-bottom: 18px;}
    .back-btn { display: inline-block; margin-bottom: 15px; color: #1976d2; text-decoration: none; padding: 6px 12px; border-radius: 4px; transition: background 0.2s; }
    .back-btn:hover { background: #e3f2fd; }
    .back-btn i { margin-right: 5px; }
    .no-record { text-align:center; color:#b22; margin:28px 0 14px 0; font-size:1.1em;}
    @media (max-width: 500px) {
        .container { max-width:99vw; }
        .header { font-size:1.35em;}
    }
';

// Page-specific content based on $page variable
switch ($page) {
    case 'close_dates':
        // Get closing dates
        $display_dates = getClosingDates();
        
        // Output page content
        ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>2D ထီပိတ်ရက်များ</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        <?php echo $common_styles; ?>
        .today-marker {
            background: #e3f2fd;
            padding: 8px 12px;
            border-radius: 6px;
            margin-bottom: 15px;
            text-align: center;
            font-weight: 500;
            color: #1976d2;
            border: 1px solid #bbdefb;
        }
        
        .dates-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 15px;
        }
        .dates-table th { 
            background: #f0f5ff; 
            padding: 8px; 
            text-align: left;
            color: #1976d2;
            font-size: 0.95em;
            border-bottom: 1px solid #e0e8f7;
        }
        .dates-table td { 
            padding: 10px 8px; 
            border-bottom: 1px solid #f0f4f9; 
        }
        .date-row { transition: background 0.2s; }
        .date-row:hover { background-color: #f5f9ff; }
        .date-value { font-weight: 500; }
        .date-myanmar { color: #666; font-size: 0.9em; }
        .date-reason { color: #666; }
        
        .upcoming-date { 
            background-color: #fff8e1; 
        }
        .today-date {
            background-color: #e8f5e9;
            font-weight: bold;
        }
        .past-date {
            color: #777;
        }
        
        .calendar-icon {
            display: block;
            text-align: center;
            font-size: 3em;
            color: #1976d2;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Back Button -->
        <a href="?page=index" class="back-btn">
            <i class="bi bi-arrow-left"></i> ပင်မစာမျက်နှာသို့
        </a>
        
        <i class="bi bi-calendar-week calendar-icon"></i>
        <div class="header">2D ထီပိတ်ရက်များ</div>
        <div class="sub-header"><?= htmlspecialchars(date('Y')) ?> ခုနှစ်</div>

        <div class="today-marker">
            ယနေ့ရက်စွဲ: <?= htmlspecialchars(date('d-m-Y')) ?>
        </div>
        
        <?php if (empty($display_dates)): ?>
            <div class="no-record">ထီပိတ်ရက် မှတ်တမ်းမရှိသေးပါ</div>
        <?php else: ?>
            <table class="dates-table">
                <thead>
                    <tr>
                        <th>ရက်စွဲ</th>
                        <th>အကြောင်းအရာ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($display_dates as $date_info): 
                        // Determine the row class
                        $row_class = 'date-row';
                        if ($date_info['date'] == $current_date) {
                            $row_class .= ' today-date';
                        } elseif ($date_info['date'] > $current_date) {
                            $row_class .= ' upcoming-date';
                        } else {
                            $row_class .= ' past-date';
                        }
                    ?>
                        <tr class="<?= $row_class ?>">
                            <td>
                                <div class="date-value">
                                    <?php if ($date_info['date'] == $current_date): ?>
                                        <i class="bi bi-calendar-check"></i>
                                    <?php elseif ($date_info['date'] > $current_date): ?>
                                        <i class="bi bi-calendar-plus"></i>
                                    <?php else: ?>
                                        <i class="bi bi-calendar-x"></i>
                                    <?php endif; ?>
                                    <?= htmlspecialchars($date_info['date']) ?>
                                </div>
                                <div class="date-myanmar">
                                    <?php
                                    // Format to Myanmar preferred date format
                                    $timestamp = strtotime($date_info['date']);
                                    echo htmlspecialchars(date('d-m-Y', $timestamp));
                                    ?>
                                </div>
                            </td>
                            <td class="date-reason"><?= htmlspecialchars($date_info['reason']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
        <?php
        break;
        
    case 'record':
        // Get bet records
        $today_records = getBetRecords($current_user);
        
        // Output page content
        ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bet Record</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        <?php echo $common_styles; ?>
        .record-block { background: #f5f7fb; border-radius: 10px; margin: 12px 0; padding: 14px 10px 10px 10px; box-shadow:0 1px 2px #e6e9ed;}
        .bet-time { font-weight: bold; color: #1387c6; font-size: 1.04em; }
        .bet-numbers { margin: 9px 0 4px 0; }
        .bet-number { display: inline-block; background: #dbeafe; color: #1976d2; font-weight: bold; margin: 0 4px 4px 0; padding: 4px 10px; border-radius: 7px; font-size: 1.07em;}
        .bet-amount { color: #b97e13; font-size: 1em; font-weight: 500; margin-left: 5px;}
        .bet-total { color: #387d2c; font-size: 1.08em; font-weight: 500; margin-left: 10px;}
    </style>
</head>
<body>
    <div class="container">
        <!-- Back Button -->
        <a href="?page=index" class="back-btn">
            <i class="bi bi-arrow-left"></i> ပင်မစာမျက်နှာသို့
        </a>

        <div class="header"><?= htmlspecialchars($current_user) ?> ၏ ​တစ်နေ့တာ ထိုးကြေး မှတ်တမ်း</div>
        <div class="sub-header">(<?= htmlspecialchars($current_date) ?>)</div>
        <?php if (empty($today_records)): ?>
            <div class="no-record">ယနေ့အတွက် မှတ်တမ်းမရှိပါ။</div>
        <?php else: ?>
            <?php foreach ($today_records as $rec): ?>
                <div class="record-block">
                    <div class="bet-time"><?= htmlspecialchars($rec['bet_time']) ?></div>
                    <div class="bet-numbers">
                        <?php foreach ($rec['numbers'] as $num): ?>
                            <span class="bet-number"><?= htmlspecialchars($num) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <div>
                        <span class="bet-amount">တစ်လုံးလျှင် <?= number_format($rec['amount']) ?> Ks</span>
                        <span class="bet-total">စုစုပေါင်း <?= number_format($rec['total']) ?> Ks</span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>
        <?php
        break;
        
    case 'winner':
        // Get winners data
        $winners_data = getWinnersData();
        extract($winners_data);
        
        // Output page content
        ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>2D ကံထူးရှင်များ</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        <?php echo $common_styles; ?>
        /* Draw info bar */
        .draw-info-bar-wrap {
            background: #f5faff;
            border-radius: 10px;
            padding: 13px 14px 14px 14px;
            margin-bottom: 18px;
            border: 1.5px solid #e9ecf5;
            box-shadow: 0 1px 5px 0 rgba(40,60,140,0.04);
        }
        .draw-number {
            font-size: 1.25em;
            font-weight: bold;
            color: #1976d2;
            letter-spacing: 1px;
            display: inline-block;
            margin-right: 14px;
        }
        .draw-updated {
            color: #b97e13;
            font-size: 1em;
            font-weight: 500;
            margin-left: 7px;
        }
        .draw-bar-label {
            display: inline-block;
            font-size: 0.97em;
            color: #444;
            margin-top: 8px;
            margin-bottom: 2px;
        }
        .draw-bar-bg {
            background: #e6ecfa;
            border-radius: 7px;
            width: 100%;
            height: 17px;
            margin-top: 2px;
            overflow: hidden;
        }
        .draw-bar-fg {
            background: <?= $bar_color ?>;
            height: 100%;
            border-radius: 7px;
            width: <?= $bar_percent ?>%;
            transition: width 0.9s cubic-bezier(.85,.07,.49,1.01);
            min-width: 1px;
        }
        .draw-bar-text {
            position: absolute;
            left: 50%;
            top: 47%;
            transform: translate(-50%, -50%);
            font-size: 0.98em;
            color: #123;
            font-weight: bold;
            letter-spacing: 1px;
        }
        .draw-bar-relative {
            position: relative;
            width: 100%;
            height: 100%;
        }
        .triangle-winners {
            display: flex;
            justify-content: center;
            align-items: flex-end;
            margin-bottom: 22px;
            gap: 10px;
            min-height: 160px;
        }
        .triangle-card {
            background: #fffbe6;
            box-shadow: 0 0 12px 0 rgba(230, 200, 40, 0.10);
            border-radius: 13px 13px 13px 13px;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 13px 10px 13px 10px;
            min-width: 105px;
            position: relative;
            transition: transform 0.2s;
        }
        .triangle-card.gold { border: 2.7px solid gold; z-index:3; transform: scale(1.13);}
        .triangle-card.silver { border: 2px solid silver; z-index:2;}
        .triangle-card.bronze { border: 2px solid #cd7f32; z-index:1;}
        .triangle-card .medal {
            font-size: 1.45em;
            margin-bottom: 2px;
        }
        .triangle-card.gold .medal { color: gold; }
        .triangle-card.silver .medal { color: silver; }
        .triangle-card.bronze .medal { color: #cd7f32; }
        .triangle-card .number {
            font-weight: bold;
            font-size: 1.2em;
            color: #1976d2;
            margin-bottom: 3px;
        }
        .triangle-card .name {
            font-weight: 500;
            font-size: 1.08em;
        }
        .triangle-card .phone {
            color: #666;
            font-size: 0.89em;
            margin-bottom: 5px;
        }
        .triangle-card .amount {
            color: #b97e13;
            font-size: 0.95em;
        }
        .triangle-card .winning {
            color: #387d2c;
            font-weight: bold;
            font-size: 1.08em;
        }
        .triangle-card.gold { margin-bottom: 0; min-height: 148px;}
        .triangle-card.silver { margin-bottom: 18px; min-height: 118px;}
        .triangle-card.bronze { margin-bottom: 18px; min-height: 118px;}
        .winners-table { width: 100%; border-collapse: collapse; }
        .winners-table th { background: #f0f5ff; padding: 8px 5px; color: #1976d2; font-size: 0.9em; text-align: center; border-bottom: 1px solid #e0e8f7; }
        .winners-table td { padding: 10px 5px; text-align: center; border-bottom: 1px solid #f0f4f9; }
        .winner-row:hover { background-color: #f5f9ff; }
        @media (max-width: 600px) {
            .winners-table th { font-size: 0.8em; padding: 6px 3px; }
            .winners-table td { padding: 8px 3px; }
            .number { font-size: 1em; }
            .triangle-winners { gap: 4px; }
            .triangle-card { min-width: 90px; padding: 8px 5px 10px 5px;}
            .triangle-card.gold { min-height: 110px; }
            .triangle-card.silver, .triangle-card.bronze { min-height: 87px;}
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Back Button -->
        <a href="?page=index" class="back-btn">
            <i class="bi bi-arrow-left"></i> ပင်မစာမျက်နှာသို့
        </a>
        
        <div class="header">2D ကံထူးရှင်များ</div>
        <div class="sub-header">(<?= htmlspecialchars($current_date) ?>)</div>
        
        <!-- Draw info bar -->
        <div class="draw-info-bar-wrap">
            <span class="draw-number"><?= htmlspecialchars($latest_draw['number']) ?></span>
            <span class="draw-updated">Updated at:<br><?= $updated_at_str ?></span>
            <div class="draw-bar-label">
                ကံထူးရှင်များမှ <?= htmlspecialchars($matching_number) ?> နံပါတ်တူညီသူများ (<?= $matching_count ?>/<?= $total_count ?>)
            </div>
            <div class="draw-bar-bg">
                <div class="draw-bar-relative">
                    <div class="draw-bar-fg"></div>
                    <span class="draw-bar-text"><?= $bar_percent ?>%</span>
                </div>
            </div>
        </div>

        <?php if (empty($winners)): ?>
            <div class="no-record">ယနေ့အတွက် ကံထူးရှင်မှတ်တမ်း မရှိသေးပါ။</div>
        <?php else: ?>
            <!-- Triangle style for Top 3 winners -->
            <div class="triangle-winners">
                <!-- Silver -->
                <div class="triangle-card silver">
                    <div class="medal">🥈</div>
                    <div class="number"><?= htmlspecialchars($top_3[1]['number']) ?></div>
                    <div class="name"><?= htmlspecialchars($top_3[1]['name']) ?></div>
                    <div class="phone"><?= htmlspecialchars($top_3[1]['phone']) ?></div>
                    <div class="amount"><?= number_format($top_3[1]['bet_amount']) ?> Ks</div>
                    <div class="winning"><?= number_format($top_3[1]['winning_amount']) ?> Ks</div>
                </div>
                <!-- Gold -->
                <div class="triangle-card gold">
                    <div class="medal">🥇</div>
                    <div class="number"><?= htmlspecialchars($top_3[0]['number']) ?></div>
                    <div class="name"><?= htmlspecialchars($top_3[0]['name']) ?></div>
                    <div class="phone"><?= htmlspecialchars($top_3[0]['phone']) ?></div>
                    <div class="amount"><?= number_format($top_3[0]['bet_amount']) ?> Ks</div>
                    <div class="winning"><?= number_format($top_3[0]['winning_amount']) ?> Ks</div>
                </div>
                <!-- Bronze -->
                <div class="triangle-card bronze">
                    <div class="medal">🥉</div>
                    <div class="number"><?= htmlspecialchars($top_3[2]['number']) ?></div>
                    <div class="name"><?= htmlspecialchars($top_3[2]['name']) ?></div>
                    <div class="phone"><?= htmlspecialchars($top_3[2]['phone']) ?></div>
                    <div class="amount"><?= number_format($top_3[2]['bet_amount']) ?> Ks</div>
                    <div class="winning"><?= number_format($top_3[2]['winning_amount']) ?> Ks</div>
                </div>
            </div>

            <!-- Table for other winners -->
            <table class="winners-table">
                <thead>
                    <tr>
                        <th>အဆင့်</th>
                        <th>နံပါတ်</th>
                        <th>အမည်/ဖုန်း</th>
                        <th>ထိုးငွေ</th>
                        <th>အနိုင်ရငွေ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($other_winners as $index => $winner): ?>
                        <tr class="winner-row">
                            <td><?= $index + 4 ?></td>
                            <td><div class="number"><?= htmlspecialchars($winner['number']) ?></div></td>
                            <td>
                                <div class="name"><?= htmlspecialchars($winner['name']) ?></div>
                                <div class="phone"><?= htmlspecialchars($winner['phone']) ?></div>
                            </td>
                            <td><div class="amount"><?= number_format($winner['bet_amount']) ?> Ks</div></td>
                            <td><div class="winning"><?= number_format($winner['winning_amount']) ?> Ks</div></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
        <?php
        break;

    case 'bet':
        // Handle betting page (redirect to 2d.php with time parameter)
        $time = $_GET['time'] ?? '11:00:00';
        header("Location: 2d.php?time=$time");
        exit;
        break;
        
    default: 
        // Main index page (default)
        $lottery_data = getLotteryData();
        extract($lottery_data);
        ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>2D Latest Results</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        <?php echo $common_styles; ?>
        .latest-header {
            text-align:center;
            font-size: 4em;
            color: #222;
            font-weight: bold;
            margin-top: 10px;
            margin-bottom: 0;
            letter-spacing: 6px;
            line-height: 1;
            text-shadow: 0 4px 18px #eee, 0 1.5px 0 #bbb;
        }
        .latest-dt { text-align:center; color:#888; font-size:1em; margin-bottom:14px; }
        .menu-row {
            display: flex;
            justify-content: space-around;
            align-items: center;
            margin: 12px auto 18px auto;
            max-width: 340px;
        }
        .menu-btn {
            flex: 1 1 0;
            background: #f8f5ff;
            border-radius: 9px;
            margin: 0 5px;
            text-align: center;
            padding: 13px 0 8px 0;
            cursor: pointer;
            box-shadow: 0 1px 6px 0 #e7e7f7;
            border: none;
            outline: none;
            transition: background 0.12s;
            font-size: 1.03em;
            min-width: 0;
        }
        .menu-btn:hover {
            background: #e5e3ff;
        }
        .menu-btn i {
            display: block;
            font-size: 1.65em;
            color: #7b4de6;
            margin-bottom: 4px;
        }
        .menu-label {
            font-size: 0.99em;
            color: #4a4773;
            font-weight: 500;
            letter-spacing: 0.3px;
        }
        .block { background: #f4f7fb; border-radius: 8px; margin: 12px 0; padding: 12px 10px 7px 10px; box-shadow:0 1px 2px #e6e9ed; }
        .time-label { font-weight:bold; color: #234; letter-spacing:1px; font-size:1.08em;}
        .twod { font-size:2.1em; color:#1976d2; font-weight:bold; margin: 10px 0 3px 0;}
        .set-label { color: #387d2c; margin-right:6px; font-size:1em;}
        .value-label { color: #b97e13; margin-left:8px; font-size:1em;}
        .dt { color: #888; font-size:0.95em; margin-top:3px;}
        
        /* Betting Button and Popup Styles */
        .bet-btn-fixed {
            position: fixed;
            left: 0; right: 0; bottom: 0;
            width: 100vw;
            max-width: 370px;
            margin: auto;
            background: #ffae42;
            color: #333;
            font-size: 1.28em;
            font-weight: bold;
            border-radius: 13px 13px 0 0;
            box-shadow: 0 -2px 12px #ddd;
            padding: 14px 0 10px 0;
            text-align: center;
            cursor: pointer;
            z-index: 1001;
            letter-spacing: 1px;
            transition: background 0.13s;
        }
        .bet-btn-fixed:hover { background: #ffa97d; }
        .bet-popup-bg {
            display: none;
            position: fixed; left:0; top:0; width:100vw; height:100vh;
            background: rgba(32,20,20,0.29);
            z-index: 2000;
        }
        .bet-popup {
            position: fixed; left:0; right:0; bottom: 0;
            max-width: 370px;
            margin: auto;
            background: #fff;
            border-radius: 18px 18px 0 0;
            box-shadow: 0 -2px 18px #bbb;
            padding: 20px 18px 24px 18px;
            z-index: 2001;
            animation: popupUp 0.19s;
        }
        @keyframes popupUp { from { transform: translateY(100%);} to { transform: translateY(0);} }
        .bet-popup-title {
            font-size:1.25em;
            font-weight:bold;
            color:#632;
            text-align:center;
            margin-bottom:18px;
        }
        .bet-time-btn {
            display: block;
            width: 100%;
            margin: 8px 0;
            padding: 13px 0;
            font-size: 1.08em;
            color: #fff;
            background: linear-gradient(90deg,#ff9a44,#ff5e62);
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.14s;
        }
        .bet-time-btn:hover { background: linear-gradient(90deg,#ff7f00,#e94a36);}
        .bet-popup-close {
            position: absolute;
            top: 10px; right: 18px;
            font-size: 1.3em;
            color: #c33;
            background: none;
            border: none;
            cursor: pointer;
        }
        
        @media (max-width: 500px) {
            .latest-header { font-size:2.5em; letter-spacing: 2.5px;}
            .twod { font-size:1.3em;}
            .menu-row { max-width: 99vw; }
            .menu-btn { font-size:0.97em; padding: 10px 0 5px 0;}
            .bet-btn-fixed, .bet-popup { max-width: 99vw; }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($latest_twod): ?>
            <div class="latest-header"><?= htmlspecialchars($latest_twod) ?></div>
            <div class="latest-dt">Updated: <?= htmlspecialchars(date('Y-m-d H:i:s', strtotime($latest_datetime))) ?></div>
        <?php endif; ?>

        <!-- Menu Buttons Row -->
        <div class="menu-row">
            <button class="menu-btn" onclick="window.location.href='?page=record'">
                <i class="bi bi-file-earmark-text"></i>
                <span class="menu-label">မှတ်တမ်း</span>
            </button>
            <button class="menu-btn" onclick="window.location.href='?page=winner'">
                <i class="bi bi-award"></i>
                <span class="menu-label">ကံထူးရှင်</span>
            </button>
            <button class="menu-btn" onclick="window.location.href='?page=close_dates'">
                <i class="bi bi-calendar2-week"></i>
                <span class="menu-label">ထီပိတ်ရက်</span>
            </button>
        </div>

        <?php
        $has = false;
        foreach ($order as $tm):
            if (isset($latest_results[$tm])):
                $r = $latest_results[$tm];
                $has = true;
        ?>
            <div class="block">
                <div class="time-label"><?= $r['label'] ?></div>
                <div class="set-label">Set</div> <span><?= htmlspecialchars($r['set']) ?></span>
                <div class="value-label">Value</div> <span><?= htmlspecialchars($r['value']) ?></span>
                <div class="twod">2D <?= htmlspecialchars($r['twod']) ?></div>
            </div>
        <?php
            endif;
        endforeach;
        if (!$has): ?>
            <div class="no-record">No data available.</div>
        <?php endif; ?>
    </div>
    
    <!-- Bet Button -->
    <div class="bet-btn-fixed" onclick="showBetPopup()">ထိုးမည်</div>

    <!-- Bet Popup -->
    <div id="betPopupBg" class="bet-popup-bg" onclick="hideBetPopup()">
        <div class="bet-popup" onclick="event.stopPropagation()">
            <button class="bet-popup-close" onclick="hideBetPopup()">&times;</button>
            <div class="bet-popup-title">ထိုးမည်အချိန်ရွေးပါ</div>
            <form id="betForm" method="get" action="2d.php">
                <button class="bet-time-btn" name="time" value="11:00:00" type="submit">11:00 AM</button>
                <button class="bet-time-btn" name="time" value="12:01:00" type="submit">12:01 PM</button>
                <button class="bet-time-btn" name="time" value="15:00:00" type="submit">03:00 PM</button>
                <button class="bet-time-btn" name="time" value="16:30:00" type="submit">04:30 PM</button>
            </form>
        </div>
    </div>

    <script>
        function showBetPopup() {
            document.getElementById('betPopupBg').style.display = 'block';
        }
        function hideBetPopup() {
            document.getElementById('betPopupBg').style.display = 'none';
        }
    </script>
</body>
</html>
        <?php
        break;
}
?>





<?php  
// 2d.php
// Create an array of numbers from 0 to 99 with leading zeros  
$numbers = [];  
for ($i = 0; $i < 100; $i++) {  
    $numbers[] = str_pad($i, 2, '0', STR_PAD_LEFT);  
}  
$rows = array_chunk($numbers, 5);  
$tops = range(0,9); // For top digit quick pick
$bottoms = range(0,9); // For bottom digit quick pick
$apuArr = ['00','11','22','33','44','55','66','77','88','99'];
$pawaArr = ['05','50','16','61','27','72','38','83','49','94'];
$nakhaArr = ['18','81','24','42','35','53','69','96','70','07'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Phone Number Pad UI (20x5) Fast Pick + R + Combination</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: Arial, sans-serif; background: #f8f8f8; margin: 0; padding: 0; }
        .container { max-width: 340px; margin: 18px auto 0 auto; background: #fff; border-radius: 18px; box-shadow: 0 2px 12px rgba(0,0,0,0.06), 0 1.5px 6px rgba(0,0,0,0.03); padding: 18px 8px 18px 8px; }
        h2 { text-align:center; margin-top:6px; margin-bottom:10px; font-size: 1.2em; letter-spacing: 1px; color: #222; }
        .balance-bar { margin: 0 auto 14px auto; max-width: 280px; display: flex; justify-content: flex-end; align-items: center; background: none; border-radius: 0; border: none; padding: 0; font-size: 1.06em; color: #bb2222; font-weight: bold; }
        .balance-text { color: #bb2222; font-weight: bold; letter-spacing: 0.02em; }
        .fastpick-bar { margin: 0 auto 10px auto; max-width: 300px; display: flex; justify-content: space-between; align-items: center; gap: 8px; flex-wrap: wrap; }
        .fastpick-btn { min-width: 70px; padding: 5px 14px; border: none; border-radius: 8px; background: #e0e7ef; color: #244; font-size: 1em; font-weight: bold; cursor: pointer; transition: background 0.13s, color 0.13s; outline: none; }
        .fastpick-btn.selected, .fastpick-btn:active { background: #4da8ff; color: #fff; }
        .bet-controls { display: flex; align-items: center; gap: 8px; }
        .amount-input { font-size: 1.02em; width: 70px; padding: 5px 8px; border-radius: 6px; border: 1px solid #b7b7b7; outline: none; background: #f2f5fa; }
        .amount-input:focus { border-color: #4da8ff; background: #fff; }
        .bet-btn { background: #0bc66b; color: #fff; border: none; border-radius: 7px; font-size: 1em; font-weight: bold; padding: 5px 14px; cursor: pointer; transition: background 0.13s, color 0.13s; }
        .bet-btn:active, .bet-btn:hover { background: #089e58; }
        .legend-box { margin: 10px auto 18px auto; background: #f8f8f8; border-radius: 10px; border: 1px solid #e3e7ef; padding: 10px 6px 10px 6px; max-width: 300px; font-size: 0.98em; display: flex; justify-content: space-around; align-items: center; gap: 6px; }
        .legend-color { width: 28px; height: 28px; border-radius: 8px; display: inline-block; border: 2px solid #b6b6b6; font-weight: bold; font-size: 1.07em; text-align: center; line-height: 28px; }
        .legend-low { background: #ffda92; color: #ffda92; border-color: #ffda92;}
        .legend-medium { background: #ffe66d; color: #ffe66d; border-color: #ffe66d;}
        .legend-high { background: #0bc66b; color: #0bc66b; border-color: #0bc66b;}
        .legend-r { background: #1b263b; color: #fff; border-color: #1b263b; font-family: Arial, sans-serif; font-size: 0.98em; text-decoration: none; font-weight: bold; letter-spacing: 0.1em; text-align: center; line-height: 28px; cursor: pointer; transition: background 0.12s, color 0.12s, border 0.12s; display: flex; align-items: center; justify-content: center; }
        .legend-r:hover, .legend-r:active { background: #274472; border-color: #274472; color: #fff; }
        .popup-mask { position: fixed; left:0; top:0; right:0; bottom:0; background: rgba(0,0,0,0.16); z-index: 9999; display: none; }
        .popup-dialog { position: fixed; left: 0; right: 0; top: 60px; margin: auto; background: #fff; border-radius: 14px; box-shadow: 0 6px 36px rgba(60,60,80,0.13); max-width: 340px; width: 98vw; z-index: 10000; display: none; padding: 24px 12px 16px 12px; }
        .popup-dialog h3 { margin-top: 0; margin-bottom: 12px; font-size: 1.09em; color: #274472; letter-spacing: 0.02em; }
        .popup-dialog .popup-content { font-size: 1.05em; margin-bottom: 12px; word-break: break-all; }
        .quickpick-row { display: flex; gap: 7px; justify-content: center; margin-bottom: 10px; margin-top: -4px; flex-wrap: wrap; }
        .quickpick-btn { padding: 3px 9px; border-radius: 7px; border: none; background: #e0e7ef; color: #23395d; font-weight: bold; cursor: pointer; transition: background 0.13s, color 0.13s; font-size: 1em; }
        .quickpick-btn:hover, .quickpick-btn:active { background: #4da8ff; color: #fff; }

        .quickpick-section-label { font-weight:bold; font-size:1em; margin: 14px 0 2px 0; display:block; }
        .quickpick-inline-row { display: flex; gap: 7px; justify-content: center; margin-bottom: 7px; flex-wrap: wrap; }

        .quickpick-btn.selected { background: #4da8ff; color: #fff; }

        .top-pick-row, .bottom-pick-row { display: flex; gap: 6px; justify-content: center; margin-bottom: 12px; flex-wrap: wrap;}
        .top-pick-btn, .bottom-pick-btn { min-width: 27px; padding: 4px 0; font-size: 1.08em; font-weight: bold; background: #f1f6ff; color: #23395d; border: 1px solid #bcd2f7; border-radius: 7px; cursor: pointer; transition: background 0.13s, color 0.13s; }
        .top-pick-btn.selected, .top-pick-btn:active,
        .bottom-pick-btn.selected, .bottom-pick-btn:active { background: #4da8ff; color: #fff; border-color: #2666a3; }
        .popup-dialog .popup-close { display: inline-block; margin-top: 6px; padding: 4px 18px; background: #eee; color: #222; border: none; border-radius: 7px; font-size: 1em; font-weight: bold; cursor: pointer; transition: background 0.12s; }
        .popup-dialog .popup-close:hover, .popup-dialog .popup-close:active { background: #e0e7ef; }
        .popup-actions-row { display: flex; gap: 10px; justify-content: center; margin-top: 3px; margin-bottom: 8px; }
        .popup-action-btn { padding: 3px 14px; border-radius: 7px; border: none; background: #e0e7ef; color: #23395d; font-weight: bold; cursor: pointer; transition: background 0.13s, color 0.13s; font-size: 1em; }
        .popup-action-btn:hover, .popup-action-btn:active { background: #4da8ff; color: #fff; }
        .bet-list-table { width: 100%; border-collapse: separate; border-spacing: 0 7px; margin-bottom: 18px;}
        .bet-list-table th, .bet-list-table td { text-align: center; font-size: 1.04em; padding: 5px 5px; }
        .bet-list-table th { font-weight: bold; color: #274472; background: #f6faff;}
        .bet-list-table td .edit-amt { width: 60px; font-size:1em; border: 1px solid #b5c7e5; border-radius: 4px; padding: 2px 5px; }
        .bet-list-table td .del-btn { color: #bb2222; background: #fff0f0; border: 1px solid #fdc0c0; border-radius: 5px; padding: 2px 8px; font-size: 0.98em; cursor: pointer;}
        .bet-list-table td .del-btn:hover { background: #ffecec; }
        .bet-summary-row { font-weight:bold; background: #f8f8f8;}
        table { width: 100%; border-collapse: separate; border-spacing: 8px 8px; }
        td { border-radius: 12px; border: none; padding: 0; text-align: center; font-size: 1.25em; font-weight: 600; color: #23395d; box-shadow: 0 1px 2px rgba(80,80,80,0.04); transition: background 0.15s, color 0.15s; user-select: none; cursor: pointer; position: relative; height: 44px; }
        .cell-btn { width: 100%; height: 44px; border: none; outline: none; background: none; padding: 0; margin: 0; font-size: inherit; font-weight: inherit; border-radius: 12px; cursor: pointer; transition: background 0.15s, color 0.15s; display: flex; align-items: center; justify-content: center; }
        .color-low { background: #ffda92; color: #543600;}
        .color-medium { background: #ffe66d; color: #473f00;}
        .color-high { background: #0bc66b; color: #fff;}
        .color-full { background: #1b263b; color: #fff; text-decoration: line-through;}
        .color-default { background: #e0e7ef; color: #23395d;}
        .cell-btn.selected.color-low { box-shadow: 0 0 0 2.5px #ffb900; }
        .cell-btn.selected.color-medium { box-shadow: 0 0 0 2.5px #e8c600; }
        .cell-btn.selected.color-high { box-shadow: 0 0 0 2.5px #04ae5a; }
        .cell-btn.selected.color-full { box-shadow: 0 0 0 2.5px #14202c; }
        .cell-btn.selected.color-default { box-shadow: 0 0 0 2.5px #a5b0be; }
        @media (max-width: 500px) {  
            .container { max-width: 99vw;}
            table td { font-size:1em; padding: 0;}
            h2 { font-size: 1em; }
            .balance-bar { font-size: 0.99em; }
            .legend-box { font-size:0.93em; padding: 7px 2px;}
            .legend-color { width:21px; height:21px; line-height:21px; font-size:0.92em;}
            .legend-r { width:21px; height:21px; line-height:21px; font-size:0.92em;}
            .fastpick-btn { min-width: 0; padding: 3px 10px; font-size: 0.96em;}
            .bet-btn { padding: 3px 8px; font-size: 0.96em; }
            .amount-input { width: 55px; font-size: 0.96em; padding: 3px 5px; }
            .cell-btn { height:32px; }
            td { height:32px; }
            .popup-dialog { top: 18px; max-width: 99vw; padding: 18px 6px 12px 6px; }
            .top-pick-row, .bottom-pick-row, .quickpick-row, .quickpick-inline-row { gap: 3px; }
            .top-pick-btn, .bottom-pick-btn { min-width: 17px; padding: 2px 0; font-size: 1em; }
            .popup-action-btn { padding: 2px 8px; font-size: 0.95em;}
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>2d ထိုးရန်</h2>
        <!-- Balance Bar -->
        <div class="balance-bar">
            <span class="balance-text">လက်ကျန်ငွေ 0 ကျပ်</span>
        </div>
        <!-- Modified Fastpick bar with amount input and bet button -->
        <div class="fastpick-bar">
            <button type="button" class="fastpick-btn" id="fastpick-quick">အမြန်ရွေး</button>
            <div class="bet-controls">
                <input type="number" min="100" step="100" value="100" id="amount-input" class="amount-input" />
                <span style="font-size:0.97em;color:#555;">ကျပ်</span>
                <button class="bet-btn" id="bet-btn">ထိုးမည်</button>
            </div>
        </div>
        
        <div class="legend-box">
            <span class="legend-color legend-low"></span>
            <span class="legend-color legend-medium"></span>
            <span class="legend-color legend-high"></span>
            <span class="legend-color legend-r" id="legend-r-btn" title="Reverse Auto Select">R</span>
        </div>
        <table id="numberPadTable">
            <?php foreach ($rows as $rowIdx => $row): ?>
                <tr>
                    <?php foreach ($row as $colIdx => $num):  
                        $n = intval($num);
                        $colorClass = "color-default";
                        if ($n < 50) $colorClass = "color-low";
                        elseif ($n < 90) $colorClass = "color-medium";
                        elseif ($n < 99) $colorClass = "color-high";
                        else $colorClass = "color-full";
                        $btnId = "btn_{$rowIdx}_{$colIdx}";
                    ?>
                    <td>
                        <button 
                            type="button"
                            class="cell-btn <?= $colorClass ?>" 
                            id="<?= $btnId ?>" 
                            data-num="<?= $num ?>"
                        ><?= $num ?></button>
                    </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
    <!-- Fastpick Popup -->
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
            <div class="popup-actions-row">
                <button class="popup-action-btn" id="bet-popup-cancel">ပယ်ဖြတ်မည်</button>
                <button class="popup-action-btn" id="bet-popup-submit" style="background:#0bc66b;color:#fff;">အတည်ပြု</button>
            </div>
        </div>
    </div>

    <script>
    // Number pad button logic
    const btns = document.querySelectorAll('.cell-btn');
    btns.forEach(btn => {
        btn.addEventListener('click', function() {
            btn.classList.toggle('selected');
        });
    });

    // Amount input validation - modified to allow user custom input
    const amtInput = document.getElementById('amount-input');
    amtInput.addEventListener('input', function() {
        // Let users enter any valid value
        // Just ensure it's a positive number
        let val = parseInt(amtInput.value, 10);
        if (isNaN(val) || val < 0) {
            amtInput.value = '100'; // Default to 100 only if invalid
        }
    });

    // Fastpick popup logic
    function showPopup() {
        document.getElementById('popup-mask').style.display = 'block';
        document.getElementById('popup-dialog').style.display = 'block';
    }
    function hidePopup() {
        document.getElementById('popup-mask').style.display = 'none';
        document.getElementById('popup-dialog').style.display = 'none';
    }
    document.getElementById('fastpick-quick').onclick = showPopup;
    document.getElementById('popup-close').onclick = hidePopup;
    document.getElementById('popup-mask').onclick = hidePopup;

    // Quickpick row logic
    const apuArr = <?= json_encode($apuArr) ?>;
    const pawaArr = <?= json_encode($pawaArr) ?>;
    const nakhaArr = <?= json_encode($nakhaArr) ?>;

    let selectedBottomPopup = [];
    let selectedBkPopup = [];

    // --- Inline Bottom Pick in Popup (multi-select) ---
    const quickBottomBtns = document.querySelectorAll('.quick-bottom-btn');
    document.getElementById('quick-bottom-clear').onclick = function() {
        quickBottomBtns.forEach(btn => btn.classList.remove('selected'));
        selectedBottomPopup = [];
    };
    quickBottomBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const digit = btn.getAttribute('data-bottom');
            btn.classList.toggle('selected');
            if (btn.classList.contains('selected')) {
                if (!selectedBottomPopup.includes(digit)) {
                    selectedBottomPopup.push(digit);
                }
            } else {
                selectedBottomPopup = selectedBottomPopup.filter(d => d !== digit);
            }
        });
    });
    document.getElementById('quick-bottom-apply').onclick = function() {
        if (selectedBottomPopup.length === 0) return;
        const pairs = [];
        for (let i = 0; i < selectedBottomPopup.length; i++) {
            for (let j = 0; j < selectedBottomPopup.length; j++) {
                // include both 55 etc, so no i!==j
                pairs.push(selectedBottomPopup[i] + selectedBottomPopup[j]);
            }
        }
        btns.forEach(b => {
            const num = b.getAttribute('data-num');
            if (pairs.includes(num)) {
                b.classList.add('selected');
            } else {
                b.classList.remove('selected');
            }
        });
        hidePopup();
    };

    // --- Inline Bk Pick in Popup (multi-select) ---
    const quickBkBtns = document.querySelectorAll('.quick-bk-btn');
    document.getElementById('quick-bk-clear').onclick = function() {
        quickBkBtns.forEach(btn => btn.classList.remove('selected'));
        selectedBkPopup = [];
    };
    quickBkBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const digit = btn.getAttribute('data-comb');
            btn.classList.toggle('selected');
            if (btn.classList.contains('selected')) {
                if (!selectedBkPopup.includes(digit)) {
                    selectedBkPopup.push(digit);
                }
            } else {
                selectedBkPopup = selectedBkPopup.filter(d => d !== digit);
            }
        });
    });
    document.getElementById('quick-bk-apply').onclick = function() {
        if (selectedBkPopup.length === 0) return;
        btns.forEach(b => {
            const num = b.getAttribute('data-num');
            const sum = parseInt(num[0], 10) + parseInt(num[1], 10);
            if (selectedBkPopup.includes(String(sum))) {
                b.classList.add('selected');
            } else {
                b.classList.remove('selected');
            }
        });
        hidePopup();
    };

    // --- Quickpick types ---
    document.querySelectorAll('.quickpick-btn[data-quick]').forEach(btn => {
        btn.addEventListener('click', function() {
            btns.forEach(b => b.classList.remove('selected'));
            const type = btn.getAttribute('data-quick');
            if (type === 'even-even') {
                btns.forEach(b => {
                    const num = b.getAttribute('data-num');
                    if (parseInt(num[0], 10) % 2 === 0 && parseInt(num[1], 10) % 2 === 0) {
                        b.classList.add('selected');
                    }
                });
            } else if (type === 'odd-odd') {
                btns.forEach(b => {
                    const num = b.getAttribute('data-num');
                    if (parseInt(num[0], 10) % 2 === 1 && parseInt(num[1], 10) % 2 === 1) {
                        b.classList.add('selected');
                    }
                });
            } else if (type === 'even-odd') {
                btns.forEach(b => {
                    const num = b.getAttribute('data-num');
                    if (parseInt(num[0], 10) % 2 === 0 && parseInt(num[1], 10) % 2 === 1) {
                        b.classList.add('selected');
                    }
                });
            } else if (type === 'apu') {
                btns.forEach(b => {
                    const num = b.getAttribute('data-num');
                    if (apuArr.includes(num)) {
                        b.classList.add('selected');
                    }
                });
            } else if (type === 'pawa') {
                btns.forEach(b => {
                    const num = b.getAttribute('data-num');
                    if (pawaArr.includes(num)) {
                        b.classList.add('selected');
                    }
                });
            } else if (type === 'nakha') {
                btns.forEach(b => {
                    const num = b.getAttribute('data-num');
                    if (nakhaArr.includes(num)) {
                        b.classList.add('selected');
                    }
                });
            } else if (type === 'even-top') {
                btns.forEach(b => {
                    const num = b.getAttribute('data-num');
                    if (parseInt(num[0], 10) % 2 === 0) {
                        b.classList.add('selected');
                    }
                });
            } else if (type === 'odd-top') {
                btns.forEach(b => {
                    const num = b.getAttribute('data-num');
                    if (parseInt(num[0], 10) % 2 === 1) {
                        b.classList.add('selected');
                    }
                });
            }
            hidePopup();
        });
    });

    // --- Old Top/Bottom Pick logic (remains for completeness) ---
    const topPickBtns = document.querySelectorAll('.top-pick-btn');
    let selectedTops = [];
    topPickBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const digit = btn.getAttribute('data-top');
            btn.classList.toggle('selected');
            if (btn.classList.contains('selected')) {
                if (!selectedTops.includes(digit)) {
                    selectedTops.push(digit);
                }
            } else {
                selectedTops = selectedTops.filter(d => d !== digit);
            }
        });
    });

    const bottomPickBtns = document.querySelectorAll('.bottom-pick-btn');
    let selectedBottoms = [];
    bottomPickBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const digit = btn.getAttribute('data-bottom');
            btn.classList.toggle('selected');
            if (btn.classList.contains('selected')) {
                if (!selectedBottoms.includes(digit)) {
                    selectedBottoms.push(digit);
                }
            } else {
                selectedBottoms = selectedBottoms.filter(d => d !== digit);
            }
        });
    });

    document.getElementById('top-pick-clear').onclick = function() {
        topPickBtns.forEach(btn => btn.classList.remove('selected'));
        bottomPickBtns.forEach(btn => btn.classList.remove('selected'));
        selectedTops = [];
        selectedBottoms = [];
    };

    document.getElementById('top-pick-apply').onclick = function() {
        if (selectedTops.length === 0 && selectedBottoms.length === 0) return;
        btns.forEach(b => {
            const num = b.getAttribute('data-num');
            if (
                (num.length === 2 && selectedTops.includes(num[0])) ||
                (num.length === 2 && selectedBottoms.includes(num[1]))
            ) {
                b.classList.add('selected');
            } else {
                b.classList.remove('selected');
            }
        });
        hidePopup();
    };

    function resetPickPopup() {
        topPickBtns.forEach(btn => btn.classList.remove('selected'));
        bottomPickBtns.forEach(btn => btn.classList.remove('selected'));
        selectedTops = [];
        selectedBottoms = [];
        quickBottomBtns.forEach(btn => btn.classList.remove('selected'));
        selectedBottomPopup = [];
        quickBkBtns.forEach(btn => btn.classList.remove('selected'));
        selectedBkPopup = [];
    }
    document.getElementById('fastpick-quick').addEventListener('click', resetPickPopup);

    document.getElementById('legend-r-btn').addEventListener('click', function() {
        const selected = [];
        btns.forEach(btn => {
            if (btn.classList.contains('selected')) {
                selected.push(btn.getAttribute('data-num'));
            }
        });
        btns.forEach(btn => btn.classList.remove('selected'));
        selected.forEach(num => {
            if (num.length === 2) {
                const origBtn = document.querySelector('.cell-btn[data-num="' + num + '"]');
                if (origBtn) origBtn.classList.add('selected');
                const reversed = num[1] + num[0];
                if (reversed !== num) {
                    const reversedBtn = document.querySelector('.cell-btn[data-num="' + reversed + '"]');
                    if (reversedBtn) reversedBtn.classList.add('selected');
                }
            }
        });
    });

    // --- BET POPUP LOGIC ---
    function showBetPopup() {
        // Collect selected numbers
        const selBtns = Array.from(document.querySelectorAll('.cell-btn.selected'));
        if (selBtns.length === 0) {
            alert('ထိုးရန် နံပါတ်ရွေးပါ။');
            return;
        }
        let defaultAmt = parseInt(document.getElementById('amount-input').value, 10);
        if (isNaN(defaultAmt) || defaultAmt < 0) defaultAmt = 100;
        // Build bet list data
        window.betList = selBtns.map(btn => ({
            num: btn.getAttribute('data-num'),
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
        const tbody = document.querySelector('#bet-list-table tbody');
        tbody.innerHTML = '';
        let totalBet = 0, totalAmt = 0;
        betList.forEach((bet, idx) => {
            totalBet++;
            totalAmt += parseInt(bet.amt, 10);
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${bet.num}</td>
                <td>
                    <input type="number" min="0" value="${bet.amt}" class="edit-amt" data-idx="${idx}" />
                </td>
                <td>1</td>
                <td><button class="del-btn" data-idx="${idx}">ဖျက်</button></td>
            `;
            tbody.appendChild(tr);
        });
        document.getElementById('bet-popup-total-amount').innerText = totalAmt + ' ကျပ်';
        document.getElementById('bet-popup-total-bet').innerText = totalBet + ' ကွက်';
        // Bind edit
        tbody.querySelectorAll('.edit-amt').forEach(inp => {
            inp.addEventListener('input', function() {
                let val = parseInt(inp.value, 10);
                if (isNaN(val) || val < 0) val = 0;
                inp.value = val;
                betList[inp.getAttribute('data-idx')].amt = val;
                renderBetListTable();
            });
        });
        // Bind delete
        tbody.querySelectorAll('.del-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                betList.splice(btn.getAttribute('data-idx'), 1);
                renderBetListTable();
                // If no bets left, close the popup
                if (betList.length === 0) {
                    hideBetPopup();
                }
            });
        });
    }
    // BET SUBMIT DUMMY
    document.getElementById('bet-popup-submit').onclick = function() {
        alert('ထိုးခြင်း အတည်ပြုပါသည်!\n' + betList.length + ' ကွက်\n' + betList.reduce((a,b)=>a+parseInt(b.amt,10),0) + ' ကျပ်');
        hideBetPopup();
    };
    </script>
</body>
</html>



ပူး​ပေါင်းပြီး တဖိုင်ထဲ ပြန်ထုတ်ရန်
