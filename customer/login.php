<?php
session_start();
require_once __DIR__ . '/../config.php';

$error = '';
$success = '';

if (isset($_POST['login'])) {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['pass'] ?? '';

    $select = $conn->prepare(
        "SELECT customer_id, customer_password
         FROM customer
         WHERE customer_email = ?"
    );
    $select->execute([$email]);

    if ($select->rowCount() === 1) {
        $user = $select->fetch(PDO::FETCH_ASSOC);

        if ($pass === $user['customer_password']) {
            $_SESSION['customer_id'] = $user['customer_id'];
            $success = 'Login successful!';
        } else {
            $error = 'Incorrect password.';
        }
    } else {
        $error = 'Email not found.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../js/theme.js"></script>
<link rel="stylesheet" href="../css/style.css">
</head>

<body class="auth-page">

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

        <h3>Login</h3>

        <div class="field">
            <label class="label" for="loginEmail">Email</label>
            <input
                type="email"
                name="email"
                id="loginEmail"
                class="box"
                placeholder="Enter your email address"
                required
            >
        </div>

        <div class="field">
            <label class="label" for="loginPass">Password</label>
            <div class="password-wrap">
                <input
                    type="password"
                    name="pass"
                    id="loginPass"
                    class="box"
                    placeholder="Enter your password"
                    required
                >
                <i
                    class="fa-solid fa-eye"
                    role="button"
                    tabindex="0"
                    aria-label="Show password"
                    onclick="togglePassword('loginPass', this)"
                    onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();togglePassword('loginPass',this);}"
                ></i>
            </div>
        </div>

        <button type="submit" name="login" class="btn">Login</button>

        <p>Don't have an account? <a href="register.php">Register</a></p>
    </form>
</section>

<?php if (!empty($error)): ?>
<script>
Swal.fire({
    icon: 'error',
    title: 'Login Failed',
    text: <?= json_encode($error); ?>
});
</script>
<?php endif; ?>

<?php if (!empty($success)): ?>
<script>
Swal.fire({
    icon: 'success',
    title: 'Welcome',
    text: <?= json_encode($success); ?>
}).then(() => {
    window.location.href = 'home.php';
});
</script>
<?php endif; ?>

<script>
function togglePassword(id, icon) {
    const input = document.getElementById(id);
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
