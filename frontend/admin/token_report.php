<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

include '../includes/config.php';

// Get date filters with validation
$from_date = isset($_GET['from']) ? $_GET['from'] : date('Y-m-01'); // Default: first day of current month
$to_date = isset($_GET['to']) ? $_GET['to'] : date('Y-m-d'); // Default: today

// Validate dates and prevent negative range
if (strtotime($from_date) > strtotime($to_date)) {
    // Swap dates if from > to
    $temp = $from_date;
    $from_date = $to_date;
    $to_date = $temp;
}

// Ensure dates are not in future
$today = date('Y-m-d');
if (strtotime($from_date) > strtotime($today)) {
    $from_date = $today;
}
if (strtotime($to_date) > strtotime($today)) {
    $to_date = $today;
}

// Get seller token statistics
$result = $conn->query("
    SELECT 
        u.id,
        u.name,
        u.email,
        COALESCE(SUM(CASE WHEN tl.token_type = 'mint' THEN tl.token_units ELSE 0 END), 0) as total_minted,
        COALESCE(SUM(CASE WHEN tl.token_type = 'transfer_out' THEN tl.token_units ELSE 0 END), 0) as total_transferred,
        COALESCE(SUM(CASE WHEN tl.token_type = 'burn' THEN tl.token_units ELSE 0 END), 0) as total_burned,
        COUNT(DISTINCT CASE WHEN tl.token_type = 'transfer_out' THEN tl.id END) as transaction_count,
        COALESCE(SUM(
            CASE 
                WHEN tl.token_type IN ('mint','transfer_in') THEN tl.token_units
                WHEN tl.token_type IN ('transfer_out','burn') THEN -tl.token_units
                ELSE 0
            END
        ), 0) as current_balance
    FROM users u
    LEFT JOIN token_ledger tl ON u.id = tl.user_id 
        AND tl.created_at BETWEEN '$from_date 00:00:00' AND '$to_date 23:59:59'
    WHERE u.role = 'seller'
    GROUP BY u.id, u.name, u.email
    ORDER BY total_minted DESC
");

// Get totals
$totals = $conn->query("
    SELECT 
        COALESCE(SUM(CASE WHEN token_type = 'mint' THEN token_units ELSE 0 END), 0) as total_minted,
        COALESCE(SUM(CASE WHEN token_type = 'transfer_out' THEN token_units ELSE 0 END), 0) as total_transferred,
        COUNT(DISTINCT CASE WHEN token_type = 'transfer_out' THEN id END) as total_transactions
    FROM token_ledger
    WHERE created_at BETWEEN '$from_date 00:00:00' AND '$to_date 23:59:59'
")->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Token Report - Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
body {
    background: #f0f2f5;
    font-family: 'Segoe UI', sans-serif;
    color: #1f2937;
    font-size: 15px;
}

.main-content {
    margin-top: 80px;
    margin-left: 260px;
    padding: 20px;
    max-width: 1400px;
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

.top-bar h4 {
    font-weight: 700;
    margin: 0;
    font-size: 20px;
}

.filter-card {
    background: #fff;
    border-radius: 12px;
    padding: 20px 24px;
    margin-bottom: 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
}

.filter-card label {
    font-weight: 600;
    font-size: 14px;
    color: #374151;
    margin-bottom: 6px;
}

.filter-card input {
    border: 1.5px solid #d1d5db;
    border-radius: 8px;
    padding: 9px 14px;
    font-size: 15px;
}

.filter-card input:focus {
    border-color: #818cf8;
    box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
    outline: none;
}

.stat-card-mini {
    background: linear-gradient(135deg, #fffbeb, #fef3c7);
    border: 2px solid #fbbf24;
    border-radius: 12px;
    padding: 20px;
    text-align: center;
    box-shadow: 0 4px 12px rgba(251,191,36,0.2);
}

.stat-card-mini h6 {
    font-size: 12px;
    font-weight: 700;
    color: #92400e;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
}

.stat-card-mini h3 {
    font-size: 28px;
    font-weight: 800;
    color: #78350f;
    margin: 0;
}

.main-table-wrap {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    overflow: hidden;
}

.table {
    margin: 0;
    font-size: 14px;
}

.table thead th {
    background: #1e293b;
    color: #ffffff;
    font-size: 13px;
    font-weight: 600;
    padding: 14px 12px;
    text-align: center;
    border: 1px solid #374151;
    letter-spacing: 0.3px;
}

.table tbody td {
    padding: 13px 12px;
    text-align: center;
    vertical-align: middle;
    border: 1px solid #e5e7eb;
    font-size: 14px;
}

.table tbody tr:hover { background: #f9fafb; }

.badge-seller {
    background: linear-gradient(135deg, #fef3c7, #fde68a);
    color: #92400e;
    padding: 5px 12px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
    border: 1px solid #fbbf24;
}

@media (max-width: 992px) {
    .main-content { margin-left: 0; padding: 15px; }
}
</style>

</head>

<body>

<?php include '../includes/header.php'; ?>
<div class="main-content">

    <div class="top-bar">
        <h4><i class="fas fa-coins me-2 text-warning"></i>Token Report</h4>
        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
        </a>
    </div>

    <!-- Date Filter -->
    <div class="filter-card">
        <form method="GET" class="row g-3 align-items-end" onsubmit="return validateDates()">
            <div class="col-md-4">
                <label>From Date</label>
                <input type="date" name="from" id="dateFrom" value="<?= $from_date ?>" max="<?= date('Y-m-d') ?>" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label>To Date</label>
                <input type="date" name="to" id="dateTo" value="<?= $to_date ?>" max="<?= date('Y-m-d') ?>" class="form-control" required>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-funnel me-1"></i>Filter
                </button>
            </div>
        </form>
    </div>

<script>
function validateDates() {
    const fromDate = document.getElementById('dateFrom').value;
    const toDate = document.getElementById('dateTo').value;
    
    if (!fromDate || !toDate) {
        alert('Please select both dates');
        return false;
    }
    
    if (new Date(fromDate) > new Date(toDate)) {
        alert('From date cannot be after To date');
        return false;
    }
    
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    if (new Date(fromDate) > today || new Date(toDate) > today) {
        alert('Dates cannot be in the future');
        return false;
    }
    
    return true;
}
</script>

    <!-- Summary Stats -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="stat-card-mini">
                <h6><i class="fas fa-coins me-1"></i>Total Minted</h6>
                <h3><?= number_format($totals['total_minted'], 2) ?> kWh</h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card-mini">
                <h6><i class="fas fa-exchange-alt me-1"></i>Total Transferred</h6>
                <h3><?= number_format($totals['total_transferred'], 2) ?> kWh</h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card-mini">
                <h6><i class="fas fa-hashtag me-1"></i>Total Transactions</h6>
                <h3><?= number_format($totals['total_transactions']) ?></h3>
            </div>
        </div>
    </div>

    <!-- Seller Token Table -->
    <div class="main-table-wrap">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Seller ID</th>
                        <th>Seller Name</th>
                        <th>Email</th>
                        <th>Tokens Minted (kWh)</th>
                        <th>Tokens Transferred (kWh)</th>
                        <th>Tokens Burned (kWh)</th>
                        <th>Current Balance (kWh)</th>
                        <th>Number of Transactions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if($result && $result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><strong>TKN-<?= str_pad($row['id'], 6, '0', STR_PAD_LEFT) ?></strong></td>
                        <td>
                            <span class="badge-seller">
                                <i class="bi bi-lightning-charge me-1"></i><?= htmlspecialchars($row['name']) ?>
                            </span>
                        </td>
                        <td style="color:#6b7280;"><?= htmlspecialchars($row['email']) ?></td>
                        <td><strong class="text-success"><?= number_format($row['total_minted'], 2) ?></strong></td>
                        <td><strong class="text-primary"><?= number_format($row['total_transferred'], 2) ?></strong></td>
                        <td><strong class="text-danger"><?= number_format($row['total_burned'], 2) ?></strong></td>
                        <td><strong class="text-warning"><?= number_format($row['current_balance'], 2) ?></strong></td>
                        <td><span class="badge bg-info"><?= $row['transaction_count'] ?></span></td>
                    </tr>
                <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">No token data found for selected period</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
