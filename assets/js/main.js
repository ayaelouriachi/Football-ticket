// Configuration globale
const CONFIG = {
    baseUrl: 'http://localhost/football_tickets/',
    endpoints: {
        addToCart: 'ajax/add-to-cart.php',
        updateCart: 'ajax/update-cart.php',
        removeFromCart: 'ajax/remove-from-cart.php'
    }
};

// Utilitaires
const Utils = {
    // Afficher les notifications
    showNotification: function(message, type = 'success') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.classList.add('show');
        }, 100);
        
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        }, 3000);
    },
    
    // Requête AJAX simple
    ajax: function(url, options = {}) {
        const defaults = {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };
        
        const config = Object.assign(defaults, options);
        
        return fetch(url, config)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erreur réseau');
                }
                return response.json();
            });
    },
    
    // Formatage des prix
    formatPrice: function(price) {
        return new Intl.NumberFormat('fr-FR', {
            style: 'currency',
            currency: 'MAD',
            minimumFractionDigits: 2
        }).format(price);
    }
};

// Gestion du panier
const Cart = {
    init: function() {
        this.bindEvents();
        this.updateCartBadge();
    },
    
    bindEvents: function() {
        // Ajouter au panier
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('add-to-cart-btn')) {
                e.preventDefault();
                this.addToCart(e.target);
            }
        });
        
        // Modifier quantité
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('qty-plus')) {
                this.updateQuantity(e.target.dataset.itemId, 'increase');
            } else if (e.target.classList.contains('qty-minus')) {
                this.updateQuantity(e.target.dataset.itemId, 'decrease');
            }
        });
        
        // Supprimer du panier
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('btn-remove') || e.target.parentElement.classList.contains('btn-remove')) {
                const itemId = e.target.dataset.itemId || e.target.parentElement.dataset.itemId;
                this.removeFromCart(itemId);
            }
        });
    },
    
    addToCart: function(button) {
        const categoryId = button.dataset.categoryId;
        const categoryName = button.dataset.categoryName;
        const price = parseFloat(button.dataset.price);
        const quantitySelect = document.getElementById(`qty_${categoryId}`);
        const quantity = quantitySelect ? parseInt(quantitySelect.value) : 1;
        
        button.disabled = true;
        button.textContent = 'Ajout en cours...';
        
        const data = {
            category_id: categoryId,
            quantity: quantity
        };
        
        Utils.ajax(CONFIG.endpoints.addToCart, {
            method: 'POST',
            body: JSON.stringify(data)
        })
        .then(response => {
            if (response.success) {
                Utils.showNotification(`${quantity} billet(s) ${categoryName} ajouté(s) au panier`);
                this.updateCartBadge();
                this.showCartModal(categoryName, quantity, price);
            } else {
                Utils.showNotification(response.message || 'Erreur lors de l\'ajout au panier', 'error');
            }
        })
        .catch(error => {
            Utils.showNotification('Erreur de connexion', 'error');
        })
        .finally(() => {
            button.disabled = false;
            button.textContent = 'Ajouter au panier';
        });
    },
    
    updateQuantity: function(itemId, action) {
        const data = {
            item_id: itemId,
            action: action
        };
        
        Utils.ajax(CONFIG.endpoints.updateCart, {
            method: 'POST',
            body: JSON.stringify(data)
        })
        .then(response => {
            if (response.success) {
                this.updateCartDisplay(response.data);
                this.updateCartBadge();
            } else {
                Utils.showNotification(response.message || 'Erreur lors de la mise à jour', 'error');
            }
        })
        .catch(error => {
            Utils.showNotification('Erreur de connexion', 'error');
        });
    },
    
    removeFromCart: function(itemId) {
        if (!confirm('Êtes-vous sûr de vouloir supprimer cet article ?')) {
            return;
        }
        
        const data = {
            item_id: itemId
        };
        
        Utils.ajax(CONFIG.endpoints.removeFromCart, {
            method: 'POST',
            body: JSON.stringify(data)
        })
        .then(response => {
            if (response.success) {
                const itemElement = document.querySelector(`[data-item-id="${itemId}"]`);
                if (itemElement) {
                    itemElement.remove();
                }
                this.updateCartTotals(response.data);
                this.updateCartBadge();
                Utils.showNotification('Article supprimé du panier');
                
                // Rediriger si le panier est vide
                if (response.data.total_items === 0) {
                    window.location.reload();
                }
            } else {
                Utils.showNotification(response.message || 'Erreur lors de la suppression', 'error');
            }
        })
        .catch(error => {
            Utils.showNotification('Erreur de connexion', 'error');
        });
    },
    
    updateCartBadge: function() {
        const badge = document.querySelector('.cart-badge');
        if (badge) {
            // Récupérer le nombre d'articles via AJAX
            Utils.ajax('ajax/get-cart-count.php', { method: 'GET' })
                .then(response => {
                    if (response.success) {
                        badge.textContent = response.count;
                        badge.style.display = response.count > 0 ? 'block' : 'none';
                    }
                })
                .catch(error => {
                    console.error('Erreur lors de la mise à jour du badge panier');
                });
        }
    },
    
    updateCartDisplay: function(data) {
        // Mettre à jour l'affichage de la quantité et du prix
        const itemElement = document.querySelector(`[data-item-id="${data.item_id}"]`);
        if (itemElement) {
            const quantityElement = itemElement.querySelector('.cart-item-quantity');
            const priceElement = itemElement.querySelector('.cart-item-price');
            quantityElement.textContent = data.quantity;
            priceElement.textContent = data.price;
        }
        
        this.updateCartTotals(data);
    }
};

// Global error handler
window.onerror = function(msg, url, lineNo, columnNo, error) {
    console.error('Error: ' + msg + '\nURL: ' + url + '\nLine: ' + lineNo + '\nColumn: ' + columnNo);
    return false;
};

// Handle image loading errors
function handleImageError(img) {
    const type = img.dataset.type || 'default';
    const placeholders = {
        team: '/assets/images/default-team.png',
        stadium: '/assets/images/default-stadium.png',
        match: '/assets/images/default-match.png',
        default: '/assets/images/default-placeholder.png'
    };
    img.src = placeholders[type] || placeholders.default;
    img.classList.add('placeholder-img');
}

// AJAX request handler with error handling
function makeAjaxRequest(url, method, data, successCallback, errorCallback) {
    fetch(url, {
        method: method,
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: method === 'POST' ? new URLSearchParams(data).toString() : null
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            successCallback(data);
        } else {
            throw new Error(data.message || 'Operation failed');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (errorCallback) {
            errorCallback(error);
        } else {
            showErrorMessage(error.message);
        }
    });
}

// Show error message to user
function showErrorMessage(message, duration = 5000) {
    const errorDiv = document.getElementById('error-message') || createErrorDiv();
    errorDiv.textContent = message;
    errorDiv.classList.add('show');
    setTimeout(() => {
        errorDiv.classList.remove('show');
    }, duration);
}

// Create error message div if it doesn't exist
function createErrorDiv() {
    const div = document.createElement('div');
    div.id = 'error-message';
    div.className = 'error-message';
    document.body.appendChild(div);
    return div;
}

// Show success message
function showSuccessMessage(message, duration = 3000) {
    const successDiv = document.getElementById('success-message') || createSuccessDiv();
    successDiv.textContent = message;
    successDiv.classList.add('show');
    setTimeout(() => {
        successDiv.classList.remove('show');
    }, duration);
}

// Create success message div
function createSuccessDiv() {
    const div = document.createElement('div');
    div.id = 'success-message';
    div.className = 'success-message';
    document.body.appendChild(div);
    return div;
}

// Update cart count in header
function updateCartCount(count) {
    const cartCount = document.querySelector('.cart-count');
    if (cartCount) {
        cartCount.textContent = count;
        cartCount.classList.add('updated');
        setTimeout(() => {
            cartCount.classList.remove('updated');
        }, 300);
    }
}

// Document ready handler
document.addEventListener('DOMContentLoaded', function() {
    // Handle all image errors
    document.querySelectorAll('img').forEach(img => {
        img.addEventListener('error', function() {
            handleImageError(this);
        });
    });
    
    // Initialize quantity selectors
    document.querySelectorAll('.quantity-selector').forEach(selector => {
        const input = selector.querySelector('.quantity-input');
        const minusBtn = selector.querySelector('.minus');
        const plusBtn = selector.querySelector('.plus');
        
        if (input && minusBtn && plusBtn) {
            minusBtn.addEventListener('click', () => {
                const currentValue = parseInt(input.value);
                if (currentValue > parseInt(input.min)) {
                    input.value = currentValue - 1;
                }
            });
            
            plusBtn.addEventListener('click', () => {
                const currentValue = parseInt(input.value);
                if (currentValue < parseInt(input.max)) {
                    input.value = currentValue + 1;
                }
            });
        }
    });
    
    // Initialize add to cart buttons
    document.querySelectorAll('.add-to-cart-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const categoryId = this.dataset.categoryId;
            const quantityInput = document.querySelector(`#qty_${categoryId}`);
            const quantity = quantityInput ? parseInt(quantityInput.value) : 1;
            
            Cart.addToCart(this);
        });
    });
});