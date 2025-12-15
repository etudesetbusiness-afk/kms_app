<?php
/**
 * test_import_page.php
 * Test la page d'import
 */

session_start();

// Simuler un utilisateur connecté
$_SESSION['user_id'] = 1;
$_SESSION['utilisateur'] = [
    'id' => 1,
    'login' => 'admin',
    'nom_complet' => 'Administrateur',
    'email' => 'admin@test.com'
];

$_SESSION['permissions'] = [
    'PRODUITS_LIRE',
    'PRODUITS_CREER',
    'PRODUITS_MODIFIER',
    'PRODUITS_SUPPRIMER',
];

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// Simuler GET sans POST
$_GET = ['step' => '1'];
$_SERVER['REQUEST_METHOD'] = 'GET';
$_POST = [];
$_FILES = [];

require 'security.php';

// Juste charger et vérifier qu'il n'y a pas d'erreurs critiques
ob_start();

try {
    require 'admin/catalogue/import.php';
    $output = ob_get_clean();
    
    // Chercher des éléments clés
    $has_form = strpos($output, '<form') !== false;
    $has_title = strpos($output, 'Import Produits') !== false;
    $has_button = strpos($output, 'Continuer') !== false;
    
    echo "✓ Page charge sans erreur\n";
    echo "✓ Formulaire présent: " . ($has_form ? "OUI" : "NON") . "\n";
    echo "✓ Titre présent: " . ($has_title ? "OUI" : "NON") . "\n";
    echo "✓ Bouton présent: " . ($has_button ? "OUI" : "NON") . "\n";
    
    if ($has_form && $has_title && $has_button) {
        echo "\n✅ Page d'import OK\n";
    }
} catch (Exception $e) {
    ob_end_clean();
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}
?>
