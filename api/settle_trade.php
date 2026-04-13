<?php
session_start();
include '../frontend/includes/config.php';

if($_SESSION['role'] != 'admin'){
    echo json_encode(["error" => "Unauthorized"]);
    exit();
}

 $trade_id = (int)$_POST['trade_id'];

// Trade fetch
 $trade = $conn->query("SELECT * FROM trades WHERE id = $trade_id")->fetch_assoc();

if(!$trade){
    echo json_encode(["error" => "Trade not found"]);
    exit();
}

 $status = $trade['settlement_status'] ?? 'pending';

if($status == 'settled'){
    echo json_encode(["error" => "Already settled"]);
    exit();
}

 $buyer_id = $trade['buyer_id'];
 $seller_id = $trade['seller_id'];
 $units = (float)$trade['units'];

// Get date and time_block from trade or listing
 $trade_date = $trade['date'];
 $trade_time = $trade['time_block'];

if(empty($trade_date)){
    // Try to get from listing via token_ledger
    $tl = $conn->query("SELECT date, time_block FROM token_ledger WHERE trade_id = $trade_id AND token_type = 'transfer_in' LIMIT 1")->fetch_assoc();
    $trade_date = $tl['date'] ?? date('Y-m-d');
    $trade_time = $tl['time_block'] ?? 'N/A';
}

// Check if already burned
 $alreadyBurned = $conn->query("SELECT id FROM token_ledger WHERE trade_id = $trade_id AND token_type = 'burn'")->num_rows;

if($alreadyBurned > 0){
    // Just update status
    $conn->query("UPDATE trades SET settlement_status = 'settled' WHERE id = $trade_id");
    echo json_encode(["success" => true, "message" => "Already burned, status updated"]);
    exit();
}

// BURN TOKEN
 $tx_hash = "0x" . bin2hex(random_bytes(16));

 $burn = $conn->query("
    INSERT INTO token_ledger
    (user_id, from_user_id, trade_id, token_units, token_type, date, time_block, tx_hash, remarks)
    VALUES (
        $buyer_id,
        $buyer_id,
        $trade_id,
        $units,
        'burn',
        '$trade_date',
        '$trade_time',
        '$tx_hash',
        'Energy consumed — token burned after settlement'
    )
");

if(!$burn){
    echo json_encode(["error" => "Burn failed: " . $conn->error]);
    exit();
}

// UPDATE TRADE STATUS
 $conn->query("UPDATE trades SET settlement_status = 'settled' WHERE id = $trade_id");

echo json_encode(["success" => true, "message" => "Trade settled, $units tokens burned"]);
?>