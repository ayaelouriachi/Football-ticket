<?php
require_once(__DIR__ . '/config/session.php');
require_once(__DIR__ . '/config/constants.php');
require_once(__DIR__ . '/includes/auth_middleware.php');

// Initialize session
SessionManager::init();

// Check if user is authenticated
if (!isLoggedIn()) {
    // Store cart data in session if available
    if (isset($_GET['amount'])) {
        $_SESSION['pending_payment'] = [
            'amount' => $_GET['amount'],
            'description' => $_GET['description'] ?? 'Achat de billets',
            'timestamp' => time()
        ];
    }
    
    // Store the current URL to redirect back after login
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    
    // Set flash message
    setFlashMessage('warning', 'Veuillez vous connecter pour continuer avec le paiement.');
    
    // Redirect to login page
    header('Location: ' . BASE_URL . 'pages/login.php');
    exit;
}

// Check if we have pending payment data and no amount in URL
if (!isset($_GET['amount']) && isset($_SESSION['pending_payment'])) {
    // Check if pending payment is not expired (30 minutes)
    if (time() - $_SESSION['pending_payment']['timestamp'] < 1800) {
        // Reconstruct URL with pending payment data
        $redirectUrl = $_SERVER['PHP_SELF'] . '?amount=' . $_SESSION['pending_payment']['amount'];
        if (isset($_SESSION['pending_payment']['description'])) {
            $redirectUrl .= '&description=' . urlencode($_SESSION['pending_payment']['description']);
        }
        // Clear pending payment data
        unset($_SESSION['pending_payment']);
        // Redirect to payment page with parameters
        header('Location: ' . $redirectUrl);
        exit;
    } else {
        // Clear expired pending payment data
        unset($_SESSION['pending_payment']);
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paiement PayPal - TicketFoot</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="css/style.css" rel="stylesheet">
    <link href="css/payment-icons.css" rel="stylesheet">
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    
    <style>
        :root {
            --color-blue-400: #60A5FA;
            --color-blue-500: #3B82F6;
            --color-blue-600: #2563EB;
            --color-yellow-400: #FBBF24;
            --color-yellow-500: #F59E0B;
            --color-yellow-600: #D97706;
            --color-green-400: #34D399;
            --color-green-500: #10B981;
            --color-green-600: #059669;
            --color-red-400: #F87171;
            --color-red-500: #EF4444;
            --color-red-600: #DC2626;
            --color-gray-300: #D1D5DB;
            --color-gray-400: #9CA3AF;
            --color-gray-700: #374151;
        }

        body {
            margin: 0;
            padding: 20px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #1F2937;
            color: white;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .container {
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
        }

        .w-full {
            width: 100%;
        }

        .space-y-4 > * + * {
            margin-top: 1rem;
        }

        .loading-container {
            width: 100%;
            background-color: rgba(55, 65, 81, 0.5);
            border-radius: 0.5rem;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .loading-content {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .payment-info {
            background-color: rgba(37, 99, 235, 0.2);
            border: 1px solid rgba(59, 130, 246, 0.3);
            border-radius: 0.5rem;
            padding: 1rem;
        }

        .info-header {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.75rem;
            color: var(--color-blue-400);
            font-weight: 500;
        }

        .info-content {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            font-size: 0.875rem;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .info-label {
            color: var(--color-gray-300);
        }

        .info-value {
            color: white;
            font-weight: 600;
        }

        .status-message {
            padding: 0.75rem;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
        }

        .status-message.processing {
            background-color: rgba(217, 119, 6, 0.2);
            border: 1px solid rgba(245, 158, 11, 0.3);
            color: var(--color-yellow-400);
        }

        .status-message.success {
            background-color: rgba(5, 150, 105, 0.2);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: var(--color-green-400);
        }

        .status-message.error {
            background-color: rgba(220, 38, 38, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: var(--color-red-400);
        }

        .paypal-button-container {
            width: 100%;
        }

        .paypal-button-container.disabled {
            opacity: 0.5;
            pointer-events: none;
        }

        .security-info {
            text-align: center;
            font-size: 0.75rem;
            color: var(--color-gray-400);
        }

        .security-info p {
            margin: 0.25rem 0;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .animate-spin {
            animation: spin 1s linear infinite;
        }

        .hidden {
            display: none;
        }

        .payment-methods {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .payment-methods img {
            height: 24px;
            opacity: 0.7;
            transition: opacity 0.2s;
        }

        .payment-methods img:hover {
            opacity: 1;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="w-full space-y-4">
            <!-- Loading State -->
            <div id="loading-container" class="loading-container">
                <div class="loading-content">
                    <i data-lucide="loader-2" class="animate-spin"></i>
                    <span class="text-gray-300">Chargement de PayPal...</span>
                </div>
            </div>

            <!-- Payment Information -->
            <div id="payment-content" class="hidden">
                <div class="payment-info">
                    <div class="info-header">
                        <i data-lucide="credit-card"></i>
                        <span>Paiement sécurisé PayPal</span>
                    </div>

                    <div class="info-content">
                        <div class="info-row">
                            <span class="info-label">Montant (DH):</span>
                            <span class="info-value" id="amount-dh">500 DH</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Montant (EUR):</span>
                            <span class="info-value" id="amount-eur">50.00 EUR</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Description:</span>
                            <span class="info-value">Achat de billets</span>
                        </div>
                    </div>

                    <div class="payment-methods">
                        <img src="assets/images/visa.png" alt="Visa" class="payment-icon">
                        <img src="assets/images/mastercard.png" alt="Mastercard" class="payment-icon">
                        <img src="assets/images/paypal.png" alt="PayPal" class="payment-icon">
                    </div>
                </div>

                <!-- Payment Status Messages -->
                <div id="status-processing" class="status-message processing hidden">
                    <i data-lucide="loader-2" class="animate-spin"></i>
                    <span>Traitement du paiement en cours...</span>
                </div>

                <div id="status-success" class="status-message success hidden">
                    <i data-lucide="check-circle"></i>
                    <span>Paiement effectué avec succès!</span>
                </div>

                <div id="status-error" class="status-message error hidden">
                    <i data-lucide="alert-circle"></i>
                    <span id="error-message"></span>
                </div>

                <!-- PayPal Button Container -->
                <div id="paypal-button-container" class="paypal-button-container"></div>

                <!-- Security Information -->
                <div class="security-info">
                    <p>✓ Paiement sécurisé par PayPal</p>
                    <p>✓ Vos informations bancaires restent confidentielles</p>
                    <p>✓ Mode sandbox - Aucun vrai paiement ne sera effectué</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- PayPal Script -->
    <script src="https://www.paypal.com/sdk/js?client-id=<?php echo PAYPAL_CLIENT_ID; ?>&currency=<?php echo PAYPAL_CURRENCY; ?>"></script>

    <script>
        // Get URL parameters
        const urlParams = new URLSearchParams(window.location.search);
        
        // Configuration
        const config = {
            amount: parseFloat(urlParams.get('amount')) || 50, // Default to 50 if not provided
            currency: '<?php echo PAYPAL_CURRENCY; ?>',
            description: urlParams.get('description') || 'Achat de billets',
            isAuthenticated: <?php echo isLoggedIn() ? 'true' : 'false'; ?>,
            loginUrl: '<?php echo BASE_URL; ?>pages/login.php',
            userId: <?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'null'; ?>
        };

        // Validate amount and authentication
        if (!config.isAuthenticated) {
            window.location.href = config.loginUrl + '?redirect=' + encodeURIComponent(window.location.href);
        } else if (isNaN(config.amount) || config.amount <= 0) {
            window.location.href = 'cart.php?error=' + encodeURIComponent('Montant invalide');
        }

        // State management
        let state = {
            isScriptLoaded: false,
            isLoading: true,
            paymentStatus: 'idle', // 'idle' | 'processing' | 'success' | 'error'
            errorMessage: '',
            authCheckInterval: null
        };

        // DOM Elements
        const elements = {
            loadingContainer: document.getElementById('loading-container'),
            paymentContent: document.getElementById('payment-content'),
            statusProcessing: document.getElementById('status-processing'),
            statusSuccess: document.getElementById('status-success'),
            statusError: document.getElementById('status-error'),
            errorMessage: document.getElementById('error-message'),
            amountDH: document.getElementById('amount-dh'),
            amountEUR: document.getElementById('amount-eur'),
            paypalContainer: document.getElementById('paypal-button-container')
        };

        // Initialize Lucide icons
        lucide.createIcons();

        // Helper functions
        function updateUI() {
            // Update amounts
            elements.amountDH.textContent = `${(config.amount * 10).toFixed(2)} DH`;
            elements.amountEUR.textContent = `${config.amount.toFixed(2)} ${config.currency}`;

            // Update loading state
            elements.loadingContainer.classList.toggle('hidden', !state.isLoading);
            elements.paymentContent.classList.toggle('hidden', state.isLoading);

            // Update status messages
            elements.statusProcessing.classList.toggle('hidden', state.paymentStatus !== 'processing');
            elements.statusSuccess.classList.toggle('hidden', state.paymentStatus !== 'success');
            elements.statusError.classList.toggle('hidden', state.paymentStatus !== 'error');

            if (state.paymentStatus === 'error') {
                elements.errorMessage.textContent = state.errorMessage;
            }

            // Refresh Lucide icons
            lucide.createIcons();
        }

        function setState(newState) {
            state = { ...state, ...newState };
            updateUI();
        }

        // Initialize PayPal button
        function initializePayPalButton() {
            if (!window.paypal || !elements.paypalContainer || !config.isAuthenticated) {
                setState({ 
                    isLoading: false, 
                    paymentStatus: 'error',
                    errorMessage: !config.isAuthenticated ? 
                        'Veuillez vous connecter pour effectuer le paiement.' : 
                        'Erreur de chargement PayPal'
                });
                return;
            }

            // Clear container
            elements.paypalContainer.innerHTML = '';

            window.paypal.Buttons({
                style: {
                    layout: 'vertical',
                    color: 'gold',
                    shape: 'rect',
                    label: 'paypal',
                    height: 50
                },

                createOrder: (data, actions) => {
                    // Check authentication again before creating order
                    if (!config.isAuthenticated) {
                        window.location.href = config.loginUrl + '?redirect=' + encodeURIComponent(window.location.href);
                        return Promise.reject('Authentication required');
                    }

                    setState({ paymentStatus: 'processing' });

                    return actions.order.create({
                        purchase_units: [{
                            amount: {
                                value: config.amount.toFixed(2),
                                currency_code: config.currency
                            },
                            description: config.description
                        }],
                        application_context: {
                            brand_name: 'TicketFoot',
                            locale: 'fr-FR',
                            landing_page: 'NO_PREFERENCE',
                            user_action: 'PAY_NOW'
                        }
                    });
                },

                onApprove: async (data, actions) => {
                    try {
                        const order = await actions.order.capture();
                        console.log('Paiement approuvé:', order);

                        // Préparation des données pour la sauvegarde
                        const paymentData = {
                            order_id: order.id,
                            amount: order.purchase_units[0].amount.value,
                            currency: order.purchase_units[0].amount.currency_code,
                            status: order.status,
                            transaction_id: data.paymentID || order.id,
                            payment_method: 'paypal',
                            gateway_response: JSON.stringify(order)
                        };
                        console.log('Données préparées pour PHP:', paymentData);

                        // Envoi des données au serveur
                        try {
                            const response = await fetch('test_save_payment.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: new URLSearchParams(paymentData)
                            });
                            const result = await response.json();
                            console.log('Réponse du serveur PHP:', result);
                        } catch (error) {
                            console.error('Erreur lors de la sauvegarde:', error);
                        }

                        setState({
                            paymentStatus: 'success',
                            isLoading: false
                        });

                        // Redirect to success page after 2 seconds
                        setTimeout(() => {
                            window.location.href = 'cart.php?payment_status=success&order_id=' + order.id;
                        }, 2000);

                    } catch (error) {
                        console.error('Erreur lors de la capture:', error);
                        setState({
                            paymentStatus: 'error',
                            errorMessage: 'Erreur lors de la finalisation du paiement',
                            isLoading: false
                        });
                    }
                },

                onError: (err) => {
                    console.error('Erreur PayPal:', err);
                    setState({
                        paymentStatus: 'error',
                        errorMessage: 'Erreur lors du paiement PayPal',
                        isLoading: false
                    });
                },

                onCancel: (data) => {
                    console.log('Paiement annulé:', data);
                    setState({
                        paymentStatus: 'idle',
                        isLoading: false
                    });
                }
            }).render(elements.paypalContainer);
        }

        // Helper functions
        async function checkAuthStatus() {
            try {
                const response = await fetch('ajax/verify_auth.php', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                const data = await response.json();
                
                if (!data.isAuthenticated) {
                    // Clear auth check interval
                    if (state.authCheckInterval) {
                        clearInterval(state.authCheckInterval);
                    }
                    // Redirect to login
                    window.location.href = data.redirectUrl;
                }
                
                return data.isAuthenticated;
            } catch (error) {
                console.error('Error checking auth status:', error);
                return config.isAuthenticated; // Fall back to initial auth state
            }
        }

        // Start periodic auth check
        function startAuthCheck() {
            // Initial check
            checkAuthStatus();
            
            // Check every 30 seconds
            state.authCheckInterval = setInterval(checkAuthStatus, 30000);
        }

        // Clean up on page unload
        window.addEventListener('unload', () => {
            if (state.authCheckInterval) {
                clearInterval(state.authCheckInterval);
            }
        });

        // Initialize auth check when page loads
        document.addEventListener('DOMContentLoaded', startAuthCheck);

        // Initialize the application
        document.addEventListener('DOMContentLoaded', () => {
            updateUI();
            setState({ isLoading: false }); // Hide loading immediately
            initializePayPalButton(); // Initialize PayPal button directly
        });

        // Fonction pour envoyer les données de paiement au serveur
        function savePayment(orderData) {
            // Prépare les données pour correspondre à la structure de la base de données
            const paymentData = {
                order_id: orderData.id,  // Utilise l'ID de commande PayPal
                payment_id: orderData.id,  // Utilise le même ID comme payment_id
                amount: orderData.purchase_units[0].amount.value,
                currency: orderData.purchase_units[0].amount.currency_code,
                status: orderData.status,
                user_id: window.currentUserId || 1  // Utilise l'ID utilisateur courant ou une valeur par défaut
            };

            // Log pour le débogage
            console.log('Données de paiement à enregistrer:', paymentData);

            // Envoie les données au serveur
            return fetch('test_save_payment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams(paymentData)
            })
            .then(response => response.json())
            .then(data => {
                console.log('Réponse du serveur:', data);
                if (!data.success) {
                    throw new Error(data.error || 'Erreur lors de l\'enregistrement du paiement');
                }
                return data;
            });
        }
    </script>
</body>
</html>