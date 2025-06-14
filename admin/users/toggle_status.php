<?php
require_once '../includes/header.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message'] = "ID de l'utilisateur invalide.";
    header('Location: /admin/users');
    exit;
}

if (!isset($_GET['status']) || !in_array($_GET['status'], ['0', '1', 'true', 'false'])) {
    $_SESSION['error_message'] = "Statut invalide.";
    header('Location: /admin/users');
    exit;
}

$user_id = (int)$_GET['id'];
$new_status = in_array($_GET['status'], ['1', 'true']) ? 1 : 0;

try {
    // Vérifier si l'utilisateur existe
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        $_SESSION['error_message'] = "Utilisateur introuvable.";
        header('Location: /admin/users');
        exit;
    }

    // Mettre à jour le statut
    $stmt = $db->prepare("UPDATE users SET is_active = ? WHERE id = ?");
    $stmt->execute([$new_status, $user_id]);

    $_SESSION['success_message'] = "Le statut de l'utilisateur a été mis à jour avec succès.";

} catch (PDOException $e) {
    error_log($e->getMessage());
    $_SESSION['error_message'] = "Une erreur est survenue lors de la mise à jour du statut.";
}

header('Location: /admin/users');
exit; 