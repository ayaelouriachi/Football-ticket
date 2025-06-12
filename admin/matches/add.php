<?php
$pageTitle = "Ajouter un match";
require_once(__DIR__ . '/../includes/layout.php');

// Check permissions
$auth->requireRole(['super_admin', 'admin']);

// Get teams and stadiums for dropdowns
$teams = $db->query("SELECT id, name FROM teams ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$stadiums = $db->query("SELECT id, name, capacity FROM stadiums ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    // Validate required fields
    $requiredFields = ['title', 'home_team_id', 'away_team_id', 'stadium_id', 'match_date', 'match_time'];
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            $errors[] = "Le champ " . str_replace('_', ' ', $field) . " est requis.";
        }
    }
    
    // Validate teams are different
    if ($_POST['home_team_id'] === $_POST['away_team_id']) {
        $errors[] = "Les équipes domicile et extérieur doivent être différentes.";
    }
    
    // Validate date and time
    $matchDateTime = date('Y-m-d H:i:s', strtotime($_POST['match_date'] . ' ' . $_POST['match_time']));
    if ($matchDateTime < date('Y-m-d H:i:s')) {
        $errors[] = "La date du match doit être dans le futur.";
    }
    
    // Process if no errors
    if (empty($errors)) {
        try {
            $db->beginTransaction();
            
            // Insert match
            $stmt = $db->prepare("
                INSERT INTO matches (
                    title, description, home_team_id, away_team_id, stadium_id, 
                    match_date, status, ticket_price, capacity, created_at
                ) VALUES (
                    :title, :description, :home_team_id, :away_team_id, :stadium_id,
                    :match_date, 'upcoming', :ticket_price, 
                    (SELECT capacity FROM stadiums WHERE id = :stadium_id),
                    NOW()
                )
            ");
            
            $stmt->execute([
                'title' => $_POST['title'],
                'description' => $_POST['description'],
                'home_team_id' => $_POST['home_team_id'],
                'away_team_id' => $_POST['away_team_id'],
                'stadium_id' => $_POST['stadium_id'],
                'match_date' => $matchDateTime,
                'ticket_price' => $_POST['ticket_price']
            ]);
            
            $matchId = $db->lastInsertId();
            
            // Create ticket categories if specified
            if (!empty($_POST['categories'])) {
                $stmt = $db->prepare("
                    INSERT INTO ticket_categories (
                        match_id, name, price, capacity
                    ) VALUES (
                        :match_id, :name, :price, :capacity
                    )
                ");
                
                foreach ($_POST['categories'] as $category) {
                    if (!empty($category['name']) && !empty($category['price']) && !empty($category['capacity'])) {
                        $stmt->execute([
                            'match_id' => $matchId,
                            'name' => $category['name'],
                            'price' => $category['price'],
                            'capacity' => $category['capacity']
                        ]);
                    }
                }
            }
            
            $db->commit();
            $_SESSION['success'] = "Le match a été créé avec succès.";
            header('Location: index.php');
            exit;
            
        } catch (PDOException $e) {
            $db->rollBack();
            $errors[] = "Une erreur est survenue lors de la création du match : " . $e->getMessage();
        }
    }
}
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">Ajouter un match</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../index.php">Tableau de bord</a></li>
                <li class="breadcrumb-item"><a href="index.php">Matchs</a></li>
                <li class="breadcrumb-item active">Ajouter</li>
            </ol>
        </nav>
    </div>
</div>

<!-- Error Messages -->
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<!-- Match Form -->
<div class="card">
    <div class="card-body">
        <form method="POST" class="needs-validation" novalidate>
            <div class="row g-4">
                <!-- Basic Information -->
                <div class="col-md-12">
                    <h5 class="card-title">Informations de base</h5>
                    <hr>
                </div>
                
                <div class="col-md-12">
                    <label class="form-label">Titre du match</label>
                    <input type="text" name="title" class="form-control" required
                           value="<?php echo $_POST['title'] ?? ''; ?>"
                           placeholder="Ex: Finale de la Coupe">
                    <div class="invalid-feedback">Le titre est requis</div>
                </div>
                
                <div class="col-md-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3"
                              placeholder="Description détaillée du match..."><?php echo $_POST['description'] ?? ''; ?></textarea>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Équipe domicile</label>
                    <select name="home_team_id" class="form-select" required>
                        <option value="">Sélectionner une équipe</option>
                        <?php foreach ($teams as $team): ?>
                            <option value="<?php echo $team['id']; ?>" 
                                    <?php echo isset($_POST['home_team_id']) && $_POST['home_team_id'] == $team['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($team['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback">L'équipe domicile est requise</div>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">Équipe extérieur</label>
                    <select name="away_team_id" class="form-select" required>
                        <option value="">Sélectionner une équipe</option>
                        <?php foreach ($teams as $team): ?>
                            <option value="<?php echo $team['id']; ?>"
                                    <?php echo isset($_POST['away_team_id']) && $_POST['away_team_id'] == $team['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($team['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback">L'équipe extérieur est requise</div>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Stade</label>
                    <select name="stadium_id" class="form-select" required>
                        <option value="">Sélectionner un stade</option>
                        <?php foreach ($stadiums as $stadium): ?>
                            <option value="<?php echo $stadium['id']; ?>"
                                    data-capacity="<?php echo $stadium['capacity']; ?>"
                                    <?php echo isset($_POST['stadium_id']) && $_POST['stadium_id'] == $stadium['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($stadium['name']); ?> 
                                (<?php echo number_format($stadium['capacity']); ?> places)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback">Le stade est requis</div>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Date</label>
                    <input type="date" name="match_date" class="form-control" required
                           value="<?php echo $_POST['match_date'] ?? ''; ?>">
                    <div class="invalid-feedback">La date est requise</div>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Heure</label>
                    <input type="time" name="match_time" class="form-control" required
                           value="<?php echo $_POST['match_time'] ?? ''; ?>">
                    <div class="invalid-feedback">L'heure est requise</div>
                </div>
                
                <!-- Ticket Categories -->
                <div class="col-md-12 mt-4">
                    <h5 class="card-title">Catégories de billets</h5>
                    <hr>
                </div>
                
                <div class="col-md-12">
                    <div id="ticketCategories">
                        <!-- Template for ticket category -->
                        <template id="categoryTemplate">
                            <div class="row g-3 mb-3 category-row">
                                <div class="col-md-4">
                                    <label class="form-label">Nom de la catégorie</label>
                                    <input type="text" name="categories[{index}][name]" class="form-control"
                                           placeholder="Ex: Tribune VIP">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Prix</label>
                                    <div class="input-group">
                                        <input type="number" name="categories[{index}][price]" class="form-control"
                                               min="0" step="0.01" placeholder="0.00">
                                        <span class="input-group-text">€</span>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Capacité</label>
                                    <input type="number" name="categories[{index}][capacity]" class="form-control category-capacity"
                                           min="1" placeholder="Nombre de places">
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="button" class="btn btn-outline-danger remove-category">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </template>
                        
                        <!-- Container for dynamic categories -->
                        <div id="categoriesContainer"></div>
                        
                        <button type="button" class="btn btn-outline-primary" id="addCategory">
                            <i class="bi bi-plus-lg me-2"></i>Ajouter une catégorie
                        </button>
                    </div>
                </div>
                
                <div class="col-12 mt-4">
                    <hr>
                    <div class="d-flex justify-content-end gap-2">
                        <a href="index.php" class="btn btn-light">Annuler</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-2"></i>Créer le match
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<?php
// Add page-specific scripts
$pageScripts = <<<HTML
<script>
    // Form validation
    (function() {
        'use strict';
        
        const form = document.querySelector('.needs-validation');
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    })();
    
    // Ticket categories management
    (function() {
        const container = document.getElementById('categoriesContainer');
        const template = document.getElementById('categoryTemplate');
        const addButton = document.getElementById('addCategory');
        let categoryCount = 0;
        
        // Add category
        addButton.addEventListener('click', () => {
            const content = template.content.cloneNode(true);
            const row = content.querySelector('.category-row');
            
            // Update indices
            row.querySelectorAll('input').forEach(input => {
                input.name = input.name.replace('{index}', categoryCount);
            });
            
            // Add remove handler
            row.querySelector('.remove-category').addEventListener('click', () => {
                row.remove();
                updateCapacityValidation();
            });
            
            // Add capacity change handler
            row.querySelector('.category-capacity').addEventListener('input', updateCapacityValidation);
            
            container.appendChild(row);
            categoryCount++;
            updateCapacityValidation();
        });
        
        // Validate total capacity
        function updateCapacityValidation() {
            const stadium = document.querySelector('select[name="stadium_id"] option:checked');
            if (!stadium) return;
            
            const maxCapacity = parseInt(stadium.dataset.capacity || 0);
            let totalCapacity = 0;
            
            document.querySelectorAll('.category-capacity').forEach(input => {
                totalCapacity += parseInt(input.value || 0);
            });
            
            const isValid = totalCapacity <= maxCapacity;
            document.querySelectorAll('.category-capacity').forEach(input => {
                input.setCustomValidity(isValid ? '' : `La capacité totale ne peut pas dépasser ${maxCapacity} places`);
            });
        }
        
        // Update validation when stadium changes
        document.querySelector('select[name="stadium_id"]').addEventListener('change', updateCapacityValidation);
        
        // Add initial category
        addButton.click();
    })();
</script>
HTML;
?>
