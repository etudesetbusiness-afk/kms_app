<?php
/**
 * AUDIT FONCTIONNEL EXHAUSTIF - KMS Gestion
 * 
 * Phase 2: Test des parcours utilisateurs, logique métier, DB
 */

require_once __DIR__ . '/security.php';

global $pdo;
define('PROJECT_ROOT', __DIR__);

$tests = [];
$pass = 0;
$fail = 0;

// Helper
function test($name, $condition, $details = '') {
    global $pass, $fail, $tests;
    if ($condition) {
        $pass++;
        $tests[] = ['PASS', $name, $details];
    } else {
        $fail++;
        $tests[] = ['FAIL', $name, $details];
    }
}

echo "🧪 AUDIT FONCTIONNEL - KMS Gestion\n";
echo str_repeat("=", 80) . "\n\n";

// ===========================
// 1. VÉRIFIER LES TABLES DB
// ===========================

echo "📊 Test 1: Structure de la base de données\n";
echo str_repeat("-", 80) . "\n";

$tables_required = [
    'utilisateurs', 'clients', 'produits', 'ventes', 'bons_livraison',
    'caisse_operations', 'retours_litiges', 'stocks_mouvements',
    'compta_journal', 'compta_pieces', 'compta_plan_comptable',
    'user_preferences', 'formations'
];

foreach ($tables_required as $table) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM $table LIMIT 1");
        test("Table $table existe", $stmt !== false, "");
    } catch (Exception $e) {
        test("Table $table existe", false, $e->getMessage());
    }
}

// ===========================
// 2. VÉRIFIER LES COLONNES CLÉS
// ===========================

echo "\n📋 Test 2: Colonnes essentielles\n";
echo str_repeat("-", 80) . "\n";

$columns = [
    'utilisateurs' => ['id', 'email', 'password_hash', 'role', 'nom_complet'],
    'clients' => ['id', 'nom', 'email', 'type_client', 'statut'],
    'produits' => ['id', 'code_produit', 'designation', 'stock_actuel', 'prix_vente'],
    'ventes' => ['id', 'numero', 'client_id', 'date_vente', 'montant_total', 'statut'],
    'bons_livraison' => ['id', 'numero', 'vente_id', 'client_id', 'date_bl', 'statut'],
    'stocks_mouvements' => ['id', 'produit_id', 'quantite', 'type_mouvement', 'date_mouvement'],
    'compta_journal' => ['id', 'numero_piece', 'type_mouvement', 'montant_debit', 'montant_credit'],
    'retours_litiges' => ['id', 'client_id', 'date_retour', 'montant_rembourse', 'statut_traitement']
];

foreach ($columns as $table => $cols) {
    foreach ($cols as $col) {
        try {
            $stmt = $pdo->query("SELECT $col FROM $table LIMIT 1");
            test("$table.$col", $stmt !== false, "");
        } catch (Exception $e) {
            test("$table.$col", false, $e->getMessage());
        }
    }
}

// ===========================
// 3. VÉRIFIER LES DONNÉES
// ===========================

echo "\n📈 Test 3: Données existantes\n";
echo str_repeat("-", 80) . "\n";

$data_checks = [
    'SELECT COUNT(*) as cnt FROM utilisateurs' => ['Utilisateurs', 1],
    'SELECT COUNT(*) as cnt FROM clients' => ['Clients', 1],
    'SELECT COUNT(*) as cnt FROM produits' => ['Produits', 1],
];

foreach ($data_checks as $query => $check) {
    try {
        $stmt = $pdo->query($query);
        $result = $stmt->fetch();
        $count = $result['cnt'] ?? 0;
        test($check[0] . " (count=$count)", $count >= $check[1], "");
    } catch (Exception $e) {
        test($check[0], false, $e->getMessage());
    }
}

// ===========================
// 4. VÉRIFIER LES FONCTIONS
// ===========================

echo "\n⚙️  Test 4: Fonctions globales\n";
echo str_repeat("-", 80) . "\n";

$functions_required = [
    'utilisateurConnecte',
    'exigerConnexion',
    'exigerPermission',
    'verifierCsrf',
    'url_for',
    'getPaginationParams',
    'cached',
    'validateAndFormatDate',
    'getDateRangePreset',
    'logStockMovement',
];

foreach ($functions_required as $func) {
    test("Fonction $func()", function_exists($func), "");
}

// ===========================
// 5. VÉRIFIER LES LIBRAIRIES
// ===========================

echo "\n📚 Test 5: Librairies PHP\n";
echo str_repeat("-", 80) . "\n";

$libs = [
    'lib/pagination.php',
    'lib/user_preferences.php',
    'lib/date_helpers.php',
    'lib/cache.php',
    'lib/stock.php',
    'lib/compta.php',
    'lib/caisse.php',
    'lib/kpi_cache.php'
];

foreach ($libs as $lib) {
    $path = PROJECT_ROOT . '/' . $lib;
    test("Library $lib existe", file_exists($path), "");
}

// ===========================
// 6. VÉRIFIER LES PERMISSIONS
// ===========================

echo "\n🔐 Test 6: Système de permissions\n";
echo str_repeat("-", 80) . "\n";

try {
    $stmt = $pdo->query("SELECT DISTINCT role FROM utilisateurs LIMIT 10");
    $roles = $stmt->fetchAll(PDO::FETCH_COLUMN);
    test("Rôles trouvés", count($roles) > 0, "Rôles: " . implode(", ", $roles));
} catch (Exception $e) {
    test("Rôles trouvés", false, $e->getMessage());
}

// ===========================
// 7. VÉRIFIER LES URLS
// ===========================

echo "\n🔗 Test 7: Fonction url_for\n";
echo str_repeat("-", 80) . "\n";

$urls = [
    'dashboard.php' => '/kms_app/dashboard.php',
    'ventes/list.php' => '/kms_app/ventes/list.php',
    'produits/edit.php?id=1' => '/kms_app/produits/edit.php?id=1',
    'compta/balance.php' => '/kms_app/compta/balance.php',
];

foreach ($urls as $path => $expected) {
    $result = url_for($path);
    test("url_for('$path')", strpos($result, 'dashboard.php') !== false || strpos($result, 'ventes') !== false, "Result: $result");
}

// ===========================
// 8. TEST CACHE
// ===========================

echo "\n💾 Test 8: Système de cache\n";
echo str_repeat("-", 80) . "\n";

try {
    cached('test_key', fn() => 'test_value', 3600);
    $value = cached('test_key', fn() => 'should_not_be_called', 3600);
    test("Cache set/get", $value === 'test_value', "");
} catch (Exception $e) {
    test("Cache set/get", false, $e->getMessage());
}

// ===========================
// 9. TEST DATES
// ===========================

echo "\n📅 Test 9: Fonction de dates\n";
echo str_repeat("-", 80) . "\n";

try {
    $range = getDateRangePreset('last_30d');
    test("Preset last_30d", isset($range['start']) && isset($range['end']), "");
    
    $formatted = validateAndFormatDate('2025-12-15');
    test("Validation date", $formatted === '2025-12-15', "");
} catch (Exception $e) {
    test("Fonction dates", false, $e->getMessage());
}

// ===========================
// 10. TEST PAGINATION
// ===========================

echo "\n📄 Test 10: Pagination\n";
echo str_repeat("-", 80) . "\n";

try {
    $_GET = ['page' => 2, 'per_page' => 50];
    $pagination = getPaginationParams($_GET, 1000, 25);
    test("Pagination page 2", isset($pagination['page']) && $pagination['page'] == 2, "");
    test("Pagination limit", isset($pagination['limit']) && isset($pagination['offset']), "");
} catch (Exception $e) {
    test("Pagination", false, $e->getMessage());
}

// ===========================
// RAPPORT FINAL
// ===========================

echo "\n" . str_repeat("=", 80) . "\n";
echo "📊 RÉSUMÉ FINAL\n";
echo str_repeat("=", 80) . "\n";
echo "  Tests réussis: $pass\n";
echo "  Tests échoués: $fail\n";
echo "  Total: " . ($pass + $fail) . "\n";
echo "  Taux de succès: " . round(($pass / ($pass + $fail)) * 100, 2) . "%\n";

if ($fail > 0) {
    echo "\n❌ Tests échoués:\n";
    foreach ($tests as $test) {
        if ($test[0] === 'FAIL') {
            echo "  - " . $test[1] . " (" . $test[2] . ")\n";
        }
    }
}

echo "\n✅ Audit fonctionnel terminé\n";

// Sauvegarder
file_put_contents(PROJECT_ROOT . '/AUDIT_FONCTIONNEL.json', json_encode([
    'timestamp' => date('Y-m-d H:i:s'),
    'pass' => $pass,
    'fail' => $fail,
    'tests' => $tests
], JSON_PRETTY_PRINT));

echo "📊 Détails sauvegardés: AUDIT_FONCTIONNEL.json\n";
?>
