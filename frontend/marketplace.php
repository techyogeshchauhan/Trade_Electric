<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'includes/config.php';
$role = strtolower($_SESSION['role'] ?? '');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Energy Trading Marketplace</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            background: #e8eef3;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .main-content { 
            padding: 20px; 
            margin-top: 80px;
            margin-left: 260px;
            max-height: calc(100vh - 95px);
            overflow-y: auto;
        }
        
        /* Page Title */
        .page-title {
            background: #fff;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .page-title h4 {
            margin: 0;
            font-size: 20px;
            font-weight: 600;
            color: #1e293b;
        }
        
        /* Market Cards */
        .market-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        /* Card Headers with specific colors */
        .card-header-yellow {
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            color: #78350f;
            padding: 12px 20px;
            font-weight: 700;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .card-header-blue {
            background: linear-gradient(135deg, #60a5fa, #3b82f6);
            color: #fff;
            padding: 12px 20px;
            font-weight: 700;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .card-header-dark {
            background: #1e293b;
            color: #fff;
            padding: 12px 20px;
            font-weight: 700;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .card-header-yellow .badge-price {
            background: #fff;
            color: #f59e0b;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            margin-left: auto;
        }
        
        .card-header-blue .badge-price {
            background: rgba(255,255,255,0.2);
            color: #fff;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            margin-left: auto;
        }
        
        /* Table Styling */
        .table {
            margin: 0 !important;
            font-size: 13px;
            border-collapse: collapse;
            width: 100%;
        }
        
        .table thead th {
            background: #1e293b;
            color: #ffffff;
            text-align: center;
            padding: 10px 8px !important;
            font-size: 12px;
            font-weight: 700;
            border-bottom: 2px solid #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .table tbody td {
            text-align: center;
            vertical-align: middle;
            padding: 12px 8px !important;
            font-size: 13px;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
        }
        
        .table tbody tr:hover {
            background-color: #f8fafc;
        }
        
        /* Status Badges */
        .badge-listed {
            background: #fbbf24;
            color: #78350f;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }
        
        .badge-available {
            background: #10b981;
            color: #fff;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }
        
        .badge-matched {
            background: #10b981;
            color: #fff;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }
        
        /* Action Buttons */
        .btn-match {
            background: #10b981;
            color: #fff;
            border: none;
            padding: 6px 16px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-match:hover {
            background: #059669;
            transform: translateY(-1px);
        }
        
        .btn-bid {
            background: #10b981;
            color: #fff;
            border: none;
            padding: 6px 16px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-bid:hover {
            background: #059669;
            transform: translateY(-1px);
        }
        
        /* Table Container */
        .table-responsive {
            max-height: 350px;
            overflow-y: auto;
            overflow-x: auto;
        }
        
        .table-responsive::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        .table-responsive::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        
        .table-responsive::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        
        /* Update Text */
        .update-text {
            font-size: 11px;
            color: #64748b;
            padding: 8px 20px;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
        }
        
        /* Responsive Design */
        @media (max-width: 992px) {
            .main-content { 
                margin-left: 0;
                padding: 15px;
            }
        }
        
        @media (max-width: 768px) {
            .main-content {
                padding: 10px;
                margin-top: 75px;
            }
            
            .table {
                font-size: 11px;
            }
            
            .table thead th {
                font-size: 10px;
                padding: 8px 4px !important;
            }
            
            .table tbody td {
                font-size: 11px;
                padding: 10px 4px !important;
            }
        }
    </style>
</head>
<body>

<?php include 'includes/header.php'; ?>

<div class="main-content">

    <!-- Page Title -->
    <div class="page-title">
        <h4>🏪 Live Marketplace</h4>
    </div>

    <!-- Parallel Tables Row -->
    <div class="row g-3 mb-3">
        <!-- Available Energy (Yellow Header) - Left Side -->
        <div class="col-lg-6">
            <div class="market-card">
                <div class="card-header-yellow">
                    <i class="bi bi-lightning-charge-fill"></i>
                    <span>Available Energy</span>
                    <span class="badge-price">Cheapest first</span>
                </div>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Seller Name</th>
                                <th>Units Available</th>
                                <th>Price</th>
                                <th>Units Left</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="listingTable">
                            <tr><td colspan="7" class="text-center py-4 text-muted">Loading available energy...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Buyer Demands (Blue Header) - Right Side -->
        <div class="col-lg-6">
            <div class="market-card">
                <div class="card-header-blue">
                    <i class="bi bi-person-fill"></i>
                    <span>Buyer Demands</span>
                    <span class="badge-price">Highest price first</span>
                </div>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Buyer Name</th>
                                <th>Units Required</th>
                                <th>Price</th>
                                <th>Units Left</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="demandTable">
                            <tr><td colspan="7" class="text-center py-4 text-muted">Loading buyer demands...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Live Best Matches (Dark Header) - Full Width -->
    <div class="market-card">
        <div class="card-header-dark">
            <i class="bi bi-fire"></i>
            <span>Live Best Matches</span>
        </div>
        <div class="update-text">Updated every 3 seconds</div>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Units Matched</th>
                        <th>Seller Name</th>
                        <th>Units Available</th>
                        <th>Buyer Name</th>
                        <th>Units Required</th>
                        <th>Price</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="matchTable">
                    <tr><td colspan="9" class="text-center py-4 text-muted">No matches yet...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Buyer → Place Bid
function placeBid(listing_id){
    $.post("../api/place_bid.php", {listing_id: listing_id}, function(res){
        alert(res);
        loadMarketplace();
    });
}

// Seller → Match Now
function matchNow(demand_id){
    $.post("../api/execute_match.php", {demand_id: demand_id}, function(res){
        try {
            let data = JSON.parse(res);

            if(data.error){
                alert(data.error);
                console.log(data.details || "");
            } else {
                alert("✅ Match Successful: " + data.matched_units + " units at price " + data.price);
            }

        } catch(e){
            console.error("Invalid JSON:", res);
        }

        loadMarketplace();
    });
}

// Load all marketplace data
function loadMarketplace() {
    // Fetch listings without hardcoded limit (API will use default from settings)
    $.get("../api/get_all_energy_listings.php", function(res) {
        $("#listingTable").html(res);
    }).fail(function(xhr, status, error) {
        console.error("Error loading energy listings:", error);
        $("#listingTable").html("<tr><td colspan='7' class='text-center text-danger'>Error loading data. Please refresh.</td></tr>");
    });

    $.get("../api/get_all_demands.php", function(res) {
        $("#demandTable").html(res);
    }).fail(function(xhr, status, error) {
        console.error("Error loading demands:", error);
        $("#demandTable").html("<tr><td colspan='7' class='text-center text-danger'>Error loading data. Please refresh.</td></tr>");
    });

    $.get("../api/get_market_matches.php", function(res) {
        $("#matchTable").html(res);
    }).fail(function(xhr, status, error) {
        console.error("Error loading matches:", error);
        $("#matchTable").html("<tr><td colspan='9' class='text-center text-danger'>Error loading data. Please refresh.</td></tr>");
    });
}

// Auto refresh every 3 seconds for live updates
setInterval(function() {
    loadMarketplace();
}, 3000);

// Initialize on page load
$(document).ready(function() {
    loadMarketplace();
});
</script>

</body>
</html>