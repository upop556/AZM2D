<?php
//animals.php
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

// --- Animals ---
$animals = [
    ["key" => "chicken", "name" => "ကြက်", "photo" => "/images/chicken.png"],
    ["key" => "elephant", "name" => "ဆင်", "photo" => "/images/elephant.png"],
    ["key" => "tiger", "name" => "ကျား", "photo" => "/images/tiger.png"],
    ["key" => "shrimp", "name" => "ပုစွန်", "photo" => "/images/shrimp.png"],
    ["key" => "turtle", "name" => "လိပ်", "photo" => "/images/turtle.png"],
    ["key" => "fish", "name" => "ငါး", "photo" => "/images/fish.png"],
];
$animal_keys = array_column($animals, 'key');

// --- Round logic ---
define('SLOT_ROUND_DURATION', 180); // 3 min
define('FIRST_GAME_START', strtotime('2025-06-28 00:00:00'));

function get_current_round_index() {
    $now = time();
    $index = floor(($now - FIRST_GAME_START) / SLOT_ROUND_DURATION);
    return max(0, $index);
}
$round_index = get_current_round_index();
$round_no = $round_index + 1;

// --- DB & User ---
$user_id = $_SESSION['user_id'];
$pdo = Db::getInstance()->getConnection();
$stmt = $pdo->prepare("SELECT balance FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$user_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$current_balance = $row ? (float)$row['balance'] : 0;

if (!isset($_SESSION['balance']) || $_SESSION['balance'] !== $current_balance) {
    $_SESSION['balance'] = $current_balance;
}
if (!isset($_SESSION['last_bet_amount']) || !is_numeric($_SESSION['last_bet_amount'])) $_SESSION['last_bet_amount'] = 0;

// --- Bets initialization ---
if (!isset($_SESSION['bets']) || !is_array($_SESSION['bets'])) {
    $_SESSION['bets'] = [];
}
foreach ($animal_keys as $k) {
    if (!isset($_SESSION['bets'][$k])) {
        $_SESSION['bets'][$k] = 0;
    }
}

// --- Reset bets on new round ---
// FIX: Do NOT reset bets at round change, reset only after auto_spin payout
if (!isset($_SESSION['round_no']) || $_SESSION['round_no'] !== $round_no) {
    unset($_SESSION['payout_claimed']);
    $_SESSION['round_no'] = $round_no;
    // $_SESSION['bets'] ကို auto_spin ပြီးမှသာ reset လုပ်ပါ
}

// --- Slot animal selection: fetch or create in DB for this round ---
function get_or_create_slot_round($pdo, $animals, $current_round_no) {
    $stmt = $pdo->prepare("SELECT slot1, slot2, slot3, draw_time FROM slot_rounds WHERE round_no = ?");
    $stmt->execute([$current_round_no]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $animal_keys = array_column($animals, 'key');

    if ($row) {
        return [$row['slot1'], $row['slot2'], $row['slot3'], $row['draw_time']];
    } else {
        // Pick 3 unique random animals for this round
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
        return [$slot1, $slot2, $slot3, $draw_time];
    }
}

list($slot1_key, $slot2_key, $slot3_key, $draw_time) = get_or_create_slot_round($pdo, $animals, $round_index);
$slot_animals = [];
foreach([$slot1_key, $slot2_key, $slot3_key] as $slot_key) {
    foreach ($animals as $animal) {
        if ($animal['key'] === $slot_key) {
            $slot_animals[] = $animal;
            break;
        }
    }
}

// --- Calculate next round time ---
$now = time();
$next_draw_time = strtotime($draw_time);
$remaining = $next_draw_time - $now;
if ($remaining < 0) $remaining = 0;
$min = floor($remaining / 60);
$sec = $remaining % 60;

// --- Place bet API (POST) ---
// Now accept multiple animals and one amount for all
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bet_animals']) && isset($_POST['bet_amt'])) {
    $bet_animals = $_POST['bet_animals'];
    if (!is_array($bet_animals)) $bet_animals = explode(',', $bet_animals);
    $bet_amt = (int)$_POST['bet_amt'];
    $invalid = false;
    foreach ($bet_animals as $animal_key) {
        if (!in_array($animal_key, $animal_keys)) {
            $invalid = true;
            break;
        }
    }
    if ($invalid) {
        echo json_encode(['success' => false, 'msg' => 'Invalid animal.']);
        exit;
    }
    if ($bet_amt < 1) {
        echo json_encode(['success' => false, 'msg' => 'Bet amount too low.']);
        exit;
    }
    $total_bet = $bet_amt * count($bet_animals);
    if ($_SESSION['balance'] < $total_bet) {
        echo json_encode(['success' => false, 'msg' => 'လက်ကျန်ငွေ မလုံလောက်ပါ။']);
        exit;
    }
    $_SESSION['balance'] -= $total_bet;
    foreach ($bet_animals as $animal_key) {
        $_SESSION['bets'][$animal_key] += $bet_amt;
    }
    $_SESSION['last_bet_amount'] = $bet_amt;
    // Update DB
    $stmt = $pdo->prepare("UPDATE users SET balance = ? WHERE id = ?");
    $stmt->execute([$_SESSION['balance'], $user_id]);
    echo json_encode([
        'success' => true,
        'bets' => $_SESSION['bets'],
        'balance' => $_SESSION['balance']
    ]);
    exit;
}

// --- Get balance/bets API (AJAX poll) ---
if (isset($_GET['get_balance'])) {
    echo json_encode([
        'balance' => $_SESSION['balance'],
        'bets' => $_SESSION['bets'],
        'last_bet_amount' => $_SESSION['last_bet_amount']
    ]);
    exit;
}

// --- API: Auto spin slots & payout & round_no update ---
if (isset($_GET['auto_spin'])) {
    $slot_keys = [$slot1_key, $slot2_key, $slot3_key];
    $slot_wins = array_count_values($slot_keys);

    // BETS ကို auto_spin လုပ်ချိန်မှာ မပျက်စေရ
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
    $user_won = false;
    foreach ($user_bets as $animal => $bet_amt) {
        $bet_amt = (float)$bet_amt;
        $total_bet += $bet_amt;
        $count = isset($slot_wins[$animal]) ? (int)$slot_wins[$animal] : 0;
        if ($bet_amt > 0 && $count > 0) {
            $user_won = true;
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
        $_SESSION['payout_claimed'] !== $round_no
    ) {
        if ($payout > 0) {
            $stmt = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
            $stmt->execute([$payout, $user_id]);
            $_SESSION['balance'] += $payout;
        }
        $_SESSION['payout_claimed'] = $round_no;
    }
    // result message
    $result_text = "";
    if ($total_bet == 0) {
        $result_text = "ဒီပွဲအတွက် ဘာမှ မထိုးရသေးပါ။";
    } elseif ($user_won) {
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
                '<div class="result-line">%s (Bet %s x %s) = <b>%s</b> ကျပ်</div>',
                htmlspecialchars($animal_name),
                number_format($w['bet']),
                $w['multiplier'],
                number_format($w['pay'])
            );
        }
        $main = '<div class="result-main">အနိုင်!</div>';
        $main .= '<div class="result-win-total">စုစုပေါင်း အနိုင်ငွေ: <span class="balance-highlight">' . number_format($payout) . '</span> ကျပ်</div>';
        $result_text = $main . implode("\n", $lines);
    } else {
        $result_text = "<span style=\"color:#e74c3c\">အရှုံးပါ! နောက်တစ်ပတ်တိုးကြိုးစားပါ။</span>";
    }
    header("Content-Type: application/json; charset=UTF-8");
    echo json_encode([
        'slots' => $slot_keys,
        'bets' => $user_bets,
        'payout' => $payout,
        'won_animals' => $won_animals,
        'current_balance' => $_SESSION['balance'],
        'result_text' => $result_text,
        'round_no' => $round_no + 1,
        'slot1' => $slot1_key,
        'slot2' => $slot2_key,
        'slot3' => $slot3_key,
        'draw_time' => $draw_time
    ]);
    // *** auto_spin ပြီးမှသာ bets reset လုပ်ပါ ***
    $_SESSION['bets'] = [];
    foreach ($animal_keys as $k) {
        $_SESSION['bets'][$k] = 0;
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="my">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>ဂလုံးဂလုံးဂိမ်း</title>
<style>

body {
    font-family: "Myanmar Text", "Poppins", sans-serif;
    background: #f4f4f4;
    margin: 0;
    padding: 0;
}
.round-bar {
    max-width: 420px;
    margin: 0 auto 0 auto;
    display: flex;
    justify-content: flex-end;
    align-items: center;
    padding: 12px 10px 0 10px;
}
.round-no {
    font-size: 1.11em;
    color: #fffde7;
    font-weight: bold;
    background: #388e3c;
    padding: 5px 18px;
    border-radius: 18px;
    box-shadow: 0 1px 4px #388e3c33;
    letter-spacing: 1px;
}
.balance-bar {
    max-width: 420px;
    margin: 10px auto 0 auto;
    display: flex;
    justify-content: flex-end;
    align-items: center;
    padding: 0 10px;
}
.user-balance {
    font-size: 1.08em;
    color: #16a085;
    font-weight: bold;
    background: #e0fcf6;
    border-radius: 14px;
    padding: 6px 20px;
    margin-left: auto;
    letter-spacing: 0.5px;
    box-shadow: 0 1px 4px #b9ecec33;
}
.slot-row-container {
    display: flex;
    justify-content: center;
    margin-top: 28px;
}
.slot-row {
    display: flex;
    gap: 34px;
    background: #f6fff9;
    border-radius: 25px;
    box-shadow: 0 4px 16px #b2e1d2;
    padding: 36px 38px;
}
.slot {
    width: 140px;
    height: 140px;
    background: #fff;
    border-radius: 24px;
    box-shadow: 0 4px 22px #b2e1d299;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    font-size: 1.25em;
    margin: 0 4px;
    position: relative;
}
.slot img {
    width: 90px;
    height: 90px;
    display: block;
    margin-bottom: 12px;
}
.slot-name {
    color: #1976d2;
    font-size: 1.18em;
    font-weight: bold;
}
.bet-amt {
    display:block;
    font-size: 1em;
    font-weight: bold;
    color: #e67e22;
    margin-top: 5px;
}
.slot-timer {
    text-align: center;
    margin-top: 16px;
    margin-bottom: 12px;
    color: #16a085;
    font-size: 1.13em;
    font-weight: bold;
    letter-spacing: 0.2px;
}
.animal-grid-container {
    display: flex;
    justify-content: center;
    margin-top: 24px;
}
.animal-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    grid-template-rows: repeat(2, 1fr);
    gap: 16px;
    background: #ffffffde;
    padding: 24px 12px;
    border-radius: 18px;
    box-shadow: 0 2px 12px #e0e0e0;
    max-width: 420px;
    width: 94vw;
}
.animal-card {
    background: #fff;
    border-radius: 14px;
    padding: 12px 6px 8px 6px;
    box-shadow: 0 2px 8px #e0e0e0;
    text-align: center;
    width: 100%;
    min-width: 81px;
    min-height: 110px;
    transition: box-shadow 0.2s, background 0.2s;
    cursor: pointer;
    position: relative;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}
.animal-card.selected, .animal-card:hover, .animal-card:active {
    box-shadow: 0 4px 16px #b1f0e0;
    background: #e8fcf8;
}
.animal-card img {
    width: 54px;
    height: 54px;
    margin-bottom: 7px;
    border-radius: 12px;
    background: #f6f6f6;
    display: block;
    object-fit: contain;
}
.animal-name {
    font-size: 1.06em;
    color: #16a085;
    font-weight: bold;
    letter-spacing: 0.2px;
}
.bet-amt-animal {
    font-size: 1em;
    color: #e67e22;
    font-weight: bold;
    margin-top: 4px;
    min-height: 19px;
    display: block;
}
.bet-bar {
    margin: 20px auto 0 auto;
    max-width: 420px;
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 12px;
}
.bet-bar input[type=number] {
    font-size: 1.1em;
    padding: 6px 10px;
    border: 1px solid #b2e1d2;
    border-radius: 8px;
    width: 90px;
}
.bet-bar button {
    font-size: 1.1em;
    background: #16a085;
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 7px 18px;
    cursor: pointer;
    font-weight: 600;
}
.bet-bar button:disabled {
    background: #b7e2d3;
    color: #fff;
    cursor: not-allowed;
}
.icon-row {
    display: flex;
    justify-content: center;
    align-items: center;
    margin: 18px 0 0 0;
    gap: 16px;
    max-width: 320px;
    margin-left: auto;
    margin-right: auto;
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
    object-fit: contain;
    display: block;
}

/* Responsive adjustments for slot size */
@media (max-width: 900px) {
    .slot-row { gap: 15px; padding: 15px 2vw;}
    .slot { width: 85px; height: 85px;}
    .slot img { width: 48px; height: 48px; }
    .slot-name { font-size: 1em; }
}
@media (max-width: 600px) {
    .round-bar, .balance-bar { padding: 8px 7vw 0 7vw; }
    .slot-row { gap: 7px; padding: 8px 2vw;}
    .slot { width: 62px; height: 62px;}
    .slot img { width: 34px; height: 34px; }
    .slot-name { font-size: 0.95em; }
    .slot-timer { font-size: 0.93em;}
    .animal-grid { padding: 13px 2vw; gap: 8px; }
    .animal-card { min-width: 54px; min-height: 66px; padding: 7px 2px 5px 2px;}
    .animal-card img { width: 30px; height: 30px; margin-bottom: 3px;}
    .animal-name { font-size: 0.92em; }
    .icon-row { max-width: 220px; gap: 10px; }
    .icon-link { font-size: 0.98em; padding: 7px 0; }
    .icon-link img { width: 28px; height: 28px; }
}
@media (max-width: 400px) {
    .animal-grid { gap: 4px; padding: 7px 0.5vw;}
    .animal-card { min-width: 40px; min-height: 41px; }
    .icon-row { max-width: 95vw; }
}
.sticky-bet-bar {
    position: fixed;
    left: 0; right: 0; bottom: 0;
    background: #fff;
    box-shadow: 0 -2px 12px #cfd8dc66;
    padding: 13px 8vw 13px 8vw;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    gap: 12px;
}
.sticky-bet-bar .amount-group {
    display: flex;
    gap: 7px;
    flex-wrap: wrap;
    align-items: center;
}
.sticky-bet-bar .amount-btn {
    font-size: 1.05em;
    border-radius: 7px;
    padding: 7px 13px;
    border: 1px solid #b2e1d2;
    background: #f6f6f6;
    color: #16a085;
    font-weight: bold;
    cursor: pointer;
    transition: background 0.15s, color 0.15s, border 0.15s;
}
.sticky-bet-bar .amount-btn.selected,
.sticky-bet-bar .amount-btn:active {
    background: #16a085;
    color: #fff;
    border: 1.5px solid #16a085;
}
.sticky-bet-bar .bet-btn {
    font-size: 1.09em;
    background: #16a085;
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 7px 26px;
    cursor: pointer;
    font-weight: 600;
    margin-left: 8px;
}
.sticky-bet-bar .bet-btn:disabled {
    background: #b7e2d3;
    color: #fff;
    cursor: not-allowed;
}
.animal-card.selectable {
    border: 2px solid transparent;
    transition: border 0.15s;
}
.animal-card.selectable.selected {
    border: 2.5px solid #16a085;
    background: #d0faf0;
    box-shadow: 0 4px 16px #b1f0e0;
}
.result-main {
    font-size: 1.18em;
    font-weight: bold;
    color: #16a085;
    margin-bottom: 6px;
}
.result-win-total {
    font-size: 1.11em;
    color: #e67e22;
    margin-bottom: 4px;
}
.result-line {
    font-size: 1em;
    color: #888;
    margin-bottom: 2px;
}
</style>
</head>
<body>
    <div class="round-bar">
        <div class="round-no">ပွဲစဉ်နံပါတ်: <span id="round-no"><?php echo $round_no; ?></span></div>
    </div>
    <div class="balance-bar">
        <div class="user-balance">
            လက်ကျန်ငွေ: <span id="user-balance"><?php echo number_format($current_balance); ?></span> ကျပ်
        </div>
    </div>
    <div class="slot-row-container">
        <div class="slot-row" id="slot-row">
            <?php foreach ($slot_animals as $slot): ?>
                <div class="slot">
                    <img src="<?php echo htmlspecialchars($slot['photo']); ?>" alt="<?php echo htmlspecialchars($slot['name']); ?>">
                    <div class="slot-name"><?php echo htmlspecialchars($slot['name']); ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="slot-timer" id="slot-timer">
        <span style="color:#1976d2;font-weight:bold;">ပေါက်ကောင် ဖွင့်ရန် ကျန်ရှိချိန် - </span>
        <span id="timer-tick"><?php printf("%02d:%02d", $min, $sec); ?></span>
    </div>
    <div class="icon-row">
        <a href="bet-history.php" class="icon-link" title="Bet History">
            <img src="/images/history.png" alt="History" />
            ထိုးမှတ်တမ်း
        </a>
        <a href="animals-winner.php" class="icon-link" title="Animals Winner">
            <img src="/images/trophy.png" alt="Winners" />
            Winners
        </a>
    </div>
    <div class="animal-grid-container">
        <div class="animal-grid">
            <?php foreach ($animals as $animal): ?>
                <div class="animal-card selectable" tabindex="0" data-animal="<?php echo $animal['key']; ?>">
                    <img src="<?php echo htmlspecialchars($animal['photo']); ?>"
                         alt="<?php echo htmlspecialchars($animal['name']); ?>">
                    <div class="animal-name"><?php echo htmlspecialchars($animal['name']); ?></div>
                    <span class="bet-amt-animal" id="bet-amt-<?php echo $animal['key']; ?>">
                    <?php echo isset($_SESSION['bets'][$animal['key']]) && $_SESSION['bets'][$animal['key']] > 0 ? number_format($_SESSION['bets'][$animal['key']]) : ''; ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div id="bet-msg" style="text-align:center;color:#e67e22;min-height:24px;margin-top:12px;font-weight:bold;"></div>
    <!-- Sticky bet bar -->
    <div class="sticky-bet-bar">
        <div class="amount-group" id="bet-amount-group">
            <?php foreach([100,200,300,500,1000,2000,3000,5000,10000] as $amt): ?>
                <button type="button" class="amount-btn" data-amt="<?php echo $amt; ?>"><?php echo number_format($amt); ?></button>
            <?php endforeach; ?>
        </div>
        <button id="place-bet-btn" class="bet-btn" disabled>Bet တင်မည်</button>
    </div>
    <script>
    // ----------- MULTI ANIMAL, AMOUNT BUTTONS, STICKY BAR LOGIC -----------
    let selectedAnimals = [];
    let selectedAmt = null;
    let betMsg = document.getElementById('bet-msg');
    let betBtns = document.querySelectorAll('.animal-card.selectable');
    let amountBtns = document.querySelectorAll('.amount-btn');
    let placeBetBtn = document.getElementById('place-bet-btn');
    let balance = <?php echo (int)$current_balance; ?>;

    // Animal selection (multi)
    betBtns.forEach(el=>{
        el.onclick = function() {
            const key = this.dataset.animal;
            if (selectedAnimals.includes(key)) {
                selectedAnimals = selectedAnimals.filter(v=>v!==key);
                this.classList.remove('selected');
            } else {
                selectedAnimals.push(key);
                this.classList.add('selected');
            }
            updateBetBtnState();
            betMsg.textContent = "";
        }
    });

    // Amount selection
    amountBtns.forEach(el=>{
        el.onclick = function() {
            amountBtns.forEach(b=>b.classList.remove('selected'));
            this.classList.add('selected');
            selectedAmt = parseInt(this.dataset.amt);
            updateBetBtnState();
            betMsg.textContent = "";
        }
    });

    function updateBetBtnState() {
        placeBetBtn.disabled = !(selectedAnimals.length && selectedAmt);
    }

    // Bet placing
    placeBetBtn.onclick = function() {
        if (!selectedAnimals.length) {
            betMsg.textContent = "ထိုးမည့် တိရစ္ဆာန် (အနည်းဆုံး ၁ ခု) ရွေးပါ။";
            return;
        }
        if (!selectedAmt) {
            betMsg.textContent = "Bet ငွေ ရွေးပါ။";
            return;
        }
        let totalBet = selectedAmt * selectedAnimals.length;
        if (totalBet > balance) {
            betMsg.textContent = "လက်ကျန်ငွေ မလုံလောက်ပါ။";
            return;
        }
        placeBetBtn.disabled = true;
        fetch(window.location.pathname, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'bet_animals=' + encodeURIComponent(selectedAnimals.join(',')) + '&bet_amt=' + encodeURIComponent(selectedAmt)
        })
        .then(r=>r.json())
        .then(res=>{
            if (res.success) {
                betMsg.textContent = "Bet တင်ပြီးပါပြီ!";
                balance = res.balance;
                document.getElementById('user-balance').textContent = Number(balance).toLocaleString();
                for (const k in res.bets) {
                    document.getElementById('bet-amt-'+k).textContent = res.bets[k] ? Number(res.bets[k]).toLocaleString() : '';
                }
                // reset selections
                selectedAnimals = [];
                betBtns.forEach(b=>b.classList.remove('selected'));
                amountBtns.forEach(b=>b.classList.remove('selected'));
                selectedAmt = null;
                placeBetBtn.disabled = true;
            } else {
                betMsg.textContent = res.msg || "Bet မအောင်မြင်ပါ။";
                placeBetBtn.disabled = false;
            }
        })
        .catch(()=>{ betMsg.textContent = "ပြဿနာတစ်ခုရှိနေသည်။"; placeBetBtn.disabled = false; });
    };

    // Timer
    let remaining = <?php echo (int)$remaining; ?>;
    function pad(n) { return n < 10 ? "0" + n : n; }
    function updateTimer() {
        if (remaining <= 0) {
            document.getElementById('timer-tick').textContent = "00:00";
            fetch(window.location.pathname + '?auto_spin=1')
                .then(r => r.json())
                .then(data => {
                    showPayoutResult(data);
                    setTimeout(() => { 
                        clearBetMsg();
                        location.reload(); 
                    }, 2000);
                });
            return;
        }
        let min = Math.floor(remaining / 60);
        let sec = remaining % 60;
        document.getElementById('timer-tick').textContent = pad(min) + ":" + pad(sec);
        remaining--;
    }
    setInterval(updateTimer, 1000);

    // Show result
    function showPayoutResult(data) {
        document.getElementById('bet-msg').innerHTML = data.result_text;
        document.getElementById('user-balance').textContent = Number(data.current_balance).toLocaleString();
        for (const k in data.bets) {
            document.getElementById('bet-amt-'+k).textContent = '';
        }
    }

    function clearBetMsg() {
        document.getElementById('bet-msg').innerHTML = '';
    }

    // Balance Poll
    setInterval(function(){
        fetch(window.location.pathname + '?get_balance=1')
        .then(r=>r.json())
        .then(res=>{
            if (typeof res.balance !== "undefined") {
                document.getElementById('user-balance').textContent = Number(res.balance).toLocaleString();
                for (const k in res.bets) {
                    document.getElementById('bet-amt-'+k).textContent = res.bets[k] ? Number(res.bets[k]).toLocaleString() : '';
                }
            }
        });
    }, 7000);
    </script>
</body>
</html>