<?php
include 'frontend/includes/config.php';
$user_id = 1;
$demands = $conn->query("SELECT * FROM demand_listings WHERE user_id = $user_id AND remaining_units > 0");
while($d = $demands->fetch_assoc()){
    $q = "SELECT el.*, u.name as seller_name FROM energy_listings el 
          JOIN users u ON el.user_id = u.id 
          WHERE el.date = '{$d['date']}' 
          AND REPLACE(el.time_block, ' ', '') = REPLACE('{$d['time_block']}', ' ', '') 
          AND el.remaining_units > 0 
          AND el.price <= {$d['max_price']} 
          AND u.role = 'seller' 
          AND el.status IN ('available', 'active')";
    $list = $conn->query($q);
    if($list && $list->num_rows > 0) {
        echo "MATCH FOUND!\n";
    } else {
        echo "NO MATCH. Query: $q\n";
    }
}
?>
