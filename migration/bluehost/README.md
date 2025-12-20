# Migration KMS Gestion vers Bluehost

## 📋 Vue d'ensemble

Ce dossier contient les fichiers nécessaires pour migrer la base de données `kms_gestion` vers un hébergement mutualisé Bluehost (ou tout autre hébergement cPanel).

### Problème résolu
Les hébergements mutualisés n'ont pas les privilèges `SUPER` ou `SET_USER_ID` nécessaires pour :
- Créer des procédures stockées avec `DEFINER`
- Créer des triggers
- Créer des vues avec `SQL SECURITY DEFINER`

Ce script de migration génère une version compatible de votre base de données.

---

## 📁 Fichiers du dossier

| Fichier | Description |
|---------|-------------|
| `generate_migration.php` | Script PHP qui génère le fichier SQL compatible |
| `migration_kms_gestion.sql` | Fichier SQL généré (prêt à importer) |
| `db.php` | Configuration BDD prête pour Bluehost |
| `email_config.php` | Configuration SMTP pour les emails |
| `test_email.php` | Script de test d'envoi email |
| `README.md` | Ce fichier d'instructions |
| `config-db-migration.php.example` | Exemple de configuration de connexion |

---

## 🚀 Étapes de migration

### Étape 1 : Générer le fichier SQL compatible

Exécutez le script de génération depuis votre terminal local :

```bash
cd c:\xampp\htdocs\kms_app\migration\bluehost
php generate_migration.php
```

Ou via le navigateur :
```
http://localhost/kms_app/migration/bluehost/generate_migration.php
```

Cela créera le fichier `migration_kms_gestion.sql`.

### Étape 2 : Créer la base de données sur Bluehost

1. Connectez-vous à votre **cPanel Bluehost**
2. Allez dans **MySQL Databases**
3. Créez une nouvelle base de données (ex: `votrecompte_kms`)
4. Créez un nouvel utilisateur MySQL avec un mot de passe fort
5. **Attribuez TOUS les privilèges** à cet utilisateur sur la base de données

> ⚠️ **Important** : Notez le nom complet de la base (préfixe inclus), l'utilisateur et le mot de passe.

### Étape 3 : Importer le fichier SQL

#### Option A : Via phpMyAdmin (recommandé pour fichiers < 50 Mo)

1. Dans cPanel, ouvrez **phpMyAdmin**
2. Sélectionnez votre base de données dans le panneau gauche
3. Cliquez sur l'onglet **Import**
4. Sélectionnez le fichier `migration_kms_gestion.sql`
5. Laissez les paramètres par défaut et cliquez **Go**

#### Option B : Via SSH (pour fichiers volumineux)

```bash
mysql -u votrecompte_utilisateur -p votrecompte_kms < migration_kms_gestion.sql
```

#### Option C : Via File Manager + import fractionné

Si le fichier est trop volumineux :
1. Uploadez le fichier SQL via **File Manager**
2. Utilisez un outil comme **BigDump** pour l'import fractionné

### Étape 4 : Configurer la connexion dans le projet

Copiez le fichier `db.php` de ce dossier vers `db/db.php` sur le serveur, ou modifiez avec ces identifiants :

```php
<?php
// db/db.php - Configuration Bluehost KMS
$host     = 'localhost';
$dbname   = 'kdfvxvmy_kms_gestion';
$username = 'kdfvxvmy_WPEUF';
$password = 'adminKMs_app#2025';
$charset  = 'utf8mb4';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=$charset",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données: " . $e->getMessage());
}
```

### Étape 5 : Uploader les fichiers du projet

1. Via **File Manager** ou **FTP**, uploadez tout le contenu du projet dans `public_html/` (ou un sous-dossier)
2. Assurez-vous que les permissions sont correctes (755 pour les dossiers, 644 pour les fichiers)
3. Le fichier `.htaccess` doit être présent à la racine

### Étape 6 : Configurer l'envoi d'emails (2FA)

L'authentification 2FA par email nécessite une configuration SMTP sur Bluehost.

1. **Créez un compte email dans cPanel** > Email Accounts (ex: `admin@kennemulti-services.com`)
2. **Copiez le fichier** `migration/bluehost/email_config.php` vers `lib/email_config.php`
3. **Modifiez les identifiants** dans `lib/email_config.php` :

```php
define('EMAIL_SMTP_HOST', 'mail.kennemulti-services.com');
define('EMAIL_SMTP_USERNAME', 'admin@kennemulti-services.com');
define('EMAIL_SMTP_PASSWORD', 'votre_mot_de_passe_email');
define('EMAIL_FROM_ADDRESS', 'admin@kennemulti-services.com');
```

4. **Testez l'envoi** en accédant à : `https://votre-domaine.com/migration/bluehost/test_email.php`
5. **Supprimez le fichier de test** après validation : `rm migration/bluehost/test_email.php`

> 💡 **Astuce Bluehost** : Si le port 465 (SSL) ne fonctionne pas, essayez le port 587 (TLS) avec `localhost` comme serveur.

---

## ⚠️ Éléments supprimés et alternatives

### Procédures stockées supprimées

| Procédure | Alternative PHP |
|-----------|-----------------|
| `cleanup_sms_codes` | Créer un fichier `lib/cleanup_sms.php` appelé par un CRON |

**Fichier alternatif à créer** : `lib/cleanup_sms.php`

```php
<?php
// lib/cleanup_sms.php - Nettoyage des codes SMS expirés
// À appeler via CRON : php /home/user/public_html/lib/cleanup_sms.php

require_once __DIR__ . '/../db/db.php';

// Supprimer les codes expirés (plus de 1 jour)
$pdo->exec("DELETE FROM sms_2fa_codes WHERE expire_a < DATE_SUB(NOW(), INTERVAL 1 DAY)");

// Supprimer les anciens logs de tracking (plus de 30 jours)
$pdo->exec("DELETE FROM sms_tracking WHERE envoye_a < DATE_SUB(NOW(), INTERVAL 30 DAY)");

echo "Nettoyage SMS effectué: " . date('Y-m-d H:i:s') . "\n";
```

**CRON à configurer dans cPanel** :
```
0 3 * * * /usr/local/bin/php /home/votrecompte/public_html/lib/cleanup_sms.php
```

### Triggers supprimés

Les triggers suivants ont été supprimés car ils ne sont pas compatibles :

| Trigger | Table | Alternative |
|---------|-------|-------------|
| `after_inscription_formation_insert` | inscriptions_formation | Géré dans le contrôleur PHP |
| `after_inscription_formation_update` | inscriptions_formation | Géré dans le contrôleur PHP |
| `after_reservation_hotel_insert` | reservations_hotel | Géré dans le contrôleur PHP |
| `after_reservation_hotel_update` | reservations_hotel | Géré dans le contrôleur PHP |

**Note** : Ces triggers ajoutaient automatiquement des entrées dans `caisse_journal`. Cette logique doit être intégrée directement dans les fichiers PHP concernés.

### Vues modifiées

Les vues suivantes ont été nettoyées (DEFINER supprimé) :

- `v_pipeline_commercial`
- `v_ventes_livraison_encaissement`

Elles fonctionneront normalement après l'import.

---

## 📝 Fichiers du projet pouvant nécessiter des modifications

### Fichiers de connexion base de données

| Fichier | Modification requise |
|---------|---------------------|
| `db/db.php` | **OUI** - Mettre à jour les identifiants de connexion |
| `security.php` | Non - Utilise déjà `$pdo` de db.php |

### Fichiers potentiellement affectés par les triggers manquants

| Fichier | Action recommandée |
|---------|-------------------|
| Contrôleurs formations | Ajouter l'insertion dans caisse_journal après création/modification d'inscription |
| Contrôleurs hôtel | Ajouter l'insertion dans caisse_journal après création/modification de réservation |

---

## ✅ Vérification post-migration

Après l'import, vérifiez les points suivants :

1. **Connexion** : Testez la page de login
2. **Dashboard** : Vérifiez que les statistiques s'affichent
3. **Listes** : Testez l'affichage des ventes, clients, produits
4. **Création** : Testez la création d'une vente
5. **Vues** : Vérifiez que les pages utilisant les vues fonctionnent

---

## 🆘 Dépannage

### Erreur "Access denied" persistante
- Vérifiez que l'utilisateur a bien TOUS les privilèges sur la base
- Vérifiez le nom de la base (avec préfixe)

### Erreur "Table doesn't exist"
- L'import n'est peut-être pas complet
- Réimportez le fichier SQL

### Erreur de charset
- Assurez-vous que la base est en `utf8mb4_unicode_ci`
- Vérifiez que `SET NAMES utf8mb4` est bien exécuté

### Fichier SQL trop volumineux
- Utilisez l'import SSH ou un outil de fractionnement
- Contactez le support Bluehost pour augmenter les limites

---

## 📞 Support

En cas de problème, vérifiez :
1. Les logs d'erreurs PHP dans cPanel
2. Les logs MySQL dans phpMyAdmin
3. La documentation Bluehost pour les spécificités de votre plan
