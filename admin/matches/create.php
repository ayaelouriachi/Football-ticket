<?php
$pageTitle = "Ajouter un match";
require_once(__DIR__ . '/../includes/layout.php');
require_once(__DIR__ . '/../api/config/database.php');

// Vérifier les permissions
if (!$auth->hasPermission('manage_matches')) {
    SessionManager::setFlashMessage('error', 'Vous n\'avez pas la permission d\'accéder à cette page.');
    header('Location: ../index.php');
    exit;
}

// Initialiser la connexion à la base de données
try {
    $db = Database::getInstance();
    
    // Récupérer la liste des équipes
    $teamsStmt = $db->query("SELECT id, name FROM teams ORDER BY name");
    $teams = $teamsStmt->fetchAll();
    
    // Récupérer la liste des stades
    $stadiumsStmt = $db->query("SELECT id, name, capacity FROM stadiums ORDER BY name");
    $stadiums = $stadiumsStmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Error in create match form: " . $e->getMessage());
    SessionManager::setFlashMessage('error', 'Une erreur est survenue lors du chargement du formulaire.');
}
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0 text-light">Ajouter un match</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../index.php">Tableau de bord</a></li>
                <li class="breadcrumb-item"><a href="../matches.php">Matchs</a></li>
                <li class="breadcrumb-item active" aria-current="page">Ajouter</li>
            </ol>
        </nav>
    </div>
</div>

<!-- Match Form -->
<div class="card">
    <div class="card-body">
        <form id="matchForm" method="POST">
            <!-- Équipes -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="home_team_id" class="form-label">Équipe domicile</label>
                        <select name="home_team_id" id="home_team_id" class="form-select" required>
                            <option value="">Sélectionner une équipe</option>
                            <?php foreach ($teams as $team): ?>
                                <option value="<?php echo $team['id']; ?>">
                                    <?php echo htmlspecialchars($team['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="away_team_id" class="form-label">Équipe extérieur</label>
                        <select name="away_team_id" id="away_team_id" class="form-select" required>
                            <option value="">Sélectionner une équipe</option>
                            <?php foreach ($teams as $team): ?>
                                <option value="<?php echo $team['id']; ?>">
                                    <?php echo htmlspecialchars($team['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Stade et Date -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="stadium_id" class="form-label">Stade</label>
                        <select name="stadium_id" id="stadium_id" class="form-select" required>
                            <option value="">Sélectionner un stade</option>
                            <?php foreach ($stadiums as $stadium): ?>
                                <option value="<?php echo $stadium['id']; ?>">
                                    <?php echo htmlspecialchars($stadium['name']); ?> 
                                    (<?php echo number_format($stadium['capacity']); ?> places)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="match_date" class="form-label">Date et heure du match</label>
                        <input type="datetime-local" name="match_date" id="match_date" class="form-control" required>
                    </div>
                </div>
            </div>

            <!-- Catégories de billets -->
            <div class="mb-4">
                <h4>Catégories de billets</h4>
                <div id="ticketCategories">
                    <div class="ticket-category card mb-3">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="form-label">Nom de la catégorie</label>
                                        <input type="text" name="category_name[]" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="form-label">Prix</label>
                                        <input type="number" name="category_price[]" class="form-control" min="0" step="0.01" required>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="form-label">Capacité</label>
                                        <input type="number" name="category_capacity[]" class="form-control" min="1" required>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label class="form-label">Description</label>
                                        <input type="text" name="category_description[]" class="form-control">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-outline-primary" onclick="addTicketCategory()">
                    <i class="bi bi-plus-circle me-2"></i>Ajouter une catégorie
                </button>
            </div>

            <!-- Boutons -->
            <div class="d-flex justify-content-end gap-2">
                <a href="../matches.php" class="btn btn-secondary">Annuler</a>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-2"></i>Créer le match
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function addTicketCategory() {
    const template = document.querySelector('.ticket-category').cloneNode(true);
    // Réinitialiser les valeurs
    template.querySelectorAll('input').forEach(input => input.value = '');
    document.getElementById('ticketCategories').appendChild(template);
}

// Validation des équipes
document.getElementById('matchForm').addEventListener('submit', function(e) {
    const homeTeam = document.getElementById('home_team_id').value;
    const awayTeam = document.getElementById('away_team_id').value;
    
    if (homeTeam === awayTeam) {
        e.preventDefault();
        alert('Les équipes domicile et extérieur doivent être différentes');
    }
});
</script>

<!-- Inclure le script de gestion des matchs -->
<script src="/admin/assets/js/matches.js"></script> 