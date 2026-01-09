# 🔧 GUIDE EXÉCUTION MIGRATION 004 - BLUEHOST

## 🚨 SITUATION ACTUELLE

**Erreur reçue :**
```
Schéma incomplet : colonnes manquantes sur prospections_terrain 
(statut_crm, tag_activite, date_relance, canal_relance, message_relance, telephone, email)
```

**Cause :** La migration CRM n'a pas été exécutée sur la base Bluehost.

**Solution :** Exécuter la migration `004_prospections_crm.sql` via phpMyAdmin

---

## 📝 ÉTAPE 1 : PRÉPARER LE SCRIPT SQL

### 1.1 Sur votre PC local

**Fichier source :**
```
c:\xampp\htdocs\kms_app\migration\004_prospections_crm.sql
```

### 1.2 Copier le script complet

Ci-dessous : **LE SCRIPT COMPLET À EXÉCUTER** (copier-coller directement dans phpMyAdmin)

```sql
-- ========================================================
-- MIGRATION 004 : REFONTE PROSPECTIONS TERRAIN → CRM
-- Date : 2025-12-16
-- ========================================================

-- ÉTAPE 1 : ALTER TABLE prospections_terrain
ALTER TABLE `prospections_terrain`
ADD COLUMN `heure_prospection` TIME NULL DEFAULT NULL AFTER `date_prospection`,
ADD COLUMN `telephone` VARCHAR(20) NOT NULL AFTER `prospect_nom`,
ADD COLUMN `email` VARCHAR(150) NULL DEFAULT NULL AFTER `telephone`,
ADD COLUMN `statut_crm` ENUM(
    'PROSPECT',
    'INTERESSE',
    'PROSPECT_CHAUD',
    'DEVIS_DEMANDE',
    'DEVIS_EMIS',
    'COMMANDE_OBTENUE',
    'CLIENT_ACTIF',
    'FIDELISATION',
    'PERDU'
) NOT NULL DEFAULT 'PROSPECT' AFTER `prochaine_etape`,
ADD COLUMN `tag_activite` ENUM('QUINCAILLERIE', 'MENUISERIE', 'AUTRE') NULL DEFAULT NULL AFTER `statut_crm`,
ADD COLUMN `date_relance` DATE NULL DEFAULT NULL AFTER `tag_activite`,
ADD COLUMN `canal_relance` ENUM('WHATSAPP', 'APPEL', 'SMS', 'EMAIL', 'VISITE') NULL DEFAULT NULL AFTER `date_relance`,
ADD COLUMN `message_relance` TEXT NULL DEFAULT NULL AFTER `canal_relance`,
ADD COLUMN `latitude` DECIMAL(10,8) NULL DEFAULT NULL AFTER `message_relance`,
ADD COLUMN `longitude` DECIMAL(11,8) NULL DEFAULT NULL AFTER `latitude`,
ADD COLUMN `adresse_gps` TEXT NULL DEFAULT NULL AFTER `longitude`,
ADD COLUMN `date_creation` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `commercial_id`,
ADD COLUMN `date_modification` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `date_creation`;

-- Créer indices pour optimisation
CREATE INDEX idx_telephone ON `prospections_terrain` (`telephone`);
CREATE INDEX idx_statut_crm ON `prospections_terrain` (`statut_crm`);
CREATE INDEX idx_date_relance ON `prospections_terrain` (`date_relance`);
CREATE INDEX idx_commercial_id ON `prospections_terrain` (`commercial_id`);

-- ÉTAPE 2 : TABLE prospect_notes
CREATE TABLE IF NOT EXISTS `prospect_notes` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `prospection_id` INT(10) UNSIGNED NOT NULL,
  `utilisateur_id` INT(10) UNSIGNED NOT NULL,
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

-- ÉTAPE 3 : TABLE prospect_relances
CREATE TABLE IF NOT EXISTS `prospect_relances` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `prospection_id` INT(10) UNSIGNED NOT NULL,
  `utilisateur_id` INT(10) UNSIGNED NOT NULL,
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

-- ÉTAPE 4 : TABLE prospect_timeline
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

-- ÉTAPE 5 : Triggers
DELIMITER $$

DROP TRIGGER IF EXISTS `trg_prospection_timeline_insert`$$
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
    CONCAT('Prospect créé par ', (SELECT nom FROM utilisateurs WHERE id = NEW.commercial_id LIMIT 1)),
    NEW.statut_crm,
    NEW.date_creation
  );
END$$

DROP TRIGGER IF EXISTS `trg_prospection_timeline_status_update`$$
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

-- ÉTAPE 6 : Vues
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
  u.nom AS commercial
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

-- ========================================================
-- ✅ MIGRATION 004 COMPLÈTEMENT EXÉCUTÉE
-- ========================================================
```

---

## 🌐 ÉTAPE 2 : EXÉCUTER SUR BLUEHOST (phpMyAdmin)

### 2.1 Accéder à phpMyAdmin

1. **Connexion cPanel Bluehost**
   ```
   Allez sur : https://votre-domaine.com:2083
   Username : votre login cPanel
   Password : votre mot de passe
   ```

2. **Accéder phpMyAdmin**
   ```
   Cliquez sur "phpMyAdmin" (dans le sidebar)
   ```

3. **Sélectionner base de données**
   ```
   À gauche : Cliquez sur votre base "kms_gestion"
   ```

### 2.2 Exécuter le script

1. **Aller à l'onglet SQL**
   ```
   En haut : Cliquez sur "SQL"
   ```

2. **Copier-coller le script**
   ```
   Copiez TOUT le script SQL (section "ÉTAPE 1 : PRÉPARER LE SCRIPT")
   Collez dans la grande zone de texte (requête)
   ```

3. **Exécuter**
   ```
   Cliquez sur le bouton "Exécuter" (en bas à droite)
   ```

4. **Vérifier le succès**
   ```
   ✅ Attendez le message : "Query executed successfully" ou "OK"
   ```

---

## ✅ ÉTAPE 3 : VÉRIFICATIONS POST-MIGRATION

### 3.1 Vérifier les colonnes ajoutées

**Dans phpMyAdmin :**

1. **Onglet "Structure"**
   ```
   Allez sur l'onglet "Structure"
   Cherchez la table : prospections_terrain
   Cliquez dessus pour voir les colonnes
   ```

2. **Vérifier les 10 colonnes ajoutées**
   ```
   ✓ heure_prospection (TIME)
   ✓ telephone (VARCHAR 20) 
   ✓ email (VARCHAR 150)
   ✓ statut_crm (ENUM)
   ✓ tag_activite (ENUM)
   ✓ date_relance (DATE)
   ✓ canal_relance (ENUM)
   ✓ message_relance (TEXT)
   ✓ latitude (DECIMAL)
   ✓ longitude (DECIMAL)
   ✓ adresse_gps (TEXT)
   ✓ date_creation (DATETIME)
   ✓ date_modification (DATETIME)
   ```

### 3.2 Vérifier les 3 nouvelles tables

**Requête de vérification :**

```sql
SHOW TABLES LIKE 'prospect_%';
```

**Résultat attendu :**
```
prospect_notes
prospect_relances
prospect_timeline
```

### 3.3 Vérifier les 3 vues

**Requête :**

```sql
SHOW FULL TABLES WHERE TABLE_TYPE LIKE 'VIEW';
```

**Résultat attendu (contient) :**
```
v_prospects_par_statut
v_relances_en_retard
v_pipeline_commercial
```

### 3.4 Vérifier les triggers

**Requête :**

```sql
SHOW TRIGGERS LIKE 'prospections_terrain';
```

**Résultat attendu :**
```
trg_prospection_timeline_insert
trg_prospection_timeline_status_update
```

---

## 🧪 ÉTAPE 4 : TEST FINAL

### 4.1 Rafraîchir la page d'erreur

1. **Accédez au site en production**
   ```
   https://votre-domaine.com/kms_app/terrain/prospections_list.php
   ```

2. **Rafraîchir (Ctrl+F5)**
   ```
   La page doit charger SANS l'erreur "Schéma incomplet"
   ```

### 4.2 Vérifier la liste prospections

1. **Page doit afficher**
   ```
   ✓ Liste des prospections
   ✓ Colonnes visibles (téléphone, email, statut_crm, etc.)
   ✓ Filtres fonctionnels
   ✓ Aucune erreur PHP
   ```

### 4.3 Tester nouveau prospect

1. **Créer un prospect test**
   ```
   Cliquez sur "Nouveau prospect"
   Remplissez le formulaire
   Enregistrez
   ```

2. **Vérifier dans phpMyAdmin**
   ```
   Requête : SELECT * FROM prospections_terrain WHERE id = [votre_id];
   Vérifiez que les nouveaux champs sont remplis (telephone, statut_crm = 'PROSPECT')
   ```

---

## 📊 RÉSUMÉ MIGRATION 004

### Quoi a été ajouté ?

| Type | Nombre | Détail |
|------|--------|--------|
| **Colonnes** | 13 | Sur table prospections_terrain |
| **Index** | 4 | Pour optimiser recherches |
| **Tables** | 3 | prospect_notes, prospect_relances, prospect_timeline |
| **Vues** | 3 | Pour dashboards & reporting |
| **Triggers** | 2 | Auto-logging actions sur prospects |

### Nouvelles colonnes

**Identification contact :**
- `telephone` (VARCHAR 20) - Obligatoire
- `email` (VARCHAR 150) - Optionnel

**CRM & Suivi :**
- `statut_crm` (ENUM) - Prospect → Client Actif
- `tag_activite` (ENUM) - Quincaillerie / Menuiserie / Autre
- `heure_prospection` (TIME) - Heure de visite

**Relances :**
- `date_relance` (DATE) - Quand relancer ?
- `canal_relance` (ENUM) - WhatsApp / Appel / SMS / Email / Visite
- `message_relance` (TEXT) - Message à transmettre

**Localisation :**
- `latitude` (DECIMAL)
- `longitude` (DECIMAL)
- `adresse_gps` (TEXT)

**Traçabilité :**
- `date_creation` (DATETIME) - Quand créé ?
- `date_modification` (DATETIME) - Dernière modif

### Avantages

✅ **Telephones & Emails** : Contactabilité améliorée  
✅ **Statut CRM** : Pipeline commercial visible  
✅ **Relances** : Planification systématique  
✅ **Timeline** : Historique complet de chaque prospect  
✅ **Localisation GPS** : Cartographie commerciale future  
✅ **Auto-logs** : Traçabilité action via triggers  

---

## 🚨 EN CAS D'ERREUR

### Erreur : "Column already exists"

**Cause :** Colonnes déjà présentes (migration partiellement appliquée)

**Solution :**
```sql
-- Vérifier quelles colonnes existent
DESCRIBE prospections_terrain;

-- Puis modifier le script pour ajouter SEULEMENT les colonnes manquantes
ALTER TABLE prospections_terrain
ADD COLUMN [COLONNE_MANQUANTE] [TYPE] DEFAULT [VALEUR];
```

### Erreur : "Foreign key constraint"

**Cause :** Table utilisateurs n'existe pas

**Solution :**
```sql
-- Vérifier existence table
SHOW TABLES LIKE 'utilisateurs';

-- Si manquante : restaurer à partir de kms_gestion.sql
```

### Erreur : "Syntax error"

**Cause :** Copie-colle incomplète du script

**Solution :**
```
1. Vérifier que TOUT le script est copié (du BEGIN au END)
2. Chercher les caractères spéciaux mal encodés
3. Réessayer avec le script fourni ci-dessus
```

---

## 📋 CHECKLIST POST-MIGRATION

- [ ] Script SQL exécuté sans erreur
- [ ] 13 colonnes vérifiées sur prospections_terrain
- [ ] 3 tables créées (prospect_notes, prospect_relances, prospect_timeline)
- [ ] 4 index créés
- [ ] 2 triggers actifs
- [ ] 3 vues disponibles
- [ ] Page prospections_list.php charge sans erreur
- [ ] Nouveau prospect peut être créé
- [ ] Téléphone & email remplissables
- [ ] Statut CRM visible & modifiable
- [ ] Pas d'erreur "Schéma incomplet" ❌

---

## 📞 SUPPORT

**Si problème persiste :**

1. **Relire cette procédure** (⬆️ point manquant ?)
2. **Vérifier les logs** : cPanel → Errors
3. **Contact support Bluehost** : Joindre le fichier `MIGRATION_004_BLUEHOST.txt` (copie de ce script)

---

**✅ Migration 004 prête pour Bluehost**  
Date : 9 janvier 2026
