<?php
session_start();
include '../frontend/includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'seller') {
    die("Access denied");
}

$user_id = $_SESSION['user_id'];
$date = $_POST['date'];

foreach($_POST['generated'] as $block => $generated) {
    $self = $_POST['self'][$block] ?? 0;
    $sold = $_POST['sold'][$block] ?? 0;

    $generated = (float)$generated;
    $self = (float)$self;
    $sold = (float)$sold;

    $conn->query("INSERT INTO prosumer_meter_data 
        (user_id, date, time_block, generated_kwh, self_consumed_kwh, sold_kwh) 
        VALUES ($user_id, '$date', '$block', $generated, $self, $sold)
        ON DUPLICATE KEY UPDATE 
        generated_kwh = $generated,
        self_consumed_kwh = $self,
        sold_kwh = $sold");
}

echo "All 96 blocks data saved successfully!";
header("Location: ../seller/meter_report.php");
exit();
?>