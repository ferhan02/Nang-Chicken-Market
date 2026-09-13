<?php
session_start();
require_once __DIR__ . '/../config.php';

if (isset($_POST['submit'])) {

    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $cpassword = $_POST['cpassword'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['reg_error'] = "Invalid email address!";
    } elseif ($password !== $cpassword) {
        $_SESSION['reg_error'] = "Passwords do not match!";
    } else {

        $check = $conn->prepare(
            "SELECT 1 FROM customer WHERE customer_email = ?"
        );
        $check->execute([$email]);

        if ($check->rowCount() > 0) {
            $_SESSION['reg_error'] = "Email already registered!";
        } else {
            $insert = $conn->prepare(
                "INSERT INTO customer (customer_name, customer_email, customer_password)
                 VALUES (?, ?, ?)"
            );
            $insert->execute([$name, $email, $password]);

            $_SESSION['reg_success'] =
                "Registration successful! Click OK to log in.";
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

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../js/theme.js"></script>
<link rel="stylesheet" href="../css/style.css">
</head>

<body class="auth-page">

<button
    type="button"
    class="floating-theme-toggle theme-toggle"
    aria-label="Toggle dark mode"
>
    <i class="fa-solid fa-moon"></i>
    <span>Dark Mode</span>
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

    <h3>Create Account</h3>
    <div class="sub">
        Register to place orders and track your order history.
    </div>

    <div class="field">
        <label class="label">Full Name</label>
        <input type="text" name="name" class="box"
               placeholder="Enter your full name" required>
    </div>

    <div class="field">
        <label class="label">Email</label>
        <input type="email" name="email" class="box"
               placeholder="Enter your email address" required>
    </div>

    <div class="field">
        <label class="label">Password</label>
        <div class="password-wrap">
            <input type="password" name="password" id="p1" class="box"
                   placeholder="Enter your password" required>
            <i class="fa-solid fa-eye" onclick="toggle('p1',this)"></i>
        </div>
    </div>

    <div class="field">
        <label class="label">Confirm Password</label>
        <div class="password-wrap">
            <input type="password" name="cpassword" id="p2" class="box"
                   placeholder="Confirm your password" required>
            <i class="fa-solid fa-eye" onclick="toggle('p2',this)"></i>
        </div>
    </div>

    <button type="submit" name="submit" class="btn">Register</button>

    <p>Already have an account? <a href="login.php">Login</a></p>

</form>
</section>

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
    text:'<?= addslashes($_SESSION['reg_success']); ?>'
}).then(()=>location.href='login.php');
</script>
<?php unset($_SESSION['reg_success']); endif; ?>

<script>
function toggle(id, icon){
    const i=document.getElementById(id);
    if(i.type==="password"){
        i.type="text";
        icon.classList.replace("fa-eye","fa-eye-slash");
    }else{
        i.type="password";
        icon.classList.replace("fa-eye-slash","fa-eye");
    }
}
</script>

<script src="../js/theme.js"></script>

</body>
</html>
