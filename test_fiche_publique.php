<?php
/**
 * test_fiche_publique.php
 * Test de la fiche publique pour vérifier que les images s'affichent
 */

session_start();

// Pas besoin d'authentification pour accéder au catalogue public
// mais il faut les fonctions de sécurité

require_once 'security.php';
require_once 'catalogue/controllers/catalogue_controller.php';

global $pdo;

// Récupérer un produit avec image
$stmt = $pdo->query('
    SELECT id, slug, designation, image_principale 
    FROM catalogue_produits 
    WHERE image_principale IS NOT NULL 
    LIMIT 1
');

$produit = $stmt->fetch();

if (!$produit) {
    echo "❌ Aucun produit avec image trouvé\n";
    exit;
}

echo "=== TEST FICHE PUBLIQUE ===\n\n";
echo "Produit testée: " . $produit['designation'] . "\n";
echo "Slug: " . $produit['slug'] . "\n";
echo "Image (DB): " . $produit['image_principale'] . "\n";

// Tester la fonction catalogue_image_path
$imgPath = catalogue_image_path($produit['image_principale']);
echo "Image path généré: " . $imgPath . "\n";

// Vérifier l'URL
$url = url_for('catalogue/fiche.php?slug=' . urlencode($produit['slug']));
echo "\nURL de la fiche: " . $url . "\n";

// Vérifier que l'image sera bien chargée
$status = strpos($imgPath, 'uploads/') !== false ? "✓ Image trouvée" : "✗ Placeholder";
echo "Status: " . $status . "\n";

echo "\n=== HTML qui sera généré ===\n";
echo "<img src=\"" . htmlspecialchars($imgPath) . "\" alt=\"" . htmlspecialchars($produit['designation']) . "\">\n";
?>
