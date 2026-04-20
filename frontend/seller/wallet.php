<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
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
    border-radius: 15px;
    padding: 22px;
    color: white;
    text-align: center;
}

.balance { background: linear-gradient(45deg, #4facfe, #00f2fe); }
.blocked { background: linear-gradient(45deg, #f7971e, #ffd200); }

/* ✅ TOKEN CARD — LIGHT COLOR */
.token-balance {
    background: linear-gradient(135deg, #fffbeb, #fef3c7) !important;
    border: 2px solid #fbbf24;
    color: #78350f;
}
.token-balance h6 {
    color: #92400e !important;
    font-weight: 700;
}
.token-balance h3 {
    color: #b45309 !important;
    font-size: 2rem;
    font-weight: 800;
}
.token-balance i {
    color: #d97706;
}
.token-balance small {
    color: #92400e;
}

.table th {
    background: #1e293b;
    color: #ffffff;
    text-align: center;
    font-size: 13px;
}

.table td {
    text-align: center;
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

#debugBox {
    background: #fff3cd;
    border: 1px solid #ffc107;
    border-radius: 8px;
    padding: 10px 14px;
    font-size: 12px;
    font-family: monospace;
    margin-bottom: 15px;
    display: none;
}

@media (max-width: 992px) {
    .main-content { margin-left: 0; padding: 15px; }
}
</style>

</head>

<body>

<?php include '../includes/header.php'; ?>

<div class="main-content">
<div class="container mt-5">

<!-- DEBUG BOX -->
<div id="debugBox"></div>

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

<!-- TOKEN WALLET -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card-box token-balance">
            <h6><i class="fas fa-coins me-1"></i>Energy Token Balance</h6>
            <h3 id="tokenBalance">0.00 kWh</h3>
            <small>1 Token = 1 kWh (Blockchain Verified)</small>
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
                </tr>
            </thead>
            <tbody id="txnBody">
                <tr><td colspan="3">Loading...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- TOKEN LEDGER -->
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
function debug(msg){
    let box = document.getElementById('debugBox');
    box.style.display = 'block';
    box.innerHTML += msg + '<br>';
}

function loadWallet(){
    debug('Calling get_wallet.php...');

    // ✅ FIXED PATH — 2 level up
    $.ajax({
        url: '../../api/get_wallet.php',
        method: 'GET',
        dataType: 'json',
        success: function(data){
            debug('✅ Wallet OK — Balance: ' + data.balance + ', Tokens: ' + data.token_balance);

            if(data.error){
                debug('❌ ' + data.error);
                return;
            }

            $('#balance').text('₹ ' + parseFloat(data.balance).toLocaleString('en-IN'));
            $('#blocked').text('₹ ' + parseFloat(data.blocked_balance).toLocaleString('en-IN'));
            $('#tokenBalance').text(parseFloat(data.token_balance).toFixed(2) + ' kWh');

            let html = '';

            if(data.transactions && data.transactions.length){
                data.transactions.forEach(function(t){
                    let color = 'secondary';
                    if(t.type === 'credit') color = 'success';
                    if(t.type === 'debit') color = 'danger';
                    if(t.type === 'block') color = 'warning';

                    html += '<tr>';
                    html += '<td><span class="badge bg-' + color + '">' + t.type + '</span></td>';
                    html += '<td>₹ ' + parseFloat(t.amount).toLocaleString('en-IN') + '</td>';
                    html += '<td>' + (t.role_view || '') + ' — ' + (t.user || '') + '</td>';
                    html += '</tr>';
                });
            } else {
                html = '<tr><td colspan="3">No transactions</td></tr>';
            }

            $('#txnBody').html(html);
        },
        error: function(xhr, status, err){
            debug('❌ Wallet Error: ' + status + ' — ' + err);
            debug('Response: ' + xhr.responseText.substring(0, 200));
        }
    });
}

function loadTokenHistory(){
    debug('Calling get_token_history.php...');

    // ✅ FIXED PATH — 2 level up
    $.ajax({
        url: '../../api/get_token_history.php',
        method: 'GET',
        success: function(res){
            debug('✅ Token history OK');
            $('#tokenBody').html(res);
        },
        error: function(xhr, status, err){
            debug('❌ Token Error: ' + status + ' — ' + err);
            debug('Response: ' + xhr.responseText.substring(0, 200));
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