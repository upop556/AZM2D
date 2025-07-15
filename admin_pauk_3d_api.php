<?php
// admin_pauk_3d_api.php - 3D ပေါက်သီး admin API (winner payout/fake winner logic)
// Pays only real winners (not fake winners), using is_fake flag for fake bets
// Uses lottery_bets table and bet_type='3D'

// --- 1. Authentication & Authorization ---
header('Content-Type: application/json');
session_start();
if (empty($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
    echo json_encode(['success' => false, 'message' => 'Admin access required.']);
    exit;
}

// --- 2. Request Method and Input Validation ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input) || empty($input['draw_date']) || !isset($input['number'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data format.']);
    exit;
}

// --- 3. Data Validation ---
$draw_date = $input['draw_date'];
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $draw_date)) {
    echo json_encode(['success' => false, 'message' => 'Draw date format invalid.']);
    exit;
}
$number = trim($input['number']);
if (!preg_match('/^\d{3}$/', $number)) {
    echo json_encode(['success' => false, 'message' => 'နံပါတ် ၃ လုံးသာ ရေးနိုင်သည်။']);
    exit;
}
$bet_type = '3D';

require_once(__DIR__ . '/db.php');
try {
    $pdo = Db::getInstance()->getConnection();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'DB connection error.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Main payout rate
    $prize_rate = 700;
    // Side payout rate for တွဒ်လျှော်ကြေး
    $side_rate = 10;

    // --- Upsert winning number ---
    $stmt = $pdo->prepare('SELECT id FROM winning_numbers WHERE draw_date = :draw_date AND bet_type = :bet_type');
    $stmt->execute([':draw_date' => $draw_date, ':bet_type' => $bet_type]);
    if ($stmt->fetch()) {
        $updateStmt = $pdo->prepare('UPDATE winning_numbers SET number = :number, prize_rate = :prize_rate, updated_at = NOW() WHERE draw_date = :draw_date AND bet_type = :bet_type');
        $updateStmt->execute([':number' => $number, ':prize_rate' => $prize_rate, ':draw_date' => $draw_date, ':bet_type' => $bet_type]);
    } else {
        $insertStmt = $pdo->prepare('INSERT INTO winning_numbers (draw_date, bet_type, number, prize_rate, created_at) VALUES (:draw_date, :bet_type, :number, :prize_rate, NOW())');
        $insertStmt->execute([':draw_date' => $draw_date, ':bet_type' => $bet_type, ':number' => $number, ':prize_rate' => $prize_rate]);
    }

    // --- Calculate real winners BEFORE adding fakes ---
    $real_winners_stmt = $pdo->prepare("SELECT COUNT(id) FROM lottery_bets WHERE bet_date = ? AND bet_type = ? AND number = ? AND (is_fake IS NULL OR is_fake = 0)");
    $real_winners_stmt->execute([$draw_date, $bet_type, $number]);
    $real_winner_count = $real_winners_stmt->fetchColumn();

    // --- Generate Fake Winners if needed ---
    $min_total_winners = 50;
    $fake_winners_added = 0;
    if ($real_winner_count < $min_total_winners) {
        $needed_fakes = $min_total_winners - $real_winner_count;
        $user_pool_stmt = $pdo->query("SELECT id FROM users WHERE is_admin IS NULL OR is_admin = 0 ORDER BY RAND() LIMIT 100");
        $user_ids = $user_pool_stmt->fetchAll(PDO::FETCH_COLUMN);
        if (count($user_ids) > 0) {
            $insert_fake_bet = $pdo->prepare("INSERT INTO lottery_bets (user_id, bet_type, number, amount, bet_date, created_at, is_fake) VALUES (?, ?, ?, ?, ?, ?, 1)");
            for ($i = 0; $i < $needed_fakes; $i++) {
                if (empty($user_ids)) break;
                $user_index = array_rand($user_ids);
                $fake_user_id = $user_ids[$user_index];
                // Generate a random bet amount from 100 to 5000 (descending)
                $bet_amount = rand(1, 50) * 100; // 100 ~ 5000
                // If you want more winners with less amount, you can use: $bet_amount = 5100 - ($i * 100); but for random: keep as is.
                $random_hour = rand(8, 11); $random_minute = rand(0, 59);
                $created_time = date('Y-m-d H:i:s', strtotime("$draw_date $random_hour:$random_minute"));
                $insert_fake_bet->execute([$fake_user_id, $bet_type, $number, $bet_amount, $draw_date, $created_time]);
                $fake_winners_added++;
                unset($user_ids[$user_index]);
            }
        }
    }

    // --- Main payout for main winners ---
    $winner_fetch_stmt = $pdo->prepare("
        SELECT user_id, SUM(amount) as total_bet
        FROM lottery_bets
        WHERE bet_date = :bet_date
          AND bet_type = :bet_type
          AND number = :number
          AND (is_fake IS NULL OR is_fake = 0)
        GROUP BY user_id
    ");
    $winner_fetch_stmt->execute([
        ':bet_date' => $draw_date,
        ':bet_type' => $bet_type,
        ':number' => $number
    ]);
    $real_winner_rows = $winner_fetch_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Update balance for real winners only
    $update_balance_stmt = $pdo->prepare("UPDATE users SET balance = balance + :payout WHERE id = :user_id");
    foreach ($real_winner_rows as $w) {
        $payout = $w['total_bet'] * $prize_rate;
        $update_balance_stmt->execute([
            ':payout' => $payout,
            ':user_id' => $w['user_id']
        ]);
        // Optionally log the payout transaction here
    }

    // --- တွဒ်လျှော်ကြေး logic ---
    function get_side_numbers($num_str) {
        // "တပွင့်တိုး" (next)
        $next = str_pad((intval($num_str)+1)%1000, 3, '0', STR_PAD_LEFT);
        // "တပွင့်လျှော့" (previous)
        $prev = str_pad((intval($num_str)-1+1000)%1000, 3, '0', STR_PAD_LEFT);

        // "ပါတ်လည်လုံးတူ" (rotations)
        $rotations = [];
        $d = str_split($num_str);
        $rotations[] = $d[1].$d[2].$d[0];
        $rotations[] = $d[2].$d[0].$d[1];
        $rotations[] = $d[2].$d[1].$d[0];
        $rotations[] = $d[0].$d[2].$d[1];
        $rotations[] = $d[1].$d[0].$d[2];

        $rotations = array_unique(array_filter($rotations, function($val) use ($num_str){ return $val !== $num_str; }));

        return array_unique(array_merge([$next, $prev], $rotations));
    }
    $side_numbers = get_side_numbers($number);

    // Pay side winners (10x), exclude from top winner stats
    $side_winner_fetch_stmt = $pdo->prepare("
        SELECT user_id, SUM(amount) as total_bet
        FROM lottery_bets
        WHERE bet_date = :bet_date
          AND bet_type = :bet_type
          AND number = :side_number
          AND (is_fake IS NULL OR is_fake = 0)
        GROUP BY user_id
    ");
    foreach ($side_numbers as $side_number) {
        $side_winner_fetch_stmt->execute([
            ':bet_date' => $draw_date,
            ':bet_type' => $bet_type,
            ':side_number' => $side_number
        ]);
        $side_rows = $side_winner_fetch_stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($side_rows as $w) {
            $side_payout = $w['total_bet'] * $side_rate;
            $update_balance_stmt->execute([
                ':payout' => $side_payout,
                ':user_id' => $w['user_id']
            ]);
            // Optionally log the payout transaction here
        }
    }

    // --- Recalculate stats AFTER adding fakes ---
    $stats_stmt = $pdo->prepare("
        SELECT 
            COUNT(id) as total_winners,
            SUM(amount) as total_bet_amount,
            SUM(amount * :prize_rate) as total_payout_amount,
            COUNT(DISTINCT user_id) as unique_winners
        FROM lottery_bets
        WHERE bet_date = :bet_date AND bet_type = :bet_type AND number = :number
    ");
    $stats_stmt->execute([':bet_date' => $draw_date, ':bet_type' => $bet_type, ':number' => $number, ':prize_rate' => $prize_rate]);
    $winner_stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

    // --- Get Top 50 Winners for Preview (main number only, both real and fake) ---
    $top_winners_stmt = $pdo->prepare("
        SELECT u.name, u.phone, lb.amount, (lb.amount * :prize_rate) as payout_amount, lb.is_fake
        FROM lottery_bets lb
        JOIN users u ON u.id = lb.user_id
        WHERE lb.bet_date = :bet_date AND lb.bet_type = :bet_type AND lb.number = :number
        ORDER BY lb.amount DESC LIMIT 50
    ");
    $top_winners_stmt->execute([':bet_date' => $draw_date, ':bet_type' => $bet_type, ':number' => $number, ':prize_rate' => $prize_rate]);
    $top_winners = $top_winners_stmt->fetchAll(PDO::FETCH_ASSOC);

    $pdo->commit();

    // --- Send Final Response ---
    echo json_encode([
        'success' => true,
        'message' => 'ပေါက်သီး (3D) တင်ခြင်း အောင်မြင်ပါသည်။',
        'winner_stats' => [
            'real_winners' => (int)$real_winner_count,
            'fake_winners_added' => $fake_winners_added,
            'total_winners' => (int)($winner_stats['total_winners'] ?? 0),
            'unique_winners' => (int)($winner_stats['unique_winners'] ?? 0),
            'total_bet_amount' => (float)($winner_stats['total_bet_amount'] ?? 0),
            'total_payout_amount' => (float)($winner_stats['total_payout_amount'] ?? 0),
            'prize_rate' => $prize_rate,
            'side_rate' => $side_rate,
            'side_numbers' => $side_numbers
        ],
        'top_winners' => $top_winners
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}
?>