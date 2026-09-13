<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config.php';

$customer_id = $_SESSION['customer_id'] ?? null;
$current_page = basename($_SERVER['PHP_SELF'] ?? '');

if ($customer_id) {
    $count_cart_items = $conn->prepare(
        "SELECT * FROM cart WHERE customer_id = ?"
    );
    $count_cart_items->execute([$customer_id]);
    $cart_count = $count_cart_items->rowCount();
} else {
    $cart_count = 0;
}

$fetch_profile = null;
$header_profile_image = '../images/default-user.jpg';
$has_custom_profile_image = false;

if ($customer_id) {
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

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">

<header class="header">
    <div class="flex">

        <a href="home.php" class="logo header-logo">
            <img
                src="../images/Nang-logo.png"
                alt="Nang Chicken Market"
                class="site-logo"
            >
            <span class="logo-text">Nang Chicken Market</span>
        </a>

        <nav
            class="navbar"
            id="main-navigation"
            aria-label="Main navigation"
        >
            <a
                href="home.php"
                class="<?= $current_page === 'home.php' ? 'is-active' : ''; ?>"
            >
                Home
            </a>

            <a
                href="orders.php"
                class="<?= $current_page === 'orders.php' ? 'is-active' : ''; ?>"
            >
                Orders
            </a>

            <a
                href="about.php"
                class="<?= $current_page === 'about.php' ? 'is-active' : ''; ?>"
            >
                About
            </a>

            <a
                href="contact.php"
                class="<?= $current_page === 'contact.php' ? 'is-active' : ''; ?>"
            >
                Contact
            </a>
        </nav>

        <div class="icons">

            <button
                type="button"
                id="menu-btn"
                class="fa-solid fa-bars menu-toggle"
                aria-label="Open navigation"
                aria-controls="main-navigation"
                aria-expanded="false"
            ></button>

            <?php if ($customer_id && $has_custom_profile_image): ?>

                <div
                    id="user-btn"
                    class="user-avatar-trigger"
                    aria-label="User profile"
                    role="button"
                    tabindex="0"
                >
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
                    tabindex="0"
                ></div>

            <?php endif; ?>

            <a
                href="cart.php"
                class="fa-solid fa-cart-shopping cart-link"
                aria-label="Cart"
            >
                <span class="cart-count">
                    <span class="cart-count-text">
                        <?= (int)$cart_count; ?>
                    </span>
                </span>
            </a>

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

        <div class="profile" id="profileDropdown">

            <?php if ($customer_id): ?>

                <img
                    src="<?= htmlspecialchars($header_profile_image); ?>"
                    alt="User Profile Picture"
                >

                <p>
                    <?= htmlspecialchars($fetch_profile['customer_name'] ?? 'Customer'); ?>
                </p>

                <a href="my_profile.php" class="btn">Profile</a>
                <a href="logout.php" class="delete-btn">Logout</a>

            <?php else: ?>

                <div class="flex-btn">
                    <a href="login.php" class="option-btn">Login</a>
                    <a href="register.php" class="option-btn">Register</a>
                </div>

            <?php endif; ?>

        </div>

    </div>
</header>
