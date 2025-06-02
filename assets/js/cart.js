class CartManager {
    constructor() {
        this.init();
    }
    
    init() {
        // Boutons d'ajout au panier
        document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
            btn.addEventListener('click', (e) => this.addToCart(e));
        });
        
        // Mise à jour des quantités
        document.querySelectorAll('.quantity-input').forEach(input => {
            input.addEventListener('change', (e) => this.updateQuantity(e));
        });
        
        // Suppression d'articles
        document.querySelectorAll('.remove-item-btn').forEach(btn => {
            btn.addEventListener('click', (e) => this.removeItem(e));
        });
    }
    
    async addToCart(event) {
        event.preventDefault();
        const form = event.target.closest('form');
        const formData = new FormData(form);
        
        try {
            showLoading('Ajout au panier...');
            
            const response = await fetch('ajax/add-to-cart.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.updateCartCounter(result.cartCount);
                this.showNotification('Article ajouté au panier', 'success');
                
                // Animation du bouton
                event.target.classList.add('added');
                setTimeout(() => event.target.classList.remove('added'), 2000);
            } else {
                this.showNotification(result.message, 'error');
            }
            
        } catch (error) {
            this.showNotification('Erreur lors de l\'ajout', 'error');
        } finally {
            hideLoading();
        }
    }
    
    updateCartCounter(count) {
        const counter = document.querySelector('.cart-counter');
        if (counter) {
            counter.textContent = count;
            counter.classList.add('updated');
            setTimeout(() => counter.classList.remove('updated'), 1000);
        }
    }
    
    showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => notification.classList.add('show'), 100);
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
}

// Initialiser quand le DOM est prêt
document.addEventListener('DOMContentLoaded', () => {
    new CartManager();
});