<?php
/**
 * Générateur simplifié de données démo pour KMS Gestion
 * Version adaptée aux structures réelles de la base
 */

require_once __DIR__ . '/db/db.php';
require_once __DIR__ . '/lib/stock.php';
require_once __DIR__ . '/lib/caisse.php';

set_time_limit(600);

echo "=== GÉNÉRATION DONNÉES DÉMO KMS (version simplifiée) ===\n\n";

$PERIODE_DEBUT = date('Y-m-d', strtotime('-60 days'));
$stats = ['clients' => 0, 'produits' => 0, 'devis' => 0, 'ventes' => 0, 'livraisons' => 0, 'encaissements' => 0];

try {
    $pdo->beginTransaction();
    
    // ============ CLIENTS ============
    echo "👥 Clients...\n";
    $nomsCI = ['Kouassi', 'Koné', 'Bamba', 'Touré', 'Yao', 'N\'Guessan', 'Ouattara', 'Coulibaly'];
    $prenoms = ['Jean', 'Marie', 'Ibrahim', 'Fatou', 'Aya', 'Mamadou', 'Aminata', 'Kouadio'];
    
    $clientsIds = [];
    for ($i = 0; $i < 30; $i++) {
        $nom = $nomsCI[array_rand($nomsCI)] . ' ' . $prenoms[array_rand($prenoms)];
        $stmt = $pdo->prepare("
            INSERT INTO clients (nom, type_client_id, telephone, email, adresse, source, statut)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $nom, rand(1, 4), 
            '0' . rand(1, 7) . sprintf('%08d', rand(0, 99999999)),
            strtolower(str_replace([' ', '\''], '.', $nom)) . '@email.ci',
            'Abidjan, Cocody',
            ['Showroom', 'Terrain', 'Digital'][rand(0, 2)],
            ['CLIENT', 'CLIENT', 'PROSPECT'][rand(0, 2)]
        ]);
        $clientsIds[] = $pdo->lastInsertId();
        $stats['clients']++;
    }
    echo "   ✅ {$stats['clients']} clients\n\n";
    
    // ============ PRODUITS ============
    echo "📦 Produits...\n";
    
    // Familles
    $familles = ['Electricite', 'Plomberie', 'Peinture', 'Quincaillerie', 'Construction'];
    $famillesIds = [];
    foreach ($familles as $fam) {
        $stmt = $pdo->prepare("SELECT id FROM familles_produits WHERE nom = ?");
        $stmt->execute([$fam]);
        $id = $stmt->fetchColumn();
        if (!$id) {
            $pdo->prepare("INSERT INTO familles_produits (nom) VALUES (?)")->execute([$fam]);
            $id = $pdo->lastInsertId();
        }
        $famillesIds[] = $id;
    }
    
    // Produits
    $produits = [
        ['CBL-001', 'Cable electrique 2.5mm2', 0, 45000, 25000, 150],
        ['DISJ-001', 'Disjoncteur 16A', 0, 8500, 5000, 200],
        ['PRISE-001', 'Prise double', 0, 2500, 1500, 300],
        ['TUY-001', 'Tube PVC 110mm', 1, 12000, 7000, 100],
        ['ROB-001', 'Robinet chrome', 1, 15000, 9000, 80],
        ['WC-001', 'WC complet', 1, 85000, 50000, 30],
        ['PEIN-001', 'Peinture int 25L', 2, 35000, 20000, 60],
        ['PEIN-002', 'Peinture ext 25L', 2, 42000, 25000, 50],
        ['ROUL-001', 'Rouleau 230mm', 2, 3500, 2000, 100],
        ['MART-001', 'Marteau 500g', 3, 6500, 3500, 60],
        ['SCIE-001', 'Scie metaux', 3, 8500, 5000, 40],
        ['CIM-001', 'Ciment 50kg', 4, 5500, 3200, 500],
        ['BRIQUE-001', 'Brique creuse', 4, 250, 150, 5000],
        ['CARR-001', 'Carreau 40x40', 4, 8500, 5000, 150],
    ];
    
    $produitsIds = [];
    foreach ($produits as $p) {
        $stmt = $pdo->prepare("
            INSERT INTO produits 
            (code_produit, famille_id, prix_vente, prix_achat, stock_actuel, seuil_alerte)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$p[0], $p[1], $famillesIds[$p[2]], $p[3], $p[4], $p[5], 10]);
        $prodId = $pdo->lastInsertId();
        $produitsIds[] = $prodId;
        $stats['produits']++;
        
        // Stock initial
        stock_enregistrer_mouvement($pdo, [
            'produit_id' => $prodId,
            'type_mouvement' => 'ENTREE_ACHAT',
            'quantite' => $p[5],
            'commentaire' => 'Stock initial',
            'utilisateur_id' => 1
        ]);
    }
    echo "   ✅ {$stats['produits']} produits\n\n";
    
    // ============ DEVIS ============
    echo "📄 Devis...\n";
    
    // Récupérer canal pour devis
    $stmt = $pdo->query("SELECT id FROM canaux_vente WHERE code = 'SHOWROOM'");
    $canalDevisId = $stmt->fetchColumn() ?: 1;
    
    $devisIds = [];
    for ($i = 0; $i < 25; $i++) {
        $clientId = $clientsIds[array_rand($clientsIds)];
        $dateDevis = date('Y-m-d', strtotime($PERIODE_DEBUT . ' +' . rand(0, 55) . ' days'));
        $numero = 'DEV-' . date('Ymd', strtotime($dateDevis)) . '-' . str_pad($i + 1, 3, '0', STR_PAD_LEFT);
        $statut = (rand(1, 100) <= 50) ? 'ACCEPTE' : 'EN_ATTENTE';
        
        $stmt = $pdo->prepare("
            INSERT INTO devis (numero, date_devis, client_id, canal_vente_id, montant_total_ht, montant_total_ttc, statut, utilisateur_id)
            VALUES (?, ?, ?, ?, 0, 0, ?, 1)
        ");
        $stmt->execute([$numero, $dateDevis, $clientId, $canalDevisId, $statut]);
        $devisId = $pdo->lastInsertId();
        $devisIds[] = ['id' => $devisId, 'statut' => $statut, 'date' => $dateDevis, 'client' => $clientId];
        
        // Lignes
        $nbLignes = rand(2, 5);
        $total = 0;
        for ($j = 0; $j < $nbLignes; $j++) {
            $prodId = $produitsIds[array_rand($produitsIds)];
            $stmt = $pdo->prepare("SELECT prix_vente, designation FROM produits WHERE id = ?");
            $stmt->execute([$prodId]);
            $prod = $stmt->fetch();
            
            $qte = rand(1, 15);
            $montant = $qte * $prod['prix_vente'];
            $total += $montant;
            
            $pdo->prepare("
                INSERT INTO devis_lignes 
                (devis_id, produit_id, quantite, prix_unitaire, remise, montant_ligne_ht)
                VALUES (?, ?, ?, ?, 0, ?)
            ")->execute([$devisId, $prodId, $qte, $prod['prix_vente'], $montant]);
        }
        
        $pdo->prepare("UPDATE devis SET montant_total_ht = ?, montant_total_ttc = ? WHERE id = ?")
            ->execute([$total, $total, $devisId]);
        $stats['devis']++;
    }
    echo "   ✅ {$stats['devis']} devis\n\n";
    
    // ============ VENTES ============
    echo "💰 Ventes...\n";
    
    // Récupérer les canaux
    $stmt = $pdo->query("SELECT id FROM canaux_vente WHERE code = 'SHOWROOM'");
    $canalId = $stmt->fetchColumn() ?: 1;
    
    $ventesIds = [];
    
    // Ventes depuis devis acceptés
    $devisAcceptes = array_filter($devisIds, fn($d) => $d['statut'] === 'ACCEPTE');
    foreach ($devisAcceptes as $devis) {
        $dateVente = date('Y-m-d', strtotime($devis['date'] . ' +' . rand(1, 7) . ' days'));
        $numero = 'VTE-' . date('Ymd', strtotime($dateVente)) . '-' . str_pad(count($ventesIds) + 1, 3, '0', STR_PAD_LEFT);
        
        $stmt = $pdo->prepare("SELECT montant_total_ht, montant_total_ttc FROM devis WHERE id = ?");
        $stmt->execute([$devis['id']]);
        $montants = $stmt->fetch();
        
        $stmt = $pdo->prepare("
            INSERT INTO ventes 
            (numero, date_vente, client_id, canal_vente_id, devis_id, 
             montant_total_ht, montant_total_ttc, statut, utilisateur_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'EN_ATTENTE_LIVRAISON', 1)
        ");
        $stmt->execute([
            $numero, $dateVente, $devis['client'], $canalId, $devis['id'],
            $montants['montant_total_ht'], $montants['montant_total_ttc']
        ]);
        $venteId = $pdo->lastInsertId();
        $ventesIds[] = ['id' => $venteId, 'date' => $dateVente, 'client' => $devis['client'], 'montant' => $montants['montant_total_ttc']];
        
        // Copier lignes
        $stmt = $pdo->prepare("SELECT * FROM devis_lignes WHERE devis_id = ?");
        $stmt->execute([$devis['id']]);
        foreach ($stmt->fetchAll() as $ligne) {
            $pdo->prepare("
                INSERT INTO ventes_lignes 
                (vente_id, produit_id, quantite, prix_unitaire, remise, montant_ligne_ht)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ")->execute([
                $venteId, $ligne['produit_id'], $ligne['designation'],
                $ligne['quantite'], $ligne['prix_unitaire'], $ligne['remise'], $ligne['montant_ligne_ht']
            ]);
        }
        $stats['ventes']++;
    }
    
    // Ventes directes
    for ($i = 0; $i < 20; $i++) {
        $clientId = $clientsIds[array_rand($clientsIds)];
        $dateVente = date('Y-m-d', strtotime($PERIODE_DEBUT . ' +' . rand(0, 60) . ' days'));
        $numero = 'VTE-' . date('Ymd', strtotime($dateVente)) . '-' . str_pad(count($ventesIds) + 1, 3, '0', STR_PAD_LEFT);
        
        $stmt = $pdo->prepare("
            INSERT INTO ventes 
            (numero, date_vente, client_id, canal_vente_id, montant_total_ht, montant_total_ttc, statut, utilisateur_id)
            VALUES (?, ?, ?, ?, 0, 0, 'EN_ATTENTE_LIVRAISON', 1)
        ");
        $stmt->execute([$numero, $dateVente, $clientId, $canalId]);
        $venteId = $pdo->lastInsertId();
        
        // Lignes
        $nbLignes = rand(1, 4);
        $total = 0;
        for ($j = 0; $j < $nbLignes; $j++) {
            $prodId = $produitsIds[array_rand($produitsIds)];
            $stmt = $pdo->prepare("SELECT prix_vente, designation FROM produits WHERE id = ?");
            $stmt->execute([$prodId]);
            $prod = $stmt->fetch();
            
            $qte = rand(1, 10);
            $montant = $qte * $prod['prix_vente'];
            $total += $montant;
            
            $pdo->prepare("
                INSERT INTO ventes_lignes 
                (vente_id, produit_id, quantite, prix_unitaire, remise, montant_ligne_ht)
                VALUES (?, ?, ?, ?, ?, 0, ?)
            ")->execute([$venteId, $prodId, $prod['designation'], $qte, $prod['prix_vente'], $montant]);
        }
        
        $pdo->prepare("UPDATE ventes SET montant_total_ht = ?, montant_total_ttc = ? WHERE id = ?")
            ->execute([$total, $total, $venteId]);
        
        $ventesIds[] = ['id' => $venteId, 'date' => $dateVente, 'client' => $clientId, 'montant' => $total];
        $stats['ventes']++;
    }
    echo "   ✅ {$stats['ventes']} ventes\n\n";
    
    // ============ LIVRAISONS ============
    echo "📦 Livraisons...\n";
    foreach ($ventesIds as $vente) {
        if (rand(1, 100) <= 70) {
            $dateBL = date('Y-m-d', strtotime($vente['date'] . ' +' . rand(1, 5) . ' days'));
            $numeroBL = 'BL-' . date('Ymd', strtotime($dateBL)) . '-' . str_pad($stats['livraisons'] + 1, 3, '0', STR_PAD_LEFT);
            
            $stmt = $pdo->prepare("
                INSERT INTO bons_livraison 
                (numero, date_bl, vente_id, client_id, magasinier_id, livreur_id, statut, signe_client)
                VALUES (?, ?, ?, ?, 1, 1, 'LIVRE', 1)
            ");
            $stmt->execute([$numeroBL, $dateBL, $vente['id'], $vente['client']]);
            $blId = $pdo->lastInsertId();
            
            // Lignes BL
            $stmt = $pdo->prepare("SELECT * FROM ventes_lignes WHERE vente_id = ?");
            $stmt->execute([$vente['id']]);
            foreach ($stmt->fetchAll() as $ligne) {
                $pdo->prepare("
                    INSERT INTO bons_livraison_lignes 
                    (bon_livraison_id, produit_id, quantite, 
                     quantite_commandee, quantite_restante, prix_unitaire)
                    VALUES (?, ?, ?, ?, ?, 0, ?)
                ")->execute([
                    $blId, $ligne['produit_id'], $ligne['designation'],
                    $ligne['quantite'], $ligne['quantite'], $ligne['prix_unitaire']
                ]);
                
                // Sortie stock
                stock_enregistrer_mouvement($pdo, [
                    'produit_id' => $ligne['produit_id'],
                    'type_mouvement' => 'SORTIE_VENTE',
                    'quantite' => -$ligne['quantite'],
                    'commentaire' => "Livraison $numeroBL",
                    'utilisateur_id' => 1,
                    'source_id' => $blId,
                    'source_type' => 'bon_livraison'
                ]);
            }
            
            // Statut vente
            $pdo->prepare("UPDATE ventes SET statut = 'LIVREE' WHERE id = ?")->execute([$vente['id']]);
            $stats['livraisons']++;
        }
    }
    echo "   ✅ {$stats['livraisons']} livraisons\n\n";
    
    // ============ ENCAISSEMENTS ============
    echo "💵 Encaissements...\n";
    $stmt = $pdo->query("SELECT id, montant_total_ttc, date_vente FROM ventes WHERE statut = 'LIVREE'");
    foreach ($stmt->fetchAll() as $vente) {
        if (rand(1, 100) <= 70) {
            $dateEnc = date('Y-m-d', strtotime($vente['date_vente'] . ' +' . rand(1, 7) . ' days'));
            $modes = ['ESPECES', 'MOBILE_MONEY', 'VIREMENT'];
            
            enregistrerEncaissement(
                $pdo,
                $dateEnc,
                $vente['montant_total_ttc'],
                $modes[array_rand($modes)],
                "Paiement vente",
                1,
                $vente['id']
            );
            $stats['encaissements']++;
        }
    }
    echo "   ✅ {$stats['encaissements']} encaissements\n\n";
    
    // ============ VALIDATION ============
    echo "=== VALIDATION ===\n";
    $stmt = $pdo->query("SELECT COUNT(*) FROM produits WHERE stock_actuel < 0");
    $negatif = $stmt->fetchColumn();
    echo $negatif > 0 ? "⚠️  $negatif stocks négatifs\n" : "✅ Stocks OK\n";
    
    echo "\n📊 RÉSUMÉ:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━\n";
    foreach ($stats as $k => $v) {
        printf("%-20s: %d\n", ucfirst($k), $v);
    }
    echo "━━━━━━━━━━━━━━━━━━━━━\n";
    
    $pdo->commit();
    echo "\n✅ GÉNÉRATION TERMINÉE\n\n";
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "\n❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Ligne: " . $e->getLine() . "\n";
    exit(1);
}

echo "Vos données démo sont prêtes pour tester KMS Gestion!\n";


