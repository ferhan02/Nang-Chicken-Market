<!-- =========================
     FOOTER STYLESHEET
========================= -->

<!--
   Footer-specific CSS file.
   Keeps footer styles separate from main layout.
-->

<!-- =========================
     FOOTER SECTION
========================= -->

<footer class="footer">

   <!--
      Main footer container.
      Uses the grid layout from the shared stylesheet.
   -->
   <section class="box-container">

      <!-- =========================
           BRAND / ABOUT SECTION
      ========================= -->

      <!--
         Brand name and short description.
         Helps users understand the website purpose.
      -->
      <div class="box footer-brand">
         <h3>Nang Chicken Market</h3>

         <p class="footer-desc">
            Fresh chicken cuts delivered with care. View your orders, manage your cart, and checkout securely.
         </p>
      </div>

      <!-- =========================
           QUICK LINKS
      ========================= -->

      <!--
         Main navigation links.
         Helps users move quickly around the site.
      -->
      <div class="box">
         <h3>Quick Links</h3>

         <a href="home.php">
            <i class="fas fa-angle-right"></i> Home
         </a>

         <a href="about.php">
            <i class="fas fa-angle-right"></i> About
         </a>

         <a href="contact.php">
            <i class="fas fa-angle-right"></i> Contact
         </a>
      </div>

      <!-- =========================
           EXTRA LINKS
      ========================= -->

      <!--
         Secondary links related to user actions.
         Often placed in footer instead of main menu.
      -->
      <div class="box">
         <h3>Extra Links</h3>

         <a href="cart.php">
            <i class="fas fa-angle-right"></i> Cart
         </a>

         <a href="login.php">
            <i class="fas fa-angle-right"></i> Login
         </a>

         <a href="register.php">
            <i class="fas fa-angle-right"></i> Register
         </a>
      </div>

      <!-- =========================
           CONTACT INFORMATION
      ========================= -->

      <!--
         Business contact details.
         Shown on every page via footer.
      -->
      <div class="box">
         <h3>Contact Info</h3>

         <!-- Phone contact 1 -->
         <p>
            <i class="fas fa-phone"></i>
            +60 018-3868171
            <span class="contact-name">Ferhan</span>
         </p>

         <!-- Email address -->
         <p>
            <i class="fas fa-envelope"></i>
            fmuriddan@gmail.com
         </p>

         <!-- Location -->
         <p>
            <i class="fas fa-map-marker-alt"></i>
            Malaysia
         </p>
      </div>

   </section>

   <!-- =========================
        FOOTER BOTTOM BAR
   ========================= -->

   <!--
      Bottom section with copyright.
      Uses PHP date() to always show current year.
   -->
   <div class="footer-bottom">
      <p class="credit">
         &copy; <?= date('Y'); ?> Nang Chicken Market Sdn. Bhd.
         [201501009497 (1134832-W)]. All Rights Reserved.
      </p>
   </div>

</footer>
