<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include '../includes/config.php';

$user_id = (int) $_SESSION['user_id'];
$role = $_SESSION['role'] ?? '';

// ✅ Validate role
if (!in_array($role, ['buyer', 'seller'])) {
    die("Invalid role");
}

// Dynamic query with JOIN
if ($role == 'buyer') {
    $query = "
    SELECT t.*, 
           u.name AS seller_name,
           tl.tx_hash
    FROM trades t
    LEFT JOIN users u ON t.seller_id = u.id
    LEFT JOIN token_ledger tl 
        ON t.id = tl.trade_id 
        AND tl.token_type = 'transfer_in'
    WHERE t.buyer_id = $user_id
    ORDER BY t.created_at DESC, t.id DESC
    ";
} else {
    $query = "
    SELECT t.*, 
           u.name AS buyer_name,
           tl.tx_hash,
           e.remaining_units as seller_remaining_units
    FROM trades t
    LEFT JOIN users u ON t.buyer_id = u.id
    LEFT JOIN token_ledger tl 
        ON t.id = tl.trade_id 
        AND tl.token_type = 'transfer_in'
    LEFT JOIN energy_listings e ON t.listing_id = e.id
    WHERE t.seller_id = $user_id
    ORDER BY t.created_at DESC, t.id DESC
    ";
}

// Execute query
$result = $conn->query($query);

if (!$result) {
    die("Query Error: " . $conn->error);
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Smart Trade History</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

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
}

.card {
    border-radius: 15px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
}

.table th {
    background: #4facfe;
    color: white;
    text-align: center;
}

.table td {
    text-align: center;
    vertical-align: middle;
    font-size: 14px;
}

.no-data {
    text-align: center;
    padding: 20px;
    color: gray;
}

.tx-hash {
    font-family: monospace;
    font-size: 11px;
    color: #6b7280;
    cursor: help;
}
</style>
</head>

<body>

<?php include '../includes/header.php'; ?>

<div class="main-content container-fluid">

<h2 class="mb-4">
<i class="fas fa-exchange-alt me-2"></i>Smart Trade History
</h2>

<?php if ($role == 'seller'): ?>
<!-- Pending Contracts Section (Seller Only) -->
<div class="card p-3 mb-4">
    <h5 class="mb-3">
        <i class="bi bi-file-earmark-text me-2"></i>Pending Contracts
        <span class="badge bg-warning text-dark" id="pendingCount">0</span>
    </h5>
    
    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead>
                <tr>
                    <th>S.No</th>
                    <th>Contract ID</th>
                    <th>Buyer</th>
                    <th>Date</th>
                    <th>Time Block</th>
                    <th>Units</th>
                    <th>Price/Unit</th>
                    <th>Total Amount</th>
                    <th>Confirmation</th>
                </tr>
            </thead>
            <tbody id="pendingContractsTable">
                <tr>
                    <td colspan="9" class="text-center py-3">
                        <i class="spinner-border spinner-border-sm me-2"></i>Loading...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<div class="card p-3">

<div class="table-responsive">
<table class="table table-bordered table-hover">

<thead>
<tr>
    <?php if ($role == 'buyer'): ?>
    <th>S.No</th>
    <?php endif; ?>
    <th>Date</th>
    <th>Time Slot</th>
    <th>Units Smart Traded</th>
    <th>Price/Unit</th>
    <th>Total</th>
    <th><?= ($role == 'buyer') ? 'Seller' : 'Buyer' ?></th>
    <?php if ($role == 'seller'): ?>
    <th>Remaining Units</th>
    <?php endif; ?>
    <th>TX Hash</th>
</tr>
</thead>

<tbody>

<?php if ($result && $result->num_rows > 0) { ?>

<?php 
$serial = 1;
while ($row = $result->fetch_assoc()) { 

    $hash = $row['tx_hash'] ?? '—';
    $shortHash = ($hash !== '—') ? substr($hash, 0, 10) . '...' : '—';

    $dateValue = $row['date'] ?? $row['created_at'] ?? null;
    $formattedDate = (!empty($dateValue) && strtotime($dateValue)) 
        ? date('d-m-Y', strtotime($dateValue)) 
        : "N/A";
?>

<tr>

<?php if ($role == 'buyer'): ?>
<td><strong><?= $serial++ ?></strong></td>
<?php endif; ?>

<td><?= $formattedDate ?></td>

<td><?= !empty($row['time_block']) ? $row['time_block'] : 'N/A' ?></td>

<td><?= $row['units'] ?> kWh</td>

<td>₹ <?= number_format($row['price'], 2) ?></td>

<td><b>₹ <?= number_format($row['total_amount'], 2) ?></b></td>

<td>
<?php if ($role == 'buyer') { ?>
    <?= $row['seller_name'] ?? 'N/A' ?>
<?php } else { ?>
    <?= $row['buyer_name'] ?? 'N/A' ?>
<?php } ?>
</td>

<?php if ($role == 'seller'): ?>
<td>
    <?php 
    $remaining = $row['seller_remaining_units'] ?? 0;
    if ($remaining > 0) {
        echo "<span class='badge bg-warning text-dark'>{$remaining} kWh</span>";
    } else {
        echo "<span class='badge bg-success'>Fully Sold</span>";
    }
    ?>
</td>
<?php endif; ?>

<td>
<?php if ($hash !== '—') { ?>
    <span class="tx-hash" title="<?= $hash ?>">
        <i class="fas fa-link me-1 text-warning"></i><?= $shortHash ?>
    </span>
<?php } else { ?>
    <span class="text-muted">—</span>
<?php } ?>
</td>

</tr>

<?php } ?>

<?php } else { ?>

<tr>
<td colspan="<?= $role == 'buyer' ? '8' : '9' ?>" class="no-data">
No smart trades found
</td>
</tr>

<?php } ?>

</tbody>

</table>
</div>

</div>

</div>

</body>
</html>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<?php if ($role == 'seller'): ?>
<script>
// Load Pending Contracts for Seller
function loadPendingContracts() {
    $.get("../../api/get_pending_contracts.php", function(res){
        let data = typeof res === "string" ? JSON.parse(res) : res;
        
        if(data.success && data.contracts.length > 0) {
            let html = '';
            let count = 0;
            
            data.contracts.forEach(function(contract, index) {
                count++;
                let date = new Date(contract.date).toLocaleDateString('en-GB');
                let total = (contract.units * contract.price_per_unit).toFixed(2);
                
                html += `
                <tr>
                    <td>${index + 1}</td>
                    <td><small class="text-primary">${contract.contract_id}</small></td>
                    <td><b>${contract.buyer_name}</b></td>
                    <td>${date}</td>
                    <td>${contract.time_block}</td>
                    <td>${contract.units} kWh</td>
                    <td>₹ ${contract.price_per_unit}</td>
                    <td><b>₹ ${total}</b></td>
                    <td>
                        <button class="btn btn-success btn-sm me-1" 
                                onclick="confirmContract('${contract.contract_id}', 'confirm')">
                            <i class="bi bi-check-circle me-1"></i>Yes
                        </button>
                        <button class="btn btn-danger btn-sm" 
                                onclick="confirmContract('${contract.contract_id}', 'reject')">
                            <i class="bi bi-x-circle me-1"></i>No
                        </button>
                    </td>
                </tr>`;
            });
            
            $("#pendingContractsTable").html(html);
            $("#pendingCount").text(count);
        } else {
            $("#pendingContractsTable").html(`
                <tr>
                    <td colspan="9" class="text-muted py-3">
                        <i class="bi bi-inbox me-2"></i>No pending contracts
                    </td>
                </tr>
            `);
            $("#pendingCount").text('0');
        }
    }).fail(function(){
        $("#pendingContractsTable").html(`
            <tr>
                <td colspan="9" class="text-danger py-3">
                    <i class="bi bi-exclamation-triangle me-2"></i>Failed to load contracts
                </td>
            </tr>
        `);
    });
}

// Confirm or Reject Contract
function confirmContract(contractId, action) {
    let message = action === 'confirm' 
        ? "Are you sure you want to CONFIRM this contract?\n\nThis will execute the smart trade immediately."
        : "Are you sure you want to REJECT this contract?";
    
    if(!confirm(message)) return;
    
    $.post("../../api/confirm_contract.php", {
        contract_id: contractId,
        action: action
    }, function(res){
        let data = typeof res === "string" ? JSON.parse(res) : res;
        
        if(data.success) {
            alert("✅ " + data.message);
            loadPendingContracts();
            location.reload(); // Reload to show updated smart trade history
        } else {
            alert("❌ " + (data.message || "Operation failed"));
        }
    }).fail(function(xhr){
        alert("Server Error: " + xhr.responseText);
    });
}

// Auto-refresh every 10 seconds
setInterval(loadPendingContracts, 10000);

// Initial load
loadPendingContracts();
</script>
<?php endif; ?>

