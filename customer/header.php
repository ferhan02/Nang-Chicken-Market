<?php
/* =========================
   SESSION & DATABASE
========================= */

/*
   Start session only if it is not already started.
   Needed to read login information.
*/
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
   Load database connection.
   Provides $conn for queries.
*/
require_once __DIR__ . '/../config.php';

/*
   Get logged-in customer ID from session.
   Will be null if user is not logged in.
*/
$customer_id = $_SESSION['customer_id'] ?? null;
$current_page = basename($_SERVER['PHP_SELF'] ?? '');

/* =========================
   CART ITEM COUNT
========================= */

/*
   Count how many items are in the cart.
   Used to show number next to cart icon.
*/
if ($customer_id) {

    $count_cart_items = $conn->prepare(
        "SELECT * FROM cart WHERE customer_id = ?"
    );
    $count_cart_items->execute([$customer_id]);

    /*
       rowCount() gives number of rows in result.
    */
    $cart_count = $count_cart_items->rowCount();

} else {

    /*
       If user is not logged in,
       cart count is always zero.
    */
    $cart_count = 0;
}

/* =========================
   CUSTOMER HEADER PROFILE
========================= */

$fetch_profile = null;
$header_profile_image = '../images/default-user.jpg';
$has_custom_profile_image = false;

if ($customer_id) {

    /*
       Get the logged-in customer's name
       and their own profile picture.
    */
    $select_profile = $conn->prepare(
        "SELECT customer_name, customer_image
         FROM customer
         WHERE customer_id = ?"
    );

    $select_profile->execute([$customer_id]);

    $fetch_profile = $select_profile->fetch(PDO::FETCH_ASSOC);

    if ($fetch_profile) {

        $header_customer_image =
            $fetch_profile['customer_image'] ?? '';

        if (
            $header_customer_image !== '' &&
            file_exists(
                __DIR__ .
                '/../images/' .
                basename($header_customer_image)
            )
        ) {

            $header_profile_image =
                '../images/' .
                rawurlencode(
                    basename($header_customer_image)
                );

            $has_custom_profile_image = true;
        }
    }
}
?>

<!--
   Font Awesome icons.
   Used for user icon and cart icon.
-->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">

<!-- =========================
     HEADER SECTION
========================= -->

<header class="header">
    <div class="flex">

        <!-- =========================
             LOGO
        ========================= -->

        <!--
           Website logo and brand name.
           Clicking this returns user to home page.
        -->
        <a href="home.php" class="logo header-logo">
            <img src="../images/Nang-logo.png" alt="Nang Chicken Market" class="site-logo">
            <span class="logo-text">Nang Chicken Market</span>
        </a>

        <!-- =========================
             NAVIGATION MENU
        ========================= -->

        <!--
           Main navigation links.
           Visible on all pages.
        -->
        <nav class="navbar" id="main-navigation" aria-label="Main navigation">
            <a href="home.php" class="<?= $current_page === 'home.php' ? 'is-active' : ''; ?>">Home</a>
            <a href="orders.php" class="<?= $current_page === 'orders.php' ? 'is-active' : ''; ?>">Orders</a>
            <a href="about.php" class="<?= $current_page === 'about.php' ? 'is-active' : ''; ?>">About</a>
            <a href="contact.php" class="<?= $current_page === 'contact.php' ? 'is-active' : ''; ?>">Contact</a>
        </nav>

        <!-- =========================
             HEADER ICONS
        ========================= -->

        <!--
           Icons area:
           - User icon opens profile menu
           - Cart icon shows item count
        -->
        <div class="icons">

            <button
                type="button"
                id="menu-btn"
                class="fa-solid fa-bars menu-toggle"
                aria-label="Open navigation"
                aria-controls="main-navigation"
                aria-expanded="false">
            </button>

            <!-- User icon / uploaded profile picture -->
            <?php if ($customer_id && $has_custom_profile_image): ?>

                <div
                    id="user-btn"
                    class="user-avatar-trigger"
                    aria-label="User profile"
                    role="button"
                    tabindex="0">

                    <img
                        src="<?= htmlspecialchars($header_profile_image); ?>"
                        alt="Profile Picture"
                    >

                </div>

            <?php else: ?>

                <div
                    id="user-btn"
                    class="fa-solid fa-user"
                    aria-label="User"
                    role="button"
                    tabindex="0">
                </div>

            <?php endif; ?>

            <!-- Cart icon with item count -->
            <a href="cart.php" class="fa-solid fa-cart-shopping cart-link" aria-label="Cart">
                <span class="cart-count">
                    <span class="cart-count-text">
                        <?= (int)$cart_count; ?>
                    </span>
            </a>

            <!-- Shared light/dark mode control. Kept last so it is always right-most. -->
            <button
                type="button"
                class="theme-toggle"
                data-theme-toggle
                aria-label="Use dark mode"
                aria-pressed="false">
                <i class="fa-solid fa-moon" aria-hidden="true"></i>
                <span class="theme-toggle-label">Dark</span>
            </button>
        </div>

        <!-- =========================
             PROFILE DROPDOWN
        ========================= -->

        <!--
           Profile dropdown panel.
           Content changes based on login state.
        -->
        <div class="profile" id="profileDropdown">

            <?php if ($customer_id): ?>

                <!-- User avatar -->
                <img src="<?= htmlspecialchars($header_profile_image); ?>" alt="User Profile Picture">

                <!-- Display customer name -->
                <p>
                    <?= htmlspecialchars($fetch_profile['customer_name'] ?? 'Customer'); ?>
                </p>

                <!-- Profile page link -->
                <a href="my_profile.php" class="btn">Profile</a>

                <!-- Logout link -->
                <a href="logout.php" class="delete-btn">Logout</a>

            <?php else: ?>

                <!--
                   Buttons shown when user is not logged in.
                -->
                <div class="flex-btn">
                    <a href="login.php" class="option-btn">Login</a>
                    <a href="register.php" class="option-btn">Register</a>
                </div>

            <?php endif; ?>

        </div>

    </div>
</header>
