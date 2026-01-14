# 📋 Guide de Déploiement - Modifications Reporting Terrain
**Date:** 12 janvier 2026  
**Environnement cible:** Bluehost (Production)  
**Durée estimée:** 15-20 minutes

---

## 📌 AVANT DE COMMENCER

- ✅ Faire une **sauvegarde complète** de la base de données MySQL
- ✅ Faire une **sauvegarde complète** du dossier `/commercial/reporting_terrain/`
- ✅ Tester en environnement de staging si possible
- ✅ Avoir accès à cPanel Bluehost pour accéder à phpMyAdmin

---

## 🔄 ÉTAPES DE DÉPLOIEMENT

### **ÉTAPE 1: Sauvegarder la base de données (CRITIQUE)**

1. Aller sur cPanel Bluehost → **phpMyAdmin**
2. Sélectionner la base de données `kms_gestion`
3. Cliquer sur **Exporter** → **Lancer l'exportation**
4. Enregistrer le fichier `kms_gestion_backup_[DATE].sql`
5. **Garder ce fichier** pour rollback d'urgence

**En cas de problème:** Importer ce fichier via phpMyAdmin pour revenir à l'état initial.

---

### **ÉTAPE 2: Uploader les fichiers PHP (Code)**

Via FTP (FileZilla) ou cPanel File Manager, uploader/remplacer:

#### **Nouveaux fichiers:**
```
/commercial/reporting_terrain/edit.php           [NOUVEAU]
```

#### **Fichiers modifiés:**
```
/commercial/reporting_terrain/create.php         [MODIFIÉ - Section 2]
/commercial/reporting_terrain/store.php          [MODIFIÉ - Gestion brouillon/soumis]
/commercial/reporting_terrain/index.php          [MODIFIÉ - Bouton édition]
/commercial/reporting_terrain/show.php           [MODIFIÉ - Vérif admin]
/commercial/reporting_terrain/print.php          [MODIFIÉ - Vérif admin]
```

**Instructions FTP:**
1. Connecter à `ftp.bluehost.com` (identifiants dans email Bluehost)
2. Naviguer vers `/public_html/kms_app/commercial/reporting_terrain/`
3. **Uploader les fichiers** (remplacer les existants)
4. Vérifier les permissions: `644` pour `.php`, `755` pour dossiers

---

### **ÉTAPE 3: Exécuter les migrations SQL (ORDRE CRITIQUE)**

Via phpMyAdmin sur Bluehost:

#### **Migration 1: Ajouter colonne statut**

1. Aller à cPanel → **phpMyAdmin**
2. Sélectionner base `kms_gestion`
3. Cliquer sur onglet **SQL**
4. **Copier-coller ce code:**

```sql
ALTER TABLE terrain_reporting 
ADD COLUMN statut ENUM('brouillon','soumis') NOT NULL DEFAULT 'soumis' AFTER updated_at;

ALTER TABLE terrain_reporting 
ADD INDEX idx_statut (statut);
```

5. Cliquer **Exécuter** (bouton bleu)
6. Attendre le message "Requête exécutée avec succès"

#### **Migration 2: Modifier type_cible**

1. **Copier-coller ce code dans le même onglet SQL:**

```sql
ALTER TABLE terrain_reporting_zones
MODIFY COLUMN type_cible VARCHAR(255) DEFAULT NULL COMMENT 'Types de cibles séparés par virgules';
```

2. Cliquer **Exécuter**
3. Attendre confirmation

---

### **ÉTAPE 4: Vérifier les modifications en BD**

Via phpMyAdmin, exécuter ces **requêtes de vérification:**

#### **Vérifier colonne statut:**
```sql
SHOW COLUMNS FROM terrain_reporting WHERE Field IN ('statut', 'id');
```
✅ **Résultat attendu:**
```
| Field  | Type                       |
|--------|----------------------------|
| id     | int(10) unsigned           |
| statut | enum('brouillon','soumis') |
```

#### **Vérifier type_cible:**
```sql
SHOW COLUMNS FROM terrain_reporting_zones WHERE Field = 'type_cible';
```
✅ **Résultat attendu:**
```
| Field      | Type         |
|------------|--------------|
| type_cible | varchar(255) |
```

---

### **ÉTAPE 5: Tester en Production**

1. **Ouvrir le navigateur** → `https://votredomaine.com/kms_app/commercial/reporting_terrain/`

2. **Test 1: Créer un nouveau reporting**
   - Cliquer "+ Nouveau reporting"
   - Remplir Section 2 (Zones) → Sélectionner **plusieurs checkboxes** pour un jour
   - Ex: Menuiserie + Quincaillerie
   - Cliquer "Enregistrer (brouillon)"
   - ✅ Doit voir: "Brouillon en édition"

3. **Test 2: Voir le bouton modifier**
   - Retour à la liste
   - ✅ Doit voir: **Icône crayon** sur le reporting brouillon
   - ✅ Rapports soumis = PAS de crayon

4. **Test 3: Éditer le brouillon**
   - Cliquer sur le crayon du brouillon
   - ✅ Doit charger: Toutes les données pré-remplies
   - ✅ Doit afficher: Badge "Brouillon en édition"
   - Modifier 1-2 champs
   - Cliquer "Enregistrer (brouillon)"
   - Retour à show.php
   - ✅ Doit voir: Les modifications sauvegardées

5. **Test 4: Soumettre le brouillon**
   - Ouvrir le brouillon en édition
   - Cliquer "Soumettre"
   - ✅ Doit voir: Message "Reporting modifié et soumis"
   - Retour à la liste
   - ✅ Doit voir: Status changé à "soumis"
   - ✅ Doit voir: PLUS de crayon (verrouillé)

6. **Test 5: Admin imprime n'importe quel rapport**
   - Cliquer sur œil (voir) pour un rapport
   - En haut à droite: Cliquer "Imprimer"
   - ✅ Doit fonctionner pour admin (vérifier logs)

---

## 🔧 FICHIERS DE RÉFÉRENCE

### Fichiers uploadés
Les fichiers suivants doivent être présents dans `/commercial/reporting_terrain/`:

```
create.php          → Formulaire création (checkboxes Section 2)
edit.php            → Formulaire édition (NOUVEAU)
store.php           → Handler création + édition
index.php           → Liste avec bouton éditer
show.php            → Détail rapport
print.php           → Impression rapport
```

### Fichiers de migration (pour archivage)
```
/db/migrations/004_terrain_reporting_statut.sql
/db/migrations/005_terrain_reporting_type_cible.sql
```

---

## 🚨 TROUBLESHOOTING

### ❌ "La colonne statut existe déjà"
→ Vérifier qu'on n'a pas exécuté la migration 2 fois  
→ Exécuter vérification:
```sql
SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME='terrain_reporting' AND COLUMN_NAME='statut';
```

### ❌ "Edit button ne s'affiche pas"
1. Vérifier migration 004 exécutée (colonne statut existe)
2. Vérifier fichier `edit.php` uploadé
3. Marquer UN reporting en brouillon (voir ÉTAPE 6 plus bas)
4. Rafraîchir la page

### ❌ "Les modifications ne sont pas sauvegardées"
1. Vérifier fichier `store.php` uploadé (chercher "isUpdate")
2. Vérifier permissions 644 sur store.php
3. Regarder logs PHP: cPanel → **Metrics** → **Raw Access Logs**

### ❌ "Erreur 500 en accédant à edit.php"
1. Vérifier `<?php` de syntaxe (pas d'erreurs de parsing)
2. Vérifier permissions: 644
3. Vérifier includes: `security.php`, `partials/header.php`
4. Voir error_log Bluehost: `public_html/error_log`

### ✅ Rollback d'urgence
1. cPanel → phpMyAdmin
2. Base `kms_gestion` → Importer
3. Sélectionner fichier `kms_gestion_backup_[DATE].sql`
4. Cliquer Importer
5. Attendre quelques secondes
6. **Base restaurée à l'état initial**

---

## 📊 ÉTAPE 6: Mettre des rapports en brouillon (OPTIONNEL)

Pour tester, convertir des rapports existants en brouillon:

Via phpMyAdmin SQL:
```sql
UPDATE terrain_reporting 
SET statut='brouillon' 
WHERE id IN (SELECT id FROM terrain_reporting ORDER BY id DESC LIMIT 1);
```

Cela marque le dernier reporting en brouillon pour test.

---

## ✅ CHECKLIST FINAL

Avant de déclarer le déploiement réussi:

- [ ] Sauvegarde BD faite ✓
- [ ] Fichiers PHP uploadés (6 fichiers) ✓
- [ ] Migration 004 exécutée (colonne statut) ✓
- [ ] Migration 005 exécutée (type_cible VARCHAR) ✓
- [ ] Vérifications BD passées ✓
- [ ] Nouveau reporting créé = brouillon ✓
- [ ] Bouton édition visible sur brouillon ✓
- [ ] Édition d'un brouillon fonctionne ✓
- [ ] Soumission brouillon fonctionne ✓
- [ ] Rapport soumis = verrouillé ✓
- [ ] Admin peut imprimer tous rapports ✓

---

## 📞 SUPPORT

**En cas de blocage:**
1. Consulter `error_log` Bluehost
2. Vérifier permissions fichiers (644)
3. Vérifier syntaxe PHP locale avant upload
4. Utiliser rollback de la sauvegarde BD

**Contact Bluehost:** 1-888-401-4678 (support technique)

---

## ⏱️ Temps estimé par phase

| Phase | Durée |
|-------|-------|
| Sauvegarde BD | 3 min |
| Upload fichiers FTP | 5 min |
| Exécution migrations | 2 min |
| Tests fonctionnels | 5 min |
| **Total** | **15 min** |

---

**Déploiement préparé le:** 12 janvier 2026  
**Par:** Github Copilot  
**Prêt pour production:** ✅ OUI
