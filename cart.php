<?php
require_once __DIR__ . '/config/init.php';
require_once __DIR__ . '/config/paypal.php';

// Initialize cart
$cart = new Cart($db, $_SESSION);

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'update':
                    if (!isset($_POST['category_id']) || !isset($_POST['quantity'])) {
                        throw new Exception('Missing parameters for cart update');
                    }
                    $result = $cart->updateItem($_POST['category_id'], $_POST['quantity']);
                    if (!$result['success']) {
                        throw new Exception($result['message']);
                    }
                    $_SESSION['success'] = 'Panier mis à jour avec succès';
                    break;

                case 'remove':
                    if (!isset($_POST['category_id'])) {
                        throw new Exception('Missing category ID for removal');
                    }
                    $result = $cart->removeItem($_POST['category_id']);
                    if (!$result['success']) {
                        throw new Exception($result['message']);
                    }
                    $_SESSION['success'] = 'Article retiré du panier';
                    break;

                case 'clear':
                    $result = $cart->clearCart();
                    if (!$result['success']) {
                        throw new Exception($result['message']);
                    }
                    $_SESSION['success'] = 'Panier vidé avec succès';
                    break;
            }
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
    
    header('Location: ' . BASE_URL . 'cart.php');
    exit;
}

// Get cart contents
$cart_contents = $cart->getCartContents();
$total_usd = number_format($cart_contents['total'] * MAD_TO_USD_RATE, 2);

$pageTitle = "Mon Panier";
include 'includes/header.php';
?>

<div class="container cart-container py-5">
    <?php if (isset($_SESSION['error']) || isset($_SESSION['success'])): ?>
        <div class="alert <?php echo isset($_SESSION['error']) ? 'alert-danger' : 'alert-success'; ?> alert-dismissible fade show" role="alert">
            <?php 
            echo htmlspecialchars(isset($_SESSION['error']) ? $_SESSION['error'] : $_SESSION['success']);
            unset($_SESSION['error']);
            unset($_SESSION['success']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (empty($cart_contents['items'])): ?>
        <div class="empty-cart">
            <div class="empty-cart-icon">
                <i class="bi bi-cart-x"></i>
            </div>
            <h3>Votre panier est vide</h3>
            <p>Découvrez nos matchs et ajoutez des billets à votre panier.</p>
            <a href="matches.php" class="btn btn-primary">
                <i class="bi bi-ticket-perforated me-2"></i>Voir les matchs
            </a>
        </div>
    <?php else: ?>
        <div class="row">
            <div class="col-lg-8">
                <?php foreach ($cart_contents['items'] as $item): ?>
                    <div class="cart-item">
                        <div class="ticket-header">
                            <div class="match-info">
                                <div class="teams-container">
                                    <img src="<?php echo htmlspecialchars($item['team1_logo'] ?? 'assets/images/default-team.png'); ?>" 
                                         alt="<?php echo htmlspecialchars($item['team1_name']); ?>"
                                         class="team-logo"
                                         onerror="this.src='assets/images/default-team.png'">
                                    <span class="vs-badge">VS</span>
                                    <img src="<?php echo htmlspecialchars($item['team2_logo'] ?? 'assets/images/default-team.png'); ?>" 
                                         alt="<?php echo htmlspecialchars($item['team2_name']); ?>"
                                         class="team-logo"
                                         onerror="this.src='assets/images/default-team.png'">
                                </div>
                                <div class="match-details">
                                    <h3 class="match-title"><?php echo htmlspecialchars($item['match_title']); ?></h3>
                                    <div class="match-meta">
                                        <div class="meta-item">
                                            <i class="bi bi-ticket-perforated"></i>
                                            <span><?php echo htmlspecialchars($item['category_name']); ?></span>
                                        </div>
                                        <div class="meta-item">
                                            <i class="bi bi-calendar-event"></i>
                                            <span><?php echo date('d/m/Y H:i', strtotime($item['match_date'])); ?></span>
                                        </div>
                                        <div class="meta-item">
                                            <i class="bi bi-geo-alt"></i>
                                            <span><?php echo htmlspecialchars($item['stadium_name']); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="ticket-body">
                            <div class="quantity-section">
                                <label class="quantity-label">Quantité</label>
                                <form method="post" id="quantity-form-<?php echo $item['ticket_category_id']; ?>" class="quantity-form">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="category_id" value="<?php echo $item['ticket_category_id']; ?>">
                                    <div class="quantity-wrapper">
                                        <button type="button" class="btn-quantity minus" onclick="updateQuantity(<?php echo $item['ticket_category_id']; ?>, -1)">
                                            <i class="bi bi-dash-lg"></i>
                                        </button>
                                        <input type="text" 
                                               id="quantity-<?php echo $item['ticket_category_id']; ?>"
                                               name="quantity" 
                                               value="<?php echo $item['quantity']; ?>" 
                                               class="quantity-input"
                                               readonly>
                                        <button type="button" class="btn-quantity plus" onclick="updateQuantity(<?php echo $item['ticket_category_id']; ?>, 1)">
                                            <i class="bi bi-plus-lg"></i>
                                        </button>
                                    </div>
                                </form>
                            </div>
                            <div class="price-section">
                                <div class="price-tag">
                                    <div class="price-label">Prix total</div>
                                    <div class="price-value"><?php echo number_format($item['subtotal'], 2); ?> MAD</div>
                                </div>
                                <div class="price-tag">
                                    <div class="price-label">Prix total (USD)</div>
                                    <div class="price-value"><?php echo number_format($item['subtotal'] * MAD_TO_USD_RATE, 2); ?> USD</div>
                                </div>
                                <form method="post" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce billet ?');">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="category_id" value="<?php echo $item['ticket_category_id']; ?>">
                                    <button type="submit" class="btn-delete">
                                        <i class="bi bi-trash"></i>
                                        <span>Supprimer</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="col-lg-4">
                <div class="cart-summary">
                    <h3 class="summary-title">Résumé de la commande</h3>
                    <div class="summary-item">
                        <span>Sous-total</span>
                        <span><?php echo number_format($cart_contents['total'], 2); ?> MAD</span>
                    </div>
                    <div class="summary-item">
                        <span>Frais de service</span>
                        <span>0.00 MAD</span>
                    </div>
                    <div class="summary-total">
                        <span>Total</span>
                        <span>
                            <?php echo number_format($cart_contents['total'], 2); ?> MAD
                            <br>
                            <small class="text-muted">
                                (<?php echo $total_usd; ?> USD)
                            </small>
                        </span>
                    </div>
                    
                    <?php if ($cart_contents['total'] > 0): ?>
                        <div id="loading-spinner" class="spinner-border text-primary" style="display: none;" role="status">
                            <span class="visually-hidden">Chargement...</span>
                        </div>
                        <div id="paypal-button-container" class="mt-4"></div>
                        <div id="payment-message" class="alert mt-3" style="display: none;"></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php if (!empty($cart_contents['items']) && $cart_contents['total'] > 0): ?>
    <!-- PayPal Script -->
    <script src="<?php echo PAYPAL_SDK_URL; ?>?client-id=<?php echo PAYPAL_CLIENT_ID; ?>&currency=<?php echo PAYPAL_CURRENCY; ?>&debug=true"></script>
    
    <script>
    // Debug function
    function debugLog(title, data) {
        console.log('=== ' + title + ' ===');
        console.log(data);
        
        // Also display in UI
        const message = document.getElementById('payment-message');
        message.style.display = 'block';
        message.className = 'alert alert-info mt-3';
        message.innerHTML += '<br>' + title + ': ' + JSON.stringify(data);
    }

    paypal.Buttons({
        createOrder: function(data, actions) {
            debugLog('Starting PayPal Payment', { 
                clientId: '<?php echo PAYPAL_CLIENT_ID; ?>',
                currency: '<?php echo PAYPAL_CURRENCY; ?>',
                madAmount: <?php echo $cart_contents['total']; ?>,
                usdAmount: <?php echo $cart_contents['total'] * MAD_TO_USD_RATE; ?>
            });
            
            document.getElementById('loading-spinner').style.display = 'flex';
            
            return fetch('scripts/process_paypal.php', {
                method: 'post',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'action=create_order'
            })
            .then(function(response) {
                debugLog('Server Response', {
                    status: response.status,
                    statusText: response.statusText
                });
                
                return response.text().then(function(text) {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        debugLog('Response Parse Error', { text: text, error: e.message });
                        throw new Error('Invalid server response');
                    }
                });
            })
            .then(function(data) {
                if (!data.success) {
                    throw new Error(data.message || 'Une erreur est survenue');
                }
                debugLog('Order Created', data);
                document.getElementById('loading-spinner').style.display = 'none';
                return data.orderID;
            })
            .catch(function(error) {
                document.getElementById('loading-spinner').style.display = 'none';
                document.getElementById('payment-message').className = 'alert alert-danger mt-3';
                document.getElementById('payment-message').style.display = 'block';
                document.getElementById('payment-message').textContent = 'Erreur: ' + error.message;
                console.error('PayPal create order error:', error);
                throw error;
            });
        },
        
        onApprove: function(data, actions) {
            debugLog('Payment Approved', data);
            document.getElementById('loading-spinner').style.display = 'flex';
            
            return fetch('scripts/process_paypal.php', {
                method: 'post',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'action=capture_order&orderID=' + data.orderID
            })
            .then(function(response) {
                debugLog('Capture Response', {
                    status: response.status,
                    statusText: response.statusText
                });
                
                return response.text().then(function(text) {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        debugLog('Response Parse Error', { text: text, error: e.message });
                        throw new Error('Invalid server response');
                    }
                });
            })
            .then(function(data) {
                if (!data.success) {
                    throw new Error(data.message || 'Une erreur est survenue lors de la capture du paiement');
                }
                debugLog('Payment Captured', data);
                window.location.href = 'payment_success.php';
            })
            .catch(function(error) {
                document.getElementById('loading-spinner').style.display = 'none';
                document.getElementById('payment-message').className = 'alert alert-danger mt-3';
                document.getElementById('payment-message').style.display = 'block';
                document.getElementById('payment-message').textContent = 'Erreur de capture: ' + error.message;
                console.error('PayPal capture error:', error);
                throw error;
            });
        },
        
        onError: function(err) {
            document.getElementById('loading-spinner').style.display = 'none';
            document.getElementById('payment-message').className = 'alert alert-danger mt-3';
            document.getElementById('payment-message').style.display = 'block';
            document.getElementById('payment-message').textContent = 'Erreur PayPal: ' + (err.message || 'Une erreur est survenue lors du paiement. Veuillez réessayer.');
            console.error('PayPal error:', err);
            debugLog('PayPal Error', err);
        },

        onCancel: function(data) {
            document.getElementById('loading-spinner').style.display = 'none';
            document.getElementById('payment-message').className = 'alert alert-warning mt-3';
            document.getElementById('payment-message').style.display = 'block';
            document.getElementById('payment-message').textContent = 'Paiement annulé par l\'utilisateur';
            debugLog('Payment Cancelled', data);
        }
    }).render('#paypal-button-container');
    </script>
<?php endif; ?>

<style>
.cart-container {
    background: var(--bs-light);
    min-height: calc(100vh - 200px);
}

.cart-item {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 1.5rem;
    overflow: hidden;
}

.ticket-header {
    background: var(--bs-light);
    padding: 1.5rem;
    border-bottom: 1px solid rgba(0,0,0,0.1);
}

.match-info {
    display: flex;
    align-items: center;
    gap: 2rem;
}

.teams-container {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.team-logo {
    width: 60px;
    height: 60px;
    object-fit: contain;
}

.vs-badge {
    font-weight: bold;
    color: var(--bs-gray);
}

.match-details {
    flex: 1;
}

.match-title {
    margin: 0 0 0.5rem;
    font-size: 1.25rem;
}

.match-meta {
    display: flex;
    gap: 1.5rem;
    color: var(--bs-gray);
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.ticket-body {
    padding: 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.quantity-section {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.quantity-wrapper {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-quantity {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    background: var(--bs-light);
    border-radius: 4px;
    cursor: pointer;
}

.quantity-input {
    width: 50px;
    text-align: center;
    border: 1px solid var(--bs-gray-300);
    border-radius: 4px;
    padding: 0.25rem;
}

.price-section {
    display: flex;
    align-items: center;
    gap: 2rem;
}

.price-tag {
    text-align: right;
}

.price-value {
    font-size: 1.25rem;
    font-weight: bold;
}

.btn-delete {
    color: var(--bs-danger);
    background: none;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.cart-summary {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    position: sticky;
    top: 2rem;
}

.summary-title {
    margin-bottom: 1.5rem;
    font-size: 1.25rem;
}

.summary-item {
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
    border-top: 1px solid var(--bs-gray-200);
    font-weight: bold;
}

.empty-cart {
    text-align: center;
    padding: 3rem 1rem;
}

.empty-cart-icon {
    font-size: 4rem;
    color: var(--bs-gray);
    margin-bottom: 1rem;
}

@media (max-width: 768px) {
    .match-info {
        flex-direction: column;
        text-align: center;
    }
    
    .match-meta {
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .ticket-body {
        flex-direction: column;
        gap: 1.5rem;
        text-align: center;
    }
    
    .price-section {
        flex-direction: column;
        gap: 1rem;
    }
    
    .price-tag {
        text-align: center;
    }
}
</style>

<script>
function updateQuantity(itemId, delta) {
    const form = document.getElementById('quantity-form-' + itemId);
    const input = document.getElementById('quantity-' + itemId);
    const currentValue = parseInt(input.value) || 1;
    const newValue = currentValue + delta;
    
    if (newValue >= 1 && newValue <= 10) {
        input.value = newValue;
        form.submit();
    }
}
</script>

<?php include 'includes/footer.php'; ?>
</body>
</html>