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

// --- Get latest main value and hourly slots ---
function getMainValueRow() {
    $pdo = Db::getInstance()->getConnection();
    $stmt = $pdo->prepare('SELECT mainVALUE, updated_at FROM mainvalue ORDER BY updated_at DESC LIMIT 1');
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['mainVALUE' => '--', 'updated_at' => date("Y-m-d H:i:s")];
}
$row = getMainValueRow();

// --- Hourly slots result (should fetch from DB or file) ---
function getHourlyResults() {
    // You should fetch from DB: SELECT * FROM hourly_results WHERE date = CURRENT_DATE()
    // Here, we just return empty for demo, slot: timeHH, value: 'N/A'
    $results = [];
    for ($h = 0; $h < 24; $h++) {
        $slot_key = 'time' . str_pad($h, 2, '0', STR_PAD_LEFT);
        $results[$slot_key] = [
            'value' => 'N/A',
            'updated_at' => '--'
        ];
    }
    return $results;
}
$hourly_results = getHourlyResults();

// --- AJAX endpoint for latest value ---
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    $mainVALUE = $row['mainVALUE'] ?? '--';
    // Correct Myanmar 12-hour format with AM/PM
    $dt = new DateTime($row['updated_at'] ?? date("Y-m-d H:i:s"), new DateTimeZone('Asia/Yangon'));
    $updated_at = $dt->format("d/m/Y h:i:s A");
    header('Content-Type: application/json');
    echo json_encode([
        "mainVALUE" => $mainVALUE,
        "updated_at" => $updated_at
    ]);
    exit;
}

// --- Helper function for MM 12hr format ---
// Always show correct AM/PM using DateTimeZone
function mm_hour_label($hour) {
    $dt = new DateTime(sprintf('%02d:00:00', $hour), new DateTimeZone('Asia/Yangon'));
    return $dt->format('h:00 A'); // e.g., 01:00 PM
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
        .balance-panel { margin: 16px auto 20px auto; background: var(--primary); color: #fff; border-radius: 13px; box-shadow: 0 4px 15px rgba(25, 118, 210, 0.25); padding: 15px 20px; font-size: 1.2em; }
        .balance-panel .bi-wallet2 { font-size: 1.3em; margin-right: 10px; }
        .card { background: var(--card-bg); border-radius: 18px; box-shadow: 0 4px 25px rgba(0,0,0,0.08); padding: 20px; width: 100%; text-align: center; margin: 0 auto 25px auto; box-sizing: border-box; }
        .section-title { font-size: 1.15em; font-weight: 600; color: var(--text-dark); margin-bottom: 15px; text-align: left; }
        .mainvalue-large { font-size: 6.6em; font-weight: bold; color: var(--accent); letter-spacing: 0.05em; margin-bottom: 0.2em; min-height: 1.2em; line-height: 1.2; transition: opacity 0.4s; }
        .updated-row { text-align: center; font-size: 0.95em; color: var(--text-light); margin-bottom: 1em; }
        .hourly-grid { display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 12px; margin-bottom: 50px; }
        .hourly-card { display: flex; flex-direction: column; justify-content: center; align-items: center; background: var(--card-bg); border-radius: 12px; padding: 10px 0; box-shadow: 0 2px 8px rgba(0,0,0,0.12); }
        .hour-label { color: var(--primary); font-weight: bold; margin-bottom: 4px; }
        .hour-value { font-size: 1.6em; font-weight: bold; }
        .hour-detail { line-height: 1.2; font-size: 0.95em; color: #555; }
        @media (max-width: 600px) {
            .hourly-grid { grid-template-columns: 1fr 1fr; }
            .mainvalue-large { font-size: 3.3em; }
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

    // Myanmar hour label (12-hour format, correct AM/PM)
    function mmHourLabel(hour) {
        let h = hour % 12;
        if (h === 0) h = 12;
        let suffix = (hour < 12) ? 'AM' : 'PM';
        return h.toString().padStart(2, '0') + ':00 ' + suffix;
    }

    // Get current Myanmar time regardless of device timezone
    function getMyanmarNow() {
        // Get the time in UTC milliseconds
        const now = new Date();
        // UTC time + 6.5 hours (Myanmar)
        return new Date(now.getUTCFullYear(), now.getUTCMonth(), now.getUTCDate(), now.getUTCHours() + 6, now.getUTCMinutes() + 30, now.getUTCSeconds(), now.getUTCMilliseconds());
    }

    // Render 24 hour buttons (no hidden hours, always 24 hours displayed)
    function renderBetHourBtns() {
        const betHourBtnsDiv = document.getElementById('betHourBtns');
        betHourBtnsDiv.innerHTML = '';
        // Use Myanmar real time
        const mmtNow = getMyanmarNow();
        const currHour = mmtNow.getHours();
        const currMin = mmtNow.getMinutes();

        for (let h = 0; h < 24; h++) {
            let hourLabel = mmHourLabel(h);
            let slotKey = 'time' + h.toString().padStart(2, '0');
            // Betting closes 3 min before the hour slot
            let cutoffHour = h;
            let cutoffMin = 57; // 3 min before next hour
            let disabled = false;
            if (currHour > cutoffHour || (currHour === cutoffHour && currMin >= cutoffMin)) {
                disabled = true;
            }
            let btn = document.createElement('button');
            btn.className = 'bet-hour-btn';
            btn.textContent = hourLabel + ' အတွက်ထိုးမည်';
            btn.disabled = disabled;
            btn.onclick = function() {
                if (!disabled) {
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
                Updated: 
                <?php
                $dt = new DateTime($row['updated_at'] ?? date("Y-m-d H:i:s"), new DateTimeZone('Asia/Yangon'));
                echo htmlspecialchars($dt->format("d/m/Y h:i:s A"));
                ?>
            </div>
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
            <div class="bet-popup-title">ထိုးမည့်အချိန် (၃ မိနစ်အလို ပိတ်မည်)</div>
            <div id="betHourBtns"></div>
        </div>
    </div>
</body>
</html>