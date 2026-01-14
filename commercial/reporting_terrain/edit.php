<?php
/**
 * Reporting Terrain - Édition d'un brouillon
 * Module: commercial/reporting_terrain/edit.php
 * Permet de modifier un reporting en brouillon
 */

require_once __DIR__ . '/../../security.php';
exigerConnexion();

global $pdo;
$utilisateur = utilisateurConnecte();

// Récupérer l'ID du reporting
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    $_SESSION['flash_error'] = 'Reporting introuvable.';
    header('Location: ' . url_for('commercial/reporting_terrain/'));
    exit;
}

// Charger le reporting principal
$stmt = $pdo->prepare("SELECT * FROM terrain_reporting WHERE id = ?");
$stmt->execute([$id]);
$reporting = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reporting) {
    $_SESSION['flash_error'] = 'Reporting introuvable.';
    header('Location: ' . url_for('commercial/reporting_terrain/'));
    exit;
}

// Vérifier les droits d'accès
$isAdmin = estAdmin(); // Utilise la fonction de security.php
if (!$isAdmin && $reporting['user_id'] != $utilisateur['id']) {
    $_SESSION['flash_error'] = 'Accès refusé : vous n\'êtes pas propriétaire de ce reporting.';
    header('Location: ' . url_for('commercial/reporting_terrain/'));
    exit;
}

// Vérifier que c'est un brouillon
if (($reporting['statut'] ?? 'soumis') !== 'brouillon') {
    $_SESSION['flash_error'] = 'Ce reporting n\'est pas un brouillon et ne peut pas être modifié.';
    header('Location: ' . url_for('commercial/reporting_terrain/show.php?id=' . $id));
    exit;
}

// Générer le token CSRF
$csrfToken = getCsrfToken();

// Jours et indicateurs
$jours = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'];
$indicateurs = [
    'visites_terrain' => 'Visites terrain',
    'contacts_qualifies' => 'Contacts qualifiés',
    'devis_emis' => 'Devis émis',
    'commandes_obtenues' => 'Commandes obtenues',
    'montant_commandes' => 'Montant commandes (FCFA)',
    'encaissements' => 'Encaissements (FCFA)'
];
$objections_list = [
    'prix_eleve' => 'Prix jugé élevé',
    'qualite_pas_regardee' => 'Client final ne regarde pas qualité',
    'similaire_moins_cher' => 'Produits similaires moins chers',
    'pas_tresorerie' => 'Pas le bon moment / trésorerie',
    'decideur_absent' => 'Décideur absent',
    'autre' => 'Autre'
];
$arguments_list = [
    'qualite_durabilite' => 'Qualité & durabilité',
    'marge_possible' => 'Marge possible',
    'echantillons_visibles' => 'Échantillons visibles',
    'stock_disponible' => 'Stock disponible',
    'autre' => 'Autre'
];

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
$objections_data = $stmtObj->fetchAll(PDO::FETCH_ASSOC);
$objections_by_code = [];
foreach ($objections_data as $o) {
    $objections_by_code[$o['objection_code']] = $o;
}

// Charger les arguments
$stmtArg = $pdo->prepare("SELECT * FROM terrain_reporting_arguments WHERE reporting_id = ?");
$stmtArg->execute([$id]);
$arguments_data = $stmtArg->fetchAll(PDO::FETCH_ASSOC);
$arguments_by_code = [];
foreach ($arguments_data as $a) {
    $arguments_by_code[$a['argument_code']] = $a;
}

// Charger le plan d'action
$stmtPlan = $pdo->prepare("SELECT * FROM terrain_reporting_plan_action WHERE reporting_id = ? ORDER BY priorite");
$stmtPlan->execute([$id]);
$plans = $stmtPlan->fetchAll(PDO::FETCH_ASSOC);
$plans_by_pri = [];
foreach ($plans as $p) {
    $plans_by_pri[$p['priorite']] = $p;
}

include __DIR__ . '/../../partials/header.php';
include __DIR__ . '/../../partials/sidebar.php';
?>

<style>
/* Mobile-first styles */
.form-control, .form-select, .btn {
    font-size: 16px !important;
}

@media (min-width: 768px) {
    .form-control, .form-select {
        min-height: 38px;
    }
}
@media (max-width: 767.98px) {
    .form-control, .form-select {
        min-height: 48px;
        font-size: 16px !important;
    }
    textarea.form-control {
        min-height: 80px !important;
    }
    .mobile-full-width {
        width: 100% !important;
        margin-bottom: 0.5rem;
    }
}

.accordion-button {
    font-weight: 600;
    padding: 1rem;
}
.accordion-button:not(.collapsed) {
    background-color: var(--bs-primary);
    color: white;
}
.accordion-button:not(.collapsed)::after {
    filter: brightness(0) invert(1);
}

.table-responsive {
    font-size: 14px;
}
.table input[type="number"],
.table input[type="text"] {
    min-width: 60px;
}

@media (max-width: 767.98px) {
    .table-mobile-stack thead {
        display: none;
    }
    
    .table-mobile-stack,
    .table-mobile-stack tbody,
    .table-mobile-stack tr,
    .table-mobile-stack td {
        display: block;
        width: 100%;
    }
    
    .table-mobile-stack tr {
        background: #f8f9fa;
        border-radius: 0.5rem;
        margin-bottom: 1rem;
        padding: 0.75rem;
        border: 1px solid #dee2e6;
    }
    
    .table-mobile-stack td {
        display: flex;
        flex-direction: column;
        padding: 0.5rem 0;
        border: none;
        text-align: left;
    }
    
    .table-mobile-stack td::before {
        content: attr(data-label);
        font-weight: 600;
        font-size: 12px;
        color: #6c757d;
        text-transform: uppercase;
        margin-bottom: 0.25rem;
    }
    
    .table-mobile-stack td.jour-cell {
        background: linear-gradient(135deg, var(--bs-primary) 0%, #4a6cf7 100%);
        color: white !important;
        border-radius: 0.5rem;
        padding: 0.75rem !important;
        margin-bottom: 0.5rem;
        font-weight: bold;
        font-size: 16px;
    }
    .table-mobile-stack td.jour-cell::before {
        display: none;
    }
    
    .table-mobile-stack input,
    .table-mobile-stack select {
        width: 100% !important;
        min-height: 48px !important;
    }
    
    .table-mobile-stack textarea {
        width: 100% !important;
        min-height: 80px !important;
    }
}

.checkbox-row {
    background-color: #f8f9fa;
    border-radius: 0.5rem;
    padding: 0.75rem 1rem;
    margin-bottom: 0.75rem;
    border: 1px solid #e9ecef;
    transition: all 0.2s;
}
.checkbox-row:hover {
    background-color: #e9ecef;
    border-color: #0d6efd;
}
.checkbox-row .form-check {
    margin-bottom: 0;
}

.form-check-input {
    width: 1.5em;
    height: 1.5em;
    border: 2px solid #6c757d;
    background-color: #fff;
    cursor: pointer;
}
.form-check-input:checked {
    background-color: #0d6efd;
    border-color: #0d6efd;
}
.form-check-input:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}
.form-check-input:hover {
    border-color: #0d6efd;
}
.form-check-label {
    cursor: pointer;
    padding-left: 0.5rem;
    font-weight: 500;
}

@media (max-width: 767.98px) {
    .checkbox-row .row {
        flex-direction: column;
    }
    .checkbox-row .row > [class*="col-"] {
        width: 100%;
        max-width: 100%;
        padding: 0.25rem 0;
    }
    .checkbox-row .form-check-label {
        font-size: 15px;
    }
    .checkbox-row select,
    .checkbox-row input[type="text"] {
        width: 100% !important;
        min-height: 48px !important;
        margin-top: 0.25rem;
    }
    .checkbox-row .mobile-label {
        font-size: 12px;
        color: #6c757d;
        margin-top: 0.5rem;
        margin-bottom: 0.25rem;
        display: block;
    }
}
@media (min-width: 768px) {
    .checkbox-row .mobile-label {
        display: none;
    }
}

@media (max-width: 767.98px) {
    .card-body .row > [class*="col-"] {
        width: 100%;
        max-width: 100%;
        margin-bottom: 0.5rem;
    }
    .card-body input,
    .card-body textarea {
        min-height: 48px !important;
    }
    .card-body input[placeholder="Zone / Cible"] {
        min-height: 60px !important;
    }
}

.btn-submit-fixed {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 1rem;
    background: white;
    border-top: 1px solid #dee2e6;
    z-index: 1050;
}
@media (min-width: 768px) {
    .btn-submit-fixed {
        position: static;
        background: transparent;
        border: none;
        padding: 0;
    }
}

.section-title {
    background: linear-gradient(135deg, var(--bs-primary) 0%, #4a6cf7 100%);
    color: white;
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
    margin-bottom: 1rem;
}
</style>

<div class="container-fluid py-4 pb-5 mb-5">
    <!-- En-tête -->
    <div class="d-flex align-items-center mb-4">
        <a href="<?= url_for('commercial/reporting_terrain/show.php?id=' . $id) ?>" class="btn btn-outline-secondary me-3">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-pencil-square text-warning me-2"></i>
                Modifier Reporting Brouillon
            </h1>
            <p class="text-muted mb-0 small">Semaine du <?= date('d/m/Y', strtotime($reporting['semaine_debut'])) ?></p>
        </div>
    </div>

    <!-- Badge brouillon -->
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="bi bi-info-circle me-2"></i>
        <strong>Brouillon en édition.</strong> Vous pouvez enregistrer et modifier cette version jusqu'à la soumettre.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>

    <form action="<?= url_for('commercial/reporting_terrain/store.php') ?>" method="POST" id="formReporting">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
        <input type="hidden" name="action" id="formAction" value="save">
        <input type="hidden" name="reporting_id" value="<?= $id ?>">

        <div class="accordion" id="accordionReporting">

            <!-- SECTION 1: IDENTIFICATION -->
            <div class="accordion-item border-0 shadow-sm mb-3">
                <h2 class="accordion-header">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#section1">
                        <i class="bi bi-person-badge me-2"></i>
                        1. Identification
                    </button>
                </h2>
                <div id="section1" class="accordion-collapse collapse show" data-bs-parent="#accordionReporting">
                    <div class="accordion-body">
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold">Commercial</label>
                                <input type="text" class="form-control" name="commercial_nom" 
                                       value="<?= htmlspecialchars($reporting['commercial_nom']) ?>" readonly>
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label fw-semibold">Semaine du</label>
                                <input type="date" class="form-control" name="semaine_debut" 
                                       value="<?= htmlspecialchars($reporting['semaine_debut']) ?>" required>
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label fw-semibold">Au</label>
                                <input type="date" class="form-control" name="semaine_fin" 
                                       value="<?= htmlspecialchars($reporting['semaine_fin']) ?>" required>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold">Ville</label>
                                <input type="text" class="form-control" name="ville" 
                                       value="<?= htmlspecialchars($reporting['ville'] ?? '') ?>">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold">Responsable</label>
                                <input type="text" class="form-control" name="responsable_nom" 
                                       value="<?= htmlspecialchars($reporting['responsable_nom'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION 2: ZONES & CIBLES COUVERTES -->
            <div class="accordion-item border-0 shadow-sm mb-3">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#section2">
                        <i class="bi bi-geo-alt me-2"></i>
                        2. Zones & Cibles Couvertes
                    </button>
                </h2>
                <div id="section2" class="accordion-collapse collapse" data-bs-parent="#accordionReporting">
                    <div class="accordion-body p-2 p-md-3">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0 table-mobile-stack">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:60px;">Jour</th>
                                        <th>Zone / Quartier</th>
                                        <th style="width:200px;">Types de cibles</th>
                                        <th style="width:80px;">Pts visités</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($jours as $jour): 
                                        $z = $zones_by_jour[$jour] ?? null;
                                        $typeCibles = $z['type_cible'] ?? '';
                                        $selectedTypes = array_filter(array_map('trim', explode(',', $typeCibles)));
                                    ?>
                                    <tr>
                                        <td class="jour-cell"><?= $jour ?></td>
                                        <td data-label="Zone / Quartier">
                                            <input type="text" class="form-control" 
                                                   name="zones[<?= $jour ?>][zone_quartier]" 
                                                   value="<?= htmlspecialchars($z['zone_quartier'] ?? '') ?>">
                                        </td>
                                        <td data-label="Types de cibles">
                                            <div class="d-flex flex-column gap-2">
                                                <div class="form-check">
                                                    <input type="checkbox" class="form-check-input" 
                                                           id="cible_menu_<?= $jour ?>" 
                                                           name="zones[<?= $jour ?>][type_cible][]" 
                                                           value="Menuiserie"
                                                           <?= in_array('Menuiserie', $selectedTypes) ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="cible_menu_<?= $jour ?>">
                                                        Menuiserie
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input type="checkbox" class="form-check-input" 
                                                           id="cible_quinca_<?= $jour ?>" 
                                                           name="zones[<?= $jour ?>][type_cible][]" 
                                                           value="Quincaillerie"
                                                           <?= in_array('Quincaillerie', $selectedTypes) ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="cible_quinca_<?= $jour ?>">
                                                        Quincaillerie
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input type="checkbox" class="form-check-input" 
                                                           id="cible_btp_<?= $jour ?>" 
                                                           name="zones[<?= $jour ?>][type_cible][]" 
                                                           value="Cabinet_BTP"
                                                           <?= in_array('Cabinet_BTP', $selectedTypes) ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="cible_btp_<?= $jour ?>">
                                                        Cabinet BTP
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input type="checkbox" class="form-check-input" 
                                                           id="cible_etude_<?= $jour ?>" 
                                                           name="zones[<?= $jour ?>][type_cible][]" 
                                                           value="Cabinet_etudes"
                                                           <?= in_array('Cabinet_etudes', $selectedTypes) ? 'checked' : '' ?>>
                                                    <label class="form-check-label" for="cible_etude_<?= $jour ?>">
                                                        Cabinet d'études
                                                    </label>
                                                </div>
                                            </div>
                                        </td>
                                        <td data-label="Points visités">
                                            <input type="number" class="form-control text-center" 
                                                   name="zones[<?= $jour ?>][nb_points]" min="0" 
                                                   value="<?= intval($z['nb_points'] ?? 0) ?>">
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION 3: SUIVI JOURNALIER -->
            <div class="accordion-item border-0 shadow-sm mb-3">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#section3">
                        <i class="bi bi-calendar-check me-2"></i>
                        3. Suivi Journalier
                    </button>
                </h2>
                <div id="section3" class="accordion-collapse collapse" data-bs-parent="#accordionReporting">
                    <div class="accordion-body p-2 p-md-3">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0 table-mobile-stack">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:50px;">Jour</th>
                                        <th style="width:70px;">Contacts</th>
                                        <th style="width:70px;">Décideurs</th>
                                        <th style="width:80px;" class="text-center">Échantillons présentés</th>
                                        <th style="width:70px;" class="text-center">Grille de prix présentée</th>
                                        <th style="width:60px;">RDV</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($jours as $jour): 
                                        $a = $activites_by_jour[$jour] ?? null;
                                    ?>
                                    <tr>
                                        <td class="jour-cell"><?= $jour ?></td>
                                        <td data-label="Contacts qualifiés">
                                            <input type="number" class="form-control text-center" 
                                                   name="activite[<?= $jour ?>][contacts_qualifies]" min="0" 
                                                   value="<?= intval($a['contacts_qualifies'] ?? 0) ?>">
                                        </td>
                                        <td data-label="Décideurs rencontrés">
                                            <input type="number" class="form-control text-center" 
                                                   name="activite[<?= $jour ?>][decideurs_rencontres]" min="0" 
                                                   value="<?= intval($a['decideurs_rencontres'] ?? 0) ?>">
                                        </td>
                                        <td data-label="Échantillons présentés">
                                            <div class="form-check d-flex align-items-center justify-content-center" style="min-height:48px;">
                                                <input type="checkbox" class="form-check-input" 
                                                       name="activite[<?= $jour ?>][echantillons_presentes]" value="1"
                                                       <?= ($a['echantillons_presentes'] ?? 0) ? 'checked' : '' ?>>
                                                <label class="form-check-label ms-2 d-md-none">Oui</label>
                                            </div>
                                        </td>
                                        <td data-label="Grille de prix présentée">
                                            <div class="form-check d-flex align-items-center justify-content-center" style="min-height:48px;">
                                                <input type="checkbox" class="form-check-input" 
                                                       name="activite[<?= $jour ?>][grille_prix_remise]" value="1"
                                                       <?= ($a['grille_prix_remise'] ?? 0) ? 'checked' : '' ?>>
                                                <label class="form-check-label ms-2 d-md-none">Oui</label>
                                            </div>
                                        </td>
                                        <td data-label="RDV obtenus">
                                            <input type="number" class="form-control text-center" 
                                                   name="activite[<?= $jour ?>][rdv_obtenus]" min="0" 
                                                   value="<?= intval($a['rdv_obtenus'] ?? 0) ?>">
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <small class="text-muted">Légende: Échantillons présentés = produits affichés au client | Grille de prix présentée = conditions tarifaires proposées</small>
                    </div>
                </div>
            </div>

            <!-- SECTION 4: RÉSULTATS COMMERCIAUX -->
            <div class="accordion-item border-0 shadow-sm mb-3">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#section4">
                        <i class="bi bi-graph-up-arrow me-2"></i>
                        4. Résultats Commerciaux Semaine
                    </button>
                </h2>
                <div id="section4" class="accordion-collapse collapse" data-bs-parent="#accordionReporting">
                    <div class="accordion-body p-2 p-md-3">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0 table-mobile-stack">
                                <thead class="table-light">
                                    <tr>
                                        <th>Indicateur</th>
                                        <th style="width:140px;">Objectif</th>
                                        <th style="width:100px;">Réalisé</th>
                                        <th style="width:100px;">Écart</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($indicateurs as $code => $label): 
                                        $r = $resultats_by_ind[$code] ?? null;
                                    ?>
                                    <tr>
                                        <td class="jour-cell"><?= $label ?></td>
                                        <td data-label="Objectif">
                                            <input type="number" step="0.01" class="form-control text-end obj-input" 
                                                style="min-width: 120px;" 
                                                name="resultats[<?= $code ?>][objectif]" min="0" 
                                                value="<?= floatval($r['objectif'] ?? 0) ?>" 
                                                data-indicateur="<?= $code ?>">
                                        </td>
                                        <td data-label="Réalisé">
                                            <input type="number" step="0.01" class="form-control text-end real-input" 
                                                name="resultats[<?= $code ?>][realise]" min="0" 
                                                value="<?= floatval($r['realise'] ?? 0) ?>"
                                                data-indicateur="<?= $code ?>">
                                        </td>
                                        <td data-label="Écart">
                                            <input type="text" class="form-control text-end ecart-display" 
                                                id="ecart_<?= $code ?>" readonly value="0">
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION 5: OBJECTIONS RENCONTRÉES -->
            <div class="accordion-item border-0 shadow-sm mb-3">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#section5">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        5. Objections Rencontrées
                    </button>
                </h2>
                <div id="section5" class="accordion-collapse collapse" data-bs-parent="#accordionReporting">
                    <div class="accordion-body">
                        <?php foreach ($objections_list as $code => $label): 
                            $o = $objections_by_code[$code] ?? null;
                        ?>
                        <div class="checkbox-row">
                            <div class="row g-2 align-items-center">
                                <div class="col-12">
                                    <div class="form-check d-flex align-items-center">
                                        <input type="checkbox" class="form-check-input objection-check me-2" 
                                               id="obj_<?= $code ?>" name="objections[<?= $code ?>][active]" value="1"
                                               <?= $o ? 'checked' : '' ?>
                                               style="flex-shrink: 0;">
                                        <label class="form-check-label" for="obj_<?= $code ?>">
                                            <?= $label ?>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-12 col-md-4">
                                    <span class="mobile-label">Fréquence</span>
                                    <select class="form-select" name="objections[<?= $code ?>][frequence]">
                                        <option value="Faible" <?= ($o['frequence'] ?? '') === 'Faible' ? 'selected' : '' ?>>Faible</option>
                                        <option value="Moyenne" <?= ($o['frequence'] ?? 'Moyenne') === 'Moyenne' ? 'selected' : '' ?>>Moyenne</option>
                                        <option value="Élevée" <?= ($o['frequence'] ?? '') === 'Élevée' ? 'selected' : '' ?>>Élevée</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-8">
                                    <span class="mobile-label">Commentaire</span>
                                    <?php if ($code === 'autre'): ?>
                                        <textarea class="form-control" rows="2"
                                               name="objections[<?= $code ?>][autre_texte]" 
                                               placeholder="Précisez l'objection rencontrée..."><?= htmlspecialchars($o['autre_texte'] ?? '') ?></textarea>
                                    <?php else: ?>
                                        <textarea class="form-control" rows="2"
                                               name="objections[<?= $code ?>][commentaire]" 
                                               placeholder="Détails, contexte, remarques..."><?= htmlspecialchars($o['commentaire'] ?? '') ?></textarea>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- SECTION 6: ARGUMENTS EFFICACES -->
            <div class="accordion-item border-0 shadow-sm mb-3">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#section6">
                        <i class="bi bi-lightbulb me-2"></i>
                        6. Arguments qui ont fonctionné
                    </button>
                </h2>
                <div id="section6" class="accordion-collapse collapse" data-bs-parent="#accordionReporting">
                    <div class="accordion-body">
                        <?php foreach ($arguments_list as $code => $label): 
                            $arg = $arguments_by_code[$code] ?? null;
                        ?>
                        <div class="checkbox-row">
                            <div class="row g-2 align-items-center">
                                <div class="col-12">
                                    <div class="form-check d-flex align-items-center">
                                        <input type="checkbox" class="form-check-input me-2" 
                                               id="arg_<?= $code ?>" name="arguments[<?= $code ?>][active]" value="1"
                                               <?= $arg ? 'checked' : '' ?>
                                               style="flex-shrink: 0;">
                                        <label class="form-check-label" for="arg_<?= $code ?>">
                                            <?= $label ?>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-12 col-md-3">
                                    <span class="mobile-label">Impact</span>
                                    <select class="form-select" name="arguments[<?= $code ?>][impact]">
                                        <option value="Faible" <?= ($arg['impact'] ?? '') === 'Faible' ? 'selected' : '' ?>>Faible</option>
                                        <option value="Moyen" <?= ($arg['impact'] ?? 'Moyen') === 'Moyen' ? 'selected' : '' ?>>Moyen</option>
                                        <option value="Fort" <?= ($arg['impact'] ?? '') === 'Fort' ? 'selected' : '' ?>>Fort</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-9">
                                    <span class="mobile-label">Exemple / Contexte</span>
                                    <?php if ($code === 'autre'): ?>
                                        <textarea class="form-control" rows="2"
                                               name="arguments[<?= $code ?>][autre_texte]" 
                                               placeholder="Précisez l'argument utilisé..."><?= htmlspecialchars($arg['autre_texte'] ?? '') ?></textarea>
                                    <?php else: ?>
                                        <textarea class="form-control" rows="2"
                                               name="arguments[<?= $code ?>][exemple_contexte]" 
                                               placeholder="Décrivez le contexte, l'exemple concret..."><?= htmlspecialchars($arg['exemple_contexte'] ?? '') ?></textarea>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- SECTION 7: PLAN D'ACTION SEMAINE SUIVANTE -->
            <div class="accordion-item border-0 shadow-sm mb-3">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#section7">
                        <i class="bi bi-bullseye me-2"></i>
                        7. Plan d'Action Semaine Suivante
                    </button>
                </h2>
                <div id="section7" class="accordion-collapse collapse" data-bs-parent="#accordionReporting">
                    <div class="accordion-body">
                        <?php for ($i = 1; $i <= 3; $i++): 
                            $p = $plans_by_pri[$i] ?? null;
                        ?>
                        <div class="card mb-3 border">
                            <div class="card-header bg-light py-2">
                                <strong>Priorité <?= $i ?></strong>
                            </div>
                            <div class="card-body p-3">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Action concrète à mener</label>
                                    <textarea class="form-control" rows="2"
                                           name="plan_action[<?= $i ?>][action_concrete]" 
                                           placeholder="Décrivez l'action à réaliser..."><?= htmlspecialchars($p['action_concrete'] ?? '') ?></textarea>
                                </div>
                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <label class="form-label fw-semibold">Zone / Cible</label>
                                        <input type="text" class="form-control" 
                                               name="plan_action[<?= $i ?>][zone_cible]" 
                                               value="<?= htmlspecialchars($p['zone_cible'] ?? '') ?>">
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label fw-semibold">Échéance</label>
                                        <input type="date" class="form-control" 
                                               name="plan_action[<?= $i ?>][echeance]"
                                               value="<?= htmlspecialchars($p['echeance'] ?? '') ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>

            <!-- SECTION 8: SYNTHÈSE -->
            <div class="accordion-item border-0 shadow-sm mb-3">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#section8">
                        <i class="bi bi-file-text me-2"></i>
                        8. Synthèse (5 lignes max)
                    </button>
                </h2>
                <div id="section8" class="accordion-collapse collapse" data-bs-parent="#accordionReporting">
                    <div class="accordion-body">
                        <textarea class="form-control" name="synthese" rows="5" maxlength="900" 
                                  placeholder="Résumé de la semaine, points clés, observations..."
                                  id="synthese"><?= htmlspecialchars($reporting['synthese'] ?? '') ?></textarea>
                        <div class="d-flex justify-content-between mt-1">
                            <small class="text-muted">Maximum 5 lignes / 900 caractères</small>
                            <small class="text-muted"><span id="synthese-count">0</span>/900</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION 9: SIGNATURE -->
            <div class="accordion-item border-0 shadow-sm mb-3">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#section9">
                        <i class="bi bi-pen me-2"></i>
                        9. Signature
                    </button>
                </h2>
                <div id="section9" class="accordion-collapse collapse" data-bs-parent="#accordionReporting">
                    <div class="accordion-body">
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold">Nom pour signature</label>
                                <input type="text" class="form-control" name="signature_nom" 
                                       value="<?= htmlspecialchars($reporting['signature_nom'] ?? '') ?>"
                                       placeholder="Tapez votre nom pour signer">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div><!-- Fin accordion -->

        <!-- Boutons submit fixes en mobile -->
        <div class="btn-submit-fixed d-md-none">
            <div class="d-grid gap-2">
                <button type="button" class="btn btn-outline-primary btn-lg w-100" id="btnSaveDraftMobile">
                    <i class="bi bi-save me-2"></i>
                    Enregistrer (brouillon)
                </button>
                <button type="button" class="btn btn-primary btn-lg w-100" id="btnSubmitMobile">
                    <i class="bi bi-send-check me-2"></i>
                    Soumettre
                </button>
            </div>
        </div>

        <!-- Boutons submit desktop -->
        <div class="d-none d-md-block mt-4">
            <div class="d-flex justify-content-end gap-3">
                <a href="<?= url_for('commercial/reporting_terrain/show.php?id=' . $id) ?>" class="btn btn-outline-secondary btn-lg">
                    <i class="bi bi-x-lg me-1"></i>
                    Annuler
                </a>
                <button type="button" class="btn btn-outline-primary btn-lg" id="btnSaveDraftDesktop">
                    <i class="bi bi-save me-2"></i>
                    Enregistrer (brouillon)
                </button>
                <button type="button" class="btn btn-primary btn-lg" id="btnSubmitDesktop">
                    <i class="bi bi-send-check me-2"></i>
                    Soumettre
                </button>
            </div>
        </div>

    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Calcul automatique des écarts
    function updateEcart(indicateur) {
        const objInput = document.querySelector(`.obj-input[data-indicateur="${indicateur}"]`);
        const realInput = document.querySelector(`.real-input[data-indicateur="${indicateur}"]`);
        const ecartDisplay = document.getElementById(`ecart_${indicateur}`);
        
        if (objInput && realInput && ecartDisplay) {
            const obj = parseFloat(objInput.value) || 0;
            const real = parseFloat(realInput.value) || 0;
            const ecart = real - obj;
            ecartDisplay.value = ecart.toLocaleString('fr-FR');
            ecartDisplay.style.color = ecart >= 0 ? 'green' : 'red';
        }
    }

    // Attach event listeners
    document.querySelectorAll('.obj-input, .real-input').forEach(input => {
        input.addEventListener('input', function() {
            updateEcart(this.dataset.indicateur);
        });
    });

    // Compteur de caractères synthèse
    const synthese = document.getElementById('synthese');
    const syntheseCount = document.getElementById('synthese-count');
    if (synthese && syntheseCount) {
        synthese.addEventListener('input', function() {
            syntheseCount.textContent = this.value.length;
            if (this.value.length > 900) {
                syntheseCount.style.color = 'red';
            } else {
                syntheseCount.style.color = '';
            }
        });
        // Initialiser le compteur au chargement
        syntheseCount.textContent = synthese.value.length;
    }

    // Validation avant submit
    document.getElementById('formReporting').addEventListener('submit', function(e) {
        const synthese = document.getElementById('synthese');
        if (synthese && synthese.value.length > 900) {
            e.preventDefault();
            alert('La synthèse ne doit pas dépasser 900 caractères.');
            return false;
        }
    });

    // Gestion des actions: brouillon vs soumettre
    const form = document.getElementById('formReporting');
    const actionInput = document.getElementById('formAction');
    const bindAction = (btn, action) => {
        if (!btn) return;
        btn.addEventListener('click', function() {
            actionInput.value = action;
            form.requestSubmit();
        });
    };
    bindAction(document.getElementById('btnSaveDraftMobile'), 'save');
    bindAction(document.getElementById('btnSubmitMobile'), 'submit');
    bindAction(document.getElementById('btnSaveDraftDesktop'), 'save');
    bindAction(document.getElementById('btnSubmitDesktop'), 'submit');

    // Calculer les écarts au chargement
    document.querySelectorAll('.obj-input').forEach(input => {
        updateEcart(input.dataset.indicateur);
    });
});
</script>

<?php include __DIR__ . '/../../partials/footer.php'; ?>
