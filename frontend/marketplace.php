<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: seller/login.php");
    exit();
}

include 'includes/config.php';
$role = $_SESSION['role'];
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
            color: white;
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

<?php include 'includes/header.php'; ?>

<div class="main-content">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0 fw-semibold">
                <i class="bi bi-lightning-charge-fill text-warning"></i> 
                Energy Trading Marketplace
            </h2>
            <p class="text-muted mb-0">Real-time buyer & seller trading</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="row mb-4 g-3">
        <div class="col-md-3">
            <label class="form-label fw-bold">Date</label>
            <input type="date" id="filter_date" class="form-control">
        </div>
        <div class="col-md-3">
            <label class="form-label fw-bold">Time Block</label>
            <select id="filter_time" class="form-select">
                <option value="">All</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label fw-bold">Sort</label>
            <select id="sort_by" class="form-select">
                <option value="best_match">Best Match</option>
                <option value="price_low">Price: Low to High</option>
                <option value="price_high">Price: High to Low</option>
            </select>
        </div>
        <div class="col-md-3 d-flex align-items-end">
            <button class="btn btn-primary w-100" onclick="loadMarketplace()">
                <i class="bi bi-funnel-fill"></i> Apply
            </button>
        </div>
    </div>

    <div class="row g-4">

        <!-- Available Energy -->
        <div class="col-lg-6">
            <div class="market-card card h-100">
                <div class="card-header py-3">
                    <h5 class="mb-0 section-title">
                        ⚡ Available Energy
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Seller</th>
                                    <th>Units</th>
                                    <th>Price</th>
                                    <th>Remaining</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="listingTable">
                                <tr><td colspan="7" class="text-center py-5 text-muted">Loading available energy...</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-center p-3 border-top">
                        <button class="btn btn-outline-primary btn-sm" onclick="showMoreListings()">
                            <i class="bi bi-arrow-down-circle me-1"></i>Show More
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Buyer Demand -->
        <div class="col-lg-6">
            <div class="market-card card h-100">
                <div class="card-header py-3">
                    <h5 class="mb-0 section-title">
                        🛒 Consumer Demand
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Buyer</th>
                                    <th>Units</th>
                                    <th>Max Price</th>
                                    <th>Remaining</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="demandTable">
                                <tr><td colspan="7" class="text-center py-5 text-muted">Loading buyer demands...</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-center p-3 border-top">
                        <button class="btn btn-outline-success btn-sm" onclick="showMoreListings()">
                            <i class="bi bi-arrow-down-circle me-1"></i>Show More
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Live Matches -->
    <div class="mt-4">
        <div class="market-card card">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 section-title">🔥 Live Matches</h5>
               
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Units</th>
                                <th>Prosumer</th>
                                <th>Available</th>
                                <th>Consumer</th>
                                <th>Required</th>
                                <th>Price</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="matchTable">
                            <tr><td colspan="9" class="text-center py-5 text-muted">No matches yet...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Generate Time Blocks
function generateTimeBlocks() {
    let select = document.getElementById("filter_time");
    for (let hour = 0; hour < 24; hour++) {
        for (let min = 0; min < 60; min += 15) {
            let start = `${String(hour).padStart(2, '0')}:${String(min).padStart(2, '0')}`;
            let endMin = min + 15;
            let endHour = hour + (endMin >= 60 ? 1 : 0);
            endMin = endMin >= 60 ? 0 : endMin;
            if (endHour === 24) endHour = 0;

            let end = `${String(endHour).padStart(2, '0')}:${String(endMin).padStart(2, '0')}`;
            let option = document.createElement("option");
            option.value = `${start}-${end}`;
            option.textContent = `${start} - ${end}`;
            select.appendChild(option);
        }
    }
}

function setTodayDate() {
    let today = new Date().toISOString().split('T')[0];
    document.getElementById("filter_date").value = today;
}

// Buyer → Place Bid
function placeBid(listing_id){
    $.post("../api/place_bid.php", {listing_id: listing_id}, function(res){
        alert(res);
        loadMarketplace();
    });
}

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

// Load all data with limit
function loadMarketplace(limit = 10) {
    $.get("../api/get_all_energy_listings.php?limit=" + limit, function(res) {
        $("#listingTable").html(res);
    });

    $.get("../api/get_all_demands.php?limit=" + limit, function(res) {
        $("#demandTable").html(res);
    });

    $.get("../api/get_market_matches.php?limit=" + limit, function(res) {
        $("#matchTable").html(res);
    });
}

// Show more functionality
let currentLimit = 10;

function showMoreListings() {
    currentLimit += 10;
    loadMarketplace(currentLimit);
}

// Auto refresh every 10 seconds (increased from 5)
setInterval(function() {
    loadMarketplace(currentLimit);
}, 10000);

// Initialize
$(document).ready(function() {
    generateTimeBlocks();
    setTodayDate();
    loadMarketplace();
});
</script>

</body>
</html>