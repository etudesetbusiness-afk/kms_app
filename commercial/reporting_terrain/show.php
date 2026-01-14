<?php
/**
 * Reporting Terrain - Affichage détaillé
 * Module: commercial/reporting_terrain/show.php
 * Vue complète du reporting en lecture seule
 */

require_once __DIR__ . '/../../security.php';
exigerConnexion();

global $pdo;
$utilisateur = utilisateurConnecte();

// Récupérer l'ID du reporting
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    $_SESSION['flash_error'] = 'Reporting non trouvé.';
    header('Location: ' . url_for('commercial/reporting_terrain/'));
    exit;
}

// Charger le reporting principal
$stmt = $pdo->prepare("
    SELECT r.*, u.nom_complet as user_nom
    FROM terrain_reporting r
    LEFT JOIN utilisateurs u ON r.user_id = u.id
    WHERE r.id = ?
");
$stmt->execute([$id]);
$reporting = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reporting) {
    $_SESSION['flash_error'] = 'Reporting non trouvé.';
    header('Location: ' . url_for('commercial/reporting_terrain/'));
    exit;
}

// Vérifier les droits d'accès (admin ou propriétaire)
$isAdmin = estAdmin(); // Utilise la fonction de security.php
if (!$isAdmin && $reporting['user_id'] != $utilisateur['id']) {
    $_SESSION['flash_error'] = 'Vous n\'avez pas accès à ce reporting.';
    header('Location: ' . url_for('commercial/reporting_terrain/'));
    exit;
}

// Charger les zones
$stmtZones = $pdo->prepare("SELECT * FROM terrain_reporting_zones WHERE reporting_id = ? ORDER BY FIELD(jour, 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam')");
$stmtZones->execute([$id]);
$zones = $stmtZones->fetchAll(PDO::FETCH_ASSOC);
$zones_by_jour = [];
foreach ($zones as $z) {
    $zones_by_jour[$z['jour']] = $z;
}

// Charger les activités
$stmtAct = $pdo->prepare("SELECT * FROM terrain_reporting_activite WHERE reporting_id = ? ORDER BY FIELD(jour, 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam')");
$stmtAct->execute([$id]);
$activites = $stmtAct->fetchAll(PDO::FETCH_ASSOC);
$activites_by_jour = [];
foreach ($activites as $a) {
    $activites_by_jour[$a['jour']] = $a;
}

// Charger les résultats
$stmtRes = $pdo->prepare("SELECT * FROM terrain_reporting_resultats WHERE reporting_id = ?");
$stmtRes->execute([$id]);
$resultats = $stmtRes->fetchAll(PDO::FETCH_ASSOC);
$resultats_by_ind = [];
foreach ($resultats as $r) {
    $resultats_by_ind[$r['indicateur']] = $r;
}

// Charger les objections
$stmtObj = $pdo->prepare("SELECT * FROM terrain_reporting_objections WHERE reporting_id = ?");
$stmtObj->execute([$id]);
$objections = $stmtObj->fetchAll(PDO::FETCH_ASSOC);

// Charger les arguments
$stmtArg = $pdo->prepare("SELECT * FROM terrain_reporting_arguments WHERE reporting_id = ?");
$stmtArg->execute([$id]);
$arguments = $stmtArg->fetchAll(PDO::FETCH_ASSOC);

// Charger le plan d'action
$stmtPlan = $pdo->prepare("SELECT * FROM terrain_reporting_plan_action WHERE reporting_id = ? ORDER BY priorite");
$stmtPlan->execute([$id]);
$plan_actions = $stmtPlan->fetchAll(PDO::FETCH_ASSOC);

// Labels
$jours = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'];
$indicateurs_labels = [
    'visites_terrain' => 'Visites terrain',
    'contacts_qualifies' => 'Contacts qualifiés',
    'devis_emis' => 'Devis émis',
    'commandes_obtenues' => 'Commandes obtenues',
    'montant_commandes' => 'Montant commandes (FCFA)',
    'encaissements' => 'Encaissements (FCFA)'
];
$objections_labels = [
    'prix_eleve' => 'Prix jugé élevé',
    'qualite_pas_regardee' => 'Client final ne regarde pas qualité',
    'similaire_moins_cher' => 'Produits similaires moins chers',
    'pas_tresorerie' => 'Pas le bon moment / trésorerie',
    'decideur_absent' => 'Décideur absent',
    'autre' => 'Autre'
];
$arguments_labels = [
    'qualite_durabilite' => 'Qualité & durabilité',
    'marge_possible' => 'Marge possible',
    'echantillons_visibles' => 'Échantillons visibles',
    'stock_disponible' => 'Stock disponible',
    'autre' => 'Autre'
];

// Format dates
$semaine_debut_fmt = date('d/m/Y', strtotime($reporting['semaine_debut']));
$semaine_fin_fmt = date('d/m/Y', strtotime($reporting['semaine_fin']));

include __DIR__ . '/../../partials/header.php';
include __DIR__ . '/../../partials/sidebar.php';
?>

<style>
.section-header {
    background: linear-gradient(135deg, var(--bs-primary) 0%, #4a6cf7 100%);
    color: white;
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
    margin-bottom: 1rem;
}
.data-card {
    border-left: 4px solid var(--bs-primary);
}
.data-label {
    font-weight: 600;
    color: #6c757d;
    font-size: 0.85rem;
}
.data-value {
    font-size: 1rem;
}
.badge-frequence {
    font-size: 0.75rem;
}
.ecart-positif { color: #198754; font-weight: bold; }
.ecart-negatif { color: #dc3545; font-weight: bold; }
</style>

<div class="container-fluid py-4">
    <!-- En-tête -->
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-4 gap-2">
        <div class="d-flex align-items-center">
            <a href="<?= url_for('commercial/reporting_terrain/') ?>" class="btn btn-outline-secondary me-3">
                <i class="bi bi-arrow-left"></i>
            </a>
            <div>
                <h1 class="h3 mb-0">
                    <i class="bi bi-file-earmark-text text-primary me-2"></i>
                    Reporting Hebdomadaire
                </h1>
                <p class="text-muted mb-0 small">
                    <?= htmlspecialchars($reporting['commercial_nom']) ?> – 
                    Semaine du <?= $semaine_debut_fmt ?> au <?= $semaine_fin_fmt ?>
                </p>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= url_for('commercial/reporting_terrain/print.php?id=' . $id) ?>" 
               class="btn btn-outline-primary" target="_blank">
                <i class="bi bi-printer me-1"></i>
                Imprimer
            </a>
        </div>
    </div>

    <!-- Flash messages -->
    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['flash_success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!-- SECTION 1: IDENTIFICATION -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <div class="card shadow-sm mb-4">
        <div class="section-header">
            <i class="bi bi-person-badge me-2"></i>
            1. Identification
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-6 col-md-3">
                    <div class="data-label">Commercial</div>
                    <div class="data-value"><?= htmlspecialchars($reporting['commercial_nom']) ?></div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="data-label">Période</div>
                    <div class="data-value"><?= $semaine_debut_fmt ?> → <?= $semaine_fin_fmt ?></div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="data-label">Ville</div>
                    <div class="data-value"><?= htmlspecialchars($reporting['ville'] ?: '-') ?></div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="data-label">Responsable</div>
                    <div class="data-value"><?= htmlspecialchars($reporting['responsable_nom'] ?: '-') ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!-- SECTION 2: ZONES & CIBLES -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <div class="card shadow-sm mb-4">
        <div class="section-header">
            <i class="bi bi-geo-alt me-2"></i>
            2. Zones & Cibles Couvertes
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Jour</th>
                            <th>Zone / Quartier</th>
                            <th>Type cible</th>
                            <th class="text-center">Points visités</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total_points = 0;
                        foreach ($jours as $jour): 
                            $z = $zones_by_jour[$jour] ?? null;
                            $pts = $z ? intval($z['nb_points']) : 0;
                            $total_points += $pts;
                        ?>
                        <tr>
                            <td class="fw-bold"><?= $jour ?></td>
                            <td><?= htmlspecialchars($z['zone_quartier'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($z['type_cible'] ?? '-') ?></td>
                            <td class="text-center"><?= $pts ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="table-primary fw-bold">
                            <td colspan="3">Total</td>
                            <td class="text-center"><?= $total_points ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!-- SECTION 3: SUIVI JOURNALIER -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <div class="card shadow-sm mb-4">
        <div class="section-header">
            <i class="bi bi-calendar-check me-2"></i>
            3. Suivi Journalier
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Jour</th>
                            <th class="text-center">Contacts</th>
                            <th class="text-center">Décideurs</th>
                            <th class="text-center">Échant.</th>
                            <th class="text-center">Grille</th>
                            <th class="text-center">RDV</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $totaux = ['contacts' => 0, 'decideurs' => 0, 'echant' => 0, 'grille' => 0, 'rdv' => 0];
                        foreach ($jours as $jour): 
                            $a = $activites_by_jour[$jour] ?? null;
                            if ($a) {
                                $totaux['contacts'] += intval($a['contacts_qualifies']);
                                $totaux['decideurs'] += intval($a['decideurs_rencontres']);
                                $totaux['echant'] += intval($a['echantillons_presentes']);
                                $totaux['grille'] += intval($a['grille_prix_remise']);
                                $totaux['rdv'] += intval($a['rdv_obtenus']);
                            }
                        ?>
                        <tr>
                            <td class="fw-bold"><?= $jour ?></td>
                            <td class="text-center"><?= $a ? intval($a['contacts_qualifies']) : 0 ?></td>
                            <td class="text-center"><?= $a ? intval($a['decideurs_rencontres']) : 0 ?></td>
                            <td class="text-center">
                                <?php if ($a && $a['echantillons_presentes']): ?>
                                    <i class="bi bi-check-circle-fill text-success"></i>
                                <?php else: ?>
                                    <i class="bi bi-x-circle text-muted"></i>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($a && $a['grille_prix_remise']): ?>
                                    <i class="bi bi-check-circle-fill text-success"></i>
                                <?php else: ?>
                                    <i class="bi bi-x-circle text-muted"></i>
                                <?php endif; ?>
                            </td>
                            <td class="text-center"><?= $a ? intval($a['rdv_obtenus']) : 0 ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="table-primary fw-bold">
                            <td>Total</td>
                            <td class="text-center"><?= $totaux['contacts'] ?></td>
                            <td class="text-center"><?= $totaux['decideurs'] ?></td>
                            <td class="text-center"><?= $totaux['echant'] ?></td>
                            <td class="text-center"><?= $totaux['grille'] ?></td>
                            <td class="text-center"><?= $totaux['rdv'] ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!-- SECTION 4: RÉSULTATS COMMERCIAUX -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <div class="card shadow-sm mb-4">
        <div class="section-header">
            <i class="bi bi-graph-up-arrow me-2"></i>
            4. Résultats Commerciaux Semaine
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Indicateur</th>
                            <th class="text-end">Objectif</th>
                            <th class="text-end">Réalisé</th>
                            <th class="text-end">Écart</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($indicateurs_labels as $code => $label): 
                            $r = $resultats_by_ind[$code] ?? null;
                            $obj = $r ? floatval($r['objectif']) : 0;
                            $real = $r ? floatval($r['realise']) : 0;
                            $ecart = $real - $obj;
                            $ecart_class = $ecart >= 0 ? 'ecart-positif' : 'ecart-negatif';
                            $is_montant = in_array($code, ['montant_commandes', 'encaissements']);
                        ?>
                        <tr>
                            <td><?= $label ?></td>
                            <td class="text-end">
                                <?= $is_montant ? number_format($obj, 0, ',', ' ') : $obj ?>
                            </td>
                            <td class="text-end">
                                <?= $is_montant ? number_format($real, 0, ',', ' ') : $real ?>
                            </td>
                            <td class="text-end <?= $ecart_class ?>">
                                <?= ($ecart >= 0 ? '+' : '') . ($is_montant ? number_format($ecart, 0, ',', ' ') : $ecart) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <!-- SECTION 5: OBJECTIONS -->
        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <div class="col-12 col-lg-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="section-header">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    5. Objections Rencontrées
                </div>
                <div class="card-body">
                    <?php if (empty($objections)): ?>
                        <p class="text-muted mb-0">Aucune objection signalée.</p>
                    <?php else: ?>
                        <?php foreach ($objections as $obj): 
                            $label = $objections_labels[$obj['objection_code']] ?? $obj['objection_code'];
                            $badge_class = $obj['frequence'] === 'Élevée' ? 'bg-danger' : 
                                          ($obj['frequence'] === 'Moyenne' ? 'bg-warning text-dark' : 'bg-secondary');
                        ?>
                        <div class="d-flex align-items-start mb-2 pb-2 border-bottom">
                            <i class="bi bi-exclamation-circle text-warning me-2 mt-1"></i>
                            <div class="flex-grow-1">
                                <div class="fw-semibold">
                                    <?= htmlspecialchars($label) ?>
                                    <?php if ($obj['objection_code'] === 'autre' && $obj['autre_texte']): ?>
                                        : <?= htmlspecialchars($obj['autre_texte']) ?>
                                    <?php endif; ?>
                                </div>
                                <span class="badge <?= $badge_class ?> badge-frequence">
                                    <?= htmlspecialchars($obj['frequence']) ?>
                                </span>
                                <?php if ($obj['commentaire']): ?>
                                    <div class="text-muted small mt-1"><?= htmlspecialchars($obj['commentaire']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <!-- SECTION 6: ARGUMENTS -->
        <!-- ═══════════════════════════════════════════════════════════════════ -->
        <div class="col-12 col-lg-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="section-header">
                    <i class="bi bi-lightbulb me-2"></i>
                    6. Arguments Efficaces
                </div>
                <div class="card-body">
                    <?php if (empty($arguments)): ?>
                        <p class="text-muted mb-0">Aucun argument signalé.</p>
                    <?php else: ?>
                        <?php foreach ($arguments as $arg): 
                            $label = $arguments_labels[$arg['argument_code']] ?? $arg['argument_code'];
                            $badge_class = $arg['impact'] === 'Fort' ? 'bg-success' : 
                                          ($arg['impact'] === 'Moyen' ? 'bg-info' : 'bg-secondary');
                        ?>
                        <div class="d-flex align-items-start mb-2 pb-2 border-bottom">
                            <i class="bi bi-check2-circle text-success me-2 mt-1"></i>
                            <div class="flex-grow-1">
                                <div class="fw-semibold">
                                    <?= htmlspecialchars($label) ?>
                                    <?php if ($arg['argument_code'] === 'autre' && $arg['autre_texte']): ?>
                                        : <?= htmlspecialchars($arg['autre_texte']) ?>
                                    <?php endif; ?>
                                </div>
                                <span class="badge <?= $badge_class ?> badge-frequence">
                                    Impact <?= htmlspecialchars($arg['impact']) ?>
                                </span>
                                <?php if ($arg['exemple_contexte']): ?>
                                    <div class="text-muted small mt-1"><?= htmlspecialchars($arg['exemple_contexte']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!-- SECTION 7: PLAN D'ACTION -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <div class="card shadow-sm mb-4">
        <div class="section-header">
            <i class="bi bi-bullseye me-2"></i>
            7. Plan d'Action Semaine Suivante
        </div>
        <div class="card-body">
            <?php if (empty($plan_actions)): ?>
                <p class="text-muted mb-0">Aucun plan d'action défini.</p>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($plan_actions as $pa): ?>
                    <div class="col-12 col-md-4">
                        <div class="card data-card h-100">
                            <div class="card-body">
                                <h6 class="card-subtitle mb-2 text-muted">Priorité <?= $pa['priorite'] ?></h6>
                                <p class="card-text fw-semibold mb-2">
                                    <?= htmlspecialchars($pa['action_concrete'] ?: '-') ?>
                                </p>
                                <div class="small">
                                    <i class="bi bi-geo-alt me-1"></i>
                                    <?= htmlspecialchars($pa['zone_cible'] ?: '-') ?>
                                </div>
                                <?php if ($pa['echeance']): ?>
                                <div class="small text-muted mt-1">
                                    <i class="bi bi-calendar me-1"></i>
                                    <?= date('d/m/Y', strtotime($pa['echeance'])) ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!-- SECTION 8: SYNTHÈSE -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <?php if ($reporting['synthese']): ?>
    <div class="card shadow-sm mb-4">
        <div class="section-header">
            <i class="bi bi-file-text me-2"></i>
            8. Synthèse
        </div>
        <div class="card-body">
            <p class="mb-0" style="white-space: pre-line;"><?= htmlspecialchars($reporting['synthese']) ?></p>
        </div>
    </div>
    <?php endif; ?>

    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <!-- SIGNATURE -->
    <!-- ═══════════════════════════════════════════════════════════════════════ -->
    <?php if ($reporting['signature_nom']): ?>
    <div class="card shadow-sm mb-4">
        <div class="card-body text-center">
            <div class="text-muted small">Signé par</div>
            <div class="fw-bold fs-5"><?= htmlspecialchars($reporting['signature_nom']) ?></div>
            <div class="text-muted small">
                Le <?= date('d/m/Y à H:i', strtotime($reporting['created_at'])) ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>

<?php include __DIR__ . '/../../partials/footer.php'; ?>
