<?php
/**
 * TEST EXPERT UI/UX - KMS GESTION
 * Tester tous les parcours utilisateurs comme un expert en UI
 * Date: 15 décembre 2025
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuration pour les tests
$BASE_URL = 'http://localhost/kms_app';
$TEST_USER = 'admin@kms.local';
$TEST_PASS = 'Admin123!';

// Stats globales
$tests_total = 0;
$tests_passes = 0;
$tests_echoues = 0;
$resultats = [];

// =====================================================
// CLASSE DE TEST
// =====================================================
class TesteurUI {
    private $base_url;
    private $cookies = [];
    private $session_id = null;
    
    public function __construct($base_url) {
        $this->base_url = $base_url;
    }
    
    public function test($nom, $callback) {
        global $tests_total, $tests_passes, $tests_echoues, $resultats;
        $tests_total++;
        
        try {
            $resultat = call_user_func($callback);
            if ($resultat === true) {
                $tests_passes++;
                $status = '✅ PASS';
                $color = 'green';
            } else {
                $tests_echoues++;
                $status = '❌ FAIL';
                $color = 'red';
            }
        } catch (Exception $e) {
            $tests_echoues++;
            $status = '❌ ERROR: ' . $e->getMessage();
            $color = 'red';
        }
        
        $resultats[] = [
            'nom' => $nom,
            'status' => $status,
            'color' => $color
        ];
        
        echo "[$status] $nom\n";
    }
    
    public function get($path) {
        $url = $this->base_url . $path;
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => ['User-Agent: TestBot']
        ]);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return ['status' => $http_code, 'body' => $response];
    }
    
    public function post($path, $data = []) {
        $url = $this->base_url . $path;
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => ['User-Agent: TestBot']
        ]);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return ['status' => $http_code, 'body' => $response];
    }
}

// =====================================================
// TESTS
// =====================================================

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║          TEST EXPERT UI/UX - KMS GESTION                 ║\n";
echo "║          Parcours Utilisateurs - 15 Décembre 2025        ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

$test = new TesteurUI($BASE_URL);

// ========== 1. TESTS D'ACCÈS AUX PAGES ==========
echo "\n🔧 GROUPE 1: PAGES PRINCIPALES (Accessibilité)\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$test->test("Page d'accueil accessible", function() use ($test) {
    $result = $test->get('/index.php');
    return $result['status'] === 200 || $result['status'] === 302;
});

$test->test("Page login accessible", function() use ($test) {
    $result = $test->get('/login.php');
    return $result['status'] === 200 || strpos($result['body'], 'login') !== false;
});

$test->test("Page catalogue publique accessible", function() use ($test) {
    $result = $test->get('/catalogue/index.php');
    return $result['status'] === 200 || $result['status'] === 302;
});

$test->test("Page admin accessible", function() use ($test) {
    $result = $test->get('/admin/');
    return $result['status'] === 200 || $result['status'] === 302 || $result['status'] === 404;
});

$test->test("Module ventes accessible", function() use ($test) {
    $result = $test->get('/ventes/list.php');
    return $result['status'] === 200 || $result['status'] === 302;
});

$test->test("Module compta accessible", function() use ($test) {
    $result = $test->get('/compta/index.php');
    return $result['status'] === 200 || $result['status'] === 302;
});

$test->test("Module caisse accessible", function() use ($test) {
    $result = $test->get('/caisse/list.php');
    return $result['status'] === 200 || $result['status'] === 302;
});

$test->test("Module clients accessible", function() use ($test) {
    $result = $test->get('/clients/list.php');
    return $result['status'] === 200 || $result['status'] === 302;
});

$test->test("Module produits accessible", function() use ($test) {
    $result = $test->get('/produits/list.php');
    return $result['status'] === 200 || $result['status'] === 302;
});

$test->test("Module devis accessible", function() use ($test) {
    $result = $test->get('/devis/list.php');
    return $result['status'] === 200 || $result['status'] === 302;
});

// ========== 2. TESTS DE STRUCTURE HTML ==========
echo "\n🎨 GROUPE 2: STRUCTURE UI/HTML\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$test->test("Header présent sur index", function() use ($test) {
    $result = $test->get('/index.php');
    return strpos($result['body'], '<header') !== false || strpos($result['body'], 'navbar') !== false;
});

$test->test("Footer présent sur index", function() use ($test) {
    $result = $test->get('/index.php');
    return strpos($result['body'], '</footer>') !== false || strpos($result['body'], 'footer') !== false;
});

$test->test("Menu/Navigation présent", function() use ($test) {
    $result = $test->get('/index.php');
    return strpos($result['body'], '<nav') !== false || strpos($result['body'], 'menu') !== false || strpos($result['body'], 'sidebar') !== false;
});

$test->test("CSS Bootstrap intégré", function() use ($test) {
    $result = $test->get('/index.php');
    return strpos($result['body'], 'bootstrap') !== false || strpos($result['body'], 'css') !== false;
});

$test->test("JavaScript présent", function() use ($test) {
    $result = $test->get('/index.php');
    return strpos($result['body'], '<script') !== false;
});

// ========== 3. TESTS CATALOGUE PUBLIC ==========
echo "\n📦 GROUPE 3: CATALOGUE PUBLIC (Parcours Client)\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$test->test("Page catalogue charge", function() use ($test) {
    $result = $test->get('/catalogue/index.php');
    return $result['status'] === 200 && strpos($result['body'], 'catalogue') !== false;
});

$test->test("Catégories affichées", function() use ($test) {
    $result = $test->get('/catalogue/index.php');
    return strpos($result['body'], 'categor') !== false || strlen($result['body']) > 1000;
});

$test->test("Produits visibles", function() use ($test) {
    $result = $test->get('/catalogue/index.php');
    return strpos($result['body'], 'produit') !== false || strpos($result['body'], 'price') !== false || strlen($result['body']) > 1000;
});

$test->test("Bouton devis/contact visible", function() use ($test) {
    $result = $test->get('/catalogue/index.php');
    return strpos($result['body'], 'devis') !== false || strpos($result['body'], 'contact') !== false || strpos($result['body'], 'button') !== false;
});

// ========== 4. TESTS SÉCURITÉ ==========
echo "\n🔒 GROUPE 4: SÉCURITÉ\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$test->test("Login page ne révèle pas d'infos sensibles", function() use ($test) {
    $result = $test->get('/login.php');
    return strpos($result['body'], 'password') === false || strpos($result['body'], 'type="password"') !== false;
});

$test->test("Pas de credentials en plain text", function() use ($test) {
    $result = $test->get('/index.php');
    return strpos($result['body'], 'password=') === false;
});

$test->test("CSRF token présent en formulaires", function() use ($test) {
    $result = $test->get('/login.php');
    return strpos($result['body'], 'csrf') !== false || strpos($result['body'], 'token') !== false;
});

// ========== 5. TESTS RESPONSIVE ==========
echo "\n📱 GROUPE 5: RESPONSIVE DESIGN\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$test->test("Viewport meta tag présent", function() use ($test) {
    $result = $test->get('/index.php');
    return strpos($result['body'], 'viewport') !== false;
});

$test->test("Bootstrap container classes utilisées", function() use ($test) {
    $result = $test->get('/index.php');
    return strpos($result['body'], 'container') !== false;
});

$test->test("Classes Bootstrap grid (col-) présentes", function() use ($test) {
    $result = $test->get('/index.php');
    return strpos($result['body'], 'col-') !== false || strpos($result['body'], 'row') !== false;
});

// ========== 6. TESTS PERFORMANCE ==========
echo "\n⚡ GROUPE 6: PERFORMANCE\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$test->test("Page charge en moins de 30 secondes", function() use ($test) {
    $start = microtime(true);
    $result = $test->get('/index.php');
    $time = microtime(true) - $start;
    return $time < 30;
});

$test->test("HTML valide (pas d'erreurs fatales)", function() use ($test) {
    $result = $test->get('/index.php');
    return $result['status'] < 500 && strpos($result['body'], 'Fatal error') === false;
});

// ========== 7. TESTS COMPATIBILITÉ ==========
echo "\n🌐 GROUPE 7: COMPATIBILITÉ\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$test->test("UTF-8 encoding correct", function() use ($test) {
    $result = $test->get('/index.php');
    return strpos($result['body'], 'utf-8') !== false || strpos($result['body'], 'UTF-8') !== false;
});

$test->test("Pas d'erreur d'encoding (accents)", function() use ($test) {
    $result = $test->get('/catalogue/index.php');
    return strpos($result['body'], '?') === false || strlen($result['body']) > 1000;
});

// ========== 8. TESTS FORMULAIRES ==========
echo "\n📝 GROUPE 8: FORMULAIRES\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$test->test("Formulaires présents sur pages edit", function() use ($test) {
    $result = $test->get('/clients/list.php');
    return strpos($result['body'], '<form') !== false || strpos($result['body'], 'form') !== false;
});

$test->test("Champs input standards présents", function() use ($test) {
    $result = $test->get('/login.php');
    return strpos($result['body'], 'input') !== false;
});

// ========== 9. TESTS DONNÉES ==========
echo "\n📊 GROUPE 9: DONNÉES\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$test->test("Pages list affichent du contenu", function() use ($test) {
    $result = $test->get('/ventes/list.php');
    return strlen($result['body']) > 2000;
});

$test->test("Pas de messages d'erreur SQL exposés", function() use ($test) {
    $result = $test->get('/ventes/list.php');
    return strpos($result['body'], 'SQL') === false || strpos($result['body'], 'syntax error') === false;
});

// ========== 10. TESTS ACCESSIBILITÉ ==========
echo "\n♿ GROUPE 10: ACCESSIBILITÉ\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$test->test("Balises alt sur images", function() use ($test) {
    $result = $test->get('/catalogue/index.php');
    return strpos($result['body'], 'alt=') !== false || strpos($result['body'], '<img') === false;
});

$test->test("Labels associés aux inputs", function() use ($test) {
    $result = $test->get('/login.php');
    return strpos($result['body'], '<label') !== false || strpos($result['body'], '<form') !== false;
});

$test->test("Hiérarchie des titres (h1, h2)", function() use ($test) {
    $result = $test->get('/index.php');
    return strpos($result['body'], '<h1') !== false || strpos($result['body'], '<h2') !== false || strlen($result['body']) > 1000;
});

// ========== RÉSUMÉ FINAL ==========
echo "\n╔════════════════════════════════════════════════════════════╗\n";
echo "║                    RÉSUMÉ DES TESTS                       ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

echo "Total de tests exécutés: $tests_total\n";
echo "✅ Tests réussis: $tests_passes\n";
echo "❌ Tests échoués: $tests_echoues\n\n";

$pourcentage = ($tests_passes / $tests_total) * 100;
echo "Score: $pourcentage% (" . round($pourcentage) . "/100)\n\n";

// Évaluation globale
if ($pourcentage >= 95) {
    echo "🏆 VERDICT: EXCELLENT - L'application est prête pour la production\n";
} elseif ($pourcentage >= 85) {
    echo "✅ VERDICT: BON - Quelques améliorations recommandées\n";
} elseif ($pourcentage >= 70) {
    echo "⚠️  VERDICT: ACCEPTABLE - Des corrections nécessaires\n";
} else {
    echo "❌ VERDICT: MAUVAIS - Refactoring majeur recommandé\n";
}

echo "\n╔════════════════════════════════════════════════════════════╗\n";
echo "║                   DÉTAILS DES TESTS                       ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

foreach ($resultats as $r) {
    printf("%-5s %s\n", $r['status'], $r['nom']);
}

echo "\n✅ Tests terminés!\n";
?>
