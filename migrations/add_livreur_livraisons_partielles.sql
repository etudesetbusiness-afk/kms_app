-- Migration: Ajout livreur et support livraisons partielles
-- Date: 2025-12-13

-- Ajouter colonne livreur_id dans bons_livraison
ALTER TABLE bons_livraison
ADD COLUMN livreur_id INT(10) UNSIGNED NULL AFTER magasinier_id,
ADD COLUMN date_livraison_effective DATETIME NULL AFTER date_bl,
ADD COLUMN statut ENUM('EN_PREPARATION','PRET','EN_COURS_LIVRAISON','LIVRE','ANNULE') DEFAULT 'EN_PREPARATION' AFTER signe_client,
ADD INDEX idx_livreur (livreur_id),
ADD INDEX idx_statut (statut);

-- Ajouter colonne ordre_preparation_id pour lier livraisons aux ordres
ALTER TABLE bons_livraison
ADD COLUMN ordre_preparation_id INT(10) UNSIGNED NULL AFTER vente_id,
ADD INDEX idx_ordre_preparation (ordre_preparation_id);

-- Ajouter colonnes pour gérer les quantités livrées partiellement dans bons_livraison_lignes
ALTER TABLE bons_livraison_lignes
ADD COLUMN quantite_commandee DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER quantite,
ADD COLUMN quantite_restante DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER quantite_commandee;

-- Mettre à jour les données existantes (quantite_commandee = quantite)
UPDATE bons_livraison_lignes SET quantite_commandee = quantite WHERE quantite_commandee = 0;

-- Ajouter statut plus détaillé pour ventes (gérer PARTIELLEMENT_LIVREE)
-- (Vérifier si existe déjà avant d'exécuter si nécessaire)
ALTER TABLE ventes
MODIFY COLUMN statut ENUM(
    'DEVIS',
    'DEVIS_ACCEPTE', 
    'EN_ATTENTE_LIVRAISON',
    'EN_PREPARATION',
    'PRET_LIVRAISON',
    'PARTIELLEMENT_LIVREE',
    'LIVREE',
    'FACTUREE',
    'PAYEE',
    'ANNULEE'
) NOT NULL DEFAULT 'EN_ATTENTE_LIVRAISON';
