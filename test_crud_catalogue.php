<?php
/**
 * test_crud_catalogue.php
 * Test du CRUD du module catalogue
 */

session_start();

// Initialiser utilisateur admin avec toutes les permissions
$_SESSION['user_id'] = 1;
$_SESSION['utilisateur'] = [
    'id' => 1,
    'login' => 'admin',
    'nom_complet' => 'Administrateur',
    'email' => 'admin@test.com'
];

// Les permissions dont on a besoin
$_SESSION['permissions'] = [
    'PRODUITS_LIRE',
    'PRODUITS_CREER',
    'PRODUITS_MODIFIER',
    'PRODUITS_SUPPRIMER',
    'CATEGORIES_LIRE',
    'CATEGORIES_CREER',
    'CATEGORIES_MODIFIER',
    'CATEGORIES_SUPPRIMER',
];

require_once __DIR__ . '/security.php';
require_once __DIR__ . '/lib/pagination.php';

global $pdo;

echo "=== TEST CRUD CATALOGUE ===\n\n";

try {
    // Test 1: Lire les catégories
    echo "Test 1: Lecture des catégories\n";
    $stmt = $pdo->query("SELECT COUNT(*) FROM catalogue_categories");
    $count = $stmt->fetchColumn();
    echo "✓ Nombre de catégories: $count\n";
    
    // Test 2: Créer une catégorie TEST
    echo "\nTest 2: Création d'une catégorie TEST\n";
    $testNom = "Catégorie Test " . date('His');
    $testSlug = strtolower(str_replace([' ', 'é'], ['-', 'e'], $testNom));
    
    $stmt = $pdo->prepare("INSERT INTO catalogue_categories (nom, slug, actif, ordre) VALUES (?, ?, 1, 99)");
    $result = $stmt->execute([$testNom, $testSlug]);
    if ($result) {
        $catId = $pdo->lastInsertId();
        echo "✓ Catégorie créée avec ID: $catId\n";
        
        // Test 3: Lire la catégorie créée
        echo "\nTest 3: Lecture de la catégorie créée\n";
        $stmt = $pdo->prepare("SELECT * FROM catalogue_categories WHERE id = ?");
        $stmt->execute([$catId]);
        $cat = $stmt->fetch();
        if ($cat) {
            echo "✓ Catégorie trouvée: " . $cat['nom'] . "\n";
            
            // Test 4: Modifier la catégorie
            echo "\nTest 4: Modification de la catégorie\n";
            $newNom = "Catégorie Test Modifiée " . date('His');
            $stmt = $pdo->prepare("UPDATE catalogue_categories SET nom = ? WHERE id = ?");
            $stmt->execute([$newNom, $catId]);
            echo "✓ Catégorie modifiée\n";
            
            // Test 5: Créer un produit TEST avec cette catégorie
            echo "\nTest 5: Création d'un produit TEST\n";
            $testCode = "TEST-" . strtoupper(substr(md5(uniqid()), 0, 6));
            $testDesignation = "Produit Test " . date('His');
            $testSlug = strtolower(str_replace([' ', 'é'], ['-', 'e'], $testDesignation));
            
            $stmt = $pdo->prepare("
                INSERT INTO catalogue_produits 
                (code, designation, slug, categorie_id, prix_unite, prix_gros, actif) 
                VALUES (?, ?, ?, ?, 1000.00, 900.00, 1)
            ");
            $result = $stmt->execute([$testCode, $testDesignation, $testSlug, $catId]);
            if ($result) {
                $prodId = $pdo->lastInsertId();
                echo "✓ Produit créé avec ID: $prodId (Code: $testCode)\n";
                
                // Test 6: Lire le produit créé
                echo "\nTest 6: Lecture du produit créé\n";
                $stmt = $pdo->prepare("SELECT * FROM catalogue_produits WHERE id = ?");
                $stmt->execute([$prodId]);
                $prod = $stmt->fetch();
                if ($prod) {
                    echo "✓ Produit trouvé: " . $prod['designation'] . " (Catégorie ID: " . $prod['categorie_id'] . ")\n";
                    
                    // Test 7: Modifier le produit
                    echo "\nTest 7: Modification du produit\n";
                    $newDesignation = "Produit Test Modifié " . date('His');
                    $stmt = $pdo->prepare("UPDATE catalogue_produits SET designation = ? WHERE id = ?");
                    $stmt->execute([$newDesignation, $prodId]);
                    echo "✓ Produit modifié\n";
                    
                    // Test 8: Supprimer le produit
                    echo "\nTest 8: Suppression du produit\n";
                    $stmt = $pdo->prepare("DELETE FROM catalogue_produits WHERE id = ?");
                    $stmt->execute([$prodId]);
                    echo "✓ Produit supprimé\n";
                    
                    // Test 9: Vérifier que le produit est supprimé
                    echo "\nTest 9: Vérification suppression du produit\n";
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM catalogue_produits WHERE id = ?");
                    $stmt->execute([$prodId]);
                    $count = $stmt->fetchColumn();
                    echo "✓ Produit absent de la base: " . ($count == 0 ? "OK" : "ERREUR") . "\n";
                } else {
                    echo "✗ Produit non trouvé après création\n";
                }
            } else {
                echo "✗ Erreur lors de la création du produit\n";
            }
        } else {
            echo "✗ Catégorie non trouvée après création\n";
        }
        
        // Test 10: Supprimer la catégorie
        echo "\nTest 10: Suppression de la catégorie\n";
        $stmt = $pdo->prepare("DELETE FROM catalogue_categories WHERE id = ?");
        $stmt->execute([$catId]);
        echo "✓ Catégorie supprimée\n";
        
        // Test 11: Vérifier que la catégorie est supprimée
        echo "\nTest 11: Vérification suppression de la catégorie\n";
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM catalogue_categories WHERE id = ?");
        $stmt->execute([$catId]);
        $count = $stmt->fetchColumn();
        echo "✓ Catégorie absente de la base: " . ($count == 0 ? "OK" : "ERREUR") . "\n";
    } else {
        echo "✗ Erreur lors de la création de la catégorie\n";
    }
    
    // Test 12: Vérifier les relations FK
    echo "\nTest 12: Vérification des relations FK\n";
    $stmt = $pdo->query("
        SELECT cp.id, cp.designation, cp.categorie_id, cc.nom 
        FROM catalogue_produits cp 
        LEFT JOIN catalogue_categories cc ON cp.categorie_id = cc.id 
        LIMIT 5
    ");
    $products = $stmt->fetchAll();
    echo "✓ Produits avec catégories: " . count($products) . " affichés\n";
    foreach ($products as $p) {
        echo "  - " . $p['designation'] . " (Cat: " . ($p['nom'] ?? 'NULL') . ")\n";
    }
    
} catch (Exception $e) {
    echo "✗ ERREUR: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DES TESTS ===\n";
?>
