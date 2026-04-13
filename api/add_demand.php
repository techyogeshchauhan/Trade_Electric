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
$time_block = $_POST['time_block'] ?? '';
$units = (float)($_POST['units'] ?? 0);
$price = (float)($_POST['price'] ?? 0);

// Validate input
if (empty($date) || empty($time_block) || $units <= 0 || $price <= 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Fill all fields correctly"
    ]);
    exit();
}

// Fetch charges/settings
$settings = $conn->query("SELECT * FROM settings LIMIT 1");
$settingsData = $settings ? $settings->fetch_assoc() : [];

$utility_charge = (float)($settingsData['utility_charge'] ?? 0.02);
$platform_charge = (float)($settingsData['platform_charge'] ?? 2);

// Calculate total amount to block
$energyCost = $units * $price;
$utility = $units * $utility_charge;
$platform = $units * $platform_charge;
$total = $energyCost + $utility + $platform;

// Get wallet
$walletQuery = $conn->query("
    SELECT balance, blocked_balance
    FROM wallet
    WHERE user_id = $user_id
");

$wallet = $walletQuery ? $walletQuery->fetch_assoc() : null;

// Auto create wallet if not exists
if (!$wallet) {
    $default_balance = 5000;

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

if ($currentBalance < $total) {
    echo json_encode([
        "status" => "error",
        "message" => "Insufficient wallet balance. Required ₹" . number_format($total, 2)
    ]);
    exit();
}

// Start transaction
$conn->begin_transaction();

try {

    // Block amount in wallet
    $updateWallet = $conn->query("
        UPDATE wallet
        SET balance = balance - $total,
            blocked_balance = blocked_balance + $total
        WHERE user_id = $user_id
    ");

    if (!$updateWallet) {
        throw new Exception("Wallet update failed: " . $conn->error);
    }

    // Insert demand
    $insertDemand = $conn->query("
        INSERT INTO demand_listings
        (user_id, date, time_block, units_required, remaining_units, max_price)
        VALUES
        ($user_id, '$date', '$time_block', $units, $units, $price)
    ");

    if (!$insertDemand) {
        throw new Exception("Demand insert failed: " . $conn->error);
    }

    // Wallet transaction log
    $log = $conn->query("
        INSERT INTO wallet_transactions
        (user_id, type, amount, description)
        VALUES
        ($user_id, 'block', $total, 'Amount blocked for energy demand')
    ");

    if (!$log) {
        throw new Exception("Wallet log failed: " . $conn->error);
    }

    $conn->commit();

    echo json_encode([
        "status" => "success",
        "message" => "Demand added successfully"
    ]);

} catch (Exception $e) {

    $conn->rollback();

    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?>