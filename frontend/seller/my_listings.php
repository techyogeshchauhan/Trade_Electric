<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'seller') {
    header("Location: ../login.php");
    exit();
}

include '../includes/config.php';
$user_id = $_SESSION['user_id'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Listings - EnergyTrade</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../../assets/css/style.css" rel="stylesheet">

    <style>
        .main-content {
            margin-left: 260px;
            padding: 20px;
            margin-top: 80px;
        }
        .card {
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            margin-bottom: 20px;
            border: none;
            overflow: hidden;
        }
        .card-header {
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            color: white;
            padding: 15px 20px;
            border-radius: 0 !important;
            font-weight: 700;
            font-size: 16px;
        }
        .card-header.blue-header {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
        }
        .table {
            font-size: 14px;
            margin: 0;
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
            overflow: hidden;
            background: #ffffff;
        }
        @media (max-width: 992px) {
            .main-content { margin-left: 0; }
        }
    </style>
</head>

<body>

<?php include '../includes/header.php'; ?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">
            <i class="bi bi-lightning-charge"></i> My Energy Listings
        </h2>
        <a href="marketplace.php" class="btn btn-warning">
            <i class="bi bi-shop"></i> Back to Marketplace
        </a>
    </div>

    <!-- Parallel Tables Row -->
    <div class="row g-3">
        <!-- Active Listings - Left Side -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-list-ul"></i> Active Listings
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Time Slot</th>
                                    <th>Total Units</th>
                                    <th>Remaining Units</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="listingsTable">
                                <tr>
                                    <td colspan="6" class="text-center py-3">
                                        <div class="spinner-border text-warning" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        <p class="mt-2 text-muted">Loading listings...</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sales History - Right Side -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header blue-header">
                    <h5 class="mb-0">
                        <i class="bi bi-clock-history"></i> Sales History
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Time Slot</th>
                                    <th>Total Units</th>
                                    <th>Sold Units</th>
                                    <th>Price</th>
                                </tr>
                            </thead>
                            <tbody id="historyTable">
                                <tr>
                                    <td colspan="5" class="text-center py-3">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        <p class="mt-2 text-muted">Loading history...</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
function loadListings() {
    $.get("../../api/get_seller_listings.php", function(res) {

        if(res.trim() === ""){
            $("#listingsTable").html(`
                <tr>
                    <td colspan="5">No listings found</td>
                </tr>
            `);
        } else {
            $("#listingsTable").html(res);
        }

    });
}

loadListings();

function loadHistory() {
    $.get("../../api/get_seller_history.php", function(res) {

        if(res.trim() === ""){
            $("#historyTable").html(`
                <tr>
                    <td colspan="5">No history found</td>
                </tr>
            `);
        } else {
            $("#historyTable").html(res);
        }

    });
}

loadHistory();
</script>

</body>
</html>