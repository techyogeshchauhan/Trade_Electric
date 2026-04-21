<?php
session_start();
ob_start();
include '../frontend/includes/config.php';

// Get today's date and limit matches
$today = date('Y-m-d');
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 30;

$buyers = $conn->query("
SELECT d.*, u.name as buyer_name 
FROM demand_listings d
JOIN users u ON d.user_id = u.id
WHERE d.remaining_units > 0
AND d.date >= '$today'
ORDER BY d.id DESC
LIMIT $limit
");

$match_count = 0;

while($b = $buyers->fetch_assoc()){
    $date = $b['date'];
    $time = $b['time_block'];
    $max_price = $b['max_price'];

    // Find best seller (cheapest first)
    $seller = $conn->query("
        SELECT e.*, u.name as seller_name 
        FROM energy_listings e
        JOIN users u ON e.user_id = u.id
        WHERE e.date='$date'
        AND REPLACE(e.time_block, ' ', '') = REPLACE('$time', ' ', '')
        AND e.remaining_units > 0
        AND e.price <= $max_price
        ORDER BY e.price ASC
        LIMIT 1
    ");

    if($seller->num_rows > 0){
        $s = $seller->fetch_assoc();
        $units = min($b['remaining_units'], $s['remaining_units']);
        
        // Format date
        $date_obj = new DateTime($date);
        $formatted_date = $date_obj->format('d-m-Y');
        
        // Format values
        $units_formatted = number_format($units, 0);
        $seller_avail = number_format($s['remaining_units'], 0);
        $buyer_need = number_format($b['remaining_units'], 0);
        $price_formatted = number_format($s['price'], 1);

        // Partial or Full Match logic
        if ($units == $b['remaining_units'] && $units == $s['remaining_units']) {
            $status_badge = "<span class='badge bg-success'><i class='bi bi-check-circle'></i> Fully Matched</span>";
        } else {
            $status_badge = "<span class='badge bg-warning text-dark'><i class='bi bi-pie-chart-fill'></i> Partial Match</span>";
        }

        echo "<tr>
            <td>$formatted_date</td>
            <td>$time</td>
            <td><strong>$units_formatted kWh</strong></td>
            <td>{$s['seller_name']}</td>
            <td><span class='text-success fw-bold'>$seller_avail kWh</span></td>
            <td>{$b['buyer_name']}</td>
            <td><span class='text-primary fw-bold'>$buyer_need kWh</span></td>
            <td>₹$price_formatted</td>
            <td>$status_badge</td>
        </tr>";
        
        $match_count++;
    }
}

if($match_count == 0) {
    echo "<tr><td colspan='9' class='text-center py-3 text-muted'>
            <i class='bi bi-info-circle'></i> No matches found. Add more listings or demands.
          </td></tr>";
}

ob_end_flush();
?>