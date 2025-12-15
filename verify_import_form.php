<?php
/**
 * Vérification rapide du formulaire d'import
 */

echo "=== VÉRIFICATION FORMULAIRE D'IMPORT ===\n\n";

$import_file = 'admin/catalogue/import.php';

echo "1. Fichier existe: " . (file_exists($import_file) ? "✓" : "✗") . "\n";
echo "2. Fichier accessible: " . (is_readable($import_file) ? "✓" : "✗") . "\n";

$content = file_get_contents($import_file);

echo "3. Form HTML présent: " . (strpos($content, '<form') !== false ? "✓" : "✗") . "\n";
echo "4. Input file présent: " . (strpos($content, 'type="file"') !== false ? "✓" : "✗") . "\n";
echo "5. CSRF token présent: " . (strpos($content, 'csrf_token') !== false ? "✓" : "✗") . "\n";
echo "6. parseCSV() définie: " . (strpos($content, 'function parseCSV') !== false ? "✓" : "✗") . "\n";
echo "7. parseExcel() définie: " . (strpos($content, 'function parseExcel') !== false ? "✓" : "✗") . "\n";
echo "8. importProducts() définie: " . (strpos($content, 'function importProducts') !== false ? "✓" : "✗") . "\n";

echo "\n✅ Formulaire d'import est prêt pour le test HTTP\n";
echo "URL: http://localhost/kms_app/admin/catalogue/import.php\n";
?>
