<?php
/**
 * Refund Unused Energy API
 * Returns blocked funds for demands that weren't matched
 */

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
$role = $_SESSION['role'];

// Only buyers can request refunds
if ($role !== 'buyer') {
    echo json_encode([
        "status" => "error",
        "message" => "Only buyers can request refunds"
    ]);
    exit();
}

// Get demand_id if provided, otherwise process all expired demands
$demand_id = isset($_POST['demand_id']) ? (int)$_POST['demand_id'] : null;

// Start transaction
$conn->begin_transaction();

try {
    // Find demands that are expired or have remaining units
    $where = "user_id = $user_id AND remaining_units > 0";
    
    if ($demand_id) {
        $where .= " AND id = $demand_id";
    } else {
        // Auto-refund for demands older than 24 hours
        $where .= " AND date < CURDATE()";
    }
    
    $demands = $conn->query("
        SELECT * FROM demand_listings 
        WHERE $where
    ");
    
    if (!$demands || $demands->num_rows == 0) {
        throw new Exception("No refundable demands found");
    }
    
    // Fetch charges/settings
    $settings = $conn->query("SELECT * FROM settings LIMIT 1");
    $settingsData = $settings ? $settings->fetch_assoc() : [];
    
    $utility_charge = (float)($settingsData['utility_charge'] ?? 0.02);
    $platform_charge = (float)($settingsData['platform_charge'] ?? 2);
    
    $total_refund = 0;
    $refunded_demands = [];
    
    while ($demand = $demands->fetch_assoc()) {
        $remaining_units = (float)$demand['remaining_units'];
        $price = (float)$demand['max_price'];
        
        // Calculate refund amount (what was blocked)
        $energyCost = $remaining_units * $price;
        $utility = $remaining_units * $utility_charge;
        $platform = $remaining_units * $platform_charge;
        $refund_amount = $energyCost + $utility + $platform;
        
        // Unblock and return to balance
        $updateWallet = $conn->query("
            UPDATE wallet
            SET balance = balance + $refund_amount,
                blocked_balance = blocked_balance - $refund_amount
            WHERE user_id = $user_id
        ");
        
        if (!$updateWallet) {
            throw new Exception("Wallet update failed: " . $conn->error);
        }
        
        // Log wallet transaction
        $description = "Refund for unused energy - Demand ID: {$demand['id']} | Units: $remaining_units kWh | Date: {$demand['date']} | Time: {$demand['time_block']}";
        
        $log = $conn->query("
            INSERT INTO wallet_transactions
            (user_id, type, amount, description)
            VALUES
            ($user_id, 'refund', $refund_amount, '$description')
        ");
        
        if (!$log) {
            throw new Exception("Wallet log failed: " . $conn->error);
        }
        
        // Mark demand as refunded (set remaining_units to 0)
        $updateDemand = $conn->query("
            UPDATE demand_listings
            SET remaining_units = 0,
                status = 'refunded'
            WHERE id = {$demand['id']}
        ");
        
        if (!$updateDemand) {
            throw new Exception("Demand update failed: " . $conn->error);
        }
        
        $total_refund += $refund_amount;
        $refunded_demands[] = [
            'demand_id' => $demand['id'],
            'units' => $remaining_units,
            'amount' => $refund_amount
        ];
    }
    
    $conn->commit();
    
    echo json_encode([
        "status" => "success",
        "message" => "Refund processed successfully",
        "total_refund" => $total_refund,
        "refunded_demands" => $refunded_demands,
        "count" => count($refunded_demands)
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?>
