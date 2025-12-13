<?php
/**
 * Integration Hôtel & Formation → Caisse
 * 
 * Ce script crée les triggers MySQL et fonctions PHP nécessaires pour que
 * les réservations hôtel et inscriptions formation aient un impact immédiat
 * sur la caisse et apparaissent dans le tableau de bord.
 */

require_once __DIR__ . '/db/db.php';
require_once __DIR__ . '/lib/caisse.php';

echo "=== INTÉGRATION HÔTEL & FORMATION → CAISSE ===\n\n";

try {
    // ÉTAPE 1 : Enregistrer les réservations hôtel existantes en caisse
    echo "1️⃣  Enregistrement des réservations hôtel existantes...\n";
    
    $stmt = $pdo->query("
        SELECT id, montant_total, statut, client_id, date_reservation
        FROM reservations_hotel
        WHERE montant_total > 0
    ");
    $reservations = $stmt->fetchAll();
    
    $count_hotel = 0;
    foreach ($reservations as $res) {
        // Vérifier si déjà en caisse
        $check = $pdo->prepare("SELECT id FROM caisse_journal WHERE source_type = 'reservation_hotel' AND source_id = ?");
        $check->execute([$res['id']]);
        
        if (!$check->fetch()) {
            // Enregistrer en caisse
            $stmt_caisse = $pdo->prepare("
                INSERT INTO caisse_journal 
                (date_ecriture, montant, sens, source_type, source_id, utilisateur_id, commentaire)
                VALUES (?, ?, 'ENTREE', 'reservation_hotel', ?, 1, ?)
            ");
            $stmt_caisse->execute([
                $res['date_reservation'],
                $res['montant_total'],
                $res['id'],
                "Réservation hôtel #" . $res['id']
            ]);
            $count_hotel++;
        }
    }
    echo "   ✅ {$count_hotel} réservations enregistrées en caisse\n\n";
    
    // ÉTAPE 2 : Enregistrer les inscriptions formation existantes en caisse
    echo "2️⃣  Enregistrement des inscriptions formation existantes...\n";
    
    $stmt = $pdo->query("
        SELECT id, montant_paye, client_id, date_inscription
        FROM inscriptions_formation
        WHERE montant_paye > 0
    ");
    $inscriptions = $stmt->fetchAll();
    
    $count_formation = 0;
    foreach ($inscriptions as $ins) {
        // Vérifier si déjà en caisse
        $check = $pdo->prepare("SELECT id FROM caisse_journal WHERE source_type = 'inscription_formation' AND source_id = ?");
        $check->execute([$ins['id']]);
        
        if (!$check->fetch()) {
            // Enregistrer en caisse
            $stmt_caisse = $pdo->prepare("
                INSERT INTO caisse_journal 
                (date_ecriture, montant, sens, source_type, source_id, utilisateur_id, commentaire)
                VALUES (?, ?, 'ENTREE', 'inscription_formation', ?, 1, ?)
            ");
            $stmt_caisse->execute([
                $ins['date_inscription'],
                $ins['montant_paye'],
                $ins['id'],
                "Inscription formation #" . $ins['id']
            ]);
            $count_formation++;
        }
    }
    echo "   ✅ {$count_formation} inscriptions enregistrées en caisse\n\n";
    
    // ÉTAPE 3 : Créer les triggers automatiques
    echo "3️⃣  Création des triggers MySQL...\n";
    
    // Supprimer les anciens triggers s'ils existent
    $pdo->exec("DROP TRIGGER IF EXISTS after_reservation_hotel_insert");
    $pdo->exec("DROP TRIGGER IF EXISTS after_reservation_hotel_update");
    $pdo->exec("DROP TRIGGER IF EXISTS after_inscription_formation_insert");
    $pdo->exec("DROP TRIGGER IF EXISTS after_inscription_formation_update");
    
    // Trigger : INSERT réservation hôtel
    $pdo->exec("
        CREATE TRIGGER after_reservation_hotel_insert
        AFTER INSERT ON reservations_hotel
        FOR EACH ROW
        BEGIN
            IF NEW.montant_total > 0 THEN
                INSERT INTO caisse_journal 
                (date_ecriture, montant, sens, source_type, source_id, utilisateur_id, commentaire)
                VALUES (
                    NEW.date_reservation,
                    NEW.montant_total,
                    'ENTREE',
                    'reservation_hotel',
                    NEW.id,
                    COALESCE(NEW.concierge_id, 1),
                    CONCAT('Réservation hôtel #', NEW.id)
                );
            END IF;
        END
    ");
    echo "   ✅ Trigger after_reservation_hotel_insert créé\n";
    
    // Trigger : UPDATE réservation hôtel (si montant change)
    $pdo->exec("
        CREATE TRIGGER after_reservation_hotel_update
        AFTER UPDATE ON reservations_hotel
        FOR EACH ROW
        BEGIN
            IF NEW.montant_total != OLD.montant_total THEN
                -- Annuler l'ancienne écriture
                DELETE FROM caisse_journal 
                WHERE source_type = 'reservation_hotel' AND source_id = NEW.id;
                
                -- Créer nouvelle écriture si montant > 0
                IF NEW.montant_total > 0 THEN
                    INSERT INTO caisse_journal 
                    (date_ecriture, montant, sens, source_type, source_id, utilisateur_id, commentaire)
                    VALUES (
                        NEW.date_reservation,
                        NEW.montant_total,
                        'ENTREE',
                        'reservation_hotel',
                        NEW.id,
                        COALESCE(NEW.concierge_id, 1),
                        CONCAT('Réservation hôtel #', NEW.id)
                    );
                END IF;
            END IF;
        END
    ");
    echo "   ✅ Trigger after_reservation_hotel_update créé\n";
    
    // Trigger : INSERT inscription formation
    $pdo->exec("
        CREATE TRIGGER after_inscription_formation_insert
        AFTER INSERT ON inscriptions_formation
        FOR EACH ROW
        BEGIN
            IF NEW.montant_paye > 0 THEN
                INSERT INTO caisse_journal 
                (date_ecriture, montant, sens, source_type, source_id, utilisateur_id, commentaire)
                VALUES (
                    NEW.date_inscription,
                    NEW.montant_paye,
                    'ENTREE',
                    'inscription_formation',
                    NEW.id,
                    1,
                    CONCAT('Inscription formation #', NEW.id)
                );
            END IF;
        END
    ");
    echo "   ✅ Trigger after_inscription_formation_insert créé\n";
    
    // Trigger : UPDATE inscription formation (si paiement change)
    $pdo->exec("
        CREATE TRIGGER after_inscription_formation_update
        AFTER UPDATE ON inscriptions_formation
        FOR EACH ROW
        BEGIN
            IF NEW.montant_paye != OLD.montant_paye THEN
                -- Annuler l'ancienne écriture
                DELETE FROM caisse_journal 
                WHERE source_type = 'inscription_formation' AND source_id = NEW.id;
                
                -- Créer nouvelle écriture si montant > 0
                IF NEW.montant_paye > 0 THEN
                    INSERT INTO caisse_journal 
                    (date_ecriture, montant, sens, source_type, source_id, utilisateur_id, commentaire)
                    VALUES (
                        NEW.date_inscription,
                        NEW.montant_paye,
                        'ENTREE',
                        'inscription_formation',
                        NEW.id,
                        1,
                        CONCAT('Inscription formation #', NEW.id)
                    );
                END IF;
            END IF;
        END
    ");
    echo "   ✅ Trigger after_inscription_formation_update créé\n\n";
    
    // ÉTAPE 4 : Vérification finale
    echo "4️⃣  VÉRIFICATION FINALE\n";
    echo str_repeat("=", 60) . "\n";
    
    $stmt = $pdo->query("
        SELECT 
            source_type,
            COUNT(*) as nb_operations,
            SUM(montant) as total
        FROM caisse_journal
        WHERE sens = 'ENTREE'
        GROUP BY source_type
        ORDER BY source_type
    ");
    
    echo sprintf("%-30s %10s %20s\n", "Source", "Opérations", "Total (FCFA)");
    echo str_repeat("-", 60) . "\n";
    
    $total_general = 0;
    while ($row = $stmt->fetch()) {
        echo sprintf(
            "%-30s %10d %20s\n",
            $row['source_type'],
            $row['nb_operations'],
            number_format($row['total'], 2, ',', ' ')
        );
        $total_general += $row['total'];
    }
    
    echo str_repeat("=", 60) . "\n";
    echo sprintf("%-30s %10s %20s\n", "TOTAL GÉNÉRAL", "", number_format($total_general, 2, ',', ' '));
    echo str_repeat("=", 60) . "\n\n";
    
    echo "✅ INTÉGRATION RÉUSSIE !\n\n";
    echo "🎯 Impacts :\n";
    echo "   • Les réservations hôtel créent automatiquement une écriture en caisse\n";
    echo "   • Les inscriptions formation créent automatiquement une écriture en caisse\n";
    echo "   • Le tableau de bord affichera le CA total multi-canal\n";
    echo "   • Le bilan comptable sera plus cohérent\n\n";
    
} catch (Exception $e) {
    echo "❌ ERREUR : " . $e->getMessage() . "\n";
    echo "Trace : " . $e->getTraceAsString() . "\n";
}
