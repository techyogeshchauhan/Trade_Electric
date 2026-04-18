<?php
session_start();

if (!isset($_SESSION['user_id']) || strtolower(trim($_SESSION['role'])) !== 'admin') {
    header("Location: ../login.php");
    exit();
}

include '../includes/config.php';

function safeFetch($query, $key = 'total') {
    global $conn;
    $result = $conn->query($query);
    if ($result && $row = $result->fetch_assoc()) {
        return $row[$key] ?? 0;
    }
    return 0;
}

$totalUsers    = safeFetch("SELECT COUNT(*) as total FROM users");
$totalSellers  = safeFetch("SELECT COUNT(*) as total FROM users WHERE role='seller'");
$totalBuyers   = safeFetch("SELECT COUNT(*) as total FROM users WHERE role='buyer'");
$totalListings = safeFetch("SELECT COUNT(*) as total FROM energy_listings");
$totalDemands  = safeFetch("SELECT COUNT(*) as total FROM demand_listings");
$totalTrades   = safeFetch("SELECT COUNT(*) as total FROM trades");

// Active Systems = Sellers with active listings (remaining_units > 0)
$activeSystems = safeFetch("SELECT COUNT(DISTINCT user_id) as total FROM energy_listings WHERE remaining_units > 0");

// Total Tokens = Sum of all minted tokens (accurate seller generation)
$totalTokens = safeFetch("SELECT COALESCE(SUM(token_units),0) as total FROM token_ledger WHERE token_type='mint'");

// Recent Trades & Settlements
$recent = $conn->query("
    SELECT t.*, u1.name as buyer_name, u2.name as seller_name 
    FROM trades t
    LEFT JOIN users u1 ON t.buyer_id = u1.id
    LEFT JOIN users u2 ON t.seller_id = u2.id
    ORDER BY t.id DESC LIMIT 10
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard - EnergyTrade</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
* { box-sizing: border-box; }
html, body { overflow-x: hidden; }

body {
    background: #f0f2f5;
    font-family: 'Segoe UI', sans-serif;
    color: #1f2937;
    font-size: 15px;
    margin: 0;
}

.main-content {
    margin-top: 80px;
    margin-left: 260px;
    padding: 20px;
    max-width: 1400px;
}

/* ── Stat Cards ── */
.stat-card {
    border-radius: 14px;
    padding: 24px 20px;
    color: white;
    text-align: center;
    box-shadow: 0 8px 20px rgba(0,0,0,0.12);
    transition: 0.3s;
    position: relative;
    overflow: hidden;
}
.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 28px rgba(0,0,0,0.18);
}

.stat-card .stat-icon {
    font-size: 2rem;
    margin-bottom: 8px;
    opacity: 0.9;
}

.stat-card h6 {
    font-size: 13px;
    font-weight: 500;
    opacity: 0.85;
    margin-bottom: 4px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-card h2 {
    font-weight: 800;
    margin-bottom: 10px;
}

/* Card Colors */
.bg-users { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
.bg-listings { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
.bg-systems { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
.bg-tokens { background: linear-gradient(135deg, #ffd89b 0%, #19547b 100%); }

/* ── Cards ── */
.card {
    border-radius: 16px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.08);
    border: none;
}

.card-header {
    background: #fff;
    border-bottom: 2px solid #f1f5f9;
    padding: 16px 20px;
    font-weight: 700;
    font-size: 16px;
    color: #1e293b;
}

.table {
    margin: 0;
    font-size: 14px;
}

.table th {
    background: #1e293b;
    color: #ffffff;
    text-align: center;
    padding: 12px 8px;
    font-size: 13px;
    font-weight: 600;
}
.table td {
    text-align: center;
    vertical-align: middle;
    padding: 12px 8px;
    font-size: 14px;
}

@media (max-width: 992px) {
    .main-content { margin-left: 0; padding: 15px; }
}
</style>

</head>

<body>

<?php include '../includes/header.php'; ?>

<div class="main-content">

<div class="top-bar" style="background: #fff; border-radius: 12px; padding: 20px 28px; margin-bottom: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); display: flex; justify-content: space-between; align-items: center;">
    <h4 style="font-weight: 700; margin: 0; font-size: 20px;"><i class="bi bi-speedometer2 me-2 text-primary"></i>Admin Dashboard</h4>
    <small class="text-muted"><?= date('d-m-Y h:i A') ?></small>
</div>

<!-- ═══════ ROW 1: MAIN STATS ═══════ -->
<div class="row g-4 mb-4">

    <!-- Total Users -->
    <div class="col-md-3">
        <div class="stat-card bg-users">
            <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
            <h6>Total Users</h6>
            <h2><?= $totalUsers ?></h2>
            <small>Buyers: <?= $totalBuyers ?> | Sellers: <?= $totalSellers ?></small>
        </div>
    </div>

    <!-- Total Listings -->
    <div class="col-md-3">
        <div class="stat-card bg-listings">
            <div class="stat-icon"><i class="bi bi-lightning-charge-fill"></i></div>
            <h6>Total Listings</h6>
            <h2><?= $totalListings ?></h2>
            <small>Energy listings created</small>
        </div>
    </div>

    <!-- Active Systems -->
    <div class="col-md-3">
        <div class="stat-card bg-systems">
            <div class="stat-icon"><i class="bi bi-cpu-fill"></i></div>
            <h6>Active Systems</h6>
            <h2><?= $activeSystems ?></h2>
            <small>Sellers with active listings</small>
        </div>
    </div>

    <!-- Total Tokens -->
    <div class="col-md-3">
        <div class="stat-card bg-tokens">
            <div class="stat-icon"><i class="fas fa-coins"></i></div>
            <h6>Total Tokens</h6>
            <h2><?= number_format($totalTokens, 0) ?></h2>
            <small>kWh minted by sellers</small>
        </div>
    </div>

</div>

<div class="row g-4">

    <!-- RECENT TRADES & SETTLEMENTS -->
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Recent Trades & Settlements</h5>
                <a href="trades.php" class="btn btn-sm btn-outline-secondary">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Date</th>
                                <th>Time Block</th>
                                <th>Buyer</th>
                                <th>Seller</th>
                                <th>Units (kWh)</th>
                                <th>Price (₹/kWh)</th>
                                <th>Total Amount (₹)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($recent && $recent->num_rows > 0): ?>
                            <?php while($r = $recent->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($r['contract_id'] ?? 'TRD-'.str_pad($r['id'], 5, '0', STR_PAD_LEFT)) ?></strong></td>
                                <td><?= date('d-m-Y', strtotime($r['date'] ?? $r['created_at'])) ?></td>
                                <td><?= htmlspecialchars($r['time_block'] ?? '—') ?></td>
                                <td><?= htmlspecialchars($r['buyer_name'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($r['seller_name'] ?? '-') ?></td>
                                <td><?= number_format($r['units'], 2) ?></td>
                                <td>₹<?= number_format($r['price'], 2) ?></td>
                                <td><strong>₹<?= number_format($r['total_amount'], 2) ?></strong></td>
                            </tr>
                            <?php endwhile; ?>
                            <?php else: ?>
                            <tr><td colspan="8" class="text-muted py-3">No trades yet</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
