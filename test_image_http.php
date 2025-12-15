<?php
/**
 * test_image_http.php
 * Test avec accès HTTP réel
 */

session_start();
$_SESSION['user_id'] = 1;
$_SESSION['utilisateur'] = ['id' => 1, 'login' => 'admin'];
$_SESSION['permissions'] = ['PRODUITS_LIRE'];
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

require_once 'security.php';
require_once 'catalogue/controllers/catalogue_controller.php';

echo "<h1>🧪 Test Image Path en HTTP</h1>\n";
echo "<p>DOCUMENT_ROOT: " . htmlspecialchars($_SERVER['DOCUMENT_ROOT']) . "</p>\n";
echo "<p>Request URI: " . htmlspecialchars($_SERVER['REQUEST_URI']) . "</p>\n";

// Test avec base de données
global $pdo;

echo "<h2>Produits et leurs images</h2>\n";

$stmt = $pdo->query("
    SELECT id, designation, image_principale, slug 
    FROM catalogue_produits 
    WHERE image_principale IS NOT NULL 
    LIMIT 5
");

$products = $stmt->fetchAll();

if (empty($products)) {
    echo "<p>❌ Aucun produit avec image trouvé. Il faut d'abord en créer un.</p>\n";
} else {
    echo "<table border='1' cellpadding='10'>\n";
    echo "<tr><th>Produit</th><th>Image DB</th><th>Chemin généré</th><th>Fichier existe</th></tr>\n";
    
    foreach ($products as $prod) {
        $img_path = catalogue_image_path($prod['image_principale']);
        $full_path = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($img_path, '/');
        $file_exists = file_exists($full_path) ? "✓ OUI" : "✗ NON";
        
        echo "<tr>";
        echo "<td>" . htmlspecialchars($prod['designation']) . "</td>";
        echo "<td>" . htmlspecialchars($prod['image_principale']) . "</td>";
        echo "<td><a href='" . htmlspecialchars($img_path) . "'>" . htmlspecialchars($img_path) . "</a></td>";
        echo "<td>" . $file_exists . "</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
}

echo "<h2>Liens de test</h2>\n";
echo "<ul>\n";

$stmt = $pdo->query("SELECT id, slug FROM catalogue_produits LIMIT 3");
$products = $stmt->fetchAll();

foreach ($products as $prod) {
    $url = url_for('catalogue/fiche.php?slug=' . urlencode($prod['slug']));
    echo "<li><a href='" . htmlspecialchars($url) . "' target='_blank'>" . htmlspecialchars($prod['slug']) . "</a></li>\n";
}

echo "</ul>\n";
?>
