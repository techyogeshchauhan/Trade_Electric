<?php
session_start();
header('Content-Type: application/json');
mysqli_report(MYSQLI_REPORT_OFF); // Prevent exceptions from breaking JSON output

include '../frontend/includes/config.php';

// Fix: use strtolower() so 'Admin', 'ADMIN', 'admin' all work
if (!isset($_SESSION['user_id']) || strtolower(trim($_SESSION['role'] ?? '')) !== 'admin') {
    echo json_encode([
        "status"  => "error",
        "message" => "Unauthorized access — role: " . ($_SESSION['role'] ?? 'not set')
    ]);
    exit();
}

// Get form data
$default_wallet_balance = (float)($_POST['default_wallet_balance'] ?? 5000);
$gescom_avg_consumption = (float)($_POST['gescom_avg_consumption'] ?? 5);
$gescom_avg_supply = (float)($_POST['gescom_avg_supply'] ?? 5);
$max_units_per_slot = (int)($_POST['max_units_per_slot'] ?? 100);
$default_listing_limit = (int)($_POST['default_listing_limit'] ?? 10);
$max_listing_limit = (int)($_POST['max_listing_limit'] ?? 1000);
$logo_left = $conn->real_escape_string($_POST['logo_left'] ?? '../assets/gescomLogo.png');
$logo_right = $conn->real_escape_string($_POST['logo_right'] ?? '../assets/apcLogo.jpg');
// Trading hours
$trading_start_time = $conn->real_escape_string($_POST['trading_start_time'] ?? '10:00');
$trading_end_time   = $conn->real_escape_string($_POST['trading_end_time']   ?? '17:00');

// Validate inputs
if ($default_wallet_balance < 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Default wallet balance cannot be negative"
    ]);
    exit();
}

if ($max_units_per_slot < 1) {
    echo json_encode([
        "status" => "error",
        "message" => "Max units per slot must be at least 1"
    ]);
    exit();
}

if ($default_listing_limit < 1 || $max_listing_limit < $default_listing_limit) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid listing limits"
    ]);
    exit();
}

// Check if settings exist
$checkSettings = $conn->query("SELECT id FROM settings LIMIT 1");

if ($checkSettings && $checkSettings->num_rows > 0) {
    // Update existing settings
    $update = $conn->query("
        UPDATE settings SET
            default_wallet_balance = $default_wallet_balance,
            gescom_avg_consumption = $gescom_avg_consumption,
            gescom_avg_supply = $gescom_avg_supply,
            max_units_per_slot = $max_units_per_slot,
            default_listing_limit = $default_listing_limit,
            max_listing_limit = $max_listing_limit,
            logo_left = '$logo_left',
            logo_right = '$logo_right',
            trading_start_time = '$trading_start_time',
            trading_end_time = '$trading_end_time'
        WHERE id = 1
    ");

    if ($update) {
        echo json_encode([
            "status"  => "success",
            "message" => "System settings updated successfully! ✅"
        ]);
    } else {
        echo json_encode([
            "status"  => "error",
            "message" => "DB Update failed: " . $conn->error
        ]);
    }
} else {
    // Insert new settings
    $insert = $conn->query("
        INSERT INTO settings (
            default_wallet_balance,
            gescom_avg_consumption,
            gescom_avg_supply,
            max_units_per_slot,
            default_listing_limit,
            max_listing_limit,
            logo_left,
            logo_right,
            utility_charge_buyer,
            utility_charge_seller,
            platform_charge
        ) VALUES (
            $default_wallet_balance,
            $gescom_avg_consumption,
            $gescom_avg_supply,
            $max_units_per_slot,
            $default_listing_limit,
            $max_listing_limit,
            '$logo_left',
            '$logo_right',
            0.02,
            0.02,
            2.00
        )
    ");

    if ($insert) {
        echo json_encode([
            "status" => "success",
            "message" => "System settings created successfully"
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Failed to create settings: " . $conn->error
        ]);
    }
}
?>
