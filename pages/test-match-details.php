<?php
// Chemins de debug
$base_dir = __DIR__ . '/../';
echo "Base directory: " . $base_dir . "<br>";

try {
    require_once $base_dir . 'config/database.php';
    require_once $base_dir . 'classes/Match.php';
    require_once $base_dir . 'classes/TicketCategory.php';
    require_once $base_dir . 'config/session.php';
    require_once $base_dir . 'config/init.php';
    echo "✅ Tous les fichiers requis ont été chargés<br>";
} catch (Exception $e) {
    echo "❌ Erreur de chargement des fichiers: " . $e->getMessage() . "<br>";
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    echo "✅ Connexion BDD OK<br>";
} catch (Exception $e) {
    echo "❌ Erreur BDD: " . $e->getMessage() . "<br>";
    exit;
}

echo "GET parameters: ";
var_dump($_GET);

if (!isset($_GET['id'])) {
    echo "❌ Paramètre ID manquant";
    exit;
}

if (!is_numeric($_GET['id'])) {
    echo "❌ ID non numérique: " . $_GET['id'];
    exit;
}

echo "✅ ID valide: " . $_GET['id'] . "<br>";

$matchObj = new FootballMatch();
$match = $matchObj->getMatchById($_GET['id']);

echo "Match data: ";
var_dump($match);

if (!$match) {
    echo "❌ Match non trouvé";
    exit;
}

echo "✅ Match trouvé<br>";

// Test de la méthode getTicketCategories
if (method_exists($matchObj, 'getTicketCategories')) {
    echo "✅ Méthode getTicketCategories existe<br>";
    $categories = $matchObj->getTicketCategories($_GET['id']);
    echo "Categories: ";
    var_dump($categories);
} else {
    echo "❌ Méthode getTicketCategories n'existe pas dans la classe FootballMatch<br>";
}
?> 