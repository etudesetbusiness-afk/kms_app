<?php
/**
 * test_integration_catalogue.php
 * Test d'intégration du module catalogue (simulation complet de session utilisateur)
 */

session_start();

// Initialiser une session utilisateur admin complète
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['utilisateur'] = [
        'id' => 1,
        'login' => 'admin',
        'nom_complet' => 'Administrateur',
        'email' => 'admin@test.com'
    ];
    
    // Permissions admin complet
    $_SESSION['permissions'] = [
        'PRODUITS_LIRE',
        'PRODUITS_CREER',
        'PRODUITS_MODIFIER',
        'PRODUITS_SUPPRIMER',
        'CATEGORIES_LIRE',
        'CATEGORIES_CREER',
        'CATEGORIES_MODIFIER',
        'CATEGORIES_SUPPRIMER',
    ];
    
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Récupérer les infos de session
$user_connected = isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0;

// Afficher le statut
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test d'Intégration Catalogue</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        .test { margin: 10px 0; padding: 10px; border: 1px solid #ccc; }
        .pass { background: #d4edda; color: #155724; }
        .fail { background: #f8d7da; color: #721c24; }
        .info { background: #d1ecf1; color: #0c5460; }
        h2 { color: #333; }
        code { background: #f4f4f4; padding: 2px 5px; }
        a { color: #0066cc; margin: 5px; }
    </style>
</head>
<body>
<h1>🧪 Test d'Intégration Module Catalogue</h1>

<h2>État de la Session</h2>
<div class="test <?= $user_connected ? 'pass' : 'fail' ?>">
    <strong><?= $user_connected ? '✓' : '✗' ?> Utilisateur connecté</strong><br>
    Utilisateur: <?= $_SESSION['utilisateur']['login'] ?? 'N/A' ?><br>
    ID: <?= $_SESSION['user_id'] ?? 'N/A' ?><br>
    Permissions: <?= count($_SESSION['permissions'] ?? []) ?> activées<br>
    CSRF Token: <?= substr($_SESSION['csrf_token'] ?? '', 0, 20) ?>...
</div>

<h2>Accès aux Pages du Module</h2>

<div class="test info">
    <h3>1. Page Liste Produits</h3>
    <a href="admin/catalogue/produits.php" target="_blank">Accéder à /admin/catalogue/produits.php</a>
    <p><small>Teste: affichage liste, filtres, tri, boutons CRUD</small></p>
</div>

<div class="test info">
    <h3>2. Page Catégories</h3>
    <a href="admin/catalogue/categories.php" target="_blank">Accéder à /admin/catalogue/categories.php</a>
    <p><small>Teste: modals créer/éditer/supprimer, tableau catégories</small></p>
</div>

<div class="test info">
    <h3>3. Créer Produit</h3>
    <a href="admin/catalogue/produit_edit.php" target="_blank">Accéder à /admin/catalogue/produit_edit.php (création)</a>
    <p><small>Teste: formulaire création, validation, upload image</small></p>
</div>

<h2>Tests Techniques</h2>

<?php
require_once 'security.php';
require_once 'lib/pagination.php';

global $pdo;

// Test 1: Connexion PDO
$db_test = false;
try {
    $result = $pdo->query("SELECT 1");
    $db_test = true;
?>
<div class="test pass">
    <strong>✓ Connexion Base de Données</strong><br>
    PDO connecté et fonctionnel
</div>
<?php
} catch (Exception $e) {
?>
<div class="test fail">
    <strong>✗ Connexion Base de Données</strong><br>
    Erreur: <?= $e->getMessage() ?>
</div>
<?php
}

// Test 2: Tables
if ($db_test) {
?>
<div class="test info">
    <strong>Vérification Tables</strong><br>
    <?php
    $tables = ['catalogue_categories', 'catalogue_produits'];
    foreach ($tables as $table) {
        $result = $pdo->query("SHOW TABLES LIKE '$table'")->fetch();
        if ($result) {
            echo "✓ Table <code>$table</code><br>";
        } else {
            echo "✗ Table <code>$table</code> manquante<br>";
        }
    }
    ?>
</div>

<div class="test info">
    <strong>Comptages</strong><br>
    <?php
    $stmt = $pdo->query("SELECT COUNT(*) FROM catalogue_categories");
    $cat_count = $stmt->fetchColumn();
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM catalogue_produits");
    $prod_count = $stmt->fetchColumn();
    
    echo "✓ Catégories: <strong>$cat_count</strong><br>";
    echo "✓ Produits: <strong>$prod_count</strong><br>";
    ?>
</div>

<div class="test pass">
    <strong>✓ Fonctions Critiques</strong><br>
    <?php
    $functions = ['getCsrfToken', 'verifierCsrf', 'peut', 'exigerPermission', 'url_for'];
    foreach ($functions as $func) {
        if (function_exists($func)) {
            echo "✓ <code>$func()</code><br>";
        } else {
            echo "✗ <code>$func()</code> manquante<br>";
        }
    }
    ?>
</div>

<?php
}
?>

<h2>Checklist Fonctionnelle</h2>

<div style="margin: 20px 0;">
    <h3>Catégories</h3>
    <ul>
        <li>[ ] Bouton "Nouvelle Catégorie" visible</li>
        <li>[ ] Modal création s'ouvre</li>
        <li>[ ] Formulaire peut être soumis (sans erreur CSRF)</li>
        <li>[ ] Catégorie créée apparaît dans la liste</li>
        <li>[ ] Bouton modifier (crayon) visible</li>
        <li>[ ] Modal édition s'ouvre avec données pré-remplies</li>
        <li>[ ] Modifications sauvegardées</li>
        <li>[ ] Bouton supprimer (corbeille) visible</li>
        <li>[ ] Confirmation s'affiche</li>
        <li>[ ] Catégorie supprimée de la liste</li>
    </ul>
    
    <h3>Produits</h3>
    <ul>
        <li>[ ] Bouton "Nouveau Produit" visible</li>
        <li>[ ] Formulaire création charge</li>
        <li>[ ] Catégories disponibles dans dropdown</li>
        <li>[ ] Upload image fonctionne</li>
        <li>[ ] Formulaire peut être soumis</li>
        <li>[ ] Produit créé apparaît dans la liste</li>
        <li>[ ] Boutons modifier/supprimer visibles</li>
        <li>[ ] Édition fonctionne</li>
        <li>[ ] Suppression fonctionne avec confirmation</li>
        <li>[ ] Images supprimées lors de la suppression du produit</li>
    </ul>
    
    <h3>Sécurité</h3>
    <ul>
        <li>[ ] Tokens CSRF valides sur tous les formulaires</li>
        <li>[ ] Pas d'erreur 400 "Requête invalide (CSRF)"</li>
        <li>[ ] Permissions vérifiées (si utilisateur sans permission)</li>
        <li>[ ] Redirects fonctionnent correctement</li>
    </ul>
</div>

<h2>Rapports de Bugs à Documenter</h2>

<div style="border: 2px solid red; padding: 10px; margin: 10px 0;">
    <h3>❌ Si vous rencontrez une erreur:</h3>
    <ol>
        <li>Notez l'URL exacte</li>
        <li>Décrivez l'action qui a causé l'erreur</li>
        <li>Copiez le message d'erreur exact</li>
        <li>Vérifiez la console du navigateur (F12 > Console)</li>
        <li>Signalez-le avec ces détails</li>
    </ol>
</div>

</body>
</html>
<?php
// Mise à jour: garder la session active pour les appels suivants
setcookie(session_name(), session_id(), time() + 3600, '/');
?>
