/**
 * assets/js/script.js
 * Sidebar toggle, delete confirm, flash auto-hide
 */
document.addEventListener('DOMContentLoaded', function () {

    /* ---- Sidebar Mobile Toggle ---- */
    const toggleBtn = document.getElementById('sidebarToggle');
    const sidebar   = document.getElementById('sidebar');
    const overlay   = document.getElementById('sidebarOverlay');

    function openSidebar() {
        sidebar  && sidebar.classList.add('show');
        overlay  && overlay.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
    function closeSidebar() {
        sidebar  && sidebar.classList.remove('show');
        overlay  && overlay.classList.remove('show');
        document.body.style.overflow = '';
    }

    toggleBtn && toggleBtn.addEventListener('click', function () {
        sidebar && sidebar.classList.contains('show') ? closeSidebar() : openSidebar();
    });
    overlay && overlay.addEventListener('click', closeSidebar);

    /* ---- Confirm Delete ---- */
    document.querySelectorAll('.btn-delete-confirm').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            if (!confirm('Yakin ingin menghapus data ini? Tindakan tidak dapat dibatalkan.')) {
                e.preventDefault();
            }
        });
    });

    /* ---- Auto-hide Alert ---- */
    document.querySelectorAll('.alert-auto-hide').forEach(function (el) {
        setTimeout(function () {
            el.style.transition = 'opacity .4s ease';
            el.style.opacity = '0';
            setTimeout(function () { el.remove(); }, 420);
        }, 4500);
    });

    /* ---- Active sidebar link highlight from URL ---- */
    const links = document.querySelectorAll('.sidebar-menu .nav-link');
    links.forEach(function (link) {
        if (link.href === window.location.href) {
            link.classList.add('active');
        }
    });

});
