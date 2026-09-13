<?php
/*
   Start the session only if it is not already started.
   This file may be included in many staff pages,
   so this check avoids session warnings.
*/
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
   Get the logged-in staff username from session.
   This value is set during staff login.
   A fallback value is used if something goes wrong.
*/
$staffUsername = $_SESSION['valid_user'] ?? 'Unknown';
?>

<footer class="staff-footer">
    <div class="staff-footer-inner">

        <!--
            Left section:
            Shows company copyright
            and the currently logged-in staff user.
        -->
        <div class="staff-footer-left">
            <span>
                © 2026 Nang Chicken Market Sdn. Bhd.
                [201501009497 (1134832-W)]. All Rights Reserved.
            </span>

            <span class="staff-user">
                Logged in as: <?= htmlspecialchars($staffUsername) ?>
            </span>
        </div>

        <!--
            Logout button for staff.
            Uses a confirmation dialog to prevent accidental logout.
            Redirects to enterprise_logout.php.
        -->
        <a href="enterprise_logout.php"
           class="staff-logout"
           data-confirm="Log out from Staff Portal?"
           data-confirm-text="You will need to sign in again to manage the market."
           data-confirm-button="Log Out">
            <i class="fa-solid fa-right-from-bracket"></i>
            Log Out
        </a>

    </div>
</footer>

<script src="../js/confirmations.js"></script>
