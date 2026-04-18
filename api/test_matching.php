<?php
/**
 * Test Matching Script
 * This script tests if the matching logic works correctly for 10KW listings
 */

include '../frontend/includes/config.php';

echo "<h2>🔍 Testing Matching Logic</h2>";
echo "<hr>";

// Test 1: Check energy listings
echo "<h3>1. Energy Listings (Sellers)</h3>";
$listings = $conn->query("
    SELECT el.*, u.name as seller_name 
    FROM energy_listings el
    JOIN users u ON el.user_id = u.id
    WHERE el.remaining_units > 0
    AND el.status = 'available'
    ORDER BY el.date, el.time_block
    LIMIT 10
");

if ($listings && $listings->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Seller</th><th>Date</th><th>Time Block</th><th>Units</th><th>Price</th><th>Status</th></tr>";
    while ($row = $listings->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['seller_name']}</td>";
        echo "<td>{$row['date']}</td>";
        echo "<td>{$row['time_block']}</td>";
        echo "<td>{$row['remaining_units']} kWh</td>";
        echo "<td>₹{$row['price']}</td>";
        echo "<td>{$row['status']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='text-warning'>No active listings found</p>";
}

echo "<hr>";

// Test 2: Check demand listings
echo "<h3>2. Demand Listings (Buyers)</h3>";
$demands = $conn->query("
    SELECT dl.*, u.name as buyer_name 
    FROM demand_listings dl
    JOIN users u ON dl.user_id = u.id
    WHERE dl.remaining_units > 0
    ORDER BY dl.date, dl.time_block
    LIMIT 10
");

if ($demands && $demands->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Buyer</th><th>Date</th><th>Time Block</th><th>Units</th><th>Max Price</th></tr>";
    while ($row = $demands->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['buyer_name']}</td>";
        echo "<td>{$row['date']}</td>";
        echo "<td>{$row['time_block']}</td>";
        echo "<td>{$row['remaining_units']} kWh</td>";
        echo "<td>₹{$row['max_price']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='text-warning'>No active demands found</p>";
}

echo "<hr>";

// Test 3: Find potential matches
echo "<h3>3. Potential Matches (with normalized time comparison)</h3>";
$matches = $conn->query("
    SELECT 
        dl.id as demand_id,
        el.id as listing_id,
        bu.name as buyer_name,
        su.name as seller_name,
        dl.date,
        dl.time_block as demand_time,
        el.time_block as listing_time,
        LEAST(dl.remaining_units, el.remaining_units) as match_units,
        el.price,
        dl.max_price
    FROM demand_listings dl
    INNER JOIN energy_listings el 
        ON dl.date = el.date 
        AND REPLACE(dl.time_block, ' ', '') = REPLACE(el.time_block, ' ', '')
        AND el.price <= dl.max_price
        AND el.remaining_units > 0
        AND el.status = 'available'
    INNER JOIN users bu ON dl.user_id = bu.id
    INNER JOIN users su ON el.user_id = su.id
    WHERE dl.remaining_units > 0
    ORDER BY dl.date, dl.time_block
    LIMIT 20
");

if ($matches && $matches->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Demand ID</th><th>Listing ID</th><th>Buyer</th><th>Seller</th><th>Date</th><th>Time</th><th>Units</th><th>Price</th></tr>";
    $count = 0;
    while ($row = $matches->fetch_assoc()) {
        $count++;
        echo "<tr>";
        echo "<td>{$row['demand_id']}</td>";
        echo "<td>{$row['listing_id']}</td>";
        echo "<td>{$row['buyer_name']}</td>";
        echo "<td>{$row['seller_name']}</td>";
        echo "<td>{$row['date']}</td>";
        echo "<td>{$row['demand_time']}</td>";
        echo "<td>{$row['match_units']} kWh</td>";
        echo "<td>₹{$row['price']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<p class='text-success'><strong>✅ Found $count potential matches!</strong></p>";
} else {
    echo "<p class='text-danger'>❌ No matches found. Check if:</p>";
    echo "<ul>";
    echo "<li>Dates match between listings and demands</li>";
    echo "<li>Time blocks match (normalized)</li>";
    echo "<li>Listing price <= Demand max price</li>";
    echo "<li>Both have remaining units > 0</li>";
    echo "<li>Listing status is 'available'</li>";
    echo "</ul>";
}

echo "<hr>";
echo "<p><a href='auto_match.php'>Run Auto Match</a> | <a href='../frontend/buyer/dashboard.php'>Buyer Dashboard</a></p>";
?>
