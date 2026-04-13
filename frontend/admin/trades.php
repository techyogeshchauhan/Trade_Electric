<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

include '../includes/config.php';

 $date_filter = $_GET['date'] ?? '';
 $status_filter = $_GET['status'] ?? '';
 $seller_filter = $_GET['seller'] ?? '';
 $buyer_filter = $_GET['buyer'] ?? '';

 $query = "SELECT t.*, b.name as buyer_name, s.name as seller_name
          FROM trades t
          LEFT JOIN users b ON b.id = t.buyer_id
          LEFT JOIN users s ON s.id = t.seller_id
          WHERE 1";

if (!empty($date_filter)) $query .= " AND t.date = '$date_filter'";
if (!empty($status_filter)) $query .= " AND t.settlement_status = '$status_filter'";
if (!empty($seller_filter)) $query .= " AND t.seller_id = '$seller_filter'";
if (!empty($buyer_filter)) $query .= " AND t.buyer_id = '$buyer_filter'";
 $query .= " ORDER BY t.id DESC";

 $result = $conn->query($query);
 $totalCount = $result->num_rows;

 $totalAmount = $conn->query("SELECT COALESCE(SUM(total_amount),0) as c FROM trades")->fetch_assoc()['c'];
 $totalUnits = $conn->query("SELECT COALESCE(SUM(units),0) as c FROM trades")->fetch_assoc()['c'];
 /*$settled = $conn->query("SELECT COUNT(*) as c FROM trades WHERE settlement_status='settled'")->fetch_assoc()['c'];
 $pending = $totalCount - $settled;*/

 $sellersDrop = $conn->query("SELECT DISTINCT u.id, u.name FROM trades t JOIN users u ON u.id = t.seller_id ORDER BY u.name");
 $buyersDrop = $conn->query("SELECT DISTINCT u.id, u.name FROM trades t JOIN users u ON u.id = t.buyer_id ORDER BY u.name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Trades</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

<style>
body {
    background: #f0f2f5;
    font-family: 'Segoe UI', sans-serif;
    color: #1f2937;
    font-size: 15px;
}

.top-bar {
    background: #fff;
    border-radius: 12px;
    padding: 20px 28px;
    margin-bottom: 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
}

.top-bar h4 { font-weight: 700; margin: 0; font-size: 20px; }

.top-stats { display: flex; gap: 24px; }
.top-stat-val { font-size: 22px; font-weight: 800; color: #111827; }
.top-stat-lbl { font-size: 12px; color: #9ca3af; text-transform: uppercase; font-weight: 600; letter-spacing: 0.4px; }

.filter-bar {
    background: #fff;
    border-radius: 12px;
    padding: 16px 24px;
    margin-bottom: 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
}

.form-control, .form-select {
    border: 1.5px solid #d1d5db;
    border-radius: 8px;
    padding: 9px 14px;
    font-size: 15px;
}
.form-control:focus, .form-select:focus {
    border-color: #818cf8;
    box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
}

.main-table-wrap {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    overflow: hidden;
}

.table { margin: 0; font-size: 15px; }

.table thead th {
    background: #1e293b;
    color: #fff;
    font-size: 13px;
    font-weight: 600;
    padding: 13px 12px;
    text-align: center;
    border: 1px solid #374151;
    letter-spacing: 0.3px;
}

.table tbody td {
    padding: 12px;
    text-align: center;
    vertical-align: middle;
    border: 1px solid #e5e7eb;
    font-size: 14px;
}

.table tbody tr:hover { background: #f9fafb; }

.badge-settled { background: #d1fae5; color: #065f46; padding: 5px 12px; border-radius: 6px; font-size: 13px; font-weight: 600; }
.badge-pending { background: #fef3c7; color: #92400e; padding: 5px 12px; border-radius: 6px; font-size: 13px; font-weight: 600; }

.tx-hash {
    font-family: monospace;
    font-size: 12px;
    color: #6b7280;
    cursor: help;
}

.empty-msg { text-align: center; padding: 40px; color: #9ca3af; font-size: 16px; }
</style>

</head>

<body>

<?php include '../includes/header.php'; ?>
<div class="main-content">
<div style="margin-top: 80px; padding: 20px; max-width: 1400px; margin-left: auto; margin-right: auto;">

    <div class="top-bar">
        <h4><i class="bi bi-arrow-left-right me-2 text-info"></i>All Trades</h4>
        <div class="top-stats">
            <div>
                <div class="top-stat-val"><?= $totalCount ?></div>
                <div class="top-stat-lbl">Total</div>
            </div>
        <!--    <div>
                <div class="top-stat-val" style="color:#10b981"><?= $settled ?></div>
                <div class="top-stat-lbl">Settled</div>
            </div>
            <div>
                <div class="top-stat-val" style="color:#f59e0b"><?= $pending ?></div>
                <div class="top-stat-lbl">Pending</div>
            </div>-->
            <div>
                <div class="top-stat-val">₹<?= number_format($totalAmount) ?></div>
                <div class="top-stat-lbl">Amount</div>
            </div>
            <div>
                <div class="top-stat-val"><?= number_format($totalUnits,1) ?></div>
                <div class="top-stat-lbl">kWh</div>
            </div>
        </div>
    </div>

    <div class="filter-bar">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label fw-bold" style="font-size:12px; color:#6b7280;">Date</label>
                <input type="date" name="date" value="<?= $date_filter ?>" class="form-control">
            </div>
        <!--    <div class="col-md-2">
                <label class="form-label fw-bold" style="font-size:12px; color:#6b7280;">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    <option value="settled" <?= $status_filter==='settled'?'selected':'' ?>>Settled</option>
                    <option value="pending" <?= $status_filter==='pending'?'selected':'' ?>>Pending</option>
                </select>
            </div>-->
            <div class="col-md-3">
                <label class="form-label fw-bold" style="font-size:12px; color:#6b7280;">Seller</label>
                <select name="seller" class="form-select">
                    <option value="">All</option>
                    <?php while($s = $sellersDrop->fetch_assoc()): ?>
                    <option value="<?= $s['id'] ?>" <?= $seller_filter==$s['id']?'selected':'' ?>><?= htmlspecialchars($s['name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold" style="font-size:12px; color:#6b7280;">Buyer</label>
                <select name="buyer" class="form-select">
                    <option value="">All</option>
                    <?php while($b = $buyersDrop->fetch_assoc()): ?>
                    <option value="<?= $b['id'] ?>" <?= $buyer_filter==$b['id']?'selected':'' ?>><?= htmlspecialchars($b['name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1" style="border-radius:8px; font-size:14px; font-weight:600;"><i class="bi bi-funnel me-1"></i>Filter</button>
                <?php if(!empty($date_filter)||!empty($status_filter)||!empty($seller_filter)||!empty($buyer_filter)): ?>
                    <a href="trades.php" class="btn btn-outline-secondary" style="border-radius:8px; padding:9px 12px;"><i class="bi bi-x-lg"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="main-table-wrap">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Buyer</th>
                        <th>Seller</th>
                        <th>kWh</th>
                        <th>Price</th>
                        <th>Total (₹)</th>
                        <th>TX Hash</th>
                     <!--   <th>Status</th>-->
                    </tr>
                </thead>
                <tbody>
                <?php if($totalCount > 0): ?>
                <?php $c = 1; ?>
                <?php while($row = $result->fetch_assoc()):
                    $st = $row['settlement_status'] ?? 'pending';
                    $hash = $conn->query("SELECT tx_hash FROM token_ledger WHERE trade_id = {$row['id']} AND token_type = 'transfer_in' LIMIT 1")->fetch_assoc();
                    $txh = $hash['tx_hash'] ?? '';
                ?>
                    <tr>
                        <td><strong><?= $c++ ?></strong></td>
                        <td style="color:#6b7280;"><?= $row['date'] ?? '—' ?></td>
                        <td><?= $row['time_block'] ?? '—' ?></td>
                        <td class="fw-bold"><?= htmlspecialchars($row['buyer_name'] ?? '—') ?></td>
                        <td class="fw-bold"><?= htmlspecialchars($row['seller_name'] ?? '—') ?></td>
                        <td class="fw-bold"><?= $row['units'] ?></td>
                        <td>₹<?= $row['price'] ?></td>
                        <td class="fw-bold" style="color:#059669;">₹<?= number_format($row['total_amount'],2) ?></td>
                        <td>
                            <?php if($txh): ?>
                                <span class="tx-hash" title="<?= $txh ?>"><i class="bi bi-link-45deg me-1"></i><?= substr($txh,0,10) ?>...</span>
                            <?php else: ?>
                                <span style="color:#d1d5db;">—</span>
                            <?php endif; ?>
                        </td>
                    <!--    <td>
                            <?php if($st==='settled'): ?>
                                <span class="badge-settled"><i class="bi bi-check-circle me-1"></i>Settled</span>
                            <?php else: ?>
                                <span class="badge-pending"><i class="bi bi-clock me-1"></i>Pending</span>
                            <?php endif; ?>
                        </td>-->
                    </tr>
                <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="11"><div class="empty-msg">No trades found</div></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
</div>
</body>
</html>