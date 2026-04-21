<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

include '../includes/config.php';

// Fetch current settings
$settings = $conn->query("SELECT * FROM settings LIMIT 1")->fetch_assoc() ?? [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        body {
            background: #f0f2f5;
            font-family: 'Segoe UI', sans-serif;
            color: #1f2937;
            font-size: 15px;
        }
        
        .main-content {
            margin-top: 80px;
            padding: 20px;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .settings-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            margin-bottom: 20px;
        }
        
        .settings-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 16px 24px;
            border-radius: 12px 12px 0 0;
            font-weight: 700;
            font-size: 18px;
        }
        
        .settings-body {
            padding: 24px;
        }
        
        .section-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1e40af;
            border-bottom: 2px solid #e0f2fe;
            padding-bottom: 8px;
            margin-bottom: 16px;
        }
        
        .form-control, .input-group-text {
            border: 1.5px solid #d1d5db;
            border-radius: 8px;
            padding: 9px 14px;
            font-size: 15px;
        }
        
        .form-control:focus {
            border-color: #818cf8;
            box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
        }
        
        .btn-primary {
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            padding: 12px 24px;
        }
        
        .top-bar {
            background: #fff;
            border-radius: 12px;
            padding: 20px 28px;
            margin-bottom: 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .top-bar h4 {
            font-weight: 700;
            margin: 0;
            font-size: 20px;
        }

        .info-box {
            background: #eff6ff;
            border-left: 4px solid #3b82f6;
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
            color: #1e40af;
        }
    </style>
</head>
<body>

<?php include '../includes/header.php'; ?>

<div class="main-content">

    <div class="top-bar">
        <h4><i class="bi bi-sliders me-2 text-primary"></i>System Settings</h4>
        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Back to Dashboard
        </a>
    </div>

    <div class="info-box">
        <i class="bi bi-info-circle me-2"></i>
        <strong>Note:</strong> These settings control system-wide defaults and limits. Changes apply immediately.
    </div>

    <form id="systemSettingsForm">

        <!-- Wallet Settings -->
        <div class="settings-card">
            <div class="settings-header">
                <i class="bi bi-wallet2 me-2"></i>Wallet Settings
            </div>
            <div class="settings-body">
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Default Wallet Balance</label>
                        <div class="input-group">
                            <span class="input-group-text">₹</span>
                            <input type="number" step="0.01" name="default_wallet_balance" 
                                   class="form-control" value="<?= $settings['default_wallet_balance'] ?? 5000 ?>" required>
                        </div>
                        <small class="text-muted">Initial balance when creating new wallet</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Analytics Settings -->
        <div class="settings-card">
            <div class="settings-header">
                <i class="bi bi-graph-up me-2"></i>Analytics Baseline Settings
            </div>
            <div class="settings-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Average Consumption (Buyer)</label>
                        <div class="input-group">
                            <input type="number" step="0.01" name="gescom_avg_consumption" 
                                   class="form-control" value="<?= $settings['gescom_avg_consumption'] ?? 5 ?>" required>
                            <span class="input-group-text">kWh</span>
                        </div>
                        <small class="text-muted">Baseline for buyer consumption graphs</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Average Supply (Seller)</label>
                        <div class="input-group">
                            <input type="number" step="0.01" name="gescom_avg_supply" 
                                   class="form-control" value="<?= $settings['gescom_avg_supply'] ?? 5 ?>" required>
                            <span class="input-group-text">kWh</span>
                        </div>
                        <small class="text-muted">Baseline for seller supply graphs</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Listing & Slot Settings -->
        <div class="settings-card">
            <div class="settings-header">
                <i class="bi bi-list-ul me-2"></i>Listing & Slot Limits
            </div>
            <div class="settings-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Max Units Per Slot</label>
                        <div class="input-group">
                            <input type="number" name="max_units_per_slot" 
                                   class="form-control" value="<?= $settings['max_units_per_slot'] ?? 100 ?>" required>
                            <span class="input-group-text">kWh</span>
                        </div>
                        <small class="text-muted">Maximum units per time slot</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Default Listing Limit</label>
                        <input type="number" name="default_listing_limit" 
                               class="form-control" value="<?= $settings['default_listing_limit'] ?? 10 ?>" required>
                        <small class="text-muted">Default listings to display</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Max Listing Limit</label>
                        <input type="number" name="max_listing_limit" 
                               class="form-control" value="<?= $settings['max_listing_limit'] ?? 1000 ?>" required>
                        <small class="text-muted">Maximum listings allowed</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Logo Settings -->
        <div class="settings-card">
            <div class="settings-header">
                <i class="bi bi-image me-2"></i>Logo Settings
            </div>
            <div class="settings-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Left Logo Path</label>
                        <input type="text" name="logo_left" 
                               class="form-control" value="<?= $settings['logo_left'] ?? '../assets/gescomLogo.png' ?>" required>
                        <small class="text-muted">Path to left header logo (e.g., ../assets/logo.png)</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Right Logo Path</label>
                        <input type="text" name="logo_right" 
                               class="form-control" value="<?= $settings['logo_right'] ?? '../assets/apcLogo.jpg' ?>" required>
                        <small class="text-muted">Path to right header logo (e.g., ../assets/logo.jpg)</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Trading Hours Settings -->
        <div class="settings-card">
            <div class="settings-header">
                <i class="bi bi-clock me-2"></i>Trading Hours
            </div>
            <div class="settings-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Trading Start Time</label>
                        <input type="time" name="trading_start_time"
                               class="form-control"
                               value="<?= substr($settings['trading_start_time'] ?? '10:00:00', 0, 5) ?>" required>
                        <small class="text-muted">Time slot dropdown start (e.g., 10:00)</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Trading End Time</label>
                        <input type="time" name="trading_end_time"
                               class="form-control"
                               value="<?= substr($settings['trading_end_time'] ?? '17:00:00', 0, 5) ?>" required>
                        <small class="text-muted">Time slot dropdown end (e.g., 17:00)</small>
                    </div>
                </div>
                <div class="alert alert-info mt-3 mb-0 py-2 px-3" style="font-size:13px;">
                    <i class="bi bi-info-circle me-1"></i>
                    <strong>Note:</strong> Controls time-slot dropdowns for all Sellers &amp; Buyers portal-wide.
                </div>
            </div>
        </div>

        <div class="text-center">
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="bi bi-save me-2"></i>Save All Settings
            </button>
        </div>

    </form>

    <div id="msg" class="mt-3"></div>

</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
$("#systemSettingsForm").submit(function(e){
    e.preventDefault();
    var btn = $(this).find('button[type=submit]');
    btn.prop('disabled', true).html('<i class="bi bi-hourglass-split me-2"></i>Saving...');

    $.post("../../api/save_system_settings.php", $(this).serialize(), function(response){
        btn.prop('disabled', false).html('<i class="bi bi-save me-2"></i>Save All Settings');
        try {
            let res = JSON.parse(response);
            if(res.status === "success"){
                $("#msg").html('<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>'+res.message+'</div>');
                setTimeout(function(){ location.reload(); }, 1500);
            } else {
                $("#msg").html('<div class="alert alert-danger"><i class="bi bi-x-circle me-2"></i><strong>Error:</strong> '+res.message+'</div>');
            }
        } catch(ex) {
            // Show raw server output to help debug
            $("#msg").html('<div class="alert alert-danger"><strong>Server response (raw):</strong><pre style="font-size:12px;margin-top:8px;">'+response+'</pre></div>');
        }
    }).fail(function(xhr){
        btn.prop('disabled', false).html('<i class="bi bi-save me-2"></i>Save All Settings');
        $("#msg").html('<div class="alert alert-danger"><strong>HTTP Error '+xhr.status+':</strong> '+xhr.responseText+'</div>');
    });
});
</script>

</body>
</html>
