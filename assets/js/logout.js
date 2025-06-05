document.addEventListener('DOMContentLoaded', function() {
    const logoutForm = document.getElementById('logoutForm');
    if (logoutForm) {
        logoutForm.addEventListener('submit', function(e) {
            // Empêcher la soumission multiple
            const submitButton = this.querySelector('button[type="submit"]');
            if (submitButton.disabled) {
                e.preventDefault();
                return;
            }
            
            // Ajouter le spinner et désactiver le bouton
            submitButton.disabled = true;
            const originalContent = submitButton.innerHTML;
            submitButton.innerHTML = `
                <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                Déconnexion...
            `;
            
            // Laisser le formulaire se soumettre normalement
        });
    }
}); 