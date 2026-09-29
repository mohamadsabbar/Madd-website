(function () {
    'use strict';

    function findModalForForm(form) {
        var modalId = form.getAttribute('data-modal-id');
        if (modalId) {
            return document.getElementById(modalId);
        }
        return form.closest('.agent-renew-modal');
    }

    function setRenewLoading(form, loading) {
        var modal = findModalForForm(form);
        var confirmBtn = form.id
            ? document.querySelector('button[form="' + form.id + '"].js-agent-renew-submit')
            : null;
        if (!confirmBtn && modal) {
            confirmBtn = modal.querySelector('.js-agent-renew-submit');
        }

        var overlay = modal ? modal.querySelector('.agent-renew-modal__loading') : null;
        var cancelBtn = modal ? modal.querySelector('.btn-agent-cancel') : null;
        var closeBtn = modal ? modal.querySelector('.btn-close') : null;

        if (loading) {
            form.dataset.submitting = '1';
            if (overlay) {
                overlay.classList.remove('d-none');
                overlay.setAttribute('aria-hidden', 'false');
            }
            if (confirmBtn) {
                confirmBtn.disabled = true;
                confirmBtn.classList.add('is-loading');
                confirmBtn.setAttribute('aria-busy', 'true');
            }
            if (cancelBtn) {
                cancelBtn.disabled = true;
            }
            if (closeBtn) {
                closeBtn.disabled = true;
                closeBtn.style.pointerEvents = 'none';
                closeBtn.style.opacity = '0.35';
            }
            if (modal && typeof bootstrap !== 'undefined') {
                modal.setAttribute('data-bs-backdrop', 'static');
                modal.setAttribute('data-bs-keyboard', 'false');
            }
            return;
        }

        delete form.dataset.submitting;
        if (overlay) {
            overlay.classList.add('d-none');
            overlay.setAttribute('aria-hidden', 'true');
        }
        if (confirmBtn) {
            confirmBtn.disabled = false;
            confirmBtn.classList.remove('is-loading');
            confirmBtn.removeAttribute('aria-busy');
        }
        if (cancelBtn) {
            cancelBtn.disabled = false;
        }
        if (closeBtn) {
            closeBtn.disabled = false;
            closeBtn.style.pointerEvents = '';
            closeBtn.style.opacity = '';
        }
    }

    document.querySelectorAll('.js-agent-renew-form').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (form.dataset.submitting === '1') {
                event.preventDefault();
                return;
            }
            setRenewLoading(form, true);
        });
    });
})();
