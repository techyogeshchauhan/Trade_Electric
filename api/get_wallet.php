<?php
session_start();
header('Content-Type: application/json');
include '../frontend/includes/config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

 $wallet = $conn->query("
    SELECT balance, blocked_balance 
    FROM wallet 
    WHERE user_id = $user_id
")->fetch_assoc();

if (!$wallet) {
    $conn->query("
        INSERT INTO wallet (user_id, balance, blocked_balance)
        VALUES ($user_id, 5000, 0)
    ");
    $wallet = ["balance" => 5000, "blocked_balance" => 0];
}

// ✅ TOKEN BALANCE
 $tokenData = $conn->query("
    SELECT 
        SUM(
            CASE 
                WHEN token_type IN ('mint','transfer_in') THEN token_units
                WHEN token_type IN ('transfer_out','burn') THEN -token_units
                ELSE 0
            END
        ) as token_balance
    FROM token_ledger
    WHERE user_id = $user_id
")->fetch_assoc();

 $token_balance = $tokenData['token_balance'] ?? 0;

// GET WALLET TRANSACTIONS (Proper transaction history)
$query = "
    SELECT wt.*, 
           wt.created_at as transaction_date
    FROM wallet_transactions wt
    WHERE wt.user_id = $user_id
    ORDER BY wt.id DESC
    LIMIT 50
";

$result = $conn->query($query);
$transactions = [];

while($row = $result->fetch_assoc()){
    $transactions[] = [
        "type" => $row['type'],
        "amount" => $row['amount'],
        "description" => $row['description'],
        "date" => date('d-m-Y H:i', strtotime($row['transaction_date']))
    ];
}

echo json_encode([
    "balance" => (float)$wallet['balance'],
    "blocked_balance" => (float)$wallet['blocked_balance'],
    "token_balance" => (float)$token_balance,
    "transactions" => $transactions
]);
?>