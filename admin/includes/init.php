<?php
/**
 * Fichier d'initialisation principal de l'application
 * Charge les dépendances dans l'ordre correct
 */

// Affichage des erreurs en développement
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Chargement des constantes (doit être en premier)
require_once dirname(dirname(__DIR__)) . '/config/constants.php';

// Démarrage de la session si nécessaire
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuration de l'encodage
mb_internal_encoding('UTF-8');

// Configuration du fuseau horaire
date_default_timezone_set('Africa/Casablanca');

// Chargement des fonctions utilitaires
require_once INCLUDES_PATH . '/functions.php';

// Configuration de la base de données
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    error_log("Erreur de connexion à la base de données : " . $e->getMessage());
    die("Erreur de connexion à la base de données. Veuillez contacter l'administrateur.");
}

// Vérification de l'authentification si nécessaire
if (!isset($_SESSION['user_id']) && !in_array($_SERVER['SCRIPT_NAME'], ['/login.php', '/register.php'])) {
    header('Location: ' . ADMIN_URL . 'login.php');
    exit;
}

// Définition des constantes si non définies
defined('ROOT_PATH') || define('ROOT_PATH', realpath(dirname(__FILE__) . '/../../'));
defined('ADMIN_PATH') || define('ADMIN_PATH', ROOT_PATH . '/admin');
defined('INCLUDES_PATH') || define('INCLUDES_PATH', ADMIN_PATH . '/includes');
defined('UPLOADS_PATH') || define('UPLOADS_PATH', ROOT_PATH . '/uploads');

// Chargement des fichiers de configuration
require_once ROOT_PATH . '/config/database.php';

// Chargement des fonctions utilitaires
require_once INCLUDES_PATH . '/validation.php';
require_once INCLUDES_PATH . '/auth.php';

// Fonctions de base de données
if (!function_exists('getAllTeams')) {
    function getAllTeams() {
        global $pdo;
        try {
            $stmt = $pdo->query("SELECT id, name FROM teams ORDER BY name");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des équipes : " . $e->getMessage());
            return [];
        }
    }
}

if (!function_exists('getAllStadiums')) {
    function getAllStadiums() {
        global $pdo;
        try {
            $stmt = $pdo->query("SELECT id, name FROM stadiums ORDER BY name");
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des stades : " . $e->getMessage());
            return [];
        }
    }
}

if (!function_exists('createMatch')) {
    function createMatch($data) {
        global $pdo;
        try {
            $pdo->beginTransaction();

            $sql = "INSERT INTO matches (
                title, team1_id, team2_id, stadium_id, match_date, match_time,
                vip_price, vip_capacity, covered_price, covered_capacity,
                popular_price, popular_capacity, lawn_price, lawn_capacity,
                description, image, featured, tv_broadcast, competition,
                created_at
            ) VALUES (
                :title, :team1_id, :team2_id, :stadium_id, :match_date, :match_time,
                :vip_price, :vip_capacity, :covered_price, :covered_capacity,
                :popular_price, :popular_capacity, :lawn_price, :lawn_capacity,
                :description, :image, :featured, :tv_broadcast, :competition,
                NOW()
            )";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'title' => $data['title'],
                'team1_id' => $data['team1_id'],
                'team2_id' => $data['team2_id'],
                'stadium_id' => $data['stadium_id'],
                'match_date' => $data['match_date'],
                'match_time' => $data['match_time'],
                'vip_price' => $data['vip_price'],
                'vip_capacity' => $data['vip_capacity'],
                'covered_price' => $data['covered_price'],
                'covered_capacity' => $data['covered_capacity'],
                'popular_price' => $data['popular_price'],
                'popular_capacity' => $data['popular_capacity'],
                'lawn_price' => $data['lawn_price'],
                'lawn_capacity' => $data['lawn_capacity'],
                'description' => $data['description'],
                'image' => $data['image'] ?? null,
                'featured' => $data['featured'] ?? 0,
                'tv_broadcast' => $data['tv_broadcast'] ?? 0,
                'competition' => $data['competition']
            ]);

            $matchId = $pdo->lastInsertId();
            $pdo->commit();
            return $matchId;
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Erreur lors de la création du match : " . $e->getMessage());
            throw $e;
        }
    }
}

// Fonctions utilitaires pour l'administration
if (!function_exists('setFlashMessage')) {
    function setFlashMessage($type, $message) {
        $_SESSION['flash_message'] = [
            'type' => $type,
            'message' => $message
        ];
    }
}

if (!function_exists('getFlashMessage')) {
    function getFlashMessage() {
        if (isset($_SESSION['flash_message'])) {
            $message = $_SESSION['flash_message'];
            unset($_SESSION['flash_message']);
            return $message;
        }
        return null;
    }
}

if (!function_exists('generateCSRFToken')) {
    function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('validateCSRFToken')) {
    function validateCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}

if (!function_exists('isValidDate')) {
    function isValidDate($date) {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
}

if (!function_exists('isValidTime')) {
    function isValidTime($time) {
        $t = DateTime::createFromFormat('H:i', $time);
        return $t && $t->format('H:i') === $time;
    }
}

// Ne pas redéfinir uploadImage si elle existe déjà dans functions.php
if (!function_exists('uploadImage')) {
    function uploadImage($file, $targetDir) {
        // Vérification du dossier de destination
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        // Génération d'un nom de fichier unique
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $fileName = uniqid('match_') . '.' . $extension;
        $targetPath = $targetDir . $fileName;

        // Upload du fichier
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            return $fileName;
        }

        return false;
    }
} 