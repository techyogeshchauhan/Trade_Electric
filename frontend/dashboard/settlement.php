<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../seller/login.php");
    exit();
}

include '../includes/config.php';
$user_id = $_SESSION['user_id'];
$role    = $_SESSION['role'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settlement - EnergyTrade</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">

    <style>
        .main-content {
            margin-left: 260px;
            margin-top: 100px;
            padding: 30px;
        }
        .settlement-card {
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .token-id {
            font-family: monospace;
            background: #f1f5f9;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 14px;
        }
        .fee-positive {
            color: #dc3545;
            font-weight: 500;
        }
    </style>
</head>
<body>

<?php include '../includes/header.php'; ?>

<div class="main-content">
    <h2 class="mb-4">💰 Settlement Details</h2>

    

    <div class="settlement-card card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered text-center mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>S.No</th>
                            <th>Date</th>
                            <th>Time Block</th>
                            <?php if($role == 'admin'): ?>
                                <th>Buyer</th>
                                <th>Seller</th>
                            <?php elseif($role == 'buyer'): ?>
                                <th>Seller</th>
                            <?php else: ?>
                                <th>Buyer</th>
                            <?php endif; ?>
                            <th>Units</th>
                            <th>Rate/Unit</th>
                            <th>Gross Amount</th>
                            <th>Platform Fee</th>
                            <th>Utility Fee</th>
                            <th><?= $role == 'seller' ? 'Net Profit' : 'Total Paid' ?></th>
                            <th>Token ID</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $where = "";
                        if($role == 'buyer') $where = "WHERE t.buyer_id = $user_id";
                        elseif($role == 'seller') $where = "WHERE t.seller_id = $user_id";

                        $result = $conn->query("
                            SELECT t.*, 
                                   b.name as buyer_name, 
                                   s.name as seller_name,
                                   tl.tx_hash as token_id
                            FROM trades t
                            LEFT JOIN users b ON t.buyer_id = b.id
                            LEFT JOIN users s ON t.seller_id = s.id
                            LEFT JOIN token_ledger tl ON t.id = tl.trade_id AND tl.token_type = 'transfer_in'
                            $where
                            ORDER BY t.id DESC
                        ");

                        // Fetch charge settings
                        $settings = $conn->query("SELECT * FROM settings LIMIT 1")->fetch_assoc() ?? [
                            'platform_charge' => 0.25,
                            'utility_charge_buyer' => 0.02
                        ];
                        
                        $serial = 1;
                        while($row = $result->fetch_assoc()):
                            $units        = $row['units'];
                            $rate         = $row['price'];
                            $gross        = $units * $rate;
                            $platform_fee = $units * $settings['platform_charge'];
                            $utility_fee  = $units * $settings['utility_charge_buyer'];
                            
                            // Total Amount calculation (same for both buyer and seller view)
                            $final_amount = $gross - $platform_fee - $utility_fee;
                        ?>
                        <tr>
                            <td><strong><?= $serial++ ?></strong></td>
                            <td><?= date('d-m-Y', strtotime($row['date'])) ?></td>
                            <td><?= htmlspecialchars($row['time_block'] ?? '-') ?></td>

                            <?php if($role == 'admin'): ?>
                                <td><?= htmlspecialchars($row['buyer_name'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($row['seller_name'] ?? '-') ?></td>
                            <?php elseif($role == 'buyer'): ?>
                                <td><?= htmlspecialchars($row['seller_name'] ?? '-') ?></td>
                            <?php else: ?>
                                <td><?= htmlspecialchars($row['buyer_name'] ?? '-') ?></td>
                            <?php endif; ?>

                            <td><?= number_format($units, 2) ?> kWh</td>
                            <td>₹<?= number_format($rate, 2) ?></td>
                            <td>₹<?= number_format($gross, 2) ?></td>
                            <td class="fee-positive">₹<?= number_format($platform_fee, 2) ?></td>
                            <td class="fee-positive">₹<?= number_format($utility_fee, 2) ?></td>
                            <td class="text-success fw-bold">₹<?= number_format($final_amount, 2) ?></td>
                            <td>
                                <?php if(!empty($row['token_id'])): ?>
                                    <span class="token-id"><?= substr($row['token_id'], 0, 12) ?>...</span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>

                        <?php if($result->num_rows == 0): ?>
                        <tr>
                            <td colspan="12" class="text-center py-4 text-muted">No settlement records found yet.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

</body>
</html>