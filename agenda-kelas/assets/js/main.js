/**
 * Agenda Kelas - Main JavaScript
 * File: assets/js/main.js
 * Fungsi global untuk seluruh halaman
 */

// ============================================
// READY STATE
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    initTooltips();
    initDropdowns();
    initSidebarToggle();
    initLoadingOnSubmit();
});

// ============================================
// TOOLTIPS (Bootstrap 5)
// ============================================
function initTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

// ============================================
// DROPDOWNS
// ============================================
function initDropdowns() {
    const dropdowns = document.querySelectorAll('.dropdown-toggle');
    dropdowns.forEach(dropdown => {
        new bootstrap.Dropdown(dropdown);
    });
}

// ============================================
// SIDEBAR TOGGLE (Mobile)
// ============================================
function initSidebarToggle() {
    const toggleBtn = document.getElementById('toggleSidebar');
    const sidebar = document.querySelector('.sidebar');
    
    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }
}

// ============================================
// AUTO HIDE ALERT
// ============================================
function autoHideAlert(selector, timeout = 3000) {
    const alerts = document.querySelectorAll(selector);
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, timeout);
    });
}

// ============================================
// FORMAT CURRENCY / NUMBER
// ============================================
function formatNumber(num) {
    return new Intl.NumberFormat('id-ID').format(num);
}

// ============================================
// TRUNCATE TEXT
// ============================================
function truncateText(text, maxLength = 50) {
    if (text.length <= maxLength) return text;
    return text.substr(0, maxLength) + '...';
}

// ============================================
// LOADING ON FORM SUBMIT
// ============================================
function initLoadingOnSubmit() {
    const forms = document.querySelectorAll('form[data-loading="true"]');
    forms.forEach(form => {
        form.addEventListener('submit', function() {
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Memproses...';
                submitBtn.disabled = true;
                
                // Optional: restore after error (will be handled by page reload on success)
                setTimeout(() => {
                    if (!form.submitted) {
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    }
                }, 10000);
            }
        });
    });
}

// ============================================
// GET URL PARAMETERS
// ============================================
function getUrlParam(param) {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get(param);
}

// ============================================
// SET ACTIVE NAVIGATION
// ============================================
function setActiveNav() {
    const currentPath = window.location.pathname;
    const links = document.querySelectorAll('.sidebar-link');
    
    links.forEach(link => {
        const href = link.getAttribute('href');
        if (href && currentPath.includes(href)) {
            link.classList.add('active');
        } else {
            link.classList.remove('active');
        }
    });
}

// ============================================
// CONFIRM DELETE (SweetAlert2)
// ============================================
function confirmDelete(url, title = 'Hapus Data?', text = 'Data yang dihapus tidak dapat dikembalikan!') {
    Swal.fire({
        title: title,
        text: text,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6c6f78',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = url;
        }
    });
}

// ============================================
// SHOW TOAST NOTIFICATION
// ============================================
function showToast(message, type = 'success') {
    const bgColor = type === 'success' ? '#10b981' : (type === 'error' ? '#dc2626' : '#3b82f6');
    
    Swal.fire({
        title: type === 'success' ? 'Berhasil!' : (type === 'error' ? 'Gagal!' : 'Info'),
        text: message,
        icon: type,
        timer: 2000,
        showConfirmButton: false,
        toast: true,
        position: 'top-end'
    });
}

// ============================================
// SHOW SUCCESS MESSAGE
// ============================================
function showSuccess(message, redirect = null) {
    Swal.fire({
        icon: 'success',
        title: 'Berhasil!',
        text: message,
        confirmButtonColor: '#FFD65A',
        confirmButtonText: 'OK'
    }).then(() => {
        if (redirect) {
            window.location.href = redirect;
        }
    });
}

// ============================================
// SHOW ERROR MESSAGE
// ============================================
function showError(message) {
    Swal.fire({
        icon: 'error',
        title: 'Oops...',
        text: message,
        confirmButtonColor: '#FFD65A',
        confirmButtonText: 'OK'
    });
}