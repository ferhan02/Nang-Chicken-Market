<?php

/* =========================
   CONFIG & SESSION
========================= */

/*
   Load configuration file.
   This gives access to database connection ($conn).
*/
require_once __DIR__ . '/../config.php';

/*
   Start session so we can read login data.
*/
session_start();

/*
   user_id:
   - Used for message table
   - Can be null if user is not logged in
*/
$user_id = $_SESSION['user_id'] ?? null;

/*
   customer_id:
   - Used for main customer login
   - Used to prefill contact form
*/
$customer_id = $_SESSION['customer_id'] ?? null;

/* =========================
   PREFILL DEFAULT VALUES
========================= */

/*
   Default values for form fields.
   These are used if user is logged in.
*/
$prefill_name  = '';
$prefill_email = '';
$prefill_phone = '';

/*
   If customer is logged in,
   fetch their data to prefill the form.
*/
if ($customer_id) {

   $stmt = $conn->prepare(
      "SELECT customer_name, customer_email, customer_phone
       FROM customer
       WHERE customer_id = ?"
   );
   $stmt->execute([$customer_id]);

   /*
      Fetch customer record.
   */
   $cust = $stmt->fetch(PDO::FETCH_ASSOC);

   if ($cust) {
      $prefill_name  = $cust['customer_name'] ?? '';
      $prefill_email = $cust['customer_email'] ?? '';
      $prefill_phone = $cust['customer_phone'] ?? '';
   }
}

/*
   Alert data for SweetAlert popup.
*/
$alert = null;

/* =========================
   FORM STATE PRESERVATION
========================= */

/*
   Keep form values if submission fails.
   This avoids clearing the form on error.
*/
$form_name   = $prefill_name;
$form_email  = $prefill_email;
$form_number = $prefill_phone;
$msg_raw     = '';

/* =========================
   HANDLE FORM SUBMISSION
========================= */

if (isset($_POST['send'])) {

   /*
      Read form input and remove extra spaces.
   */
   $form_name   = trim($_POST['name'] ?? '');
   $form_email  = trim($_POST['email'] ?? '');
   $form_number = trim($_POST['number'] ?? '');
   $msg_raw     = trim($_POST['msg'] ?? '');

   /*
      Basic validation:
      - All fields must be filled
   */
   if ($form_name === '' || $form_email === '' || $form_number === '' || $msg_raw === '') {

      $alert = [
         'type' => 'error',
         'text' => 'Please fill in all fields.'
      ];

   /*
      Validate email format.
   */
   } elseif (!filter_var($form_email, FILTER_VALIDATE_EMAIL)) {

      $alert = [
         'type' => 'error',
         'text' => 'Please enter a valid email address.'
      ];

   } else {

      /* =========================
         DUPLICATE MESSAGE CHECK
      ========================= */

      /*
         Check if the same message
         has already been sent.
      */
      $select_message = $conn->prepare(
         "SELECT 1 FROM `message`
          WHERE name = ? AND email = ? AND number = ? AND message = ?"
      );
      $select_message->execute([
         $form_name,
         $form_email,
         $form_number,
         $msg_raw
      ]);

      if ($select_message->rowCount() > 0) {

         /*
            Prevent sending duplicate messages.
         */
         $alert = [
            'type' => 'warning',
            'text' => 'You already sent this message!'
         ];

      } else {

         /* =========================
            INSERT MESSAGE
         ========================= */

         /*
            Save message into database.
            user_id can be null if user is not logged in.
         */
         $insert_message = $conn->prepare(
            "INSERT INTO `message` (user_id, name, email, number, message)
             VALUES (?, ?, ?, ?, ?)"
         );
         $insert_message->execute([
            $user_id,
            $form_name,
            $form_email,
            $form_number,
            $msg_raw
         ]);

         /*
            Show success message
            and clear message field.
         */
         $alert = [
            'type' => 'success',
            'text' => 'Message sent successfully!'
         ];
         $msg_raw = '';
      }
   }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">

   <!-- Responsive layout -->
   <meta name="viewport" content="width=device-width, initial-scale=1.0">

   <title>Contact - Nang Chicken Market</title>

   <!-- Icons -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

   <!-- Shared site styles -->

   <!-- Contact page styles -->

   <!-- SweetAlert for popup messages -->
   <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../js/theme.js"></script>
<link rel="stylesheet" href="../css/style.css">
</head>

<body class="contact-page">

<!-- Site header -->
<?php include 'header.php'; ?>

<!-- =========================
     CONTACT HERO SECTION
========================= -->
<section class="contact-hero">
   <div class="contact-hero-inner">

      <!-- LEFT SIDE: TEXT & CONTACT INFO -->
      <div class="contact-hero-text">
         <p class="kicker">Contact</p>
         <h1>Get in Touch</h1>

         <p class="lead">
            Have a question about your order, delivery, or products?
            Send us a message and we’ll respond as soon as possible.
         </p>

         <!-- Contact details -->
         <div class="contact-info">

            <!-- Phone -->
            <div class="info-item">
               <i class="fas fa-phone"></i>
               <div>
                  <div class="info-label">Phone</div>
                  <div class="info-value">
                     +60 018-3868171 (Ferhan)
                  </div>
               </div>
            </div>

            <!-- Email -->
            <div class="info-item">
               <i class="fas fa-envelope"></i>
               <div>
                  <div class="info-label">Email</div>
                  <div class="info-value">fmuriddan@gmail.com</div>
               </div>
            </div>

            <!-- Location -->
            <div class="info-item">
               <i class="fas fa-map-marker-alt"></i>
               <div>
                  <div class="info-label">Location</div>
                  <div class="info-value">Malaysia</div>
               </div>
            </div>

         </div>
      </div>

      <!-- RIGHT SIDE: CONTACT FORM -->
      <div class="contact-hero-card">
         <h2>Send Us a Message</h2>
         <p class="small">Questions, feedback, or order concerns? Send us a message and we’ll be happy to help.</p>

         <!-- Contact form -->
         <form method="POST" class="contact-form" novalidate>

            <div class="form-grid">

               <!-- Name -->
               <div class="field">
                  <label for="name">Full Name</label>
                  <input type="text" id="name" name="name" class="box" required
                         value="<?= htmlspecialchars($form_name); ?>">
               </div>

               <!-- Email -->
               <div class="field">
                  <label for="email">Email Address</label>
                  <input type="email" id="email" name="email" class="box" required
                         value="<?= htmlspecialchars($form_email); ?>">
               </div>

               <!-- Phone -->
               <div class="field">
                  <label for="number">Phone Number</label>
                  <input type="text" id="number" name="number" class="box" required
                         value="<?= htmlspecialchars($form_number); ?>">
               </div>

               <!-- Message -->
               <div class="field field-full">
                  <label for="msg">Message</label>
                  <textarea id="msg" name="msg" class="box" required rows="7"><?= htmlspecialchars($msg_raw); ?></textarea>
               </div>

            </div>

            <!-- Submit button -->
            <button type="submit" class="btn send-btn" name="send">
               <i class="fas fa-paper-plane"></i> Send Message
            </button>
         </form>
      </div>

   </div>
</section>

<?php include 'footer.php'; ?>

<!-- Global JS -->
<script src="../js/script.js"></script>

<!-- =========================
     ALERT MESSAGE
========================= -->
<?php if ($alert): ?>
<script>
Swal.fire({
   icon: <?= json_encode($alert['type']); ?>,
   title: <?= json_encode(
      $alert['type'] === 'success'
         ? 'Success'
         : ($alert['type'] === 'warning' ? 'Notice' : 'Error')
   ); ?>,
   text: <?= json_encode($alert['text']); ?>,
   confirmButtonText: 'OK',
   width: 480
});
</script>
<?php endif; ?>

</body>
</html>
