<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/debug.log');

echo "=== DEBUT DEBUG ===<br>";

// Test 1: PHP fonctionne
echo "1. PHP fonctionne: OK<br>";

// Test 2: Inclusions
echo "2. Test des inclusions:<br>";
try {
    if (file_exists(__DIR__ . '/../includes/config.php')) {
        echo "- config.php existe<br>";
        require_once __DIR__ . '/../includes/config.php';
        echo "- config.php chargé<br>";
    } else {
        echo "- config.php MANQUANT<br>";
    }

    if (file_exists(__DIR__ . '/../includes/layout.php')) {
        echo "- layout.php existe<br>";
    } else {
        echo "- layout.php MANQUANT<br>";
    }

    if (file_exists(__DIR__ . '/../includes/auth.php')) {
        echo "- auth.php existe<br>";
    } else {
        echo "- auth.php MANQUANT<br>";
    }
} catch(Exception $e) {
    echo "- Erreur inclusion: " . $e->getMessage() . "<br>";
}

// Test 3: Base de données
echo "3. Test base de données:<br>";
try {
    if (isset($db)) {
        echo "- Connexion PDO: OK<br>";
        $test = $db->query("SELECT 1");
        echo "- Requête test: OK<br>";

        // Test des tables nécessaires
        $tables = ['teams', 'stadiums', 'matches'];
        foreach ($tables as $table) {
            try {
                $test = $db->query("SELECT 1 FROM $table LIMIT 1");
                echo "- Table $table: OK<br>";
            } catch(PDOException $e) {
                echo "- Table $table: ERREUR - " . $e->getMessage() . "<br>";
            }
        }
    } else {
        echo "- Variable DB manquante<br>";
    }
} catch(Exception $e) {
    echo "- Erreur DB: " . $e->getMessage() . "<br>";
}

// Test 4: Sessions
echo "4. Test sessions:<br>";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
echo "- Session démarrée: OK<br>";
echo "- Session ID: " . session_id() . "<br>";
echo "- Session data: <pre>" . print_r($_SESSION, true) . "</pre><br>";

// Test 5: Layout et templates
echo "5. Test layout et templates:<br>";
try {
    $layoutFiles = [
        '../includes/layout.php',
        '../includes/header.php',
        '../includes/footer.php',
        '../includes/sidebar.php'
    ];
    
    foreach ($layoutFiles as $file) {
        if (file_exists(__DIR__ . '/' . $file)) {
            echo "- " . basename($file) . " existe<br>";
            $content = file_get_contents(__DIR__ . '/' . $file);
            if ($content === false) {
                echo "- " . basename($file) . " non lisible<br>";
            } else {
                echo "- " . basename($file) . " lisible (" . strlen($content) . " bytes)<br>";
            }
        } else {
            echo "- " . basename($file) . " MANQUANT<br>";
        }
    }
} catch(Exception $e) {
    echo "- Erreur layout: " . $e->getMessage() . "<br>";
}

// Test 6: Permissions
echo "6. Test permissions:<br>";
$testDirs = [
    __DIR__,
    __DIR__ . '/../includes',
    __DIR__ . '/../css',
    __DIR__ . '/../js'
];

foreach ($testDirs as $dir) {
    echo "- " . basename($dir) . ": ";
    if (is_readable($dir)) {
        echo "Lecture OK";
        if (is_writable($dir)) {
            echo ", Écriture OK";
        }
        echo "<br>";
    } else {
        echo "NON LISIBLE<br>";
    }
}

// Test 7: Variables globales importantes
echo "7. Test variables globales:<br>";
echo "- Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "- Script Filename: " . $_SERVER['SCRIPT_FILENAME'] . "<br>";
echo "- PHP Version: " . phpversion() . "<br>";
echo "- Memory Limit: " . ini_get('memory_limit') . "<br>";
echo "- Max Execution Time: " . ini_get('max_execution_time') . "<br>";

echo "=== FIN DEBUG ===<br>"; 