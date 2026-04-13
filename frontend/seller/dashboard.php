<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'seller') {
    header("Location: ../login.php");
    exit();
}

include '../includes/config.php';

 $user_id = $_SESSION['user_id'];

 $userQuery = $conn->query("SELECT name FROM users WHERE id = $user_id");
 $userData = $userQuery->fetch_assoc();
 $userName = $userData['name'] ?? 'Prosumer';

function formatDate($date){
    return date('d-m-Y', strtotime($date));
}

 $totalListings = $conn->query("SELECT COUNT(*) as total FROM energy_listings WHERE user_id = $user_id")->fetch_assoc()['total'] ?? 0;
 $soldUnits     = $conn->query("SELECT COALESCE(SUM(units_available - remaining_units), 0) as total FROM energy_listings WHERE user_id = $user_id")->fetch_assoc()['total'];
 $earnings      = $conn->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM trades WHERE seller_id = $user_id")->fetch_assoc()['total'];
 $wallet        = $earnings;

// ✅ TOKEN STATS
 $totalMinted = $conn->query("
    SELECT COALESCE(SUM(token_units), 0) as total 
    FROM token_ledger 
    WHERE user_id = $user_id AND token_type = 'mint'
")->fetch_assoc()['total'] ?? 0;

 $totalSoldTokens = $conn->query("
    SELECT COALESCE(SUM(token_units), 0) as total 
    FROM token_ledger 
    WHERE user_id = $user_id AND token_type = 'transfer_out'
")->fetch_assoc()['total'] ?? 0;

 $tokenBalanceQuery = $conn->query("
    SELECT 
        SUM(
            CASE 
                WHEN token_type IN ('mint','transfer_in') THEN token_units
                WHEN token_type IN ('transfer_out','burn') THEN -token_units
                ELSE 0
            END
        ) as balance
    FROM token_ledger 
    WHERE user_id = $user_id
");

$tokenRow = $tokenBalanceQuery->fetch_assoc();

// Ensure token balance is always positive or zero
$tokenBalance = isset($tokenRow['balance']) && $tokenRow['balance'] !== null
    ? max(0, (float)$tokenRow['balance'])
    : 0;

 $trades = $conn->query("SELECT * FROM trades WHERE seller_id = $user_id ORDER BY id DESC LIMIT 5");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Seller Dashboard</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body {
    display: flex;
    background: #f8fafc;
}

.main-content {
    margin-top: 80px;
    margin-left: 260px;
    padding: 20px;
    width: calc(100% - 260px);
    overflow-x: hidden;
}

.stat-card {
    border-radius: 12px;
    padding: 20px;
    color: white;
    text-align: center;
    transition: 0.3s;
}
.stat-card:hover {
    transform: translateY(-5px);
}

.card { border-radius: 12px; }

.table th {
    background: #0ea5e9;
    color: white;
    text-align: center;
}
.table td {
    text-align: center;
    vertical-align: middle;
}

/* ✅ TOKEN CARD SPECIAL */
.token-card {
    background: linear-gradient(135deg, #1a1a2e, #16213e) !important;
    border: 2px solid #ffc107;
    position: relative;
    overflow: hidden;
}
.token-card::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 100%;
    height: 100%;
    background: radial-gradient(circle, rgba(255,193,7,0.1) 0%, transparent 70%);
}
.token-card h3 {
    color: #ffc107 !important;
}
</style>
</head>

<body>

<?php include '../includes/header.php'; ?>

<div class="main-content container-fluid">

    <h2 class="mb-4">
        <span class="text-primary fw-bold"><?= htmlspecialchars($userName) ?></span> - Dashboard
    </h2>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card bg-primary">
                <h6>Total Listings</h6>
                <h3><?= $totalListings ?></h3>
            </div>
        </div>

        <div class="col-md-3">
            <div class="stat-card bg-success">
                <h6>Units Sold (kWh)</h6>
                <h3><?= number_format($soldUnits,2) ?></h3>
            </div>
        </div>

        <div class="col-md-3">
            <div class="stat-card bg-warning">
                <h6>Earnings</h6>
                <h3>₹ <?= number_format($earnings) ?></h3>
            </div>
        </div>

        <div class="col-md-3">
            <div class="stat-card bg-info">
                <h6>Wallet</h6>
                <h3>₹ <?= number_format($wallet) ?></h3>
            </div>
        </div>
    </div>

    <!-- ✅ TOKEN WALLET ROW -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="stat-card token-card">
                <h6 class="text-warning"><i class="fas fa-coins me-1"></i>Tokens Minted</h6>
                <h3><?= number_format($totalMinted,2) ?> kWh</h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card token-card">
                <h6 class="text-warning"><i class="fas fa-exchange-alt me-1"></i>Tokens Sold</h6>
                <h3><?= number_format($totalSoldTokens,2) ?> kWh</h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card token-card">
                <h6 class="text-warning"><i class="fas fa-wallet me-1"></i>Available Tokens</h6>
                <h3><?= number_format($tokenBalance,2) ?> kWh</h3>
            </div>
        </div>
    </div>

    <div class="row g-4">

        <!-- Add Energy -->
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header"><b>Add Energy Availability</b></div>
                <div class="card-body">

                    <form id="energyForm">
                        <div class="mb-2">
                            <label>Date</label>
                            <input type="date" name="date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <div class="mb-2">
                            <label>Time Block</label>
                            <select name="time_block" class="form-select">
                                <?php
                                for ($time = strtotime("00:00"); $time <= strtotime("23:45"); $time += 900) {
                                    $from = date("H:i", $time);
                                    $to = date("H:i", $time + 900);
                                    echo "<option value='$from-$to'>$from - $to</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="mb-2">
                            <label>Units (kWh)</label>
                            <input type="number" name="units" class="form-control" required>
                        </div>

                        <div class="mb-2">
                            <label>Price (₹)</label>
                            <input type="number" step="0.01" name="price" class="form-control" required>
                        </div>

                        <button class="btn btn-primary w-100">
                            <i class="fas fa-bolt me-1"></i>List Energy & Mint Tokens
                        </button>
                    </form>

                </div>
            </div>
        </div>

        <!-- My Listings -->
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <b>My Listings</b>
                    <a href="my_listings.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Time Slot</th>
                                    <th>Total (kWh)</th>
                                    <th>Remaining (kWh)</th>
                                    <th>Price (₹)</th>
                                </tr>
                            </thead>
                            <tbody id="listingData">
                                <tr><td colspan="5">Loading...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Trades -->
    <div class="card mt-4">
        <div class="card-header"><b>Recent Trades</b></div>
        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Units (kWh)</th>
                        <th>Price (₹)</th>
                        <th>Total (₹)</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $trades->fetch_assoc()): ?>
                    <tr>
                        <td><?= formatDate($row['date']) ?></td>
                        <td><?= $row['time_block'] ?></td>
                        <td><?= $row['units'] ?></td>
                        <td>₹ <?= $row['price'] ?></td>
                        <td><b>₹ <?= $row['total_amount'] ?></b></td>
                        <td><span class="badge bg-success">Completed</span></td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if($trades->num_rows == 0): ?>
                    <tr><td colspan="6">No trades yet</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script>
$("#energyForm").submit(function(e){
    e.preventDefault();

    $.post("../../api/add_energy.php", $(this).serialize(), function(res){

        let data = typeof res === "string" ? JSON.parse(res) : res;

        if(data.status === "success"){
            alert("✅ " + data.message);
            loadListings();
            $("#energyForm")[0].reset();
        } else {
            alert("❌ " + data.message);
        }

    }).fail(function(xhr){
        alert("Server Error: " + xhr.responseText);
    });
});
function loadListings(){
    $("#listingData").load("../../api/get_seller_listings.php");
}

loadListings();
</script>

</body>
</html>