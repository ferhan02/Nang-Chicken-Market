<?php
/* =========================
   SESSION & CONFIG
========================= */

/*
   Start session.
   Needed to store login state.
*/
session_start();

/*
   Load database connection.
   Provides $conn for database queries.
*/
require_once __DIR__ . '/../config.php';

/*
   Variables to store feedback messages.
*/
$error = '';
$success = '';

/* =========================
   LOGIN PROCESS
========================= */

/*
   Run when login form is submitted.
*/
if (isset($_POST['login'])) {

    /*
       Read and clean user input.
    */
    $email = trim($_POST['email']);
    $pass  = $_POST['pass'];

    /*
       Look for user with this email.
    */
    $select = $conn->prepare(
        "SELECT customer_id, customer_password
         FROM customer
         WHERE customer_email = ?"
    );
    $select->execute([$email]);

    /*
       Check if exactly one account is found.
    */
    if ($select->rowCount() === 1) {

        /*
           Fetch user data.
        */
        $user = $select->fetch(PDO::FETCH_ASSOC);

        /*
           Compare entered password with stored password.
        */
        if ($pass === $user['customer_password']) {

            /*
               Save customer ID in session.
               This marks the user as logged in.
            */
            $_SESSION['customer_id'] = $user['customer_id'];

            /*
               Set success message.
            */
            $success = "Login successful!";

        } else {

            /*
               Password is incorrect.
            */
            $error = "Incorrect password.";
        }

    } else {

        /*
           No account found for this email.
        */
        $error = "Email not found.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- Page title -->
<title>Login</title>

<!-- Font Awesome icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">

<!-- SweetAlert for popup messages -->
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

<!-- =========================
     LOGIN FORM
========================= -->
<section class="auth-card">
<a href="../index.php" class="back-link" aria-label="Back to portal selection">
    <i class="fa-solid fa-arrow-left"></i> Back to Portal
</a>
<form method="POST">

    <!-- Brand -->
    <div class="brand">
        <img src="../images/Nang-logo.png" alt="Nang Chicken Market">
        <h1>Nang Chicken Market</h1>
    </div>

    <div class="divider"></div>

    <h3>Login</h3>

    <!-- Email field -->
    <div class="field">
        <label class="label">Email</label>
        <input
            type="email"
            name="email"
            class="box"
            placeholder="Enter your email address"
            required
        >
    </div>

    <!-- Password field -->
    <div class="field">
        <label class="label">Password</label>
        <div class="password-wrap">
            <input
                type="password"
                name="pass"
                id="loginPass"
                class="box"
                placeholder="Enter your password"
                required
            >
            <!-- Eye icon toggles password visibility -->
            <i class="fa-solid fa-eye" onclick="toggle('loginPass',this)"></i>
        </div>
    </div>

    <!-- Submit button -->
    <button type="submit" name="login" class="btn">Login</button>

    <!-- Register link -->
    <p>Don't have an account? <a href="register.php">Register</a></p>

</form>
</section>

<!-- =========================
     ERROR MESSAGE
========================= -->
<?php if (!empty($error)): ?>
<script>
Swal.fire({
    icon:'error',
    title:'Login Failed',
    text:'<?= htmlspecialchars($error) ?>'
});
</script>
<?php endif; ?>

<!-- =========================
     SUCCESS MESSAGE
========================= -->
<?php if (!empty($success)): ?>
<script>
Swal.fire({
    icon:'success',
    title:'Welcome',
    text:'<?= htmlspecialchars($success) ?>'
}).then(() => location.href='home.php');
</script>
<?php endif; ?>

<!-- =========================
     PASSWORD TOGGLE SCRIPT
========================= -->
<script>
/*
   Toggle password visibility.
*/
function toggle(id, icon){
    const i = document.getElementById(id);

    if (i.type === "password") {
        i.type = "text";
        icon.classList.replace("fa-eye","fa-eye-slash");
    } else {
        i.type = "password";
        icon.classList.replace("fa-eye-slash","fa-eye");
    }
}
</script>

<script src="../js/theme.js"></script>

</body>
</html>
