<?php
session_start();
header('Content-Type: application/json');

include '../frontend/includes/config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        "status" => "error",
        "message" => "Login required"
    ]);
    exit();
}

$user_id = (int)$_SESSION['user_id'];

$date = $_POST['date'] ?? '';
$start_time = $_POST['start_time'] ?? '';
$end_time = $_POST['end_time'] ?? '';
$units = (float)($_POST['units'] ?? 0);
$price = (float)($_POST['price'] ?? 0);

// Validate input
if (empty($date) || empty($start_time) || empty($end_time) || $units <= 0 || $price <= 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Fill all fields correctly"
    ]);
    exit();
}

// Validate time range
if ($start_time >= $end_time) {
    echo json_encode([
        "status" => "error",
        "message" => "End time must be after start time"
    ]);
    exit();
}

// Fetch charges/settings
$settings = $conn->query("SELECT * FROM settings LIMIT 1");
$settingsData = $settings ? $settings->fetch_assoc() : [];

$utility_charge = (float)($settingsData['utility_charge'] ?? 0.02);
$platform_charge = (float)($settingsData['platform_charge'] ?? 2);

// Generate 15-minute time blocks
$time_blocks = [];
$current = strtotime($start_time);
$end = strtotime($end_time);

while ($current < $end) {
    $from = date("H:i", $current);
    $to = date("H:i", $current + 900); // 15 minutes = 900 seconds
    $time_blocks[] = "$from-$to";
    $current += 900;
}

if (empty($time_blocks)) {
    echo json_encode([
        "status" => "error",
        "message" => "No valid time blocks generated"
    ]);
    exit();
}

// Calculate total amount to block
$energyCost = $units * $price;
$utility = $units * $utility_charge;
$platform = $units * $platform_charge;
$totalPerBlock = $energyCost + $utility + $platform;
$totalAmount = $totalPerBlock * count($time_blocks);

// Get wallet
$walletQuery = $conn->query("
    SELECT balance, blocked_balance
    FROM wallet
    WHERE user_id = $user_id
");

$wallet = $walletQuery ? $walletQuery->fetch_assoc() : null;

// Auto create wallet if not exists
if (!$wallet) {
    // Get default balance from settings
    $settingsBalance = $conn->query("SELECT default_wallet_balance FROM settings LIMIT 1");
    $balanceData = $settingsBalance ? $settingsBalance->fetch_assoc() : null;
    $default_balance = $balanceData['default_wallet_balance'] ?? 5000;

    $createWallet = $conn->query("
        INSERT INTO wallet (user_id, balance, blocked_balance)
        VALUES ($user_id, $default_balance, 0)
    ");

    if (!$createWallet) {
        echo json_encode([
            "status" => "error",
            "message" => "Wallet creation failed: " . $conn->error
        ]);
        exit();
    }

    $wallet = [
        'balance' => $default_balance,
        'blocked_balance' => 0
    ];
}

// Check balance
$currentBalance = (float)$wallet['balance'];

if ($currentBalance < $totalAmount) {
    echo json_encode([
        "status" => "error",
        "message" => "Insufficient wallet balance. Required ₹" . number_format($totalAmount, 2) . " for " . count($time_blocks) . " time blocks"
    ]);
    exit();
}

// Start transaction
$conn->begin_transaction();

try {

    // Block amount in wallet
    $updateWallet = $conn->query("
        UPDATE wallet
        SET balance = balance - $totalAmount,
            blocked_balance = blocked_balance + $totalAmount
        WHERE user_id = $user_id
    ");

    if (!$updateWallet) {
        throw new Exception("Wallet update failed: " . $conn->error);
    }

    // Insert demands for each time block
    $insertedCount = 0;
    foreach ($time_blocks as $time_block) {
        $insertDemand = $conn->query("
            INSERT INTO demand_listings
            (user_id, date, time_block, units_required, remaining_units, max_price)
            VALUES
            ($user_id, '$date', '$time_block', $units, $units, $price)
        ");

        if (!$insertDemand) {
            throw new Exception("Demand insert failed for $time_block: " . $conn->error);
        }
        $insertedCount++;
    }

    // Wallet transaction log
    $log = $conn->query("
        INSERT INTO wallet_transactions
        (user_id, type, amount, description)
        VALUES
        ($user_id, 'block', $totalAmount, 'Amount blocked for $insertedCount energy demands')
    ");

    if (!$log) {
        throw new Exception("Wallet log failed: " . $conn->error);
    }

    $conn->commit();

    echo json_encode([
        "status" => "success",
        "message" => "$insertedCount demands added successfully from $start_time to $end_time"
    ]);

} catch (Exception $e) {

    $conn->rollback();

    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?>
