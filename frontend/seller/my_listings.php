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
            margin-top: 8px;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }
        .card-header {
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            color: white;
            padding: 15px 20px;
            border-radius: 10px 10px 0 0 !important;
        }
        .table {
            font-size: 13px;
            margin: 0;
        }
        .table th {
            background: #1e2937;
            color: white;
            text-align: center;
            padding: 12px 8px;
            font-size: 12px;
            font-weight: 600;
            border: 1px solid #374151;
        }
        .table td {
            text-align: center;
            vertical-align: middle;
            padding: 10px 8px;
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
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">
            <i class="bi bi-lightning-charge"></i> My Energy Listings
        </h2>
        <a href="marketplace.php" class="btn btn-warning">
            <i class="bi bi-shop"></i> Back to Marketplace
        </a>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="bi bi-list-ul"></i> Active Listings
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0">
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

    <div class="card">
        <div class="card-header" style="background: linear-gradient(135deg, #3b82f6, #2563eb);">
            <h5 class="mb-0">
                <i class="bi bi-clock-history"></i> Sales History
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0">
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