<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include '../includes/config.php';

 $user_id = $_SESSION['user_id'];
 $role    = strtolower($_SESSION['role']);

// Date filters with validation
$date_from = $_GET['from'] ?? date('Y-m-d', strtotime('-7 days'));
$date_to   = $_GET['to'] ?? date('Y-m-d');

// Validate dates and prevent negative range
if (strtotime($date_from) > strtotime($date_to)) {
    // Swap dates if from > to
    $temp = $date_from;
    $date_from = $date_to;
    $date_to = $temp;
}

// Ensure dates are not in future
$today = date('Y-m-d');
if (strtotime($date_from) > strtotime($today)) {
    $date_from = $today;
}
if (strtotime($date_to) > strtotime($today)) {
    $date_to = $today;
}

// Build query based on role
if ($role === 'admin') {
    $where = "1";
} elseif ($role === 'buyer') {
    $where = "buyer_id = $user_id";
} else {
    $where = "seller_id = $user_id";
}

 $query = "
    SELECT 
        DATE(t.date) as trade_date,
        COUNT(*) as p2p_transactions,
        COALESCE(SUM(t.units), 0) as total_energy,
        COALESCE(SUM(t.total_amount), 0) as total_amount
    FROM trades t
    WHERE $where
      AND t.date BETWEEN '$date_from' AND '$date_to'
    GROUP BY DATE(t.date)
    ORDER BY trade_date ASC
";

 $result = $conn->query($query);
 $data = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

 $dates   = array_column($data, 'trade_date');
 $txns    = array_column($data, 'p2p_transactions');
 $energy  = array_column($data, 'total_energy');
 $amounts = array_column($data, 'total_amount');

// Totals
 $totalTxns   = array_sum($txns);
 $totalEnergy = array_sum($energy);
 $totalAmount = array_sum($amounts);
 $avgPrice    = $totalEnergy > 0 ? $totalAmount / $totalEnergy : 0;
 $maxTxn      = max(array_merge($txns, [1]));

// Role labels
 $titles = [
    'admin'  => 'Platform Analytics',
    'seller' => 'My Selling Analytics',
    'buyer'  => 'My Buying Analytics'
];
 $pageTitle  = $titles[$role] ?? 'Analytics';
 $barColor   = $role === 'buyer' ? '#3b82f6' : ($role === 'admin' ? '#6366f1' : '#10b981');
 $lineColor  = $role === 'admin' ? '#f59e0b' : '#ef4444';

// Token stats for admin
if ($role === 'admin') {
    $tokMinted  = $conn->query("SELECT COALESCE(SUM(token_units),0) as c FROM token_ledger WHERE token_type='mint'")->fetch_assoc()['c'];
    $tokBurned  = $conn->query("SELECT COALESCE(SUM(token_units),0) as c FROM token_ledger WHERE token_type='burn'")->fetch_assoc()['c'];
    $tokActive  = $conn->query("SELECT COALESCE(SUM(CASE WHEN token_type IN('mint','transfer_in') THEN token_units WHEN token_type IN('transfer_out','burn') THEN -token_units ELSE 0 END),0) as c FROM token_ledger")->fetch_assoc()['c'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <style>
        body {
            background: #f0f2f5;
            font-family: 'Segoe UI', sans-serif;
            color: #1f2937;
            font-size: 15px;
        }

        .main-content {
            margin-top: 90px;
            margin-left: 260px;
            padding: 24px 28px;
            max-width: 1300px;
        }

        .page-head {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 20px;
        }

        /* ── Summary Cards ── */
        .s-card {
            border-radius: 12px;
            padding: 18px 20px;
            text-align: center;
            color: #fff;
            transition: 0.2s;
        }
        .s-card:hover { transform: translateY(-3px); }
        .s-card .s-icon { font-size: 1.4rem; margin-bottom: 5px; opacity: 0.85; }
        .s-card .s-val { font-size: 24px; font-weight: 800; }
        .s-card .s-lbl { font-size: 12px; opacity: 0.8; text-transform: uppercase; letter-spacing: 0.4px; font-weight: 600; }

        .bg-blue { background: linear-gradient(135deg, #3b82f6, #2563eb); }
        .bg-green { background: linear-gradient(135deg, #10b981, #059669); }
        .bg-amber { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .bg-purple { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
        .bg-teal { background: linear-gradient(135deg, #14b8a6, #0d9488); }

        /* ── Token light cards ── */
        .t-card {
            border-radius: 12px;
            padding: 18px 20px;
            text-align: center;
            background: linear-gradient(135deg, #fffbeb, #fef3c7);
            border: 2px solid #fbbf24;
            color: #78350f;
            transition: 0.2s;
        }
        .t-card:hover { transform: translateY(-3px); }
        .t-card .s-icon { font-size: 1.4rem; color: #d97706; margin-bottom: 5px; }
        .t-card .s-val { font-size: 24px; font-weight: 800; color: #b45309; }
        .t-card .s-lbl { font-size: 12px; color: #92400e; text-transform: uppercase; letter-spacing: 0.4px; font-weight: 600; }

        /* ── Filter Bar ── */
        .filter-bar {
            background: #fff;
            border-radius: 12px;
            padding: 16px 24px;
            margin-bottom: 20px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            display: flex;
            gap: 12px;
            align-items: flex-end;
            flex-wrap: wrap;
        }

        .filter-bar label {
            font-size: 13px;
            font-weight: 700;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-bottom: 4px;
        }

        .filter-bar input,
        .filter-bar select,
        .filter-bar button {
            border-radius: 8px;
            font-size: 14px;
        }

        .filter-bar input,
        .filter-bar select {
            border: 1.5px solid #d1d5db;
            padding: 8px 12px;
        }
        .filter-bar input:focus,
        .filter-bar select:focus {
            border-color: #818cf8;
            box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
            outline: none;
        }

        /* ── Chart Card ── */
        .chart-card {
            background: #fff;
            border-radius: 14px;
            padding: 24px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
            margin-bottom: 20px;
        }

        .chart-card h5 {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .chart-card .chart-sub {
            font-size: 13px;
            color: #9ca3af;
            margin-bottom: 16px;
        }

        .chart-wrap {
            position: relative;
            height: 380px;
        }

        /* ── Table ── */
        .table-card {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
            overflow: hidden;
        }

        .table-card h5 {
            font-size: 16px;
            font-weight: 700;
            margin: 0;
            padding: 18px 24px;
            border-bottom: 2px solid #f3f4f6;
        }

        .a-table {
            margin: 0;
            font-size: 15px;
        }

        .a-table thead th {
            background: #1e293b;
            color: #fff;
            font-size: 13px;
            font-weight: 600;
            padding: 12px 14px;
            text-align: center;
            border: 1px solid #374151;
            letter-spacing: 0.3px;
        }

        .a-table tbody td {
            padding: 12px 14px;
            text-align: center;
            vertical-align: middle;
            border: 1px solid #e5e7eb;
            font-size: 15px;
        }

        .a-table tbody tr:hover { background: #f9fafb; }

        .no-data {
            height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #9ca3af;
            font-size: 16px;
        }

        @media (max-width: 992px) {
            .main-content { margin-left: 0; padding: 16px; }
        }
    </style>
</head>

<body>

<?php include '../includes/header.php'; ?>

<div class="main-content">

<div class="page-head">
    <i class="bi bi-graph-up me-2 text-primary"></i><?= $pageTitle ?>
</div>

<!-- DATE FILTER -->
<div class="filter-bar">
    <form method="GET" class="d-flex gap-2 align-items-end flex-grow-1" style="margin:0;" onsubmit="return validateDates()">
        <div>
            <label>From</label>
            <input type="date" name="from" id="dateFrom" value="<?= $date_from ?>" max="<?= date('Y-m-d') ?>" class="form-control" required>
        </div>
        <div>
            <label>To</label>
            <input type="date" name="to" id="dateTo" value="<?= $date_to ?>" max="<?= date('Y-m-d') ?>" class="form-control" required>
        </div>
        <div>
            <button type="submit" class="btn btn-primary" style="padding:8px 22px; font-weight:600;">
                <i class="bi bi-funnel me-1"></i>Apply
            </button>
        </div>
        <a href="analytics.php" class="btn btn-outline-secondary" style="padding:8px 16px; font-weight:600; border-radius:8px;">
            <i class="bi bi-x-lg me-1"></i>Reset
        </a>
    </form>
</div>

<script>
function validateDates() {
    const fromDate = document.getElementById('dateFrom').value;
    const toDate = document.getElementById('dateTo').value;
    
    if (!fromDate || !toDate) {
        alert('Please select both dates');
        return false;
    }
    
    if (new Date(fromDate) > new Date(toDate)) {
        alert('From date cannot be after To date');
        return false;
    }
    
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    if (new Date(fromDate) > today || new Date(toDate) > today) {
        alert('Dates cannot be in the future');
        return false;
    }
    
    return true;
}
</script>

<!-- SUMMARY CARDS -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="s-card bg-blue">
            <div class="s-icon"><i class="bi bi-arrow-left-right"></i></div>
            <div class="s-val"><?= number_format($totalTxns) ?></div>
            <div class="s-lbl">Total Transactions</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="s-card bg-green">
            <div class="s-icon"><i class="bi bi-lightning-charge-fill"></i></div>
            <div class="s-val"><?= number_format($totalEnergy, 0) ?></div>
            <div class="s-lbl">Total Energy (kWh)</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="s-card bg-amber">
            <div class="s-icon"><i class="bi bi-currency-rupee"></i></div>
            <div class="s-val">₹<?= number_format($totalAmount, 0) ?></div>
            <div class="s-lbl">Total Amount</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="s-card bg-purple">
            <div class="s-icon"><i class="bi bi-graph-up-arrow"></i></div>
            <div class="s-val">₹<?= number_format($avgPrice, 2) ?></div>
            <div class="s-lbl">Avg Price/kWh</div>
        </div>
    </div>
</div>

<!-- HOURLY ENERGY REQUIREMENT GRAPH (Buyer/Seller specific) -->
<?php if ($role === 'buyer' || $role === 'seller'): 
    // Get hourly energy data for today
    $today = date('Y-m-d');
    $hourly_query = "
        SELECT 
            HOUR(STR_TO_DATE(SUBSTRING_INDEX(time_block, '-', 1), '%H:%i')) as hour,
            SUM(units) as total_units
        FROM trades
        WHERE $where
        AND date = '$today'
        GROUP BY hour
        ORDER BY hour ASC
    ";
    $hourly_result = $conn->query($hourly_query);
    $hourly_data = [];
    
    // Initialize all 24 hours with 0
    for ($h = 0; $h < 24; $h++) {
        $hourly_data[$h] = 0;
    }
    
    // Fill actual data
    if ($hourly_result) {
        while ($row = $hourly_result->fetch_assoc()) {
            $hourly_data[(int)$row['hour']] = (float)$row['total_units'];
        }
    }
    
    $hours = array_keys($hourly_data);
    $hourly_units = array_values($hourly_data);
    
    // Get user name from session/database
    $user_query = $conn->query("SELECT name FROM users WHERE id = $user_id");
    $user_data = $user_query->fetch_assoc();
    $user_name = $user_data['name'] ?? 'User';
    
    // Get GESCOM average from settings or calculate dynamically
    $gescom_settings = $conn->query("SELECT gescom_avg_consumption, gescom_avg_supply FROM settings LIMIT 1");
    $gescom_data = $gescom_settings ? $gescom_settings->fetch_assoc() : null;
    
    if ($role === 'buyer') {
        $gescom_avg = $gescom_data['gescom_avg_consumption'] ?? 0;
    } else {
        $gescom_avg = $gescom_data['gescom_avg_supply'] ?? 0;
    }
?>

<!-- HORIZONTAL LAYOUT FOR BOTH GRAPHS -->
<div class="row g-3">
    <!-- Hourly Energy Graph -->
    <div class="col-lg-6">
        <div class="chart-card">
            <h5><i class="bi bi-clock-history me-2 text-success"></i>Hourly Energy <?= $role === 'buyer' ? 'Requirement' : 'Supply' ?></h5>
            <div class="chart-sub"><?= $user_name ?> (<?= ucfirst($role) ?>) - <?= date('d M Y') ?></div>

            <div class="chart-wrap">
                <canvas id="hourlyChart"></canvas>
            </div>
        </div>
    </div>

    <!-- P2P Transactions Chart -->
    <div class="col-lg-6">
        <div class="chart-card">
            <h5><i class="bi bi-bar-chart-line me-2 text-primary"></i>P2P Transactions vs Energy</h5>
            <div class="chart-sub"><?= date('d M Y', strtotime($date_from)) ?> — <?= date('d M Y', strtotime($date_to)) ?></div>

            <?php if (empty($dates)): ?>
                <div class="no-data">
                    <div class="text-center">
                        <i class="bi bi-bar-chart-line fs-1 mb-3"></i><br>
                        No trading data for this period
                    </div>
                </div>
            <?php else: ?>
                <div class="chart-wrap">
                    <canvas id="mainChart"></canvas>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php else: ?>
    <!-- For Admin - Full Width Chart -->
    <div class="chart-card">
        <h5><i class="bi bi-bar-chart-line me-2 text-primary"></i>P2P Transactions vs Total Energy</h5>
        <div class="chart-sub"><?= date('d M Y', strtotime($date_from)) ?> — <?= date('d M Y', strtotime($date_to)) ?></div>

        <?php if (empty($dates)): ?>
            <div class="no-data">
                <div class="text-center">
                    <i class="bi bi-bar-chart-line fs-1 mb-3"></i><br>
                    No trading data for this period
                </div>
            </div>
        <?php else: ?>
            <div class="chart-wrap">
                <canvas id="mainChart"></canvas>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>





<!-- BUYER-SPECIFIC ADDITIONAL GRAPHS -->
<?php if ($role === 'buyer'): 
    // 1. Daily Spending Trend
    $spending_query = "
        SELECT 
            DATE(t.date) as trade_date,
            COALESCE(SUM(t.total_amount), 0) as daily_spending
        FROM trades t
        WHERE buyer_id = $user_id
        AND t.date BETWEEN '$date_from' AND '$date_to'
        GROUP BY DATE(t.date)
        ORDER BY trade_date ASC
    ";
    $spending_result = $conn->query($spending_query);
    $spending_data = $spending_result ? $spending_result->fetch_all(MYSQLI_ASSOC) : [];
    $spending_dates = array_column($spending_data, 'trade_date');
    $spending_amounts = array_column($spending_data, 'daily_spending');

    // 2. Price Comparison (Buyer's avg vs Market avg)
    $buyer_avg_query = "
        SELECT COALESCE(AVG(t.price_per_unit), 0) as buyer_avg
        FROM trades t
        WHERE buyer_id = $user_id
        AND t.date BETWEEN '$date_from' AND '$date_to'
    ";
    $buyer_avg_result = $conn->query($buyer_avg_query);
    $buyer_avg_price = $buyer_avg_result ? $buyer_avg_result->fetch_assoc()['buyer_avg'] : 0;

    $market_avg_query = "
        SELECT COALESCE(AVG(t.price_per_unit), 0) as market_avg
        FROM trades t
        WHERE t.date BETWEEN '$date_from' AND '$date_to'
    ";
    $market_avg_result = $conn->query($market_avg_query);
    $market_avg_price = $market_avg_result ? $market_avg_result->fetch_assoc()['market_avg'] : 0;

    // 3. Peak vs Off-Peak Consumption
    $peak_query = "
        SELECT 
            CASE 
                WHEN HOUR(STR_TO_DATE(SUBSTRING_INDEX(time_block, '-', 1), '%H:%i')) BETWEEN 10 AND 17 THEN 'Peak Hours (10 AM - 5 PM)'
                ELSE 'Off-Peak Hours'
            END as period,
            COALESCE(SUM(units), 0) as total_units
        FROM trades
        WHERE buyer_id = $user_id
        AND date BETWEEN '$date_from' AND '$date_to'
        GROUP BY period
    ";
    $peak_result = $conn->query($peak_query);
    $peak_data = [];
    if ($peak_result) {
        while ($row = $peak_result->fetch_assoc()) {
            $peak_data[$row['period']] = (float)$row['total_units'];
        }
    }
    $peak_hours = $peak_data['Peak Hours (10 AM - 5 PM)'] ?? 0;
    $offpeak_hours = $peak_data['Off-Peak Hours'] ?? 0;

    // 4. Top 5 Sellers
    $top_sellers_query = "
        SELECT 
            u.name as seller_name,
            COALESCE(SUM(t.units), 0) as total_units,
            COALESCE(SUM(t.total_amount), 0) as total_spent
        FROM trades t
        JOIN users u ON t.seller_id = u.id
        WHERE t.buyer_id = $user_id
        AND t.date BETWEEN '$date_from' AND '$date_to'
        GROUP BY t.seller_id, u.name
        ORDER BY total_units DESC
        LIMIT 5
    ";
    $top_sellers_result = $conn->query($top_sellers_query);
    $top_sellers = $top_sellers_result ? $top_sellers_result->fetch_all(MYSQLI_ASSOC) : [];
    $seller_names = array_column($top_sellers, 'seller_name');
    $seller_units = array_column($top_sellers, 'total_units');

    // 5. Contract Status Distribution
    $contract_status_query = "
        SELECT 
            status,
            COUNT(*) as count
        FROM contracts
        WHERE buyer_id = $user_id
        GROUP BY status
    ";
    $contract_status_result = $conn->query($contract_status_query);
    $contract_status = [];
    if ($contract_status_result) {
        while ($row = $contract_status_result->fetch_assoc()) {
            $contract_status[$row['status']] = (int)$row['count'];
        }
    }
    $confirmed_contracts = $contract_status['confirmed'] ?? 0;
    $pending_contracts = $contract_status['pending'] ?? 0;
?>

<!-- ROW 1: Daily Spending + Price Comparison -->
<div class="row g-3 mt-2">
    <div class="col-lg-6">
        <div class="chart-card">
            <h5><i class="bi bi-graph-up me-2 text-danger"></i>Daily Spending Trend</h5>
            <div class="chart-sub">Your spending pattern over time</div>
            
            <?php if (empty($spending_dates)): ?>
                <div class="no-data">
                    <div class="text-center">
                        <i class="bi bi-currency-rupee fs-1 mb-3"></i><br>
                        No spending data for this period
                    </div>
                </div>
            <?php else: ?>
                <div class="chart-wrap">
                    <canvas id="spendingChart"></canvas>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="chart-card">
            <h5><i class="bi bi-bar-chart me-2 text-info"></i>Price Comparison</h5>
            <div class="chart-sub">Your average vs market average (₹/kWh)</div>
            
            <?php if ($buyer_avg_price == 0 && $market_avg_price == 0): ?>
                <div class="no-data">
                    <div class="text-center">
                        <i class="bi bi-cash-stack fs-1 mb-3"></i><br>
                        No price data available
                    </div>
                </div>
            <?php else: ?>
                <div class="chart-wrap">
                    <canvas id="priceComparisonChart"></canvas>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ROW 2: Peak/Off-Peak + Top Sellers -->
<div class="row g-3 mt-2">
    <div class="col-lg-6">
        <div class="chart-card">
            <h5><i class="bi bi-pie-chart me-2 text-warning"></i>Energy Consumption Pattern</h5>
            <div class="chart-sub">Peak vs Off-Peak hours distribution</div>
            
            <?php if ($peak_hours == 0 && $offpeak_hours == 0): ?>
                <div class="no-data">
                    <div class="text-center">
                        <i class="bi bi-lightning-charge fs-1 mb-3"></i><br>
                        No consumption data available
                    </div>
                </div>
            <?php else: ?>
                <div class="chart-wrap">
                    <canvas id="peakOffPeakChart"></canvas>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="chart-card">
            <h5><i class="bi bi-people-fill me-2 text-success"></i>Top 5 Sellers</h5>
            <div class="chart-sub">Most purchased from (by energy units)</div>
            
            <?php if (empty($seller_names)): ?>
                <div class="no-data">
                    <div class="text-center">
                        <i class="bi bi-shop fs-1 mb-3"></i><br>
                        No seller data available
                    </div>
                </div>
            <?php else: ?>
                <div class="chart-wrap">
                    <canvas id="topSellersChart"></canvas>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ROW 3: Contract Status Distribution -->
<div class="row g-3 mt-2">
    <div class="col-lg-6 mx-auto">
        <div class="chart-card">
            <h5><i class="bi bi-file-earmark-text me-2 text-primary"></i>Contract Status Distribution</h5>
            <div class="chart-sub">Overview of your contracts</div>
            
            <?php if ($confirmed_contracts == 0 && $pending_contracts == 0): ?>
                <div class="no-data">
                    <div class="text-center">
                        <i class="bi bi-file-earmark-check fs-1 mb-3"></i><br>
                        No contracts available
                    </div>
                </div>
            <?php else: ?>
                <div class="chart-wrap">
                    <canvas id="contractStatusChart"></canvas>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php endif; ?>

<!-- SELLER-SPECIFIC ADDITIONAL GRAPHS -->
<?php if ($role === 'seller'): 
    // Debug: Show that seller section is loading
    echo "<!-- SELLER GRAPHS SECTION LOADING -->";
    
    // 1. Daily Revenue Trend
    $revenue_query = "
        SELECT 
            DATE(t.date) as trade_date,
            COALESCE(SUM(t.total_amount), 0) as daily_revenue
        FROM trades t
        WHERE seller_id = $user_id
        AND t.date BETWEEN '$date_from' AND '$date_to'
        GROUP BY DATE(t.date)
        ORDER BY trade_date ASC
    ";
    $revenue_result = $conn->query($revenue_query);
    $revenue_data = $revenue_result ? $revenue_result->fetch_all(MYSQLI_ASSOC) : [];
    $revenue_dates = array_column($revenue_data, 'trade_date');
    $revenue_amounts = array_column($revenue_data, 'daily_revenue');

    // 2. Price Comparison (Seller's avg vs Market avg)
    $seller_avg_query = "
        SELECT COALESCE(AVG(t.price_per_unit), 0) as seller_avg
        FROM trades t
        WHERE seller_id = $user_id
        AND t.date BETWEEN '$date_from' AND '$date_to'
    ";
    $seller_avg_result = $conn->query($seller_avg_query);
    $seller_avg_price = $seller_avg_result ? $seller_avg_result->fetch_assoc()['seller_avg'] : 0;

    $market_avg_query = "
        SELECT COALESCE(AVG(t.price_per_unit), 0) as market_avg
        FROM trades t
        WHERE t.date BETWEEN '$date_from' AND '$date_to'
    ";
    $market_avg_result = $conn->query($market_avg_query);
    $market_avg_price = $market_avg_result ? $market_avg_result->fetch_assoc()['market_avg'] : 0;

    // 3. Peak vs Off-Peak Sales
    $peak_query = "
        SELECT 
            CASE 
                WHEN HOUR(STR_TO_DATE(SUBSTRING_INDEX(time_block, '-', 1), '%H:%i')) BETWEEN 10 AND 17 THEN 'Peak Hours (10 AM - 5 PM)'
                ELSE 'Off-Peak Hours'
            END as period,
            COALESCE(SUM(units), 0) as total_units
        FROM trades
        WHERE seller_id = $user_id
        AND date BETWEEN '$date_from' AND '$date_to'
        GROUP BY period
    ";
    $peak_result = $conn->query($peak_query);
    $peak_data = [];
    if ($peak_result) {
        while ($row = $peak_result->fetch_assoc()) {
            $peak_data[$row['period']] = (float)$row['total_units'];
        }
    }
    $peak_hours = $peak_data['Peak Hours (10 AM - 5 PM)'] ?? 0;
    $offpeak_hours = $peak_data['Off-Peak Hours'] ?? 0;

    // 4. Top 5 Buyers
    $top_buyers_query = "
        SELECT 
            u.name as buyer_name,
            COALESCE(SUM(t.units), 0) as total_units,
            COALESCE(SUM(t.total_amount), 0) as total_earned
        FROM trades t
        JOIN users u ON t.buyer_id = u.id
        WHERE t.seller_id = $user_id
        AND t.date BETWEEN '$date_from' AND '$date_to'
        GROUP BY t.buyer_id, u.name
        ORDER BY total_units DESC
        LIMIT 5
    ";
    $top_buyers_result = $conn->query($top_buyers_query);
    $top_buyers = $top_buyers_result ? $top_buyers_result->fetch_all(MYSQLI_ASSOC) : [];
    $buyer_names = array_column($top_buyers, 'buyer_name');
    $buyer_units = array_column($top_buyers, 'total_units');

    // 5. Energy Listings Status
    $listings_status_query = "
        SELECT 
            CASE 
                WHEN remaining_units > 0 THEN 'Available'
                ELSE 'Sold Out'
            END as status,
            COUNT(*) as count
        FROM energy_listings
        WHERE user_id = $user_id
        GROUP BY status
    ";
    $listings_status_result = $conn->query($listings_status_query);
    $listings_status = [];
    if ($listings_status_result) {
        while ($row = $listings_status_result->fetch_assoc()) {
            $listings_status[$row['status']] = (int)$row['count'];
        }
    }
    $available_listings = $listings_status['Available'] ?? 0;
    $soldout_listings = $listings_status['Sold Out'] ?? 0;
?>

<!-- ROW 1: Daily Revenue + Price Comparison -->
<div class="row g-3 mt-2">
    <div class="col-lg-6">
        <div class="chart-card">
            <h5><i class="bi bi-graph-up me-2 text-success"></i>Daily Revenue Trend</h5>
            <div class="chart-sub">Your earnings pattern over time</div>
            
            <?php if (empty($revenue_dates)): ?>
                <div class="no-data">
                    <div class="text-center">
                        <i class="bi bi-currency-rupee fs-1 mb-3"></i><br>
                        No revenue data for this period
                    </div>
                </div>
            <?php else: ?>
                <div class="chart-wrap">
                    <canvas id="revenueChart"></canvas>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="chart-card">
            <h5><i class="bi bi-bar-chart me-2 text-info"></i>Price Comparison</h5>
            <div class="chart-sub">Your average vs market average (₹/kWh)</div>
            
            <?php if ($seller_avg_price == 0 && $market_avg_price == 0): ?>
                <div class="no-data">
                    <div class="text-center">
                        <i class="bi bi-cash-stack fs-1 mb-3"></i><br>
                        No price data available
                    </div>
                </div>
            <?php else: ?>
                <div class="chart-wrap">
                    <canvas id="sellerPriceComparisonChart"></canvas>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ROW 2: Peak/Off-Peak + Top Buyers -->
<div class="row g-3 mt-2">
    <div class="col-lg-6">
        <div class="chart-card">
            <h5><i class="bi bi-pie-chart me-2 text-warning"></i>Energy Sales Pattern</h5>
            <div class="chart-sub">Peak vs Off-Peak hours distribution</div>
            
            <?php if ($peak_hours == 0 && $offpeak_hours == 0): ?>
                <div class="no-data">
                    <div class="text-center">
                        <i class="bi bi-lightning-charge fs-1 mb-3"></i><br>
                        No sales data available
                    </div>
                </div>
            <?php else: ?>
                <div class="chart-wrap">
                    <canvas id="sellerPeakOffPeakChart"></canvas>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="chart-card">
            <h5><i class="bi bi-people-fill me-2 text-primary"></i>Top 5 Buyers</h5>
            <div class="chart-sub">Most sold to (by energy units)</div>
            
            <?php if (empty($buyer_names)): ?>
                <div class="no-data">
                    <div class="text-center">
                        <i class="bi bi-person-check fs-1 mb-3"></i><br>
                        No buyer data available
                    </div>
                </div>
            <?php else: ?>
                <div class="chart-wrap">
                    <canvas id="topBuyersChart"></canvas>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ROW 3: Listings Status Distribution -->
<div class="row g-3 mt-2">
    <div class="col-lg-6 mx-auto">
        <div class="chart-card">
            <h5><i class="bi bi-list-check me-2 text-success"></i>Energy Listings Status</h5>
            <div class="chart-sub">Overview of your listings</div>
            
            <?php if ($available_listings == 0 && $soldout_listings == 0): ?>
                <div class="no-data">
                    <div class="text-center">
                        <i class="bi bi-clipboard-check fs-1 mb-3"></i><br>
                        No listings available
                    </div>
                </div>
            <?php else: ?>
                <div class="chart-wrap">
                    <canvas id="listingsStatusChart"></canvas>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
const dates   = <?= json_encode($dates) ?>;
const txns    = <?= json_encode($txns) ?>;
const energy  = <?= json_encode($energy) ?>;
const amounts = <?= json_encode($amounts) ?>;

// Main Chart - P2P Transactions vs Energy
if (dates.length > 0) {
    new Chart(document.getElementById('mainChart'), {
        type: 'bar',
        data: {
            labels: dates.map(d => {
                const dt = new Date(d);
                return dt.toLocaleDateString('en-IN', { day: 'numeric', month: 'short' });
            }),
            datasets: [
                {
                    label: 'P2P Transactions',
                    data: txns,
                    backgroundColor: '<?= $barColor ?>',
                    borderColor: '#1e3a8a',
                    borderWidth: 2,
                    borderRadius: 6,
                    yAxisID: 'y',
                    order: 2
                },
                {
                    label: 'Total Energy (kWh)',
                    data: energy,
                    type: 'line',
                    borderColor: '<?= $lineColor ?>',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    borderWidth: 3,
                    pointRadius: 5,
                    pointBackgroundColor: '<?= $lineColor ?>',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    tension: 0.3,
                    fill: true,
                    yAxisID: 'y1',
                    order: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false
            },
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        font: { size: 14, weight: '600' },
                        padding: 16,
                        usePointStyle: true,
                        pointStyle: 'rectRounded'
                    }
                },
                tooltip: {
                    backgroundColor: '#1e293b',
                    titleFont: { size: 14, weight: '600' },
                    bodyFont: { size: 14 },
                    padding: 12,
                    cornerRadius: 8,
                    callbacks: {
                        label: function(ctx) {
                            let label = ctx.dataset.label || '';
                            if (ctx.dataset.yAxisID === 'y') {
                                return label + ': ' + ctx.parsed.y;
                            }
                            return label + ': ' + ctx.parsed.y + ' kWh';
                        }
                    }
                }
            },
            scales: {
                y: {
                    type: 'linear',
                    position: 'left',
                    beginAtZero: true,
                    max: <?= ceil($maxTxn * 1.3) ?>,
                    ticks: {
                        stepSize: <?= max(1, ceil($maxTxn / 5)) ?>,
                        font: { size: 13 },
                        color: '#6b7280'
                    },
                    title: {
                        display: true,
                        text: 'Transactions',
                        font: { size: 14, weight: '600' },
                        color: '#374151'
                    },
                    grid: {
                        color: 'rgba(0,0,0,0.06)',
                        drawBorder: false
                    }
                },
                y1: {
                    type: 'linear',
                    position: 'right',
                    beginAtZero: true,
                    ticks: {
                        font: { size: 13 },
                        color: '#6b7280'
                    },
                    title: {
                        display: true,
                        text: 'Energy (kWh)',
                        font: { size: 14, weight: '600' },
                        color: '#374151'
                    },
                    grid: {
                        drawOnChartArea: false
                    }
                },
                x: {
                    ticks: {
                        maxRotation: 45,
                        minRotation: 45,
                        font: { size: 12 },
                        color: '#6b7280'
                    },
                    grid: {
                        color: 'rgba(0,0,0,0.04)',
                        drawBorder: false
                    }
                }
            }
        }
    });
}

<?php if ($role === 'buyer' || $role === 'seller'): ?>
// Hourly Energy Chart
const hourlyData = <?= json_encode($hourly_units) ?>;
const userName = '<?= $user_name ?>';
const gescomAvg = <?= $gescom_avg ?>;

// Generate time labels (00:00, 00:15, 00:30, etc.)
const timeLabels = [];
for (let h = 0; h < 24; h++) {
    for (let m = 0; m < 60; m += 15) {
        timeLabels.push(`${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`);
    }
}

// Create hourly data with 15-min intervals (distribute hourly data across 4 blocks)
const detailedData = [];
hourlyData.forEach(val => {
    const quarterVal = val / 4;
    detailedData.push(quarterVal, quarterVal, quarterVal, quarterVal);
});

// GESCOM baseline (constant)
const gescomData = new Array(timeLabels.length).fill(gescomAvg);

// Calculate dynamic max value for Y-axis
const maxHourlyValue = Math.max(...detailedData, gescomAvg);
const chartMaxValue = Math.ceil(maxHourlyValue * 1.2); // 20% padding above max value

new Chart(document.getElementById('hourlyChart'), {
    type: 'line',
    data: {
        labels: timeLabels,
        datasets: [
            {
                label: 'GESCOM',
                data: gescomData,
                borderColor: '#94a3b8',
                backgroundColor: 'rgba(148, 163, 184, 0.1)',
                borderWidth: 2,
                borderDash: [5, 5],
                pointRadius: 0,
                tension: 0,
                fill: false
            },
            {
                label: userName,
                data: detailedData,
                borderColor: '<?= $role === "buyer" ? "#ef4444" : "#10b981" ?>',
                backgroundColor: '<?= $role === "buyer" ? "rgba(239, 68, 68, 0.1)" : "rgba(16, 185, 129, 0.1)" ?>',
                borderWidth: 3,
                pointRadius: 0,
                tension: 0.4,
                fill: true
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
            mode: 'index',
            intersect: false
        },
        plugins: {
            legend: {
                position: 'top',
                labels: {
                    font: { size: 14, weight: '600' },
                    padding: 16,
                    usePointStyle: true
                }
            },
            tooltip: {
                backgroundColor: '#1e293b',
                titleFont: { size: 13, weight: '600' },
                bodyFont: { size: 13 },
                padding: 10,
                cornerRadius: 8,
                callbacks: {
                    label: function(ctx) {
                        return ctx.dataset.label + ': ' + ctx.parsed.y.toFixed(2) + ' kWh';
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                max: chartMaxValue,
                ticks: {
                    stepSize: Math.ceil(chartMaxValue / 5),
                    font: { size: 12 },
                    color: '#6b7280',
                    callback: function(value) {
                        return value + ' kWh';
                    }
                },
                title: {
                    display: true,
                    text: 'Energy (kWh)',
                    font: { size: 13, weight: '600' },
                    color: '#374151'
                },
                grid: {
                    color: 'rgba(0,0,0,0.06)'
                }
            },
            x: {
                ticks: {
                    maxRotation: 45,
                    minRotation: 45,
                    font: { size: 10 },
                    color: '#6b7280',
                    autoSkip: true,
                    maxTicksLimit: 24
                },
                title: {
                    display: true,
                    text: 'Time of the Day',
                    font: { size: 13, weight: '600' },
                    color: '#374151'
                },
                grid: {
                    color: 'rgba(0,0,0,0.04)'
                }
            }
        }
    }
});
<?php endif; ?>

<?php if ($role === 'buyer'): ?>
// ========== BUYER-SPECIFIC CHARTS ==========

// 1. Daily Spending Trend Chart
const spendingDates = <?= json_encode($spending_dates) ?>;
const spendingAmounts = <?= json_encode($spending_amounts) ?>;

if (spendingDates.length > 0) {
    new Chart(document.getElementById('spendingChart'), {
        type: 'line',
        data: {
            labels: spendingDates.map(d => {
                const dt = new Date(d);
                return dt.toLocaleDateString('en-IN', { day: 'numeric', month: 'short' });
            }),
            datasets: [{
                label: 'Daily Spending (₹)',
                data: spendingAmounts,
                borderColor: '#ef4444',
                backgroundColor: 'rgba(239, 68, 68, 0.1)',
                borderWidth: 3,
                pointRadius: 5,
                pointBackgroundColor: '#ef4444',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        font: { size: 14, weight: '600' },
                        padding: 16
                    }
                },
                tooltip: {
                    backgroundColor: '#1e293b',
                    titleFont: { size: 14, weight: '600' },
                    bodyFont: { size: 14 },
                    padding: 12,
                    cornerRadius: 8,
                    callbacks: {
                        label: function(ctx) {
                            return 'Spent: ₹' + ctx.parsed.y.toFixed(2);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        font: { size: 13 },
                        color: '#6b7280',
                        callback: function(value) {
                            return '₹' + value;
                        }
                    },
                    title: {
                        display: true,
                        text: 'Amount (₹)',
                        font: { size: 14, weight: '600' },
                        color: '#374151'
                    },
                    grid: {
                        color: 'rgba(0,0,0,0.06)'
                    }
                },
                x: {
                    ticks: {
                        maxRotation: 45,
                        minRotation: 45,
                        font: { size: 12 },
                        color: '#6b7280'
                    },
                    grid: {
                        color: 'rgba(0,0,0,0.04)'
                    }
                }
            }
        }
    });
}

// 2. Price Comparison Chart
const buyerAvgPrice = <?= $buyer_avg_price ?>;
const marketAvgPrice = <?= $market_avg_price ?>;

if (buyerAvgPrice > 0 || marketAvgPrice > 0) {
    const savings = marketAvgPrice - buyerAvgPrice;
    const savingsPercent = marketAvgPrice > 0 ? ((savings / marketAvgPrice) * 100).toFixed(1) : 0;
    
    new Chart(document.getElementById('priceComparisonChart'), {
        type: 'bar',
        data: {
            labels: ['Your Avg Price', 'Market Avg Price'],
            datasets: [{
                label: 'Price per kWh (₹)',
                data: [buyerAvgPrice, marketAvgPrice],
                backgroundColor: ['#3b82f6', '#f59e0b'],
                borderColor: ['#1e40af', '#d97706'],
                borderWidth: 2,
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: '#1e293b',
                    titleFont: { size: 14, weight: '600' },
                    bodyFont: { size: 14 },
                    padding: 12,
                    cornerRadius: 8,
                    callbacks: {
                        label: function(ctx) {
                            return '₹' + ctx.parsed.y.toFixed(2) + ' per kWh';
                        },
                        afterLabel: function(ctx) {
                            if (ctx.dataIndex === 0 && savings > 0) {
                                return 'You saved ₹' + savings.toFixed(2) + ' (' + savingsPercent + '%)';
                            }
                            return '';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        font: { size: 13 },
                        color: '#6b7280',
                        callback: function(value) {
                            return '₹' + value.toFixed(2);
                        }
                    },
                    title: {
                        display: true,
                        text: 'Price per kWh (₹)',
                        font: { size: 14, weight: '600' },
                        color: '#374151'
                    },
                    grid: {
                        color: 'rgba(0,0,0,0.06)'
                    }
                },
                x: {
                    ticks: {
                        font: { size: 13, weight: '600' },
                        color: '#374151'
                    },
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
}

// 3. Peak vs Off-Peak Consumption Chart
const peakHours = <?= $peak_hours ?>;
const offPeakHours = <?= $offpeak_hours ?>;

if (peakHours > 0 || offPeakHours > 0) {
    new Chart(document.getElementById('peakOffPeakChart'), {
        type: 'doughnut',
        data: {
            labels: ['Peak Hours (10 AM - 5 PM)', 'Off-Peak Hours'],
            datasets: [{
                data: [peakHours, offPeakHours],
                backgroundColor: ['#f59e0b', '#10b981'],
                borderColor: ['#fff', '#fff'],
                borderWidth: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        font: { size: 14, weight: '600' },
                        padding: 20,
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                },
                tooltip: {
                    backgroundColor: '#1e293b',
                    titleFont: { size: 14, weight: '600' },
                    bodyFont: { size: 14 },
                    padding: 12,
                    cornerRadius: 8,
                    callbacks: {
                        label: function(ctx) {
                            const total = peakHours + offPeakHours;
                            const percent = ((ctx.parsed / total) * 100).toFixed(1);
                            return ctx.label + ': ' + ctx.parsed.toFixed(2) + ' kWh (' + percent + '%)';
                        }
                    }
                }
            }
        }
    });
}

// 4. Top 5 Sellers Chart
const sellerNames = <?= json_encode($seller_names) ?>;
const sellerUnits = <?= json_encode($seller_units) ?>;

if (sellerNames.length > 0) {
    new Chart(document.getElementById('topSellersChart'), {
        type: 'bar',
        data: {
            labels: sellerNames,
            datasets: [{
                label: 'Energy Purchased (kWh)',
                data: sellerUnits,
                backgroundColor: '#10b981',
                borderColor: '#059669',
                borderWidth: 2,
                borderRadius: 8
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: '#1e293b',
                    titleFont: { size: 14, weight: '600' },
                    bodyFont: { size: 14 },
                    padding: 12,
                    cornerRadius: 8,
                    callbacks: {
                        label: function(ctx) {
                            return ctx.parsed.x.toFixed(2) + ' kWh';
                        }
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: {
                        font: { size: 13 },
                        color: '#6b7280',
                        callback: function(value) {
                            return value + ' kWh';
                        }
                    },
                    title: {
                        display: true,
                        text: 'Energy (kWh)',
                        font: { size: 14, weight: '600' },
                        color: '#374151'
                    },
                    grid: {
                        color: 'rgba(0,0,0,0.06)'
                    }
                },
                y: {
                    ticks: {
                        font: { size: 13, weight: '600' },
                        color: '#374151'
                    },
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
}

// 5. Contract Status Distribution Chart
const confirmedContracts = <?= $confirmed_contracts ?>;
const pendingContracts = <?= $pending_contracts ?>;

if (confirmedContracts > 0 || pendingContracts > 0) {
    new Chart(document.getElementById('contractStatusChart'), {
        type: 'doughnut',
        data: {
            labels: ['Confirmed', 'Pending'],
            datasets: [{
                data: [confirmedContracts, pendingContracts],
                backgroundColor: ['#10b981', '#f59e0b'],
                borderColor: ['#fff', '#fff'],
                borderWidth: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        font: { size: 14, weight: '600' },
                        padding: 20,
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                },
                tooltip: {
                    backgroundColor: '#1e293b',
                    titleFont: { size: 14, weight: '600' },
                    bodyFont: { size: 14 },
                    padding: 12,
                    cornerRadius: 8,
                    callbacks: {
                        label: function(ctx) {
                            const total = confirmedContracts + pendingContracts;
                            const percent = ((ctx.parsed / total) * 100).toFixed(1);
                            return ctx.label + ': ' + ctx.parsed + ' contracts (' + percent + '%)';
                        }
                    }
                }
            }
        }
    });
}
<?php endif; ?>

<?php if ($role === 'seller'): ?>
// ========== SELLER-SPECIFIC CHARTS ==========

// 1. Daily Revenue Trend Chart
const revenueDates = <?= json_encode($revenue_dates) ?>;
const revenueAmounts = <?= json_encode($revenue_amounts) ?>;

if (revenueDates.length > 0) {
    new Chart(document.getElementById('revenueChart'), {
        type: 'line',
        data: {
            labels: revenueDates.map(d => {
                const dt = new Date(d);
                return dt.toLocaleDateString('en-IN', { day: 'numeric', month: 'short' });
            }),
            datasets: [{
                label: 'Daily Revenue (₹)',
                data: revenueAmounts,
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                borderWidth: 3,
                pointRadius: 5,
                pointBackgroundColor: '#10b981',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        font: { size: 14, weight: '600' },
                        padding: 16
                    }
                },
                tooltip: {
                    backgroundColor: '#1e293b',
                    titleFont: { size: 14, weight: '600' },
                    bodyFont: { size: 14 },
                    padding: 12,
                    cornerRadius: 8,
                    callbacks: {
                        label: function(ctx) {
                            return 'Earned: ₹' + ctx.parsed.y.toFixed(2);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        font: { size: 13 },
                        color: '#6b7280',
                        callback: function(value) {
                            return '₹' + value;
                        }
                    },
                    title: {
                        display: true,
                        text: 'Revenue (₹)',
                        font: { size: 14, weight: '600' },
                        color: '#374151'
                    },
                    grid: {
                        color: 'rgba(0,0,0,0.06)'
                    }
                },
                x: {
                    ticks: {
                        maxRotation: 45,
                        minRotation: 45,
                        font: { size: 12 },
                        color: '#6b7280'
                    },
                    grid: {
                        color: 'rgba(0,0,0,0.04)'
                    }
                }
            }
        }
    });
}

// 2. Seller Price Comparison Chart
const sellerAvgPrice = <?= $seller_avg_price ?>;
const sellerMarketAvgPrice = <?= $market_avg_price ?>;

if (sellerAvgPrice > 0 || sellerMarketAvgPrice > 0) {
    const priceDiff = sellerAvgPrice - sellerMarketAvgPrice;
    const priceDiffPercent = sellerMarketAvgPrice > 0 ? ((priceDiff / sellerMarketAvgPrice) * 100).toFixed(1) : 0;
    
    new Chart(document.getElementById('sellerPriceComparisonChart'), {
        type: 'bar',
        data: {
            labels: ['Your Avg Price', 'Market Avg Price'],
            datasets: [{
                label: 'Price per kWh (₹)',
                data: [sellerAvgPrice, sellerMarketAvgPrice],
                backgroundColor: ['#10b981', '#f59e0b'],
                borderColor: ['#059669', '#d97706'],
                borderWidth: 2,
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: '#1e293b',
                    titleFont: { size: 14, weight: '600' },
                    bodyFont: { size: 14 },
                    padding: 12,
                    cornerRadius: 8,
                    callbacks: {
                        label: function(ctx) {
                            return '₹' + ctx.parsed.y.toFixed(2) + ' per kWh';
                        },
                        afterLabel: function(ctx) {
                            if (ctx.dataIndex === 0 && priceDiff > 0) {
                                return 'Premium: ₹' + priceDiff.toFixed(2) + ' (' + priceDiffPercent + '%)';
                            } else if (ctx.dataIndex === 0 && priceDiff < 0) {
                                return 'Below market: ₹' + Math.abs(priceDiff).toFixed(2) + ' (' + Math.abs(priceDiffPercent) + '%)';
                            }
                            return '';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        font: { size: 13 },
                        color: '#6b7280',
                        callback: function(value) {
                            return '₹' + value.toFixed(2);
                        }
                    },
                    title: {
                        display: true,
                        text: 'Price per kWh (₹)',
                        font: { size: 14, weight: '600' },
                        color: '#374151'
                    },
                    grid: {
                        color: 'rgba(0,0,0,0.06)'
                    }
                },
                x: {
                    ticks: {
                        font: { size: 13, weight: '600' },
                        color: '#374151'
                    },
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
}

// 3. Seller Peak vs Off-Peak Sales Chart
const sellerPeakHours = <?= $peak_hours ?>;
const sellerOffPeakHours = <?= $offpeak_hours ?>;

if (sellerPeakHours > 0 || sellerOffPeakHours > 0) {
    new Chart(document.getElementById('sellerPeakOffPeakChart'), {
        type: 'doughnut',
        data: {
            labels: ['Peak Hours (10 AM - 5 PM)', 'Off-Peak Hours'],
            datasets: [{
                data: [sellerPeakHours, sellerOffPeakHours],
                backgroundColor: ['#f59e0b', '#10b981'],
                borderColor: ['#fff', '#fff'],
                borderWidth: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        font: { size: 14, weight: '600' },
                        padding: 20,
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                },
                tooltip: {
                    backgroundColor: '#1e293b',
                    titleFont: { size: 14, weight: '600' },
                    bodyFont: { size: 14 },
                    padding: 12,
                    cornerRadius: 8,
                    callbacks: {
                        label: function(ctx) {
                            const total = sellerPeakHours + sellerOffPeakHours;
                            const percent = ((ctx.parsed / total) * 100).toFixed(1);
                            return ctx.label + ': ' + ctx.parsed.toFixed(2) + ' kWh (' + percent + '%)';
                        }
                    }
                }
            }
        }
    });
}

// 4. Top 5 Buyers Chart
const buyerNames = <?= json_encode($buyer_names) ?>;
const buyerUnits = <?= json_encode($buyer_units) ?>;

if (buyerNames.length > 0) {
    new Chart(document.getElementById('topBuyersChart'), {
        type: 'bar',
        data: {
            labels: buyerNames,
            datasets: [{
                label: 'Energy Sold (kWh)',
                data: buyerUnits,
                backgroundColor: '#3b82f6',
                borderColor: '#2563eb',
                borderWidth: 2,
                borderRadius: 8
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: '#1e293b',
                    titleFont: { size: 14, weight: '600' },
                    bodyFont: { size: 14 },
                    padding: 12,
                    cornerRadius: 8,
                    callbacks: {
                        label: function(ctx) {
                            return ctx.parsed.x.toFixed(2) + ' kWh';
                        }
                    }
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: {
                        font: { size: 13 },
                        color: '#6b7280',
                        callback: function(value) {
                            return value + ' kWh';
                        }
                    },
                    title: {
                        display: true,
                        text: 'Energy (kWh)',
                        font: { size: 14, weight: '600' },
                        color: '#374151'
                    },
                    grid: {
                        color: 'rgba(0,0,0,0.06)'
                    }
                },
                y: {
                    ticks: {
                        font: { size: 13, weight: '600' },
                        color: '#374151'
                    },
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
}

// 5. Listings Status Distribution Chart
const availableListings = <?= $available_listings ?>;
const soldoutListings = <?= $soldout_listings ?>;

if (availableListings > 0 || soldoutListings > 0) {
    new Chart(document.getElementById('listingsStatusChart'), {
        type: 'doughnut',
        data: {
            labels: ['Available', 'Sold Out'],
            datasets: [{
                data: [availableListings, soldoutListings],
                backgroundColor: ['#10b981', '#ef4444'],
                borderColor: ['#fff', '#fff'],
                borderWidth: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        font: { size: 14, weight: '600' },
                        padding: 20,
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                },
                tooltip: {
                    backgroundColor: '#1e293b',
                    titleFont: { size: 14, weight: '600' },
                    bodyFont: { size: 14 },
                    padding: 12,
                    cornerRadius: 8,
                    callbacks: {
                        label: function(ctx) {
                            const total = availableListings + soldoutListings;
                            const percent = ((ctx.parsed / total) * 100).toFixed(1);
                            return ctx.label + ': ' + ctx.parsed + ' listings (' + percent + '%)';
                        }
                    }
                }
            }
        }
    });
}
<?php endif; ?>
</script>

</body>
</html>