<?php
require_once 'config/init.php';

$pageTitle = "Paiement réussi";
include 'includes/header.php';
?>

<div class="container py-5">
    <div class="text-center">
        <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
        <h1 class="mt-4">Paiement réussi !</h1>
        <p class="lead">Merci pour votre achat. Votre commande a été traitée avec succès.</p>
        <p>Un email de confirmation vous sera envoyé avec les détails de votre commande.</p>
        <div class="mt-4">
            <a href="orders.php" class="btn btn-primary me-3">
                <i class="fas fa-ticket-alt me-2"></i>Voir mes commandes
            </a>
            <a href="matches.php" class="btn btn-outline-primary">
                <i class="fas fa-futbol me-2"></i>Continuer mes achats
            </a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 