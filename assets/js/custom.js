/* ============================================================
   SisisFour - Custom JavaScript
   Inisialisasi library & helper functions
   ============================================================ */

$(document).ready(function () {

    // ============================================================
    // 1. DATATABLES (Mendukung .table-datatable dan .datatable)
    // ============================================================
    $('.table-datatable, .datatable').DataTable({
        responsive: true,
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/id.json'
        },
        pageLength: 25,
        lengthMenu: [10, 25, 50, 100]
    });

    // ============================================================
    // 2. SELECT2
    // ============================================================
    $('.select2').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Pilih...',
        allowClear: true
    });

    // ============================================================
    // 3. SWEETALERT2 — GLOBAL HELPERS
    // ============================================================
    window.Swal = Swal;

    // Toast notification
    window.showToast = function (icon, title, timer = 3000) {
        Swal.fire({
            icon: icon,
            title: title,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: timer,
            timerProgressBar: true
        });
    };

    // Success shortcut
    window.showSuccess = function (message) {
        showToast('success', message);
    };

    // Error shortcut
    window.showError = function (message) {
        showToast('error', message);
    };

    // Info shortcut
    window.showInfo = function (message) {
        showToast('info', message);
    };

    // Warning shortcut
    window.showWarning = function (message) {
        showToast('warning', message);
    };

    // Confirm delete dialog
    window.confirmDelete = function (title, text, callback) {
        Swal.fire({
            title: title || 'Apakah Anda yakin?',
            text: text || 'Data yang dihapus tidak dapat dikembalikan!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed && callback) {
                callback();
            }
        });
    };

    // ============================================================
    // 4. AUTO-CLOSE ALERT (Flash message)
    // ============================================================
    $('.alert-auto-close').each(function () {
        var $alert = $(this);
        setTimeout(function () {
            $alert.fadeOut(500, function () {
                $alert.remove();
            });
        }, 4000);
    });

    // ============================================================
    // 5. TOGGLE PASSWORD VISIBILITY
    // ============================================================
    $('.toggle-password').on('click', function () {
        var $input = $($(this).data('target'));
        var type = $input.attr('type') === 'password' ? 'text' : 'password';
        $input.attr('type', type);
        $(this).find('i').toggleClass('bx-hide bx-show');
    });

    // ============================================================
    // 6. CONFIRM BEFORE SUBMIT (untuk form hapus)
    // ============================================================
    $('form[data-confirm]').on('submit', function (e) {
        var message = $(this).data('confirm') || 'Apakah Anda yakin?';
        e.preventDefault();
        var $form = $(this);
        confirmDelete('Konfirmasi', message, function () {
            $form.off('submit').submit();
        });
    });

    // ============================================================
    // 7. DATE PICKER FALLBACK (jika input type date tidak support)
    // ============================================================
    if ($('input[type="date"]').length) {
        var isDateInputSupported = function () {
            var input = document.createElement('input');
            input.setAttribute('type', 'date');
            var notSupported = 'not-supported';
            input.setAttribute('value', notSupported);
            return (input.value !== notSupported);
        };
        if (!isDateInputSupported()) {
            $('input[type="date"]').attr('type', 'text').attr('placeholder', 'YYYY-MM-DD');
        }
    }

});
