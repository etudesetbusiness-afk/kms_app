<?php
/**
 * test_image_path.php
 * Tester la fonction catalogue_image_path
 */

require_once 'security.php';
require_once 'catalogue/controllers/catalogue_controller.php';

echo "=== TEST IMAGE PATH ===\n\n";

// Test 1: Fichier juste nom (cas du bug)
echo "Test 1: Nom de fichier seul (img_123.jpg)\n";
$filename = 'img_' . uniqid() . '.jpg';
echo "  Fichier: $filename\n";
$path = catalogue_image_path($filename);
echo "  Résultat: $path\n";
echo "  Type: " . (strpos($path, 'logo-kms.png') !== false ? "PLACEHOLDER" : "PATH CORRECT") . "\n\n";

// Test 2: Avec chemin uploads/
echo "Test 2: Avec chemin uploads/catalogue/\n";
$filename_with_path = 'uploads/catalogue/img_' . uniqid() . '.jpg';
echo "  Fichier: $filename_with_path\n";
$path = catalogue_image_path($filename_with_path);
echo "  Résultat: $path\n";
echo "  Type: " . (strpos($path, 'logo-kms.png') !== false ? "PLACEHOLDER" : "PATH CORRECT") . "\n\n";

// Test 3: NULL
echo "Test 3: NULL\n";
$path = catalogue_image_path(null);
echo "  Résultat: $path\n";
echo "  Type: " . (strpos($path, 'logo-kms.png') !== false ? "PLACEHOLDER" : "PATH INCORRECT") . "\n\n";

// Test 4: Produit réel de la DB
global $pdo;
echo "Test 4: Produits réels de la base de données\n";
$stmt = $pdo->query("SELECT id, designation, image_principale FROM catalogue_produits LIMIT 3");
$products = $stmt->fetchAll();

foreach ($products as $prod) {
    echo "  Produit: " . $prod['designation'] . "\n";
    echo "    Image DB: " . ($prod['image_principale'] ?? 'NULL') . "\n";
    if ($prod['image_principale']) {
        $path = catalogue_image_path($prod['image_principale']);
        echo "    Path généré: $path\n";
        echo "    Type: " . (strpos($path, 'logo-kms.png') !== false ? "PLACEHOLDER" : "OK") . "\n";
    }
}

echo "\n=== FIN DES TESTS ===\n";
?>
