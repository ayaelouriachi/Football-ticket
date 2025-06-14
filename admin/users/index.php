<?php
require_once '../includes/header.php';

// Récupération des utilisateurs
try {
    $stmt = $db->query("
        SELECT u.*,
               COUNT(DISTINCT o.id) as total_orders,
               SUM(CASE WHEN o.status = 'completed' THEN o.total_amount ELSE 0 END) as total_spent
        FROM users u
        LEFT JOIN orders o ON u.id = o.user_id
        GROUP BY u.id
        ORDER BY u.created_at DESC
    ");
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log($e->getMessage());
    $_SESSION['error_message'] = "Une erreur est survenue lors de la récupération des utilisateurs.";
    $users = [];
}
?>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Gestion des utilisateurs</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/admin">Dashboard</a></li>
                    <li class="breadcrumb-item active">Utilisateurs</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="usersTable">
                    <thead>
                        <tr>
                            <th>Utilisateur</th>
                            <th>Email</th>
                            <th>Commandes</th>
                            <th>Total dépensé</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-initials me-2">
                                        <?php echo strtoupper(substr($user['firstname'], 0, 1) . substr($user['lastname'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold">
                                            <?php echo escape($user['firstname'] . ' ' . $user['lastname']); ?>
                                        </div>
                                        <small class="text-muted">
                                            Inscrit le <?php echo formatDate($user['created_at']); ?>
                                        </small>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo escape($user['email']); ?></td>
                            <td>
                                <?php if ($user['total_orders'] > 0): ?>
                                    <a href="/admin/orders?user_id=<?php echo $user['id']; ?>" class="text-decoration-none">
                                        <?php echo $user['total_orders']; ?> commande(s)
                                    </a>
                                <?php else: ?>
                                    Aucune commande
                                <?php endif; ?>
                            </td>
                            <td><?php echo formatPrice($user['total_spent'] ?? 0); ?></td>
                            <td>
                                <?php
                                $statusClass = $user['is_active'] ? 'success' : 'danger';
                                $statusText = $user['is_active'] ? 'Actif' : 'Inactif';
                                ?>
                                <span class="badge bg-<?php echo $statusClass; ?>-subtle text-<?php echo $statusClass; ?>">
                                    <?php echo $statusText; ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="/admin/users/edit.php?id=<?php echo $user['id']; ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-<?php echo $user['is_active'] ? 'warning' : 'success'; ?>"
                                            onclick="toggleUserStatus(<?php echo $user['id']; ?>, <?php echo $user['is_active'] ? 'false' : 'true'; ?>)">
                                        <i class="bi bi-<?php echo $user['is_active'] ? 'pause' : 'play'; ?>"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialisation de DataTables
    $('#usersTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/fr-FR.json'
        },
        order: [[0, 'desc']],
        pageLength: 10,
        responsive: true
    });
});

function toggleUserStatus(userId, newStatus) {
    const action = newStatus ? 'activer' : 'désactiver';
    
    Swal.fire({
        title: `Êtes-vous sûr de vouloir ${action} cet utilisateur ?`,
        text: `L'utilisateur sera ${action} immédiatement.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: newStatus ? '#198754' : '#ffc107',
        cancelButtonColor: '#6c757d',
        confirmButtonText: `Oui, ${action}`,
        cancelButtonText: 'Annuler'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `/admin/users/toggle_status.php?id=${userId}&status=${newStatus}`;
        }
    });
}
</script>

<?php require_once '../includes/footer.php'; ?> 