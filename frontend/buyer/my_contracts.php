<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'buyer') {
    header("Location: ../login.php");
    exit();
}

$buyer_id = $_SESSION['user_id'];
?>

<!DOCTYPE html>
<html>
<head>
<title>My Contracts</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<style>
body { background: #f4f6f9; }

.main-content {
    margin-left: 260px;
    padding: 20px;
    margin-top: 8px;
}

.contract-card {
    background: white;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 15px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}

.contract-card:hover {
    box-shadow: 0 4px 15px rgba(0,0,0,0.15);
    transform: translateY(-2px);
}

.contract-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 2px solid #e9ecef;
}

.contract-id {
    font-weight: 700;
    color: #0d6efd;
    font-size: 14px;
}

.contract-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 15px;
}

.detail-item {
    display: flex;
    flex-direction: column;
}

.detail-label {
    font-size: 12px;
    color: #6c757d;
    margin-bottom: 4px;
}

.detail-value {
    font-size: 16px;
    font-weight: 600;
    color: #212529;
}

.badge-pending {
    background: #ffc107;
    color: #000;
}

.badge-confirmed {
    background: #28a745;
}

.badge-rejected {
    background: #dc3545;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #6c757d;
}

.empty-state i {
    font-size: 64px;
    margin-bottom: 20px;
    opacity: 0.3;
}

@media (max-width: 992px) {
    .main-content {
        margin-left: 0;
    }
}
</style>

<?php include '../includes/header.php'; ?>
</head>

<body>

<div class="main-content">
<div class="container mt-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold">
            <i class="fas fa-file-contract me-2"></i>My Contracts
        </h3>
        <a href="dashboard.php" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
        </a>
    </div>

    <!-- Filter Tabs -->
    <ul class="nav nav-pills mb-4" id="statusFilter">
        <li class="nav-item">
            <a class="nav-link active" data-status="all" href="#">All</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-status="pending" href="#">Pending</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-status="confirmed" href="#">Confirmed</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-status="rejected" href="#">Rejected</a>
        </li>
    </ul>

    <!-- Contracts Container -->
    <div id="contractsContainer">
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3 text-muted">Loading contracts...</p>
        </div>
    </div>

</div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
let currentFilter = 'all';

function loadContracts() {
    $('#contractsContainer').html(`
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3 text-muted">Loading contracts...</p>
        </div>
    `);

    $.ajax({
        url: '../../api/get_buyer_contracts.php',
        method: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success && response.contracts) {
                displayContracts(response.contracts);
            } else {
                $('#contractsContainer').html(`
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h5>No Contracts Found</h5>
                        <p>You haven't created any contracts yet.</p>
                        <a href="dashboard.php" class="btn btn-primary mt-3">
                            <i class="fas fa-plus me-2"></i>Create Contract
                        </a>
                    </div>
                `);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading contracts:', error);
            $('#contractsContainer').html(`
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Failed to load contracts. Please try again.
                </div>
            `);
        }
    });
}

function displayContracts(contracts) {
    // Filter contracts based on current filter
    let filtered = contracts;
    if (currentFilter !== 'all') {
        filtered = contracts.filter(c => c.status === currentFilter);
    }

    if (filtered.length === 0) {
        $('#contractsContainer').html(`
            <div class="empty-state">
                <i class="fas fa-filter"></i>
                <h5>No ${currentFilter === 'all' ? '' : currentFilter} Contracts</h5>
                <p>No contracts found with this status.</p>
            </div>
        `);
        return;
    }

    let html = '';

    filtered.forEach(contract => {
        // Parse values as floats to ensure they're numbers
        const units = parseFloat(contract.units) || 0;
        const pricePerUnit = parseFloat(contract.price_per_unit) || 0;
        const platformFee = parseFloat(contract.platform_fee) || 0;
        const utilityFee = parseFloat(contract.utility_fee) || 0;
        const totalAmount = parseFloat(contract.total_amount) || 0;
        
        // Calculate total payment
        const totalPayment = totalAmount + platformFee + utilityFee;

        // Status badge
        let statusBadge = '';
        if (contract.status === 'pending') {
            statusBadge = '<span class="badge badge-pending"><i class="fas fa-clock me-1"></i>Pending</span>';
        } else if (contract.status === 'confirmed') {
            statusBadge = '<span class="badge badge-confirmed"><i class="fas fa-check-circle me-1"></i>Confirmed</span>';
        } else if (contract.status === 'rejected') {
            statusBadge = '<span class="badge badge-rejected"><i class="fas fa-times-circle me-1"></i>Rejected</span>';
        }

        html += `
        <div class="contract-card">
            <div class="contract-header">
                <div>
                    <div class="contract-id">${contract.contract_id}</div>
                    <small class="text-muted">
                        <i class="fas fa-calendar me-1"></i>
                        Created: ${new Date(contract.created_at).toLocaleString()}
                    </small>
                </div>
                <div>${statusBadge}</div>
            </div>

            <div class="contract-details">
                <div class="detail-item">
                    <span class="detail-label">Seller</span>
                    <span class="detail-value">
                        <i class="fas fa-user text-primary me-1"></i>
                        ${contract.seller_name || 'Unknown'}
                    </span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Date & Time</span>
                    <span class="detail-value">
                        <i class="fas fa-calendar-day text-success me-1"></i>
                        ${contract.date}
                        <br>
                        <small class="text-muted">${contract.time_block}</small>
                    </span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Energy Units</span>
                    <span class="detail-value">
                        <i class="fas fa-bolt text-warning me-1"></i>
                        ${units.toFixed(2)} kWh
                    </span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Price per Unit</span>
                    <span class="detail-value">
                        <i class="fas fa-rupee-sign text-info me-1"></i>
                        ${pricePerUnit.toFixed(2)}
                    </span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Base Amount</span>
                    <span class="detail-value">
                        ₹${totalAmount.toFixed(2)}
                    </span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Fees</span>
                    <span class="detail-value">
                        <small class="text-muted">
                            Platform: ₹${platformFee.toFixed(2)}<br>
                            Utility: ₹${utilityFee.toFixed(2)}
                        </small>
                    </span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Total Payment</span>
                    <span class="detail-value text-primary">
                        <i class="fas fa-rupee-sign me-1"></i>
                        ${totalPayment.toFixed(2)}
                    </span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Status Date</span>
                    <span class="detail-value">
                        ${contract.status === 'confirmed' && contract.confirmed_at ? 
                            '<small class="text-success">Confirmed: ' + new Date(contract.confirmed_at).toLocaleString() + '</small>' :
                            contract.status === 'rejected' && contract.rejected_at ?
                            '<small class="text-danger">Rejected: ' + new Date(contract.rejected_at).toLocaleString() + '</small>' :
                            '<small class="text-muted">Waiting...</small>'
                        }
                    </span>
                </div>
            </div>
        </div>
        `;
    });

    $('#contractsContainer').html(html);
}

// Filter click handler
$('#statusFilter .nav-link').on('click', function(e) {
    e.preventDefault();
    $('#statusFilter .nav-link').removeClass('active');
    $(this).addClass('active');
    currentFilter = $(this).data('status');
    loadContracts();
});

// Load contracts on page load
loadContracts();

// Auto-refresh every 10 seconds
setInterval(loadContracts, 10000);
</script>

</body>
</html>
