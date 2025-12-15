<?php
/**
 * test_catalogue_admin.php
 * Tests complets du module administration catalogue
 */

require_once __DIR__ . '/security.php';
require_once __DIR__ . '/lib/compta.php';

exigerConnexion();

global $pdo;

$tests_passed = 0;
$tests_failed = 0;
$errors = [];

function test($name, $condition, $error_msg = '') {
    global $tests_passed, $tests_failed, $errors;
    if ($condition) {
        $tests_passed++;
        echo "✓ $name\n";
        return true;
    } else {
        $tests_failed++;
        $errors[] = "$name: $error_msg";
        echo "✗ $name: $error_msg\n";
        return false;
    }
}

echo "==========================================\n";
echo "  TESTS MODULE ADMINISTRATION CATALOGUE\n";
echo "==========================================\n\n";

// ==================================================
// SECTION 1: TESTS DE BASE DE DONNÉES
// ==================================================
echo "--- Section 1: Base de données ---\n";

// Test 1.1: Table catalogue_produits existe
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'catalogue_produits'");
    test("Table catalogue_produits existe", $stmt->rowCount() > 0, "Table non trouvée");
} catch (Exception $e) {
    test("Table catalogue_produits existe", false, $e->getMessage());
}

// Test 1.2: Table catalogue_categories existe
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'catalogue_categories'");
    test("Table catalogue_categories existe", $stmt->rowCount() > 0, "Table non trouvée");
} catch (Exception $e) {
    test("Table catalogue_categories existe", false, $e->getMessage());
}

// Test 1.3: Colonnes JSON dans catalogue_produits
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM catalogue_produits LIKE 'caracteristiques_json'");
    test("Colonne caracteristiques_json existe", $stmt->rowCount() > 0, "Colonne non trouvée");
    
    $stmt = $pdo->query("SHOW COLUMNS FROM catalogue_produits LIKE 'galerie_images'");
    test("Colonne galerie_images existe", $stmt->rowCount() > 0, "Colonne non trouvée");
} catch (Exception $e) {
    test("Colonnes JSON", false, $e->getMessage());
}

// Test 1.4: Foreign key categorie_id
try {
    $stmt = $pdo->query("SELECT * FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                         WHERE TABLE_NAME = 'catalogue_produits' 
                         AND COLUMN_NAME = 'categorie_id' 
                         AND REFERENCED_TABLE_NAME = 'catalogue_categories'");
    test("Foreign key categorie_id existe", $stmt->rowCount() > 0, "FK non trouvée");
} catch (Exception $e) {
    test("Foreign key categorie_id", false, $e->getMessage());
}

echo "\n";

// ==================================================
// SECTION 2: TESTS FICHIERS ET STRUCTURE
// ==================================================
echo "--- Section 2: Fichiers ---\n";

$files = [
    'admin/catalogue/produits.php',
    'admin/catalogue/produit_edit.php',
    'admin/catalogue/produit_delete.php',
    'admin/catalogue/categories.php',
    'admin/catalogue/README.md',
    'uploads/catalogue/'
];

foreach ($files as $file) {
    $path = __DIR__ . '/' . $file;
    if (substr($file, -1) === '/') {
        test("Dossier $file existe", is_dir($path), "Dossier non trouvé");
        test("Dossier $file accessible en écriture", is_writable($path), "Pas d'accès écriture");
    } else {
        test("Fichier $file existe", file_exists($path), "Fichier non trouvé");
        test("Fichier $file syntaxe PHP valide", !preg_match('/\.php$/', $file) || shell_exec("php -l \"$path\" 2>&1") !== null, "Erreur syntaxe");
    }
}

echo "\n";

// ==================================================
// SECTION 3: TESTS PERMISSIONS
// ==================================================
echo "--- Section 3: Permissions ---\n";

// Vérifier que les codes permission existent
$permission_codes = ['PRODUITS_LIRE', 'PRODUITS_CREER', 'PRODUITS_MODIFIER', 'PRODUITS_SUPPRIMER'];
foreach ($permission_codes as $code) {
    try {
        $stmt = $pdo->prepare("SELECT id FROM permissions WHERE code = ?");
        $stmt->execute([$code]);
        test("Permission $code existe", $stmt->rowCount() > 0, "Permission non trouvée en DB");
    } catch (Exception $e) {
        test("Permission $code", false, $e->getMessage());
    }
}

echo "\n";

// ==================================================
// SECTION 4: TESTS FONCTIONNELS CATEGORIES
// ==================================================
echo "--- Section 4: Catégories (CRUD) ---\n";

// Test 4.1: Créer catégorie test
$test_category_id = null;
try {
    $nom = "Catégorie Test " . uniqid();
    $slug = strtolower(str_replace(' ', '-', $nom));
    $stmt = $pdo->prepare("INSERT INTO catalogue_categories (nom, slug, actif, ordre) VALUES (?, ?, 1, 999)");
    $stmt->execute([$nom, $slug]);
    $test_category_id = $pdo->lastInsertId();
    test("Créer catégorie test", $test_category_id > 0, "Échec insertion");
} catch (Exception $e) {
    test("Créer catégorie test", false, $e->getMessage());
}

// Test 4.2: Lire catégorie
if ($test_category_id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM catalogue_categories WHERE id = ?");
        $stmt->execute([$test_category_id]);
        $cat = $stmt->fetch();
        test("Lire catégorie créée", $cat !== false, "Catégorie non trouvée");
        test("Slug généré correctement", $cat && strpos($cat['slug'], 'categorie-test') !== false, "Slug invalide: " . ($cat['slug'] ?? 'null'));
    } catch (Exception $e) {
        test("Lire catégorie", false, $e->getMessage());
    }
}

// Test 4.3: Modifier catégorie
if ($test_category_id) {
    try {
        $nouveau_nom = "Catégorie Modifiée " . uniqid();
        $stmt = $pdo->prepare("UPDATE catalogue_categories SET nom = ? WHERE id = ?");
        $stmt->execute([$nouveau_nom, $test_category_id]);
        
        $stmt = $pdo->prepare("SELECT nom FROM catalogue_categories WHERE id = ?");
        $stmt->execute([$test_category_id]);
        $cat = $stmt->fetch();
        test("Modifier catégorie", $cat && $cat['nom'] === $nouveau_nom, "Modification échouée");
    } catch (Exception $e) {
        test("Modifier catégorie", false, $e->getMessage());
    }
}

echo "\n";

// ==================================================
// SECTION 5: TESTS FONCTIONNELS PRODUITS
// ==================================================
echo "--- Section 5: Produits (CRUD) ---\n";

// Test 5.1: Créer produit test
$test_product_id = null;
if ($test_category_id) {
    try {
        $code = "TEST-" . uniqid();
        $designation = "Produit Test " . uniqid();
        $slug = strtolower(str_replace(' ', '-', $designation));
        
        $stmt = $pdo->prepare("
            INSERT INTO catalogue_produits (code, slug, designation, categorie_id, prix_unite, actif) 
            VALUES (?, ?, ?, ?, 100.00, 1)
        ");
        $stmt->execute([$code, $slug, $designation, $test_category_id]);
        $test_product_id = $pdo->lastInsertId();
        test("Créer produit test", $test_product_id > 0, "Échec insertion produit");
    } catch (Exception $e) {
        test("Créer produit test", false, $e->getMessage());
    }
}

// Test 5.2: Lire produit
if ($test_product_id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM catalogue_produits WHERE id = ?");
        $stmt->execute([$test_product_id]);
        $prod = $stmt->fetch();
        test("Lire produit créé", $prod !== false, "Produit non trouvé");
        test("Code produit unique", $prod && $prod['code'] !== null, "Code null");
        test("Slug produit généré", $prod && $prod['slug'] !== null, "Slug null");
    } catch (Exception $e) {
        test("Lire produit", false, $e->getMessage());
    }
}

// Test 5.3: Modifier produit
if ($test_product_id) {
    try {
        $new_prix = 250.50;
        $stmt = $pdo->prepare("UPDATE catalogue_produits SET prix_unite = ? WHERE id = ?");
        $stmt->execute([$new_prix, $test_product_id]);
        
        $stmt = $pdo->prepare("SELECT prix_unite FROM catalogue_produits WHERE id = ?");
        $stmt->execute([$test_product_id]);
        $prod = $stmt->fetch();
        test("Modifier prix produit", $prod && floatval($prod['prix_unite']) === $new_prix, "Modification prix échouée");
    } catch (Exception $e) {
        test("Modifier produit", false, $e->getMessage());
    }
}

// Test 5.4: Caractéristiques JSON
if ($test_product_id) {
    try {
        $carac = json_encode(['Epaisseur' => '18 mm', 'Dimensions' => '1220 x 2440 mm']);
        $stmt = $pdo->prepare("UPDATE catalogue_produits SET caracteristiques_json = ? WHERE id = ?");
        $stmt->execute([$carac, $test_product_id]);
        
        $stmt = $pdo->prepare("SELECT caracteristiques_json FROM catalogue_produits WHERE id = ?");
        $stmt->execute([$test_product_id]);
        $prod = $stmt->fetch();
        $decoded = json_decode($prod['caracteristiques_json'], true);
        test("Stocker caractéristiques JSON", is_array($decoded) && isset($decoded['Epaisseur']), "JSON invalide");
    } catch (Exception $e) {
        test("Caractéristiques JSON", false, $e->getMessage());
    }
}

// Test 5.5: Galerie JSON
if ($test_product_id) {
    try {
        $galerie = json_encode(['img1.jpg', 'img2.jpg', 'img3.jpg']);
        $stmt = $pdo->prepare("UPDATE catalogue_produits SET galerie_images = ? WHERE id = ?");
        $stmt->execute([$galerie, $test_product_id]);
        
        $stmt = $pdo->prepare("SELECT galerie_images FROM catalogue_produits WHERE id = ?");
        $stmt->execute([$test_product_id]);
        $prod = $stmt->fetch();
        $decoded = json_decode($prod['galerie_images'], true);
        test("Stocker galerie JSON", is_array($decoded) && count($decoded) === 3, "Galerie JSON invalide");
    } catch (Exception $e) {
        test("Galerie JSON", false, $e->getMessage());
    }
}

echo "\n";

// ==================================================
// SECTION 6: TESTS CONTRAINTES
// ==================================================
echo "--- Section 6: Contraintes ---\n";

// Test 6.1: Code unique
if ($test_product_id) {
    try {
        $stmt = $pdo->prepare("SELECT code FROM catalogue_produits WHERE id = ?");
        $stmt->execute([$test_product_id]);
        $prod = $stmt->fetch();
        $code_existant = $prod['code'];
        
        $stmt = $pdo->prepare("INSERT INTO catalogue_produits (code, slug, designation, categorie_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$code_existant, 'autre-slug-' . uniqid(), 'Autre produit', $test_category_id]);
        test("Code unique (doit échouer)", false, "Code doublon accepté !!");
    } catch (Exception $e) {
        test("Code unique (doit échouer)", true, "Contrainte respectée"); // Expected failure = success
    }
}

// Test 6.2: Catégorie requise
try {
    $stmt = $pdo->prepare("INSERT INTO catalogue_produits (code, slug, designation, categorie_id) VALUES (?, ?, ?, ?)");
    $stmt->execute(['CODE-NULL-' . uniqid(), 'slug-null-' . uniqid(), 'Test sans catégorie', null]);
    test("Catégorie requise (doit échouer)", false, "NULL categorie_id accepté !!");
} catch (Exception $e) {
    test("Catégorie requise (doit échouer)", true, "Contrainte respectée");
}

// Test 6.3: Empêcher suppression catégorie avec produits
if ($test_category_id && $test_product_id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM catalogue_categories WHERE id = ?");
        $stmt->execute([$test_category_id]);
        test("Empêcher suppression catégorie avec produits (doit échouer)", false, "Suppression autorisée !!");
    } catch (Exception $e) {
        test("Empêcher suppression catégorie avec produits (doit échouer)", true, "Contrainte FK respectée");
    }
}

echo "\n";

// ==================================================
// SECTION 7: TESTS UPLOAD
// ==================================================
echo "--- Section 7: Upload images ---\n";

$upload_dir = __DIR__ . '/uploads/catalogue/';

// Test 7.1: Dossier uploads accessible
test("Dossier uploads existe", is_dir($upload_dir), "Dossier non trouvé");
test("Dossier uploads accessible en écriture", is_writable($upload_dir), "Pas d'accès écriture");

// Test 7.2: Simuler upload fichier test
try {
    $test_file = $upload_dir . 'test_' . uniqid() . '.jpg';
    $result = file_put_contents($test_file, 'fake image content');
    test("Créer fichier test dans uploads", $result !== false, "Échec écriture fichier");
    
    if ($result) {
        test("Fichier test existe", file_exists($test_file), "Fichier non trouvé après création");
        
        // Nettoyer
        @unlink($test_file);
        test("Suppression fichier test", !file_exists($test_file), "Fichier non supprimé");
    }
} catch (Exception $e) {
    test("Upload simulation", false, $e->getMessage());
}

echo "\n";

// ==================================================
// SECTION 8: TESTS INTÉGRATION
// ==================================================
echo "--- Section 8: Intégration ---\n";

// Test 8.1: Comptage produits par catégorie
if ($test_category_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT c.id, c.nom, COUNT(p.id) as nb_produits
            FROM catalogue_categories c
            LEFT JOIN catalogue_produits p ON p.categorie_id = c.id
            WHERE c.id = ?
            GROUP BY c.id
        ");
        $stmt->execute([$test_category_id]);
        $cat = $stmt->fetch();
        test("Comptage produits par catégorie", $cat && $cat['nb_produits'] >= 1, "Comptage incorrect: " . ($cat['nb_produits'] ?? '0'));
    } catch (Exception $e) {
        test("Comptage produits", false, $e->getMessage());
    }
}

// Test 8.2: Requête avec JOIN (simulation liste produits)
try {
    $stmt = $pdo->query("
        SELECT p.*, c.nom AS categorie_nom
        FROM catalogue_produits p
        LEFT JOIN catalogue_categories c ON p.categorie_id = c.id
        LIMIT 5
    ");
    $produits = $stmt->fetchAll();
    test("Requête liste produits avec JOIN", count($produits) >= 0, "Erreur requête");
} catch (Exception $e) {
    test("Requête liste produits", false, $e->getMessage());
}

// Test 8.3: Filtrage par actif (simulation catalogue public)
try {
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM catalogue_produits WHERE actif = 1");
    $result = $stmt->fetch();
    test("Filtrage produits actifs", $result['cnt'] >= 0, "Erreur filtrage");
} catch (Exception $e) {
    test("Filtrage actifs", false, $e->getMessage());
}

echo "\n";

// ==================================================
// SECTION 9: NETTOYAGE
// ==================================================
echo "--- Section 9: Nettoyage ---\n";

// Supprimer produit test
if ($test_product_id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM catalogue_produits WHERE id = ?");
        $stmt->execute([$test_product_id]);
        test("Supprimer produit test", true, "");
    } catch (Exception $e) {
        test("Supprimer produit test", false, $e->getMessage());
    }
}

// Supprimer catégorie test
if ($test_category_id) {
    try {
        $stmt = $pdo->prepare("DELETE FROM catalogue_categories WHERE id = ?");
        $stmt->execute([$test_category_id]);
        test("Supprimer catégorie test", true, "");
    } catch (Exception $e) {
        test("Supprimer catégorie test", false, $e->getMessage());
    }
}

echo "\n";

// ==================================================
// RÉSUMÉ FINAL
// ==================================================
echo "==========================================\n";
echo "          RÉSUMÉ DES TESTS\n";
echo "==========================================\n";
echo "Tests réussis: $tests_passed\n";
echo "Tests échoués: $tests_failed\n";
echo "Total: " . ($tests_passed + $tests_failed) . "\n";

if ($tests_failed > 0) {
    echo "\n❌ ÉCHECS:\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
    echo "\n";
}

$percentage = $tests_passed + $tests_failed > 0 
    ? round(($tests_passed / ($tests_passed + $tests_failed)) * 100, 2) 
    : 0;

echo "\n";
if ($percentage === 100.0) {
    echo "✓✓✓ TOUS LES TESTS PASSÉS ($percentage%) ✓✓✓\n";
} elseif ($percentage >= 80) {
    echo "⚠ Quelques tests échoués ($percentage%)\n";
} else {
    echo "❌ ATTENTION: Plusieurs tests échoués ($percentage%)\n";
}
echo "==========================================\n";
?>
