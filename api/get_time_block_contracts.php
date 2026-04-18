<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'seller') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

include '../frontend/includes/config.php';

$user_id = $_SESSION['user_id'];
$date = $_GET['date'] ?? date('Y-m-d');
$time_block = $_GET['time_block'] ?? '';

if (empty($time_block)) {
    echo json_encode(['success' => false, 'message' => 'Time block required']);
    exit();
}

try {
    // Get contracts for this time block
    $stmt = $conn->prepare("SELECT c.*, u.name as buyer_name 
                           FROM contracts c 
                           JOIN users u ON c.buyer_id = u.id 
                           WHERE c.seller_id = ? AND c.date = ? AND c.time_block = ?
                           ORDER BY c.created_at DESC");
    $stmt->bind_param("iss", $user_id, $date, $time_block);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $contracts = [];
    $total_units = 0;
    
    while ($row = $result->fetch_assoc()) {
        $contracts[] = $row;
        $total_units += $row['units'];
    }
    
    echo json_encode([
        'success' => true,
        'contracts' => $contracts,
        'total_units' => $total_units,
        'has_contracts' => count($contracts) > 0
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
