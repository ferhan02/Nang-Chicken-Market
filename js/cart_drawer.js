(function () {
  const drawer   = document.getElementById('cartDrawer');
  const overlay  = document.getElementById('cartDrawerOverlay');
  const closeBtn = document.getElementById('cartDrawerClose');
  const openBtn  = document.getElementById('cartDrawerOpen');

  if (!drawer || !overlay) return;

  function openDrawer() {
    drawer.classList.add('open');
    overlay.classList.add('open');
    drawer.setAttribute('aria-hidden', 'false');
    overlay.setAttribute('aria-hidden', 'false');
    document.documentElement.style.overflow = 'hidden';
  }

  function closeDrawer() {
    drawer.classList.remove('open');
    overlay.classList.remove('open');
    drawer.setAttribute('aria-hidden', 'true');
    overlay.setAttribute('aria-hidden', 'true');
    document.documentElement.style.overflow = '';
  }

  // Open from header cart icon (keep fallback navigation if drawer missing)
  if (openBtn) {
    openBtn.addEventListener('click', function (e) {
      e.preventDefault();
      openDrawer();
    });
  }

  if (closeBtn) closeBtn.addEventListener('click', closeDrawer);
  overlay.addEventListener('click', closeDrawer);

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeDrawer();
  });

  // Click product inside drawer -> close + scroll to products section
  drawer.addEventListener('click', function (e) {
    const btn = e.target.closest('.cart-drawer-item-link');
    if (!btn) return;

    const selector = btn.getAttribute('data-scroll-target') || '.products';
    closeDrawer();

    // Wait a tick so closing animation feels smooth before scrolling
    window.setTimeout(() => {
      const target = document.querySelector(selector);

      if (target) {
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      } else {
        // fallback: go home and let the browser jump near products
        window.location.href = 'home.php#products';
      }
    }, 120);
  });

  // Expose helpers if needed later
  window.NC_CartDrawer = {
    open: openDrawer,
    close: closeDrawer
  };
})();
