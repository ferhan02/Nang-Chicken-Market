<?php
/* =========================
   SESSION & AUTH CHECK
========================= */

/*
   Start session so we can read staff login info.
*/
session_start();

/*
   Load database connection and shared config.
*/
require_once __DIR__ . '/../config.php';

/*
   Only allow staff users.
   If staff_id is missing, redirect to staff login.
*/
if (!isset($_SESSION['staff_id'])) {
    header('Location: enterprise_login.php');
    exit;
}

/* =========================
   GET RECEIPT NUMBER
========================= */

/*
   Receipt number comes from the URL.
   This identifies the whole checkout order.
*/
$receipt_no = trim($_GET['receipt_no'] ?? '');

/*
   If no receipt number is given,
   redirect back to the staff orders page.
*/
if ($receipt_no === '') {
    header('Location: enterprise_sales.php');
    exit;
}

/* =========================
   FETCH RECEIPT DATA
========================= */

/*
   This query fetches:
   - Order lines (each product)
   - Customer info
   - Product info
   - Payment method
   All rows share the same receipt number.
*/
$stmt = $conn->prepare("
    SELECT
        o.orders_id,
        o.receipt_no,
        o.sale_date,
        o.sale_quantitysold,
        o.sale_total,
        s.stock_name,
        s.stock_price,
        c.customer_name,
        c.customer_phone,
        c.customer_email,
        c.customer_address,
        p.payment_method
    FROM orders o
    JOIN stock s ON o.stock_id = s.stock_id
    JOIN customer c ON o.customer_id = c.customer_id
    LEFT JOIN payment p ON p.orders_id = o.orders_id
    WHERE o.receipt_no = ?
    ORDER BY o.orders_id ASC
");
$stmt->execute([$receipt_no]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

/*
   If no rows are returned,
   the receipt number does not exist.
*/
if (!$rows) {
    echo "Receipt not found.";
    exit;
}

/* =========================
   PREPARE RECEIPT DATA
========================= */

/*
   Use the first row to get shared data
   (customer, date, payment).
*/
$first = $rows[0];

$customer_name    = $first['customer_name'];
$customer_phone   = $first['customer_phone'];
$customer_email   = $first['customer_email'];
$customer_address = $first['customer_address'];
$sale_date        = $first['sale_date'];
$payment_method   = (int)($first['payment_method'] ?? 0);

/*
   Total will be calculated from line items.
*/
$total = 0;

/*
   Convert payment method number into readable text.
*/
function paymentLabel($m) {
    if ((int)$m === 1) return 'Card';
    return 'Cash on Delivery';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Staff Receipt</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script src="../js/theme.js"></script>
<link rel="stylesheet" href="../css/style.css">
</head>

<body class="receipt-page">

<div class="receipt-wrap">

    <!-- HEADER -->
    <div class="receipt-header">
        <div class="brand">
            <img src="../images/Nang-logo.png" alt="Nang Chicken Market">
            <div>
                <h1>Nang Chicken Market</h1>
                <div class="receipt-caption">Staff Receipt View</div>
            </div>
        </div>

        <div class="meta">
            <div><b>Receipt:</b> <?= htmlspecialchars($receipt_no); ?></div>
            <div><b>Date:</b> <?= htmlspecialchars($sale_date); ?></div>
            <div><b>Payment:</b> <?= paymentLabel($payment_method); ?></div>
        </div>
    </div>

    <!-- BODY -->
    <div class="body">

        <!-- CUSTOMER INFO -->
        <div class="grid">
            <div class="card">
                <h3>Customer</h3>
                <p><b>Name:</b> <?= htmlspecialchars($customer_name); ?></p>
                <p><b>Phone:</b> <?= htmlspecialchars($customer_phone); ?></p>
                <p><b>Email:</b> <?= htmlspecialchars($customer_email); ?></p>
            </div>

            <div class="card">
                <h3>Address</h3>
                <p><?= nl2br(htmlspecialchars($customer_address)); ?></p>
            </div>
        </div>

        <!-- ITEMS TABLE -->
        <table class="table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th class="right">Unit Price</th>
                    <th class="right">Qty</th>
                    <th class="right">Total</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r):
                $line_total = (float)$r['sale_total'];
                $total += $line_total;
            ?>
                <tr>
                    <td><?= htmlspecialchars($r['stock_name']); ?></td>
                    <td class="right">RM <?= number_format((float)$r['stock_price'], 2); ?></td>
                    <td class="right"><?= (int)$r['sale_quantitysold']; ?></td>
                    <td class="right">RM <?= number_format($line_total, 2); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <!-- GRAND TOTAL -->
        <div class="total">
            <div class="total-box">
                <b>Grand Total:</b>
                RM <?= number_format($total, 2); ?>
            </div>
        </div>

    </div>

    <!-- ACTIONS -->
    <div class="actions">
        <button class="btn" onclick="window.print()">Print</button>
        <a href="enterprise_sales.php" class="btn back">Back to Sales</a>
    </div>

</div>

</body>
</html>
