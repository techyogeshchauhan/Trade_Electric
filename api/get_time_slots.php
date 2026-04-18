<?php
session_start();
include '../frontend/includes/config.php';

$date = $conn->real_escape_string($_GET['date'] ?? date('Y-m-d'));

// Get max limit from settings
$settingsQuery = $conn->query("SELECT max_units_per_slot FROM settings LIMIT 1");
$settingsData = $settingsQuery ? $settingsQuery->fetch_assoc() : null;
$max_limit = $settingsData['max_units_per_slot'] ?? 100;

$slots = [];

// generate 15 min slots
$start = strtotime("00:00");
$end = strtotime("23:45");

for ($time = $start; $time <= $end; $time += 900) {
    $from = date("H:i", $time);
    $to = date("H:i", $time + 900);

    $slot = "$from-$to";

    // check used units
    $res = $conn->query("
        SELECT SUM(units) as total 
        FROM energy_listings 
        WHERE date='$date' AND time_block='$slot'
    ");

    $used = $res->fetch_assoc()['total'] ?? 0;

    if($used < $max_limit){
        $slots[] = $slot;
    }
}

echo json_encode($slots);