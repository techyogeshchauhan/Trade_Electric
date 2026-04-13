<?php
// Manual script to insert meter data for 15-minute intervals (9 AM to 5 PM)
session_start();
include '../frontend/includes/config.php';

// Check if user is logged in as seller
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'seller') {
    die("Access denied. Please login as seller.");
}

$user_id = $_SESSION['user_id'];
$date = date('Y-m-d'); // Current date

// Define all 15-minute time blocks from 9 AM to 5 PM with realistic solar generation data
$meter_data = [
    // 9:00 AM to 10:00 AM (40% capacity)
    ['09:00-09:15', 2.50, 0.95, 1.55],
    ['09:15-09:30', 2.75, 1.05, 1.70],
    ['09:30-09:45', 3.00, 1.15, 1.85],
    ['09:45-10:00', 3.25, 1.25, 2.00],
    
    // 10:00 AM to 11:00 AM (60% capacity)
    ['10:00-10:15', 3.75, 1.40, 2.35],
    ['10:15-10:30', 4.00, 1.50, 2.50],
    ['10:30-10:45', 4.25, 1.60, 2.65],
    ['10:45-11:00', 4.50, 1.70, 2.80],
    
    // 11:00 AM to 12:00 PM (75% capacity)
    ['11:00-11:15', 4.70, 1.75, 2.95],
    ['11:15-11:30', 4.90, 1.85, 3.05],
    ['11:30-11:45', 5.10, 1.95, 3.15],
    ['11:45-12:00', 5.30, 2.00, 3.30],
    
    // 12:00 PM to 1:00 PM (90% capacity)
    ['12:00-12:15', 5.50, 2.10, 3.40],
    ['12:15-12:30', 5.65, 2.15, 3.50],
    ['12:30-12:45', 5.75, 2.20, 3.55],
    ['12:45-13:00', 5.85, 2.25, 3.60],
    
    // 1:00 PM to 2:00 PM (90% capacity)
    ['13:00-13:15', 5.90, 2.25, 3.65],
    ['13:15-13:30', 6.00, 2.30, 3.70],
    ['13:30-13:45', 6.10, 2.35, 3.75],
    ['13:45-14:00', 6.20, 2.40, 3.80],
    
    // 2:00 PM to 3:00 PM (100% capacity - PEAK TIME)
    ['14:00-14:15', 6.25, 2.40, 3.85],
    ['14:15-14:30', 6.30, 2.45, 3.85],
    ['14:30-14:45', 6.25, 2.40, 3.85],
    ['14:45-15:00', 6.20, 2.35, 3.85],
    
    // 3:00 PM to 4:00 PM (90% capacity)
    ['15:00-15:15', 5.65, 2.15, 3.50],
    ['15:15-15:30', 5.40, 2.05, 3.35],
    ['15:30-15:45', 5.15, 1.95, 3.20],
    ['15:45-16:00', 4.90, 1.85, 3.05],
    
    // 4:00 PM to 5:00 PM (70% capacity)
    ['16:00-16:15', 4.40, 1.65, 2.75],
    ['16:15-16:30', 4.00, 1.50, 2.50],
    ['16:30-16:45', 3.50, 1.30, 2.20],
    ['16:45-17:00', 3.00, 1.15, 1.85]
];

$inserted = 0;
$updated = 0;
$errors = 0;

echo "<h2>Inserting Meter Data for User ID: $user_id</h2>";
echo "<p>Date: $date</p>";
echo "<hr>";

foreach ($meter_data as $data) {
    list($time_block, $generated, $self_consumed, $surplus) = $data;
    
    // Check if record already exists
    $check = $conn->query("SELECT id FROM meter_data 
                          WHERE user_id = $user_id 
                          AND date = '$date' 
                          AND time_block = '$time_block'");
    
    if ($check->num_rows > 0) {
        // Update existing record
        $sql = "UPDATE meter_data 
                SET generated_units = $generated,
                    self_consumption = $self_consumed,
                    surplus_units = $surplus
                WHERE user_id = $user_id 
                AND date = '$date' 
                AND time_block = '$time_block'";
        
        if ($conn->query($sql)) {
            echo "✅ Updated: $time_block<br>";
            $updated++;
        } else {
            echo "❌ Error updating $time_block: " . $conn->error . "<br>";
            $errors++;
        }
    } else {
        // Insert new record
        $sql = "INSERT INTO meter_data 
                (user_id, date, time_block, generated_units, self_consumption, surplus_units, created_at) 
                VALUES 
                ($user_id, '$date', '$time_block', $generated, $self_consumed, $surplus, NOW())";
        
        if ($conn->query($sql)) {
            echo "✅ Inserted: $time_block<br>";
            $inserted++;
        } else {
            echo "❌ Error inserting $time_block: " . $conn->error . "<br>";
            $errors++;
        }
    }
}

echo "<hr>";
echo "<h3>Summary:</h3>";
echo "<p>✅ Inserted: $inserted records</p>";
echo "<p>🔄 Updated: $updated records</p>";
echo "<p>❌ Errors: $errors</p>";
echo "<p><strong>Total: " . ($inserted + $updated) . " records processed</strong></p>";

echo "<hr>";
echo "<p><a href='../frontend/seller/meter_report.php'>View Meter Report</a></p>";
?>
