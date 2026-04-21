<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../frontend/includes/config.php';

if (!isset($_POST['demand_id'])) {
    echo json_encode(["error" => "Missing demand id"]);
    exit;
}

 $demand_id = (int)$_POST['demand_id'];

// GET DEMAND
 $sql = "SELECT * FROM demand_listings WHERE id = $demand_id";
 $demand_query = $conn->query($sql);

if (!$demand_query || $demand_query->num_rows == 0) {
    echo json_encode(["error" => "Demand not found"]);
    exit;
}

 $demand = $demand_query->fetch_assoc();

 $buyer_id = $demand['user_id'];
 $units_needed = $demand['remaining_units'];
 $max_price = $demand['max_price'];
 $demand_date = $demand['date'];
 $demand_time = $demand['time_block'];

if ($units_needed <= 0) {
    echo json_encode(["error" => "No remaining units in demand"]);
    exit;
}

// FIND MATCHING SELLER
 $listing_query = $conn->query("
    SELECT * FROM energy_listings 
    WHERE price <= $max_price AND remaining_units > 0
    AND date = '$demand_date' AND REPLACE(time_block, ' ', '') = REPLACE('$demand_time', ' ', '')
    ORDER BY price ASC 
    LIMIT 1
");

if (!$listing_query || $listing_query->num_rows == 0) {
    echo json_encode(["error" => "No seller available at this price"]);
    exit;
}

 $listing = $listing_query->fetch_assoc();

 $seller_id = $listing['user_id'];
 $available_units = $listing['remaining_units'];
 $price = $listing['price'];
 $listing_date = $listing['date'];
 $listing_time = $listing['time_block'];

// CALCULATE MATCH
 $units = min($units_needed, $available_units);

// CALCULATIONS
 $energy = $units * $price;
 $buyerUtility = $units * 0.02;
 $sellerUtility = $units * 0.02;
 $platform = $units * 2;

 $buyerTotal = $energy + $buyerUtility + $platform;
 $sellerCredit = $energy - $sellerUtility;

// CHECK BUYER BALANCE
 $wallet_query = $conn->query("SELECT balance FROM wallet WHERE user_id = $buyer_id");

if (!$wallet_query || $wallet_query->num_rows == 0) {
    echo json_encode(["error" => "Buyer wallet not found"]);
    exit;
}

 $wallet = $wallet_query->fetch_assoc();

if ($wallet['balance'] < $buyerTotal) {
    echo json_encode(["error" => "Insufficient balance"]);
    exit;
}

// START TRANSACTION
 $conn->begin_transaction();

try {

    // Buyer debit
    $conn->query("UPDATE wallet SET balance = balance - $buyerTotal WHERE user_id = $buyer_id");

    // Seller credit
    $conn->query("UPDATE wallet SET balance = balance + $sellerCredit WHERE user_id = $seller_id");

    // Admin earning
    $conn->query("UPDATE wallet SET balance = balance + $platform WHERE user_id = 1");

    // UPDATE LISTING
    $conn->query("UPDATE energy_listings SET remaining_units = remaining_units - $units WHERE id = {$listing['id']}");

    // UPDATE DEMAND
    $conn->query("UPDATE demand_listings SET remaining_units = remaining_units - $units WHERE id = $demand_id");

    // INSERT TRADE
    $conn->query("
        INSERT INTO trades 
        (buyer_id, seller_id, units, price, total_amount, date, time_block)
        VALUES (
            $buyer_id,
            $seller_id,
            $units,
            $price,
            $buyerTotal,
            '$listing_date',
            '$listing_time'
        )
    ");

    $trade_id = $conn->insert_id;

    // ✅ TOKEN TRANSFER
    $tx_out = "0x" . bin2hex(random_bytes(16));
    $tx_in = "0x" . bin2hex(random_bytes(16));

    $conn->query("
        INSERT INTO token_ledger 
        (user_id, from_user_id, to_user_id, trade_id, listing_id, token_units, token_type, date, time_block, tx_hash, remarks)
        VALUES (
            $seller_id, $seller_id, $buyer_id, $trade_id, {$listing['id']},
            $units, 'transfer_out', '$listing_date', '$listing_time', '$tx_out',
            'Tokens transferred to buyer'
        )
    ");

    $conn->query("
        INSERT INTO token_ledger 
        (user_id, from_user_id, to_user_id, trade_id, listing_id, token_units, token_type, date, time_block, tx_hash, remarks)
        VALUES (
            $buyer_id, $seller_id, $buyer_id, $trade_id, {$listing['id']},
            $units, 'transfer_in', '$listing_date', '$listing_time', '$tx_in',
            'Tokens received from seller'
        )
    ");

    // WALLET TRANSACTIONS
    $conn->query("
        INSERT INTO wallet_transactions (user_id, type, amount, description) VALUES
        ($buyer_id,'debit',$buyerTotal,'Energy purchase'),
        ($seller_id,'credit',$sellerCredit,'Energy sold'),
        (1,'credit',$platform,'Platform fee')
    ");

    $conn->commit();

    echo json_encode([
        "success" => true,
        "matched_units" => $units,
        "price" => $price
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        "error" => "Transaction failed",
        "details" => $e->getMessage()
    ]);
}
?>