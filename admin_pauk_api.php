<?php
// admin_pauk_api.php - UPDATED TO USE CORRECT TABLES AND FIELDS FOR WINNER PAYOUT LOGIC
// API for admin to submit 2D winning numbers with fake winner generation
// Pays only real winners (not fake winners), using is_fake flag for fake bets
// Uses lottery_bets table and correct bet_type schema

header('Content-Type: application/json');
session_start();

// --- 1. Authentication & Authorization ---
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

if (!is_array($input) || empty($input['session_time']) || !isset($input['number'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data format.']);
    exit;
}

// --- 3. Data Validation ---
$session_time = $input['session_time'];
$allowed_sessions = ['11:00:00', '12:01:00', '15:00:00', '16:30:00'];
$bet_type_map = [
    '11:00:00' => '2D-1100',
    '12:01:00' => '2D-1201',
    '15:00:00' => '2D-1500',
    '16:30:00' => '2D-1630',
];
if (!in_array($session_time, $allowed_sessions, true)) {
    echo json_encode(['success' => false, 'message' => 'Session time မမှန်ပါ။']);
    exit;
}
$bet_type = $bet_type_map[$session_time];

$number = trim($input['number']);
if (!preg_match('/^\d{2}$/', $number)) {
    echo json_encode(['success' => false, 'message' => 'နံပါတ် ၂ လုံးသာ ရေးနိုင်သည်။']);
    exit;
}

// --- 4. Database Operations ---
date_default_timezone_set('Asia/Yangon');
$today = date('Y-m-d');

require_once(__DIR__ . '/db.php');
try {
    $pdo = Db::getInstance()->getConnection();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'DB connection error.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Prize rate is now 95
    $prize_rate = 95;

    // --- Upsert into winning_numbers table ---
    $stmt = $pdo->prepare('SELECT id FROM winning_numbers WHERE draw_date = :draw_date AND session_time = :session_time');
    $stmt->execute([':draw_date' => $today, ':session_time' => $session_time]);

    if ($stmt->fetch()) {
        $updateStmt = $pdo->prepare('UPDATE winning_numbers SET number = :number, prize_rate = :prize_rate, updated_at = NOW() WHERE draw_date = :draw_date AND session_time = :session_time');
        $updateStmt->execute([':number' => $number, ':prize_rate' => $prize_rate, ':draw_date' => $today, ':session_time' => $session_time]);
    } else {
        $insertStmt = $pdo->prepare('INSERT INTO winning_numbers (draw_date, session_time, number, prize_rate, created_at) VALUES (:draw_date, :session_time, :number, :prize_rate, NOW())');
        $insertStmt->execute([':draw_date' => $today, ':session_time' => $session_time, ':number' => $number, ':prize_rate' => $prize_rate]);
    }

    // --- Calculate real winners BEFORE adding fakes ---
    $real_winners_stmt = $pdo->prepare("SELECT COUNT(id) FROM lottery_bets WHERE bet_date = ? AND bet_type = ? AND number = ? AND (is_fake IS NULL OR is_fake = 0)");
    $real_winners_stmt->execute([$today, $bet_type, $number]);
    $real_winner_count = $real_winners_stmt->fetchColumn();

    // --- Generate Fake Winners if needed ---
    $min_total_winners = 100; // အနည်းဆုံး အနိုင်ရရှိသူ ၁၀၀ ယောက်ရှိစေရန်
    $fake_winners_added = 0;

    if ($real_winner_count < $min_total_winners) {
        $needed_fakes = $min_total_winners - $real_winner_count;

        // Get a larger pool of real user accounts to use for fakes
        $user_pool_stmt = $pdo->query("SELECT id FROM users WHERE is_admin IS NULL OR is_admin = 0 ORDER BY RAND() LIMIT 200");
        $user_ids = $user_pool_stmt->fetchAll(PDO::FETCH_COLUMN);

        if (count($user_ids) > 0) {
            // Add is_fake = 1 for fake bets
            $insert_fake_bet = $pdo->prepare("INSERT INTO lottery_bets (user_id, bet_type, number, amount, bet_date, created_at, is_fake) VALUES (?, ?, ?, ?, ?, ?, 1)");

            for ($i = 0; $i < $needed_fakes; $i++) {
                if (empty($user_ids)) break; // Stop if we run out of users

                $user_index = array_rand($user_ids);
                $fake_user_id = $user_ids[$user_index];

                // Generate a random bet amount from 100 to 10,000
                $bet_amount = rand(1, 100) * 100;

                // Generate a realistic created_at time before the session
                $session_start_hour = (int)substr($session_time, 0, 2);
                $random_hour = rand(max(9, $session_start_hour - 2), $session_start_hour - 1);
                $random_minute = rand(0, 59);
                $created_time = date('Y-m-d H:i:s', strtotime("$today $random_hour:$random_minute"));

                // Insert the fake bet into the database
                $insert_fake_bet->execute([$fake_user_id, $bet_type, $number, $bet_amount, $today, $created_time]);

                $fake_winners_added++;
                unset($user_ids[$user_index]); // Remove user from pool to avoid duplicates
            }
        }
    }

    // --- PAYOUT ONLY REAL WINNERS ---
    // Get all real winners (is_fake IS NULL or is_fake = 0)
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
        ':bet_date' => $today,
        ':bet_type' => $bet_type,
        ':number' => $number
    ]);
    $real_winner_rows = $winner_fetch_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Update balance for each real winner only
    $update_balance_stmt = $pdo->prepare("UPDATE users SET balance = balance + :payout WHERE id = :user_id");
    foreach ($real_winner_rows as $w) {
        $payout = $w['total_bet'] * $prize_rate;
        $update_balance_stmt->execute([
            ':payout' => $payout,
            ':user_id' => $w['user_id']
        ]);
        // Optionally log the payout transaction here
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
    $stats_stmt->execute([':bet_date' => $today, ':bet_type' => $bet_type, ':number' => $number, ':prize_rate' => $prize_rate]);
    $winner_stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

    // --- Get Top 100 Winners for Preview (both real and fake for display) ---
    $top_winners_stmt = $pdo->prepare("
        SELECT u.name, u.phone, lb.amount, (lb.amount * :prize_rate) as payout_amount, lb.is_fake
        FROM lottery_bets lb
        JOIN users u ON u.id = lb.user_id
        WHERE lb.bet_date = :bet_date AND lb.bet_type = :bet_type AND lb.number = :number
        ORDER BY lb.amount DESC LIMIT 100
    ");
    $top_winners_stmt->execute([':bet_date' => $today, ':bet_type' => $bet_type, ':number' => $number, ':prize_rate' => $prize_rate]);
    $top_winners = $top_winners_stmt->fetchAll(PDO::FETCH_ASSOC);

    $pdo->commit();

    // --- Send Final Response ---
    echo json_encode([
        'success' => true,
        'message' => 'ပေါက်သီး တင်ခြင်း အောင်မြင်ပါသည်။',
        'winner_stats' => [
            'real_winners' => (int)$real_winner_count,
            'fake_winners_added' => $fake_winners_added,
            'total_winners' => (int)($winner_stats['total_winners'] ?? 0),
            'unique_winners' => (int)($winner_stats['unique_winners'] ?? 0),
            'total_bet_amount' => (float)($winner_stats['total_bet_amount'] ?? 0),
            'total_payout_amount' => (float)($winner_stats['total_payout_amount'] ?? 0),
            'prize_rate' => $prize_rate
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