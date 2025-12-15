<?php
/**
 * test_import_csv.php
 * Tester l'import CSV
 */

require 'security.php';

global $pdo;

echo "=== TEST IMPORT CSV ===\n\n";

// Simuler le parsing CSV
function parseCSV($filepath) {
    $data = [];
    if (($handle = fopen($filepath, 'r')) !== false) {
        $headers = null;
        $row_num = 0;
        
        while (($row = fgetcsv($handle, 1000, ",")) !== false) {
            $row_num++;
            
            if ($row_num === 1) {
                // Headers
                $headers = array_map('trim', $row);
                continue;
            }
            
            if ($headers) {
                $item = array_combine($headers, $row) ?: [];
                $data[] = $item;
            }
        }
        
        fclose($handle);
    }
    
    return $data;
}

// Tester avec le fichier exemple
$filepath = __DIR__ . '/uploads/exemple_import.csv';

if (!file_exists($filepath)) {
    echo "❌ Fichier non trouvé: $filepath\n";
    exit;
}

$data = parseCSV($filepath);
echo "✓ " . count($data) . " lignes lues\n\n";

// Afficher les données
echo "Aperçu des données:\n";
echo str_pad("Code", 20) . " | " . str_pad("Designation", 30) . " | " . str_pad("Cat", 3) . " | " . str_pad("Prix", 10) . "\n";
echo str_repeat("-", 70) . "\n";

foreach ($data as $row) {
    echo str_pad($row['code'], 20) . " | " . 
         str_pad(substr($row['designation'], 0, 28), 30) . " | " . 
         str_pad($row['categorie_id'], 3) . " | " . 
         str_pad($row['prix_unite'], 10) . "\n";
}

// Test d'insertion
echo "\n=== TEST INSERTION (simulation) ===\n";

$count = 0;
$errors = [];

foreach ($data as $idx => $row) {
    try {
        // Validation minimale
        $code = trim($row['code'] ?? '');
        $designation = trim($row['designation'] ?? '');
        $categorie_id = trim($row['categorie_id'] ?? '1');
        $prix_unite = trim($row['prix_unite'] ?? '0');
        
        if (empty($code) || empty($designation)) {
            $errors[] = "Ligne " . ($idx + 2) . ": Code et Désignation obligatoires";
            continue;
        }
        
        // Vérifier l'unicité du code
        $stmt = $pdo->prepare("SELECT id FROM catalogue_produits WHERE code = ?");
        $stmt->execute([$code]);
        if ($stmt->fetch()) {
            echo "⚠️  Ligne " . ($idx + 2) . ": Code '$code' déjà existant (ignoré)\n";
            continue;
        }
        
        // Générer slug
        $slug = strtolower(str_replace([' ', 'é', 'è', 'ê', 'à', 'â', 'ù'], ['-', 'e', 'e', 'e', 'a', 'a', 'u'], $designation));
        $slug = preg_replace('/[^a-z0-9-]/', '', $slug);
        
        // Vérifier l'unicité du slug
        $stmt = $pdo->prepare("SELECT id FROM catalogue_produits WHERE slug = ?");
        $stmt->execute([$slug]);
        if ($stmt->fetch()) {
            $slug .= '-' . uniqid();
        }
        
        echo "✓ Insérer: $code - $designation (Slug: $slug)\n";
        
        $count++;
    } catch (Exception $e) {
        $errors[] = "Ligne " . ($idx + 2) . ": " . $e->getMessage();
    }
}

echo "\n=== RÉSULTATS ===\n";
echo "✓ À insérer: $count\n";
if (!empty($errors)) {
    echo "❌ Erreurs: " . count($errors) . "\n";
    foreach ($errors as $err) {
        echo "  - $err\n";
    }
}
?>
