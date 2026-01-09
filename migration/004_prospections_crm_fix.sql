-- Patch migration 004 CRM (safe reapply)

ALTER TABLE `prospections_terrain`
  ADD COLUMN IF NOT EXISTS `telephone` VARCHAR(20) NOT NULL AFTER `prospect_nom`,
  ADD COLUMN IF NOT EXISTS `email` VARCHAR(150) NULL DEFAULT NULL AFTER `telephone`,
  ADD COLUMN IF NOT EXISTS `statut_crm` ENUM(
    'PROSPECT','INTERESSE','PROSPECT_CHAUD','DEVIS_DEMANDE','DEVIS_EMIS','COMMANDE_OBTENUE','CLIENT_ACTIF','FIDELISATION','PERDU'
  ) NOT NULL DEFAULT 'PROSPECT' AFTER `prochaine_etape`,
  ADD COLUMN IF NOT EXISTS `tag_activite` ENUM('QUINCAILLERIE','MENUISERIE','AUTRE') NULL DEFAULT NULL AFTER `statut_crm`,
  ADD COLUMN IF NOT EXISTS `date_relance` DATE NULL DEFAULT NULL AFTER `tag_activite`,
  ADD COLUMN IF NOT EXISTS `canal_relance` ENUM('WHATSAPP','APPEL','SMS','EMAIL','VISITE') NULL DEFAULT NULL AFTER `date_relance`,
  ADD COLUMN IF NOT EXISTS `message_relance` TEXT NULL DEFAULT NULL AFTER `canal_relance`,
  ADD COLUMN IF NOT EXISTS `date_creation` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `commercial_id`,
  ADD COLUMN IF NOT EXISTS `date_modification` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `date_creation`,
  MODIFY COLUMN `adresse_gps` TEXT NULL DEFAULT NULL;

CREATE INDEX IF NOT EXISTS `idx_telephone` ON `prospections_terrain` (`telephone`);
CREATE INDEX IF NOT EXISTS `idx_statut_crm` ON `prospections_terrain` (`statut_crm`);
CREATE INDEX IF NOT EXISTS `idx_date_relance` ON `prospections_terrain` (`date_relance`);
CREATE INDEX IF NOT EXISTS `idx_commercial_id` ON `prospections_terrain` (`commercial_id`);

CREATE TABLE IF NOT EXISTS `prospect_notes` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `prospection_id` INT(10) UNSIGNED NOT NULL,
  `utilisateur_id` INT(10) UNSIGNED NULL DEFAULT NULL,
  `note` TEXT NOT NULL,
  `date_creation` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_prospection_id` (`prospection_id`),
  KEY `idx_utilisateur_id` (`utilisateur_id`),
  CONSTRAINT `fk_prospect_notes_prospection` FOREIGN KEY (`prospection_id`) REFERENCES `prospections_terrain` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_prospect_notes_utilisateur` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `prospect_relances` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `prospection_id` INT(10) UNSIGNED NOT NULL,
  `utilisateur_id` INT(10) UNSIGNED NULL DEFAULT NULL,
  `date_relance_prevue` DATE NOT NULL,
  `canal` ENUM('WHATSAPP','APPEL','SMS','EMAIL','VISITE') NOT NULL,
  `message` TEXT NULL DEFAULT NULL,
  `statut` ENUM('A_FAIRE','FAIT','ANNULE') NOT NULL DEFAULT 'A_FAIRE',
  `date_realisation` DATETIME NULL DEFAULT NULL,
  `resultat` TEXT NULL DEFAULT NULL,
  `date_creation` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_prospection_id` (`prospection_id`),
  KEY `idx_utilisateur_id` (`utilisateur_id`),
  KEY `idx_date_relance_prevue` (`date_relance_prevue`),
  KEY `idx_statut` (`statut`),
  CONSTRAINT `fk_prospect_relances_prospection` FOREIGN KEY (`prospection_id`) REFERENCES `prospections_terrain` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_prospect_relances_utilisateur` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `prospect_timeline` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `prospection_id` INT(10) UNSIGNED NOT NULL,
  `utilisateur_id` INT(10) UNSIGNED NULL DEFAULT NULL,
  `type_action` ENUM('CREATION','PROSPECTION','NOTE','APPEL','EMAIL','WHATSAPP','VISITE','CHANGEMENT_STATUT','DEVIS_CREE','DEVIS_ENVOYE','VENTE_CONCLUE','RELANCE') NOT NULL,
  `titre` VARCHAR(255) NOT NULL,
  `description` TEXT NULL DEFAULT NULL,
  `ancien_statut` VARCHAR(50) NULL DEFAULT NULL,
  `nouveau_statut` VARCHAR(50) NULL DEFAULT NULL,
  `date_action` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_prospection_id` (`prospection_id`),
  KEY `idx_type_action` (`type_action`),
  KEY `idx_date_action` (`date_action`),
  CONSTRAINT `fk_prospect_timeline_prospection` FOREIGN KEY (`prospection_id`) REFERENCES `prospections_terrain` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_prospect_timeline_utilisateur` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TRIGGER IF EXISTS `trg_prospection_timeline_insert`;
DROP TRIGGER IF EXISTS `trg_prospection_timeline_status_update`;

DELIMITER $$
CREATE TRIGGER `trg_prospection_timeline_insert`
AFTER INSERT ON `prospections_terrain`
FOR EACH ROW
BEGIN
  INSERT INTO `prospect_timeline` (
    `prospection_id`, `utilisateur_id`, `type_action`, `titre`, `description`, `nouveau_statut`, `date_action`
  ) VALUES (
    NEW.id,
    NEW.commercial_id,
    'CREATION',
    'Prospect créé',
    CONCAT('Prospect créé par ', (SELECT nom_complet FROM utilisateurs WHERE id = NEW.commercial_id LIMIT 1)),
    NEW.statut_crm,
    NOW()
  );
END$$

CREATE TRIGGER `trg_prospection_timeline_status_update`
AFTER UPDATE ON `prospections_terrain`
FOR EACH ROW
BEGIN
  IF OLD.statut_crm != NEW.statut_crm THEN
    INSERT INTO `prospect_timeline` (
      `prospection_id`, `utilisateur_id`, `type_action`, `titre`, `description`, `ancien_statut`, `nouveau_statut`, `date_action`
    ) VALUES (
      NEW.id,
      NEW.commercial_id,
      'CHANGEMENT_STATUT',
      'Changement de statut',
      CONCAT('Statut changé de "', OLD.statut_crm, '" vers "', NEW.statut_crm, '"'),
      OLD.statut_crm,
      NEW.statut_crm,
      NOW()
    );
  END IF;
END$$
DELIMITER ;

CREATE OR REPLACE VIEW `v_prospects_par_statut` AS
SELECT 
  statut_crm,
  COUNT(*) AS nb_prospects,
  COUNT(DISTINCT commercial_id) AS nb_commerciaux
FROM prospections_terrain
GROUP BY statut_crm;

CREATE OR REPLACE VIEW `v_relances_en_retard` AS
SELECT 
  r.id AS relance_id,
  r.prospection_id,
  p.prospect_nom,
  p.telephone,
  p.secteur,
  r.date_relance_prevue,
  r.canal,
  DATEDIFF(CURDATE(), r.date_relance_prevue) AS jours_retard,
  u.nom_complet AS commercial
FROM prospect_relances r
INNER JOIN prospections_terrain p ON r.prospection_id = p.id
INNER JOIN utilisateurs u ON r.utilisateur_id = u.id
WHERE r.statut = 'A_FAIRE'
  AND r.date_relance_prevue < CURDATE();

CREATE OR REPLACE VIEW `v_pipeline_commercial` AS
SELECT 
  statut_crm,
  COUNT(*) AS nb_prospects,
  ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM prospections_terrain), 2) AS pourcentage
FROM prospections_terrain
GROUP BY statut_crm
ORDER BY 
  CASE statut_crm
    WHEN 'PROSPECT' THEN 1
    WHEN 'INTERESSE' THEN 2
    WHEN 'PROSPECT_CHAUD' THEN 3
    WHEN 'DEVIS_DEMANDE' THEN 4
    WHEN 'DEVIS_EMIS' THEN 5
    WHEN 'COMMANDE_OBTENUE' THEN 6
    WHEN 'CLIENT_ACTIF' THEN 7
    WHEN 'FIDELISATION' THEN 8
    WHEN 'PERDU' THEN 9
  END;
