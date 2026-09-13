<?php
/* =========================
   CONFIG & SESSION
========================= */

/*
   Start output buffering.
   PHP will hold all output first,
   so redirects (header()) still work later.
*/
ob_start();

/*
   Load configuration file.
   This provides database connection ($conn).
*/
require_once __DIR__ . '/../config.php';

/*
   Start session if it is not already started.
   Needed for login and order data.
*/
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
   Set timezone so all dates and times
   follow Malaysia time.
*/
date_default_timezone_set('Asia/Kuala_Lumpur');

/* =========================
   AUTHENTICATION CHECK
========================= */

/*
   Get customer ID from session.
*/
$customer_id = $_SESSION['customer_id'] ?? null;

/*
   Redirect to login page if user is not logged in.
   Checkout page must be protected.
*/
if (!$customer_id) {
    header('Location: login.php');
    exit;
}

/* =========================
   FETCH CUSTOMER DATA
========================= */

/*
   Fetch customer information.
   Used to pre-fill checkout form.
*/
$select_customer = $conn->prepare("
    SELECT customer_name, customer_phone, customer_email, customer_address
    FROM customer
    WHERE customer_id = ?
");
$select_customer->execute([$customer_id]);

/*
   Store customer data safely.
*/
$customer = $select_customer->fetch(PDO::FETCH_ASSOC);

$name    = $customer['customer_name'] ?? '';
$number  = $customer['customer_phone'] ?? '';
$email   = $customer['customer_email'] ?? '';
$address = $customer['customer_address'] ?? '';

/* =========================
   PLACE ORDER LOGIC
========================= */

/*
   Used to store error messages.
*/
$error = '';

/*
   Check if checkout form is submitted
   and required fields exist.
*/
$is_checkout_post = ($_SERVER['REQUEST_METHOD'] === 'POST')
    && isset($_POST['name'], $_POST['number'], $_POST['email'], $_POST['method'], $_POST['address']);

if ($is_checkout_post) {

    /*
       Clean user input.
    */
    $name    = trim($_POST['name']);
    $number  = trim($_POST['number']);
    $email   = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

    /*
       Payment method:
       0 = Cash on Delivery
       1 = Card
    */
    $method  = isset($_POST['method']) ? (int)$_POST['method'] : -1;

    $address = trim($_POST['address']);

    /*
       Set order date.
    */
    $sale_date    = date('Y-m-d');
    $payment_date = $sale_date;

    /*
       Basic validation checks.
    */
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email address!";
    } elseif (!preg_match('/^\d+$/', $number)) {
        $error = "Invalid phone number!";
    } elseif (!in_array($method, [0,1], true)) {
        $error = "Please select a valid payment method.";
    }

    if (empty($error)) {

        /*
           Fetch all items in user's cart.
        */
        $cart_query = $conn->prepare("
            SELECT c.stock_id, c.quantity, s.stock_name, s.stock_price, s.stock_quantity
            FROM cart c
            JOIN stock s ON c.stock_id = s.stock_id
            WHERE c.customer_id = ?
        ");
        $cart_query->execute([$customer_id]);
        $cart_items = $cart_query->fetchAll(PDO::FETCH_ASSOC);

        /*
           Stop if cart is empty.
        */
        if (empty($cart_items)) {
            $error = "Your cart is empty!";
        } else {

            /*
               Check stock availability before ordering.
            */
            $out_of_stock = [];
            foreach ($cart_items as $item) {
                if ((int)$item['quantity'] > (int)$item['stock_quantity']) {
                    $out_of_stock[] = $item['stock_name'];
                }
            }

            if (!empty($out_of_stock)) {
                $error = "The following items are out of stock: " . implode(", ", $out_of_stock);
            } else {

                try {
                    /*
                       Start database transaction.
                       All queries must succeed together.
                    */
                    $conn->beginTransaction();

                    /*
                       Update customer info with checkout data.
                    */
                    $update_customer = $conn->prepare("
                        UPDATE customer
                        SET customer_name = ?, customer_phone = ?, customer_email = ?, customer_address = ?
                        WHERE customer_id = ?
                    ");
                    $update_customer->execute([$name, $number, $email, $address, $customer_id]);

                    /* =========================
                       RECEIPT NUMBER
                    ========================= */

                    /*
                       Generate a unique receipt number.
                       Example format: NC-9F3A2C7D
                    */
                    $receipt_no = '';
                    for ($i = 0; $i < 10; $i++) {
                        $candidate = 'NC-' . strtoupper(bin2hex(random_bytes(4)));
                        $check = $conn->prepare("
                            SELECT 1 FROM orders WHERE receipt_no = ? LIMIT 1
                        ");
                        $check->execute([$candidate]);
                        if (!$check->fetchColumn()) {
                            $receipt_no = $candidate;
                            break;
                        }
                    }

                    if ($receipt_no === '') {
                        throw new Exception('Receipt generation failed');
                    }

                    /*
                       Prepare SQL statements once.
                       They will be reused in the loop.
                    */
                    $insert_order = $conn->prepare("
                        INSERT INTO orders
                        (customer_id, staff_id, stock_id, receipt_no, sale_date, sale_quantitysold, sale_total)
                        VALUES (?, 1, ?, ?, ?, ?, ?)
                    ");

                    $insert_payment = $conn->prepare("
                        INSERT INTO payment
                        (orders_id, customer_id, payment_date, payment_amount, payment_method)
                        VALUES (?, ?, ?, ?, ?)
                    ");

                    $update_stock = $conn->prepare("
                        UPDATE stock
                        SET stock_quantity = stock_quantity - ?
                        WHERE stock_id = ?
                    ");

                    /*
                       Prepare receipt data.
                    */
                    $receipt_items = [];
                    $receipt_total = 0;

                    /*
                       Process each cart item.
                    */
                    foreach ($cart_items as $item) {

                        $qty   = (int)$item['quantity'];
                        $price = (float)$item['stock_price'];
                        $total = $qty * $price;

                        /*
                           Insert order record.
                        */
                        $insert_order->execute([
                            $customer_id,
                            (int)$item['stock_id'],
                            $receipt_no,
                            $sale_date,
                            $qty,
                            $total
                        ]);

                        /*
                           Get ID of inserted order.
                        */
                        $orders_id = (int)$conn->lastInsertId();

                        /*
                           Insert payment record.
                        */
                        $insert_payment->execute([
                            $orders_id,
                            $customer_id,
                            $payment_date,
                            $total,
                            $method
                        ]);

                        /*
                           Reduce stock quantity.
                        */
                        $update_stock->execute([$qty, (int)$item['stock_id']]);

                        /*
                           Build receipt summary.
                        */
                        $receipt_total += $total;
                        $receipt_items[] = [
                            'orders_id'  => $orders_id,
                            'stock_id'   => (int)$item['stock_id'],
                            'stock_name' => $item['stock_name'],
                            'qty'        => $qty,
                            'unit_price' => $price,
                            'line_total' => $total
                        ];
                    }

                    /*
                       Clear cart after successful checkout.
                    */
                    $clear_cart = $conn->prepare("
                        DELETE FROM cart WHERE customer_id = ?
                    ");
                    $clear_cart->execute([$customer_id]);

                    /*
                       Save all database changes.
                    */
                    $conn->commit();

                    /*
                       Save receipt data in session.
                       Used by receipt.php.
                    */
                    $_SESSION['last_receipt'] = [
                        'receipt_no' => $receipt_no,
                        'date'       => $sale_date,
                        'customer'   => [
                            'name'    => $name,
                            'phone'   => $number,
                            'email'   => $email,
                            'address' => $address
                        ],
                        'payment_method' => $method,
                        'items' => $receipt_items,
                        'total' => $receipt_total
                    ];

                    /*
                       Redirect to receipt page.
                    */
                    header('Location: receipt.php');
                    exit;

                } catch (Exception $e) {

                    /*
                       Cancel all changes if error happens.
                    */
                    if ($conn->inTransaction()) {
                        $conn->rollBack();
                    }

                    $error = "Something went wrong while placing your order. Please try again.";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Checkout</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
<script src="../js/theme.js"></script>
<link rel="stylesheet" href="../css/style.css">
</head>

<body class="checkout-page">

<?php include 'header.php'; ?>

<!-- =========================
     DISPLAY CART ITEMS
========================= -->
<section class="display-orders">
<div class="page-heading">
    <a href="cart.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Back to Cart</a>
    <span class="eyebrow">Secure checkout</span>
</div>
<h3>Products Ordered</h3>

<?php
$cart_total = 0;

/*
   Fetch cart items again for display.
*/
$select_cart = $conn->prepare("
    SELECT c.quantity, s.stock_id, s.stock_name, s.stock_price
    FROM cart c
    JOIN stock s ON c.stock_id = s.stock_id
    WHERE c.customer_id = ?
");
$select_cart->execute([$customer_id]);
$cart_items = $select_cart->fetchAll(PDO::FETCH_ASSOC);

if ($cart_items):
    foreach ($cart_items as $item):

        $subtotal = (int)$item['quantity'] * (float)$item['stock_price'];
        $cart_total += $subtotal;

        /*
           Map stock ID to image file.
        */
        $stock_id_str = str_pad((string)$item['stock_id'], 3, '0', STR_PAD_LEFT);
        $image_file = '../images/placeholder.jpg';
        switch ($stock_id_str) {
            case '001': $image_file = '../images/breast.png'; break;
            case '002': $image_file = '../images/drumsticks.png'; break;
            case '003': $image_file = '../images/thighs.png'; break;
            case '004': $image_file = '../images/wings.png'; break;
        }
?>
<div class="product-box">
    <img src="<?= $image_file ?>" alt="<?= htmlspecialchars($item['stock_name']); ?>">
    <div class="product-name"><?= htmlspecialchars($item['stock_name']); ?></div>

    <div class="product-details">
        <div class="detail-box">
            <div class="header">Unit Price</div>
            <div class="value">RM<?= number_format((float)$item['stock_price'], 2); ?></div>
        </div>
        <div class="detail-box">
            <div class="header">Amount</div>
            <div class="value"><?= (int)$item['quantity']; ?></div>
        </div>
        <div class="detail-box">
            <div class="header">Subtotal</div>
            <div class="value">RM<?= number_format($subtotal, 2); ?></div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<div class="grand-total">
   Grand Total: <span>RM<?= number_format($cart_total, 2); ?></span>
</div>

<?php else: ?>
<p class="empty">Your cart is empty!</p>
<?php endif; ?>
</section>

<!-- =========================
     CHECKOUT FORM
========================= -->
<section class="checkout-orders">
<form method="POST" id="checkoutForm">
    <h3>Checkout</h3>

    <div class="flex">

        <div class="inputBox name-box">
            <span>Your Name:</span>
            <input type="text" name="name" class="box"
                   value="<?= htmlspecialchars($name); ?>" required>
        </div>

        <div class="inputBox">
            <span>Phone Number:</span>
            <input type="text" name="number" class="box"
                   value="<?= htmlspecialchars($number); ?>" required>
        </div>

        <div class="inputBox">
            <span>Email:</span>
            <input type="email" name="email" class="box"
                   value="<?= htmlspecialchars($email); ?>" required>
        </div>

        <div class="inputBox">
            <span>Payment Method:</span>
            <select name="method" id="paymentMethod" class="box" required>
                <option value="">-- Select Payment Method --</option>
                <option value="0">Cash on Delivery</option>
                <option value="1">Card</option>
            </select>
        </div>

        <div class="inputBox full-address">
            <span>Full Address:</span>
            <input type="text" name="address" class="box"
                   value="<?= htmlspecialchars($address); ?>" required>
        </div>
    </div>

    <input
        type="submit"
        id="placeOrderBtn"
        class="btn <?= ($cart_total > 0) ? '' : 'disabled'; ?>"
        value="Place Order"
        <?= ($cart_total > 0) ? '' : 'disabled'; ?>
    >

    <a href="cart.php" class="option-btn checkout-continue-btn">Back to Cart</a>
</form>
</section>

<?php include 'footer.php'; ?>

<?php
/*
   Show error message using SweetAlert.
*/
if (!empty($error)) {
    echo "<script>
        Swal.fire({
            title: 'Error',
            text: '" . addslashes($error) . "',
            icon: 'error',
            confirmButtonText: 'OK'
        });
    </script>";
}
?>

<script>
/* =========================
   CHECKOUT CONFIRMATION
========================= */

/*
   Ask user to confirm order before submitting.
*/
document.addEventListener('DOMContentLoaded', () => {

    const form   = document.getElementById('checkoutForm');
    const btn    = document.getElementById('placeOrderBtn');
    const method = document.getElementById('paymentMethod');

    if (!form || !btn || btn.disabled) return;

    let allowSubmit = false;

    form.addEventListener('submit', function (e) {

        if (allowSubmit) return;

        e.preventDefault();

        if (!method || method.value === '') {
            Swal.fire({
                title: 'Payment Method Required',
                text: 'Please select a payment method.',
                icon: 'warning'
            });
            return;
        }

        Swal.fire({
            title: 'Confirm Order?',
            text: 'This purchase cannot be refunded.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Place Order',
            cancelButtonText: 'Back to Cart'
        }).then((result) => {

            if (result.isConfirmed) {
                allowSubmit = true;

                if (typeof form.requestSubmit === 'function') {
                    form.requestSubmit(btn);
                } else {
                    form.submit();
                }
            } else {
                window.location.href = 'cart.php';
            }
        });
    });
});
</script>

<script src="../js/script.js"></script>
</body>
</html>

<?php
/*
   Send buffered output to browser
   and end output buffering.
*/
ob_end_flush();
?>
