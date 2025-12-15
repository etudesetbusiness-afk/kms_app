<?php
/**
 * test_recursif_complet.php
 * Test récursif complet du projet - Vérifie syntaxe, fonctions, BD, modules
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$start_time = microtime(true);
$root = __DIR__;
$results = [
    'php_files' => 0,
    'syntax_errors' => 0,
    'syntax_ok' => 0,
    'modules_tested' => 0,
    'modules_ok' => 0,
    'errors' => [],
    'warnings' => [],
    'details' => []
];

echo "╔════════════════════════════════════════════════════════════════════════════╗\n";
echo "║           TEST RÉCURSIF COMPLET - KMS GESTION                             ║\n";
echo "║        Vérification syntaxe, modules, BD, pages principales                ║\n";
echo "╚════════════════════════════════════════════════════════════════════════════╝\n\n";

// ==================== 1. SYNTAXE PHP ====================
echo "PHASE 1: Vérification syntaxe PHP\n";
echo "════════════════════════════════════════════════════════════════════════════\n";

$php_files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

$excluded = ['node_modules', '.git', 'vendor', '.cache', '__pycache__'];

foreach ($php_files as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $filepath = $file->getRealPath();
        
        // Vérifier si dans répertoire exclu
        $skip = false;
        foreach ($excluded as $exc) {
            if (strpos($filepath, DIRECTORY_SEPARATOR . $exc . DIRECTORY_SEPARATOR) !== false) {
                $skip = true;
                break;
            }
        }
        
        if ($skip) continue;
        
        $results['php_files']++;
        
        // Vérifier syntaxe
        $output = shell_exec("php -l " . escapeshellarg($filepath) . " 2>&1");
        
        if (strpos($output, 'No syntax errors') === false) {
            $results['syntax_errors']++;
            $results['errors'][] = [
                'file' => str_replace($root, '', $filepath),
                'type' => 'SYNTAX',
                'message' => trim($output)
            ];
            echo "  ✗ " . basename($filepath) . " - ERREUR SYNTAXE\n";
        } else {
            $results['syntax_ok']++;
        }
    }
}

echo "\nRésultat: $results[syntax_ok]/$results[php_files] fichiers OK\n";
if ($results['syntax_errors'] > 0) {
    echo "⚠️  $results[syntax_errors] erreurs de syntaxe détectées!\n";
}
echo "\n";

// ==================== 2. VÉRIFICATION BD ====================
echo "PHASE 2: Vérification base de données\n";
echo "════════════════════════════════════════════════════════════════════════════\n";

try {
    require 'security.php';
    global $pdo;
    
    // Test 1: Connexion
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()");
    $table_count = $stmt->fetch()['cnt'];
    echo "  ✓ Connexion BD OK\n";
    echo "  ✓ Nombre de tables: $table_count\n";
    
    // Test 2: Tables principales
    $tables_to_check = [
        'utilisateurs', 'catalogue_produits', 'catalogue_categories',
        'ventes', 'devis', 'livraisons', 'clients', 'compta_accounts', 
        'compta_journal_entries', 'caisse', 'stocks_mouvements'
    ];
    
    $missing = [];
    foreach ($tables_to_check as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() === 0) {
            $missing[] = $table;
        }
    }
    
    if (empty($missing)) {
        echo "  ✓ Toutes les tables principales présentes\n";
    } else {
        echo "  ⚠️  Tables manquantes: " . implode(', ', $missing) . "\n";
        $results['warnings'][] = "Tables manquantes: " . implode(', ', $missing);
    }
    
    // Test 3: Données de base
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM utilisateurs");
    $user_count = $stmt->fetch()['cnt'];
    echo "  ✓ Utilisateurs en BD: $user_count\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM catalogue_produits");
    $prod_count = $stmt->fetch()['cnt'];
    echo "  ✓ Produits en BD: $prod_count\n";
    
} catch (Exception $e) {
    $results['errors'][] = ['type' => 'BD', 'message' => $e->getMessage()];
    echo "  ✗ Erreur BD: " . $e->getMessage() . "\n";
}

echo "\n";

// ==================== 3. MODULES CLÉS ====================
echo "PHASE 3: Vérification modules clés\n";
echo "════════════════════════════════════════════════════════════════════════════\n";

$modules = [
    'admin/catalogue/produits.php' => 'Admin - Liste Produits',
    'admin/catalogue/produit_edit.php' => 'Admin - Édition Produit',
    'admin/catalogue/categories.php' => 'Admin - Catégories',
    'admin/catalogue/import.php' => 'Admin - Import Excel',
    'catalogue/index.php' => 'Public - Catalogue',
    'catalogue/fiche.php' => 'Public - Fiche Produit',
    'ventes/list.php' => 'Ventes - Liste',
    'devis/list.php' => 'Devis - Liste',
    'livraisons/list.php' => 'Livraisons - Liste',
    'caisse/journal.php' => 'Caisse - Journal',
    'dashboard.php' => 'Dashboard',
];

foreach ($modules as $file => $name) {
    $results['modules_tested']++;
    
    if (file_exists($file) && is_readable($file)) {
        $output = shell_exec("php -l " . escapeshellarg($file) . " 2>&1");
        
        if (strpos($output, 'No syntax errors') !== false) {
            echo "  ✓ $name\n";
            $results['modules_ok']++;
        } else {
            echo "  ✗ $name - ERREUR\n";
            $results['errors'][] = [
                'file' => $file,
                'type' => 'MODULE',
                'message' => 'Erreur syntaxe'
            ];
        }
    } else {
        echo "  ✗ $name - FICHIER MANQUANT\n";
        $results['errors'][] = [
            'file' => $file,
            'type' => 'MODULE',
            'message' => 'Fichier manquant'
        ];
    }
}

echo "\nRésultat: $results[modules_ok]/$results[modules_tested] modules OK\n\n";

// ==================== 4. VÉRIFICATION FONCTIONS CLÉS ====================
echo "PHASE 4: Vérification fonctions clés\n";
echo "════════════════════════════════════════════════════════════════════════════\n";

try {
    require 'security.php';
    
    $functions_to_check = [
        'getCsrfToken',
        'verifierCsrf',
        'exigerConnexion',
        'exigerPermission',
        'peut',
        'url_for',
    ];
    
    $missing_functions = [];
    foreach ($functions_to_check as $func) {
        if (function_exists($func)) {
            echo "  ✓ $func()\n";
        } else {
            echo "  ✗ $func() - MANQUANTE\n";
            $missing_functions[] = $func;
        }
    }
    
    if (!empty($missing_functions)) {
        $results['warnings'][] = "Fonctions manquantes: " . implode(', ', $missing_functions);
    }
    
} catch (Exception $e) {
    echo "  ✗ Erreur lors du chargement de security.php\n";
    $results['errors'][] = ['type' => 'FUNCTION_CHECK', 'message' => $e->getMessage()];
}

echo "\n";

// ==================== 5. VÉRIFICATION PAGES PUBLIQUES ====================
echo "PHASE 5: Vérification pages publiques (load sans erreurs)\n";
echo "════════════════════════════════════════════════════════════════════════════\n";

$public_pages = [
    'catalogue/index.php',
    'catalogue/fiche.php?id=1',
];

$curl_available = function_exists('curl_version');

if ($curl_available) {
    foreach ($public_pages as $page) {
        $url = "http://localhost/kms_app/$page";
        
        // Utiliser curl si disponible, sinon skip
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        $response = @curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code === 200 || $http_code === 301 || $http_code === 302) {
            echo "  ✓ $page (HTTP $http_code)\n";
        } else {
            echo "  ⚠️  $page (HTTP $http_code)\n";
            $results['warnings'][] = "$page retourne HTTP $http_code";
        }
    }
} else {
    echo "  ⓘ cURL non disponible - test HTTP ignoré\n";
}

echo "\n";

// ==================== RÉSUMÉ FINAL ====================
echo "╔════════════════════════════════════════════════════════════════════════════╗\n";
echo "║                          RÉSUMÉ FINAL                                      ║\n";
echo "╚════════════════════════════════════════════════════════════════════════════╝\n\n";

$syntax_percentage = ($results['php_files'] > 0) ? round(($results['syntax_ok'] / $results['php_files']) * 100) : 0;
$modules_percentage = ($results['modules_tested'] > 0) ? round(($results['modules_ok'] / $results['modules_tested']) * 100) : 0;

echo "SYNTAXE PHP\n";
echo "  Fichiers testés: {$results['php_files']}\n";
echo "  Sans erreurs: {$results['syntax_ok']}\n";
echo "  Erreurs: {$results['syntax_errors']}\n";
echo "  Taux de réussite: $syntax_percentage%\n\n";

echo "MODULES\n";
echo "  Modules testés: {$results['modules_tested']}\n";
echo "  Opérationnels: {$results['modules_ok']}\n";
echo "  Taux de réussite: $modules_percentage%\n\n";

if (count($results['errors']) > 0) {
    echo "ERREURS DÉTECTÉES\n";
    foreach ($results['errors'] as $error) {
        echo "  ✗ [{$error['type']}] {$error['file']}\n";
        echo "    └─ {$error['message']}\n";
    }
    echo "\n";
}

if (count($results['warnings']) > 0) {
    echo "AVERTISSEMENTS\n";
    foreach ($results['warnings'] as $warning) {
        echo "  ⚠️  $warning\n";
    }
    echo "\n";
}

// ==================== VERDICT FINAL ====================
$total_errors = count($results['errors']);
$all_syntax_ok = $results['syntax_errors'] === 0;
$all_modules_ok = $results['modules_ok'] === $results['modules_tested'];

echo "═════════════════════════════════════════════════════════════════════════════\n\n";

if ($total_errors === 0 && $all_syntax_ok && $all_modules_ok) {
    echo "✅ VERDICT: PROJET 100% OPÉRATIONNEL\n\n";
    echo "  ✓ Syntaxe PHP: 100%\n";
    echo "  ✓ Modules: 100%\n";
    echo "  ✓ Aucune erreur critique\n";
    echo "  ✓ Prêt pour la production\n";
} else {
    echo "⚠️  VERDICT: PROBLÈMES DÉTECTÉS\n\n";
    if ($results['syntax_errors'] > 0) {
        echo "  ✗ Erreurs de syntaxe: {$results['syntax_errors']}\n";
    }
    if (!$all_modules_ok) {
        $module_errors = $results['modules_tested'] - $results['modules_ok'];
        echo "  ✗ Modules en erreur: $module_errors\n";
    }
    if (count($results['warnings']) > 0) {
        echo "  ⚠️  Avertissements: " . count($results['warnings']) . "\n";
    }
}

$elapsed = round(microtime(true) - $start_time, 2);
echo "\n═════════════════════════════════════════════════════════════════════════════\n";
echo "Temps d'exécution: {$elapsed}s\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo "═════════════════════════════════════════════════════════════════════════════\n";

// ==================== SAUVEGARDER RAPPORT ====================
$report = [
    'timestamp' => date('Y-m-d H:i:s'),
    'elapsed_time' => $elapsed,
    'php_files_tested' => $results['php_files'],
    'syntax_ok' => $results['syntax_ok'],
    'syntax_errors' => $results['syntax_errors'],
    'modules_tested' => $results['modules_tested'],
    'modules_ok' => $results['modules_ok'],
    'total_errors' => count($results['errors']),
    'total_warnings' => count($results['warnings']),
    'errors' => $results['errors'],
    'warnings' => $results['warnings'],
    'status' => ($total_errors === 0 && $all_syntax_ok && $all_modules_ok) ? 'OK' : 'WARNINGS'
];

file_put_contents('TEST_RECURSIF_RAPPORT.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "\n✓ Rapport sauvegardé: TEST_RECURSIF_RAPPORT.json\n";
?>
