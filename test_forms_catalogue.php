<?php
/**
 * test_forms_catalogue.php
 * Test de la logique des formulaires du module catalogue
 */

session_start();

// Initialiser utilisateur admin
$_SESSION['user_id'] = 1;
$_SESSION['utilisateur'] = [
    'id' => 1,
    'login' => 'admin',
    'nom_complet' => 'Administrateur',
    'email' => 'admin@test.com'
];

$_SESSION['permissions'] = [
    'PRODUITS_LIRE',
    'PRODUITS_CREER',
    'PRODUITS_MODIFIER',
    'PRODUITS_SUPPRIMER',
];

// Générer un token CSRF
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

require_once __DIR__ . '/security.php';

global $pdo;

echo "=== TEST FORMULAIRES CATALOGUE ===\n\n";

try {
    // Test 1: Simulation création produit par formulaire
    echo "Test 1: Simulation création produit (POST)\n";
    
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST = [
        'csrf_token' => $_SESSION['csrf_token'],
        'code' => 'FORM-TEST-001',
        'designation' => 'Produit Formulaire Test',
        'categorie_id' => '1',
        'prix_unite' => '1500.50',
        'prix_gros' => '1200.00',
        'description' => 'Description test',
        'actif' => '1',
    ];
    $_FILES = [];
    
    // Vérifier que getCsrfToken fonctionne
    $token = getCsrfToken();
    echo "✓ Token CSRF valide: " . substr($token, 0, 20) . "...\n";
    
    // Test 2: Vérifier que verifierCsrf ne lance pas d'erreur
    echo "\nTest 2: Vérification CSRF valide\n";
    try {
        verifierCsrf($_POST['csrf_token']);
        echo "✓ Vérification CSRF passée\n";
    } catch (Exception $e) {
        echo "✗ Erreur CSRF: " . $e->getMessage() . "\n";
    }
    
    // Test 3: Simuler validation d'un formulaire de catégorie
    echo "\nTest 3: Validation formulaire catégorie\n";
    
    $nom = trim($_POST['designation'] ?? ''); // Réutiliser un champ test
    $actif = isset($_POST['actif']) ? 1 : 0;
    $ordre = (int)($_POST['categorie_id'] ?? 1);
    
    $errors = [];
    if (empty($nom)) {
        $errors[] = "Le nom est obligatoire";
    }
    
    if (empty($errors)) {
        echo "✓ Validation réussie\n";
        echo "  - Nom: $nom\n";
        echo "  - Actif: " . ($actif ? "Oui" : "Non") . "\n";
    } else {
        echo "✗ Erreurs de validation: " . implode(", ", $errors) . "\n";
    }
    
    // Test 4: Tester une fonction manquante (generateSlug)
    echo "\nTest 4: Vérification fonction generateSlug\n";
    if (function_exists('generateSlug')) {
        $slug = generateSlug("Produit Test");
        echo "✓ generateSlug existe, résultat: $slug\n";
    } else {
        echo "✗ generateSlug n'existe pas - besoin de vérifier lib\n";
    }
    
    // Test 5: Tester une fonction manquante (handleImageUpload)
    echo "\nTest 5: Vérification fonction handleImageUpload\n";
    if (function_exists('handleImageUpload')) {
        echo "✓ handleImageUpload existe\n";
    } else {
        echo "✗ handleImageUpload n'existe pas - besoin de vérifier lib\n";
    }
    
    // Test 6: Vérifier que les requêtes SQL sont corrects
    echo "\nTest 6: Test de requête de mise à jour\n";
    
    // Chercher un produit existant pour le test
    $stmt = $pdo->query("SELECT id FROM catalogue_produits LIMIT 1");
    $prod = $stmt->fetch();
    
    if ($prod) {
        $prodId = $prod['id'];
        $stmt = $pdo->prepare("SELECT * FROM catalogue_produits WHERE id = ?");
        $stmt->execute([$prodId]);
        $product = $stmt->fetch();
        
        if ($product) {
            echo "✓ Produit trouvé: " . $product['designation'] . "\n";
            
            // Simuler une mise à jour
            $newDesignation = "Test Update " . time();
            $stmt = $pdo->prepare("UPDATE catalogue_produits SET designation = ? WHERE id = ?");
            $result = $stmt->execute([$newDesignation, $prodId]);
            
            if ($result) {
                echo "✓ Mise à jour réussie\n";
                
                // Restaurer
                $stmt = $pdo->prepare("UPDATE catalogue_produits SET designation = ? WHERE id = ?");
                $stmt->execute([$product['designation'], $prodId]);
                echo "✓ Restauration réussie\n";
            } else {
                echo "✗ Mise à jour échouée\n";
            }
        }
    } else {
        echo "? Aucun produit dans la base pour tester\n";
    }
    
    echo "\n=== FIN DES TESTS ===\n";
    
} catch (Exception $e) {
    echo "✗ ERREUR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
?>
