<?php
// Disable error display for production
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// ============================================================
// DATABASE CONFIGURATION
// ------------------------------------------------------------
// LOCAL (XAMPP):
// $conn = new mysqli("localhost", "root", "", "energy_trading");
//
// SERVER — Update the values below with your hosting credentials:
//   DB Host     : usually "localhost"
//   DB Username : your cPanel MySQL username (e.g. solarsuvi_user)
//   DB Password : your MySQL user password
//   DB Name     : your database name (e.g. solarsuvi_energy)
// ============================================================
$db_host = "localhost";
$db_user = "mansi";       // ← CHANGE THIS
$db_pass = "GBugiWA40bMG3GUG8Yov";    // ← CHANGE THIS
$db_name = "p2p";    // ← CHANGE THIS (must match DB imported on server)

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    // Log error instead of displaying
    error_log("Database connection failed: " . $conn->connect_error);

    // For API calls, return JSON error
    if (strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'Database connection failed'
        ]);
        exit;
    }

    die("Connection failed. Please check database settings.");
}

// Set charset to UTF-8
$conn->set_charset("utf8mb4");

// Get logo paths from settings (with fallback to default) - only if not API call
if (strpos($_SERVER['REQUEST_URI'], '/api/') === false) {
    try {
        $logoQuery = $conn->query("SELECT logo_left, logo_right FROM settings LIMIT 1");
        $logoData = $logoQuery ? $logoQuery->fetch_assoc() : null;

        if (!defined('LOGO_LEFT')) {
            define('LOGO_LEFT', $logoData['logo_left'] ?? '../assets/gescomLogo.png');
        }
        if (!defined('LOGO_RIGHT')) {
            define('LOGO_RIGHT', $logoData['logo_right'] ?? '../assets/apcLogo.jpg');
        }
    } catch (Exception $e) {
        // Fallback if settings table doesn't have logo columns yet
        if (!defined('LOGO_LEFT')) {
            define('LOGO_LEFT', '../assets/gescomLogo.png');
        }
        if (!defined('LOGO_RIGHT')) {
            define('LOGO_RIGHT', '../assets/apcLogo.jpg');
        }
    }
}
?>