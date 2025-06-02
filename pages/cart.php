<?php
session_start();
require_once 'config/database.php';
require_once 'classes/Cart.php';
require_once 'classes/Match.php';

$cart = new Cart();
$match = new FootballMatch();

// Gestion des actions AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'update_quantity':
            $item_id = intval($_POST['item_id']);
            $quantity = intval($_POST['quantity']);
            $result = $cart->updateQuantity($item_id, $quantity);
            echo json_encode($result);
            exit();
            
        case 'remove_item':
            $item_id = intval($_POST['item_id']);
            $result = $cart->removeItem($item_id);
            echo json_encode($result);
            exit();
            
        case 'clear_cart':
            $result = $cart->clearCart();
            echo json_encode($result);
            exit();
    }
}

// Récupérer les articles du panier
$cartItems = $cart->getItems();
$cartTotal = $cart->getTotal();
$cartCount = $cart->getItemCount();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Panier - Football Tickets Maroc</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/cart.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main class="cart-main">
        <div class="container">
            <div class="page-header">
                <h1>Mon Panier</h1>
                <div class="breadcrumb">
                    <a href="index.php">Accueil</a>
                    <span class="separator">></span>
                    <span class="current">Panier</span>
                </div>
            </div>
            
            <?php if (empty($cartItems)): ?>
                <div class="empty-cart">
                    <div class="empty-cart-icon">
                        <i class="icon-cart-empty"></i>
                    </div>
                    <h2>Votre panier est vide</h2>
                    <p>Découvrez nos matchs et réservez vos places dès maintenant !</p>
                    <a href="matches.php" class="btn btn-primary">
                        <i class="icon-ticket"></i>
                        Voir les matchs disponibles
                    </a>
                </div>
            <?php else: ?>
                <div class="cart-content">
                    <div class="cart-items-section">
                        <div class="cart-header">
                            <h2>Articles dans votre panier (<?php echo $cartCount; ?>)</h2>
                            <button class="btn btn-link btn-clear-cart" onclick="clearCart()">
                                <i class="icon-trash"></i>
                                Vider le panier
                            </button>
                        </div>
                        
                        <div class="cart-items" id="cart-items">
                            <?php foreach ($cartItems as $item): ?>
                                <div class="cart-item" data-item-id="<?php echo $item['id']; ?>">
                                    <div class="item-match-info">
                                        <div class="match-date">
                                            <span class="day"><?php echo date('d', strtotime($item['match_date'])); ?></span>
                                            <span class="month"><?php echo date('M', strtotime($item['match_date'])); ?></span>
                                        </div>
                                        <div class="match-details">
                                            <h3 class="match-title">
                                                <?php echo htmlspecialchars($item['home_team'] . ' vs ' . $item['away_team']); ?>
                                            </h3>
                                            <p class="match-venue">
                                                <i class="icon-location"></i>
                                                <?php echo htmlspecialchars($item['stadium_name']); ?>
                                            </p>
                                            <p class="match-datetime">
                                                <i class="icon-clock"></i>
                                                <?php echo date('d/m/Y à H:i', strtotime($item['match_date'])); ?>
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <div class="item-ticket-info">
                                        <h4 class="ticket-category">
                                            <?php echo htmlspecialchars($item['category_name']); ?>
                                        </h4>
                                        <p class="ticket-description">
                                            <?php echo htmlspecialchars($item['category_description'] ?? ''); ?>
                                        </p>
                                        <div class="ticket-zone">
                                            <span class="zone-badge" style="background-color: <?php echo $item['color_hex']; ?>">
                                                <?php echo htmlspecialchars($item['seating_zone'] ?? ''); ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="item-price">
                                        <span class="unit-price"><?php echo number_format($item['price'], 2); ?> MAD</span>
                                        <span class="price-per">par billet</span>
                                    </div>
                                    
                                    <div class="item-quantity">
                                        <div class="quantity-controls">
                                            <button class="quantity-btn quantity-minus" onclick="updateQuantity(<?php echo $item['id']; ?>, <?php echo $item['quantity'] - 1; ?>)">
                                                <i class="icon-minus"></i>
                                            </button>
                                            <input type="number" class="quantity-input" 
                                                   value="<?php echo $item['quantity']; ?>" 
                                                   min="1" max="<?php echo $item['available_capacity']; ?>"
                                                   onchange="updateQuantity(<?php echo $item['id']; ?>, this.value)">
                                            <button class="quantity-btn quantity-plus" onclick="updateQuantity(<?php echo $item['id']; ?>, <?php echo $item['quantity'] + 1; ?>)">
                                                <i class="icon-plus"></i>
                                            </button>
                                        </div>
                                        <span class="stock-info"><?php echo $item['available_capacity']; ?> disponibles</span>
                                    </div>
                                    
                                    <div class="item-total">
                                        <span class="total-price"><?php echo number_format($item['price'] * $item['quantity'], 2); ?> MAD</span>
                                        <button class="btn-remove" onclick="removeItem(<?php echo $item['id']; ?>)">
                                            <i class="icon-trash"></i>
                                            Supprimer
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="cart-summary-section">
                        <div class="cart-summary">
                            <h3>Résumé de la commande</h3>
                            
                            <div class="summary-details">
                                <div class="summary-line">
                                    <span>Sous-total (<?php echo $cartCount; ?> articles)</span>
                                    <span id="subtotal"><?php echo number_format($cartTotal, 2); ?> MAD</span>
                                </div>
                                
                                <div class="summary-line">
                                    <span>Frais de service</span>
                                    <span>Gratuit</span>
                                </div>
                                
                                <div class="summary-line summary-total">
                                    <span>Total</span>
                                    <span id="total"><?php echo number_format($cartTotal, 2); ?> MAD</span>
                                </div>
                            </div>
                            
                            <div class="promo-code">
                                <h4>Code promo</h4>
                                <div class="promo-input-group">
                                    <input type="text" placeholder="Entrez votre code" id="promo-code">
                                    <button class="btn btn-secondary">Appliquer</button>
                                </div>
                            </div>
                            
                            <div class="checkout-buttons">
                                <button class="btn btn-primary btn-full btn-checkout" onclick="proceedToCheckout()">
                                    <i class="icon-credit-card"></i>
                                    Procéder au paiement
                                </button>
                                
                                <div class="security-badges">
                                    <img src="assets/images/paypal-badge.png" alt="PayPal" class="payment-badge">
                                    <img src="assets/images/ssl-badge.png" alt="SSL Secure" class="security-badge">
                                </div>
                            </div>
                            
                            <div class="continue-shopping">
                                <a href="matches.php" class="btn btn-link">
                                    <i class="icon-arrow-left"></i>
                                    Continuer mes achats
                                </a>
                            </div>
                        </div>
                        
                        <div class="cart-info">
                            <div class="info-item">
                                <i class="icon-shield"></i>
                                <div>
                                    <h4>Paiement sécurisé</h4>
                                    <p>Vos données sont protégées par le cryptage SSL</p>
                                </div>
                            </div>
                            
                            <div class="info-item">
                                <i class="icon-mobile"></i>
                                <div>
                                    <h4>Billets électroniques</h4>
                                    <p>Recevez vos billets instantanément par email</p>
                                </div>
                            </div>
                            
                            <div class="info-item">
                                <i class="icon-support"></i>
                                <div>
                                    <h4>Support client</h4>
                                    <p>Assistance disponible 7j/7</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>
    
    <?php include 'includes/footer.php'; ?>
    
    <!-- Loading overlay -->
    <div class="loading-overlay" id="loading-overlay">
        <div class="loading-spinner"></div>
    </div>
    
    <!-- Toast notifications -->
    <div class="toast-container" id="toast-container"></div>
    
    <script src="assets/js/cart.js"></script>
</body>
</html>