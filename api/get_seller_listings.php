<?php
session_start();
include '../frontend/includes/config.php';

if(!isset($_SESSION['user_id'])){
    exit("Unauthorized");
}

$user_id = $_SESSION['user_id'];

$query = "
    SELECT date, time_block, units_available, remaining_units, price, status 
    FROM energy_listings 
    WHERE user_id = $user_id 
    AND remaining_units > 0
    ORDER BY date DESC, time_block ASC
";

$result = $conn->query($query);

if(!$result){
    die("SQL Error: " . $conn->error);
}

if($result->num_rows > 0){
   while($row = $result->fetch_assoc()) {
        $formatted_date = date('d-m-Y', strtotime($row['date']));
        $units = number_format($row['units_available'], 0);
        $remaining = number_format($row['remaining_units'], 0);
        $price = number_format($row['price'], 1);
        $status = ucfirst($row['status']);
        
        $statusBadge = '';
        if ($row['status'] == 'active') {
            $statusBadge = "<span class='badge bg-success'>Active</span>";
        } else if ($row['status'] == 'matched') {
            $statusBadge = "<span class='badge bg-primary'>Matched</span>";
        } else {
            $statusBadge = "<span class='badge bg-secondary'>$status</span>";
        }

        echo "<tr>
                <td>$formatted_date</td>
                <td>{$row['time_block']}</td>
                <td>$units kWh</td>
                <td>$remaining kWh</td>
                <td>₹$price</td>
                <td>$statusBadge</td>
            </tr>";
    }
} else {
    echo "<tr>
            <td colspan='6' class='text-center text-muted py-4'>
                <i class='bi bi-inbox' style='font-size: 48px; opacity: 0.3;'></i>
                <p class='mt-2'>No active listings</p>
            </td>
        </tr>";
}
?>