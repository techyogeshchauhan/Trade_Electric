<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include '../includes/config.php';
$role = $_SESSION['role'];
$user_name = $_SESSION['name'] ?? 'User';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Energy Marketplace - EnergyTrade</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../../assets/css/style.css" rel="stylesheet">

    <style>
        .main-content { 
            padding: 12px; 
            margin-top: 8px;
            margin-left: 260px;
            max-height: calc(100vh - 85px);
            overflow-y: auto;
        }
        .market-card {
            border-radius: 10px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            margin-bottom: 10px;
        }
        .card-header {
            padding: 10px 15px !important;
        }
        .card-header h5 {
            font-size: 16px !important;
            margin: 0 !important;
            font-weight: 600;
        }
        .card-header small {
            font-size: 12px !important;
        }
        .table {
            margin: 0 !important;
            font-size: 12px;
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
            table-layout: fixed;
        }
        .table th {
            background: #1e293b;
            color: #ffffff;
            text-align: center;
            padding: 8px 4px !important;
            font-size: 11px;
            white-space: nowrap;
            font-weight: 600;
            border: 1px solid #374151;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .table td {
            text-align: center;
            vertical-align: middle;
            padding: 6px 4px !important;
            white-space: nowrap;
            font-size: 12px;
            border: 1px solid #e5e7eb;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .table tbody tr:hover {
            background-color: #f3f4f6;
        }
        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
            white-space: nowrap;
        }
        .table-responsive {
            max-height: 240px;
            overflow-y: auto;
            overflow-x: hidden;
        }
        .table-responsive::-webkit-scrollbar {
            width: 6px;
        }
        .table-responsive::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 3px;
        }
        /* Column widths */
        .col-date { width: 11%; min-width: 75px; }
        .col-time { width: 13%; min-width: 85px; }
        .col-name { width: 15%; min-width: 85px; }
        .col-units { width: 12%; min-width: 65px; }
        .col-price { width: 11%; min-width: 60px; }
        .col-left { width: 12%; min-width: 65px; }
        .col-action { width: 14%; min-width: 80px; }
        
        @media (max-width: 992px) {
            .main-content { margin-left: 0; }
        }
    </style>
</head>
<body>

<?php include '../includes/header.php'; ?>

<div class="main-content">
    
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">🏪 Live Marketplace</h2>
        <span class="badge badge-live">● LIVE</span>
    </div>

    <div class="row g-2">

        <!-- Available Energy (Seller / Prosumer Listings) -->
        <div class="col-lg-6 col-md-12">
            <div class="market-card card h-100" style="background: linear-gradient(135deg, #fef3c7 0%, #fef9e7 100%); border: 2px solid #fcd34d;">
                <div class="card-header d-flex justify-content-between" style="background: linear-gradient(135deg, #fbbf24, #f59e0b); color: white; border-bottom: 2px solid #d97706;">
                    <h5 class="mb-0">⚡ Available Energy</h5>
                    <small style="color: rgba(255,255,255,0.9);">Cheapest first</small>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 240px; overflow-x: hidden;">
                        <table class="table table-hover mb-0 table-sm table-bordered" style="width: 100%;">
                            <thead>
                                <tr>
                                    <th class="col-date">Date</th>
                                    <th class="col-time">Time</th>
                                    <th class="col-name">Seller</th>
                                    <th class="col-units">Units</th>
                                    <th class="col-price">Price</th>
                                    <th class="col-left">Left</th>
                                </tr>
                            </thead>
                            <tbody id="listingTable">
                                <tr><td colspan="6" class="text-center py-2">Loading...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Buyer Demands -->
        <div class="col-lg-6 col-md-12">
            <div class="market-card card h-100" style="background: linear-gradient(135deg, #dbeafe 0%, #eff6ff 100%); border: 2px solid #93c5fd;">
                <div class="card-header d-flex justify-content-between" style="background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; border-bottom: 2px solid #1d4ed8;">
                    <h5 class="mb-0">🛒 Buyer Demands</h5>
                    <small style="color: rgba(255,255,255,0.9);">Highest price first</small>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 240px; overflow-x: hidden;">
                        <table class="table table-hover mb-0 table-sm table-bordered" style="width: 100%;">
                            <thead>
                                <tr>
                                    <th class="col-date">Date</th>
                                    <th class="col-time">Time</th>
                                    <th class="col-name">Buyer</th>
                                    <th class="col-units">Units</th>
                                    <th class="col-price">Max ₹</th>
                                    <th class="col-left">Left</th>
                                </tr>
                            </thead>
                            <tbody id="demandTable">
                                <tr><td colspan="6" class="text-center py-2">Loading...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Live Best Matches Section -->
    <div class="row mt-1">
        <div class="col-12">
            <div class="market-card card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">🔥 Live Best Matches</h5>
                    <small class="text-muted">Updated every 3 seconds</small>
                </div>
                <div class="card-body">
                    <div class="table-responsive" style="max-height: 180px; overflow-x: hidden;">
                        <table class="table table-bordered table-hover mb-0 table-sm" style="width: 100%; table-layout: fixed;">
                            <thead class="table-dark">
                                <tr>
                                    <th style="width: 9%; font-size: 10px;">Date</th>
                                    <th style="width: 10%; font-size: 10px;">Time</th>
                                    <th style="width: 7%; font-size: 10px;">Units</th>
                                    <th style="width: 14%; font-size: 10px;">Seller</th>
                                    <th style="width: 9%; font-size: 10px;">Avail</th>
                                    <th style="width: 14%; font-size: 10px;">Buyer</th>
                                    <th style="width: 9%; font-size: 10px;">Need</th>
                                    <th style="width: 9%; font-size: 10px;">Price</th>
                                    <th style="width: 11%; font-size: 10px;">Status</th>
                                </tr>
                            </thead>
                            <tbody id="matchTable" style="font-size: 11px;">
                                <tr><td colspan="9" class="text-center py-2">Waiting for matches...</td></tr>
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
// Load All Sections
function loadMarketplace() {
    // Available Energy Listings
    $.get("../../api/get_all_energy_listings.php", function(res) {
        $("#listingTable").html(res);
    }).fail(function(xhr, status, error) {
        console.error("Energy Listings Error:", error);
        $("#listingTable").html("<tr><td colspan='6' class='text-danger'>Error loading listings</td></tr>");
    });

    // Buyer Demands
    $.get("../../api/get_all_demands.php", function(res) {
        $("#demandTable").html(res);
    }).fail(function(xhr, status, error) {
        console.error("Demands Error:", error);
        $("#demandTable").html("<tr><td colspan='6' class='text-danger'>Error loading demands</td></tr>");
    });

    // Live Matches
    $.get("../../api/get_market_matches.php", function(res) {
        $("#matchTable").html(res);
    }).fail(function(xhr, status, error) {
        console.error("Matches Error:", error);
        $("#matchTable").html("<tr><td colspan='9' class='text-danger'>Error loading matches</td></tr>");
    });
}



// Live Auto Refresh every 3 seconds
setInterval(loadMarketplace, 3000);

// Initial Load
loadMarketplace();
</script>

</body>
</html>