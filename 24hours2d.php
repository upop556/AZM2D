<?php
// Show errors for debugging (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 24hours2d.php
session_start();
date_default_timezone_set('Asia/Yangon');

require_once __DIR__ . '/db.php';

// FIX: Get PDO connection from Db class
$pdo = Db::getInstance()->getConnection();

$now = time();

// နောက်ဆုံး update လုပ်ချိန် စစ်
$stmt = $pdo->query("SELECT set_value, value, UNIX_TIMESTAMP(updated_at) as updated_ts, updated_at, time10, time13, time15, time18, time19, time20 FROM set_value WHERE id = 1");
$row = $stmt->fetch(PDO::FETCH_ASSOC);

$should_update = true;
if ($row && isset($row['updated_ts'])) {
    $last_update = intval($row['updated_ts']);
    if (($now - $last_update) < 4) {
        $should_update = false;
    }
}

if ($should_update) {
    $new_set = mt_rand(31000, 32000) + mt_rand(0,99)/100;
    $new_value = mt_rand(8000, 9000) + mt_rand(0,99)/100;
    $stmt2 = $pdo->prepare("UPDATE set_value SET set_value = ?, value = ?, updated_at = NOW() WHERE id = 1");
    $stmt2->execute([$new_set, $new_value]);
    // ပြန် select (updated_at ကိုပါယူ)
    $stmt = $pdo->query("SELECT set_value, value, UNIX_TIMESTAMP(updated_at) as updated_ts, updated_at, time10, time13, time15, time18, time19, time20 FROM set_value WHERE id = 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Helper for 2D: always return last 2 digits, pad with 0 if needed
function get2d($val) {
    $val = preg_replace('/[^\d]/', '', $val);
    return strlen($val) >= 2 ? substr($val, -2) : str_pad($val, 2, "0", STR_PAD_LEFT);
}

// For today's slots, only show value if the slot time is reached, otherwise show '--'
function slot_reached($slot_time_str) {
    date_default_timezone_set('Asia/Yangon');
    $now = time();
    $today = date('Y-m-d');
    $slot_time = strtotime($today . ' ' . $slot_time_str);
    return $now >= $slot_time;
}

// Define slot time boundaries
$slot_times = [
    'time10' => '10:00',
    'time13' => '13:00',
    'time15' => '15:00',
    'time18' => '18:00',
    'time19' => '19:00',
    'time20' => '20:00',
];

// Main value calculation (keep as-is, or update if you want 2D-style)
$set = ($row && isset($row['set_value'])) ? number_format((float)$row['set_value'], 2, '.', '') : '0.00';
$value = ($row && isset($row['value'])) ? number_format((float)$row['value'], 2, '.', '') : '0.00';

$just_digits_set = preg_replace('/[^\d]/', '', $set);
$set_last_digit = (strlen($just_digits_set) > 0) ? substr($just_digits_set, -1) : '0';

$just_digits_val = preg_replace('/[^\d]/', '', $value);
$value_special_digit = (strlen($just_digits_val) >= 4) ? substr($just_digits_val, 3, 1) : '0';

$mainVALUE = $set_last_digit . $value_special_digit;
$updated_at = ($row && isset($row['updated_at'])) ? date("d/m/Y H:i:s", strtotime($row['updated_at'])) : date("d/m/Y H:i:s");

// Ensure slot fields are always 2D string, but only show value if slot time is reached, else '--'
function safeStr2d_with_slot($v, $slot_key, $slot_times) {
    if (!isset($slot_times[$slot_key]) || !slot_reached($slot_times[$slot_key])) {
        return '--';
    }
    return (isset($v) && $v !== null && $v !== '') ? get2d($v) : '--';
}

// --- UI HTML Output ---
?>
<!DOCTYPE html>
<html lang="my">
<head>
    <meta charset="UTF-8">
    <title>24 Hours 2D Live</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Main app styles for UI consistency -->
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
        }
        html, body { height: 100%; margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif; }
        body { min-height: 100vh; box-sizing: border-box; display: flex; flex-direction: column; align-items: center; justify-content: flex-start; padding-top: 115px; padding-bottom: 120px; }
        .container { width: 100%; max-width: 420px; margin: 0 auto; padding: 0 15px; box-sizing: border-box; }
        .card { background: var(--card-bg); border-radius: 18px; box-shadow: 0 4px 25px rgba(0,0,0,0.08); padding: 20px; width: 100%; text-align: center; margin: 0 auto 25px auto; box-sizing: border-box; }
        .section-title { font-size: 1.15em; font-weight: 600; color: var(--text-dark); margin-bottom: 15px; text-align: left; }
        .mainvalue-large { font-size: 4em; font-weight: bold; color: var(--accent); letter-spacing: 0.05em; margin-bottom: 0.2em; min-height: 1.2em; line-height: 1.2; }
        .updated-row { text-align: center; font-size: 0.95em; color: var(--text-light); margin-bottom: 1em; }
        .live-stats { display: flex; justify-content: space-around; margin-top: 1em; padding-top: 1em; border-top: 1px solid var(--border-color); }
        .stat-item { text-align: center; }
        .stat-label { display: block; color: var(--text-light); font-size: 0.9em; margin-bottom: 4px; }
        .stat-value { font-weight: 500; font-size: 1.1em; color: var(--text-dark); }
        .history-grid { display: grid; grid-template-columns: 1fr; gap: 12px; margin-bottom: 50px; }
        .history-card { display: flex; justify-content: space-between; align-items: center; background: var(--card-bg); border-radius: 12px; padding: 12px 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
        .history-left { text-align: left; }
        .history-right { text-align: right; color: #555; font-size: 0.95em; }
        .history-time { color: var(--primary); font-weight: bold; margin-bottom: 4px; }
        .history-main-value { font-size: 2em; font-weight: bold; }
        .history-detail { line-height: 1.4; }
        /* Header fixes for overlay */
        .header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
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
        .header-logo {
            height: 52px;
            width: 52px;
            border-radius: 12px;
            box-shadow: 0 2px 8px #0002;
            background: #fff5;
            object-fit: contain;
            margin-right: 16px;
        }
        .header-title {
            font-family: 'Poppins', 'Noto Sans Myanmar', sans-serif;
            font-size: 2.2em;
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
        }
    </style>
</head>
<body>
    <!-- Main UI Header (consistent with main app, no download icon) -->
    <div class="header" id="main-header">
        <div class="header-title">
            <img src="/images/azm-logo.png" alt="Logo" class="header-logo" />
            24 Hours 2D Game
        </div>
        <div class="header-right">
            <a href="/index.html" class="back-btn"><i class="bi bi-arrow-left"></i> Back</a>
        </div>
    </div>
    <div class="container">
        <div class="card">
            <div class="section-title">Live Data</div>
            <div class="mainvalue-large"><?= htmlspecialchars($mainVALUE) ?></div>
            <div class="updated-row">Updated: <?= htmlspecialchars($updated_at) ?></div>
            <div class="live-stats">
                <div class="stat-item">
                    <span class="stat-label">SET</span>
                    <span class="stat-value"><?= htmlspecialchars($set) ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Value</span>
                    <span class="stat-value"><?= htmlspecialchars($value) ?></span>
                </div>
            </div>
        </div>
        <div class="section-title">Today’s 2D Results</div>
        <div class="history-grid">
            <?php
            $slot_labels = [
                'time10' => '10:00 AM',
                'time13' => '1:00 PM',
                'time15' => '3:00 PM',
                'time18' => '6:00 PM',
                'time19' => '7:00 PM',
                'time20' => '8:00 PM',
            ];
            foreach ($slot_labels as $k => $label) {
                $val = safeStr2d_with_slot($row[$k] ?? null, $k, $slot_times);
                $color = $val !== '--' ? 'var(--accent)' : '#999';
                echo '<div class="history-card">';
                echo    '<div class="history-left">';
                echo        '<div class="history-time">'.htmlspecialchars($label).'</div>';
                echo        '<div class="history-main-value" style="color:'.$color.';">'.htmlspecialchars($val).'</div>';
                echo    '</div>';
                echo    '<div class="history-right">';
                echo        '<div class="history-detail"><strong>Slot:</strong> '.htmlspecialchars($k).'</div>';
                echo    '</div>';
                echo '</div>';
            }
            ?>
        </div>
    </div>
</body>
</html>