// Gestion des interactions du formulaire d'ajout/édition de match
document.addEventListener('DOMContentLoaded', function() {
    // Initialisation des composants
    initializeFormValidation();
    initializeImagePreview();
    initializePriceInputs();
    initializeTeamSelects();
    setupDynamicFields();
});

// Validation du formulaire
function initializeFormValidation() {
    const form = document.querySelector('#matchForm');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        if (!validateForm()) {
            e.preventDefault();
            showError('Veuillez corriger les erreurs dans le formulaire.');
        }
    });
}

// Validation des champs du formulaire
function validateForm() {
    let isValid = true;
    const requiredFields = document.querySelectorAll('[required]');
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            isValid = false;
            field.classList.add('is-invalid');
        } else {
            field.classList.remove('is-invalid');
        }
    });

    // Validation des équipes
    const team1 = document.querySelector('#team1_id');
    const team2 = document.querySelector('#team2_id');
    if (team1.value === team2.value && team1.value !== '') {
        isValid = false;
        showError('Les deux équipes doivent être différentes');
    }

    // Validation des prix
    const prices = document.querySelectorAll('.price-input');
    prices.forEach(price => {
        if (price.value && !isValidPrice(price.value)) {
            isValid = false;
            price.classList.add('is-invalid');
        }
    });

    return isValid;
}

// Prévisualisation de l'image
function initializeImagePreview() {
    const imageInput = document.querySelector('#match_image');
    const previewContainer = document.querySelector('#imagePreview');
    
    if (!imageInput || !previewContainer) return;

    imageInput.addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                previewContainer.innerHTML = `
                    <img src="${e.target.result}" class="img-preview mb-2" alt="Aperçu">
                    <button type="button" class="btn btn-sm btn-danger" onclick="removeImage()">
                        Supprimer
                    </button>`;
            };
            reader.readAsDataURL(file);
        }
    });
}

// Gestion des prix et capacités
function initializePriceInputs() {
    const priceInputs = document.querySelectorAll('.price-input');
    priceInputs.forEach(input => {
        input.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9.,]/g, '');
            updateTotalCapacity();
        });
    });
}

// Mise à jour de la capacité totale
function updateTotalCapacity() {
    const capacityInputs = document.querySelectorAll('.capacity-input');
    let total = 0;
    
    capacityInputs.forEach(input => {
        const value = parseInt(input.value) || 0;
        total += value;
    });
    
    const totalCapacityElement = document.querySelector('#totalCapacity');
    if (totalCapacityElement) {
        totalCapacityElement.textContent = total;
    }
}

// Gestion des sélecteurs d'équipes
function initializeTeamSelects() {
    const team1Select = document.querySelector('#team1_id');
    const team2Select = document.querySelector('#team2_id');
    
    if (!team1Select || !team2Select) return;

    [team1Select, team2Select].forEach(select => {
        select.addEventListener('change', function() {
            validateTeamSelection();
            updateStadiumOptions(team1Select.value);
        });
    });
}

// Validation de la sélection des équipes
function validateTeamSelection() {
    const team1 = document.querySelector('#team1_id');
    const team2 = document.querySelector('#team2_id');
    
    if (team1.value && team2.value && team1.value === team2.value) {
        showError('Les deux équipes doivent être différentes');
        team2.value = '';
    }
}

// Mise à jour des options de stade en fonction de l'équipe à domicile
function updateStadiumOptions(teamId) {
    if (!teamId) return;
    
    fetch(`/admin/ajax/get_team_stadiums.php?team_id=${teamId}`)
        .then(response => response.json())
        .then(data => {
            const stadiumSelect = document.querySelector('#stadium_id');
            stadiumSelect.innerHTML = '<option value="">Sélectionner un stade</option>';
            
            data.forEach(stadium => {
                stadiumSelect.innerHTML += `
                    <option value="${stadium.id}">${stadium.name}</option>`;
            });
        })
        .catch(error => console.error('Erreur:', error));
}

// Configuration des champs dynamiques
function setupDynamicFields() {
    // Affichage/masquage du champ chaîne TV
    const tvBroadcastCheckbox = document.querySelector('#tv_broadcast');
    const tvChannelField = document.querySelector('#tv_channel_group');
    
    if (tvBroadcastCheckbox && tvChannelField) {
        tvBroadcastCheckbox.addEventListener('change', function() {
            tvChannelField.style.display = this.checked ? 'block' : 'none';
        });
    }

    // Gestion de l'ajout de nouvelle équipe
    const newTeamButtons = document.querySelectorAll('.add-new-team');
    newTeamButtons.forEach(button => {
        button.addEventListener('click', function() {
            const teamType = this.dataset.type;
            showNewTeamModal(teamType);
        });
    });
}

// Affichage des erreurs
function showError(message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-danger alert-dismissible fade show';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
    
    const form = document.querySelector('#matchForm');
    form.insertBefore(alertDiv, form.firstChild);
}

// Validation du format des prix
function isValidPrice(price) {
    return /^\d+([.,]\d{0,2})?$/.test(price);
}

// Suppression de l'image
function removeImage() {
    const imageInput = document.querySelector('#match_image');
    const previewContainer = document.querySelector('#imagePreview');
    
    imageInput.value = '';
    previewContainer.innerHTML = '';
}

// Modal d'ajout d'équipe
function showNewTeamModal(teamType) {
    const modal = new bootstrap.Modal(document.querySelector('#newTeamModal'));
    document.querySelector('#teamType').value = teamType;
    modal.show();
}

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