<?php
require_once(__DIR__ . '/../includes/config.php');

try {
    // Vérifier si des équipes existent déjà
    $stmt = $pdo->query("SELECT COUNT(*) FROM teams");
    $count = $stmt->fetchColumn();
    
    if ($count === 0) {
        // Ajouter les équipes de test
        $teams = [
            'Real Madrid',
            'FC Barcelona',
            'Manchester United',
            'Liverpool',
            'Bayern Munich',
            'Paris Saint-Germain',
            'Juventus',
            'AC Milan'
        ];
        
        $stmt = $pdo->prepare("INSERT INTO teams (name) VALUES (?)");
        
        foreach ($teams as $team) {
            $stmt->execute([$team]);
        }
        
        echo "✅ Équipes de test ajoutées avec succès!\n";
    } else {
        echo "ℹ️ Des équipes existent déjà dans la base de données.\n";
    }
    
} catch (PDOException $e) {
    die("❌ Erreur: " . $e->getMessage() . "\n");
} 