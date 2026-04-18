<?php
session_start();
include '../frontend/includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'seller') {
    echo json_encode(['error' => 'Only sellers can match now.']);
    exit();
}

 $demand_id = $_POST['demand_id'] ?? 0;
 $match_units = intval($_POST['units'] ?? 0) ?: 1;
 $user_id = $_SESSION['user_id'];

if($demand_id == 0) {
    echo json_encode(['error' => 'Invalid demand.']);
    exit();
}

// Get demand
 $demand = $conn->query("SELECT * FROM demand_listings WHERE id = $demand_id AND remaining_units > 0")->fetch_assoc();

if(!$demand) {
    echo json_encode(['error' => 'Demand not available.']);
    exit();
}

// Find seller's own listing
 $listing = $conn->query("SELECT * FROM energy_listings 
                        WHERE user_id = $user_id 
                          AND date = '{$demand['date']}' 
                          AND time_block = '{$demand['time_block']}'
                          AND remaining_units > 0 
                        LIMIT 1")->fetch_assoc();

if(!$listing) {
    echo json_encode(['error' => "You don't have matching energy in this time block."]);
    exit();
}

 $trade_units = min($listing['remaining_units'], $demand['remaining_units'], $match_units);
 $price = $listing['price'];
 $total = $trade_units * $price;

 $buyer_id = $demand['user_id'];
 $trade_date = $demand['date'];
 $trade_time = $demand['time_block'];

// Check buyer wallet
 $wallet = $conn->query("SELECT balance FROM wallet WHERE user_id = $buyer_id")->fetch_assoc();
if (!$wallet || $wallet['balance'] < $total) {
    echo json_encode(['error' => 'Buyer insufficient balance']);
    exit();
}

// Deduct buyer wallet
 $conn->query("UPDATE wallet SET balance = balance - $total WHERE user_id = $buyer_id");

// ✅ Credit seller wallet (ye pehle missing tha)
 $conn->query("UPDATE wallet SET balance = balance + $total WHERE user_id = $user_id");

// Log wallet transactions
 $conn->query("INSERT INTO wallet_transactions (user_id, type, amount, description) VALUES 
    ($buyer_id, 'debit', $total, 'Energy purchase via match')");

 $conn->query("INSERT INTO wallet_transactions (user_id, type, amount, description) VALUES 
    ($user_id, 'credit', $total, 'Energy sold via match')");

// Create Trade
 $conn->query("INSERT INTO trades (buyer_id, seller_id, units, price, total_amount, date, time_block) 
              VALUES ($buyer_id, $user_id, $trade_units, $price, $total, '$trade_date', '$trade_time')");

 $trade_id = $conn->insert_id;

// ✅ TOKEN TRANSFER
 $tx_out = "0x" . bin2hex(random_bytes(16));
 $tx_in = "0x" . bin2hex(random_bytes(16));

 $conn->query("
    INSERT INTO token_ledger 
    (user_id, from_user_id, to_user_id, trade_id, listing_id, token_units, token_type, date, time_block, tx_hash, remarks)
    VALUES (
        $user_id,
        $user_id,
        $buyer_id,
        $trade_id,
        {$listing['id']},
        $trade_units,
        'transfer_out',
        '$trade_date',
        '$trade_time',
        '$tx_out',
        'Tokens transferred to buyer via match'
    )
");

 $conn->query("
    INSERT INTO token_ledger 
    (user_id, from_user_id, to_user_id, trade_id, listing_id, token_units, token_type, date, time_block, tx_hash, remarks)
    VALUES (
        $buyer_id,
        $user_id,
        $buyer_id,
        $trade_id,
        {$listing['id']},
        $trade_units,
        'transfer_in',
        '$trade_date',
        '$trade_time',
        '$tx_in',
        'Tokens received from seller via match'
    )
");

// Update remaining
 $conn->query("UPDATE energy_listings SET remaining_units = remaining_units - $trade_units WHERE id = {$listing['id']}");
 $conn->query("UPDATE demand_listings SET remaining_units = remaining_units - $trade_units WHERE id = $demand_id");

// Clean up - Delete fully consumed listings
$conn->query("DELETE FROM demand_listings WHERE id = $demand_id AND remaining_units <= 0");
$conn->query("DELETE FROM energy_listings WHERE id = {$listing['id']} AND remaining_units <= 0");

header('Content-Type: application/json');
echo json_encode([
    'status' => 'success',
    'message' => "Matched $trade_units kWh for ₹" . number_format($total, 2),
    'units' => $trade_units,
    'amount' => $total
]);
?>