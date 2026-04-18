<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'buyer') {
    header("Location: ../login.php");
    exit();
}

include '../includes/config.php';
$user_id = $_SESSION['user_id'];

// Fetch trading hours from settings
$settingsRow = $conn->query("SELECT trading_start_time, trading_end_time FROM settings LIMIT 1")->fetch_assoc();
$trading_start_hi = substr($settingsRow['trading_start_time'] ?? '10:00:00', 0, 5);
$trading_end_hi   = substr($settingsRow['trading_end_time']   ?? '17:00:00', 0, 5);

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

// Get default balance from settings if wallet doesn't exist
if (!$walletData) {
    $settingsBalance = $conn->query("SELECT default_wallet_balance FROM settings LIMIT 1");
    $balanceData = $settingsBalance ? $settingsBalance->fetch_assoc() : null;
    $walletBalance = $balanceData['default_wallet_balance'] ?? 0;
} else {
    $walletBalance = $walletData['balance'];
}

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
body { 
    background: #f8fafc; 
}

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
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    transition: 0.3s;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.15);
}

.card { 
    border-radius: 12px; 
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    border: none;
    overflow: hidden;
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
    border-collapse: collapse;
}

.table thead th { 
    background: #2d3748;
    color: #ffffff; 
    text-align: center;
    padding: 14px 12px;
    font-size: 14px;
    font-weight: 600;
    border: none;
    letter-spacing: 0.3px;
    white-space: nowrap;
}

.table tbody td {
    text-align: center;
    vertical-align: middle;
    padding: 14px 12px;
    font-size: 14px;
    border: none;
    border-bottom: 1px solid #e2e8f0;
    background: #ffffff;
    color: #2d3748;
}

.table tbody tr:hover td {
    background-color: #f7fafc;
}

.table tbody tr:last-child td {
    border-bottom: none;
}

.table-responsive {
    border-radius: 0 0 12px 12px;
    overflow: hidden;
    background: #ffffff;
}

@media (max-width: 992px) {
    .main-content { 
        margin-left: 0;
        padding: 15px;
    }
}
</style>
</head>
<body>

<?php include '../includes/header.php'; ?>

<div class="main-content">

    <h2 class="text-center mb-4">
        Welcome, <span class="text-primary"><?= htmlspecialchars($userName) ?></span>
    </h2>

    <!-- Refund Button -->
    <div class="text-end mb-3">
        <button class="btn btn-warning" onclick="requestRefund()">
            <i class="bi bi-arrow-counterclockwise me-1"></i>Refund Unused Energy
        </button>
    </div>

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
                <h5>Add Future Demand</h5>
                <form id="demandForm">
                    <div class="mb-2">
                        <label>Date</label>
                        <input type="date" name="date" value="<?= date('Y-m-d') ?>" class="form-control" required>
                    </div>
                    
                    <div class="mb-2">
                        <label>Time Range</label>
                        <div class="row g-2">
                            <div class="col-6">
                                <select name="start_time" id="startTime" class="form-control" required>
                                    <?php
                                    // Dynamic from settings
                                    for ($time = strtotime($trading_start_hi); $time <= strtotime($trading_end_hi); $time += 900) {
                                        $timeStr = date("H:i", $time);
                                        $selected = ($timeStr == $trading_start_hi) ? "selected" : "";
                                        echo "<option value='$timeStr' $selected>$timeStr</option>";
                                    }
                                    ?>
                                </select>
                                <small class="text-muted">From</small>
                            </div>
                            <div class="col-6">
                                <select name="end_time" id="endTime" class="form-control" required>
                                    <?php
                                    // Dynamic from settings
                                    for ($time = strtotime($trading_start_hi); $time <= strtotime($trading_end_hi); $time += 900) {
                                        $timeStr = date("H:i", $time);
                                        $selected = ($timeStr == $trading_end_hi) ? "selected" : "";
                                        echo "<option value='$timeStr' $selected>$timeStr</option>";
                                    }
                                    ?>
                                </select>
                                <small class="text-muted">To</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-2">
                        <label>Units per 15-min block (kWh)</label>
                        <input type="number" name="units" placeholder="e.g., 10" class="form-control" required>
                    </div>
                    
                    <div class="mb-2">
                        <label>Max Price (₹/kWh)</label>
                        <input type="number" step="0.01" name="price" placeholder="e.g., 5.5" class="form-control" required>
                    </div>
                    
                    <button class="btn btn-primary w-100">Submit Bulk Demand</button>
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

    const formData = $(this).serialize();
    const startTime = $("#startTime").val();
    const endTime = $("#endTime").val();
    
    // Validate time range
    if (startTime >= endTime) {
        alert("❌ End time must be after start time");
        return;
    }

    $.post("../../api/add_demand_bulk.php", formData, function(res){

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

// Refund function
function requestRefund() {
    if (!confirm("Request refund for all unused energy from expired demands?\n\nThis will return blocked funds to your wallet.")) {
        return;
    }
    
    $.post("../../api/refund_unused_energy.php", {}, function(res) {
        let data = typeof res === "string" ? JSON.parse(res) : res;
        
        if (data.status === "success") {
            alert("✅ " + data.message + "\n\nTotal Refund: ₹" + data.total_refund.toFixed(2) + "\nDemands Refunded: " + data.count);
            loadDemands();
            // Reload page to update wallet balance
            setTimeout(function() {
                location.reload();
            }, 1000);
        } else {
            alert("❌ " + data.message);
        }
    }).fail(function(xhr) {
        alert("Error: " + xhr.responseText);
    });
}
</script>

</body>
</html>