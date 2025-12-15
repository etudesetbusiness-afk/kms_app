<?php
/**
 * SCANNER EXHAUSTIF DE VARIABLES UNDEFINED
 * Détecte tous les problèmes de variables non initialisées dans le projet
 * Date: 15 décembre 2025
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(300); // 5 minutes max

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║     SCANNER EXHAUSTIF - VARIABLES UNDEFINED                 ║\n";
echo "║     KMS Gestion - 15 Décembre 2025                          ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

// Configuration
$root_dir = __DIR__;
$exclude_dirs = ['vendor', 'node_modules', '.git', 'cache', 'logs', 'uploads'];
$problems_found = [];
$files_scanned = 0;

// Superglobales PHP à ignorer
$superglobals = ['$_GET', '$_POST', '$_SESSION', '$_SERVER', '$_COOKIE', '$_FILES', '$_ENV', '$_REQUEST', '$GLOBALS'];

/**
 * Récupère tous les fichiers PHP récursivement
 */
function getAllPhpFiles($dir, $exclude_dirs) {
    $files = [];
    $items = @scandir($dir);
    
    if ($items === false) {
        return $files;
    }
    
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        
        // Ignorer les dossiers exclus
        if (is_dir($path)) {
            $basename = basename($path);
            if (in_array($basename, $exclude_dirs)) continue;
            $files = array_merge($files, getAllPhpFiles($path, $exclude_dirs));
        } elseif (is_file($path) && pathinfo($path, PATHINFO_EXTENSION) === 'php') {
            $files[] = $path;
        }
    }
    
    return $files;
}

/**
 * Analyse un fichier PHP pour détecter les variables undefined
 */
function analyzePhpFile($file, &$problems, $superglobals) {
    $content = @file_get_contents($file);
    if ($content === false) return;
    
    $lines = explode("\n", $content);
    $defined_vars = [];
    $used_vars = [];
    
    foreach ($lines as $line_num => $line) {
        $line_num++; // 1-indexed
        
        // Ignorer les commentaires
        if (preg_match('/^\s*(\/\/|#|\*)/', $line)) continue;
        
        // Détecter les définitions de variables (=)
        if (preg_match_all('/\$([a-zA-Z_][a-zA-Z0-9_]*)\s*=/', $line, $matches)) {
            foreach ($matches[1] as $var) {
                $defined_vars[$var] = true;
            }
        }
        
        // Détecter les utilisations de variables
        if (preg_match_all('/\$([a-zA-Z_][a-zA-Z0-9_]*)/', $line, $matches)) {
            foreach ($matches[0] as $full_var) {
                // Ignorer les superglobales
                if (in_array($full_var, $superglobals)) continue;
                
                $var_name = substr($full_var, 1); // Enlever $
                
                // Vérifier si utilisée avant définition
                if (!isset($defined_vars[$var_name]) && !isset($used_vars[$var_name])) {
                    // Vérifier si ce n'est pas une assignation
                    if (!preg_match('/\$' . preg_quote($var_name) . '\s*=/', $line)) {
                        $problems[] = [
                            'file' => $file,
                            'line' => $line_num,
                            'variable' => $full_var,
                            'context' => trim($line),
                            'severity' => 'WARNING'
                        ];
                    }
                }
                
                $used_vars[$var_name] = true;
            }
        }
    }
}

// Scanner tous les fichiers
echo "🔍 Recherche des fichiers PHP...\n";
$php_files = getAllPhpFiles($root_dir, $exclude_dirs);
$total_files = count($php_files);
echo "   Trouvé: $total_files fichiers PHP\n\n";

echo "📊 Analyse en cours...\n";
$progress = 0;

foreach ($php_files as $file) {
    $files_scanned++;
    analyzePhpFile($file, $problems_found, $superglobals);
    
    // Afficher la progression
    $new_progress = (int)(($files_scanned / $total_files) * 100);
    if ($new_progress > $progress && $new_progress % 10 === 0) {
        $progress = $new_progress;
        echo "   Progression: $progress% ($files_scanned/$total_files fichiers)\n";
    }
}

echo "\n";

// Filtrer les problèmes pour éliminer les faux positifs courants
$filtered_problems = [];
foreach ($problems_found as $problem) {
    $var = $problem['variable'];
    $context = $problem['context'];
    
    // Ignorer certains patterns courants
    if (preg_match('/foreach\s*\(.*as\s+' . preg_quote($var) . '/', $context)) continue;
    if (preg_match('/function\s+\w+\s*\([^)]*' . preg_quote($var) . '/', $context)) continue;
    if (preg_match('/global\s+' . preg_quote($var) . '/', $context)) continue;
    if (preg_match('/isset\s*\(\s*' . preg_quote($var) . '/', $context)) continue;
    if (preg_match('/empty\s*\(\s*' . preg_quote($var) . '/', $context)) continue;
    
    $filtered_problems[] = $problem;
}

// Grouper par fichier
$grouped = [];
foreach ($filtered_problems as $problem) {
    $file = str_replace($root_dir . DIRECTORY_SEPARATOR, '', $problem['file']);
    if (!isset($grouped[$file])) {
        $grouped[$file] = [];
    }
    $grouped[$file][] = $problem;
}

// Afficher les résultats
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║                    RÉSULTATS                                ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

echo "Fichiers scannés: $files_scanned\n";
echo "Problèmes bruts détectés: " . count($problems_found) . "\n";
echo "Problèmes après filtrage: " . count($filtered_problems) . "\n";
echo "Fichiers avec problèmes: " . count($grouped) . "\n\n";

if (count($grouped) > 0) {
    echo "⚠️  FICHIERS AVEC VARIABLES UNDEFINED:\n";
    echo str_repeat("═", 70) . "\n\n";
    
    foreach ($grouped as $file => $problems) {
        echo "📄 $file (" . count($problems) . " problème(s))\n";
        echo str_repeat("─", 70) . "\n";
        
        foreach ($problems as $problem) {
            echo sprintf("   Ligne %d: %s\n", $problem['line'], $problem['variable']);
            echo sprintf("   Contexte: %s\n", substr($problem['context'], 0, 60));
            echo "\n";
        }
    }
    
    // Sauvegarder dans un fichier
    $report_file = $root_dir . '/RAPPORT_VARIABLES_UNDEFINED.json';
    file_put_contents($report_file, json_encode([
        'date' => date('Y-m-d H:i:s'),
        'files_scanned' => $files_scanned,
        'problems_count' => count($filtered_problems),
        'problems' => $filtered_problems,
        'grouped' => $grouped
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    echo "\n📄 Rapport JSON sauvegardé: RAPPORT_VARIABLES_UNDEFINED.json\n";
    
} else {
    echo "✅ Aucun problème détecté!\n";
}

echo "\n✅ Analyse terminée!\n";
?>
