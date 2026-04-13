<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Wallet</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<style>
body { background:#f4f6f9; }

.main-content {
    margin-left: 250px;
    padding: 20px;
}

.card-box {
    border-radius:15px;
    padding:22px;
    color:white;
    text-align:center;
}

.balance { background:linear-gradient(45deg,#4facfe,#00f2fe); }
.blocked { background:linear-gradient(45deg,#f7971e,#ffd200); }

/* ✅ TOKEN CARD */
.token-balance {
    background: linear-gradient(135deg, #1a1a2e, #16213e) !important;
    border: 2px solid #ffc107;
    position: relative;
    overflow: hidden;
}
.token-balance::before {
    content: '';
    position: absolute;
    top: -30%;
    right: -30%;
    width: 80%;
    height: 80%;
    background: radial-gradient(circle, rgba(255,193,7,0.12) 0%, transparent 70%);
}
.token-balance h3 {
    color: #ffc107 !important;
    font-size: 2rem;
}

.table th {
    background:#0d6efd;
    color:white;
    text-align:center;
    font-size: 13px;
}

.table td {
    text-align:center;
    font-size: 13px;
    vertical-align: middle;
}

.section-title {
    font-weight: 700;
    font-size: 16px;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.badge-mint { background: #198754; }
.badge-transfer_in { background: #0d6efd; }
.badge-transfer_out { background: #ffc107; color: #333; }
.badge-burn { background: #dc3545; }
</style>

<?php include '../includes/header.php'; ?>
</head>

<body>

<div class="main-content">
<div class="container mt-5">

<h3 class="mb-4 fw-bold"><i class="fas fa-wallet me-2"></i>My Wallet</h3>

<!-- MONEY WALLET -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card-box balance">
            <h6>Available Balance</h6>
            <h3 id="balance">₹0</h3>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card-box blocked">
            <h6>Blocked Balance</h6>
            <h3 id="blocked">₹0</h3>
        </div>
    </div>
</div>

<!-- ✅ TOKEN WALLET -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card-box token-balance">
            <h6 class="text-warning mb-1"><i class="fas fa-coins me-1"></i>Energy Token Balance</h6>
            <h3 id="tokenBalance">0.00 kWh</h3>
            <small class="text-muted" style="position:relative; z-index:1;">1 Token = 1 kWh (Blockchain Verified)</small>
        </div>
    </div>
</div>

<!-- MONEY TRANSACTIONS -->
<div class="card mb-4">
    <div class="card-header">
        <span class="section-title mb-0"><i class="fas fa-rupee-sign"></i> Money Transactions</span>
    </div>
    <div class="card-body p-0">
        <table class="table table-bordered mb-0">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Description</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody id="txnBody">
                <tr><td colspan="4">Loading...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- ✅ TOKEN LEDGER -->
<div class="card">
    <div class="card-header">
        <span class="section-title mb-0"><i class="fas fa-link"></i> Token Ledger (Blockchain)</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered mb-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Block</th>
                        <th>Type</th>
                        <th>Tokens</th>
                        <th>TX Hash</th>
                        <th>Party</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody id="tokenBody">
                    <tr><td colspan="7">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

</div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script>
function loadWallet(){

    $.ajax({
        url: '../../api/get_wallet.php',
        method: 'GET',
        dataType: 'json',
        success: function(data) {
            console.log('Wallet data:', data);

            if (data.error) {
                console.error('Wallet error:', data.error);
                $('#balance').text('Error');
                $('#blocked').text('Error');
                $('#tokenBalance').text('Error');
                $('#txnBody').html('<tr><td colspan="4" class="text-danger">Error: ' + data.error + '</td></tr>');
                return;
            }

            $('#balance').text('₹ ' + parseFloat(data.balance || 0).toLocaleString('en-IN'));
            $('#blocked').text('₹ ' + parseFloat(data.blocked_balance || 0).toLocaleString('en-IN'));
            $('#tokenBalance').text(parseFloat(data.token_balance || 0).toFixed(2) + ' kWh');

            let html = '';

            if(data.transactions && data.transactions.length){

                data.transactions.forEach(t => {

                    let color = 'secondary';
                    if(t.type === 'credit') color = 'success';
                    if(t.type === 'debit') color = 'danger';
                    if(t.type === 'block') color = 'warning';

                    html += `
                    <tr>
                        <td><span class="badge bg-${color}">${t.type.toUpperCase()}</span></td>
                        <td><strong>₹ ${parseFloat(t.amount || 0).toLocaleString('en-IN', {minimumFractionDigits: 2})}</strong></td>
                        <td style="text-align:left; font-size:12px;">${t.description || 'N/A'}</td>
                        <td>${t.date || '—'}</td>
                    </tr>`;
                });

            } else {
                html = `<tr><td colspan="4" class="text-muted">No transactions yet</td></tr>`;
            }

            $('#txnBody').html(html);
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', status, error);
            console.error('Response:', xhr.responseText);
            $('#balance').text('Error');
            $('#blocked').text('Error');
            $('#tokenBalance').text('Error');
            $('#txnBody').html('<tr><td colspan="4" class="text-danger">Failed to load wallet data. Please refresh.</td></tr>');
        }
    });
}

function loadTokenHistory(){
    $.ajax({
        url: '../../api/get_token_history.php',
        method: 'GET',
        success: function(res) {
            console.log('Token history loaded');
            $('#tokenBody').html(res);
        },
        error: function(xhr, status, error) {
            console.error('Token history error:', status, error);
            $('#tokenBody').html('<tr><td colspan="7" class="text-danger">Failed to load token history. Please refresh.</td></tr>');
        }
    });
}

loadWallet();
loadTokenHistory();

setInterval(function(){
    loadWallet();
    loadTokenHistory();
}, 5000);

</script>

</body>
</html>