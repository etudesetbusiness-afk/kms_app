/**
 * modern-forms.js
 * Framework JavaScript pour formulaires d'édition KMS Gestion
 * Appliqué sur toutes les pages edit.php
 */

(function() {
    'use strict';

    // ========================================
    // 1. INITIALISATION AU CHARGEMENT
    // ========================================
    
    document.addEventListener('DOMContentLoaded', function() {
        initFormAnimations();
        initFormValidation();
        initCharacterCounters();
        initAutoSave();
        initKeyboardShortcuts();
        initConfirmations();
        initDynamicFields();
        initFileUploads();
        initAutoDismissAlerts();
    });

    // ========================================
    // 2. ANIMATIONS D'ENTRÉE
    // ========================================
    
    function initFormAnimations() {
        // Animation en cascade des cartes de formulaire
        const formCards = document.querySelectorAll('.form-card');
        formCards.forEach((card, index) => {
            card.style.animationDelay = `${index * 0.1}s`;
        });

        // Animation du header
        const header = document.querySelector('.form-page-header');
        if (header) {
            header.style.opacity = '0';
            setTimeout(() => {
                header.style.transition = 'opacity 0.5s ease-out';
                header.style.opacity = '1';
            }, 100);
        }

        // Focus automatique sur le premier champ
        const firstInput = document.querySelector('.form-control:not([readonly]):not([disabled])');
        if (firstInput) {
            setTimeout(() => firstInput.focus(), 300);
        }
    }

    // ========================================
    // 3. VALIDATION EN TEMPS RÉEL
    // ========================================
    
    function initFormValidation() {
        const form = document.querySelector('form');
        if (!form) return;

        // Validation des champs requis
        const requiredFields = form.querySelectorAll('[required]');
        requiredFields.forEach(field => {
            field.addEventListener('blur', function() {
                validateField(this);
            });

            field.addEventListener('input', function() {
                if (this.classList.contains('is-invalid')) {
                    validateField(this);
                }
            });
        });

        // Validation avant soumission
        form.addEventListener('submit', function(e) {
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!validateField(field)) {
                    isValid = false;
                }
            });

            if (!isValid) {
                e.preventDefault();
                showNotification('Veuillez corriger les erreurs dans le formulaire', 'error');
                
                // Scroll vers la première erreur
                const firstError = form.querySelector('.is-invalid');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    firstError.focus();
                }
            } else {
                // Animation du bouton de sauvegarde
                const saveBtn = form.querySelector('.btn-save');
                if (saveBtn) {
                    saveBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Enregistrement...';
                    saveBtn.disabled = true;
                }
            }
        });
    }

    function validateField(field) {
        const value = field.value.trim();
        let isValid = true;
        let errorMessage = '';

        // Vérification requis
        if (field.hasAttribute('required') && value === '') {
            isValid = false;
            errorMessage = 'Ce champ est obligatoire';
        }

        // Vérification email
        if (field.type === 'email' && value !== '') {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                isValid = false;
                errorMessage = 'Adresse email invalide';
            }
        }

        // Vérification téléphone
        if (field.type === 'tel' && value !== '') {
            const phoneRegex = /^[0-9\s\-\+\(\)]+$/;
            if (!phoneRegex.test(value)) {
                isValid = false;
                errorMessage = 'Numéro de téléphone invalide';
            }
        }

        // Vérification nombre
        if (field.type === 'number' && value !== '') {
            const min = field.getAttribute('min');
            const max = field.getAttribute('max');
            const numValue = parseFloat(value);

            if (min !== null && numValue < parseFloat(min)) {
                isValid = false;
                errorMessage = `La valeur doit être supérieure ou égale à ${min}`;
            }
            if (max !== null && numValue > parseFloat(max)) {
                isValid = false;
                errorMessage = `La valeur doit être inférieure ou égale à ${max}`;
            }
        }

        // Application des classes
        if (isValid) {
            field.classList.remove('is-invalid');
            field.classList.add('is-valid');
            removeErrorMessage(field);
        } else {
            field.classList.remove('is-valid');
            field.classList.add('is-invalid');
            showErrorMessage(field, errorMessage);
        }

        return isValid;
    }

    function showErrorMessage(field, message) {
        removeErrorMessage(field);
        const errorDiv = document.createElement('div');
        errorDiv.className = 'invalid-feedback';
        errorDiv.textContent = message;
        field.parentNode.appendChild(errorDiv);
    }

    function removeErrorMessage(field) {
        const existingError = field.parentNode.querySelector('.invalid-feedback');
        if (existingError) {
            existingError.remove();
        }
    }

    // ========================================
    // 4. COMPTEURS DE CARACTÈRES
    // ========================================
    
    function initCharacterCounters() {
        const textareas = document.querySelectorAll('textarea[maxlength]');
        textareas.forEach(textarea => {
            const maxLength = textarea.getAttribute('maxlength');
            const counter = document.createElement('div');
            counter.className = 'character-count';
            counter.textContent = `0 / ${maxLength}`;
            textarea.parentNode.appendChild(counter);

            textarea.addEventListener('input', function() {
                const currentLength = this.value.length;
                counter.textContent = `${currentLength} / ${maxLength}`;
                
                if (currentLength > maxLength * 0.9) {
                    counter.style.color = '#dc3545';
                } else if (currentLength > maxLength * 0.7) {
                    counter.style.color = '#ffc107';
                } else {
                    counter.style.color = '#6c757d';
                }
            });
        });
    }

    // ========================================
    // 5. AUTO-SAUVEGARDE (BROUILLON LOCAL)
    // ========================================
    
    function initAutoSave() {
        const form = document.querySelector('form');
        if (!form || !form.id) return;

        const formId = form.id;
        
        // Restauration des données sauvegardées
        const savedData = localStorage.getItem(`form_draft_${formId}`);
        if (savedData) {
            try {
                const data = JSON.parse(savedData);
                Object.keys(data).forEach(name => {
                    const field = form.querySelector(`[name="${name}"]`);
                    if (field && !field.value) {
                        field.value = data[name];
                        // Afficher un indicateur
                        showDraftIndicator(field);
                    }
                });
            } catch (e) {
                console.error('Erreur lors de la restauration du brouillon:', e);
            }
        }

        // Sauvegarde automatique toutes les 30 secondes
        let autoSaveTimer;
        const inputs = form.querySelectorAll('input, textarea, select');
        
        inputs.forEach(input => {
            input.addEventListener('input', function() {
                clearTimeout(autoSaveTimer);
                autoSaveTimer = setTimeout(() => {
                    saveFormDraft(form, formId);
                }, 30000); // 30 secondes
            });
        });

        // Nettoyage après soumission réussie
        form.addEventListener('submit', function() {
            localStorage.removeItem(`form_draft_${formId}`);
        });
    }

    function saveFormDraft(form, formId) {
        const formData = {};
        const inputs = form.querySelectorAll('input, textarea, select');
        
        inputs.forEach(input => {
            if (input.name && input.value) {
                formData[input.name] = input.value;
            }
        });

        localStorage.setItem(`form_draft_${formId}`, JSON.stringify(formData));
        showNotification('Brouillon sauvegardé automatiquement', 'info', 2000);
    }

    function showDraftIndicator(field) {
        const indicator = document.createElement('span');
        indicator.className = 'badge bg-info ms-2';
        indicator.innerHTML = '<i class="bi bi-clock-history"></i> Restauré';
        indicator.style.fontSize = '0.75rem';
        
        const label = field.closest('.form-group')?.querySelector('label');
        if (label) {
            label.appendChild(indicator);
            setTimeout(() => indicator.remove(), 5000);
        }
    }

    // ========================================
    // 6. RACCOURCIS CLAVIER
    // ========================================
    
    function initKeyboardShortcuts() {
        document.addEventListener('keydown', function(e) {
            // Ctrl+S : Sauvegarder
            if (e.ctrlKey && e.key === 's') {
                e.preventDefault();
                const saveBtn = document.querySelector('.btn-save');
                if (saveBtn) {
                    saveBtn.click();
                }
            }

            // Escape : Annuler / Retour
            if (e.key === 'Escape') {
                const cancelBtn = document.querySelector('.btn-cancel');
                if (cancelBtn) {
                    if (confirm('Abandonner les modifications ?')) {
                        cancelBtn.click();
                    }
                }
            }
        });
    }

    // ========================================
    // 7. CONFIRMATIONS D'ACTIONS
    // ========================================
    
    function initConfirmations() {
        // Confirmation de suppression
        const deleteButtons = document.querySelectorAll('.btn-delete');
        deleteButtons.forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (!confirm('Êtes-vous sûr de vouloir supprimer cet élément ? Cette action est irréversible.')) {
                    e.preventDefault();
                }
            });
        });

        // Avertissement avant de quitter avec des modifications non sauvegardées
        let formModified = false;
        const form = document.querySelector('form');
        
        if (form) {
            const inputs = form.querySelectorAll('input, textarea, select');
            inputs.forEach(input => {
                input.addEventListener('change', function() {
                    formModified = true;
                });
            });

            form.addEventListener('submit', function() {
                formModified = false;
            });

            window.addEventListener('beforeunload', function(e) {
                if (formModified) {
                    e.preventDefault();
                    e.returnValue = 'Vous avez des modifications non sauvegardées. Voulez-vous vraiment quitter ?';
                }
            });
        }
    }

    // ========================================
    // 8. CHAMPS DYNAMIQUES
    // ========================================
    
    function initDynamicFields() {
        // Calculs automatiques
        const priceFields = document.querySelectorAll('[data-calculate]');
        priceFields.forEach(field => {
            field.addEventListener('input', calculateTotal);
        });

        // Champs conditionnels
        const triggerFields = document.querySelectorAll('[data-toggle-field]');
        triggerFields.forEach(field => {
            field.addEventListener('change', function() {
                const targetId = this.getAttribute('data-toggle-field');
                const targetField = document.getElementById(targetId);
                
                if (targetField) {
                    const showValue = this.getAttribute('data-show-value');
                    if (this.value === showValue || this.checked) {
                        targetField.style.display = 'block';
                    } else {
                        targetField.style.display = 'none';
                    }
                }
            });
        });
    }

    function calculateTotal() {
        // Exemple de calcul automatique (à adapter selon les besoins)
        const quantityField = document.querySelector('[name="quantite"]');
        const priceField = document.querySelector('[name="prix_unitaire"]');
        const totalField = document.querySelector('[name="montant_total"]');

        if (quantityField && priceField && totalField) {
            const quantity = parseFloat(quantityField.value) || 0;
            const price = parseFloat(priceField.value) || 0;
            const total = quantity * price;
            totalField.value = total.toFixed(2);
        }
    }

    // ========================================
    // 9. UPLOAD DE FICHIERS
    // ========================================
    
    function initFileUploads() {
        const fileInputs = document.querySelectorAll('input[type="file"]');
        
        fileInputs.forEach(input => {
            input.addEventListener('change', function() {
                const fileName = this.files[0]?.name || 'Aucun fichier sélectionné';
                const label = this.closest('.file-upload-wrapper')?.querySelector('.file-upload-label');
                
                if (label) {
                    const textSpan = label.querySelector('span') || label;
                    textSpan.textContent = fileName;
                }
            });
        });
    }

    // ========================================
    // 10. AUTO-DISMISS DES ALERTES
    // ========================================
    
    function initAutoDismissAlerts() {
        const alerts = document.querySelectorAll('.form-alert:not(.alert-danger)');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-20px)';
                setTimeout(() => alert.remove(), 500);
            }, 5000);
        });
    }

    // ========================================
    // 11. NOTIFICATIONS
    // ========================================
    
    function showNotification(message, type = 'info', duration = 3000) {
        const notification = document.createElement('div');
        notification.className = `form-alert alert-${type === 'error' ? 'danger' : type}`;
        notification.style.position = 'fixed';
        notification.style.top = '20px';
        notification.style.right = '20px';
        notification.style.zIndex = '9999';
        notification.style.minWidth = '300px';
        
        const icon = type === 'success' ? 'check-circle' : 
                     type === 'error' ? 'exclamation-triangle' : 
                     type === 'warning' ? 'exclamation-circle' : 'info-circle';
        
        notification.innerHTML = `<i class="bi bi-${icon}"></i> ${message}`;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            notification.style.opacity = '0';
            notification.style.transform = 'translateX(400px)';
            setTimeout(() => notification.remove(), 500);
        }, duration);
    }

    // Exposition de la fonction pour utilisation externe
    window.showFormNotification = showNotification;

})();
