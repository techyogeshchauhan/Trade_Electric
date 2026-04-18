<?php
include 'frontend/includes/config.php';
$e = $conn->query("SELECT * FROM energy_listings WHERE date >= '2026-04-17'");
if($e->num_rows == 0) {
    echo "No energy listings found >= 2026-04-17\n";
}
while($r = $e->fetch_assoc()) { print_r($r); }
?>
