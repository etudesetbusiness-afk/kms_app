# Guide de Déploiement en Production

## 1. Sur le serveur de production (via SSH ou cPanel Terminal)

### Étape 1: Sauvegarder la base de données actuelle
```bash
# Via SSH
mysqldump -u [user] -p kdfvxvmy_kms_gestion > backup_$(date +%Y%m%d_%H%M%S).sql

# Via cPanel: Utiliser phpMyAdmin pour exporter la base
```

### Étape 2: Mettre à jour le code depuis GitHub
```bash
cd /home/kdfvxvmy/public_html
git pull origin main
```

**Si git n'est pas configuré sur le serveur:**
```bash
# Initialiser git (une seule fois)
git init
git remote add origin https://github.com/etudesetbusiness-afk/kms_app.git
git fetch origin
git reset --hard origin/main
```

### Étape 3: Vérifier les permissions des dossiers d'upload
```bash
# Via SSH
chmod 755 uploads/
chmod 755 uploads/catalogue/
chmod 644 uploads/catalogue/*

# Les images doivent être accessibles en lecture par le serveur web
```

**Via cPanel File Manager:**
- Aller dans `uploads/catalogue/`
- Sélectionner le dossier → Permissions → 755
- Sélectionner tous les fichiers → Permissions → 644

### Étape 4: Vérifier la configuration de la base de données
Ouvrir `db/db.php` et s'assurer que les identifiants sont corrects:
```php
$DB_HOST = 'localhost';
$DB_NAME = 'kdfvxvmy_kms_gestion';
$DB_USER = 'kdfvxvmy_[votre_user]';
$DB_PASS = '[votre_mot_de_passe]';
```

### Étape 5: Tester la modification d'un produit
1. Connexion: `https://kennemulti-services.com/admin/catalogue/produits.php`
2. Cliquer sur "Modifier" pour un produit existant
3. Changer la désignation ou uploader une image
4. Enregistrer
5. Vérifier que les modifications apparaissent

### Étape 6: Vérifier les logs en cas de problème
```bash
# Via SSH
tail -f /home/kdfvxvmy/public_html/error_log

# Ou via cPanel: consulter Error Log dans "Metrics"
```

## 2. Vérifications post-déploiement

### ✓ Checklist:
- [ ] Code mis à jour depuis GitHub
- [ ] Permissions correctes sur `/uploads/catalogue/` (755)
- [ ] Fichiers `db/db.php` configuré correctement
- [ ] Test de modification d'un produit catalogue réussi
- [ ] Test d'upload d'image réussi
- [ ] Aucune erreur dans les logs

## 3. En cas de problème

### Problème: "Permission denied" lors de l'upload
**Solution:**
```bash
chmod 755 uploads/catalogue/
chown kdfvxvmy:kdfvxvmy uploads/catalogue/
```

### Problème: "Database connection error"
**Solution:**
- Vérifier que les credentials dans `db/db.php` sont corrects
- Tester la connexion MySQL depuis cPanel → phpMyAdmin

### Problème: Les images ne s'affichent pas
**Solution:**
- Vérifier que les fichiers existent dans `/uploads/catalogue/`
- Vérifier permissions: `chmod 644 uploads/catalogue/*.{jpg,png,webp}`
- Vérifier que le chemin dans la BDD est correct (pas de `/` au début)

### Problème: "Token CSRF invalide"
**Solution:**
- Vider le cache du navigateur (Ctrl+Shift+Del)
- Vérifier que les sessions fonctionnent: dans `php.ini`, `session.save_path` doit être writable

## 4. Commandes Git utiles

```bash
# Voir l'état actuel
git status

# Voir les derniers commits
git log --oneline -10

# Annuler les modifications locales (ATTENTION: perte des changements)
git reset --hard origin/main

# Mettre à jour sans perdre les modifications locales
git stash
git pull origin main
git stash pop
```

## 5. Rollback en cas de problème critique

```bash
# Revenir au commit précédent
git log --oneline -5  # Noter le hash du commit stable
git reset --hard [hash_du_commit]

# Restaurer la base de données
mysql -u [user] -p kdfvxvmy_kms_gestion < backup_YYYYMMDD_HHMMSS.sql
```

## Contact Support
En cas de problème persistant, vérifier:
1. Les logs PHP: `/home/kdfvxvmy/public_html/error_log`
2. Les logs Apache: via cPanel → Metrics → Error Log
3. Les logs applicatifs ajoutés dans `produit_edit.php` (chercher `[produit_edit.php]`)
