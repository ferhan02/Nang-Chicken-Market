<?php
/* =========================
   SESSION & CONFIG
========================= */

/*
   Start a session.
   This is used to store temporary messages
   like success or error alerts.
*/
session_start();

/*
   Show PHP errors (useful during development).
*/
ini_set('display_errors', 1);
error_reporting(E_ALL);

/*
   Load the shared database connection.
   This file creates $conn as a PDO object.
*/
require_once __DIR__ . '/../config.php';

/* =========================
   REGISTRATION PROCESS
========================= */

/*
   Run only when the register form is submitted.
*/
if (isset($_POST['submit'])) {

    /*
       Read user input safely.
       trim() removes extra spaces.
    */
    $name       = trim($_POST['name'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $password   = $_POST['password'] ?? '';
    $cpassword  = $_POST['cpassword'] ?? '';

    /*
       Sanitize text input.
       This prevents HTML or script injection.
    */
    $name  = filter_var($name, FILTER_SANITIZE_SPECIAL_CHARS);
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);

    /*
       Basic validation checks.
    */
    if ($name === '' || $email === '' || $password === '' || $cpassword === '') {
        $_SESSION['reg_error'] = "Please fill in all fields!";
    }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['reg_error'] = "Invalid email address!";
    }
    elseif ($password !== $cpassword) {
        $_SESSION['reg_error'] = "Passwords do not match!";
    }
    else {

        /*
           Check if the email already exists.
           Uses a prepared statement to prevent SQL injection.
        */
        $check = $conn->prepare(
            "SELECT 1 FROM customer WHERE customer_email = ? LIMIT 1"
        );
        $check->execute([$email]);

        if ($check->fetchColumn()) {
            $_SESSION['reg_error'] = "Email already registered!";
        } else {

            /*
               Insert the new customer into the database.
               Password is stored as plain text in this project.
            */
            $insert = $conn->prepare(
                "INSERT INTO customer (customer_name, customer_email, customer_password)
                 VALUES (?, ?, ?)"
            );
            $insert->execute([$name, $email, $password]);

            /*
               Store success message in session.
               This will be shown using SweetAlert.
            */
            $_SESSION['reg_success'] =
                "Registration successful! Click OK to go to login page.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />

<!-- Makes the layout responsive on mobile -->
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>Create Account</title>

<!-- SweetAlert2 for popup messages -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Font Awesome icons -->
<link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
<script src="../js/theme.js"></script>
<link rel="stylesheet" href="../css/style.css">
</head>

<body class="auth-page staff-auth-page">

<!-- =========================
     REGISTER CARD
========================= -->
<section class="auth-card">
    <a href="../index.php" class="back-link" aria-label="Back to portal selection">
        <i class="fa-solid fa-arrow-left"></i> Back to Portal
    </a>

    <!-- Brand -->
    <div class="brand">
        <img src="../images/Nang-logo.png" alt="Nang Chicken Market">
        <h1>Nang Chicken Market</h1>
    </div>

    <div class="divider"></div>

    <h2 class="title">Create Account</h2>
    <div class="subtitle">
        Register to place orders and track your order history.
    </div>

    <!-- Registration Form -->
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
                <i class="fa-solid fa-eye eye" onclick="toggle('p1', this)"></i>
            </div>
        </div>

        <div>
            <label class="label" for="p2">Confirm Password</label>
            <div class="password-wrap">
                <input id="p2" type="password" name="cpassword" class="input" required>
                <i class="fa-solid fa-eye eye" onclick="toggle('p2', this)"></i>
            </div>
        </div>

        <button type="submit" name="submit" class="btn">Register</button>

        <div class="footer-text">
            Already have an account? <a href="enterprise_login.php">Staff Login</a>
        </div>

    </form>
</section>

<!-- =========================
     PASSWORD TOGGLE SCRIPT
========================= -->
<script>
/*
   Toggle password visibility.
*/
function toggle(id, icon){
    const input = document.getElementById(id);
    if (!input) return;

    if (input.type === "password") {
        input.type = "text";
        icon.classList.replace("fa-eye","fa-eye-slash");
    } else {
        input.type = "password";
        icon.classList.replace("fa-eye-slash","fa-eye");
    }
}
</script>

<!-- =========================
     ALERT MESSAGES
========================= -->

<?php if(isset($_SESSION['reg_error'])): ?>
<script>
Swal.fire({
    icon:'error',
    title:'Error',
    text:'<?= addslashes($_SESSION['reg_error']); ?>'
});
</script>
<?php unset($_SESSION['reg_error']); endif; ?>

<?php if(isset($_SESSION['reg_success'])): ?>
<script>
Swal.fire({
    icon:'success',
    title:'Success',
    text:'<?= addslashes($_SESSION['reg_success']); ?>',
    confirmButtonText:'Go to Login'
}).then(() => {
    window.location.href = 'enterprise_login.php';
});
</script>
<?php unset($_SESSION['reg_success']); endif; ?>

</body>
</html>
