-- ========================================================
-- MIGRATION 007 : AJUSTEMENT TABLES ENFANTS REPORTING
-- Date : 2026-01-09
-- Objectif : Adapter les tables enfants au formulaire mobile-first
-- ========================================================

-- --------------------------------------------------------
-- AJUSTEMENT reporting_terrain_journal
-- --------------------------------------------------------
ALTER TABLE `reporting_terrain_journal`
ADD COLUMN IF NOT EXISTS `jour_semaine` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT '1=Lundi, 6=Samedi' AFTER `jour`,
ADD COLUMN IF NOT EXISTS `nb_visites` INT(10) UNSIGNED DEFAULT 0 AFTER `jour_semaine`,
ADD COLUMN IF NOT EXISTS `nb_rdv` INT(10) UNSIGNED DEFAULT 0 AFTER `nb_visites`,
ADD COLUMN IF NOT EXISTS `nb_devis` INT(10) UNSIGNED DEFAULT 0 AFTER `nb_rdv`,
ADD COLUMN IF NOT EXISTS `nb_ventes` INT(10) UNSIGNED DEFAULT 0 AFTER `nb_devis`,
ADD COLUMN IF NOT EXISTS `ca_realise` DECIMAL(15,2) DEFAULT 0 AFTER `nb_ventes`,
ADD COLUMN IF NOT EXISTS `zone_couverte` VARCHAR(255) NULL DEFAULT NULL AFTER `ca_realise`,
ADD COLUMN IF NOT EXISTS `remarques` TEXT NULL DEFAULT NULL AFTER `zone_couverte`;

-- --------------------------------------------------------
-- AJUSTEMENT reporting_terrain_zones (colonnes existantes: zone_quartier, type_cibles)
-- --------------------------------------------------------
ALTER TABLE `reporting_terrain_zones`
ADD COLUMN IF NOT EXISTS `nom_zone` VARCHAR(255) NULL DEFAULT NULL AFTER `nb_points_visites`,
ADD COLUMN IF NOT EXISTS `type_cible` VARCHAR(50) NULL DEFAULT NULL AFTER `nom_zone`,
ADD COLUMN IF NOT EXISTS `potentiel` VARCHAR(50) NULL DEFAULT NULL AFTER `type_cible`,
ADD COLUMN IF NOT EXISTS `remarques` TEXT NULL DEFAULT NULL AFTER `potentiel`;

-- Copier données existantes vers nouvelles colonnes
UPDATE `reporting_terrain_zones` SET nom_zone = zone_quartier WHERE nom_zone IS NULL AND zone_quartier IS NOT NULL;
UPDATE `reporting_terrain_zones` SET type_cible = type_cibles WHERE type_cible IS NULL AND type_cibles IS NOT NULL;

-- --------------------------------------------------------
-- AJUSTEMENT reporting_terrain_produits (colonnes existantes: produit_libelle, prix_unitaire, total)
-- --------------------------------------------------------
ALTER TABLE `reporting_terrain_produits`
ADD COLUMN IF NOT EXISTS `produit_nom` VARCHAR(255) NULL DEFAULT NULL AFTER `total`,
ADD COLUMN IF NOT EXISTS `montant_total` DECIMAL(15,2) DEFAULT 0 AFTER `produit_nom`,
ADD COLUMN IF NOT EXISTS `type_client` VARCHAR(50) NULL DEFAULT NULL AFTER `montant_total`,
ADD COLUMN IF NOT EXISTS `remarques` TEXT NULL DEFAULT NULL AFTER `type_client`;

-- Copier données existantes vers nouvelles colonnes
UPDATE `reporting_terrain_produits` SET produit_nom = produit_libelle WHERE produit_nom IS NULL AND produit_libelle IS NOT NULL;
UPDATE `reporting_terrain_produits` SET montant_total = total WHERE montant_total = 0 AND total IS NOT NULL;

-- --------------------------------------------------------
-- AJUSTEMENT reporting_terrain_objections (colonnes existent déjà)
-- --------------------------------------------------------
-- Rien à faire, les colonnes objection, reponse_apportee, frequence existent

-- --------------------------------------------------------
-- AJUSTEMENT reporting_terrain_arguments (colonnes existent déjà)
-- --------------------------------------------------------
-- Rien à faire, les colonnes argument, efficacite, contexte existent

-- --------------------------------------------------------
-- AJUSTEMENT reporting_terrain_actions (colonnes existent déjà)  
-- --------------------------------------------------------
-- Rien à faire, les colonnes description, echeance, priorite, statut existent

-- --------------------------------------------------------
-- AJUSTEMENT reporting_terrain_historique
-- --------------------------------------------------------
ALTER TABLE `reporting_terrain_historique`
ADD COLUMN IF NOT EXISTS `utilisateur_id` INT(10) UNSIGNED NULL DEFAULT NULL AFTER `effectue_par`,
ADD COLUMN IF NOT EXISTS `ancien_statut` VARCHAR(50) NULL DEFAULT NULL AFTER `action`,
ADD COLUMN IF NOT EXISTS `nouveau_statut` VARCHAR(50) NULL DEFAULT NULL AFTER `ancien_statut`,
ADD COLUMN IF NOT EXISTS `commentaire` TEXT NULL DEFAULT NULL AFTER `nouveau_statut`;

-- Copier données
UPDATE `reporting_terrain_historique` SET utilisateur_id = effectue_par WHERE utilisateur_id IS NULL;

-- ========================================================
-- FIN MIGRATION 007
-- ========================================================
