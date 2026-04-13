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

$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid match"
    ]);
    exit();
}

$conn->begin_transaction();

try {

    // Match fetch
    $match = $conn->query("
        SELECT * 
        FROM match_suggestions 
        WHERE id = $id AND status = 'pending'
    ")->fetch_assoc();

    if (!$match) {
        throw new Exception("Match not found or already completed");
    }

    $buyer_id  = (int)$match['buyer_id'];
    $seller_id = (int)$match['seller_id'];
    $units     = (float)$match['units'];
    $price     = (float)$match['price'];

    // Listing data
    $listing = $conn->query("
        SELECT id, date, time_block, remaining_units 
        FROM energy_listings 
        WHERE id = {$match['listing_id']}
    ")->fetch_assoc();

    if (!$listing) {
        throw new Exception("Listing not found");
    }

    if ((float)$listing['remaining_units'] < $units) {
        throw new Exception("Seller doesn't have enough units");
    }

    $trade_date = $listing['date'];
    $time_block = $listing['time_block'];

    // Charges
    $settings = $conn->query("SELECT * FROM settings LIMIT 1")->fetch_assoc();

    $utility_charge_rate = (float)($settings['utility_charge'] ?? 0.02);
    $platform_charge_rate = (float)($settings['platform_charge'] ?? 2);

    $energyCost = $units * $price;
    $utility = $units * $utility_charge_rate;
    $platform = $units * $platform_charge_rate;
    $total = $energyCost + $utility + $platform;
    $netAmount = $energyCost;

    // Buyer wallet
    $wallet = $conn->query("
        SELECT balance, blocked_balance 
        FROM wallet 
        WHERE user_id = $buyer_id
    ")->fetch_assoc();

    if (!$wallet) {
        throw new Exception("Buyer wallet not found");
    }

    if ((float)$wallet['blocked_balance'] < $total) {
        throw new Exception("Blocked balance insufficient");
    }

    // ✅ Buyer blocked amount reduce only
    $conn->query("
        UPDATE wallet 
        SET blocked_balance = blocked_balance - $total
        WHERE user_id = $buyer_id
    ");

    // ✅ Seller credit
    $conn->query("
        UPDATE wallet 
        SET balance = balance + $netAmount
        WHERE user_id = $seller_id
    ");

    // Insert trade
    $conn->query("
        INSERT INTO trades 
        (
            buyer_id,
            seller_id,
            units,
            price,
            total_amount,
            utility_charge,
            platform_charge,
            net_amount,
            date,
            time_block,
            trade_time,
            status
        )
        VALUES
        (
            $buyer_id,
            $seller_id,
            $units,
            $price,
            $total,
            $utility,
            $platform,
            $netAmount,
            '$trade_date',
            '$time_block',
            NOW(),
            'completed'
        )
    ");

    $trade_id = $conn->insert_id;

    // Token transfer
    $tx_out = "0x" . bin2hex(random_bytes(16));
    $tx_in = "0x" . bin2hex(random_bytes(16));

    $conn->query("
        INSERT INTO token_ledger
        (
            user_id, from_user_id, to_user_id, trade_id, listing_id,
            token_units, token_type, date, time_block, tx_hash, remarks
        )
        VALUES
        (
            $seller_id, $seller_id, $buyer_id, $trade_id, {$match['listing_id']},
            $units, 'transfer_out', '$trade_date', '$time_block', '$tx_out',
            'Transferred to buyer'
        )
    ");

    $conn->query("
        INSERT INTO token_ledger
        (
            user_id, from_user_id, to_user_id, trade_id, listing_id,
            token_units, token_type, date, time_block, tx_hash, remarks
        )
        VALUES
        (
            $buyer_id, $seller_id, $buyer_id, $trade_id, {$match['listing_id']},
            $units, 'transfer_in', '$trade_date', '$time_block', '$tx_in',
            'Received from seller'
        )
    ");

    // Update listing
    $conn->query("
        UPDATE energy_listings
        SET remaining_units = remaining_units - $units
        WHERE id = {$match['listing_id']}
    ");

    // Update demand
    $conn->query("
        UPDATE demand_listings
        SET remaining_units = remaining_units - $units
        WHERE id = {$match['demand_id']}
    ");

    // Close completed demand
    $conn->query("
        DELETE FROM demand_listings
        WHERE id = {$match['demand_id']} AND remaining_units <= 0
    ");

    // Seller sold status
    $conn->query("
        UPDATE energy_listings
        SET status = 'sold'
        WHERE id = {$match['listing_id']} AND remaining_units <= 0
    ");

    // Match complete
    $conn->query("
        UPDATE match_suggestions
        SET status = 'completed'
        WHERE id = $id
    ");

    // Wallet logs
    $conn->query("
        INSERT INTO wallet_transactions
        (user_id, type, amount, description)
        VALUES
        ($buyer_id, 'debit', $total, 'Energy purchase')
    ");

    $conn->query("
        INSERT INTO wallet_transactions
        (user_id, type, amount, description)
        VALUES
        ($seller_id, 'credit', $netAmount, 'Energy sold')
    ");

    $conn->commit();

    echo json_encode([
        "status" => "success",
        "message" => "Trade completed successfully"
    ]);

} catch (Exception $e) {

    $conn->rollback();

    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?>