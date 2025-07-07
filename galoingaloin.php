<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
session_start();

require_once __DIR__ . '/db.php';

// --- AUTH ---
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

define('SLOT_ROUND_DURATION', 180);
define('FIRST_GAME_START', strtotime('2025-06-28 00:00:00'));
define('BET_CLOSE_BEFORE_DRAW', 5);

function db() {
    return Db::getInstance()->getConnection();
}
function get_current_round_index() {
    $now = time();
    $index = floor(($now - FIRST_GAME_START) / SLOT_ROUND_DURATION);
    return max(0, $index);
}

// --- USER BALANCE ---
$user_id = $_SESSION['user_id'];
$pdo = db();
$stmt = $pdo->prepare("SELECT balance FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$user_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$current_balance = $row ? (float)$row['balance'] : 0;

if (!isset($_SESSION['balance']) || $_SESSION['balance'] !== $current_balance) {
    $_SESSION['balance'] = $current_balance;
}
if (!isset($_SESSION['last_bet_amount']) || !is_numeric($_SESSION['last_bet_amount'])) $_SESSION['last_bet_amount'] = 0;

// --- ANIMAL KEYS ---
$animal_keys = ["chicken", "elephant", "tiger", "shrimp", "turtle", "fish"];
if (!isset($_SESSION['bets']) || !is_array($_SESSION['bets'])) {
    $_SESSION['bets'] = [];
}
foreach ($animal_keys as $k) {
    if (!isset($_SESSION['bets'][$k])) {
        $_SESSION['bets'][$k] = 0;
    }
}

// --- ANIMALS ---
$animals = [
    ["name" => "ကြက်", "photo" => "/images/chicken.png", "key" => "chicken"],
    ["name" => "ဆင်", "photo" => "/images/elephant.png", "key" => "elephant"],
    ["name" => "ကျား", "photo" => "/images/tiger.png", "key" => "tiger"],
    ["name" => "ပုစွန်", "photo" => "/images/shrimp.png", "key" => "shrimp"],
    ["name" => "လိပ်", "photo" => "/images/turtle.png", "key" => "turtle"],
    ["name" => "ငါး", "photo" => "/images/fish.png", "key" => "fish"],
];

// --- PURE RANDOM ROUND LOGIC ---
function get_or_create_round($animals) {
    $pdo = db();
    $current_round_no = get_current_round_index();
    $stmt = $pdo->prepare("SELECT * FROM slot_rounds WHERE round_no = ?");
    $stmt->execute([$current_round_no]);
    $round = $stmt->fetch(PDO::FETCH_ASSOC);

    $animal_keys = array_column($animals, 'key');

    if (!$round) {
        $rand_keys = array_rand($animal_keys, 3);
        if (!is_array($rand_keys)) $rand_keys = [$rand_keys];
        $rand_keys = array_unique($rand_keys);
        while (count($rand_keys) < 3) {
            $more = array_rand($animal_keys, 1);
            if (!in_array($more, $rand_keys)) {
                $rand_keys[] = $more;
            }
        }
        $slot1 = $animal_keys[$rand_keys[0]];
        $slot2 = $animal_keys[$rand_keys[1]];
        $slot3 = $animal_keys[$rand_keys[2]];
        $draw_time = date("Y-m-d H:i:s", FIRST_GAME_START + ($current_round_no + 1) * SLOT_ROUND_DURATION);

        $stmt2 = $pdo->prepare("INSERT INTO slot_rounds (round_no, slot1, slot2, slot3, draw_time) VALUES (?, ?, ?, ?, ?)");
        $stmt2->execute([$current_round_no, $slot1, $slot2, $slot3, $draw_time]);
        $round = [
            "round_no" => $current_round_no,
            "slot1" => $slot1,
            "slot2" => $slot2,
            "slot3" => $slot3,
            "draw_time" => $draw_time
        ];
    }
    return $round;
}

$round = get_or_create_round($animals);

function clear_all_bets_and_session() {
    unset($_SESSION['bets']);
    unset($_SESSION['bets_clear_time']);
    unset($_SESSION['payout_claimed']);
}

// --- Reset bets and session state when round changes (after 4 seconds from previous round's draw_time) ---
if (
    isset($_SESSION['previous_round_no']) &&
    $_SESSION['previous_round_no'] != $round['round_no']
) {
    $stmt = $pdo->prepare("SELECT draw_time FROM slot_rounds WHERE round_no = ?");
    $stmt->execute([$_SESSION['previous_round_no']]);
    $prev_round = $stmt->fetch(PDO::FETCH_ASSOC);
    $prev_draw_time = isset($prev_round['draw_time']) ? strtotime($prev_round['draw_time']) : false;

    if ($prev_draw_time && (time() >= $prev_draw_time + 4)) {
        clear_all_bets_and_session();
    } else {
        $_SESSION['bets_clear_time'] = $prev_draw_time ? $prev_draw_time + 4 : time() + 4;
    }
}
$_SESSION['previous_round_no'] = $round['round_no'];

// --- Always clear bets if time comes (failsafe) ---
if (isset($_SESSION['bets_clear_time']) && time() >= $_SESSION['bets_clear_time']) {
    clear_all_bets_and_session();
}

// --- Check if betting is open for the current round ---
function is_bet_open($round) {
    $draw_time = strtotime($round['draw_time']);
    $now = time();
    $remaining = $draw_time - $now;
    return $remaining > BET_CLOSE_BEFORE_DRAW;
}

function result_text_message($payout, $won_animals, $animals) {
    if ($payout > 0) {
        $lines = [];
        foreach ($won_animals as $w) {
            $animal_name = "";
            foreach ($animals as $a) {
                if ($a['key'] === $w['animal']) {
                    $animal_name = $a['name'];
                    break;
                }
            }
            $lines[] = sprintf(
                "%s (Bet %s x %s) = <b>%s</b> ကျပ်",
                htmlspecialchars($animal_name),
                number_format($w['bet']),
                $w['multiplier'],
                number_format($w['pay'])
            );
        }
        $main = "အနိုင်!";
        $main .= "<br>စုစုပေါင်း အနိုင်ငွေ: <span class=\"balance-highlight\">" . number_format($payout) . "</span> ကျပ်";
        return $main . "<br>" . implode("<br>", $lines);
    } else {
        return "<span style=\"color:#e74c3c\">အရှုံးပါ! နောက်တစ်ပတ်တိုးကြိုးစားပါ။</span>";
    }
}

// --- API: Place bet for animal ---
if (isset($_SESSION['bets_clear_time']) && time() < $_SESSION['bets_clear_time']) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bet_animal'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Please wait for new round to start.',
            'balance' => $_SESSION['balance'],
            'bets' => isset($_SESSION['bets']) ? $_SESSION['bets'] : [],
        ]);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_balance'])) {
    $amt = intval($_POST['set_balance']);
    if ($amt > 0) {
        $_SESSION['last_bet_amount'] = $amt;
    }
    echo json_encode([
        'balance' => $_SESSION['balance'],
        'last_bet_amount' => $_SESSION['last_bet_amount']
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bet_animal'])) {
    $animal_key = $_POST['bet_animal'];
    $bet_amount = isset($_SESSION['last_bet_amount']) ? intval($_SESSION['last_bet_amount']) : 0;
    if (!in_array($animal_key, $animal_keys)) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid animal selected.',
            'balance' => $_SESSION['balance'],
            'bets' => isset($_SESSION['bets']) ? $_SESSION['bets'] : [],
        ]);
        exit;
    }
    if (!is_bet_open($round)) {
        echo json_encode([
            'success' => false,
            'message' => 'Betting is closed for this round.',
            'balance' => $_SESSION['balance'],
            'bets' => isset($_SESSION['bets']) ? $_SESSION['bets'] : [],
        ]);
        exit;
    }
    if ($bet_amount <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Bet amount is invalid.',
            'balance' => $_SESSION['balance'],
            'bets' => isset($_SESSION['bets']) ? $_SESSION['bets'] : [],
        ]);
        exit;
    }
    if ($_SESSION['balance'] < $bet_amount) {
        echo json_encode([
            'success' => false,
            'message' => 'လက်ကျန်ငွေ မလုံလောက်ပါ။',
            'balance' => $_SESSION['balance'],
            'bets' => isset($_SESSION['bets']) ? $_SESSION['bets'] : [],
        ]);
        exit;
    }
    if (!isset($_SESSION['bets']) || !is_array($_SESSION['bets'])) {
        $_SESSION['bets'] = [];
        foreach ($animal_keys as $k) {
            $_SESSION['bets'][$k] = 0;
        }
    } elseif (!isset($_SESSION['bets'][$animal_key])) {
        $_SESSION['bets'][$animal_key] = 0;
    }
    $_SESSION['balance'] -= $bet_amount;
    $_SESSION['bets'][$animal_key] += $bet_amount;

    $stmt = $pdo->prepare("UPDATE users SET balance = ? WHERE id = ?");
    $stmt->execute([$_SESSION['balance'], $user_id]);
    echo json_encode([
        'success' => true,
        'message' => 'Bet placed successfully.',
        'balance' => $_SESSION['balance'],
        'bets' => $_SESSION['bets']
    ]);
    exit;
}

// --- API: Get balance, bets, bet_amount ---
if (isset($_GET['get_balance'])) {
    $stmt = $pdo->prepare("SELECT balance FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$user_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $db_balance = $row ? (float)$row['balance'] : 0;
    $_SESSION['balance'] = $db_balance;
    echo json_encode([
        'balance' => $_SESSION['balance'],
        'bets' => isset($_SESSION['bets']) ? $_SESSION['bets'] : [],
        'last_bet_amount' => $_SESSION['last_bet_amount'],
        'debug' => [
            'user_id' => $user_id,
            'round_no' => $round['round_no'],
            'session_id' => session_id()
        ]
    ]);
    exit;
}

// --- API: Auto spin slots & payout & round_no update ---
if (isset($_GET['auto_spin'])) {
    $slot_keys = [$round['slot1'], $round['slot2'], $round['slot3']];
    $slot_wins = array_count_values($slot_keys);

    if (!isset($_SESSION['bets']) || !is_array($_SESSION['bets'])) {
        $_SESSION['bets'] = [];
    }
    foreach ($animal_keys as $k) {
        if (!isset($_SESSION['bets'][$k])) {
            $_SESSION['bets'][$k] = 0;
        }
    }
    $user_bets = $_SESSION['bets'];

    $payout = 0;
    $won_animals = [];
    $total_bet = 0;
    foreach ($user_bets as $animal => $bet_amt) {
        $bet_amt = (float)$bet_amt;
        $total_bet += $bet_amt;
        $count = isset($slot_wins[$animal]) ? (int)$slot_wins[$animal] : 0;
        if ($bet_amt > 0 && $count > 0) {
            $multiplier = $count;
            $pay = $bet_amt * $multiplier;
            $payout += $pay;
            $won_animals[] = [
                'animal' => $animal,
                'bet' => $bet_amt,
                'count' => $count,
                'pay' => $pay,
                'multiplier' => $multiplier
            ];
        }
    }
    if (
        !isset($_SESSION['payout_claimed']) ||
        $_SESSION['payout_claimed'] !== $round['round_no']
    ) {
        if ($payout > 0) {
            $stmt = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
            $stmt->execute([$payout, $user_id]);
            $_SESSION['balance'] += $payout;
        }
        $_SESSION['payout_claimed'] = $round['round_no'];
    }
    $bet_animals = [];
    foreach ($animal_keys as $k) {
        $bet_animals[$k] = isset($user_bets[$k]) ? $user_bets[$k] : 0;
    }
    $bet_animals_json = json_encode($bet_animals);
    $draw_time_str = date('H:i:s', strtotime($round['draw_time']));
    $draw_date = date('Y-m-d', strtotime($round['draw_time']));
    $status = $payout > 0 ? 'won' : 'lost';
    $winnings = number_format($payout, 2, '.', '');
    $created_at = date('Y-m-d H:i:s');
    $check = $pdo->prepare("SELECT id FROM bets WHERE user_id = ? AND round_no = ?");
    $check->execute([$user_id, $round['round_no']]);
    $already_bet = $check->fetch(PDO::FETCH_ASSOC);
    if ($already_bet) {
        $stmt = $pdo->prepare("UPDATE bets SET bet_animals = ?, amount = ?, status = ?, winnings = ?, draw_time = ?, draw_date = ?, created_at = ? WHERE id = ?");
        $stmt->execute([
            $bet_animals_json, $total_bet, $status, $winnings, $draw_time_str, $draw_date, $created_at, $already_bet['id']
        ]);
    } elseif ($total_bet > 0) {
        $stmt = $pdo->prepare("INSERT INTO bets (user_id, round_no, bet_animals, amount, draw_time, draw_date, status, winnings, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $user_id, $round['round_no'], $bet_animals_json, $total_bet, $draw_time_str, $draw_date, $status, $winnings, $created_at
        ]);
    }
    $result_text = $total_bet > 0 ? result_text_message($payout, $won_animals, $animals) : "ဒီပွဲအတွက် ဘာမှ မထိုးရသေးပါ။";
    header("Content-Type: application/json; charset=UTF-8");
    echo json_encode([
        'slots' => $slot_keys,
        'bets' => $user_bets,
        'payout' => $payout,
        'won_animals' => $won_animals,
        'current_balance' => $_SESSION['balance'],
        'result_text' => $result_text,
        'round_no' => $round['round_no'] + 1,
        'slot1' => $round['slot1'],
        'slot2' => $round['slot2'],
        'slot3' => $round['slot3'],
        'draw_time' => $round['draw_time']
    ]);
    exit;
}

// --- API: Get draw time ---
if (isset($_GET['get_draw_time'])) {
    $draw_time = strtotime($round['draw_time']);
    $now = time();
    $remaining = $draw_time - $now;
    if ($remaining < 0) $remaining = 0;

    echo json_encode([
        'draw_time' => $round['draw_time'],
        'remaining' => $remaining,
        'round_no' => $round['round_no'] + 1,
        'bet_open' => $remaining > BET_CLOSE_BEFORE_DRAW ? 1 : 0,
        'slot1' => $round['slot1'],
        'slot2' => $round['slot2'],
        'slot3' => $round['slot3']
    ]);
    exit;
}

// --- API: Clear all current bets (UX improvement) ---
if (isset($_GET['clear_bets'])) {
    clear_all_bets_and_session();
    echo json_encode([
        'success' => true,
        'message' => 'All bets cleared.',
        'bets' => isset($_SESSION['bets']) ? $_SESSION['bets'] : [],
        'balance' => $_SESSION['balance']
    ]);
    exit;
}

// --- API: Debug session data ---
if (isset($_GET['debug_session'])) {
    echo json_encode([
        'session_id' => session_id(),
        'user_id' => $user_id,
        'current_round' => $round['round_no'],
        'previous_round' => isset($_SESSION['previous_round_no']) ? $_SESSION['previous_round_no'] : 'none',
        'balance' => $_SESSION['balance'],
        'bets' => isset($_SESSION['bets']) ? $_SESSION['bets'] : [],
        'last_bet_amount' => $_SESSION['last_bet_amount'],
        'payout_claimed' => isset($_SESSION['payout_claimed']) ? $_SESSION['payout_claimed'] : 'none',
        'session_data' => $_SESSION
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="my">
<head>
    <meta charset="UTF-8">
    <title>ဂလုံးဂလုံးဂိမ်း</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="/css/header.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: "Myanmar Text", sans-serif; background: #f4f4f4; text-align: center; padding: 20px 5px; margin: 0;}
        .balance-info { font-size:1.13em; color:#1abc9c; margin-top:12px; background:#e0fcf6; border-radius:8px; font-weight:bold; display:inline-block; padding:8px 16px;}
        .balance-info .balance-highlight { color:#16a085; background: #fffbe8; padding: 2px 10px; border-radius:8px;}
        .balance-info.updated { animation: highlightBalance 1s; }
        @keyframes highlightBalance { 
            0% { background: #fffbe8; } 
            60% { background: #f1ffe8; } 
            100% { background: #e0fcf6; }
        }
        .game-card {
            max-width: 430px;
            margin: 0 auto;
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 3px 16px #b1b1b1;
            padding: 30px 10px 20px 10px;
            padding-bottom: 100px;
            position: relative;
        }
        .round-info {
            margin: 18px 0;
            color: #1976d2;
            font-size: 1.15em;
            font-weight: 700;
        }
        .back-btn {
            position: absolute;
            left: 12px;
            top: 12px;
            background: #1976d2;
            color: #fff;
            border: none;
            border-radius: 50%;
            width: 42px;
            height: 42px;
            font-size: 1.6em;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 1px 6px #d1d1d1;
            z-index: 10;
            transition: background 0.15s;
        }
        .back-btn:hover {
            background: #145aa6;
        }
        .balance-info {
            font-size:1.13em;
            color:#1abc9c;
            margin-top:12px;
        }
        .slot-container {
            margin: 20px auto 0 auto;
            display: flex;
            justify-content: center;
            min-height:110px;
        }
        .slot {
            font-size: 1.4em;
            width: 82px;
            height: 100px;
            background: #f9f9f9;
            margin: 0 8px;
            border-radius: 12px;
            box-shadow: 2px 2px 10px #eee;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        }
        .slot img {
            width: 56px;
            height: 56px;
            margin-bottom: 8px;
        }
        /* ICON ROW */
        .icon-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 18px 0 0 0;
            max-width: 320px;
            margin-left: auto;
            margin-right: auto;
            gap: 16px;
        }
        .icon-link {
            flex: 1 1 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-decoration: none;
            color: #16a085;
            font-size: 1.08em;
            background: #f6f6f6;
            border-radius: 12px;
            padding: 10px 0;
            box-shadow: 1px 1px 5px #eee;
            transition: box-shadow 0.2s, background 0.2s;
        }
        .icon-link:hover, .icon-link:active, .icon-link:focus {
            background: #e9f6f5;
            box-shadow: 0 0 2px #16a085, 1px 1px 5px #eee;
        }
        .icon-link img {
            width: 36px;
            height: 36px;
            margin-bottom: 4px;
        }
        .autospin-info {
            margin-top: 24px;
            font-size: 1.07em;
            color: #1abc9c;
        }
        .animals-list {
            margin-top: 28px;
        }
        .animals-list h3 {
            margin-bottom: 6px;
        }
        .animal-item {
            display: inline-block;
            font-size: 1.6em;
            margin: 0 10px 18px 10px;
            padding: 12px 20px;
            background: #f6f6f6;
            border-radius: 12px;
            box-shadow: 1px 1px 5px #eee;
            vertical-align: top;
            cursor:pointer;
            transition:box-shadow 0.15s, background 0.15s;
        }
        .animal-item.selected, .animal-item:active {
            background:#e9f6f5;
            box-shadow:0 0 2px #16a085, 1px 1px 5px #eee;
        }
        .animal-item img {
            width: 96px;
            height: 96px;
            display: block;
            margin: 0 auto 10px auto;
            pointer-events:none;
        }
        .bet-amount {
            color: #e67e22;
            font-size: 1.06em;
            margin-top:8px;
        }
        .balance-btn-bottom-sticky {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 0;
            background: #fff;
            z-index: 999;
            box-shadow: 0 -1px 8px #d0d0d0;
            padding: 10px 0 14px 0;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 18px;
        }
        .balance-btn-bottom-sticky button {
            font-size:1.10em;
            background:#f4f4f4;
            border:1px solid #1abc9c;
            color:#16a085;
            border-radius:8px;
            padding:10px 42px;
            cursor:pointer;
        }
        .balance-btn-bottom-sticky .selected-amount {
            color: #16a085;
            font-weight: 700;
            font-size: 1.1em;
            margin-left: 0;
        }
        .balance-btn-bottom-sticky .selected-amount[hidden] { display:none; }
        .popup-inner { margin-bottom: 32px; }
        .balance-popup {
            position: fixed;
            left:0;top:0;right:0;bottom:0;
            background:rgba(0,0,0,0.3);
            display:none;
            align-items:flex-end;
            justify-content:center;
            z-index:1000;
        }
        .popup-inner {
            background:#fff;
            border-radius:14px;
            padding:26px 20px 20px 20px;
            max-width:320px;
            margin:auto;
            margin-bottom:32px;
        }
        .popup-inner h3 { margin:0 0 18px 0; color:#16a085;}
        .popup-amounts button {
            margin: 6px 8px 6px 0;
            padding: 8px 15px;
            font-size:1.11em;
            border-radius:7px;
            border:1px solid #16a085;
            background:#f6f6f6;
            color:#16a085; cursor:pointer;
        }
        .popup-amounts button.active { background:#16a085; color:#fff;}
        .popup-inner .popup-confirm {
            margin-top: 20px;
            background:#16a085;
            color:#fff;
            border:none;
            border-radius:7px;
            padding:8px 28px;
            font-size:1.1em;
            cursor:pointer;
        }
        @media (max-width: 700px) {
            .game-card { max-width: 100vw; border-radius: 0; padding-bottom: 100px;}
            .icon-row { max-width: 220px; gap: 10px; }
            .icon-link { font-size: 0.98em; padding: 7px 0; }
            .icon-link img { width: 28px; height: 28px; }
            .slot { width: 65px; height: 75px; font-size: 1em; }
            .slot img { width: 38px; height: 38px; }
            .animal-item { font-size: 1.1em; padding: 7px 11px; margin: 0 5px 9px 5px; }
            .animal-item img { width: 52px; height: 52px; }
            .balance-btn-bottom-sticky button { padding: 8px 24px; font-size: 1.07em; }
            .balance-btn-bottom-sticky { padding: 7px 0 10px 0; gap: 10px;}
        }
        @media (max-width: 400px) {
            .icon-row { max-width: 95vw; }
            .animal-item { padding: 4px 3px; }
            .game-card { padding-bottom: 90px; }
        }
    </style>
</head>
<body style="background-image: url('https://amazemm.xyz/images/bg-main.jpg'); background-size: cover; background-repeat: no-repeat; background-position: center center; background-attachment: fixed;">
  <div class="header" id="main-header">
    <div class="header-title">
      <img src="/images/azm-logo.png" alt="Logo" class="header-logo" />
      AZM2D Game
    </div>
  </div>
  <div class="game-card" style="margin-top: 80px;">
      <button class="back-btn" onclick="window.location.href='/a/game.php'" title="Back">&#8592;</button>
      <div class="round-info">
          ပွဲစဉ် နံပါတ်: <span id="round_no"><?= $round['round_no'] + 1 ?></span>
      </div>
      <h1 style="color:#16a085;margin-top:0;">ဂလုံးဂလုံးဂိမ်း</h1>
      <div class="balance-info" id="balance-info">
          လက်ကျန်ငွေ: <span id="balance"><?php echo number_format($current_balance); ?></span> ကျပ်
      </div>
      <div id="payout-message" style="margin:10px 0 0 0; color:#e67e22; font-weight:bold;"></div>
      <div class="slot-container" id="slots">
          <?php
              $slot_keys = [$round['slot1'], $round['slot2'], $round['slot3']];
              foreach ($slot_keys as $k) {
                  foreach ($animals as $a) {
                      if ($a['key'] === $k) {
                          echo '<div class="slot"><img src="'.htmlspecialchars($a['photo']).'" alt="'.htmlspecialchars($a['name']).'">'.htmlspecialchars($a['name']).'</div>';
                      }
                  }
              }
          ?>
      </div>
      <div class="icon-row">
          <a href="bet-history.php" class="icon-link" title="Bet History">
              <img src="/images/history.png" alt="History">
              Bet History
          </a>
          <a href="animals-winner.php" class="icon-link" title="Animals Winner">
              <img src="/images/trophy.png" alt="Winners">
              Winners
          </a>
      </div>
      <div class="autospin-info">
          <span></span>
          <div id="autospin-timer" style="margin-top:6px;font-size:0.95em;color:#888;"></div>
      </div>
      <div class="animals-list" id="animal-bet-list">
          <h3>ပါဝင်သတ္တဝါများ</h3>
          <?php foreach($animals as $animal): ?>
              <span class="animal-item" data-animal="<?php echo $animal['key']; ?>">
                  <img src="<?php echo htmlspecialchars($animal['photo'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($animal['name'], ENT_QUOTES, 'UTF-8'); ?>">
                  <?php echo htmlspecialchars($animal['name'], ENT_QUOTES, 'UTF-8'); ?>
                  <div class="bet-amount" id="bet-<?php echo $animal['key']; ?>">0</div>
              </span>
          <?php endforeach; ?>
      </div>
  </div>
  <div class="balance-btn-bottom-sticky">
      <button type="button" id="select-balance-btn">Bet ငွေရွေးရန်</button>
      <span class="selected-amount" id="selected-bet-amount" hidden></span>
  </div>
  <div class="balance-popup" id="balance-popup">
      <div class="popup-inner">
          <h3>Bet ငွေရွေးရန်</h3>
          <div class="popup-amounts" id="popup-amounts">
              <?php
              $amounts = [100,200,300,500,1000,2000,3000,5000,10000];
              foreach($amounts as $amt){
                  echo '<button data-amt="'.$amt.'">'.number_format($amt).'</button>';
              }
              ?>
          </div>
          <button class="popup-confirm" id="balance-confirm" disabled>OK</button>
      </div>
  </div>

<script>
let lastBetAmount = 0;
let selectedAmount = null;
let timerDiv = document.getElementById('autospin-timer');
let timerInterval = null;

// Show selected bet amount in sticky bar
function showSelectedBetAmount(val) {
    let el = document.getElementById('selected-bet-amount');
    if (val) {
        el.textContent = Number(val).toLocaleString() + " ကျပ်";
        el.removeAttribute('hidden');
    } else {
        el.setAttribute('hidden', 'hidden');
    }
}

// Timer display update
function updateTimerDisplay(remaining) {
    let min = Math.floor(remaining / 60);
    let sec = remaining % 60;
    timerDiv.textContent = "ပေါက်ကောင်ဖွင့်ရန် ကျန်ရှိချိန် - " + min + " မိနစ် " + (sec < 10 ? ("0" + sec) : sec) + " စက္ကန့်";
}

// Animated slot update + payout + result
function fetchAndUpdateSlotsAnimated(callback) {
    fetch(window.location.pathname + '?auto_spin=1')
        .then(response => response.json())
        .then(data => {
            // Update slot UI from server response!
            const slotDivs = document.querySelectorAll('#slots .slot');
            const slotKeys = [data.slot1, data.slot2, data.slot3];
            const animalDict = {
                chicken: {name: "ကြက်", photo: "/images/chicken.png"},
                elephant: {name: "ဆင်", photo: "/images/elephant.png"},
                tiger: {name: "ကျား", photo: "/images/tiger.png"},
                shrimp: {name: "ပုစွန်", photo: "/images/shrimp.png"},
                turtle: {name: "လိပ်", photo: "/images/turtle.png"},
                fish: {name: "ငါး", photo: "/images/fish.png"}
            };
            slotDivs.forEach((div, i) => {
                const a = animalDict[slotKeys[i]];
                div.innerHTML = `<img src="${a.photo}" alt="${a.name}">${a.name}`;
            });
            // Update round number after spin!
            if (data.round_no) {
                document.getElementById('round_no').textContent = data.round_no;
            }
            if (typeof callback === "function") {
                callback(data);
            }
        });
}

// Payout message and UI update after payout (now uses auto_spin result)
function showPayoutMessageFromAutoSpin(data) {
    let payoutDiv = document.getElementById('payout-message');
    payoutDiv.innerHTML = data.result_text;

    // Update balance instantly with highlight
    let balEl = document.getElementById('balance');
    let balInfo = document.getElementById('balance-info');
    balEl.textContent = Number(data.current_balance).toLocaleString();
    balInfo.classList.add('updated');
    setTimeout(()=>{ balInfo.classList.remove('updated'); }, 1100);

    // BET AMOUNT တွေကို UI မှာ 0 ပြန်တင်
    if (data.bets) { refreshBetsOnUI(data.bets); }
    else { document.querySelectorAll('.bet-amount').forEach(el => el.textContent = "0"); }

    document.querySelectorAll('.animal-item').forEach(el => el.classList.remove('selected'));
    showSelectedBetAmount(0);

    setTimeout(() => { payoutDiv.innerHTML = ""; }, 6000);
}

// Timer fetch and auto round logic
function fetchDrawTimeAndStartTimer() {
    fetch(window.location.pathname + '?get_draw_time=1')
        .then(r => r.json())
        .then(data => {
            let countdown = data.remaining;
            let thisRoundNo = data.round_no;
            document.getElementById('round_no').textContent = thisRoundNo;
            // Always update slots for new round from server (pre-spin)
            const slotDivs = document.querySelectorAll('#slots .slot');
            const slotKeys = [data.slot1, data.slot2, data.slot3];
            const animalDict = {
                chicken: {name: "ကြက်", photo: "/images/chicken.png"},
                elephant: {name: "ဆင်", photo: "/images/elephant.png"},
                tiger: {name: "ကျား", photo: "/images/tiger.png"},
                shrimp: {name: "ပုစွန်", photo: "/images/shrimp.png"},
                turtle: {name: "လိပ်", photo: "/images/turtle.png"},
                fish: {name: "ငါး", photo: "/images/fish.png"}
            };
            slotDivs.forEach((div, i) => {
                const a = animalDict[slotKeys[i]];
                div.innerHTML = `<img src="${a.photo}" alt="${a.name}">${a.name}`;
            });
            // BET amount UI reset
            document.querySelectorAll('.bet-amount').forEach(el => el.textContent = "0");
            updateTimerDisplay(countdown);
            if (timerInterval) clearInterval(timerInterval);
            timerInterval = setInterval(() => {
                countdown--;
                if (countdown < 0) {
                    clearInterval(timerInterval);
                    fetchAndUpdateSlotsAnimated(function (data) {
                        showPayoutMessageFromAutoSpin(data);
                        fetchDrawTimeAndStartTimer();
                    });
                    return;
                }
                updateTimerDisplay(countdown);
            }, 1000);
        });
}
fetchDrawTimeAndStartTimer();

// Popup logic for bet amount selection
function openBalancePopup() {
    document.getElementById('balance-popup').style.display = 'flex';
    selectedAmount = null;
    document.getElementById('balance-confirm').disabled = true;
    document.querySelectorAll('#popup-amounts button').forEach(btn => btn.classList.remove('active'));
}
function closeBalancePopup() {
    document.getElementById('balance-popup').style.display = 'none';
}
document.getElementById('select-balance-btn').onclick = openBalancePopup;
document.getElementById('balance-popup').addEventListener('click', function (e) {
    if (e.target === this) closeBalancePopup();
});
document.querySelectorAll('#popup-amounts button').forEach(btn => {
    btn.onclick = function () {
        selectedAmount = this.getAttribute('data-amt');
        document.querySelectorAll('#popup-amounts button').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        document.getElementById('balance-confirm').disabled = false;
    }
});
document.getElementById('balance-confirm').onclick = function () {
    if (!selectedAmount) return;
    lastBetAmount = parseInt(selectedAmount);
    showSelectedBetAmount(lastBetAmount);
    closeBalancePopup();
    fetch(window.location.pathname + '?get_balance=1')
        .then(r => r.json())
        .then(data => {
            let realBal = data.balance;
            if (selectedAmount > realBal) {
                alert("လက်ကျန်ငွေထက်ပို၍ မထိုးနိုင်ပါ။");
                return;
            }
            fetch(window.location.pathname, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'set_balance=' + encodeURIComponent(selectedAmount)
            })
                .then(r => r.json())
                .then(data => {
                    document.getElementById('balance').textContent = Number(data.balance).toLocaleString();
                    refreshBetsOnUI({});
                });
        });
};

// Animal click/bet logic
document.querySelectorAll('.animal-item').forEach(el => {
    el.onclick = function () {
        if (!lastBetAmount) {
            openBalancePopup();
            return;
        }
        let animalKey = this.getAttribute('data-animal');
        fetch(window.location.pathname + '?get_balance=1')
            .then(r => r.json())
            .then(data => {
                let bal = data.balance;
                lastBetAmount = data.last_bet_amount;
                showSelectedBetAmount(lastBetAmount);
                if (bal < lastBetAmount) {
                    openBalancePopup();
                    return;
                }
                fetch(window.location.pathname, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'bet_animal=' + encodeURIComponent(animalKey)
                })
                    .then(r => r.json())
                    .then(result => {
                        if (result.success) {
                            document.getElementById('balance').textContent = Number(result.balance).toLocaleString();
                            refreshBetsOnUI(result.bets);
                        } else {
                            alert(result.message || "Bet မအောင်မြင်ပါ။");
                            if (result.balance === 0) openBalancePopup();
                        }
                    });
            });
    }
});

function refreshBalance() {
    fetch(window.location.pathname + '?get_balance=1')
        .then(r => r.json())
        .then(data => {
            document.getElementById('balance').textContent = Number(data.balance).toLocaleString();
            lastBetAmount = data.last_bet_amount;
            showSelectedBetAmount(lastBetAmount);
            refreshBetsOnUI(data.bets);
        });
}

function refreshBetsOnUI(bets) {
    document.querySelectorAll('.animal-item').forEach(el => {
        let key = el.getAttribute('data-animal');
        let amt = bets && bets[key] ? bets[key] : 0;
        document.getElementById('bet-' + key).textContent = amt ? Number(amt).toLocaleString() : "0";
        el.classList.remove('selected');
    });
}
refreshBalance();
</script>
</body>
</html>