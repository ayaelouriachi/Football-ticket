// Navigation menu functionality
document.addEventListener('DOMContentLoaded', function() {
    // Get the "Matchs" navigation link
    const matchsNavLink = document.querySelector('.nav-link[href*="matches.php"]');
    
    if (matchsNavLink) {
        matchsNavLink.addEventListener('click', function(e) {
            e.preventDefault();
            // Get the base URL from the link's href attribute
            const baseUrl = matchsNavLink.getAttribute('href');
            // Redirect to matches.php, which is the same as clicking "Voir tous les matchs"
            window.location.href = baseUrl;
        });
    }
}); 