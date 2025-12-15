<?php
/**
 * Test de configuration PHP - error_reporting
 * Décode la valeur actuelle et recommande les ajustements
 */

echo "╔═══════════════════════════════════════════════════════════╗\n";
echo "║     ANALYSE CONFIGURATION PHP - ERROR REPORTING          ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";

// Valeur actuelle
$current = error_reporting();
echo "📊 Configuration actuelle\n";
echo "══════════════════════════════════════════════════════════════════════\n";
echo "Valeur numérique: $current\n";
echo "Valeur binaire:   " . decbin($current) . "\n\n";

// Décodage
echo "📋 Types d'erreurs activés:\n";
$errorTypes = [
    E_ERROR             => 'E_ERROR (erreurs fatales)',
    E_WARNING           => 'E_WARNING (avertissements)',
    E_PARSE             => 'E_PARSE (erreurs de syntaxe)',
    E_NOTICE            => 'E_NOTICE (notices)',
    E_CORE_ERROR        => 'E_CORE_ERROR (erreurs PHP core)',
    E_CORE_WARNING      => 'E_CORE_WARNING (warnings PHP core)',
    E_COMPILE_ERROR     => 'E_COMPILE_ERROR (erreurs Zend)',
    E_COMPILE_WARNING   => 'E_COMPILE_WARNING (warnings Zend)',
    E_USER_ERROR        => 'E_USER_ERROR (erreurs user)',
    E_USER_WARNING      => 'E_USER_WARNING (warnings user)',
    E_USER_NOTICE       => 'E_USER_NOTICE (notices user)',
    E_STRICT            => 'E_STRICT (suggestions)',
    E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR (erreurs récupérables)',
    E_DEPRECATED        => 'E_DEPRECATED (obsolètes)',
    E_USER_DEPRECATED   => 'E_USER_DEPRECATED (obsolètes user)',
];

$enabled = [];
$disabled = [];

foreach ($errorTypes as $type => $name) {
    if ($current & $type) {
        $enabled[] = "  ✅ $name";
    } else {
        $disabled[] = "  ❌ $name";
    }
}

foreach ($enabled as $e) echo "$e\n";
echo "\n";
foreach ($disabled as $d) echo "$d\n";

echo "\n";
echo "══════════════════════════════════════════════════════════════════════\n\n";

// Configuration recommandée
echo "💡 Configurations recommandées\n";
echo "══════════════════════════════════════════════════════════════════════\n\n";

echo "🔧 DÉVELOPPEMENT (strict, tout voir):\n";
$devLevel = E_ALL;
echo "   error_reporting = E_ALL\n";
echo "   Valeur numérique: $devLevel\n";
echo "   display_errors = On\n";
echo "   display_startup_errors = On\n";
echo "   log_errors = On\n\n";

echo "🚀 PRODUCTION (masquer notices/warnings):\n";
$prodLevel = E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_STRICT;
echo "   error_reporting = E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_STRICT\n";
echo "   Valeur numérique: $prodLevel\n";
echo "   display_errors = Off\n";
echo "   display_startup_errors = Off\n";
echo "   log_errors = On\n";
echo "   error_log = /path/to/php-error.log\n\n";

echo "⚖️ TEST/STAGING (équilibré):\n";
$testLevel = E_ALL & ~E_NOTICE & ~E_DEPRECATED;
echo "   error_reporting = E_ALL & ~E_NOTICE & ~E_DEPRECATED\n";
echo "   Valeur numérique: $testLevel\n";
echo "   display_errors = On\n";
echo "   log_errors = On\n\n";

// Diagnostic du problème actuel
echo "══════════════════════════════════════════════════════════════════════\n\n";
echo "🔍 DIAGNOSTIC DU PROBLÈME ACTUEL\n";
echo "══════════════════════════════════════════════════════════════════════\n\n";

if ($current & E_NOTICE) {
    echo "⚠️  E_NOTICE est activé - C'est la raison de vos warnings \"undefined variable\"\n";
    echo "   PHP signale TOUTES les variables non définies, même avec l'opérateur ??.\n\n";
} else {
    echo "✅ E_NOTICE est désactivé - Vous ne devriez pas voir de warnings undefined.\n\n";
}

if ($current & E_WARNING) {
    echo "⚠️  E_WARNING est activé - Peut générer beaucoup de bruit en développement.\n\n";
} else {
    echo "✅ E_WARNING est désactivé.\n\n";
}

if ($current & E_DEPRECATED) {
    echo "⚠️  E_DEPRECATED est activé - Vous verrez les avis sur le code obsolète.\n\n";
} else {
    echo "✅ E_DEPRECATED est désactivé.\n\n";
}

echo "══════════════════════════════════════════════════════════════════════\n\n";

// Test pratique
echo "🧪 TEST PRATIQUE\n";
echo "══════════════════════════════════════════════════════════════════════\n\n";

echo "Test 1: Variable undefined sans protection\n";
// Ceci génère une notice si E_NOTICE est actif
@$test1 = $variableInexistante;
echo "   Résultat: " . ($test1 === null ? "null (notice supprimée avec @)" : "valeur") . "\n\n";

echo "Test 2: Variable undefined avec ??\n";
$test2 = $autreVariableInexistante ?? 'valeur_par_defaut';
echo "   Résultat: $test2\n\n";

echo "Test 3: isset() sur variable undefined\n";
$test3 = isset($encoreUneAutre) ? 'existe' : 'n\'existe pas';
echo "   Résultat: $test3\n\n";

echo "══════════════════════════════════════════════════════════════════════\n\n";

// Recommandation finale
echo "💬 RECOMMANDATION POUR VOS WARNINGS\n";
echo "══════════════════════════════════════════════════════════════════════\n\n";

echo "Les warnings \"undefined variable\" que vous voyez dans ventes/list.php\n";
echo "sont causés par:\n\n";
echo "1. E_NOTICE est activé dans votre php.ini (valeur actuelle: $current)\n";
echo "2. PHP affiche les notices même si vous utilisez ?? (comportement normal)\n\n";

echo "✅ SOLUTION:\n";
echo "   Modifier C:\\xampp\\php\\php.ini:\n\n";
echo "   ; Avant\n";
echo "   error_reporting = E_ALL\n\n";
echo "   ; Après (pour masquer les notices)\n";
echo "   error_reporting = E_ALL & ~E_NOTICE\n\n";
echo "   Puis redémarrer Apache: xampp-control.exe\n\n";

echo "══════════════════════════════════════════════════════════════════════\n";
echo "Analyse terminée.\n";
