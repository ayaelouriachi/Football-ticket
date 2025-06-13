<?php
// Démarre la mise en tampon de sortie
ob_start();

// Désactive l'affichage des erreurs PHP dans la sortie
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Force le type de contenu en JSON
header('Content-Type: application/json');

// Fonction pour logger les erreurs
function logError($message, $context = []) {
    $logFile = __DIR__ . '/logs/payment_test.log';
    $logDir = dirname($logFile);
    
    // Crée le dossier de logs s'il n'existe pas
    if (!file_exists($logDir)) {
        mkdir($logDir, 0777, true);
    }
    
    error_log(sprintf(
        "[%s][PaymentTest] %s - Context: %s\n",
        date('Y-m-d H:i:s'),
        $message,
        json_encode($context, JSON_UNESCAPED_UNICODE)
    ), 3, $logFile);
}

// Fonction pour mapper les données PayPal vers notre format de base de données
function mapPayPalDataToDatabase($paypalData) {
    // Mapping du statut PayPal vers notre format
    $statusMapping = [
        'COMPLETED' => 'completed',
        'PENDING' => 'pending',
        'FAILED' => 'failed',
        'DENIED' => 'failed',
        'EXPIRED' => 'failed',
        'VOIDED' => 'failed'
    ];

    // Utilise l'order_id de PayPal
    $orderId = $paypalData['order_id'];
    
    if (empty($orderId)) {
        throw new Exception('ID de commande manquant');
    }

    // Utilise payment_id comme demandé par la structure de la base de données
    $paymentId = $paypalData['payment_id'] ?? $orderId;

    // Normalise le statut
    $status = strtoupper($paypalData['status']);
    $status = $statusMapping[$status] ?? 'pending';

    // Utilise l'ID utilisateur s'il est fourni, sinon utilise une valeur par défaut
    $userId = isset($paypalData['user_id']) ? intval($paypalData['user_id']) : 1;

    return [
        'order_id' => $orderId,
        'payment_id' => $paymentId,
        'amount' => floatval($paypalData['amount']),
        'currency' => strtoupper(trim($paypalData['currency'])),
        'status' => $status,
        'user_id' => $userId
    ];
}

// Fonction pour envoyer une réponse JSON
function sendJsonResponse($success, $message, $data = null, $error = null) {
    // Nettoie tout tampon de sortie existant
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    $response = [
        'success' => $success,
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ];

    if ($data !== null) {
        $response['data'] = $data;
    }

    if ($error !== null) {
        $response['error'] = $error;
    }

    // Ajoute des informations de débogage
    $response['debug'] = [
        'request_method' => $_SERVER['REQUEST_METHOD'],
        'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'non défini',
        'post_data' => $_POST,
        'raw_input' => file_get_contents('php://input')
    ];

    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // Vérifie que la requête est en POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Méthode non autorisée. Utilisez POST.');
    }

    // Log des données reçues
    logError('Données POST reçues', $_POST);

    // Validation des données requises
    $requiredFields = ['order_id', 'amount', 'currency', 'status'];
    $missingFields = array_filter($requiredFields, function($field) {
        return !isset($_POST[$field]) || empty($_POST[$field]);
    });

    if (!empty($missingFields)) {
        throw new Exception('Champs requis manquants: ' . implode(', ', $missingFields));
    }

    // Connexion à la base de données
    require_once(__DIR__ . '/config/database.php');
    
    try {
        // Utilise la classe Database au lieu d'une connexion directe
        $db = Database::getInstance()->getConnection();
        
        if (!$db) {
            throw new Exception('Impossible d\'obtenir la connexion à la base de données');
        }
    } catch (Exception $e) {
        logError('Erreur de connexion BDD', ['error' => $e->getMessage()]);
        throw new Exception('Erreur de connexion à la base de données: ' . $e->getMessage());
    }

    // Transforme les données PayPal en format base de données
    $data = mapPayPalDataToDatabase($_POST);

    // Validation des données
    if ($data['amount'] <= 0) {
        throw new Exception('Le montant doit être supérieur à 0');
    }

    // Requête d'insertion adaptée à la structure existante
    $sql = "INSERT INTO payments (
                order_id,
                payment_id,
                amount,
                currency,
                status,
                user_id,
                created_at
            ) VALUES (
                :order_id,
                :payment_id,
                :amount,
                :currency,
                :status,
                :user_id,
                NOW()
            )";

    $stmt = $db->prepare($sql);
    
    // Exécution de la requête
    if (!$stmt->execute($data)) {
        throw new Exception('Erreur lors de l\'insertion en base de données');
    }

    // Log du succès
    logError('Paiement enregistré avec succès', [
        'payment_id' => $data['payment_id'],
        'order_id' => $data['order_id']
    ]);

    // Réponse de succès
    sendJsonResponse(
        true,
        'Paiement enregistré avec succès',
        [
            'order_id' => $data['order_id'],
            'payment_id' => $data['payment_id'],
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'status' => $data['status']
        ]
    );

} catch (Exception $e) {
    logError('Erreur lors du traitement', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);

    sendJsonResponse(
        false,
        'Erreur lors du traitement du paiement',
        null,
        $e->getMessage()
    );
} 