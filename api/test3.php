<?php
session_start();
$_SESSION['user_id'] = 1;
include 'api/get_matches_direct.php';
?>
