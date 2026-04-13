<?php
// api/auto_meter_fill.php - Automatic Meter Data Generator
session_start();
include '../frontend/includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'seller') {
    exit("Access denied");
}

$user_id = $_SESSION['user_id'];
$date = date('Y-m-d');

// Current 15-min block calculate karo
$currentHour = (int)date('H');
$currentMin  = (int)date('i');
$blockStartMin = floor($currentMin / 15) * 15;

$start = sprintf("%02d:%02d", $currentHour, $blockStartMin);
$end   = sprintf("%02d:%02d", $currentHour, $blockStartMin + 15);
if ($end == "24:00") $end = "00:00";

$time_block = "$start-$end";

// Realistic solar generation (2 to 25 kWh per block)
$generated = rand(3, 22) + (rand(0,99)/100); 
$self_consumed = rand(25, 75) / 100 * $generated;   // 25% to 75% self use
$sold = 0; // abhi trade nahi hui toh 0

// Insert ya Update
$conn->query("INSERT INTO prosumer_meter_data 
    (user_id, date, time_block, generated_kwh, self_consumed_kwh, sold_kwh)
    VALUES ($user_id, '$date', '$time_block', $generated, $self_consumed, $sold)
    ON DUPLICATE KEY UPDATE 
        generated_kwh = VALUES(generated_kwh),
        self_consumed_kwh = VALUES(self_consumed_kwh),
        sold_kwh = VALUES(sold_kwh)");

echo "✅ Auto-filled meter data for block: $time_block | Generated: " . number_format($generated,2) . " kWh";
?>