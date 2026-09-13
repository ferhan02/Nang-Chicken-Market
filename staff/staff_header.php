<?php
/*
   This variable is used to highlight
   the active menu item in the staff navigation.

   Each staff page sets $activePage before including this file.
*/
$activePage = $activePage ?? '';
?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<header class="staff-topbar">
    <div class="staff-topbar-inner">

        <!--
            Brand / Logo section.
            Clicking this returns staff to the stock page.
        -->
        <a href="enterprise_stock.php" class="staff-header-logo">
            <img src="../images/Nang-logo.png"
                 alt="Nang Chicken Market"
                 class="site-logo">

            <span class="logo-text">Nang Chicken Market</span>
            <span class="staff-portal-label">Staff Portal</span>
        </a>

        <!--
            Staff navigation menu.
            Active link is highlighted using PHP logic.
        -->
        <nav class="staff-nav">
            <a href="enterprise_stock.php"
               class="<?= ($activePage === 'stock') ? 'is-active' : ''; ?>">
                <i class="fa-solid fa-box"></i> Stock
            </a>

            <a href="enterprise_sales.php"
               class="<?= ($activePage === 'sales') ? 'is-active' : ''; ?>">
                <i class="fa-solid fa-receipt"></i> Sales
            </a>

            <a href="enterprise_staff.php"
               class="<?= ($activePage === 'staff') ? 'is-active' : ''; ?>">
                <i class="fa-solid fa-users"></i> Staff
            </a>

            <button
                type="button"
                class="theme-toggle"
                data-theme-toggle
                aria-label="Use dark mode"
                aria-pressed="false">
                <i class="fa-solid fa-moon" aria-hidden="true"></i>
                <span class="theme-toggle-label">Dark</span>
            </button>
        </nav>

    </div>
</header>
