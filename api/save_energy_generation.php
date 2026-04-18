<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'seller') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

include '../frontend/includes/config.php';

$user_id = $_SESSION['user_id'];
$time_block = $_POST['time_block'] ?? '';
$generated_kwh = floatval($_POST['generated_kwh'] ?? 0);
$self_consumed_kwh = floatval($_POST['self_consumed_kwh'] ?? 0);
$date = $_POST['date'] ?? date('Y-m-d');

// Validation
if (empty($time_block) || $generated_kwh < 0 || $self_consumed_kwh < 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid input data']);
    exit();
}

if ($self_consumed_kwh > $generated_kwh) {
    echo json_encode(['success' => false, 'message' => 'Self consumed cannot be more than generated']);
    exit();
}

// Calculate available energy
$sold_kwh = $generated_kwh - $self_consumed_kwh;

try {
    // Check if entry already exists
    $check = $conn->prepare("SELECT id FROM prosumer_meter_data WHERE user_id = ? AND date = ? AND time_block = ?");
    $check->bind_param("iss", $user_id, $date, $time_block);
    $check->execute();
    $result = $check->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing entry
        $stmt = $conn->prepare("UPDATE prosumer_meter_data 
                                SET generated_kwh = ?, self_consumed_kwh = ?, sold_kwh = ?, updated_at = NOW() 
                                WHERE user_id = ? AND date = ? AND time_block = ?");
        $stmt->bind_param("dddiis", $generated_kwh, $self_consumed_kwh, $sold_kwh, $user_id, $date, $time_block);
    } else {
        // Insert new entry
        $stmt = $conn->prepare("INSERT INTO prosumer_meter_data 
                                (user_id, date, time_block, generated_kwh, self_consumed_kwh, sold_kwh, created_at) 
                                VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("issddd", $user_id, $date, $time_block, $generated_kwh, $self_consumed_kwh, $sold_kwh);
    }
    
    if ($stmt->execute()) {
        // Check if there are contracts for this time block that need energy
        $contract_check = $conn->prepare("SELECT contract_id, units, buyer_id, seller_id, price_per_unit, total_amount 
                                          FROM contracts 
                                          WHERE seller_id = ? AND date = ? AND time_block = ? 
                                          AND status IN ('pending', 'confirmed')
                                          AND energy_filled = 0");
        $contract_check->bind_param("iss", $user_id, $date, $time_block);
        $contract_check->execute();
        $contracts = $contract_check->get_result();
        
        $contracts_updated = 0;
        while ($contract = $contracts->fetch_assoc()) {
            // Update contract: mark energy as filled and change trade status
            $update_contract = $conn->prepare("UPDATE contracts 
                                              SET energy_filled = 1, 
                                                  trade_status = 'fully_sold',
                                                  energy_filled_at = NOW()
                                              WHERE contract_id = ?");
            $update_contract->bind_param("s", $contract['contract_id']);
            $update_contract->execute();
            $contracts_updated++;
            
            // TODO: Generate tokens and credit wallet (will be done in next step)
            // For now, just mark that energy is available
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Energy data saved successfully',
            'contracts_updated' => $contracts_updated,
            'available_energy' => $sold_kwh,
            'status_changed' => $contracts_updated > 0 ? 'to_be_traded → fully_sold' : 'no contracts'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save energy data']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
