<?php
require_once 'includes/functions.php';
$pageTitle = 'Erreur serveur';
require_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 text-center">
            <h1 class="display-1 text-danger mb-4">500</h1>
            <h2 class="mb-4">Erreur serveur</h2>
            <p class="lead mb-5">Une erreur inattendue s'est produite. Veuillez réessayer plus tard.</p>
            <a href="/" class="btn btn-primary">Retour à l'accueil</a>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 