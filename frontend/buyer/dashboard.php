<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'buyer') {
    header("Location: ../login.php");
    exit();
}

include '../includes/config.php';

$user_id = $_SESSION['user_id'];

$userQuery = $conn->query("SELECT name FROM users WHERE id = $user_id");
$userData = $userQuery->fetch_assoc();
$userName = $userData['name'] ?? 'User';

// Format Date Function
function formatDate($date) {
    return !empty($date) ? date('d-m-Y', strtotime($date)) : 'N/A';
}

$totalDemands = $conn->query("SELECT COUNT(*) as total FROM demand_listings WHERE user_id = $user_id")->fetch_assoc()['total'] ?? 0;
$buyUnits     = $conn->query("SELECT COALESCE(SUM(units), 0) as total FROM trades WHERE buyer_id = $user_id")->fetch_assoc()['total'];
$spent        = $conn->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM trades WHERE buyer_id = $user_id")->fetch_assoc()['total'];

$walletData   = $conn->query("SELECT balance FROM wallet WHERE user_id = $user_id")->fetch_assoc();
$walletBalance = $walletData['balance'] ?? 5000;

$trades = $conn->query("SELECT * FROM trades WHERE buyer_id = $user_id ORDER BY id DESC LIMIT 5");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Buyer Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

<style>
body { background: #f8fafc; }
.main-content {
    margin-top: 80px;
    margin-left: 260px;
    padding: 25px;
    max-width: 1400px;
}
.stat-card {
    border-radius: 12px;
    padding: 20px;
    color: white;
    text-align: center;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
.card { 
    border-radius: 12px; 
    box-shadow: 0 6px 20px rgba(0,0,0,0.08);
    border: none;
}
.table { 
    margin: 0;
    font-size: 13px;
}
.table th { 
    background: #1e2937;
    color: white; 
    text-align: center;
    padding: 10px 8px;
    font-size: 12px;
    font-weight: 600;
    border: 1px solid #374151;
}
.table td {
    text-align: center;
    vertical-align: middle;
    padding: 8px;
    font-size: 13px;
    border: 1px solid #e5e7eb;
}
.table tbody tr:hover {
    background-color: #f3f4f6;
}
@media (max-width: 992px) {
    .main-content { margin-left: 0; }
}
</style>
</head>
<body>

<?php include '../includes/header.php'; ?>

<div class="main-content">

    <h2 class="text-center mb-4">
        Welcome, <span class="text-primary"><?= htmlspecialchars($userName) ?></span>
    </h2>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card bg-primary">
                <h6>Demands</h6>
                <h3><?= $totalDemands ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card bg-success">
                <h6>Units Bought</h6>
                <h3><?= number_format($buyUnits,2) ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card bg-warning">
                <h6>Spent</h6>
                <h3>₹ <?= number_format($spent) ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card bg-info">
                <h6>Wallet Balance</h6>
                <h3>₹ <?= number_format($walletBalance) ?></h3>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Add Demand -->
        <div class="col-md-5">
            <div class="card p-3">
                <h5>Add Demand</h5>
                <form id="demandForm">
                    <input type="date" name="date" value="<?= date('Y-m-d') ?>" class="form-control mb-2" required>
                    <select name="time_block" class="form-control mb-2" required>
                        <?php
                        for ($time = strtotime("00:00"); $time <= strtotime("23:45"); $time += 900) {
                            $from = date("H:i", $time);
                            $to = date("H:i", $time + 900);
                            echo "<option value='$from-$to'>$from - $to</option>";
                        }
                        ?>
                    </select>
                    <input type="number" name="units" placeholder="Units (kWh)" class="form-control mb-2" required>
                    <input type="number" step="0.01" name="price" placeholder="Max Price (₹)" class="form-control mb-2" required>
                    <button class="btn btn-primary w-100">Submit Demand</button>
                </form>
            </div>
        </div>

        <!-- Your Demands -->
        <div class="col-md-7">
            <div class="card p-3">
                <h5 class="mb-3">
                    <i class="bi bi-list-check"></i> Your Demands
                </h5>
                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <div id="demandData">
                        <div class="text-center py-3">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2 text-muted">Loading demands...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Available Matches -->
    <div class="card mt-4">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0">
                <i class="bi bi-lightning-charge text-warning"></i> Available Matches
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                <table class="table table-bordered table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Seller</th>
                            <th>Units</th>
                            <th>Price</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="matchData">
                        <tr>
                            <td colspan="6" class="text-center py-3">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-2 text-muted">Loading matches...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Recent Purchases -->
    <div class="card mt-4">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0">
                <i class="bi bi-clock-history text-info"></i> Recent Purchases
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Units</th>
                            <th>Price</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $trades->fetch_assoc()): ?>
                        <tr>
                            <td><?= formatDate($row['trade_time'] ?? $row['created_at'] ?? '') ?></td>
                            <td><?= $row['time_block'] ?? 'N/A' ?></td>
                            <td><?= number_format($row['units'], 0) ?> kWh</td>
                            <td>₹<?= number_format($row['price'], 1) ?></td>
                            <td><strong>₹<?= number_format($row['total_amount'], 2) ?></strong></td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if($trades->num_rows == 0): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                <i class="bi bi-inbox" style="font-size: 48px; opacity: 0.3;"></i>
                                <p class="mt-2">No purchases yet</p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
$("#demandForm").submit(function(e){
    e.preventDefault();

    $.post("../../api/add_demand.php", $(this).serialize(), function(res){

        let data = typeof res === "string" ? JSON.parse(res) : res;

        if(data.status === "success"){
            alert("✅ " + data.message);
            loadDemands();
            loadMatches();
            $("#demandForm")[0].reset();
        } else {
            alert("❌ " + data.message);
        }

    }).fail(function(xhr){
        alert("Error: " + xhr.responseText);
    });
});

function loadDemands(){
    $.get("../../api/get_demands.php", function(res){
        $("#demandData").html(res);
    });
}

function loadMatches(){
    // Try direct matches first (works without match_suggestions table)
    $.get("../../api/get_matches_direct.php", function(res){
        $("#matchData").html(res);
    }).fail(function(){
        // Fallback to original matches API
        $.get("../../api/get_matches.php", function(res){
            $("#matchData").html(res);
        }).fail(function(){
            $("#matchData").html("<tr><td colspan='6' class='text-danger'>Error loading matches</td></tr>");
        });
    });
}

// Create Contract Function
function createContract(btn, matchId, sellerId, listingId, demandId, units, price, date, timeBlock){
    
    console.log("Contract function called with:", {
        matchId, sellerId, listingId, demandId, units, price, date, timeBlock
    });
    
    if(!confirm("Do you want to create this contract?\n\nUnits: " + units + " kWh\nPrice: ₹" + price + "/kWh\nTotal: ₹" + (units * price))) {
        return;
    }

    $(btn).prop('disabled', true).html('<i class="spinner-border spinner-border-sm me-1"></i>Creating...');

    $.post("../../api/create_contract_v2.php", {
        buyer_id: <?= $user_id ?>,
        seller_id: sellerId,
        listing_id: listingId,
        demand_id: demandId,
        units: units,
        price_per_unit: price,
        date: date,
        time_block: timeBlock
    }, function(res){
        
        console.log("API Response:", res);

        let data = typeof res === "string" ? JSON.parse(res) : res;

        if(data.success){
            alert("✅ Contract Created Successfully!\n\nContract ID: " + data.contract_id + "\n\nWaiting for seller confirmation...");
            loadMatches();
            loadDemands();
        } else {
            alert("❌ " + (data.message || "Failed to create contract"));
            console.error("Error details:", data);
            $(btn).prop('disabled', false).html('<i class="bi bi-file-earmark-text me-1"></i>Contract');
        }

    }).fail(function(xhr, status, error){
        console.error("AJAX Error:", {
            status: xhr.status,
            statusText: xhr.statusText,
            responseText: xhr.responseText,
            error: error
        });
        
        alert("Server Error: " + xhr.status + "\n\nCheck browser console for details");
        $(btn).prop('disabled', false).html('<i class="bi bi-file-earmark-text me-1"></i>Contract');
    });
}
setInterval(function(){
    loadDemands();
    loadMatches();
}, 5000);

loadDemands();
loadMatches();
</script>

</body>
</html>