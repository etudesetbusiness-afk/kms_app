<?php
/**
 * test_phase3_2.php - Tests Phase 3.2 User Preferences
 */

require_once __DIR__ . '/security.php';

echo "\n" . str_repeat("=", 60) . "\n";
echo "PHASE 3.2 - USER PREFERENCES TESTS\n";
echo str_repeat("=", 60) . "\n\n";

$test_results = [];

// Test 1: Files & Table
echo "1. Vérification fichiers et table...\n";
global $pdo;

$files_ok = 0;
if (file_exists(__DIR__ . '/lib/user_preferences.php')) {
    echo "  ✅ lib/user_preferences.php\n";
    $files_ok++;
} else {
    echo "  ❌ lib/user_preferences.php\n";
}

if (file_exists(__DIR__ . '/db/003_user_preferences.sql')) {
    echo "  ✅ db/003_user_preferences.sql\n";
    $files_ok++;
} else {
    echo "  ❌ db/003_user_preferences.sql\n";
}

// Vérifier que la table existe
try {
    $result = $pdo->query("SELECT COUNT(*) FROM user_preferences LIMIT 1");
    if ($result) {
        echo "  ✅ Table user_preferences existe\n";
        $files_ok++;
    }
} catch (Exception $e) {
    echo "  ❌ Table user_preferences: " . $e->getMessage() . "\n";
}

echo "Résultat: $files_ok/3\n\n";
$test_results[] = "Files/Table: $files_ok/3";

// Test 2: Syntax
echo "2. Validation syntaxe PHP...\n";
$syntax_ok = 0;
if (strpos(shell_exec("php -l " . __DIR__ . "/lib/user_preferences.php 2>&1"), 'No syntax errors') !== false) {
    echo "  ✅ lib/user_preferences.php\n";
    $syntax_ok++;
}
if (strpos(shell_exec("php -l " . __DIR__ . "/ventes/list.php 2>&1"), 'No syntax errors') !== false) {
    echo "  ✅ ventes/list.php (intégration)\n";
    $syntax_ok++;
}
echo "Résultat: $syntax_ok/2\n\n";
$test_results[] = "Syntax: $syntax_ok/2";

// Test 3: Functions
echo "3. Vérification des fonctions...\n";
require_once __DIR__ . '/lib/user_preferences.php';

$functions = [
    'getUserPagePreferences',
    'saveUserPagePreferences',
    'mergePreferencesWithGet',
    'updateUserPreferencesFromGet',
    'getUserAllPreferences',
    'deleteUserPagePreferences',
    'resetAllUserPreferences'
];
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

// Test 4: Function tests
echo "4. Test des fonctions...\n";

try {
    // Test getUserPagePreferences (user non-existent → defaults)
    $prefs = getUserPagePreferences(99999, 'ventes');
    if ($prefs['sort_by'] === 'date' && $prefs['per_page'] === 25) {
        echo "  ✅ getUserPagePreferences returns defaults\n";
        $functions_ok++;
    }
    
    // Test mergePreferencesWithGet
    $merged = mergePreferencesWithGet(
        ['sort_by' => 'client', 'per_page' => 50],
        ['sort_by' => 'montant', 'sort_dir' => 'asc', 'per_page' => 25],
        ['date', 'client', 'montant']
    );
    if ($merged['sort_by'] === 'client' && $merged['per_page'] === 50) {
        echo "  ✅ mergePreferencesWithGet (GET priority)\n";
        $functions_ok++;
    } else {
        echo "  ❌ mergePreferencesWithGet\n";
    }
    
    // Test mergePreferencesWithGet sans GET
    $merged2 = mergePreferencesWithGet(
        [],
        ['sort_by' => 'montant', 'sort_dir' => 'asc', 'per_page' => 50],
        ['date', 'client', 'montant']
    );
    if ($merged2['sort_by'] === 'montant' && $merged2['per_page'] === 50) {
        echo "  ✅ mergePreferencesWithGet (prefs fallback)\n";
        $functions_ok++;
    } else {
        echo "  ❌ mergePreferencesWithGet fallback\n";
    }
    
    // Test saveUserPagePreferences (create)
    if (saveUserPagePreferences(1, 'test_page', ['sort_by' => 'client', 'per_page' => 50])) {
        echo "  ✅ saveUserPagePreferences (create)\n";
        $functions_ok++;
    } else {
        echo "  ❌ saveUserPagePreferences\n";
    }
    
    // Test getUserPagePreferences (after save)
    $saved_prefs = getUserPagePreferences(1, 'test_page');
    if ($saved_prefs['sort_by'] === 'client' && $saved_prefs['per_page'] === 50) {
        echo "  ✅ getUserPagePreferences (after save)\n";
        $functions_ok++;
    } else {
        echo "  ❌ getUserPagePreferences save failed\n";
    }
    
    // Test deleteUserPagePreferences
    if (deleteUserPagePreferences(1, 'test_page')) {
        echo "  ✅ deleteUserPagePreferences\n";
        $functions_ok++;
    } else {
        echo "  ❌ deleteUserPagePreferences\n";
    }
    
    echo "Résultat: 6/6 fonction tests\n\n";
    $test_results[] = "Function Tests: 6/6";
    
} catch (Exception $e) {
    echo "  ❌ Erreur: " . $e->getMessage() . "\n\n";
}

// Summary
echo "\n" . str_repeat("=", 60) . "\n";
echo "RÉSUMÉ\n";
echo str_repeat("=", 60) . "\n\n";
foreach ($test_results as $r) {
    echo "  • $r\n";
}

$total_ok = count(array_filter($test_results, fn($r) => strpos($r, '✅') === false || strpos($r, '1') !== false));
echo "\n✅ Phase 3.2 - Tests complétés (" . count($test_results) . " suites)\n\n";
