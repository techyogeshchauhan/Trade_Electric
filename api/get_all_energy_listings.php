<?php
session_start();
ob_start();
include '../frontend/includes/config.php';

// Get current user role
$current_user_role = $_SESSION['role'] ?? '';
$current_user_id = $_SESSION['user_id'] ?? 0;

// Get today's date and current time
$today = date('Y-m-d');
$current_time = date('H:i');
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;

$sql = "SELECT e.*, u.name as seller_name 
        FROM energy_listings e 
        JOIN users u ON e.user_id = u.id 
        WHERE e.remaining_units > 0
        AND u.role = 'seller'
        AND e.date >= '$today'
        ORDER BY e.id DESC
        LIMIT $limit";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        // Skip past time blocks for current date
        if ($row['date'] === $today) {
            $time_parts = explode('-', $row['time_block']);
            $block_end_time = trim($time_parts[1] ?? '');
            if ($block_end_time && $block_end_time <= $current_time) {
                continue; // Skip this past time block
            }
        }
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
        
        // Action button - only for buyers
        $action_btn = '';
        if ($current_user_role === 'buyer' && $row['user_id'] != $current_user_id) {
            $action_btn = "<button class='btn-bid' onclick='placeBid({$row['id']})'>Listed</button>";
        } else if ($current_user_role === 'seller' && $row['user_id'] == $current_user_id) {
            $action_btn = "<span class='badge-listed'>Listed</span>";
        } else {
            $action_btn = "<span class='badge-listed'>Listed</span>";
        }
        
        echo "<tr>
                <td>$date</td>
                <td>$time_block</td>
                <td><strong>{$row['seller_name']}</strong></td>
                <td>$units kWh</td>
                <td>₹$price</td>
                <td>$remaining kWh</td>
                <td>$action_btn</td>
              </tr>";
    }
} else {
    echo "<tr><td colspan='7' class='text-center py-3 text-muted'>
            <i class='bi bi-inbox'></i> No energy listings available
          </td></tr>";
}

ob_end_flush();
?>