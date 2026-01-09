-- ========================================================
-- MIGRATION 005 : REPORTING HEBDOMADAIRE TERRAIN
-- Date : 2026-01-09
-- Objectif : Module de reporting hebdomadaire commerciaux
-- ========================================================

-- --------------------------------------------------------
-- TABLE PRINCIPALE : reporting_terrain
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `reporting_terrain` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `commercial_id` INT(10) UNSIGNED NOT NULL COMMENT 'ID du commercial (créateur)',
  `semaine_debut` DATE NOT NULL COMMENT 'Date début semaine (lundi)',
  `semaine_fin` DATE NOT NULL COMMENT 'Date fin semaine (dimanche)',
  `ville` VARCHAR(150) NULL DEFAULT NULL,
  `responsable` VARCHAR(150) NULL DEFAULT NULL,
  `signature` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Signature numérique ou texte',
  
  -- Résultats commerciaux agrégés
  `objectif_visites` INT(10) UNSIGNED DEFAULT 30,
  `realise_visites` INT(10) UNSIGNED DEFAULT 0,
  `objectif_contacts` INT(10) UNSIGNED DEFAULT 10,
  `realise_contacts` INT(10) UNSIGNED DEFAULT 0,
  `objectif_devis` INT(10) UNSIGNED DEFAULT 5,
  `realise_devis` INT(10) UNSIGNED DEFAULT 0,
  `objectif_commandes` INT(10) UNSIGNED DEFAULT 5,
  `realise_commandes` INT(10) UNSIGNED DEFAULT 0,
  `objectif_montant` DECIMAL(15,2) DEFAULT 50000,
  `realise_montant` DECIMAL(15,2) DEFAULT 0,
  
  -- Synthèse
  `synthese` TEXT NULL DEFAULT NULL COMMENT 'Synthèse commerciale max 5 lignes',
  
  -- Métadonnées
  `statut` ENUM('BROUILLON','SOUMIS','VALIDE') NOT NULL DEFAULT 'BROUILLON',
  `date_creation` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `date_soumission` DATETIME NULL DEFAULT NULL,
  `valide_par` INT(10) UNSIGNED NULL DEFAULT NULL,
  `date_validation` DATETIME NULL DEFAULT NULL,
  
  PRIMARY KEY (`id`),
  KEY `idx_commercial_id` (`commercial_id`),
  KEY `idx_semaine` (`semaine_debut`, `semaine_fin`),
  KEY `idx_statut` (`statut`),
  CONSTRAINT `fk_reporting_terrain_commercial` 
    FOREIGN KEY (`commercial_id`) 
    REFERENCES `utilisateurs` (`id`) 
    ON DELETE CASCADE,
  CONSTRAINT `fk_reporting_terrain_valideur` 
    FOREIGN KEY (`valide_par`) 
    REFERENCES `utilisateurs` (`id`) 
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Reporting hebdomadaire commerciaux terrain';

-- --------------------------------------------------------
-- ZONES & CIBLES COUVERTES (par jour)
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `reporting_terrain_zones` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `reporting_id` INT(10) UNSIGNED NOT NULL,
  `jour` ENUM('Lun','Mar','Mer','Jeu','Ven','Sam') NOT NULL,
  `zone_quartier` VARCHAR(255) NULL DEFAULT NULL,
  `type_cibles` VARCHAR(255) NULL DEFAULT NULL,
  `nb_points_visites` INT(10) UNSIGNED DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_reporting_id` (`reporting_id`),
  CONSTRAINT `fk_rtz_reporting` 
    FOREIGN KEY (`reporting_id`) 
    REFERENCES `reporting_terrain` (`id`) 
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- SUIVI JOURNALIER ACTIVITÉ
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `reporting_terrain_journal` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `reporting_id` INT(10) UNSIGNED NOT NULL,
  `jour` ENUM('Lun','Mar','Mer','Jeu','Ven','Sam') NOT NULL,
  `contacts_qualifies` INT(10) UNSIGNED DEFAULT 0,
  `decideurs_rencontres` INT(10) UNSIGNED DEFAULT 0,
  `echantillons` TINYINT(1) DEFAULT 0 COMMENT '0=Non, 1=Oui',
  `rdv_obtenus` INT(10) UNSIGNED DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_reporting_id` (`reporting_id`),
  CONSTRAINT `fk_rtj_reporting` 
    FOREIGN KEY (`reporting_id`) 
    REFERENCES `reporting_terrain` (`id`) 
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- PRODUITS VENDUS (max 4 lignes visibles par défaut)
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `reporting_terrain_produits` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `reporting_id` INT(10) UNSIGNED NOT NULL,
  `produit_libelle` VARCHAR(255) NOT NULL,
  `quantite` INT(10) UNSIGNED DEFAULT 1,
  `prix_unitaire` DECIMAL(15,2) DEFAULT 0,
  `total` DECIMAL(15,2) GENERATED ALWAYS AS (`quantite` * `prix_unitaire`) STORED,
  PRIMARY KEY (`id`),
  KEY `idx_reporting_id` (`reporting_id`),
  CONSTRAINT `fk_rtp_reporting` 
    FOREIGN KEY (`reporting_id`) 
    REFERENCES `reporting_terrain` (`id`) 
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- OBJECTIONS RENCONTRÉES
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `reporting_terrain_objections` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `reporting_id` INT(10) UNSIGNED NOT NULL,
  `objection` VARCHAR(255) NOT NULL,
  `frequence` ENUM('Faible','Moyenne','Élevée') NULL DEFAULT NULL,
  `commentaire` TEXT NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_reporting_id` (`reporting_id`),
  CONSTRAINT `fk_rto_reporting` 
    FOREIGN KEY (`reporting_id`) 
    REFERENCES `reporting_terrain` (`id`) 
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- ARGUMENTS QUI ONT FONCTIONNÉ
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `reporting_terrain_arguments` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `reporting_id` INT(10) UNSIGNED NOT NULL,
  `argument` VARCHAR(255) NOT NULL,
  `impact` ENUM('Faible','Moyen','Fort') NULL DEFAULT NULL,
  `exemple` TEXT NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_reporting_id` (`reporting_id`),
  CONSTRAINT `fk_rta_reporting` 
    FOREIGN KEY (`reporting_id`) 
    REFERENCES `reporting_terrain` (`id`) 
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- PLAN D'ACTION SEMAINE SUIVANTE
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `reporting_terrain_actions` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `reporting_id` INT(10) UNSIGNED NOT NULL,
  `priorite` TINYINT(1) UNSIGNED DEFAULT 1,
  `action` VARCHAR(255) NOT NULL,
  `zone_cible` VARCHAR(255) NULL DEFAULT NULL,
  `echeance` DATE NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_reporting_id` (`reporting_id`),
  CONSTRAINT `fk_rtac_reporting` 
    FOREIGN KEY (`reporting_id`) 
    REFERENCES `reporting_terrain` (`id`) 
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- HISTORIQUE RÉATTRIBUTIONS (pour traçabilité admin)
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `reporting_terrain_historique` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `reporting_id` INT(10) UNSIGNED NOT NULL,
  `action` VARCHAR(100) NOT NULL COMMENT 'Ex: REASSIGNATION, VALIDATION, MODIFICATION',
  `ancien_commercial_id` INT(10) UNSIGNED NULL DEFAULT NULL,
  `nouveau_commercial_id` INT(10) UNSIGNED NULL DEFAULT NULL,
  `effectue_par` INT(10) UNSIGNED NOT NULL,
  `commentaire` TEXT NULL DEFAULT NULL,
  `date_action` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_reporting_id` (`reporting_id`),
  CONSTRAINT `fk_rth_reporting` 
    FOREIGN KEY (`reporting_id`) 
    REFERENCES `reporting_terrain` (`id`) 
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Historique des actions admin sur reportings';

-- --------------------------------------------------------
-- HISTORIQUE RÉATTRIBUTIONS PROSPECTIONS (pour traçabilité admin)
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `prospection_historique` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `prospection_id` INT(10) UNSIGNED NOT NULL,
  `action` VARCHAR(100) NOT NULL COMMENT 'Ex: REASSIGNATION, MODIFICATION',
  `ancien_commercial_id` INT(10) UNSIGNED NULL DEFAULT NULL,
  `nouveau_commercial_id` INT(10) UNSIGNED NULL DEFAULT NULL,
  `effectue_par` INT(10) UNSIGNED NOT NULL,
  `commentaire` TEXT NULL DEFAULT NULL,
  `date_action` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_prospection_id` (`prospection_id`),
  CONSTRAINT `fk_ph_prospection` 
    FOREIGN KEY (`prospection_id`) 
    REFERENCES `prospections_terrain` (`id`) 
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Historique des réattributions prospections par admin';

-- ========================================================
-- FIN MIGRATION 005
-- ========================================================
