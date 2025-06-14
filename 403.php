<?php
require_once 'includes/functions.php';
$pageTitle = 'Accès interdit';
require_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 text-center">
            <h1 class="display-1 text-danger mb-4">403</h1>
            <h2 class="mb-4">Accès interdit</h2>
            <p class="lead mb-5">Vous n'avez pas les permissions nécessaires pour accéder à cette page.</p>
            <a href="/" class="btn btn-primary">Retour à l'accueil</a>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?> 