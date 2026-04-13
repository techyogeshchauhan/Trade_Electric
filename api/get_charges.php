<?php
session_start();
include '../frontend/includes/config.php';

if ($_SESSION['role'] != 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Access denied']);
    exit();
}

$utility_buyer   = (float)$_POST['utility_charge_buyer'];
$utility_seller  = (float)$_POST['utility_charge_seller'];
$platform_charge = (float)$_POST['platform_charge'];

$conn->query("UPDATE settings SET 
    utility_charge_buyer = $utility_buyer,
    utility_charge_seller = $utility_seller,
    platform_charge = $platform_charge 
    LIMIT 1");

if ($conn->affected_rows > 0) {
    echo json_encode(['status' => 'success', 'message' => 'Charges updated successfully!']);
} else {
    echo json_encode(['status' => 'success', 'message' => 'No changes made or settings already same.']);
}
?>