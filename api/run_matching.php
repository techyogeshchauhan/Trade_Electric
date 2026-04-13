<?php
// api/run_matching.php  
while(true) {
    include 'match_orders.php';
    echo "\n[" . date('H:i:s') . "] Matching cycle completed.\n";
    sleep(5);   
}
?>