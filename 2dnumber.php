<?php
//2dnumber.php - UPDATED
session_start();
date_default_timezone_set('Asia/Yangon');
header('Content-Type: text/html; charset=utf-8');

// --- DB Connection ---
require_once __DIR__ . '/db.php';

// --- Check Login ---
if (empty($_SESSION['user_id'])) {
    echo "<h2 style='color:red;text-align:center;margin-top:50px;'>အကောင့်ဝင်ပါ။ (Please login)</h2>";
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

// --- Check for market holidays ---
$pdo = Db::getInstance()->getConnection();
$is_closed_today = false;
$closed_reason = '';
$is_closed_next_day = false;
$next_day_closed_reason = '';

try {
    $today = date('Y-m-d');
    $tomorrow = date('Y-m-d', strtotime('+1 day'));

    // Check for today
    $stmt_today = $pdo->prepare("SELECT reason FROM closed_dates WHERE closed_date = ?");
    $stmt_today->execute([$today]);
    $closed_row_today = $stmt_today->fetch(PDO::FETCH_ASSOC);
    if ($closed_row_today) {
        $is_closed_today = true;
        $closed_reason = $closed_row_today['reason'];
    }

    // Check for next day
    $stmt_tomorrow = $pdo->prepare("SELECT reason FROM closed_dates WHERE closed_date = ?");
    $stmt_tomorrow->execute([$tomorrow]);
    $closed_row_tomorrow = $stmt_tomorrow->fetch(PDO::FETCH_ASSOC);
    if ($closed_row_tomorrow) {
        $is_closed_next_day = true;
        $next_day_closed_reason = $closed_row_tomorrow['reason'];
    }

} catch (PDOException $e) {
    // Keep closed status as false in case of DB error
}


// If ?api=1, serve JSON API
if (isset($_GET['api'])) {
    header('Content-Type: application/json');

    // --- Get historical/manual results from admin-edited file ---
    $history_file = 'results.json';
    $historical_data = [];
    if (file_exists($history_file)) {
        $file_content = file_get_contents($history_file);
        // Ensure that the file is not empty and is valid JSON
        if (!empty($file_content)) {
            $decoded_json = json_decode($file_content, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $historical_data = $decoded_json;
            }
        }
    }

    // Try several proxies in order to fetch LIVE data
    $target_url = "https://www.set.or.th/th/home";
    $proxies = [
        "https://api.allorigins.win/raw?url=",
        "https://thingproxy.freeboard.io/fetch/",
        "https://corsproxy.io/?"
    ];
    $html = false;
    foreach ($proxies as $proxy) {
        $proxy_url = $proxy . urlencode($target_url);
        $context = stream_context_create(['http' => ['timeout' => 7]]); // 7 seconds timeout
        $html = @file_get_contents($proxy_url, false, $context);
        if ($html && strlen($html) > 1000) break;
    }

    $live_data_error = null;
    $mainValue = "-";
    $set_index = "-";
    $value = "-";

    if (!$html) {
        $live_data_error = 'Failed to fetch live data from SET website.';
    } else {
        // Parse HTML for SET index (table) and Value (regex)
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        libxml_clear_errors();
        $xpath = new DOMXPath($dom);

        // 1. Extract SET Index
        $rows = $xpath->query('//table//tr');
        foreach ($rows as $row) {
            $tds = $row->getElementsByTagName('td');
            if ($tds->length > 1 && trim($tds->item(0)->textContent) === 'SET') {
                $set_index = trim($tds->item(1)->textContent);
                break;
            }
        }

        // 2. Extract Value (from "ล้านบาท", e.g. "47,225.04 ล้านบาท")
        if (preg_match('/([\d,]+\.\d{2})\s*ล้านบาท/', $html, $m)) {
            $value = $m[1];
        }

        // 3. MainValue: SET နောက်ဆုံး digit + Value ဒဿမအရှေ့က digit
        if ($set_index !== "-" && $value !== "-") {
            $set_digits = preg_replace('/\D/', '', $set_index);
            $set_last_digit = !empty($set_digits) ? substr($set_digits, -1) : '0';

            $value_clean = str_replace(',', '', $value);
            $value_parts = explode('.', $value_clean);
            $value_before_decimal = $value_parts[0];
            $value_special_digit = !empty($value_before_decimal) ? substr($value_before_decimal, -1) : '0';

            $mainValue = $set_last_digit . $value_special_digit;
        }
    }

    // 4. Output JSON
    $data = [
        "MainValue" => $mainValue,
        "Set"       => $set_index,
        "Value"     => $value,
        "Balance"   => $user_balance,
        "Updated"   => date('Y-m-d H:i:s'),
        "error"     => $live_data_error,
        // This key contains the manually entered data from the admin panel
        "historical" => $historical_data
    ];
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
?>
<!DOCTYPE html>
<html lang="my">
<head>
    <meta charset="UTF-8">
    <title>2D Live App</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- UI Consistency: Main header and background -->
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
        :root { --primary: #1976d2; --primary-light: #e3f2fd; --warning: #ffae42; --accent: #0a67a3; --panel-bg: #f6f8fa; --card-bg: #fff; --text-light: #888; --text-dark: #333; --border-color: #eef; }
        html, body { height: 100%; margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif; }
        body { min-height: 100vh; box-sizing: border-box; display: flex; flex-direction: column; align-items: center; justify-content: flex-start; padding-top: 115px; padding-bottom: 120px; }
        .container { width: 100%; max-width: 420px; margin: 0 auto; padding: 0 15px; box-sizing: border-box; }
        .balance-panel { margin: 16px auto 20px auto; background: var(--primary); color: #fff; border-radius: 13px; box-shadow: 0 4px 15px rgba(25, 118, 210, 0.25); padding: 15px 20px; font-size: 1.2em; display: flex; align-items: center; justify-content: center; font-weight: bold; }
        .balance-panel .bi-wallet2 { font-size: 1.3em; margin-right: 10px; }
        .card { background: var(--card-bg); border-radius: 18px; box-shadow: 0 4px 25px rgba(0,0,0,0.08); padding: 20px; width: 100%; text-align: center; margin: 0 auto 25px auto; box-sizing: border-box; }
        .section-title { font-size: 1.15em; font-weight: 600; color: var(--text-dark); margin-bottom: 15px; text-align: left; }
        .mainvalue-large { font-size: 4em; font-weight: bold; color: var(--text-dark); letter-spacing: 0.05em; margin-bottom: 0.2em; min-height: 1.2em; line-height: 1.2; }
        .updated-row { text-align: center; font-size: 0.95em; color: var(--text-light); margin-bottom: 1em; }
        .live-stats { display: flex; justify-content: space-around; margin-top: 1em; padding-top: 1em; border-top: 1px solid var(--border-color); }
        .stat-item { text-align: center; }
        .stat-label { display: block; color: var(--text-light); font-size: 0.9em; margin-bottom: 4px; }
        .stat-value { font-weight: 500; font-size: 1.1em; color: var(--text-dark); }
        .menu-row { display: flex; flex-direction: row; align-items: stretch; justify-content: space-between; gap: 10px; margin-bottom: 25px; }
        .menu-btn { flex: 1 1 0; background: #f8f5ff; border-radius: 12px; text-align: center; padding: 13px 5px 10px 5px; cursor: pointer; box-shadow: 0 2px 8px rgba(0,0,0,0.06); border: 1px solid #ede7f6; font-size: 1.03em; display: flex; flex-direction: column; align-items: center; text-decoration: none; transition: transform 0.1s, box-shadow 0.1s; }
        .menu-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        .menu-btn i { font-size: 1.8em; color: #7b4de6; margin-bottom: 5px; }
        .menu-label { font-size: 0.95em; color: #4a4773; font-weight: 500; }
        .history-grid { display: grid; grid-template-columns: 1fr; gap: 12px; margin-bottom: 50px; }
        .history-card { display: flex; justify-content: space-between; align-items: center; background: var(--card-bg); border-radius: 12px; padding: 12px 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
        .history-left { text-align: left; }
        .history-right { text-align: right; color: #555; font-size: 0.95em; }
        .history-time { color: var(--primary); font-weight: bold; margin-bottom: 4px; }
        .history-main-value { font-size: 2em; font-weight: bold; }
        .history-detail { line-height: 1.4; }
        #error { color: #d32f2f; font-weight: 500; text-align:center; padding: 1em; min-height: 1.2em; }
        .loading-spinner { display: inline-block; width: 24px; height: 24px; border: 3px solid rgba(0,0,0,0.1); border-radius: 50%; border-top-color: var(--primary); animation: spin 1s ease-in-out infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .bet-btn-fixed { position: fixed; left: 0; right: 0; bottom: 0; width: 100%; max-width: 420px; margin: auto; background: var(--warning); color: #333; font-size: 1.18em; font-weight: bold; border-radius: 13px 13px 0 0; box-shadow: 0 -2px 12px #ddd; padding: 14px 0 10px 0; text-align: center; cursor: pointer; z-index: 1001; transition: background-color 0.2s; }
        .bet-popup-bg { display: none; position: fixed; left:0; top:0; width:100vw; height:100vh; background: rgba(32,20,20,0.29); z-index: 2000;}
        .bet-popup { position: fixed; left:0; right:0; bottom: 0; max-width: 420px; margin: auto; background: #fff; border-radius: 18px 18px 0 0; box-shadow: 0 -2px 18px #bbb; padding: 32px 18px 38px 18px; z-index: 2001; animation: popupUp 0.19s; box-sizing: border-box;}
        @keyframes popupUp { from { transform: translateY(100%);} to { transform: translateY(0);} }
        .bet-popup-title { font-size: 1.15em; font-weight: 600; color: var(--text-dark); margin-bottom: 15px; text-align: center; }
        .bet-time-btn { display: block; width: 100%; margin: 14px 0; padding: 13px 0; font-size: 1.08em; color: #fff; background: linear-gradient(90deg,#ff9a44,#ff5e62); border: none; border-radius: 8px; cursor: pointer; font-weight: 500; }
        .bet-time-btn:disabled { background: #ccc; cursor: not-allowed; color: #888; }
        .bet-popup-close { position: absolute; top: 10px; right: 18px; font-size: 1.3em; color: #c33; background: none; border: none; cursor: pointer;}
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
            .balance-panel { margin-top: 12px; }
            .header-right .back-btn {
                font-size: 0.95em;
                padding: 6px 10px;
                margin-left: 10px;
            }
            .bet-popup { padding: 22px 7vw 28px 7vw; }
            .bet-time-btn { margin: 10px 0; }
        }
    </style>
</head>
<body>
    <!-- Main UI Header (consistent with main app, no download icon) -->
    <div class="header" id="main-header">
        <div class="header-title">
            <img src="/images/azm-logo.png" alt="Logo" class="header-logo" />
            AZM2D Game
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
        <div class="card">
            <div class="section-title">အချိန်နှင့်တပြေးညီ Data (Live)</div>
            <div id="result">
                <div class="mainvalue-large" id="MainValue"><div class="loading-spinner"></div></div>
                <div class="updated-row">Updated: <span id="Updated">-</span></div>
                 <div class="live-stats">
                    <div class="stat-item">
                        <span class="stat-label">SET</span>
                        <span class="stat-value" id="Set">-</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Value</span>
                        <span class="stat-value" id="Value">-</span>
                    </div>
                </div>
            </div>
            <div id="error"></div>
        </div>
        <div class="menu-row">
            <a href="bet_record.php" class="menu-btn"><i class="bi bi-file-earmark-text"></i><span class="menu-label">မှတ်တမ်း</span></a>
            <a href="winner.php" class="menu-btn"><i class="bi bi-award"></i><span class="menu-label">ကံထူးရှင်</span></a>
            <a href="close_dates.php" class="menu-btn"><i class="bi bi-calendar2-week"></i><span class="menu-label">ထီပိတ်ရက်</span></a>
        </div>
        <div class="section-title">ယနေ့ထွက်ဂဏန်းများ</div>
        <div class="history-grid" id="historyGrid"></div>
    </div>
    <div class="bet-btn-fixed" id="betBtnFixed" onclick="showBetPopup()">ထိုးမည်</div>
    <div id="betPopupBg" class="bet-popup-bg" onclick="hideBetPopup()">
        <div class="bet-popup" onclick="event.stopPropagation()">
            <button class="bet-popup-close" onclick="hideBetPopup()">&times;</button>
            <div class="bet-popup-title" id="betPopupTitle">ထိုးမည့်အချိန်ရွေးပါ</div>
            <button class="bet-time-btn" id="btn-1100">11:00 AM</button>
            <button class="bet-time-btn" id="btn-1201">12:01 PM</button>
            <button class="bet-time-btn" id="btn-1500">03:00 PM</button>
            <button class="bet-time-btn" id="btn-1630">04:30 PM</button>
        </div>
    </div>
    <script>
    const isClosedToday = <?= $is_closed_today ? 'true' : 'false' ?>;
    const closedReason = <?= json_encode($closed_reason) ?>;
    const isClosedNextDay = <?= $is_closed_next_day ? 'true' : 'false' ?>;
    const nextDayClosedReason = <?= json_encode($next_day_closed_reason) ?>;

    function renderTimeTables(historicalData) {
        const times = { "11:00": "11:00 AM", "12:01": "12:01 PM", "15:00": "03:00 PM", "16:30": "04:30 PM" };
        let html = "";
        const today = new Date().toISOString().slice(0, 10); 

        for (const key in times) {
            const displayTime = times[key];
            const result = historicalData && historicalData[today] && historicalData[today][key] ? historicalData[today][key] : null;
            
            const mainValue = result ? result.MainValue : "စောင့်ဆိုင်းပါ";
            const set = result ? result.Set : "-";
            const value = result ? result.Value : "-";
            const color = result ? 'var(--accent)' : '#999';

            html += `
            <div class="history-card">
                <div class="history-left">
                    <div class="history-time">${displayTime}</div>
                    <div class="history-main-value" style="color:${color};">${mainValue}</div>
                </div>
                <div class="history-right">
                    <div class="history-detail"><strong>SET:</strong> ${set}</div>
                    <div class="history-detail"><strong>Value:</strong> ${value}</div>
                </div>
            </div>`;
        }
        document.getElementById("historyGrid").innerHTML = html;
    }

    function getDataAndRender() {
        // If the main value is currently showing a spinner, don't do it again.
        // This prevents the spinner from showing on every auto-refresh.
        if (document.getElementById('MainValue').textContent === '') {
             document.getElementById('MainValue').innerHTML = '<div class="loading-spinner"></div>';
        }
        
        fetch('?api=1')
            .then(r => r.json())
            .then(data => {
                document.getElementById('MainValue').textContent = data.MainValue || "-";
                document.getElementById('Set').textContent = data.Set || "-";
                document.getElementById('Value').textContent = data.Value || "-";
                document.getElementById('Balance').textContent = parseInt(data.Balance).toLocaleString();
                document.getElementById('Updated').textContent = data.Updated;

                if(data.error){
                    document.getElementById('error').textContent = data.error;
                } else {
                    document.getElementById('error').textContent = ''; 
                }
                
                renderTimeTables(data.historical);
            })
            .catch(e=>{
                document.getElementById('error').textContent = 'API fetch error: ' + e.message;
                document.getElementById('MainValue').textContent = '-';
                renderTimeTables({});
            });
    }

    function updateBettingStatus() {
        const now = new Date();
        const mmtOffsetMinutes = 390; // UTC+6:30
        const mmtTimeInMinutes = now.getUTCHours() * 60 + now.getUTCMinutes() + mmtOffsetMinutes;
        const mmtHour = Math.floor(mmtTimeInMinutes / 60) % 24;
        const mmtMinute = mmtTimeInMinutes % 60;
        const mmtTime = mmtHour * 100 + mmtMinute;

        const betBtnFixed = document.getElementById('betBtnFixed');
        const betPopupTitle = document.getElementById('betPopupTitle');

        const btn1100 = document.getElementById('btn-1100');
        const btn1201 = document.getElementById('btn-1201');
        const btn1500 = document.getElementById('btn-1500');
        const btn1630 = document.getElementById('btn-1630');

        // After 5 PM, betting is for the next day
        if (mmtTime >= 1700) {
            betBtnFixed.textContent = "ထိုးမည်";
            betPopupTitle.textContent = "နောက်ရက်အတွက် ထိုးမည့်အချိန်ရွေးပါ";

            // Get next day's day of the week (0=Sun, 6=Sat)
            const nextDay = new Date(now.getTime() + 24 * 60 * 60 * 1000);
            const nextDayDow = nextDay.getUTCDay();
            const isNextDayWeekend = (nextDayDow === 0 || nextDayDow === 6);
            
            const reasonText = nextDayClosedReason ? `ပိတ်ရက် (${nextDayClosedReason})` : "ဈေးပိတ်သည်";

            if (isClosedNextDay || isNextDayWeekend) {
                [btn1100, btn1201, btn1500, btn1630].forEach(btn => {
                    btn.disabled = true;
                    btn.textContent = reasonText;
                });
                betBtnFixed.style.background = '#ccc';
                betBtnFixed.style.cursor = 'not-allowed';
                betBtnFixed.textContent = `နောက်ရက် ${reasonText}`;
                betBtnFixed.onclick = null;
            } else {
                 // Re-enable everything for next-day betting
                betBtnFixed.style.background = 'var(--warning)';
                betBtnFixed.style.cursor = 'pointer';
                betBtnFixed.textContent = "ထိုးမည်";
                betBtnFixed.onclick = showBetPopup;

                btn1100.disabled = false; btn1100.textContent = "11:00 AM"; btn1100.onclick = () => location.href='11am_2d.php';
                btn1201.disabled = false; btn1201.textContent = "12:01 PM"; btn1201.onclick = () => location.href='1201am_2d.php';
                btn1500.disabled = false; btn1500.textContent = "03:00 PM"; btn1500.onclick = () => location.href='3pm_2d.php';
                btn1630.disabled = false; btn1630.textContent = "04:30 PM"; btn1630.onclick = () => location.href='430pm_2d.php';
            }
        } 
        // Before 5 PM, betting is for today
        else {
            betBtnFixed.textContent = "ထိုးမည်";
            betPopupTitle.textContent = "ထိုးမည့်အချိန်ရွေးပါ";

            const reasonText = closedReason ? `ပိတ်ရက် (${closedReason})` : "ယနေ့ ဈေးပိတ်သည်";
            const isWeekend = (now.getUTCDay() === 0 || now.getUTCDay() === 6);

            if (isClosedToday || isWeekend) {
                 [btn1100, btn1201, btn1500, btn1630].forEach(btn => {
                    btn.disabled = true;
                    btn.textContent = reasonText;
                });
                betBtnFixed.style.background = '#ccc';
                betBtnFixed.style.cursor = 'not-allowed';
                betBtnFixed.textContent = reasonText;
                betBtnFixed.onclick = null;
                return;
            }

            btn1100.disabled = mmtTime >= 1055; btn1100.onclick = () => location.href='11am_2d.php';
            btn1201.disabled = mmtTime >= 1156; btn1201.onclick = () => location.href='1201am_2d.php';
            btn1500.disabled = mmtTime >= 1455; btn1500.onclick = () => location.href='3pm_2d.php';
            btn1630.disabled = mmtTime >= 1625; btn1630.onclick = () => location.href='430pm_2d.php';
        }
    }

    function showBetPopup() {
        // Always update state right before showing
        updateBettingStatus(); 
        const betBtnFixed = document.getElementById('betBtnFixed');
        // If the main button is enabled, show the popup
        if (betBtnFixed.style.cursor !== 'not-allowed') {
            document.getElementById('betPopupBg').style.display = 'block';
        }
    }
    function hideBetPopup() {
        document.getElementById('betPopupBg').style.display = 'none';
    }

    // Initial calls
    getDataAndRender();
    updateBettingStatus();

    // Auto-refresh data and betting status
    setInterval(getDataAndRender, 5000);
    setInterval(updateBettingStatus, 15000); // Check betting status every 15s
    </script>
</body>
</html>