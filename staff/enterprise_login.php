<?php
session_start();
require_once __DIR__ . '/../config.php';

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $user = $_POST['username'] ?? '';
        $pass = $_POST['password'] ?? '';

        $sql = "SELECT staff_id, staff_username
                FROM staff
                WHERE staff_username = ? AND staff_password = ?
                LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$user, $pass]);

        $staff = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($staff) {
            $_SESSION['valid_user'] = $staff['staff_username'];
            $_SESSION['staff_id'] = (int)$staff['staff_id'];
            header('Location: enterprise_stock.php');
            exit();
        }

        $error_message = 'Invalid Username or Password!';
    } catch (Throwable $e) {
        $error_message = 'Database error';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Staff Login</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
<script src="../js/theme.js"></script>
<link rel="stylesheet" href="../css/style.css">
</head>

<body class="auth-page staff-auth-page">

<button
    type="button"
    class="floating-theme-toggle theme-toggle"
    data-theme-toggle
    aria-label="Switch to dark theme"
    aria-pressed="false"
>
    <i class="fa-solid fa-moon" aria-hidden="true"></i>
    <span class="theme-toggle-label">Dark</span>
</button>

<section class="auth-card">
    <a href="../index.php" class="back-link" aria-label="Back to portal selection">
        <i class="fa-solid fa-arrow-left"></i> Back to Portal
    </a>

    <form method="POST">
        <div class="brand">
            <img src="../images/Nang-logo.png" alt="Nang Chicken Market">
            <h1>Nang Chicken Market</h1>
        </div>

        <div class="divider"></div>

        <h3>Staff Login</h3>

        <?php if (!empty($error_message)): ?>
            <p class="auth-error">
                <?= htmlspecialchars($error_message); ?>
            </p>
        <?php endif; ?>

        <div class="field">
            <label class="label" for="staffUsername">Username</label>
            <input
                type="text"
                name="username"
                id="staffUsername"
                class="box"
                placeholder="Enter your username"
                required
            >
        </div>

        <div class="field">
            <label class="label" for="staffPass">Password</label>
            <div class="password-wrap">
                <input
                    type="password"
                    name="password"
                    id="staffPass"
                    class="box"
                    placeholder="Enter your password"
                    required
                >
                <i
                    class="fa-solid fa-eye"
                    role="button"
                    tabindex="0"
                    aria-label="Show password"
                    onclick="togglePass(this)"
                    onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();togglePass(this);}"
                ></i>
            </div>
        </div>

        <button type="submit" class="btn">Login</button>

        <p>This login is for staff only.</p>
    </form>
</section>

<script>
function togglePass(icon) {
    const input = document.getElementById('staffPass');
    if (!input) return;

    const show = input.type === 'password';
    input.type = show ? 'text' : 'password';

    icon.classList.toggle('fa-eye', !show);
    icon.classList.toggle('fa-eye-slash', show);
    icon.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
}
</script>

</body>
</html>
