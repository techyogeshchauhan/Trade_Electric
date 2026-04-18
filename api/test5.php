<?php
session_start();
$_SESSION['user_id'] = 1;
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'buyer_id' => 1,
    'seller_id' => 20,
    'listing_id' => 3218,
    'demand_id' => 1570,
    'units' => 15.00,
    'price_per_unit' => 3.20,
    'date' => '2026-04-18',
    'time_block' => '09:00-10:00'
];
include 'create_contract_v2.php';
?>
