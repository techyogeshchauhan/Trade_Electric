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

 $query = "SELECT el.*, u.name as seller_name FROM energy_listings el LEFT JOIN users u ON u.id = el.user_id WHERE 1";

if (!empty($date_filter)) $query .= " AND el.date = '$date_filter'";
if (!empty($status_filter)) $query .= " AND el.status = '$status_filter'";
if (!empty($seller_filter)) $query .= " AND el.user_id = '$seller_filter'";
 $query .= " ORDER BY el.id DESC";

 $result = $conn->query($query);
 $totalCount = $result->num_rows;

 $countAll = $conn->query("SELECT COUNT(*) as c FROM energy_listings")->fetch_assoc()['c'];
 $countAvail = $conn->query("SELECT COUNT(*) as c FROM energy_listings WHERE status='available'")->fetch_assoc()['c'];
 $countSold = $conn->query("SELECT COUNT(*) as c FROM energy_listings WHERE status='sold'")->fetch_assoc()['c'];
 $totalKwh = $conn->query("SELECT COALESCE(SUM(units_available),0) as c FROM energy_listings")->fetch_assoc()['c'];

 $sellers = $conn->query("SELECT DISTINCT u.id, u.name FROM energy_listings el JOIN users u ON u.id = el.user_id ORDER BY u.name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Energy Listings</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

<style>
body {
    background: #f0f2f5;
    font-family: 'Segoe UI', sans-serif;
    color: #1f2937;
    font-size: 15px;
}

.main-content {
    margin-top: 80px;
    padding: 20px;
    max-width: 1400px;
    margin-left: auto;
    margin-right: auto;
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

.top-stats {
    display: flex;
    gap: 24px;
}
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
    font-size: 14px;
    font-weight: 600;
    padding: 14px 14px;
    text-align: center;
    border: 1px solid #374151;
}

.table tbody td {
    padding: 13px 14px;
    text-align: center;
    vertical-align: middle;
    border: 1px solid #e5e7eb;
    font-size: 15px;
}

.table tbody tr:hover { background: #f9fafb; }

.seller-cell {
    display: flex;
    align-items: center;
    gap: 10px;
    text-align: left;
}
.seller-av {
    width: 36px; height: 36px; border-radius: 50%;
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: #fff; font-weight: 700; font-size: 14px;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.seller-nm { font-weight: 600; color: #111827; }

.bar-wrap { width: 70px; height: 6px; background: #e5e7eb; border-radius: 3px; margin: 4px auto 0; overflow: hidden; }
.bar-fill { height: 100%; border-radius: 3px; }

.badge-avail { background: #d1fae5; color: #065f46; padding: 5px 14px; border-radius: 6px; font-size: 14px; font-weight: 600; }
.badge-sold { background: #fee2e2; color: #991b1b; padding: 5px 14px; border-radius: 6px; font-size: 14px; font-weight: 600; }

.empty-msg { text-align: center; padding: 40px; color: #9ca3af; font-size: 16px; }
</style>

</head>

<body>

<?php include '../includes/header.php'; ?>
<div class="main-content">

    <div class="top-bar">
        <h4><i class="bi bi-lightning-charge-fill me-2 text-success"></i>Energy Listings</h4>
        <div class="top-stats">
            <div>
                <div class="top-stat-val"><?= number_format($totalKwh,1) ?></div>
                <div class="top-stat-lbl">Total kWh</div>
            </div>
            <div>
                <div class="top-stat-val" style="color:#10b981"><?= $countAvail ?></div>
                <div class="top-stat-lbl">Available</div>
            </div>
            <div>
                <div class="top-stat-val" style="color:#ef4444"><?= $countSold ?></div>
                <div class="top-stat-lbl">Sold</div>
            </div>
        </div>
    </div>

    <div class="filter-bar">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-bold" style="font-size:13px; color:#6b7280;">Date</label>
                <input type="date" name="date" value="<?= $date_filter ?>" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold" style="font-size:13px; color:#6b7280;">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    <option value="available" <?= $status_filter==='available'?'selected':'' ?>>Available</option>
                    <option value="sold" <?= $status_filter==='sold'?'selected':'' ?>>Sold</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold" style="font-size:13px; color:#6b7280;">Seller</label>
                <select name="seller" class="form-select">
                    <option value="">All Sellers</option>
                    <?php while($s = $sellers->fetch_assoc()): ?>
                    <option value="<?= $s['id'] ?>" <?= $seller_filter==$s['id']?'selected':'' ?>><?= htmlspecialchars($s['name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1" style="border-radius:8px; font-size:15px; font-weight:600;"><i class="bi bi-funnel me-1"></i>Filter</button>
                <?php if(!empty($date_filter)||!empty($status_filter)||!empty($seller_filter)): ?>
                    <a href="energy.php" class="btn btn-outline-secondary" style="border-radius:8px; padding:9px 14px;"><i class="bi bi-x-lg"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="main-table-wrap">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th style="text-align:left">Seller</th>
                        <th>Date</th>
                        <th>Time Block</th>
                        <th>Total (kWh)</th>
                        <th>Remaining (kWh)</th>
                        <th>Price (₹)</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php if($totalCount > 0): ?>
                <?php while($row = $result->fetch_assoc()):
                    $tot = (float)$row['units_available'];
                    $rem = (float)$row['remaining_units'];
                    $pct = $tot > 0 ? ($rem/$tot)*100 : 0;
                    $clr = $pct > 50 ? '#10b981' : ($pct > 0 ? '#f59e0b' : '#ef4444');
                ?>
                    <tr>
                        <td><strong>#<?= $row['id'] ?></strong></td>
                        <td>
                            <div class="seller-cell">
                                <div class="seller-av"><?= strtoupper(substr($row['seller_name'],0,1)) ?></div>
                                <div class="seller-nm"><?= htmlspecialchars($row['seller_name']) ?></div>
                            </div>
                        </td>
                        <td style="color:#6b7280;"><?= date('d M Y', strtotime($row['date'])) ?></td>
                        <td><?= $row['time_block'] ?></td>
                        <td class="fw-bold"><?= $tot ?></td>
                        <td>
                            <div class="fw-bold"><?= $rem ?></div>
                            <div class="bar-wrap"><div class="bar-fill" style="width:<?= $pct ?>%; background:<?= $clr ?>"></div></div>
                        </td>
                        <td class="fw-bold" style="color:#dc2626;">₹<?= number_format($row['price'],2) ?></td>
                        <td>
                            <?php if($row['status']==='available'): ?>
                                <span class="badge-avail"><i class="bi bi-check-circle me-1"></i>Available</span>
                            <?php else: ?>
                                <span class="badge-sold"><i class="bi bi-x-circle me-1"></i>Sold</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="8"><div class="empty-msg">No listings found</div></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
</body>
</html>