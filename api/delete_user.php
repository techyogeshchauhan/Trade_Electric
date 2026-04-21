<?php
session_start();
header('Content-Type: application/json');
mysqli_report(MYSQLI_REPORT_OFF);

include '../frontend/includes/config.php';

// Admin only
if (!isset($_SESSION['user_id']) || strtolower(trim($_SESSION['role'] ?? '')) !== 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$user_id    = (int)($_POST['user_id'] ?? 0);
$admin_id   = (int)$_SESSION['user_id'];

if ($user_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid user ID']);
    exit();
}

// Prevent admin from deleting themselves
if ($user_id === $admin_id) {
    echo json_encode(['status' => 'error', 'message' => 'You cannot delete your own account']);
    exit();
}

// Prevent deleting other admins
$check = $conn->query("SELECT role FROM users WHERE id = $user_id LIMIT 1");
if (!$check || $check->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'User not found']);
    exit();
}
$targetRole = strtolower($check->fetch_assoc()['role']);
if ($targetRole === 'admin') {
    echo json_encode(['status' => 'error', 'message' => 'Cannot delete admin accounts']);
    exit();
}

// Delete the user (related records may cascade depending on FK setup)
$del = $conn->query("DELETE FROM users WHERE id = $user_id");

if ($del) {
    echo json_encode(['status' => 'success', 'message' => 'User deleted successfully']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'DB error: ' . $conn->error]);
}
?>
