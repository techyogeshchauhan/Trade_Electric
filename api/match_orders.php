<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../frontend/includes/config.php';

echo "<h4>🔄 CDA Matching Engine Running... (" . date('H:i:s') . ")</h4>";

 $matchedCount = 0;
 $skipped = 0;

// Pending demands
 $buyers = $conn->query("
    SELECT * FROM demand_listings 
    WHERE remaining_units > 0 
    ORDER BY max_price DESC, id ASC
");

if(!$buyers || $buyers->num_rows == 0){
    echo "<p class='text-muted'>No pending demands</p>";
    exit();
}

echo "<p>Found <b>{$buyers->num_rows}</b> pending demands</p>";

while($buyer = $buyers->fetch_assoc()) {
    $remaining = (float)$buyer['remaining_units'];
    if ($remaining <= 0) continue;

    // Find sellers
    $sellers = $conn->query("
        SELECT * FROM energy_listings 
        WHERE date = '{$buyer['date']}'
          AND time_block = '{$buyer['time_block']}'
          AND price <= {$buyer['max_price']}
          AND remaining_units > 0 
        ORDER BY price ASC, id ASC
    ");

    if(!$sellers || $sellers->num_rows == 0){
        $skipped++;
        continue;
    }

    while(($seller = $sellers->fetch_assoc()) && $remaining > 0) {

        $seller_remaining = (float)$seller['remaining_units'];
        $trade_units = min($remaining, $seller_remaining);

        if ($trade_units <= 0) break;

        $price = (float)$seller['price'];

        // Already suggested for this pair?
        $exists = $conn->query("
            SELECT id FROM match_suggestions 
            WHERE demand_id = {$buyer['id']} 
              AND listing_id = {$seller['id']} 
              AND status = 'pending'
        ")->num_rows;

        if($exists > 0){
            echo "<p class='text-secondary'>↪ Already suggested: Buyer {$buyer['user_id']} ↔ Seller {$seller['user_id']}</p>";
            $skipped++;
            continue;
        }

        // ✅ SUGGESTION BANAO — TRADE NAHI
        $conn->query("
            INSERT INTO match_suggestions 
            (buyer_id, seller_id, listing_id, demand_id, units, price, status)
            VALUES (
                {$buyer['user_id']},
                {$seller['user_id']},
                {$seller['id']},
                {$buyer['id']},
                $trade_units,
                $price,
                'pending'
            )
        ");

        echo "<p class='text-success'>✅ Suggested: {$trade_units} kWh @ ₹{$price} — Buyer: {$buyer['user_id']} ↔ Seller: {$seller['user_id']}</p>";

        $matchedCount++;
        $remaining -= $trade_units;
    }
}

echo "<hr>";
echo "<h3 class='text-success'>✅ $matchedCount new suggestions created</h3>";
echo "<p class='text-muted'>$skipped skipped (already exists or no match)</p>";
echo "<p>[" . date('H:i:s') . "] Done. Buyers will see these in Available Matches.</p>";
?>