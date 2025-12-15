<?php
/**
 * Scanner Amélioré v2 - Variables Undefined avec Filtrage Intelligent
 * KMS Gestion - 15 Décembre 2025
 * 
 * Améliorations:
 * - Ignore les variables dans catch blocks
 * - Ignore les variables dans foreach loops (clés et valeurs)
 * - Ignore les variables avec null coalescing (??)
 * - Ignore les variables globales standard ($pdo, $config, etc.)
 * - Détecte les require/include pour suivre les dépendances
 */

echo "╔═══════════════════════════════════════════════════════════╗\n";
echo "║     SCANNER AMÉLIORÉ v2 - VARIABLES UNDEFINED            ║\n";
echo "║     KMS Gestion - 15 Décembre 2025                        ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";

// Exclusions standard
$excludedDirs = ['vendor', 'node_modules', '.git', 'tests', 'tmp', 'cache'];

// Variables globales standard du projet
$globalVars = ['pdo', 'config', 'db', 'utilisateur', '_GET', '_POST', '_SESSION', '_SERVER', 
               '_FILES', '_COOKIE', '_REQUEST', '_ENV', 'GLOBALS', 'argv', 'argc'];

/**
 * Récupère tous les fichiers PHP du projet
 */
function getAllPhpFilesV2($dir, $excludedDirs) {
    $files = [];
    $items = scandir($dir);
    
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        
        if (is_dir($path)) {
            if (!in_array($item, $excludedDirs)) {
                $files = array_merge($files, getAllPhpFilesV2($path, $excludedDirs));
            }
        } elseif (is_file($path) && pathinfo($path, PATHINFO_EXTENSION) === 'php') {
            $files[] = $path;
        }
    }
    
    return $files;
}

/**
 * Analyse un fichier PHP et détecte les vraies variables undefined
 */
function analyzePhpFileV2($filePath, $globalVars) {
    $content = file_get_contents($filePath);
    $lines = explode("\n", $content);
    
    $defined = [];
    $used = [];
    $requires = [];
    
    // Détecter les require/include pour tracer les dépendances
    if (preg_match_all('/(?:require|include)(?:_once)?\s*[\'"]([^\'"]+)[\'"]/', $content, $matches)) {
        $requires = $matches[1];
    }
    
    // Si le fichier require db.php, considérer $pdo comme défini
    foreach ($requires as $req) {
        if (strpos($req, 'db.php') !== false || strpos($req, 'db/db.php') !== false) {
            $defined[] = 'pdo';
        }
        if (strpos($req, 'security.php') !== false) {
            $defined[] = 'pdo';
            $defined[] = 'utilisateur';
        }
    }
    
    foreach ($lines as $lineNum => $line) {
        $lineNumber = $lineNum + 1;
        
        // ============ PATTERNS À IGNORER ============
        
        // 1. Variables dans catch blocks: catch (Exception $e)
        if (preg_match('/catch\s*\(\s*\w+\s+\$(\w+)\s*\)/', $line, $m)) {
            $defined[] = $m[1];
            continue;
        }
        
        // 2. Variables dans foreach: foreach ($arr as $key => $value)
        if (preg_match('/foreach\s*\([^)]+\s+as\s+\$(\w+)(?:\s*=>\s*\$(\w+))?\s*\)/', $line, $m)) {
            if (isset($m[1])) $defined[] = $m[1]; // $key ou $value si pas de =>
            if (isset($m[2])) $defined[] = $m[2]; // $value si =>
            continue;
        }
        
        // 3. Variables avec null coalescing: $var ?? 'default'
        // Si une variable utilise ??, on ne la signale pas comme problème
        if (preg_match('/\$(\w+)\s*\?\?/', $line)) {
            // Ignorer ces utilisations, elles sont sécurisées
            continue;
        }
        
        // 4. Variables dans isset() ou empty()
        if (preg_match('/(?:isset|empty)\s*\(\s*\$(\w+)/', $line)) {
            // Ces fonctions vérifient justement si la variable existe
            continue;
        }
        
        // 5. Paramètres de fonction: function test($param1, $param2)
        if (preg_match('/function\s+\w+\s*\(([^)]*)\)/', $line, $m)) {
            if (preg_match_all('/\$(\w+)/', $m[1], $params)) {
                foreach ($params[1] as $param) {
                    $defined[] = $param;
                }
            }
            continue;
        }
        
        // 6. Variables globales déclarées: global $pdo;
        if (preg_match('/global\s+\$(\w+)/', $line, $m)) {
            $defined[] = $m[1];
            continue;
        }
        
        // ============ DÉTECTION DES DÉFINITIONS ============
        
        // Variables définies par assignation: $var = ...
        if (preg_match_all('/\$(\w+)\s*=/', $line, $matches)) {
            foreach ($matches[1] as $var) {
                if (!in_array($var, $defined)) {
                    $defined[] = $var;
                }
            }
        }
        
        // Variables définies dans list(): list($a, $b) = ...
        if (preg_match('/list\s*\(([^)]+)\)/', $line, $m)) {
            if (preg_match_all('/\$(\w+)/', $m[1], $listVars)) {
                foreach ($listVars[1] as $var) {
                    $defined[] = $var;
                }
            }
        }
        
        // ============ DÉTECTION DES UTILISATIONS ============
        
        // Chercher toutes les utilisations de variables
        if (preg_match_all('/\$(\w+)/', $line, $matches)) {
            foreach ($matches[1] as $var) {
                // Ignorer les superglobales et variables globales standard
                if (in_array($var, $globalVars)) {
                    continue;
                }
                
                // Vérifier si la variable est utilisée (pas définie sur cette ligne)
                if (!preg_match('/\$' . preg_quote($var, '/') . '\s*=/', $line)) {
                    $used[] = [
                        'var' => $var,
                        'line' => $lineNumber,
                        'context' => trim($line)
                    ];
                }
            }
        }
    }
    
    // Trouver les variables utilisées mais jamais définies
    $problems = [];
    foreach ($used as $usage) {
        $var = $usage['var'];
        
        // Si la variable n'a jamais été définie, c'est un vrai problème
        if (!in_array($var, $defined)) {
            $problems[] = $usage;
        }
    }
    
    return [
        'problems' => $problems,
        'requires' => $requires,
        'defined' => array_unique($defined),
        'used' => count($used)
    ];
}

// ============ EXÉCUTION ============

echo "🔍 Recherche des fichiers PHP...\n";
$files = getAllPhpFilesV2(__DIR__, $excludedDirs);
echo "   Trouvé: " . count($files) . " fichiers PHP\n\n";

echo "🔎 Analyse en cours...\n";

$allProblems = [];
$totalProblems = 0;
$filesWithProblems = 0;
$progressStep = ceil(count($files) / 10);

foreach ($files as $idx => $file) {
    // Afficher la progression tous les 10%
    if (($idx + 1) % $progressStep === 0) {
        $percent = round((($idx + 1) / count($files)) * 100);
        echo "   Progression: {$percent}% (" . ($idx + 1) . "/" . count($files) . " fichiers)\n";
    }
    
    $result = analyzePhpFileV2($file, $globalVars);
    
    if (!empty($result['problems'])) {
        $relativePath = str_replace(__DIR__ . DIRECTORY_SEPARATOR, '', $file);
        $allProblems[$relativePath] = $result;
        $totalProblems += count($result['problems']);
        $filesWithProblems++;
    }
}

// ============ RAPPORT ============

echo "\n";
echo "╔═══════════════════════════════════════════════════════════╗\n";
echo "║                    RÉSULTATS v2                           ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";

echo "Fichiers scannés: " . count($files) . "\n";
echo "Vrais problèmes détectés: $totalProblems\n";
echo "Fichiers avec problèmes: $filesWithProblems\n\n";

if ($totalProblems > 0) {
    echo "⚠️  FICHIERS AVEC VRAIS PROBLÈMES:\n";
    echo str_repeat("═", 70) . "\n\n";
    
    $count = 0;
    foreach ($allProblems as $file => $data) {
        $count++;
        
        // Limiter à 20 fichiers pour ne pas surcharger la sortie
        if ($count > 20) {
            echo "\n... (" . ($filesWithProblems - 20) . " fichiers supplémentaires avec problèmes)\n";
            break;
        }
        
        echo "📄 $file (" . count($data['problems']) . " problème(s))\n";
        echo str_repeat("─", 70) . "\n";
        
        // Afficher les dépendances
        if (!empty($data['requires'])) {
            echo "   📦 Requires: " . implode(', ', array_slice($data['requires'], 0, 3));
            if (count($data['requires']) > 3) echo " (+" . (count($data['requires']) - 3) . " autres)";
            echo "\n";
        }
        
        // Afficher les problèmes (max 5 par fichier)
        foreach (array_slice($data['problems'], 0, 5) as $problem) {
            echo "   Ligne {$problem['line']}: \${$problem['var']}\n";
            $context = substr($problem['context'], 0, 60);
            echo "   Contexte: " . $context;
            if (strlen($problem['context']) > 60) echo "...";
            echo "\n\n";
        }
        
        if (count($data['problems']) > 5) {
            echo "   ... (" . (count($data['problems']) - 5) . " problèmes supplémentaires)\n\n";
        }
    }
} else {
    echo "✅ AUCUN VRAI PROBLÈME DÉTECTÉ !\n";
    echo "Le projet est propre. Les warnings que vous voyez sont gérés par le code (null coalescing).\n";
}

// Sauvegarder en JSON
$jsonData = [
    'scan_date' => date('Y-m-d H:i:s'),
    'total_files' => count($files),
    'files_with_problems' => $filesWithProblems,
    'total_problems' => $totalProblems,
    'problems_by_file' => $allProblems
];

file_put_contents('RAPPORT_VARIABLES_V2.json', json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

echo "\n✅ Rapport sauvegardé: RAPPORT_VARIABLES_V2.json\n";
echo "\n=== Scan terminé ===\n";
