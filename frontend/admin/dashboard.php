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
 $totalEnergy   = safeFetch("SELECT COALESCE(SUM(units_available),0) as total FROM energy_listings");

 $totalMinted      = safeFetch("SELECT COALESCE(SUM(token_units),0) as total FROM token_ledger WHERE token_type='mint'");
 $totalTransferred = safeFetch("SELECT COALESCE(SUM(token_units),0) as total FROM token_ledger WHERE token_type='transfer_out'");
 $totalBurned      = safeFetch("SELECT COALESCE(SUM(token_units),0) as total FROM token_ledger WHERE token_type='burn'");
 $activeTokens    = safeFetch("
    SELECT COALESCE(
        SUM(CASE WHEN token_type IN ('mint','transfer_in') THEN token_units
                 WHEN token_type IN ('transfer_out','burn') THEN -token_units ELSE 0 END), 0
    ) as total FROM token_ledger
");

 $recent = $conn->query("
    SELECT t.*, u1.name as buyer_name, u2.name as seller_name 
    FROM trades t
    LEFT JOIN users u1 ON t.buyer_id = u1.id
    LEFT JOIN users u2 ON t.seller_id = u2.id
    ORDER BY t.id DESC LIMIT 5
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
    padding: 20px;
    max-width: 1400px;
    margin-left: auto;
    margin-right: auto;
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

/* ── Clickable Sub-links (always visible) ── */
.stat-links {
    display: flex;
    justify-content: center;
    gap: 6px;
    flex-wrap: wrap;
}

.stat-link {
    background: rgba(255,255,255,0.2);
    color: white;
    padding: 4px 14px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    text-decoration: none;
    transition: 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.stat-link:hover {
    background: rgba(255,255,255,0.4);
    color: white;
    text-decoration: none;
}

.stat-link-arrow {
    font-size: 10px;
    opacity: 0;
    transition: 0.2s;
}
.stat-link:hover .stat-link-arrow {
    opacity: 1;
    transform: translateX(2px);
}

/* ── Token Cards ── */
.token-card {
    background: linear-gradient(135deg, #fffbeb, #fef3c7) !important;
    border: 2px solid #fbbf24;
    color: #78350f;
}
.token-card h6 { color: #92400e !important; font-weight: 700; }
.token-card h2 { color: #b45309 !important; font-weight: 800; }
.token-card .stat-icon { opacity: 1; }
.token-card small { color: #92400e; }

/* ── Cards ── */
.card {
    border-radius: 16px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.08);
}

.table th {
    background: #0ea5e9;
    color: white;
    text-align: center;
}
.table td {
    text-align: center;
    vertical-align: middle;
}

/* ── Quick Action Buttons ── */
.action-btn {
    border-radius: 10px;
    padding: 12px 16px;
    font-size: 14px;
    font-weight: 600;
    transition: 0.2s;
    text-decoration: none;
}
.action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    color: inherit;
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

    <!-- Users -->
    <div class="col-md-3">
        <div class="stat-card bg-primary">
            <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
            <h6>Total Users</h6>
            <h2><?= $totalUsers ?></h2>
            <div class="stat-links">
                <a href="users.php?filter=seller" class="stat-link">
                    Sellers: <?= $totalSellers ?> <span class="stat-link-arrow">→</span>
                </a>
                <a href="users.php?filter=buyer" class="stat-link">
                    Buyers: <?= $totalBuyers ?> <span class="stat-link-arrow">→</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Energy Listings -->
    <div class="col-md-3">
        <div class="stat-card bg-success">
            <div class="stat-icon"><i class="bi bi-lightning-charge-fill"></i></div>
            <h6>Energy Listings</h6>
            <h2><?= $totalListings ?></h2>
            <div class="stat-links">
                <a href="energy.php" class="stat-link">
                    <?= number_format($totalEnergy, 1) ?> kWh listed <span class="stat-link-arrow">→</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Demands → Marketplace -->
    <div class="col-md-3">
        <div class="stat-card bg-warning">
            <div class="stat-icon"><i class="bi bi-cart-check-fill"></i></div>
            <h6>Demands</h6>
            <h2><?= $totalDemands ?></h2>
            <div class="stat-links">
                <a href="../marketplace.php" class="stat-link">
                    View Marketplace <span class="stat-link-arrow">→</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Trades -->
    <div class="col-md-3">
        <div class="stat-card bg-info">
            <div class="stat-icon"><i class="bi bi-arrow-left-right"></i></div>
            <h6>Trades</h6>
            <h2><?= $totalTrades ?></h2>
            <div class="stat-links">
                <a href="trades.php" class="stat-link">
                    View All <span class="stat-link-arrow">→</span>
                </a>
              <!--  <a href="settlement.php" class="stat-link">
                    Settlement <span class="stat-link-arrow">→</span>
                </a>-->
            </div>
        </div>
    </div>

</div>

<!-- ═══════ ROW 2: TOKEN STATS ═══════ -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="stat-card token-card">
            <div class="stat-icon"><i class="fas fa-coins"></i></div>
            <h6>Total Minted</h6>
            <h2><?= number_format($totalMinted, 2) ?></h2>
            <small>By all sellers</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card token-card">
            <div class="stat-icon"><i class="fas fa-exchange-alt"></i></div>
            <h6>Total Transferred</h6>
            <h2><?= number_format($totalTransferred, 2) ?></h2>
            <small>Across all trades</small>
        </div>
    </div>
  <!--  <div class="col-md-3">
        <div class="stat-card token-card">
            <div class="stat-icon"><i class="fas fa-fire"></i></div>
            <h6>Total Burned</h6>
            <h2><?= number_format($totalBurned, 2) ?></h2>
            <small>After settlement</small>
        </div>
    </div>-->
    <div class="col-md-3">
        <div class="stat-card token-card">
            <div class="stat-icon"><i class="fas fa-wallet"></i></div>
            <h6>Active in System</h6>
            <h2><?= number_format($activeTokens, 2) ?></h2>
            <small>Not yet burned</small>
        </div>
    </div>
</div>

<div class="row g-4">

    <!-- QUICK ACTIONS -->
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-grid me-2"></i>Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-6">
                        <a href="users.php?filter=seller" class="btn btn-outline-primary action-btn w-100 d-block text-center">
                            <i class="bi bi-people me-1"></i> Sellers
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="users.php?filter=buyer" class="btn btn-outline-success action-btn w-100 d-block text-center">
                            <i class="bi bi-person me-1"></i> Buyers
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="energy.php" class="btn btn-outline-info action-btn w-100 d-block text-center">
                            <i class="bi bi-lightning me-1"></i> Energy
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="../marketplace.php" class="btn btn-outline-warning action-btn w-100 d-block text-center">
                            <i class="bi bi-shop me-1"></i> Marketplace
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="trades.php" class="btn btn-outline-secondary action-btn w-100 d-block text-center">
                            <i class="bi bi-arrow-left-right me-1"></i> Trades
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="../dashboard/settlement.php" class="btn btn-outline-danger action-btn w-100 d-block text-center">
                            <i class="bi bi-gavel me-1"></i> Settlement
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="charges_settings.php" class="btn btn-outline-dark action-btn w-100 d-block text-center">
                            <i class="bi bi-gear me-1"></i> Charges
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="../dashboard/wallet.php" class="btn btn-outline-info action-btn w-100 d-block text-center">
                            <i class="bi bi-wallet me-1"></i> Wallet
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- RECENT TRADES -->
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Recent Trades</h5>
                <a href="trades.php" class="btn btn-sm btn-outline-secondary">View All</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Buyer</th>
                                <th>Seller</th>
                                <th>Units</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($recent && $recent->num_rows > 0): ?>
                            <?php while($r = $recent->fetch_assoc()): ?>
                            <tr>
                                <td><?= $r['date'] ?? '—' ?></td>
                                <td><?= htmlspecialchars($r['buyer_name'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($r['seller_name'] ?? '-') ?></td>
                                <td><?= $r['units'] ?> kWh</td>
                                <td><b>₹ <?= number_format($r['total_amount'], 2) ?></b></td>
                            </tr>
                            <?php endwhile; ?>
                            <?php else: ?>
                            <tr><td colspan="5" class="text-muted py-3">No trades yet</td></tr>
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