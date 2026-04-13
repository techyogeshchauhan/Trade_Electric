<?php
session_start();
header('Content-Type: application/json');
include '../frontend/includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'seller') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$seller_id = $_SESSION['user_id'];

// Get all pending contracts for seller
$sql = "SELECT 
    c.*,
    u.name as buyer_name,
    u.email as buyer_email
FROM contracts c
JOIN users u ON c.buyer_id = u.id
WHERE c.seller_id = ? AND c.status = 'pending'
ORDER BY c.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $seller_id);
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
