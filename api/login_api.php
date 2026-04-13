<?php
// Disable all output before JSON
error_reporting(0);
ini_set('display_errors', 0);

// Start session BEFORE any output
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Start output buffering to catch any unwanted output
ob_start();

// Include config for database connection
include '../frontend/includes/config.php';

// Check connection
if ($conn->connect_error) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode([
        "status" => "error",
        "message" => "Database connection failed"
    ]);
    exit;
}

// Check if POST data exists
if (!isset($_POST['name']) || !isset($_POST['password'])) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode([
        "status" => "error",
        "message" => "Missing credentials"
    ]);
    exit;
}

// Get and sanitize input
$name = trim($_POST['name']);
$password = trim($_POST['password']);

// Prepare query (using simple query for now)
$name_escaped = $conn->real_escape_string($name);
$password_escaped = $conn->real_escape_string($password);

$sql = "SELECT * FROM users WHERE name='$name_escaped' AND password='$password_escaped'";
$result = $conn->query($sql);

// Check result
if ($result && $result->num_rows > 0) {
    $user = $result->fetch_assoc();
    
    // Set session variables
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['name'] = $user['name'];

    // Clean buffer and send JSON
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode([
        "status" => "success",
        "role" => $user['role'],
        "name" => $user['name']
    ]);
} else {
    // Clean buffer and send error
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode([
        "status" => "error",
        "message" => "Invalid username or password"
    ]);
}

// Close connection
$conn->close();
exit;
?>
