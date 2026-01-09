-- ========================================================
-- MIGRATION 006 : AJUSTEMENT STRUCTURE REPORTING TERRAIN
-- Date : 2026-01-09
-- Objectif : Adapter la table reporting_terrain au formulaire
-- ========================================================

-- Ajouter les colonnes manquantes à reporting_terrain
ALTER TABLE `reporting_terrain`
ADD COLUMN IF NOT EXISTS `numero_semaine` INT(2) UNSIGNED NOT NULL DEFAULT 1 AFTER `commercial_id`,
ADD COLUMN IF NOT EXISTS `annee` INT(4) UNSIGNED NOT NULL DEFAULT 2025 AFTER `numero_semaine`,
ADD COLUMN IF NOT EXISTS `date_debut` DATE NULL DEFAULT NULL AFTER `annee`,
ADD COLUMN IF NOT EXISTS `date_fin` DATE NULL DEFAULT NULL AFTER `date_debut`,
ADD COLUMN IF NOT EXISTS `objectif_ca` DECIMAL(15,2) DEFAULT 0 AFTER `date_fin`,
ADD COLUMN IF NOT EXISTS `objectif_rdv` INT(10) UNSIGNED DEFAULT 0 AFTER `objectif_visites`,
ADD COLUMN IF NOT EXISTS `points_forts` TEXT NULL DEFAULT NULL AFTER `synthese`,
ADD COLUMN IF NOT EXISTS `difficultes` TEXT NULL DEFAULT NULL AFTER `points_forts`,
ADD COLUMN IF NOT EXISTS `besoins_support` TEXT NULL DEFAULT NULL AFTER `difficultes`,
ADD COLUMN IF NOT EXISTS `commentaire_rejet` TEXT NULL DEFAULT NULL AFTER `besoins_support`,
ADD COLUMN IF NOT EXISTS `submitted_at` DATETIME NULL DEFAULT NULL AFTER `date_soumission`,
ADD COLUMN IF NOT EXISTS `validated_at` DATETIME NULL DEFAULT NULL AFTER `date_validation`,
ADD COLUMN IF NOT EXISTS `validated_by` INT(10) UNSIGNED NULL DEFAULT NULL AFTER `validated_at`,
ADD COLUMN IF NOT EXISTS `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `validated_by`,
ADD COLUMN IF NOT EXISTS `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;

-- Copier les données des anciennes colonnes si besoin
UPDATE `reporting_terrain` SET 
    date_debut = semaine_debut,
    date_fin = semaine_fin,
    objectif_ca = objectif_montant
WHERE date_debut IS NULL;

-- Ajouter le statut REJETE à l'enum
ALTER TABLE `reporting_terrain` 
MODIFY COLUMN `statut` ENUM('BROUILLON','SOUMIS','VALIDE','REJETE') NOT NULL DEFAULT 'BROUILLON';

-- Créer index sur numero_semaine et annee
ALTER TABLE `reporting_terrain`
ADD INDEX IF NOT EXISTS `idx_semaine_annee` (`annee`, `numero_semaine`);

-- ========================================================
-- FIN MIGRATION 006
-- ========================================================
