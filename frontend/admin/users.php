<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

include '../includes/config.php';

 $filter = isset($_GET['filter']) ? $_GET['filter'] : '';
 $search = isset($_GET['search']) ? trim($_GET['search']) : '';

 $filterSQL = '';
 $filterLabel = 'All Users';

if ($filter === 'seller') {
    $filterSQL = " WHERE role='seller' ";
    $filterLabel = 'Sellers';
} elseif ($filter === 'buyer') {
    $filterSQL = " WHERE role='buyer' ";
    $filterLabel = 'Buyers';
} elseif ($filter === 'admin') {
    $filterSQL = " WHERE role='admin' ";
    $filterLabel = 'Admins';
}

if (!empty($search)) {
    $filterSQL .= (empty($filterSQL) ? " WHERE " : " AND ");
    $filterSQL .= "(name LIKE '%$search%' OR email LIKE '%$search%')";
}

 $result = $conn->query("SELECT * FROM users $filterSQL ORDER BY id DESC");
 $totalCount = $result->num_rows;

 $countAll = $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];
 $countSellers = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='seller'")->fetch_assoc()['c'];
 $countBuyers = $conn->query("SELECT COUNT(*) as c FROM users WHERE role='buyer'")->fetch_assoc()['c'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>User Management</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
    max-width: 1400px;
    margin-left: auto;
    margin-right: auto;
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
    flex-wrap: wrap;
    gap: 12px;
}

.top-bar h4 {
    font-weight: 700;
    margin: 0;
    font-size: 20px;
}

.pills {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}

.pill {
    padding: 7px 16px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    border: 2px solid #e5e7eb;
    color: #6b7280;
    background: #fff;
    transition: 0.2s;
}
.pill:hover { border-color: #818cf8; color: #4f46e5; text-decoration: none; }
.pill.on { background: #4f46e5; border-color: #4f46e5; color: #fff; }
.pill .cnt {
    display: inline-block;
    min-width: 22px;
    height: 22px;
    line-height: 22px;
    text-align: center;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 700;
    margin-left: 5px;
    padding: 0 5px;
}
.pill.on .cnt { background: rgba(255,255,255,0.2); color: #fff; }
.pill:not(.on) .cnt { background: #f3f4f6; color: #6b7280; }

.search-row {
    background: #fff;
    border-radius: 12px;
    padding: 14px 24px;
    margin-bottom: 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    display: flex;
    gap: 10px;
    align-items: center;
}

.search-row input {
    border: 1.5px solid #d1d5db;
    border-radius: 8px;
    padding: 9px 14px;
    font-size: 15px;
    flex: 1;
}
.search-row input:focus {
    border-color: #818cf8;
    box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
    outline: none;
}

.search-row .btn {
    border-radius: 8px;
    padding: 9px 22px;
    font-size: 15px;
    font-weight: 600;
}

.main-table-wrap {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    overflow: hidden;
}

.table {
    margin: 0;
    font-size: 15px;
}

.table thead th {
    background: #1e293b;
    color: #fff;
    font-size: 14px;
    font-weight: 600;
    padding: 14px 16px;
    text-align: center;
    border: 1px solid #374151;
    letter-spacing: 0.3px;
}

.table tbody td {
    padding: 13px 16px;
    text-align: center;
    vertical-align: middle;
    border: 1px solid #e5e7eb;
    font-size: 15px;
}

.table tbody tr:hover { background: #f9fafb; }

.avatar {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    font-weight: 700;
    font-size: 15px;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.av-seller { background: linear-gradient(135deg, #f59e0b, #d97706); }
.av-buyer { background: linear-gradient(135deg, #3b82f6, #2563eb); }
.av-admin { background: linear-gradient(135deg, #ef4444, #dc2626); }

.user-cell {
    display: flex;
    align-items: center;
    gap: 12px;
    text-align: left;
}
.user-cell .name { font-weight: 600; color: #111827; }
.user-cell .uid { font-size: 13px; color: #9ca3af; }

.badge-role {
    padding: 5px 14px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
}
.br-seller { background: #fef3c7; color: #92400e; }
.br-buyer { background: #dbeafe; color: #1e40af; }
.br-admin { background: #fee2e2; color: #991b1b; }

.badge-status {
    padding: 5px 14px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
}
.bs-active { background: #d1fae5; color: #065f46; }
.bs-pending { background: #fef3c7; color: #92400e; }

.empty-msg {
    text-align: center;
    padding: 40px;
    color: #9ca3af;
    font-size: 16px;
}
</style>

</head>

<body>

<?php include '../includes/header.php'; ?>
<div class="main-content">

    <div class="top-bar">
        <h4><i class="bi bi-people-fill me-2 text-primary"></i><?= $filterLabel ?></h4>
        <div class="pills">
            <a href="users.php" class="pill <?= empty($filter) ? 'on' : '' ?>">All <span class="cnt"><?= $countAll ?></span></a>
            <a href="users.php?filter=seller" class="pill <?= $filter==='seller' ? 'on' : '' ?>">Sellers <span class="cnt"><?= $countSellers ?></span></a>
            <a href="users.php?filter=buyer" class="pill <?= $filter==='buyer' ? 'on' : '' ?>">Buyers <span class="cnt"><?= $countBuyers ?></span></a>
        </div>
    </div>

    <div class="search-row">
        <i class="bi bi-search text-muted"></i>
        <form method="GET" class="d-flex gap-2 flex-grow-1" style="margin:0;">
            <?php if(!empty($filter)): ?><input type="hidden" name="filter" value="<?= $filter ?>"><?php endif; ?>
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by name or email..." style="border:none; box-shadow:none; padding:9px 0; font-size:15px;">
            <button type="submit" class="btn btn-primary">Search</button>
        </form>
        <?php if(!empty($filter) || !empty($search)): ?>
            <a href="users.php" class="btn btn-outline-secondary" style="border-radius:8px; padding:9px 16px; font-size:14px; font-weight:600;">Clear</a>
        <?php endif; ?>
    </div>

    <div class="main-table-wrap">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th style="text-align:left">User</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Joined</th>
                    </tr>
                </thead>
                <tbody>
                <?php if($totalCount > 0): ?>
                <?php while($row = $result->fetch_assoc()):
                    $role = strtolower($row['role']);
                    $status = strtolower($row['status'] ?? 'approved');
                ?>
                    <tr>
                        <td><strong>#<?= $row['id'] ?></strong></td>
                        <td>
                            <div class="user-cell">
                                <div class="avatar av-<?= $role ?>"><?= strtoupper(substr($row['name'],0,1)) ?></div>
                                <div>
                                    <div class="name"><?= htmlspecialchars($row['name']) ?></div>
                                    <div class="uid">ID: <?= $row['id'] ?></div>
                                </div>
                            </div>
                        </td>
                        <td style="color:#6b7280;"><?= $row['email'] ?? '—' ?></td>
                        <td>
                            <?php if($role==='seller'): ?>
                                <span class="badge-role br-seller"><i class="bi bi-lightning-charge me-1"></i>Seller</span>
                            <?php elseif($role==='buyer'): ?>
                                <span class="badge-role br-buyer"><i class="bi bi-cart me-1"></i>Buyer</span>
                            <?php else: ?>
                                <span class="badge-role br-admin"><i class="bi bi-shield-lock me-1"></i>Admin</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if($status==='approved' || $status==='active'): ?>
                                <span class="badge-status bs-active"><i class="bi bi-check-circle me-1"></i>Active</span>
                            <?php else: ?>
                                <span class="badge-status bs-pending"><i class="bi bi-clock me-1"></i>Pending</span>
                            <?php endif; ?>
                        </td>
                        <td style="color:#6b7280;"><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                    </tr>
                <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6"><div class="empty-msg">No users found</div></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
</body>
</html>