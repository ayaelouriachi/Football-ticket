<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$pageTitle = "Gestion des matchs";

// Inclure les fichiers nécessaires
require_once(__DIR__ . '/../config/init.php');
require_once(__DIR__ . '/includes/config.php');

// Start output buffering
ob_start();

// Get filters from query string
$status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10;

try {
    // Build where clause
    $where = [];
    $params = [];
    
    if ($status) {
        $where[] = "m.status = ?";
        $params[] = $status;
    }
    
    if ($search) {
        $where[] = "(t1.name LIKE ? OR t2.name LIKE ? OR s.name LIKE ?)";
        $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
    }
    
    $whereClause = $where ? "WHERE " . implode(" AND ", $where) : "";
    
    // Get total count
    $countSql = "SELECT COUNT(*) as total FROM matches m
                 LEFT JOIN teams t1 ON m.home_team_id = t1.id
                 LEFT JOIN teams t2 ON m.away_team_id = t2.id
                 LEFT JOIN stadiums s ON m.stadium_id = s.id
                 $whereClause";
    $stmt = $db->prepare($countSql);
    $stmt->execute($params);
    $total = $stmt->fetch()['total'];
    
    // Get matches
    $offset = ($page - 1) * $limit;
    $sql = "SELECT 
                m.*,
                t1.name as home_team,
                t1.logo as home_team_logo,
                t2.name as away_team,
                t2.logo as away_team_logo,
                s.name as stadium,
                (SELECT COUNT(*) FROM order_items oi WHERE oi.match_id = m.id) as tickets_sold,
                (SELECT SUM(tc.capacity) FROM ticket_categories tc WHERE tc.match_id = m.id) as total_capacity
            FROM matches m
            LEFT JOIN teams t1 ON m.home_team_id = t1.id
            LEFT JOIN teams t2 ON m.away_team_id = t2.id
            LEFT JOIN stadiums s ON m.stadium_id = s.id
            $whereClause
            ORDER BY m.match_date DESC
            LIMIT ? OFFSET ?";
    
    $stmt = $db->prepare($sql);
    $stmt->execute(array_merge($params, [$limit, $offset]));
    $matches = $stmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Error fetching matches: " . $e->getMessage());
    $error = "Une erreur s'est produite lors de la récupération des matchs.";
}
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Gestion des matchs</h1>
        <a href="matches/create.php" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i>Ajouter un match
        </a>
    </div>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Rechercher</label>
                    <input type="text" name="search" class="form-control" 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Rechercher équipes ou stade...">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Statut</label>
                    <select name="status" class="form-select">
                        <option value="">Tous les statuts</option>
                        <option value="draft" <?php echo $status === 'draft' ? 'selected' : ''; ?>>Brouillon</option>
                        <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Actif</option>
                        <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Terminé</option>
                        <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Annulé</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search me-2"></i>Filtrer
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo $error; ?>
        </div>
    <?php else: ?>
    
    <!-- Matches Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Match</th>
                            <th>Date & Heure</th>
                            <th>Stade</th>
                            <th>Billets</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($matches as $match): ?>
                        <tr>
                            <td>#<?php echo str_pad($match['id'], 5, '0', STR_PAD_LEFT); ?></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="team-logos">
                                        <img src="<?php echo htmlspecialchars($match['home_team_logo']); ?>" 
                                             alt="<?php echo htmlspecialchars($match['home_team']); ?>"
                                             class="team-logo me-2"
                                             onerror="this.src='../assets/img/default-team.png'">
                                        <span class="text-muted">vs</span>
                                        <img src="<?php echo htmlspecialchars($match['away_team_logo']); ?>" 
                                             alt="<?php echo htmlspecialchars($match['away_team']); ?>"
                                             class="team-logo ms-2"
                                             onerror="this.src='../assets/img/default-team.png'">
                                    </div>
                                    <div class="ms-3">
                                        <div class="fw-bold"><?php echo htmlspecialchars($match['home_team']); ?></div>
                                        <div class="text-muted">vs</div>
                                        <div class="fw-bold"><?php echo htmlspecialchars($match['away_team']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div><?php echo date('d M Y', strtotime($match['match_date'])); ?></div>
                                <div class="text-muted"><?php echo date('H:i', strtotime($match['match_date'])); ?></div>
                            </td>
                            <td><?php echo htmlspecialchars($match['stadium']); ?></td>
                            <td>
                                <?php 
                                $soldPercent = $match['total_capacity'] ? 
                                    round(($match['tickets_sold'] / $match['total_capacity']) * 100) : 0;
                                ?>
                                <div class="d-flex align-items-center">
                                    <div class="progress flex-grow-1 me-2" style="height: 6px;">
                                        <div class="progress-bar" role="progressbar" 
                                             style="width: <?php echo $soldPercent; ?>%"
                                             aria-valuenow="<?php echo $soldPercent; ?>" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100"></div>
                                    </div>
                                    <span class="text-muted small">
                                        <?php echo $match['tickets_sold']; ?>/<?php echo $match['total_capacity']; ?>
                                    </span>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo match($match['status']) {
                                        'active' => 'success',
                                        'draft' => 'warning',
                                        'completed' => 'info',
                                        'cancelled' => 'danger',
                                        default => 'secondary'
                                    };
                                ?>">
                                    <?php echo ucfirst($match['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="matches/edit.php?id=<?php echo $match['id']; ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="matches/categories.php?id=<?php echo $match['id']; ?>" 
                                       class="btn btn-sm btn-outline-info">
                                        <i class="bi bi-ticket-perforated"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total > $limit): ?>
            <div class="d-flex justify-content-center mt-4">
                <nav aria-label="Page navigation">
                    <ul class="pagination">
                        <?php
                        $totalPages = ceil($total / $limit);
                        $range = 2;
                        
                        // Previous page
                        if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo ($page - 1); ?>&status=<?php echo $status; ?>&search=<?php echo urlencode($search); ?>">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>
                        <?php endif;
                        
                        // Page numbers
                        for ($i = max(1, $page - $range); $i <= min($totalPages, $page + $range); $i++): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $status; ?>&search=<?php echo urlencode($search); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor;
                        
                        // Next page
                        if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo ($page + 1); ?>&status=<?php echo $status; ?>&search=<?php echo urlencode($search); ?>">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php
// Get the buffered content
$content = ob_get_clean();

// Include the layout
require_once(__DIR__ . '/includes/layout.php'); 