</main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <div class="footer-brand">
                        <img src="<?php echo ASSETS_URL; ?>images/logo-white.png" alt="<?php echo APP_NAME; ?>" class="footer-logo">
                        <p class="footer-description">
                            La plateforme de référence pour réserver vos billets de football au Maroc. 
                            Vivez l'émotion du football marocain !
                        </p>
                        <div class="social-links">
                            <a href="#" class="social-link" title="Facebook">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="#" class="social-link" title="Twitter">
                                <i class="fab fa-twitter"></i>
                            </a>
                            <a href="#" class="social-link" title="Instagram">
                                <i class="fab fa-instagram"></i>
                            </a>
                            <a href="#" class="social-link" title="YouTube">
                                <i class="fab fa-youtube"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="footer-section">
                    <h3 class="footer-title">Navigation</h3>
                    <ul class="footer-links">
                        <li><a href="<?php echo BASE_URL; ?>">Accueil</a></li>
                        <li><a href="<?php echo BASE_URL; ?>pages/matches.php">Matchs</a></li>
                        <li><a href="<?php echo BASE_URL; ?>pages/about.php">À propos</a></li>
                        <li><a href="<?php echo BASE_URL; ?>pages/contact.php">Contact</a></li>
                    </ul>
                </div>

                <div class="footer-section">
                    <h3 class="footer-title">Compte</h3>
                    <ul class="footer-links">
                        <?php if (SessionManager::has('user_id')): ?>
                            <li><a href="<?php echo BASE_URL; ?>pages/profile.php">Mon profil</a></li>
                            <li><a href="<?php echo BASE_URL; ?>pages/orders.php">Mes commandes</a></li>
                            <li><a href="<?php echo BASE_URL; ?>pages/cart.php">Mon panier</a></li>
                        <?php else: ?>
                            <li><a href="<?php echo BASE_URL; ?>pages/login.php">Connexion</a></li>
                            <li><a href="<?php echo BASE_URL; ?>pages/register.php">Inscription</a></li>
                        <?php endif; ?>
                        <li><a href="<?php echo BASE_URL; ?>pages/help.php">Aide</a></li>
                    </ul>
                </div>

                <div class="footer-section">
                    <h3 class="footer-title">Contact</h3>
                    <div class="contact-info">
                        <div class="contact-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>Casablanca, Maroc</span>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-phone"></i>
                            <span>+212 5XX XX XX XX</span>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-envelope"></i>
                            <span>contact@footballtickets.ma</span>
                        </div>
                    </div>
                    
                    <div class="payment-methods">
                        <h4>Moyens de paiement</h4>
                        <div class="payment-icons">
                            <img src="<?php echo ASSETS_URL; ?>images/paypal.png" alt="PayPal" title="PayPal">
                            <img src="<?php echo ASSETS_URL; ?>images/visa.png" alt="Visa" title="Visa">
                            <img src="<?php echo ASSETS_URL; ?>images/mastercard.png" alt="Mastercard" title="Mastercard">
                        </div>
                    </div>
                </div>
            </div>

            <div class="footer-bottom">
                <div class="footer-bottom-content">
                    <p class="copyright">
                        &copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. Tous droits réservés.
                    </p>
                    <div class="footer-bottom-links">
                        <a href="<?php echo BASE_URL; ?>pages/privacy.php">Politique de confidentialité</a>
                        <a href="<?php echo BASE_URL; ?>pages/terms.php">Conditions d'utilisation</a>
                        <a href="<?php echo BASE_URL; ?>pages/refund.php">Politique de remboursement</a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Toast Notifications -->
    <div id="toast-container" class="toast-container"></div>

    <!-- Modals -->
    <div id="modal-overlay" class="modal-overlay">
        <div class="modal" id="confirm-modal">
            <div class="modal-header">
                <h3 class="modal-title">Confirmation</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p id="modal-message">Êtes-vous sûr de vouloir continuer ?</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal()">Annuler</button>
                <button class="btn btn-primary" id="modal-confirm">Confirmer</button>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="<?php echo ASSETS_URL; ?>js/app.js"></script>
    <script src="<?php echo ASSETS_URL; ?>js/cart.js"></script>
    
    <?php if (isset($additional_js)): ?>
        <?php foreach ($additional_js as $js_file): ?>
            <script src="<?php echo ASSETS_URL; ?>js/<?php echo $js_file; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Custom Page Scripts -->
    <?php if (isset($inline_js)): ?>
        <script><?php echo $inline_js; ?></script>
    <?php endif; ?>
</body>
</html>