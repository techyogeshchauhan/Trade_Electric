<?php
include '../frontend/includes/config.php';

$date = $_GET['date'];

// MAX LIMIT PER SLOT
$max_limit = 100;

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