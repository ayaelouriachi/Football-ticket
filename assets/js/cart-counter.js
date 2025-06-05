document.addEventListener('DOMContentLoaded', function() {
    // Mettre à jour le compteur du panier au chargement de la page
    updateCartCount();

    // Fonction pour mettre à jour le compteur du panier
    function updateCartCount() {
        fetch(BASE_URL + 'ajax/get-cart-count.php', {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            const cartCount = document.getElementById('cart-count');
            if (cartCount) {
                cartCount.textContent = data.count;
                cartCount.style.display = data.count > 0 ? 'block' : 'none';
            }
        })
        .catch(error => {
            console.error('Error fetching cart count:', error);
        });
    }

    // Écouter les événements personnalisés pour mettre à jour le compteur
    document.addEventListener('cartUpdated', function() {
        updateCartCount();
    });

    // Mettre à jour le compteur toutes les 30 secondes
    setInterval(updateCartCount, 30000);
}); 