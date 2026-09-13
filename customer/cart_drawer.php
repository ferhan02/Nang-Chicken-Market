<?php
// This file is included from header.php
// Assumes:
// - $conn (PDO database connection) already exists via config.php
// - Session has already been started before this file is included

// Resolve customer ID safely:
// - Use existing $customer_id if already defined
// - Otherwise fall back to session value
$customer_id = $customer_id ?? ($_SESSION['customer_id'] ?? null);

// Initialize variables used by the mini cart drawer
// These defaults ensure the UI renders safely even with no data
$mini_items = [];
$mini_total = 0.0;
$mini_item_count = 0;

// Only run cart-related queries if user is logged in
if ($customer_id) {

    /*
        Mini cart items query

        This retrieves cart items joined with stock details.
        It is designed for display purposes only (summary view),
        not for full cart editing.
    */

    try {
        /*
            Primary query:
            - Assumes `stock_image` exists in stock table
            - Fetches item name, price, image, and quantity
        */
        $stmt = $conn->prepare("
            SELECT
                c.stock_id,
                s.stock_name,
                s.stock_price,
                s.stock_image,
                c.quantity
            FROM cart c
            JOIN stock s ON c.stock_id = s.stock_id
            WHERE c.customer_id = ?
            ORDER BY c.cart_id DESC
        ");
        $stmt->execute([$customer_id]);
        $mini_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (Throwable $e) {

        /*
            Fallback query:
            - Used if `stock_image` column does not exist
            - Keeps compatibility with earlier database structure
            - Still allows mini cart to function normally
        */
        $stmt = $conn->prepare("
            SELECT
                c.stock_id,
                s.stock_name,
                s.stock_price,
                c.quantity
            FROM cart c
            JOIN stock s ON c.stock_id = s.stock_id
            WHERE c.customer_id = ?
            ORDER BY c.cart_id DESC
        ");
        $stmt->execute([$customer_id]);
        $mini_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /*
        Subtotal calculation

        Uses SQL aggregation instead of PHP looping:
        - More efficient
        - Ensures price consistency with database
    */
    $sum = $conn->prepare("
        SELECT COALESCE(SUM(c.quantity * s.stock_price), 0)
        FROM cart c
        JOIN stock s ON c.stock_id = s.stock_id
        WHERE c.customer_id = ?
    ");
    $sum->execute([$customer_id]);
    $mini_total = (float)($sum->fetchColumn() ?? 0);

    /*
        Total item count calculation

        This counts total quantity (not number of rows),
        which is more meaningful for cart indicators.
    */
    $cnt = $conn->prepare("
        SELECT COALESCE(SUM(quantity), 0)
        FROM cart
        WHERE customer_id = ?
    ");
    $cnt->execute([$customer_id]);
    $mini_item_count = (int)($cnt->fetchColumn() ?? 0);
}
?>

<!-- Overlay used to dim background when cart drawer is open -->
<!-- aria-hidden improves accessibility when drawer is closed -->
<div class="cart-drawer-overlay" id="cartDrawerOverlay" aria-hidden="true"></div>

<!-- Cart drawer sidebar -->
<!-- Implemented as an <aside> because it is secondary content -->
<aside class="cart-drawer" id="cartDrawer" aria-hidden="true">

    <!-- Drawer header: title and close button -->
    <div class="cart-drawer-header">
        <div class="cart-drawer-title">
            <i class="fa-solid fa-bag-shopping"></i>
            <h3>Your Cart</h3>
        </div>

        <!-- Close button for drawer -->
        <button
            class="cart-drawer-close"
            id="cartDrawerClose"
            aria-label="Close cart drawer"
            type="button"
        >
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <!-- Visual separator -->
    <div class="cart-drawer-divider"></div>

    <!-- Drawer body content -->
    <div class="cart-drawer-body" id="cartDrawerBody">

        <!-- Case 1: User not logged in -->
        <?php if (!$customer_id): ?>
            <p class="cart-drawer-empty">
                Please <a href="login.php">login</a> to view your cart.
            </p>

        <!-- Case 2: Logged in but cart is empty -->
        <?php elseif (empty($mini_items)): ?>
            <p class="cart-drawer-empty">Your cart is empty.</p>

        <!-- Case 3: Cart has items -->
        <?php else: ?>

            <!-- List of cart items -->
            <ul class="cart-drawer-items">

                <?php foreach ($mini_items as $it):

                    // Quantity of current item
                    $qty  = (int)$it['quantity'];

                    // Unit price
                    $unit = (float)$it['stock_price'];

                    // Line total (quantity × unit price)
                    $line = $qty * $unit;

                    // Image handling:
                    // - Allows cart to render even if image is missing
                    $img = $it['stock_image'] ?? null;
                    $hasImg = is_string($img) && $img !== '';
                ?>

                    <!-- Individual cart item -->
                    <li class="cart-drawer-item">

                        <!-- Entire item is clickable (UX choice) -->
                        <!-- data-scroll-target used by JS to scroll page -->
                        <button
                            type="button"
                            class="cart-drawer-item-link"
                            data-scroll-target=".products"
                            aria-label="View products section"
                        >

                            <!-- Optional thumbnail image -->
                            <?php if ($hasImg): ?>
                                <span class="cart-drawer-thumb">
                                    <img
                                        src="images/<?= htmlspecialchars($img); ?>"
                                        alt="<?= htmlspecialchars($it['stock_name']); ?>"
                                    >
                                </span>
                            <?php endif; ?>

                            <!-- Item name and pricing info -->
                            <span class="cart-drawer-item-main">

                                <span class="cart-drawer-item-name">
                                    <?= htmlspecialchars($it['stock_name']); ?>
                                </span>

                                <span class="cart-drawer-item-meta">
                                    <span><?= $qty; ?> × RM<?= number_format($unit, 2); ?></span>
                                    <b>RM<?= number_format($line, 2); ?></b>
                                </span>

                            </span>

                            <!-- Decorative arrow icon -->
                            <span class="cart-drawer-item-icon" aria-hidden="true">
                                <i class="fa-solid fa-angle-right"></i>
                            </span>

                        </button>
                    </li>
                <?php endforeach; ?>
            </ul>

            <!-- Divider between items and summary -->
            <div class="cart-drawer-divider"></div>

            <!-- Cart summary section -->
            <div class="cart-drawer-summary">

                <!-- Total quantity of items -->
                <div class="cart-drawer-row">
                    <span>Total items</span>
                    <b><?= (int)$mini_item_count; ?></b>
                </div>

                <div class="cart-drawer-divider thin"></div>

                <!-- Subtotal amount -->
                <div class="cart-drawer-row">
                    <span>Subtotal</span>
                    <b>RM<?= number_format($mini_total, 2); ?></b>
                </div>
            </div>

            <!-- Divider before actions -->
            <div class="cart-drawer-divider"></div>

            <!-- Cart action buttons -->
            <div class="cart-drawer-actions">
                <a href="cart.php" class="cart-drawer-btn outline">
                    <i class="fa-solid fa-cart-shopping"></i>
                    View Cart
                </a>

                <a href="checkout.php" class="cart-drawer-btn primary">
                    <i class="fa-solid fa-credit-card"></i>
                    Checkout
                </a>
            </div>

        <?php endif; ?>
    </div>
</aside>
