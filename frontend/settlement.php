<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include 'includes/config.php';

 $user_id = $_SESSION['user_id'];
 $role = $_SESSION['role'];

if ($role == 'admin') {
    $trades = $conn->query("
        SELECT t.*, b.name as buyer_name, s.name as seller_name
        FROM trades t
        LEFT JOIN users b ON t.buyer_id = b.id
        LEFT JOIN users s ON t.seller_id = s.id
        ORDER BY t.id DESC
    ");
} elseif ($role == 'buyer') {
    $trades = $conn->query("
        SELECT t.*, s.name as seller_name
        FROM trades t
        LEFT JOIN users s ON t.seller_id = s.id
        WHERE t.buyer_id = $user_id
        ORDER BY t.id DESC
    ");
} else {
    $trades = $conn->query("
        SELECT t.*, b.name as buyer_name
        FROM trades t
        LEFT JOIN users b ON t.buyer_id = b.id
        WHERE t.seller_id = $user_id
        ORDER BY t.id DESC
    ");
}

// Check if settlement_status column exists
 $hasSettleCol = $conn->query("SHOW COLUMNS FROM trades LIKE 'settlement_status'")->num_rows > 0;

 $summary = $conn->query("
    SELECT 
        COUNT(*) as total_trades,
        COALESCE(SUM(units), 0) as total_units,
        COALESCE(SUM(total_amount), 0) as total_amount
    FROM trades
")->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
<title>Settlement</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<style>
body { background: #f4f6f9; }

.main-content {
    margin-left: 250px;
    margin-top: 90px;
    padding: 25px;
}

.card-box {
    border-radius: 14px;
    padding: 22px;
    color: white;
    text-align: center;
    transition: 0.3s;
}
.card-box:hover { transform: translateY(-3px); }

.bg1 { background: linear-gradient(135deg, #3b82f6, #2563eb); }
.bg2 { background: linear-gradient(135deg, #10b981, #059669); }
.bg3 { background: linear-gradient(135deg, #f59e0b, #d97706); }

.card {
    border-radius: 14px;
    box-shadow: 0 6px 20px rgba(0,0,0,0.06);
}

.table th {
    background: #1e293b;
    color: white;
    text-align: center;
    font-size: 13px;
    padding: 12px 8px;
}

.table td {
    text-align: center;
    vertical-align: middle;
    font-size: 13px;
    padding: 12px 8px;
}

.btn-settle {
    background: linear-gradient(135deg, #dc3545, #b02a37);
    color: white;
    border: none;
    border-radius: 8px;
    padding: 6px 16px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: 0.2s;
}
.btn-settle:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 12px rgba(220,53,69,0.35);
}
.btn-settle:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
}

.badge-pending {
    background: #fef3c7;
    color: #92400e;
    font-weight: 600;
    padding: 5px 12px;
    border-radius: 6px;
    font-size: 12px;
}

.badge-settled {
    background: #d1fae5;
    color: #065f46;
    font-weight: 600;
    padding: 5px 12px;
    border-radius: 6px;
    font-size: 12px;
}

.tx-link {
    font-family: monospace;
    font-size: 11px;
    color: #6b7280;
    cursor: help;
}

#debugBox {
    background: #fff3cd;
    border: 1px solid #ffc107;
    border-radius: 8px;
    padding: 10px 14px;
    font-size: 12px;
    font-family: monospace;
    margin-bottom: 15px;
    display: none;
}

@media (max-width: 992px) {
    .main-content { margin-left: 0; padding: 15px; }
}
</style>

</head>

<body>

<?php include 'includes/header.php'; ?>

<div class="main-content">

<!-- DEBUG BOX -->
<div id="debugBox"></div>

<h3 class="mb-4 fw-bold"><i class="fas fa-gavel me-2 text-warning"></i>Settlement Dashboard</h3>

<!-- SUMMARY -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card-box bg1">
            <h6>Total Trades</h6>
            <h3 class="mb-0"><?= $summary['total_trades'] ?? 0 ?></h3>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card-box bg2">
            <h6>Total Energy</h6>
            <h3 class="mb-0"><?= number_format($summary['total_units'] ?? 0, 2) ?> kWh</h3>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card-box bg3">
            <h6>Total Amount</h6>
            <h3 class="mb-0">₹ <?= number_format($summary['total_amount'] ?? 0) ?></h3>
        </div>
    </div>
</div>

<?php if(!$hasSettleCol): ?>
<div class="alert alert-danger">
    <b>Missing Column:</b> settlement_status not found in trades table.<br>
    Run this SQL: <code>ALTER TABLE trades ADD COLUMN settlement_status VARCHAR(20) DEFAULT 'pending';</code>
</div>
<?php endif; ?>

<!-- TABLE -->
<div class="card">
    <div class="card-header bg-white py-3">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold">Settlement Details</h5>
            <small class="text-muted">
                <i class="fas fa-fire text-danger me-1"></i>Settle = Burn buyer tokens
            </small>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-bordered">

        <thead>
        <tr>
            <th>#</th>
            <th>Date</th>
            <th>Time</th>
            <?php if($role == 'admin'): ?>
            <th>Buyer</th>
            <th>Seller</th>
            <?php elseif($role == 'buyer'): ?>
            <th>Seller</th>
            <?php else: ?>
            <th>Buyer</th>
            <?php endif; ?>
            <th>Units</th>
            <th>Price</th>
            <th>Total</th>
            <th>Status</th>
            <?php if($role == 'admin' && $hasSettleCol): ?>
            <th>Action</th>
            <?php endif; ?>
        </tr>
        </thead>

        <tbody>
        <?php
        $count = 1;
        while($row = $trades->fetch_assoc()):
            $status = 'pending';
            if($hasSettleCol){
                $status = $row['settlement_status'] ?? 'pending';
            }
        ?>
        <tr>
            <td><?= $count++ ?></td>
            <td><?= $row['date'] ?? '—' ?></td>
            <td><?= $row['time_block'] ?? '—' ?></td>

            <?php if($role == 'admin'): ?>
            <td><?= $row['buyer_name'] ?? '—' ?></td>
            <td><?= $row['seller_name'] ?? '—' ?></td>
            <?php elseif($role == 'buyer'): ?>
            <td><?= $row['seller_name'] ?? '—' ?></td>
            <?php else: ?>
            <td><?= $row['buyer_name'] ?? '—' ?></td>
            <?php endif; ?>

            <td><?= $row['units'] ?> kWh</td>
            <td>₹ <?= $row['price'] ?></td>
            <td><b>₹ <?= number_format($row['total_amount'], 2) ?></b></td>

            <td>
                <?php if($status == 'settled'): ?>
                    <span class="badge-settled">
                        <i class="fas fa-check-circle me-1"></i>Settled
                    </span>
                <?php else: ?>
                    <span class="badge-pending">
                        <i class="fas fa-clock me-1"></i>Pending
                    </span>
                <?php endif; ?>
            </td>

            <?php if($role == 'admin' && $hasSettleCol): ?>
            <td>
                <?php if($status != 'settled'): ?>
                    <button class="btn-settle" id="settleBtn<?= $row['id'] ?>" onclick="settleTrade(<?= $row['id'] ?>)">
                        <i class="fas fa-fire me-1"></i>Settle & Burn
                    </button>
                <?php else: ?>
                    <span class="text-muted">—</span>
                <?php endif; ?>
            </td>
            <?php endif; ?>
        </tr>
        <?php endwhile; ?>

        <?php if($trades->num_rows == 0): ?>
        <tr><td colspan="10" class="text-muted py-4">No trades found</td></tr>
        <?php endif; ?>

        </tbody>
        </table>
        </div>
    </div>
</div>

</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
function showDebug(msg){
    let box = document.getElementById('debugBox');
    box.style.display = 'block';
    box.innerHTML += msg + '<br>';
}

function settleTrade(tradeId){
    if(!confirm('Settle this trade? Buyer tokens will be BURNED.')){
        return;
    }

    let btn = $('#settleBtn' + tradeId);
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Burning...');

    showDebug('Calling settle_trade.php with trade_id=' + tradeId + '...');

    $.ajax({
        url: '../api/settle_trade.php',
        method: 'POST',
        data: {trade_id: tradeId},
        dataType: 'json',
        success: function(data){
            showDebug('Response: ' + JSON.stringify(data));

            if(data.success){
                alert('✅ ' + data.message);
                location.reload();
            } else {
                alert('❌ ' + (data.error || 'Error'));
                btn.prop('disabled', false).html('<i class="fas fa-fire me-1"></i>Settle & Burn');
            }
        },
        error: function(xhr, status, err){
            showDebug('❌ AJAX Error: ' + status + ' - ' + err);
            showDebug('Response: ' + xhr.responseText.substring(0, 300));
            btn.prop('disabled', false).html('<i class="fas fa-fire me-1"></i>Settle & Burn');
        }
    });
}
</script>

</body>
</html>