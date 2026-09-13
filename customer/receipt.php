<?php
/* =========================
   OUTPUT BUFFERING & SESSION
========================= */

/*
   Turn on output buffering.
   This lets PHP send headers (like redirect)
   even if some output is generated later.
*/
ob_start();

/*
   Start session if not already started.
   Needed to read receipt data from session.
*/
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* =========================
   LOAD RECEIPT FROM SESSION
========================= */

/*
   Receipt data is stored in session after checkout.
*/
$receipt = $_SESSION['last_receipt'] ?? null;

/*
   If user opens this page without completing checkout,
   redirect them back to home page.
*/
if (
    !is_array($receipt) ||
    empty($receipt['items']) ||
    !is_array($receipt['items'])
) {
    header('Location: home.php');
    exit;
}

/*
   Optional:
   You can clear receipt after showing once.
   This keeps receipt from being reused.
*/
// unset($_SESSION['last_receipt']);

/* =========================
   PAYMENT METHOD LABEL
========================= */

/*
   Convert payment method number into readable text.
*/
function paymentMethodLabel($method) {
    $method = (int)$method;

    // 0 = Cash on Delivery
    // 1 = Card
    if ($method === 0) return 'Cash on Delivery';
    if ($method === 1) return 'Card';

    return 'Unknown';
}

/* =========================
   PREPARE RECEIPT DATA
========================= */

/*
   Extract values from receipt array.
*/
$items    = $receipt['items'];
$total    = isset($receipt['total']) ? (float)$receipt['total'] : 0.0;
$customer = isset($receipt['customer']) && is_array($receipt['customer'])
    ? $receipt['customer']
    : [];

/*
   Basic receipt information.
*/
$receipt_date   = $receipt['date'] ?? date('Y-m-d');
$payment_method = $receipt['payment_method'] ?? 0;
$receipt_no     = $receipt['receipt_no'] ?? 'N/A';

/*
   Safe customer fields.
*/
$c_name    = $customer['name'] ?? '';
$c_phone   = $customer['phone'] ?? '';
$c_email   = $customer['email'] ?? '';
$c_address = $customer['address'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Receipt</title>

<!-- Global shared styles -->
<script src="../js/theme.js"></script>
<link rel="stylesheet" href="../css/style.css">
</head>

<body class="receipt-page">

<div class="receipt-wrap">

    <!-- HEADER -->
    <div class="receipt-header">
        <div class="brand">
            <img src="../images/Nang-logo.png" alt="Nang Chicken Market" class="receipt-logo">
            <div>
                <h1>Nang Chicken Market</h1>
                <div class="receipt-caption">Official Receipt</div>
            </div>
        </div>

        <div class="meta">
            <div class="big">Receipt No: <?= htmlspecialchars($receipt_no); ?></div>
            <div>Date: <?= htmlspecialchars($receipt_date); ?></div>
            <div>Payment: <?= htmlspecialchars(paymentMethodLabel($payment_method)); ?></div>
        </div>
    </div>

    <!-- BODY -->
    <div class="receipt-body">

        <!-- CUSTOMER INFO -->
        <div class="grid">
            <div class="card">
                <h3>Customer</h3>
                <p><b>Name:</b> <?= htmlspecialchars($c_name); ?></p>
                <p><b>Phone:</b> <?= htmlspecialchars($c_phone); ?></p>
                <p><b>Email:</b> <?= htmlspecialchars($c_email); ?></p>
            </div>
            <div class="card">
                <h3>Delivery Address</h3>
                <p><?= nl2br(htmlspecialchars($c_address)); ?></p>
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
            <?php foreach ($items as $it): ?>
                <?php
                    $stock_name = $it['stock_name'] ?? '';
                    $orders_id  = isset($it['orders_id']) ? (int)$it['orders_id'] : 0;
                    $unit_price = (float)($it['unit_price'] ?? 0);
                    $qty        = (int)($it['qty'] ?? 0);
                    $line_total = (float)($it['line_total'] ?? ($unit_price * $qty));
                ?>
                <tr>
                    <td>
                        <?= htmlspecialchars($stock_name); ?>
                    </td>
                    <td class="right">RM <?= number_format($unit_price, 2); ?></td>
                    <td class="right"><?= $qty; ?></td>
                    <td class="right">RM <?= number_format($line_total, 2); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <!-- TOTAL -->
        <div class="total-row">
            <div class="total-box">
                <div class="line grand">
                    <span>Grand Total</span>
                    <span>RM <?= number_format($total, 2); ?></span>
                </div>
            </div>
        </div>

        <!-- NOTICE -->
        <div class="notice">
            This purchase is NOT refundable. Please keep this receipt for your records.
        </div>

    </div>

    <!-- ACTIONS -->
    <div class="receipt-actions">
        <button class="action-btn print-btn" onclick="window.print()">Print / Save PDF</button>
        <a class="action-btn staff-btn-outline" href="orders.php">Order History</a>
        <a class="action-btn shop-btn" href="home.php">Continue Shopping</a>
    </div>

</div>


</body>
</html>

<?php
/*
   Flush output buffer and send everything to browser.
*/
ob_end_flush();
?>
