<?php
session_start();
include '../frontend/includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'seller') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Access denied']);
    exit;
}

$user_id = $_SESSION['user_id'];
$date = date('Y-m-d');

// Get current time
$current_hour = (int)date('H');
$current_minute = (int)date('i');

// Solar generation multiplier based on time
function getSolarMultiplier($hour) {
    if ($hour < 9 || $hour >= 17) return 0; // No solar outside 9 AM - 5 PM
    if ($hour >= 9 && $hour < 10) return 0.4;
    if ($hour >= 10 && $hour < 11) return 0.6;
    if ($hour >= 11 && $hour < 12) return 0.75;
    if ($hour >= 12 && $hour < 14) return 0.9;
    if ($hour >= 14 && $hour < 15) return 1.0; // Peak hour 2-3 PM
    if ($hour >= 15 && $hour < 16) return 0.9;
    if ($hour >= 16 && $hour < 17) return 0.7;
    return 0;
}

$base_generation = 25; // Base kWh capacity per hour
$inserted = 0;
$updated = 0;

// Generate data for all 15-minute blocks from 9 AM to current time
for ($h = 9; $h < 24; $h++) {
    for ($m = 0; $m < 60; $m += 15) {
        // Only generate data up to current time
        if ($h > $current_hour || ($h == $current_hour && $m > $current_minute)) {
            break 2; // Exit both loops
        }
        
        $start = sprintf("%02d:%02d", $h, $m);
        $end_h = $h;
        $end_m = $m + 15;
        if ($end_m >= 60) {
            $end_h++;
            $end_m = 0;
        }
        $end = sprintf("%02d:%02d", $end_h, $end_m);
        $time_block = "$start-$end";
        
        // Calculate generation based on solar multiplier
        $multiplier = getSolarMultiplier($h);
        $generated = ($base_generation / 4) * $multiplier; // Divide by 4 for 15-min blocks
        
        // Add some randomness (±10%)
        $generated = $generated * (0.9 + (rand(0, 200) / 1000));
        
        // Self consumption (35-45%)
        $self_consumed = $generated * (0.35 + (rand(0, 100) / 1000));
        
        // Available to sell
        $available = $generated - $self_consumed;
        
        // Check if record exists
        $check = $conn->query("SELECT id FROM prosumer_meter_data 
                               WHERE user_id = $user_id 
                               AND date = '$date' 
                               AND time_block = '$time_block'");
        
        if ($check->num_rows > 0) {
            // Update existing record
            $stmt = $conn->prepare("UPDATE prosumer_meter_data 
                                    SET generated_kwh = ?, 
                                        self_consumed_kwh = ?, 
                                        sold_kwh = 0 
                                    WHERE user_id = ? 
                                    AND date = ? 
                                    AND time_block = ?");
            $stmt->bind_param("ddiss", $generated, $self_consumed, $user_id, $date, $time_block);
            $stmt->execute();
            $updated++;
        } else {
            // Insert new record
            $stmt = $conn->prepare("INSERT INTO prosumer_meter_data 
                                    (user_id, date, time_block, generated_kwh, self_consumed_kwh, sold_kwh) 
                                    VALUES (?, ?, ?, ?, ?, 0)");
            $stmt->bind_param("issdd", $user_id, $date, $time_block, $generated, $self_consumed);
            $stmt->execute();
            $inserted++;
        }
    }
}

echo json_encode([
    'status' => 'success',
    'message' => "Meter data populated successfully",
    'inserted' => $inserted,
    'updated' => $updated,
    'total' => $inserted + $updated,
    'date' => $date
]);
?>
