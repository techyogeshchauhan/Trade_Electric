<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'seller') {
    header("Location: ../login.php");
    exit();
}

include '../includes/config.php';
$user_id = $_SESSION['user_id'];

// Get current time
$current_hour = (int)date('H');
$current_minute = (int)date('i');
$current_date = date('Y-m-d');

// Auto-populate meter data if not exists for today
$check_data = $conn->query("SELECT COUNT(*) as count FROM prosumer_meter_data 
                            WHERE user_id = $user_id AND date = '$current_date'");
$data_count = $check_data->fetch_assoc()['count'];

// If no data exists for today, we'll show a message to refresh
// (Auto-population happens via the refresh button or API call)

// Get token statistics
$token_stats = $conn->query("
    SELECT 
        COALESCE(SUM(CASE WHEN token_type = 'mint' THEN token_units ELSE 0 END), 0) as minted,
        COALESCE(SUM(CASE WHEN token_type = 'transfer_out' THEN token_units ELSE 0 END), 0) as sold
    FROM token_ledger 
    WHERE user_id = $user_id
")->fetch_assoc();

$tokens_minted = $token_stats['minted'] ?? 0;
$tokens_sold = $token_stats['sold'] ?? 0;
$tokens_available = max(0, $tokens_minted - $tokens_sold);

// Fetch today's energy data from database
$energy_query = $conn->query("
    SELECT time_block, generated_kwh, self_consumed_kwh, sold_kwh 
    FROM prosumer_meter_data 
    WHERE user_id = $user_id AND date = '$current_date'
    ORDER BY time_block ASC
");

$energy_data = [];
$db_data = [];

// Store database data in associative array
while ($row = $energy_query->fetch_assoc()) {
    $db_data[$row['time_block']] = [
        'generated' => (float)$row['generated_kwh'],
        'self_consumed' => (float)$row['self_consumed_kwh'],
        'available' => (float)$row['generated_kwh'] - (float)$row['self_consumed_kwh']
    ];
}

// Generate all 96 blocks (24 hours * 4 blocks per hour)
for ($h = 0; $h < 24; $h++) {
    for ($m = 0; $m < 60; $m += 15) {
        $start = sprintf("%02d:%02d", $h, $m);
        $end_h = $h;
        $end_m = $m + 15;
        if ($end_m >= 60) {
            $end_h++;
            $end_m = 0;
        }
        $end = sprintf("%02d:%02d", $end_h, $end_m);
        $block = "$start-$end";
        
        // Check if data exists in database
        if (isset($db_data[$block])) {
            $energy_data[] = [
                'block' => $block,
                'generated' => $db_data[$block]['generated'],
                'self_consumed' => $db_data[$block]['self_consumed'],
                'available' => $db_data[$block]['available']
            ];
        } else {
            // No data for this block (future or no generation)
            $energy_data[] = [
                'block' => $block,
                'generated' => 0,
                'self_consumed' => 0,
                'available' => 0
            ];
        }
    }
}

// Calculate totals
$total_generated = array_sum(array_column($energy_data, 'generated'));
$total_self_consumed = array_sum(array_column($energy_data, 'self_consumed'));
$total_available = array_sum(array_column($energy_data, 'available'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Energy Monitor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    
    <style>
        body { 
            background: #f8fafc;
            position: relative;
        }
        .main-content {
            margin-top: 80px;
            margin-left: 260px;
            padding: 25px;
            position: relative;
            z-index: 1;
        }
        .stat-card {
            border-radius: 12px;
            padding: 20px;
            color: white;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            position: relative;
            z-index: 1;
        }
        .card {
            border-radius: 12px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.08);
            position: relative;
            z-index: 1;
        }
        .table th {
            background: #0ea5e9;
            color: white;
            text-align: center;
            font-size: 14px;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .table td {
            text-align: center;
            vertical-align: middle;
            font-size: 13px;
        }
        .solar-hours {
            background: #fef3c7;
        }
        .peak-hour {
            background: #fef08a;
            font-weight: bold;
        }
        .no-solar {
            background: #f1f5f9;
            color: #94a3b8;
        }
        .token-badge {
            font-size: 24px;
            font-weight: bold;
        }
    </style>
</head>
<body>

<?php include '../includes/header.php'; ?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>
            <i class="bi bi-speedometer2 me-2"></i>Energy Monitor
            <small class="text-muted">(<?= date('d M Y') ?>)</small>
        </h2>
        <button id="refreshMeterData" class="btn btn-primary">
            <i class="bi bi-arrow-clockwise me-2"></i>Refresh Meter Data
        </button>
    </div>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card bg-warning">
                <i class="bi bi-sun fs-3 mb-2"></i>
                <h6>Generated</h6>
                <h3><?= number_format($total_generated, 2) ?> kWh</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card bg-info">
                <i class="bi bi-house fs-3 mb-2"></i>
                <h6>Self Consumed</h6>
                <h3><?= number_format($total_self_consumed, 2) ?> kWh</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card bg-success">
                <i class="bi bi-lightning-charge fs-3 mb-2"></i>
                <h6>Available to Sell</h6>
                <h3><?= number_format($total_available, 2) ?> kWh</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card bg-primary">
                <i class="bi bi-coin fs-3 mb-2"></i>
                <h6>Available Tokens</h6>
                <h3 class="token-badge"><?= number_format($tokens_available, 2) ?></h3>
            </div>
        </div>
    </div>

    <!-- Token Details -->
    <div class="card mb-4 p-3">
        <h5 class="mb-3"><i class="bi bi-coin me-2"></i>Token Summary</h5>
        <div class="row text-center">
            <div class="col-md-4">
                <div class="p-3 bg-light rounded">
                    <h6 class="text-muted">Tokens Minted</h6>
                    <h4 class="text-primary"><?= number_format($tokens_minted, 2) ?></h4>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 bg-light rounded">
                    <h6 class="text-muted">Tokens Sold</h6>
                    <h4 class="text-danger"><?= number_format($tokens_sold, 2) ?></h4>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 bg-light rounded">
                    <h6 class="text-muted">Available Tokens</h6>
                    <h4 class="text-success"><?= number_format($tokens_available, 2) ?></h4>
                </div>
            </div>
        </div>
        <p class="text-muted mt-3 mb-0 text-center">
            <i class="bi bi-info-circle me-1"></i>
            1 Unit = 1 Token | Solar hours: 9 AM - 5 PM | Peak: 2 PM - 3 PM
        </p>
    </div>

    <!-- Detailed Time Block Data -->
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-table me-2"></i>15-Minute Block Data (96 Blocks)</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                <table class="table table-bordered table-hover mb-0">
                    <thead class="sticky-top">
                        <tr>
                            <th>Time Block</th>
                            <th>Generated (kWh)</th>
                            <th>Self Consumed (kWh)</th>
                            <th>Available to Sell (kWh)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($energy_data as $data): 
                            $hour = (int)substr($data['block'], 0, 2);
                            $is_solar_hour = ($hour >= 9 && $hour < 17);
                            $is_peak = ($hour >= 14 && $hour < 15);
                            
                            $row_class = '';
                            if (!$is_solar_hour) {
                                $row_class = 'no-solar';
                            } elseif ($is_peak) {
                                $row_class = 'peak-hour';
                            } elseif ($is_solar_hour) {
                                $row_class = 'solar-hours';
                            }
                        ?>
                        <tr class="<?= $row_class ?>">
                            <td><strong><?= $data['block'] ?></strong></td>
                            <td><?= $data['generated'] ?></td>
                            <td><?= $data['self_consumed'] ?></td>
                            <td><?= $data['available'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Legend -->
    <div class="card mt-3 p-3">
        <h6 class="mb-2">Legend:</h6>
        <div class="d-flex gap-4">
            <div><span class="badge peak-hour">Yellow</span> Peak Hours (2-3 PM)</div>
            <div><span class="badge solar-hours">Light Yellow</span> Solar Hours (9 AM - 5 PM)</div>
            <div><span class="badge no-solar">Gray</span> No Solar Generation</div>
        </div>
    </div>

</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Refresh meter data button
$("#refreshMeterData").click(function(){
    const btn = $(this);
    btn.prop('disabled', true).html('<i class="spinner-border spinner-border-sm me-2"></i>Refreshing...');
    
    $.get("../../api/populate_meter_data.php", function(response){
        let data = typeof response === "string" ? JSON.parse(response) : response;
        
        if(data.status === "success"){
            alert("✅ " + data.message + "\n\nInserted: " + data.inserted + "\nUpdated: " + data.updated + "\nTotal: " + data.total);
            location.reload();
        } else {
            alert("❌ Failed to refresh meter data");
        }
    }).fail(function(){
        alert("❌ Server error occurred");
    }).always(function(){
        btn.prop('disabled', false).html('<i class="bi bi-arrow-clockwise me-2"></i>Refresh Meter Data');
    });
});
</script>

</body>
</html>
