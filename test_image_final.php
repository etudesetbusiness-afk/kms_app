<?php
// Test en contexte réel
require 'security.php';
require 'catalogue/controllers/catalogue_controller.php';

global $pdo;

echo "=== TEST CATALOGUE_IMAGE_PATH - FINAL ===\n\n";

$stmt = $pdo->query('
    SELECT designation, image_principale 
    FROM catalogue_produits 
    WHERE image_principale IS NOT NULL 
    LIMIT 3
');

$count_ok = 0;
$count_fail = 0;

foreach ($stmt->fetchAll() as $r) {
    echo "Produit: " . $r['designation'] . "\n";
    echo "  Image (DB): " . $r['image_principale'] . "\n";
    
    $result = catalogue_image_path($r['image_principale']);
    echo "  Path généré: " . $result . "\n";
    
    if (strpos($result, 'uploads/') !== false) {
        echo "  ✓ OK - Image trouvée\n";
        $count_ok++;
    } else {
        echo "  ✗ FAIL - Placeholder utilisé\n";
        $count_fail++;
    }
    echo "\n";
}

echo "=== RÉSULTATS ===\n";
echo "✓ OK: $count_ok\n";
echo "✗ FAIL: $count_fail\n";
?>
