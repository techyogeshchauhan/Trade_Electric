<?php
/**
 * Direct Matches API - Without match_suggestions table
 * This creates matches on-the-fly from energy_listings and demand_listings
 */
session_start();
include '../frontend/includes/config.php';

$user_id = $_SESSION['user_id'];

// Get buyer's demands
$demands = $conn->query("
    SELECT * FROM demand_listings 
    WHERE user_id = $user_id 
    AND remaining_units > 0
    ORDER BY date ASC, time_block ASC
");

$html = '';
$match_count = 0;

if ($demands && $demands->num_rows > 0) {
    while ($demand = $demands->fetch_assoc()) {
        // Find matching energy listings
        $listings = $conn->query("
            SELECT el.*, u.name as seller_name, u.id as seller_id
            FROM energy_listings el
            JOIN users u ON el.user_id = u.id
            WHERE el.date = '{$demand['date']}'
            AND el.time_block = '{$demand['time_block']}'
            AND el.remaining_units > 0
            AND el.price <= {$demand['max_price']}
            AND u.role = 'seller'
            ORDER BY el.price ASC
            LIMIT 5
        ");
        
        if ($listings && $listings->num_rows > 0) {
            while ($listing = $listings->fetch_assoc()) {
                $match_count++;
                $date = date('d-m-Y', strtotime($listing['date']));
                $units = min($demand['remaining_units'], $listing['remaining_units']);
                $total = $units * $listing['price'];
                
                $html .= "
                <tr>
                    <td>$date</td>
                    <td>{$listing['time_block']}</td>
                    <td class='fw-bold'>{$listing['seller_name']}</td>
                    <td>$units kWh</td>
                    <td>₹ {$listing['price']}</td>
                    <td>
                        <button class='btn btn-primary btn-sm' 
                                onclick='createContract(this, 0, {$listing['seller_id']}, {$listing['id']}, {$demand['id']}, $units, {$listing['price']}, \"{$listing['date']}\", \"{$listing['time_block']}\")'>
                            <i class='bi bi-file-earmark-text me-1'></i>Contract (₹$total)
                        </button>
                    </td>
                </tr>";
            }
        }
    }
}

if ($match_count === 0) {
    $html = "<tr><td colspan='6' class='text-muted py-4'>
                <i class='bi bi-inbox me-2'></i>No available matches
                <br><small>Submit a demand and wait for sellers to list energy</small>
             </td></tr>";
}

echo $html;
?>
