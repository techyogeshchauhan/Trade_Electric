<?php
include 'frontend/includes/config.php';
$d = $conn->query('SELECT * FROM demand_listings');
echo "Demands: \n";
while($r = $d->fetch_assoc()) { print_r($r); }
echo "\n---\nEnergy: \n";
$e = $conn->query('SELECT * FROM energy_listings');
while($r = $e->fetch_assoc()) { print_r($r); }
?>
