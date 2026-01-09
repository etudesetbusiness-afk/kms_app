<?php
// terrain/reporting/edit.php - Formulaire de rapport hebdomadaire terrain (mobile-first)
require_once __DIR__ . '/../../security.php';
exigerConnexion();
exigerPermission('CLIENTS_CREER');

global $pdo;

$utilisateur  = utilisateurConnecte();
$userId       = (int)$utilisateur['id'];
$csrfToken    = getCsrfToken();
$erreurs      = [];

$voirTout = peutVoirToutesProspections();

// Mode édition ou création
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $id > 0;

// Paramètres pour nouveau rapport
$semaineParam = isset($_GET['semaine']) ? (int)$_GET['semaine'] : (int)date('W');
$anneeParam   = isset($_GET['annee']) ? (int)$_GET['annee'] : (int)date('Y');

// Données par défaut
$rapport = [
    'id'              => 0,
    'commercial_id'   => $userId,
    'numero_semaine'  => $semaineParam,
    'annee'           => $anneeParam,
    'date_debut'      => null,
    'date_fin'        => null,
    'objectif_ca'     => 0,
    'objectif_visites'=> 0,
    'objectif_rdv'    => 0,
    'synthese'        => '',
    'points_forts'    => '',
    'difficultes'     => '',
    'besoins_support' => '',
    'statut'          => 'BROUILLON',
];

// Calcul des dates de la semaine
function getWeekDates(int $week, int $year): array {
    $dto = new DateTime();
    $dto->setISODate($year, $week);
    $start = $dto->format('Y-m-d');
    $dto->modify('+5 days'); // Lundi → Samedi
    $end = $dto->format('Y-m-d');
    return [$start, $end];
}

if ($isEdit) {
    // Charger le rapport existant
    $stmt = $pdo->prepare("SELECT * FROM reporting_terrain WHERE id = ?");
    $stmt->execute([$id]);
    $rapport = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$rapport) {
        $_SESSION['flash_error'] = "Rapport introuvable.";
        header('Location: ' . url_for('terrain/reporting/list.php'));
        exit;
    }
    
    // Vérifier les droits d'accès
    if (!$voirTout && $rapport['commercial_id'] != $userId) {
        $_SESSION['flash_error'] = "Vous n'avez pas accès à ce rapport.";
        header('Location: ' . url_for('terrain/reporting/list.php'));
        exit;
    }
    
    // Vérifier si modifiable
    if (!in_array($rapport['statut'], ['BROUILLON', 'REJETE'])) {
        $_SESSION['flash_error'] = "Ce rapport ne peut plus être modifié.";
        header('Location: ' . url_for('terrain/reporting/view.php?id=' . $id));
        exit;
    }
} else {
    // Nouveau rapport : calculer les dates
    [$dateDebut, $dateFin] = getWeekDates($semaineParam, $anneeParam);
    $rapport['date_debut'] = $dateDebut;
    $rapport['date_fin']   = $dateFin;
    
    // Vérifier si rapport existe déjà pour cette semaine
    $stmtCheck = $pdo->prepare("
        SELECT id FROM reporting_terrain 
        WHERE commercial_id = ? AND annee = ? AND numero_semaine = ?
    ");
    $stmtCheck->execute([$userId, $anneeParam, $semaineParam]);
    $existing = $stmtCheck->fetch(PDO::FETCH_ASSOC);
    if ($existing) {
        header('Location: ' . url_for('terrain/reporting/edit.php?id=' . $existing['id']));
        exit;
    }
}

// Charger les données liées (zones, journal, produits, objections, arguments, actions)
$zones      = [];
$journal    = [];
$produits   = [];
$objections = [];
$arguments  = [];
$actions    = [];

if ($isEdit) {
    $zones = $pdo->prepare("SELECT * FROM reporting_terrain_zones WHERE reporting_id = ? ORDER BY id");
    $zones->execute([$id]);
    $zones = $zones->fetchAll(PDO::FETCH_ASSOC);
    
    $journal = $pdo->prepare("SELECT * FROM reporting_terrain_journal WHERE reporting_id = ? ORDER BY jour_semaine");
    $journal->execute([$id]);
    $journal = $journal->fetchAll(PDO::FETCH_ASSOC);
    
    $produits = $pdo->prepare("SELECT * FROM reporting_terrain_produits WHERE reporting_id = ? ORDER BY id");
    $produits->execute([$id]);
    $produits = $produits->fetchAll(PDO::FETCH_ASSOC);
    
    $objections = $pdo->prepare("SELECT * FROM reporting_terrain_objections WHERE reporting_id = ? ORDER BY id");
    $objections->execute([$id]);
    $objections = $objections->fetchAll(PDO::FETCH_ASSOC);
    
    $arguments = $pdo->prepare("SELECT * FROM reporting_terrain_arguments WHERE reporting_id = ? ORDER BY id");
    $arguments->execute([$id]);
    $arguments = $arguments->fetchAll(PDO::FETCH_ASSOC);
    
    $actions = $pdo->prepare("SELECT * FROM reporting_terrain_actions WHERE reporting_id = ? ORDER BY id");
    $actions->execute([$id]);
    $actions = $actions->fetchAll(PDO::FETCH_ASSOC);
}

// Initialiser le journal pour 6 jours si vide
if (empty($journal)) {
    $joursSemaine = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
    foreach ($joursSemaine as $i => $jour) {
        $journal[] = [
            'id' => 0,
            'jour_semaine' => $i + 1,
            'jour_label' => $jour,
            'nb_visites' => 0,
            'nb_rdv' => 0,
            'nb_devis' => 0,
            'nb_ventes' => 0,
            'ca_realise' => 0,
            'zone_couverte' => '',
            'remarques' => ''
        ];
    }
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifierCsrf($_POST['csrf_token'] ?? '');
    
    $action = $_POST['action'] ?? 'save';
    
    // Données principales
    $rapport['objectif_ca']      = (float)($_POST['objectif_ca'] ?? 0);
    $rapport['objectif_visites'] = (int)($_POST['objectif_visites'] ?? 0);
    $rapport['objectif_rdv']     = (int)($_POST['objectif_rdv'] ?? 0);
    $rapport['synthese']         = trim($_POST['synthese'] ?? '');
    $rapport['points_forts']     = trim($_POST['points_forts'] ?? '');
    $rapport['difficultes']      = trim($_POST['difficultes'] ?? '');
    $rapport['besoins_support']  = trim($_POST['besoins_support'] ?? '');
    
    // Validation
    if ($action === 'submit') {
        if (empty($rapport['synthese'])) {
            $erreurs[] = "La synthèse est obligatoire pour soumettre le rapport.";
        }
    }
    
    if (empty($erreurs)) {
        try {
            $pdo->beginTransaction();
            
            if (!$isEdit) {
                // Créer le rapport
                $stmt = $pdo->prepare("
                    INSERT INTO reporting_terrain 
                    (commercial_id, numero_semaine, annee, date_debut, date_fin, 
                     objectif_ca, objectif_visites, objectif_rdv, 
                     synthese, points_forts, difficultes, besoins_support, statut)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $userId, $rapport['numero_semaine'], $rapport['annee'],
                    $rapport['date_debut'], $rapport['date_fin'],
                    $rapport['objectif_ca'], $rapport['objectif_visites'], $rapport['objectif_rdv'],
                    $rapport['synthese'], $rapport['points_forts'], 
                    $rapport['difficultes'], $rapport['besoins_support'],
                    'BROUILLON'
                ]);
                $id = $pdo->lastInsertId();
                $rapport['id'] = $id;
            } else {
                // Mettre à jour
                $stmt = $pdo->prepare("
                    UPDATE reporting_terrain SET
                        objectif_ca = ?, objectif_visites = ?, objectif_rdv = ?,
                        synthese = ?, points_forts = ?, difficultes = ?, besoins_support = ?,
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([
                    $rapport['objectif_ca'], $rapport['objectif_visites'], $rapport['objectif_rdv'],
                    $rapport['synthese'], $rapport['points_forts'], 
                    $rapport['difficultes'], $rapport['besoins_support'],
                    $id
                ]);
            }
            
            // Sauvegarder les zones
            $pdo->prepare("DELETE FROM reporting_terrain_zones WHERE reporting_id = ?")->execute([$id]);
            if (!empty($_POST['zone_nom'])) {
                $stmtZone = $pdo->prepare("
                    INSERT INTO reporting_terrain_zones (reporting_id, nom_zone, type_cible, potentiel, remarques)
                    VALUES (?, ?, ?, ?, ?)
                ");
                foreach ($_POST['zone_nom'] as $i => $nom) {
                    if (trim($nom) !== '') {
                        $stmtZone->execute([
                            $id,
                            trim($nom),
                            $_POST['zone_type_cible'][$i] ?? '',
                            $_POST['zone_potentiel'][$i] ?? '',
                            $_POST['zone_remarques'][$i] ?? ''
                        ]);
                    }
                }
            }
            
            // Sauvegarder le journal (6 jours)
            $pdo->prepare("DELETE FROM reporting_terrain_journal WHERE reporting_id = ?")->execute([$id]);
            if (!empty($_POST['jour_semaine'])) {
                $stmtJour = $pdo->prepare("
                    INSERT INTO reporting_terrain_journal 
                    (reporting_id, jour_semaine, nb_visites, nb_rdv, nb_devis, nb_ventes, ca_realise, zone_couverte, remarques)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                foreach ($_POST['jour_semaine'] as $i => $jour) {
                    $stmtJour->execute([
                        $id,
                        (int)$jour,
                        (int)($_POST['jour_nb_visites'][$i] ?? 0),
                        (int)($_POST['jour_nb_rdv'][$i] ?? 0),
                        (int)($_POST['jour_nb_devis'][$i] ?? 0),
                        (int)($_POST['jour_nb_ventes'][$i] ?? 0),
                        (float)($_POST['jour_ca'][$i] ?? 0),
                        $_POST['jour_zone'][$i] ?? '',
                        $_POST['jour_remarques'][$i] ?? ''
                    ]);
                }
            }
            
            // Sauvegarder les produits vendus
            $pdo->prepare("DELETE FROM reporting_terrain_produits WHERE reporting_id = ?")->execute([$id]);
            if (!empty($_POST['produit_nom'])) {
                $stmtProd = $pdo->prepare("
                    INSERT INTO reporting_terrain_produits (reporting_id, produit_nom, quantite, montant_total, type_client, remarques)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                foreach ($_POST['produit_nom'] as $i => $nom) {
                    if (trim($nom) !== '') {
                        $stmtProd->execute([
                            $id,
                            trim($nom),
                            (int)($_POST['produit_qte'][$i] ?? 1),
                            (float)($_POST['produit_montant'][$i] ?? 0),
                            $_POST['produit_type_client'][$i] ?? '',
                            $_POST['produit_remarques'][$i] ?? ''
                        ]);
                    }
                }
            }
            
            // Sauvegarder les objections
            $pdo->prepare("DELETE FROM reporting_terrain_objections WHERE reporting_id = ?")->execute([$id]);
            if (!empty($_POST['objection_texte'])) {
                $stmtObj = $pdo->prepare("
                    INSERT INTO reporting_terrain_objections (reporting_id, objection, reponse_apportee, frequence)
                    VALUES (?, ?, ?, ?)
                ");
                foreach ($_POST['objection_texte'] as $i => $obj) {
                    if (trim($obj) !== '') {
                        $stmtObj->execute([
                            $id,
                            trim($obj),
                            $_POST['objection_reponse'][$i] ?? '',
                            $_POST['objection_frequence'][$i] ?? 'MOYENNE'
                        ]);
                    }
                }
            }
            
            // Sauvegarder les arguments
            $pdo->prepare("DELETE FROM reporting_terrain_arguments WHERE reporting_id = ?")->execute([$id]);
            if (!empty($_POST['argument_texte'])) {
                $stmtArg = $pdo->prepare("
                    INSERT INTO reporting_terrain_arguments (reporting_id, argument, efficacite, contexte)
                    VALUES (?, ?, ?, ?)
                ");
                foreach ($_POST['argument_texte'] as $i => $arg) {
                    if (trim($arg) !== '') {
                        $stmtArg->execute([
                            $id,
                            trim($arg),
                            $_POST['argument_efficacite'][$i] ?? 'MOYENNE',
                            $_POST['argument_contexte'][$i] ?? ''
                        ]);
                    }
                }
            }
            
            // Sauvegarder les actions
            $pdo->prepare("DELETE FROM reporting_terrain_actions WHERE reporting_id = ?")->execute([$id]);
            if (!empty($_POST['action_description'])) {
                $stmtAct = $pdo->prepare("
                    INSERT INTO reporting_terrain_actions (reporting_id, description, echeance, priorite, statut)
                    VALUES (?, ?, ?, ?, ?)
                ");
                foreach ($_POST['action_description'] as $i => $desc) {
                    if (trim($desc) !== '') {
                        $stmtAct->execute([
                            $id,
                            trim($desc),
                            $_POST['action_echeance'][$i] ?: null,
                            $_POST['action_priorite'][$i] ?? 'NORMALE',
                            'A_FAIRE'
                        ]);
                    }
                }
            }
            
            // Soumettre si demandé
            if ($action === 'submit') {
                $pdo->prepare("UPDATE reporting_terrain SET statut = 'SOUMIS', submitted_at = NOW() WHERE id = ?")
                    ->execute([$id]);
            }
            
            $pdo->commit();
            
            if ($action === 'submit') {
                $_SESSION['flash_success'] = "Rapport S{$rapport['numero_semaine']} soumis avec succès.";
                header('Location: ' . url_for('terrain/reporting/list.php'));
            } else {
                $_SESSION['flash_success'] = "Rapport sauvegardé.";
                header('Location: ' . url_for('terrain/reporting/edit.php?id=' . $id));
            }
            exit;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $erreurs[] = "Erreur lors de la sauvegarde : " . $e->getMessage();
        }
    }
}

$joursSemaine = ['', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];

require_once __DIR__ . '/../../templates/header.php';
?>

<style>
.accordion-button:not(.collapsed) { background-color: #e7f1ff; }
.form-section { border-left: 3px solid #0d6efd; padding-left: 1rem; margin-bottom: 1rem; }
.dynamic-row { background: #f8f9fa; border-radius: 0.5rem; padding: 0.75rem; margin-bottom: 0.5rem; }
.btn-add-row { border-style: dashed; }
@media (max-width: 768px) {
    .table-responsive-stack td { display: block; text-align: left; }
    .table-responsive-stack td::before { content: attr(data-label); font-weight: bold; display: block; }
}
</style>

<div class="container-fluid py-3">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
        <div>
            <a href="<?= url_for('terrain/reporting/list.php') ?>" class="text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i>Retour
            </a>
            <h4 class="mb-0 mt-2">
                <i class="bi bi-journal-text me-2"></i>
                Rapport Semaine <?= $rapport['numero_semaine'] ?> / <?= $rapport['annee'] ?>
            </h4>
            <small class="text-muted">
                <?= $rapport['date_debut'] ? date('d/m/Y', strtotime($rapport['date_debut'])) : '' ?>
                → <?= $rapport['date_fin'] ? date('d/m/Y', strtotime($rapport['date_fin'])) : '' ?>
            </small>
        </div>
        <div>
            <span class="badge bg-<?= $rapport['statut'] === 'BROUILLON' ? 'secondary' : 'primary' ?>">
                <?= $rapport['statut'] ?>
            </span>
        </div>
    </div>

    <!-- Erreurs -->
    <?php if (!empty($erreurs)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($erreurs as $err): ?>
                    <li><?= htmlspecialchars($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" id="reportForm">
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
        
        <div class="accordion" id="accordionReport">
            
            <!-- Section 1: Objectifs -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" 
                            data-bs-target="#collapseObjectifs">
                        <i class="bi bi-bullseye me-2"></i>Objectifs de la semaine
                    </button>
                </h2>
                <div id="collapseObjectifs" class="accordion-collapse collapse show">
                    <div class="accordion-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Objectif CA (FCFA)</label>
                                <input type="number" name="objectif_ca" class="form-control" 
                                       value="<?= (int)$rapport['objectif_ca'] ?>" min="0" step="1000">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Objectif visites</label>
                                <input type="number" name="objectif_visites" class="form-control" 
                                       value="<?= (int)$rapport['objectif_visites'] ?>" min="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Objectif RDV</label>
                                <input type="number" name="objectif_rdv" class="form-control" 
                                       value="<?= (int)$rapport['objectif_rdv'] ?>" min="0">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Section 2: Zones et Cibles -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                            data-bs-target="#collapseZones">
                        <i class="bi bi-geo-alt me-2"></i>Zones et cibles
                    </button>
                </h2>
                <div id="collapseZones" class="accordion-collapse collapse">
                    <div class="accordion-body">
                        <div id="zonesContainer">
                            <?php 
                            $zonesData = !empty($zones) ? $zones : [['nom_zone' => '', 'type_cible' => '', 'potentiel' => '', 'remarques' => '']];
                            foreach ($zonesData as $i => $z): 
                            ?>
                            <div class="dynamic-row zone-row">
                                <div class="row g-2">
                                    <div class="col-md-3">
                                        <input type="text" name="zone_nom[]" class="form-control form-control-sm" 
                                               placeholder="Nom de la zone" value="<?= htmlspecialchars($z['nom_zone'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <select name="zone_type_cible[]" class="form-select form-select-sm">
                                            <option value="">Type de cible</option>
                                            <option value="ENTREPRISE" <?= ($z['type_cible'] ?? '') === 'ENTREPRISE' ? 'selected' : '' ?>>Entreprises</option>
                                            <option value="PARTICULIER" <?= ($z['type_cible'] ?? '') === 'PARTICULIER' ? 'selected' : '' ?>>Particuliers</option>
                                            <option value="REVENDEUR" <?= ($z['type_cible'] ?? '') === 'REVENDEUR' ? 'selected' : '' ?>>Revendeurs</option>
                                            <option value="MIXTE" <?= ($z['type_cible'] ?? '') === 'MIXTE' ? 'selected' : '' ?>>Mixte</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <select name="zone_potentiel[]" class="form-select form-select-sm">
                                            <option value="">Potentiel</option>
                                            <option value="FORT" <?= ($z['potentiel'] ?? '') === 'FORT' ? 'selected' : '' ?>>Fort</option>
                                            <option value="MOYEN" <?= ($z['potentiel'] ?? '') === 'MOYEN' ? 'selected' : '' ?>>Moyen</option>
                                            <option value="FAIBLE" <?= ($z['potentiel'] ?? '') === 'FAIBLE' ? 'selected' : '' ?>>Faible</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <input type="text" name="zone_remarques[]" class="form-control form-control-sm" 
                                               placeholder="Remarques" value="<?= htmlspecialchars($z['remarques'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-1">
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row" 
                                                onclick="this.closest('.zone-row').remove()">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary btn-add-row mt-2" 
                                onclick="addZoneRow()">
                            <i class="bi bi-plus me-1"></i>Ajouter une zone
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Section 3: Journal quotidien -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                            data-bs-target="#collapseJournal">
                        <i class="bi bi-calendar-week me-2"></i>Suivi journalier
                    </button>
                </h2>
                <div id="collapseJournal" class="accordion-collapse collapse">
                    <div class="accordion-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Jour</th>
                                        <th class="text-center" style="width:60px">Visites</th>
                                        <th class="text-center" style="width:60px">RDV</th>
                                        <th class="text-center" style="width:60px">Devis</th>
                                        <th class="text-center" style="width:60px">Ventes</th>
                                        <th style="width:120px">CA (F)</th>
                                        <th>Zone</th>
                                        <th>Remarques</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($journal as $j): ?>
                                    <tr>
                                        <td>
                                            <strong><?= $joursSemaine[$j['jour_semaine']] ?? $j['jour_label'] ?? '' ?></strong>
                                            <input type="hidden" name="jour_semaine[]" value="<?= $j['jour_semaine'] ?>">
                                        </td>
                                        <td><input type="number" name="jour_nb_visites[]" class="form-control form-control-sm text-center" 
                                                   value="<?= (int)$j['nb_visites'] ?>" min="0"></td>
                                        <td><input type="number" name="jour_nb_rdv[]" class="form-control form-control-sm text-center" 
                                                   value="<?= (int)$j['nb_rdv'] ?>" min="0"></td>
                                        <td><input type="number" name="jour_nb_devis[]" class="form-control form-control-sm text-center" 
                                                   value="<?= (int)$j['nb_devis'] ?>" min="0"></td>
                                        <td><input type="number" name="jour_nb_ventes[]" class="form-control form-control-sm text-center" 
                                                   value="<?= (int)$j['nb_ventes'] ?>" min="0"></td>
                                        <td><input type="number" name="jour_ca[]" class="form-control form-control-sm" 
                                                   value="<?= (int)$j['ca_realise'] ?>" min="0" step="100"></td>
                                        <td><input type="text" name="jour_zone[]" class="form-control form-control-sm" 
                                                   value="<?= htmlspecialchars($j['zone_couverte'] ?? '') ?>" placeholder="Zone"></td>
                                        <td><input type="text" name="jour_remarques[]" class="form-control form-control-sm" 
                                                   value="<?= htmlspecialchars($j['remarques'] ?? '') ?>" placeholder="Notes"></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Section 4: Produits vendus -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                            data-bs-target="#collapseProduits">
                        <i class="bi bi-box-seam me-2"></i>Produits vendus
                    </button>
                </h2>
                <div id="collapseProduits" class="accordion-collapse collapse">
                    <div class="accordion-body">
                        <div id="produitsContainer">
                            <?php 
                            $produitsData = !empty($produits) ? $produits : array_fill(0, 4, ['produit_nom' => '', 'quantite' => '', 'montant_total' => '', 'type_client' => '', 'remarques' => '']);
                            foreach ($produitsData as $i => $p): 
                            ?>
                            <div class="dynamic-row produit-row">
                                <div class="row g-2">
                                    <div class="col-md-3">
                                        <input type="text" name="produit_nom[]" class="form-control form-control-sm" 
                                               placeholder="Produit" value="<?= htmlspecialchars($p['produit_nom'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-2">
                                        <input type="number" name="produit_qte[]" class="form-control form-control-sm" 
                                               placeholder="Qté" value="<?= (int)($p['quantite'] ?? 0) ?>" min="0">
                                    </div>
                                    <div class="col-md-2">
                                        <input type="number" name="produit_montant[]" class="form-control form-control-sm" 
                                               placeholder="Montant" value="<?= (int)($p['montant_total'] ?? 0) ?>" min="0">
                                    </div>
                                    <div class="col-md-2">
                                        <select name="produit_type_client[]" class="form-select form-select-sm">
                                            <option value="">Type client</option>
                                            <option value="NOUVEAU" <?= ($p['type_client'] ?? '') === 'NOUVEAU' ? 'selected' : '' ?>>Nouveau</option>
                                            <option value="EXISTANT" <?= ($p['type_client'] ?? '') === 'EXISTANT' ? 'selected' : '' ?>>Existant</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <input type="text" name="produit_remarques[]" class="form-control form-control-sm" 
                                               placeholder="Notes" value="<?= htmlspecialchars($p['remarques'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-1">
                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                onclick="this.closest('.produit-row').remove()">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary btn-add-row mt-2" 
                                onclick="addProduitRow()">
                            <i class="bi bi-plus me-1"></i>Ajouter un produit
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Section 5: Objections rencontrées -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                            data-bs-target="#collapseObjections">
                        <i class="bi bi-chat-left-text me-2"></i>Objections rencontrées
                    </button>
                </h2>
                <div id="collapseObjections" class="accordion-collapse collapse">
                    <div class="accordion-body">
                        <div id="objectionsContainer">
                            <?php 
                            $objectionsData = !empty($objections) ? $objections : [['objection' => '', 'reponse_apportee' => '', 'frequence' => '']];
                            foreach ($objectionsData as $i => $o): 
                            ?>
                            <div class="dynamic-row objection-row">
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <input type="text" name="objection_texte[]" class="form-control form-control-sm" 
                                               placeholder="Objection client" value="<?= htmlspecialchars($o['objection'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <input type="text" name="objection_reponse[]" class="form-control form-control-sm" 
                                               placeholder="Réponse apportée" value="<?= htmlspecialchars($o['reponse_apportee'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <select name="objection_frequence[]" class="form-select form-select-sm">
                                            <option value="FAIBLE" <?= ($o['frequence'] ?? '') === 'FAIBLE' ? 'selected' : '' ?>>Rare</option>
                                            <option value="MOYENNE" <?= ($o['frequence'] ?? '') === 'MOYENNE' ? 'selected' : '' ?>>Fréquente</option>
                                            <option value="FORTE" <?= ($o['frequence'] ?? '') === 'FORTE' ? 'selected' : '' ?>>Très fréquente</option>
                                        </select>
                                    </div>
                                    <div class="col-md-1">
                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                onclick="this.closest('.objection-row').remove()">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary btn-add-row mt-2" 
                                onclick="addObjectionRow()">
                            <i class="bi bi-plus me-1"></i>Ajouter une objection
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Section 6: Arguments efficaces -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                            data-bs-target="#collapseArguments">
                        <i class="bi bi-lightbulb me-2"></i>Arguments efficaces
                    </button>
                </h2>
                <div id="collapseArguments" class="accordion-collapse collapse">
                    <div class="accordion-body">
                        <div id="argumentsContainer">
                            <?php 
                            $argumentsData = !empty($arguments) ? $arguments : [['argument' => '', 'efficacite' => '', 'contexte' => '']];
                            foreach ($argumentsData as $i => $a): 
                            ?>
                            <div class="dynamic-row argument-row">
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <input type="text" name="argument_texte[]" class="form-control form-control-sm" 
                                               placeholder="Argument de vente" value="<?= htmlspecialchars($a['argument'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <select name="argument_efficacite[]" class="form-select form-select-sm">
                                            <option value="FAIBLE" <?= ($a['efficacite'] ?? '') === 'FAIBLE' ? 'selected' : '' ?>>Peu efficace</option>
                                            <option value="MOYENNE" <?= ($a['efficacite'] ?? '') === 'MOYENNE' ? 'selected' : '' ?>>Efficace</option>
                                            <option value="FORTE" <?= ($a['efficacite'] ?? '') === 'FORTE' ? 'selected' : '' ?>>Très efficace</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <input type="text" name="argument_contexte[]" class="form-control form-control-sm" 
                                               placeholder="Contexte d'utilisation" value="<?= htmlspecialchars($a['contexte'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-1">
                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                onclick="this.closest('.argument-row').remove()">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary btn-add-row mt-2" 
                                onclick="addArgumentRow()">
                            <i class="bi bi-plus me-1"></i>Ajouter un argument
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Section 7: Plan d'actions -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                            data-bs-target="#collapseActions">
                        <i class="bi bi-check2-square me-2"></i>Plan d'actions
                    </button>
                </h2>
                <div id="collapseActions" class="accordion-collapse collapse">
                    <div class="accordion-body">
                        <div id="actionsContainer">
                            <?php 
                            $actionsData = !empty($actions) ? $actions : [['description' => '', 'echeance' => '', 'priorite' => '']];
                            foreach ($actionsData as $i => $act): 
                            ?>
                            <div class="dynamic-row action-row">
                                <div class="row g-2">
                                    <div class="col-md-5">
                                        <input type="text" name="action_description[]" class="form-control form-control-sm" 
                                               placeholder="Action à réaliser" value="<?= htmlspecialchars($act['description'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <input type="date" name="action_echeance[]" class="form-control form-control-sm" 
                                               value="<?= $act['echeance'] ?? '' ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <select name="action_priorite[]" class="form-select form-select-sm">
                                            <option value="BASSE" <?= ($act['priorite'] ?? '') === 'BASSE' ? 'selected' : '' ?>>Basse</option>
                                            <option value="NORMALE" <?= ($act['priorite'] ?? '') === 'NORMALE' ? 'selected' : '' ?>>Normale</option>
                                            <option value="HAUTE" <?= ($act['priorite'] ?? '') === 'HAUTE' ? 'selected' : '' ?>>Haute</option>
                                            <option value="URGENTE" <?= ($act['priorite'] ?? '') === 'URGENTE' ? 'selected' : '' ?>>Urgente</option>
                                        </select>
                                    </div>
                                    <div class="col-md-1">
                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                onclick="this.closest('.action-row').remove()">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary btn-add-row mt-2" 
                                onclick="addActionRow()">
                            <i class="bi bi-plus me-1"></i>Ajouter une action
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Section 8: Synthèse -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                            data-bs-target="#collapseSynthese">
                        <i class="bi bi-file-text me-2"></i>Synthèse de la semaine
                    </button>
                </h2>
                <div id="collapseSynthese" class="accordion-collapse collapse">
                    <div class="accordion-body">
                        <div class="mb-3">
                            <label class="form-label">Synthèse globale <span class="text-danger">*</span></label>
                            <textarea name="synthese" class="form-control" rows="4" 
                                      placeholder="Résumé de la semaine, points clés, observations..."><?= htmlspecialchars($rapport['synthese'] ?? '') ?></textarea>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Points forts</label>
                                <textarea name="points_forts" class="form-control" rows="3" 
                                          placeholder="Ce qui a bien fonctionné..."><?= htmlspecialchars($rapport['points_forts'] ?? '') ?></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Difficultés rencontrées</label>
                                <textarea name="difficultes" class="form-control" rows="3" 
                                          placeholder="Obstacles, problèmes..."><?= htmlspecialchars($rapport['difficultes'] ?? '') ?></textarea>
                            </div>
                        </div>
                        <div class="mt-3">
                            <label class="form-label">Besoins de support</label>
                            <textarea name="besoins_support" class="form-control" rows="2" 
                                      placeholder="Formation, outils, accompagnement..."><?= htmlspecialchars($rapport['besoins_support'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Actions -->
        <div class="card mt-3">
            <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <a href="<?= url_for('terrain/reporting/list.php') ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-x-lg me-1"></i>Annuler
                    </a>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" name="action" value="save" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i>Enregistrer brouillon
                    </button>
                    <button type="submit" name="action" value="submit" class="btn btn-success"
                            onclick="return confirm('Soumettre ce rapport ? Il ne pourra plus être modifié.')">
                        <i class="bi bi-send me-1"></i>Soumettre
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
// Templates pour les lignes dynamiques
function addZoneRow() {
    const container = document.getElementById('zonesContainer');
    const html = `
        <div class="dynamic-row zone-row">
            <div class="row g-2">
                <div class="col-md-3">
                    <input type="text" name="zone_nom[]" class="form-control form-control-sm" placeholder="Nom de la zone">
                </div>
                <div class="col-md-3">
                    <select name="zone_type_cible[]" class="form-select form-select-sm">
                        <option value="">Type de cible</option>
                        <option value="ENTREPRISE">Entreprises</option>
                        <option value="PARTICULIER">Particuliers</option>
                        <option value="REVENDEUR">Revendeurs</option>
                        <option value="MIXTE">Mixte</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="zone_potentiel[]" class="form-select form-select-sm">
                        <option value="">Potentiel</option>
                        <option value="FORT">Fort</option>
                        <option value="MOYEN">Moyen</option>
                        <option value="FAIBLE">Faible</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="text" name="zone_remarques[]" class="form-control form-control-sm" placeholder="Remarques">
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.zone-row').remove()">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        </div>`;
    container.insertAdjacentHTML('beforeend', html);
}

function addProduitRow() {
    const container = document.getElementById('produitsContainer');
    const html = `
        <div class="dynamic-row produit-row">
            <div class="row g-2">
                <div class="col-md-3">
                    <input type="text" name="produit_nom[]" class="form-control form-control-sm" placeholder="Produit">
                </div>
                <div class="col-md-2">
                    <input type="number" name="produit_qte[]" class="form-control form-control-sm" placeholder="Qté" min="0">
                </div>
                <div class="col-md-2">
                    <input type="number" name="produit_montant[]" class="form-control form-control-sm" placeholder="Montant" min="0">
                </div>
                <div class="col-md-2">
                    <select name="produit_type_client[]" class="form-select form-select-sm">
                        <option value="">Type client</option>
                        <option value="NOUVEAU">Nouveau</option>
                        <option value="EXISTANT">Existant</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="text" name="produit_remarques[]" class="form-control form-control-sm" placeholder="Notes">
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.produit-row').remove()">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        </div>`;
    container.insertAdjacentHTML('beforeend', html);
}

function addObjectionRow() {
    const container = document.getElementById('objectionsContainer');
    const html = `
        <div class="dynamic-row objection-row">
            <div class="row g-2">
                <div class="col-md-4">
                    <input type="text" name="objection_texte[]" class="form-control form-control-sm" placeholder="Objection client">
                </div>
                <div class="col-md-4">
                    <input type="text" name="objection_reponse[]" class="form-control form-control-sm" placeholder="Réponse apportée">
                </div>
                <div class="col-md-3">
                    <select name="objection_frequence[]" class="form-select form-select-sm">
                        <option value="FAIBLE">Rare</option>
                        <option value="MOYENNE" selected>Fréquente</option>
                        <option value="FORTE">Très fréquente</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.objection-row').remove()">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        </div>`;
    container.insertAdjacentHTML('beforeend', html);
}

function addArgumentRow() {
    const container = document.getElementById('argumentsContainer');
    const html = `
        <div class="dynamic-row argument-row">
            <div class="row g-2">
                <div class="col-md-4">
                    <input type="text" name="argument_texte[]" class="form-control form-control-sm" placeholder="Argument de vente">
                </div>
                <div class="col-md-3">
                    <select name="argument_efficacite[]" class="form-select form-select-sm">
                        <option value="FAIBLE">Peu efficace</option>
                        <option value="MOYENNE" selected>Efficace</option>
                        <option value="FORTE">Très efficace</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <input type="text" name="argument_contexte[]" class="form-control form-control-sm" placeholder="Contexte d'utilisation">
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.argument-row').remove()">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        </div>`;
    container.insertAdjacentHTML('beforeend', html);
}

function addActionRow() {
    const container = document.getElementById('actionsContainer');
    const html = `
        <div class="dynamic-row action-row">
            <div class="row g-2">
                <div class="col-md-5">
                    <input type="text" name="action_description[]" class="form-control form-control-sm" placeholder="Action à réaliser">
                </div>
                <div class="col-md-3">
                    <input type="date" name="action_echeance[]" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <select name="action_priorite[]" class="form-select form-select-sm">
                        <option value="BASSE">Basse</option>
                        <option value="NORMALE" selected>Normale</option>
                        <option value="HAUTE">Haute</option>
                        <option value="URGENTE">Urgente</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.action-row').remove()">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        </div>`;
    container.insertAdjacentHTML('beforeend', html);
}
</script>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
