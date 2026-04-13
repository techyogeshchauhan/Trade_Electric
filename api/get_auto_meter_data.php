<?php
// api/get_auto_meter_data.php - Current Time tak automatic data
session_start();
include '../frontend/includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'seller') {
    echo "<tr><td colspan='6' class='text-center py-4 text-danger'>Access denied.</td></tr>";
    exit();
}

$user_id = $_SESSION['user_id'];
$currentDate = date('Y-m-d');
$currentHour = (int)date('H');
$currentMin  = (int)date('i');

// Current block tak kitne blocks hain
$currentBlockIndex = ($currentHour * 4) + floor($currentMin / 15);

// Last 7 days tak data generate karo, lekin sirf current block tak
for ($d = 0; $d < 7; $d++) {
    $loopDate = date('Y-m-d', strtotime("-$d days"));
    
    for ($b = 0; $b <= $currentBlockIndex; $b++) {   // Sirf current block tak
        $hour = floor($b / 4);
        $min  = ($b % 4) * 15;
        $start = sprintf("%02d:%02d", $hour, $min);
        $end   = sprintf("%02d:%02d", $hour, $min + 15);
        if ($end == "24:00") $end = "00:00";
        $time_block = "$start-$end";

        // Agar data already hai toh skip (ek baar jo aa gaya woh fixed rahe)
        $check = $conn->query("SELECT id FROM prosumer_meter_data 
                              WHERE user_id = $user_id 
                              AND date = '$loopDate' 
                              AND time_block = '$time_block'");

        if ($check->num_rows == 0) {
            $generated = rand(4, 28) + (rand(0,99)/100);   // realistic solar
            $self = rand(25, 75) / 100 * $generated;
            $sold = 0; // trade hone pe update hoga

            $conn->query("INSERT INTO prosumer_meter_data 
                (user_id, date, time_block, generated_kwh, self_consumed_kwh, sold_kwh)
                VALUES ($user_id, '$loopDate', '$time_block', $generated, $self, $sold)");
        }
    }
}

// Fetch only up to current block
$result = $conn->query("
    SELECT m.*, COALESCE(t.token_id, '-') as token_id 
    FROM prosumer_meter_data m
    LEFT JOIN trades t ON m.date = t.date AND m.time_block = t.time_block 
        AND t.seller_id = $user_id
    WHERE m.user_id = $user_id 
      AND m.date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    ORDER BY m.date DESC, m.time_block ASC
");

if ($result->num_rows == 0) {
    echo "<tr><td colspan='6' class='text-center py-4 text-muted'>Waiting for meter data...</td></tr>";
    exit;
}

while($row = $result->fetch_assoc()) {
    echo "<tr>
            <td>{$row['date']}</td>
            <td>{$row['time_block']}</td>
            <td><strong>" . number_format($row['generated_kwh'], 2) . "</strong></td>
            <td>" . number_format($row['self_consumed_kwh'], 2) . "</td>
            <td>" . number_format($row['sold_kwh'], 2) . "</td>
            <td><span class='badge bg-primary'>" . htmlspecialchars($row['token_id']) . "</span></td>
          </tr>";
}
?>