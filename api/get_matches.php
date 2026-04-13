<?php
session_start();
include '../frontend/includes/config.php';

 $user_id = $_SESSION['user_id'];

// ✅ JOIN with demand_listings to get date and time_block
 $matches = $conn->query("
    SELECT ms.*, 
           u.name AS seller_name,
           dl.date,
           dl.time_block
    FROM match_suggestions ms
    LEFT JOIN users u ON u.id = ms.seller_id
    LEFT JOIN demand_listings dl ON dl.id = ms.demand_id
    WHERE ms.buyer_id = $user_id AND ms.status = 'pending'
    ORDER BY ms.price ASC, ms.id DESC
");

 $html = '';

if($matches && $matches->num_rows > 0){
    while($row = $matches->fetch_assoc()){
        $date = date('d-m-Y', strtotime($row['date'] ?? 'now'));
        $total = $row['units'] * $row['price'];

        // ✅ this pass karo instead of event
        $html .= "
        <tr>
            <td>$date</td>
            <td>{$row['time_block']}</td>
            <td class='fw-bold'>{$row['seller_name']}</td>
            <td>{$row['units']} kWh</td>
            <td>₹ {$row['price']}</td>
            <td>
                <button class='btn btn-primary btn-sm' 
                        onclick='createContract(this, {$row['id']}, {$row['seller_id']}, {$row['listing_id']}, {$row['demand_id']}, {$row['units']}, {$row['price']}, \"{$row['date']}\", \"{$row['time_block']}\")'>
                    <i class='bi bi-file-earmark-text me-1'></i>Contract (₹$total)
                </button>
            </td>
        </tr>";
    }
} else {
    $html = "<tr><td colspan='6' class='text-muted py-4'>No available matches</td></tr>";
}

echo $html;
?>