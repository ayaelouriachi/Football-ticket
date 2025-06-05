document.addEventListener('DOMContentLoaded', function() {
    console.log('Script chargé');
    
    // Sélectionner les éléments
    const userDropdown = document.querySelector('.user-dropdown');
    const userToggle = document.querySelector('.user-toggle');
    const userDropdownMenu = document.querySelector('.user-dropdown-menu');
    
    // Vérifier l'état initial et les styles
    if (userDropdownMenu) {
        const computedStyle = window.getComputedStyle(userDropdownMenu);
        console.log('État initial du menu:', {
            hasShowClass: userDropdownMenu.classList.contains('show'),
            display: computedStyle.display,
            visibility: computedStyle.visibility,
            position: computedStyle.position,
            zIndex: computedStyle.zIndex
        });
    }
    
    if (userToggle && userDropdownMenu) {
        // S'assurer que les attributs ARIA sont initialisés correctement
        userToggle.setAttribute('aria-expanded', 'false');
        userToggle.setAttribute('aria-haspopup', 'true');
        userDropdownMenu.setAttribute('role', 'menu');
        
        // Réinitialiser l'état du menu
        userDropdownMenu.classList.remove('show');
        userDropdownMenu.style.display = 'none';
        
        // Gérer le clic sur le bouton toggle
        userToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const isExpanded = userToggle.getAttribute('aria-expanded') === 'true';
            console.log('Clic sur toggle, état actuel:', isExpanded);
            
            // Toggle l'état
            if (!isExpanded) {
                userDropdownMenu.classList.add('show');
                userDropdownMenu.style.display = 'block';
                userToggle.setAttribute('aria-expanded', 'true');
            } else {
                userDropdownMenu.classList.remove('show');
                userDropdownMenu.style.display = 'none';
                userToggle.setAttribute('aria-expanded', 'false');
            }
            
            // Log l'état après le changement
            const computedStyle = window.getComputedStyle(userDropdownMenu);
            console.log('État après toggle:', {
                isExpanded: !isExpanded,
                hasShowClass: userDropdownMenu.classList.contains('show'),
                display: computedStyle.display,
                visibility: computedStyle.visibility,
                position: computedStyle.position,
                zIndex: computedStyle.zIndex
            });
        });
        
        // Fermer le menu lors d'un clic à l'extérieur
        document.addEventListener('click', function(e) {
            if (userToggle.getAttribute('aria-expanded') === 'true' && !userDropdown.contains(e.target)) {
                userDropdownMenu.classList.remove('show');
                userDropdownMenu.style.display = 'none';
                userToggle.setAttribute('aria-expanded', 'false');
                console.log('Menu fermé (clic extérieur)');
            }
        });
        
        // Fermer le menu avec la touche Escape
        document.addEventListener('keydown', function(e) {
            if (userToggle.getAttribute('aria-expanded') === 'true' && e.key === 'Escape') {
                userDropdownMenu.classList.remove('show');
                userDropdownMenu.style.display = 'none';
                userToggle.setAttribute('aria-expanded', 'false');
                console.log('Menu fermé (Escape)');
            }
        });
        
        // Gérer la navigation au clavier dans le menu
        userDropdownMenu.addEventListener('keydown', function(e) {
            if (!['ArrowUp', 'ArrowDown', 'Enter', 'Space', 'Escape'].includes(e.key)) return;
            
            const items = Array.from(userDropdownMenu.querySelectorAll('.dropdown-item:not(.dropdown-divider)'));
            const currentIndex = items.indexOf(document.activeElement);
            
            switch(e.key) {
                case 'ArrowUp':
                    e.preventDefault();
                    const prevIndex = currentIndex > 0 ? currentIndex - 1 : items.length - 1;
                    items[prevIndex].focus();
                    break;
                    
                case 'ArrowDown':
                    e.preventDefault();
                    const nextIndex = currentIndex < items.length - 1 ? currentIndex + 1 : 0;
                    items[nextIndex].focus();
                    break;
                    
                case 'Enter':
                case ' ':
                    e.preventDefault();
                    if (document.activeElement.classList.contains('dropdown-item')) {
                        document.activeElement.click();
                    }
                    break;
            }
        });
    }
    
    // Gérer le formulaire de déconnexion
    const logoutForm = document.getElementById('logout-form');
    if (logoutForm) {
        logoutForm.addEventListener('submit', function(e) {
            const submitButton = this.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Déconnexion...';
            }
        });
    }
}); 