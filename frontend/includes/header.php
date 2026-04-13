<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$role = $_SESSION['role'] ?? '';
$user_name = $_SESSION['name'] ?? 'User';
$current_page = basename($_SERVER['PHP_SELF']);

// Get base URL dynamically
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$base_url = $protocol . "://" . $host . "/apc/New_project/frontend";
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>P2P Energy Trading</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

<style>

/* ================= BODY ================= */
body {
    background: #f4f8fb;
    font-family: 'Segoe UI', sans-serif;
}

/* ================= HEADER ================= */
.top-header {
    background: linear-gradient(135deg, #1e3a8a, #3b82f6, #06b6d4);
    color: #fff;
    position: fixed;
    top: 0;
    width: 100%;
    height: 70px;
    z-index: 1000;
    display: flex;
    align-items: center;
    padding: 0 20px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

/* FLEX CONTAINER */
.header-container {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
}

/* CENTER TITLE */
.center-section {
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
    font-size: 30px;
    font-weight: 600;
    color: #ffffff;
}

/* LOGOS */
.logo-left {
    height: 60px;
}

.logo-right {
    height: 40px;
    display: block;
}

/* RIGHT SECTION */
.right-section {
    display: flex;
    align-items: center;
    gap: 10px;
}

/* USER BUTTON */
.user-btn {
    background: #ffffff !important;
    border-radius: 25px;
    padding: 6px 14px;
    border: none;
    font-weight: 500;
}

/* ================= SIDEBAR ================= */
.sidebar {
    position: fixed;
    top: 70px;
    left: 0;
    height: calc(100vh - 70px);
    width: 260px;
    background: linear-gradient(180deg, #60a5fa 0%, #3b82f6 50%, #2563eb 100%);
    border-right: 2px solid #1e40af;
    overflow-y: auto;
    box-shadow: 4px 0 15px rgba(0, 0, 0, 0.1);
}

/* NAV LINKS */
.sidebar .nav-link {
    color: #ffffff !important;
    padding: 12px 18px;
    border-radius: 10px;
    margin: 6px 12px;
    display: flex;
    align-items: center;
    font-weight: 500;
    transition: all 0.25s ease;
}

/* ICON */
.sidebar .nav-link i {
    font-size: 18px;
    color: #ffffff;
}

/* HOVER */
.sidebar .nav-link:hover {
    background: rgba(255, 255, 255, 0.2);
    color: #ffffff !important;
    transform: translateX(5px);
}

.sidebar .nav-link:hover i {
    color: #ffffff;
}

/* ACTIVE */
.sidebar .nav-link.active {
    background: rgba(255, 255, 255, 0.3);
    color: #ffffff !important;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}

.sidebar .nav-link.active i {
    color: #ffffff;
}

/* ================= MAIN ================= */
.main-content {
    margin-top: 80px;
    margin-left: 260px;
    padding: 20px;
}

/* ================= MOBILE ================= */
@media (max-width: 992px) {
    .sidebar {
        transform: translateX(-100%);
    }
    .sidebar.show {
        transform: translateX(0);
    }
    .main-content {
        margin-left: 0;
    }
}

</style>
</head>

<body>

<!-- HEADER -->
<div class="top-header">
    <div class="header-container">

        <!-- LEFT LOGO -->
        <img src="<?= $base_url ?>/../assets/gescomLogo.png" class="logo-left">

        <!-- CENTER TITLE -->
        <div class="center-section">
            P2P Trading Marketplace
        </div>

        <!-- RIGHT -->
        <div class="right-section">

            <?php if(isset($_SESSION['user_id'])): ?>
                <div class="dropdown">
                    <button class="btn user-btn dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i>
                        <?= htmlspecialchars($user_name) ?> (<?= ucfirst($role) ?>)
                    </button>

                    <ul class="dropdown-menu dropdown-menu-end shadow">
                        <li><a class="dropdown-item" href="<?= $base_url ?>/profile.php">My Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="<?= $base_url ?>/../api/logout.php">
                                Logout
                            </a>
                        </li>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- APC LOGO -->
            <img src="<?= $base_url ?>/../assets/apcLogo.jpg" class="logo-right">

        </div>
    </div>
</div>
<div class="sidebar">
    <div class="p-2">

        <!-- COMMON (ALL USERS) -->
        <?php if($role == 'seller'): ?>
            <a href="<?= $base_url ?>/seller/marketplace.php" class="nav-link <?= $current_page=='marketplace.php'?'active':'' ?>">
                <i class="bi bi-shop me-2"></i> Marketplace
            </a>
        <?php elseif($role == 'buyer'): ?>
            <a href="<?= $base_url ?>/buyer/marketplace.php" class="nav-link <?= $current_page=='marketplace.php'?'active':'' ?>">
                <i class="bi bi-shop me-2"></i> Marketplace
            </a>
        <?php else: ?>
            <a href="<?= $base_url ?>/marketplace.php" class="nav-link <?= $current_page=='marketplace.php'?'active':'' ?>">
                <i class="bi bi-shop me-2"></i> Marketplace
            </a>
        <?php endif; ?>
        
        <?php if($role == 'seller'): ?>
            <a href="<?= $base_url ?>/seller/dashboard.php" class="nav-link <?= $current_page=='dashboard.php'?'active':'' ?>">
                <i class="bi bi-speedometer2 me-2"></i> Dashboard
            </a>
        <?php elseif($role == 'buyer'): ?>
            <a href="<?= $base_url ?>/buyer/dashboard.php" class="nav-link <?= $current_page=='dashboard.php'?'active':'' ?>">
                <i class="bi bi-speedometer2 me-2"></i> Dashboard
            </a>
        <?php elseif($role == 'admin'): ?>
            <a href="<?= $base_url ?>/admin/dashboard.php" class="nav-link <?= $current_page=='dashboard.php'?'active':'' ?>">
                <i class="bi bi-speedometer2 me-2"></i> Dashboard
            </a>
        <?php endif; ?>

        <!-- SELLER ONLY -->
        <?php if($role == 'seller'): ?>
            <a href="<?= $base_url ?>/seller/my_listings.php" class="nav-link <?= $current_page=='my_listings.php'?'active':'' ?>">
                <i class="bi bi-list-ul me-2"></i> My Listings
            </a>
            
            <a href="<?= $base_url ?>/seller/meter_report.php" class="nav-link <?= $current_page=='meter_report.php'?'active':'' ?>">
                <i class="bi bi-speedometer me-2"></i> Energy Monitor
            </a>
        <?php endif; ?>

        <!-- BUYER ONLY -->
        <?php if($role == 'buyer'): ?>
            <a href="<?= $base_url ?>/buyer/my_contracts.php" class="nav-link <?= $current_page=='my_contracts.php'?'active':'' ?>">
                <i class="bi bi-file-earmark-text me-2"></i> My Contracts
            </a>
        <?php endif; ?>

        <!-- ADMIN ONLY -->
        <?php if($role == 'admin'): ?>
            <a href="<?= $base_url ?>/admin/users.php" class="nav-link">
                <i class="bi bi-people me-2"></i> Manage Users
            </a>
        <?php endif; ?>

        <!-- COMMON ADVANCED -->
        <?php if($role == 'buyer' || $role == 'seller'): ?>
            <a href="<?= $base_url ?>/dashboard/trades.php" class="nav-link <?= $current_page=='trades.php'?'active':'' ?>">
                <i class="bi bi-arrow-left-right me-2"></i> Trades
            </a>

            <a href="<?= $base_url ?>/dashboard/settlement.php" class="nav-link <?= $current_page=='settlement.php'?'active':'' ?>">
                <i class="bi bi-cash-stack me-2"></i> Settlement
            </a>

            <a href="<?= $base_url ?>/dashboard/wallet.php" class="nav-link <?= $current_page=='wallet.php'?'active':'' ?>">
                <i class="bi bi-wallet2 me-2"></i> Wallet
            </a>
        <?php endif; ?>

        <!-- ALL USERS -->
        <a href="<?= $base_url ?>/dashboard/analytics.php" class="nav-link <?= $current_page=='analytics.php'?'active':'' ?>">
            <i class="bi bi-graph-up me-2"></i> Analytics
        </a>

        <!-- LOGOUT (ALWAYS VISIBLE) -->
        <hr class="my-3 mx-3" style="border-color: rgba(255, 255, 255, 0.3);">
        <a href="<?= $base_url ?>/../api/logout.php" class="nav-link" style="color: #ffebee !important;" onclick="return confirm('Are you sure you want to logout?')">
            <i class="bi bi-box-arrow-right me-2"></i> Logout
        </a>

    </div>
</div>


<!-- Bootstrap JS (Required for Dropdown) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
