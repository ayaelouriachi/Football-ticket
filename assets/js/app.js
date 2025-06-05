// Fonctions utilitaires globales
document.addEventListener('DOMContentLoaded', function() {
    // Initialisation des tooltips
    const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltips.forEach(tooltip => {
        new bootstrap.Tooltip(tooltip);
    });

    // Gestion du menu mobile
    const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
    const navbarMenu = document.getElementById('navbar-menu');
    
    if (mobileMenuToggle && navbarMenu) {
        mobileMenuToggle.addEventListener('click', function() {
            navbarMenu.classList.toggle('show');
            mobileMenuToggle.classList.toggle('active');
        });
    }

    // Gestion du dropdown utilisateur
    const userDropdowns = document.querySelectorAll('.user-dropdown');
    userDropdowns.forEach(dropdown => {
        const toggle = dropdown.querySelector('.user-toggle');
        const menu = dropdown.querySelector('.user-dropdown-menu');
        
        if (toggle && menu) {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                menu.classList.toggle('show');
            });

            // Fermer le dropdown quand on clique ailleurs
            document.addEventListener('click', function(e) {
                if (!dropdown.contains(e.target)) {
                    menu.classList.remove('show');
                }
            });
        }
    });

    // Gestion du loading spinner
    window.showLoading = function() {
        document.getElementById('loading-spinner').style.display = 'flex';
    };

    window.hideLoading = function() {
        document.getElementById('loading-spinner').style.display = 'none';
    };

    // Intercepter les soumissions de formulaire pour montrer le loading
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function() {
            showLoading();
        });
    });
}); 