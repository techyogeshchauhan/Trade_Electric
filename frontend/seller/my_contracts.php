<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'seller') {
    header("Location: ../login.php");
    exit();
}

include '../includes/config.php';
$seller_id = $_SESSION['user_id'];

// Get contracts with buyer info
$contracts_query = $conn->query("
    SELECT c.*, u.name as buyer_name, u.email as buyer_email
    FROM contracts c
    JOIN users u ON c.buyer_id = u.id
    WHERE c.seller_id = $seller_id
    ORDER BY c.created_at DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Contracts - Seller</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    
    <style>
        body { background: #f8fafc; }
        .main-content {
            margin-top: 80px;
            margin-left: 260px;
            padding: 25px;
        }
        .card {
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            border: none;
            overflow: hidden;
            margin-bottom: 20px;
        }
        .card-header {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 16px 20px;
            font-weight: 700;
            font-size: 16px;
        }
        .table {
            margin: 0;
            font-size: 14px;
            border-collapse: collapse;
        }
        .table thead th {
            background: #2d3748;
            color: #ffffff;
            text-align: center;
            font-size: 14px;
            padding: 14px 12px;
            font-weight: 600;
            border: none;
        }
        .table tbody td {
            text-align: center;
            vertical-align: middle;
            font-size: 14px;
            padding: 14px 12px;
            border: none;
            border-bottom: 1px solid #e2e8f0;
            background: #ffffff;
        }
        .table tbody tr:hover td {
            background-color: #f7fafc;
        }
        .badge-to-be-traded {
            background: #fef3c7;
            color: #92400e;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-fully-sold {
            background: #d1fae5;
            color: #065f46;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-completed {
            background: #dbeafe;
            color: #1e40af;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }
        @media (max-width: 992px) {
            .main-content { margin-left: 0; padding: 15px; }
        }
    </style>
</head>
<body>

<?php include '../includes/header.php'; ?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>
            <i class="bi bi-file-earmark-text me-2"></i>My Contracts
        </h2>
        <a href="dashboard.php" class="btn btn-outline-primary">
            <i class="bi bi-arrow-left me-2"></i>Back to Dashboard
        </a>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-list-check me-2"></i>All Contracts</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Contract ID</th>
                            <th>Buyer</th>
                            <th>Date</th>
                            <th>Time Block</th>
                            <th>Units (kWh)</th>
                            <th>Price/Unit</th>
                            <th>Total Amount</th>
                            <th>Trade Status</th>
                            <th>Energy Status</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($contracts_query->num_rows > 0): ?>
                            <?php while ($contract = $contracts_query->fetch_assoc()): 
                                $trade_status = $contract['trade_status'] ?? 'to_be_traded';
                                $energy_filled = $contract['energy_filled'] ?? 0;
                                
                                // Determine status badge
                                if ($trade_status == 'completed') {
                                    $status_badge = '<span class="badge-completed"><i class="bi bi-check-circle me-1"></i>Completed</span>';
                                } elseif ($trade_status == 'fully_sold') {
                                    $status_badge = '<span class="badge-fully-sold"><i class="bi bi-check-circle me-1"></i>Fully Sold</span>';
                                } else {
                                    $status_badge = '<span class="badge-to-be-traded"><i class="bi bi-clock me-1"></i>To be Traded</span>';
                                }
                                
                                $energy_status = $energy_filled ? 
                                    '<span class="badge bg-success">Filled</span>' : 
                                    '<span class="badge bg-warning text-dark">Pending</span>';
                            ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($contract['contract_id']) ?></strong></td>
                                <td><?= htmlspecialchars($contract['buyer_name']) ?></td>
                                <td><?= date('d-m-Y', strtotime($contract['date'])) ?></td>
                                <td><?= htmlspecialchars($contract['time_block']) ?></td>
                                <td><strong><?= number_format($contract['units'], 2) ?></strong></td>
                                <td>₹<?= number_format($contract['price_per_unit'], 2) ?></td>
                                <td><strong class="text-success">₹<?= number_format($contract['total_amount'], 2) ?></strong></td>
                                <td><?= $status_badge ?></td>
                                <td><?= $energy_status ?></td>
                                <td><?= date('d-m-Y H:i', strtotime($contract['created_at'])) ?></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox" style="font-size: 48px; opacity: 0.3;"></i>
                                    <p class="mt-3">No contracts found</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Status Legend -->
    <div class="card p-3">
        <h6 class="mb-3">Status Legend:</h6>
        <div class="row">
            <div class="col-md-4">
                <h6 class="text-muted mb-2">Trade Status:</h6>
                <div class="mb-2"><span class="badge-to-be-traded">To be Traded</span> - Contract created, waiting for energy</div>
                <div class="mb-2"><span class="badge-fully-sold">Fully Sold</span> - Energy filled, ready for settlement</div>
                <div><span class="badge-completed">Completed</span> - Trade completed, tokens & wallet updated</div>
            </div>
            <div class="col-md-4">
                <h6 class="text-muted mb-2">Energy Status:</h6>
                <div class="mb-2"><span class="badge bg-warning text-dark">Pending</span> - Energy not yet filled in monitor</div>
                <div><span class="badge bg-success">Filled</span> - Energy data entered in monitor</div>
            </div>
            <div class="col-md-4">
                <h6 class="text-muted mb-2">Flow:</h6>
                <ol class="small">
                    <li>Contract created → <strong>To be Traded</strong></li>
                    <li>Energy filled → <strong>Fully Sold</strong></li>
                    <li>Time block complete → Units sold</li>
                    <li>Tokens generated → Wallet credited</li>
                    <li>Status → <strong>Completed</strong></li>
                </ol>
            </div>
        </div>
    </div>

</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
