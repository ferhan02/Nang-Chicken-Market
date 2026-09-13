<?php
/* =========================
   CONFIG & SESSION
========================= */

/*
   Load config file.
   Provides database connection ($conn).
*/
require_once __DIR__ . '/../config.php';

/*
   Start session if not already started.
*/
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* =========================
   AUTHENTICATION CHECK
========================= */

/*
   Get logged-in customer ID.
*/
$customer_id = $_SESSION['customer_id'] ?? null;

/*
   Redirect to login if user is not logged in.
*/
if (!$customer_id) {
    header('Location: login.php');
    exit;
}

/* =========================
   READ FILTER INPUTS
========================= */

/*
   Read filter values from URL.
   These are optional filters.
*/
$search     = trim($_GET['search'] ?? '');
$status     = $_GET['status'] ?? 'all';
$date_from  = $_GET['date_from'] ?? '';
$date_to    = $_GET['date_to'] ?? '';

/* =========================
   CHECK IF order_status COLUMN EXISTS
========================= */

/*
   Some databases may not have order_status column.
   This check keeps the page working in both cases.
*/
$has_status = false;

try {
    $check_col = $conn->prepare("
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = 'orders'
          AND column_name = 'order_status'
        LIMIT 1
    ");
    $check_col->execute();
    $has_status = (bool)$check_col->fetchColumn();
} catch (Exception $e) {
    $has_status = false;
}

/* =========================
   BUILD ORDERS QUERY
========================= */

/*
   If order_status exists, use it.
   Otherwise return a fake value (0).
*/
$status_select = $has_status ? "o.order_status" : "0 AS order_status";

/*
   Base SQL query.
   Joins orders, stock, and customer tables.
*/
$sql = "
   SELECT 
      o.receipt_no,
      o.sale_date,
      o.sale_total,
      o.sale_quantitysold,
      {$status_select},
      s.stock_id,
      s.stock_name,
      c.customer_name,
      c.customer_phone,
      c.customer_address
   FROM orders o
   JOIN stock s ON o.stock_id = s.stock_id
   JOIN customer c ON o.customer_id = c.customer_id
   WHERE o.customer_id = ?
";

/*
   Parameters array for prepared statement.
*/
$params = [$customer_id];

/* =========================
   SEARCH FILTER
========================= */

/*
   Apply search filter if provided.
*/
if ($search !== '') {
    $sql .= "
      AND (
            o.receipt_no LIKE ?
         OR o.tale_date LIKE ?
         OR s.stock_name LIKE ?
         OR c.customer_name LIKE ?
         OR c.customer_phone LIKE ?
         OR c.customer_address LIKE ?
      )
    ";
    $like = "%{$search}%";
    array_push($params, $like, $like, $like, $like, $like, $like);
}

/* =========================
   STATUS FILTER
========================= */

/*
   Apply status filter only if column exists.
*/
if ($has_status && $status !== 'all') {
    $sql .= " AND o.order_status = ? ";
    $params[] = (int)$status;
}

/* =========================
   DATE RANGE FILTERS
========================= */

if ($date_from !== '') {
    $sql .= " AND o.sale_date >= ? ";
    $params[] = $date_from;
}

if ($date_to !== '') {
    $sql .= " AND o.sale_date <= ? ";
    $params[] = $date_to;
}

/*
   Sort orders by date (latest first).
*/
$sql .= " ORDER BY o.sale_date DESC, o.orders_id ASC";

/*
   Execute final query.
*/
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   STATUS LABEL HELPER
========================= */

/*
   Convert numeric status to readable text.
*/
function statusLabel($s) {
    return ((int)$s === 1) ? 'Completed' : 'Pending';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Placed Orders</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- Global styles -->

<!-- Orders page styles -->

<!-- Icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
<link rel="stylesheet" href="../css/style.css">
<script src="../js/theme.js"></script>
</head>

<body class="orders-page">

<?php include 'header.php'; ?>

<section class="placed-orders">

<div class="page-heading">
<a href="home.php" class="back-link">
    <i class="fa-solid fa-arrow-left"></i> Back to Shop
</a>
</div>

<h1 class="title">Placed Orders</h1>

<!-- =========================
     FILTER FORM
========================= -->
<form method="get" class="orders-filter">

    <div class="orders-filter-head">
        <h2><i class="fa-solid fa-filter"></i> Filters</h2>
        <p>Search and narrow down records by status and date range.</p>
    </div>

    <div class="orders-filter-grid">

        <!-- Search -->
        <div class="orders-field">
            <label>Search</label>
            <input
                type="text"
                name="search"
                placeholder="Search customer, product, receipt"
                value="<?= htmlspecialchars($search); ?>"
            >
        </div>

        <!-- Status -->
        <div class="orders-field">
            <label>Status</label>
            <select name="status" <?= $has_status ? '' : 'disabled'; ?>>
                <option value="all">All Status</option>
                <option value="0" <?= $status === '0' ? 'selected' : '' ?>>Pending</option>
                <option value="1" <?= $status === '1' ? 'selected' : '' ?>>Completed</option>
            </select>
        </div>

        <!-- Date from -->
        <div class="orders-field">
            <label>Date From</label>
            <input type="date" name="date_from" value="<?= htmlspecialchars($date_from); ?>">
        </div>

        <!-- Date to -->
        <div class="orders-field">
            <label>Date To</label>
            <input type="date" name="date_to" value="<?= htmlspecialchars($date_to); ?>">
        </div>

    </div>

    <div class="orders-filter-actions">
        <button type="submit" class="orders-btn">
            <i class="fa-solid fa-magnifying-glass"></i> Apply
        </button>

        <a href="orders.php" class="orders-btn outline">
            <i class="fa-solid fa-rotate-left"></i> Reset
        </a>
    </div>
</form>

<!-- =========================
     ORDERS LIST
========================= -->
<div class="box-container">
<?php
if ($rows) {

    /*
       Group rows by receipt number.
       Each receipt represents one order.
    */
    $grouped = [];

    foreach ($rows as $r) {

        $key = trim((string)($r['receipt_no'] ?? ''));

        if ($key === '') {
            $key = 'LEGACY-' . $r['sale_date'];
        }

        if (!isset($grouped[$key])) {
            $grouped[$key] = [
                'receipt_no' => $r['receipt_no'] ?: $key,
                'date' => $r['sale_date'],
                'status' => (int)$r['order_status'],
                'customer_name' => $r['customer_name'],
                'customer_phone' => $r['customer_phone'],
                'customer_address' => $r['customer_address'],
                'products' => []
            ];
        }

        $sid = (int)$r['stock_id'];

        if (!isset($grouped[$key]['products'][$sid])) {
            $grouped[$key]['products'][$sid] = [
                'name' => $r['stock_name'],
                'qty' => 0,
                'total' => 0
            ];
        }

        $grouped[$key]['products'][$sid]['qty'] += (int)$r['sale_quantitysold'];
        $grouped[$key]['products'][$sid]['total'] += (float)$r['sale_total'];
    }

    /* =========================
       DISPLAY EACH ORDER
    ========================= */
    foreach ($grouped as $order):

        $grand = 0;
        foreach ($order['products'] as $p) {
            $grand += (float)$p['total'];
        }

        $status_int = (int)$order['status'];
?>
    <div class="order-card collapsed">

        <!-- Order header -->
        <div class="order-card-header">
            <div class="header-left">
                <div class="receipt-line">
                    <i class="fa-solid fa-receipt"></i>
                    <span class="receipt-no"><?= htmlspecialchars($order['receipt_no']); ?></span>
                </div>

                <div class="customer-line">
                    <i class="fa-solid fa-user"></i>
                    <span><?= htmlspecialchars($order['customer_name']); ?></span>
                </div>
            </div>

            <div class="header-right">
                <span class="mini-date"><?= htmlspecialchars($order['date']); ?></span>
                <span class="status-pill <?= $status_int ? 'completed' : 'pending'; ?>">
                    <?= htmlspecialchars(statusLabel($status_int)); ?>
                </span>
                <span class="mini-total">RM<?= number_format($grand, 2); ?></span>
                <i class="fa-solid fa-chevron-down toggle-icon"></i>
            </div>
        </div>

        <!-- Order body -->
        <div class="order-card-body">

            <!-- Order details -->
            <div class="order-meta">
                <div class="meta-row">
                    <span class="meta-k">Receipt</span>
                    <span class="meta-v"><?= htmlspecialchars($order['receipt_no']); ?></span>
                </div>

                <div class="meta-row">
                    <span class="meta-k">Date</span>
                    <span class="meta-v"><?= htmlspecialchars($order['date']); ?></span>
                </div>

                <div class="meta-row">
                    <span class="meta-k">Status</span>
                    <span class="meta-v"><?= htmlspecialchars(statusLabel($status_int)); ?></span>
                </div>

                <div class="meta-row">
                    <span class="meta-k">Phone</span>
                    <span class="meta-v"><?= htmlspecialchars($order['customer_phone']); ?></span>
                </div>

                <div class="meta-row meta-wide">
                    <span class="meta-k">Address</span>
                    <span class="meta-v"><?= nl2br(htmlspecialchars($order['customer_address'])); ?></span>
                </div>
            </div>

            <!-- Items table -->
            <div class="table-wrap">
                <table class="order-items-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th class="center">Quantity</th>
                            <th class="center">Subtotal (RM)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($order['products'] as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['name']); ?></td>
                            <td class="center"><?= (int)$p['qty']; ?></td>
                            <td class="center"><?= number_format((float)$p['total'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="2" class="center">Grand Total</td>
                            <td class="center">RM<?= number_format($grand, 2); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Actions -->
            <div class="order-actions">
                <a
                    class="view-receipt-btn"
                    href="receipt.php?receipt_no=<?= urlencode($order['receipt_no']); ?>">
                    View Receipt
                </a>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<?php } else { ?>
    <p class="empty">
        <?= ($search || $status !== 'all' || $date_from || $date_to)
            ? 'No orders match your filter.'
            : 'No orders found.'; ?>
    </p>
<?php } ?>
</div>

</section>

<?php include 'footer.php'; ?>

<!-- Global JS -->
<script src="../js/script.js"></script>

<script>
/*
   Toggle order expand/collapse.
*/
document.querySelectorAll('.order-card-header').forEach(h => {
    h.addEventListener('click', (e) => {
        if (e.target.closest('.view-receipt-btn')) return;
        h.parentElement.classList.toggle('collapsed');
    });
});
</script>

</body>
</html>
