/**
 * Global AJAX defaults, loader, and Toastify helpers for the admin panel.
 */
$(function () {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
        },
    });

    $(document).ajaxStart(function () {
        showLoader();
    });

    $(document).ajaxStop(function () {
        hideLoader();
    });

    $(document).ajaxError(function (_event, xhr) {
        if (xhr.status === 422) {
            return;
        }
        var msg =
            xhr.responseJSON && xhr.responseJSON.message
                ? xhr.responseJSON.message
                : 'An unexpected error occurred.';
        notifyError(msg);
    });
});

/**
 * Show the full-screen loading overlay when present in the DOM.
 */
function showLoader() {
    var $loader = $('#global-loader');
    if ($loader.length) {
        $loader.removeClass('d-none').addClass('d-flex');
    }
}

/**
 * Hide the full-screen loading overlay.
 */
function hideLoader() {
    var $loader = $('#global-loader');
    if ($loader.length) {
        $loader.removeClass('d-flex').addClass('d-none');
    }
}

/**
 * Display a success toast (bottom-right).
 *
 * @param {string} message
 */
function notifySuccess(message) {
    Toastify({
        text: message,
        duration: 3500,
        gravity: 'bottom',
        position: 'right',
        backgroundColor: '#28a745',
        stopOnFocus: true,
    }).showToast();
}

/**
 * Display an error toast (bottom-right).
 *
 * @param {string} message
 */
function notifyError(message) {
    Toastify({
        text: message,
        duration: 4000,
        gravity: 'bottom',
        position: 'right',
        backgroundColor: '#dc3545',
        stopOnFocus: true,
    }).showToast();
}

/**
 * Display a warning toast (bottom-right).
 *
 * @param {string} message
 */
function notifyWarning(message) {
    Toastify({
        text: message,
        duration: 4000,
        gravity: 'bottom',
        position: 'right',
        backgroundColor: '#ffc107',
        style: { color: '#212529' },
        stopOnFocus: true,
    }).showToast();
}
