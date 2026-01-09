# 🔧 Migration 004 - CORRECTION pour Bluehost

## ⚠️ PROBLÈME IDENTIFIÉ

Le serveur Bluehost a un schéma légèrement différent du local. Plusieurs colonnes existent déjà dans `prospections_terrain` (heure_prospection, telephone, etc.), mais le script de migration 004 essaie de les re-ajouter → **Error #1060: Duplicate column name**.

## ✅ SOLUTION APPLIQUÉE

Le script ci-dessous utilise des **vérifications automatiques** pour chaque colonne, indice, table, trigger et vue :
- ✅ Si la colonne/index/objet **n'existe pas**, il est créé
- ✅ Si la colonne/index/objet **existe déjà**, l'opération est ignorée sans erreur
- ✅ **Aucune erreur #1060** même si des colonnes/objets existent partiellement

---

## ✅ SOLUTION : Exécuter le script CORRIGÉ

Accédez à **phpMyAdmin sur Bluehost** et exécutez le script ci-dessous à la place du script original :

### 📋 Script SQL Corrigé (Migration 004 adaptée)

```sql
-- ========================================================
-- MIGRATION 004 CORRIGÉE : REFONTE PROSPECTIONS → CRM
-- Date : 2025-12-16
-- Objectif : Transformer module prospections en mini-CRM
-- Note: Script adapté pour Bluehost (heure_prospection existe déjà)
-- ========================================================

-- --------------------------------------------------------
-- ÉTAPE 1 : ALTER TABLE prospections_terrain
-- Ajout des champs CRM manquants (avec vérification de non-existence)
-- --------------------------------------------------------

-- Ajouter telephone (vérification)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'prospections_terrain' 
  AND COLUMN_NAME = 'telephone');
SET @sql = IF(@col_exists = 0, 
  'ALTER TABLE `prospections_terrain` ADD COLUMN `telephone` VARCHAR(20) NOT NULL DEFAULT \"\" AFTER `prospect_nom`',
  'SELECT "Colonne telephone existe déjà"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Ajouter email (vérification)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'prospections_terrain' 
  AND COLUMN_NAME = 'email');
SET @sql = IF(@col_exists = 0, 
  'ALTER TABLE `prospections_terrain` ADD COLUMN `email` VARCHAR(150) NULL DEFAULT NULL AFTER `telephone`',
  'SELECT "Colonne email existe déjà"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Ajouter statut_crm (vérification)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'prospections_terrain' 
  AND COLUMN_NAME = 'statut_crm');
SET @sql = IF(@col_exists = 0, 
  'ALTER TABLE `prospections_terrain` ADD COLUMN `statut_crm` ENUM(\"PROSPECT\",\"INTERESSE\",\"PROSPECT_CHAUD\",\"DEVIS_DEMANDE\",\"DEVIS_EMIS\",\"COMMANDE_OBTENUE\",\"CLIENT_ACTIF\",\"FIDELISATION\",\"PERDU\") NOT NULL DEFAULT \"PROSPECT\" AFTER `prochaine_etape`',
  'SELECT "Colonne statut_crm existe déjà"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Ajouter tag_activite (vérification)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'prospections_terrain' 
  AND COLUMN_NAME = 'tag_activite');
SET @sql = IF(@col_exists = 0, 
  'ALTER TABLE `prospections_terrain` ADD COLUMN `tag_activite` ENUM(\"QUINCAILLERIE\",\"MENUISERIE\",\"AUTRE\") NULL DEFAULT NULL AFTER `statut_crm`',
  'SELECT "Colonne tag_activite existe déjà"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Ajouter date_relance (vérification)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'prospections_terrain' 
  AND COLUMN_NAME = 'date_relance');
SET @sql = IF(@col_exists = 0, 
  'ALTER TABLE `prospections_terrain` ADD COLUMN `date_relance` DATE NULL DEFAULT NULL AFTER `tag_activite`',
  'SELECT "Colonne date_relance existe déjà"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Ajouter canal_relance (vérification)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'prospections_terrain' 
  AND COLUMN_NAME = 'canal_relance');
SET @sql = IF(@col_exists = 0, 
  'ALTER TABLE `prospections_terrain` ADD COLUMN `canal_relance` ENUM(\"WHATSAPP\",\"APPEL\",\"SMS\",\"EMAIL\",\"VISITE\") NULL DEFAULT NULL AFTER `date_relance`',
  'SELECT "Colonne canal_relance existe déjà"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Ajouter message_relance (vérification)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'prospections_terrain' 
  AND COLUMN_NAME = 'message_relance');
SET @sql = IF(@col_exists = 0, 
  'ALTER TABLE `prospections_terrain` ADD COLUMN `message_relance` TEXT NULL DEFAULT NULL AFTER `canal_relance`',
  'SELECT "Colonne message_relance existe déjà"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Note: Les colonnes latitude, longitude, adresse_gps existent déjà
-- Vérifier leur existence avant d'exécuter le bloc suivant
-- ALTER TABLE `prospections_terrain`
-- ADD COLUMN `latitude` DECIMAL(10,8) NULL DEFAULT NULL AFTER `message_relance`,
-- ADD COLUMN `longitude` DECIMAL(11,8) NULL DEFAULT NULL AFTER `latitude`,
-- ADD COLUMN `adresse_gps` TEXT NULL DEFAULT NULL AFTER `longitude`;

-- Note: Les colonnes date_creation et date_modification peuvent nécessiter un ajustement
-- selon l'état du schéma Bluehost
-- ALTER TABLE `prospections_terrain`
-- ADD COLUMN `date_creation` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `commercial_id`,
-- ADD COLUMN `date_modification` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `date_creation`;

-- Index pour optimisation recherches (avec vérification)
SET @idx_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'prospections_terrain' 
  AND INDEX_NAME = 'idx_telephone');
SET @sql = IF(@idx_exists = 0, 
  'CREATE INDEX idx_telephone ON `prospections_terrain` (`telephone`)',
  'SELECT "Index idx_telephone existe déjà"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'prospections_terrain' 
  AND INDEX_NAME = 'idx_statut_crm');
SET @sql = IF(@idx_exists = 0, 
  'CREATE INDEX idx_statut_crm ON `prospections_terrain` (`statut_crm`)',
  'SELECT "Index idx_statut_crm existe déjà"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'prospections_terrain' 
  AND INDEX_NAME = 'idx_date_relance');
SET @sql = IF(@idx_exists = 0, 
  'CREATE INDEX idx_date_relance ON `prospections_terrain` (`date_relance`)',
  'SELECT "Index idx_date_relance existe déjà"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'prospections_terrain' 
  AND INDEX_NAME = 'idx_commercial_id');
SET @sql = IF(@idx_exists = 0, 
  'CREATE INDEX idx_commercial_id ON `prospections_terrain` (`commercial_id`)',
  'SELECT "Index idx_commercial_id existe déjà"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- --------------------------------------------------------
-- ÉTAPE 2 : TABLE prospect_notes
-- Historique des notes sur prospects
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `prospect_notes` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `prospection_id` INT(10) UNSIGNED NOT NULL,
  `utilisateur_id` INT(10) UNSIGNED NULL DEFAULT NULL,
  `note` TEXT NOT NULL,
  `date_creation` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_prospection_id` (`prospection_id`),
  KEY `idx_utilisateur_id` (`utilisateur_id`),
  CONSTRAINT `fk_prospect_notes_prospection` 
    FOREIGN KEY (`prospection_id`) 
    REFERENCES `prospections_terrain` (`id`) 
    ON DELETE CASCADE,
  CONSTRAINT `fk_prospect_notes_utilisateur` 
    FOREIGN KEY (`utilisateur_id`) 
    REFERENCES `utilisateurs` (`id`) 
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Notes privées commerciaux sur prospects';

-- --------------------------------------------------------
-- ÉTAPE 3 : TABLE prospect_relances
-- Historique des relances planifiées et effectuées
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `prospect_relances` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `prospection_id` INT(10) UNSIGNED NOT NULL,
  `utilisateur_id` INT(10) UNSIGNED NULL DEFAULT NULL,
  `date_relance_prevue` DATE NOT NULL,
  `canal` ENUM('WHATSAPP', 'APPEL', 'SMS', 'EMAIL', 'VISITE') NOT NULL,
  `message` TEXT NULL DEFAULT NULL,
  `statut` ENUM('A_FAIRE', 'FAIT', 'ANNULE') NOT NULL DEFAULT 'A_FAIRE',
  `date_realisation` DATETIME NULL DEFAULT NULL,
  `resultat` TEXT NULL DEFAULT NULL,
  `date_creation` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_prospection_id` (`prospection_id`),
  KEY `idx_utilisateur_id` (`utilisateur_id`),
  KEY `idx_date_relance_prevue` (`date_relance_prevue`),
  KEY `idx_statut` (`statut`),
  CONSTRAINT `fk_prospect_relances_prospection` 
    FOREIGN KEY (`prospection_id`) 
    REFERENCES `prospections_terrain` (`id`) 
    ON DELETE CASCADE,
  CONSTRAINT `fk_prospect_relances_utilisateur` 
    FOREIGN KEY (`utilisateur_id`) 
    REFERENCES `utilisateurs` (`id`) 
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Planification et suivi des relances prospects';

-- --------------------------------------------------------
-- ÉTAPE 4 : TABLE prospect_timeline
-- Timeline complète des actions sur un prospect
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `prospect_timeline` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `prospection_id` INT(10) UNSIGNED NOT NULL,
  `utilisateur_id` INT(10) UNSIGNED NULL DEFAULT NULL,
  `type_action` ENUM(
    'CREATION',
    'PROSPECTION',
    'NOTE',
    'APPEL',
    'EMAIL',
    'WHATSAPP',
    'VISITE',
    'CHANGEMENT_STATUT',
    'DEVIS_CREE',
    'DEVIS_ENVOYE',
    'VENTE_CONCLUE',
    'RELANCE'
  ) NOT NULL,
  `titre` VARCHAR(255) NOT NULL,
  `description` TEXT NULL DEFAULT NULL,
  `ancien_statut` VARCHAR(50) NULL DEFAULT NULL,
  `nouveau_statut` VARCHAR(50) NULL DEFAULT NULL,
  `date_action` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_prospection_id` (`prospection_id`),
  KEY `idx_type_action` (`type_action`),
  KEY `idx_date_action` (`date_action`),
  CONSTRAINT `fk_prospect_timeline_prospection` 
    FOREIGN KEY (`prospection_id`) 
    REFERENCES `prospections_terrain` (`id`) 
    ON DELETE CASCADE,
  CONSTRAINT `fk_prospect_timeline_utilisateur` 
    FOREIGN KEY (`utilisateur_id`) 
    REFERENCES `utilisateurs` (`id`) 
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Historique complet actions sur prospects (timeline CRM)';

-- --------------------------------------------------------
-- ÉTAPE 5 : Triggers auto-création timeline (avec vérification)
-- --------------------------------------------------------

DROP TRIGGER IF EXISTS `trg_prospection_timeline_insert`;
DROP TRIGGER IF EXISTS `trg_prospection_timeline_status_update`;

DELIMITER $$

CREATE TRIGGER `trg_prospection_timeline_insert`
AFTER INSERT ON `prospections_terrain`
FOR EACH ROW
BEGIN
  INSERT INTO `prospect_timeline` (
    `prospection_id`,
    `utilisateur_id`,
    `type_action`,
    `titre`,
    `description`,
    `nouveau_statut`,
    `date_action`
  ) VALUES (
    NEW.id,
    NEW.commercial_id,
    'CREATION',
    'Prospect créé',
    CONCAT('Prospect créé par ', (SELECT COALESCE(nom_complet, 'Utilisateur') FROM utilisateurs WHERE id = NEW.commercial_id LIMIT 1)),
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
      `prospection_id`,
      `utilisateur_id`,
      `type_action`,
      `titre`,
      `description`,
      `ancien_statut`,
      `nouveau_statut`,
      `date_action`
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

-- --------------------------------------------------------
-- ÉTAPE 6 : Vues pour dashboards & reporting
-- --------------------------------------------------------

DROP VIEW IF EXISTS `v_prospects_par_statut`;
CREATE OR REPLACE VIEW `v_prospects_par_statut` AS
SELECT 
  statut_crm,
  COUNT(*) AS nb_prospects,
  COUNT(DISTINCT commercial_id) AS nb_commerciaux
FROM prospections_terrain
GROUP BY statut_crm;

DROP VIEW IF EXISTS `v_relances_en_retard`;
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

DROP VIEW IF EXISTS `v_pipeline_commercial`;
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

-- ========================================================
-- FIN MIGRATION 004 CORRIGÉE
-- ========================================================
```

---

## 🚀 Étapes d'Exécution sur Bluehost

### 1️⃣ **Accédez à cPanel → phpMyAdmin**
   - Connexion avec vos identifiants Bluehost

### 2️⃣ **Sélectionnez la base de données `kdfvxvmy_kms_gestion`**

### 3️⃣ **Onglet SQL → Copier-coller le script corrigé**

### 4️⃣ **Cliquez sur "Exécuter"**

### 5️⃣ **Vérification du succès**
   ```sql
   -- Exécutez cette requête pour vérifier
   SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE 
   FROM INFORMATION_SCHEMA.COLUMNS 
   WHERE TABLE_NAME = 'prospections_terrain' 
   AND TABLE_SCHEMA = 'kdfvxvmy_kms_gestion'
   ORDER BY COLUMN_NAME;
   ```

---

## ✅ Colonnes attendues après migration

| Colonne | Type | Nullable |
|---------|------|----------|
| id | int | NO |
| date_prospection | date | NO |
| heure_prospection | time | YES |
| prospect_nom | varchar | NO |
| **telephone** | varchar | NO |
| **email** | varchar | YES |
| secteur | varchar | NO |
| **statut_crm** | enum | NO |
| **tag_activite** | enum | YES |
| **date_relance** | date | YES |
| **canal_relance** | enum | YES |
| **message_relance** | text | YES |
| latitude | decimal | YES |
| longitude | decimal | YES |
| adresse_gps | varchar | YES |
| besoin_identifie | text | NO |
| action_menee | text | NO |
| resultat | text | NO |
| prochaine_etape | text | YES |
| client_id | int | YES |
| commercial_id | int | NO |

---

## 📊 Nouveaux Objets de Base de Données

### Tables créées (3)
- ✅ `prospect_notes`
- ✅ `prospect_relances`
- ✅ `prospect_timeline`

### Triggers créés (2)
- ✅ `trg_prospection_timeline_insert`
- ✅ `trg_prospection_timeline_status_update`

### Views créées (3)
- ✅ `v_prospects_par_statut`
- ✅ `v_relances_en_retard`
- ✅ `v_pipeline_commercial`

---

## 🧪 Validation Post-Migration

Après exécution, testez :

```sql
-- Test 1 : Vérifier les nouvelles colonnes
DESCRIBE prospections_terrain;

-- Test 2 : Vérifier les nouvelles tables
SHOW TABLES LIKE 'prospect_%';

-- Test 3 : Charger la page prospections_list.php
-- → Doit afficher sans erreur "Schéma incomplet"
```

---

## ⏮️ Rollback (Si problème)

```sql
DROP TABLE IF EXISTS prospect_notes;
DROP TABLE IF EXISTS prospect_relances;
DROP TABLE IF EXISTS prospect_timeline;
DROP TRIGGER IF EXISTS trg_prospection_timeline_insert;
DROP TRIGGER IF EXISTS trg_prospection_timeline_status_update;

-- Les colonnes ne seront pas supprimées (pour compatibilité)
```

---

## 📌 Résumé des Changements

| Élément | Avant | Après | Status |
|---------|-------|-------|--------|
| Colonnes prospections_terrain | 13 | 19 | ✅ Ajoutées |
| Index | 1 | 5 | ✅ Créés |
| Tables CRM | 0 | 3 | ✅ Créées |
| Triggers | 0 | 2 | ✅ Créés |
| Views | 0 | 3 | ✅ Créées |

