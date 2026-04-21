<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$role = strtolower($_SESSION['role'] ?? '');
$user_name = $_SESSION['name'] ?? 'User';
$current_page = basename($_SERVER['PHP_SELF']);

// Get base URL - points to the /frontend directory always
// Works for: /p2p/frontend/, /frontend/, any server path
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$script_name = $_SERVER['SCRIPT_NAME'];

// Find '/frontend' in the path and use everything up to and including it
$frontend_pos = strrpos($script_name, '/frontend');
if ($frontend_pos !== false) {
    $frontend_path = substr($script_name, 0, $frontend_pos + 9); // +9 = length of '/frontend'
} else {
    // Fallback: go up directories until we find frontend root
    $dir = dirname($script_name);
    $frontend_path = rtrim($dir, '/seller,/buyer,/admin,/dashboard') . '';
    // Simple fallback: assume script is inside frontend subtree
    $frontend_path = preg_replace('#/(seller|buyer|admin|dashboard)(/.*)?$#', '', $dir);
    if (empty($frontend_path)) $frontend_path = '/frontend';
}

$base_url = $protocol . '://' . $host . $frontend_path;
?>

<!-- ── Icon CDN links (loaded here to guarantee icons on ALL pages) ── -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

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
    font-weight: 700;
    font-size: 18px;
    transition: all 0.25s ease;
}

/* ICON */
.sidebar .nav-link i {
    font-size: 22px;
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

<!-- Bootstrap JS (Required for Dropdown) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- HEADER -->
<div class="top-header">
    <div class="header-container">

        <!-- HAMBURGER MENU (Mobile) -->
        <button class="hamburger-btn" id="hamburgerBtn">
            <i class="bi bi-list"></i>
        </button>

        <!-- LEFT LOGO -->
        <?php 
        $logo_left_path = defined('LOGO_LEFT') ? LOGO_LEFT : '../assets/gescomLogo.png';
        // Adjust path based on current directory depth
        if (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false || 
            strpos($_SERVER['REQUEST_URI'], '/buyer/') !== false || 
            strpos($_SERVER['REQUEST_URI'], '/seller/') !== false ||
            strpos($_SERVER['REQUEST_URI'], '/dashboard/') !== false) {
            $logo_left_path = str_replace('../', '../../', $logo_left_path);
        }
        ?>
        <img src="<?= $logo_left_path ?>" class="logo-left" alt="Logo">

        <!-- CENTER TITLE -->
        <div class="center-section">
            P2P Energy Trading Marketplace
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

            <!-- RIGHT LOGO -->
            <?php 
            $logo_right_path = defined('LOGO_RIGHT') ? LOGO_RIGHT : '../assets/apcLogo.jpg';
            // Adjust path based on current directory depth
            if (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false || 
                strpos($_SERVER['REQUEST_URI'], '/buyer/') !== false || 
                strpos($_SERVER['REQUEST_URI'], '/seller/') !== false ||
                strpos($_SERVER['REQUEST_URI'], '/dashboard/') !== false) {
                $logo_right_path = str_replace('../', '../../', $logo_right_path);
            }
            ?>
            <img src="<?= $logo_right_path ?>" class="logo-right" alt="Logo">

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
            
            <a href="<?= $base_url ?>/seller/my_contracts.php" class="nav-link <?= $current_page=='my_contracts.php'?'active':'' ?>">
                <i class="bi bi-file-earmark-text me-2"></i> My Contracts
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
            <a href="<?= $base_url ?>/admin/users.php" class="nav-link <?= $current_page=='users.php'?'active':'' ?>">
                <i class="bi bi-people me-2"></i> Manage Users
            </a>
            <a href="<?= $base_url ?>/admin/energy.php" class="nav-link <?= $current_page=='energy.php'?'active':'' ?>">
                <i class="bi bi-lightning-charge me-2"></i> Energy Listings
            </a>
            <a href="<?= $base_url ?>/admin/trades.php" class="nav-link <?= $current_page=='trades.php'?'active':'' ?>">
                <i class="bi bi-arrow-left-right me-2"></i> All Trades
            </a>
            <a href="<?= $base_url ?>/admin/charges_settings.php" class="nav-link <?= $current_page=='charges_settings.php'?'active':'' ?>">
                <i class="bi bi-gear me-2"></i> Charges Settings
            </a>
            <a href="<?= $base_url ?>/admin/system_settings.php" class="nav-link <?= $current_page=='system_settings.php'?'active':'' ?>">
                <i class="bi bi-sliders me-2"></i> System Settings
            </a>
            <a href="<?= $base_url ?>/admin/token_report.php" class="nav-link <?= $current_page=='token_report.php'?'active':'' ?>">
                <i class="fas fa-coins me-2"></i> Token Report
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
