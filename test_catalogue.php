<?php
/**
 * test_catalogue.php
 * Script de test pour vérifier le fonctionnement du module catalogue
 */

// Simulation d'une session utilisateur
session_start();

// Créer un utilisateur simulé
$_SESSION['user_id'] = 1;
$_SESSION['user'] = [
    'id' => 1,
    'login' => 'admin',
    'nom' => 'Admin',
    'role' => 'ADMIN',
    'permissions' => [
        'PRODUITS_LIRE' => true,
        'PRODUITS_CREER' => true,
        'PRODUITS_MODIFIER' => true,
        'PRODUITS_SUPPRIMER' => true,
        'CATEGORIES_LIRE' => true,
        'CATEGORIES_CREER' => true,
        'CATEGORIES_MODIFIER' => true,
        'CATEGORIES_SUPPRIMER' => true,
    ]
];

// Inclure le security.php pour les fonctions
require_once __DIR__ . '/security.php';

echo "=== TEST CATALOGUE MODULE ===\n\n";

// Test 1: getCsrfToken() existe
echo "Test 1: getCsrfToken() fonction\n";
try {
    $token = getCsrfToken();
    echo "✓ getCsrfToken() retourne: " . substr($token, 0, 20) . "...\n";
} catch (Exception $e) {
    echo "✗ Erreur: " . $e->getMessage() . "\n";
}

// Test 2: verifierCsrf() existe
echo "\nTest 2: verifierCsrf() fonction\n";
try {
    // Créer un token valide
    $testToken = getCsrfToken();
    $_SESSION['csrf_token'] = $testToken;
    
    // Tenter de vérifier (peut lancer une exception, c'est attendu)
    verifierCsrf($testToken);
    echo "✓ verifierCsrf() fonctionne avec token valide\n";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'CSRF') !== false) {
        echo "✗ Token CSRF invalide: " . $e->getMessage() . "\n";
    } else {
        echo "? Erreur attendue ou non: " . $e->getMessage() . "\n";
    }
}

// Test 3: Vérifier les fichiers PHP du catalogue
echo "\nTest 3: Vérification syntaxe des fichiers du catalogue\n";
$files = [
    'admin/catalogue/produits.php',
    'admin/catalogue/produit_edit.php',
    'admin/catalogue/produit_delete.php',
    'admin/catalogue/categories.php',
];

foreach ($files as $file) {
    $filepath = __DIR__ . '/' . $file;
    if (!file_exists($filepath)) {
        echo "✗ Fichier manquant: $file\n";
        continue;
    }
    
    // Vérifier la syntaxe
    $output = shell_exec("php -l \"$filepath\" 2>&1");
    if (strpos($output, 'No syntax errors') !== false) {
        echo "✓ $file - Syntaxe OK\n";
    } else {
        echo "✗ $file - Erreur: " . trim($output) . "\n";
    }
}

// Test 4: Vérifier les fonctions critiques
echo "\nTest 4: Vérification fonctions critiques\n";
$criticalFunctions = [
    'getCsrfToken' => 'security.php',
    'verifierCsrf' => 'security.php',
    'url_for' => 'security.php',
    'peut' => 'security.php',
    'exigerPermission' => 'security.php',
];

foreach ($criticalFunctions as $func => $file) {
    if (function_exists($func)) {
        echo "✓ $func() existe\n";
    } else {
        echo "✗ $func() manquante\n";
    }
}

// Test 5: Vérifier les tables
echo "\nTest 5: Vérification des tables de base de données\n";
try {
    global $pdo;
    if (!$pdo) {
        echo "? PDO non initialisé dans ce contexte de test\n";
    } else {
        $tables = ['catalogue_categories', 'catalogue_produits'];
        foreach ($tables as $table) {
            $result = $pdo->query("SHOW TABLES LIKE '$table'")->fetch();
            if ($result) {
                echo "✓ Table $table existe\n";
            } else {
                echo "✗ Table $table manquante\n";
            }
        }
    }
} catch (Exception $e) {
    echo "? Impossible de vérifier les tables: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DES TESTS ===\n";
?>
