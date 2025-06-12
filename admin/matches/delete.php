<?php
require_once(__DIR__ . '/../includes/layout.php');

// Check permissions
$auth->requireRole(['super_admin', 'admin']);
$auth->requirePermission('manage_matches');

// Validate match ID
if (empty($_POST['match_id']) || !is_numeric($_POST['match_id'])) {
    $_SESSION['error'] = "ID de match invalide.";
    header('Location: index.php');
    exit;
}

$matchId = (int)$_POST['match_id'];

try {
    $db->beginTransaction();
    
    // Check if match exists and can be deleted
    $stmt = $db->prepare("
        SELECT m.*, 
               (SELECT COALESCE(SUM(oi.quantity), 0) FROM orders o JOIN order_items oi ON o.id = oi.order_id JOIN ticket_categories tc ON oi.ticket_category_id = tc.id WHERE tc.match_id = m.id) as tickets_sold
        FROM matches m 
        WHERE m.id = ?
    ");
    $stmt->execute([$matchId]);
    $match = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$match) {
        throw new Exception("Match introuvable.");
    }
    
    // Check if match has sold tickets
    if ($match['tickets_sold'] > 0) {
        throw new Exception("Impossible de supprimer ce match car des billets ont déjà été vendus.");
    }
    
    // Check if match is not completed
    if ($match['status'] === 'completed') {
        throw new Exception("Impossible de supprimer un match terminé.");
    }
    
    // Delete related records
    $db->prepare("DELETE FROM ticket_categories WHERE match_id = ?")->execute([$matchId]);
    $db->prepare("DELETE FROM matches WHERE id = ?")->execute([$matchId]);
    
    $db->commit();
    $_SESSION['success'] = "Le match a été supprimé avec succès.";
    
} catch (Exception $e) {
    $db->rollBack();
    $_SESSION['error'] = $e->getMessage();
}

header('Location: index.php');
exit;
?>
