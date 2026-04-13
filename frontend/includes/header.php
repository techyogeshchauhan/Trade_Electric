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
    gap: 10px;
}

/* CENTER TITLE */
.center-section {
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
    font-size: 30px;
    font-weight: 600;
    color: #ffffff;
    white-space: nowrap;
}

/* LOGOS */
.logo-left {
    height: 60px;
    flex-shrink: 0;
}

.logo-right {
    height: 40px;
    display: block;
    flex-shrink: 0;
}

/* RIGHT SECTION */
.right-section {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-shrink: 0;
}

/* USER BUTTON */
.user-btn {
    background: #ffffff !important;
    border-radius: 25px;
    padding: 6px 14px;
    border: none;
    font-weight: 500;
    font-size: 14px;
    white-space: nowrap;
    display: flex;
    align-items: center;
    gap: 5px;
}

.user-btn i {
    font-size: 18px;
}

/* HAMBURGER MENU */
.hamburger-btn {
    display: none;
    background: #ffffff;
    border: none;
    border-radius: 8px;
    padding: 8px 10px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.hamburger-btn i {
    font-size: 20px;
    color: #1e3a8a;
}

.hamburger-btn:hover {
    background: #f0f9ff;
}

/* SIDEBAR OVERLAY */
.sidebar-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 998;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.sidebar-overlay.show {
    display: block;
    opacity: 1;
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
    z-index: 999;
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
    .hamburger-btn {
        display: block;
    }
    
    .sidebar {
        transform: translateX(-100%);
        transition: transform 0.3s ease;
    }
    .sidebar.show {
        transform: translateX(0);
    }
    .main-content {
        margin-left: 0;
    }
    
    .center-section {
        font-size: 20px;
    }
    
    .logo-left {
        height: 50px;
    }
    
    .logo-right {
        height: 35px;
    }
}

@media (max-width: 768px) {
    .top-header {
        height: 65px;
        padding: 0 12px;
    }
    
    .center-section {
        font-size: 16px;
        position: static;
        transform: none;
        flex: 1;
        text-align: center;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .logo-left {
        height: 45px;
    }
    
    .logo-right {
        height: 30px;
    }
    
    .user-btn {
        padding: 5px 10px;
        font-size: 12px;
    }
    
    .user-btn i {
        font-size: 16px;
    }
    
    .user-btn .user-name {
        display: none;
    }
}

@media (max-width: 576px) {
    .top-header {
        height: 60px;
        padding: 0 10px;
    }
    
    .header-container {
        gap: 5px;
    }
    
    .center-section {
        font-size: 14px;
    }
    
    .logo-left {
        height: 40px;
    }
    
    .logo-right {
        height: 28px;
    }
    
    .user-btn {
        padding: 4px 8px;
        font-size: 11px;
        border-radius: 20px;
    }
    
    .user-btn i {
        font-size: 14px;
    }
    
    .dropdown-menu {
        font-size: 13px;
        min-width: 150px;
    }
}

@media (max-width: 400px) {
    .top-header {
        height: 55px;
        padding: 0 8px;
    }
    
    .center-section {
        font-size: 12px;
    }
    
    .logo-left {
        height: 35px;
    }
    
    .logo-right {
        height: 25px;
    }
    
    .user-btn {
        padding: 3px 6px;
        font-size: 10px;
    }
    
    .user-btn i {
        font-size: 12px;
    }
}

</style>
</head>

<body>

<!-- HEADER -->
<div class="top-header">
    <div class="header-container">

        <!-- HAMBURGER MENU (Mobile) -->
        <button class="hamburger-btn" id="hamburgerBtn">
            <i class="bi bi-list"></i>
        </button>

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
                        <span class="user-name"><?= htmlspecialchars($user_name) ?> (<?= ucfirst($role) ?>)</span>
                    </button>

                    <ul class="dropdown-menu dropdown-menu-end shadow">
                        <li>
                            <a class="dropdown-item" href="<?= $base_url ?>/profile.php">
                                <i class="bi bi-person me-2"></i>My Profile
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="<?= $base_url ?>/../api/logout.php">
                                <i class="bi bi-box-arrow-right me-2"></i>Logout
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

<!-- SIDEBAR OVERLAY -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- SIDEBAR -->
<div class="sidebar" id="sidebar">
    <div class="p-2">

        <!-- COMMON (ALL USERS) -->
        <a href="<?= $base_url ?>/marketplace.php" class="nav-link <?= $current_page=='marketplace.php'?'active':'' ?>">
            <i class="bi bi-shop me-2"></i> Marketplace
        </a>
        
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
                <i class="bi bi-arrow-left-right me-2"></i> Smart Trades
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

<script>
// Mobile sidebar toggle
document.addEventListener('DOMContentLoaded', function() {
    const hamburgerBtn = document.getElementById('hamburgerBtn');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    
    if (hamburgerBtn && sidebar && overlay) {
        // Open sidebar
        hamburgerBtn.addEventListener('click', function() {
            sidebar.classList.add('show');
            overlay.classList.add('show');
        });
        
        // Close sidebar when clicking overlay
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        });
        
        // Close sidebar when clicking any nav link
        const navLinks = sidebar.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
            });
        });
    }
});
</script>
