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
        :root {
            --primary: #3b82f6;
        }

        .main-content {
            margin-top: 90px;
            margin-left: 260px;
            padding: 25px 30px;
            min-height: calc(100vh - 90px);
            background: #f8fafc;
        }

        .market-card {
            border-radius: 16px;
            box-shadow: 0 8px 25px rgba(19, 138, 145, 0.08);
            border: none;
            overflow: hidden;
        }

        .card-header {
            background: white !important;
            border-bottom: 1px solid #e2e8f0;
        }

        .table th {
            background: #1e2937;
            color: white;
            font-weight: 500;
            text-align: center;
            padding: 14px 10px;
            font-size: 14px;
        }

        .table td {
            padding: 14px 10px;
            vertical-align: middle;
            text-align: center;
        }

        .live-badge {
            background: #10b981;
            color: white;
            padding: 5px 14px;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .price {
            font-weight: 600;
            color: #1e40af;
        }

        .remaining {
            background: #e0f2fe;
            color: #0369a1;
            padding: 4px 10px;
            border-radius: 6px;
            font-weight: 500;
        }

        .btn-place-bid {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 6px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
        }

        .btn-match-now {
            background: #10b981;
            color: white;
            border: none;
            padding: 6px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
        }

        .section-title {
            font-size: 1.1rem;
            font-weight: 600;
        }

        
        @media (max-width: 992px) {
            .main-content {
                margin-left: 0;
                margin-top: 90px;
                padding: 15px;
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
                                <th>Consumer</th>
                                <th>Price</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="matchTable">
                            <tr><td colspan="7" class="text-center py-5 text-muted">No matches yet...</td></tr>
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