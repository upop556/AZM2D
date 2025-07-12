<?php
// Show errors for debugging (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
date_default_timezone_set('Asia/Yangon');

require_once __DIR__ . '/db.php';

// --- Check Login ---
if (empty($_SESSION['user_id'])) {
    header('Location: /index.html');
    exit;
}
$current_user = $_SESSION['user_id'] ?? null;

// --- Get User Balance ---
function getUserBalance($user_id) {
    $pdo = Db::getInstance()->getConnection();
    $stmt = $pdo->prepare('SELECT balance FROM users WHERE id = :uid');
    $stmt->execute([':uid' => $user_id]);
    $row = $stmt->fetch();
    return $row ? (int)$row['balance'] : 0;
}
$user_balance = getUserBalance($current_user);

// --- Shared random value for Latest Update (4 seconds cycle, file cache) ---
function getSharedMainValue() {
    $cache_file = __DIR__ . '/mainvalue_cache.json';
    $now = time();
    $cache_lifetime = 4; // seconds

    if (file_exists($cache_file)) {
        $data = json_decode(file_get_contents($cache_file), true);
        if (isset($data['mainVALUE'], $data['timestamp'], $data['updated_at'])) {
            if ($now - $data['timestamp'] < $cache_lifetime) {
                return $data;
            }
        }
    }

    $mainVALUE = str_pad(mt_rand(0, 99), 2, '0', STR_PAD_LEFT);
    $updated_at = date("Y-m-d H:i:s");
    $data = [
        'mainVALUE' => $mainVALUE,
        'updated_at' => $updated_at,
        'timestamp' => $now
    ];
    file_put_contents($cache_file, json_encode($data));
    return $data;
}

$row = getSharedMainValue();

// --- Save Latest Update to Current Hourly Slot (auto-update table) ---
// FIX: Only update for current hour, never overwrite past hour data!
function saveLatestResultToHourly($number, $datetime = null) {
    $pdo = Db::getInstance()->getConnection();
    if ($datetime === null) {
        $now = new DateTime("now", new DateTimeZone("Asia/Yangon"));
    } else {
        $now = new DateTime($datetime, new DateTimeZone("Asia/Yangon"));
    }
    $hour = (int)$now->format('H');
    $slot_key = 'time' . str_pad($hour, 2, '0', STR_PAD_LEFT);
    $updated_at = $now->format('Y-m-d H:i:s');

    // Only update current hour; do not change past hour data!
    $today = date('Y-m-d');
    $current_hour = (int)date('H');
    if ($hour === $current_hour) {
        // Check if already exists (do not overwrite if already set for this hour)
        $stmt_check = $pdo->prepare("SELECT value FROM hourly_results WHERE slot_key = :slot_key AND DATE(updated_at) = :today");
        $stmt_check->execute([':slot_key' => $slot_key, ':today' => $today]);
        $row = $stmt_check->fetch();
        if (!$row) {
            // Only insert if not already exists
            $stmt = $pdo->prepare('REPLACE INTO hourly_results (slot_key, value, updated_at) VALUES (:slot_key, :value, :updated_at)');
            $stmt->execute([
                ':slot_key' => $slot_key,
                ':value' => $number,
                ':updated_at' => $updated_at
            ]);
        }
    }
}

// --- AUTO: Update hourly table with latest value every 4 seconds ---
if (!empty($row['mainVALUE'])) {
    saveLatestResultToHourly($row['mainVALUE'], $row['updated_at']);
}

// --- Hourly slots result (fetch from DB or fill fake data for remaining) ---
// FAKE data for past hours should NOT change or update after the hour is done!
function getHourlyResultsWithFake() {
    $pdo = Db::getInstance()->getConnection();
    $results = [];
    $today = date('Y-m-d');
    $current_hour = (int)date('H');

    // Get real results from DB
    $stmt = $pdo->prepare("SELECT slot_key, value, updated_at FROM hourly_results WHERE DATE(updated_at) = :today");
    $stmt->execute([':today' => $today]);
    $db_results = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $db_results[$row['slot_key']] = [
            'value' => $row['value'],
            'updated_at' => $row['updated_at']
        ];
    }

    // Deterministic FAKE data for past hours (not random every time!)
    $fake_numbers = [];
    $seed = intval(date('Ymd'));
    mt_srand($seed);
    for ($h = 0; $h < 24; $h++) {
        $fake_numbers[$h] = str_pad(mt_rand(0,99), 2, '0', STR_PAD_LEFT);
    }
    mt_srand(); // reset random seed

    // Fill all 24 slots
    for ($h = 0; $h < 24; $h++) {
        $slot_key = 'time' . str_pad($h, 2, '0', STR_PAD_LEFT);
        if (isset($db_results[$slot_key])) {
            $results[$slot_key] = $db_results[$slot_key];
        } else {
            if ($h < $current_hour) {
                // Use deterministic fake number, will NOT change every reload
                $results[$slot_key] = [
                    'value' => $fake_numbers[$h],
                    'updated_at' => $today . ' ' . str_pad($h, 2, '0', STR_PAD_LEFT) . ':00:00'
                ];
            } else {
                // For current hour and future hour, show N/A if no real data
                $results[$slot_key] = [
                    'value' => 'N/A',
                    'updated_at' => '--'
                ];
            }
        }
    }
    return $results;
}
$hourly_results = getHourlyResultsWithFake();

// --- Helper function for MM 12hr format ---
function mm_hour_label($hour) {
    // $hour is 0-23
    $h = $hour % 12;
    if ($h == 0) $h = 12;
    $suffix = ($hour >= 12) ? 'PM' : 'AM';
    return sprintf('%02d:00 %s', $h, $suffix);
}

// --- Helper function for betting cutoff (server side accurate) ---
function isBetEnabled($slot_hour) {
    $now = new DateTime("now", new DateTimeZone("Asia/Yangon"));
    $current_hour = (int)$now->format('H');
    // Betting closes as soon as the hour is reached
    if ($current_hour >= $slot_hour) {
        return false; // Disabled
    }
    return true; // Enabled
}

// --- AJAX endpoint for latest value ---
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    $data = getSharedMainValue();
    header('Content-Type: application/json');
    echo json_encode([
        "mainVALUE" => $data['mainVALUE'],
        "updated_at" => $data['updated_at']
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="my">
<head>
    <meta charset="UTF-8">
    <title>24 Hours 2D Live</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
        .balance-panel { margin: 16px auto 20px auto; background: var(--primary); color: #fff; border-radius: 13px; box-shadow: 0 4px 15px rgba(25, 118, 210, 0.25); padding: 15px 20px; font-size: 1.2em;}
        .balance-panel .bi-wallet2 { font-size: 1.3em; margin-right: 10px; }
        .card { background: var(--card-bg); border-radius: 18px; box-shadow: 0 4px 25px rgba(0,0,0,0.08); padding: 20px; width: 100%; text-align: center; margin: 0 auto 25px auto; box-sizing: border-box; }
        .section-title { font-size: 1.15em; font-weight: 600; color: var(--text-dark); margin-bottom: 15px; text-align: left; }
        .mainvalue-large { font-size: 6.6em; font-weight: bold; color: var(--accent); letter-spacing: 0.05em; margin-bottom: 0.2em; min-height: 1.2em; line-height: 1.2; transition: opacity 0.4s; }
        .updated-row { text-align: center; font-size: 0.95em; color: var(--text-light); margin-bottom: 1em; }
        .hourly-grid { display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 12px; margin-bottom: 50px; }
        .hourly-card { display: flex; flex-direction: column; justify-content: center; align-items: center; background: var(--card-bg); border-radius: 12px; padding: 10px 0; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
        .hour-label { color: var(--primary); font-weight: bold; margin-bottom: 4px; }
        .hour-value { font-size: 1.6em; font-weight: bold; }
        .hour-detail { line-height: 1.2; font-size: 0.95em; color: #555; }
        @media (max-width: 600px) {
            .hourly-grid { grid-template-columns: 1fr 1fr; }
            .mainvalue-large { font-size: 3.3em; }
        }
        .menu-row { display: flex; flex-direction: row; align-items: stretch; justify-content: space-between; gap: 10px; margin-bottom: 25px; }
        .menu-btn { flex: 1 1 0; background: #f8f5ff; border-radius: 12px; text-align: center; padding: 13px 5px 10px 5px; cursor: pointer; box-shadow: 0 2px 8px rgba(0,0,0,0.06); border: 1px solid #e5e4ff; }
        .menu-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        .menu-btn i { font-size: 1.8em !important; margin-bottom: 5px; display:inline-block; vertical-align:middle;}
        .menu-label { font-size: 0.95em; color: #4a4773; font-weight: 500; }
        /* menu row responsive */
        @media (max-width: 600px) {
            .menu-row { gap: 5px; margin-bottom: 18px; }
            .menu-btn { font-size: 0.93em; padding: 9px 2px 7px 2px; }
            .menu-btn i { font-size: 1.8em !important; }
        }
        .bet-btn-fixed {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 0;
            width: 100%;
            max-width: 420px;
            margin: auto;
            background: var(--warning, #ffae42);
            color: #333;
            font-size: 1.18em;
            font-weight: bold;
            border-radius: 0 0 18px 18px;
            box-shadow: 0 -2px 18px #bbb;
            padding: 18px 0 14px 0;
            text-align: center;
            z-index: 1200;
            cursor: pointer;
            transition: background 0.18s;
        }
        .bet-btn-fixed:hover {
            background: #ffd98b;
        }
        .bet-hour-btn {
            margin: 7px 0;
            width: 100%;
            padding: 10px 0;
            font-size: 1em;
            background: linear-gradient(90deg, #ffae42, #ffd98b);
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.18s;
        }
        .bet-hour-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            color: #888;
        }
        .bet-popup-bg { 
            display: none; 
            position: fixed; left:0; top:0; width:100vw; height:100vh; 
            background: rgba(32,20,20,0.29); 
            z-index: 2000;
        }
        .bet-popup { 
            position: fixed; 
            left:0; right:0; 
            bottom: 0; 
            max-width: 420px; 
            margin: auto; 
            background: #fff; 
            border-radius: 18px 18px 0 0; 
            box-shadow: 0 -2px 18px #bbb; 
            padding: 32px 18px 38px 18px; 
            animation: popupUp 0.22s;
            height: 50vh;
            min-height: 320px;
            max-height: 60vh;
            overflow-y: auto;
        }
        @keyframes popupUp { from { transform: translateY(100%);} to { transform: translateY(0);} }
        .bet-popup-title { font-size: 1.15em; font-weight: 600; color: var(--text-dark); margin-bottom: 15px; text-align: center; }
        .bet-popup-close { position: absolute; top: 10px; right: 18px; font-size: 1.3em; color: #c33; background: none; border: none; cursor: pointer;}
        @media (max-width: 600px) {
            .bet-popup {
                height: 55vh;
                min-height: 220px;
                max-height: 70vh;
            }
        }
    </style>
    <script>
    // Auto refresh latest update from backend every 4 seconds
    function refreshLatestUpdate() {
        fetch(window.location.pathname + '?ajax=1')
        .then(res => res.json())
        .then(data => {
            window.latestMainVALUE = data.mainVALUE;
            document.getElementById('updated-row').innerText = "Updated: " + data.updated_at;
            document.getElementById('mainvalue-large').innerText = window.latestMainVALUE;
        });
    }
    window.addEventListener('DOMContentLoaded', function(){
        window.latestMainVALUE = document.getElementById('mainvalue-large').innerText.trim();
        setInterval(refreshLatestUpdate, 4000);
    });

    // Bet popup logic
    function showBetPopup() {
        document.getElementById('betPopupBg').style.display = 'block';
        renderBetHourBtns();
    }
    function hideBetPopup() {
        document.getElementById('betPopupBg').style.display = 'none';
    }

    // Helper for MM 12hr format (must match PHP logic)
    function mmHourLabel(hour) {
        let h = hour % 12;
        if (h === 0) h = 12;
        let suffix = hour >= 12 ? 'PM' : 'AM';
        return h.toString().padStart(2, '0') + ':00 ' + suffix;
    }

    // Render 24 hour buttons (disabled by server, not client time)
    function renderBetHourBtns() {
        const betHourBtnsDiv = document.getElementById('betHourBtns');
        betHourBtnsDiv.innerHTML = '';
        <?php
        $bet_status = [];
        for ($h = 0; $h < 24; $h++) {
            $bet_status[$h] = isBetEnabled($h);
        }
        echo "const betEnabledArr = " . json_encode($bet_status) . ";";
        ?>
        for (let h = 0; h < 24; h++) {
            let hourLabel = mmHourLabel(h);
            let slotKey = 'time' + h.toString().padStart(2, '0');
            let enabled = betEnabledArr[h];
            let btn = document.createElement('button');
            btn.className = 'bet-hour-btn';
            btn.textContent = hourLabel + ' အတွက်ထိုးမည်';
            btn.disabled = !enabled;
            btn.onclick = function() {
                if (enabled) {
                    window.location.href = 'bet.php?hour=' + slotKey;
                }
            };
            betHourBtnsDiv.appendChild(btn);
        }
    }
    </script>
</head>
<body>
    <div class="header" id="main-header">
        <div class="header-title">
            <img src="/images/azm-logo.png" alt="Logo" class="header-logo" />
            AZM2D3D Game
        </div>
        <div class="header-right">
            <a href="/index.html" class="back-btn"><i class="bi bi-arrow-left"></i> </a>
        </div>
    </div>
    <div class="container">
        <div class="balance-panel">
            <i class="bi bi-wallet2"></i>
            လက်ကျန်ငွေ: <span id="Balance"><?= number_format($user_balance) ?></span> ကျပ်
        </div>
        
        <div class="card" id="latest-update-card">
            <div class="section-title">Latest Update</div>
            <div class="mainvalue-large" id="mainvalue-large">
                <?= htmlspecialchars($row['mainVALUE'] ?? '--') ?>
            </div>
            <div class="updated-row" id="updated-row">
                Updated: <?= htmlspecialchars($row['updated_at'] ?? '--') ?>
            </div>
        </div>

        <div class="menu-row">
            <a href="bet_record.php" class="menu-btn">
                <i class="bi bi-file-earmark-text"></i>
                <span class="menu-label">မှတ်တမ်း</span>
            </a>
            <a href="winner.php" class="menu-btn">
                <i class="bi bi-award"></i>
                <span class="menu-label">ကံထူးရှင်</span>
            </a>
        </div>

        <div class="section-title">၂၄ နာရီအတွက် 2D Results</div>
        <div class="hourly-grid">
            <?php
            foreach ($hourly_results as $slot_key => $data) {
                $h = intval(substr($slot_key, 4, 2));
                $label = mm_hour_label($h);
                $color = $data['value'] !== 'N/A' ? 'var(--accent)' : '#999';
                echo '<div class="hourly-card">';
                echo    '<div class="hour-label">' . htmlspecialchars($label) . '</div>';
                echo    '<div class="hour-value" style="color:' . $color . ';">' . htmlspecialchars($data['value']) . '</div>';
                echo    '<div class="hour-detail">Slot: ' . htmlspecialchars($slot_key) . '</div>';
                echo '</div>';
            }
            ?>
        </div>
    </div>
    <!-- Fixed Bet Button -->
    <div class="bet-btn-fixed" onclick="showBetPopup()">၂၄ နာရီထိုးမည်</div>
    <div id="betPopupBg" class="bet-popup-bg" onclick="hideBetPopup()">
        <div class="bet-popup" onclick="event.stopPropagation()">
            <button class="bet-popup-close" onclick="hideBetPopup()">&times;</button>
            <div class="bet-popup-title">ထိုးမည့်အချိန် (အချိန်တိတိရောက်သောအခါ ပိတ်မည်)</div>
            <div id="betHourBtns"></div>
        </div>
    </div>
</body>
</html>