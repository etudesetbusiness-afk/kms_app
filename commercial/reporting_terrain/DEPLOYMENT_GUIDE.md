# 📋 Guide de Déploiement – Module Reporting Terrain
## Déploiement sur Bluehost

---

## 🎯 Vue d'ensemble

Ce guide explique comment déployer manuellement le module **Reporting Hebdomadaire Terrain** sur votre application KMS hébergée sur Bluehost.

**Durée estimée :** 15-20 minutes  
**Prérequis :** Accès cPanel Bluehost + identifiants FTP

---

## 📦 ÉTAPE 1 : Préparer les fichiers à transférer

### Fichiers à copier depuis votre PC local

```
📁 Dossier local : C:\xampp\htdocs\kms_app\

Fichiers à transférer :
├── commercial/reporting_terrain/         (DOSSIER COMPLET)
│   ├── index.php
│   ├── create.php
│   ├── store.php
│   ├── show.php
│   ├── print.php
│   └── README.md
│
├── db/migrations/
│   └── 003_terrain_reporting.sql        (FICHIER SQL)
│
└── partials/
    └── sidebar.php                       (FICHIER MODIFIÉ)
```

---

## 🌐 ÉTAPE 2 : Transférer les fichiers via FTP/cPanel

### Option A : Via FileZilla (FTP) — **RECOMMANDÉ**

1. **Ouvrir FileZilla**
   - Hôte : `ftp.kennemulti-services.com` (ou votre domaine)
   - Utilisateur : votre login FTP Bluehost
   - Mot de passe : votre mot de passe FTP
   - Port : `21`

2. **Naviguer vers le dossier racine de l'application**
   ```
   /home/votre_user/public_html/kms_app/
   ```

3. **Créer le dossier du module**
   - Aller dans : `/commercial/`
   - Créer le dossier : `reporting_terrain`

4. **Transférer les fichiers**
   - Glisser-déposer les 5 fichiers PHP + README dans `/commercial/reporting_terrain/`
   - Remplacer `/partials/sidebar.php` par la nouvelle version
   - Copier `003_terrain_reporting.sql` dans `/db/migrations/`

### Option B : Via cPanel File Manager

1. **Se connecter à cPanel Bluehost**
   - URL : `https://votre-domaine.com:2083`
   - Identifiants : email + mot de passe Bluehost

2. **Ouvrir File Manager**
   - Dans cPanel → **Files** → **File Manager**
   - Naviguer vers : `public_html/kms_app/`

3. **Créer le dossier**
   - Aller dans `/commercial/`
   - Clic droit → **New Folder** → Nom : `reporting_terrain`

4. **Upload les fichiers**
   - Entrer dans `/commercial/reporting_terrain/`
   - Cliquer **Upload** (en haut)
   - Glisser-déposer les 6 fichiers du module
   - Répéter pour `sidebar.php` et `003_terrain_reporting.sql`

---

## 🗄️ ÉTAPE 3 : Exécuter la migration SQL

### Via phpMyAdmin (dans cPanel)

1. **Ouvrir phpMyAdmin**
   - cPanel → **Databases** → **phpMyAdmin**

2. **Sélectionner la base de données**
   - Cliquer sur `kms_gestion` (ou votre nom de BDD) dans la colonne de gauche

3. **Exécuter le script SQL**
   - Cliquer sur l'onglet **SQL** en haut
   - Copier-coller le contenu COMPLET du fichier :
     ```
     db/migrations/003_terrain_reporting.sql
     ```
   - Cliquer **Go** (ou **Exécuter**)

4. **Vérifier les tables créées**
   - Cliquer sur **Structure** dans la barre latérale gauche
   - Vous devriez voir 7 nouvelles tables :
     ```
     ✓ terrain_reporting
     ✓ terrain_reporting_activite
     ✓ terrain_reporting_arguments
     ✓ terrain_reporting_objections
     ✓ terrain_reporting_plan_action
     ✓ terrain_reporting_resultats
     ✓ terrain_reporting_zones
     ```

---

## ✅ ÉTAPE 4 : Vérifier le déploiement

### 4.1 Vérifier les fichiers

1. **Via cPanel File Manager**
   - Naviguer vers `/commercial/reporting_terrain/`
   - Vérifier que tous les fichiers sont présents :
     - `index.php` (~8 KB)
     - `create.php` (~15 KB)
     - `store.php` (~9 KB)
     - `show.php` (~17 KB)
     - `print.php` (~18 KB)
     - `README.md`

2. **Vérifier les permissions**
   - Clic droit sur chaque fichier PHP → **Permissions**
   - Recommandé : `644` (rw-r--r--)

### 4.2 Tester le module en ligne

1. **Se connecter à l'application web**
   ```
   https://votre-domaine.com/kms_app/
   ```

2. **Naviguer vers le module**
   - Sidebar → **Commercial** → **Reporting terrain**
   - Ou directement :
     ```
     https://votre-domaine.com/kms_app/commercial/reporting_terrain/
     ```

3. **Tests à effectuer**
   - ✅ La page de liste s'affiche sans erreur
   - ✅ Le bouton "Nouveau Reporting" fonctionne
   - ✅ Le formulaire s'affiche avec les 9 sections accordéon
   - ✅ Les dates sont pré-remplies (semaine courante)
   - ✅ La soumission du formulaire fonctionne
   - ✅ La page de détail s'affiche correctement
   - ✅ Le bouton "Imprimer" ouvre la version imprimable

---

## 🔧 DÉPANNAGE (Troubleshooting)

### Problème : "Page not found" (404)

**Cause :** Fichiers non transférés ou mauvais chemin

**Solution :**
1. Vérifier que le dossier `/commercial/reporting_terrain/` existe
2. Vérifier que `index.php` est bien dans ce dossier
3. Vider le cache du navigateur (Ctrl+Shift+R)

---

### Problème : "Table doesn't exist" (Erreur SQL)

**Cause :** Migration SQL non exécutée

**Solution :**
1. Aller dans phpMyAdmin
2. Vérifier si les tables `terrain_reporting*` existent
3. Si non, exécuter à nouveau le script SQL complet

---

### Problème : "Permission denied" ou erreur d'écriture

**Cause :** Permissions fichiers incorrectes

**Solution :**
1. Via cPanel File Manager
2. Sélectionner tous les fichiers PHP
3. Clic droit → **Change Permissions**
4. Définir : `644` pour les fichiers, `755` pour les dossiers

---

### Problème : Le lien "Reporting terrain" n'apparaît pas dans la sidebar

**Cause :** Fichier `sidebar.php` non remplacé OU cache

**Solution :**
1. Vérifier que `/partials/sidebar.php` a bien été remplacé
2. Se déconnecter puis reconnecter à l'application
3. Vider le cache du navigateur
4. Vérifier que l'utilisateur a la permission `VENTES_LIRE`

---

### Problème : Erreurs PHP affichées

**Cause :** Incompatibilité de version PHP ou erreurs de syntaxe

**Solution :**
1. Vérifier la version PHP dans cPanel :
   - **Software** → **Select PHP Version**
   - Recommandé : **PHP 8.0** ou supérieur
2. Vérifier les logs d'erreurs dans cPanel :
   - **Metrics** → **Errors**
3. Si caractères bizarres (encodage) :
   - Re-transférer les fichiers en mode **binaire** (FileZilla)

---

### Problème : Le formulaire ne soumet pas (rien ne se passe)

**Cause :** Erreur JavaScript ou problème CSRF

**Solution :**
1. Ouvrir la Console du navigateur (F12)
2. Vérifier les erreurs JavaScript
3. Tester dans un autre navigateur
4. Vérifier que `security.php` génère bien les tokens CSRF

---

## 📞 Support

Si vous rencontrez d'autres problèmes :

1. **Vérifier les logs PHP**
   - cPanel → **Metrics** → **Errors**
   - Ou fichier : `/home/user/public_html/error_log`

2. **Vérifier les erreurs MySQL**
   - phpMyAdmin → onglet SQL → copier-coller requête de test :
     ```sql
     SELECT COUNT(*) FROM terrain_reporting;
     ```

3. **Vérifier la connexion DB**
   - Ouvrir `/db/db.php` et vérifier les identifiants Bluehost

---

## 🎉 Déploiement terminé !

Une fois toutes les étapes validées, le module est opérationnel.

**Prochaines étapes :**
- Former les utilisateurs (commerciaux)
- Tester la création d'un reporting complet
- Vérifier l'impression PDF (Ctrl+P)

---

**Date de création :** 9 janvier 2026  
**Version du guide :** 1.0  
**Application :** KMS Gestion – Module Reporting Terrain
