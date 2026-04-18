<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application for Registration for P2P Energy Transactions</title>

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

        .main-card {
            max-width: 820px;
            margin: 20px auto;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        /* ── Header ── */
        .header {
            background: linear-gradient(135deg, #e8f4fd 0%, #f0f7ff 50%, #eaf2fb 100%);
            padding: 24px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            border-bottom: 1px solid #d4e8f7;
        }

        .header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #ffc107, #ff9800, #ffc107);
        }

        .header h4 {
            color: #1a3a5c;
            font-size: 19px;
            font-weight: 800;
            line-height: 1.45;
            max-width: 540px;
            letter-spacing: -0.2px;
        }

        .header .header-icon {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, #fff8e1, #fff3cd);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            border: 2px solid #ffe082;
        }

        .header .header-icon i {
            font-size: 26px;
            color: #f59e0b;
        }

        /* ── Sidebar Stepper ── */
        .content-wrapper {
            display: flex;
            flex: 1;
        }

        .sidebar {
            width: 230px;
            background: #f7fafd;
            border-right: 1px solid #e8eef4;
            padding: 30px 0;
            flex-shrink: 0;
        }

        .sidebar-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 16px 26px;
            cursor: pointer;
            position: relative;
            transition: all 0.25s ease;
        }

        .sidebar-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 0;
            background: #ffc107;
            border-radius: 0 4px 4px 0;
            transition: width 0.3s ease;
        }

        .sidebar-item.active::before {
            width: 4px;
        }

        .sidebar-item.active {
            background: #fff8e1;
        }

        .sidebar-item.completed {
            opacity: 0.7;
        }

        .sidebar-icon {
            width: 40px;
            height: 40px;
            border-radius: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            flex-shrink: 0;
            border: 2px solid #d0dbe6;
            background: #fff;
            color: #8ea4b8;
            transition: all 0.3s ease;
        }

        .sidebar-item.active .sidebar-icon {
            background: #ffc107;
            border-color: #ffc107;
            color: #1a3a5c;
        }

        .sidebar-item.completed .sidebar-icon {
            background: #28a745;
            border-color: #28a745;
            color: #fff;
        }

        .sidebar-text {
            font-size: 14px;
            font-weight: 600;
            color: #8ea4b8;
            line-height: 1.35;
            transition: color 0.3s ease;
        }

        .sidebar-item.active .sidebar-text {
            color: #1a3a5c;
        }

        .sidebar-item.completed .sidebar-text {
            color: #28a745;
        }

        .sidebar-connector {
            width: 2px;
            height: 22px;
            background: #d8e4ee;
            margin-left: 47px;
            transition: background 0.3s ease;
        }

        .sidebar-connector.filled {
            background: #28a745;
        }

        /* ── Form Area ── */
        .form-area {
            flex: 1;
            padding: 38px 40px 32px;
            overflow-y: auto;
        }

        .form-area h5 {
            font-size: 23px;
            font-weight: 800;
            color: #1a3a5c;
            margin-bottom: 6px;
        }

        .form-area .subtitle {
            font-size: 14.5px;
            color: #94a3b8;
            margin-bottom: 30px;
        }

        .form-label {
            font-weight: 600;
            font-size: 14.5px;
            color: #374151;
            margin-bottom: 7px;
        }

        .form-control, .form-select {
            border-radius: 11px;
            border: 1.5px solid #dde4ec;
            padding: 12px 16px;
            font-size: 15px;
            transition: all 0.25s ease;
            background: #f9fafb;
            color: #1f2937;
        }

        .form-control:focus, .form-select:focus {
            border-color: #ffc107;
            box-shadow: 0 0 0 3px rgba(255,193,7,0.15);
            background: #fff;
        }

        .form-check-input {
            width: 20px;
            height: 20px;
        }

        .form-check-input:checked {
            background-color: #ffc107;
            border-color: #ffc107;
        }

        .form-check-label {
            font-size: 15px;
            font-weight: 600;
            color: #374151;
        }

        .hint-text {
            font-size: 12.5px;
            color: #d97706;
            font-weight: 600;
            margin-top: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .hint-text i {
            font-size: 13px;
        }

        /* ── Buttons ── */
        .form-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 34px;
            padding-top: 22px;
            border-top: 1px solid #f0f0f0;
        }

        .btn-back-custom {
            background: transparent;
            border: 2px solid #d1d5db;
            color: #6b7280;
            font-weight: 700;
            padding: 12px 28px;
            border-radius: 11px;
            font-size: 15px;
            transition: all 0.25s ease;
            cursor: pointer;
        }

        .btn-back-custom:hover {
            border-color: #9ca3af;
            color: #374151;
            background: #f9fafb;
        }

        .btn-next-custom {
            background: linear-gradient(135deg, #ffc107, #f59e0b);
            border: none;
            color: #1a3a5c;
            font-weight: 700;
            padding: 12px 34px;
            border-radius: 11px;
            font-size: 15px;
            transition: all 0.25s ease;
            cursor: pointer;
            box-shadow: 0 4px 16px rgba(245,158,11,0.3);
        }

        .btn-next-custom:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 22px rgba(245,158,11,0.4);
        }

        .btn-submit-custom {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            border: none;
            color: #fff;
            font-weight: 700;
            padding: 12px 34px;
            border-radius: 11px;
            font-size: 15px;
            transition: all 0.25s ease;
            cursor: pointer;
            box-shadow: 0 4px 16px rgba(34,197,94,0.3);
        }

        .btn-submit-custom:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 22px rgba(34,197,94,0.4);
        }

        /* ── Credentials Preview (below submit) ── */
        .credentials-preview {
            margin-top: 18px;
            animation: fadeSlideIn 0.35s ease;
        }

        .cred-mini-card {
            background: linear-gradient(135deg, #fffbeb, #fef9e7);
            border: 2px solid #fcd34d;
            border-radius: 13px;
            padding: 18px 22px;
            position: relative;
            overflow: hidden;
        }

        .cred-mini-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(180deg, #f59e0b, #d97706);
            border-radius: 4px 0 0 4px;
        }

        .cred-mini-header {
            font-size: 11.5px;
            font-weight: 800;
            color: #92400e;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 7px;
        }

        .cred-mini-header i {
            font-size: 13px;
            color: #d97706;
        }

        .cred-mini-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 9px 0;
        }

        .cred-mini-row:not(:last-child) {
            border-bottom: 1px solid #fde68a;
        }

        .cred-mini-label {
            font-size: 12.5px;
            font-weight: 700;
            color: #a16207;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .cred-mini-value {
            font-size: 15px;
            font-weight: 800;
            color: #78350f;
            font-family: 'Inter', monospace;
        }

        .cred-mini-value.empty {
            color: #d4a04a;
            font-style: italic;
            font-weight: 600;
        }

        /* ── Bottom Bar ── */
        .bottom-bar {
            background: linear-gradient(90deg, #d32f2f, #e53935, #d32f2f);
            height: 6px;
            width: 100%;
        }

        /* ── Toast ── */
        .toast-msg {
            position: fixed;
            top: 24px;
            left: 50%;
            transform: translateX(-50%) translateY(-20px);
            background: #1a3a5c;
            color: #fff;
            padding: 12px 28px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            z-index: 99999;
            opacity: 0;
            transition: all 0.3s ease;
            pointer-events: none;
            box-shadow: 0 8px 24px rgba(26,58,92,0.3);
        }

        .toast-msg.show {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }

        /* ── Success Modal ── */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(4px);
        }

        .modal-overlay.show {
            display: flex;
            animation: fadeIn 0.3s ease;
        }

        .modal-box {
            background: #fff;
            border-radius: 22px;
            padding: 44px 40px 34px;
            max-width: 440px;
            width: 92%;
            text-align: center;
            animation: slideUp 0.4s ease;
            position: relative;
        }

        .modal-box .success-icon {
            width: 78px;
            height: 78px;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 22px;
            box-shadow: 0 8px 28px rgba(34,197,94,0.3);
        }

        .modal-box .success-icon i {
            font-size: 34px;
            color: #fff;
        }

        .modal-box h5 {
            font-weight: 800;
            color: #1a3a5c;
            font-size: 24px;
            margin-bottom: 8px;
        }

        .modal-box .modal-subtitle {
            color: #94a3b8;
            font-size: 14.5px;
            margin-bottom: 26px;
            line-height: 1.5;
        }

        .cred-card {
            background: #f8fafc;
            border: 2px dashed #cbd5e1;
            border-radius: 14px;
            padding: 22px 24px;
            margin-bottom: 26px;
        }

        .cred-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
        }

        .cred-row:not(:last-child) {
            border-bottom: 1px solid #e2e8f0;
        }

        .cred-label {
            font-size: 13px;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .cred-value {
            font-size: 16px;
            font-weight: 800;
            color: #1a3a5c;
        }

        .btn-modal-close {
            background: linear-gradient(135deg, #1a3a5c, #1e4a73);
            color: #fff;
            border: none;
            padding: 13px 44px;
            border-radius: 11px;
            font-weight: 700;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.25s ease;
        }

        .btn-modal-close:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 22px rgba(26,58,92,0.3);
        }
        
        /* ── Login Link (below form) ── */
        .login-footer {
            text-align: center;
            margin-top: 22px;
            padding-top: 18px;
            border-top: 1px solid #f0f0f0;
            font-size: 14px;
            color: #94a3b8;
            font-weight: 500;
        }

        .login-footer a {
            color: #ffc107;
            text-decoration: none;
            font-weight: 700;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .login-footer a:hover {
            text-decoration: underline;
            color: #f59e0b;
        }
        
        .login-footer a i {
            font-size: 13px;
        }

        /* ── Animations ── */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeSlideIn {
            from { opacity: 0; transform: translateX(20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .form-section.visible {
            animation: fadeSlideIn 0.35s ease;
        }

        /* ── Responsive ── */
        @media (max-width: 768px) {
            .header-bar { 
                padding: 14px 20px;
            }
            .idlogo { height: 46px; }
            .logo { height: 38px; }
            .main-card { 
                margin: 10px;
            }
            .header { padding: 18px 20px; }
            .header h4 { font-size: 16px; }
            .content-wrapper { flex-direction: column; }
            .sidebar {
                width: 100%;
                flex-direction: row;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 0;
                padding: 16px 10px;
                border-right: none;
                border-bottom: 1px solid #e8eef4;
            }
            .sidebar-connector {
                width: 20px;
                height: 2px;
                margin-left: 0;
            }
            .sidebar-item {
                padding: 8px 4px;
                flex-direction: column;
                gap: 6px;
            }
            .sidebar-item::before { display: none; }
            .sidebar-item.active { background: transparent; }
            .sidebar-text { font-size: 10.5px; text-align: center; }
            .sidebar-icon { width: 36px; height: 36px; font-size: 13px; }
            .form-area { padding: 24px 20px 20px; }
            .form-area h5 { font-size: 20px; }
            .form-footer { flex-direction: column; gap: 12px; }
            .form-footer button { width: 100%; }
            
            /* Login footer mobile */
            .login-footer {
                font-size: 13px;
                margin-top: 18px;
                padding: 16px 12px;
                background: #f8fafc;
                border-radius: 10px;
                border: 1px solid #e2e8f0;
            }
            
            .login-footer a {
                font-size: 14px;
                padding: 8px 16px;
                background: linear-gradient(135deg, #ffc107, #f59e0b);
                color: #1a3a5c;
                border-radius: 8px;
                display: inline-block;
                margin-top: 8px;
                text-decoration: none;
            }
            
            .login-footer a:hover {
                transform: translateY(-1px);
                box-shadow: 0 4px 12px rgba(245,158,11,0.3);
            }
        }
        
        @media (max-width: 480px) {
            .header-bar {
                padding: 12px 15px;
            }
            .idlogo { height: 40px; }
            .logo { height: 35px; }
            
            .login-footer {
                font-size: 12px;
                padding: 14px 10px;
            }
            
            .login-footer a {
                font-size: 13px;
                padding: 7px 14px;
            }
        }
    </style>
</head>
<body>

<!-- HEADER -->
<div class="header-bar">
    <?php
    // Get logo paths with proper fallback
    $logo_left = defined('LOGO_LEFT') ? LOGO_LEFT : '../assets/gescomLogo.png';
    $logo_right = defined('LOGO_RIGHT') ? LOGO_RIGHT : '../assets/apcLogo.jpg';
    ?>
    <img src="<?= $logo_left ?>" class="idlogo" alt="Logo">
    <img src="<?= $logo_right ?>" class="logo" alt="Logo">
</div>

<!-- Toast -->
<div class="toast-msg" id="toastMsg"></div>

<div class="main-card">
    <!-- Header -->
    <div class="header">
        <h4>Application for Registration for P2P Energy Transactions</h4>
        <div class="header-icon">
            <i class="fas fa-bolt"></i>
        </div>
    </div>

    <div class="content-wrapper">
        <!-- Sidebar Stepper -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-item active" data-step="1" onclick="goToStep(1)">
                <div class="sidebar-icon"><i class="fas fa-user"></i></div>
                <div class="sidebar-text">Personal<br>Info</div>
            </div>
            <div class="sidebar-connector" id="conn1"></div>
            <div class="sidebar-item" data-step="2" onclick="goToStep(2)">
                <div class="sidebar-icon"><i class="fas fa-solar-panel"></i></div>
                <div class="sidebar-text">Energy<br>Details</div>
            </div>
            <div class="sidebar-connector" id="conn2"></div>
            <div class="sidebar-item" data-step="3" onclick="goToStep(3)">
                <div class="sidebar-icon"><i class="fas fa-tachometer-alt"></i></div>
                <div class="sidebar-text">Metering &<br>Billing</div>
            </div>
            <div class="sidebar-connector" id="conn3"></div>
            <div class="sidebar-item" data-step="4" id="sidebarStep4" onclick="goToStep(4)">
                <div class="sidebar-icon"><i class="fas fa-tools"></i></div>
                <div class="sidebar-text">Installation<br>Details</div>
            </div>
        </div>

        <!-- Form Area -->
        <div class="form-area">

            <form id="p2pRegistrationForm">

                <!-- ═══════════ Step 1 : Personal Info ═══════════ -->
                <div class="form-section visible" id="stepContent1">
                    <h5>Personal Information</h5>
                    <p class="subtitle">Fill in your basic details to get started</p>

                    <div class="mb-3">
                        <label class="form-label">Select Role</label>
                        <select name="role" class="form-select" id="roleSelect" required>
                            <option value="">Choose Role</option>
                            <option value="buyer">Buyer</option>
                            <option value="seller">Seller</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" id="nameInput" required>
                        <div class="hint-text"><i class="fas fa-info-circle"></i> This name will be your Login ID</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Address for Communication</label>
                        <textarea name="address" class="form-control" rows="2" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Consumer Account No.</label>
                        <input type="text" name="consumer_account" id="consumerAccount" class="form-control" readonly required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Mobile No.</label>
                        <input type="tel" name="telephone" class="form-control" id="phoneInput" required>
                        <div class="hint-text"><i class="fas fa-info-circle"></i> This mobile number will be your Password</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">E-mail</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>

                    <div class="form-footer">
                        <div></div>
                        <button type="button" class="btn-next-custom" onclick="nextStep(1)">Continue <i class="fas fa-arrow-right ms-1"></i></button>
                    </div>
                </div>

                <!-- ═══════════ Step 2 : Energy Details ═══════════ -->
                <div class="form-section d-none" id="stepContent2">
                    <h5>Energy Details</h5>
                    <p class="subtitle">Provide your energy system information</p>

                    <!-- Seller only -->
                    <div class="mb-3" id="renewableSourceWrapper" style="display:none;">
                        <label class="form-label">Renewable Energy Source</label>
                        <select name="renewable_source" class="form-select">
                            <option value="">Select Source</option>
                            <option value="Solar">Solar</option>
                        </select>
                    </div>

                    <!-- Both -->
                    <div class="mb-3">
                        <label class="form-label">Category of Prosumer / Seller</label>
                        <select name="prosumer_category" class="form-select" required>
                            <option value="">Select Category</option>
                            <option value="Residential">Domestic</option>
                            <option value="Commercial">Commercial</option>
                            <option value="Industrial">Industrial</option>
                        </select>
                    </div>

                    <!-- Buyer only -->
                    <div class="mb-3" id="averageLoadWrapper" style="display:none;">
                        <label class="form-label">Average Load (kW)</label>
                        <input type="number" name="average_load" class="form-control" step="0.01" placeholder="e.g. 5.00">
                    </div>

                    <!-- Buyer only -->
                    <div class="mb-3" id="unitsRequiredWrapper" style="display:none;">
                        <label class="form-label">Units Required (kWh)</label>
                        <input type="number" name="units_required" class="form-control" step="0.01" placeholder="e.g. 150.00">
                    </div>

                    <!-- Seller only -->
                    <div class="mb-3" id="sanctionedLoadWrapper" style="display:none;">
                        <label class="form-label">Sanctioned Load (kW)</label>
                        <input type="number" name="sanctioned_load" class="form-control" step="0.01" placeholder="e.g. 10.00">
                    </div>

                    <!-- Seller only -->
                    <div class="mb-3" id="capacityWrapper" style="display:none;">
                        <label class="form-label">Capacity of Renewable Energy System (kW)</label>
                        <input type="number" name="capacity" class="form-control" step="0.01" placeholder="e.g. 8.00">
                    </div>

                    <div class="form-footer">
                        <button type="button" class="btn-back-custom" onclick="prevStep(2)"><i class="fas fa-arrow-left me-1"></i> Back</button>
                        <button type="button" class="btn-next-custom" onclick="nextStep(2)">Continue <i class="fas fa-arrow-right ms-1"></i></button>
                    </div>
                </div>

                <!-- ═══════════ Step 3 : Metering & Billing ═══════════ -->
                <div class="form-section d-none" id="stepContent3">
                    <h5>Metering & Billing</h5>
                    <p class="subtitle">Configure your metering and billing preferences</p>

                    <div class="mb-3">
                        <label class="form-label">Under ToD Billing System?</label>
                        <select name="tod_billing" class="form-select" required>
                            <option value="">Select</option>
                            <option value="Yes">Yes</option>
                            <option value="No">No</option>
                        </select>
                    </div>

                    <!-- Seller only -->
                    <div class="mb-3" id="meterPurchaseWrapper" style="display:none;">
                        <label class="form-label">Who will purchase the meter?</label>
                        <select name="meter_purchase" class="form-select" id="meterPurchaseInput">
                            <option value="">Select</option>
                            <option value="Utility">Utility Company</option>
                            <option value="Prosumer">Consumer</option>
                        </select>
                    </div>

                    <div class="form-footer">
                        <button type="button" class="btn-back-custom" onclick="prevStep(3)"><i class="fas fa-arrow-left me-1"></i> Back</button>
                        <!-- Seller: Continue to Step 4 -->
                        <button type="button" class="btn-next-custom" id="step3Continue" onclick="nextStep(3)" style="display:none;">Continue <i class="fas fa-arrow-right ms-1"></i></button>
                        <!-- Buyer: Submit directly -->
                        <button type="submit" class="btn-submit-custom" id="step3Submit" style="display:none;"><i class="fas fa-paper-plane me-1"></i> Submit Application</button>
                    </div>

                    <!-- Credentials Preview — Buyer only -->
                    <div class="credentials-preview" id="step3Credentials" style="display:none;">
                        <div class="cred-mini-card">
                            <div class="cred-mini-header"><i class="fas fa-key"></i> Your Login Credentials</div>
                            <div class="cred-mini-row">
                                <span class="cred-mini-label">Login ID</span>
                                <span class="cred-mini-value" id="previewId3">—</span>
                            </div>
                            <div class="cred-mini-row">
                                <span class="cred-mini-label">Password</span>
                                <span class="cred-mini-value" id="previewPass3">—</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ═══════════ Step 4 : Installation Details (Seller only) ═══════════ -->
                <div class="form-section d-none" id="stepContent4">
                    <h5>Installation Details</h5>
                    <p class="subtitle">Final step — confirm your installation information</p>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="drawings_attached" id="drawings">
                            <label class="form-check-label" for="drawings">Drawings attached</label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Demand / Supply per Day (kW)</label>
                        <input type="number" name="demand_supply" class="form-control" step="0.01" placeholder="e.g. 6.00">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Installation / Commissioning Date</label>
                        <input type="date" name="installation_date" class="form-control" id="installationDateInput">
                    </div>

                    <div class="form-footer">
                        <button type="button" class="btn-back-custom" onclick="prevStep(4)"><i class="fas fa-arrow-left me-1"></i> Back</button>
                        <button type="submit" class="btn-submit-custom" id="step4Submit"><i class="fas fa-paper-plane me-1"></i> Submit Application</button>
                    </div>

                    <!-- Credentials Preview — Seller -->
                    <div class="credentials-preview" id="step4Credentials">
                        <div class="cred-mini-card">
                            <div class="cred-mini-header"><i class="fas fa-key"></i> Your Login Credentials</div>
                            <div class="cred-mini-row">
                                <span class="cred-mini-label">Login ID</span>
                                <span class="cred-mini-value" id="previewId4">—</span>
                            </div>
                            <div class="cred-mini-row">
                                <span class="cred-mini-label">Password</span>
                                <span class="cred-mini-value" id="previewPass4">—</span>
                            </div>
                        </div>
                    </div>
                </div>

            </form>
            
            <!-- Login Link (Always Visible at Bottom) -->
            <div class="login-footer">
                Already have an account? <a href="login.php"><i class="fas fa-sign-in-alt me-1"></i>Login here</a>
            </div>
            
        </div>
    </div>

    <!-- Bottom Red Bar -->
    <div class="bottom-bar"></div>
</div>

<!-- Success Modal -->
<div class="modal-overlay" id="successModal">
    <div class="modal-box">
        <div class="success-icon">
            <i class="fas fa-check"></i>
        </div>
        <h5>Registration Successful!</h5>
        <p class="modal-subtitle">Your account has been created.<br>Please save your credentials below.</p>

        <div class="cred-card">
            <div class="cred-row">
                <span class="cred-label">Login ID</span>
                <span class="cred-value" id="modalId">—</span>
            </div>
            <div class="cred-row">
                <span class="cred-label">Password</span>
                <span class="cred-value" id="modalPass">—</span>
            </div>
        </div>

        <button class="btn-modal-close" onclick="closeModalAndRedirect()">Go to Login</button>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
let currentStep = 1;

/* ══════════════════════════════════════════
   Role Change Handler
   ══════════════════════════════════════════ */
 $('#roleSelect').on('change', function () {
    const role = $(this).val();

    // Reset everything first
    resetAllOptionalFields();

    if (!role) {
        // Clear account number
        $('#consumerAccount').val('');
        return;
    }

    // Generate consumer account
    generateConsumerAccount(role);

    if (role === 'buyer') {
        setupBuyerFlow();
    } else if (role === 'seller') {
        setupSellerFlow();
    }
});

function resetAllOptionalFields() {
    // Slide up all optional wrappers
    $('#renewableSourceWrapper').slideUp(200);
    $('#averageLoadWrapper').slideUp(200);
    $('#unitsRequiredWrapper').slideUp(200);
    $('#sanctionedLoadWrapper').slideUp(200);
    $('#capacityWrapper').slideUp(200);
    $('#meterPurchaseWrapper').slideUp(200);

    // Remove required from optional fields
    $('input[name="average_load"], input[name="units_required"], input[name="sanctioned_load"], input[name="capacity"], input[name="installation_date"], select[name="renewable_source"], select[name="meter_purchase"]').removeAttr('required');

    // Clear optional field values
    $('input[name="average_load"], input[name="units_required"], input[name="sanctioned_load"], input[name="capacity"], input[name="installation_date"]').val('');
    $('select[name="renewable_source"], select[name="meter_purchase"]').val('');

    // Hide step 4 sidebar + connector
    $('#sidebarStep4').hide();
    $('#conn3').hide();

    // Hide step 3 buttons
    $('#step3Continue').hide();
    $('#step3Submit').hide();
    $('#step3Credentials').hide();
}

function setupBuyerFlow() {
    // ── Energy Details: Category, Average Load, Units Required ──
    $('#averageLoadWrapper').slideDown(250);
    $('#unitsRequiredWrapper').slideDown(250);
    $('input[name="average_load"], input[name="units_required"]').attr('required', 'required');

    // ── Step 3: Submit directly (no step 4 for buyer) ──
    $('#step3Submit').slideDown(250);
    $('#step3Credentials').slideDown(250);

    // ── If currently on step 4, bounce back to step 3 ──
    if (currentStep === 4) {
        currentStep = 3;
        showStep(currentStep);
    }
}

function setupSellerFlow() {
    // ── Energy Details: Renewable Source, Category, Sanctioned Load, Capacity ──
    $('#renewableSourceWrapper').slideDown(250);
    $('#sanctionedLoadWrapper').slideDown(250);
    $('#capacityWrapper').slideDown(250);
    $('select[name="renewable_source"], input[name="sanctioned_load"], input[name="capacity"]').attr('required', 'required');

    // ── Step 3: Continue to step 4 ──
    $('#step3Continue').slideDown(250);

    // ── Show step 4 in sidebar ──
    $('#sidebarStep4').show();
    $('#conn3').show();

    // ── Meter purchase field ──
    $('#meterPurchaseWrapper').slideDown(250);
    $('select[name="meter_purchase"]').attr('required', 'required');
    
    // ── Installation date field (seller only) ──
    $('#installationDateInput').attr('required', 'required');
}

/* ══════════════════════════════════════════
   Consumer Account Generator
   ══════════════════════════════════════════ */
function generateConsumerAccount(role) {
    let random = Math.floor(1000 + Math.random() * 9000);
    let prefix = (role === 'buyer') ? 'B0' : 'S0';
    $('#consumerAccount').val(prefix + random);
}

/* ══════════════════════════════════════════
   Credentials Preview (real-time)
   ══════════════════════════════════════════ */
 $('#nameInput, #phoneInput').on('input', function () {
    updateCredentialsPreview();
});

function updateCredentialsPreview() {
    const name = $('#nameInput').val().trim();
    const phone = $('#phoneInput').val().trim();

    // Step 3 (buyer)
    const id3 = document.getElementById('previewId3');
    const pass3 = document.getElementById('previewPass3');
    if (id3 && pass3) {
        id3.textContent = name || '—';
        pass3.textContent = phone || '—';
        id3.classList.toggle('empty', !name);
        pass3.classList.toggle('empty', !phone);
    }

    // Step 4 (seller)
    const id4 = document.getElementById('previewId4');
    const pass4 = document.getElementById('previewPass4');
    if (id4 && pass4) {
        id4.textContent = name || '—';
        pass4.textContent = phone || '—';
        id4.classList.toggle('empty', !name);
        pass4.classList.toggle('empty', !phone);
    }
}

/* ══════════════════════════════════════════
   Toast Notification
   ══════════════════════════════════════════ */
function showToast(msg) {
    const toast = document.getElementById('toastMsg');
    toast.textContent = msg;
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 2800);
}

/* ══════════════════════════════════════════
   Step Navigation
   ══════════════════════════════════════════ */
function updateSidebar(step) {
    const role = $('#roleSelect').val();
    const maxStep = (role === 'buyer') ? 3 : 4;
    const items = document.querySelectorAll('.sidebar-item');
    const connectors = [
        document.getElementById('conn1'),
        document.getElementById('conn2'),
        document.getElementById('conn3')
    ];

    items.forEach((item, idx) => {
        const s = idx + 1;
        item.classList.remove('active', 'completed');

        // Skip step 4 sidebar if buyer
        if (s === 4 && role === 'buyer') return;

        if (s === step) {
            item.classList.add('active');
        } else if (s < step) {
            item.classList.add('completed');
        }
    });

    connectors.forEach((conn, idx) => {
        if (idx + 1 < step) {
            conn.classList.add('filled');
        } else {
            conn.classList.remove('filled');
        }
    });
}

function showStep(step) {
    for (let i = 1; i <= 4; i++) {
        const el = document.getElementById(`stepContent${i}`);
        if (i === step) {
            el.classList.remove('d-none');
            el.classList.add('visible');
        } else {
            el.classList.add('d-none');
            el.classList.remove('visible');
        }
    }
    updateSidebar(step);

    // Update credentials preview when stepping
    setTimeout(updateCredentialsPreview, 50);
}

function goToStep(step) {
    const role = $('#roleSelect').val();
    const maxStep = (role === 'buyer') ? 3 : 4;

    if (step <= currentStep && step <= maxStep) {
        currentStep = step;
        showStep(currentStep);
    }
}

function nextStep(step) {
    // Step 1: validate role selection
    if (step === 1) {
        if (!$('#roleSelect').val()) {
            showToast('Please select a Role first');
            $('#roleSelect').focus();
            return;
        }
    }

    const role = $('#roleSelect').val();
    const maxStep = (role === 'buyer') ? 3 : 4;

    if (step < maxStep) {
        currentStep = step + 1;
        showStep(currentStep);
    }
}

function prevStep(step) {
    if (step > 1) {
        currentStep = step - 1;
        showStep(currentStep);
    }
}

/* ══════════════════════════════════════════
   Form Submit
   ══════════════════════════════════════════ */
 $('#p2pRegistrationForm').submit(function (e) {
    e.preventDefault();

    const role = $('#roleSelect').val();
    const isValidStep = (role === 'buyer' && currentStep === 3) || (role === 'seller' && currentStep === 4);

    if (!isValidStep) return;

    const loginId = $('#nameInput').val().trim();
    const loginPass = $('#phoneInput').val().trim();

    if (!loginId || !loginPass) {
        showToast('Name and Mobile number are required for credentials');
        return;
    }

    $.post("../api/register_api.php", $(this).serialize(), function (res) {
        let data = JSON.parse(res);

        if (data.status === 'success') {
            // Show success modal with credentials
            $('#modalId').text(loginId);
            $('#modalPass').text(loginPass);
            $('#successModal').addClass('show');
        } else {
            showToast(data.message || 'Registration failed. Please try again.');
        }
    }).fail(function () {
        showToast('Server error. Please try again later.');
    });
});

function closeModalAndRedirect() {
    $('#successModal').removeClass('show');
    window.location.href = 'login.php';
}

/* ══════════════════════════════════════════
   Initialize
   ══════════════════════════════════════════ */
showStep(1);
</script>

</body>
</html>