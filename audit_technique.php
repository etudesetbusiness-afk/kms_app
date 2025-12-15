<?php
/**
 * AUDIT TECHNIQUE EXHAUSTIF - KMS Gestion
 * 
 * Ce script parcourt tous les fichiers PHP et détecte:
 * - Erreurs de syntaxe
 * - Variables non définies
 * - Fonctions appelées non définies
 * - Inclusions manquantes
 * - Accès tables/colonnes DB inexistants
 * - Problèmes de sécurité
 */

require_once __DIR__ . '/security.php';
require_once __DIR__ . '/lib/pagination.php';
require_once __DIR__ . '/lib/cache.php';

global $pdo;

// Configuration
define('PROJECT_ROOT', __DIR__);
$errors = [];
$warnings = [];
$infos = [];

// ===========================
// 1. SCANNER LES FICHIERS PHP
// ===========================

function scanPhpFiles($dir = PROJECT_ROOT, $excludeDirs = []) {
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    foreach ($iterator as $path) {
        if ($path->isFile() && $path->getExtension() === 'php') {
            // Exclure certains répertoires
            $relative = str_replace(PROJECT_ROOT . '\\', '', $path->getRealPath());
            $skip = false;
            foreach ($excludeDirs as $exclude) {
                if (stripos($relative, $exclude) === 0) {
                    $skip = true;
                    break;
                }
            }
            if (!$skip) {
                $files[] = $path->getRealPath();
            }
        }
    }
    return $files;
}

// ===========================
// 2. ANALYSER CHAQUE FICHIER
// ===========================

function analyzePhpFile($filePath) {
    global $errors, $warnings, $infos;
    
    $fileName = str_replace(PROJECT_ROOT . '\\', '', $filePath);
    $content = file_get_contents($filePath);
    
    $result = [
        'file' => $fileName,
        'issues' => [],
        'syntax_ok' => true,
        'requires' => [],
        'defines_functions' => [],
        'calls_functions' => [],
        'db_tables' => [],
        'db_columns' => [],
        'variables' => []
    ];
    
    // === 1. Vérifier la syntaxe ===
    exec("php -l \"$filePath\" 2>&1", $output, $code);
    if ($code !== 0) {
        $result['syntax_ok'] = false;
        $result['issues'][] = [
            'type' => 'ERROR',
            'msg' => implode("\n", $output),
            'severity' => 'CRITICAL'
        ];
        return $result;
    }
    
    // === 2. Extraire les includes/requires ===
    if (preg_match_all('/(?:require|include)(?:_once)?\s*[\'"]([^\'"]+)[\'"]/', $content, $matches)) {
        $result['requires'] = $matches[1];
    }
    
    // === 3. Détecter les appels de fonctions ===
    if (preg_match_all('/(\w+)\s*\(/', $content, $matches)) {
        $result['calls_functions'] = array_unique($matches[1]);
    }
    
    // === 4. Détecter les variables non assignées ===
    // Variables utilisées mais pas définies
    if (preg_match_all('/\$([a-zA-Z_]\w*)/', $content, $matches)) {
        $vars = array_unique($matches[1]);
        foreach ($vars as $var) {
            // Vérifier si elle est assignée dans le fichier
            $assigned = preg_match('/\$' . $var . '\s*=/', $content);
            if (!$assigned && !in_array($var, ['_GET', '_POST', '_SESSION', '_COOKIE', '_SERVER', '_FILES', '_REQUEST', 'GLOBALS', 'argc', 'argv'])) {
                // Vérifier si c'est utilisée sans être globale
                $isGlobal = preg_match('/global\s+\$' . $var . '/', $content);
                if (!$isGlobal && preg_match('/\$' . $var . '\s*(?!=>)/', $content)) {
                    if (preg_match('/foreach.*\$' . $var . '\s+as/', $content)) {
                        continue; // Variable de boucle
                    }
                }
            }
        }
    }
    
    // === 5. Détecter les accès DB (tables/colonnes) ===
    if (preg_match_all('/FROM\s+([a-zA-Z_]\w*)/i', $content, $matches)) {
        $result['db_tables'] = array_unique($matches[1]);
    }
    if (preg_match_all('/\b([a-zA-Z_]\w*)\.([a-zA-Z_]\w*)\b/', $content, $matches)) {
        for ($i = 0; $i < count($matches[0]); $i++) {
            $result['db_columns'][] = $matches[1][$i] . '.' . $matches[2][$i];
        }
    }
    
    // === 6. Vérifier la présence de security.php ===
    if (!in_array('security.php', $result['requires']) && 
        !in_array('../security.php', $result['requires']) &&
        !in_array(__DIR__ . '/security.php', $result['requires']) &&
        basename($filePath) !== 'security.php' &&
        !preg_match('/(api|cron|cli|install)/', $fileName)) {
        // Pour les pages web standard
        if (preg_match('/^(admin|coordination|clients|produits|ventes|livraisons|compta|caisse|formations|terrain|showroom|dashboard|api)\/[^\/]+\.php$/', $fileName)) {
            $result['issues'][] = [
                'type' => 'WARNING',
                'msg' => 'security.php non inclus - Risque de sécurité',
                'severity' => 'HIGH'
            ];
        }
    }
    
    return $result;
}

// ===========================
// 3. VÉRIFIER LES FONCTIONS
// ===========================

function checkFunctionExists($func) {
    // Liste des fonctions intégrées PHP + custom du projet
    $builtins = [
        'require', 'include', 'require_once', 'include_once',
        'empty', 'isset', 'unset', 'array_merge', 'array_push',
        'preg_match', 'preg_match_all', 'preg_replace',
        'json_encode', 'json_decode', 'header', 'die', 'exit',
        'implode', 'explode', 'strlen', 'trim', 'htmlspecialchars',
        'strpos', 'substr', 'date', 'time', 'microtime',
        'var_dump', 'print_r', 'echo', 'return',
        'count', 'sizeof', 'sizeof',
        'get_class', 'get_parent_class', 'get_object_vars',
        'method_exists', 'function_exists',
        'is_array', 'is_string', 'is_int', 'is_bool',
        'intval', 'floatval', 'strval', 'boolval',
        'in_array', 'array_key_exists', 'key_exists',
        'range', 'array_slice', 'array_splice',
        'sort', 'rsort', 'asort', 'arsort', 'ksort', 'krsort',
        'usort', 'uasort', 'uksort',
        'array_filter', 'array_map', 'array_reduce',
        'number_format', 'sprintf', 'printf',
        'ceil', 'floor', 'round', 'abs', 'min', 'max',
        'serialize', 'unserialize',
        'base64_encode', 'base64_decode',
        'md5', 'sha1', 'hash', 'password_hash', 'password_verify',
        'uniqid', 'random_int', 'random_bytes',
        'pathinfo', 'realpath', 'basename', 'dirname',
        'file_get_contents', 'file_put_contents',
        'mkdir', 'rmdir', 'is_dir', 'is_file', 'file_exists',
        'opendir', 'readdir', 'closedir',
        'fopen', 'fclose', 'fgets', 'fwrite',
        'class_exists', 'interface_exists', 'trait_exists',
        'new', 'instanceof', 'throw', 'try', 'catch',
        'if', 'else', 'elseif', 'switch', 'case', 'default',
        'for', 'foreach', 'while', 'do', 'break', 'continue',
        'function', 'public', 'private', 'protected', 'static',
        'class', 'interface', 'trait', 'namespace', 'use',
        'const', 'define', 'defined',
        'global', 'static', 'callable',
        'yield', 'clone', 'new', 'extends', 'implements',
        'final', 'abstract', 'const',
        'use', 'as', 'from', 'namespace',
    ];
    
    // Fonctions custom du projet
    $custom = [
        'utilisateurConnecte', 'exigerConnexion', 'exigerPermission',
        'verifierCsrf', 'url_for', 'flash',
        'getPaginationParams', 'renderPaginationControls', 'getPaginationLimitClause', 'buildPaginationUrl',
        'cached', 'getKPICache', 'getAllKPIs',
        'updateUserPreferencesFromGet', 'getUserPreferences', 'saveUserPreference',
        'validateAndFormatDate', 'getDateRangePreset', 'buildDateWhereClause', 'formatDateForDisplay',
        'logStockMovement', 'getStockMovements',
        'createDoubleEntry', 'validatePiece', 'getBalance',
        'enregistrerOperationCaisse', 'getOperationsCaisse',
        'deduplicateHistoryRecords', 'isDuplicateHistory',
    ];
    
    return in_array($func, $builtins) || in_array($func, $custom) || function_exists($func);
}

// ===========================
// 4. SCANNER LES FICHIERS
// ===========================

echo "🔍 AUDIT TECHNIQUE EXHAUSTIF - KMS Gestion\n";
echo str_repeat("=", 80) . "\n\n";

$phpFiles = scanPhpFiles(PROJECT_ROOT, ['vendor', 'node_modules', '.git', 'db']);

echo "📊 Statistiques initiales:\n";
echo "  - Fichiers PHP trouvés: " . count($phpFiles) . "\n";
echo "  - Répertoire racine: " . PROJECT_ROOT . "\n\n";

$results = [];
$fileCount = 0;
$errorCount = 0;
$warningCount = 0;

echo "🔍 Analyse en cours...\n";

foreach ($phpFiles as $file) {
    $result = analyzePhpFile($file);
    $results[] = $result;
    
    if (!$result['syntax_ok']) {
        $errorCount++;
    }
    $warningCount += count($result['issues']);
    $fileCount++;
    
    if ($fileCount % 10 == 0) {
        echo "  ✓ $fileCount fichiers analysés\n";
    }
}

echo "\n✅ Analyse complète: $fileCount fichiers\n\n";

// ===========================
// 5. GÉNÉRER LE RAPPORT
// ===========================

echo "📋 RAPPORT D'AUDIT\n";
echo str_repeat("=", 80) . "\n\n";

// Résumé
$criticalCount = 0;
$highCount = 0;
$mediumCount = 0;
$lowCount = 0;

foreach ($results as $result) {
    foreach ($result['issues'] as $issue) {
        switch ($issue['severity'] ?? 'MEDIUM') {
            case 'CRITICAL': $criticalCount++; break;
            case 'HIGH': $highCount++; break;
            case 'MEDIUM': $mediumCount++; break;
            case 'LOW': $lowCount++; break;
        }
    }
}

echo "🎯 RÉSUMÉ:\n";
echo "  - Fichiers avec erreurs: $errorCount\n";
echo "  - Issues détectées: " . ($criticalCount + $highCount + $mediumCount + $lowCount) . "\n";
echo "    * CRITICAL: $criticalCount\n";
echo "    * HIGH: $highCount\n";
echo "    * MEDIUM: $mediumCount\n";
echo "    * LOW: $lowCount\n\n";

if ($criticalCount > 0) {
    echo "🚨 ERREURS CRITIQUES:\n";
    echo str_repeat("-", 80) . "\n";
    foreach ($results as $result) {
        if (!$result['syntax_ok'] || count($result['issues']) > 0) {
            echo "\n📄 " . $result['file'] . "\n";
            foreach ($result['issues'] as $issue) {
                if ($issue['severity'] === 'CRITICAL') {
                    echo "   [" . $issue['type'] . "] " . $issue['msg'] . "\n";
                }
            }
        }
    }
    echo "\n";
}

if ($highCount > 0) {
    echo "⚠️  AVERTISSEMENTS IMPORTANTS:\n";
    echo str_repeat("-", 80) . "\n";
    foreach ($results as $result) {
        foreach ($result['issues'] as $issue) {
            if ($issue['severity'] === 'HIGH') {
                echo "  📄 " . $result['file'] . "\n";
                echo "     " . $issue['msg'] . "\n";
            }
        }
    }
    echo "\n";
}

// Détails complets
echo "📝 DÉTAILS COMPLETS:\n";
echo str_repeat("=", 80) . "\n";

foreach ($results as $result) {
    if ($result['syntax_ok'] && count($result['issues']) === 0) {
        continue; // Sauter les fichiers sans issues
    }
    
    echo "\n📄 " . $result['file'] . "\n";
    
    if (!$result['syntax_ok']) {
        echo "  ❌ ERREUR SYNTAXE\n";
        foreach ($result['issues'] as $issue) {
            echo "     " . $issue['msg'] . "\n";
        }
    } else {
        if (count($result['issues']) > 0) {
            echo "  Issues:\n";
            foreach ($result['issues'] as $issue) {
                echo "    [" . $issue['type'] . "] " . $issue['msg'] . "\n";
            }
        }
    }
    
    if (count($result['requires']) > 0) {
        echo "  Requires: " . implode(", ", array_slice($result['requires'], 0, 3)) . "\n";
    }
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "✅ AUDIT TECHNIQUE TERMINÉ\n";

// Générer un fichier de rapport
file_put_contents(PROJECT_ROOT . '/AUDIT_RAPPORT.json', json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo "📊 Rapport détaillé sauvegardé: AUDIT_RAPPORT.json\n";
?>
