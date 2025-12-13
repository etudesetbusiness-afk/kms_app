<?php
/**
 * Générateur de données de démonstration v2 - Adapté à la structure réelle
 * 
 * Génère des données cohérentes pour KMS Gestion en respectant
 * strictement la structure existante de la base de données
 */

require_once __DIR__ . '/db/db.php';
require_once __DIR__ . '/lib/stock.php';
require_once __DIR__ . '/lib/caisse.php';

set_time_limit(600);
ini_set('memory_limit', '512M');

// Helper function pour simplifier les appels
function ajouterMouvementStock($pdo, $produitId, $type, $quantite, $commentaire, $userId = 1, $sourceId = null, $sourceType = null) {
    return stock_enregistrer_mouvement($pdo, [
        'produit_id' => $produitId,
        'type_mouvement' => $type,
        'quantite' => $quantite,
        'commentaire' => $commentaire,
        'utilisateur_id' => $userId,
        'source_id' => $sourceId,
        'source_type' => $sourceType
    ]);
}

echo "=== GÉNÉRATION DONNÉES DÉMO KMS v2 ===\n\n";

$PERIODE_DEBUT = date('Y-m-d', strtotime('-60 days'));
$PERIODE_FIN = date('Y-m-d');

$stats = [
    'clients' => 0,
    'produits' => 0,
    'fournisseurs' => 0,
    'devis' => 0,
    'ventes' => 0,
    'ordres' => 0,
    'livraisons' => 0,
    'mouvements_stock' => 0,
    'encaissements' => 0,
    'achats' => 0
];

try {
    $pdo->beginTransaction();
    
    echo "🔍 Récupération des canaux de vente...\n";
    $stmt = $pdo->query("SELECT id, code FROM canaux_vente");
    $canaux = [];
    while ($row = $stmt->fetch()) {
        $canaux[$row['code']] = $row['id'];
    }
    echo "   → " . count($canaux) . " canaux trouvés\n\n";
    
    // ==========================================
    // CLIENTS
    // ==========================================
    echo "👥 Génération clients...\n";
    
    $nomsCI = ['Kouassi', 'Koné', 'Bamba', 'Touré', 'Yao', 'N\'Guessan', 'Ouattara', 'Coulibaly', 'Traoré', 'Diallo'];
    $prenoms = ['Jean', 'Marie', 'Ibrahim', 'Fatou', 'Aya', 'Mamadou', 'Aminata', 'Kouadio', 'Adjoua', 'Moussa'];
    $sources = ['Showroom', 'Terrain', 'Digital', 'Référence', 'Publicité'];
    $statuts = ['PROSPECT', 'CLIENT', 'CLIENT', 'CLIENT']; // Plus de clients que de prospects
    
    $clientsIds = [];
    for ($i = 0; $i < 50; $i++) {
        $nom = $nomsCI[array_rand($nomsCI)] . ' ' . $prenoms[array_rand($prenoms)];
        $telephone = '0' . rand(1, 7) . sprintf('%08d', rand(0, 99999999));
        $email = strtolower(str_replace([' ', '\''], ['.',  ''], $nom)) . '@email.ci';
        $adresse = 'Lot ' . rand(1, 500) . ' Quartier Résidentiel, Abidjan';
        
        $stmt = $pdo->prepare("
            INSERT INTO clients (nom, type_client_id, telephone, email, adresse, source, statut, date_creation)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $nom,
            rand(1, 4),
            $telephone,
            $email,
            $adresse,
            $sources[array_rand($sources)],
            $statuts[array_rand($statuts)],
            date('Y-m-d H:i:s', strtotime("-" . rand(1, 180) . " days"))
        ]);
        $clientsIds[] = $pdo->lastInsertId();
        $stats['clients']++;
    }
    echo "   ✅ {$stats['clients']} clients créés\n\n";
    
    // ==========================================
    // FOURNISSEURS
    // ==========================================
    echo "🏭 Création fournisseurs...\n";
    
    $fournisseurs = [
        ['SOCOCE CI', 'Amadou Traoré', '2523456789', 'contact@sococe.ci'],
        ['PROSUMA', 'Marie Kouassi', '2524567890', 'info@prosuma.ci'],
        ['ELECTRA', 'Jean Bamba', '2525678901', 'ventes@electra.ci'],
        ['BATIMAT CI', 'Fatou Koné', '2526789012', 'commandes@batimat.ci'],
        ['QUINCA PLUS', 'Ibrahim Yao', '2527890123', 'contact@quincaplus.ci']
    ];
    
    $fournisseursIds = [];
    foreach ($fournisseurs as $f) {
        $stmt = $pdo->prepare("INSERT INTO fournisseurs (nom, contact, telephone, email) VALUES (?, ?, ?, ?)");
        $stmt->execute($f);
        $fournisseursIds[] = $pdo->lastInsertId();
        $stats['fournisseurs']++;
    }
    echo "   ✅ {$stats['fournisseurs']} fournisseurs créés\n\n";
    
    // ==========================================
    // FAMILLES & PRODUITS
    // ==========================================
    echo "📦 Création produits...\n";
    
    // Créer ou récupérer familles
    echo "   → Gestion des familles...\n";
    $famillesNecessaires = ['Electricite', 'Plomberie', 'Peinture', 'Quincaillerie', 'Construction'];
    $famillesIds = [];
    
    foreach ($famillesNecessaires as $fam) {
        // Vérifier si existe
        $stmt = $pdo->prepare("SELECT id FROM familles_produits WHERE nom = ?");
        $stmt->execute([$fam]);
        $existId = $stmt->fetchColumn();
        
        if ($existId) {
            $famillesIds[$fam] = $existId;
        } else {
            // Créer
            $stmt = $pdo->prepare("INSERT INTO familles_produits (nom) VALUES (?)");
            $stmt->execute([$fam]);
            $famillesIds[$fam] = $pdo->lastInsertId();
        }
    }
    
    // Produits (sans accents pour éviter problèmes d'encodage)
    $produits = [
        ['CBL-25MM', 'Cable electrique 2.5mm2 (100m)', 'Electricite', 45000, 25000, 150, 50],
        ['DISJ-16A', 'Disjoncteur 16A', 'Electricite', 8500, 5000, 200, 40],
        ['PRISE-2P', 'Prise double avec terre', 'Electricite', 2500, 1500, 300, 60],
        ['INTER-VA', 'Interrupteur va-et-vient', 'Electricite', 1800, 1000, 250, 50],
        ['LAMP-LED15', 'Ampoule LED 15W', 'Electricite', 3500, 2000, 400, 80],
        
        ['TUY-PVC110', 'Tube PVC O110mm (3m)', 'Plomberie', 12000, 7000, 100, 20],
        ['ROB-LAV', 'Robinet lavabo chrome', 'Plomberie', 15000, 9000, 80, 15],
        ['WC-COMPLET', 'WC complet avec abattant', 'Plomberie', 85000, 50000, 30, 5],
        ['LAVABO', 'Lavabo suspendu blanc', 'Plomberie', 35000, 20000, 25, 5],
        
        ['PEIN-INT25', 'Peinture interieure mate 25L', 'Peinture', 35000, 20000, 60, 10],
        ['PEIN-EXT25', 'Peinture exterieure 25L', 'Peinture', 42000, 25000, 50, 10],
        ['ROUL-230', 'Rouleau peinture 230mm', 'Peinture', 3500, 2000, 100, 20],
        
        ['MART-500', 'Marteau 500g', 'Quincaillerie', 6500, 3500, 60, 12],
        ['SCIE-MET', 'Scie a metaux', 'Quincaillerie', 8500, 5000, 40, 8],
        ['METRE-5M', 'Metre ruban 5m', 'Quincaillerie', 4500, 2500, 100, 20],
        
        ['CIM-50KG', 'Sac ciment 50kg', 'Construction', 5500, 3200, 500, 100],
        ['SABLE-M3', 'Sable fin m3', 'Construction', 18000, 12000, 50, 10],
        ['BRIQUE', 'Brique creuse (unite)', 'Construction', 250, 150, 5000, 1000],
        ['FER-12MM', 'Fer a beton O12mm (12m)', 'Construction', 15000, 9000, 200, 40],
        ['CARR-40x40', 'Carreau 40x40cm blanc (m2)', 'Construction', 8500, 5000, 150, 30],
    ];
    
    $produitsIds = [];
    foreach ($produits as $p) {
        $familleId = $famillesIds[$p[2]];
        
        $stmt = $pdo->prepare("
            INSERT INTO produits 
            (code_produit, designation, famille_id, prix_vente, prix_achat, stock_actuel, seuil_alerte)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$p[0], $p[1], $familleId, $p[3], $p[4], $p[5], $p[6]]);
        $prodId = $pdo->lastInsertId();
        $produitsIds[] = $prodId;
        $stats['produits']++;
        
        // Stock initial
        ajouterMouvementStock($pdo, $prodId, 'ENTREE_ACHAT', $p[5], 'Stock initial', 1);
        $stats['mouvements_stock']++;
    }
    echo "   ✅ {$stats['produits']} produits créés\n\n";
    
    // ==========================================
    // ACHATS
    // ==========================================
    echo "🛒 Génération achats...\n";
    
    for ($i = 0; $i < 15; $i++) {
        $dateAchat = date('Y-m-d', strtotime($PERIODE_DEBUT . ' +' . rand(0, 50) . ' days'));
        $numero = 'ACH-' . date('Ymd', strtotime($dateAchat)) . '-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT);
        
        // Récupérer un fournisseur aléatoire
        $stmt = $pdo->prepare("SELECT nom, contact FROM fournisseurs WHERE id = ?");
        $stmt->execute([$fournisseursIds[array_rand($fournisseursIds)]]);
        $fournisseur = $stmt->fetch();
        
        $stmt = $pdo->prepare("
            INSERT INTO achats 
            (numero, date_achat, fournisseur_nom, fournisseur_contact, 
             montant_total_ht, montant_total_ttc, statut, utilisateur_id)
            VALUES (?, ?, ?, ?, 0, 0, 'VALIDE', 1)
        ");
        $stmt->execute([$numero, $dateAchat, $fournisseur['nom'], $fournisseur['contact']]);
        $achatId = $pdo->lastInsertId();
        
        // Lignes
        $nbLignes = rand(2, 5);
        $montantTotal = 0;
        
        for ($j = 0; $j < $nbLignes; $j++) {
            $prodId = $produitsIds[array_rand($produitsIds)];
            $stmt = $pdo->prepare("SELECT prix_achat FROM produits WHERE id = ?");
            $stmt->execute([$prodId]);
            $prixAchat = $stmt->fetchColumn();
            
            $qte = rand(10, 100);
            $montant = $prixAchat * $qte;
            $montantTotal += $montant;
            
            $stmt = $pdo->prepare("
                INSERT INTO achats_lignes (achat_id, produit_id, quantite, prix_unitaire, montant_ligne_ht)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$achatId, $prodId, $qte, $prixAchat, $montant]);
            
            // Stock
            ajouterMouvementStock($pdo, $prodId, 'ENTREE_ACHAT', $qte, "Achat $numero", 1, $achatId);
            $stats['mouvements_stock']++;
        }
        
        // Mise à jour montant (sans tva car pas dans la structure)
        $stmt = $pdo->prepare("UPDATE achats SET montant_total_ht = ?, montant_total_ttc = ? WHERE id = ?");
        $stmt->execute([$montantTotal, $montantTotal, $achatId]);
        $stats['achats']++;
    }
    echo "   ✅ {$stats['achats']} achats créés\n\n";
    
    // ==========================================
    // DEVIS
    // ==========================================
    echo "📄 Génération devis...\n";
    
    $devisIds = [];
    for ($i = 0; $i < 40; $i++) {
        $clientId = $clientsIds[array_rand($clientsIds)];
        $dateDevis = date('Y-m-d', strtotime($PERIODE_DEBUT . ' +' . rand(0, 55) . ' days'));
        $numero = 'DEV-' . date('Ymd', strtotime($dateDevis)) . '-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT);
        
        $rand = rand(1, 100);
        $statut = $rand <= 40 ? 'ACCEPTE' : ($rand <= 70 ? 'EN_ATTENTE' : 'REFUSE');
        
        $stmt = $pdo->prepare("
            INSERT INTO devis 
            (numero, date_devis, client_id, montant_total_ht, tva, montant_total_ttc, statut, commercial_id)
            VALUES (?, ?, ?, 0, 0, 0, ?, 1)
        ");
        $stmt->execute([
            $numero, 
            $dateDevis, 
            $clientId, 
            $statut
        ]);
        $devisId = $pdo->lastInsertId();
        $devisIds[] = ['id' => $devisId, 'statut' => $statut, 'date' => $dateDevis, 'client' => $clientId];
        
        // Lignes
        $nbLignes = rand(2, 6);
        $total = 0;
        
        for ($j = 0; $j < $nbLignes; $j++) {
            $prodId = $produitsIds[array_rand($produitsIds)];
            $stmt = $pdo->prepare("SELECT prix_vente, designation FROM produits WHERE id = ?");
            $stmt->execute([$prodId]);
            $prod = $stmt->fetch();
            
            $qte = rand(1, 20);
            $prix = $prod['prix_vente'];
            $remise = rand(0, 1) ? rand(0, 10) : 0;
            $montant = $qte * $prix * (1 - $remise / 100);
            $total += $montant;
            
            $stmt = $pdo->prepare("
                INSERT INTO devis_lignes 
                (devis_id, produit_id, designation, quantite, prix_unitaire, remise, montant_ligne_ht)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$devisId, $prodId, $prod['designation'], $qte, $prix, $remise, $montant]);
        }
        
        $tva = $total * 0.1925;
        $stmt = $pdo->prepare("UPDATE devis SET montant_total_ht = ?, tva = ?, montant_total_ttc = ? WHERE id = ?");
        $stmt->execute([$total, $tva, $total + $tva, $devisId]);
        $stats['devis']++;
    }
    echo "   ✅ {$stats['devis']} devis créés\n\n";
    
    // ==========================================
    // VENTES
    // ==========================================
    echo "💰 Génération ventes...\n";
    
    $ventesIds = [];
    
    // Ventes issues de devis acceptés
    $devisAcceptes = array_filter($devisIds, fn($d) => $d['statut'] === 'ACCEPTE');
    foreach ($devisAcceptes as $devis) {
        $dateVente = date('Y-m-d', strtotime($devis['date'] . ' +' . rand(1, 10) . ' days'));
        $numero = 'VTE-' . date('Ymd', strtotime($dateVente)) . '-' . str_pad(count($ventesIds) + 1, 4, '0', STR_PAD_LEFT);
        
        $stmt = $pdo->prepare("SELECT * FROM devis WHERE id = ?");
        $stmt->execute([$devis['id']]);
        $devisData = $stmt->fetch();
        
        $stmt = $pdo->prepare("
            INSERT INTO ventes 
            (numero, date_vente, client_id, canal_vente_id, devis_id, 
             montant_total_ht, tva, montant_total_ttc, statut, commercial_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'EN_ATTENTE_LIVRAISON', 1)
        ");
        $stmt->execute([
            $numero, $dateVente, $devisData['client_id'], $canaux['SHOWROOM'], $devis['id'],
            $devisData['montant_total_ht'], $devisData['tva'], $devisData['montant_total_ttc']
        ]);
        $venteId = $pdo->lastInsertId();
        $ventesIds[] = ['id' => $venteId, 'date' => $dateVente, 'client' => $devisData['client_id'], 'montant' => $devisData['montant_total_ttc']];
        
        // Copier lignes
        $stmt = $pdo->prepare("SELECT * FROM devis_lignes WHERE devis_id = ?");
        $stmt->execute([$devis['id']]);
        foreach ($stmt->fetchAll() as $ligne) {
            $pdo->prepare("
                INSERT INTO ventes_lignes 
                (vente_id, produit_id, designation, quantite, prix_unitaire, remise, montant_ligne_ht)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ")->execute([
                $venteId, $ligne['produit_id'], $ligne['designation'], 
                $ligne['quantite'], $ligne['prix_unitaire'], $ligne['remise'], $ligne['montant_ligne_ht']
            ]);
        }
        $stats['ventes']++;
    }
    
    // Ventes directes
    $nbDirectes = 60 - count($ventesIds);
    for ($i = 0; $i < $nbDirectes; $i++) {
        $clientId = $clientsIds[array_rand($clientsIds)];
        $dateVente = date('Y-m-d', strtotime($PERIODE_DEBUT . ' +' . rand(0, 60) . ' days'));
        $numero = 'VTE-' . date('Ymd', strtotime($dateVente)) . '-' . str_pad(count($ventesIds) + 1, 4, '0', STR_PAD_LEFT);
        
        $canalKeys = array_keys($canaux);
        $canalId = $canaux[$canalKeys[array_rand($canalKeys)]];
        
        $stmt = $pdo->prepare("
            INSERT INTO ventes 
            (numero, date_vente, client_id, canal_vente_id, 
             montant_total_ht, tva, montant_total_ttc, statut, commercial_id)
            VALUES (?, ?, ?, ?, 0, 0, 0, 'EN_ATTENTE_LIVRAISON', 1)
        ");
        $stmt->execute([$numero, $dateVente, $clientId, $canalId]);
        $venteId = $pdo->lastInsertId();
        
        // Lignes
        $nbLignes = rand(1, 5);
        $total = 0;
        
        for ($j = 0; $j < $nbLignes; $j++) {
            $prodId = $produitsIds[array_rand($produitsIds)];
            $stmt = $pdo->prepare("SELECT prix_vente, designation FROM produits WHERE id = ?");
            $stmt->execute([$prodId]);
            $prod = $stmt->fetch();
            
            $qte = rand(1, 15);
            $prix = $prod['prix_vente'];
            $remise = rand(0, 1) ? rand(0, 5) : 0;
            $montant = $qte * $prix * (1 - $remise / 100);
            $total += $montant;
            
            $stmt = $pdo->prepare("
                INSERT INTO ventes_lignes 
                (vente_id, produit_id, designation, quantite, prix_unitaire, remise, montant_ligne_ht)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$venteId, $prodId, $prod['designation'], $qte, $prix, $remise, $montant]);
        }
        
        $tva = $total * 0.1925;
        $stmt = $pdo->prepare("UPDATE ventes SET montant_total_ht = ?, tva = ?, montant_total_ttc = ? WHERE id = ?");
        $stmt->execute([$total, $tva, $total + $tva, $venteId]);
        
        $ventesIds[] = ['id' => $venteId, 'date' => $dateVente, 'client' => $clientId, 'montant' => $total + $tva];
        $stats['ventes']++;
    }
    echo "   ✅ {$stats['ventes']} ventes créées\n\n";
    
    // ==========================================
    // ORDRES & LIVRAISONS
    // ==========================================
    echo "📦 Génération ordres et livraisons...\n";
    
    foreach ($ventesIds as $vente) {
        if (rand(1, 100) <= 80) {
            $dateOrdre = date('Y-m-d', strtotime($vente['date'] . ' +' . rand(0, 3) . ' days'));
            $numero = 'OP-' . date('Ymd', strtotime($dateOrdre)) . '-' . str_pad($stats['ordres'] + 1, 4, '0', STR_PAD_LEFT);
            
            $stmt = $pdo->prepare("
                INSERT INTO ordres_preparation 
                (numero_ordre, date_ordre, vente_id, client_id, commercial_responsable_id, 
                 priorite, statut, magasinier_id, date_creation)
                VALUES (?, ?, ?, ?, 1, 'NORMALE', 'PRET', 1, ?)
            ");
            $stmt->execute([$numero, $dateOrdre, $vente['id'], $vente['client'], $dateOrdre]);
            $ordreId = $pdo->lastInsertId();
            $stats['ordres']++;
            
            // Livraison (90% livrés)
            if (rand(1, 100) <= 90) {
                $dateBL = date('Y-m-d', strtotime($dateOrdre . ' +' . rand(1, 5) . ' days'));
                $numeroBL = 'BL-' . date('Ymd', strtotime($dateBL)) . '-' . str_pad($stats['livraisons'] + 1, 4, '0', STR_PAD_LEFT);
                
                $stmt = $pdo->prepare("
                    INSERT INTO bons_livraison 
                    (numero, date_bl, vente_id, ordre_preparation_id, client_id, 
                     magasinier_id, livreur_id, statut, signe_client)
                    VALUES (?, ?, ?, ?, ?, 1, 1, 'LIVRE', 1)
                ");
                $stmt->execute([$numeroBL, $dateBL, $vente['id'], $ordreId, $vente['client']]);
                $blId = $pdo->lastInsertId();
                
                // Lignes BL
                $stmt = $pdo->prepare("SELECT * FROM ventes_lignes WHERE vente_id = ?");
                $stmt->execute([$vente['id']]);
                $lignesVente = $stmt->fetchAll();
                
                $partielle = rand(1, 100) <= 30;
                
                foreach ($lignesVente as $ligne) {
                    $qteLivree = $partielle && rand(1, 100) <= 50
                        ? round($ligne['quantite'] * (rand(50, 80) / 100))
                        : $ligne['quantite'];
                    
                    $pdo->prepare("
                        INSERT INTO bons_livraison_lignes 
                        (bon_livraison_id, produit_id, designation, quantite, 
                         quantite_commandee, quantite_restante, prix_unitaire)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ")->execute([
                        $blId, $ligne['produit_id'], $ligne['designation'], $qteLivree,
                        $ligne['quantite'], $ligne['quantite'] - $qteLivree, $ligne['prix_unitaire']
                    ]);
                    
                    // Stock
                    ajouterMouvementStock($pdo, $ligne['produit_id'], 'SORTIE_VENTE', -$qteLivree, "BL $numeroBL", 1, null, $blId);
                    $stats['mouvements_stock']++;
                }
                
                // Statut vente
                $stmt = $pdo->prepare("SELECT SUM(quantite_restante) FROM bons_livraison_lignes WHERE bon_livraison_id = ?");
                $stmt->execute([$blId]);
                $reste = $stmt->fetchColumn();
                
                $statut = $reste > 0 ? 'PARTIELLEMENT_LIVREE' : 'LIVREE';
                $pdo->prepare("UPDATE ventes SET statut = ? WHERE id = ?")->execute([$statut, $vente['id']]);
                
                $stats['livraisons']++;
            }
        }
    }
    echo "   ✅ {$stats['ordres']} ordres, {$stats['livraisons']} livraisons\n\n";
    
    // ==========================================
    // ENCAISSEMENTS
    // ==========================================
    echo "💵 Génération encaissements...\n";
    
    $stmt = $pdo->query("SELECT id, montant_total_ttc, date_vente FROM ventes WHERE statut IN ('LIVREE', 'PARTIELLEMENT_LIVREE')");
    $ventesLivrees = $stmt->fetchAll();
    
    foreach ($ventesLivrees as $vente) {
        if (rand(1, 100) <= 70) {
            $dateEnc = date('Y-m-d', strtotime($vente['date_vente'] . ' +' . rand(1, 7) . ' days'));
            $modes = ['ESPECES', 'MOBILE_MONEY', 'VIREMENT', 'CHEQUE'];
            
            enregistrerEncaissement(
                $pdo,
                $dateEnc,
                $vente['montant_total_ttc'],
                $modes[array_rand($modes)],
                "Encaissement vente",
                1,
                $vente['id']
            );
            $stats['encaissements']++;
        }
    }
    echo "   ✅ {$stats['encaissements']} encaissements\n\n";
    
    // ==========================================
    // VALIDATION
    // ==========================================
    echo "=== VALIDATION ===\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM produits WHERE stock_actuel < 0");
    $nbNegatif = $stmt->fetchColumn();
    
    if ($nbNegatif > 0) {
        echo "⚠️  $nbNegatif produits en stock négatif!\n";
    } else {
        echo "✅ Tous les stocks sont positifs\n";
    }
    
    echo "\n📊 RÉSUMÉ:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    foreach ($stats as $key => $val) {
        printf("%-20s: %d\n", ucfirst(str_replace('_', ' ', $key)), $val);
    }
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    $pdo->commit();
    echo "\n✅ GÉNÉRATION TERMINÉE AVEC SUCCÈS\n";
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "\n❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Ligne: " . $e->getLine() . "\n";
    exit(1);
}

echo "\n=== DONNÉES DÉMO PRÊTES ===\n";

