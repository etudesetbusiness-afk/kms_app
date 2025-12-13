# 🔐 Guide d'Installation - Sécurité & Performance

## Vue d'ensemble

Ce guide couvre l'installation et la configuration de :
- ✅ **Redis** (Cache & Rate Limiting)
- ✅ **2FA** (Authentification à deux facteurs)
- ✅ **Rate Limiting** (Protection contre les abus)
- ✅ **Audit avancé** (Logs de sécurité)

---

## 📋 Prérequis

- PHP 8.0+ avec extension `redis`
- Base de données MySQL/MariaDB configurée
- XAMPP installé (Windows)

---

## 🚀 Installation Étape par Étape

### 1. Installation de Redis sur XAMPP Windows

#### Option A: Installation manuelle

```powershell
# Télécharger Redis pour Windows
# https://github.com/microsoftarchive/redis/releases

# Extraire dans C:\xampp\redis\
# Copier le fichier de configuration
Copy-Item config\redis.conf C:\xampp\redis\redis.conf

# Lancer Redis
cd C:\xampp\redis
.\redis-server.exe redis.conf
```

#### Option B: Via Chocolatey

```powershell
# Installer Chocolatey si pas déjà fait
# Puis installer Redis
choco install redis-64

# Démarrer le service
redis-server
```

#### Vérifier l'installation

```powershell
# Tester la connexion
redis-cli ping
# Devrait retourner : PONG
```

---

### 2. Installation de l'extension PHP Redis

#### Vérifier si l'extension est déjà installée

```powershell
php -m | findstr redis
```

#### Si non installé

1. **Télécharger** l'extension PHP Redis pour Windows :
   - https://windows.php.net/downloads/pecl/releases/redis/
   - Choisir la version correspondant à votre PHP (voir `php -v`)
   - Architecture: x64 ou x86
   - Thread Safe (TS) pour XAMPP

2. **Extraire** `php_redis.dll` dans `C:\xampp\php\ext\`

3. **Activer** dans `C:\xampp\php\php.ini` :
   ```ini
   extension=redis
   ```

4. **Redémarrer** Apache depuis XAMPP Control Panel

5. **Vérifier** :
   ```powershell
   php -m | findstr redis
   ```

---

### 3. Configuration de Redis pour KMS

#### Créer le fichier .env (optionnel)

```bash
# C:\xampp\htdocs\kms_app\.env
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=
```

#### Ou configurer directement dans db/db.php

```php
// Ajouter après la connexion PDO
putenv('REDIS_HOST=127.0.0.1');
putenv('REDIS_PORT=6379');
putenv('REDIS_PASSWORD='); // Vide pour dev local
```

---

### 4. Migration de la Base de Données

Exécuter le script de migration pour créer les tables 2FA et audit :

```powershell
# Via phpMyAdmin
# Importer le fichier : db/migrations/002_security_enhancements.sql

# Ou via ligne de commande
cd C:\xampp\htdocs\kms_app
C:\xampp\mysql\bin\mysql.exe -u root kms_gestion < db\migrations\002_security_enhancements.sql
```

#### Vérifier les tables créées

```sql
SHOW TABLES LIKE '%2fa%';
SHOW TABLES LIKE '%audit%';
SHOW TABLES LIKE '%sessions%';
```

Tables attendues :
- `utilisateurs_2fa`
- `utilisateurs_2fa_recovery`
- `sessions_actives`
- `audit_log`
- `tentatives_connexion`
- `blocages_ip`
- `parametres_securite`

---

### 5. Activation du Nouveau Login

Remplacer l'ancien `login.php` par la nouvelle version :

```powershell
cd C:\xampp\htdocs\kms_app

# Backup de l'ancien
Move-Item login.php login_old_backup.php

# Activer le nouveau
Move-Item login_new.php login.php
```

---

### 6. Test de l'Installation

#### Test 1: Redis fonctionne

```powershell
# Dans PowerShell
php -r "var_dump(class_exists('Redis'));"
# Devrait retourner : bool(true)
```

#### Test 2: Connexion au système

1. Ouvrir http://localhost/kms_app/login.php
2. Se connecter avec `admin` / `admin123`
3. Vérifier : connexion réussie sans erreur

#### Test 3: Rate Limiting

Essayer de se connecter 6 fois avec un mauvais mot de passe :
- ✅ Après la 5ème tentative, message de blocage devrait apparaître

#### Test 4: 2FA Setup

1. Se connecter en tant qu'admin
2. Aller sur http://localhost/kms_app/utilisateurs/2fa.php
3. Cliquer sur "Activer le 2FA"
4. Scanner le QR code avec Google Authenticator
5. Entrer le code à 6 chiffres
6. ✅ Codes de récupération doivent s'afficher

#### Test 5: Connexion avec 2FA

1. Se déconnecter
2. Se reconnecter
3. ✅ Devrait demander le code 2FA après le mot de passe

---

## 🎯 Configuration Post-Installation

### Réglages de sécurité

Modifier dans la base de données (`parametres_securite`) :

```sql
-- Forcer 2FA pour tous les admins
UPDATE parametres_securite 
SET valeur = '1' 
WHERE cle = '2fa_obligatoire_admin';

-- Durée de session : 2 heures
UPDATE parametres_securite 
SET valeur = '120' 
WHERE cle = 'session_timeout_minutes';

-- Max 3 sessions simultanées par utilisateur
UPDATE parametres_securite 
SET valeur = '3' 
WHERE cle = 'max_sessions_simultanees';
```

---

### Warmup du Cache

Pré-charger les données fréquentes :

```php
<?php
// Créer : tools/cache_warmup.php
require_once __DIR__ . '/../db/db.php';
require_once __DIR__ . '/../lib/cache_helper.php';

$result = CacheHelper::warmup($pdo);
echo json_encode($result, JSON_PRETTY_PRINT);
```

Exécuter :
```powershell
php tools\cache_warmup.php
```

---

### Configurer un Cron pour le Nettoyage

#### Windows (Planificateur de tâches)

Créer une tâche qui exécute quotidiennement :

```batch
@echo off
REM cleanup_security.bat
cd C:\xampp\htdocs\kms_app
php tools\cleanup_security.php
```

---

## 📊 Monitoring & Logs

### Vérifier les logs

```powershell
# Logs Redis
type C:\xampp\redis\redis.log

# Logs Rate Limiting
type C:\xampp\htdocs\kms_app\logs\rate_limit_abuse.log

# Logs PHP
type C:\xampp\php\logs\php_error_log
```

### Consulter les tentatives de connexion

```sql
-- Dernières tentatives échouées
SELECT * FROM tentatives_connexion 
WHERE succes = 0 
ORDER BY date_tentative DESC 
LIMIT 20;

-- IP bloquées
SELECT * FROM blocages_ip 
WHERE actif = 1;
```

---

## 🔧 Dépannage

### Problème: Redis ne démarre pas

```powershell
# Vérifier si le port 6379 est déjà utilisé
netstat -ano | findstr :6379

# Tuer le processus si nécessaire
taskkill /PID <PID> /F

# Relancer
redis-server C:\xampp\redis\redis.conf
```

### Problème: Extension Redis non trouvée

```powershell
# Vérifier la version PHP
php -v

# Vérifier l'architecture
php -i | findstr Architecture

# Télécharger la bonne version de php_redis.dll
# Vérifier dans php.ini que la ligne n'est pas commentée
# Redémarrer Apache
```

### Problème: QR Code 2FA ne s'affiche pas

- Vérifier la connexion internet (l'API qrserver.com doit être accessible)
- Alternative : Copier manuellement la clé secrète dans l'app

### Problème: "Session expirée" en étape 2FA

- Normal après 5 minutes
- Se reconnecter et réessayer

---

## 🎓 Utilisation du 2FA

### Pour activer le 2FA :

1. **Télécharger** une app d'authentification :
   - Google Authenticator (iOS/Android)
   - Microsoft Authenticator
   - Authy (multi-plateforme)

2. **Activer** depuis le profil utilisateur

3. **Scanner** le QR code ou entrer manuellement la clé

4. **Sauvegarder** les codes de récupération en lieu sûr

### Pour se connecter avec 2FA :

1. Entrer login + mot de passe
2. Ouvrir l'app d'authentification
3. Entrer le code à 6 chiffres (renouvelé toutes les 30 secondes)

### En cas de perte du téléphone :

- Utiliser un des 10 codes de récupération (usage unique)
- Contacter l'administrateur pour réinitialiser

---

## 📈 Performance & Optimisation

### Statistiques de cache

Créer une page admin pour voir les stats :

```php
<?php
require_once 'security.php';
require_once 'lib/cache_helper.php';
exigerPermission('ADMIN');

$stats = CacheHelper::getStats();
print_r($stats);
```

### Invalider le cache manuellement

```php
<?php
// Tout le cache
CacheHelper::flush();

// Un produit spécifique
CacheHelper::invalidateProduit(123);

// Tous les clients
CacheHelper::invalidateClients();
```

---

## ✅ Checklist de Production

Avant déploiement en production :

- [ ] Redis installé et sécurisé avec mot de passe
- [ ] Extension PHP Redis activée
- [ ] Tables de migration créées
- [ ] 2FA activé pour tous les administrateurs
- [ ] Rate limiting testé
- [ ] Logs d'audit fonctionnels
- [ ] Backup de la configuration Redis
- [ ] Monitoring en place
- [ ] Documentation utilisateur fournie

---

## 🆘 Support

En cas de problème :

1. Vérifier les logs (voir section Monitoring)
2. Consulter la documentation Redis : https://redis.io/docs/
3. Vérifier les issues GitHub du projet
4. Contacter l'administrateur système

---

## 📚 Ressources Complémentaires

- [Documentation Redis](https://redis.io/documentation)
- [PHP Redis Extension](https://github.com/phpredis/phpredis)
- [Google Authenticator](https://support.google.com/accounts/answer/1066447)
- [TOTP RFC 6238](https://tools.ietf.org/html/rfc6238)
- [OWASP Authentication Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Authentication_Cheat_Sheet.html)

---

**Date:** 2025-12-13  
**Version:** 1.0  
**Auteur:** KMS Dev Team
