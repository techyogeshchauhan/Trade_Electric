<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../seller/login.php");
    exit();
}

include '../includes/config.php';

// Fetch current settings
$settings = $conn->query("SELECT * FROM settings LIMIT 1")->fetch_assoc() ?? [
    'utility_charge_buyer' => 0.02,
    'utility_charge_seller' => 0.02,
    'platform_charge' => 2.00
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Charges Settings - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">

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
            max-width: 1400px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .settings-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            max-width: 700px;
            margin: 0 auto;
        }
        
        .section-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1e40af;
            border-bottom: 2px solid #e0f2fe;
            padding-bottom: 8px;
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
        }
        
        .top-bar {
            background: #fff;
            border-radius: 12px;
            padding: 20px 28px;
            margin-bottom: 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
        }
        
        .top-bar h4 {
            font-weight: 700;
            margin: 0;
            font-size: 20px;
        }
    </style>
</head>
<body>

<?php include '../includes/header.php'; ?>

<div class="main-content">

<div class="top-bar">
        <h4><i class="bi bi-gear-fill me-2 text-primary"></i>Charges Settings</h4>
    </div>

    <div class="settings-card card p-4">
        <form id="chargesForm">

            <!-- Buyer Section -->
            <h5 class="section-title mb-3">👤 Buyer Charges</h5>
            <div class="mb-4">
                <label class="form-label fw-bold">Utility Charge (Buyer)</label>
                <div class="input-group">
                    <input type="number" step="0.01" name="utility_charge_buyer" 
                           class="form-control" value="<?= $settings['utility_charge_buyer'] ?? 0.02 ?>" required>
                    <span class="input-group-text">₹ per kWh</span>
                </div>
                <small class="text-muted">Charged from buyer side</small>
            </div>

            <!-- Seller Section -->
            <h5 class="section-title mb-3 mt-4">☀️ Prosumer / Seller Charges</h5>
            <div class="mb-4">
                <label class="form-label fw-bold">Utility Charge (Seller)</label>
                <div class="input-group">
                    <input type="number" step="0.01" name="utility_charge_seller" 
                           class="form-control" value="<?= $settings['utility_charge_seller'] ?? 0.02 ?>" required>
                    <span class="input-group-text">₹ per kWh</span>
                </div>
                <small class="text-muted">Charged from prosumer side</small>
            </div>

            <!-- Platform Charge (Common) -->
            <h5 class="section-title mb-3 mt-4">🏢 Platform Charges</h5>
            <div class="mb-4">
                <label class="form-label fw-bold">Platform Charge</label>
                <div class="input-group">
                    <input type="number" step="0.01" name="platform_charge" 
                           class="form-control" value="<?= $settings['platform_charge'] ?? 2.00 ?>" required>
                    <span class="input-group-text">₹ per kWh</span>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-3">Save All Charges</button>
        </form>

        <div id="msg" class="mt-3"></div>
    </div>

</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
$("#chargesForm").submit(function(e){
    e.preventDefault();

    $.post("../api/save_charges.php", $(this).serialize(), function(response){
        try {
            let res = JSON.parse(response);
            if(res.status === "success"){
                $("#msg").html('<div class="alert alert-success">'+res.message+'</div>');
            } else {
                $("#msg").html('<div class="alert alert-danger">'+res.message+'</div>');
            }
        } catch(e) {
            $("#msg").html('<div class="alert alert-danger">Server error occurred.</div>');
        }
    });
});
</script>

</body>
</html>