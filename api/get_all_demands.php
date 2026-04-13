<?php
ob_start();
include '../frontend/includes/config.php';

// Get today's date and limit to recent data
$today = date('Y-m-d');
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;

$sql = "SELECT d.*, u.name as buyer_name 
        FROM demand_listings d 
        JOIN users u ON d.user_id = u.id 
        WHERE d.remaining_units > 0
        AND u.role = 'buyer'
        AND d.date >= '$today'
        ORDER BY d.date ASC, d.time_block ASC, d.max_price DESC
        LIMIT $limit";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        // Format date properly
        $date_obj = new DateTime($row['date']);
        $date = $date_obj->format('d-m-Y');
        
        // Format time block
        $time_block = $row['time_block'];
        
        // Format price to 1 decimal
        $max_price = number_format($row['max_price'], 1);
        
        // Format units
        $units = number_format($row['units_required'], 0);
        $remaining = number_format($row['remaining_units'], 0);
        
        echo "<tr>
                <td>$date</td>
                <td>$time_block</td>
                <td><strong>{$row['buyer_name']}</strong></td>
                <td>$units kWh</td>
                <td>₹$max_price</td>
                <td>$remaining kWh</td>
              </tr>";
    }
} else {
    echo "<tr><td colspan='6' class='text-center py-3 text-muted'>
            <i class='bi bi-inbox'></i> No buyer demands available
          </td></tr>";
}

ob_end_flush();
?>