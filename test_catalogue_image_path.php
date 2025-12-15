<?php
// Simuler un contexte HTTP
$_SERVER['DOCUMENT_ROOT'] = 'C:\\xampp\\htdocs';
$_SERVER['HTTP_HOST'] = 'localhost';

require 'security.php';
require 'catalogue/controllers/catalogue_controller.php';

global $pdo;

echo "=== TEST catalogue_image_path() avec DOCUMENT_ROOT ===\n\n";
echo "DOCUMENT_ROOT: " . $_SERVER['DOCUMENT_ROOT'] . "\n\n";

$stmt = $pdo->query('
    SELECT id, designation, image_principale, slug 
    FROM catalogue_produits 
    WHERE image_principale IS NOT NULL 
    LIMIT 5
');

$rows = $stmt->fetchAll();

foreach ($rows as $r) {
    echo "Produit: " . $r['designation'] . "\n";
    echo "  Image (DB): " . $r['image_principale'] . "\n";
    
    // Appeler la fonction
    $path = catalogue_image_path($r['image_principale']);
    echo "  Path généré: " . $path . "\n";
    
    // Vérifier
    $full_path = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($path, '/');
    echo "  Chemin absolu: " . $full_path . "\n";
    echo "  Fichier existe: " . (file_exists($full_path) ? "OUI ✓" : "NON ✗") . "\n";
    
    // Vérifier le chemin expected
    $expected = 'uploads/catalogue/' . $r['image_principale'];
    echo "  Expected path: " . url_for($expected) . "\n";
    echo "\n";
}
?>
