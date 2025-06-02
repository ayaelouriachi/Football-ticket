<?php

class Validator {
    private $errors = [];
    private $data = [];
    
    public function __construct($data = []) {
        $this->data = $data;
    }
    
    // Valider un champ requis
    public function required($field, $message = null) {
        $value = $this->data[$field] ?? '';
        
        if (empty(trim($value))) {
            $this->errors[$field][] = $message ?? "Le champ {$field} est requis";
        }
        
        return $this;
    }
    
    // Valider un email
    public function email($field, $message = null) {
        $value = $this->data[$field] ?? '';
        
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field][] = $message ?? "Le format de l'email est invalide";
        }
        
        return $this;
    }
    
    // Valider la longueur minimale
    public function minLength($field, $min, $message = null) {
        $value = $this->data[$field] ?? '';
        
        if (!empty($value) && strlen($value) < $min) {
            $this->errors[$field][] = $message ?? "Le champ {$field} doit contenir au moins {$min} caractères";
        }
        
        return $this;
    }
    
    // Valider la longueur maximale
    public function maxLength($field, $max, $message = null) {
        $value = $this->data[$field] ?? '';
        
        if (strlen($value) > $max) {
            $this->errors[$field][] = $message ?? "Le champ {$field} ne peut pas dépasser {$max} caractères";
        }
        
        return $this;
    }
    
    // Valider un nombre entier
    public function integer($field, $message = null) {
        $value = $this->data[$field] ?? '';
        
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_INT)) {
            $this->errors[$field][] = $message ?? "Le champ {$field} doit être un nombre entier";
        }
        
        return $this;
    }
    
    // Valider un nombre décimal
    public function numeric($field, $message = null) {
        $value = $this->data[$field] ?? '';
        
        if (!empty($value) && !is_numeric($value)) {
            $this->errors[$field][] = $message ?? "Le champ {$field} doit être un nombre";
        }
        
        return $this;
    }
    
    // Valider une date
    public function date($field, $format = 'Y-m-d', $message = null) {
        $value = $this->data[$field] ?? '';
        
        if (!empty($value)) {
            $dateObj = DateTime::createFromFormat($format, $value);
            if (!$dateObj || $dateObj->format($format) !== $value) {
                $this->errors[$field][] = $message ?? "Le format de date est invalide pour {$field}";
            }
        }
        
        return $this;
    }
    
    // Valider qu'une date est dans le futur
    public function futureDate($field, $message = null) {
        $value = $this->data[$field] ?? '';
        
        if (!empty($value)) {
            $dateObj = new DateTime($value);
            $now = new DateTime();
            
            if ($dateObj <= $now) {
                $this->errors[$field][] = $message ?? "La date {$field} doit être dans le futur";
            }
        }
        
        return $this;
    }
    
    // Valider un numéro de téléphone
    public function phone($field, $message = null) {
        $value = $this->data[$field] ?? '';
        
        if (!empty($value) && !SecurityHelper::validateMoroccanPhone($value)) {
            $this->errors[$field][] = $message ?? "Le format du numéro de téléphone est invalide";
        }
        
        return $this;
    }
    
    // Valider une URL
    public function url($field, $message = null) {
        $value = $this->data[$field] ?? '';
        
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
            $this->errors[$field][] = $message ?? "L'URL fournie est invalide";
        }
        
        return $this;
    }
    
    // Valider une confirmation de mot de passe
    public function confirmed($field, $confirmField, $message = null) {
        $value = $this->data[$field] ?? '';
        $confirmValue = $this->data[$confirmField] ?? '';
        
        if ($value !== $confirmValue) {
            $this->errors[$field][] = $message ?? "La confirmation ne correspond pas";
        }
        
        return $this;
    }
    
    // Valider contre une liste de valeurs
    public function in($field, $allowedValues, $message = null) {
        $value = $this->data[$field] ?? '';
        
        if (!empty($value) && !in_array($value, $allowedValues)) {
            $this->errors[$field][] = $message ?? "La valeur sélectionnée pour {$field} est invalide";
        }
        
        return $this;
    }
    
    // Validation personnalisée avec callback
    public function custom($field, $callback, $message = null) {
        $value = $this->data[$field] ?? '';
        
        if (!call_user_func($callback, $value)) {
            $this->errors[$field][] = $message ?? "La validation personnalisée a échoué pour {$field}";
        }
        
        return $this;
    }
    
    // Vérifier si la validation a réussi
    public function passes() {
        return empty($this->errors);
    }
    
    // Vérifier si la validation a échoué
    public function fails() {
        return !$this->passes();
    }
    
    // Obtenir toutes les erreurs
    public function getErrors() {
        return $this->errors;
    }
    
    // Obtenir les erreurs pour un champ spécifique
    public function getError($field) {
        return $this->errors[$field] ?? [];
    }
    
    // Obtenir la première erreur pour un champ
    public function getFirstError($field) {
        $errors = $this->getError($field);
        return $errors[0] ?? '';
    }
    
    // Ajouter une erreur manuellement
    public function addError($field, $message) {
        $this->errors[$field][] = $message;
        return $this;
    }
}
?>