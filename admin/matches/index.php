<?php
$pageTitle = "Gestion des matchs";
require_once(__DIR__ . '/../includes/layout.php');

// Check permissions
$auth->requireRole(['super_admin', 'admin', 'moderator']);

// Get filters
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = ADMIN_SETTINGS['items_per_page'];
$offset = ($page - 1) * $limit;

// Build query
$where = [];
$params = [];

if ($search) {
    $where[] = "(m.title LIKE ? OR m.description LIKE ? OR h.name LIKE ? OR a.name LIKE ?)";
    $params = array_merge($params, ["%$search%", "%$search%", "%$search%", "%$search%"]);
}

if ($status) {
    $where[] = "m.status = ?";
    $params[] = $status;
}

if ($date_from) {
    $where[] = "m.match_date >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $where[] = "m.match_date <= ?";
    $params[] = $date_to;
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total matches
$stmt = $db->prepare("
    SELECT COUNT(*) 
    FROM matches m
    LEFT JOIN teams h ON m.home_team_id = h.id
    LEFT JOIN teams a ON m.away_team_id = a.id
    $whereClause
");
$stmt->execute($params);
$total = $stmt->fetchColumn();
$totalPages = ceil($total / $limit);

// Get matches
$stmt = $db->prepare("
    SELECT 
        m.*,
        h.name as home_team,
        a.name as away_team,
        s.name as stadium,
        (SELECT COUNT(*) FROM orders o JOIN order_items oi ON o.id = oi.order_id WHERE oi.match_id = m.id) as tickets_sold
    FROM matches m
    LEFT JOIN teams h ON m.home_team_id = h.id
    LEFT JOIN teams a ON m.away_team_id = a.id
    LEFT JOIN stadiums s ON m.stadium_id = s.id
    $whereClause
    ORDER BY m.match_date DESC
    LIMIT ? OFFSET ?
");

$params[] = $limit;
$params[] = $offset;
$stmt->execute($params);
$matches = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get match statuses for filter
$statuses = ['upcoming', 'live', 'completed', 'cancelled'];
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">Gestion des matchs</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../index.php">Tableau de bord</a></li>
                <li class="breadcrumb-item active">Matchs</li>
            </ol>
        </nav>
    </div>
    
    <a href="add.php" class="btn btn-primary">
        <i class="bi bi-plus-lg me-2"></i>Ajouter un match
    </a>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Rechercher</label>
                <input type="text" name="search" class="form-control" value="<?php echo htmlspecialchars($search); ?>" placeholder="Titre, équipe...">
            </div>
            
            <div class="col-md-2">
                <label class="form-label">Statut</label>
                <select name="status" class="form-select">
                    <option value="">Tous</option>
                    <?php foreach ($statuses as $s): ?>
                        <option value="<?php echo $s; ?>" <?php echo $status === $s ? 'selected' : ''; ?>>
                            <?php echo ucfirst($s); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-2">
                <label class="form-label">Date début</label>
                <input type="date" name="date_from" class="form-control" value="<?php echo $date_from; ?>">
            </div>
            
            <div class="col-md-2">
                <label class="form-label">Date fin</label>
                <input type="date" name="date_to" class="form-control" value="<?php echo $date_to; ?>">
            </div>
            
            <div class="col-md-2 d-flex align-items-end">
                <div class="d-grid gap-2 w-100">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search me-2"></i>Filtrer
                    </button>
                    
                    <?php if ($search || $status || $date_from || $date_to): ?>
                        <a href="?" class="btn btn-light">
                            <i class="bi bi-x-lg me-2"></i>Réinitialiser
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Matches List -->
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Match</th>
                    <th>Date</th>
                    <th>Stade</th>
                    <th>Billets vendus</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($matches)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4">
                            <i class="bi bi-inbox text-muted" style="font-size: 2rem;"></i>
                            <p class="text-muted mt-2">Aucun match trouvé</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($matches as $match): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <img src="<?php echo BASE_URL . 'assets/images/teams/' . $match['home_team_id'] . '.png'; ?>" 
                                         alt="<?php echo htmlspecialchars($match['home_team']); ?>"
                                         class="team-logo" width="32">
                                    <div>
                                        <h6 class="mb-0">
                                            <?php echo htmlspecialchars($match['home_team']); ?> 
                                            vs 
                                            <?php echo htmlspecialchars($match['away_team']); ?>
                                        </h6>
                                        <small class="text-muted"><?php echo htmlspecialchars($match['title']); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php echo date('d/m/Y H:i', strtotime($match['match_date'])); ?>
                            </td>
                            <td><?php echo htmlspecialchars($match['stadium']); ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <?php echo number_format($match['tickets_sold']); ?>
                                    <div class="progress" style="width: 100px; height: 6px;">
                                        <div class="progress-bar" style="width: <?php echo min(100, ($match['tickets_sold'] / $match['capacity']) * 100); ?>%"></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo match($match['status']) {
                                        'upcoming' => 'primary',
                                        'live' => 'success',
                                        'completed' => 'secondary',
                                        'cancelled' => 'danger',
                                        default => 'secondary'
                                    };
                                ?>">
                                    <?php echo ucfirst($match['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    <a href="edit.php?id=<?php echo $match['id']; ?>" class="btn btn-sm btn-primary" title="Modifier">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="view.php?id=<?php echo $match['id']; ?>" class="btn btn-sm btn-info" title="Détails">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <?php if ($auth->hasPermission('manage_matches')): ?>
                                        <button type="button" class="btn btn-sm btn-danger" title="Supprimer"
                                                onclick="deleteMatch(<?php echo $match['id']; ?>)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <?php if ($totalPages > 1): ?>
        <div class="card-footer d-flex justify-content-between align-items-center">
            <div class="text-muted">
                Affichage de <?php echo $offset + 1; ?> à <?php echo min($offset + $limit, $total); ?> 
                sur <?php echo $total; ?> matchs
            </div>
            
            <nav aria-label="Pagination">
                <ul class="pagination mb-0">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>">
                                <i class="bi bi-chevron-left"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>">
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmer la suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer ce match ? Cette action est irréversible.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <form id="deleteForm" method="POST" action="delete.php" class="d-inline">
                    <input type="hidden" name="match_id" id="deleteMatchId">
                    <button type="submit" class="btn btn-danger">Supprimer</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
// Add page-specific scripts
$pageScripts = <<<HTML
<script>
    // Initialize date pickers
    const dateInputs = document.querySelectorAll('input[type="date"]');
    dateInputs.forEach(input => {
        new Datepicker(input, {
            format: 'yyyy-mm-dd',
            autohide: true
        });
    });
    
    // Delete match confirmation
    function deleteMatch(matchId) {
        document.getElementById('deleteMatchId').value = matchId;
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    }
</script>
HTML;
?>
