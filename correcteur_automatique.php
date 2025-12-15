<?php
/**
 * SCRIPT DE CORRECTION GLOBALE
 * Corrige tous les fichiers list.php avec variables undefined
 * 15 Décembre 2025
 */

echo "═══════════════════════════════════════════════════════════\n";
echo "   CORRECTEUR AUTOMATIQUE - Variables Undefined\n";
echo "═══════════════════════════════════════════════════════════\n\n";

// Fichiers à corriger
$corrections = [
    'livraisons/list.php' => [
        'issue' => 'Variables $dateDeb et $dateFin undefined',
        'pattern' => 'htmlspecialchars($dateDeb)',
        'init_vars' => ['dateDeb', 'dateFin'],
        'replace_from' => '$date_start = validateAndFormatDate($_GET[\'date_start\'] ?? $_GET[\'date\'] ?? null)',
        'replace_to' => '$date_start = validateAndFormatDate($_GET[\'date_start\'] ?? $_GET[\'date\'] ?? null);
// Aliases pour compatibilité
$dateDeb = $date_start ?? \'\';
$dateFin = $_GET[\'date_fin\'] ?? $_GET[\'date_end\'] ?? \'\'',
    ],
];

// Scanner récursif
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__));
$fixed_files = [];
$issues_found = 0;
$issues_fixed = 0;

foreach ($iterator as $file) {
    if ($file->getFilename() === 'list.php') {
        $filepath = $file->getRealPath();
        $content = file_get_contents($filepath);
        $original = $content;
        
        // Pattern: htmlspecialchars($dateXX)  sans initialisation
        $has_issue = false;
        
        // Vérifier si dateDeb/dateFin non initialisés
        if ((strpos($content, '$dateDeb') !== false || strpos($content, '$dateFin') !== false) &&
            !preg_match('/\$dateDeb\s*=.*?\$_GET/', $content) &&
            !preg_match('/\$dateFin\s*=.*?\$_GET/', $content)) {
            
            $has_issue = true;
            
            // Ajouter les initialisations si manquantes
            if (preg_match('/(\$date_start\s*=\s*validateAndFormatDate.*?\n)/s', $content, $m)) {
                $init = "\n// Aliases pour compatibilité avec les formulaires existants\n";
                $init .= "\$dateDeb = \$date_start;\n";
                $init .= "\$dateFin = \$date_end ?? '';\n";
                
                // Insérer après les initialisations de date_start/date_end
                $content = preg_replace(
                    '/(\$date_end\s*=.*?\n)/s',
                    '$1' . $init,
                    $content,
                    1
                );
                
                if ($content !== $original) {
                    file_put_contents($filepath, $content);
                    $issues_fixed++;
                    $fixed_files[] = str_replace(__DIR__ . '/', '', $filepath);
                }
            }
        }
        
        if ($has_issue) {
            $issues_found++;
        }
    }
}

echo "✅ CORRECTION EFFECTUÉE\n";
echo "─────────────────────────────────────────────────────────\n";
echo "Issues trouvées: $issues_found\n";
echo "Issues corrigées: $issues_fixed\n";

if (!empty($fixed_files)) {
    echo "\n📝 Fichiers modifiés:\n";
    foreach ($fixed_files as $file) {
        echo "  ✓ $file\n";
    }
}

echo "\n";

// Vérifier la correction
echo "🔍 VÉRIFICATION POST-CORRECTION\n";
echo "─────────────────────────────────────────────────────────\n";

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__));
$still_broken = 0;

foreach ($iterator as $file) {
    if ($file->getFilename() === 'list.php') {
        $filepath = $file->getRealPath();
        $content = file_get_contents($filepath);
        
        // Vérifier si les variables utilisées sont initialisées
        if ((strpos($content, '$dateDeb') !== false || strpos($content, '$dateFin') !== false) &&
            !preg_match('/\$dateDeb\s*=/', $content) &&
            !preg_match('/\$dateFin\s*=/', $content)) {
            
            $still_broken++;
            echo "⚠️  " . str_replace(__DIR__ . '/', '', $filepath) . " - Toujours des issues!\n";
        }
    }
}

if ($still_broken === 0) {
    echo "✅ TOUTES LES CORRECTIONS APPLIQUÉES AVEC SUCCÈS!\n";
} else {
    echo "⚠️  $still_broken fichiers nécessitent une correction manuelle\n";
}

echo "\n";
?>
