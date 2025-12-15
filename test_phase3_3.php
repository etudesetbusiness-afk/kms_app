<?php
/**
 * test_phase3_3.php - Tests pour Phase 3.3 (Date Picker Avancé)
 * Tests: Dates helpers, validation, présets, filtres SQL
 */

echo "========================================\n";
echo "Phase 3.3 - Tests Date Picker Avancé\n";
echo "========================================\n\n";

$test_count = 0;
$pass_count = 0;

// ============================================
// 1. TEST FILES
// ============================================
echo "1. Vérification des fichiers...\n";
$files = [
    'lib/date_helpers.php',
    'components/date_range_picker.html',
    'api/get_date_preset.php'
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
$php_files = ['lib/date_helpers.php', 'api/get_date_preset.php'];
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
// 3. TEST FUNCTIONS
// ============================================
echo "\n3. Vérification des fonctions...\n";
require_once __DIR__ . '/lib/date_helpers.php';

$functions_to_test = [
    'getDateRangePreset',
    'getDatePresets',
    'validateAndFormatDate',
    'buildDateWhereClause',
    'formatDateForDisplay',
    'getPresetLabel'
];

foreach ($functions_to_test as $func) {
    $test_count++;
    if (function_exists($func)) {
        echo "   ✅ $func()\n";
        $pass_count++;
    } else {
        echo "   ❌ $func() (non trouvée)\n";
    }
}

// ============================================
// 4. TEST EXECUTIONS
// ============================================
echo "\n4. Tests d'exécution des fonctions...\n";

// Test getDateRangePreset
$test_count++;
$range = getDateRangePreset('last_7d');
if (!empty($range['start']) && !empty($range['end']) && strtotime($range['start']) <= strtotime($range['end'])) {
    echo "   ✅ getDateRangePreset('last_7d') retourne une plage valide\n";
    $pass_count++;
} else {
    echo "   ❌ getDateRangePreset('last_7d') format invalide\n";
}

// Test getDateRangePreset - this_month
$test_count++;
$range = getDateRangePreset('this_month');
$start_month = date('Y-m', strtotime($range['start']));
$current_month = date('Y-m');
if ($start_month === $current_month) {
    echo "   ✅ getDateRangePreset('this_month') commence ce mois\n";
    $pass_count++;
} else {
    echo "   ❌ getDateRangePreset('this_month') ne commence pas ce mois (got: $start_month, expected: $current_month)\n";
}

// Test getDatePresets
$test_count++;
$presets = getDatePresets();
if (count($presets) >= 6 && isset($presets[0]['key']) && isset($presets[0]['label'])) {
    echo "   ✅ getDatePresets() retourne " . count($presets) . " présets valides\n";
    $pass_count++;
} else {
    echo "   ❌ getDatePresets() format invalide\n";
}

// Test validateAndFormatDate
$test_count++;
$valid = validateAndFormatDate('2025-12-14');
if ($valid === '2025-12-14') {
    echo "   ✅ validateAndFormatDate('2025-12-14') valide\n";
    $pass_count++;
} else {
    echo "   ❌ validateAndFormatDate('2025-12-14') retourne: $valid\n";
}

// Test validateAndFormatDate - format invalide
$test_count++;
$invalid = validateAndFormatDate('invalid-date');
if ($invalid === null) {
    echo "   ✅ validateAndFormatDate('invalid-date') retourne null\n";
    $pass_count++;
} else {
    echo "   ❌ validateAndFormatDate('invalid-date') devrait retourner null\n";
}

// Test buildDateWhereClause
$test_count++;
$where = buildDateWhereClause('v.date', '2025-12-01', '2025-12-31');
if (strpos($where, '>=') !== false && strpos($where, '<=') !== false && strpos($where, 'AND') !== false) {
    echo "   ✅ buildDateWhereClause() génère une clause WHERE valide\n";
    $pass_count++;
} else {
    echo "   ❌ buildDateWhereClause() clause invalide: $where\n";
}

// Test formatDateForDisplay
$test_count++;
$formatted = formatDateForDisplay('2025-12-14', 'd/m/Y');
if ($formatted === '14/12/2025') {
    echo "   ✅ formatDateForDisplay('2025-12-14', 'd/m/Y') = '14/12/2025'\n";
    $pass_count++;
} else {
    echo "   ❌ formatDateForDisplay() retourne: $formatted\n";
}

// Test getPresetLabel
$test_count++;
$label = getPresetLabel('last_30d');
if ($label === 'Derniers 30 jours') {
    echo "   ✅ getPresetLabel('last_30d') = 'Derniers 30 jours'\n";
    $pass_count++;
} else {
    echo "   ❌ getPresetLabel() retourne: $label\n";
}

// ============================================
// RÉSUMÉ
// ============================================
echo "\n========================================\n";
echo "Résultat: $pass_count / $test_count tests réussis\n";
echo "========================================\n";

if ($pass_count === $test_count) {
    echo "✅ TOUS LES TESTS PASSENT - PHASE 3.3 OK\n";
    exit(0);
} else {
    echo "❌ Certains tests ont échoué\n";
    exit(1);
}
