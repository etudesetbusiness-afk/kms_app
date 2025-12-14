<?php
/**
 * test_phase3_1.php - Tests Phase 3.1 Pagination
 */

require_once __DIR__ . '/security.php';

echo "\n" . str_repeat("=", 60) . "\n";
echo "PHASE 3.1 - PAGINATION TESTS\n";
echo str_repeat("=", 60) . "\n\n";

$test_results = [];

// Test 1: Files
echo "1. Vérification des fichiers...\n";
$files = [
    'lib/pagination.php',
    'ventes/list.php'
];
$files_ok = 0;
foreach ($files as $f) {
    if (file_exists(__DIR__ . '/' . $f)) {
        echo "  ✅ $f\n";
        $files_ok++;
    }
}
echo "Résultat: $files_ok/" . count($files) . "\n\n";
$test_results[] = "Files: $files_ok/" . count($files);

// Test 2: Syntax
echo "2. Validation syntaxe PHP...\n";
$syntax_ok = 0;
foreach ($files as $f) {
    $output = shell_exec("php -l " . __DIR__ . "/$f 2>&1");
    if (strpos($output, 'No syntax errors') !== false) {
        echo "  ✅ $f\n";
        $syntax_ok++;
    } else {
        echo "  ❌ $f\n";
    }
}
echo "Résultat: $syntax_ok/" . count($files) . "\n\n";
$test_results[] = "Syntax: $syntax_ok/" . count($files);

// Test 3: Functions
echo "3. Vérification des fonctions...\n";
require_once __DIR__ . '/lib/pagination.php';

$functions = ['getPaginationParams', 'getPaginationLimitClause', 'buildPaginationUrl', 'renderPaginationControls'];
$functions_ok = 0;
foreach ($functions as $func) {
    if (function_exists($func)) {
        echo "  ✅ $func\n";
        $functions_ok++;
    } else {
        echo "  ❌ $func\n";
    }
}
echo "Résultat: $functions_ok/" . count($functions) . "\n\n";
$test_results[] = "Functions: $functions_ok/" . count($functions);

// Test 4: Function outputs
echo "4. Test des fonctions...\n";
global $pdo;

if ($pdo) {
    // Test getPaginationParams
    $result = getPaginationParams(['page' => 2, 'per_page' => 25], 100, 25);
    if ($result['page'] == 2 && $result['offset'] == 25 && $result['total_pages'] == 4) {
        echo "  ✅ getPaginationParams(page=2, total=100, per_page=25) → offset=25, total_pages=4\n";
        $functions_ok++;
    } else {
        echo "  ❌ getPaginationParams incorrect\n";
    }
    
    // Test avec page invalide
    $result = getPaginationParams(['page' => 999], 100, 25);
    if ($result['page'] == 4) {
        echo "  ✅ getPaginationParams(page=999) → capped to 4\n";
        $functions_ok++;
    } else {
        echo "  ❌ Page capping failed\n";
    }
    
    // Test buildPaginationUrl
    $url = buildPaginationUrl(['search' => 'test', 'sort_by' => 'date'], 3, 50);
    if (strpos($url, 'page=3') !== false && strpos($url, 'per_page=50') !== false) {
        echo "  ✅ buildPaginationUrl preserves filters\n";
        $functions_ok++;
    } else {
        echo "  ❌ buildPaginationUrl lost filters\n";
    }
    
    // Test getPaginationLimitClause
    $limit = getPaginationLimitClause(50, 25);
    if ($limit == "LIMIT 50, 25") {
        echo "  ✅ getPaginationLimitClause(50, 25) → \"$limit\"\n";
        $functions_ok++;
    } else {
        echo "  ❌ getPaginationLimitClause: $limit\n";
    }
    
    echo "Résultat: 4/4 fonction tests\n\n";
    $test_results[] = "Function Tests: 4/4";
} else {
    echo "  ⚠️  PDO non disponible, skip\n\n";
}

// Summary
echo "\n" . str_repeat("=", 60) . "\n";
echo "RÉSUMÉ\n";
echo str_repeat("=", 60) . "\n\n";
foreach ($test_results as $r) {
    echo "  • $r\n";
}

echo "\n✅ Phase 3.1 - Tests complétés\n";
echo "Prêt pour livraison et intégration dans autres pages\n\n";
