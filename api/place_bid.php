<?php
session_start();
include '../frontend/includes/config.php';

$user_id = $_SESSION['user_id'];
$listing_id = $_POST['listing_id'];

$listing = $conn->query("SELECT * FROM energy_listings WHERE id=$listing_id")->fetch_assoc();

$conn->query("
INSERT INTO demand_listings 
(user_id, date, time_block, units_required, remaining_units, max_price)
VALUES (
    $user_id,
    '{$listing['date']}',
    '{$listing['time_block']}',
    {$listing['remaining_units']},
    {$listing['remaining_units']},
    {$listing['price']}
)
");

echo "Bid Placed";
?>