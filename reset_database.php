<?php
/**
 * Outil de réinitialisation de la base de données KMS Gestion
 * 
 * Exécute le script reset_prod.sql pour vider la base en préservant :
 * - Catalogue (catégories + produits)
 * - Utilisateurs et configuration
 * - Tables de configuration
 * 
 * URL: https://votre-domaine.com/reset_database.php
 * 
 * ⚠️ À SUPPRIMER APRÈS UTILISATION EN PRODUCTION
 */

// Sécurité basique
if (!isset($_GET['confirm']) || $_GET['confirm'] !== 'YES_I_UNDERSTAND_THIS_WILL_RESET_THE_DATABASE') {
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Réinitialiser Base de Données - KMS Gestion</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
            .card { background: #f8f9fa; border-radius: 8px; padding: 20px; margin: 20px 0; border: 1px solid #dee2e6; }
            .warning { background: #fff3cd; border-color: #ffeeba; color: #856404; }
            .danger { background: #f8d7da; border-color: #f5c6cb; color: #721c24; }
            .success { background: #d4edda; border-color: #c3e6cb; }
            button { background: #dc3545; color: white; padding: 15px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; font-weight: bold; }
            button:hover { background: #c82333; }
            h1 { color: #333; }
            table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            th, td { padding: 10px; text-align: left; border-bottom: 1px solid #dee2e6; }
            th { background: #f8f9fa; font-weight: bold; }
            code { background: #f5f5f5; padding: 2px 5px; border-radius: 3px; }
            .checkbox-group { margin: 20px 0; }
            input[type="checkbox"] { margin-right: 10px; }
            label { display: block; margin: 10px 0; }
        </style>
    </head>
    <body>
        <h1>🗑️ Réinitialisation Base de Données - KMS Gestion</h1>
        
        <div class="card danger">
            <h2>⚠️ ATTENTION</h2>
            <p><strong>Cette action va supprimer TOUTES les données opérationnelles :</strong></p>
            <ul>
                <li>Ventes et devis</li>
                <li>Achats et fournisseurs</li>
                <li>Clients et prospects</li>
                <li>Mouvements de stock</li>
                <li>Écritures comptables</li>
                <li>Logs et historiques</li>
                <li>Sessions et préférences</li>
            </ul>
            <p><strong>Cette action ne peut PAS être annulée!</strong></p>
        </div>

        <div class="card success">
            <h3>✅ Données conservées :</h3>
            <ul>
                <li>Catalogue produits (catégories + produits)</li>
                <li>Utilisateurs et rôles/permissions</li>
                <li>Configuration comptable</li>
                <li>Tables de configuration</li>
            </ul>
        </div>

        <div class="card">
            <h3>📋 Étapes de réinitialisation :</h3>
            <ol>
                <li>Désactivation des contraintes FK</li>
                <li>Suppression des données opérationnelles (47 tables)</li>
                <li>Réinitialisation des AUTO_INCREMENT</li>
                <li>Réactivation des contraintes FK</li>
            </ol>
        </div>

        <form method="get" style="margin: 40px 0;">
            <div class="checkbox-group">
                <label>
                    <input type="checkbox" name="confirm_check" value="1" required>
                    Je confirme vouloir réinitialiser la base de données
                </label>
                <label>
                    <input type="checkbox" name="confirm_data" value="1" required>
                    Je suis conscient que TOUTES les données seront supprimées
                </label>
                <label>
                    <input type="checkbox" name="confirm_final" value="1" required>
                    Je ne pourrai pas annuler cette action
                </label>
            </div>
            
            <input type="hidden" name="confirm" value="YES_I_UNDERSTAND_THIS_WILL_RESET_THE_DATABASE">
            <button type="submit">⚠️ RÉINITIALISER LA BASE</button>
        </form>

        <div class="card warning">
            <h3>📞 Support</h3>
            <p>Pour plus d'informations, consultez le fichier <code>reset_prod.sql</code></p>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Vérifier que toutes les confirmations sont présentes
if (!isset($_GET['confirm_check']) || !isset($_GET['confirm_data']) || !isset($_GET['confirm_final'])) {
    die('<h2>❌ Confirmations manquantes</h2><p><a href="reset_database.php">Retour</a></p>');
}

// Lire le script SQL
$sqlFile = __DIR__ . '/reset_prod.sql';
if (!file_exists($sqlFile)) {
    die('<h2>❌ Fichier reset_prod.sql non trouvé</h2>');
}

$sql = file_get_contents($sqlFile);

// Connexion à la base de données
try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=kms_gestion;charset=utf8mb4',
        'root',
        '',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die('<h2>❌ Erreur de connexion :</h2><p>' . htmlspecialchars($e->getMessage()) . '</p>');
}

// Exécuter le script SQL
try {
    // Diviser le script en commandes individuelles
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && strpos($stmt, '--') !== 0;
        }
    );
    
    $count = 0;
    foreach ($statements as $statement) {
        if (!empty(trim($statement))) {
            $pdo->exec($statement);
            $count++;
        }
    }
    
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Réinitialisation Complète</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
            .card { background: #d4edda; border: 1px solid #c3e6cb; border-radius: 8px; padding: 20px; color: #155724; }
            h1 { color: #155724; }
            code { background: #f5f5f5; padding: 10px; display: block; margin: 10px 0; border-radius: 5px; }
        </style>
    </head>
    <body>
        <div class="card">
            <h1>✅ Réinitialisation réussie!</h1>
            <p><strong><?php echo $count; ?> commandes SQL exécutées</strong></p>
            <p>La base de données KMS Gestion est maintenant prête pour un nouveau démarrage.</p>
            
            <h3>📋 Résumé:</h3>
            <ul>
                <li>✅ 47 tables vidées</li>
                <li>✅ AUTO_INCREMENT réinitialisés</li>
                <li>✅ Catalogue conservé</li>
                <li>✅ Utilisateurs conservés</li>
                <li>✅ Schéma inchangé</li>
            </ul>
            
            <h3>🔒 Sécurité</h3>
            <p><strong>Supprimez ce fichier après utilisation :</strong></p>
            <code>rm reset_database.php</code>
            
            <p><a href="index.php">Retour à l'accueil</a></p>
        </div>
    </body>
    </html>
    <?php
    
} catch (Exception $e) {
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <title>Erreur lors de la réinitialisation</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
            .card { background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 8px; padding: 20px; color: #721c24; }
            h1 { color: #721c24; }
        </style>
    </head>
    <body>
        <div class="card">
            <h1>❌ Erreur lors de la réinitialisation</h1>
            <p><strong><?php echo htmlspecialchars($e->getMessage()); ?></strong></p>
            <p><a href="reset_database.php">Réessayer</a></p>
        </div>
    </body>
    </html>
    <?php
}
