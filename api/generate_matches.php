<?php
session_start();
include '../frontend/includes/config.php';

 $user_id = $_SESSION['user_id'];

 $demands = $conn->query("
    SELECT * FROM demand_listings 
    WHERE user_id = $user_id AND remaining_units > 0
");

if(!$demands || $demands->num_rows == 0){
    echo "no_demands";
    exit();
}

 $count = 0;

while($demand = $demands->fetch_assoc()){

    // Already suggested?
    $already = $conn->query("
        SELECT id FROM match_suggestions 
        WHERE demand_id = {$demand['id']} AND status = 'pending'
    ")->num_rows;

    if($already > 0) continue;

    // Find sellers
    $sellers = $conn->query("
        SELECT * FROM energy_listings 
        WHERE date = '{$demand['date']}'
          AND time_block = '{$demand['time_block']}'
          AND price <= {$demand['max_price']}
          AND remaining_units > 0
          AND user_id != $user_id
        ORDER BY price ASC
    ");

    while($seller = $sellers->fetch_assoc()){

        $units = min((float)$demand['remaining_units'], (float)$seller['remaining_units']);
        if($units <= 0) continue;

        // Duplicate check
        $exists = $conn->query("
            SELECT id FROM match_suggestions 
            WHERE demand_id = {$demand['id']} AND listing_id = {$seller['id']} AND status = 'pending'
        ")->num_rows;

        if($exists > 0) continue;

        $conn->query("
            INSERT INTO match_suggestions 
            (buyer_id, seller_id, listing_id, demand_id, units, price, status)
            VALUES ($user_id, {$seller['user_id']}, {$seller['id']}, {$demand['id']}, $units, {$seller['price']}, 'pending')
        ");

        $count++;
    }
}

echo $count > 0 ? "$count" : "0";
?>