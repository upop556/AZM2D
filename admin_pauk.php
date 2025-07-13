<?php
//admin_pauk.php
session_start();
// Only allow admin (reuse admin_dashboard.php's login logic)
if (empty($_SESSION['admin_logged_in'])) {
    header('Location: admin_dashboard.php');
    exit;
}
// For new admin UI, also set is_admin for API
$_SESSION['user_id'] = 1; // Assuming admin user_id is 1
$_SESSION['is_admin'] = true;
?>
<!DOCTYPE html>
<html lang="my">
<head>
    <meta charset="UTF-8">
    <title>ပေါက်သီး တင်ရန် (Admin Only)</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', 'Noto Sans Myanmar', Arial, sans-serif; background: #f6f7fa; }
        .container { max-width: 500px; margin: 60px auto 20px auto; background: #fff; border-radius: 10px; padding: 32px 24px 24px 24px; box-shadow: 0 4px 16px #0001; position: relative; }
        .top-back {
            position: absolute;
            left: 16px;
            top: 18px;
            z-index: 10;
            text-decoration: none;
            color: #5c56b6;
            background: none;
            font-size: 1.18em;
            font-weight: bold;
            display: flex;
            align-items: center;
        }
        .top-back:hover { color: #6e72fc; }
        .top-back .arrow {
            font-size: 1.5em;
            margin-right: 7px;
            line-height: 1;
        }
        h2 { text-align: center; margin-bottom: 22px; font-size: 1.5rem; color: #5c56b6; }
        label { display: block; margin: 14px 0 4px 0; color: #3a2174; font-weight: 600; letter-spacing: .3px; }
        select, input[type="text"] { width: 100%; padding: 10px; margin-bottom: 12px; border: 1.5px solid #bbb; border-radius: 4px; font-size: 1.12em; background: #f7faff;}
        select:focus, input[type="text"]:focus { border-color: #a585f8; outline: none; background: #f4f1fa;}
        button { width: 100%; padding: 13px; font-size: 1.09em; margin-top: 10px; border-radius: 4px; border: none; cursor: pointer; background: linear-gradient(90deg,#6e72fc 0%,#a585f8 100%); color: #fff; font-weight:700;}
        button:hover { background: linear-gradient(90deg,#563d7c 0%,#8c60f7 100%);}
        .msg { text-align: center; margin-top: 16px; font-size: 1.08em; min-height: 1.5em; }
        
        /* Stats boxes styling */
        .stats-container { 
            display: none; 
            margin-top: 30px;
            background: #f7f5ff;
            border-radius: 8px;
            padding: 15px 20px;
            border: 1px solid #e0deff;
        }
        .stats-title {
            color: #4a2c9c;
            text-align: center;
            font-weight: bold;
            font-size: 1.18em;
            margin-bottom: 15px;
            border-bottom: 1px solid #dcd9f6;
            padding-bottom: 8px;
        }
        .stats-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
            padding: 4px 0;
        }
        .stat-label {
            color: #4a2c9c;
            font-weight: 500;
        }
        .stat-value {
            color: #1a1a1a;
            font-weight: bold;
        }
        .winners-container {
            margin-top: 20px;
            border-top: 1px solid #dcd9f6;
            padding-top: 15px;
        }
        .winner-item {
            padding: 10px 15px;
            margin-bottom: 8px;
            border-radius: 6px;
            background: #fff;
            box-shadow: 0 1px 5px rgba(0,0,0,0.05);
        }
        .winner-name {
            font-weight: bold;
            color: #4a2c9c;
            margin-bottom: 3px;
        }
        .winner-details {
            display: flex;
            justify-content: space-between;
            color: #555;
            font-size: 0.95em;
        }
        .winner-phone {
            color: #777;
        }
        .winner-amount {
            font-weight: bold;
            color: #2e7d32;
        }
    </style>
</head>
<body>
<div class="container">
    <a href="admin_dashboard.php" class="top-back"><span class="arrow">&#8592;</span>Back</a>
    <h2>ပေါက်သီး (2D) တင်ရန်</h2>
    <form id="paukForm" autocomplete="off">
        <label for="session_time">Session Time</label>
        <select id="session_time" name="session_time" required>
            <option value="11:00:00">11:00 AM</option>
            <option value="12:01:00">12:01 PM</option>
            <option value="15:00:00">3:00 PM</option>
            <option value="16:30:00">4:30 PM</option>
        </select>
        <label for="number">ပေါက်သီး (၂ လုံး နံပါတ်)</label>
        <input type="text" id="number" name="number" maxlength="2" pattern="\d{2}" required placeholder="ဥပမာ - 23">
        <button type="submit">တင်မည်</button>
    </form>
    <div class="msg" id="resultMsg"></div>
    
    <!-- Stats container (hidden by default) -->
    <div class="stats-container" id="statsContainer">
        <div class="stats-title">အနိုင်ရရှိသူ အချက်အလက်များ</div>
        
        <div class="stats-row">
            <span class="stat-label">ပေါက်ဂဏန်း:</span>
            <span class="stat-value" id="winningNumber">-</span>
        </div>
        
        <div class="stats-row">
            <span class="stat-label">စစ်မှန်သော အနိုင်ရရှိသူ:</span>
            <span class="stat-value" id="realWinners">-</span>
        </div>
        
        <div class="stats-row">
            <span class="stat-label">အတု ထပ်တိုးသူ:</span>
            <span class="stat-value" id="fakeWinners">-</span>
        </div>
        
        <div class="stats-row">
            <span class="stat-label">စုစုပေါင်း အနိုင်ရရှိသူ:</span>
            <span class="stat-value" id="totalWinners">-</span>
        </div>
        
        <div class="stats-row">
            <span class="stat-label">မတူညီသော အနိုင်ရသူ (ကိုယ်စီ):</span>
            <span class="stat-value" id="uniqueWinners">-</span>
        </div>
        
        <div class="stats-row">
            <span class="stat-label">စုစုပေါင်း ထိုးငွေ:</span>
            <span class="stat-value" id="totalBetAmount">-</span>
        </div>
        
        <div class="stats-row">
            <span class="stat-label">စုစုပေါင်း လျှော်ကြေးငွေ:</span>
            <span class="stat-value" id="totalPayoutAmount">-</span>
        </div>
        
        <div class="winners-container">
            <div class="stats-title">ထိပ်တန်းအနိုင်ရရှိသူများ</div>
            <div id="topWinnersList"></div>
        </div>
    </div>
</div>
<script>
document.getElementById('paukForm').onsubmit = async function(e) {
    e.preventDefault();
    const session_time = document.getElementById('session_time').value;
    const number = document.getElementById('number').value.trim();
    const msg = document.getElementById('resultMsg');
    const statsContainer = document.getElementById('statsContainer');
    
    msg.textContent = 'တင်နေသည်...';
    msg.style.color = "#555";
    statsContainer.style.display = 'none';
    
    if (!/^\d{2}$/.test(number)) {
        msg.textContent = "နံပါတ် ၂ လုံးသာ ရိုက်ထည့်ပါ။";
        msg.style.color = "#d00";
        return;
    }
    
    try {
        const res = await fetch('admin_pauk_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ session_time, number })
        });
        const data = await res.json();
        
        msg.textContent = data.message;
        msg.style.color = data.success ? "#2185d0" : "#d00";
        
        if (data.success && data.winner_stats) {
            document.getElementById('winningNumber').textContent = number;
            document.getElementById('realWinners').textContent = data.winner_stats.real_winners;
            document.getElementById('fakeWinners').textContent = data.winner_stats.fake_winners_added;
            document.getElementById('totalWinners').textContent = data.winner_stats.total_winners;
            document.getElementById('uniqueWinners').textContent = data.winner_stats.unique_winners;
            document.getElementById('totalBetAmount').textContent = 
                Number(data.winner_stats.total_bet_amount).toLocaleString() + ' ကျပ်';
            document.getElementById('totalPayoutAmount').textContent = 
                Number(data.winner_stats.total_payout_amount).toLocaleString() + ' ကျပ်';
            
            const topWinnersList = document.getElementById('topWinnersList');
            topWinnersList.innerHTML = '';
            
            if (data.top_winners && data.top_winners.length > 0) {
                data.top_winners.forEach(winner => {
                    const winnerItem = document.createElement('div');
                    winnerItem.className = 'winner-item';
                    
                    let phone = winner.phone || 'N/A';
                    // Mask phone number (show first 3 and last 3 digits if possible)
                    if (/^\d{7,}$/.test(phone)) {
                        phone = phone.substring(0, 3) + '*****' + phone.substring(phone.length - 3);
                    }
                    
                    winnerItem.innerHTML = `
                        <div class="winner-name">${winner.name || 'Unknown'}</div>
                        <div class="winner-details">
                            <span class="winner-phone">${phone}</span>
                            <span class="winner-amount">ထိုး: ${Number(winner.amount).toLocaleString()} | ရ: ${Number(winner.payout_amount).toLocaleString()}</span>
                        </div>
                    `;
                    topWinnersList.appendChild(winnerItem);
                });
            } else {
                topWinnersList.innerHTML = '<div style="text-align:center;color:#888;padding:10px;">အနိုင်ရရှိသူ မရှိသေးပါ</div>';
            }
            
            statsContainer.style.display = 'block';
        }
    } catch (err) {
        msg.textContent = "Network error or invalid response.";
        msg.style.color = "#d00";
        console.error(err);
    }
};
</script>
</body>
</html>