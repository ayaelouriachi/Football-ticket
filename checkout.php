<?php
require_once __DIR__ . '/config/init.php';

// Initialize cart
$cart = new Cart($db, $_SESSION);

// Get cart contents
$cart_contents = $cart->getCartContents();

// Redirect if cart is empty
if (empty($cart_contents['items'])) {
    header('Location: cart.php');
    exit;
}

$pageTitle = "Paiement";
include 'includes/header.php';
?>

<div class="container checkout-container py-5">
    <div class="row">
        <div class="col-lg-8">
            <div class="checkout-section">
                <h2 class="section-title">Informations de paiement</h2>
                <div class="payment-methods">
                    <div class="payment-method">
                        <input type="radio" class="form-check-input" id="card" name="payment_method" value="card" checked required>
                        <label class="form-check-label" for="card">
                            <i class="bi bi-credit-card me-2"></i>
                            Carte bancaire
                        </label>
                    </div>
                </div>
                
                <form id="payment-form" class="mt-4">
                    <div class="mb-3">
                        <label for="card-number" class="form-label">Numéro de carte</label>
                        <input type="text" class="form-control" id="card-number" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="expiry" class="form-label">Date d'expiration</label>
                            <input type="text" class="form-control" id="expiry" placeholder="MM/AA" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="cvv" class="form-label">CVV</label>
                            <input type="text" class="form-control" id="cvv" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="card-name" class="form-label">Nom sur la carte</label>
                        <input type="text" class="form-control" id="card-name" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-lg w-100">
                        <i class="bi bi-lock me-2"></i>
                        Payer <?php echo number_format($cart_contents['total'], 2); ?> MAD
                    </button>
                </form>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="order-summary">
                <h3 class="summary-title">Résumé de la commande</h3>
                
                <?php foreach ($cart_contents['items'] as $item): ?>
                    <div class="summary-item">
                        <div class="item-info">
                            <span class="item-quantity"><?php echo $item['quantity']; ?>x</span>
                            <span class="item-name"><?php echo htmlspecialchars($item['match_title']); ?></span>
                        </div>
                        <span class="item-price"><?php echo number_format($item['subtotal'], 2); ?> MAD</span>
                    </div>
                <?php endforeach; ?>
                
                <div class="summary-subtotal">
                    <span>Sous-total</span>
                    <span><?php echo number_format($cart_contents['total'], 2); ?> MAD</span>
                </div>
                
                <div class="summary-fees">
                    <span>Frais de service</span>
                    <span>0.00 MAD</span>
                </div>
                
                <div class="summary-total">
                    <span>Total</span>
                    <span><?php echo number_format($cart_contents['total'], 2); ?> MAD</span>
                </div>
            </div>
            
            <div class="secure-checkout mt-4">
                <div class="secure-item">
                    <i class="bi bi-shield-check"></i>
                    <span>Paiement 100% sécurisé</span>
                </div>
                <div class="secure-item">
                    <i class="bi bi-lock"></i>
                    <span>Données cryptées SSL</span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.checkout-container {
    background: var(--bs-light);
    min-height: calc(100vh - 200px);
}

.checkout-section {
    background: white;
    padding: 2rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.section-title {
    margin-bottom: 1.5rem;
    font-size: 1.5rem;
}

.payment-methods {
    margin-bottom: 2rem;
}

.payment-method {
    padding: 1rem;
    border: 1px solid var(--bs-gray-300);
    border-radius: 4px;
    margin-bottom: 1rem;
    cursor: pointer;
}

.payment-method:hover {
    background: var(--bs-light);
}

.payment-method input {
    margin-right: 1rem;
}

.order-summary {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.summary-title {
    margin-bottom: 1.5rem;
    font-size: 1.25rem;
}

.summary-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--bs-gray-200);
}

.item-info {
    display: flex;
    gap: 0.5rem;
}

.item-quantity {
    color: var(--bs-gray);
}

.summary-subtotal,
.summary-fees {
    display: flex;
    justify-content: space-between;
    margin-bottom: 1rem;
    color: var(--bs-gray);
}

.summary-total {
    display: flex;
    justify-content: space-between;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 2px solid var(--bs-gray-200);
    font-weight: bold;
    font-size: 1.1rem;
}

.secure-checkout {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.secure-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
    color: var(--bs-gray);
}

.secure-item i {
    font-size: 1.25rem;
    color: var(--bs-success);
}

@media (max-width: 768px) {
    .checkout-section,
    .order-summary {
        margin-bottom: 1.5rem;
    }
}
</style>

<script>
document.getElementById('payment-form').addEventListener('submit', function(e) {
    e.preventDefault();
    // Ici, vous pouvez ajouter votre logique de paiement
    alert('Cette fonctionnalité est en cours de développement.');
});
</script>

<?php include 'includes/footer.php'; ?> 