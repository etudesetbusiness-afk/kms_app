<?php
/**
 * Reporting Terrain - Formulaire de création
 * Module: commercial/reporting_terrain/create.php
 * Mobile-first, sections accordéon Bootstrap
 */

require_once __DIR__ . '/../../security.php';
exigerConnexion();

global $pdo;
$utilisateur = utilisateurConnecte();

// Générer le token CSRF
$csrfToken = getCsrfToken();

// Calculer la semaine courante (Lundi à Samedi)
$today = new DateTime();
$dayOfWeek = (int)$today->format('N'); // 1 = Lundi, 7 = Dimanche
$monday = clone $today;
$monday->modify('-' . ($dayOfWeek - 1) . ' days');
$saturday = clone $monday;
$saturday->modify('+5 days');

$semaine_debut = $monday->format('Y-m-d');
$semaine_fin = $saturday->format('Y-m-d');

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

include __DIR__ . '/../../partials/header.php';
include __DIR__ . '/../../partials/sidebar.php';
?>

<style>
/* Mobile-first styles */
.form-control, .form-select, .btn {
    font-size: 16px !important; /* Prevent zoom on iOS */
}

/* Desktop: normal height, Mobile: larger touch targets */
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
    /* Textareas plus grands sur mobile */
    textarea.form-control {
        min-height: 80px !important;
    }
    /* Inputs de texte pleine largeur sur mobile */
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

/* Tables - Desktop layout */
.table-responsive {
    font-size: 14px;
}
.table input[type="number"],
.table input[type="text"] {
    min-width: 60px;
}

/* ========================================== */
/* MOBILE TABLE STACK - Transformation tables */
/* ========================================== */
@media (max-width: 767.98px) {
    /* Hide table headers on mobile */
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
    
    /* Jour header - make it stand out */
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
    
    /* Inputs pleine largeur sur mobile */
    .table-mobile-stack input,
    .table-mobile-stack select {
        width: 100% !important;
        min-height: 48px !important;
    }
    
    /* Textarea dans table */
    .table-mobile-stack textarea {
        width: 100% !important;
        min-height: 80px !important;
    }
}

/* ========================================== */
/* SECTION OBJECTIONS / ARGUMENTS - MOBILE   */
/* ========================================== */
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

/* Checkboxes bien visibles */
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

/* Mobile: stack checkbox rows vertically */
@media (max-width: 767.98px) {
    .checkbox-row .row {
        flex-direction: column;
    }
    .checkbox-row .row > [class*="col-"] {
        width: 100%;
        max-width: 100%;
        padding: 0.25rem 0;
    }
    /* Checkbox label first, then fields stacked below */
    .checkbox-row .form-check-label {
        font-size: 15px;
    }
    /* Select and input full width with larger size */
    .checkbox-row select,
    .checkbox-row input[type="text"] {
        width: 100% !important;
        min-height: 48px !important;
        margin-top: 0.25rem;
    }
    /* Label for mobile fields */
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

/* ========================================== */
/* PLAN ACTION - MOBILE                       */
/* ========================================== */
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
    /* Zone/Cible textarea larger */
    .card-body input[placeholder="Zone / Cible"] {
        min-height: 60px !important;
    }
}

/* ========================================== */
/* BOUTON SUBMIT FIXE                         */
/* ========================================== */
.section-title {
    background: linear-gradient(135deg, var(--bs-primary) 0%, #4a6cf7 100%);
    color: white;
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
    margin-bottom: 1rem;
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
</style>

<div class="container-fluid py-4 pb-5 mb-5">
    <!-- En-tête -->
    <div class="d-flex align-items-center mb-4">
        <a href="<?= url_for('commercial/reporting_terrain/') ?>" class="btn btn-outline-secondary me-3">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-clipboard-plus text-primary me-2"></i>
                Nouveau Reporting Hebdomadaire
            </h1>
            <p class="text-muted mb-0 small">Activité commerciale terrain</p>
        </div>
    </div>

    <form action="<?= url_for('commercial/reporting_terrain/store.php') ?>" method="POST" id="formReporting">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

        <div class="accordion" id="accordionReporting">

            <!-- ═══════════════════════════════════════════════════════════════ -->
            <!-- SECTION 1: IDENTIFICATION -->
            <!-- ═══════════════════════════════════════════════════════════════ -->
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
                                       value="<?= htmlspecialchars($utilisateur['nom_complet'] ?? $utilisateur['login']) ?>" 
                                       required readonly>
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label fw-semibold">Semaine du</label>
                                <input type="date" class="form-control" name="semaine_debut" 
                                       value="<?= $semaine_debut ?>" required>
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label fw-semibold">Au</label>
                                <input type="date" class="form-control" name="semaine_fin" 
                                       value="<?= $semaine_fin ?>" required>
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold">Ville</label>
                                <input type="text" class="form-control" name="ville" 
                                       placeholder="Ex: Douala, Yaoundé...">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label fw-semibold">Responsable</label>
                                <input type="text" class="form-control" name="responsable_nom" 
                                       placeholder="Nom du responsable">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════════════════════ -->
            <!-- SECTION 2: ZONES & CIBLES COUVERTES -->
            <!-- ═══════════════════════════════════════════════════════════════ -->
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
                                        <th style="width:140px;">Type cible</th>
                                        <th style="width:80px;">Pts visités</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($jours as $jour): ?>
                                    <tr>
                                        <td class="jour-cell"><?= $jour ?></td>
                                        <td data-label="Zone / Quartier">
                                            <input type="text" class="form-control" 
                                                   name="zones[<?= $jour ?>][zone_quartier]" 
                                                   placeholder="Ex: Akwa, Bonamoussadi...">
                                        </td>
                                        <td data-label="Type de cible">
                                            <select class="form-select" name="zones[<?= $jour ?>][type_cible]">
                                                <option value="Quincaillerie">Quincaillerie</option>
                                                <option value="Menuiserie">Menuiserie</option>
                                                <option value="Autre">Autre</option>
                                            </select>
                                        </td>
                                        <td data-label="Points visités">
                                            <input type="number" class="form-control text-center" 
                                                   name="zones[<?= $jour ?>][nb_points]" min="0" value="0">
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════════════════════ -->
            <!-- SECTION 3: SUIVI JOURNALIER -->
            <!-- ═══════════════════════════════════════════════════════════════ -->
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
                                        <th style="width:80px;" class="text-center">Échant.</th>
                                        <th style="width:70px;" class="text-center">Grille</th>
                                        <th style="width:60px;">RDV</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($jours as $jour): ?>
                                    <tr>
                                        <td class="jour-cell"><?= $jour ?></td>
                                        <td data-label="Contacts qualifiés">
                                            <input type="number" class="form-control text-center" 
                                                   name="activite[<?= $jour ?>][contacts_qualifies]" min="0" value="0">
                                        </td>
                                        <td data-label="Décideurs rencontrés">
                                            <input type="number" class="form-control text-center" 
                                                   name="activite[<?= $jour ?>][decideurs_rencontres]" min="0" value="0">
                                        </td>
                                        <td data-label="Échantillons présentés">
                                            <div class="form-check d-flex align-items-center justify-content-center" style="min-height:48px;">
                                                <input type="checkbox" class="form-check-input" 
                                                       name="activite[<?= $jour ?>][echantillons_presentes]" value="1">
                                                <label class="form-check-label ms-2 d-md-none">Oui</label>
                                            </div>
                                        </td>
                                        <td data-label="Grille prix remise">
                                            <div class="form-check d-flex align-items-center justify-content-center" style="min-height:48px;">
                                                <input type="checkbox" class="form-check-input" 
                                                       name="activite[<?= $jour ?>][grille_prix_remise]" value="1">
                                                <label class="form-check-label ms-2 d-md-none">Oui</label>
                                            </div>
                                        </td>
                                        <td data-label="RDV obtenus">
                                            <input type="number" class="form-control text-center" 
                                                   name="activite[<?= $jour ?>][rdv_obtenus]" min="0" value="0">
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <small class="text-muted">Échant. = Échantillons présentés | Grille = Grille prix remise</small>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════════════════════ -->
            <!-- SECTION 4: RÉSULTATS COMMERCIAUX -->
            <!-- ═══════════════════════════════════════════════════════════════ -->
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
                                        <th style="width:100px;">Objectif</th>
                                        <th style="width:100px;">Réalisé</th>
                                        <th style="width:100px;">Écart</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($indicateurs as $code => $label): ?>
                                    <tr>
                                        <td class="jour-cell"><?= $label ?></td>
                                        <td data-label="Objectif">
                                            <input type="number" step="0.01" class="form-control text-end obj-input" 
                                                   name="resultats[<?= $code ?>][objectif]" min="0" value="0" 
                                                   data-indicateur="<?= $code ?>">
                                        </td>
                                        <td data-label="Réalisé">
                                            <input type="number" step="0.01" class="form-control text-end real-input" 
                                                   name="resultats[<?= $code ?>][realise]" min="0" value="0"
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

            <!-- ═══════════════════════════════════════════════════════════════ -->
            <!-- SECTION 5: OBJECTIONS RENCONTRÉES -->
            <!-- ═══════════════════════════════════════════════════════════════ -->
            <div class="accordion-item border-0 shadow-sm mb-3">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#section5">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        5. Objections Rencontrées
                    </button>
                </h2>
                <div id="section5" class="accordion-collapse collapse" data-bs-parent="#accordionReporting">
                    <div class="accordion-body">
                        <?php foreach ($objections_list as $code => $label): ?>
                        <div class="checkbox-row">
                            <div class="row g-2 align-items-center">
                                <div class="col-12">
                                    <div class="form-check d-flex align-items-center">
                                        <input type="checkbox" class="form-check-input objection-check me-2" 
                                               id="obj_<?= $code ?>" name="objections[<?= $code ?>][active]" value="1"
                                               style="flex-shrink: 0;">
                                        <label class="form-check-label" for="obj_<?= $code ?>">
                                            <?= $label ?>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-12 col-md-4">
                                    <span class="mobile-label">Fréquence</span>
                                    <select class="form-select" name="objections[<?= $code ?>][frequence]">
                                        <option value="Faible">Faible</option>
                                        <option value="Moyenne" selected>Moyenne</option>
                                        <option value="Élevée">Élevée</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-8">
                                    <span class="mobile-label">Commentaire</span>
                                    <?php if ($code === 'autre'): ?>
                                        <textarea class="form-control" rows="2"
                                               name="objections[<?= $code ?>][autre_texte]" 
                                               placeholder="Précisez l'objection rencontrée..."></textarea>
                                    <?php else: ?>
                                        <textarea class="form-control" rows="2"
                                               name="objections[<?= $code ?>][commentaire]" 
                                               placeholder="Détails, contexte, remarques..."></textarea>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════════════════════ -->
            <!-- SECTION 6: ARGUMENTS EFFICACES -->
            <!-- ═══════════════════════════════════════════════════════════════ -->
            <div class="accordion-item border-0 shadow-sm mb-3">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#section6">
                        <i class="bi bi-lightbulb me-2"></i>
                        6. Arguments qui ont fonctionné
                    </button>
                </h2>
                <div id="section6" class="accordion-collapse collapse" data-bs-parent="#accordionReporting">
                    <div class="accordion-body">
                        <?php foreach ($arguments_list as $code => $label): ?>
                        <div class="checkbox-row">
                            <div class="row g-2 align-items-center">
                                <div class="col-12">
                                    <div class="form-check d-flex align-items-center">
                                        <input type="checkbox" class="form-check-input me-2" 
                                               id="arg_<?= $code ?>" name="arguments[<?= $code ?>][active]" value="1"
                                               style="flex-shrink: 0;">
                                        <label class="form-check-label" for="arg_<?= $code ?>">
                                            <?= $label ?>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-12 col-md-3">
                                    <span class="mobile-label">Impact</span>
                                    <select class="form-select" name="arguments[<?= $code ?>][impact]">
                                        <option value="Faible">Faible</option>
                                        <option value="Moyen" selected>Moyen</option>
                                        <option value="Fort">Fort</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-9">
                                    <span class="mobile-label">Exemple / Contexte</span>
                                    <?php if ($code === 'autre'): ?>
                                        <textarea class="form-control" rows="2"
                                               name="arguments[<?= $code ?>][autre_texte]" 
                                               placeholder="Précisez l'argument utilisé..."></textarea>
                                    <?php else: ?>
                                        <textarea class="form-control" rows="2"
                                               name="arguments[<?= $code ?>][exemple_contexte]" 
                                               placeholder="Décrivez le contexte, l'exemple concret..."></textarea>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════════════════════ -->
            <!-- SECTION 7: PLAN D'ACTION SEMAINE SUIVANTE -->
            <!-- ═══════════════════════════════════════════════════════════════ -->
            <div class="accordion-item border-0 shadow-sm mb-3">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#section7">
                        <i class="bi bi-bullseye me-2"></i>
                        7. Plan d'Action Semaine Suivante
                    </button>
                </h2>
                <div id="section7" class="accordion-collapse collapse" data-bs-parent="#accordionReporting">
                    <div class="accordion-body">
                        <?php for ($i = 1; $i <= 3; $i++): ?>
                        <div class="card mb-3 border">
                            <div class="card-header bg-light py-2">
                                <strong>Priorité <?= $i ?></strong>
                            </div>
                            <div class="card-body p-3">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Action concrète à mener</label>
                                    <textarea class="form-control" rows="2"
                                           name="plan_action[<?= $i ?>][action_concrete]" 
                                           placeholder="Décrivez l'action à réaliser..."></textarea>
                                </div>
                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <label class="form-label fw-semibold">Zone / Cible</label>
                                        <input type="text" class="form-control" 
                                               name="plan_action[<?= $i ?>][zone_cible]" 
                                               placeholder="Ex: Akwa, Menuiseries...">
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label fw-semibold">Échéance</label>
                                        <input type="date" class="form-control" 
                                               name="plan_action[<?= $i ?>][echeance]">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════════════════════ -->
            <!-- SECTION 8: SYNTHÈSE -->
            <!-- ═══════════════════════════════════════════════════════════════ -->
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
                                  id="synthese"></textarea>
                        <div class="d-flex justify-content-between mt-1">
                            <small class="text-muted">Maximum 5 lignes / 900 caractères</small>
                            <small class="text-muted"><span id="synthese-count">0</span>/900</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══════════════════════════════════════════════════════════════ -->
            <!-- SECTION 9: SIGNATURE -->
            <!-- ═══════════════════════════════════════════════════════════════ -->
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
                                       placeholder="Tapez votre nom pour signer">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div><!-- Fin accordion -->

        <!-- Bouton submit fixe en mobile -->
        <div class="btn-submit-fixed d-md-none">
            <button type="submit" class="btn btn-primary btn-lg w-100">
                <i class="bi bi-check-lg me-2"></i>
                Enregistrer le Reporting
            </button>
        </div>

        <!-- Bouton submit desktop -->
        <div class="d-none d-md-block mt-4">
            <div class="d-flex justify-content-end gap-3">
                <a href="<?= url_for('commercial/reporting_terrain/') ?>" class="btn btn-outline-secondary btn-lg">
                    <i class="bi bi-x-lg me-1"></i>
                    Annuler
                </a>
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="bi bi-check-lg me-2"></i>
                    Enregistrer le Reporting
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
});
</script>

<?php include __DIR__ . '/../../partials/footer.php'; ?>
