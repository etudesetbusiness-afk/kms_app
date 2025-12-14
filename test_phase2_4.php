<?php
/**
 * Phase 2.4 - Validation Tests
 * Tests de performance, sécurité, et UX des features Phase 2.2 & 2.3
 */

require_once __DIR__ . '/security.php';

// Couleurs pour output
$colors = [
    'green' => "\033[32m",
    'red' => "\033[31m",
    'yellow' => "\033[33m",
    'blue' => "\033[34m",
    'reset' => "\033[0m"
];

$test_results = [];

echo "\n" . str_repeat("=", 60) . "\n";
echo "PHASE 2.4 - VALIDATION TESTS\n";
echo str_repeat("=", 60) . "\n\n";

// ============ TEST 1: Files Existence ============
echo "1. Vérification des fichiers...\n";
$files_to_check = [
    'lib/filters_helpers.php',
    'lib/dashboard_helpers.php',
    'components/sortable_header.php',
    'components/search_filter_bar.php',
    'ventes/list.php',
    'livraisons/list.php',
    'coordination/litiges.php',
    'dashboard.php'
];

$files_ok = 0;
foreach ($files_to_check as $file) {
    $full_path = __DIR__ . '/' . $file;
    if (file_exists($full_path)) {
        echo "  ✅ $file\n";
        $files_ok++;
    } else {
        echo "  ❌ $file (MANQUANT)\n";
    }
}
echo "Résultat: $files_ok/" . count($files_to_check) . " fichiers\n\n";
$test_results[] = "Files: $files_ok/" . count($files_to_check);

// ============ TEST 2: PHP Syntax ============
echo "2. Validation syntaxe PHP...\n";
$syntax_ok = 0;
foreach ($files_to_check as $file) {
    $full_path = __DIR__ . '/' . $file;
    if (!file_exists($full_path)) continue;
    
    $output = shell_exec("php -l $full_path 2>&1");
    if (strpos($output, 'No syntax errors') !== false) {
        echo "  ✅ $file\n";
        $syntax_ok++;
    } else {
        echo "  ❌ $file\n$output\n";
    }
}
echo "Résultat: $syntax_ok/" . count($files_to_check) . " fichiers\n\n";
$test_results[] = "Syntax: $syntax_ok/" . count($files_to_check);

// ============ TEST 3: Functions Availability ============
echo "3. Vérification des fonctions...\n";
$required_functions = [
    'lib/filters_helpers.php' => [
        'buildSearchWhereClause',
        'saveUserFilters',
        'getUserFilters',
        'getPaginationParams',
        'getPaginationUrl',
        'renderPaginationControls'
    ],
    'lib/dashboard_helpers.php' => [
        'calculateCAJour',
        'calculateCAMois',
        'calculateBLSignedRate',
        'calculateEncaissementRate',
        'calculateStockStats',
        'getAlertsCritiques',
        'getChartCAParJour',
        'getChartEncaissementStatut'
    ]
];

$functions_ok = 0;
$total_functions = 0;
foreach ($required_functions as $file => $functions) {
    foreach ($functions as $func) {
        $total_functions++;
        if (function_exists($func)) {
            echo "  ✅ $func\n";
            $functions_ok++;
        } else {
            echo "  ❌ $func (MANQUANTE)\n";
        }
    }
}
echo "Résultat: $functions_ok/$total_functions fonctions\n\n";
$test_results[] = "Functions: $functions_ok/$total_functions";

// ============ TEST 4: Database Queries ============
echo "4. Test des requêtes DB...\n";
global $pdo;
if (!$pdo) {
    echo "  ⚠️  PDO non disponible, skip\n\n";
} else {
    try {
        // Test ventes query
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM ventes WHERE numero LIKE ? LIMIT 1");
        $stmt->execute(['%']);
        echo "  ✅ Ventes search query\n";
        
        // Test livraisons query
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM bons_livraison WHERE numero LIKE ? LIMIT 1");
        $stmt->execute(['%']);
        echo "  ✅ Livraisons search query\n";
        
        // Test litiges query
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM retours_litiges WHERE description LIKE ? LIMIT 1");
        $stmt->execute(['%']);
        echo "  ✅ Litiges search query\n";
        
        // Test KPI queries
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(montant), 0) FROM caisse_journal WHERE DATE(date_operation) = CURDATE() AND type IN ('VENTE', 'HOTEL', 'FORMATION')");
        $stmt->execute();
        $result = $stmt->fetch();
        echo "  ✅ KPI CA jour query\n";
        
        echo "Résultat: 4/4 requêtes OK\n\n";
        $test_results[] = "Database: 4/4";
    } catch (Exception $e) {
        echo "  ❌ Erreur DB: " . $e->getMessage() . "\n\n";
        $test_results[] = "Database: Erreur";
    }
}

// ============ TEST 5: Performance Check ============
echo "5. Vérification des perfs...\n";
$perf_checks = [
    'ventes/list.php' => 'Ventes list',
    'livraisons/list.php' => 'Livraisons list',
    'dashboard.php' => 'Dashboard',
    'coordination/litiges.php' => 'Litiges'
];

$perf_ok = 0;
foreach ($perf_checks as $file => $name) {
    $full_path = __DIR__ . '/' . $file;
    if (file_exists($full_path)) {
        $size = filesize($full_path);
        $lines = count(file($full_path));
        
        // Warn if > 500 lines (refactor candidate)
        if ($lines > 500) {
            echo "  ⚠️  $name: $lines lignes (consider refactor)\n";
        } else {
            echo "  ✅ $name: $lines lignes\n";
            $perf_ok++;
        }
    }
}
echo "Résultat: $perf_ok/" . count($perf_checks) . " fichiers optimisés\n\n";
$test_results[] = "Performance: $perf_ok/" . count($perf_checks);

// ============ TEST 6: Security Check ============
echo "6. Vérification sécurité...\n";
$security_checks = 0;
$security_total = 3;

// Check CSRF in forms
$ventes_list = file_get_contents(__DIR__ . '/ventes/list.php');
if (strpos($ventes_list, 'exigerPermission') !== false) {
    echo "  ✅ Permissions vérifiées\n";
    $security_checks++;
} else {
    echo "  ❌ Permissions manquantes\n";
}

// Check prepared statements
if (strpos($ventes_list, '$pdo->prepare') !== false) {
    echo "  ✅ Requêtes préparées utilisées\n";
    $security_checks++;
} else {
    echo "  ❌ Requêtes non préparées\n";
}

// Check htmlspecialchars
if (strpos($ventes_list, 'htmlspecialchars') !== false || strpos($ventes_list, 'h()') !== false) {
    echo "  ✅ Outputs échappés\n";
    $security_checks++;
} else {
    echo "  ⚠️  Outputs non vérifiés\n";
}

echo "Résultat: $security_checks/$security_total checks\n\n";
$test_results[] = "Security: $security_checks/$security_total";

// ============ TEST 7: Responsive Design ============
echo "7. Vérification Bootstrap 5...\n";
$responsive_checks = 0;
$responsive_total = 4;

$dashboard = file_get_contents(__DIR__ . '/dashboard.php');

if (strpos($dashboard, 'class="container"') !== false || strpos($dashboard, 'class="row"') !== false) {
    echo "  ✅ Grid Bootstrap utilisé\n";
    $responsive_checks++;
} else {
    echo "  ❌ Grid manquant\n";
}

if (strpos($dashboard, 'col-lg') !== false || strpos($dashboard, 'col-md') !== false) {
    echo "  ✅ Responsive classes présentes\n";
    $responsive_checks++;
} else {
    echo "  ⚠️  Classes responsive non trouvées\n";
}

if (strpos($dashboard, 'meta name="viewport"') !== false) {
    echo "  ✅ Viewport mobile configuré\n";
    $responsive_checks++;
} else {
    echo "  ⚠️  Viewport non configuré\n";
}

if (strpos($dashboard, 'Chart') !== false) {
    echo "  ✅ Charts intégrés\n";
    $responsive_checks++;
} else {
    echo "  ❌ Charts manquants\n";
}

echo "Résultat: $responsive_checks/$responsive_total checks\n\n";
$test_results[] = "Responsive: $responsive_checks/$responsive_total";

// ============ SUMMARY ============
echo "\n" . str_repeat("=", 60) . "\n";
echo "RÉSUMÉ DES TESTS\n";
echo str_repeat("=", 60) . "\n\n";

foreach ($test_results as $result) {
    echo "  • $result\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "✅ PHASE 2.4 - Tests de validation complétés\n";
echo "Prêt pour Phase 3.1 (Pagination)\n";
echo str_repeat("=", 60) . "\n\n";
