<?php
// index.php (portal selector)
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Nang Chicken Market</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
<script src="js/theme.js"></script>
<link rel="stylesheet" href="css/style.css">
</head>

<body class="portal-page">

<button
    type="button"
    class="floating-theme-toggle theme-toggle"
    data-theme-toggle
    aria-label="Switch to dark theme"
    aria-pressed="false"
>
    <i class="fa-solid fa-moon" aria-hidden="true"></i>
    <span class="theme-toggle-label">Dark</span>
</button>

<div class="portal-wrap">

    <div class="portal-header">
        <img src="images/Nang-logo.png" alt="Nang Chicken Market">
        <h1>Nang Chicken Market</h1>
        <p>Choose your portal to continue</p>
    </div>

    <div class="portal-grid">

        <div class="portal-card">
            <div class="portal-title">
                <span class="portal-icon"><i class="fa-solid fa-user"></i></span>
                Customer Portal
            </div>

            <div class="portal-desc">
                Access the shopping experience. Log in to place orders or register for a new account.
            </div>

            <div class="portal-actions">
                <a href="customer/login.php" class="btn btn-login">
                    <i class="fa-solid fa-right-to-bracket"></i>
                    Login
                </a>

                <a href="customer/register.php" class="btn btn-register">
                    <i class="fa-solid fa-user-plus"></i>
                    Register
                </a>
            </div>
        </div>

        <div class="portal-card">
            <div class="portal-title">
                <span class="portal-icon"><i class="fa-solid fa-user-tie"></i></span>
                Staff Portal
            </div>

            <div class="portal-desc">
                Staff and administrators access the enterprise portal to manage orders, stock, and staff.
            </div>

            <div class="portal-actions">
                <a href="staff/enterprise_login.php" class="btn btn-staff">
                    <i class="fa-solid fa-lock"></i>
                    Login (Enterprise Portal)
                </a>
            </div>
        </div>

    </div>
</div>

</body>
</html>
