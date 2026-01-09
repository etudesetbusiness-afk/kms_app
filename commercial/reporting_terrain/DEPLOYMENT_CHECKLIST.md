# 📦 CHECKLIST DE DÉPLOIEMENT

## Fichiers à transférer sur Bluehost

### ✅ Étape 1 : Préparer les fichiers locaux

Cochez chaque fichier avant de le transférer :

```
□ commercial/reporting_terrain/index.php          (8 KB)
□ commercial/reporting_terrain/create.php         (15 KB)
□ commercial/reporting_terrain/store.php          (9 KB)
□ commercial/reporting_terrain/show.php           (17 KB)
□ commercial/reporting_terrain/print.php          (18 KB)
□ commercial/reporting_terrain/README.md          (3 KB)
□ commercial/reporting_terrain/DEPLOYMENT_GUIDE.md (5 KB)

□ partials/sidebar.php                            (REMPLACER fichier existant)

□ db/migrations/003_terrain_reporting.sql         (4 KB)
```

### ✅ Étape 2 : Transfert FTP

#### Connexion FTP
```
Hôte :      ftp.kennemulti-services.com (ou votre domaine)
Port :      21
Utilisateur: [votre login FTP Bluehost]
Mot de passe: [votre mot de passe FTP]
```

#### Arborescence cible sur le serveur
```
/home/[user]/public_html/kms_app/
│
├── commercial/
│   └── reporting_terrain/          ← CRÉER CE DOSSIER
│       ├── index.php
│       ├── create.php
│       ├── store.php
│       ├── show.php
│       ├── print.php
│       ├── README.md
│       └── DEPLOYMENT_GUIDE.md
│
├── partials/
│   └── sidebar.php                 ← REMPLACER
│
└── db/
    └── migrations/
        └── 003_terrain_reporting.sql
```

### ✅ Étape 3 : Exécution SQL

**Base de données :** `kms_gestion` (ou votre nom de BDD)

**Accès :** cPanel → phpMyAdmin → Onglet SQL

**Script à exécuter :**
```
Copier-coller INTÉGRALEMENT le contenu de :
db/migrations/003_terrain_reporting.sql
```

**Tables créées (vérification) :**
```
□ terrain_reporting
□ terrain_reporting_activite
□ terrain_reporting_arguments
□ terrain_reporting_objections
□ terrain_reporting_plan_action
□ terrain_reporting_resultats
□ terrain_reporting_zones
```

### ✅ Étape 4 : Tests post-déploiement

**URL de test :**
```
https://[votre-domaine]/kms_app/commercial/reporting_terrain/
```

**Tests à effectuer :**
```
□ Page de liste accessible (index.php)
□ Bouton "Nouveau Reporting" fonctionne
□ Formulaire s'affiche avec 9 sections
□ Soumission du formulaire OK
□ Affichage du détail OK
□ Bouton "Imprimer" ouvre la vue imprimable
□ Lien "Reporting terrain" visible dans sidebar (section Commercial)
```

### ✅ Permissions fichiers (recommandées)

```
Dossiers : 755 (rwxr-xr-x)
Fichiers : 644 (rw-r--r--)
```

---

## 🚀 Commandes rapides (si SSH disponible)

Si vous avez accès SSH sur Bluehost :

```bash
# 1. Se connecter
ssh user@kennemulti-services.com

# 2. Naviguer vers l'app
cd ~/public_html/kms_app

# 3. Créer le dossier
mkdir -p commercial/reporting_terrain

# 4. Uploader les fichiers
# (via SFTP ou scp depuis votre PC)

# 5. Importer la migration SQL
mysql -u [db_user] -p[db_pass] kms_gestion < db/migrations/003_terrain_reporting.sql

# 6. Vérifier les tables
mysql -u [db_user] -p[db_pass] kms_gestion -e "SHOW TABLES LIKE 'terrain_reporting%';"
```

---

## 📝 Notes importantes

1. **Sauvegarde avant déploiement**
   - Faire un backup de la BDD via phpMyAdmin (Export)
   - Sauvegarder `partials/sidebar.php` original

2. **Cache**
   - Vider le cache navigateur après déploiement (Ctrl+Shift+R)
   - Se déconnecter/reconnecter à l'application

3. **Permissions utilisateur**
   - Le module nécessite la permission `VENTES_LIRE`
   - Vérifier que vos utilisateurs ont cette permission

---

**Déploiement préparé le :** 9 janvier 2026  
**Prêt pour :** Production Bluehost
