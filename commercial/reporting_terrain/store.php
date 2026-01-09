<?php
/**
 * Reporting Terrain - Traitement du formulaire de création
 * Module: commercial/reporting_terrain/store.php
 * Insère les données dans les 7 tables avec transaction
 */

require_once __DIR__ . '/../../security.php';
exigerConnexion();

global $pdo;
$utilisateur = utilisateurConnecte();

// Vérifier la méthode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['flash_error'] = 'Méthode non autorisée.';
    header('Location: ' . url_for('commercial/reporting_terrain/'));
    exit;
}

// Vérifier le CSRF
verifierCsrf($_POST['csrf_token'] ?? '');

try {
    $pdo->beginTransaction();

    // ════════════════════════════════════════════════════════════════════════
    // 1. INSERT TABLE PRINCIPALE: terrain_reporting
    // ════════════════════════════════════════════════════════════════════════
    $commercial_nom = trim($_POST['commercial_nom'] ?? $utilisateur['nom_complet'] ?? $utilisateur['login']);
    $semaine_debut = $_POST['semaine_debut'] ?? date('Y-m-d');
    $semaine_fin = $_POST['semaine_fin'] ?? date('Y-m-d');
    $ville = trim($_POST['ville'] ?? '');
    $responsable_nom = trim($_POST['responsable_nom'] ?? '');
    $synthese = trim($_POST['synthese'] ?? '');
    $signature_nom = trim($_POST['signature_nom'] ?? '');

    // Validation des dates
    if (strtotime($semaine_fin) < strtotime($semaine_debut)) {
        throw new Exception('La date de fin doit être postérieure à la date de début.');
    }
    
    // Limiter synthèse à 900 caractères
    if (strlen($synthese) > 900) {
        $synthese = substr($synthese, 0, 900);
    }

    $stmt = $pdo->prepare("
        INSERT INTO terrain_reporting 
        (user_id, commercial_nom, semaine_debut, semaine_fin, ville, responsable_nom, synthese, signature_nom, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    $stmt->execute([
        $utilisateur['id'],
        $commercial_nom,
        $semaine_debut,
        $semaine_fin,
        $ville ?: null,
        $responsable_nom ?: null,
        $synthese ?: null,
        $signature_nom ?: null
    ]);
    $reporting_id = $pdo->lastInsertId();

    // ════════════════════════════════════════════════════════════════════════
    // 2. INSERT ZONES & CIBLES (terrain_reporting_zones)
    // ════════════════════════════════════════════════════════════════════════
    $jours = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'];
    $zones = $_POST['zones'] ?? [];
    
    $stmtZone = $pdo->prepare("
        INSERT INTO terrain_reporting_zones 
        (reporting_id, jour, zone_quartier, type_cible, nb_points)
        VALUES (?, ?, ?, ?, ?)
    ");
    
    foreach ($jours as $jour) {
        if (isset($zones[$jour])) {
            $z = $zones[$jour];
            $zone_quartier = trim($z['zone_quartier'] ?? '');
            $type_cible = $z['type_cible'] ?? 'Quincaillerie';
            $nb_points = intval($z['nb_points'] ?? 0);
            
            // On insère même si vide, pour maintenir la structure
            $stmtZone->execute([
                $reporting_id,
                $jour,
                $zone_quartier ?: null,
                $type_cible,
                $nb_points
            ]);
        }
    }

    // ════════════════════════════════════════════════════════════════════════
    // 3. INSERT ACTIVITÉ JOURNALIÈRE (terrain_reporting_activite)
    // ════════════════════════════════════════════════════════════════════════
    $activites = $_POST['activite'] ?? [];
    
    $stmtAct = $pdo->prepare("
        INSERT INTO terrain_reporting_activite 
        (reporting_id, jour, contacts_qualifies, decideurs_rencontres, echantillons_presentes, grille_prix_remise, rdv_obtenus)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($jours as $jour) {
        if (isset($activites[$jour])) {
            $a = $activites[$jour];
            $stmtAct->execute([
                $reporting_id,
                $jour,
                intval($a['contacts_qualifies'] ?? 0),
                intval($a['decideurs_rencontres'] ?? 0),
                isset($a['echantillons_presentes']) ? 1 : 0,
                isset($a['grille_prix_remise']) ? 1 : 0,
                intval($a['rdv_obtenus'] ?? 0)
            ]);
        }
    }

    // ════════════════════════════════════════════════════════════════════════
    // 4. INSERT RÉSULTATS COMMERCIAUX (terrain_reporting_resultats)
    // ════════════════════════════════════════════════════════════════════════
    $resultats = $_POST['resultats'] ?? [];
    $indicateurs = [
        'visites_terrain',
        'contacts_qualifies',
        'devis_emis',
        'commandes_obtenues',
        'montant_commandes',
        'encaissements'
    ];
    
    $stmtRes = $pdo->prepare("
        INSERT INTO terrain_reporting_resultats 
        (reporting_id, indicateur, objectif, realise)
        VALUES (?, ?, ?, ?)
    ");
    
    foreach ($indicateurs as $ind) {
        if (isset($resultats[$ind])) {
            $r = $resultats[$ind];
            $objectif = floatval($r['objectif'] ?? 0);
            $realise = floatval($r['realise'] ?? 0);
            
            $stmtRes->execute([
                $reporting_id,
                $ind,
                $objectif,
                $realise
            ]);
        }
    }

    // ════════════════════════════════════════════════════════════════════════
    // 5. INSERT OBJECTIONS (terrain_reporting_objections)
    // ════════════════════════════════════════════════════════════════════════
    $objections = $_POST['objections'] ?? [];
    $objections_list = [
        'prix_eleve',
        'qualite_pas_regardee',
        'similaire_moins_cher',
        'pas_tresorerie',
        'decideur_absent',
        'autre'
    ];
    
    $stmtObj = $pdo->prepare("
        INSERT INTO terrain_reporting_objections 
        (reporting_id, objection_code, frequence, commentaire, autre_texte)
        VALUES (?, ?, ?, ?, ?)
    ");
    
    foreach ($objections_list as $type) {
        if (isset($objections[$type]) && isset($objections[$type]['active'])) {
            $o = $objections[$type];
            $frequence = $o['frequence'] ?? 'Moyenne';
            $commentaire = trim($o['commentaire'] ?? '');
            $autre_texte = ($type === 'autre') ? trim($o['autre_texte'] ?? '') : null;
            
            $stmtObj->execute([
                $reporting_id,
                $type,
                $frequence,
                $commentaire ?: null,
                $autre_texte
            ]);
        }
    }

    // ════════════════════════════════════════════════════════════════════════
    // 6. INSERT ARGUMENTS (terrain_reporting_arguments)
    // ════════════════════════════════════════════════════════════════════════
    $arguments = $_POST['arguments'] ?? [];
    $arguments_list = [
        'qualite_durabilite',
        'marge_possible',
        'echantillons_visibles',
        'stock_disponible',
        'autre'
    ];
    
    $stmtArg = $pdo->prepare("
        INSERT INTO terrain_reporting_arguments 
        (reporting_id, argument_code, impact, exemple_contexte, autre_texte)
        VALUES (?, ?, ?, ?, ?)
    ");
    
    foreach ($arguments_list as $type) {
        if (isset($arguments[$type]) && isset($arguments[$type]['active'])) {
            $arg = $arguments[$type];
            $impact = $arg['impact'] ?? 'Moyen';
            $exemple = trim($arg['exemple_contexte'] ?? '');
            $autre_texte = ($type === 'autre') ? trim($arg['autre_texte'] ?? '') : null;
            
            $stmtArg->execute([
                $reporting_id,
                $type,
                $impact,
                $exemple ?: null,
                $autre_texte
            ]);
        }
    }

    // ════════════════════════════════════════════════════════════════════════
    // 7. INSERT PLAN D'ACTION (terrain_reporting_plan_action)
    // ════════════════════════════════════════════════════════════════════════
    $plan_actions = $_POST['plan_action'] ?? [];
    
    $stmtPlan = $pdo->prepare("
        INSERT INTO terrain_reporting_plan_action 
        (reporting_id, priorite, action_concrete, zone_cible, echeance)
        VALUES (?, ?, ?, ?, ?)
    ");
    
    for ($i = 1; $i <= 3; $i++) {
        if (isset($plan_actions[$i])) {
            $pa = $plan_actions[$i];
            $action = trim($pa['action_concrete'] ?? '');
            $zone = trim($pa['zone_cible'] ?? '');
            $echeance = !empty($pa['echeance']) ? $pa['echeance'] : null;
            
            // N'insérer que si au moins une donnée est présente
            if ($action || $zone || $echeance) {
                $stmtPlan->execute([
                    $reporting_id,
                    $i,
                    $action ?: null,
                    $zone ?: null,
                    $echeance
                ]);
            }
        }
    }

    // ════════════════════════════════════════════════════════════════════════
    // COMMIT TRANSACTION
    // ════════════════════════════════════════════════════════════════════════
    $pdo->commit();

    $_SESSION['flash_success'] = 'Reporting hebdomadaire créé avec succès !';
    header('Location: ' . url_for('commercial/reporting_terrain/show.php?id=' . $reporting_id));
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    
    // Log l'erreur
    error_log('Erreur création reporting terrain: ' . $e->getMessage());
    
    $_SESSION['flash_error'] = 'Erreur lors de la création du reporting : ' . htmlspecialchars($e->getMessage());
    header('Location: ' . url_for('commercial/reporting_terrain/create.php'));
    exit;
}
