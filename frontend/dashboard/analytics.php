<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include '../includes/config.php';

 $user_id = $_SESSION['user_id'];
 $role    = strtolower($_SESSION['role']);

// Date filters
 $date_from = $_GET['from'] ?? date('Y-m-d', strtotime('-30 days'));
 $date_to   = $_GET['to'] ?? date('Y-m-d');

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
    <form method="GET" class="d-flex gap-2 align-items-end flex-grow-1" style="margin:0;">
        <div>
            <label>From</label>
            <input type="date" name="from" value="<?= $date_from ?>" class="form-control">
        </div>
        <div>
            <label>To</label>
            <input type="date" name="to" value="<?= $date_to ?>" class="form-control">
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





<!-- CHART -->
<div class="chart-card">
    <h5><i class="bi bi-bar-chart-line me-2 text-primary"></i>My P2P Transactions vs Total Energy</h5>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
const dates   = <?= json_encode($dates) ?>;
const txns    = <?= json_encode($txns) ?>;
const energy  = <?= json_encode($energy) ?>;
const amounts = <?= json_encode($amounts) ?>;

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
</script>

</body>
</html>