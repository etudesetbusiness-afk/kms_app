<?php
echo "DOCUMENT_ROOT: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "Script: " . __FILE__ . "\n";
echo "Dossier uploads/catalogue existe: " . (is_dir($_SERVER['DOCUMENT_ROOT'] . '/uploads/catalogue/') ? "OUI" : "NON") . "\n";
echo "Dossier uploads existe: " . (is_dir($_SERVER['DOCUMENT_ROOT'] . '/uploads/') ? "OUI" : "NON") . "\n";

// Lister le contenu de uploads/catalogue
$path = $_SERVER['DOCUMENT_ROOT'] . '/uploads/catalogue/';
if (is_dir($path)) {
    $files = scandir($path);
    echo "\nFichiers dans uploads/catalogue/:\n";
    foreach ($files as $f) {
        if ($f !== '.' && $f !== '..') {
            echo "  - $f\n";
        }
    }
}
?>
