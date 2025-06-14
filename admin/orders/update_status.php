<?php
require_once '../includes/header.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message'] = "ID de la commande invalide.";
    header('Location: /admin/orders');
    exit;
}

if (!isset($_GET['status']) || !in_array($_GET['status'], ['pending', 'completed', 'cancelled', 'refunded'])) {
    $_SESSION['error_message'] = "Statut invalide.";
    header('Location: /admin/orders');
    exit;
}

$order_id = (int)$_GET['id'];
$new_status = $_GET['status'];

try {
    // Vérifier si la commande existe
    $stmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();

    if (!$order) {
        $_SESSION['error_message'] = "Commande introuvable.";
        header('Location: /admin/orders');
        exit;
    }

    // Vérifier les transitions de statut valides
    $valid_transitions = [
        'pending' => ['completed', 'cancelled'],
        'completed' => ['refunded'],
        'cancelled' => [],
        'refunded' => []
    ];

    if (!in_array($new_status, $valid_transitions[$order['status']])) {
        $_SESSION['error_message'] = "Transition de statut invalide.";
        header('Location: /admin/orders');
        exit;
    }

    // Début de la transaction
    $db->beginTransaction();

    // Mettre à jour le statut de la commande
    $stmt = $db->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $order_id]);

    // Mettre à jour le statut des tickets
    $ticket_status = [
        'completed' => 'paid',
        'cancelled' => 'cancelled',
        'refunded' => 'refunded'
    ][$new_status] ?? 'pending';

    $stmt = $db->prepare("UPDATE tickets SET status = ? WHERE order_id = ?");
    $stmt->execute([$ticket_status, $order_id]);

    // Valider la transaction
    $db->commit();

    $_SESSION['success_message'] = "Le statut de la commande a été mis à jour avec succès.";

} catch (PDOException $e) {
    // Annuler la transaction en cas d'erreur
    $db->rollBack();
    error_log($e->getMessage());
    $_SESSION['error_message'] = "Une erreur est survenue lors de la mise à jour du statut.";
}

header('Location: /admin/orders');
exit; 