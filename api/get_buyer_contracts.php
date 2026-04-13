<?php
session_start();
header('Content-Type: application/json');
include '../frontend/includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'buyer') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$buyer_id = $_SESSION['user_id'];

// Get all contracts for buyer
$sql = "SELECT 
    c.*,
    u.name as seller_name,
    u.email as seller_email
FROM contracts c
JOIN users u ON c.seller_id = u.id
WHERE c.buyer_id = ?
ORDER BY c.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $buyer_id);
$stmt->execute();
$result = $stmt->get_result();

$contracts = [];
while ($row = $result->fetch_assoc()) {
    $contracts[] = $row;
}

echo json_encode([
    'success' => true,
    'contracts' => $contracts
]);

$stmt->close();
$conn->close();
?>
