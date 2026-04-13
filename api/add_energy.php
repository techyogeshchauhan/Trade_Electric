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

$date = trim($_POST['date'] ?? '');
$time_block = trim($_POST['time_block'] ?? '');
$units = (float)($_POST['units'] ?? 0);
$price = (float)($_POST['price'] ?? 0);

// ✅ Basic Validation
if (empty($date) || empty($time_block) || $units <= 0 || $price <= 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Fill all fields correctly"
    ]);
    exit();
}

// ✅ Get Token Balance (Default 30 if no token record)
$tokenQuery = $conn->query("
    SELECT 
        SUM(
            CASE 
                WHEN token_type IN ('mint','transfer_in') THEN token_units
                WHEN token_type IN ('transfer_out','burn') THEN -token_units
                ELSE 0
            END
        ) AS balance
    FROM token_ledger
    WHERE user_id = $user_id
");

if (!$tokenQuery) {
    echo json_encode([
        "status" => "error",
        "message" => "Token balance fetch failed: " . $conn->error
    ]);
    exit();
}

$tokenData = $tokenQuery->fetch_assoc();

$tokenBalance = (
    isset($tokenData['balance']) &&
    $tokenData['balance'] !== null &&
    $tokenData['balance'] > 0
)
    ? (float)$tokenData['balance']
    : 30;

// ✅ Check available balance
if ($units > $tokenBalance) {
    echo json_encode([
        "status" => "error",
        "message" => "Insufficient token balance. Available: " . number_format($tokenBalance, 2) . " kWh"
    ]);
    exit();
}

// ✅ Prevent duplicate listing for same slot
$checkDuplicate = $conn->query("
    SELECT id 
    FROM energy_listings
    WHERE user_id = $user_id
      AND date = '$date'
      AND time_block = '$time_block'
      AND status = 'available'
      AND remaining_units > 0
");

if ($checkDuplicate && $checkDuplicate->num_rows > 0) {
    echo json_encode([
        "status" => "error",
        "message" => "You already have an active listing for this time slot"
    ]);
    exit();
}

// ✅ Insert Listing
$insert = $conn->query("
    INSERT INTO energy_listings 
    (
        user_id,
        date,
        time_block,
        units_available,
        remaining_units,
        price,
        status
    )
    VALUES
    (
        $user_id,
        '$date',
        '$time_block',
        $units,
        $units,
        $price,
        'available'
    )
");

if ($insert) {
    echo json_encode([
        "status" => "success",
        "message" => "Energy listed successfully"
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Database Error: " . $conn->error
    ]);
}
?>