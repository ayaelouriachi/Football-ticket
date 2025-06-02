<?php
require_once 'config/init.php';

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

                case 'checkout':
                    $result = $cart->validateCart();
                    if (!$result['success']) {
                        throw new Exception($result['message']);
                    }
                    header('Location: checkout.php');
                    exit;
            }
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
    
    header('Location: cart.php');
    exit;
}

// Get cart contents
$cart_contents = $cart->getCartContents();

$page_title = "Mon Panier";
include 'includes/header.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap Icons CDN -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>

<style>
:root {
    --primary: #3498db;
    --secondary: #f8f9fa;
    --success: #2ecc71;
    --danger: #e74c3c;
    --dark: #2c3e50;
    --gray: #95a5a6;
    --light: #ecf0f1;
    --shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
    --radius: 16px;
    --transition: all 0.3s ease;
}

.cart-container {
    padding: 2rem 0;
    background: var(--secondary);
    min-height: calc(100vh - 100px);
}

.cart-item {
    background: white;
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    margin-bottom: 1.5rem;
    overflow: hidden;
    border: 1px solid rgba(0,0,0,0.05);
    transition: var(--transition);
}

.cart-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.12);
}

.ticket-header {
    background: var(--light);
    padding: 1.5rem;
    border-bottom: 1px solid rgba(0,0,0,0.05);
}

.match-info {
    display: flex;
    align-items: center;
    gap: 2rem;
}

.teams-container {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    padding: 0.5rem;
    background: white;
    border-radius: var(--radius);
    box-shadow: var(--shadow);
}

.team-logo {
    width: 64px;
    height: 64px;
    padding: 8px;
    background: white;
    border-radius: 50%;
    transition: var(--transition);
}

.team-logo:hover {
    transform: scale(1.1);
}

.vs-badge {
    background: var(--primary);
    color: white;
    padding: 0.5rem 1.2rem;
    border-radius: 30px;
    font-weight: 600;
    font-size: 1rem;
    box-shadow: var(--shadow);
}

.match-details {
    flex-grow: 1;
}

.match-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--dark);
    margin-bottom: 0.75rem;
}

.match-meta {
    display: flex;
    gap: 2rem;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    color: var(--gray);
    font-size: 1rem;
    background: white;
    padding: 0.5rem 1rem;
    border-radius: 25px;
    box-shadow: var(--shadow);
}

.meta-item i {
    color: var(--primary);
    font-size: 1.1rem;
}

.ticket-body {
    padding: 1.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 2rem;
}

.quantity-section {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.quantity-label {
    font-size: 0.95rem;
    color: var(--gray);
    font-weight: 500;
}

.quantity-wrapper {
    display: flex;
    align-items: center;
    background: var(--secondary);
    border-radius: var(--radius);
    padding: 0.5rem;
    gap: 0.5rem;
    box-shadow: var(--shadow);
}

.btn-quantity {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    border-radius: 12px;
    cursor: pointer;
    transition: var(--transition);
    background: white;
    box-shadow: var(--shadow);
}

.btn-quantity.minus {
    color: var(--danger);
}

.btn-quantity.plus {
    color: var(--success);
}

.btn-quantity:hover {
    transform: scale(1.1);
}

.btn-quantity i {
    font-size: 1.2rem;
}

.quantity-input {
    width: 60px;
    height: 40px;
    text-align: center;
    border: 1px solid rgba(0,0,0,0.1);
    border-radius: 12px;
    font-size: 1.2rem;
    font-weight: 600;
    color: var(--dark);
    background: white;
}

.price-section {
    display: flex;
    align-items: center;
    gap: 2rem;
}

.price-tag {
    text-align: right;
    background: var(--secondary);
    padding: 1rem 1.5rem;
    border-radius: var(--radius);
    box-shadow: var(--shadow);
}

.price-label {
    font-size: 0.9rem;
    color: var(--gray);
    margin-bottom: 0.5rem;
}

.price-value {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--dark);
}

.btn-delete {
    background: var(--danger);
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: var(--radius);
    font-weight: 600;
    cursor: pointer;
    transition: var(--transition);
    display: flex;
    align-items: center;
    gap: 0.75rem;
    box-shadow: var(--shadow);
}

.btn-delete:hover {
    background: #c0392b;
    transform: translateY(-2px);
}

.btn-delete i {
    font-size: 1.2rem;
}

/* Style du résumé de commande */
.cart-summary {
    background: white;
    border-radius: var(--radius);
    padding: 2rem;
    box-shadow: var(--shadow);
    position: sticky;
    top: 2rem;
}

.summary-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--dark);
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid var(--secondary);
}

.summary-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 1rem;
    padding: 0.75rem;
    background: var(--secondary);
    border-radius: var(--radius);
    color: var(--gray);
}

.summary-total {
    display: flex;
    justify-content: space-between;
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--dark);
    margin-top: 1.5rem;
    padding: 1rem;
    background: var(--secondary);
    border-radius: var(--radius);
}

.checkout-btn {
    background: var(--success);
    color: white;
    border: none;
    width: 100%;
    padding: 1rem;
    border-radius: var(--radius);
    font-size: 1.2rem;
    font-weight: 600;
    cursor: pointer;
    transition: var(--transition);
    margin-top: 1.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    box-shadow: var(--shadow);
}

.checkout-btn:hover {
    background: #27ae60;
    transform: translateY(-2px);
}

.checkout-btn i {
    font-size: 1.3rem;
}

/* Style pour panier vide */
.empty-cart {
    text-align: center;
    padding: 4rem 2rem;
    background: white;
    border-radius: var(--radius);
    box-shadow: var(--shadow);
}

.empty-cart-icon {
    font-size: 5rem;
    color: var(--gray);
    margin-bottom: 2rem;
}

.empty-cart h3 {
    font-size: 1.75rem;
    color: var(--dark);
    margin-bottom: 1rem;
}

.empty-cart p {
    color: var(--gray);
    margin-bottom: 2rem;
}

.empty-cart .btn-primary {
    background: var(--primary);
    border: none;
    padding: 1rem 2rem;
    font-size: 1.1rem;
    border-radius: var(--radius);
    transition: var(--transition);
}

.empty-cart .btn-primary:hover {
    background: #2980b9;
    transform: translateY(-2px);
}

@media (max-width: 768px) {
    .match-info {
        flex-direction: column;
        text-align: center;
        gap: 1.5rem;
    }

    .match-meta {
        justify-content: center;
        flex-wrap: wrap;
    }

    .ticket-body {
        flex-direction: column;
        text-align: center;
        padding: 1.5rem;
    }

    .price-section {
        flex-direction: column;
        gap: 1.5rem;
    }

    .price-tag {
        text-align: center;
    }

    .quantity-section {
        align-items: center;
    }

    .cart-summary {
        margin-top: 2rem;
    }
}

/* Styles spécifiques pour les icônes */
.bi {
    display: inline-block;
    line-height: 1;
    vertical-align: middle;
}

.btn-quantity .bi {
    font-size: 1.4rem;
    width: 1.4rem;
    height: 1.4rem;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-delete .bi {
    font-size: 1.2rem;
}

.meta-item .bi {
    font-size: 1.2rem;
    color: var(--primary);
}

.empty-cart .bi {
    font-size: 4rem;
    color: var(--gray);
}

.checkout-btn .bi {
    font-size: 1.3rem;
}
</style>

<div class="container cart-container">
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
                                            <span><?php echo htmlspecialchars($item['name']); ?></span>
                                        </div>
                                        <div class="meta-item">
                                            <i class="bi bi-calendar-event"></i>
                                            <span><?php echo date('d/m/Y H:i', strtotime($item['match_date'])); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="ticket-body">
                            <div class="quantity-section">
                                <label class="quantity-label">Quantité</label>
                                <form method="post" id="quantity-form-<?php echo $item['id']; ?>" class="quantity-form">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="category_id" value="<?php echo $item['id']; ?>">
                                    <div class="quantity-wrapper">
                                        <button type="button" class="btn-quantity minus" onclick="updateQuantity(<?php echo $item['id']; ?>, -1)">
                                            <i class="bi bi-dash-lg"></i>
                                        </button>
                                        <input type="text" 
                                               id="quantity-<?php echo $item['id']; ?>"
                                               name="quantity" 
                                               value="<?php echo $item['quantity']; ?>" 
                                               class="quantity-input"
                                               readonly>
                                        <button type="button" class="btn-quantity plus" onclick="updateQuantity(<?php echo $item['id']; ?>, 1)">
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
                                <form method="post" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce billet ?');">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="category_id" value="<?php echo $item['id']; ?>">
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
                        <span><?php echo number_format($cart_contents['total'], 2); ?> MAD</span>
                    </div>
                    <form method="post">
                        <input type="hidden" name="action" value="checkout">
                        <button type="submit" class="checkout-btn">
                            <i class="bi bi-credit-card"></i>
                            <span>Procéder au paiement</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function updateQuantity(itemId, delta) {
    const form = document.getElementById('quantity-form-' + itemId);
    const input = document.getElementById('quantity-' + itemId);
    const currentValue = parseInt(input.value) || 1;
    const min = 1;
    const max = 10;
    const newValue = currentValue + delta;
    
    if (newValue >= min && newValue <= max) {
        input.value = newValue;
        form.submit();
    } else {
        if (newValue < min) {
            alert('La quantité minimum est de 1 billet');
        } else if (newValue > max) {
            alert('La quantité maximum est de 10 billets');
        }
    }
}
</script>

<?php include 'includes/footer.php'; ?>
</body>
</html>