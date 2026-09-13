<?php
// Load global configuration file
// This typically contains database connection, constants, and shared settings
require_once __DIR__ . '/../config.php';

// Ensure session is started only once
// This prevents errors if session_start() is called multiple times
if (session_status() === PHP_SESSION_NONE) {
   session_start();
}

// Retrieve customer ID if user is logged in
// Not required for the About page, but kept for consistent layout/header behavior
$customer_id = $_SESSION['customer_id'] ?? null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <!-- Character encoding -->
   <meta charset="UTF-8">

   <!-- Compatibility for older browsers -->
   <meta http-equiv="X-UA-Compatible" content="IE=edge">

   <!-- Responsive scaling for mobile devices -->
   <meta name="viewport" content="width=device-width, initial-scale=1.0">

   <!-- Page title shown in browser tab -->
   <title>About Us - Nang Chicken Market</title>

   <!-- Font Awesome icons library (used for stars, check icons, etc.) -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

   <!-- Main shared stylesheet -->

   <!-- Page-specific styling for About page -->
<script src="../js/theme.js"></script>
<link rel="stylesheet" href="../css/style.css">
</head>

<!-- Body class allows page-specific styling via CSS -->
<body class="about-page">

<!-- Include site-wide header (navigation, logo, login/cart, etc.) -->
<?php include 'header.php'; ?>

<!-- ================= HERO SECTION ================= -->
<!-- 
   Purpose:
   - First visual section users see
   - Introduces brand identity and business focus
   - Contains primary calls to action
-->
<section class="about-hero">
   <div class="about-hero-inner">

      <!-- Textual introduction and branding -->
      <div class="about-hero-text">
         <p class="kicker">About Nang Chicken Market</p>

         <!-- Main headline communicating trust and longevity -->
         <h1>Fresh Poultry, Trusted Since 1996</h1>

         <!-- Short descriptive paragraph explaining business value -->
         <p class="lead">
            We supply high-quality chicken cuts for households and restaurants — sourced daily, handled hygienically,
            and delivered reliably through our online ordering system.
         </p>

         <!-- Call-to-action buttons -->
         <!-- One leads to contact, the other to product browsing -->
         <div class="hero-actions">
            <a href="contact.php" class="btn hero-btn">Contact Us</a>
            <a href="home.php" class="option-btn hero-btn-outline">Browse Products</a>
         </div>
      </div>

      <!-- Visual support image for branding and freshness -->
      <!-- aria-label improves accessibility for assistive technologies -->
      <div class="about-hero-media" aria-label="Fresh chicken products">
         <img src="../images/about-img-1.png" alt="Fresh chicken cuts from Nang Chicken Market">
      </div>

   </div>
</section>

<!-- ================= ABOUT / FEATURES SECTION ================= -->
<!-- 
   Purpose:
   - Explains what differentiates the business
   - Breaks information into readable feature cards
-->
<section class="about">

   <!-- Section heading -->
   <h2 class="section-title">What Makes Us Different</h2>

   <!-- Supporting subtitle -->
   <p class="section-subtitle">
      Quality you can trust, pricing you can rely on, and service built for convenience.
   </p>

   <!-- Grid layout containing feature cards -->
   <div class="about-grid">

      <!-- Feature Card 1: Trust, experience, and sourcing -->
      <article class="about-card">

         <!-- Feature image -->
         <div class="about-card-media">
            <img src="../images/about-img-1.png" alt="Fresh chicken sourced daily">
         </div>

         <!-- Feature content -->
         <div class="about-card-body">
            <h3>Why Choose Us</h3>

            <!-- Business background explanation -->
            <p>
               Since 1996, Nang Chicken Market has been a trusted source of high-quality poultry products for both
               households and restaurants. We source fresh chickens daily from certified local suppliers, ensuring
               hygiene and top quality for every order.
            </p>

            <!-- Bullet list used for scannable key points -->
            <ul class="about-bullets">
               <li><i class="fas fa-check-circle"></i> Daily fresh supply</li>
               <li><i class="fas fa-check-circle"></i> Hygienic handling</li>
               <li><i class="fas fa-check-circle"></i> Reliable delivery</li>
            </ul>

            <!-- Navigation to contact page -->
            <a href="contact.php" class="btn">Contact Us</a>
         </div>
      </article>

      <!-- Feature Card 2: Product range and ordering system -->
      <article class="about-card">

         <!-- Feature image -->
         <div class="about-card-media">
            <img src="../images/about-img-2.png" alt="Variety of chicken parts available">
         </div>

         <!-- Feature content -->
         <div class="about-card-body">
            <h3>What We Provide</h3>

            <!-- Description of services and convenience -->
            <p>
               We offer a wide variety of chicken parts including breast, wings, thighs, and feet. With our online
               ordering system, customers can conveniently place orders from home or restaurant without visiting the market.
            </p>

            <!-- Product/service highlights -->
            <ul class="about-bullets">
               <li><i class="fas fa-check-circle"></i> Breast, wings, thighs and more</li>
               <li><i class="fas fa-check-circle"></i> Simple online ordering</li>
               <li><i class="fas fa-check-circle"></i> Fast, efficient fulfillment</li>
            </ul>

            <!-- Navigation to product listing page -->
            <a href="home.php" class="btn">Shop Now</a>
         </div>
      </article>

   </div>
</section>

<!-- ================= CUSTOMER REVIEWS SECTION ================= -->
<!-- 
   Purpose:
   - Builds trust and credibility
   - Displays social proof from real customers
-->
<section class="reviews">

   <!-- Section heading -->
   <h2 class="section-title">Customer Reviews</h2>

   <!-- Supporting subtitle -->
   <p class="section-subtitle">Real feedback from customers who order regularly.</p>

   <!-- Grid layout for review cards -->
   <div class="reviews-grid">

      <!-- Individual review card -->
      <div class="review-card">
         <div class="review-top">
            <img src="../images/pic-1.jpg" alt="Customer Alif" class="review-avatar">
            <div>
               <h3>Alif</h3>

               <!-- Star rating using Font Awesome icons -->
               <div class="stars" aria-label="5 out of 5 stars">
                  <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                  <i class="fas fa-star"></i><i class="fas fa-star"></i>
               </div>
            </div>
         </div>

         <!-- Review content -->
         <p class="review-text">
            The chicken I ordered was so fresh and well-packed! Delivery was on time, and the meat quality is always excellent.
            Highly recommend Nang Chicken Market!
         </p>
      </div>

      <div class="review-card">
         <div class="review-top">
            <img src="../images/pic-2.jpg" alt="Customer Syasya" class="review-avatar">
            <div>
               <h3>Syasya</h3>
               <div class="stars" aria-label="5 out of 5 stars">
                  <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                  <i class="fas fa-star"></i><i class="fas fa-star"></i>
               </div>
            </div>
         </div>
         <p class="review-text">Ordering was straightforward and the portions arrived neatly packed. The wings cooked really well and tasted fresh.</p>
      </div>

      <div class="review-card">
         <div class="review-top">
            <img src="../images/pic-3.jpg" alt="Customer Hafiz" class="review-avatar">
            <div>
               <h3>Hafiz</h3>
               <div class="stars" aria-label="4 out of 5 stars">
                  <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                  <i class="fas fa-star"></i><i class="far fa-star"></i>
               </div>
            </div>
         </div>
         <p class="review-text">Good prices and reliable quality. My order took a little longer during the busy hour, but everything arrived in good condition.</p>
      </div>

      <div class="review-card">
         <div class="review-top">
            <img src="../images/pic-4.jpg" alt="Customer Sabrina" class="review-avatar">
            <div>
               <h3>Sabrina</h3>
               <div class="stars" aria-label="5 out of 5 stars">
                  <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                  <i class="fas fa-star"></i><i class="fas fa-star"></i>
               </div>
            </div>
         </div>
         <p class="review-text">I like being able to check the available stock before ordering. The chicken breast was clean, fresh, and easy to prepare.</p>
      </div>

      <div class="review-card">
         <div class="review-top">
            <img src="../images/pic-5.jpg" alt="Customer Syafiq" class="review-avatar">
            <div>
               <h3>Syafiq</h3>
               <div class="stars" aria-label="5 out of 5 stars">
                  <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                  <i class="fas fa-star"></i><i class="fas fa-star"></i>
               </div>
            </div>
         </div>
         <p class="review-text">The website makes repeat orders much easier for my family. Clear prices, helpful updates, and consistently fresh products.</p>
      </div>

      <div class="review-card">
         <div class="review-top">
            <img src="../images/pic-6.jpg" alt="Customer Emily Wong" class="review-avatar">
            <div>
               <h3>Emily Wong</h3>
               <div class="stars" aria-label="4 out of 5 stars">
                  <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                  <i class="fas fa-star"></i><i class="far fa-star"></i>
               </div>
            </div>
         </div>
         <p class="review-text">Simple checkout and friendly service. I would love to see more delivery time options, but the product quality was excellent.</p>
      </div>

   </div>
</section>

<!-- Include site-wide footer -->
<?php include 'footer.php'; ?>

<!-- Global JavaScript file -->
<script src="../js/script.js"></script>
</body>
</html>
