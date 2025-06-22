<?php  
// Get the time parameter from URL
$time = $_GET['time'] ?? '11:00:00';

// Validate time is one of the allowed values
$allowed_times = ['11:00:00', '12:01:00', '15:00:00', '16:30:00'];
if (!in_array($time, $allowed_times)) {
    $time = '11:00:00'; // Default to first time slot if invalid
}

// Format time for display
$time_formats = [
    '11:00:00' => '11:00 AM',
    '12:01:00' => '12:01 PM',
    '15:00:00' => '03:00 PM',
    '16:30:00' => '04:30 PM'
];
$display_time = $time_formats[$time];

// Check if this time slot is already closed
$current_time = date('H:i:s');
$is_time_closed = $time <= $current_time;

// Get return page (defaults to index)
$returnPage = $_GET['return'] ?? 'index';

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

// Simulate bet percentages (in a real app, fetch from database)
// This would be replaced with actual data from your database
function getBetPercentages() {
    $percentages = [];
    for ($i = 0; $i < 100; $i++) {
        $num = str_pad($i, 2, '0', STR_PAD_LEFT);
        
        // Randomly assign bet percentages for demonstration
        if ($i % 23 === 0) {
            $percentages[$num] = 100; // Some numbers are fully bet (100%)
        } elseif ($i % 11 === 0) {
            $percentages[$num] = rand(90, 99); // Some are almost full (90-99%)
        } elseif ($i % 7 === 0) {
            $percentages[$num] = rand(50, 89); // Medium betting (50-89%)
        } else {
            $percentages[$num] = rand(1, 49); // Light betting (1-49%)
        }
    }
    
    // Set specific examples to match screenshot
    $percentages['22'] = 95; // Orange bar in screenshot
    $percentages['09'] = 60;
    $percentages['15'] = 60;
    $percentages['19'] = 60;
    $percentages['41'] = 60;
    $percentages['16'] = 80;
    
    return $percentages;
}

$betPercentages = getBetPercentages();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>2D ထိုးရန် - <?= htmlspecialchars($display_time) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #3e2d66; margin: 0; padding: 0; color: #fff; }
        .container { max-width: 340px; margin: 18px auto 0 auto; background: #3e2d66; border-radius: 18px; padding: 18px 8px 18px 8px; }
        h2 { text-align:center; margin-top:6px; margin-bottom:10px; font-size: 1.2em; letter-spacing: 1px; color: #fff; }
        .time-display { text-align:center; color: #ffc107; font-weight: bold; margin: 4px 0 10px 0; font-size: 1.1em; }
        .warning { background:#ffe0e0; color:#c00; padding:8px; text-align:center; margin:0 auto 12px auto; border-radius:8px; max-width:90%; font-weight:bold; }
        .back-btn { display: inline-block; margin-bottom: 15px; color: #fff; text-decoration: none; padding: 6px 12px; border-radius: 4px; transition: background 0.2s; }
        .back-btn:hover { background: rgba(255,255,255,0.1); }
        .back-btn i { margin-right: 5px; }
        .balance-bar { margin: 0 auto 14px auto; max-width: 280px; display: flex; justify-content: flex-end; align-items: center; background: none; border-radius: 0; border: none; padding: 0; font-size: 1.03em; }
        .balance-text { color: #ffc107; font-weight: bold; letter-spacing: 0.02em; }
        .fastpick-bar { margin: 0 auto 10px auto; max-width: 300px; display: flex; justify-content: space-between; align-items: center; gap: 8px; flex-wrap: wrap; }
        .fastpick-btn { min-width: 70px; padding: 5px 14px; border: none; border-radius: 8px; background: #e0e7ef; color: #244; font-size: 1em; font-weight: bold; cursor: pointer; transition: background 0.13s, color 0.13s; }
        .fastpick-btn.selected, .fastpick-btn:active { background: #4da8ff; color: #fff; }
        .bet-controls { display: flex; align-items: center; gap: 8px; }
        .amount-input { font-size: 1.02em; width: 70px; padding: 5px 8px; border-radius: 6px; border: 1px solid #b7b7b7; outline: none; background: #f2f5fa; }
        .amount-input:focus { border-color: #4da8ff; background: #fff; }
        .bet-btn { background: linear-gradient(to right, #ff9a44, #ff5e62); color: #fff; border: none; border-radius: 25px; font-size: 1em; font-weight: bold; padding: 10px 20px; cursor: pointer; transition: background 0.13s; width: 100%; max-width: 200px; margin: 0 auto; display: block; }
        .bet-btn:active, .bet-btn:hover { background: linear-gradient(to right, #ff7f00, #e94a36); }
        
        /* New legend box styling - modeled after screenshot */
        .legend-box { margin: 25px auto 25px auto; background: #fff; border-radius: 15px; padding: 15px; max-width: 300px; font-size: 1em; color: #333; }
        .legend-title { text-align: center; font-weight: bold; margin-bottom: 15px; color: #333; }
        .legend-item { display: flex; align-items: center; margin-bottom: 10px; }
        .legend-color { width: 25px; height: 15px; border-radius: 3px; margin-right: 10px; }
        .legend-low { background: linear-gradient(to right, #4CAF50, #8BC34A); }
        .legend-medium { background: linear-gradient(to right, #FFEB3B, #FFC107); }
        .legend-high { background: linear-gradient(to right, #FF9800, #FF5722); }
        .legend-full { background: #444; }
        .legend-text { font-size: 0.9em; }
        
        .control-panel {
            background: #fff;
            border-radius: 15px;
            padding: 15px;
            margin-bottom: 20px;
            color: #333;
        }
        
        .panel-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .panel-label {
            font-weight: bold;
            color: #333;
        }
        
        .panel-time {
            color: #ffc107;
            font-size: 1.2em;
            font-weight: bold;
        }
        
        .panel-wallet {
            display: flex;
            align-items: center;
        }
        
        .wallet-icon {
            background: linear-gradient(to right, #ff9a44, #ff5e62);
            padding: 8px 12px;
            border-radius: 10px;
            margin-right: 10px;
            color: white;
        }
        
        .popup-mask { position: fixed; left:0; top:0; right:0; bottom:0; background: rgba(0,0,0,0.5); z-index: 9999; display: none; }
        .popup-dialog { position: fixed; left: 0; right: 0; top: 60px; margin: auto; background: #fff; border-radius: 14px; box-shadow: 0 6px 36px rgba(60,60,80,0.13); max-width: 340px; width: 98vw; z-index: 10000; padding: 22px 10px 14px 10px; display: none; }
        .popup-dialog h3 { margin-top: 0; margin-bottom: 12px; font-size: 1.09em; color: #274472; letter-spacing: 0.02em; }
        .popup-dialog .popup-content { font-size: 1.05em; margin-bottom: 12px; word-break: break-all; }
        .quickpick-row { display: flex; gap: 7px; justify-content: center; margin-bottom: 10px; margin-top: -4px; flex-wrap: wrap; }
        .quickpick-btn { padding: 3px 9px; border-radius: 7px; border: none; background: #e0e7ef; color: #23395d; font-weight: bold; cursor: pointer; transition: background 0.13s, color 0.13s; font-size: 0.97em; }
        .quickpick-btn:hover, .quickpick-btn:active { background: #4da8ff; color: #fff; }

        .quickpick-section-label { font-weight:bold; font-size:1em; margin: 14px 0 2px 0; display:block; }
        .quickpick-inline-row { display: flex; gap: 7px; justify-content: center; margin-bottom: 7px; flex-wrap: wrap; }

        .quickpick-btn.selected { background: #4da8ff; color: #fff; }

        .top-pick-row, .bottom-pick-row { display: flex; gap: 6px; justify-content: center; margin-bottom: 12px; flex-wrap: wrap;}
        .top-pick-btn, .bottom-pick-btn { min-width: 27px; padding: 4px 0; font-size: 1.08em; font-weight: bold; background: #f1f6ff; color: #23395d; border: 1px solid #bcd2f7; border-radius: 7px; cursor: pointer; transition: background 0.13s, color 0.13s, border-color 0.13s; }
        .top-pick-btn.selected, .top-pick-btn:active,
        .bottom-pick-btn.selected, .bottom-pick-btn:active { background: #4da8ff; color: #fff; border-color: #2666a3; }
        .popup-dialog .popup-close { display: inline-block; margin-top: 6px; padding: 4px 18px; background: #eee; color: #222; border: none; border-radius: 7px; font-size: 1em; font-weight: bold; cursor: pointer; transition: background 0.13s; }
        .popup-dialog .popup-close:hover, .popup-dialog .popup-close:active { background: #e0e7ef; }
        .popup-actions-row { display: flex; gap: 10px; justify-content: center; margin-top: 3px; margin-bottom: 8px; }
        .popup-action-btn { padding: 3px 14px; border-radius: 7px; border: none; background: #e0e7ef; color: #23395d; font-weight: bold; cursor: pointer; transition: background 0.13s, color 0.13s; font-size: 1.02em; }
        .popup-action-btn:hover, .popup-action-btn:active { background: #4da8ff; color: #fff; }
        .bet-list-table { width: 100%; border-collapse: separate; border-spacing: 0 7px; margin-bottom: 18px;}
        .bet-list-table th, .bet-list-table td { text-align: center; font-size: 1.04em; padding: 5px 5px; }
        .bet-list-table th { font-weight: bold; color: #274472; background: #f6faff;}
        .bet-list-table td .edit-amt { width: 60px; font-size:1em; border: 1px solid #b5c7e5; border-radius: 4px; padding: 2px 5px; }
        .bet-list-table td .del-btn { color: #bb2222; background: #fff0f0; border: 1px solid #fdc0c0; border-radius: 5px; padding: 2px 8px; font-size: 0.98em; cursor: pointer;}
        .bet-list-table td .del-btn:hover { background: #ffecec; }
        .bet-summary-row { font-weight:bold; background: #f8f8f8;}
        
        /* New number grid styling */
        .numbers-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 10px;
            margin: 0 auto;
            max-width: 350px;
        }
        
        .number-cell {
            position: relative;
            background: rgba(79, 70, 112, 0.7);
            border-radius: 15px;
            aspect-ratio: 1/1;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 1.3em;
            font-weight: bold;
            color: white;
            cursor: pointer;
            overflow: hidden;
        }
        
        .number-cell.selected {
            box-shadow: 0 0 0 2px #fff;
        }
        
        /* Progress indicators */
        .progress-indicator {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 4px;
            border-radius: 0 0 0 15px;
        }
        
        /* Low betting (under 50%) */
        .progress-low {
            background: linear-gradient(to right, #4CAF50, #8BC34A);
        }
        
        /* Medium betting (50-90%) */
        .progress-medium {
            background: linear-gradient(to right, #FFEB3B, #FFC107);
        }
        
        /* High betting (above 90%) */
        .progress-high {
            background: linear-gradient(to right, #FF9800, #FF5722);
        }
        
        /* Closed (100% full) */
        .number-cell.closed {
            background: rgba(50, 50, 50, 0.8);
            color: rgba(255, 255, 255, 0.5);
            cursor: not-allowed;
        }
        
        @media (max-width: 500px) {  
            .container { max-width: 99vw;}
            .numbers-grid { gap: 6px; }
            .number-cell { font-size: 1.1em; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Back Button -->
        <a href="2d_live_api.php?page=<?= htmlspecialchars($returnPage) ?>" class="back-btn">
            <i class="bi bi-arrow-left"></i> ပင်မစာမျက်နှာသို့
        </a>
        
        <!-- Control Panel -->
        <div class="control-panel">
            <div class="panel-row">
                <button class="fastpick-btn" id="fastpick-quick">အမြန်ရွေး</button>
                <span class="panel-label">R</span>
                <input type="number" min="100" step="100" value="100" id="amount-input" class="amount-input" placeholder="ကျပ် 100" />
            </div>
            
            <div class="panel-row">
                <div class="panel-label">ထိုးချိန်</div>
                <div class="panel-time"><?= htmlspecialchars($display_time) ?></div>
            </div>
            
            <div class="panel-row">
                <div class="panel-wallet">
                    <div class="wallet-icon">
                        <i class="bi bi-wallet2"></i>
                    </div>
                    <div>0 ကျပ်</div>
                </div>
                <button class="bet-btn" id="bet-btn">ထိုးမည်</button>
            </div>
        </div>
        
        <!-- Legend Box -->
        <div class="legend-box">
            <div class="legend-title">ရှင်းလင်းချက်</div>
            <div class="legend-item">
                <div class="legend-color legend-low"></div>
                <div class="legend-text">၅၀% အောက်</div>
            </div>
            <div class="legend-item">
                <div class="legend-color legend-medium"></div>
                <div class="legend-text">၅၀% မှ ၉၀% ကြား</div>
            </div>
            <div class="legend-item">
                <div class="legend-color legend-high"></div>
                <div class="legend-text">၉၀% အထက်</div>
            </div>
            <div class="legend-item">
                <div class="legend-color legend-full"></div>
                <div class="legend-text">ထိုးငွေပြည့်သွားပါပြီ</div>
            </div>
        </div>
        
        <!-- Number Grid - New Design -->
        <div class="numbers-grid">
            <?php foreach (range(0, 99) as $i): 
                $num = str_pad($i, 2, '0', STR_PAD_LEFT);
                $percentage = $betPercentages[$num];
                $isClosed = $percentage >= 100;
                
                // Determine progress class and width
                $progressClass = '';
                if ($isClosed) {
                    // Closed - no progress bar needed
                } else if ($percentage > 90) {
                    $progressClass = 'progress-high';
                } else if ($percentage >= 50) {
                    $progressClass = 'progress-medium';
                } else {
                    $progressClass = 'progress-low';
                }
                ?>
                <div class="number-cell <?= $isClosed ? 'closed' : '' ?>" data-num="<?= $num ?>">
                    <?= $num ?>
                    <?php if (!$isClosed): ?>
                        <div class="progress-indicator <?= $progressClass ?>" style="width: <?= $percentage ?>%"></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
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
    // Updated number cell selection logic
    const numberCells = document.querySelectorAll('.number-cell:not(.closed)');
    numberCells.forEach(cell => {
        cell.addEventListener('click', function() {
            if (!cell.classList.contains('closed')) {
                cell.classList.toggle('selected');
            }
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
    const betPercentages = <?= json_encode($betPercentages) ?>;

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
        
        // Clear all selections first
        numberCells.forEach(cell => {
            cell.classList.remove('selected');
        });
        
        // Apply new selections
        pairs.forEach(num => {
            const cell = document.querySelector(`.number-cell[data-num="${num}"]:not(.closed)`);
            if (cell) {
                cell.classList.add('selected');
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
        
        // Clear all selections first
        numberCells.forEach(cell => {
            cell.classList.remove('selected');
        });
        
        // Apply new selections
        numberCells.forEach(cell => {
            if (cell.classList.contains('closed')) return;
            
            const num = cell.getAttribute('data-num');
            const sum = parseInt(num[0], 10) + parseInt(num[1], 10);
            if (selectedBkPopup.includes(String(sum))) {
                cell.classList.add('selected');
            }
        });
        
        hidePopup();
    };

    // --- Quickpick types ---
    document.querySelectorAll('.quickpick-btn[data-quick]').forEach(btn => {
        btn.addEventListener('click', function() {
            // Clear all selections first
            numberCells.forEach(cell => {
                cell.classList.remove('selected');
            });
            
            const type = btn.getAttribute('data-quick');
            
            // Apply different filters based on quickpick type
            numberCells.forEach(cell => {
                if (cell.classList.contains('closed')) return;
                
                const num = cell.getAttribute('data-num');
                
                if (type === 'even-even' && parseInt(num[0], 10) % 2 === 0 && parseInt(num[1], 10) % 2 === 0) {
                    cell.classList.add('selected');
                } 
                else if (type === 'odd-odd' && parseInt(num[0], 10) % 2 === 1 && parseInt(num[1], 10) % 2 === 1) {
                    cell.classList.add('selected');
                }
                else if (type === 'even-odd' && parseInt(num[0], 10) % 2 === 0 && parseInt(num[1], 10) % 2 === 1) {
                    cell.classList.add('selected');
                }
                else if (type === 'apu' && apuArr.includes(num)) {
                    cell.classList.add('selected');
                }
                else if (type === 'pawa' && pawaArr.includes(num)) {
                    cell.classList.add('selected');
                }
                else if (type === 'nakha' && nakhaArr.includes(num)) {
                    cell.classList.add('selected');
                }
                else if (type === 'even-top' && parseInt(num[0], 10) % 2 === 0) {
                    cell.classList.add('selected');
                }
                else if (type === 'odd-top' && parseInt(num[0], 10) % 2 === 1) {
                    cell.classList.add('selected');
                }
            });
            
            hidePopup();
        });
    });

    // --- Old Top/Bottom Pick logic (adjusted for new UI) ---
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
        
        // Clear all selections first
        numberCells.forEach(cell => {
            cell.classList.remove('selected');
        });
        
        // Apply new selections
        numberCells.forEach(cell => {
            if (cell.classList.contains('closed')) return;
            
            const num = cell.getAttribute('data-num');
            
            if (
                (num.length === 2 && selectedTops.includes(num[0])) ||
                (num.length === 2 && selectedBottoms.includes(num[1]))
            ) {
                cell.classList.add('selected');
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

    // --- BET POPUP LOGIC ---
    function showBetPopup() {
        // Check if time has passed
        const isTimeClosed = <?= $is_time_closed ? 'true' : 'false' ?>;
        if (isTimeClosed) {
            alert('သတိပေးချက် - <?= htmlspecialchars($display_time) ?> အချိန်အတွက် ထိုးချိန် ကုန်ဆုံးသွားပါပြီ။');
            return;
        }
        
        // Collect selected numbers
        const selCells = Array.from(document.querySelectorAll('.number-cell.selected'));
        if (selCells.length === 0) {
            alert('ထိုးရန် နံပါတ်ရွေးပါ။');
            return;
        }
        let defaultAmt = parseInt(document.getElementById('amount-input').value, 10);
        if (isNaN(defaultAmt) || defaultAmt < 0) defaultAmt = 100;
        // Build bet list data
        window.betList = selCells.map(cell => ({
            num: cell.getAttribute('data-num'),
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
        const timeDisplay = "<?= htmlspecialchars($display_time) ?>";
        alert('ထိုးခြင်း အတည်ပြုပါသည်!\nအချိန်: ' + timeDisplay + '\n' + 
             betList.length + ' ကွက်\n' + 
             betList.reduce((a,b)=>a+parseInt(b.amt,10),0) + ' ကျပ်');
        hideBetPopup();
        
        // After successful bet, automatically return to main page after short delay
        setTimeout(function() {
            window.location.href = '2d_live_api.php?page=<?= htmlspecialchars($returnPage) ?>';
        }, 1500);
    };
    </script>
</body>
</html>