<?php
session_start();
include '../frontend/includes/config.php';

$utility = $_POST['utility_charge'];
$platform = $_POST['platform_charge'];

// Update settings
$conn->query("UPDATE settings SET 
    utility_charge='$utility',
    platform_charge='$platform'
    WHERE id=1
");

echo json_encode([
    "status" => "success",
    "message" => "Charges updated successfully"
]);