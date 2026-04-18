<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Marketplace Data Check</h2>";
echo "<style>
    body { font-family: Arial; padding: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background: #4CAF50; color: white; }
</style>";

include 'frontend/includes/config.php';

// Check database connection
if ($conn->connect_error) {
    echo "<p class='error'>❌ Database connection failed: " . $conn->connect_error . "</p>";
    exit;
}
echo "<p class='success'>✅ Database connected</p>";

// Check energy_listings table
echo "<h3>1. Energy Listings (Sellers)</h3>";
$result = $conn->query("SELECT COUNT(*) as total FROM energy_listings");
$total = $result->fetch_assoc()['total'];
echo "<p>Total energy listings: <strong>$total</strong></p>";

$result = $conn->query("SELECT COUNT(*) as available FROM energy_listings WHERE remaining_units > 0");
$available = $result->fetch_assoc()['available'];
echo "<p>Available listings (remaining_units > 0): <strong>$available</strong></p>";

if ($available > 0) {
    echo "<p class='success'>✅ Energy listings available</p>";
    echo "<table>";
    echo "<tr><th>ID</th><th>Seller ID</th><th>Date</th><th>Time</th><th>Units</th><th>Remaining</th><th>Price</th></tr>";
    $result = $conn->query("SELECT * FROM energy_listings WHERE remaining_units > 0 LIMIT 5");
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['user_id']}</td>";
        echo "<td>{$row['date']}</td>";
        echo "<td>{$row['time_block']}</td>";
        echo "<td>{$row['units_available']}</td>";
        echo "<td>{$row['remaining_units']}</td>";
        echo "<td>₹{$row['price']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='error'>❌ No available energy listings found!</p>";
    echo "<p><strong>Solution:</strong> Login as seller and add energy listings</p>";
}

// Check demand_listings table
echo "<h3>2. Demand Listings (Buyers)</h3>";
$result = $conn->query("SELECT COUNT(*) as total FROM demand_listings");
$total = $result->fetch_assoc()['total'];
echo "<p>Total demand listings: <strong>$total</strong></p>";

$result = $conn->query("SELECT COUNT(*) as available FROM demand_listings WHERE remaining_units > 0");
$available = $result->fetch_assoc()['available'];
echo "<p>Available demands (remaining_units > 0): <strong>$available</strong></p>";

if ($available > 0) {
    echo "<p class='success'>✅ Demand listings available</p>";
    echo "<table>";
    echo "<tr><th>ID</th><th>Buyer ID</th><th>Date</th><th>Time</th><th>Units</th><th>Remaining</th><th>Max Price</th></tr>";
    $result = $conn->query("SELECT * FROM demand_listings WHERE remaining_units > 0 LIMIT 5");
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['user_id']}</td>";
        echo "<td>{$row['date']}</td>";
        echo "<td>{$row['time_block']}</td>";
        echo "<td>{$row['units_required']}</td>";
        echo "<td>{$row['remaining_units']}</td>";
        echo "<td>₹{$row['max_price']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='error'>❌ No available demand listings found!</p>";
    echo "<p><strong>Solution:</strong> Login as buyer and add demands</p>";
}

// Check users table
echo "<h3>3. Users Check</h3>";
$result = $conn->query("SELECT COUNT(*) as sellers FROM users WHERE role='seller'");
$sellers = $result->fetch_assoc()['sellers'];
echo "<p>Total sellers: <strong>$sellers</strong></p>";

$result = $conn->query("SELECT COUNT(*) as buyers FROM users WHERE role='buyer'");
$buyers = $result->fetch_assoc()['buyers'];
echo "<p>Total buyers: <strong>$buyers</strong></p>";

// Test API directly
echo "<h3>4. Test API Response</h3>";
echo "<p><strong>Testing get_all_energy_listings.php:</strong></p>";
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'buyer';

ob_start();
include 'api/get_all_energy_listings.php';
$api_output = ob_get_clean();

if (empty(trim($api_output))) {
    echo "<p class='error'>❌ API returned empty response</p>";
} else if (strpos($api_output, 'No energy listings') !== false) {
    echo "<p class='error'>❌ API says: No energy listings available</p>";
} else {
    echo "<p class='success'>✅ API returned data</p>";
    echo "<pre>" . htmlspecialchars(substr($api_output, 0, 500)) . "...</pre>";
}

echo "<hr>";
echo "<h3>Summary</h3>";
if ($available > 0) {
    echo "<p class='success'>✅ Data exists in database</p>";
    echo "<p>If marketplace still shows 'Loading...', check:</p>";
    echo "<ul>";
    echo "<li>Browser console (F12) for JavaScript errors</li>";
    echo "<li>Network tab to see if API calls are successful</li>";
    echo "<li>Clear browser cache (Ctrl + Shift + Delete)</li>";
    echo "</ul>";
} else {
    echo "<p class='error'>❌ No data in database</p>";
    echo "<p><strong>To fix:</strong></p>";
    echo "<ol>";
    echo "<li>Login as seller</li>";
    echo "<li>Go to 'My Listings' or Dashboard</li>";
    echo "<li>Add energy listings</li>";
    echo "<li>Login as buyer</li>";
    echo "<li>Add demands</li>";
    echo "<li>Check marketplace again</li>";
    echo "</ol>";
}
?>
