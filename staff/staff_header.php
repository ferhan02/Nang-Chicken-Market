<?php
$activePage = $activePage ?? '';
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<header class="staff-topbar">
    <div class="staff-topbar-inner">

        <a href="enterprise_stock.php" class="staff-header-logo">
            <img
                src="../images/Nang-logo.png"
                alt="Nang Chicken Market"
                class="site-logo"
            >

            <span class="logo-text">Nang Chicken Market</span>
            <span class="staff-portal-label">Staff Portal</span>
        </a>

        <nav class="staff-nav" aria-label="Staff navigation">
            <a
                href="enterprise_stock.php"
                class="<?= ($activePage === 'stock') ? 'is-active' : ''; ?>"
            >
                <i class="fa-solid fa-box"></i>
                Stock
            </a>

            <a
                href="enterprise_sales.php"
                class="<?= ($activePage === 'sales') ? 'is-active' : ''; ?>"
            >
                <i class="fa-solid fa-receipt"></i>
                Sales
            </a>

            <a
                href="enterprise_staff.php"
                class="<?= ($activePage === 'staff') ? 'is-active' : ''; ?>"
            >
                <i class="fa-solid fa-users"></i>
                Staff
            </a>
        </nav>

        <div class="staff-header-actions">
            <button
                type="button"
                class="theme-toggle"
                data-theme-toggle
                aria-label="Switch to dark theme"
                aria-pressed="false"
            >
                <i class="fa-solid fa-moon" aria-hidden="true"></i>
                <span class="theme-toggle-label">Dark</span>
            </button>
        </div>

    </div>
</header>
