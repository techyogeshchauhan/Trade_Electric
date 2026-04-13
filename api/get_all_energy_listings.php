<?php
ob_start();
include '../frontend/includes/config.php';

// Get today's date and limit to recent data
$today = date('Y-m-d');
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;

$sql = "SELECT e.*, u.name as seller_name 
        FROM energy_listings e 
        JOIN users u ON e.user_id = u.id 
        WHERE e.remaining_units > 0
        AND u.role = 'seller'
        AND e.date >= '$today'
        ORDER BY e.date ASC, e.time_block ASC, e.price ASC
        LIMIT $limit";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        // Format date properly
        $date_obj = new DateTime($row['date']);
        $date = $date_obj->format('d-m-Y');
        
        // Format time block (remove seconds if present)
        $time_block = $row['time_block'];
        
        // Format price to 1 decimal
        $price = number_format($row['price'], 1);
        
        // Format units
        $units = number_format($row['units_available'], 0);
        $remaining = number_format($row['remaining_units'], 0);
        
        echo "<tr>
                <td>$date</td>
                <td>$time_block</td>
                <td><strong>{$row['seller_name']}</strong></td>
                <td>$units kWh</td>
                <td>₹$price</td>
                <td>$remaining kWh</td>
              </tr>";
    }
} else {
    echo "<tr><td colspan='6' class='text-center py-3 text-muted'>
            <i class='bi bi-inbox'></i> No energy listings available
          </td></tr>";
}

ob_end_flush();
?>