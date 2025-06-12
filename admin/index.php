<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Inclure les fichiers nécessaires
require_once(__DIR__ . '/../config/init.php');
require_once(__DIR__ . '/includes/config.php');

$pageTitle = "Tableau de bord - Administration";

// Start output buffering
ob_start();

// Debug information
if (!isset($db)) {
    die("Database connection not available");
}

// Get statistics
try {
    // Total matches this month
    $stmt = $db->prepare("
        SELECT COUNT(*) 
        FROM matches 
        WHERE DATE_FORMAT(match_date, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')
    ");
    $stmt->execute();
    $totalMatches = $stmt->fetchColumn();
    
    // Total tickets sold
    $stmt = $db->prepare("
        SELECT COUNT(*) 
        FROM orders o 
        JOIN order_items oi ON o.id = oi.order_id 
        WHERE o.status = 'completed'
    ");
    $stmt->execute();
    $totalTickets = $stmt->fetchColumn();
    
    // Revenue generated
    $stmt = $db->prepare("
        SELECT SUM(total_amount) 
        FROM orders 
        WHERE status = 'completed'
    ");
    $stmt->execute();
    $totalRevenue = $stmt->fetchColumn() ?: 0;
    
    // Active users count
    $stmt = $db->prepare("
        SELECT COUNT(*) 
        FROM users 
        WHERE is_active = 1
    ");
    $stmt->execute();
    $activeUsers = $stmt->fetchColumn();
    
    // Monthly revenue data for chart
    $stmt = $db->prepare("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            SUM(total_amount) as revenue
        FROM orders
        WHERE status = 'completed'
        AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month ASC
    ");
    $stmt->execute();
    $monthlyRevenue = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Popular ticket categories
    $stmt = $db->prepare("
        SELECT 
            tc.name as category,
            COUNT(*) as total_sold
        FROM order_items oi
        JOIN ticket_categories tc ON oi.ticket_category_id = tc.id
        JOIN orders o ON oi.order_id = o.id
        WHERE o.status = 'completed'
        GROUP BY tc.id
        ORDER BY total_sold DESC
        LIMIT 5
    ");
    $stmt->execute();
    $popularCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Recent activities
    $stmt = $db->prepare("
        SELECT 
            o.id,
            o.created_at,
            o.total_amount,
            o.status,
            u.name as user_name,
            COUNT(oi.id) as ticket_count
        FROM orders o
        JOIN users u ON o.user_id = u.id
        JOIN order_items oi ON o.id = oi.order_id
        GROUP BY o.id
        ORDER BY o.created_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    $recentActivities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Dashboard error: " . $e->getMessage());
    adminSetFlashMessage('error', 'Une erreur est survenue lors du chargement des statistiques.');
}
?>

<!-- Statistics Cards -->
<div class="row g-4 mb-4">
    <!-- Total Matches -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="stats-icon bg-primary">
                            <i class="bi bi-calendar-event"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="card-subtitle text-muted mb-1">Matchs ce mois</h6>
                        <h2 class="card-title mb-0"><?php echo number_format($totalMatches); ?></h2>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Total Tickets -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="stats-icon bg-success">
                            <i class="bi bi-ticket-perforated"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="card-subtitle text-muted mb-1">Billets vendus</h6>
                        <h2 class="card-title mb-0"><?php echo number_format($totalTickets); ?></h2>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Revenue -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="stats-icon bg-warning">
                            <i class="bi bi-currency-dollar"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="card-subtitle text-muted mb-1">Revenus totaux</h6>
                        <h2 class="card-title mb-0"><?php echo number_format($totalRevenue, 2); ?> MAD</h2>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Active Users -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0">
                        <div class="stats-icon bg-info">
                            <i class="bi bi-people"></i>
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h6 class="card-subtitle text-muted mb-1">Utilisateurs actifs</h6>
                        <h2 class="card-title mb-0"><?php echo number_format($activeUsers); ?></h2>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row g-4 mb-4">
    <!-- Revenue Chart -->
    <div class="col-12 col-xl-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Revenus mensuels</h5>
            </div>
            <div class="card-body">
                <canvas id="revenueChart" height="300"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Popular Categories -->
    <div class="col-12 col-xl-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Catégories populaires</h5>
            </div>
            <div class="card-body">
                <canvas id="categoriesChart" height="300"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activities -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Activités récentes</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Client</th>
                    <th>Billets</th>
                    <th>Montant</th>
                    <th>Statut</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentActivities as $activity): ?>
                    <tr>
                        <td>#<?php echo $activity['id']; ?></td>
                        <td><?php echo htmlspecialchars($activity['user_name']); ?></td>
                        <td><?php echo $activity['ticket_count']; ?></td>
                        <td><?php echo number_format($activity['total_amount'], 2); ?> MAD</td>
                        <td>
                            <span class="badge bg-<?php 
                                echo match($activity['status']) {
                                    'completed' => 'success',
                                    'pending' => 'warning',
                                    'cancelled' => 'danger',
                                    default => 'secondary'
                                };
                            ?>">
                                <?php echo ucfirst($activity['status']); ?>
                            </span>
                        </td>
                        <td><?php echo date('d/m/Y H:i', strtotime($activity['created_at'])); ?></td>
                        <td>
                            <a href="orders/view.php?id=<?php echo $activity['id']; ?>" class="btn btn-sm btn-primary">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
// Get the buffered content
$content = ob_get_clean();

// Préparer les données pour les graphiques
$monthLabels = json_encode(array_column($monthlyRevenue, 'month'));
$revenueData = json_encode(array_column($monthlyRevenue, 'revenue'));
$categoryLabels = json_encode(array_column($popularCategories, 'category'));
$categoryData = json_encode(array_column($popularCategories, 'total_sold'));

// Add chart initialization scripts
$pageScripts = <<<EOT
<script>
    // Revenue Chart
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    new Chart(revenueCtx, {
        type: 'line',
        data: {
            labels: $monthLabels,
            datasets: [{
                label: 'Revenus (MAD)',
                data: $revenueData,
                borderColor: '#3B82F6',
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
    
    // Categories Chart
    const categoriesCtx = document.getElementById('categoriesChart').getContext('2d');
    new Chart(categoriesCtx, {
        type: 'doughnut',
        data: {
            labels: $categoryLabels,
            datasets: [{
                data: $categoryData,
                backgroundColor: [
                    '#3B82F6',
                    '#10B981',
                    '#F59E0B',
                    '#EF4444',
                    '#8B5CF6'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
</script>
EOT;

// Include the layout
require_once(__DIR__ . '/includes/layout.php');
