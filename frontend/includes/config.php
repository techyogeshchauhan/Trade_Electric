<?php
// Disable error display for production
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

$conn = new mysqli("localhost", "root", "", "energy_trading");

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
?>