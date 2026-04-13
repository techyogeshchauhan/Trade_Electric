<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'buyer') {
    header("Location: ../login.php");
    exit();
}

include '../includes/config.php';
$user_id = $_SESSION['user_id'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Demands - EnergyTrade</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../../assets/css/style.css" rel="stylesheet">
    
    <style>
        .main-content {
            margin-left: 260px;
            padding: 20px;
            margin-top: 8px;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.08);
        }
        .card-header {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            padding: 15px 20px;
            border-radius: 10px 10px 0 0 !important;
        }
        .table {
            font-size: 13px;
            margin: 0;
        }
        .table th {
            background: #1e2937;
            color: white;
            text-align: center;
            padding: 12px 8px;
            font-size: 12px;
            font-weight: 600;
            border: 1px solid #374151;
        }
        .table td {
            text-align: center;
            vertical-align: middle;
            padding: 10px 8px;
            font-size: 13px;
            border: 1px solid #e5e7eb;
        }
        .table tbody tr:hover {
            background-color: #f3f4f6;
        }
        @media (max-width: 992px) {
            .main-content { margin-left: 0; }
        }
    </style>
</head>
<body>

<?php include '../includes/header.php'; ?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">
            <i class="bi bi-list-check"></i> My Demands
        </h2>
        <a href="marketplace.php" class="btn btn-primary">
            <i class="bi bi-shop"></i> Back to Marketplace
        </a>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="bi bi-cart3"></i> All My Demand Listings
            </h5>
        </div>
        <div class="card-body p-0">
            <div id="demandsTable" class="table-responsive">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Loading demands...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
function loadDemands() {
    $.get("../../api/get_demands.php", function(html) {
        $("#demandsTable").html(html);
    });
}
loadDemands();
</script>
</body>
</html>