<?php
session_start();
include '../frontend/includes/config.php';

$user_id = $_SESSION['user_id'];
$amount = $_POST['amount'];

$conn->query("UPDATE wallet SET balance = balance + $amount WHERE user_id=$user_id");

$conn->query("
INSERT INTO wallet_transactions (user_id, type, amount, description)
VALUES ($user_id, 'credit', $amount, 'Wallet top-up')
");

echo json_encode([
    "status" => "success",
    "message" => "Money added successfully"
]);