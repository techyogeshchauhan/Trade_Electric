<?php
session_start();
include '../frontend/includes/config.php';

// 🔥 SHOW ERRORS (REMOVE IN PRODUCTION)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 🔒 SAFE INPUT
$name              = mysqli_real_escape_string($conn, $_POST['name']);
$address           = mysqli_real_escape_string($conn, $_POST['address']);
$consumer_account  = mysqli_real_escape_string($conn, $_POST['consumer_account']);
$telephone         = mysqli_real_escape_string($conn, $_POST['telephone']);
$email             = mysqli_real_escape_string($conn, $_POST['email']);
$password          = mysqli_real_escape_string($conn, $_POST['password'] ?? '1234'); // Get password from form
$renewable_source  = mysqli_real_escape_string($conn, $_POST['renewable_source']);
$prosumer_category = mysqli_real_escape_string($conn, $_POST['prosumer_category']);
$sanctioned_load   = floatval($_POST['sanctioned_load']);
$capacity          = floatval($_POST['capacity']);
$tod_billing       = mysqli_real_escape_string($conn, $_POST['tod_billing']);
$meter_purchase    = mysqli_real_escape_string($conn, $_POST['meter_purchase']);
$installation_date = mysqli_real_escape_string($conn, $_POST['installation_date']);

// 🔥 ROLE LOGIC
$role = mysqli_real_escape_string($conn, $_POST['role']);

// ❗ CHECK REQUIRED FIELDS
if(empty($name) || empty($email) || empty($password)){
    echo json_encode([
        "status"=>"error",
        "message"=>"Name, Email and Password are required"
    ]);
    exit;
}

// 🔍 CHECK EMAIL EXIST
$check = $conn->query("SELECT id FROM users WHERE email='$email'");
if($check && $check->num_rows > 0){
    echo json_encode([
        "status"=>"error",
        "message"=>"Email already exists"
    ]);
    exit;
}

// Use password from form input
$sql = "INSERT INTO users (name, email, password, role)
        VALUES ('$name', '$email', '$password', '$role')";
if($conn->query($sql)){

    $user_id = $conn->insert_id;

    // 🔹 INSERT APPLICATION
    $sql2 = "INSERT INTO p2p_applications (
        user_id, address, consumer_account, telephone,
        renewable_source, prosumer_category, sanctioned_load,
        capacity, tod_billing, meter_purchase, installation_date, status
    ) VALUES (
        '$user_id', '$address', '$consumer_account', '$telephone',
        '$renewable_source', '$prosumer_category', '$sanctioned_load',
        '$capacity', '$tod_billing', '$meter_purchase', '$installation_date', 'approved'
    )";

    if($conn->query($sql2)){

        // 🔥 AUTO LOGIN
        $_SESSION['user_id'] = $user_id;
        $_SESSION['name']    = $name;
        $_SESSION['role']    = $role;

        echo json_encode([
            "status" => "success",
            "role"   => $role
        ]);

    } else {
        echo json_encode([
            "status"=>"error",
            "message"=>"Application insert failed: " . $conn->error
        ]);
    }

} else {
    echo json_encode([
        "status"=>"error",
        "message"=>"User insert failed: " . $conn->error
    ]);
}

exit;
?>