<?php
/**
 * test_phase3_4.php - Tests pour Phase 3.4 (Optimisations & Caching)
 * Tests: Cache system, Query optimizer, Database analysis
 */

echo "========================================\n";
echo "Phase 3.4 - Tests Optimisations & Caching\n";
echo "========================================\n\n";

$test_count = 0;
$pass_count = 0;

// ============================================
// 1. TEST FILES
// ============================================
echo "1. Vérification des fichiers...\n";
$files = [
    'lib/cache.php',
    'lib/query_optimizer.php',
    'admin/database_optimization.php',
    'admin/clear_cache.php'
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
    'lib/cache.php',
    'lib/query_optimizer.php',
    'admin/database_optimization.php',
    'admin/clear_cache.php'
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
// 3. TEST CLASSES
// ============================================
echo "\n3. Vérification des classes...\n";
require_once __DIR__ . '/lib/cache.php';
require_once __DIR__ . '/lib/query_optimizer.php';

$classes = ['CacheManager', 'QueryOptimizer'];
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
// 4. TEST CACHE METHODS
// ============================================
echo "\n4. Tests du système de cache...\n";

// Créer instance cache
$cache = new CacheManager([], __DIR__ . '/cache_test');

// Test: set/get
$test_count++;
$cache->set('test_key', 'test_value', 3600);
$value = $cache->get('test_key');
if ($value === 'test_value') {
    echo "   ✅ Cache set/get fonctionne\n";
    $pass_count++;
} else {
    echo "   ❌ Cache set/get retourne: $value\n";
}

// Test: array storage
$test_count++;
$arr = ['a' => 1, 'b' => 2];
$cache->set('array_key', $arr);
$retrieved = $cache->get('array_key');
if ($retrieved === $arr) {
    echo "   ✅ Cache avec array fonctionne\n";
    $pass_count++;
} else {
    echo "   ❌ Cache array échoue\n";
}

// Test: delete
$test_count++;
$cache->delete('test_key');
$value = $cache->get('test_key');
if ($value === null) {
    echo "   ✅ Cache delete fonctionne\n";
    $pass_count++;
} else {
    echo "   ❌ Cache delete échoue\n";
}

// Test: remember (lazy evaluation)
$test_count++;
$callback_called = false;
$result = $cache->remember('remember_key', function() use (&$callback_called) {
    $callback_called = true;
    return 'lazy_result';
}, 3600);

if ($result === 'lazy_result' && $callback_called) {
    echo "   ✅ Cache remember (1ère fois) fonctionne\n";
    $pass_count++;
} else {
    echo "   ❌ Cache remember échoue\n";
}

// Test: remember (from cache)
$test_count++;
$callback_called = false;
$result = $cache->remember('remember_key', function() use (&$callback_called) {
    $callback_called = true;
    return 'other_result';
}, 3600);

if ($result === 'lazy_result' && !$callback_called) {
    echo "   ✅ Cache remember (retour cache) fonctionne\n";
    $pass_count++;
} else {
    echo "   ❌ Cache remember (2ème appel) échoue\n";
}

// Test: stats
$test_count++;
$stats = $cache->getStats();
if (isset($stats['files']) && isset($stats['size']) && isset($stats['cache_dir'])) {
    echo "   ✅ Cache getStats() fonctionne\n";
    $pass_count++;
} else {
    echo "   ❌ Cache getStats() format invalide\n";
}

// Test: flush
$test_count++;
$cache->flush();
$value_after = $cache->get('remember_key');
if ($value_after === null) {
    echo "   ✅ Cache flush fonctionne\n";
    $pass_count++;
} else {
    echo "   ❌ Cache flush échoue\n";
}

// Nettoyer répertoire test
@array_map('unlink', glob(__DIR__ . '/cache_test/*.cache'));
@rmdir(__DIR__ . '/cache_test');

// ============================================
// 5. TEST HELPER FUNCTIONS
// ============================================
echo "\n5. Tests des fonctions helper...\n";

// Test: getCache()
$test_count++;
$c = getCache();
if (is_object($c) && get_class($c) === 'CacheManager') {
    echo "   ✅ getCache() retourne CacheManager\n";
    $pass_count++;
} else {
    echo "   ❌ getCache() format invalide\n";
}

// Test: cached()
$test_count++;
$call_count = 0;
$result1 = cached('helper_test', function() use (&$call_count) {
    $call_count++;
    return 'result';
});

$result2 = cached('helper_test', function() use (&$call_count) {
    $call_count++;
    return 'result';
});

if ($call_count === 1 && $result1 === $result2) {
    echo "   ✅ cached() helper fonctionne\n";
    $pass_count++;
} else {
    echo "   ❌ cached() helper échoue (appels: $call_count)\n";
}

// ============================================
// RÉSUMÉ
// ============================================
echo "\n========================================\n";
echo "Résultat: $pass_count / $test_count tests réussis\n";
echo "========================================\n";

if ($pass_count === $test_count) {
    echo "✅ TOUS LES TESTS PASSENT - PHASE 3.4 OK\n";
    exit(0);
} else {
    echo "❌ Certains tests ont échoué\n";
    exit(1);
}
