<?php
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
    <title>Login — P2P Energy Trading</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #f0f2f5;
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ── Header ── */
        .header-bar {
            background: linear-gradient(135deg, #e8f4fd 0%, #f0f7ff 50%, #eaf2fb 100%);
            padding: 18px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #d4e8f7;
            position: relative;
        }

        .header-bar::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #ffc107, #ff9800, #ffc107);
        }

        .idlogo { height: 58px; }
        .logo { height: 48px; }

        /* ── Center ── */
        .login-container {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 30px 20px;
        }

        /* ── Card ── */
        .login-card {
            background: #ffffff;
            border-radius: 18px;
            padding: 44px 40px 36px;
            width: 420px;
            max-width: 100%;
            box-shadow: 0 8px 32px rgba(0,0,0,0.07);
            position: relative;
        }

        .login-card-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #fff8e1, #fff3cd);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 18px;
            border: 2px solid #ffe082;
        }

        .login-card-icon i {
            font-size: 26px;
            color: #f59e0b;
        }

        .login-title {
            text-align: center;
            margin-bottom: 6px;
            font-weight: 800;
            font-size: 22px;
            color: #1a3a5c;
        }

        .login-subtitle {
            text-align: center;
            margin-bottom: 30px;
            font-size: 14px;
            color: #94a3b8;
        }

        /* ── Form ── */
        .form-label {
            font-size: 14.5px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 7px;
        }

        .form-control {
            border-radius: 11px;
            padding: 12px 16px;
            font-size: 15px;
            border: 1.5px solid #dde4ec;
            background: #f9fafb;
            transition: all 0.25s ease;
            color: #1f2937;
        }

        .form-control:focus {
            border-color: #ffc107;
            box-shadow: 0 0 0 3px rgba(255,193,7,0.15);
            background: #fff;
        }

        .form-control::placeholder {
            color: #b0bec5;
            font-weight: 400;
        }

        .hint-text {
            font-size: 12px;
            color: #94a3b8;
            margin-top: 5px;
            font-weight: 500;
        }

        .hint-text i {
            color: #b0bec5;
            font-size: 11px;
            margin-right: 3px;
        }

        /* ── Error Message ── */
        .error-box {
            display: none;
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 10px;
            padding: 12px 16px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 600;
            color: #dc2626;
            text-align: center;
        }

        .error-box.show {
            display: block;
            animation: shake 0.4s ease;
        }

        /* ── Button ── */
        .btn-login {
            background: linear-gradient(135deg, #ffc107, #f59e0b);
            border: none;
            border-radius: 11px;
            padding: 13px;
            font-weight: 700;
            font-size: 15px;
            color: #1a3a5c;
            width: 100%;
            transition: all 0.25s ease;
            cursor: pointer;
            box-shadow: 0 4px 16px rgba(245,158,11,0.3);
            margin-top: 8px;
        }

        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 22px rgba(245,158,11,0.4);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .btn-login .spinner {
            display: none;
            width: 18px;
            height: 18px;
            border: 2.5px solid rgba(26,58,92,0.2);
            border-top-color: #1a3a5c;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
            margin: 0 auto;
        }

        .btn-login.loading .btn-text { display: none; }
        .btn-login.loading .spinner { display: block; }

        /* ── Footer ── */
        .footer-text {
            text-align: center;
            margin-top: 22px;
            font-size: 13px;
            color: #b0bec5;
            font-weight: 500;
        }

        .footer-text a {
            color: #ffc107;
            text-decoration: none;
            font-weight: 600;
        }

        .footer-text a:hover {
            text-decoration: underline;
        }

        .footer-text i {
            color: #d32f2f;
            margin-right: 4px;
        }

      

        /* ── Animations ── */
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20% { transform: translateX(-8px); }
            40% { transform: translateX(8px); }
            60% { transform: translateX(-5px); }
            80% { transform: translateX(5px); }
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        @keyframes fadeSlideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .login-card {
            animation: fadeSlideUp 0.5s ease;
        }

        /* ── Responsive ── */
        @media (max-width: 480px) {
            .header-bar { padding: 14px 20px; }
            .idlogo { height: 46px; }
            .logo { height: 38px; }
            .login-card { padding: 32px 24px 28px; }
            .login-title { font-size: 20px; }
        }
    </style>
</head>

<body>

<!-- HEADER -->
<div class="header-bar">
    <img src="../assets/gescomLogo.png" class="idlogo">
    <img src="../assets/apcLogo.jpg" class="logo">
</div>

<!-- LOGIN -->
<div class="login-container">

    <div class="login-card">

        <div class="login-card-icon">
            <i class="fas fa-bolt"></i>
        </div>

        <h4 class="login-title">Energy Trading Login</h4>
        <p class="login-subtitle">Sign in to access your P2P energy dashboard</p>

        <!-- Error Box -->
        <div class="error-box" id="errorBox">
            <i class="fas fa-exclamation-circle me-1"></i>
            <span id="errorText">Invalid login credentials</span>
        </div>

        <form id="loginForm">

            <!-- LOGIN ID -->
            <div class="mb-3">
                <label class="form-label">Login ID</label>
                <input type="text" name="name" placeholder="Enter your name" class="form-control" id="loginIdInput" required>
                <div class="hint-text"><i class="fas fa-info-circle"></i> Your registered name is your Login ID</div>
            </div>

            <!-- PASSWORD -->
            <div class="mb-3">
                <label class="form-label">Password</label>
                <div style="position: relative;">
                    <input type="password" name="password" placeholder="Enter your password" class="form-control" id="loginPassInput" required>
                    <i class="fas fa-eye" id="toggleLoginPassword" style="position: absolute; right: 16px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #94a3b8;"></i>
                </div>
                <div class="hint-text"><i class="fas fa-info-circle"></i> Your registered mobile number is your Password</div>
            </div>

            <button type="submit" class="btn-login" id="loginBtn">
                <span class="btn-text"><i class="fas fa-sign-in-alt me-2"></i>Login</span>
                <div class="spinner"></div>
            </button>

        </form>

        <div class="footer-text">
            <i class="fas fa-shield-alt"></i> Secure P2P Energy Trading Platform
        </div>

        <div class="footer-text" style="margin-top: 10px;">
            Don't have an account? <a href="register.php">Register here</a>
        </div>

    </div>
</div>



<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
// Password show/hide toggle
$('#toggleLoginPassword').on('click', function() {
    const passwordInput = $('#loginPassInput');
    const icon = $(this);
    
    if (passwordInput.attr('type') === 'password') {
        passwordInput.attr('type', 'text');
        icon.removeClass('fa-eye').addClass('fa-eye-slash');
    } else {
        passwordInput.attr('type', 'password');
        icon.removeClass('fa-eye-slash').addClass('fa-eye');
    }
});

// Hide error when user starts typing
$('#loginIdInput, #loginPassInput').on('input', function () {
    $('#errorBox').removeClass('show');
});

$("#loginForm").submit(function (e) {
    e.preventDefault();

    const btn = $('#loginBtn');
    btn.addClass('loading');
    $('#errorBox').removeClass('show');

    $.ajax({
        url: "/apc/New_project/api/login_api.php",
        method: "POST",
        data: $(this).serialize(),
        dataType: "json", // Automatically parse JSON
        success: function (data) {
            console.log("Response:", data); // Debug log
            
            if (data.status === "success") {
                // Redirect based on role
                if (data.role === 'buyer') {
                    window.location.href = "buyer/marketplace.php";
                } else if (data.role === 'seller') {
                    window.location.href = "seller/marketplace.php";
                } else if (data.role === 'admin') {
                    window.location.href = "admin/dashboard.php";
                } else {
                    window.location.href = "marketplace.php";
                }
            } else {
                btn.removeClass('loading');
                $('#errorText').text(data.message || 'Invalid credentials');
                $('#errorBox').addClass('show');
            }
        },
        error: function (xhr, status, error) {
            console.error("AJAX Error:", status, error);
            console.error("Response:", xhr.responseText);
            btn.removeClass('loading');
            $('#errorText').text('Connection error. Please try again.');
            $('#errorBox').addClass('show');
        }
    });
});
</script>

</body>
</html>