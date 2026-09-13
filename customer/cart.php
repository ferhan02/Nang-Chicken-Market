<?php
/* =========================
   CONFIG & SESSION
========================= */

/*
   Load global configuration:
   - Database connection ($conn)
   - Shared settings/constants
*/
require_once __DIR__ . '/../config.php';

/*
   Start session only if it hasn't been started yet.
   This avoids PHP warnings and ensures session data is available.
*/
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
   Retrieve logged-in customer ID from session.
   This page requires authentication.
*/
$customer_id = $_SESSION['customer_id'] ?? null;

/*
   Redirect unauthenticated users to login page.
   Cart operations should only be accessible to logged-in users.
*/
if (!$customer_id) {
    header('Location: login.php');
    exit;
}

/* =========================
   DELETE SINGLE ITEM
========================= */

/*
   Handles removal of a single cart item.
   Triggered via GET request with ?delete=cart_id
*/
if (isset($_GET['delete'])) {
    $cart_id = (int) $_GET['delete'];

    /*
       Prepared statement ensures:
       - SQL injection protection
       - Item belongs to the current user
    */
    $delete_item = $conn->prepare(
        "DELETE FROM cart WHERE cart_id = ? AND customer_id = ?"
    );
    $delete_item->execute([$cart_id, $customer_id]);

    /*
       Store success message in session
       so it can be displayed after redirect.
    */
    $_SESSION['success'] = "Item removed from cart.";

    /*
       Redirect prevents duplicate deletions on page refresh.
    */
    header('Location: cart.php');
    exit;
}

/* =========================
   DELETE ALL ITEMS
========================= */

/*
   Clears entire cart for the logged-in user.
   Triggered via GET request with ?delete_all=1
*/
if (isset($_GET['delete_all'])) {

    $delete_all = $conn->prepare(
        "DELETE FROM cart WHERE customer_id = ?"
    );
    $delete_all->execute([$customer_id]);

    $_SESSION['success'] = "All items removed from cart.";

    header('Location: cart.php');
    exit;
}

/* =========================
   UPDATE QUANTITY
========================= */

/*
   Updates quantity of a single cart item.
   Triggered by POST form submission.
*/
if (isset($_POST['update_qty'])) {

    $cart_id = (int) $_POST['cart_id'];
    $new_qty = (int) $_POST['p_qty'];

    /*
       Only update if quantity is valid (> 0).
       Quantity limits are enforced in the UI as well.
    */
    if ($new_qty > 0) {

        $update_qty = $conn->prepare(
            "UPDATE cart SET quantity = ? WHERE cart_id = ? AND customer_id = ?"
        );
        $update_qty->execute([$new_qty, $cart_id, $customer_id]);

        $_SESSION['success'] = "Cart quantity updated.";
    }

    header('Location: cart.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">

<!-- Enables responsive layout on mobile devices -->
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Shopping Cart</title>

<!-- Font Awesome icons for UI elements -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

<!-- SweetAlert2 library for confirmation dialogs and alerts -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Shared global styles -->

<!-- Cart-specific styles -->
<script src="../js/theme.js"></script>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>

<!-- Site-wide header -->
<?php include 'header.php'; ?>

<!-- =========================
     SHOPPING CART SECTION
========================= -->
<section class="shopping-cart">

   <div class="page-heading">
      <a href="home.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Back to Shop</a>
      <span class="eyebrow">Shopping bag</span>
   </div>
   <h1 class="title">Your Cart</h1>

   <div class="box-container">
   <?php
      /*
         Grand total accumulator.
         Calculated server-side for accuracy.
      */
      $grand_total = 0;

      /*
         Retrieve cart items joined with stock data:
         - Stock price
         - Available quantity
      */
      $select_cart = $conn->prepare("
         SELECT 
            cart.cart_id,
            cart.quantity AS cart_quantity,
            stock.stock_id,
            stock.stock_name,
            stock.stock_price,
            stock.stock_quantity
         FROM cart
         JOIN stock ON cart.stock_id = stock.stock_id
         WHERE cart.customer_id = ?
      ");
      $select_cart->execute([$customer_id]);

      /*
         Render cart items if available.
      */
      if ($select_cart->rowCount() > 0) {
         while ($item = $select_cart->fetch(PDO::FETCH_ASSOC)) {

            /*
               Calculate subtotal per item.
            */
            $sub_total = (float)$item['stock_price'] * (int)$item['cart_quantity'];
            $grand_total += $sub_total;

            /*
               Map stock IDs to image files.
               This avoids storing image paths in the database.
            */
            $stock_id_str = str_pad((string)$item['stock_id'], 3, '0', STR_PAD_LEFT);
            $image_file = '../images/placeholder.jpg';
            switch ($stock_id_str) {
                case '001': $image_file = '../images/breast.png'; break;
                case '002': $image_file = '../images/drumsticks.png'; break;
                case '003': $image_file = '../images/thighs.png'; break;
                case '004': $image_file = '../images/wings.png'; break;
            }

            /*
               Maximum quantity allowed based on stock availability.
            */
            $max_qty = (int)$item['stock_quantity'];
   ?>
      <form method="POST" class="cart-box">

         <!-- Remove item button (uses JS confirmation) -->
         <button type="button" class="cart-remove" onclick="confirmDelete(<?= (int)$item['cart_id']; ?>)">
            <i class="fas fa-times"></i>
         </button>

         <div class="cart-item">
            <div class="cart-item-img">
               <img src="<?= $image_file; ?>" alt="<?= htmlspecialchars($item['stock_name']); ?>">
            </div>

            <div class="cart-item-info">

               <div class="cart-item-top">
                  <div>
                     <div class="cart-item-name"><?= htmlspecialchars($item['stock_name']); ?></div>
                     <div class="cart-item-price">RM <?= number_format((float)$item['stock_price'], 2); ?></div>
                  </div>

                  <div class="cart-item-subtotal">
                     Subtotal
                     <span>RM <?= number_format($sub_total, 2); ?></span>
                  </div>
               </div>

               <!-- Hidden cart ID used during quantity update -->
               <input type="hidden" name="cart_id" value="<?= (int)$item['cart_id']; ?>">

               <div class="cart-actions">

                  <!-- Custom quantity control -->
                  <!-- data-max is used by JavaScript to enforce limits -->
                  <div class="qty-control" data-max="<?= $max_qty; ?>">
                     <button type="button" class="qty-btn minus" aria-label="Decrease quantity">−</button>

                     <input
                        type="number"
                        name="p_qty"
                        class="qty-input"
                        min="1"
                        max="<?= $max_qty; ?>"
                        value="<?= (int)$item['cart_quantity']; ?>"
                        inputmode="numeric"
                        required
                     >

                     <button type="button" class="qty-btn plus" aria-label="Increase quantity">+</button>
                  </div>

                  <!-- Submit quantity update -->
                  <input type="submit" name="update_qty" value="Update" class="option-btn cart-update-btn">
               </div>

               <!-- Stock warning if quantity exceeds available stock -->
               <?php if ((int)$item['cart_quantity'] > $max_qty): ?>
                  <div class="cart-stock-note">
                     Not enough stock available (max: <?= $max_qty; ?>)
                  </div>
               <?php endif; ?>

            </div>
         </div>

      </form>
   <?php
         }
      } else {
         /*
            Message shown when cart has no items.
         */
         echo '<p class="empty">Your cart is empty</p>';
      }
   ?>
   </div>

   <!-- =========================
        CART TOTAL & ACTIONS
   ========================= -->
   <div class="cart-total">

      <p>
         Grand total :
         <span>RM <?= number_format((float)$grand_total, 2); ?></span>
      </p>

      <div class="cart-total-actions">

         <a href="home.php" class="option-btn">Continue Shopping</a>

         <!-- Delete all uses JS confirmation -->
         <a href="javascript:void(0);"
            class="delete-btn <?= ($grand_total > 0) ? '' : 'disabled'; ?>"
            onclick="confirmDeleteAll()">
            Delete All
         </a>

         <!-- Checkout disabled if cart is empty -->
         <a href="checkout.php"
            class="btn <?= ($grand_total > 0) ? '' : 'disabled'; ?>">
            Proceed To Checkout
         </a>
      </div>
   </div>

</section>

<?php include 'footer.php'; ?>

<!-- =========================
     JAVASCRIPT CONFIRMATIONS
========================= -->
<script>
/*
   Confirmation dialog for deleting a single cart item.
   Uses SweetAlert2 for better UX.
*/
function confirmDelete(cartId) {
    Swal.fire({
        title: 'Remove item?',
        text: 'This item will be removed from your cart.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, remove it'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'cart.php?delete=' + cartId;
        }
    });
}

/*
   Confirmation dialog for clearing entire cart.
*/
function confirmDeleteAll() {
    Swal.fire({
        title: 'Clear cart?',
        text: 'All items will be removed from your cart.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, clear all'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'cart.php?delete_all=1';
        }
    });
}
</script>

<!-- =========================
     SUCCESS MESSAGE ALERT
========================= -->
<?php if (!empty($_SESSION['success'])): ?>
<script>
Swal.fire({
    icon: 'success',
    title: 'Success',
    text: '<?= addslashes($_SESSION['success']); ?>',
    timer: 1800,
    showConfirmButton: false
});
</script>
<?php unset($_SESSION['success']); endif; ?>

<!-- Global site JavaScript -->
<script src="../js/script.js"></script>
</body>
</html>
