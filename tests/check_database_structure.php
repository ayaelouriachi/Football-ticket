<?php
// Configuration des logs
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/database_check.log');

echo "=== Vérification de la structure de la base de données ===\n\n";

try {
    // Connexion à la base de données
    $conn = new PDO('mysql:host=localhost;dbname=football_tickets', 'root', '');
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✓ Connexion à la base de données réussie\n\n";
    
    // Liste des tables requises et leurs colonnes
    $requiredTables = [
        'orders' => [
            'id',
            'user_id',
            'total_amount',
            'status',
            'created_at',
            'updated_at',
            'payment_transaction_id'
        ],
        'order_items' => [
            'id',
            'order_id',
            'ticket_category_id',
            'quantity',
            'price_per_ticket'
        ],
        'users' => [
            'id',
            'name',
            'email'
        ],
        'matches' => [
            'id',
            'title',
            'match_date',
            'competition',
            'team1_id',
            'team2_id',
            'stadium_id'
        ],
        'teams' => [
            'id',
            'name'
        ],
        'stadiums' => [
            'id',
            'name',
            'city',
            'address'
        ],
        'ticket_categories' => [
            'id',
            'match_id',
            'name',
            'description',
            'price',
            'available_tickets'
        ],
        'payments' => [
            'id',
            'order_id',
            'transaction_id',
            'amount',
            'status',
            'payment_method',
            'created_at'
        ]
    ];
    
    // Vérifier chaque table
    foreach ($requiredTables as $table => $columns) {
        echo "Vérification de la table '$table':\n";
        
        // Vérifier si la table existe
        $stmt = $conn->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() == 0) {
            echo "❌ Table '$table' manquante\n";
            continue;
        }
        
        // Vérifier les colonnes
        $stmt = $conn->query("SHOW COLUMNS FROM $table");
        $existingColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $missingColumns = array_diff($columns, $existingColumns);
        
        if (empty($missingColumns)) {
            echo "✓ Structure correcte\n";
        } else {
            echo "❌ Colonnes manquantes : " . implode(', ', $missingColumns) . "\n";
        }
        
        // Vérifier les données
        $count = $conn->query("SELECT COUNT(*) FROM $table")->fetchColumn();
        echo "  - Nombre d'enregistrements : $count\n";
        
        echo "\n";
    }
    
    // Vérifier les relations
    echo "\nVérification des relations :\n";
    
    $relations = [
        'orders' => [
            'users' => 'user_id'
        ],
        'order_items' => [
            'orders' => 'order_id',
            'ticket_categories' => 'ticket_category_id'
        ],
        'matches' => [
            'teams' => ['team1_id', 'team2_id'],
            'stadiums' => 'stadium_id'
        ],
        'ticket_categories' => [
            'matches' => 'match_id'
        ],
        'payments' => [
            'orders' => 'order_id'
        ]
    ];
    
    foreach ($relations as $table => $refs) {
        foreach ($refs as $refTable => $columns) {
            if (is_array($columns)) {
                foreach ($columns as $column) {
                    checkRelation($conn, $table, $refTable, $column);
                }
            } else {
                checkRelation($conn, $table, $refTable, $columns);
            }
        }
    }
    
} catch (PDOException $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
}

function checkRelation($conn, $table, $refTable, $column) {
    echo "Vérification relation $table.$column -> $refTable.id:\n";
    
    try {
        // Vérifier les enregistrements orphelins
        $sql = "SELECT COUNT(*) FROM $table t 
                LEFT JOIN $refTable r ON t.$column = r.id 
                WHERE r.id IS NULL AND t.$column IS NOT NULL";
        $orphans = $conn->query($sql)->fetchColumn();
        
        if ($orphans > 0) {
            echo "❌ $orphans enregistrements orphelins trouvés\n";
        } else {
            echo "✓ Relation valide\n";
        }
    } catch (PDOException $e) {
        echo "❌ Erreur de vérification : " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

echo "\n=== Fin de la vérification ===\n";
?> 