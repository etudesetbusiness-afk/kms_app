-- ============================================================================
-- Migration: Reporting Hebdomadaire Terrain (Activité Commerciale)
-- Version: 1.0
-- Date: 2026-01-09
-- Description: Tables pour le module de reporting terrain des commerciaux
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Table principale des reportings hebdomadaires
CREATE TABLE IF NOT EXISTS `terrain_reporting` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL COMMENT 'FK vers utilisateurs.id',
    `commercial_nom` VARCHAR(150) NOT NULL COMMENT 'Nom du commercial (historique)',
    `semaine_debut` DATE NOT NULL COMMENT 'Lundi de la semaine',
    `semaine_fin` DATE NOT NULL COMMENT 'Samedi de la semaine',
    `ville` VARCHAR(120) DEFAULT NULL,
    `responsable_nom` VARCHAR(150) DEFAULT NULL,
    `signature_nom` VARCHAR(150) DEFAULT NULL,
    `synthese` VARCHAR(900) DEFAULT NULL COMMENT 'Synthèse max 5 lignes',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_semaine_debut` (`semaine_debut`),
    CONSTRAINT `fk_terrain_reporting_user` FOREIGN KEY (`user_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Reportings hebdomadaires terrain';

-- Zones & cibles couvertes (Lun-Sam)
CREATE TABLE IF NOT EXISTS `terrain_reporting_zones` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `reporting_id` INT UNSIGNED NOT NULL,
    `jour` ENUM('Lun','Mar','Mer','Jeu','Ven','Sam') NOT NULL,
    `zone_quartier` VARCHAR(200) DEFAULT NULL,
    `type_cible` ENUM('Quincaillerie','Menuiserie','Autre') DEFAULT 'Autre',
    `nb_points` INT UNSIGNED DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_reporting_id` (`reporting_id`),
    CONSTRAINT `fk_zones_reporting` FOREIGN KEY (`reporting_id`) REFERENCES `terrain_reporting` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Zones visitées par jour';

-- Suivi journalier d'activité (Lun-Sam)
CREATE TABLE IF NOT EXISTS `terrain_reporting_activite` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `reporting_id` INT UNSIGNED NOT NULL,
    `jour` ENUM('Lun','Mar','Mer','Jeu','Ven','Sam') NOT NULL,
    `contacts_qualifies` INT UNSIGNED DEFAULT 0,
    `decideurs_rencontres` INT UNSIGNED DEFAULT 0,
    `echantillons_presentes` TINYINT(1) DEFAULT 0,
    `grille_prix_remise` TINYINT(1) DEFAULT 0,
    `rdv_obtenus` INT UNSIGNED DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_reporting_id` (`reporting_id`),
    CONSTRAINT `fk_activite_reporting` FOREIGN KEY (`reporting_id`) REFERENCES `terrain_reporting` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Suivi activité journalière';

-- Résultats commerciaux de la semaine
CREATE TABLE IF NOT EXISTS `terrain_reporting_resultats` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `reporting_id` INT UNSIGNED NOT NULL,
    `indicateur` ENUM('visites_terrain','contacts_qualifies','devis_emis','commandes_obtenues','montant_commandes','encaissements') NOT NULL,
    `objectif` DECIMAL(15,2) DEFAULT 0.00,
    `realise` DECIMAL(15,2) DEFAULT 0.00,
    PRIMARY KEY (`id`),
    KEY `idx_reporting_id` (`reporting_id`),
    UNIQUE KEY `uk_reporting_indicateur` (`reporting_id`, `indicateur`),
    CONSTRAINT `fk_resultats_reporting` FOREIGN KEY (`reporting_id`) REFERENCES `terrain_reporting` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Résultats commerciaux hebdo';

-- Objections rencontrées
CREATE TABLE IF NOT EXISTS `terrain_reporting_objections` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `reporting_id` INT UNSIGNED NOT NULL,
    `objection_code` ENUM('prix_eleve','qualite_pas_regardee','similaire_moins_cher','pas_tresorerie','decideur_absent','autre') NOT NULL,
    `frequence` ENUM('Faible','Moyenne','Élevée') DEFAULT 'Moyenne',
    `commentaire` VARCHAR(255) DEFAULT NULL,
    `autre_texte` VARCHAR(180) DEFAULT NULL COMMENT 'Si objection_code=autre',
    PRIMARY KEY (`id`),
    KEY `idx_reporting_id` (`reporting_id`),
    CONSTRAINT `fk_objections_reporting` FOREIGN KEY (`reporting_id`) REFERENCES `terrain_reporting` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Objections rencontrées';

-- Arguments efficaces
CREATE TABLE IF NOT EXISTS `terrain_reporting_arguments` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `reporting_id` INT UNSIGNED NOT NULL,
    `argument_code` ENUM('qualite_durabilite','marge_possible','echantillons_visibles','stock_disponible','autre') NOT NULL,
    `impact` ENUM('Faible','Moyen','Fort') DEFAULT 'Moyen',
    `exemple_contexte` VARCHAR(255) DEFAULT NULL,
    `autre_texte` VARCHAR(180) DEFAULT NULL COMMENT 'Si argument_code=autre',
    PRIMARY KEY (`id`),
    KEY `idx_reporting_id` (`reporting_id`),
    CONSTRAINT `fk_arguments_reporting` FOREIGN KEY (`reporting_id`) REFERENCES `terrain_reporting` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Arguments efficaces';

-- Plan d'action semaine suivante
CREATE TABLE IF NOT EXISTS `terrain_reporting_plan_action` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `reporting_id` INT UNSIGNED NOT NULL,
    `priorite` TINYINT UNSIGNED DEFAULT 1 COMMENT '1, 2 ou 3',
    `action_concrete` VARCHAR(220) DEFAULT NULL,
    `zone_cible` VARCHAR(160) DEFAULT NULL,
    `echeance` DATE DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_reporting_id` (`reporting_id`),
    CONSTRAINT `fk_plan_action_reporting` FOREIGN KEY (`reporting_id`) REFERENCES `terrain_reporting` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Plan action semaine suivante';

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- FIN DE LA MIGRATION
-- ============================================================================
