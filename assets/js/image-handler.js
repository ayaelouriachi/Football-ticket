// Gestion des erreurs de chargement d'images
document.addEventListener('DOMContentLoaded', function() {
    // Récupérer BASE_URL depuis la meta tag
    const baseUrlMeta = document.querySelector('meta[name="base-url"]');
    const BASE_URL = baseUrlMeta ? baseUrlMeta.getAttribute('content') : '/football_tickets/';

    // Fonction pour gérer les erreurs de chargement d'images
    function handleImageError(img, defaultImage) {
        console.log('Image error:', img.src);
        img.src = defaultImage;
        img.onerror = null; // Éviter les boucles infinies
    }

    // Ajouter des gestionnaires d'erreur à toutes les images
    document.querySelectorAll('img').forEach(img => {
        const defaultSrc = img.getAttribute('data-default-src') || 
                          img.getAttribute('onerror') || 
                          BASE_URL + 'assets/images/default-placeholder.svg';
        
        img.addEventListener('error', function() {
            handleImageError(this, defaultSrc);
        });
    });

    // Fonction pour précharger les images
    function preloadImage(src) {
        return new Promise((resolve, reject) => {
            const img = new Image();
            img.onload = () => resolve(img);
            img.onerror = reject;
            img.src = src;
        });
    }

    // Précharger les images par défaut
    const defaultImages = [
        BASE_URL + 'assets/images/default-team.svg',
        BASE_URL + 'assets/images/default-placeholder.svg',
        BASE_URL + 'assets/images/default-stadium.svg'
    ];

    Promise.all(defaultImages.map(preloadImage))
        .then(() => console.log('Default images preloaded'))
        .catch(error => console.error('Error preloading images:', error));
}); 