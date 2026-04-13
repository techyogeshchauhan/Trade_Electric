<?php
ob_start();
session_start();

header('Content-Type: application/json');

include '../frontend/includes/config.php';

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Please login first');
    }
    
    $buyer_id = $_SESSION['user_id'];
    $listing_id = intval($_POST['listing_id'] ?? 0);
    $units = floatval($_POST['units'] ?? 0);
    
    if (!$listing_id || !$units) {
        throw new Exception('Missing listing ID or units');
    }
    
    // Get listing details
    $listing = $conn->query("
        SELECT e.*, u.name as seller_name 
        FROM energy_listings e 
        JOIN users u ON e.user_id = u.id 
        WHERE e.id = $listing_id
    ");
    
    if (!$listing || $listing->num_rows == 0) {
        throw new Exception('Listing not found');
    }
    
    $listing_data = $listing->fetch_assoc();
    $seller_id = $listing_data['user_id'];
    $date = $listing_data['date'];
    $time_block = $listing_data['time_block'];
    $price_per_unit = $listing_data['price'];
    $available_units = $listing_data['remaining_units'];
    
    // Check if enough units available
    if ($units > $available_units) {
        throw new Exception("Only $available_units kWh available");
    }
    
    // Calculate amounts
    $total_amount = $units * $price_per_unit;
    $platform_fee = $units * 0.25;
    $utility_fee = $units * 0.02;
    
    // Generate contract ID
    $contract_id = 'CNT-' . date('Ymd') . '-' . rand(1000, 9999);
    
    // Find or create demand listing
    $demand = $conn->query("
        SELECT id FROM demand_listings 
        WHERE user_id = $buyer_id 
        AND date = '$date' 
        AND time_block = '$time_block'
        AND remaining_units > 0
        LIMIT 1
    ");
    
    if ($demand && $demand->num_rows > 0) {
        $demand_id = $demand->fetch_assoc()['id'];
    } else {
        // Create demand automatically
        $conn->query("
            INSERT INTO demand_listings (user_id, date, time_block, units_required, remaining_units, max_price, status)
            VALUES ($buyer_id, '$date', '$time_block', $units, $units, $price_per_unit, 'active')
        ");
        $demand_id = $conn->insert_id;
    }
    
    // Insert contract
    $sql = "INSERT INTO contracts (
        contract_id, buyer_id, seller_id, listing_id, demand_id,
        date, time_block, units, price_per_unit, total_amount,
        platform_fee, utility_fee, status, created_at
    ) VALUES (
        '$contract_id', $buyer_id, $seller_id, $listing_id, $demand_id,
        '$date', '$time_block', $units, $price_per_unit, $total_amount,
        $platform_fee, $utility_fee, 'pending', NOW()
    )";
    
    if (!$conn->query($sql)) {
        throw new Exception('Database error: ' . $conn->error);
    }
    
    ob_end_clean();
    
    echo json_encode([
        'success' => true,
        'contract_id' => $contract_id,
        'message' => 'Contract created successfully'
    ]);
    
} catch (Exception $e) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
