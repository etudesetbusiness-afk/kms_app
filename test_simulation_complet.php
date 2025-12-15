<?php
/**
 * test_simulation_complet.php
 * Simule une séquence complète de requêtes avec capture d'erreurs
 */

session_start();

// Initialiser session
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

require_once 'security.php';
require_once 'lib/pagination.php';

// Initialiser les erreurs
$errors = [];
$tests = [];

// Capturer les erreurs PHP
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    global $errors;
    $errors[] = [
        'type' => 'PHP Error',
        'message' => $errstr,
        'file' => $errfile,
        'line' => $errline
    ];
    return false;
});

set_exception_handler(function($e) {
    global $errors;
    $errors[] = [
        'type' => 'Exception',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ];
});

global $pdo;

// TEST 1: Inclure categories.php partiellement pour tester la logique
echo "<h1>🧪 TEST SIMULATION COMPLÈTE</h1>\n";

// Simuler une requête GET à la page categories.php
echo "<h2>TEST 1: Charge categories.php (GET)</h2>\n";

// Capturer la sortie
ob_start();

$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET = [];
$_POST = [];

try {
    // Inclure seulement la logique PHP sans les headers
    $test1 = @include 'admin/catalogue/categories.php';
    $html = ob_get_clean();
    
    if (empty($errors)) {
        $tests['categories_get'] = ['status' => 'PASS', 'message' => 'Fichier inclus sans erreur'];
        echo "<div style='background: #d4edda; padding: 10px; border-radius: 5px;'>";
        echo "<strong>✓ PASS:</strong> Fichier categories.php inclus sans erreur<br>";
        echo "Contenu: " . strlen($html) . " octets générés<br>";
        echo "</div>\n";
    } else {
        $tests['categories_get'] = ['status' => 'FAIL', 'message' => implode(', ', array_map(fn($e) => $e['message'], $errors))];
    }
} catch (Throwable $e) {
    ob_end_clean();
    $tests['categories_get'] = ['status' => 'FAIL', 'message' => $e->getMessage()];
    echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px;'>";
    echo "<strong>✗ FAIL:</strong> " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "<br>";
    echo "</div>\n";
}

// Nettoyer les erreurs
$errors = [];

// TEST 2: Vérifier les fichiers contiennent les bonnes fonctions
echo "<h2>TEST 2: Vérification Syntaxe Fichiers</h2>\n";

$files_to_check = [
    'admin/catalogue/produits.php',
    'admin/catalogue/produit_edit.php',
    'admin/catalogue/produit_delete.php',
    'admin/catalogue/categories.php',
];

foreach ($files_to_check as $file) {
    $filepath = __DIR__ . '/' . $file;
    
    if (!file_exists($filepath)) {
        $tests[$file] = ['status' => 'FAIL', 'message' => 'Fichier non trouvé'];
        echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px;'>";
        echo "<strong>✗ FAIL:</strong> $file - Fichier non trouvé<br>";
        echo "</div>\n";
        continue;
    }
    
    // Vérifier la syntaxe
    $output = shell_exec("php -l \"$filepath\" 2>&1");
    
    if (strpos($output, 'No syntax errors') !== false) {
        $tests[$file] = ['status' => 'PASS', 'message' => 'Syntaxe valide'];
        echo "<div style='background: #d4edda; padding: 10px; border-radius: 5px;'>";
        echo "<strong>✓ PASS:</strong> $file - Syntaxe valide<br>";
        echo "</div>\n";
    } else {
        $tests[$file] = ['status' => 'FAIL', 'message' => trim($output)];
        echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px;'>";
        echo "<strong>✗ FAIL:</strong> $file<br>";
        echo "<pre>" . htmlspecialchars($output) . "</pre>\n";
        echo "</div>\n";
    }
}

// TEST 3: Vérifier les fonctions critiques dans les fichiers
echo "<h2>TEST 3: Recherche Fonctions Critiques</h2>\n";

$critical_functions = [
    'getCsrfToken',
    'verifierCsrf',
    'peut',
    'exigerPermission',
    'url_for',
];

foreach ($critical_functions as $func) {
    if (function_exists($func)) {
        $tests[$func] = ['status' => 'PASS', 'message' => 'Fonction disponible'];
        echo "<div style='background: #d4edda; padding: 10px;'>";
        echo "<strong>✓ PASS:</strong> <code>$func()</code> existe<br>";
        echo "</div>\n";
    } else {
        $tests[$func] = ['status' => 'FAIL', 'message' => 'Fonction non trouvée'];
        echo "<div style='background: #f8d7da; padding: 10px;'>";
        echo "<strong>✗ FAIL:</strong> <code>$func()</code> manquante<br>";
        echo "</div>\n";
    }
}

// TEST 4: Tester CSRF
echo "<h2>TEST 4: Système CSRF</h2>\n";

try {
    $token1 = getCsrfToken();
    $token2 = getCsrfToken();
    
    if ($token1 === $token2) {
        $tests['csrf_consistency'] = ['status' => 'PASS', 'message' => 'Token cohérent'];
        echo "<div style='background: #d4edda; padding: 10px;'>";
        echo "<strong>✓ PASS:</strong> getCsrfToken() retourne le même token<br>";
        echo "Token: <code>" . substr($token1, 0, 30) . "...</code><br>";
        echo "</div>\n";
    } else {
        $tests['csrf_consistency'] = ['status' => 'FAIL', 'message' => 'Tokens différents'];
        echo "<div style='background: #f8d7da; padding: 10px;'>";
        echo "<strong>✗ FAIL:</strong> getCsrfToken() retourne des tokens différents<br>";
        echo "</div>\n";
    }
    
    // Tester la vérification
    verifierCsrf($token1);
    $tests['csrf_verify'] = ['status' => 'PASS', 'message' => 'Vérification CSRF OK'];
    echo "<div style='background: #d4edda; padding: 10px;'>";
    echo "<strong>✓ PASS:</strong> verifierCsrf() fonctionne<br>";
    echo "</div>\n";
} catch (Exception $e) {
    $tests['csrf_verify'] = ['status' => 'FAIL', 'message' => $e->getMessage()];
    echo "<div style='background: #f8d7da; padding: 10px;'>";
    echo "<strong>✗ FAIL:</strong> CSRF Error: " . $e->getMessage() . "<br>";
    echo "</div>\n";
}

// TEST 5: Base de données
echo "<h2>TEST 5: Base de Données</h2>\n";

try {
    // Test catégories
    $stmt = $pdo->query("SELECT COUNT(*) FROM catalogue_categories");
    $cat_count = $stmt->fetchColumn();
    
    $tests['db_categories'] = ['status' => 'PASS', 'message' => "$cat_count catégories trouvées"];
    echo "<div style='background: #d4edda; padding: 10px;'>";
    echo "<strong>✓ PASS:</strong> Table catalogue_categories accessible<br>";
    echo "Nombre de catégories: <strong>$cat_count</strong><br>";
    echo "</div>\n";
    
    // Test produits
    $stmt = $pdo->query("SELECT COUNT(*) FROM catalogue_produits");
    $prod_count = $stmt->fetchColumn();
    
    $tests['db_products'] = ['status' => 'PASS', 'message' => "$prod_count produits trouvés"];
    echo "<div style='background: #d4edda; padding: 10px;'>";
    echo "<strong>✓ PASS:</strong> Table catalogue_produits accessible<br>";
    echo "Nombre de produits: <strong>$prod_count</strong><br>";
    echo "</div>\n";
    
} catch (Exception $e) {
    $tests['db_error'] = ['status' => 'FAIL', 'message' => $e->getMessage()];
    echo "<div style='background: #f8d7da; padding: 10px;'>";
    echo "<strong>✗ FAIL:</strong> Erreur base de données<br>";
    echo $e->getMessage() . "<br>";
    echo "</div>\n";
}

// RÉSUMÉ
echo "<h2>RÉSUMÉ DES TESTS</h2>\n";

$pass_count = count(array_filter($tests, fn($t) => $t['status'] === 'PASS'));
$fail_count = count(array_filter($tests, fn($t) => $t['status'] === 'FAIL'));
$total = count($tests);

echo "<div style='background: #f0f0f0; padding: 15px; border-radius: 5px;'>";
echo "<strong>Résultats:</strong><br>";
echo "✓ Passés: <strong style='color: green;'>$pass_count/$total</strong><br>";
echo "✗ Échoués: <strong style='color: red;'>$fail_count/$total</strong><br>";
echo "</div>\n";

if ($fail_count > 0) {
    echo "<h3>❌ Détails des Échecs</h3>\n";
    foreach ($tests as $test_name => $result) {
        if ($result['status'] === 'FAIL') {
            echo "<div style='background: #f8d7da; padding: 10px; margin: 5px 0; border-left: 3px solid #dc3545;'>";
            echo "<strong>$test_name:</strong><br>";
            echo $result['message'] . "<br>";
            echo "</div>\n";
        }
    }
}

?>
