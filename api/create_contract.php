<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in JSON response
ini_set('log_errors', 1);

session_start(); // Must be before any output

header('Content-Type: application/json');

// Include config
if (!file_exists('../frontend/includes/config.php')) {
    echo json_encode(['success' => false, 'message' => 'Config file not found']);
    exit;
}

include '../frontend/includes/config.php';

// Check database connection
if (!$conn || $conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
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

// If demand_id is 0 or null, try to find matching demand
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
        // Create a demand automatically
        $conn->query("
            INSERT INTO demand_listings (user_id, date, time_block, units_required, remaining_units, max_price)
            VALUES ($buyer_id, '$date', '$time_block', $units, $units, $price_per_unit)
        ");
        $demand_id = $conn->insert_id;
    }
}

// Validate inputs (demand_id can be 0, we'll handle it)
if (!$buyer_id || !$seller_id || !$listing_id || !$units || !$price_per_unit || !$date || !$time_block) {
    echo json_encode([
        'success' => false, 
        'message' => 'Missing required fields',
        'received' => [
            'buyer_id' => $buyer_id,
            'seller_id' => $seller_id,
            'listing_id' => $listing_id,
            'demand_id' => $demand_id,
            'units' => $units,
            'price_per_unit' => $price_per_unit,
            'date' => $date,
            'time_block' => $time_block
        ]
    ]);
    exit;
}

// Validate numeric values
if (!is_numeric($units) || !is_numeric($price_per_unit)) {
    echo json_encode(['success' => false, 'message' => 'Units and price must be numeric']);
    exit;
}

// Calculate amounts with correct fee structure
$total_amount = $units * $price_per_unit;
$platform_fee = $units * 0.25;  // ₹0.25 per unit
$utility_fee = $units * 0.02;   // ₹0.02 per unit

// Generate unique contract ID
$contract_id = 'CONTRACT-' . date('Ymd-His') . '-' . rand(1000, 9999);

// Insert contract
$sql = "INSERT INTO contracts (
    contract_id, buyer_id, seller_id, listing_id, demand_id, 
    date, time_block, units, price_per_unit, total_amount, 
    platform_fee, utility_fee, status
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to prepare statement: ' . $conn->error
    ]);
    exit;
}

$stmt->bind_param(
    "siiiissddddd",  // FIXED: 12 characters (s + 4i + 2s + 5d)
    $contract_id, $buyer_id, $seller_id, $listing_id, $demand_id,
    $date, $time_block, $units, $price_per_unit, $total_amount,
    $platform_fee, $utility_fee
);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'Contract created successfully',
        'contract_id' => $contract_id
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to create contract: ' . $stmt->error,
        'sql_error' => $conn->error
    ]);
}

$stmt->close();
$conn->close();
?>
