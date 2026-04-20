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

$totalUsers    = safeFetch("SELECT COUNT(*) as total FROM users WHERE role != 'admin'");
$totalSellers  = safeFetch("SELECT COUNT(*) as total FROM users WHERE role='seller'");
$totalBuyers   = safeFetch("SELECT COUNT(*) as total FROM users WHERE role='buyer'");
$totalListings = safeFetch("SELECT COUNT(*) as total FROM energy_listings");
$totalDemands  = safeFetch("SELECT COUNT(*) as total FROM demand_listings");
$totalTrades   = safeFetch("SELECT COUNT(*) as total FROM trades");
$activeSystems = safeFetch("SELECT COUNT(DISTINCT user_id) as total FROM energy_listings WHERE remaining_units > 0");
$totalTokens   = safeFetch("SELECT COALESCE(SUM(token_units),0) as total FROM token_ledger WHERE token_type='mint'");

// Recent Trades
$recent = $conn->query("
    SELECT t.*, u1.name as buyer_name, u2.name as seller_name 
    FROM trades t
    LEFT JOIN users u1 ON t.buyer_id = u1.id
    LEFT JOIN users u2 ON t.seller_id = u2.id
    ORDER BY t.id DESC LIMIT 10
");

// Buyers list for modal
$buyers_list  = $conn->query("SELECT id, name, email FROM users WHERE role='buyer' ORDER BY name ASC");
// Sellers list for modal
$sellers_list = $conn->query("SELECT id, name, email FROM users WHERE role='seller' ORDER BY name ASC");
// Active listings for modal
$active_listings = $conn->query("
    SELECT e.date, e.time_block, e.units_available, e.remaining_units, e.price, u.name as seller_name
    FROM energy_listings e JOIN users u ON e.user_id = u.id
    WHERE e.remaining_units > 0
    ORDER BY e.date DESC LIMIT 50
");
// Active demands for modal
$active_demands = $conn->query("
    SELECT d.date, d.time_block, d.units_required, d.remaining_units, d.max_price, u.name as buyer_name
    FROM demand_listings d JOIN users u ON d.user_id = u.id
    WHERE d.remaining_units > 0
    ORDER BY d.date DESC LIMIT 50
");
// Total trades recent
$all_trades = $conn->query("
    SELECT t.date, t.time_block, t.units, t.price, t.total_amount, t.status, u1.name as buyer_name, u2.name as seller_name
    FROM trades t
    LEFT JOIN users u1 ON t.buyer_id = u1.id
    LEFT JOIN users u2 ON t.seller_id = u2.id
    ORDER BY t.id DESC LIMIT 50
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
    padding: 20px 24px;
    max-width: 1400px;
}

/* ── Stat Cards ── */
.stat-card {
    border-radius: 16px;
    padding: 22px 18px;
    color: white;
    text-align: center;
    box-shadow: 0 8px 20px rgba(0,0,0,0.12);
    transition: 0.25s;
    position: relative;
    overflow: hidden;
    cursor: pointer;
    text-decoration: none;
    display: block;
}
.stat-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 14px 32px rgba(0,0,0,0.2);
    color: white;
    text-decoration: none;
}
.stat-card::after {
    content: '↗ Click to view';
    position: absolute;
    bottom: 8px;
    right: 12px;
    font-size: 10px;
    opacity: 0.7;
    letter-spacing: 0.3px;
}
.stat-card .stat-icon { font-size: 2rem; margin-bottom: 6px; opacity: 0.9; }
.stat-card h6 { font-size: 12px; font-weight: 500; opacity: 0.85; margin-bottom: 2px; text-transform: uppercase; letter-spacing: 0.5px; }
.stat-card h2 { font-weight: 800; margin-bottom: 6px; font-size: 2.2rem; }
.stat-card small { font-size: 12px; opacity: 0.85; }

/* Card sub-links */
.stat-card .sub-links { display: flex; gap: 8px; justify-content: center; margin-top: 8px; }
.stat-card .sub-link {
    background: rgba(255,255,255,0.25);
    border: 1px solid rgba(255,255,255,0.4);
    color: white;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: 0.15s;
}
.stat-card .sub-link:hover { background: rgba(255,255,255,0.4); }

/* Card Colors */
.bg-users    { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
.bg-listings { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
.bg-demands  { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: #1e3a5f; }
.bg-trades   { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: #064e3b; }
.bg-systems  { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: #7c2d12; }
.bg-tokens   { background: linear-gradient(135deg, #ffd89b 0%, #19547b 100%); }

/* ── Cards ── */
.card {
    border-radius: 16px;
    box-shadow: 0 6px 20px rgba(0,0,0,0.07);
    border: none;
}
.card-header {
    background: #fff;
    border-bottom: 2px solid #f1f5f9;
    padding: 16px 20px;
    font-weight: 700;
    font-size: 16px;
    color: #1e293b;
    border-radius: 16px 16px 0 0 !important;
}
.table { margin: 0; font-size: 14px; }
.table th {
    background: #1e293b; color: #fff;
    text-align: center; padding: 11px 8px;
    font-size: 13px; font-weight: 600;
}
.table td {
    text-align: center;
    vertical-align: middle;
    padding: 11px 8px;
    font-size: 13px;
}
.table tbody tr:hover { background: #f8fafc; }

/* Modal table */
.modal-table th { background: #1e293b; color: #fff; padding: 10px 12px; font-size: 13px; }
.modal-table td { padding: 10px 12px; font-size: 13px; border-bottom: 1px solid #f1f5f9; }

/* Marketplace section */
.mkt-card {
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.07);
    overflow: hidden;
    margin-bottom: 20px;
}
.mkt-header {
    padding: 14px 20px;
    font-weight: 700;
    font-size: 15px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.mkt-header.yellow { background: linear-gradient(135deg,#fbbf24,#f59e0b); color: #78350f; }
.mkt-header.blue   { background: linear-gradient(135deg,#60a5fa,#3b82f6); color: #fff; }
.mkt-header.dark   { background: #1e293b; color: #fff; }

@media (max-width: 992px) {
    .main-content { margin-left: 0; padding: 15px; }
}
</style>

</head>
<body>

<?php include '../includes/header.php'; ?>

<div class="main-content">

<!-- Top Bar -->
<div style="background:#fff;border-radius:12px;padding:18px 24px;margin-bottom:20px;box-shadow:0 2px 10px rgba(0,0,0,0.06);display:flex;justify-content:space-between;align-items:center;">
    <h4 style="font-weight:700;margin:0;font-size:20px;"><i class="bi bi-speedometer2 me-2 text-primary"></i>Admin Dashboard</h4>
    <small class="text-muted"><?= date('d-m-Y h:i A') ?></small>
</div>

<!-- ═══════ ROW 1: KPI CARDS ═══════ -->
<div class="row g-3 mb-4">

    <!-- Total Users (clickable → modal) -->
    <div class="col-md-4 col-lg-2">
        <a class="stat-card bg-users" href="#" data-bs-toggle="modal" data-bs-target="#modalAllUsers">
            <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
            <h6>Total Users</h6>
            <h2><?= $totalUsers ?></h2>
            <small>Buyers: <?= $totalBuyers ?> | Sellers: <?= $totalSellers ?></small>
        </a>
    </div>

    <!-- Total Listings -->
    <div class="col-md-4 col-lg-2">
        <a class="stat-card bg-listings" href="#" data-bs-toggle="modal" data-bs-target="#modalListings">
            <div class="stat-icon"><i class="bi bi-lightning-charge-fill"></i></div>
            <h6>Energy Listings</h6>
            <h2><?= $totalListings ?></h2>
            <small>Active energy supply</small>
        </a>
    </div>

    <!-- Total Demands -->
    <div class="col-md-4 col-lg-2">
        <a class="stat-card bg-demands" href="#" data-bs-toggle="modal" data-bs-target="#modalDemands">
            <div class="stat-icon"><i class="bi bi-cart-fill"></i></div>
            <h6>Buyer Demands</h6>
            <h2><?= $totalDemands ?></h2>
            <small>Active demand listings</small>
        </a>
    </div>

    <!-- Total Trades -->
    <div class="col-md-4 col-lg-2">
        <a class="stat-card bg-trades" href="trades.php">
            <div class="stat-icon"><i class="bi bi-arrow-left-right"></i></div>
            <h6>Total Trades</h6>
            <h2><?= $totalTrades ?></h2>
            <small>Completed matches</small>
        </a>
    </div>

    <!-- Active Systems -->
    <div class="col-md-4 col-lg-2">
        <a class="stat-card bg-systems" href="#" data-bs-toggle="modal" data-bs-target="#modalListings">
            <div class="stat-icon"><i class="bi bi-cpu-fill"></i></div>
            <h6>Active Systems</h6>
            <h2><?= $activeSystems ?></h2>
            <small>Sellers with supply</small>
        </a>
    </div>

    <!-- Total Tokens -->
    <div class="col-md-4 col-lg-2">
        <a class="stat-card bg-tokens" href="token_report.php">
            <div class="stat-icon"><i class="fas fa-coins"></i></div>
            <h6>Total Tokens</h6>
            <h2><?= number_format($totalTokens, 0) ?></h2>
            <small>kWh minted</small>
        </a>
    </div>

</div>

<!-- ═══════ MARKETPLACE SECTION ═══════ -->
<div class="row g-3 mb-4">
    <!-- Available Energy -->
    <div class="col-lg-6">
        <div class="mkt-card">
            <div class="mkt-header yellow"><i class="bi bi-lightning-charge-fill"></i> Available Energy &nbsp;<small style="margin-left:auto;background:rgba(255,255,255,0.4);padding:2px 10px;border-radius:12px;font-size:12px;">Cheapest first</small></div>
            <div class="table-responsive" style="max-height:280px;overflow-y:auto;">
                <table class="table mb-0">
                    <thead><tr><th>Date</th><th>Time</th><th>Seller</th><th>Units</th><th>Price</th><th>Left</th></tr></thead>
                    <tbody id="adminListingTable"><tr><td colspan="6" class="text-center py-3 text-muted">Loading...</td></tr></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Buyer Demands -->
    <div class="col-lg-6">
        <div class="mkt-card">
            <div class="mkt-header blue"><i class="bi bi-person-fill"></i> Buyer Demands &nbsp;<small style="margin-left:auto;background:rgba(255,255,255,0.25);padding:2px 10px;border-radius:12px;font-size:12px;">Highest price first</small></div>
            <div class="table-responsive" style="max-height:280px;overflow-y:auto;">
                <table class="table mb-0">
                    <thead><tr><th>Date</th><th>Time</th><th>Buyer</th><th>Units</th><th>Price</th><th>Left</th></tr></thead>
                    <tbody id="adminDemandTable"><tr><td colspan="6" class="text-center py-3 text-muted">Loading...</td></tr></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Live Matches -->
<div class="mkt-card mb-4">
    <div class="mkt-header dark"><i class="bi bi-fire"></i> Live Best Matches &nbsp;<small style="opacity:0.6;font-weight:400;font-size:12px;">Updated every 3s</small></div>
    <div class="table-responsive" style="max-height:280px;overflow-y:auto;">
        <table class="table mb-0">
            <thead><tr><th>Date</th><th>Time</th><th>Units</th><th>Seller</th><th>Seller Units</th><th>Buyer</th><th>Buyer Units</th><th>Price</th><th>Status</th></tr></thead>
            <tbody id="adminMatchTable"><tr><td colspan="9" class="text-center py-3 text-muted">Loading matches...</td></tr></tbody>
        </table>
    </div>
</div>

<!-- ═══════ RECENT TRADES TABLE ═══════ -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Recent Trades & Settlements</h5>
        <a href="trades.php" class="btn btn-sm btn-outline-secondary">View All</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Contract ID</th><th>Date</th><th>Time Block</th>
                        <th>Buyer</th><th>Seller</th><th>Units (kWh)</th>
                        <th>Price (₹/kWh)</th><th>Total (₹)</th>
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
                    <tr><td colspan="8" class="text-muted py-3 text-center">No trades yet</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</div><!-- /main-content -->

<!-- ═══════ MODALS ═══════ -->

<!-- Modal: All Users -->
<div class="modal fade" id="modalAllUsers" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-people-fill me-2"></i>All Users</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <!-- Tab filters -->
                <div class="p-3 border-bottom d-flex gap-2">
                    <button class="btn btn-sm btn-primary" onclick="filterUserTable('all')">All (<?= $totalUsers ?>)</button>
                    <button class="btn btn-sm btn-outline-info" onclick="filterUserTable('buyer')">Buyers (<?= $totalBuyers ?>)</button>
                    <button class="btn btn-sm btn-outline-success" onclick="filterUserTable('seller')">Sellers (<?= $totalSellers ?>)</button>
                </div>
                <div class="table-responsive">
                    <table class="table modal-table mb-0" id="userTable">
                        <thead><tr><th>#</th><th>Name</th><th>Role</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php
                            $all_users = $conn->query("SELECT id, name, role FROM users WHERE role != 'admin' ORDER BY role, name");
                            $i = 1;
                            while($u = $all_users->fetch_assoc()):
                            $badge = $u['role'] === 'buyer' ? 'bg-info' : 'bg-success';
                            ?>
                            <tr data-role="<?= $u['role'] ?>">
                                <td><?= $i++ ?></td>
                                <td><?= htmlspecialchars($u['name']) ?></td>
                                <td><span class="badge <?= $badge ?>"><?= ucfirst($u['role']) ?></span></td>
                                <td><a href="users.php" class="btn btn-xs btn-outline-secondary" style="padding:2px 10px;font-size:12px;">Manage</a></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Energy Listings -->
<div class="modal fade" id="modalListings" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header text-white" style="background:linear-gradient(135deg,#f093fb,#f5576c)">
                <h5 class="modal-title"><i class="bi bi-lightning-charge-fill me-2"></i>Active Energy Listings (<?= $totalListings ?>)</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table modal-table mb-0">
                        <thead><tr><th>#</th><th>Date</th><th>Time Block</th><th>Seller</th><th>Units (kWh)</th><th>Remaining</th><th>Price (₹)</th></tr></thead>
                        <tbody>
                            <?php
                            $all_listings = $conn->query("SELECT e.date, e.time_block, e.units_available, e.remaining_units, e.price, u.name as seller_name FROM energy_listings e JOIN users u ON e.user_id = u.id WHERE e.remaining_units > 0 ORDER BY e.date DESC, e.price ASC LIMIT 100");
                            $i = 1;
                            if($all_listings && $all_listings->num_rows > 0):
                            while($l = $all_listings->fetch_assoc()): ?>
                            <tr>
                                <td><?= $i++ ?></td>
                                <td><?= date('d-m-Y', strtotime($l['date'])) ?></td>
                                <td><?= htmlspecialchars($l['time_block']) ?></td>
                                <td><strong><?= htmlspecialchars($l['seller_name']) ?></strong></td>
                                <td><?= number_format($l['units_available'], 2) ?></td>
                                <td><?= number_format($l['remaining_units'], 2) ?></td>
                                <td>₹<?= number_format($l['price'], 2) ?></td>
                            </tr>
                            <?php endwhile; else: ?>
                            <tr><td colspan="7" class="text-center py-3 text-muted">No active listings</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Buyer Demands -->
<div class="modal fade" id="modalDemands" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header text-white" style="background:linear-gradient(135deg,#4facfe,#00f2fe)">
                <h5 class="modal-title" style="color:#1e3a5f"><i class="bi bi-cart-fill me-2"></i>Active Buyer Demands (<?= $totalDemands ?>)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table modal-table mb-0">
                        <thead><tr><th>#</th><th>Date</th><th>Time Block</th><th>Buyer</th><th>Units Req. (kWh)</th><th>Remaining</th><th>Max Price (₹)</th></tr></thead>
                        <tbody>
                            <?php
                            $all_demands = $conn->query("SELECT d.date, d.time_block, d.units_required, d.remaining_units, d.max_price, u.name as buyer_name FROM demand_listings d JOIN users u ON d.user_id = u.id WHERE d.remaining_units > 0 ORDER BY d.date DESC, d.max_price DESC LIMIT 100");
                            $i = 1;
                            if($all_demands && $all_demands->num_rows > 0):
                            while($d = $all_demands->fetch_assoc()): ?>
                            <tr>
                                <td><?= $i++ ?></td>
                                <td><?= date('d-m-Y', strtotime($d['date'])) ?></td>
                                <td><?= htmlspecialchars($d['time_block']) ?></td>
                                <td><strong><?= htmlspecialchars($d['buyer_name']) ?></strong></td>
                                <td><?= number_format($d['units_required'], 2) ?></td>
                                <td><?= number_format($d['remaining_units'], 2) ?></td>
                                <td>₹<?= number_format($d['max_price'], 2) ?></td>
                            </tr>
                            <?php endwhile; else: ?>
                            <tr><td colspan="7" class="text-center py-3 text-muted">No active demands</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script>
// Open users modal with pre-filter
function openModal(filter) {
    filterUserTable(filter);
    new bootstrap.Modal(document.getElementById('modalAllUsers')).show();
}

// Filter user table by role
function filterUserTable(role) {
    const rows = document.querySelectorAll('#userTable tbody tr');
    rows.forEach(row => {
        if (role === 'all' || row.dataset.role === role) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
    // Update button styles
    document.querySelectorAll('.p-3.border-bottom .btn').forEach(btn => btn.classList.remove('btn-primary'));
}

// Load marketplace data for admin (no date filter)
function loadAdminMarket() {
    $.get("../../api/get_admin_marketplace.php?type=listings", function(res) {
        $("#adminListingTable").html(res);
    });
    $.get("../../api/get_admin_marketplace.php?type=demands", function(res) {
        $("#adminDemandTable").html(res);
    });
    $.get("../../api/get_market_matches.php", function(res) {
        $("#adminMatchTable").html(res);
    });
}

$(document).ready(function() {
    loadAdminMarket();
});

setInterval(loadAdminMarket, 3000);
</script>

</body>
</html>
