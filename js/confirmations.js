(function () {
    'use strict';

    function ask(trigger) {
        return Swal.fire({
            title: trigger.dataset.confirm || 'Are you sure?',
            text: trigger.dataset.confirmText || 'Please confirm that you want to continue.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: trigger.dataset.confirmButton || 'Continue',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
            focusCancel: true
        });
    }

    document.addEventListener('click', function (event) {
        const trigger = event.target.closest('a[data-confirm], button[data-confirm]');
        if (!trigger || typeof Swal === 'undefined') return;

        event.preventDefault();

        ask(trigger).then(function (result) {
            if (!result.isConfirmed) return;

            if (trigger.tagName === 'A') {
                window.location.href = trigger.href;
                return;
            }

            if (trigger.form) trigger.form.requestSubmit(trigger);
        });
    });

    document.addEventListener('submit', function (event) {
        const form = event.target.closest('form[data-confirm]');
        if (!form || typeof Swal === 'undefined' || form.dataset.confirmed === 'true') return;

        event.preventDefault();

        ask(form).then(function (result) {
            if (!result.isConfirmed) return;

            form.dataset.confirmed = 'true';
            form.requestSubmit(event.submitter || undefined);
        });
    });
})();
