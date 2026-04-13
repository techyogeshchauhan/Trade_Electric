<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include 'includes/config.php';

$user_id = $_SESSION['user_id'];

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $phone = $conn->real_escape_string($_POST['phone'] ?? '');
    
    $update = $conn->query("
        UPDATE users 
        SET name = '$name', email = '$email', phone = '$phone'
        WHERE id = $user_id
    ");
    
    if ($update) {
        $_SESSION['name'] = $name;
        $success_msg = "Profile updated successfully!";
    } else {
        $error_msg = "Failed to update profile.";
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Verify current password
    $user_check = $conn->query("SELECT password FROM users WHERE id = $user_id")->fetch_assoc();
    
    if (password_verify($current_password, $user_check['password'])) {
        if ($new_password === $confirm_password) {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $conn->query("UPDATE users SET password = '$hashed' WHERE id = $user_id");
            $success_msg = "Password changed successfully!";
        } else {
            $error_msg = "New passwords do not match.";
        }
    } else {
        $error_msg = "Current password is incorrect.";
    }
}

// Fetch user data
$user = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();

// Fetch wallet data
$wallet = $conn->query("SELECT * FROM wallet WHERE user_id = $user_id")->fetch_assoc();

// Fetch token balance
$token_data = $conn->query("
    SELECT 
        SUM(CASE 
            WHEN token_type IN ('mint','transfer_in') THEN token_units
            WHEN token_type IN ('transfer_out','burn') THEN -token_units
            ELSE 0
        END) as token_balance
    FROM token_ledger
    WHERE user_id = $user_id
")->fetch_assoc();

$token_balance = $token_data['token_balance'] ?? 0;

// Fetch stats
$stats = [];
if ($user['role'] === 'buyer') {
    $stats = $conn->query("
        SELECT 
            COUNT(*) as total_contracts,
            SUM(CASE WHEN status='confirmed' THEN 1 ELSE 0 END) as confirmed,
            SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) as pending
        FROM contracts
        WHERE buyer_id = $user_id
    ")->fetch_assoc();
} elseif ($user['role'] === 'seller') {
    $stats = $conn->query("
        SELECT 
            COUNT(*) as total_listings,
            SUM(remaining_units) as total_units
        FROM energy_listings
        WHERE seller_id = $user_id
    ")->fetch_assoc();
}
?>

<!DOCTYPE html>
<html>
<head>
<title>My Profile</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<style>
body { background: #f4f6f9; }

.main-content {
    margin-left: 250px;
    padding: 20px;
}

.profile-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    border-radius: 15px;
    margin-bottom: 30px;
}

.profile-avatar {
    width: 100px;
    height: 100px;
    background: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 48px;
    color: #667eea;
    margin-bottom: 15px;
}

.stat-card {
    background: white;
    border-radius: 10px;
    padding: 20px;
    text-align: center;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.stat-card h3 {
    font-size: 32px;
    font-weight: bold;
    margin: 10px 0;
}

.stat-card p {
    color: #6c757d;
    margin: 0;
}

.info-card {
    background: white;
    border-radius: 10px;
    padding: 25px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.info-row {
    display: flex;
    justify-content: space-between;
    padding: 12px 0;
    border-bottom: 1px solid #e9ecef;
}

.info-row:last-child {
    border-bottom: none;
}

.info-label {
    font-weight: 600;
    color: #495057;
}

.info-value {
    color: #6c757d;
}

@media (max-width: 992px) {
    .main-content {
        margin-left: 0;
    }
}
</style>

<?php include 'includes/header.php'; ?>
</head>

<body>

<div class="main-content">
<div class="container mt-4">

    <?php if (isset($success_msg)): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i><?= $success_msg ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($error_msg)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle me-2"></i><?= $error_msg ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Profile Header -->
    <div class="profile-header">
        <div class="row align-items-center">
            <div class="col-md-2 text-center">
                <div class="profile-avatar">
                    <i class="fas fa-user"></i>
                </div>
            </div>
            <div class="col-md-10">
                <h2 class="mb-2"><?= htmlspecialchars($user['name']) ?></h2>
                <p class="mb-1"><i class="fas fa-envelope me-2"></i><?= htmlspecialchars($user['email']) ?></p>
                <p class="mb-0">
                    <span class="badge bg-warning text-dark fs-6">
                        <i class="fas fa-user-tag me-1"></i><?= ucfirst($user['role']) ?>
                    </span>
                </p>
            </div>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="stat-card">
                <i class="fas fa-wallet text-success fs-2"></i>
                <h3 class="text-success">₹<?= number_format($wallet['balance'] ?? 0, 2) ?></h3>
                <p>Wallet Balance</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <i class="fas fa-coins text-warning fs-2"></i>
                <h3 class="text-warning"><?= number_format($token_balance, 2) ?></h3>
                <p>Energy Tokens (kWh)</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <?php if ($user['role'] === 'buyer'): ?>
                    <i class="fas fa-file-contract text-primary fs-2"></i>
                    <h3 class="text-primary"><?= $stats['total_contracts'] ?? 0 ?></h3>
                    <p>Total Contracts</p>
                <?php elseif ($user['role'] === 'seller'): ?>
                    <i class="fas fa-bolt text-danger fs-2"></i>
                    <h3 class="text-danger"><?= number_format($stats['total_units'] ?? 0, 2) ?></h3>
                    <p>Available Units (kWh)</p>
                <?php else: ?>
                    <i class="fas fa-chart-line text-info fs-2"></i>
                    <h3 class="text-info">0</h3>
                    <p>Activity</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Account Information -->
        <div class="col-md-6">
            <div class="info-card">
                <h5 class="mb-3"><i class="fas fa-user-circle me-2"></i>Account Information</h5>
                <div class="info-row">
                    <span class="info-label">User ID</span>
                    <span class="info-value">#<?= $user['id'] ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Name</span>
                    <span class="info-value"><?= htmlspecialchars($user['name']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email</span>
                    <span class="info-value"><?= htmlspecialchars($user['email']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Phone</span>
                    <span class="info-value"><?= htmlspecialchars($user['phone'] ?? 'Not set') ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Role</span>
                    <span class="info-value">
                        <span class="badge bg-primary"><?= ucfirst($user['role']) ?></span>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Member Since</span>
                    <span class="info-value"><?= date('d M Y', strtotime($user['created_at'] ?? 'now')) ?></span>
                </div>
                <button class="btn btn-primary w-100 mt-3" data-bs-toggle="modal" data-bs-target="#editModal">
                    <i class="fas fa-edit me-2"></i>Edit Profile
                </button>
            </div>
        </div>

        <!-- Security -->
        <div class="col-md-6">
            <div class="info-card">
                <h5 class="mb-3"><i class="fas fa-lock me-2"></i>Security</h5>
                <p class="text-muted">Keep your account secure by using a strong password.</p>
                <button class="btn btn-warning w-100" data-bs-toggle="modal" data-bs-target="#passwordModal">
                    <i class="fas fa-key me-2"></i>Change Password
                </button>
            </div>

            <?php if ($user['role'] === 'buyer'): ?>
            <div class="info-card mt-3">
                <h5 class="mb-3"><i class="fas fa-chart-pie me-2"></i>Contract Stats</h5>
                <div class="info-row">
                    <span class="info-label">Total Contracts</span>
                    <span class="info-value"><?= $stats['total_contracts'] ?? 0 ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Confirmed</span>
                    <span class="info-value text-success"><?= $stats['confirmed'] ?? 0 ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Pending</span>
                    <span class="info-value text-warning"><?= $stats['pending'] ?? 0 ?></span>
                </div>
            </div>
            <?php elseif ($user['role'] === 'seller'): ?>
            <div class="info-card mt-3">
                <h5 class="mb-3"><i class="fas fa-chart-pie me-2"></i>Listing Stats</h5>
                <div class="info-row">
                    <span class="info-label">Total Listings</span>
                    <span class="info-value"><?= $stats['total_listings'] ?? 0 ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Available Units</span>
                    <span class="info-value text-success"><?= number_format($stats['total_units'] ?? 0, 2) ?> kWh</span>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div>
</div>

<!-- Edit Profile Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_profile" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Change Password Modal -->
<div class="modal fade" id="passwordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-key me-2"></i>Change Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="change_password" class="btn btn-warning">Change Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>