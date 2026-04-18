<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'seller') {
    header("Location: ../login.php");
    exit();
}

include '../includes/config.php';
$user_id = $_SESSION['user_id'];
$current_date = date('Y-m-d');
$current_time = date('H:i');

// Get current hour and minute for determining editable blocks
list($current_hour, $current_minute) = explode(':', $current_time);
$current_hour = (int)$current_hour;
$current_minute = (int)$current_minute;

// Fetch existing energy data
$energy_query = $conn->query("
    SELECT time_block, generated_kwh, self_consumed_kwh, sold_kwh 
    FROM prosumer_meter_data 
    WHERE user_id = $user_id AND date = '$current_date'
");

$db_data = [];
while ($row = $energy_query->fetch_assoc()) {
    $db_data[$row['time_block']] = $row;
}

// Get contracts for today
$contracts_query = $conn->query("
    SELECT time_block, SUM(units) as total_units, COUNT(*) as contract_count
    FROM contracts 
    WHERE seller_id = $user_id AND date = '$current_date'
    GROUP BY time_block
");

$contracts_data = [];
while ($row = $contracts_query->fetch_assoc()) {
    $contracts_data[$row['time_block']] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Energy Monitor - Dynamic</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    
    <style>
        body { background: #f8fafc; }
        .main-content {
            margin-top: 80px;
            margin-left: 260px;
            padding: 25px;
        }
        .card {
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            border: none;
            overflow: hidden;
        }
        .card-header {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            padding: 16px 20px;
            font-weight: 700;
            font-size: 16px;
        }
        .table {
            margin: 0;
            font-size: 14px;
            border-collapse: collapse;
        }
        .table thead th {
            background: #2d3748;
            color: #ffffff;
            text-align: center;
            font-size: 14px;
            padding: 14px 12px;
            font-weight: 600;
            border: none;
        }
        .table tbody td {
            text-align: center;
            vertical-align: middle;
            font-size: 14px;
            padding: 12px;
            border: none;
            border-bottom: 1px solid #e2e8f0;
            background: #ffffff;
        }
        .row-past {
            background: #f1f5f9 !important;
        }
        .row-current {
            background: #fef3c7 !important;
        }
        .row-future {
            background: #ffffff !important;
        }
        .row-has-contract {
            border-left: 4px solid #10b981;
        }
        .input-energy {
            width: 100px;
            padding: 6px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            text-align: center;
        }
        .input-energy:disabled {
            background: #e2e8f0;
            cursor: not-allowed;
        }
        .btn-save {
            padding: 6px 16px;
            font-size: 13px;
            border-radius: 6px;
        }
        .status-badge {
            padding: 4px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-locked { background: #e2e8f0; color: #64748b; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-filled { background: #d1fae5; color: #065f46; }
        .status-editable { background: #dbeafe; color: #1e40af; }
        @media (max-width: 992px) {
            .main-content { margin-left: 0; padding: 15px; }
        }
    </style>
</head>
<body>

<?php include '../includes/header.php'; ?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>
            <i class="bi bi-speedometer2 me-2"></i>Energy Monitor (Dynamic)
            <small class="text-muted">(<?= date('d M Y') ?>)</small>
        </h2>
    </div>

    <div class="alert alert-info">
        <i class="bi bi-info-circle me-2"></i>
        <strong>Instructions:</strong> 
        You can only fill energy data for time blocks where you have contracts. 
        Past blocks are locked. Current and future blocks with contracts are editable.
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-table me-2"></i>Energy Generation - Time Blocks</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                <table class="table mb-0">
                    <thead class="sticky-top">
                        <tr>
                            <th>Time Block</th>
                            <th>Contracts</th>
                            <th>Generated (kWh)</th>
                            <th>Self Consumed (kWh)</th>
                            <th>Energy to be Traded</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Generate time blocks from 9 AM to 5 PM (solar hours)
                        for ($h = 9; $h < 17; $h++) {
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
                                
                                // Determine if block is past, current, or future
                                $block_hour = (int)substr($start, 0, 2);
                                $block_minute = (int)substr($start, 3, 2);
                                
                                $is_past = ($block_hour < $current_hour) || 
                                          ($block_hour == $current_hour && $block_minute < $current_minute);
                                $is_current = ($block_hour == $current_hour && $block_minute == $current_minute);
                                
                                // Check if there's a contract for this block
                                $has_contract = isset($contracts_data[$block]);
                                $contract_units = $has_contract ? $contracts_data[$block]['total_units'] : 0;
                                $contract_count = $has_contract ? $contracts_data[$block]['contract_count'] : 0;
                                
                                // Check if energy data exists
                                $has_data = isset($db_data[$block]);
                                $generated = $has_data ? $db_data[$block]['generated_kwh'] : 0;
                                $self_consumed = $has_data ? $db_data[$block]['self_consumed_kwh'] : 0;
                                $available = $has_data ? $db_data[$block]['sold_kwh'] : 0;
                                
                                // Determine row class and status
                                $row_class = '';
                                $status = '';
                                $status_class = '';
                                $is_editable = false;
                                
                                if ($is_past) {
                                    $row_class = 'row-past';
                                    $status = 'Locked';
                                    $status_class = 'status-locked';
                                } elseif (!$has_contract) {
                                    $row_class = 'row-future';
                                    $status = 'No Contract';
                                    $status_class = 'status-locked';
                                } elseif ($has_data) {
                                    $row_class = 'row-future';
                                    $status = 'Filled';
                                    $status_class = 'status-filled';
                                    $is_editable = true; // Can edit filled data
                                } else {
                                    $row_class = $is_current ? 'row-current' : 'row-future';
                                    $status = 'Pending';
                                    $status_class = 'status-pending';
                                    $is_editable = true;
                                }
                                
                                if ($has_contract) {
                                    $row_class .= ' row-has-contract';
                                }
                                ?>
                                <tr class="<?= $row_class ?>" data-block="<?= $block ?>">
                                    <td><strong><?= $block ?></strong></td>
                                    <td>
                                        <?php if ($has_contract): ?>
                                            <span class="badge bg-success"><?= $contract_count ?> Contract(s)</span>
                                            <br><small><?= $contract_units ?> kWh</small>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <input type="number" 
                                               class="input-energy" 
                                               name="generated" 
                                               value="<?= $generated ?>" 
                                               step="0.01" 
                                               min="0"
                                               <?= !$is_editable ? 'disabled' : '' ?>>
                                    </td>
                                    <td>
                                        <input type="number" 
                                               class="input-energy" 
                                               name="self_consumed" 
                                               value="<?= $self_consumed ?>" 
                                               step="0.01" 
                                               min="0"
                                               <?= !$is_editable ? 'disabled' : '' ?>>
                                    </td>
                                    <td>
                                        <strong class="text-success energy-available"><?= number_format($available, 2) ?></strong> kWh
                                    </td>
                                    <td>
                                        <span class="status-badge <?= $status_class ?>"><?= $status ?></span>
                                    </td>
                                    <td>
                                        <?php if ($is_editable): ?>
                                            <button class="btn btn-primary btn-sm btn-save" 
                                                    onclick="saveEnergy('<?= $block ?>')">
                                                <i class="bi bi-save"></i> Save
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php
                            }
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card mt-3 p-3">
        <h6 class="mb-2">Legend:</h6>
        <div class="d-flex gap-4 flex-wrap">
            <div><span class="badge row-past">Gray</span> Past blocks (Locked)</div>
            <div><span class="badge row-current">Yellow</span> Current block</div>
            <div><span class="badge row-future">White</span> Future blocks</div>
            <div><span class="badge" style="background: #10b981; color: white;">Green Border</span> Has Contract</div>
        </div>
    </div>

</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
function saveEnergy(timeBlock) {
    const row = $(`tr[data-block="${timeBlock}"]`);
    const generated = parseFloat(row.find('input[name="generated"]').val()) || 0;
    const selfConsumed = parseFloat(row.find('input[name="self_consumed"]').val()) || 0;
    
    if (generated < 0 || selfConsumed < 0) {
        alert('❌ Values cannot be negative');
        return;
    }
    
    if (selfConsumed > generated) {
        alert('❌ Self consumed cannot be more than generated');
        return;
    }
    
    const btn = row.find('.btn-save');
    btn.prop('disabled', true).html('<i class="spinner-border spinner-border-sm"></i>');
    
    $.post('../../api/save_energy_generation.php', {
        time_block: timeBlock,
        generated_kwh: generated,
        self_consumed_kwh: selfConsumed,
        date: '<?= $current_date ?>'
    }, function(response) {
        if (response.success) {
            const available = generated - selfConsumed;
            row.find('.energy-available').text(available.toFixed(2));
            row.find('.status-badge').removeClass('status-pending').addClass('status-filled').text('Filled');
            
            let message = '✅ Energy data saved successfully!\n\n';
            message += `Available to trade: ${available.toFixed(2)} kWh\n`;
            if (response.contracts_updated > 0) {
                message += `${response.contracts_updated} contract(s) updated`;
            }
            
            alert(message);
        } else {
            alert('❌ ' + response.message);
        }
    }).fail(function() {
        alert('❌ Server error occurred');
    }).always(function() {
        btn.prop('disabled', false).html('<i class="bi bi-save"></i> Save');
    });
}

// Auto-calculate available energy on input change
$('input[name="generated"], input[name="self_consumed"]').on('input', function() {
    const row = $(this).closest('tr');
    const generated = parseFloat(row.find('input[name="generated"]').val()) || 0;
    const selfConsumed = parseFloat(row.find('input[name="self_consumed"]').val()) || 0;
    const available = Math.max(0, generated - selfConsumed);
    row.find('.energy-available').text(available.toFixed(2));
});
</script>

</body>
</html>
