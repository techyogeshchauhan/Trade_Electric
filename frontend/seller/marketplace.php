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
    <link href="../assets/css/style.css" rel="stylesheet">

    <style>
        body {
            background: #f8fafc;
        }
        
        .main-content { 
            padding: 15px; 
            margin-top: 80px;
            margin-left: 260px;
            max-height: calc(100vh - 95px);
            overflow-y: auto;
        }
        
        .market-card {
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            margin-bottom: 15px;
        }
        
        .market-card:hover {
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }
        
        .card-header {
            padding: 12px 16px !important;
            border-radius: 12px 12px 0 0 !important;
        }
        
        .card-header h5 {
            font-size: 18px !important;
            margin: 0 !important;
            font-weight: 700;
        }
        
        .card-header small {
            font-size: 13px !important;
        }
        
        .badge-live {
            background: #ef4444;
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        .table {
            margin: 0 !important;
            font-size: 13px;
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
        }
        
        .table th {
            background: #1e293b;
            color: #ffffff;
            text-align: center;
            padding: 10px 8px !important;
            font-size: 12px;
            white-space: nowrap;
            font-weight: 600;
            border: 1px solid #374151;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .table td {
            text-align: center;
            vertical-align: middle;
            padding: 8px 6px !important;
            font-size: 13px;
            border: 1px solid #e5e7eb;
        }
        
        .table tbody tr:hover {
            background-color: #f3f4f6;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
            white-space: nowrap;
            border-radius: 6px;
        }
        
        .table-responsive {
            max-height: 280px;
            overflow-y: auto;
            overflow-x: auto;
            border-radius: 0 0 12px 12px;
        }
        
        .table-responsive::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        .table-responsive::-webkit-scrollbar-thumb {
            background: #94a3b8;
            border-radius: 4px;
        }
        
        .table-responsive::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        
        /* Responsive Design */
        @media (max-width: 1200px) {
            .main-content {
                margin-left: 260px;
            }
            
            .table {
                font-size: 12px;
            }
            
            .table th {
                font-size: 11px;
                padding: 8px 6px !important;
            }
            
            .table td {
                font-size: 12px;
                padding: 6px 4px !important;
            }
        }
        
        @media (max-width: 992px) {
            .main-content { 
                margin-left: 0;
                padding: 12px;
                margin-top: 80px;
            }
            
            .card-header h5 {
                font-size: 16px !important;
            }
            
            .table {
                font-size: 11px;
            }
            
            .table th {
                font-size: 10px;
                padding: 6px 4px !important;
            }
            
            .table td {
                font-size: 11px;
                padding: 5px 3px !important;
            }
            
            .table-responsive {
                max-height: 220px;
            }
            
            .btn-sm {
                padding: 4px 8px;
                font-size: 11px;
            }
        }
        
        @media (max-width: 768px) {
            .main-content {
                padding: 10px;
                margin-top: 75px;
            }
            
            h2 {
                font-size: 20px !important;
            }
            
            .card-header h5 {
                font-size: 14px !important;
            }
            
            .card-header small {
                font-size: 11px !important;
            }
            
            .table {
                font-size: 10px;
            }
            
            .table th {
                font-size: 9px;
                padding: 5px 3px !important;
            }
            
            .table td {
                font-size: 10px;
                padding: 4px 2px !important;
            }
            
            .table-responsive {
                max-height: 200px;
            }
            
            .btn-sm {
                padding: 3px 6px;
                font-size: 10px;
            }
            
            .badge-live {
                font-size: 11px;
                padding: 4px 8px;
            }
            
            .market-card {
                margin-bottom: 12px;
            }
        }
        
        @media (max-width: 576px) {
            .main-content {
                padding: 8px;
                margin-top: 70px;
            }
            
            h2 {
                font-size: 18px !important;
            }
            
            .card-header {
                padding: 10px 12px !important;
            }
            
            .card-header h5 {
                font-size: 13px !important;
            }
            
            .card-header small {
                font-size: 10px !important;
                display: block;
                margin-top: 2px;
            }
            
            .table {
                font-size: 9px;
            }
            
            .table th {
                font-size: 8px;
                padding: 4px 2px !important;
            }
            
            .table td {
                font-size: 9px;
                padding: 3px 2px !important;
            }
            
            .table-responsive {
                max-height: 180px;
            }
            
            .btn-sm {
                padding: 2px 5px;
                font-size: 9px;
            }
            
            .badge-live {
                font-size: 10px;
                padding: 3px 6px;
            }
            
            .d-flex.justify-content-between {
                flex-direction: column;
                align-items: flex-start !important;
                gap: 8px;
            }
            
            .market-card {
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body>

<?php include '../includes/header.php'; ?>

<div class="main-content">
    
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
        <h2 class="mb-0">🏪 Live Marketplace</h2>
        <span class="badge badge-live">● LIVE</span>
    </div>

    <div class="row g-3">

        <!-- Available Energy (Seller / Prosumer Listings) -->
        <div class="col-lg-6 col-md-12">
            <div class="market-card card h-100" style="background: linear-gradient(135deg, #fef3c7 0%, #fef9e7 100%); border: 2px solid #fcd34d;">
                <div class="card-header d-flex justify-content-between flex-wrap" style="background: linear-gradient(135deg, #fbbf24, #f59e0b); color: white; border-bottom: 2px solid #d97706;">
                    <h5 class="mb-0">⚡ Available Energy</h5>
                    <small style="color: rgba(255,255,255,0.9);">Cheapest first</small>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Seller</th>
                                    <th>Units</th>
                                    <th>Price</th>
                                    <th>Left</th>
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
                <div class="card-header d-flex justify-content-between flex-wrap" style="background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; border-bottom: 2px solid #1d4ed8;">
                    <h5 class="mb-0">🛒 Buyer Demands</h5>
                    <small style="color: rgba(255,255,255,0.9);">Highest price first</small>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Buyer</th>
                                    <th>Units</th>
                                    <th>Max ₹</th>
                                    <th>Left</th>
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
    <div class="row mt-3">
        <div class="col-12">
            <div class="market-card card">
                <div class="card-header bg-white d-flex justify-content-between flex-wrap">
                    <h5 class="mb-0">🔥 Live Best Matches</h5>
                    <small class="text-muted">Updated every 3 seconds</small>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover mb-0 table-sm">
                            <thead class="table-dark">
                                <tr>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Units</th>
                                    <th>Seller</th>
                                    <th>Avail</th>
                                    <th>Buyer</th>
                                    <th>Need</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="matchTable">
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