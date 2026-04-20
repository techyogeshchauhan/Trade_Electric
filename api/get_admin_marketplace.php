<?php
// Admin-specific marketplace data fetch
// Shows ALL listings/demands regardless of date (no time-block filtering)
session_start();
ob_start();
include '../frontend/includes/config.php';

// Verify admin
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'admin') {
    echo "<tr><td colspan='6' class='text-center text-danger'>Unauthorized</td></tr>";
    exit;
}

$type = $_GET['type'] ?? 'listings';

if ($type === 'listings') {
    // All active energy listings — no date/time filtering for admin visibility
    $sql = "SELECT e.date, e.time_block, e.units_available, e.remaining_units, e.price, u.name as seller_name
            FROM energy_listings e
            JOIN users u ON e.user_id = u.id
            WHERE e.remaining_units > 0
            ORDER BY e.price ASC, e.date ASC
            LIMIT 100";

    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $date     = date('d-m-Y', strtotime($row['date']));
            $price    = number_format($row['price'], 2);
            $units    = number_format($row['units_available'], 0);
            $remaining = number_format($row['remaining_units'], 0);
            $seller   = htmlspecialchars($row['seller_name']);
            $time     = htmlspecialchars($row['time_block']);
            echo "<tr>
                    <td>$date</td>
                    <td>$time</td>
                    <td><strong>$seller</strong></td>
                    <td>$units kWh</td>
                    <td>₹$price</td>
                    <td>$remaining kWh</td>
                  </tr>";
        }
    } else {
        echo "<tr><td colspan='6' class='text-center py-3 text-muted'><i class='bi bi-inbox'></i> No active energy listings</td></tr>";
    }

} elseif ($type === 'demands') {
    // All active buyer demands — no date/time filtering for admin visibility
    $sql = "SELECT d.date, d.time_block, d.units_required, d.remaining_units, d.max_price, u.name as buyer_name
            FROM demand_listings d
            JOIN users u ON d.user_id = u.id
            WHERE d.remaining_units > 0
            ORDER BY d.max_price DESC, d.date ASC
            LIMIT 100";

    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $date      = date('d-m-Y', strtotime($row['date']));
            $price     = number_format($row['max_price'], 2);
            $units     = number_format($row['units_required'], 0);
            $remaining = number_format($row['remaining_units'], 0);
            $buyer     = htmlspecialchars($row['buyer_name']);
            $time      = htmlspecialchars($row['time_block']);
            echo "<tr>
                    <td>$date</td>
                    <td>$time</td>
                    <td><strong>$buyer</strong></td>
                    <td>$units kWh</td>
                    <td>₹$price</td>
                    <td>$remaining kWh</td>
                  </tr>";
        }
    } else {
        echo "<tr><td colspan='6' class='text-center py-3 text-muted'><i class='bi bi-inbox'></i> No active buyer demands</td></tr>";
    }
}

ob_end_flush();
?>
