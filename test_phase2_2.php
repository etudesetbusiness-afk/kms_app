<?php
/**
 * Script de test pour Phase 2.2 - Filtres Avancés
 * Valide les fonctionnalités de recherche, tri, et filtres persistants
 */

// Test 1: Vérifier que les fichiers helpers existent
echo "=== TEST 1: Fichiers et includes ===\n";
$files = [
    'lib/filters_helpers.php',
    'components/sortable_header.php',
    'ventes/list.php',
    'livraisons/list.php',
    'coordination/litiges.php'
];
foreach ($files as $f) {
    $path = __DIR__ . '/' . $f;
    if (file_exists($path)) {
        echo "✅ $f existe\n";
    } else {
        echo "❌ $f manquant\n";
    }
}

// Test 2: Valider les syntaxes PHP
echo "\n=== TEST 2: Validation syntaxe PHP ===\n";
foreach ($files as $f) {
    $path = __DIR__ . '/' . $f;
    $output = shell_exec("php -l \"$path\" 2>&1");
    if (strpos($output, 'No syntax errors') !== false) {
        echo "✅ $f OK\n";
    } else {
        echo "❌ $f ERREUR:\n";
        echo "   " . trim($output) . "\n";
    }
}

// Test 3: Simuler l'inclusion des helpers
echo "\n=== TEST 3: Inclusion des fonctions helpers ===\n";
try {
    require_once __DIR__ . '/lib/filters_helpers.php';
    require_once __DIR__ . '/components/sortable_header.php';
    echo "✅ Fonctions chargées avec succès\n";
    
    // Vérifier que les fonctions existent
    $functions = [
        'buildSearchWhereClause',
        'saveUserFilters',
        'getUserFilters',
        'getPaginationParams',
        'renderSortableHeader',
        'buildFilterUrl',
        'renderActiveFilterBadges'
    ];
    
    foreach ($functions as $fn) {
        if (function_exists($fn)) {
            echo "✅ $fn disponible\n";
        } else {
            echo "❌ $fn manquante\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Erreur d'inclusion: " . $e->getMessage() . "\n";
}

// Test 4: Tester les fonctions
echo "\n=== TEST 4: Test des fonctions ===\n";

// Test buildSearchWhereClause
$result = buildSearchWhereClause('cafe', ['designation', 'code']);
echo "buildSearchWhereClause('cafe', ['designation', 'code']):\n";
echo "  WHERE: " . $result[0] . "\n";
echo "  Params: " . json_encode($result[1]) . "\n";

// Test renderSortableHeader
$_GET = ['sort_by' => 'date', 'sort_dir' => 'desc', 'client_id' => '5'];
$html = renderSortableHeader('Date', 'date', 'date', 'desc', $_GET);
echo "\nrenderSortableHeader():\n";
echo "  HTML: " . substr($html, 0, 80) . "...\n";

// Test buildFilterUrl
$filters = ['date_debut' => '2024-01-01', 'client_id' => '5', 'search' => 'cafe'];
$url = buildFilterUrl($filters, ['page' => 2]);
echo "\nbuildFilterUrl():\n";
echo "  URL: " . $url . "\n";

echo "\n=== Tests complétés ===\n";
