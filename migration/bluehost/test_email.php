<?php
/**
 * Test d'envoi d'email SMTP - KMS Gestion
 * 
 * Exécutez ce script pour tester la configuration email sur Bluehost
 * URL: https://votre-domaine.com/migration/bluehost/test_email.php
 * 
 * ⚠️ SUPPRIMEZ CE FICHIER APRÈS LE TEST EN PRODUCTION
 */

require_once __DIR__ . '/../../lib/email_config.php';

// Vérifier si c'est une requête de test
$testEmail = $_GET['email'] ?? '';
$action = $_GET['action'] ?? '';

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Email SMTP - KMS Gestion</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .card { background: #f8f9fa; border-radius: 8px; padding: 20px; margin: 20px 0; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; }
        input[type="email"] { padding: 10px; width: 300px; margin: 10px 0; }
        button { background: #0d6efd; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background: #0b5ed7; }
        pre { background: #333; color: #0f0; padding: 15px; border-radius: 5px; overflow-x: auto; }
        h1 { color: #333; }
        .warning { color: #856404; background: #fff3cd; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>🔧 Test Configuration Email SMTP</h1>
    
    <div class="card info">
        <h3>📧 Configuration actuelle</h3>
        <ul>
            <li><strong>SMTP Activé:</strong> <?= EMAIL_SMTP_ENABLED ? '✅ Oui' : '❌ Non (mail() natif)' ?></li>
            <li><strong>Serveur:</strong> <?= EMAIL_SMTP_HOST ?></li>
            <li><strong>Port:</strong> <?= EMAIL_SMTP_PORT ?></li>
            <li><strong>Sécurité:</strong> <?= strtoupper(EMAIL_SMTP_SECURE) ?></li>
            <li><strong>Authentification:</strong> <?= EMAIL_SMTP_AUTH ? 'Oui' : 'Non' ?></li>
            <li><strong>Expéditeur:</strong> <?= EMAIL_FROM_NAME ?> &lt;<?= EMAIL_FROM_ADDRESS ?>&gt;</li>
        </ul>
    </div>

    <?php
    if ($action === 'test_connection') {
        echo '<div class="card">';
        echo '<h3>🔌 Test de connexion SMTP</h3>';
        $result = EmailSender::testConnection();
        if ($result['success']) {
            echo '<div class="card success">✅ ' . htmlspecialchars($result['message']) . '</div>';
        } else {
            echo '<div class="card error">❌ ' . htmlspecialchars($result['message']) . '</div>';
        }
        echo '</div>';
    }
    
    if ($action === 'send_test' && !empty($testEmail)) {
        echo '<div class="card">';
        echo '<h3>📨 Envoi de l\'email de test</h3>';
        
        $subject = "Test SMTP KMS Gestion - " . date('d/m/Y H:i:s');
        $htmlBody = "
        <html>
        <body style='font-family: Arial, sans-serif;'>
            <h2 style='color: #0d6efd;'>✅ Test SMTP Réussi!</h2>
            <p>Cet email confirme que la configuration SMTP de KMS Gestion fonctionne correctement.</p>
            <p><strong>Serveur:</strong> " . EMAIL_SMTP_HOST . "</p>
            <p><strong>Port:</strong> " . EMAIL_SMTP_PORT . "</p>
            <p><strong>Date:</strong> " . date('d/m/Y H:i:s') . "</p>
            <hr>
            <p style='color: #6c757d; font-size: 12px;'>KMS Gestion - Kenne Multi-Services</p>
        </body>
        </html>
        ";
        
        $result = EmailSender::send($testEmail, $subject, $htmlBody);
        
        if ($result['success']) {
            echo '<div class="card success">';
            echo '✅ Email envoyé avec succès à <strong>' . htmlspecialchars($testEmail) . '</strong>';
            echo '<p>Vérifiez votre boîte de réception (et les spams).</p>';
            echo '</div>';
        } else {
            echo '<div class="card error">';
            echo '❌ Échec de l\'envoi: ' . htmlspecialchars($result['message']);
            echo '</div>';
        }
        echo '</div>';
    }
    ?>
    
    <div class="card">
        <h3>🧪 Tests disponibles</h3>
        
        <p><a href="?action=test_connection"><button type="button">1. Tester la connexion SMTP</button></a></p>
        
        <form method="get" style="margin-top: 20px;">
            <input type="hidden" name="action" value="send_test">
            <label for="email"><strong>2. Envoyer un email de test:</strong></label><br>
            <input type="email" name="email" id="email" placeholder="votre@email.com" required>
            <button type="submit">Envoyer</button>
        </form>
    </div>
    
    <div class="warning">
        <strong>⚠️ Sécurité:</strong> Supprimez ce fichier après vos tests en production !<br>
        <code>rm migration/bluehost/test_email.php</code>
    </div>
    
    <div class="card">
        <h3>📋 En cas de problème</h3>
        <ol>
            <li>Vérifiez que l'email SMTP existe dans cPanel > Email Accounts</li>
            <li>Vérifiez le mot de passe de cet email</li>
            <li>Essayez le port 587 avec TLS si le port 465 ne fonctionne pas</li>
            <li>Sur Bluehost, essayez <code>localhost</code> comme serveur SMTP</li>
            <li>Vérifiez les logs d'erreurs PHP dans cPanel > Error Log</li>
        </ol>
        
        <h4>Configuration alternative (port 587 TLS):</h4>
        <pre>
define('EMAIL_SMTP_HOST', 'localhost');
define('EMAIL_SMTP_PORT', 587);
define('EMAIL_SMTP_SECURE', 'tls');
        </pre>
    </div>
</body>
</html>
