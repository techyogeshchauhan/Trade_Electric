<?php
/**
 * Create Contract API - Version 2 (With Better Error Handling)
 */

// Start output buffering to catch any unexpected output
ob_start();

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Start session
session_start();

// Set JSON header
header('Content-Type: application/json');

try {
    // Include config
    if (!file_exists('../frontend/includes/config.php')) {
        throw new Exception('Config file not found');
    }
    
    include '../frontend/includes/config.php';
    
    // Check database connection
    if (!$conn || $conn->connect_error) {
        throw new Exception('Database connection failed: ' . ($conn ? $conn->connect_error : 'No connection'));
    }
    
    // Check request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    // Get POST data
    $buyer_id = $_POST['buyer_id'] ?? null;
    $seller_id = $_POST['seller_id'] ?? null;
    $listing_id = $_POST['listing_id'] ?? null;
    $demand_id = $_POST['demand_id'] ?? null;
    $units = $_POST['units'] ?? null;
    $price_per_unit = $_POST['price_per_unit'] ?? null;
    $date = $_POST['date'] ?? null;
    $time_block = $_POST['time_block'] ?? null;
    
    // Validate time block format (from-to)
    if ($time_block && strpos($time_block, '-') !== false) {
        list($from_time, $to_time) = explode('-', $time_block);
        $from_time = trim($from_time);
        $to_time = trim($to_time);
        
        // Convert to comparable format (remove colons)
        $from_numeric = (int)str_replace(':', '', $from_time);
        $to_numeric = (int)str_replace(':', '', $to_time);
        
        // Validate: from time should be less than to time
        if ($from_numeric >= $to_numeric) {
            throw new Exception('Invalid time block: Start time must be before end time');
        }
    }
    
    // If demand_id is 0 or null, try to find or create matching demand
    if (!$demand_id || $demand_id == 0) {
        $find_demand = $conn->query("
            SELECT id FROM demand_listings 
            WHERE user_id = $buyer_id 
            AND date = '$date' 
            AND time_block = '$time_block'
            AND remaining_units > 0
            ORDER BY id DESC 
            LIMIT 1
        ");
        
        if ($find_demand && $find_demand->num_rows > 0) {
            $demand_id = $find_demand->fetch_assoc()['id'];
        } else {
            // Create demand automatically
            $conn->query("
                INSERT INTO demand_listings (user_id, date, time_block, units_required, remaining_units, max_price)
                VALUES ($buyer_id, '$date', '$time_block', $units, $units, $price_per_unit)
            ");
            $demand_id = $conn->insert_id;
        }
    }
    
    // Validate required fields
    if (!$buyer_id || !$seller_id || !$listing_id || !$demand_id || !$units || !$price_per_unit || !$date || !$time_block) {
        throw new Exception('Missing required fields');
    }
    
    // Validate numeric values
    if (!is_numeric($units) || !is_numeric($price_per_unit)) {
        throw new Exception('Units and price must be numeric');
    }
    
    // Calculate amounts
    $total_amount = floatval($units) * floatval($price_per_unit);
    $platform_fee = floatval($units) * 0.25;
    $utility_fee = floatval($units) * 0.02;
    $transaction_charge = floatval($units) * 0.14; // KERC compliance
    
    // Generate unique contract ID
    $contract_id = 'CONTRACT-' . date('Ymd-His') . '-' . rand(1000, 9999);
    
    // Prepare SQL with new fields
    $sql = "INSERT INTO contracts (
        contract_id, buyer_id, seller_id, listing_id, demand_id, 
        date, time_block, units, price_per_unit, total_amount, 
        platform_fee, utility_fee, transaction_charge, 
        status, trade_status, energy_filled, tokens_generated, wallet_credited
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'to_be_traded', 0, 0, 0)";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception('Failed to prepare statement: ' . $conn->error);
    }
    
    // Bind parameters (13 values)
    $bind_result = $stmt->bind_param(
        "siiiissdddddd",  // 13 characters for 13 values
        $contract_id,
        $buyer_id,
        $seller_id,
        $listing_id,
        $demand_id,
        $date,
        $time_block,
        $units,
        $price_per_unit,
        $total_amount,
        $platform_fee,
        $utility_fee,
        $transaction_charge
    );
    
    if (!$bind_result) {
        throw new Exception('Failed to bind parameters: ' . $stmt->error);
    }
    
    // Execute
    if (!$stmt->execute()) {
        throw new Exception('Failed to execute: ' . $stmt->error);
    }
    
    // Success!
    $insert_id = $stmt->insert_id;
    
    $stmt->close();
    $conn->close();
    
    // Clear any buffered output
    ob_end_clean();
    
    // Send success response
    echo json_encode([
        'success' => true,
        'message' => 'Contract created successfully',
        'contract_id' => $contract_id,
        'insert_id' => $insert_id
    ]);
    
} catch (Throwable $e) {
    // Clear any buffered output
    ob_end_clean();
    
    // Log error
    error_log('Contract Creation Error: ' . $e->getMessage());
    
    // Send error response
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>
