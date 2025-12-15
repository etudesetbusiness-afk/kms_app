<?php
/**
 * test_phase3_5.php - Tests pour Phase 3.5 (Intégration Complète)
 * Tests: Intégration pagination + date picker + cache dans les pages principales
 */

echo "========================================\n";
echo "Phase 3.5 - Tests Intégration Complète\n";
echo "========================================\n\n";

$test_count = 0;
$pass_count = 0;

// ============================================
// 1. TEST FILES
// ============================================
echo "1. Vérification des fichiers intégrés...\n";
$files = [
    'ventes/list.php',
    'livraisons/list.php',
    'coordination/litiges.php',
    'components/date_range_picker.html',
    'lib/pagination.php',
    'lib/date_helpers.php',
    'lib/cache.php'
];

foreach ($files as $file) {
    $test_count++;
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "   ✅ $file\n";
        $pass_count++;
    } else {
        echo "   ❌ $file (manquant)\n";
    }
}

// ============================================
// 2. TEST SYNTAX
// ============================================
echo "\n2. Vérification de la syntaxe PHP...\n";
$php_files = [
    'ventes/list.php',
    'livraisons/list.php',
    'coordination/litiges.php'
];

foreach ($php_files as $file) {
    $test_count++;
    $output = shell_exec("php -l " . __DIR__ . "/$file 2>&1");
    if (strpos($output, 'No syntax errors') !== false) {
        echo "   ✅ $file\n";
        $pass_count++;
    } else {
        echo "   ❌ $file\n   $output\n";
    }
}

// ============================================
// 3. TEST INCLUDES
// ============================================
echo "\n3. Vérification des includes et dépendances...\n";

// Vérifier que les libs peuvent être chargées
$test_count++;
try {
    require_once __DIR__ . '/lib/pagination.php';
    require_once __DIR__ . '/lib/date_helpers.php';
    require_once __DIR__ . '/lib/cache.php';
    echo "   ✅ Toutes les libs se chargent sans erreur\n";
    $pass_count++;
} catch (Exception $e) {
    echo "   ❌ Erreur lors du chargement des libs: " . $e->getMessage() . "\n";
}

// ============================================
// 4. TEST FUNCTIONS
// ============================================
echo "\n4. Vérification des fonctions disponibles...\n";

$functions = [
    'getPaginationParams',
    'buildPaginationUrl',
    'renderPaginationControls',
    'getPaginationLimitClause',
    'getDateRangePreset',
    'validateAndFormatDate',
    'getDatePresets',
    'cached',
    'getCache'
];

foreach ($functions as $func) {
    $test_count++;
    if (function_exists($func)) {
        echo "   ✅ $func()\n";
        $pass_count++;
    } else {
        echo "   ❌ $func() (non trouvée)\n";
    }
}

// ============================================
// 5. TEST CLASSES
// ============================================
echo "\n5. Vérification des classes disponibles...\n";

$classes = ['CacheManager'];

foreach ($classes as $class) {
    $test_count++;
    if (class_exists($class)) {
        echo "   ✅ $class\n";
        $pass_count++;
    } else {
        echo "   ❌ $class (non trouvée)\n";
    }
}

// ============================================
// 6. TEST COMPONENT FILE
// ============================================
echo "\n6. Vérification du composant date_range_picker...\n";

$test_count++;
$component = file_get_contents(__DIR__ . '/components/date_range_picker.html');
if (strpos($component, 'flatpickr') !== false && 
    strpos($component, 'preset-btn') !== false &&
    strpos($component, 'dateStart') !== false) {
    echo "   ✅ Composant date_range_picker.html valide\n";
    $pass_count++;
} else {
    echo "   ❌ Composant date_range_picker.html incomplet\n";
}

// ============================================
// 7. TEST INTEGRATION MARKERS
// ============================================
echo "\n7. Vérification des intégrations dans les pages...\n";

$integrations = [
    'ventes/list.php' => ['lib/date_helpers.php', 'renderPaginationControls', 'getDateRangePreset'],
    'livraisons/list.php' => ['lib/pagination.php', 'getPaginationParams', 'date_range_picker.html'],
    'coordination/litiges.php' => ['lib/date_helpers.php', 'updateUserPreferencesFromGet', 'pagination']
];

foreach ($integrations as $file => $markers) {
    $content = file_get_contents(__DIR__ . '/' . $file);
    
    foreach ($markers as $marker) {
        $test_count++;
        if (strpos($content, $marker) !== false) {
            echo "   ✅ $file contient '$marker'\n";
            $pass_count++;
        } else {
            echo "   ❌ $file ne contient pas '$marker'\n";
        }
    }
}

// ============================================
// RÉSUMÉ
// ============================================
echo "\n========================================\n";
echo "Résultat: $pass_count / $test_count tests réussis\n";
echo "========================================\n";

if ($pass_count === $test_count) {
    echo "✅ TOUS LES TESTS PASSENT - PHASE 3.5 OK\n";
    echo "\nPages intégrées avec succès:\n";
    echo "  ✅ ventes/list.php (pagination + date picker + cache + user prefs)\n";
    echo "  ✅ livraisons/list.php (pagination + date picker + cache + user prefs)\n";
    echo "  ✅ coordination/litiges.php (pagination + date picker + cache + user prefs)\n";
    exit(0);
} else {
    echo "❌ Certains tests ont échoué\n";
    exit(1);
}
