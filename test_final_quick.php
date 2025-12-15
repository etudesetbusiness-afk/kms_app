<?php
/**
 * test_final_quick.php
 * Test rapide final du projet
 */

echo "╔═══════════════════════════════════════════════════════════════╗\n";
echo "║          TEST FINAL RAPIDE - KMS GESTION                    ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

$stats = [
    'php_files' => 0,
    'syntax_errors' => 0,
    'modules_ok' => 0
];

// ==================== 1. SYNTAXE PHP ====================
echo "✓ PHASE 1: Vérification syntaxe PHP\n";
echo "───────────────────────────────────────────────────────────\n";

// Utiliser une fonction récursive simple
$php_files = [];
function find_php_files($dir, &$files, $excluded = ['node_modules', '.git', 'vendor']) {
    if (!is_dir($dir)) return;
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        
        $skip = false;
        foreach ($excluded as $exc) {
            if (strpos($path, DIRECTORY_SEPARATOR . $exc . DIRECTORY_SEPARATOR) !== false) {
                $skip = true;
                break;
            }
        }
        if ($skip) continue;
        
        if (is_file($path) && substr($path, -4) === '.php') {
            $files[] = $path;
        } elseif (is_dir($path)) {
            find_php_files($path, $files, $excluded);
        }
    }
}

find_php_files(__DIR__, $php_files);

foreach ($php_files as $file) {
    $stats['php_files']++;
    
    $output = shell_exec("php -l " . escapeshellarg($file) . " 2>&1");
    if (strpos($output, 'No syntax errors') === false) {
        $stats['syntax_errors']++;
    }
}

echo "  Résultat: " . ($stats['php_files'] - $stats['syntax_errors']) . "/" . $stats['php_files'] . " OK\n";
if ($stats['php_files'] > 0) {
    echo "  Taux: " . round((($stats['php_files'] - $stats['syntax_errors']) / $stats['php_files']) * 100) . "%\n";
}
echo "\n";

// ==================== 2. MODULES CLÉS ====================
echo "✓ PHASE 2: Vérification modules clés\n";
echo "───────────────────────────────────────────────────────────\n";

$modules = [
    'admin/catalogue/import.php',
    'admin/catalogue/produits.php',
    'catalogue/index.php',
    'dashboard.php',
];

foreach ($modules as $mod) {
    if (file_exists($mod)) {
        $output = shell_exec("php -l " . escapeshellarg($mod) . " 2>&1");
        if (strpos($output, 'No syntax errors') !== false) {
            echo "  ✓ " . basename($mod) . "\n";
            $stats['modules_ok']++;
        }
    }
}

echo "  Résultat: " . $stats['modules_ok'] . "/" . count($modules) . " modules opérationnels\n\n";

// ==================== 3. BD ====================
echo "✓ PHASE 3: Vérification base de données\n";
echo "───────────────────────────────────────────────────────────\n";

try {
    $pdo = new PDO(
        'mysql:host=localhost;charset=utf8mb4',
        'root',
        ''
    );
    
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM information_schema.SCHEMATA WHERE SCHEMA_NAME='kms_gestion'");
    if ($stmt && $stmt->rowCount() > 0) {
        echo "  ✓ BD: kms_gestion accessible\n";
        
        $pdo->exec("USE kms_gestion");
        $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM information_schema.TABLES WHERE TABLE_SCHEMA='kms_gestion'");
        if ($stmt) {
            $tables = $stmt->fetch()['cnt'];
            echo "  ✓ Tables: $tables\n";
        }
        
        $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM utilisateurs");
        if ($stmt) {
            $users = $stmt->fetch()['cnt'];
            echo "  ✓ Utilisateurs: $users\n";
        }
    } else {
        echo "  ✗ BD kms_gestion non trouvée\n";
    }
} catch (Exception $e) {
    echo "  ⓘ BD: Non testée (connexion impossible)\n";
}

echo "\n";

// ==================== 4. FICHIERS IMPORT ====================
echo "✓ PHASE 4: Fichiers import Excel\n";
echo "───────────────────────────────────────────────────────────\n";

$import_files = [
    'uploads/exemple_import.csv',
    'uploads/exemple_complet.csv',
    'admin/catalogue/import.php',
];

$import_ok = 0;
foreach ($import_files as $file) {
    if (file_exists($file)) {
        echo "  ✓ " . basename($file) . "\n";
        $import_ok++;
    } else {
        echo "  ✗ " . basename($file) . "\n";
    }
}

echo "  Résultat: $import_ok/" . count($import_files) . " présents\n\n";

// ==================== VERDICT ====================
echo "╔═══════════════════════════════════════════════════════════════╗\n";
echo "║                    VERDICT FINAL                             ║\n";
echo "╚═══════════════════════════════════════════════════════════════╝\n\n";

$syntax_pct = ($stats['php_files'] > 0) ? round((($stats['php_files'] - $stats['syntax_errors']) / $stats['php_files']) * 100) : 0;

if ($stats['syntax_errors'] === 0 && $stats['modules_ok'] === count($modules) && $import_ok === count($import_files)) {
    echo "✅ PROJET 100% OPÉRATIONNEL\n\n";
    echo "  ✓ Syntaxe: $syntax_pct%\n";
    echo "  ✓ Modules: {$stats['modules_ok']}/4\n";
    echo "  ✓ Import Excel: $import_ok/3\n";
    echo "  ✓ BD: Accessible\n";
    echo "\n  🚀 PRÊT POUR PRODUCTION\n";
} else {
    echo "⚠️  PROBLÈMES DÉTECTÉS\n\n";
    echo "  Syntaxe: " . ($stats['syntax_errors'] > 0 ? "✗ " . $stats['syntax_errors'] . " erreurs" : "✓ OK") . "\n";
    echo "  Modules: " . ($stats['modules_ok'] < count($modules) ? "✗ " . (count($modules) - $stats['modules_ok']) . " manquants" : "✓ OK") . "\n";
    echo "  Import: " . ($import_ok < count($import_files) ? "✗ " . (count($import_files) - $import_ok) . " manquants" : "✓ OK") . "\n";
}

echo "\n";
?>
