class MatchFilter {
    constructor() {
        this.matches = [];
        this.filteredMatches = [];
        this.init();
    }
    
    init() {
        this.loadMatches();
        
        // Événements de filtre
        document.getElementById('search-input')?.addEventListener('input', 
            debounce((e) => this.filterBySearch(e.target.value), 300));
        
        document.getElementById('competition-filter')?.addEventListener('change', 
            (e) => this.filterByCompetition(e.target.value));
        
        document.getElementById('date-filter')?.addEventListener('change', 
            (e) => this.filterByDate(e.target.value));
    }
    
    async loadMatches() {
        try {
            const response = await fetch('ajax/get-matches.php');
            this.matches = await response.json();
            this.filteredMatches = [...this.matches];
            this.renderMatches();
        } catch (error) {
            console.error('Erreur lors du chargement des matchs:', error);
        }
    }
    
    filterBySearch(query) {
        if (!query.trim()) {
            this.filteredMatches = [...this.matches];
        } else {
            this.filteredMatches = this.matches.filter(match => 
                match.home_team.toLowerCase().includes(query.toLowerCase()) ||
                match.away_team.toLowerCase().includes(query.toLowerCase()) ||
                match.stadium.toLowerCase().includes(query.toLowerCase())
            );
        }
        this.renderMatches();
    }
    
    renderMatches() {
        const container = document.getElementById('matches-container');
        if (!container) return;
        
        if (this.filteredMatches.length === 0) {
            container.innerHTML = '<p class="no-results">Aucun match trouvé</p>';
            return;
        }
        
        container.innerHTML = this.filteredMatches.map(match => 
            this.generateMatchCard(match)
        ).join('');
    }
}

// Fonction utilitaire debounce
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}