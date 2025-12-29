<?php
/**
 * Désactivation 2FA (production) pour l'utilisateur 'admin'
 * 
 * USAGE (Production): https://app.kennemulti-services.com/admin_disable_2fa.php?confirm=DISABLE_ADMIN_2FA_NOW
 * 
 * Sécurité minimale: nécessite le paramètre confirm ci-dessus.
 * ⚠️ Supprimez ce fichier immédiatement après exécution réussie.
 */

// Vérifier confirmation explicite
if (!isset($_GET['confirm']) || $_GET['confirm'] !== 'DISABLE_ADMIN_2FA_NOW') {
    http_response_code(400);
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Désactiver 2FA Admin - Confirmation requise</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 720px; margin: 48px auto; padding: 0 16px; }
            .card { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; border-radius: 8px; padding: 20px; }
            code { background: #f5f5f5; padding: 2px 6px; border-radius: 4px; }
            a.btn { display: inline-block; margin-top: 12px; background: #dc3545; color: #fff; padding: 10px 16px; border-radius: 6px; text-decoration: none; }
        </style>
    </head>
    <body>
        <div class="card">
            <h1>Confirmation requise</h1>
            <p>Pour désactiver la 2FA du compte <strong>admin</strong> en production, ajoutez le paramètre :</p>
            <p><code>?confirm=DISABLE_ADMIN_2FA_NOW</code></p>
            <p>
                Exemple: <code>https://app.kennemulti-services.com/admin_disable_2fa.php?confirm=DISABLE_ADMIN_2FA_NOW</code>
            </p>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Connexion à la base PRODUCTION (Bluehost)
try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=kdfvxvmy_kms_gestion;charset=utf8mb4',
        'kdfvxvmy_WPEUF',
        'adminKMs_app#2025',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo '❌ Erreur de connexion à la base de données: ' . htmlspecialchars($e->getMessage());
    exit;
}

// Trouver l'utilisateur admin
$stmt = $pdo->prepare('SELECT id, login FROM utilisateurs WHERE login = :login LIMIT 1');
$stmt->execute([':login' => 'admin']);
$admin = $stmt->fetch();

if (!$admin) {
    http_response_code(404);
    echo '❌ Utilisateur "admin" introuvable.';
    exit;
}

// Désactiver 2FA pour admin, si enregistrement existe
$pdo->beginTransaction();
try {
    // Désactiver éventuelle 2FA active
    $upd = $pdo->prepare('UPDATE utilisateurs_2fa SET actif = 0, date_desactivation = NOW() WHERE utilisateur_id = :uid');
    $upd->execute([':uid' => $admin['id']]);

    // Nettoyer codes SMS 2FA éventuels
    if ($pdo->query("SHOW TABLES LIKE 'sms_2fa_codes'")->fetch()) {
        $delSms = $pdo->prepare('DELETE FROM sms_2fa_codes WHERE utilisateur_id = :uid');
        $delSms->execute([':uid' => $admin['id']]);
    }

    // Assouplir règle globale si activée (optionnel mais recommandé)
    if ($pdo->query("SHOW TABLES LIKE 'parametres_securite'")->fetch()) {
        $ps = $pdo->prepare("UPDATE parametres_securite SET valeur='0' WHERE cle IN ('2fa_obligatoire_admin','2fa_obligatoire_tous')");
        $ps->execute();
    }

    $pdo->commit();
} catch (Throwable $ex) {
    $pdo->rollBack();
    http_response_code(500);
    echo '❌ Échec désactivation 2FA admin: ' . htmlspecialchars($ex->getMessage());
    exit;
}

// Réponse HTML
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>2FA Admin désactivée</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 720px; margin: 48px auto; padding: 0 16px; }
        .ok { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; border-radius: 8px; padding: 20px; }
        code { background: #f5f5f5; padding: 2px 6px; border-radius: 4px; }
        a.btn { display: inline-block; margin-top: 12px; background: #155724; color: #fff; padding: 10px 16px; border-radius: 6px; text-decoration: none; }
    </style>
    </head>
<body>
    <div class="ok">
        <h1>✅ 2FA désactivée pour le compte admin</h1>
        <p>Vous pouvez maintenant vous connecter avec <strong>admin</strong> sans code 2FA.</p>
        <p>
            Étapes suivantes:
            <ol>
                <li>Connectez-vous avec le compte <strong>admin</strong>.</li>
                <li>Vérifiez les paramètres 2FA dans l'application.</li>
                <li><strong>Supprimez ce fichier immédiatement:</strong> <code>admin_disable_2fa.php</code></li>
            </ol>
        </p>
        <p><a class="btn" href="https://app.kennemulti-services.com/">Aller à l'accueil</a></p>
    </div>
</body>
</html>
