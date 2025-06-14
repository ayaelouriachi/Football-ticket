// Gestion des matchs
const MatchManager = {
    // Créer un nouveau match
    create: async function(formData) {
        try {
            const response = await fetch('/admin/api/matches', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            });
            
            if (!response.ok) {
                throw new Error('Erreur lors de la création du match');
            }
            
            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Erreur:', error);
            throw error;
        }
    },
    
    // Mettre à jour un match
    update: async function(id, formData) {
        try {
            const response = await fetch(`/admin/api/matches/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            });
            
            if (!response.ok) {
                throw new Error('Erreur lors de la mise à jour du match');
            }
            
            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Erreur:', error);
            throw error;
        }
    },
    
    // Supprimer un match
    delete: async function(id) {
        try {
            const response = await fetch(`/admin/api/matches/${id}`, {
                method: 'DELETE'
            });
            
            if (!response.ok) {
                throw new Error('Erreur lors de la suppression du match');
            }
            
            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Erreur:', error);
            throw error;
        }
    },
    
    // Mettre à jour le statut d'un match
    updateStatus: async function(id, status) {
        try {
            const response = await fetch(`/admin/api/matches/${id}/status`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ status })
            });
            
            if (!response.ok) {
                throw new Error('Erreur lors de la mise à jour du statut');
            }
            
            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Erreur:', error);
            throw error;
        }
    }
};

// Gestionnaire de formulaire pour l'ajout/modification de match
document.addEventListener('DOMContentLoaded', function() {
    const matchForm = document.getElementById('matchForm');
    if (matchForm) {
        matchForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            try {
                const formData = new FormData(matchForm);
                const data = Object.fromEntries(formData.entries());
                
                // Ajouter les catégories de billets si présentes
                const ticketCategories = [];
                document.querySelectorAll('.ticket-category').forEach(category => {
                    ticketCategories.push({
                        name: category.querySelector('[name="category_name[]"]').value,
                        price: parseFloat(category.querySelector('[name="category_price[]"]').value),
                        capacity: parseInt(category.querySelector('[name="category_capacity[]"]').value),
                        description: category.querySelector('[name="category_description[]"]').value
                    });
                });
                
                if (ticketCategories.length > 0) {
                    data.ticket_categories = ticketCategories;
                }
                
                // Créer ou mettre à jour selon le mode
                const matchId = matchForm.dataset.matchId;
                const result = matchId 
                    ? await MatchManager.update(matchId, data)
                    : await MatchManager.create(data);
                
                // Rediriger vers la liste des matchs avec un message de succès
                window.location.href = '/admin/matches.php?success=1';
                
            } catch (error) {
                console.error('Erreur:', error);
                alert('Une erreur est survenue. Veuillez réessayer.');
            }
        });
    }
    
    // Gestionnaire pour la suppression
    const deleteButtons = document.querySelectorAll('.delete-match');
    deleteButtons.forEach(button => {
        button.addEventListener('click', async function(e) {
            e.preventDefault();
            
            if (!confirm('Êtes-vous sûr de vouloir supprimer ce match ?')) {
                return;
            }
            
            const matchId = this.dataset.matchId;
            try {
                await MatchManager.delete(matchId);
                // Recharger la page ou supprimer la ligne
                window.location.reload();
            } catch (error) {
                console.error('Erreur:', error);
                alert('Une erreur est survenue lors de la suppression.');
            }
        });
    });
    
    // Gestionnaire pour le changement de statut
    const statusButtons = document.querySelectorAll('.change-status');
    statusButtons.forEach(button => {
        button.addEventListener('click', async function(e) {
            e.preventDefault();
            
            const matchId = this.dataset.matchId;
            const newStatus = this.dataset.status;
            
            try {
                await MatchManager.updateStatus(matchId, newStatus);
                // Recharger la page pour afficher le nouveau statut
                window.location.reload();
            } catch (error) {
                console.error('Erreur:', error);
                alert('Une erreur est survenue lors du changement de statut.');
            }
        });
    });
}); 