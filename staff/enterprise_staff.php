<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['valid_user'])) {
    header("Location: enterprise_login.php");
    exit();
}

require_once __DIR__ . '/../config.php';

/* -------------------------
   Page Identification
-------------------------- */
$activePage = 'staff';

/* -------------------------
   Staff CRUD (PDO)
-------------------------- */

/* Add Staff */
if (isset($_POST['add_staff'])) {
    $stmt = $conn->prepare(
        "INSERT INTO staff (staff_name, staff_phone, staff_salary, staff_dob, staff_username, staff_password)
         VALUES (?, ?, ?, ?, ?, ?)"
    );

    $stmt->execute([
        $_POST['name'],
        $_POST['phone'],
        $_POST['salary'],
        $_POST['dob'],
        $_POST['user'],
        $_POST['pass']
    ]);

    header("Location: enterprise_staff.php");
    exit();
}

/* Edit Staff */
if (isset($_POST['edit_staff'])) {
    $id = (int)($_POST['staff_id'] ?? 0);

    // Prevent editing reserved staff_id=1 (optional safety)
    if ($id === 1) {
        header("Location: enterprise_staff.php");
        exit();
    }

    $fields = [
        'staff_name'   => $_POST['name']   ?? '',
        'staff_phone'  => $_POST['phone']  ?? '',
        'staff_salary' => $_POST['salary'] ?? '',
        'staff_dob'    => $_POST['dob']    ?? '',
    ];

    foreach ($fields as $col => $val) {
        if ($val !== '') {
            $stmt = $conn->prepare("UPDATE staff SET {$col} = ? WHERE staff_id = ?");
            $stmt->execute([$val, $id]);
        }
    }

    header("Location: enterprise_staff.php");
    exit();
}

/* Delete + Re-index (keep staff_id=1 reserved) */
if (isset($_POST['delete_staff'])) {
    $id = (int)($_POST['staff_id'] ?? 0);

    // Never delete reserved record
    if ($id === 1) {
        header("Location: enterprise_staff.php");
        exit();
    }

    $conn->prepare("DELETE FROM staff WHERE staff_id = ?")->execute([$id]);

    // Re-index starting from 2 (keep staff_id=1 intact)
    $conn->exec("SET @count := 1");
    $conn->exec("UPDATE staff SET staff_id = (@count := @count + 1) WHERE staff_id <> 1 ORDER BY staff_id");

    // Reset AUTO_INCREMENT to next after current max
    $maxRow = $conn->query("SELECT MAX(staff_id) AS m FROM staff")->fetch(PDO::FETCH_ASSOC);
    $nextAI = (int)($maxRow['m'] ?? 1) + 1;
    $conn->exec("ALTER TABLE staff AUTO_INCREMENT = " . $nextAI);

    header("Location: enterprise_staff.php");
    exit();
}

/* -------------------------
   Search
-------------------------- */
$search_query = "";
$category = "staff_name";
$where_clause = "WHERE staff_id <> 1"; // always hide reserved staff_id=1
$params = [];

if (isset($_POST['search_btn'])) {
    $search_query = trim($_POST['search_query'] ?? "");
    $category = $_POST['category'] ?? "staff_name";

    $allowed = ['staff_name', 'staff_phone', 'staff_salary', 'staff_dob'];
    if (!in_array($category, $allowed, true)) {
        $category = 'staff_name';
    }

    if ($search_query !== "") {
        $where_clause .= " AND {$category} LIKE ?";
        $params[] = "%{$search_query}%";
    }
}

/* -------------------------
   Fetch Staff (exclude staff_id=1)
-------------------------- */
$sql = "SELECT staff_id, staff_name, staff_phone, staff_salary, staff_dob
        FROM staff
        {$where_clause}
        ORDER BY staff_id ASC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Map for Edit dropdown autofill */
$staff_map = [];
foreach ($rows as $r) {
    $staff_map[$r['staff_id']] = $r;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Staff Directory</title>

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
<script src="../js/theme.js"></script>
<link rel="stylesheet" href="../css/style.css">
</head>

<body>

<?php include __DIR__ . '/staff_header.php'; ?>

<main class="staff-page">
    <section class="staff-card">
        <div class="staff-card-head">
            <h1><i class="fa-solid fa-users"></i> Staff Directory</h1>
            <p>View, add, edit, or delete staff records.</p>
        </div>

        <div class="staff-card-body">

            <section class="staff-filter">
                <form action="enterprise_staff.php" method="POST" class="staff-filter-form">
                    <div class="staff-grid">
                        <div class="staff-field">
                            <label for="search_query">Keyword</label>
                            <input
                                id="search_query"
                                type="text"
                                name="search_query"
                                placeholder="Type to search..."
                                value="<?php echo htmlspecialchars($search_query); ?>"
                            >
                        </div>

                        <div class="staff-field">
                            <label for="category">Filter By</label>
                            <select id="category" name="category">
                                <option value="staff_name" <?php if($category=="staff_name") echo "selected"; ?>>Staff Name</option>
                                <option value="staff_phone" <?php if($category=="staff_phone") echo "selected"; ?>>Phone Number</option>
                                <option value="staff_salary" <?php if($category=="staff_salary") echo "selected"; ?>>Salary</option>
                                <option value="staff_dob" <?php if($category=="staff_dob") echo "selected"; ?>>Date of Birth</option>
                            </select>
                        </div>

                        <div class="staff-field staff-filter-actions">
                            <button type="submit" name="search_btn" class="staff-btn">
                                <i class="fa-solid fa-magnifying-glass"></i> Search
                            </button>
                            <a href="enterprise_staff.php" class="staff-btn-outline">
                                <i class="fa-solid fa-xmark"></i> Clear
                            </a>
                        </div>
                    </div>

                    <div class="staff-actions-row">
                        <button type="button" class="staff-btn" onclick="showSection('add_section')">
                            <i class="fa-solid fa-user-plus"></i> Add Staff
                        </button>

                        <button type="button" class="staff-btn" onclick="showSection('edit_section')">
                            <i class="fa-solid fa-pen"></i> Edit Staff
                        </button>
                    </div>
                </form>
            </section>

            <div class="staff-table-wrap">
                <table class="staff-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Salary (RM)</th>
                            <th>Date of Birth</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (!empty($rows)): ?>
                            <?php foreach ($rows as $row): ?>
                                <tr>
                                    <td><?php echo (int)$row['staff_id']; ?></td>
                                    <td><?php echo htmlspecialchars($row['staff_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['staff_phone']); ?></td>
                                    <td><?php echo htmlspecialchars($row['staff_salary']); ?></td>
                                    <td><?php echo htmlspecialchars($row['staff_dob']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="staff-empty">No matching records found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Add Staff -->
            <section id="add_section" class="staff-section" style="display:none;">
                <div class="staff-section-head">
                    <h2><i class="fa-solid fa-user-plus"></i> Add New Staff</h2>
                    <p>Fill in the details below to create a staff account.</p>
                </div>

                <form method="POST" class="staff-form">
                    <div class="staff-grid-2">
                        <div class="staff-field">
                            <label for="add_name">Staff Name</label>
                            <input id="add_name" type="text" name="name" placeholder="Full name" required>
                        </div>

                        <div class="staff-field">
                            <label for="add_phone">Phone Number</label>
                            <input id="add_phone" type="text" name="phone" placeholder="Phone number" required>
                        </div>

                        <div class="staff-field">
                            <label for="add_salary">Salary (RM)</label>
                            <input id="add_salary" type="number" step="0.01" name="salary" placeholder="e.g. 1700.00" required>
                        </div>

                        <div class="staff-field">
                            <label for="add_dob">Date of Birth</label>
                            <input id="add_dob" type="date" name="dob" required>
                        </div>

                        <div class="staff-field">
                            <label for="add_user">Username</label>
                            <input id="add_user" type="text" name="user" placeholder="Username" required>
                        </div>

                        <div class="staff-field">
                            <label for="add_pass">Password</label>
                            <input id="add_pass" type="password" name="pass" placeholder="Password" required>
                        </div>
                    </div>

                    <div class="staff-form-actions">
                        <button type="submit" name="add_staff" class="staff-btn">
                            <i class="fa-solid fa-floppy-disk"></i> Save Staff
                        </button>
                    </div>
                </form>
            </section>

            <!-- Edit / Delete -->
            <section id="edit_section" class="staff-section" style="display:none;">
                <div class="staff-section-head">
                    <h2><i class="fa-solid fa-pen"></i> Edit / Delete Staff</h2>
                    <p>Select a staff record, then update fields or delete.</p>
                </div>

                <form method="POST" class="staff-form">
                    <div class="staff-grid">
                        <div class="staff-field">
                            <label for="staffSelect">Select Staff</label>
                            <select id="staffSelect" name="staff_id" required>
                                <option value="">Choose staff</option>
                                <?php foreach ($rows as $r): ?>
                                    <option value="<?php echo (int)$r['staff_id']; ?>">
                                        <?php echo htmlspecialchars($r['staff_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="staff-grid-2">
                        <div class="staff-field">
                            <label>Staff Name</label>
                            <input type="text" id="edit_name" name="name">
                        </div>

                        <div class="staff-field">
                            <label>Phone Number</label>
                            <input type="text" id="edit_phone" name="phone">
                        </div>

                        <div class="staff-field">
                            <label>Salary (RM)</label>
                            <input type="number" step="0.01" id="edit_salary" name="salary">
                        </div>

                        <div class="staff-field">
                            <label>Date of Birth</label>
                            <input type="date" id="edit_dob" name="dob">
                        </div>
                    </div>

                    <div class="staff-form-actions-split">
                        <button type="submit" name="delete_staff" class="staff-btn-danger"
                                data-confirm="Delete this staff record?"
                                data-confirm-text="This action will remove the staff record and re-index the IDs."
                                data-confirm-button="Delete Staff">
                            <i class="fa-solid fa-trash"></i> Delete
                        </button>

                        <div class="staff-form-actions">
                            <button type="button" class="staff-btn-outline" onclick="hideAll()">
                                <i class="fa-solid fa-xmark"></i> Discard
                            </button>
                            <button type="submit" name="edit_staff" class="staff-btn">
                                <i class="fa-solid fa-floppy-disk"></i> Save Changes
                            </button>
                        </div>
                    </div>
                </form>
            </section>

        </div>
    </section>
</main>

<?php include __DIR__ . '/staff_footer.php'; ?>

<script>
const STAFF = <?php echo json_encode($staff_map); ?>;

function showSection(id) {
    hideAll();
    const el = document.getElementById(id);
    if (el) el.style.display = 'block';
}

function hideAll() {
    const a = document.getElementById('add_section');
    const e = document.getElementById('edit_section');
    if (a) a.style.display = 'none';
    if (e) e.style.display = 'none';
}

document.getElementById('staffSelect')?.addEventListener('change', function () {
    const id = this.value;
    const data = STAFF[id] || {};

    document.getElementById('edit_name').value = data.staff_name || '';
    document.getElementById('edit_phone').value = data.staff_phone || '';
    document.getElementById('edit_salary').value = data.staff_salary || '';
    document.getElementById('edit_dob').value = data.staff_dob || '';
});
</script>

</body>
</html>
