class FormValidator {
    constructor(form) {
        this.form = form;
        this.errors = {};
        this.init();
    }
    
    init() {
        this.form.addEventListener('submit', (e) => this.handleSubmit(e));
        
        // Validation en temps réel
        const inputs = this.form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('blur', () => this.validateField(input));
            input.addEventListener('input', () => this.clearFieldError(input));
        });
    }
    
    validateField(field) {
        const value = field.value.trim();
        const name = field.name;
        
        // Règles de validation
        switch (field.type) {
            case 'email':
                if (!this.isValidEmail(value)) {
                    this.setFieldError(field, 'Email invalide');
                    return false;
                }
                break;
                
            case 'password':
                if (value.length < 8) {
                    this.setFieldError(field, 'Le mot de passe doit contenir au moins 8 caractères');
                    return false;
                }
                break;
                
            case 'tel':
                if (!this.isValidPhone(value)) {
                    this.setFieldError(field, 'Numéro de téléphone invalide');
                    return false;
                }
                break;
        }
        
        // Champs requis
        if (field.hasAttribute('required') && !value) {
            this.setFieldError(field, 'Ce champ est requis');
            return false;
        }
        
        this.clearFieldError(field);
        return true;
    }
    
    setFieldError(field, message) {
        field.classList.add('error');
        const errorElement = field.parentNode.querySelector('.field-error') || 
                           this.createErrorElement();
        errorElement.textContent = message;
        field.parentNode.appendChild(errorElement);
    }
    
    clearFieldError(field) {
        field.classList.remove('error');
        const errorElement = field.parentNode.querySelector('.field-error');
        if (errorElement) {
            errorElement.remove();
        }
    }
}