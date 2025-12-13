<?php
/**
 * Générateur de données cohérentes et réalistes pour KMS Gestion
 * 
 * Génère des données interconnectées pour tous les modules :
 * - Clients & Prospects (showroom, terrain, digital, hôtel, formation)
 * - Produits & Stock
 * - Devis & Ventes
 * - Ordres de préparation & Livraisons
 * - Litiges
 * - Caisse & Trésorerie
 * - Achats & Fournisseurs
 * - Comptabilité (SYSCOHADA)
 * 
 * Date: 2025-12-13
 */

require_once __DIR__ . '/db/db.php';
require_once __DIR__ . '/lib/stock.php';
require_once __DIR__ . '/lib/caisse.php';
require_once __DIR__ . '/lib/compta.php';

set_time_limit(600); // 10 minutes max
ini_set('memory_limit', '512M');

echo "=== GÉNÉRATION DE DONNÉES COHÉRENTES POUR KMS GESTION ===\n\n";

// Configuration
$PERIODE_DEBUT = date('Y-m-d', strtotime('-60 days')); // 2 mois d'historique
$PERIODE_FIN = date('Y-m-d');
$NB_CLIENTS = 50;
$NB_PRODUITS = 30;
$NB_DEVIS = 40;
$NB_VENTES = 60;
$NB_ACHATS = 15;

$stats = [
    'clients' => 0,
    'produits' => 0,
    'fournisseurs' => 0,
    'devis' => 0,
    'ventes' => 0,
    'ordres_preparation' => 0,
    'livraisons' => 0,
    'mouvements_stock' => 0,
    'encaissements' => 0,
    'achats' => 0,
    'ecritures_compta' => 0,
    'erreurs' => 0
];

try {
    $pdo->beginTransaction();
    
    // ========================================
    // 1. TYPES DE CLIENTS
    // ========================================
    echo "📋 Vérification types de clients...\n";
    $typesClient = [
        ['code' => 'PARTICULIER', 'libelle' => 'Particulier'],
        ['code' => 'ENTREPRISE', 'libelle' => 'Entreprise'],
        ['code' => 'REVENDEUR', 'libelle' => 'Revendeur'],
        ['code' => 'VIP', 'libelle' => 'Client VIP']
    ];
    
    foreach ($typesClient as $type) {
        $pdo->prepare("INSERT IGNORE INTO types_client (code, libelle) VALUES (?, ?)")
            ->execute([$type['code'], $type['libelle']]);
    }
    
    // ========================================
    // 2. CANAUX DE VENTE (déjà existants)
    // ========================================
    echo "📋 Récupération canaux de vente...\n";
    
    // Récupérer les IDs des canaux existants
    $stmt = $pdo->query("SELECT id, code FROM canaux_vente");
    $canauxIds = [];
    while ($row = $stmt->fetch()) {
        $canauxIds[$row['code']] = $row['id'];
    }
    
    // ========================================
    // 3. CLIENTS RÉALISTES
    // ========================================
    echo "\n👥 Génération de $NB_CLIENTS clients...\n";
    
    $nomsCI = ['Kouassi', 'Koné', 'Bamba', 'Touré', 'Yao', 'N\'Guessan', 'Ouattara', 'Coulibaly', 'Traoré', 'Diallo'];
    $prenoms = ['Jean', 'Marie', 'Ibrahim', 'Fatou', 'Aya', 'Mamadou', 'Aminata', 'Kouadio', 'Adjoua', 'Moussa'];
    $villes = ['Abidjan', 'Bouaké', 'Yamoussoukro', 'Daloa', 'San-Pedro', 'Korhogo'];
    $quartiers = ['Cocody', 'Yopougon', 'Marcory', 'Plateau', 'Abobo', 'Adjamé', 'Treichville'];
    
    $clientsIds = [];
    for ($i = 1; $i <= $NB_CLIENTS; $i++) {
        $nom = $nomsCI[array_rand($nomsCI)] . ' ' . $prenoms[array_rand($prenoms)];
        $telephone = '0' . rand(1, 7) . sprintf('%08d', rand(0, 99999999));
        $email = strtolower(str_replace(' ', '.', $nom)) . '@email.ci';
        $ville = $villes[array_rand($villes)];
        $quartier = $quartiers[array_rand($quartiers)];
        $adresse = 'Lot ' . rand(1, 500) . ' ' . $quartier . ', ' . $ville;
        $typeId = rand(1, 4);
        
        $stmt = $pdo->prepare("
            INSERT INTO clients (nom, telephone, email, adresse, ville, type_client_id, date_creation)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$nom, $telephone, $email, $adresse, $ville, $typeId, date('Y-m-d H:i:s', strtotime("-" . rand(1, 180) . " days"))]);
        $clientsIds[] = $pdo->lastInsertId();
        $stats['clients']++;
    }
    
    echo "✅ {$stats['clients']} clients créés\n";
    
    // ========================================
    // 4. PROSPECTS & LEADS
    // ========================================
    echo "\n🎯 Génération prospects et leads...\n";
    
    // Prospects terrain
    $nbProspects = 20;
    for ($i = 0; $i < $nbProspects; $i++) {
        $dateProsp = date('Y-m-d', strtotime($PERIODE_DEBUT . ' +' . rand(0, 60) . ' days'));
        $stmt = $pdo->prepare("
            INSERT INTO prospections_terrain 
            (date_prospection, heure_prospection, prospect_nom, secteur, besoin_identifie, 
             action_menee, resultat, commercial_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, 1)
        ");
        $stmt->execute([
            $dateProsp,
            sprintf('%02d:%02d:00', rand(8, 18), rand(0, 59)),
            $nomsCI[array_rand($nomsCI)] . ' ' . $prenoms[array_rand($prenoms)],
            $quartiers[array_rand($quartiers)],
            'Besoin en matériel ' . ['électrique', 'sanitaire', 'peinture', 'quincaillerie'][rand(0, 3)],
            'Présentation catalogue, prise de mesures',
            ['Intéressé - À recontacter', 'Devis demandé', 'Pas intéressé', 'À rappeler plus tard'][rand(0, 3)],
        ]);
    }
    
    // Leads digitaux
    $nbLeads = 15;
    for ($i = 0; $i < $nbLeads; $i++) {
        $dateLead = date('Y-m-d', strtotime($PERIODE_DEBUT . ' +' . rand(0, 60) . ' days'));
        $stmt = $pdo->prepare("
            INSERT INTO leads_digital 
            (date_lead, nom_complet, telephone, source, statut, produit_interet)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $dateLead,
            $nomsCI[array_rand($nomsCI)] . ' ' . $prenoms[array_rand($prenoms)],
            '0' . rand(1, 7) . sprintf('%08d', rand(0, 99999999)),
            ['Facebook', 'Google', 'Site web', 'Instagram'][rand(0, 3)],
            ['NOUVEAU', 'CONTACTE', 'QUALIFIE', 'CONVERTI'][rand(0, 3)],
            ['Câbles électriques', 'Peinture', 'Carrelage', 'Sanitaires'][rand(0, 3)]
        ]);
    }
    
    echo "✅ Prospects et leads générés\n";
    
    // ========================================
    // 5. FOURNISSEURS
    // ========================================
    echo "\n🏭 Création fournisseurs...\n";
    
    $fournisseurs = [
        ['SOCOCE', 'Société Commerciale de Côte d\'Ivoire', '2523456789', 'contact@sococe.ci'],
        ['PROSUMA', 'Produits Industriels et Matériaux', '2524567890', 'info@prosuma.ci'],
        ['ELECTRA CI', 'Électricité et Matériel CI', '2525678901', 'ventes@electra.ci'],
        ['BATIMAT', 'Matériaux de Construction', '2526789012', 'commandes@batimat.ci'],
        ['QUINCA PLUS', 'Quincaillerie Générale', '2527890123', 'contact@quincaplus.ci']
    ];
    
    $fournisseursIds = [];
    foreach ($fournisseurs as $f) {
        $stmt = $pdo->prepare("
            INSERT INTO fournisseurs (nom, contact, telephone, email)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute($f);
        $fournisseursIds[] = $pdo->lastInsertId();
        $stats['fournisseurs']++;
    }
    
    echo "✅ {$stats['fournisseurs']} fournisseurs créés\n";
    
    // ========================================
    // 6. FAMILLES & PRODUITS
    // ========================================
    echo "\n📦 Création familles et produits...\n";
    
    // Familles
    $familles = [
        ['ELEC', 'Électricité'],
        ['PLOMB', 'Plomberie'],
        ['PEINTURE', 'Peinture'],
        ['QUINCA', 'Quincaillerie'],
        ['CONSTR', 'Construction']
    ];
    
    $famillesIds = [];
    foreach ($familles as $f) {
        $stmt = $pdo->prepare("INSERT INTO familles_produits (code, libelle) VALUES (?, ?)");
        $stmt->execute($f);
        $famillesIds[] = $pdo->lastInsertId();
    }
    
    // Produits réalistes
    $produits = [
        // Électricité
        ['ELEC', 'CBL-25MM', 'Câble électrique 2.5mm² (rouleau 100m)', 45000, 25000, 150, 50],
        ['ELEC', 'DISJ-16A', 'Disjoncteur 16A', 8500, 5000, 200, 40],
        ['ELEC', 'PRISE-DUAL', 'Prise double avec terre', 2500, 1500, 300, 60],
        ['ELEC', 'INTER-VA', 'Interrupteur va-et-vient', 1800, 1000, 250, 50],
        ['ELEC', 'LAMPE-LED', 'Ampoule LED 15W', 3500, 2000, 400, 80],
        ['ELEC', 'TUBE-LED', 'Tube LED 120cm', 7500, 4500, 150, 30],
        
        // Plomberie
        ['PLOMB', 'TUY-PVC-110', 'Tube PVC Ø110mm (3m)', 12000, 7000, 100, 20],
        ['PLOMB', 'ROBINET-LAV', 'Robinet lavabo chromé', 15000, 9000, 80, 15],
        ['PLOMB', 'WC-COMPLET', 'WC complet avec abattant', 85000, 50000, 30, 5],
        ['PLOMB', 'LAVABO-SUSP', 'Lavabo suspendu blanc', 35000, 20000, 25, 5],
        ['PLOMB', 'FLEX-DOUCHE', 'Flexible de douche 1.5m', 4500, 2500, 120, 25],
        
        // Peinture
        ['PEINTURE', 'PEIN-INT-25L', 'Peinture intérieure mate 25L', 35000, 20000, 60, 10],
        ['PEINTURE', 'PEIN-EXT-25L', 'Peinture extérieure 25L', 42000, 25000, 50, 10],
        ['PEINTURE', 'ROUL-230', 'Rouleau peinture 230mm', 3500, 2000, 100, 20],
        ['PEINTURE', 'PINC-SET', 'Set de 5 pinceaux', 8500, 5000, 80, 15],
        
        // Quincaillerie
        ['QUINCA', 'MARTEAU-500', 'Marteau 500g', 6500, 3500, 60, 12],
        ['QUINCA', 'SCIE-METAL', 'Scie à métaux', 8500, 5000, 40, 8],
        ['QUINCA', 'METRE-5M', 'Mètre ruban 5m', 4500, 2500, 100, 20],
        ['QUINCA', 'NIVEAU-80', 'Niveau à bulle 80cm', 12000, 7000, 35, 7],
        ['QUINCA', 'CLEF-SET', 'Set de clés mixtes 12pcs', 25000, 15000, 25, 5],
        
        // Construction
        ['CONSTR', 'CIMENT-50KG', 'Sac ciment 50kg', 5500, 3200, 500, 100],
        ['CONSTR', 'SABLE-M3', 'Sable fin m³', 18000, 12000, 50, 10],
        ['CONSTR', 'GRAVIER-M3', 'Gravier 3/8 m³', 22000, 15000, 40, 8],
        ['CONSTR', 'BRIQUE-U', 'Brique creuse (unité)', 250, 150, 5000, 1000],
        ['CONSTR', 'FER-12MM', 'Fer à béton Ø12mm (barre 12m)', 15000, 9000, 200, 40],
        ['CONSTR', 'CARREAU-40', 'Carreau 40x40cm blanc (m²)', 8500, 5000, 150, 30],
        ['CONSTR', 'COLL-CARR', 'Colle à carrelage 25kg', 6500, 3800, 100, 20],
        ['CONSTR', 'JOINT-CARR', 'Joint carrelage 5kg', 3500, 2000, 80, 15],
        ['CONSTR', 'PORTE-STD', 'Porte isoplane 80x200', 45000, 28000, 20, 4],
        ['CONSTR', 'FENETRE-ALU', 'Fenêtre aluminium 120x100', 65000, 40000, 15, 3]
    ];
    
    $produitsIds = [];
    foreach ($produits as $p) {
        $famCode = $p[0];
        $famId = $famillesIds[array_search($famCode, array_column($familles, 0))];
        
        $stmt = $pdo->prepare("
            INSERT INTO produits 
            (code_produit, designation, prix_vente, prix_achat, stock_actuel, seuil_alerte, famille_id)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $p[1], // code
            $p[2], // designation
            $p[3], // prix vente
            $p[4], // prix achat
            $p[5], // stock initial
            $p[6], // seuil alerte
            $famId
        ]);
        $produitsIds[] = $pdo->lastInsertId();
        $stats['produits']++;
        
        // Mouvement initial de stock (entrée initiale)
        ajouterMouvement(
            $pdo,
            $pdo->lastInsertId(),
            'ENTREE_ACHAT',
            $p[5],
            'Stock initial',
            1
        );
        $stats['mouvements_stock']++;
    }
    
    echo "✅ {$stats['produits']} produits créés avec stock initial\n";
    
    // ========================================
    // 7. ACHATS FOURNISSEURS
    // ========================================
    echo "\n🛒 Génération achats fournisseurs...\n";
    
    for ($i = 0; $i < $NB_ACHATS; $i++) {
        $dateAchat = date('Y-m-d', strtotime($PERIODE_DEBUT . ' +' . rand(0, 50) . ' days'));
        $fournisseurId = $fournisseursIds[array_rand($fournisseursIds)];
        
        // Générer numéro achat
        $numero = 'ACH-' . date('Ymd', strtotime($dateAchat)) . '-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT);
        
        // Créer l'achat
        $stmt = $pdo->prepare("
            INSERT INTO achats 
            (numero, date_achat, fournisseur_id, montant_total_ht, tva, montant_total_ttc, statut)
            VALUES (?, ?, ?, 0, 0, 0, 'VALIDE')
        ");
        $stmt->execute([$numero, $dateAchat, $fournisseurId]);
        $achatId = $pdo->lastInsertId();
        
        // Ajouter des lignes d'achat (2-5 produits)
        $nbLignes = rand(2, 5);
        $montantTotal = 0;
        
        for ($j = 0; $j < $nbLignes; $j++) {
            $produitId = $produitsIds[array_rand($produitsIds)];
            
            // Récupérer le prix d'achat du produit
            $stmt = $pdo->prepare("SELECT prix_achat FROM produits WHERE id = ?");
            $stmt->execute([$produitId]);
            $prixAchat = $stmt->fetchColumn();
            
            $quantite = rand(10, 100);
            $montantLigne = $prixAchat * $quantite;
            $montantTotal += $montantLigne;
            
            $stmt = $pdo->prepare("
                INSERT INTO achats_lignes 
                (achat_id, produit_id, quantite, prix_unitaire, montant_ligne)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$achatId, $produitId, $quantite, $prixAchat, $montantLigne]);
            
            // Ajouter au stock
            ajouterMouvement(
                $pdo,
                $produitId,
                'ENTREE_ACHAT',
                $quantite,
                "Achat $numero",
                1,
                $achatId
            );
            $stats['mouvements_stock']++;
        }
        
        // Mettre à jour le montant total de l'achat
        $tva = $montantTotal * 0.18;
        $montantTTC = $montantTotal + $tva;
        
        $stmt = $pdo->prepare("
            UPDATE achats 
            SET montant_total_ht = ?, tva = ?, montant_total_ttc = ?
            WHERE id = ?
        ");
        $stmt->execute([$montantTotal, $tva, $montantTTC, $achatId]);
        
        $stats['achats']++;
    }
    
    echo "✅ {$stats['achats']} achats créés avec impact stock\n";
    
    // ========================================
    // 8. DEVIS
    // ========================================
    echo "\n📄 Génération devis...\n";
    
    $devisIds = [];
    for ($i = 0; $i < $NB_DEVIS; $i++) {
        $clientId = $clientsIds[array_rand($clientsIds)];
        $dateDevis = date('Y-m-d', strtotime($PERIODE_DEBUT . ' +' . rand(0, 55) . ' days'));
        $numero = 'DEV-' . date('Ymd', strtotime($dateDevis)) . '-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT);
        
        // Statut aléatoire avec probabilités réalistes
        $rand = rand(1, 100);
        if ($rand <= 40) {
            $statut = 'ACCEPTE';
        } elseif ($rand <= 70) {
            $statut = 'EN_ATTENTE';
        } else {
            $statut = 'REFUSE';
        }
        
        $dateValidite = date('Y-m-d', strtotime($dateDevis . ' +30 days'));
        
        $stmt = $pdo->prepare("
            INSERT INTO devis 
            (numero, date_devis, date_validite, client_id, montant_ht, tva, montant_ttc, statut, commercial_id)
            VALUES (?, ?, ?, ?, 0, 0, 0, ?, 1)
        ");
        $stmt->execute([$numero, $dateDevis, $dateValidite, $clientId, $statut]);
        $devisId = $pdo->lastInsertId();
        $devisIds[] = ['id' => $devisId, 'statut' => $statut, 'date' => $dateDevis, 'client_id' => $clientId];
        
        // Lignes devis (2-6 produits)
        $nbLignes = rand(2, 6);
        $montantTotal = 0;
        
        for ($j = 0; $j < $nbLignes; $j++) {
            $produitId = $produitsIds[array_rand($produitsIds)];
            
            $stmt = $pdo->prepare("SELECT prix_vente, designation FROM produits WHERE id = ?");
            $stmt->execute([$produitId]);
            $produit = $stmt->fetch();
            
            $quantite = rand(1, 20);
            $prixUnitaire = $produit['prix_vente'];
            $remise = rand(0, 1) ? rand(0, 10) : 0;
            $montantLigne = $quantite * $prixUnitaire * (1 - $remise / 100);
            $montantTotal += $montantLigne;
            
            $stmt = $pdo->prepare("
                INSERT INTO devis_lignes 
                (devis_id, produit_id, designation, quantite, prix_unitaire, remise, montant_ligne_ht)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$devisId, $produitId, $produit['designation'], $quantite, $prixUnitaire, $remise, $montantLigne]);
        }
        
        // Mise à jour montant total devis
        $tva = $montantTotal * 0.1925;
        $montantTTC = $montantTotal + $tva;
        
        $stmt = $pdo->prepare("UPDATE devis SET montant_ht = ?, tva = ?, montant_ttc = ? WHERE id = ?");
        $stmt->execute([$montantTotal, $tva, $montantTTC, $devisId]);
        
        $stats['devis']++;
    }
    
    echo "✅ {$stats['devis']} devis créés\n";
    
    // ========================================
    // 9. VENTES (issues de devis acceptés + ventes directes)
    // ========================================
    echo "\n💰 Génération ventes...\n";
    
    $ventesIds = [];
    
    // Ventes issues de devis acceptés
    $devisAcceptes = array_filter($devisIds, fn($d) => $d['statut'] === 'ACCEPTE');
    foreach ($devisAcceptes as $devis) {
        // Convertir le devis en vente (quelques jours après)
        $dateVente = date('Y-m-d', strtotime($devis['date'] . ' +' . rand(1, 10) . ' days'));
        $numero = 'VTE-' . date('Ymd', strtotime($dateVente)) . '-' . str_pad(count($ventesIds) + 1, 4, '0', STR_PAD_LEFT);
        
        // Récupérer les infos du devis
        $stmt = $pdo->prepare("SELECT * FROM devis WHERE id = ?");
        $stmt->execute([$devis['id']]);
        $devisData = $stmt->fetch();
        
        $canalId = $canauxIds['SHOWROOM'];
        
        $stmt = $pdo->prepare("
            INSERT INTO ventes 
            (numero, date_vente, client_id, canal_vente_id, devis_id, 
             montant_total_ht, tva, montant_total_ttc, statut, commercial_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'EN_ATTENTE_LIVRAISON', 1)
        ");
        $stmt->execute([
            $numero, $dateVente, $devisData['client_id'], $canalId, $devis['id'],
            $devisData['montant_ht'], $devisData['tva'], $devisData['montant_ttc']
        ]);
        $venteId = $pdo->lastInsertId();
        $ventesIds[] = [
            'id' => $venteId, 
            'date' => $dateVente, 
            'client_id' => $devisData['client_id'],
            'montant_ttc' => $devisData['montant_ttc']
        ];
        
        // Copier les lignes du devis vers la vente
        $stmt = $pdo->prepare("SELECT * FROM devis_lignes WHERE devis_id = ?");
        $stmt->execute([$devis['id']]);
        $lignesDevis = $stmt->fetchAll();
        
        foreach ($lignesDevis as $ligne) {
            $stmt = $pdo->prepare("
                INSERT INTO ventes_lignes 
                (vente_id, produit_id, designation, quantite, prix_unitaire, remise, montant_ligne_ht)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $venteId, $ligne['produit_id'], $ligne['designation'], 
                $ligne['quantite'], $ligne['prix_unitaire'], $ligne['remise'], $ligne['montant_ligne_ht']
            ]);
        }
        
        $stats['ventes']++;
    }
    
    // Ventes directes (sans devis)
    $nbVentesDirectes = $NB_VENTES - count($ventesIds);
    for ($i = 0; $i < $nbVentesDirectes; $i++) {
        $clientId = $clientsIds[array_rand($clientsIds)];
        $dateVente = date('Y-m-d', strtotime($PERIODE_DEBUT . ' +' . rand(0, 60) . ' days'));
        $numero = 'VTE-' . date('Ymd', strtotime($dateVente)) . '-' . str_pad(count($ventesIds) + 1, 4, '0', STR_PAD_LEFT);
        
        $canalId = $canauxIds[['SHOWROOM', 'TERRAIN', 'DIGITAL'][rand(0, 2)]];
        
        $stmt = $pdo->prepare("
            INSERT INTO ventes 
            (numero, date_vente, client_id, canal_vente_id, devis_id, 
             montant_total_ht, tva, montant_total_ttc, statut, commercial_id)
            VALUES (?, ?, ?, ?, NULL, 0, 0, 0, 'EN_ATTENTE_LIVRAISON', 1)
        ");
        $stmt->execute([$numero, $dateVente, $clientId, $canalId]);
        $venteId = $pdo->lastInsertId();
        
        // Lignes de vente (1-5 produits)
        $nbLignes = rand(1, 5);
        $montantTotal = 0;
        
        for ($j = 0; $j < $nbLignes; $j++) {
            $produitId = $produitsIds[array_rand($produitsIds)];
            
            $stmt = $pdo->prepare("SELECT prix_vente, designation FROM produits WHERE id = ?");
            $stmt->execute([$produitId]);
            $produit = $stmt->fetch();
            
            $quantite = rand(1, 15);
            $prixUnitaire = $produit['prix_vente'];
            $remise = rand(0, 1) ? rand(0, 5) : 0;
            $montantLigne = $quantite * $prixUnitaire * (1 - $remise / 100);
            $montantTotal += $montantLigne;
            
            $stmt = $pdo->prepare("
                INSERT INTO ventes_lignes 
                (vente_id, produit_id, designation, quantite, prix_unitaire, remise, montant_ligne_ht)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$venteId, $produitId, $produit['designation'], $quantite, $prixUnitaire, $remise, $montantLigne]);
        }
        
        // Mise à jour montant total vente
        $tva = $montantTotal * 0.1925;
        $montantTTC = $montantTotal + $tva;
        
        $stmt = $pdo->prepare("UPDATE ventes SET montant_total_ht = ?, tva = ?, montant_total_ttc = ? WHERE id = ?");
        $stmt->execute([$montantTotal, $tva, $montantTTC, $venteId]);
        
        $ventesIds[] = [
            'id' => $venteId,
            'date' => $dateVente,
            'client_id' => $clientId,
            'montant_ttc' => $montantTTC
        ];
        $stats['ventes']++;
    }
    
    echo "✅ {$stats['ventes']} ventes créées\n";
    
    // ========================================
    // 10. ORDRES DE PRÉPARATION & LIVRAISONS
    // ========================================
    echo "\n📦 Génération ordres de préparation et livraisons...\n";
    
    foreach ($ventesIds as $vente) {
        // 80% des ventes ont un ordre de préparation
        if (rand(1, 100) <= 80) {
            $dateOrdre = date('Y-m-d', strtotime($vente['date'] . ' +' . rand(0, 3) . ' days'));
            $numero = 'OP-' . date('Ymd', strtotime($dateOrdre)) . '-' . str_pad($stats['ordres_preparation'] + 1, 4, '0', STR_PAD_LEFT);
            
            $stmt = $pdo->prepare("
                INSERT INTO ordres_preparation 
                (numero_ordre, date_ordre, vente_id, client_id, commercial_responsable_id, 
                 priorite, statut, magasinier_id, date_creation)
                VALUES (?, ?, ?, ?, 1, 'NORMALE', 'PRET', 1, ?)
            ");
            $stmt->execute([$numero, $dateOrdre, $vente['id'], $vente['client_id'], $dateOrdre]);
            $ordreId = $pdo->lastInsertId();
            $stats['ordres_preparation']++;
            
            // Créer une livraison (70% complète, 30% partielle)
            if (rand(1, 100) <= 90) { // 90% des ordres sont livrés
                $dateLivraison = date('Y-m-d', strtotime($dateOrdre . ' +' . rand(1, 5) . ' days'));
                $numeroBL = 'BL-' . date('Ymd', strtotime($dateLivraison)) . '-' . str_pad($stats['livraisons'] + 1, 4, '0', STR_PAD_LEFT);
                
                $stmt = $pdo->prepare("
                    INSERT INTO bons_livraison 
                    (numero, date_bl, vente_id, ordre_preparation_id, client_id, 
                     magasinier_id, livreur_id, statut, signe_client)
                    VALUES (?, ?, ?, ?, ?, 1, 1, 'LIVRE', 1)
                ");
                $stmt->execute([$numeroBL, $dateLivraison, $vente['id'], $ordreId, $vente['client_id']]);
                $blId = $pdo->lastInsertId();
                
                // Récupérer les lignes de vente
                $stmt = $pdo->prepare("SELECT * FROM ventes_lignes WHERE vente_id = ?");
                $stmt->execute([$vente['id']]);
                $lignesVente = $stmt->fetchAll();
                
                $livraisonPartielle = rand(1, 100) <= 30; // 30% partielles
                
                foreach ($lignesVente as $ligne) {
                    $qteLivree = $livraisonPartielle && rand(1, 100) <= 50 
                        ? round($ligne['quantite'] * (rand(50, 80) / 100)) 
                        : $ligne['quantite'];
                    
                    $qteRestante = $ligne['quantite'] - $qteLivree;
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO bons_livraison_lignes 
                        (bon_livraison_id, produit_id, designation, quantite, 
                         quantite_commandee, quantite_restante, prix_unitaire)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $blId, $ligne['produit_id'], $ligne['designation'], $qteLivree,
                        $ligne['quantite'], $qteRestante, $ligne['prix_unitaire']
                    ]);
                    
                    // Mouvement de stock (sortie)
                    ajouterMouvement(
                        $pdo,
                        $ligne['produit_id'],
                        'SORTIE_VENTE',
                        -$qteLivree,
                        "Livraison $numeroBL",
                        1,
                        null,
                        $blId
                    );
                    $stats['mouvements_stock']++;
                }
                
                // Mettre à jour statut vente
                $stmt = $pdo->prepare("SELECT SUM(quantite_restante) as reste FROM bons_livraison_lignes WHERE bon_livraison_id = ?");
                $stmt->execute([$blId]);
                $reste = $stmt->fetchColumn();
                
                $nouveauStatut = $reste > 0 ? 'PARTIELLEMENT_LIVREE' : 'LIVREE';
                $stmt = $pdo->prepare("UPDATE ventes SET statut = ? WHERE id = ?");
                $stmt->execute([$nouveauStatut, $vente['id']]);
                
                $stats['livraisons']++;
            }
        }
    }
    
    echo "✅ {$stats['ordres_preparation']} ordres créés, {$stats['livraisons']} livraisons effectuées\n";
    
    // ========================================
    // 11. ENCAISSEMENTS CAISSE
    // ========================================
    echo "\n💵 Génération encaissements...\n";
    
    // Encaisser 70% des ventes livrées
    $stmt = $pdo->query("SELECT id, montant_total_ttc, date_vente FROM ventes WHERE statut IN ('LIVREE', 'PARTIELLEMENT_LIVREE')");
    $ventesLivrees = $stmt->fetchAll();
    
    foreach ($ventesLivrees as $vente) {
        if (rand(1, 100) <= 70) {
            $dateEncaissement = date('Y-m-d', strtotime($vente['date_vente'] . ' +' . rand(1, 7) . ' days'));
            
            $modesPaiement = ['ESPECES', 'MOBILE_MONEY', 'VIREMENT', 'CHEQUE'];
            $mode = $modesPaiement[array_rand($modesPaiement)];
            
            enregistrerEncaissement(
                $pdo,
                $dateEncaissement,
                $vente['montant_total_ttc'],
                $mode,
                "Encaissement vente",
                1,
                $vente['id']
            );
            $stats['encaissements']++;
        }
    }
    
    echo "✅ {$stats['encaissements']} encaissements enregistrés\n";
    
    // ========================================
    // 12. LITIGES (quelques-uns)
    // ========================================
    echo "\n⚠️ Génération litiges...\n";
    
    $nbLitiges = 5;
    $ventesAvecLivraison = $pdo->query("
        SELECT DISTINCT v.id, v.numero, v.client_id, bl.id as bl_id 
        FROM ventes v 
        JOIN bons_livraison bl ON bl.vente_id = v.id 
        LIMIT 10
    ")->fetchAll();
    
    for ($i = 0; $i < min($nbLitiges, count($ventesAvecLivraison)); $i++) {
        $vente = $ventesAvecLivraison[$i];
        
        $stmt = $pdo->prepare("
            INSERT INTO retours_litiges 
            (date_declaration, type_probleme, vente_id, client_id, 
             description_probleme, statut_traitement, declare_par)
            VALUES (?, ?, ?, ?, ?, 'EN_COURS', 1)
        ");
        $stmt->execute([
            date('Y-m-d', strtotime('-' . rand(1, 15) . ' days')),
            ['PRODUIT_DEFECTUEUX', 'ERREUR_LIVRAISON', 'QUANTITE_INCORRECTE'][rand(0, 2)],
            $vente['id'],
            $vente['client_id'],
            'Problème signalé par le client sur vente ' . $vente['numero']
        ]);
    }
    
    echo "✅ Litiges générés\n";
    
    // ========================================
    // VALIDATION & STATISTIQUES
    // ========================================
    echo "\n=== VALIDATION & STATISTIQUES ===\n\n";
    
    // Vérifier cohérence stock
    $stmt = $pdo->query("
        SELECT COUNT(*) as nb_incoherences 
        FROM produits 
        WHERE stock_actuel < 0
    ");
    $stockNegatif = $stmt->fetchColumn();
    
    if ($stockNegatif > 0) {
        echo "⚠️  ATTENTION: $stockNegatif produits avec stock négatif détectés\n";
        $stats['erreurs'] += $stockNegatif;
    } else {
        echo "✅ Tous les stocks sont cohérents (aucun stock négatif)\n";
    }
    
    // Vérifier écritures comptables
    $stmt = $pdo->query("SELECT COUNT(*) FROM compta_ecritures");
    $nbEcritures = $stmt->fetchColumn();
    $stats['ecritures_compta'] = $nbEcritures;
    
    echo "\n📊 RÉSUMÉ DE GÉNÉRATION:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    foreach ($stats as $key => $value) {
        $label = str_replace('_', ' ', ucfirst($key));
        echo sprintf("%-25s: %d\n", $label, $value);
    }
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    if ($stats['erreurs'] > 0) {
        echo "\n⚠️  ATTENTION: {$stats['erreurs']} anomalies détectées\n";
        echo "Vérifiez les logs pour plus de détails\n";
    } else {
        echo "\n✅ GÉNÉRATION TERMINÉE AVEC SUCCÈS\n";
        echo "Aucune anomalie détectée\n";
    }
    
    $pdo->commit();
    echo "\n✅ Transaction validée (COMMIT)\n";
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "\n❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Transaction annulée (ROLLBACK)\n";
    exit(1);
}

echo "\n=== FIN DE GÉNÉRATION ===\n";
echo "La base de données contient maintenant des données cohérentes et réalistes\n";
echo "pour tester tous les workflows de KMS Gestion.\n\n";
echo "Prochaines étapes recommandées:\n";
echo "1. Vérifier les modules dans l'application web\n";
echo "2. Tester la navigation entre modules\n";
echo "3. Vérifier la cohérence comptable (balance, grand livre)\n";
echo "4. Analyser les dashboards et rapports\n";
