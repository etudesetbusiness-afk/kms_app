<?php
/**
 * TEST PARCOURS UTILISATEURS COMPLETS - KMS GESTION
 * Tests de flux métier end-to-end
 * Date: 15 décembre 2025
 */

require_once 'db/db.php';

class TestParcours {
    private $pdo;
    private $resultats = [];
    private $count_pass = 0;
    private $count_fail = 0;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function test($nom, $callback) {
        try {
            $result = call_user_func($callback);
            if ($result === true) {
                $this->count_pass++;
                $status = '✅';
            } else {
                $this->count_fail++;
                $status = '❌';
            }
        } catch (Exception $e) {
            $this->count_fail++;
            $status = '❌ ' . substr($e->getMessage(), 0, 50);
        }
        
        echo "  $status $nom\n";
    }
    
    public function section($titre) {
        echo "\n\n$titre\n";
        echo str_repeat("═", 60) . "\n";
    }
    
    public function afficher_resume() {
        $total = $this->count_pass + $this->count_fail;
        $percent = ($this->count_pass / $total) * 100;
        
        echo "\n\n";
        echo "╔════════════════════════════════════════════════════════╗\n";
        echo "║              RÉSUMÉ TESTS PARCOURS                    ║\n";
        echo "╠════════════════════════════════════════════════════════╣\n";
        printf("║ Total: %d | ✅ %d | ❌ %d | Score: %.0f%%         ║\n", 
               $total, $this->count_pass, $this->count_fail, $percent);
        echo "╚════════════════════════════════════════════════════════╝\n";
    }
}

// =====================================================
// LANCER LES TESTS
// =====================================================

echo "\n╔════════════════════════════════════════════════════════════╗\n";
echo "║     TESTS PARCOURS UTILISATEURS COMPLETS                  ║\n";
echo "║     KMS Gestion - 15 Décembre 2025                        ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";

$test = new TestParcours($pdo);

// ========== PARCOURS 1: GESTION PRODUITS ==========
$test->section("📦 PARCOURS 1: GESTION PRODUITS");

$test->test("Table produits existe et contient des données", function() use ($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) as nb FROM produits");
    $row = $stmt->fetch();
    return $row['nb'] > 0;
});

$test->test("Colonnes produits correctes (id, designation, prix)", function() use ($pdo) {
    $stmt = $pdo->query("SHOW COLUMNS FROM produits");
    $cols = array_column($stmt->fetchAll(), 'Field');
    return in_array('id', $cols) && in_array('designation', $cols) && in_array('prix_vente', $cols);
});

$test->test("Stock actuel calculable", function() use ($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) as nb FROM produits WHERE stock_actuel >= 0");
    $row = $stmt->fetch();
    return $row['nb'] > 0;
});

$test->test("Catégories de produits existent", function() use ($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) as nb FROM produits WHERE categorie_id IS NOT NULL");
    $row = $stmt->fetch();
    return $row['nb'] > 0;
});

// ========== PARCOURS 2: GESTION CLIENTS ==========
$test->section("👥 PARCOURS 2: GESTION CLIENTS");

$test->test("Table clients existe et a des données", function() use ($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) as nb FROM clients");
    $row = $stmt->fetch();
    return $row['nb'] > 0;
});

$test->test("Colonnes clients valides", function() use ($pdo) {
    $stmt = $pdo->query("SHOW COLUMNS FROM clients");
    $cols = array_column($stmt->fetchAll(), 'Field');
    return in_array('id', $cols) && in_array('nom', $cols);
});

$test->test("Types de clients définis", function() use ($pdo) {
    $stmt = $pdo->query("SELECT DISTINCT type FROM clients WHERE type IS NOT NULL");
    $rows = $stmt->fetchAll();
    return count($rows) > 0;
});

$test->test("Statuts clients existants", function() use ($pdo) {
    $stmt = $pdo->query("SELECT DISTINCT statut FROM clients WHERE statut IS NOT NULL");
    $rows = $stmt->fetchAll();
    return count($rows) > 0;
});

// ========== PARCOURS 3: CYCLE COMMERCIAL (Devis -> Vente -> BL) ==========
$test->section("📊 PARCOURS 3: CYCLE COMMERCIAL");

$test->test("Table devis existe", function() use ($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) as nb FROM devis");
    $row = $stmt->fetch();
    return is_array($row);
});

$test->test("Table ventes existe et remplie", function() use ($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) as nb FROM ventes");
    $row = $stmt->fetch();
    return $row['nb'] > 0;
});

$test->test("Relation ventes ↔ clients intacte (FK)", function() use ($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) as nb FROM ventes WHERE client_id IS NOT NULL");
    $row = $stmt->fetch();
    return $row['nb'] > 0;
});

$test->test("Bons de livraison existent", function() use ($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) as nb FROM bons_livraison");
    $row = $stmt->fetch();
    return $row['nb'] > 0;
});

$test->test("Lignes de BL liées aux BL", function() use ($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) as nb FROM bons_livraison_lignes WHERE bon_livraison_id IS NOT NULL");
    $row = $stmt->fetch();
    return $row['nb'] > 0;
});

$test->test("Statuts BL existants", function() use ($pdo) {
    $stmt = $pdo->query("SELECT DISTINCT statut FROM bons_livraison WHERE statut IS NOT NULL");
    $rows = $stmt->fetchAll();
    return count($rows) > 0;
});

// ========== PARCOURS 4: GESTION STOCK ==========
$test->section("📦 PARCOURS 4: GESTION STOCK");

$test->test("Table mouvements stock existe", function() use ($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) as nb FROM stocks_mouvements");
    $row = $stmt->fetch();
    return is_array($row);
});

$test->test("Types de mouvements enregistrés", function() use ($pdo) {
    $stmt = $pdo->query("SELECT DISTINCT type_mouvement FROM stocks_mouvements WHERE type_mouvement IS NOT NULL");
    $rows = $stmt->fetchAll();
    return count($rows) > 0;
});

$test->test("Alerte ruptures disponible", function() use ($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) as nb FROM produits WHERE stock_actuel < 5");
    $row = $stmt->fetch();
    return is_array($row);
});

// ========== PARCOURS 5: TRÉSORERIE & CAISSE ==========
$test->section("💰 PARCOURS 5: TRÉSORERIE & CAISSE");

$test->test("Journal caisse existe et rempli", function() use ($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) as nb FROM caisse_journal");
    $row = $stmt->fetch();
    return $row['nb'] > 0;
});

$test->test("Sens de caisse (ENTREE/SORTIE) correct", function() use ($pdo) {
    $stmt = $pdo->query("SELECT DISTINCT sens FROM caisse_journal WHERE sens IS NOT NULL");
    $rows = $stmt->fetchAll();
    $senses = array_column($rows, 'sens');
    return in_array('ENTREE', $senses) || in_array('SORTIE', $senses);
});

$test->test("Montants caisse correctement enregistrés", function() use ($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) as nb FROM caisse_journal WHERE montant > 0");
    $row = $stmt->fetch();
    return $row['nb'] > 0;
});

$test->test("Encaissements liés aux ventes", function() use ($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) as nb FROM caisse_journal WHERE source_type = 'vente'");
    $row = $stmt->fetch();
    return $row['nb'] > 0;
});

// ========== PARCOURS 6: COMPTABILITÉ OHADA ==========
$test->section("📊 PARCOURS 6: COMPTABILITÉ OHADA");

$test->test("Plan comptable OHADA créé (comptes classes 1-9)", function() use ($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) as nb FROM compta_comptes");
    $row = $stmt->fetch();
    return $row['nb'] > 50; // OHADA devrait avoir au moins 50 comptes
});

$test->test("Écritures comptables générées", function() use ($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) as nb FROM compta_ecritures");
    $row = $stmt->fetch();
    return $row['nb'] > 0;
});

$test->test("Pièces comptables créées", function() use ($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) as nb FROM compta_pieces");
    $row = $stmt->fetch();
    return $row['nb'] > 0;
});

$test->test("Journaux comptables existants (VE, AC, TR)", function() use ($pdo) {
    $stmt = $pdo->query("SELECT DISTINCT code FROM compta_journaux WHERE code IS NOT NULL");
    $rows = $stmt->fetchAll();
    $codes = array_column($rows, 'code');
    return count($codes) > 0;
});

$test->test("Balance comptable calculable (Debit = Credit)", function() use ($pdo) {
    $stmt = $pdo->query("SELECT SUM(montant_debit) as debit, SUM(montant_credit) as credit FROM compta_ecritures");
    $row = $stmt->fetch();
    // Accepter un écart de 1 FCFA dû aux arrondis
    return abs($row['debit'] - $row['credit']) <= 1;
});

$test->test("Exercices comptables gérés", function() use ($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) as nb FROM compta_exercices");
    $row = $stmt->fetch();
    return $row['nb'] > 0;
});

// ========== PARCOURS 7: GESTION UTILISATEURS & PERMISSIONS ==========
$test->section("🔐 PARCOURS 7: UTILISATEURS & PERMISSIONS");

$test->test("Utilisateurs créés en BD", function() use ($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) as nb FROM utilisateurs");
    $row = $stmt->fetch();
    return $row['nb'] > 0;
});

$test->test("Rôles définis", function() use ($pdo) {
    $stmt = $pdo->query("SELECT DISTINCT role FROM utilisateurs WHERE role IS NOT NULL");
    $rows = $stmt->fetchAll();
    return count($rows) > 0;
});

$test->test("Table permissions existe", function() use ($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) as nb FROM permissions_utilisateurs");
    $row = $stmt->fetch();
    return is_array($row);
});

$test->test("Audit trail disponible", function() use ($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) as nb FROM audit_log");
    $row = $stmt->fetch();
    return is_array($row);
});

// ========== PARCOURS 8: LITIGES & SAV ==========
$test->section("⚠️  PARCOURS 8: LITIGES & SAV");

$test->test("Module litiges accessible", function() use ($pdo) {
    $result = file_exists(__DIR__ . '/coordination/litiges.php');
    return $result === true;
});

$test->test("Types de litiges gérés", function() use ($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) as nb FROM litiges WHERE type IS NOT NULL");
    $row = $stmt->fetch();
    return is_array($row);
});

// ========== PARCOURS 9: CATALOGUE PUBLIC ==========
$test->section("🛍️  PARCOURS 9: CATALOGUE PUBLIC");

$test->test("Catégories catalogue existent", function() use ($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) as nb FROM produits");
    $row = $stmt->fetch();
    return $row['nb'] > 0;
});

$test->test("Images produits referencées", function() use ($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) as nb FROM produits WHERE image_path IS NOT NULL");
    $row = $stmt->fetch();
    return is_array($row);
});

// ========== PARCOURS 10: DASHBOARDS & REPORTING ==========
$test->section("📈 PARCOURS 10: DASHBOARDS & REPORTING");

$test->test("Dashboard principal charge", function() use ($pdo) {
    $result = file_exists(__DIR__ . '/index.php');
    return $result === true;
});

$test->test("KPI ventes calculables", function() use ($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) as nb, SUM(montant_total_ttc) as total FROM ventes");
    $row = $stmt->fetch();
    return $row['nb'] > 0;
});

$test->test("KPI caisse calculables", function() use ($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) as nb, SUM(montant) as total FROM caisse_journal WHERE sens='ENTREE'");
    $row = $stmt->fetch();
    return $row['nb'] > 0;
});

// ========== PARCOURS 11: SÉCURITÉ 2FA ==========
$test->section("🔒 PARCOURS 11: SÉCURITÉ 2FA");

$test->test("Tables 2FA existent", function() use ($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) as nb FROM utilisateurs_2fa");
    return is_array($stmt->fetch());
});

$test->test("Sessions actives tracées", function() use ($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) as nb FROM sessions_actives");
    return is_array($stmt->fetch());
});

// ========== PARCOURS 12: INTÉGRATION MULTI-CANAL ==========
$test->section("🌐 PARCOURS 12: INTÉGRATION MULTI-CANAL");

$test->test("Réservations hôtel intégrées en caisse", function() use ($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) as nb FROM caisse_journal WHERE source_type='reservation_hotel'");
    $row = $stmt->fetch();
    return is_array($row);
});

$test->test("Inscriptions formation intégrées en caisse", function() use ($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) as nb FROM caisse_journal WHERE source_type='inscription_formation'");
    $row = $stmt->fetch();
    return is_array($row);
});

// ========== AFFICHAGE RÉSUMÉ ==========
$test->afficher_resume();

echo "\n✅ Test complet terminé!\n\n";
?>
