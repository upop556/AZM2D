<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session and include DB
session_start();
require_once '../db.php';

// User authentication
$current_user_id = $_SESSION['user_id'] ?? null;
if (!$current_user_id) {
    header('Location: /login.php');
    exit;
}

$pdo = Db::getInstance()->getConnection();

// --- Date Logic ---
$now = new DateTime("now", new DateTimeZone('Asia/Yangon'));
$first_of_month = (new DateTime('first day of this month', new DateTimeZone('Asia/Yangon')))->setTime(0, 0, 0);
$sixteenth_of_month = (new DateTime('first day of this month', new DateTimeZone('Asia/Yangon')))->setDate(date('Y'), date('m'), 16)->setTime(0, 0, 0);

// Determine the betting window (e.g., closes at 12:00 PM on bet day)
$bet_closes_on_1st = (clone $first_of_month)->setTime(12, 0, 0);
$bet_closes_on_16th = (clone $sixteenth_of_month)->setTime(12, 0, 0);

if ($now < $bet_closes_on_1st) {
    $bet_date_formatted = $bet_closes_on_1st->format('Y-m-d');
} elseif ($now < $bet_closes_on_16th) {
    $bet_date_formatted = $bet_closes_on_16th->format('Y-m-d');
} else {
    $bet_date_formatted = (new DateTime('first day of next month', new DateTimeZone('Asia/Yangon')))->format('Y-m-d');
}

// Get head digit for pagination
$head_digit = isset($_GET['head']) ? (int)$_GET['head'] : 0;
if ($head_digit < 0 || $head_digit > 9) {
    $head_digit = 0;
}

// Fetch all necessary data
$brakes = [];
$stmt_brakes = $pdo->query('SELECT number, brake_amount FROM d_3d_brakes');
while ($row_brakes = $stmt_brakes->fetch(PDO::FETCH_ASSOC)) {
    $brakes[$row_brakes['number']] = (float)$row_brakes['brake_amount'];
}

$current_totals = [];
$stmt_current = $pdo->prepare('SELECT number, SUM(amount) as total_bet FROM lottery_bets WHERE bet_date = :bet_date GROUP BY number');
$stmt_current->execute([':bet_date' => $bet_date_formatted]);
while ($row_current = $stmt_current->fetch(PDO::FETCH_ASSOC)) {
    $current_totals[$row_current['number']] = (float)$row_current['total_bet'];
}

// Format date for display
$display_date_obj = new DateTime($bet_date_formatted);
$display_date = $display_date_obj->format('F j, Y');

// Process bet form submission
$message = '';
$messageType = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_bet'])) {
    $selected_numbers = $_POST['bet_numbers'] ?? [];
    $bet_amount = (float)($_POST['bet_amount'] ?? 0);

    if (empty($selected_numbers)) {
        $message = 'ဂဏန်းအနည်းဆုံးတစ်ခု ရွေးချယ်ပါ။';
        $messageType = 'error';
    } elseif ($bet_amount < 100) {
        $message = 'အနည်းဆုံး ၁၀၀ ကျပ် ထိုးရပါမည်။';
        $messageType = 'error';
    } else {
        $pdo->beginTransaction();
        try {
            // Check user balance
            $total_this_bet = $bet_amount * count($selected_numbers);

            $stmt_user = $pdo->prepare('SELECT balance FROM users WHERE id = :uid FOR UPDATE');
            $stmt_user->execute([':uid' => $current_user_id]);
            $user = $stmt_user->fetch(PDO::FETCH_ASSOC);

            if (!$user || $user['balance'] < $total_this_bet) {
                throw new Exception('လက်ကျန်ငွေ မလုံလောက်ပါ။');
            }

            // Check individual brakes again within the transaction
            foreach ($selected_numbers as $number) {
                $brake_limit = $brakes[$number] ?? -1;
                if ($brake_limit != -1) {
                    $stmt_num_total = $pdo->prepare('SELECT SUM(amount) FROM lottery_bets WHERE bet_date = :bet_date AND number = :number FOR UPDATE');
                    $stmt_num_total->execute([':bet_date' => $bet_date_formatted, ':number' => $number]);
                    $current_bet_total_for_num = (float)($stmt_num_total->fetchColumn() ?? 0);

                    if (($current_bet_total_for_num + $bet_amount) > $brake_limit) {
                        throw new Exception("ကွက်နံပါတ် '$number' သည် ဘရိတ်ပြည့်သွားပြီဖြစ်သည်။");
                    }
                }
            }

            $new_balance = $user['balance'] - $total_this_bet;
            $update_stmt = $pdo->prepare('UPDATE users SET balance = :balance WHERE id = :uid');
            $update_stmt->execute([':balance' => $new_balance, ':uid' => $current_user_id]);

            $insert_stmt = $pdo->prepare('INSERT INTO lottery_bets (user_id, bet_type, number, amount, bet_date, created_at) VALUES (:user_id, :bet_type, :number, :amount, :bet_date, NOW())');
            foreach ($selected_numbers as $number) {
                $insert_stmt->execute([':user_id' => $current_user_id, ':bet_type' => '3D', ':number' => $number, ':amount' => $bet_amount, ':bet_date' => $bet_date_formatted]);
            }

            $pdo->commit();
            header("Location: " . $_SERVER['PHP_SELF'] . "?head=$head_digit&success=1");
            exit();

        } catch (Exception $e) {
            $pdo->rollBack();
            $message = $e->getMessage();
            $messageType = 'error';
        }
    }
}

if(isset($_GET['success'])) {
    $message = 'ထိုးပြီးပါပြီ။ သင်၏ ၃လုံးထိုးမှတ်တမ်းကို အောင်မြင်စွာ သိမ်းဆည်းပြီးပါပြီ။';
    $messageType = 'success';
}

$user_balance = getUserBalance($current_user_id, $pdo);
function getUserBalance($user_id, $pdo) {
    $stmt = $pdo->prepare('SELECT balance FROM users WHERE id = :uid');
    $stmt->execute([':uid' => $user_id]);
    $row = $stmt->fetch();
    return $row ? (float)$row['balance'] : 0;
}
?>
<!DOCTYPE html>
<html lang="my">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>3D ထီထိုးစနစ်</title>
    <style>
        :root { --primary: #3498db; --secondary: #2ecc71; --danger: #e74c3c; --warning: #f39c12; --dark: #34495e; --light: #ecf0f1; --info: #95a5a6; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; line-height: 1.6; margin: 0; padding: 20px; background-color: #f4f7f6; padding-bottom: 80px; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #eee; }
        .back-button { display: inline-block; padding: 8px 16px; background-color: var(--primary); color: white; text-decoration: none; border-radius: 5px; font-weight: bold; }
        h1 { color: var(--dark); font-size: 1.5em; text-align: center; margin: 0 0 20px 0; }
        .balance-display { background-color: var(--light); padding: 10px 15px; border-radius: 8px; text-align: center; margin-bottom: 20px; border: 1px solid #ddd; }
        .balance-amount { font-weight: bold; color: var(--secondary); font-size: 1.2em; }
        .date-info { background-color: var(--warning); color: white; padding: 15px; border-radius: 8px; text-align: center; margin-bottom: 20px; font-weight: bold; font-size: 1.1em; }
        .bet-type-badge { display: inline-block; background: #e74c3c; color: white; padding: 5px 12px; border-radius: 20px; margin-top: 8px; font-size: 0.9em; }
        .numbers-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 8px; margin-bottom: 20px; }
        .number-btn { display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 5px; background-color: #fff; border: 1px solid #ddd; border-radius: 5px; cursor: pointer; user-select: none; transition: all 0.2s; position: relative; }
        .number-display { font-size: 1.1em; font-weight: 500; }
        .number-btn.selected { background-color: var(--primary); color: white; border-color: var(--primary); }
        .number-btn.disabled { background-color: #bdc3c7; color: #7f8c8d; cursor: not-allowed; border-color: #bdc3c7; }
        .brake-progress-bar { width: 85%; height: 5px; background-color: #e9ecef; border-radius: 3px; margin-top: 4px; overflow: hidden; }
        .brake-progress-fill { height: 100%; border-radius: 3px; transition: width 0.3s ease-in-out; }
        .brake-progress-fill.fill-low { background-color: var(--secondary); }
        .brake-progress-fill.fill-medium { background-color: var(--warning); }
        .brake-progress-fill.fill-high { background-color: var(--danger); }
        .btn { display: inline-block; padding: 12px 20px; background-color: var(--primary); color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 1em; font-weight: bold; width: 100%; }
        .btn:disabled { background-color: #95a5a6; cursor: not-allowed; }
        .message { padding: 12px; border-radius: 5px; margin-bottom: 20px; text-align: center; }
        .message.error { background-color: #fbeae5; color: var(--danger); border: 1px solid #f7c5bc; }
        .message.success { background-color: #e6f7ee; color: var(--secondary); border: 1px solid #c8ebd7; }
        .selection-summary { background-color: var(--light); padding: 15px; border-radius: 8px; margin: 15px 0; }
        .summary-title { font-weight: bold; margin-bottom: 10px; color: var(--dark); }
        .selected-numbers { display: flex; flex-wrap: wrap; gap: 8px; }
        .selected-number { background-color: var(--primary); color: white; padding: 5px 10px; border-radius: 5px; font-weight: 500; }
        .total-amount { text-align: right; margin-top: 10px; font-weight: bold; }
        .quick-select { display: flex; justify-content: flex-end; margin-bottom: 15px; gap: 10px; }
        .quick-btn { padding: 8px 15px; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 1em; font-weight: bold; text-align: center; }
        .refresh-btn { background-color: var(--danger); }
        .clear-btn { background-color: var(--info); }
        .bet-section { display: flex; flex-direction: column; margin-top: 20px; border-top: 1px solid #eee; padding-top: 15px; }
        .bet-amount-input { display: flex; align-items: center; margin-bottom: 15px; }
        .bet-amount-input label { flex: 1; font-weight: bold; }
        .bet-amount-input input { width: 150px; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        .pagination { display: flex; justify-content: center; flex-wrap: wrap; gap: 5px; margin-bottom: 20px; }
        .pagination a { padding: 8px 12px; text-decoration: none; color: var(--primary); border: 1px solid var(--primary); border-radius: 5px; font-weight: bold; }
        .pagination a.active { background-color: var(--primary); color: white; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="/c/thai-lotto-results.php" class="back-button" id="backButton">နောက်သို့</a>
            <div>3D ထီထိုးစနစ်</div>
        </div>
        <div class="balance-display">
            လက်ကျန်ငွေ: <span class="balance-amount"><?= number_format($user_balance, 2) ?> ကျပ်</span>
        </div>
        <div class="date-info">
            ထိုးမည့်ရက်: <?= htmlspecialchars($display_date) ?>
            <div class="bet-type-badge"><?= date('j', strtotime($bet_date_formatted)) == 1 ? 'လစဉ် (၁) ရက်နေ့' : 'လစဉ် (၁၆) ရက်နေ့' ?></div>
        </div>
        <?php if ($message): ?>
        <div class="message <?= htmlspecialchars($messageType) ?>">
            <?= htmlspecialchars($message) ?>
        </div>
        <?php endif; ?>
        
        <form method="post" id="betForm" action="?head=<?= $head_digit ?>">
            <div id="selectionSummary" class="selection-summary" style="display: none;">
                <div class="summary-title">ရွေးချယ်ထားသော ဂဏန်းများ:</div>
                <div class="selected-numbers" id="selectedNumbersDisplay"></div>
                <div class="total-amount">စုစုပေါင်းထိုးငွေ: <span id="totalAmount">0</span> ကျပ်</div>
            </div>
            <div class="bet-section">
                <div class="bet-amount-input">
                    <label for="bet_amount">တစ်ကွက်လျှင် ထိုးငွေ (အနည်းဆုံး 100 ကျပ်):</label>
                    <input type="number" id="bet_amount" name="bet_amount" min="100" value="100" required>
                </div>
                <button type="submit" name="submit_bet" class="btn">
                    ထိုးမည်
                </button>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px;">
                <h1><?= $head_digit ?> ထိပ်စီး</h1>
                <div class="quick-select">
                    <div class="quick-btn clear-btn" id="clearBtn" title="ရွေးထားသောကွက်များအားလုံးကို ရှင်းလင်းရန်">ရှင်းလင်းမည်</div>
                    <div class="quick-btn refresh-btn" id="refreshBtn" title="ရွေးထားသောကွက်များကို ဗြောင်းပြန်လှန်ရန်">R</div>
                </div>
            </div>
            <div class="pagination">
                <?php for ($i = 0; $i <= 9; $i++): ?>
                    <a href="?head=<?= $i ?>" class="<?= $head_digit == $i ? 'active' : '' ?>"><?= $i ?> ထိပ်</a>
                <?php endfor; ?>
            </div>
            <div class="numbers-grid" id="numbersGrid">
                <?php 
                $start_number = $head_digit * 100;
                $end_number = $start_number + 99;

                for ($i = $start_number; $i <= $end_number; $i++): 
                    $num_str = sprintf('%03d', $i);
                    $brake_limit = $brakes[$num_str] ?? -1;
                    $current_total = $current_totals[$num_str] ?? 0;
                    $is_individual_brake_full = ($brake_limit != -1 && $current_total >= $brake_limit);
                    $is_disabled = $is_individual_brake_full;
                    $class = $is_disabled ? 'disabled' : '';

                    $progress_bar_html = '';
                    if ($brake_limit > 0) {
                        $percentage = ($current_total / $brake_limit) * 100;
                        if ($percentage > 100) $percentage = 100;

                        $fill_class = 'fill-low';
                        if ($percentage >= 90) {
                            $fill_class = 'fill-high';
                        } elseif ($percentage >= 50) {
                            $fill_class = 'fill-medium';
                        }

                        $progress_bar_html = "
                            <div class='brake-progress-bar' title='ဘရိတ်: " . number_format($brake_limit) . " | လက်ရှိ: " . number_format($current_total) . "'>
                                <div class='brake-progress-fill " . $fill_class . "' style='width: " . $percentage . "%;'></div>
                            </div>
                        ";
                    }
                ?>
                    <label class="number-btn <?= $class ?>" for="num-<?= $i ?>" data-number="<?= $num_str ?>">
                        <div class="number-display"><?= htmlspecialchars($num_str) ?></div>
                        <?= $progress_bar_html ?>
                        <input type="checkbox" value="<?= htmlspecialchars($num_str) ?>" id="num-<?= $i ?>" style="display: none;" <?= $is_disabled ? 'disabled' : '' ?>>
                    </label>
                <?php endfor; ?>
            </div>
        </form>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const numberButtons = document.querySelectorAll('.number-btn');
            const betAmountInput = document.getElementById('bet_amount');
            const selectionSummary = document.getElementById('selectionSummary');
            const selectedNumbersDisplay = document.getElementById('selectedNumbersDisplay');
            const totalAmountDisplay = document.getElementById('totalAmount');
            const refreshBtn = document.getElementById('refreshBtn');
            const clearBtn = document.getElementById('clearBtn');
            const paginationLinks = document.querySelectorAll('.pagination a');
            const backButton = document.getElementById('backButton');
            
            let selections = JSON.parse(sessionStorage.getItem('selections')) || {};
            
            function saveSelections() { 
                sessionStorage.setItem('selections', JSON.stringify(selections)); 
            }
            
            function restoreSelections() {
                numberButtons.forEach(button => {
                    if (button.classList.contains('disabled')) return;
                    const checkbox = button.querySelector('input[type="checkbox"]');
                    if (selections[checkbox.value]) {
                        checkbox.checked = true;
                        button.classList.add('selected');
                    }
                });
            }
            
            function getPermutations(numberStr) {
                if (numberStr.length !== 3) return [numberStr];
                let digits = numberStr.split('');
                let permutations = [];
                for (let i = 0; i < 3; i++) {
                    for (let j = 0; j < 3; j++) {
                        if (j !== i) {
                            for (let k = 0; k < 3; k++) {
                                if (k !== i && k !== j) {
                                    permutations.push(digits[i] + digits[j] + digits[k]);
                                }
                            }
                        }
                    }
                }
                return [...new Set(permutations)];
            }
            
            function updateSelectionSummary() {
                const selectedCount = Object.keys(selections).length;
                const betAmount = parseInt(betAmountInput.value) || 0;
                if (selectedCount > 0) {
                    selectionSummary.style.display = 'block';
                    selectedNumbersDisplay.innerHTML = '';
                    const sortedNumbers = Object.keys(selections).sort();
                    sortedNumbers.forEach(number => {
                        const numberSpan = document.createElement('span');
                        numberSpan.className = 'selected-number';
                        numberSpan.textContent = number;
                        selectedNumbersDisplay.appendChild(numberSpan);
                    });
                    totalAmountDisplay.textContent = (selectedCount * betAmount).toLocaleString() + ' ကျပ်';
                } else {
                    selectionSummary.style.display = 'none';
                }
            }
            
            numberButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (this.classList.contains('disabled')) return;
                    const checkbox = this.querySelector('input[type="checkbox"]');
                    checkbox.checked = !checkbox.checked;
                    this.classList.toggle('selected', checkbox.checked);
                    
                    if (checkbox.checked) {
                        selections[checkbox.value] = true;
                    } else {
                        delete selections[checkbox.value];
                    }
                    
                    saveSelections();
                    updateSelectionSummary();
                });
            });
            
            betAmountInput.addEventListener('input', updateSelectionSummary);
            
            refreshBtn.addEventListener('click', function() {
                let currentSelected = Object.keys(selections);
                let allPermutations = new Set();
                currentSelected.forEach(num => {
                    let perms = getPermutations(num);
                    perms.forEach(perm => allPermutations.add(perm));
                });
                selections = {};
                allPermutations.forEach(perm => {
                    const btn = document.querySelector(`.number-btn[data-number="${perm}"]`);
                    if (btn && !btn.classList.contains('disabled')) {
                        selections[perm] = true;
                    }
                });
                saveSelections();
                numberButtons.forEach(button => {
                    const checkbox = button.querySelector('input[type="checkbox"]');
                    if (selections[checkbox.value]) {
                        button.classList.add('selected');
                        checkbox.checked = true;
                    } else {
                        button.classList.remove('selected');
                        checkbox.checked = false;
                    }
                });
                updateSelectionSummary();
            });
            
            clearBtn.addEventListener('click', function() {
                selections = {};
                saveSelections();
                numberButtons.forEach(button => {
                    const checkbox = button.querySelector('input[type="checkbox"]');
                    if (checkbox.checked) {
                        checkbox.checked = false;
                        button.classList.remove('selected');
                    }
                });
                updateSelectionSummary();
            });
            
            if (backButton) {
                backButton.addEventListener('click', function(e) {
                    sessionStorage.removeItem('selections');
                });
            }
            
            paginationLinks.forEach(link => {
                link.addEventListener('click', function() {
                    saveSelections();
                });
            });
            
            restoreSelections();
            updateSelectionSummary();
            
            document.getElementById('betForm').addEventListener('submit', function(e) {
                this.querySelectorAll('input[type="hidden"]').forEach(el => el.remove());
                
                for (const number in selections) {
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'bet_numbers[]';
                    hiddenInput.value = number;
                    this.appendChild(hiddenInput);
                }
                
                if (Object.keys(selections).length === 0) {
                    e.preventDefault();
                    alert('ကျေးဇူးပြု၍ အနည်းဆုံး ဂဏန်းတစ်ခု ရွေးချယ်ပါ။');
                }
            });
            
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('success')) {
                sessionStorage.removeItem('selections');
            }
        });
    </script>
</body>
</html>