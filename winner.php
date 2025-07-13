<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
require_once __DIR__ . '/db.php';
date_default_timezone_set('Asia/Yangon');

// --- Only logged-in users can see ---
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// --- Function to get Live MainValue ---
function get_live_mainvalue() {
    $target_url = "https://www.set.or.th/th/home";
    $proxies = [
        "https://api.allorigins.win/raw?url=",
        "https://thingproxy.freeboard.io/fetch/",
        "https://corsproxy.io/?"
    ];
    $html = false;
    foreach ($proxies as $proxy) {
        $context = stream_context_create(['http' => ['timeout' => 5]]);
        $proxy_url = $proxy . urlencode($target_url);
        $html = @file_get_contents($proxy_url, false, $context);
        if ($html && strlen($html) > 1000) break;
    }

    if (!$html) return "--";

    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    @$dom->loadHTML($html);
    libxml_clear_errors();
    $xpath = new DOMXPath($dom);

    $set_index = null;
    $rows = $xpath->query('//table//tr');
    foreach ($rows as $row) {
        $tds = $row->getElementsByTagName('td');
        if ($tds->length > 1 && trim($tds->item(0)->textContent) === 'SET') {
            $set_index = trim($tds->item(1)->textContent);
            break;
        }
    }

    $value = null;
    if (preg_match('/([\d,]+\.\d{2})\s*ล้านบาท/', $html, $m)) {
        $value = $m[1];
    }
    
    if ($set_index && $value) {
        $set_digits = preg_replace('/\D/', '', $set_index);
        $set_last_digit = substr($set_digits, -1);
        $value_clean = str_replace(',', '', $value);
        $value_before_decimal = explode('.', $value_clean)[0];
        $value_special_digit = substr($value_before_decimal, -1);
        return $set_last_digit . $value_special_digit;
    }

    return "--";
}

// Fetch the live value to display
$live_main_value = get_live_mainvalue();

// --- DB ---
$pdo = Db::getInstance()->getConnection();

// Find the latest draw that has a winning number posted by the admin
$latestWinnerInfo = $pdo->query("
    SELECT draw_date, session_time, number, prize_rate 
    FROM winning_numbers 
    ORDER BY draw_date DESC, session_time DESC 
    LIMIT 1
")->fetch(PDO::FETCH_ASSOC);

$winners = [];
$winning_number = "-";
$draw_date = null;
$session_time = null;
$prize_rate = 95; 

if ($latestWinnerInfo) {
    $draw_date = $latestWinnerInfo['draw_date'];
    $session_time = $latestWinnerInfo['session_time'];
    $winning_number = $latestWinnerInfo['number'];
    if (!empty($latestWinnerInfo['prize_rate'])) {
        $prize_rate = (int)$latestWinnerInfo['prize_rate'];
    }

    // Now, find all users who bet on this winning number for this specific session
    $stmt = $pdo->prepare("
        SELECT 
            u.name, u.phone, u.profile_photo, b.amount
        FROM bets b
        JOIN users u ON u.id = b.user_id
        WHERE b.draw_date = ? AND b.session_time = ? AND b.number = ?
        ORDER BY b.amount DESC
        LIMIT 100
    ");
    $stmt->execute([$draw_date, $session_time, $winning_number]);
    $winners = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function mask_phone($phone) {
    $clean = preg_replace('/\D/', '', $phone);
    if (strlen($clean) < 7) return str_repeat('*', strlen($clean));
    return substr($clean, 0, 3) . str_repeat('*', 5) . substr($clean, -3);
}

// Robust, always-web path profile photo helper
function get_profile_photo_url($profile_photo) {
    if (!$profile_photo || trim($profile_photo) === '') {
        return '/images/default-avatar.png';
    }
    if (preg_match('/^https?:\/\//', $profile_photo)) {
        return $profile_photo;
    }
    if (strpos($profile_photo, 'uploads/profile/') === 0 ||
        strpos($profile_photo, '/uploads/profile/') === 0) {
        return '/' . ltrim($profile_photo, '/');
    }
    if (strpos($profile_photo, 'uploads/') === 0 ||
        strpos($profile_photo, '/uploads/') === 0) {
        return '/' . ltrim($profile_photo, '/');
    }
    if (strpos($profile_photo, 'images/') === 0 ||
        strpos($profile_photo, '/images/') === 0) {
        return '/' . ltrim($profile_photo, '/');
    }
    if (!preg_match('/\//', $profile_photo)) {
        return '/uploads/profile/' . rawurlencode($profile_photo);
    }
    return '/images/default-avatar.png';
}
?>
<!DOCTYPE html>
<html lang="my">
<head>
    <meta charset="UTF-8">
    <title>ကံထူးရှင်များ (Winners)</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            font-family: 'Inter', 'Noto Sans Myanmar', Arial, sans-serif;
            background: #f6f7fa;
            min-height: 100vh;
            margin: 0;
        }
        .header {
            position: fixed;
            top: 0; left: 0; right: 0;
            height: 78px;
            background: linear-gradient(90deg, #1877f2 60%, #1153a6 120%);
            color: #fff;
            z-index: 1100;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 18px;
            border-bottom-left-radius: 14px;
            border-bottom-right-radius: 14px;
            box-shadow: 0 6px 32px rgba(24,119,242,0.13), 0 1.5px 0 #e2e7ef;
        }
        .header-title {
            font-family: 'Poppins', 'Noto Sans Myanmar', sans-serif;
            font-size: 1.35em;
            font-weight: 700;
            color: #fff;
            letter-spacing: 1.5px;
            display: flex;
            align-items: center;
            gap: 9px;
        }
        .header-logo {
            height: 36px;
            width: 36px;
            border-radius: 9px;
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
            color: #1976d2;
            text-decoration: none;
            padding: 6px 10px;
            border-radius: 8px;
            background: #fff;
            font-size: 0.97em;
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
            max-width: 430px;
            margin: 94px auto 20px auto;
            background: #fff;
            border-radius: 13px;
            padding: 24px 12px 17px 12px;
            box-shadow: 0 4px 16px #0001;
        }
        @media (max-width: 600px) {
            .header {
                height: 60px;
                padding: 0 6px;
                border-bottom-left-radius: 10px;
                border-bottom-right-radius: 10px;
            }
            .header-title {
                font-size: 1.11em;
                gap: 5px;
            }
            .header-logo {
                height: 27px;
                width: 27px;
            }
            .container { margin-top: 70px; padding: 13px 3px 10px 3px; }
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .live-2d-box {
            background: #e6f8f1;
            color: #006a4e;
            border: 1px solid #a3e0c7;
            border-radius: 6px;
            padding: 8px 11px;
            font-size: 0.99em;
            font-weight: 500;
            display: inline-block;
        }
        .live-2d-box span {
            font-weight: bold;
            font-size: 1.1em;
            letter-spacing: 1px;
        }
        h2 { text-align: center; color: #7533c5; margin-bottom: 13px; font-size:1.16em; }
        .win-number-box {
            background: linear-gradient(90deg,#ffbe76,#f6e58d); 
            color: #553c08; 
            border-radius: 7px; 
            font-size: 1.13em;
            font-weight: bold;
            padding: 5px 10px; 
            display:inline-block;
            margin-bottom: 13px;
            letter-spacing: 1.1px;
            text-align: center;
        }
        .winner-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }
        .winner-table th, .winner-table td { 
            padding: 8px 2px; 
            text-align: center; 
            font-size: 0.98em;
        }
        .winner-table th {
            background: #f7f1fe;
            color: #7533c5;
            font-weight: bold;
            border-bottom: 1.5px solid #e6dcf3;
        }
        .winner-table tr:nth-child(even) td { background: #fbfafd;}
        .winner-table tr:nth-child(odd) td { background: #f7f5fa;}
        .winner-table td { color: #333;}
        .winner-profile-photo {
            width: 29px;
            height: 29px;
            border-radius: 50%;
            object-fit: cover;
            box-shadow: 0 2px 7px #7533c510;
            border: 1.3px solid #eee;
            vertical-align: middle;
            margin-right: 2px;
            background: #fafaff;
        }
        .no-winner { text-align: center; color: #a476e2; margin: 1.3em 0 0.6em 0; font-size: 1.06em;}
    </style>
</head>
<body>
    <div class="header" id="main-header">
        <div class="header-title">
            <img src="/images/azm-logo.png" alt="Logo" class="header-logo" />
            AZM2D Game
        </div>
        <div class="header-right">
            <a href="2dnumber.php" class="back-btn"><i class="bi bi-arrow-left"></i> </a>
        </div>
    </div>
    <div class="container">
        <div class="top-bar">
            <div class="live-2d-box">
                Live 2D: <span><?= htmlspecialchars($live_main_value) ?></span>
            </div>
        </div>
        <h2>ထိပ်တန်း ကံထူးရှင်များ</h2>
        <div style="text-align: center;">
            <?php if ($winning_number != "-"): ?>
                <div class="win-number-box">
                    ငွေထုတ်နံပါတ် (Winning 2D): <span><?= htmlspecialchars($winning_number) ?></span>
                    <br>
                    <span style="font-size: 0.8em; color: #8c6c12; font-weight:400;">
                        (<?= htmlspecialchars($draw_date) ?> <?= htmlspecialchars($session_time) ?>)
                    </span>
                </div>
            <?php endif; ?>
        </div>
        <?php if ($winners): ?>
            <table class="winner-table">
                <thead>
                    <tr>
                        <th>ပုံ</th>
                        <th>အမည်</th>
                        <th>ဖုန်း</th>
                        <th>ထိုးကြေး</th>
                        <th>ပေါက်ကြေး (x<?= $prize_rate ?>)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($winners as $w): 
                        $prize = $w['amount'] * $prize_rate;
                        $photo_url = get_profile_photo_url($w['profile_photo'] ?? '');
                    ?>
                    <tr>
                        <td>
                            <img src="<?= htmlspecialchars($photo_url) ?>" class="winner-profile-photo" alt="Profile" loading="lazy" />
                        </td>
                        <td><?= htmlspecialchars($w['name']) ?></td>
                        <td><?= htmlspecialchars(mask_phone($w['phone'])) ?></td>
                        <td><?= number_format($w['amount']) ?></td>
                        <td><?= number_format($prize) ?></td>
                    </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-winner">ယခုပွဲအတွက် ကံထူးရှင် မပေါ်သေးပါ သို့မဟုတ် မရှိသေးပါ။</div>
        <?php endif ?>
    </div>
</body>
</html>