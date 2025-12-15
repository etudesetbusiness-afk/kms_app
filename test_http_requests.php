<?php
/**
 * test_http_requests.php
 * Simule les requêtes HTTP exactes comme un navigateur
 */

// Créer une session persistante
session_start();

// Initialiser si première visite
if (!isset($_SESSION['initialized'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['utilisateur'] = [
        'id' => 1,
        'login' => 'admin',
        'nom_complet' => 'Administrateur',
        'email' => 'admin@test.com'
    ];
    
    $_SESSION['permissions'] = [
        'PRODUITS_LIRE',
        'PRODUITS_CREER',
        'PRODUITS_MODIFIER',
        'PRODUITS_SUPPRIMER',
    ];
    
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['initialized'] = true;
}

// Récupérer le token pour les formulaires
$csrf = $_SESSION['csrf_token'];

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Tests HTTP Module Catalogue</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .test { margin: 15px 0; padding: 15px; border: 1px solid #ddd; background: white; border-radius: 5px; }
        .pass { background: #d4edda; border-color: #28a745; }
        .fail { background: #f8d7da; border-color: #dc3545; }
        h2 { color: #333; border-bottom: 2px solid #0066cc; padding-bottom: 10px; }
        button { padding: 10px 15px; margin: 5px; background: #0066cc; color: white; border: none; border-radius: 3px; cursor: pointer; }
        button:hover { background: #004499; }
        input[type="text"], input[type="number"], input[type="file"] { padding: 5px; margin: 5px 0; }
        form { margin: 10px 0; padding: 10px; background: #f9f9f9; border-left: 3px solid #0066cc; }
        .result { margin-top: 10px; padding: 10px; background: #fffacd; border-left: 3px solid #ffc107; }
    </style>
</head>
<body>

<h1>🔧 Tests HTTP Module Catalogue</h1>

<div class="test pass">
    <h2>État Session</h2>
    <p><strong>Utilisateur:</strong> <?= $_SESSION['utilisateur']['login'] ?></p>
    <p><strong>CSRF Token:</strong> <code><?= substr($csrf, 0, 30) ?>...</code></p>
    <p><strong>Permissions:</strong> <?= implode(', ', $_SESSION['permissions']) ?></p>
</div>

<h2>Test 1: Créer une Catégorie (POST)</h2>
<div class="test">
    <form method="POST" action="admin/catalogue/categories.php">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
        <input type="hidden" name="action" value="create">
        
        <label>Nom de la catégorie:</label><br>
        <input type="text" name="nom" value="Test <?= date('His') ?>" required><br>
        
        <label>Ordre:</label><br>
        <input type="number" name="ordre" value="1"><br>
        
        <label>
            <input type="checkbox" name="actif" checked>
            Actif
        </label><br>
        
        <button type="submit">Créer Catégorie</button>
    </form>
    <div class="result">
        <em>Cette requête POST testera:</em>
        <ul>
            <li>Vérification CSRF</li>
            <li>Validation du formulaire</li>
            <li>Insertion en base de données</li>
            <li>Génération du slug</li>
            <li>Redirection</li>
        </ul>
    </div>
</div>

<h2>Test 2: Voir la Page Catégories (GET)</h2>
<div class="test">
    <a href="admin/catalogue/categories.php" target="_blank"><button type="button">Ouvrir Page Catégories</button></a>
    <div class="result">
        <em>Cette requête GET testera:</em>
        <ul>
            <li>Chargement de la page</li>
            <li>Affichage du tableau des catégories</li>
            <li>Visibilité des boutons modifier/supprimer</li>
            <li>Modals (créer, éditer)</li>
            <li>Affichage du formulaire de suppression caché</li>
        </ul>
    </div>
</div>

<h2>Test 3: Voir la Page Produits (GET)</h2>
<div class="test">
    <a href="admin/catalogue/produits.php" target="_blank"><button type="button">Ouvrir Page Produits</button></a>
    <div class="result">
        <em>Cette requête GET testera:</em>
        <ul>
            <li>Chargement de la liste des produits</li>
            <li>Filtres et tri</li>
            <li>Pagination</li>
            <li>Boutons créer/modifier/voir/supprimer</li>
        </ul>
    </div>
</div>

<h2>Test 4: Créer un Produit (GET formulaire)</h2>
<div class="test">
    <a href="admin/catalogue/produit_edit.php" target="_blank"><button type="button">Ouvrir Formulaire Création Produit</button></a>
    <div class="result">
        <em>Cette requête GET testera:</em>
        <ul>
            <li>Chargement du formulaire vide</li>
            <li>Population du dropdown catégories</li>
            <li>Champs dynamiques (caractéristiques)</li>
            <li>Upload d'image</li>
        </ul>
    </div>
</div>

<h2>Test 5: Supprimer Produit (POST simulé)</h2>
<div class="test">
    <p><strong>Avant de tester:</strong> Créez un produit, puis récupérez son ID et testez la suppression.</p>
    
    <form method="POST" action="admin/catalogue/produit_delete.php">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
        
        <label>ID du produit à supprimer:</label><br>
        <input type="number" name="id" placeholder="Entrez l'ID du produit" required><br>
        
        <button type="submit">Supprimer Produit</button>
    </form>
    <div class="result">
        <em>Cette requête POST testera:</em>
        <ul>
            <li>Vérification CSRF</li>
            <li>Suppression des images physiques</li>
            <li>Suppression du produit en base</li>
            <li>Message de succès</li>
            <li>Redirection</li>
        </ul>
    </div>
</div>

<h2>Résultats Attendus</h2>

<div class="test">
    <h3>✓ Catégories</h3>
    <ul>
        <li>Création: Redirection vers la page catégories + message succès</li>
        <li>Édition: Modal s'ouvre, modification sauvegardée</li>
        <li>Suppression: Confirmation popup, puis suppression</li>
    </ul>
    
    <h3>✓ Produits</h3>
    <ul>
        <li>Liste: Affichage tous les produits avec boutons d'action</li>
        <li>Création: Formulaire charge, tous champs visibles</li>
        <li>Édition: Données pré-remplies, modification fonctionne</li>
        <li>Suppression: Images supprimées, produit enlevé</li>
    </ul>
    
    <h3>✓ Sécurité</h3>
    <ul>
        <li>Pas d'erreur 400 CSRF</li>
        <li>Pas d'erreur de permission</li>
        <li>Redirects avec messages flash</li>
    </ul>
</div>

<h2>Notes de Debug</h2>

<div style="background: #fff3cd; border: 1px solid #ffc107; padding: 10px; margin: 10px 0; border-radius: 3px;">
    <strong>Si une erreur se produit:</strong><br>
    1. Vérifiez la console du navigateur (F12 → Console)<br>
    2. Vérifiez le code de statut HTTP (F12 → Réseau)<br>
    3. Cherchez des erreurs PHP dans <code>/logs</code> ou XAMPP Apache logs<br>
    4. Vérifiez que <code>permissions</code> et <code>csrf_token</code> sont en session<br>
</div>

</body>
</html>
