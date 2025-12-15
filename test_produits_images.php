<?php
require 'security.php';

global $pdo;

echo "=== Produits avec images ===\n\n";

$stmt = $pdo->query('
    SELECT id, designation, image_principale, slug 
    FROM catalogue_produits 
    WHERE image_principale IS NOT NULL 
    LIMIT 5
');

$rows = $stmt->fetchAll();

if (empty($rows)) {
    echo "Aucun produit avec image trouvé.\n";
} else {
    foreach ($rows as $r) {
        echo "Produit: " . $r['designation'] . "\n";
        echo "  Image DB: " . $r['image_principale'] . "\n";
        echo "  Slug: " . $r['slug'] . "\n";
        
        // Vérifier si le fichier existe
        $filepath = __DIR__ . '/uploads/catalogue/' . $r['image_principale'];
        echo "  Fichier existe: " . (file_exists($filepath) ? "OUI" : "NON") . "\n";
        echo "\n";
    }
}
?>
