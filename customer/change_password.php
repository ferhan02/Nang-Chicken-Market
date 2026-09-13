<?php
/* =========================
   CONFIG & SESSION
========================= */

/*
   Load configuration file.
   This gives access to:
   - Database connection ($conn)
   - Any global settings
*/
require_once __DIR__ . '/../config.php';

/*
   Start session so we can read login data.
*/
session_start();

/* -------------------------------
| Authentication check
---------------------------------*/

/*
   Get logged-in customer ID from session.
*/
$customer_id = $_SESSION['customer_id'] ?? null;

/*
   If user is not logged in,
   redirect them to login page.
*/
if (!$customer_id) {
    header('location:login.php');
    exit;
}

/* -------------------------------
| Fetch customer password
---------------------------------*/

/*
   Get the current password of the logged-in user
   from the database.
*/
$select_profile = $conn->prepare(
    "SELECT customer_password FROM customer WHERE customer_id = ?"
);
$select_profile->execute([$customer_id]);

/*
   Fetch result as an associative array.
*/
$fetch_profile = $select_profile->fetch(PDO::FETCH_ASSOC);

/*
   If user record is not found,
   log the user out for safety.
*/
if (!$fetch_profile) {
    header('location:logout.php');
    exit;
}

/*
   Array to store messages
   that will be shown using SweetAlert.
*/
$message = [];

/* -------------------------------
| Handle password update
---------------------------------*/

/*
   This runs when the user submits the form.
*/
if (isset($_POST['update_password'])) {

    /*
       Read form inputs and remove extra spaces.
    */
    $current_pass = trim($_POST['current_pass'] ?? '');
    $new_pass     = trim($_POST['new_pass'] ?? '');
    $confirm_pass = trim($_POST['confirm_pass'] ?? '');

    /*
       Check if current password matches
       the password stored in database.
    */
    if ($current_pass !== $fetch_profile['customer_password']) {

        $message[] = [
            'type' => 'error',
            'text' => 'Current password is incorrect.'
        ];

    /*
       Check if new password and confirmation match.
    */
    } elseif ($new_pass !== $confirm_pass) {

        $message[] = [
            'type' => 'error',
            'text' => 'New passwords do not match.'
        ];

    /*
       Prevent empty password.
    */
    } elseif (empty($new_pass)) {

        $message[] = [
            'type' => 'error',
            'text' => 'New password cannot be empty.'
        ];

    /*
       If all checks pass, update password.
    */
    } else {

        $update = $conn->prepare(
            "UPDATE customer SET customer_password = ? WHERE customer_id = ?"
        );

        if ($update->execute([$new_pass, $customer_id])) {

            $message[] = [
                'type' => 'success',
                'text' => 'Password updated successfully.'
            ];

        } else {

            $message[] = [
                'type' => 'error',
                'text' => 'Failed to update password.'
            ];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- Page title -->
<title>Change Password</title>

<!-- Font Awesome icons (eye icon) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

<!-- Shared component styles -->

<!-- SweetAlert2 for pop-up messages -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../js/theme.js"></script>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>

<!-- Site header -->
<?php include 'header.php'; ?>

<!-- =========================
     CHANGE PASSWORD FORM
========================= -->
<section class="profile-container">
<a href="my_profile.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Back to Profile</a>
<h1>Change Password</h1>

<form method="POST">

    <!-- Current password -->
    <div class="profile-field">
        <label for="current_pass">Current Password:</label>
        <div class="input-wrapper">
            <input type="password" name="current_pass" id="current_pass" required>
            <!-- Eye icon toggles password visibility -->
            <i class="fa fa-eye eye-icon" onclick="togglePassword('current_pass', this)"></i>
        </div>
    </div>

    <!-- New password -->
    <div class="profile-field">
        <label for="new_pass">New Password:</label>
        <div class="input-wrapper">
            <input type="password" name="new_pass" id="new_pass" required>
            <i class="fa fa-eye eye-icon" onclick="togglePassword('new_pass', this)"></i>
        </div>
    </div>

    <!-- Confirm password -->
    <div class="profile-field">
        <label for="confirm_pass">Confirm New Password:</label>
        <div class="input-wrapper">
            <input type="password" name="confirm_pass" id="confirm_pass" required>
            <i class="fa fa-eye eye-icon" onclick="togglePassword('confirm_pass', this)"></i>
        </div>
    </div>

    <!-- Action buttons -->
    <div class="profile-field">
        <button type="submit" name="update_password" class="btn">Confirm</button>
        <button type="button" class="staff-btn-outline" onclick="window.location.href='my_profile.php'">Cancel</button>
    </div>
</form>
</section>

<?php include 'footer.php'; ?>

<script>
/*
   Show or hide password text.
   Used for better user experience.
*/
function togglePassword(fieldId, icon) {
    const input = document.getElementById(fieldId);

    if (input.type === "password") {
        input.type = "text";
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = "password";
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

/*
   Show success or error messages using SweetAlert.
   Messages come from PHP $message array.
*/
<?php if(!empty($message)): ?>
    <?php foreach($message as $msg): ?>
        Swal.fire({
            icon: '<?= $msg['type']; ?>',
            title: '<?= $msg['type']==='success' ? 'Success' : 'Error'; ?>',
            text: '<?= $msg['text']; ?>',
            confirmButtonColor:'#3085d6',
            width:'450px'
        });
    <?php endforeach; ?>
<?php endif; ?>
</script>

</body>
</html>
