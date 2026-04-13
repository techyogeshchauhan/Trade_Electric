<?php
include '../frontend/includes/config.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// STEP 1: GET ALL BUYER DEMANDS
$demands = $conn->query("
    SELECT * FROM demand_listings 
    WHERE remaining_units > 0
");

if(!$demands){
    die("Demand Error: " . $conn->error);
}

while($d = $demands->fetch_assoc()){

    // CLEAN VALUES (IMPORTANT FIX)
    $date = $d['date'];
    $time = trim(str_replace(" ", "", $d['time_block']));
    $max_price = $d['max_price'];

    // DEBUG (optional)
    // echo "Checking Demand ID: ".$d['id']."<br>";

    // STEP 2: FIND MATCHING SELLERS
    $sellers = $conn->query("
        SELECT * FROM energy_listings 
        WHERE date = '$date'
        AND REPLACE(time_block, ' ', '') = '$time'
        AND remaining_units > 0
        AND price <= $max_price
    ");

    if(!$sellers){
        die("Seller Error: " . $conn->error);
    }

    while($s = $sellers->fetch_assoc()){

        // CALCULATE MATCH UNITS
        $units = min($d['remaining_units'], $s['remaining_units']);

        if($units <= 0) continue;

        // STEP 3: CHECK DUPLICATE
        $check = $conn->query("
            SELECT id FROM match_suggestions 
            WHERE buyer_id = {$d['user_id']}
            AND listing_id = {$s['id']}
            AND demand_id = {$d['id']}
            AND status = 'pending'
        ");

        if(!$check){
            die("Check Error: " . $conn->error);
        }

        if($check->num_rows == 0){

            // STEP 4: INSERT MATCH SUGGESTION
            $insert = $conn->query("
                INSERT INTO match_suggestions
                (buyer_id, seller_id, listing_id, demand_id, units, price)
                VALUES
                ({$d['user_id']}, {$s['user_id']}, {$s['id']}, {$d['id']}, $units, {$s['price']})
            ");

            if(!$insert){
                die("Insert Error: " . $conn->error);
            }

        }
    }
}

echo "Matching Done ✅";
?>