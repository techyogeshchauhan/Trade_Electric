<?php
session_start();
include '../frontend/includes/config.php';

if (!isset($_SESSION['user_id'])) {
    echo "<tr><td colspan='8' class='text-danger'>Login required</td></tr>";
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Build WHERE clause based on role
$where = "";
if ($role == 'buyer') {
    $where = "WHERE dl.user_id = $user_id";
} elseif ($role == 'seller') {
    $where = "WHERE el.user_id = $user_id";
}

// Get live matches (demands with matching listings that haven't been contracted yet)
$query = "
    SELECT 
        dl.date,
        dl.time_block,
        dl.remaining_units as demand_units,
        dl.max_price as demand_price,
        el.remaining_units as listing_units,
        el.price as listing_price,
        bu.name as buyer_name,
        su.name as seller_name,
        LEAST(dl.remaining_units, el.remaining_units) as match_units
    FROM demand_listings dl
    INNER JOIN energy_listings el 
        ON dl.date = el.date 
        AND REPLACE(dl.time_block, ' ', '') = REPLACE(el.time_block, ' ', '')
        AND el.price <= dl.max_price
        AND el.remaining_units > 0
        AND el.status IN ('available', 'active')
    INNER JOIN users bu ON dl.user_id = bu.id
    INNER JOIN users su ON el.user_id = su.id
    $where
    AND dl.remaining_units > 0
    ORDER BY dl.id DESC
    LIMIT 50
";

$result = $conn->query($query);

if (!$result) {
    echo "<tr><td colspan='8' class='text-danger'>Query Error: " . htmlspecialchars($conn->error) . "</td></tr>";
    exit();
}

$html = '';
$count = 0;

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $count++;
        $date = date('d-m-Y', strtotime($row['date']));
        $units = number_format($row['match_units'], 2);
        $price = number_format($row['listing_price'], 2);
        $total = number_format($row['match_units'] * $row['listing_price'], 2);
        
        $html .= "<tr>";
        $html .= "<td>$date</td>";
        $html .= "<td>{$row['time_block']}</td>";
        
        if ($role == 'buyer') {
            $html .= "<td>{$row['seller_name']}</td>";
        } elseif ($role == 'seller') {
            $html .= "<td>{$row['buyer_name']}</td>";
        } else {
            $html .= "<td>{$row['buyer_name']}</td>";
            $html .= "<td>{$row['seller_name']}</td>";
        }
        
        $html .= "<td>$units kWh</td>";
        $html .= "<td>₹$price</td>";
        $html .= "<td class='fw-bold text-success'>₹$total</td>";
        $html .= "<td><span class='badge bg-warning text-dark'>Pending Contract</span></td>";
        $html .= "</tr>";
    }
} else {
    $colspan = ($role == 'admin') ? '8' : '7';
    $html = "<tr><td colspan='$colspan' class='text-center text-muted py-4'>
                <i class='bi bi-check-circle' style='font-size: 48px; opacity: 0.3;'></i>
                <p class='mt-2'>No pending matches</p>
                <small>All matches have been contracted or no matches available</small>
             </td></tr>";
}

echo $html;
?>
