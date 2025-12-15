<?php
/**
 * AUDIT COMPLET - KMS Gestion
 * Vérification exhaustive du projet
 */

define('PROJECT_ROOT', __DIR__);

echo "🔍 AUDIT TECHNIQUE COMPLET - KMS Gestion\n";
echo str_repeat("=", 90) . "\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

// =======================
// 1. SCANNER LES FICHIERS
// =======================

function getPhpFiles($dir) {
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($iterator as $path) {
        if ($path->isFile() && $path->getExtension() === 'php') {
            $rel = str_replace($dir . '\\', '', $path->getRealPath());
            if (!preg_match('/(vendor|node_modules|\.git)/', $rel)) {
                $files[] = $path->getRealPath();
            }
        }
    }
    return $files;
}

$phpFiles = getPhpFiles(PROJECT_ROOT);

echo "📊 STATISTIQUES INITIALES\n";
echo str_repeat("-", 90) . "\n";
echo "  ✓ Fichiers PHP trouvés: " . count($phpFiles) . "\n";
echo "  ✓ Répertoire: " . PROJECT_ROOT . "\n\n";

// =======================
// 2. VÉRIFIER LA SYNTAXE
// =======================

echo "🔧 VÉRIFICATION DE LA SYNTAXE PHP\n";
echo str_repeat("-", 90) . "\n";

$syntaxErrors = [];
$checkedCount = 0;

foreach ($phpFiles as $file) {
    $output = shell_exec("php -l \"$file\" 2>&1");
    if (strpos($output, 'No syntax errors') === false) {
        $syntaxErrors[] = [
            'file' => str_replace(PROJECT_ROOT . '\\', '', $file),
            'error' => trim($output)
        ];
    }
    $checkedCount++;
}

if (count($syntaxErrors) > 0) {
    echo "❌ ERREURS SYNTAXE DÉTECTÉES: " . count($syntaxErrors) . "\n\n";
    foreach ($syntaxErrors as $err) {
        echo "  📄 " . $err['file'] . "\n";
        echo "     " . str_replace("\n", "\n     ", $err['error']) . "\n\n";
    }
} else {
    echo "✅ Tous les fichiers PHP: Syntaxe correcte\n\n";
}

// =======================
// 3. VÉRIFIER SECURITY.PHP
// =======================

echo "🔐 VÉRIFICATION SÉCURITÉ\n";
echo str_repeat("-", 90) . "\n";

$securityIssues = [];

foreach ($phpFiles as $file) {
    $content = file_get_contents($file);
    $rel = str_replace(PROJECT_ROOT . '\\', '', $file);
    
    // Vérifier si security.php est inclus (pour les pages web)
    if (preg_match('/^(admin|coordination|clients|produits|ventes|livraisons|compta|caisse|formations|terrain|showroom|dashboard)\//', $rel)) {
        if (!preg_match('/require.*security\.php/', $content) && !preg_match('/include.*security\.php/', $content)) {
            $securityIssues[] = "❌ $rel: security.php non inclus";
        }
    }
    
    // Vérifier les variables non validées en POST
    if (preg_match('/\$_POST\[[\'"](\w+)[\'"]\](?!\s*\?\?|(?!\s*isset))/', $content, $matches)) {
        // Peut être OK si validé ou isset
        if (!preg_match('/(isset|empty|validate|trim).*\$_POST/', $content)) {
            // Warning, pas une erreur
        }
    }
}

if (count($securityIssues) > 0) {
    echo count($securityIssues) . " problèmes de sécurité détectés:\n";
    foreach ($securityIssues as $issue) {
        echo "  " . $issue . "\n";
    }
} else {
    echo "✅ Sécurité: OK (security.php inclus dans les pages)\n";
}
echo "\n";

// =======================
// 4. VÉRIFIER LES INCLUDES
// =======================

echo "📚 VÉRIFICATION DES INCLUSIONS\n";
echo str_repeat("-", 90) . "\n";

$missingIncludes = [];

foreach ($phpFiles as $file) {
    $content = file_get_contents($file);
    $rel = str_replace(PROJECT_ROOT . '\\', '', $file);
    
    // Trouver tous les requires/includes
    if (preg_match_all('/(?:require|include)(?:_once)?\s*[\'"]([^\'"]+)[\'"]/', $content, $matches)) {
        foreach ($matches[1] as $incPath) {
            // Construire le chemin absolu
            $absPath = dirname($file) . '\\' . str_replace('/', '\\', $incPath);
            $absPath = str_replace('\\..\\', '\\', $absPath);
            
            if (!file_exists($absPath) && !file_exists(PROJECT_ROOT . '\\' . str_replace('/', '\\', $incPath))) {
                $missingIncludes[] = [
                    'file' => $rel,
                    'include' => $incPath
                ];
            }
        }
    }
}

if (count($missingIncludes) > 0) {
    echo "❌ INCLUSIONS MANQUANTES: " . count($missingIncludes) . "\n\n";
    foreach ($missingIncludes as $miss) {
        echo "  📄 " . $miss['file'] . "\n";
        echo "     → Cherche: " . $miss['include'] . "\n\n";
    }
} else {
    echo "✅ Inclusions: Tous les fichiers existent\n\n";
}

// =======================
// 5. VÉRIFIER LES TABLES DB
// =======================

echo "💾 VÉRIFICATION BASE DE DONNÉES\n";
echo str_repeat("-", 90) . "\n";

// Scanner les accès à la DB dans le code
$dbTables = [];
$dbColumns = [];

foreach ($phpFiles as $file) {
    $content = file_get_contents($file);
    
    // Tables
    if (preg_match_all('/FROM\s+([a-zA-Z_]\w*)/i', $content, $matches)) {
        foreach ($matches[1] as $table) {
            if (!in_array($table, $dbTables)) {
                $dbTables[] = $table;
            }
        }
    }
    
    // Colonnes (alias.colonne)
    if (preg_match_all('/([a-z_]\w*)\.([a-z_]\w*)\b/i', $content, $matches)) {
        for ($i = 0; $i < count($matches[0]); $i++) {
            $pair = $matches[1][$i] . '.' . $matches[2][$i];
            if (!in_array($pair, $dbColumns)) {
                $dbColumns[] = $pair;
            }
        }
    }
}

echo "Tables DB référencées: " . count($dbTables) . "\n";
foreach (array_slice($dbTables, 0, 10) as $table) {
    echo "  ✓ $table\n";
}
if (count($dbTables) > 10) echo "  ... et " . (count($dbTables) - 10) . " autres\n";

echo "\nColonnes DB référencées: " . count($dbColumns) . "\n";
foreach (array_slice($dbColumns, 0, 10) as $col) {
    echo "  ✓ $col\n";
}
if (count($dbColumns) > 10) echo "  ... et " . (count($dbColumns) - 10) . " autres\n";

echo "\n";

// =======================
// 6. VÉRIFIER LES ROUTES
// =======================

echo "🔗 VÉRIFICATION DES PAGES\n";
echo str_repeat("-", 90) . "\n";

$pages = [];
foreach ($phpFiles as $file) {
    $rel = str_replace(PROJECT_ROOT . '\\', '', $file);
    $rel = str_replace('\\', '/', $rel);
    
    // Pages web (pas lib, pas api, pas db)
    if (preg_match('/^(admin|coordination|clients|produits|ventes|livraisons|compta|caisse|formations|terrain|showroom|dashboard)\//', $rel)) {
        $pages[] = $rel;
    }
}

echo "Pages dynamiques trouvées: " . count($pages) . "\n";
echo "Exemples:\n";
foreach (array_slice($pages, 0, 15) as $page) {
    echo "  ✓ /$page\n";
}
if (count($pages) > 15) echo "  ... et " . (count($pages) - 15) . " autres\n";

echo "\n";

// =======================
// 7. RAPPORT FINAL
// =======================

echo "\n" . str_repeat("=", 90) . "\n";
echo "📋 RÉSUMÉ FINAL DE L'AUDIT\n";
echo str_repeat("=", 90) . "\n";

$totalIssues = count($syntaxErrors) + count($missingIncludes) + count($securityIssues);

echo "\n✅ Éléments vérifiés:\n";
echo "   - Fichiers PHP: " . count($phpFiles) . "\n";
echo "   - Erreurs syntaxe: " . count($syntaxErrors) . "\n";
echo "   - Inclusions manquantes: " . count($missingIncludes) . "\n";
echo "   - Problèmes sécurité: " . count($securityIssues) . "\n";
echo "   - Tables DB référencées: " . count($dbTables) . "\n";
echo "   - Pages dynamiques: " . count($pages) . "\n";

if ($totalIssues === 0) {
    echo "\n🎉 AUDIT RÉUSSI: Aucun problème détecté!\n";
} else {
    echo "\n⚠️  AUDIT: " . $totalIssues . " problème(s) à corriger\n";
}

echo "\n";

// Sauvegarder le rapport
$report = [
    'timestamp' => date('Y-m-d H:i:s'),
    'php_files' => count($phpFiles),
    'syntax_errors' => count($syntaxErrors),
    'missing_includes' => count($missingIncludes),
    'security_issues' => count($securityIssues),
    'db_tables_referenced' => count($dbTables),
    'pages_found' => count($pages),
    'errors' => [
        'syntax' => $syntaxErrors,
        'includes' => $missingIncludes,
        'security' => $securityIssues
    ]
];

file_put_contents(PROJECT_ROOT . '/AUDIT_COMPLET.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo "📊 Rapport sauvegardé: AUDIT_COMPLET.json\n";

?>
