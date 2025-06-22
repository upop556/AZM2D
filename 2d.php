<?php  
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Phone Number Pad UI (20x5) Fast Pick + R + Combination</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: Arial, sans-serif; background: #f8f8f8; margin: 0; padding: 0; }
        .container { max-width: 340px; margin: 18px auto 0 auto; background: #fff; border-radius: 18px; box-shadow: 0 2px 12px rgba(0,0,0,0.06), 0 1.5px 6px rgba(0,0,0,0.03); padding: 18px 8px 18px 8px; }
        h2 { text-align:center; margin-top:6px; margin-bottom:10px; font-size: 1.2em; letter-spacing: 1px; color: #222; }
        .balance-bar { margin: 0 auto 14px auto; max-width: 280px; display: flex; justify-content: flex-end; align-items: center; background: none; border-radius: 0; border: none; padding: 0; font-size: 1.06em; color: #bb2222; font-weight: bold; }
        .balance-text { color: #bb2222; font-weight: bold; letter-spacing: 0.02em; }
        .fastpick-bar { margin: 0 auto 10px auto; max-width: 300px; display: flex; justify-content: space-between; align-items: center; gap: 8px; flex-wrap: wrap; }
        .fastpick-btn { min-width: 70px; padding: 5px 14px; border: none; border-radius: 8px; background: #e0e7ef; color: #244; font-size: 1em; font-weight: bold; cursor: pointer; transition: background 0.13s, color 0.13s; outline: none; }
        .fastpick-btn.selected, .fastpick-btn:active { background: #4da8ff; color: #fff; }
        .bet-controls { display: flex; align-items: center; gap: 8px; }
        .amount-input { font-size: 1.02em; width: 70px; padding: 5px 8px; border-radius: 6px; border: 1px solid #b7b7b7; outline: none; background: #f2f5fa; }
        .amount-input:focus { border-color: #4da8ff; background: #fff; }
        .bet-btn { background: #0bc66b; color: #fff; border: none; border-radius: 7px; font-size: 1em; font-weight: bold; padding: 5px 14px; cursor: pointer; transition: background 0.13s, color 0.13s; }
        .bet-btn:active, .bet-btn:hover { background: #089e58; }
        .legend-box { margin: 10px auto 18px auto; background: #f8f8f8; border-radius: 10px; border: 1px solid #e3e7ef; padding: 10px 6px 10px 6px; max-width: 300px; font-size: 0.98em; display: flex; justify-content: space-around; align-items: center; gap: 6px; }
        .legend-color { width: 28px; height: 28px; border-radius: 8px; display: inline-block; border: 2px solid #b6b6b6; font-weight: bold; font-size: 1.07em; text-align: center; line-height: 28px; }
        .legend-low { background: #ffda92; color: #ffda92; border-color: #ffda92;}
        .legend-medium { background: #ffe66d; color: #ffe66d; border-color: #ffe66d;}
        .legend-high { background: #0bc66b; color: #0bc66b; border-color: #0bc66b;}
        .legend-r { background: #1b263b; color: #fff; border-color: #1b263b; font-family: Arial, sans-serif; font-size: 0.98em; text-decoration: none; font-weight: bold; letter-spacing: 0.1em; text-align: center; line-height: 28px; cursor: pointer; transition: background 0.12s, color 0.12s, border 0.12s; display: flex; align-items: center; justify-content: center; }
        .legend-r:hover, .legend-r:active { background: #274472; border-color: #274472; color: #fff; }
        .popup-mask { position: fixed; left:0; top:0; right:0; bottom:0; background: rgba(0,0,0,0.16); z-index: 9999; display: none; }
        .popup-dialog { position: fixed; left: 0; right: 0; top: 60px; margin: auto; background: #fff; border-radius: 14px; box-shadow: 0 6px 36px rgba(60,60,80,0.13); max-width: 340px; width: 98vw; z-index: 10000; display: none; padding: 24px 12px 16px 12px; }
        .popup-dialog h3 { margin-top: 0; margin-bottom: 12px; font-size: 1.09em; color: #274472; letter-spacing: 0.02em; }
        .popup-dialog .popup-content { font-size: 1.05em; margin-bottom: 12px; word-break: break-all; }
        .quickpick-row { display: flex; gap: 7px; justify-content: center; margin-bottom: 10px; margin-top: -4px; flex-wrap: wrap; }
        .quickpick-btn { padding: 3px 9px; border-radius: 7px; border: none; background: #e0e7ef; color: #23395d; font-weight: bold; cursor: pointer; transition: background 0.13s, color 0.13s; font-size: 1em; }
        .quickpick-btn:hover, .quickpick-btn:active { background: #4da8ff; color: #fff; }

        .quickpick-section-label { font-weight:bold; font-size:1em; margin: 14px 0 2px 0; display:block; }
        .quickpick-inline-row { display: flex; gap: 7px; justify-content: center; margin-bottom: 7px; flex-wrap: wrap; }

        .quickpick-btn.selected { background: #4da8ff; color: #fff; }

        .top-pick-row, .bottom-pick-row { display: flex; gap: 6px; justify-content: center; margin-bottom: 12px; flex-wrap: wrap;}
        .top-pick-btn, .bottom-pick-btn { min-width: 27px; padding: 4px 0; font-size: 1.08em; font-weight: bold; background: #f1f6ff; color: #23395d; border: 1px solid #bcd2f7; border-radius: 7px; cursor: pointer; transition: background 0.13s, color 0.13s; }
        .top-pick-btn.selected, .top-pick-btn:active,
        .bottom-pick-btn.selected, .bottom-pick-btn:active { background: #4da8ff; color: #fff; border-color: #2666a3; }
        .popup-dialog .popup-close { display: inline-block; margin-top: 6px; padding: 4px 18px; background: #eee; color: #222; border: none; border-radius: 7px; font-size: 1em; font-weight: bold; cursor: pointer; transition: background 0.12s; }
        .popup-dialog .popup-close:hover, .popup-dialog .popup-close:active { background: #e0e7ef; }
        .popup-actions-row { display: flex; gap: 10px; justify-content: center; margin-top: 3px; margin-bottom: 8px; }
        .popup-action-btn { padding: 3px 14px; border-radius: 7px; border: none; background: #e0e7ef; color: #23395d; font-weight: bold; cursor: pointer; transition: background 0.13s, color 0.13s; font-size: 1em; }
        .popup-action-btn:hover, .popup-action-btn:active { background: #4da8ff; color: #fff; }
        .bet-list-table { width: 100%; border-collapse: separate; border-spacing: 0 7px; margin-bottom: 18px;}
        .bet-list-table th, .bet-list-table td { text-align: center; font-size: 1.04em; padding: 5px 5px; }
        .bet-list-table th { font-weight: bold; color: #274472; background: #f6faff;}
        .bet-list-table td .edit-amt { width: 60px; font-size:1em; border: 1px solid #b5c7e5; border-radius: 4px; padding: 2px 5px; }
        .bet-list-table td .del-btn { color: #bb2222; background: #fff0f0; border: 1px solid #fdc0c0; border-radius: 5px; padding: 2px 8px; font-size: 0.98em; cursor: pointer;}
        .bet-list-table td .del-btn:hover { background: #ffecec; }
        .bet-summary-row { font-weight:bold; background: #f8f8f8;}
        table { width: 100%; border-collapse: separate; border-spacing: 8px 8px; }
        td { border-radius: 12px; border: none; padding: 0; text-align: center; font-size: 1.25em; font-weight: 600; color: #23395d; box-shadow: 0 1px 2px rgba(80,80,80,0.04); transition: background 0.15s, color 0.15s; user-select: none; cursor: pointer; position: relative; height: 44px; }
        .cell-btn { width: 100%; height: 44px; border: none; outline: none; background: none; padding: 0; margin: 0; font-size: inherit; font-weight: inherit; border-radius: 12px; cursor: pointer; transition: background 0.15s, color 0.15s; display: flex; align-items: center; justify-content: center; }
        .color-low { background: #ffda92; color: #543600;}
        .color-medium { background: #ffe66d; color: #473f00;}
        .color-high { background: #0bc66b; color: #fff;}
        .color-full { background: #1b263b; color: #fff; text-decoration: line-through;}
        .color-default { background: #e0e7ef; color: #23395d;}
        .cell-btn.selected.color-low { box-shadow: 0 0 0 2.5px #ffb900; }
        .cell-btn.selected.color-medium { box-shadow: 0 0 0 2.5px #e8c600; }
        .cell-btn.selected.color-high { box-shadow: 0 0 0 2.5px #04ae5a; }
        .cell-btn.selected.color-full { box-shadow: 0 0 0 2.5px #14202c; }
        .cell-btn.selected.color-default { box-shadow: 0 0 0 2.5px #a5b0be; }
        @media (max-width: 500px) {  
            .container { max-width: 99vw;}
            table td { font-size:1em; padding: 0;}
            h2 { font-size: 1em; }
            .balance-bar { font-size: 0.99em; }
            .legend-box { font-size:0.93em; padding: 7px 2px;}
            .legend-color { width:21px; height:21px; line-height:21px; font-size:0.92em;}
            .legend-r { width:21px; height:21px; line-height:21px; font-size:0.92em;}
            .fastpick-btn { min-width: 0; padding: 3px 10px; font-size: 0.96em;}
            .bet-btn { padding: 3px 8px; font-size: 0.96em; }
            .amount-input { width: 55px; font-size: 0.96em; padding: 3px 5px; }
            .cell-btn { height:32px; }
            td { height:32px; }
            .popup-dialog { top: 18px; max-width: 99vw; padding: 18px 6px 12px 6px; }
            .top-pick-row, .bottom-pick-row, .quickpick-row, .quickpick-inline-row { gap: 3px; }
            .top-pick-btn, .bottom-pick-btn { min-width: 17px; padding: 2px 0; font-size: 1em; }
            .popup-action-btn { padding: 2px 8px; font-size: 0.95em;}
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>2d ထိုးရန်</h2>
        <!-- Balance Bar -->
        <div class="balance-bar">
            <span class="balance-text">လက်ကျန်ငွေ 0 ကျပ်</span>
        </div>
        <!-- Modified Fastpick bar with amount input and bet button -->
        <div class="fastpick-bar">
            <button type="button" class="fastpick-btn" id="fastpick-quick">အမြန်ရွေး</button>
            <div class="bet-controls">
                <input type="number" min="100" step="100" value="100" id="amount-input" class="amount-input" />
                <span style="font-size:0.97em;color:#555;">ကျပ်</span>
                <button class="bet-btn" id="bet-btn">ထိုးမည်</button>
            </div>
        </div>
        
        <div class="legend-box">
            <span class="legend-color legend-low"></span>
            <span class="legend-color legend-medium"></span>
            <span class="legend-color legend-high"></span>
            <span class="legend-color legend-r" id="legend-r-btn" title="Reverse Auto Select">R</span>
        </div>
        <table id="numberPadTable">
            <?php foreach ($rows as $rowIdx => $row): ?>
                <tr>
                    <?php foreach ($row as $colIdx => $num):  
                        $n = intval($num);
                        $colorClass = "color-default";
                        if ($n < 50) $colorClass = "color-low";
                        elseif ($n < 90) $colorClass = "color-medium";
                        elseif ($n < 99) $colorClass = "color-high";
                        else $colorClass = "color-full";
                        $btnId = "btn_{$rowIdx}_{$colIdx}";
                    ?>
                    <td>
                        <button 
                            type="button"
                            class="cell-btn <?= $colorClass ?>" 
                            id="<?= $btnId ?>" 
                            data-num="<?= $num ?>"
                        ><?= $num ?></button>
                    </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </table>
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
    // Number pad button logic
    const btns = document.querySelectorAll('.cell-btn');
    btns.forEach(btn => {
        btn.addEventListener('click', function() {
            btn.classList.toggle('selected');
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
        btns.forEach(b => {
            const num = b.getAttribute('data-num');
            if (pairs.includes(num)) {
                b.classList.add('selected');
            } else {
                b.classList.remove('selected');
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
        btns.forEach(b => {
            const num = b.getAttribute('data-num');
            const sum = parseInt(num[0], 10) + parseInt(num[1], 10);
            if (selectedBkPopup.includes(String(sum))) {
                b.classList.add('selected');
            } else {
                b.classList.remove('selected');
            }
        });
        hidePopup();
    };

    // --- Quickpick types ---
    document.querySelectorAll('.quickpick-btn[data-quick]').forEach(btn => {
        btn.addEventListener('click', function() {
            btns.forEach(b => b.classList.remove('selected'));
            const type = btn.getAttribute('data-quick');
            if (type === 'even-even') {
                btns.forEach(b => {
                    const num = b.getAttribute('data-num');
                    if (parseInt(num[0], 10) % 2 === 0 && parseInt(num[1], 10) % 2 === 0) {
                        b.classList.add('selected');
                    }
                });
            } else if (type === 'odd-odd') {
                btns.forEach(b => {
                    const num = b.getAttribute('data-num');
                    if (parseInt(num[0], 10) % 2 === 1 && parseInt(num[1], 10) % 2 === 1) {
                        b.classList.add('selected');
                    }
                });
            } else if (type === 'even-odd') {
                btns.forEach(b => {
                    const num = b.getAttribute('data-num');
                    if (parseInt(num[0], 10) % 2 === 0 && parseInt(num[1], 10) % 2 === 1) {
                        b.classList.add('selected');
                    }
                });
            } else if (type === 'apu') {
                btns.forEach(b => {
                    const num = b.getAttribute('data-num');
                    if (apuArr.includes(num)) {
                        b.classList.add('selected');
                    }
                });
            } else if (type === 'pawa') {
                btns.forEach(b => {
                    const num = b.getAttribute('data-num');
                    if (pawaArr.includes(num)) {
                        b.classList.add('selected');
                    }
                });
            } else if (type === 'nakha') {
                btns.forEach(b => {
                    const num = b.getAttribute('data-num');
                    if (nakhaArr.includes(num)) {
                        b.classList.add('selected');
                    }
                });
            } else if (type === 'even-top') {
                btns.forEach(b => {
                    const num = b.getAttribute('data-num');
                    if (parseInt(num[0], 10) % 2 === 0) {
                        b.classList.add('selected');
                    }
                });
            } else if (type === 'odd-top') {
                btns.forEach(b => {
                    const num = b.getAttribute('data-num');
                    if (parseInt(num[0], 10) % 2 === 1) {
                        b.classList.add('selected');
                    }
                });
            }
            hidePopup();
        });
    });

    // --- Old Top/Bottom Pick logic (remains for completeness) ---
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
        btns.forEach(b => {
            const num = b.getAttribute('data-num');
            if (
                (num.length === 2 && selectedTops.includes(num[0])) ||
                (num.length === 2 && selectedBottoms.includes(num[1]))
            ) {
                b.classList.add('selected');
            } else {
                b.classList.remove('selected');
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

    document.getElementById('legend-r-btn').addEventListener('click', function() {
        const selected = [];
        btns.forEach(btn => {
            if (btn.classList.contains('selected')) {
                selected.push(btn.getAttribute('data-num'));
            }
        });
        btns.forEach(btn => btn.classList.remove('selected'));
        selected.forEach(num => {
            if (num.length === 2) {
                const origBtn = document.querySelector('.cell-btn[data-num="' + num + '"]');
                if (origBtn) origBtn.classList.add('selected');
                const reversed = num[1] + num[0];
                if (reversed !== num) {
                    const reversedBtn = document.querySelector('.cell-btn[data-num="' + reversed + '"]');
                    if (reversedBtn) reversedBtn.classList.add('selected');
                }
            }
        });
    });

    // --- BET POPUP LOGIC ---
    function showBetPopup() {
        // Collect selected numbers
        const selBtns = Array.from(document.querySelectorAll('.cell-btn.selected'));
        if (selBtns.length === 0) {
            alert('ထိုးရန် နံပါတ်ရွေးပါ။');
            return;
        }
        let defaultAmt = parseInt(document.getElementById('amount-input').value, 10);
        if (isNaN(defaultAmt) || defaultAmt < 0) defaultAmt = 100;
        // Build bet list data
        window.betList = selBtns.map(btn => ({
            num: btn.getAttribute('data-num'),
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
        alert('ထိုးခြင်း အတည်ပြုပါသည်!\n' + betList.length + ' ကွက်\n' + betList.reduce((a,b)=>a+parseInt(b.amt,10),0) + ' ကျပ်');
        hideBetPopup();
    };
    </script>
</body>
</html>
