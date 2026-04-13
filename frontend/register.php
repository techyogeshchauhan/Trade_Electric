<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — P2P Energy Trading</title>

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
        .register-container {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 30px 20px;
        }

        /* ── Card ── */
        .register-card {
            background: #ffffff;
            border-radius: 18px;
            padding: 44px 40px 36px;
            width: 520px;
            max-width: 100%;
            box-shadow: 0 8px 32px rgba(0,0,0,0.07);
            position: relative;
        }

        .register-card-icon {
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

        .register-card-icon i {
            font-size: 26px;
            color: #f59e0b;
        }

        .register-title {
            text-align: center;
            margin-bottom: 6px;
            font-weight: 800;
            font-size: 22px;
            color: #1a3a5c;
        }

        .register-subtitle {
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

        .form-control, .form-select {
            border-radius: 11px;
            padding: 12px 16px;
            font-size: 15px;
            border: 1.5px solid #dde4ec;
            background: #f9fafb;
            transition: all 0.25s ease;
            color: #1f2937;
        }

        .form-control:focus, .form-select:focus {
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

        /* ── Success Message ── */
        .success-box {
            display: none;
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 10px;
            padding: 12px 16px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 600;
            color: #16a34a;
            text-align: center;
        }

        .success-box.show {
            display: block;
            animation: fadeSlideUp 0.4s ease;
        }

        /* ── Button ── */
        .btn-register {
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

        .btn-register:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 22px rgba(245,158,11,0.4);
        }

        .btn-register:active {
            transform: translateY(0);
        }

        .btn-register .spinner {
            display: none;
            width: 18px;
            height: 18px;
            border: 2.5px solid rgba(26,58,92,0.2);
            border-top-color: #1a3a5c;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
            margin: 0 auto;
        }

        .btn-register.loading .btn-text { display: none; }
        .btn-register.loading .spinner { display: block; }

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

        .register-card {
            animation: fadeSlideUp 0.5s ease;
        }

        /* ── Responsive ── */
        @media (max-width: 480px) {
            .header-bar { padding: 14px 20px; }
            .idlogo { height: 46px; }
            .logo { height: 38px; }
            .register-card { padding: 32px 24px 28px; }
            .register-title { font-size: 20px; }
        }
    </style>
</head>

<body>

<!-- HEADER -->
<div class="header-bar">
    <img src="../assets/gescomLogo.png" class="idlogo">
    <img src="../assets/apcLogo.jpg" class="logo">
</div>

<!-- REGISTER -->
<div class="register-container">

    <div class="register-card">

        <div class="register-card-icon">
            <i class="fas fa-user-plus"></i>
        </div>

        <h4 class="register-title">Create Account</h4>
        <p class="register-subtitle">Register for P2P Energy Trading Platform</p>

        <!-- Error Box -->
        <div class="error-box" id="errorBox">
            <i class="fas fa-exclamation-circle me-1"></i>
            <span id="errorText">Registration failed</span>
        </div>

        <!-- Success Box -->
        <div class="success-box" id="successBox">
            <i class="fas fa-check-circle me-1"></i>
            <span id="successText">Registration successful!</span>
        </div>

        <form id="registerForm">

            <!-- ROLE -->
            <div class="mb-3">
                <label class="form-label">Select Role</label>
                <select name="role" class="form-select" id="roleSelect" required>
                    <option value="">Choose your role</option>
                    <option value="buyer">Buyer (Consumer)</option>
                    <option value="seller">Seller (Prosumer)</option>
                </select>
                <div class="hint-text"><i class="fas fa-info-circle"></i> Buyers purchase energy, Sellers sell excess solar energy</div>
            </div>

            <!-- NAME -->
            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" name="name" placeholder="Enter your full name" class="form-control" id="nameInput" required>
                <div class="hint-text"><i class="fas fa-info-circle"></i> This will be your Login ID</div>
            </div>

            <!-- EMAIL -->
            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" placeholder="Enter your email" class="form-control" required>
            </div>

            <!-- MOBILE -->
            <div class="mb-3">
                <label class="form-label">Mobile Number</label>
                <input type="tel" name="telephone" placeholder="Enter your mobile number" class="form-control" id="phoneInput" required>
            </div>

            <!-- PASSWORD -->
            <div class="mb-3">
                <label class="form-label">Password</label>
                <div style="position: relative;">
                    <input type="password" name="password" placeholder="Create a password" class="form-control" id="passwordInput" required>
                    <i class="fas fa-eye" id="togglePassword" style="position: absolute; right: 16px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #94a3b8;"></i>
                </div>
                <div class="hint-text"><i class="fas fa-info-circle"></i> Minimum 4 characters</div>
            </div>

            <!-- ADDRESS -->
            <div class="mb-3">
                <label class="form-label">Address</label>
                <textarea name="address" placeholder="Enter your address" class="form-control" rows="2" required></textarea>
            </div>

            <!-- CONSUMER ACCOUNT (Auto-generated) -->
            <input type="hidden" name="consumer_account" id="consumerAccount">
            
            <!-- Hidden fields with default values -->
            <input type="hidden" name="renewable_source" value="Solar">
            <input type="hidden" name="prosumer_category" value="Residential">
            <input type="hidden" name="sanctioned_load" value="5">
            <input type="hidden" name="capacity" value="10">
            <input type="hidden" name="average_load" value="5">
            <input type="hidden" name="units_required" value="100">
            <input type="hidden" name="tod_billing" value="Yes">
            <input type="hidden" name="meter_purchase" value="GESCOM">
            <input type="hidden" name="installation_date" id="installationDate">

            <button type="submit" class="btn-register" id="registerBtn">
                <span class="btn-text"><i class="fas fa-user-plus me-2"></i>Register</span>
                <div class="spinner"></div>
            </button>

        </form>

        <div class="footer-text">
            Already have an account? <a href="login.php">Login here</a>
        </div>

    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
// Password show/hide toggle
$('#togglePassword').on('click', function() {
    const passwordInput = $('#passwordInput');
    const icon = $(this);
    
    if (passwordInput.attr('type') === 'password') {
        passwordInput.attr('type', 'text');
        icon.removeClass('fa-eye').addClass('fa-eye-slash');
    } else {
        passwordInput.attr('type', 'password');
        icon.removeClass('fa-eye-slash').addClass('fa-eye');
    }
});

// Auto-generate consumer account when role is selected
$('#roleSelect').on('change', function() {
    const role = $(this).val();
    if (role) {
        const random = Math.floor(1000 + Math.random() * 9000);
        const prefix = (role === 'buyer') ? 'B0' : 'S0';
        $('#consumerAccount').val(prefix + random);
    }
});

// Set installation date to today
$('#installationDate').val(new Date().toISOString().split('T')[0]);

// Hide error/success when user starts typing
$('#roleSelect, #nameInput, #passwordInput').on('input change', function () {
    $('#errorBox').removeClass('show');
    $('#successBox').removeClass('show');
});

$("#registerForm").submit(function (e) {
    e.preventDefault();

    const btn = $('#registerBtn');
    btn.addClass('loading');
    $('#errorBox').removeClass('show');
    $('#successBox').removeClass('show');

    $.ajax({
        url: "../api/register_api.php",
        method: "POST",
        data: $(this).serialize(),
        success: function (res) {
            try {
                let data = JSON.parse(res);

                if (data.status === "success") {
                    const loginId = $('#nameInput').val().trim();
                    const loginPass = $('#passwordInput').val().trim();
                    
                    $('#successText').html(`
                        Registration successful!<br>
                        <small>Login ID: <strong>${loginId}</strong> | Password: <strong>${loginPass}</strong></small>
                    `);
                    $('#successBox').addClass('show');
                    
                    // Redirect to login after 3 seconds
                    setTimeout(function() {
                        window.location.href = "login.php";
                    }, 3000);
                } else {
                    btn.removeClass('loading');
                    $('#errorText').text(data.message || 'Registration failed');
                    $('#errorBox').addClass('show');
                }
            } catch (e) {
                btn.removeClass('loading');
                $('#errorText').text('Server error. Please try again.');
                $('#errorBox').addClass('show');
            }
        },
        error: function () {
            btn.removeClass('loading');
            $('#errorText').text('Something went wrong. Please try again.');
            $('#errorBox').addClass('show');
        }
    });
});
</script>

</body>
</html>
