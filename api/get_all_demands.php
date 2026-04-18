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

$sql = "SELECT d.*, u.name as buyer_name 
        FROM demand_listings d 
        JOIN users u ON d.user_id = u.id 
        WHERE d.remaining_units > 0
        AND u.role = 'buyer'
        AND d.date >= '$today'
        ORDER BY d.id DESC
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
        
        // Format time block
        $time_block = $row['time_block'];
        
        // Format price to 1 decimal
        $max_price = number_format($row['max_price'], 1);
        
        // Format units
        $units = number_format($row['units_required'], 0);
        $remaining = number_format($row['remaining_units'], 0);
        
        // Action button - only for sellers
        $action_btn = '';
        if ($current_user_role === 'seller' && $row['user_id'] != $current_user_id) {
            $action_btn = "<button class='btn-match' onclick='matchNow({$row['id']})'>Match</button>";
        } else if ($current_user_role === 'buyer' && $row['user_id'] == $current_user_id) {
            $action_btn = "<span class='badge-available'>Available</span>";
        } else {
            $action_btn = "<span class='badge-available'>Available</span>";
        }
        
        echo "<tr>
                <td>$date</td>
                <td>$time_block</td>
                <td><strong>{$row['buyer_name']}</strong></td>
                <td>$units kWh</td>
                <td>₹$max_price</td>
                <td>$remaining kWh</td>
                <td>$action_btn</td>
              </tr>";
    }
} else {
    echo "<tr><td colspan='7' class='text-center py-3 text-muted'>
            <i class='bi bi-inbox'></i> No buyer demands available
          </td></tr>";
}

ob_end_flush();
?>