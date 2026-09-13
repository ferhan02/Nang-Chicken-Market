<?php
/* =========================
   SESSION & CONFIG
========================= */

/*
   Start session.
   Needed to check login and store messages.
*/
session_start();

/*
   Load database configuration.
   Provides database connection as $conn.
*/
require_once __DIR__ . '/../config.php';

/*
   Redirect user if not logged in.
   Home page requires customer login.
*/
if (!isset($_SESSION['customer_id'])) {
    header('Location: login.php');
    exit;
}

/*
   Logged-in customer ID.
*/
$customer_id = $_SESSION['customer_id'];

/* =========================
   ADD TO CART FEATURE
========================= */

/*
   Used to detect if item was added successfully.
   Helps trigger popup and cart drawer later.
*/
$added_to_cart = false;

/*
   Run when "Add to Cart" form is submitted.
*/
if (isset($_POST['add_to_cart'])) {

    /*
       Product ID from form.
    */
    $stock_id = (int) $_POST['stock_id'];

    /*
       Quantity from form.
       Sanitised and forced to minimum of 1.
    */
    $quantity = (int) filter_var($_POST['quantity'], FILTER_SANITIZE_NUMBER_INT);
    if ($quantity < 1) {
        $quantity = 1;
    }

    /* =========================
       STOCK CHECK (SERVER SIDE)
    ========================= */

    /*
       Get available stock from database.
       This ensures stock is checked on server,
       not only on the browser.
    */
    $check_stock = $conn->prepare(
        "SELECT stock_quantity FROM stock WHERE stock_id = ?"
    );
    $check_stock->execute([$stock_id]);
    $stock_row = $check_stock->fetch(PDO::FETCH_ASSOC);

    /*
       If product does not exist.
    */
    if (!$stock_row) {
        $_SESSION['success'] = "Product not found.";
        header('Location: home.php');
        exit;
    }

    /*
       Available stock quantity.
    */
    $available_stock = (int)$stock_row['stock_quantity'];

    /*
       Stop if product is out of stock.
    */
    if ($available_stock <= 0) {
        $_SESSION['success'] = "Sorry, this item is out of stock.";
        header('Location: home.php');
        exit;
    }

    /*
       Limit quantity to available stock.
    */
    if ($quantity > $available_stock) {
        $quantity = $available_stock;
    }

    /* =========================
       CHECK CART STATUS
    ========================= */

    /*
       Check if product is already in cart.
    */
    $check_cart = $conn->prepare(
        "SELECT quantity FROM cart WHERE customer_id = ? AND stock_id = ?"
    );
    $check_cart->execute([$customer_id, $stock_id]);

    /*
       If product is not in cart yet.
    */
    if ($check_cart->rowCount() === 0) {

        /*
           Insert new cart item.
        */
        $insert_cart = $conn->prepare(
            "INSERT INTO cart (customer_id, stock_id, quantity)
             VALUES (?, ?, ?)"
        );
        $insert_cart->execute([$customer_id, $stock_id, $quantity]);
        $added_to_cart = true;

    } else {

        /*
           Product already exists in cart.
           Increase quantity carefully.
        */
        $existing = $check_cart->fetch(PDO::FETCH_ASSOC);
        $existing_qty = (int)$existing['quantity'];
        $new_total = $existing_qty + $quantity;

        /*
           Prevent cart quantity from exceeding stock.
        */
        if ($new_total > $available_stock) {

            $quantity_to_add = $available_stock - $existing_qty;
            if ($quantity_to_add < 0) {
                $quantity_to_add = 0;
            }

            if ($quantity_to_add > 0) {

                $update_cart = $conn->prepare(
                    "UPDATE cart
                     SET quantity = quantity + ?
                     WHERE customer_id = ? AND stock_id = ?"
                );
                $update_cart->execute([
                    $quantity_to_add,
                    $customer_id,
                    $stock_id
                ]);
                $added_to_cart = true;

            } else {

                $_SESSION['success'] = "You already have the maximum stock in your cart.";
                header('Location: home.php');
                exit;
            }

        } else {

            /*
               Safe to increase quantity normally.
            */
            $update_cart = $conn->prepare(
                "UPDATE cart
                 SET quantity = quantity + ?
                 WHERE customer_id = ? AND stock_id = ?"
            );
            $update_cart->execute([
                $quantity,
                $customer_id,
                $stock_id
            ]);
            $added_to_cart = true;
        }
    }

    /*
       If item added, mark cart drawer to open.
       Drawer already has updated data after reload.
    */
    if ($added_to_cart) {
        $_SESSION['open_cart_drawer'] = 1;
    }
}

/* =========================
   PRODUCT DESCRIPTIONS
========================= */

/*
   Product descriptions stored in PHP.
   Avoids hardcoding text in HTML.
*/
$product_descriptions = [
    'Thighs'     => 'Tender and juicy chicken thighs, perfect for roasting or grilling.',
    'Drumsticks' => 'Flavourful drumsticks, ideal for BBQ nights or family dinners.',
    'Wings'      => 'Crispy wings, perfect for snacks, parties, or game nights.',
    'Breast'     => 'Lean chicken breast, perfect for healthy meals or stir-fry dishes.',
];

/* =========================
   IMAGE MAP FUNCTION
========================= */

/*
   Map stock ID to image filename.
   Keeps image logic in one place.
*/
function stock_image($stock_id) {
    switch ((int)$stock_id) {
        case 1: return 'thighs.png';
        case 2: return 'drumsticks.png';
        case 3: return 'wings.png';
        case 4: return 'breast.png';
        default: return 'placeholder.jpg';
    }
}

/* =========================
   LOAD PRODUCTS FROM DATABASE
========================= */

/*
   Load all products from stock table.
*/
$select_products = $conn->prepare("
    SELECT stock_id, stock_name, stock_price, stock_quantity
    FROM stock
    ORDER BY stock_id ASC
");
$select_products->execute();
$db_products = $select_products->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">

<!-- Responsive layout -->
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Home Page</title>

<!-- Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">

<!-- Global styles -->

<!-- Home page styles -->

<!-- Cart drawer styles (used only here) -->

<!-- SweetAlert popup -->
<link rel="stylesheet" href="../css/style.css">

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../js/theme.js"></script>
</head>

<body class="home-page">

<?php include 'header.php'; ?>

<!-- =========================
     HERO SECTION
========================= -->
<div class="home-bg">
    <section class="home">
        <div class="content">
            <span class="hero-kicker"><i class="fa-solid fa-sparkles"></i> Clean Cuts. Honest Quality. Real Chicken.</span>
            <h3>Fresh, High-Quality Chicken You Can Trust Every Day</h3>
            <p>Quality halal chicken prepared for everyday cooking.</p>
            <div class="hero-actions">
                <a href="#products" class="btn"><i class="fa-solid fa-basket-shopping"></i> Shop Fresh Cuts</a>
                <a href="about.php" class="option-btn hero-btn-outline">Our Story</a>
            </div>
            <div class="hero-trust" aria-label="Service highlights">
                <span><i class="fa-solid fa-circle-check"></i> Halal</span>
                <span><i class="fa-solid fa-snowflake"></i> Fresh daily</span>
                <span><i class="fa-solid fa-shield-heart"></i> Hygienic handling</span>
            </div>
        </div>
    </section>
</div>

<!-- =========================
     PRODUCT LIST
========================= -->
<section class="products" id="products">
<div class="section-heading">
    <span class="eyebrow">Fresh from our market</span>
    <h1 class="title">Our Chicken Products</h1>
    <p class="section-subtitle">Choose your cut, set the quantity, and we will handle the rest.</p>
</div>

<div class="box-container">

<?php foreach ($db_products as $product):

    $stock_id = (int)$product['stock_id'];
    $name = $product['stock_name'];

    /*
       Price comes from database.
    */
    $price = (float)$product['stock_price'];

    /*
       Available quantity.
    */
    $qty_available = (int)$product['stock_quantity'];

    /*
       Description and image.
    */
    $desc = $product_descriptions[$name] ?? 'High quality chicken for your meals.';
    $img  = stock_image($stock_id);

    /*
       Out-of-stock check.
    */
    $is_out = ($qty_available <= 0);

    /*
       Max quantity allowed.
    */
    $data_max = $is_out ? 1 : $qty_available;

    /*
       Display-only original price.
    */
    $original_price = $price * 1.1;
?>

    <form method="POST" class="box">

        <div class="product-image">
            <img src="../images/<?= htmlspecialchars($img); ?>" alt="<?= htmlspecialchars($name); ?>">
        </div>

        <div class="name"><?= htmlspecialchars($name); ?></div>
        <div class="desc"><?= htmlspecialchars($desc); ?></div>

        <div class="price-container">
            <span class="original-price">RM <?= number_format($original_price, 2); ?></span>
            <span class="selling-price">RM <?= number_format($price, 2); ?></span>
        </div>

        <!-- Hidden product ID -->
        <input type="hidden" name="stock_id" value="<?= $stock_id; ?>">

        <!-- Quantity control -->
        <div class="qty-control" data-max="<?= (int)$data_max; ?>">
            <button type="button" class="qty-btn minus">−</button>

            <input
                type="number"
                name="quantity"
                value="1"
                min="1"
                max="<?= (int)$data_max; ?>"
                class="qty-input"
                <?= $is_out ? 'disabled' : ''; ?>
                required
            >

            <button type="button" class="qty-btn plus <?= $is_out ? 'is-disabled' : ''; ?>">+</button>
        </div>

        <?php if ($is_out): ?>
            <div class="cart-stock-note">Out of stock</div>
            <button type="button" class="btn disabled" disabled>Out of Stock</button>
        <?php else: ?>
            <input type="submit" name="add_to_cart" value="Add to Cart" class="btn">
        <?php endif; ?>

    </form>

<?php endforeach; ?>

</div>
</section>

<?php include 'footer.php'; ?>

<!-- =========================
     CART DRAWER
========================= -->

<?php include 'cart_drawer.php'; ?>

<!-- =========================
     ALERTS
========================= -->

<script>
<?php if ($added_to_cart): ?>
Swal.fire({
    icon: 'success',
    title: 'Added to Cart',
    text: 'The product has been added to your cart.',
    timer: 1500,
    showConfirmButton: false
});
<?php endif; ?>
</script>

<?php if (!empty($_SESSION['success'])): ?>
<script>
Swal.fire({
    icon: 'info',
    title: 'Notice',
    text: '<?= $_SESSION['success']; ?>',
    timer: 2000,
    showConfirmButton: false
});
</script>
<?php unset($_SESSION['success']); endif; ?>

<!-- Global JS -->
<script src="../js/script.js"></script>

<!-- Cart drawer JS (used only here) -->
<script src="../js/cart_drawer.js"></script>

<?php if (!empty($_SESSION['open_cart_drawer'])):
    unset($_SESSION['open_cart_drawer']); ?>
<script>
window.addEventListener('DOMContentLoaded', () => {
    /*
       Open cart drawer automatically
       after adding item.
    */
    if (window.NC_CartDrawer) {
        window.NC_CartDrawer.open();
    }
});
</script>
<?php endif; ?>

</body>
</html>
