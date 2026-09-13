<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config.php';

if (isset($_POST['submit'])) {
    $name      = trim($_POST['name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $cpassword = $_POST['cpassword'] ?? '';

    $name  = filter_var($name, FILTER_SANITIZE_SPECIAL_CHARS);
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);

    if ($name === '' || $email === '' || $password === '' || $cpassword === '') {
        $_SESSION['reg_error'] = 'Please fill in all fields!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['reg_error'] = 'Invalid email address!';
    } elseif ($password !== $cpassword) {
        $_SESSION['reg_error'] = 'Passwords do not match!';
    } else {
        $check = $conn->prepare(
            "SELECT 1 FROM customer WHERE customer_email = ? LIMIT 1"
        );
        $check->execute([$email]);

        if ($check->fetchColumn()) {
            $_SESSION['reg_error'] = 'Email already registered!';
        } else {
            $insert = $conn->prepare(
                "INSERT INTO customer (customer_name, customer_email, customer_password)
                 VALUES (?, ?, ?)"
            );
            $insert->execute([$name, $email, $password]);

            $_SESSION['reg_success'] =
                'Registration successful! Click OK to go to login page.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create Account</title>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

    <div class="brand">
        <img src="../images/Nang-logo.png" alt="Nang Chicken Market">
        <h1>Nang Chicken Market</h1>
    </div>

    <div class="divider"></div>

    <h2 class="title">Create Account</h2>
    <div class="subtitle">
        Register to place orders and track your order history.
    </div>

    <form method="POST" class="form-grid">

        <div>
            <label class="label" for="name">Full Name</label>
            <input id="name" type="text" name="name" class="input" required>
        </div>

        <div>
            <label class="label" for="email">Email</label>
            <input id="email" type="email" name="email" class="input" required>
        </div>

        <div>
            <label class="label" for="p1">Password</label>
            <div class="password-wrap">
                <input id="p1" type="password" name="password" class="input" required>
                <i
                    class="fa-solid fa-eye eye"
                    role="button"
                    tabindex="0"
                    aria-label="Show password"
                    onclick="togglePassword('p1', this)"
                    onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();togglePassword('p1',this);}"
                ></i>
            </div>
        </div>

        <div>
            <label class="label" for="p2">Confirm Password</label>
            <div class="password-wrap">
                <input id="p2" type="password" name="cpassword" class="input" required>
                <i
                    class="fa-solid fa-eye eye"
                    role="button"
                    tabindex="0"
                    aria-label="Show password"
                    onclick="togglePassword('p2', this)"
                    onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();togglePassword('p2',this);}"
                ></i>
            </div>
        </div>

        <button type="submit" name="submit" class="btn">Register</button>

        <div class="footer-text">
            Already have an account? <a href="enterprise_login.php">Staff Login</a>
        </div>

    </form>
</section>

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

<?php if (isset($_SESSION['reg_error'])): ?>
<script>
Swal.fire({
    icon: 'error',
    title: 'Error',
    text: <?= json_encode($_SESSION['reg_error']); ?>
});
</script>
<?php unset($_SESSION['reg_error']); endif; ?>

<?php if (isset($_SESSION['reg_success'])): ?>
<script>
Swal.fire({
    icon: 'success',
    title: 'Success',
    text: <?= json_encode($_SESSION['reg_success']); ?>,
    confirmButtonText: 'Go to Login'
}).then(() => {
    window.location.href = 'enterprise_login.php';
});
</script>
<?php unset($_SESSION['reg_success']); endif; ?>

</body>
</html>
