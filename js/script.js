/* =========================================================
   HEADER (MENU + PROFILE) - SINGLE SOURCE OF TRUTH
   Fixes dropdown not opening due to conflicts/null selectors
========================================================= */
document.addEventListener('DOMContentLoaded', function () {
   // prevent duplicate init if this file loads more than once
   if (window.__NC_SCRIPT_HEADER_INIT__) return;
   window.__NC_SCRIPT_HEADER_INIT__ = true;

   const header  = document.querySelector('.header');
   const navbar  = document.querySelector('.header .flex .navbar');
   const profile = document.querySelector('.header .flex .profile');
   const menuBtn = document.querySelector('#menu-btn');
   const userBtn = document.querySelector('#user-btn');

   if (!header || !navbar || !profile) return;

   function closeAll() {
      navbar.classList.remove('active');
      profile.classList.remove('active');
      if (menuBtn) menuBtn.setAttribute('aria-expanded', 'false');
   }

   function toggleNavbar(e) {
      if (e) e.stopPropagation();
      navbar.classList.toggle('active');
      profile.classList.remove('active');
      if (menuBtn) menuBtn.setAttribute('aria-expanded', String(navbar.classList.contains('active')));
   }

   function toggleProfile(e) {
      if (e) e.stopPropagation();
      profile.classList.toggle('active');
      navbar.classList.remove('active');
      if (menuBtn) menuBtn.setAttribute('aria-expanded', 'false');
   }

   if (menuBtn) {
      menuBtn.addEventListener('click', toggleNavbar);
      menuBtn.addEventListener('keydown', (e) => {
         if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            toggleNavbar(e);
         }
      });
   }

   if (userBtn) {
      userBtn.addEventListener('click', toggleProfile);
      userBtn.addEventListener('keydown', (e) => {
         if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            toggleProfile(e);
         }
      });
   }

   // click outside closes
   document.addEventListener('click', (e) => {
      const t = e.target;
      const clickedInside =
         header.contains(t) &&
         (profile.contains(t) || navbar.contains(t) || (menuBtn && menuBtn.contains(t)) || (userBtn && userBtn.contains(t)));

      if (!clickedInside) closeAll();
   });

   // prevent inside clicks from closing
   profile.addEventListener('click', (e) => e.stopPropagation());
   navbar.addEventListener('click', (e) => e.stopPropagation());

   // close on scroll (keep your behavior)
   window.addEventListener('scroll', closeAll);

   // close on Escape
   document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') closeAll();
   });
});


/* =========================================================
   QTY CONTROLS (UNCHANGED)
========================================================= */
function getMaxFromControl(qtyControl) {
   const raw = qtyControl ? qtyControl.getAttribute('data-max') : null;
   const max = parseInt(raw, 10);
   if (!raw || isNaN(max) || max < 1) return null;
   return max;
}

function setPlusDisabled(qtyControl, shouldDisable) {
   const plusBtn = qtyControl ? qtyControl.querySelector('.qty-btn.plus') : null;
   if (!plusBtn) return;

   if (shouldDisable) {
      plusBtn.classList.add('is-disabled');
      plusBtn.setAttribute('aria-disabled', 'true');
   } else {
      plusBtn.classList.remove('is-disabled');
      plusBtn.removeAttribute('aria-disabled');
   }
}

function clampValue(val, min, max) {
   let v = parseInt(val, 10);
   if (isNaN(v)) v = min;
   if (v < min) v = min;
   if (max !== null && v > max) v = max;
   return v;
}

document.addEventListener('click', function (e) {

   // PLUS
   if (e.target.classList.contains('qty-btn') && e.target.classList.contains('plus')) {
      if (e.target.classList.contains('is-disabled')) return;

      const qtyControl = e.target.closest('.qty-control');
      const input = qtyControl.querySelector('.qty-input');
      const max = getMaxFromControl(qtyControl);

      const current = clampValue(input.value, 1, max);
      if (max !== null && current >= max) {
         input.value = max;
         setPlusDisabled(qtyControl, true);
         return;
      }

      input.value = current + 1;

      if (max !== null && parseInt(input.value, 10) >= max) {
         setPlusDisabled(qtyControl, true);
      } else {
         setPlusDisabled(qtyControl, false);
      }
   }

   // MINUS
   if (e.target.classList.contains('qty-btn') && e.target.classList.contains('minus')) {
      const qtyControl = e.target.closest('.qty-control');
      const input = qtyControl.querySelector('.qty-input');
      const max = getMaxFromControl(qtyControl);

      const current = clampValue(input.value, 1, max);
      input.value = (current <= 1) ? 1 : (current - 1);

      if (max !== null && parseInt(input.value, 10) >= max) {
         setPlusDisabled(qtyControl, true);
      } else {
         setPlusDisabled(qtyControl, false);
      }
   }
});

// Clamp while typing
document.addEventListener('input', function (e) {
   if (!e.target.classList.contains('qty-input')) return;

   const qtyControl = e.target.closest('.qty-control');
   const max = getMaxFromControl(qtyControl);
   const clamped = clampValue(e.target.value, 1, max);

   e.target.value = clamped;

   if (max !== null && clamped >= max) {
      setPlusDisabled(qtyControl, true);
   } else if (qtyControl) {
      setPlusDisabled(qtyControl, false);
   }
});

// Initialize state on load
document.addEventListener('DOMContentLoaded', function () {
   document.querySelectorAll('.qty-control').forEach((ctrl) => {
      const max = getMaxFromControl(ctrl);
      if (max === null) return;

      const input = ctrl.querySelector('.qty-input');
      if (!input) return;

      const current = clampValue(input.value, 1, max);
      input.value = current;

      if (current >= max) setPlusDisabled(ctrl, true);
   });
});
