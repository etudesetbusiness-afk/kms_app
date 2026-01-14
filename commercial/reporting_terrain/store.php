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

    // Déterminer si c'est une création ou une modification
    $reporting_id = intval($_POST['reporting_id'] ?? 0);
    $isUpdate = $reporting_id > 0;

    // ════════════════════════════════════════════════════════════════════════
    // MODE ÉDITION: Vérifier l'accès au brouillon
    // ════════════════════════════════════════════════════════════════════════
    if ($isUpdate) {
        $stmt = $pdo->prepare("SELECT user_id, statut FROM terrain_reporting WHERE id = ?");
        $stmt->execute([$reporting_id]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$existing) {
            throw new Exception('Reporting inexistant.');
        }

        $isAdmin = estAdmin(); // Utilise la fonction de security.php
        if (!$isAdmin && $existing['user_id'] != $utilisateur['id']) {
            throw new Exception('Accès refusé : vous n\'êtes pas propriétaire de ce reporting.');
        }

        if (($existing['statut'] ?? 'soumis') !== 'brouillon') {
            throw new Exception('Ce reporting n\'est pas un brouillon et ne peut pas être modifié.');
        }
    }

    // ════════════════════════════════════════════════════════════════════════
    // 1. INSERT OU UPDATE TABLE PRINCIPALE: terrain_reporting
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

    if (!$isUpdate) {
        // CRÉATION
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
    } else {
        // MODIFICATION
        $stmt = $pdo->prepare("
            UPDATE terrain_reporting 
            SET commercial_nom = ?, semaine_debut = ?, semaine_fin = ?, ville = ?, 
                responsable_nom = ?, synthese = ?, signature_nom = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([
            $commercial_nom,
            $semaine_debut,
            $semaine_fin,
            $ville ?: null,
            $responsable_nom ?: null,
            $synthese ?: null,
            $signature_nom ?: null,
            $reporting_id
        ]);
    }

    // Déterminer le statut (brouillon/soumis)
    $action = $_POST['action'] ?? 'save';
    $statut = ($action === 'submit') ? 'soumis' : 'brouillon';
    // Tenter de mettre à jour le statut si la colonne existe
    try {
        $pdo->prepare("UPDATE terrain_reporting SET statut = ? WHERE id = ?")->execute([$statut, $reporting_id]);
    } catch (Throwable $ignored) {
        // Colonne absente en base: un script de migration ajoutera `statut`
    }

    // ════════════════════════════════════════════════════════════════════════
    // 2. INSERT OU REMPLACER ZONES & CIBLES (terrain_reporting_zones)
    // ════════════════════════════════════════════════════════════════════════
    // En mode édition, supprimer les anciennes zones
    if ($isUpdate) {
        $pdo->prepare("DELETE FROM terrain_reporting_zones WHERE reporting_id = ?")->execute([$reporting_id]);
    }
    
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
            
            // Traiter les checkboxes multiples de type_cible
            $typeCiblesArray = $z['type_cible'] ?? [];
            if (!is_array($typeCiblesArray)) {
                $typeCiblesArray = [$typeCiblesArray];
            }
            $typeCiblesArray = array_filter(array_map('trim', $typeCiblesArray));
            $type_cible = !empty($typeCiblesArray) ? implode(',', $typeCiblesArray) : null;
            
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
    // 3. INSERT OU REMPLACER ACTIVITÉ JOURNALIÈRE (terrain_reporting_activite)
    // ════════════════════════════════════════════════════════════════════════
    // En mode édition, supprimer les anciennes activités
    if ($isUpdate) {
        $pdo->prepare("DELETE FROM terrain_reporting_activite WHERE reporting_id = ?")->execute([$reporting_id]);
    }
    
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
    // 4. INSERT OU REMPLACER RÉSULTATS COMMERCIAUX (terrain_reporting_resultats)
    // ════════════════════════════════════════════════════════════════════════
    // En mode édition, supprimer les anciens résultats
    if ($isUpdate) {
        $pdo->prepare("DELETE FROM terrain_reporting_resultats WHERE reporting_id = ?")->execute([$reporting_id]);
    }
    
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
    // 5. INSERT OU REMPLACER OBJECTIONS (terrain_reporting_objections)
    // ════════════════════════════════════════════════════════════════════════
    // En mode édition, supprimer les anciennes objections
    if ($isUpdate) {
        $pdo->prepare("DELETE FROM terrain_reporting_objections WHERE reporting_id = ?")->execute([$reporting_id]);
    }
    
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
    // 6. INSERT OU REMPLACER ARGUMENTS (terrain_reporting_arguments)
    // ════════════════════════════════════════════════════════════════════════
    // En mode édition, supprimer les anciens arguments
    if ($isUpdate) {
        $pdo->prepare("DELETE FROM terrain_reporting_arguments WHERE reporting_id = ?")->execute([$reporting_id]);
    }
    
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
    // 7. INSERT OU REMPLACER PLAN D'ACTION (terrain_reporting_plan_action)
    // ════════════════════════════════════════════════════════════════════════
    // En mode édition, supprimer les anciens plans
    if ($isUpdate) {
        $pdo->prepare("DELETE FROM terrain_reporting_plan_action WHERE reporting_id = ?")->execute([$reporting_id]);
    }
    
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

    if ($statut === 'soumis') {
        $_SESSION['flash_success'] = ($isUpdate) 
            ? 'Reporting modifié et soumis avec succès !' 
            : 'Reporting créé et soumis avec succès !';
    } else {
        $_SESSION['flash_success'] = ($isUpdate)
            ? 'Brouillon modifié et enregistré.'
            : 'Reporting enregistré en brouillon.';
    }
    header('Location: ' . url_for('commercial/reporting_terrain/show.php?id=' . $reporting_id));
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    
    // Log l'erreur
    error_log('Erreur création/modification reporting terrain: ' . $e->getMessage());
    
    $_SESSION['flash_error'] = 'Erreur lors de l\'opération : ' . htmlspecialchars($e->getMessage());
    if ($isUpdate) {
        header('Location: ' . url_for('commercial/reporting_terrain/edit.php?id=' . $reporting_id));
    } else {
        header('Location: ' . url_for('commercial/reporting_terrain/create.php'));
    }
    exit;
}
