import './bootstrap';
// Activar tooltips
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    })

    // Sidebar toggle para móviles
    document.querySelector('.sidebar-toggler').addEventListener('click', function() {
        document.querySelector('.sidebar').classList.toggle('collapsed')
    })
})
