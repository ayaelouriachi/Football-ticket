<?php
require_once 'config/init.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Vous devez être connecté pour accéder à la page de paiement.";
    header('Location: login.php?redirect=cart.php');
    exit;
}

// Initialiser le panier
$cart = new Cart($db, $_SESSION);
$cartContents = $cart->getCartContents();

// Vérifier si le panier n'est pas vide
if (empty($cartContents['items'])) {
    $_SESSION['error'] = "Votre panier est vide.";
    header('Location: cart.php');
    exit;
}

// Traiter le formulaire de paiement
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Valider les données du formulaire
        if (empty($_POST['card_number']) || empty($_POST['expiry_date']) || empty($_POST['cvv'])) {
            throw new Exception("Tous les champs sont obligatoires.");
        }

        // Valider le panier une dernière fois
        $validation = $cart->validateCart();
        if (!$validation['success']) {
            throw new Exception($validation['message']);
        }

        // Simuler le traitement du paiement (à remplacer par votre logique de paiement)
        $orderId = uniqid('ORD');
        
        // Créer la commande dans la base de données
        $db->beginTransaction();
        
        $stmt = $db->prepare("
            INSERT INTO orders (
                user_id, 
                total_amount, 
                payment_method,
                payment_id,
                status
            ) VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $_SESSION['user_id'],
            $cartContents['total'],
            'card',
            $orderId,
            'completed'
        ]);
        
        $orderId = $db->lastInsertId();

        // Insérer les articles de la commande
        $stmt = $db->prepare("
            INSERT INTO order_items (
                order_id,
                ticket_category_id,
                quantity,
                price
            ) VALUES (?, ?, ?, ?)
        ");

        foreach ($cartContents['items'] as $item) {
            $stmt->execute([
                $orderId,
                $item['ticket_category_id'],
                $item['quantity'],
                $item['price']
            ]);
        }

        // Vider le panier
        $cart->clearCart();

        $db->commit();

        // Rediriger vers la page de succès
        $_SESSION['success'] = "Paiement effectué avec succès !";
        header('Location: payment_success.php');
        exit;

    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $_SESSION['error'] = $e->getMessage();
    }
}

$pageTitle = "Paiement";
include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="card-title mb-4">Informations de paiement</h2>
                    
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger">
                            <?php 
                            echo htmlspecialchars($_SESSION['error']);
                            unset($_SESSION['error']);
                            ?>
                        </div>
                    <?php endif; ?>

                    <form method="post" id="payment-form">
                        <div class="mb-3">
                            <label for="card_number" class="form-label">Numéro de carte</label>
                            <input type="text" class="form-control" id="card_number" name="card_number" 
                                   placeholder="1234 5678 9012 3456" required
                                   pattern="\d{4}\s?\d{4}\s?\d{4}\s?\d{4}">
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="expiry_date" class="form-label">Date d'expiration</label>
                                <input type="text" class="form-control" id="expiry_date" name="expiry_date" 
                                       placeholder="MM/YY" required
                                       pattern="\d{2}/\d{2}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="cvv" class="form-label">CVV</label>
                                <input type="text" class="form-control" id="cvv" name="cvv" 
                                       placeholder="123" required
                                       pattern="\d{3,4}">
                            </div>
                        </div>

                        <div class="mb-4">
                            <h4 class="mb-3">Résumé de la commande</h4>
                            <?php foreach ($cartContents['items'] as $item): ?>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div>
                                        <h6 class="mb-0"><?php echo htmlspecialchars($item['match_title']); ?></h6>
                                        <small class="text-muted">
                                            <?php echo htmlspecialchars($item['category_name']); ?> x 
                                            <?php echo $item['quantity']; ?>
                                        </small>
                                    </div>
                                    <span><?php echo number_format($item['subtotal'], 2); ?> MAD</span>
                                </div>
                            <?php endforeach; ?>
                            <hr>
                            <div class="d-flex justify-content-between align-items-center">
                                <strong>Total</strong>
                                <strong><?php echo number_format($cartContents['total'], 2); ?> MAD</strong>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-lock-fill me-2"></i>
                            Payer <?php echo number_format($cartContents['total'], 2); ?> MAD
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Formater automatiquement le numéro de carte
document.getElementById('card_number').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    let formattedValue = '';
    for (let i = 0; i < value.length; i++) {
        if (i > 0 && i % 4 === 0) {
            formattedValue += ' ';
        }
        formattedValue += value[i];
    }
    e.target.value = formattedValue.slice(0, 19);
});

// Formater automatiquement la date d'expiration
document.getElementById('expiry_date').addEventListener('input', function(e) {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length >= 2) {
        value = value.slice(0, 2) + '/' + value.slice(2);
    }
    e.target.value = value.slice(0, 5);
});

// Limiter la longueur du CVV
document.getElementById('cvv').addEventListener('input', function(e) {
    e.target.value = e.target.value.replace(/\D/g, '').slice(0, 4);
});
</script>

<?php include 'includes/footer.php'; ?> 