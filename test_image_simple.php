<?php
// Simuler HTTP
$_SERVER['DOCUMENT_ROOT'] = 'C:\\xampp\\htdocs';

// Charger minimal
$pdo = new PDO('mysql:host=localhost;dbname=kms_gestion', 'root', '');

echo "=== Test image path ===\n\n";

// Fonction catalogue_image_path copiée pour test
function catalogue_image_path(?string $path): string
{
    if (!$path) {
        return '/kms_app/assets/img/logo-kms.png';
    }
    
    // Si le chemin contient déjà "uploads/", c'est un chemin complet
    if (strpos($path, 'uploads/') !== false) {
        $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($path, '/');
        echo "  Checking (uploads case): " . $fullPath . "\n";
        if (file_exists($fullPath)) {
            return $path;
        }
    } else {
        // Sinon, c'est juste le nom du fichier, construire le chemin complet
        $uploadPath = 'uploads/catalogue/' . $path;
        $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/' . $uploadPath;
        echo "  Checking (filename case): " . $fullPath . "\n";
        if (file_exists($fullPath)) {
            return $uploadPath;
        }
    }
    
    return '/kms_app/assets/img/logo-kms.png';
}

$stmt = $pdo->query('
    SELECT designation, image_principale 
    FROM catalogue_produits 
    WHERE image_principale IS NOT NULL 
    LIMIT 2
');

foreach ($stmt->fetchAll() as $r) {
    echo "Produit: " . $r['designation'] . "\n";
    echo "  Image: " . $r['image_principale'] . "\n";
    
    $result = catalogue_image_path($r['image_principale']);
    echo "  Result: " . $result . "\n";
    echo "  Is valid path: " . (strpos($result, 'uploads/') !== false ? "YES" : "NO (placeholder)") . "\n\n";
}
?>
