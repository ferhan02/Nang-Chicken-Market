<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['valid_user'])) {
    header("Location: enterprise_login.php");
    exit();
}

require_once __DIR__ . '/../config.php';

$activePage = 'stock';

/* -------------------------
   Update Stock (PDO)
   - Price updates only if provided
   - Quantity updates only if Set Quantity is provided
-------------------------- */
if (isset($_POST['save_changes'])) {
    $stock_id = (int)($_POST['stock_id'] ?? 0);
    $new_price_raw = trim($_POST['new_price'] ?? '');
    $set_qty_raw   = trim($_POST['set_quantity'] ?? '');

    if ($stock_id > 0) {
        $set_parts = [];
        $params = [];

        if ($new_price_raw !== '') {
            $new_price = (float)$new_price_raw;
            if ($new_price < 0) $new_price = 0;
            $set_parts[] = "stock_price = ?";
            $params[] = $new_price;
        }

        if ($set_qty_raw !== '') {
            $final_qty = (int)$set_qty_raw;
            if ($final_qty < 0) $final_qty = 0;
            $set_parts[] = "stock_quantity = ?";
            $params[] = $final_qty;
        }

        if (!empty($set_parts)) {
            $params[] = $stock_id;

            $sql = "UPDATE stock SET " . implode(', ', $set_parts) . " WHERE stock_id = ?";
            $upd = $conn->prepare($sql);
            $upd->execute($params);
        }

        header("Location: enterprise_stock.php?status=success");
        exit();
    }

    header("Location: enterprise_stock.php?status=error");
    exit();
}

/* -------------------------
   Fetch Stock List
-------------------------- */
$rows = [];
try {
    $stmt = $conn->prepare("SELECT stock_id, stock_name, stock_price, stock_quantity FROM stock ORDER BY stock_id ASC");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $rows = [];
}

/* -------------------------
   Build Map for Prefill (JS)
-------------------------- */
$stock_map = [];
foreach ($rows as $r) {
    $id = (int)$r['stock_id'];
    $stock_map[$id] = [
        'name'  => $r['stock_name'],
        'price' => (float)$r['stock_price'],
        'qty'   => (int)$r['stock_quantity']
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Staff Portal - Stock</title>
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
            <h1><i class="fa-solid fa-box"></i> Chicken Stock Inventory</h1>
            <p>View and update current stock levels.</p>
        </div>

        <div class="staff-card-body">

            <div class="staff-table-wrap">
                <table class="staff-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Stock Name</th>
                            <th>Unit Price (RM)</th>
                            <th>Quantity (pcs)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($rows)): ?>
                            <?php foreach ($rows as $row): ?>
                                <tr>
                                    <td><?= (int)$row['stock_id']; ?></td>
                                    <td><?= htmlspecialchars($row['stock_name']); ?></td>
                                    <td><?= number_format((float)$row['stock_price'], 2); ?></td>
                                    <td><?= (int)$row['stock_quantity']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="staff-empty">No stock records found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="staff-actions" id="updateBtnWrap">
                <button type="button" class="staff-btn" id="updateStockBtn">
                    <i class="fa-solid fa-pen-to-square"></i> Update Stock
                </button>
            </div>

            <section id="edit_section" class="staff-edit" style="display:none;">
                <div class="staff-edit-head">
                    <h2><i class="fa-solid fa-pen-to-square"></i> Update Stock Details</h2>
                    <p>Select an item to auto-fill the current values, then update the unit price and/or set a new quantity.</p>
                </div>

                <form method="post" class="staff-form">
                    <div class="staff-table-wrap staff-form-table-wrap">
                        <table class="staff-table staff-form-table">
                            <thead>
                                <tr>
                                    <th>Stock Item</th>
                                    <th>Unit Price (RM)</th>
                                    <th>Set Quantity (pcs)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <select name="stock_id" id="stockSelect" required>
                                            <option value="">Select stock item</option>
                                            <?php foreach ($rows as $r): ?>
                                                <option value="<?= (int)$r['stock_id']; ?>">
                                                    <?= htmlspecialchars($r['stock_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>

                                    <td>
                                        <div class="ctrl">
                                            <button type="button" class="ctrl-btn" id="priceMinus">−</button>
                                            <input
                                                type="number"
                                                step="0.50"
                                                name="new_price"
                                                id="priceInput"
                                                placeholder="e.g. 12.50"
                                                inputmode="decimal"
                                            >
                                            <button type="button" class="ctrl-btn" id="pricePlus">+</button>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="ctrl">
                                            <button type="button" class="ctrl-btn" id="setQtyMinus">−</button>
                                            <input
                                                type="number"
                                                name="set_quantity"
                                                id="setQtyInput"
                                                placeholder="e.g. 150"
                                                inputmode="numeric"
                                            >
                                            <button type="button" class="ctrl-btn" id="setQtyPlus">+</button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="staff-form-actions">
                        <button type="submit" name="save_changes" class="staff-btn">
                            <i class="fa-solid fa-arrows-rotate"></i> Update
                        </button>
                    </div>
                </form>
            </section>

        </div>
    </section>
</main>

<?php include __DIR__ . '/staff_footer.php'; ?>

<script>
const STOCK_MAP = <?php echo json_encode($stock_map, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

function toNum(v) {
    const n = parseFloat(v);
    return isNaN(n) ? 0 : n;
}
function toInt(v) {
    const n = parseInt(v, 10);
    return isNaN(n) ? 0 : n;
}
function formatPrice(v) {
    const n = Math.round((v + Number.EPSILON) * 100) / 100;
    return n.toFixed(2);
}

(function init(){
    const editSection = document.getElementById('edit_section');
    const updateBtn = document.getElementById('updateStockBtn');
    const updateBtnWrap = document.getElementById('updateBtnWrap');

    const select = document.getElementById('stockSelect');
    const priceInput = document.getElementById('priceInput');
    const setQtyInput = document.getElementById('setQtyInput');

    const priceMinus = document.getElementById('priceMinus');
    const pricePlus  = document.getElementById('pricePlus');

    const setQtyMinus = document.getElementById('setQtyMinus');
    const setQtyPlus  = document.getElementById('setQtyPlus');

    function setEmptyState(){
        if (priceInput) priceInput.value = '';
        if (setQtyInput) setQtyInput.value = '';
    }

    function applyPrefill(stockId){
        const id = parseInt(stockId || '0', 10);
        const data = STOCK_MAP[id];

        if (!data) {
            setEmptyState();
            return;
        }

        if (priceInput) priceInput.value = formatPrice(data.price ?? 0);
        if (setQtyInput) setQtyInput.value = (data.qty ?? 0).toString();
    }

    if (updateBtn) {
        updateBtn.addEventListener('click', function(){
            if (editSection) editSection.style.display = 'block';
            if (updateBtnWrap) updateBtnWrap.style.display = 'none';
            if (editSection) editSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    }

    if (select) {
        select.addEventListener('change', function(){
            applyPrefill(this.value);
        });

        if (!select.value) setEmptyState();
        else applyPrefill(select.value);
    }

    if (priceMinus && priceInput) {
        priceMinus.addEventListener('click', function(){
            const v = toNum(priceInput.value || '0');
            const next = v - 0.5;
            priceInput.value = formatPrice(next < 0 ? 0 : next);
        });
    }
    if (pricePlus && priceInput) {
        pricePlus.addEventListener('click', function(){
            const v = toNum(priceInput.value || '0');
            priceInput.value = formatPrice(v + 0.5);
        });
    }

    if (setQtyMinus && setQtyInput) {
        setQtyMinus.addEventListener('click', function(){
            const v = toInt(setQtyInput.value || '0') - 1;
            setQtyInput.value = (v < 0) ? 0 : v;
        });
    }
    if (setQtyPlus && setQtyInput) {
        setQtyPlus.addEventListener('click', function(){
            const v = toInt(setQtyInput.value || '0') + 1;
            setQtyInput.value = v;
        });
    }
})();
</script>

</body>
</html>
