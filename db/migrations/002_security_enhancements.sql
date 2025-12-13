-- ============================================================================
-- Migration 002: Améliorations Sécurité & Performance
-- Date: 2025-12-13
-- Description: Ajout des tables pour 2FA, sessions avancées, et audit
-- ============================================================================

USE kms_gestion;

-- ----------------------------------------------------------------------------
-- Table: utilisateurs_2fa
-- Gestion de l'authentification à deux facteurs
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS utilisateurs_2fa (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT UNSIGNED NOT NULL,
    secret VARCHAR(255) NOT NULL COMMENT 'Secret TOTP encodé',
    actif TINYINT(1) DEFAULT 0,
    date_activation DATETIME NULL,
    date_desactivation DATETIME NULL,
    methode ENUM('TOTP', 'SMS', 'EMAIL') DEFAULT 'TOTP',
    telephone_backup VARCHAR(50) NULL,
    email_backup VARCHAR(150) NULL,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_user_2fa (utilisateur_id),
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    INDEX idx_2fa_actif (actif)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Configuration 2FA par utilisateur';

-- ----------------------------------------------------------------------------
-- Table: utilisateurs_2fa_recovery
-- Codes de récupération pour 2FA
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS utilisateurs_2fa_recovery (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT UNSIGNED NOT NULL,
    code_hash VARCHAR(255) NOT NULL COMMENT 'Hash du code de récupération',
    utilise TINYINT(1) DEFAULT 0,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_utilisation DATETIME NULL,
    
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    INDEX idx_recovery_user (utilisateur_id),
    INDEX idx_recovery_utilise (utilise)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Codes de récupération 2FA (backup)';

-- ----------------------------------------------------------------------------
-- Table: sessions_actives
-- Gestion avancée des sessions utilisateurs
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS sessions_actives (
    id VARCHAR(128) PRIMARY KEY COMMENT 'Session ID',
    utilisateur_id INT UNSIGNED NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent VARCHAR(500) NULL,
    device_fingerprint VARCHAR(64) NULL COMMENT 'Empreinte du device',
    pays VARCHAR(2) NULL COMMENT 'Code pays ISO',
    ville VARCHAR(100) NULL,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_derniere_activite DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    date_expiration DATETIME NOT NULL,
    actif TINYINT(1) DEFAULT 1,
    
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    INDEX idx_session_user (utilisateur_id),
    INDEX idx_session_expiration (date_expiration),
    INDEX idx_session_actif (actif)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Sessions actives avec tracking détaillé';

-- ----------------------------------------------------------------------------
-- Table: audit_log
-- Journal d'audit complet des actions sensibles
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS audit_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT UNSIGNED NULL COMMENT 'NULL si action système',
    action VARCHAR(100) NOT NULL COMMENT 'Type action: LOGIN, LOGOUT, CREATE, UPDATE, DELETE',
    module VARCHAR(50) NOT NULL COMMENT 'Module concerné: PRODUITS, VENTES, CAISSE, etc.',
    entite_type VARCHAR(50) NULL COMMENT 'Type entité: produit, vente, client',
    entite_id INT UNSIGNED NULL COMMENT 'ID de l\'entité',
    details JSON NULL COMMENT 'Détails de l\'action',
    ancienne_valeur JSON NULL COMMENT 'Valeur avant modification',
    nouvelle_valeur JSON NULL COMMENT 'Valeur après modification',
    ip_address VARCHAR(45) NOT NULL,
    user_agent VARCHAR(500) NULL,
    date_action DATETIME DEFAULT CURRENT_TIMESTAMP,
    niveau ENUM('INFO', 'WARNING', 'ERROR', 'CRITICAL') DEFAULT 'INFO',
    
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE SET NULL,
    INDEX idx_audit_user (utilisateur_id),
    INDEX idx_audit_date (date_action),
    INDEX idx_audit_module (module),
    INDEX idx_audit_action (action),
    INDEX idx_audit_niveau (niveau),
    INDEX idx_audit_entite (entite_type, entite_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Journal d\'audit complet du système';

-- ----------------------------------------------------------------------------
-- Table: tentatives_connexion
-- Amélioration de la table existante connexions_utilisateur
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tentatives_connexion (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    login_attempt VARCHAR(100) NOT NULL COMMENT 'Login tenté',
    utilisateur_id INT UNSIGNED NULL COMMENT 'NULL si login invalide',
    ip_address VARCHAR(45) NOT NULL,
    user_agent VARCHAR(500) NULL,
    methode_2fa VARCHAR(20) NULL COMMENT 'TOTP, RECOVERY, NONE',
    succes TINYINT(1) NOT NULL,
    raison_echec VARCHAR(200) NULL COMMENT 'Mot de passe incorrect, 2FA invalide, compte bloqué',
    pays VARCHAR(2) NULL,
    ville VARCHAR(100) NULL,
    date_tentative DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE SET NULL,
    INDEX idx_tentative_date (date_tentative),
    INDEX idx_tentative_ip (ip_address),
    INDEX idx_tentative_succes (succes),
    INDEX idx_tentative_user (utilisateur_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Historique détaillé des tentatives de connexion';

-- ----------------------------------------------------------------------------
-- Table: blocages_ip
-- Gestion des IP bloquées automatiquement
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS blocages_ip (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    raison VARCHAR(255) NOT NULL,
    type_blocage ENUM('TEMPORAIRE', 'PERMANENT') DEFAULT 'TEMPORAIRE',
    tentatives_echouees INT UNSIGNED DEFAULT 0,
    date_blocage DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_expiration DATETIME NULL COMMENT 'NULL si permanent',
    debloque_par INT UNSIGNED NULL COMMENT 'Admin qui a débloqué',
    date_deblocage DATETIME NULL,
    actif TINYINT(1) DEFAULT 1,
    
    UNIQUE KEY unique_ip (ip_address),
    INDEX idx_blocage_actif (actif),
    INDEX idx_blocage_expiration (date_expiration)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Liste des adresses IP bloquées';

-- ----------------------------------------------------------------------------
-- Table: parametres_securite
-- Configuration globale de la sécurité
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS parametres_securite (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cle VARCHAR(100) NOT NULL UNIQUE,
    valeur TEXT NOT NULL,
    type ENUM('STRING', 'INT', 'BOOL', 'JSON') DEFAULT 'STRING',
    description TEXT NULL,
    modifie_par INT UNSIGNED NULL,
    date_modification DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (modifie_par) REFERENCES utilisateurs(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Configuration de sécurité globale';

-- ----------------------------------------------------------------------------
-- Insertion des paramètres de sécurité par défaut
-- ----------------------------------------------------------------------------
INSERT INTO parametres_securite (cle, valeur, type, description) VALUES
('2fa_obligatoire_admin', '1', 'BOOL', 'Forcer 2FA pour tous les administrateurs'),
('2fa_obligatoire_tous', '0', 'BOOL', 'Forcer 2FA pour tous les utilisateurs'),
('session_timeout_minutes', '120', 'INT', 'Durée de session inactive en minutes'),
('max_sessions_simultanees', '3', 'INT', 'Nombre max de sessions simultanées par utilisateur'),
('login_max_attempts', '5', 'INT', 'Tentatives de connexion max avant blocage'),
('login_block_duration_minutes', '60', 'INT', 'Durée de blocage après échecs répétés'),
('password_min_length', '8', 'INT', 'Longueur minimale du mot de passe'),
('password_require_special', '1', 'BOOL', 'Exiger caractères spéciaux dans mot de passe'),
('password_require_number', '1', 'BOOL', 'Exiger chiffres dans mot de passe'),
('password_require_uppercase', '1', 'BOOL', 'Exiger majuscules dans mot de passe'),
('password_expiration_days', '90', 'INT', 'Expiration mot de passe (0 = jamais)'),
('audit_retention_days', '365', 'INT', 'Durée conservation logs audit'),
('redis_enabled', '1', 'BOOL', 'Activer le cache Redis'),
('rate_limit_enabled', '1', 'BOOL', 'Activer le rate limiting')
ON DUPLICATE KEY UPDATE valeur=VALUES(valeur);

-- ----------------------------------------------------------------------------
-- Ajout de colonnes à la table utilisateurs existante
-- ----------------------------------------------------------------------------
ALTER TABLE utilisateurs 
ADD COLUMN IF NOT EXISTS date_changement_mdp DATETIME NULL COMMENT 'Date dernier changement mot de passe',
ADD COLUMN IF NOT EXISTS mdp_expire TINYINT(1) DEFAULT 0 COMMENT 'Mot de passe expiré',
ADD COLUMN IF NOT EXISTS force_changement_mdp TINYINT(1) DEFAULT 0 COMMENT 'Forcer changement au prochain login',
ADD COLUMN IF NOT EXISTS compte_verrouille TINYINT(1) DEFAULT 0 COMMENT 'Compte verrouillé (manuel)',
ADD COLUMN IF NOT EXISTS raison_verrouillage TEXT NULL,
ADD COLUMN IF NOT EXISTS date_verrouillage DATETIME NULL,
ADD COLUMN IF NOT EXISTS sessions_simultanees_actuelles INT DEFAULT 0 COMMENT 'Compteur sessions actives';

-- ----------------------------------------------------------------------------
-- Index supplémentaires pour performance
-- ----------------------------------------------------------------------------
ALTER TABLE utilisateurs 
ADD INDEX IF NOT EXISTS idx_compte_verrouille (compte_verrouille),
ADD INDEX IF NOT EXISTS idx_mdp_expire (mdp_expire);

-- ============================================================================
-- FIN DE LA MIGRATION
-- ============================================================================
