<?php
require_once dirname(__DIR__) . '/config/init.php';
require_once dirname(__DIR__) . '/includes/auth_middleware.php';
require_once dirname(__DIR__) . '/classes/Cart.php';

// Require login for checkout
requireLogin();

// Get current user
$currentUser = getCurrentUser();

// Initialize cart
$cart = new Cart($db, $_SESSION);
$cartContents = $cart->getCartContents();

// Redirect if cart is empty
if (empty($cartContents['items'])) {
    setFlashMessage('warning', 'Votre panier est vide');
    header('Location: ' . BASE_URL . 'cart.php');
    exit;
}

$pageTitle = "Paiement";
require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <!-- Order Summary -->
        <div class="col-md-4 order-md-2 mb-4">
            <div class="card">
                <div class="card-body">
                    <h4 class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-primary">Récapitulatif</span>
                        <span class="badge bg-primary rounded-pill"><?php echo $cartContents['count']; ?></span>
                    </h4>
                    <ul class="list-group mb-3">
                        <?php foreach ($cartContents['items'] as $item): ?>
                            <li class="list-group-item d-flex justify-content-between lh-sm">
                                <div>
                                    <h6 class="my-0"><?php echo htmlspecialchars($item['match_title']); ?></h6>
                                    <small class="text-muted">
                                        <?php echo htmlspecialchars($item['category_name']); ?> x <?php echo $item['quantity']; ?>
                                    </small>
                                </div>
                                <span class="text-muted"><?php echo number_format($item['total'], 2); ?> MAD</span>
                            </li>
                        <?php endforeach; ?>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>Total</span>
                            <strong><?php echo number_format($cartContents['total'], 2); ?> MAD</strong>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Checkout Form -->
        <div class="col-md-8 order-md-1">
            <div class="card">
                <div class="card-body">
                    <h4 class="mb-3">Informations de paiement</h4>
                    <form id="payment-form" method="POST" action="<?php echo BASE_URL; ?>process-payment.php" class="needs-validation" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <!-- Personal Information -->
                        <div class="row g-3 mb-4">
                            <div class="col-12">
                                <label for="name" class="form-label">Nom complet</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?php echo htmlspecialchars($currentUser['name']); ?>" required>
                                <div class="invalid-feedback">
                                    Le nom est requis
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($currentUser['email']); ?>" required>
                                <div class="invalid-feedback">
                                    Un email valide est requis
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <label for="phone" class="form-label">Téléphone</label>
                                <input type="tel" class="form-control" id="phone" name="phone" required>
                                <div class="invalid-feedback">
                                    Le numéro de téléphone est requis
                                </div>
                            </div>
                        </div>
                        
                        <!-- Payment Method -->
                        <div class="my-4">
                            <h5 class="mb-3">Mode de paiement</h5>
                            <div class="form-check mb-2">
                                <input type="radio" class="form-check-input" id="credit-card" name="payment_method" value="credit_card" checked required>
                                <label class="form-check-label" for="credit-card">
                                    <i class="fas fa-credit-card me-2"></i>
                                    Carte bancaire
                                </label>
                            </div>
                            <div class="form-check mb-2">
                                <input type="radio" class="form-check-input" id="paypal" name="payment_method" value="paypal" required>
                                <label class="form-check-label" for="paypal">
                                    <i class="fab fa-paypal me-2"></i>
                                    PayPal
                                </label>
                            </div>
                        </div>
                        
                        <!-- Credit Card Information (shown/hidden based on payment method) -->
                        <div id="credit-card-fields">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="card-number" class="form-label">Numéro de carte</label>
                                    <input type="text" class="form-control" id="card-number" required>
                                    <div class="invalid-feedback">
                                        Le numéro de carte est requis
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="expiry" class="form-label">Date d'expiration</label>
                                    <input type="text" class="form-control" id="expiry" placeholder="MM/YY" required>
                                    <div class="invalid-feedback">
                                        La date d'expiration est requise
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="cvv" class="form-label">CVV</label>
                                    <input type="text" class="form-control" id="cvv" required>
                                    <div class="invalid-feedback">
                                        Le code CVV est requis
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Terms and Conditions -->
                        <div class="form-check my-4">
                            <input type="checkbox" class="form-check-input" id="terms" required>
                            <label class="form-check-label" for="terms">
                                J'accepte les <a href="#">conditions générales de vente</a>
                            </label>
                            <div class="invalid-feedback">
                                Vous devez accepter les conditions générales
                            </div>
                        </div>
                        
                        <!-- Submit Button -->
                        <button class="btn btn-primary btn-lg w-100" type="submit">
                            Payer <?php echo number_format($cartContents['total'], 2); ?> MAD
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Form validation
(function() {
    'use strict';
    
    const form = document.getElementById('payment-form');
    const creditCardFields = document.getElementById('credit-card-fields');
    const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
    
    // Toggle credit card fields based on payment method
    paymentMethods.forEach(method => {
        method.addEventListener('change', function() {
            if (this.value === 'credit_card') {
                creditCardFields.style.display = 'block';
                creditCardFields.querySelectorAll('input').forEach(input => input.required = true);
            } else {
                creditCardFields.style.display = 'none';
                creditCardFields.querySelectorAll('input').forEach(input => input.required = false);
            }
        });
    });
    
    // Format credit card number
    const cardNumber = document.getElementById('card-number');
    cardNumber.addEventListener('input', function(e) {
        let value = this.value.replace(/\D/g, '');
        value = value.replace(/(.{4})/g, '$1 ').trim();
        this.value = value;
    });
    
    // Format expiry date
    const expiry = document.getElementById('expiry');
    expiry.addEventListener('input', function(e) {
        let value = this.value.replace(/\D/g, '');
        if (value.length > 2) {
            value = value.substring(0, 2) + '/' + value.substring(2, 4);
        }
        this.value = value;
    });
    
    // Format CVV
    const cvv = document.getElementById('cvv');
    cvv.addEventListener('input', function(e) {
        this.value = this.value.replace(/\D/g, '').substring(0, 3);
    });
    
    // Form validation
    form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        form.classList.add('was-validated');
    }, false);
})();
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>