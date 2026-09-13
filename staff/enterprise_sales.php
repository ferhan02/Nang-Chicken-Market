<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['valid_user'])) {
    header("Location: enterprise_login.php");
    exit();
}

require_once __DIR__ . '/../config.php';

$activePage = 'sales';

/* =========================
   ASSIGN STAFF (BY RECEIPT NO) - Pending only
========================= */
if (isset($_POST['assign_staff'])) {
    $receipt_no = trim($_POST['receipt_no'] ?? '');
    $staff_id   = (int)($_POST['staff_id'] ?? 0);

    // Only real staff (exclude id=1 placeholder)
    if ($receipt_no !== '' && $staff_id >= 2) {

        // Only assign if this receipt is still pending (any line item pending)
        $chk = $conn->prepare("
            SELECT MAX(order_status)
            FROM orders
            WHERE receipt_no = ?
        ");
        $chk->execute([$receipt_no]);
        $max_status = (int)$chk->fetchColumn(); // if any completed exists -> 1

        if ($max_status === 0) {
            $up = $conn->prepare("
                UPDATE orders
                SET staff_id = ?
                WHERE receipt_no = ?
            ");
            $up->execute([$staff_id, $receipt_no]);
        }
    }

    $qs = $_SERVER['QUERY_STRING'] ?? '';
    header("Location: enterprise_sales.php" . ($qs ? ('?' . $qs) : ''));
    exit();
}

/* =========================
   UPDATE ORDER STATUS (BY RECEIPT NO)
========================= */
if (isset($_POST['set_status'])) {
    $receipt_no = trim($_POST['receipt_no'] ?? '');
    $new_status = (int)($_POST['new_status'] ?? -1);

    if ($receipt_no !== '' && in_array($new_status, [0, 1], true)) {
        $stmt = $conn->prepare("
            UPDATE orders
            SET order_status = ?
            WHERE receipt_no = ?
        ");
        $stmt->execute([$new_status, $receipt_no]);
    }

    $qs = $_SERVER['QUERY_STRING'] ?? '';
    header("Location: enterprise_sales.php" . ($qs ? ('?' . $qs) : ''));
    exit();
}

/* =========================
   FILTERS & SEARCH
========================= */
$status_filter = $_GET['status'] ?? 'all';
$date_from     = $_GET['date_from'] ?? '';
$date_to       = $_GET['date_to'] ?? '';
$search        = trim($_GET['search'] ?? '');

$where  = [];
$params = [];

if ($status_filter !== 'all') {
    $where[]  = "o.order_status = ?";
    $params[] = (int)$status_filter;
}

if ($date_from !== '') {
    $where[]  = "o.sale_date >= ?";
    $params[] = $date_from;
}

if ($date_to !== '') {
    $where[]  = "o.sale_date <= ?";
    $params[] = $date_to;
}

if ($search !== '') {
    $where[] = "(
        o.receipt_no LIKE ?
        OR CAST(o.orders_id AS CHAR) LIKE ?
        OR o.sale_date LIKE ?
        OR c.customer_name LIKE ?
        OR c.customer_phone LIKE ?
        OR c.customer_address LIKE ?
        OR s.stock_name LIKE ?
        OR st.staff_name LIKE ?
    )";
    $like = "%{$search}%";
    $params[] = $like; // receipt_no
    $params[] = $like; // orders_id
    $params[] = $like; // sale_date
    $params[] = $like; // customer_name
    $params[] = $like; // customer_phone
    $params[] = $like; // customer_address
    $params[] = $like; // stock_name
    $params[] = $like; // staff_name
}

$where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

/* =========================
   STAFF LIST (for Assign Staff)
========================= */
$staff_stmt = $conn->prepare("
    SELECT staff_id, staff_name
    FROM staff
    WHERE staff_id >= 2
    ORDER BY staff_name ASC
");
$staff_stmt->execute();
$staff_list = $staff_stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   FETCH SALES (LINE ITEMS)
========================= */
$stmt = $conn->prepare("
    SELECT
        o.orders_id,
        o.receipt_no,
        o.sale_date,
        o.sale_quantitysold,
        o.sale_total,
        o.order_status,
        c.customer_id,
        c.customer_name,
        c.customer_phone,
        c.customer_address,
        s.stock_id,
        s.stock_name,
        st.staff_id,
        st.staff_name
    FROM orders o
    JOIN customer c ON o.customer_id = c.customer_id
    JOIN stock s ON o.stock_id = s.stock_id
    JOIN staff st ON o.staff_id = st.staff_id
    $where_sql
    ORDER BY o.sale_date DESC, o.receipt_no DESC, o.orders_id DESC
");
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   GROUP BY RECEIPT NO (CHECKOUT ORDER)
========================= */
$grouped = [];

foreach ($rows as $r) {
    $receipt_no = trim((string)($r['receipt_no'] ?? ''));

    if ($receipt_no === '') {
        $receipt_no = 'ORD-' . (int)$r['orders_id'];
    }

    if (!isset($grouped[$receipt_no])) {
        $grouped[$receipt_no] = [
            'receipt_no'        => $receipt_no,
            'sale_date'         => (string)($r['sale_date'] ?? ''),
            'status'            => (int)($r['order_status'] ?? 0),
            'customer_name'     => (string)($r['customer_name'] ?? ''),
            'customer_phone'    => (string)($r['customer_phone'] ?? ''),
            'customer_address'  => (string)($r['customer_address'] ?? ''),
            'staff_id'          => (int)($r['staff_id'] ?? 0),
            'staff_name'        => (string)($r['staff_name'] ?? ''),
            'first_orders_id'   => (int)($r['orders_id'] ?? 0),
            'grand_total'       => 0.0,
            'items'             => []
        ];
    }

    if ((int)($r['order_status'] ?? 0) === 1) {
        $grouped[$receipt_no]['status'] = 1;
    }

    $sid = (int)($r['stock_id'] ?? 0);
    if (!isset($grouped[$receipt_no]['items'][$sid])) {
        $grouped[$receipt_no]['items'][$sid] = [
            'stock_name' => (string)($r['stock_name'] ?? ''),
            'qty'        => 0,
            'subtotal'   => 0.0
        ];
    }

    $qty = (int)($r['sale_quantitysold'] ?? 0);
    $tot = (float)($r['sale_total'] ?? 0);

    $grouped[$receipt_no]['items'][$sid]['qty']      += $qty;
    $grouped[$receipt_no]['items'][$sid]['subtotal'] += $tot;

    $grouped[$receipt_no]['grand_total'] += $tot;
}

function statusLabel($s) {
    return ((int)$s === 1) ? 'Completed' : 'Pending';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Staff Portal - Sales</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
<script src="../js/theme.js"></script>
<link rel="stylesheet" href="../css/style.css">
</head>

<body>

<?php include __DIR__ . '/staff_header.php'; ?>

<main class="staff-page">
<section class="staff-card">

<div class="staff-card-head">
    <h1><i class="fa-solid fa-receipt"></i> Sales Records</h1>
    <p>Search, filter, and manage sales transactions.</p>
</div>

<div class="staff-card-body">

<!-- =========================
     FILTER BAR
========================= -->
<form method="get" class="staff-filter">

    <div class="staff-filter-head">
        <h2><i class="fa-solid fa-filter"></i> Filters</h2>
        <p>Search and narrow down records by status and date range.</p>
    </div>

    <div class="staff-filter-form">
        <div class="staff-grid">

            <div class="staff-field staff-search">
                <label for="salesSearch">Search</label>
                <input
                    id="salesSearch"
                    type="text"
                    name="search"
                    placeholder="Search receipt, order id, date, customer, phone, address, product, staff"
                    value="<?= htmlspecialchars($search); ?>"
                    class="filter-search"
                >

                <div class="staff-filter-actions">
                    <button type="submit" class="staff-btn small">
                        <i class="fa-solid fa-magnifying-glass"></i> Apply
                    </button>

                    <a href="enterprise_sales.php" class="staff-btn small outline">
                        <i class="fa-solid fa-rotate-left"></i> Reset
                    </a>
                </div>
            </div>

            <div class="staff-field">
                <label for="salesStatus">Status</label>
                <select id="salesStatus" name="status">
                    <option value="all">All Status</option>
                    <option value="0" <?= $status_filter === '0' ? 'selected' : '' ?>>Pending</option>
                    <option value="1" <?= $status_filter === '1' ? 'selected' : '' ?>>Completed</option>
                </select>
            </div>

            <div class="staff-field">
                <label for="dateFrom">Date From</label>
                <input id="dateFrom" type="date" name="date_from" value="<?= htmlspecialchars($date_from); ?>">
            </div>

            <div class="staff-field">
                <label for="dateTo">Date To</label>
                <input id="dateTo" type="date" name="date_to" value="<?= htmlspecialchars($date_to); ?>">
            </div>

        </div>
    </div>

</form>

<!-- =========================
     SALES LIST (ACCORDION)
========================= -->
<div class="sales-list">
<?php if (!empty($grouped)): ?>
    <?php foreach ($grouped as $receiptNo => $order): ?>
        <?php
            $status = (int)($order['status'] ?? 0);
            $grand  = (float)($order['grand_total'] ?? 0);
            $first_orders_id = (int)($order['first_orders_id'] ?? 0);
            $panel_id = 'panel_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $receiptNo);
        ?>

        <article class="sales-card" data-acc>
            <button class="sales-head" type="button" data-acc-btn aria-expanded="false" aria-controls="<?= htmlspecialchars($panel_id); ?>">
                <div class="sales-head-left">
                    <div class="sales-head-line">
                        <span class="sales-icon"><i class="fa-solid fa-receipt"></i></span>
                        <span class="sales-receipt"><?= htmlspecialchars($receiptNo); ?></span>
                    </div>

                    <div class="sales-sub-line">
                        <span class="sales-sub-item">
                            <i class="fa-solid fa-user"></i>
                            <span><?= htmlspecialchars($order['customer_name'] ?? ''); ?></span>
                        </span>
                    </div>
                </div>

                <div class="sales-head-right">
                    <div class="sales-pill-row">
                        <span class="sales-date-pill">
                            <i class="fa-solid fa-calendar-days"></i>
                            <?= htmlspecialchars($order['sale_date'] ?? ''); ?>
                        </span>

                        <span class="sales-status-pill <?= $status === 1 ? 'completed' : 'pending'; ?>">
                            <i class="fa-solid <?= $status === 1 ? 'fa-circle-check' : 'fa-clock'; ?>"></i>
                            <?= htmlspecialchars(statusLabel($status)); ?>
                        </span>

                        <span class="sales-total-pill">
                            RM<?= number_format($grand, 2); ?>
                        </span>
                    </div>

                    <span class="sales-chevron" aria-hidden="true">
                        <i class="fa-solid fa-chevron-down"></i>
                    </span>
                </div>
            </button>

            <div class="sales-panel" id="<?= htmlspecialchars($panel_id); ?>" data-acc-panel>
                <div class="sales-panel-inner">

                    <div class="sales-meta">
                        <div class="sales-meta-grid">
                            <div class="sales-meta-item">
                                <div class="label"><i class="fa-solid fa-id-card"></i> Receipt No</div>
                                <div class="value"><?= htmlspecialchars($receiptNo); ?></div>
                            </div>

                            <!-- ✅ Staff + Assign button (Pending only) -->
                            <div class="sales-meta-item">
                                <div class="label"><i class="fa-solid fa-user-tie"></i> Staff</div>
                                <div class="value staff-assign-row">
                                    <span class="staff-current"><?= htmlspecialchars($order['staff_name'] ?? ''); ?></span>

                                    <?php if ($status === 0): ?>
                                        <form method="post" class="assign-staff-form"
                                              data-confirm="Assign this order to the selected staff?"
                                              data-confirm-text="The selected staff member will be responsible for this order."
                                              data-confirm-button="Assign Order">
                                            <input type="hidden" name="receipt_no" value="<?= htmlspecialchars($receiptNo); ?>">
                                            <select name="staff_id" class="assign-staff-select" aria-label="Assign staff" required>
                                                <?php foreach ($staff_list as $st): ?>
                                                    <option value="<?= (int)$st['staff_id']; ?>" <?= ((int)$st['staff_id'] === (int)($order['staff_id'] ?? 0)) ? 'selected' : ''; ?>>
                                                        <?= htmlspecialchars($st['staff_name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>

                                            <button type="submit" name="assign_staff" class="assign-staff-btn" title="Assign Staff">
                                                <i class="fa-solid fa-user-check"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="sales-meta-item">
                                <div class="label"><i class="fa-solid fa-phone"></i> Phone</div>
                                <div class="value"><?= htmlspecialchars($order['customer_phone'] ?? ''); ?></div>
                            </div>

                            <div class="sales-meta-item full">
                                <div class="label"><i class="fa-solid fa-location-dot"></i> Address</div>
                                <div class="value"><?= htmlspecialchars($order['customer_address'] ?? ''); ?></div>
                            </div>

                            <div class="sales-meta-item">
                                <div class="label"><i class="fa-solid fa-calendar-days"></i> Date</div>
                                <div class="value"><?= htmlspecialchars($order['sale_date'] ?? ''); ?></div>
                            </div>

                            <div class="sales-meta-item">
                                <div class="label"><i class="fa-solid fa-flag"></i> Status</div>
                                <div class="value">
                                    <form method="post" class="status-form">
                                        <input type="hidden" name="receipt_no" value="<?= htmlspecialchars($receiptNo); ?>">
                                        <select name="new_status" class="status-select" aria-label="Set status">
                                            <option value="0" <?= $status === 0 ? 'selected' : '' ?>>Pending</option>
                                            <option value="1" <?= $status === 1 ? 'selected' : '' ?>>Completed</option>
                                        </select>
                                        <button type="submit" name="set_status" class="staff-btn small">
                                            <i class="fa-solid fa-floppy-disk"></i> Save
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="sales-items">
                        <div class="sales-items-head">
                            <div class="title"><i class="fa-solid fa-basket-shopping"></i> Items</div>
                        </div>

                        <div class="sales-items-tablewrap">
                            <table class="sales-items-table">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th class="c">Quantity</th>
                                        <th class="c">Subtotal (RM)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach (($order['items'] ?? []) as $it): ?>
                                    <tr>
                                        <td class="c"><?= htmlspecialchars($it['stock_name'] ?? ''); ?></td>
                                        <td class="c"><?= (int)($it['qty'] ?? 0); ?></td>
                                        <td class="c"><?= number_format((float)($it['subtotal'] ?? 0), 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                    <tr class="grand-row">
                                        <td class="c" colspan="2">Grand Total</td>
                                        <td class="c"><b>RM<?= number_format($grand, 2); ?></b></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="sales-actions">
                            <a
                                class="staff-btn small"
                                href="staff_receipt.php?receipt_no=<?= urlencode($receiptNo); ?>"
                                target="_blank"
                            >
                                <i class="fa-solid fa-print"></i> View Receipt
                            </a>
                        </div>
                    </div>

                </div>
            </div>
        </article>
    <?php endforeach; ?>
<?php else: ?>
    <div class="sales-empty">
        No sales records found.
    </div>
<?php endif; ?>
</div>

</div>
</section>
</main>

<?php include __DIR__ . '/staff_footer.php'; ?>

<script>
/* =========================
   ACCORDION (EXPAND/COLLAPSE)
========================= */
(function () {
    const cards = document.querySelectorAll('[data-acc]');
    cards.forEach(card => {
        const btn = card.querySelector('[data-acc-btn]');
        const panel = card.querySelector('[data-acc-panel]');
        if (!btn || !panel) return;

        const setOpen = (open) => {
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
            card.classList.toggle('open', open);

            if (open) {
                panel.style.maxHeight = panel.scrollHeight + 'px';
            } else {
                panel.style.maxHeight = '0px';
            }
        };

        setOpen(false);

        btn.addEventListener('click', () => {
            const isOpen = btn.getAttribute('aria-expanded') === 'true';
            setOpen(!isOpen);
        });

        window.addEventListener('resize', () => {
            const isOpen = btn.getAttribute('aria-expanded') === 'true';
            if (isOpen) panel.style.maxHeight = panel.scrollHeight + 'px';
        });
    });
})();
</script>

</body>
</html>
