<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start(); // Must be before header

header('Content-Type: application/json');
include '../frontend/includes/config.php';

// Check if user is logged in as seller
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'seller') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$contract_id = $_POST['contract_id'] ?? null;
$action = $_POST['action'] ?? null; // 'confirm' or 'reject'
$seller_id = $_SESSION['user_id']; // Get from session

if (!$contract_id || !$action) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Start transaction
$conn->begin_transaction();

try {
    // Get contract details
    $stmt = $conn->prepare("SELECT * FROM contracts WHERE contract_id = ? AND seller_id = ? AND status = 'pending'");
    $stmt->bind_param("si", $contract_id, $seller_id);
    $stmt->execute();
    $contract = $stmt->get_result()->fetch_assoc();
    
    if (!$contract) {
        throw new Exception("Contract not found or already processed");
    }
    
    if ($action === 'confirm') {
        // Update contract status to confirmed
        $stmt = $conn->prepare("UPDATE contracts SET status = 'confirmed', confirmed_at = NOW() WHERE contract_id = ?");
        $stmt->bind_param("s", $contract_id);
        $stmt->execute();
        
        // Generate unique TX hash for blockchain simulation
        $tx_hash = 'TX-' . strtoupper(bin2hex(random_bytes(16)));
        
        // Create trade record
        $stmt = $conn->prepare("
            INSERT INTO trades (
                buyer_id, seller_id, listing_id, demand_id, 
                date, time_block, units, price, total_amount, 
                platform_fee, utility_fee, contract_id, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'completed')
        ");
        
        $stmt->bind_param(
            "iiiissddddss",  // FIXED: 12 characters (4i + 2s + 5d + 1s)
            $contract['buyer_id'],
            $contract['seller_id'],
            $contract['listing_id'],
            $contract['demand_id'],
            $contract['date'],
            $contract['time_block'],
            $contract['units'],
            $contract['price_per_unit'],
            $contract['total_amount'],
            $contract['platform_fee'],
            $contract['utility_fee'],
            $contract_id
        );
        $stmt->execute();
        $trade_id = $conn->insert_id;
        
        // Update energy listing - reduce remaining units
        $stmt = $conn->prepare("
            UPDATE energy_listings 
            SET remaining_units = remaining_units - ? 
            WHERE id = ? AND remaining_units >= ?
        ");
        $stmt->bind_param("did", $contract['units'], $contract['listing_id'], $contract['units']);
        $stmt->execute();
        
        if ($stmt->affected_rows === 0) {
            throw new Exception("Insufficient energy units available");
        }
        
        // Update demand listing - reduce remaining units
        $stmt = $conn->prepare("
            UPDATE demand_listings 
            SET remaining_units = remaining_units - ? 
            WHERE id = ? AND remaining_units >= ?
        ");
        $stmt->bind_param("did", $contract['units'], $contract['demand_id'], $contract['units']);
        $stmt->execute();
        
        // Token Transfer: Seller to Buyer (ONLY WHEN TRADE COMPLETES)
        $stmt = $conn->prepare("
            INSERT INTO token_ledger (
                user_id, from_user_id, to_user_id, token_type, token_units, trade_id, 
                date, time_block, tx_hash, remarks
            ) VALUES 
            (?, ?, ?, 'transfer_out', ?, ?, ?, ?, ?, 'Token sold to buyer - Trade completed'),
            (?, ?, ?, 'transfer_in', ?, ?, ?, ?, ?, 'Token purchased from seller - Trade completed')
        ");
        
        $stmt->bind_param(
            "iiiidissiiiidiss",  // 16 chars for 16 vars
            $contract['seller_id'],      // i - seller_id (row 1)
            $contract['seller_id'],      // i - from_user_id (row 1)
            $contract['buyer_id'],       // i - to_user_id (row 1)
            $contract['units'],          // d - token_units (row 1)
            $trade_id,                   // i - trade_id (row 1)
            $contract['date'],           // s - date (row 1)
            $contract['time_block'],     // s - time_block (row 1)
            $tx_hash,                    // s - tx_hash (row 1)
            $contract['buyer_id'],       // i - buyer_id (row 2)
            $contract['seller_id'],      // i - from_user_id (row 2)
            $contract['buyer_id'],       // i - to_user_id (row 2)
            $contract['units'],          // d - token_units (row 2)
            $trade_id,                   // i - trade_id (row 2)
            $contract['date'],           // s - date (row 2)
            $contract['time_block'],     // s - time_block (row 2)
            $tx_hash                     // s - tx_hash (row 2)
        );
        $stmt->execute();
        
        // Update seller wallet (add earnings)
        $net_amount = $contract['total_amount'] - $contract['platform_fee'] - $contract['utility_fee'];
        $stmt = $conn->prepare("
            UPDATE wallet 
            SET balance = balance + ? 
            WHERE user_id = ?
        ");
        $stmt->bind_param("di", $net_amount, $contract['seller_id']);
        $stmt->execute();
        
        // Log seller wallet transaction (CREDIT)
        $seller_desc = "Energy sold - Contract: $contract_id | Units: {$contract['units']} kWh | Rate: ₹{$contract['price_per_unit']}/kWh";
        $stmt = $conn->prepare("
            INSERT INTO wallet_transactions (user_id, type, amount, description, created_at) 
            VALUES (?, 'credit', ?, ?, NOW())
        ");
        $stmt->bind_param("ids", $contract['seller_id'], $net_amount, $seller_desc);
        $stmt->execute();
        
        // Update buyer wallet (deduct payment)
        $total_payment = $contract['total_amount'] + $contract['platform_fee'] + $contract['utility_fee'];
        $stmt = $conn->prepare("
            UPDATE wallet 
            SET balance = balance - ? 
            WHERE user_id = ?
        ");
        $stmt->bind_param("di", $total_payment, $contract['buyer_id']);
        $stmt->execute();
        
        // Log buyer wallet transaction (DEBIT)
        $buyer_desc = "Energy purchased - Contract: $contract_id | Units: {$contract['units']} kWh | Rate: ₹{$contract['price_per_unit']}/kWh | Fees: ₹" . number_format($contract['platform_fee'] + $contract['utility_fee'], 2);
        $stmt = $conn->prepare("
            INSERT INTO wallet_transactions (user_id, type, amount, description, created_at) 
            VALUES (?, 'debit', ?, ?, NOW())
        ");
        $stmt->bind_param("ids", $contract['buyer_id'], $total_payment, $buyer_desc);
        $stmt->execute();
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Contract confirmed and trade executed successfully!',
            'trade_id' => $trade_id,
            'tx_hash' => $tx_hash
        ]);
        
    } elseif ($action === 'reject') {
        // Update contract status to rejected
        $stmt = $conn->prepare("UPDATE contracts SET status = 'rejected', rejected_at = NOW() WHERE contract_id = ?");
        $stmt->bind_param("s", $contract_id);
        $stmt->execute();
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Contract rejected successfully'
        ]);
    } else {
        throw new Exception("Invalid action");
    }
    
} catch (Exception $e) {
    $conn->rollback();
    
    // Log error
    error_log('Contract Confirmation Error: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}

$stmt->close();
$conn->close();
?>
