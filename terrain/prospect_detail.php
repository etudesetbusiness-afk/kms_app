<?php
require_once __DIR__ . '/../security.php';
exigerConnexion();
exigerPermission('CLIENTS_CREER');

global $pdo;

$utilisateur = utilisateurConnecte();
$userId      = (int)$utilisateur['id'];
$voirTout    = peutVoirToutesProspections();
$isAdmin     = estAdmin();
$csrfToken   = getCsrfToken();

$prospect_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($prospect_id <= 0) {
    ajouterFlash('Prospect introuvable', 'danger');
    header('Location: ' . url_for('terrain/prospections_list.php'));
    exit;
}

// Récupération du prospect
$stmtProspect = $pdo->prepare("
    SELECT p.*, u.nom_complet AS commercial_nom, c.id AS client_existe
    FROM prospections_terrain p
    INNER JOIN utilisateurs u ON p.commercial_id = u.id
    LEFT JOIN clients c ON p.client_id = c.id
    WHERE p.id = ?
");
$stmtProspect->execute([$prospect_id]);
$prospect = $stmtProspect->fetch(PDO::FETCH_ASSOC);

if (!$prospect) {
    ajouterFlash('Prospect introuvable', 'danger');
    header('Location: ' . url_for('terrain/prospections_list.php'));
    exit;
}

// Règle de visibilité : commercial voit uniquement ses propres prospects
if (!$voirTout && $prospect['commercial_id'] != $userId) {
    ajouterFlash('Vous n\'avez pas accès à ce prospect.', 'danger');
    header('Location: ' . url_for('terrain/prospections_list.php'));
    exit;
}

// Traitement réattribution (Admin uniquement)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isAdmin) {
    verifierCsrf($_POST['csrf_token'] ?? '');
    $action = $_POST['action'] ?? '';
    
    if ($action === 'reattribuer') {
        $nouveauCommercialId = (int)($_POST['nouveau_commercial_id'] ?? 0);
        $motif = trim($_POST['motif_reattribution'] ?? '');
        
        if ($nouveauCommercialId > 0 && $nouveauCommercialId !== $prospect['commercial_id']) {
            // Historiser la réattribution
            $stmtHist = $pdo->prepare("
                INSERT INTO prospection_historique 
                (prospection_id, action, ancien_commercial_id, nouveau_commercial_id, effectue_par, commentaire)
                VALUES (?, 'REATTRIBUTION', ?, ?, ?, ?)
            ");
            $stmtHist->execute([
                $prospect_id,
                $prospect['commercial_id'],
                $nouveauCommercialId,
                $userId,
                $motif
            ]);
            
            // Mettre à jour le commercial
            $pdo->prepare("UPDATE prospections_terrain SET commercial_id = ? WHERE id = ?")
                ->execute([$nouveauCommercialId, $prospect_id]);
            
            ajouterFlash('Prospect réattribué avec succès.', 'success');
            header('Location: ' . url_for('terrain/prospect_detail.php?id=' . $prospect_id));
            exit;
        } else {
            ajouterFlash('Veuillez sélectionner un autre commercial.', 'warning');
        }
    }
}

// Liste des commerciaux pour réattribution (Admin)
$commerciaux = [];
if ($isAdmin) {
    $stmtComm = $pdo->query("
        SELECT u.id, u.nom_complet 
        FROM utilisateurs u
        JOIN utilisateur_role ur ON u.id = ur.utilisateur_id
        JOIN roles r ON ur.role_id = r.id
        WHERE r.code IN ('TERRAIN','SHOWROOM','ADMIN') AND u.actif = 1
        ORDER BY u.nom_complet
    ");
    $commerciaux = $stmtComm->fetchAll(PDO::FETCH_ASSOC);
}

// Timeline du prospect
$stmtTimeline = $pdo->prepare("
    SELECT t.*, u.nom_complet AS utilisateur_nom
    FROM prospect_timeline t
    LEFT JOIN utilisateurs u ON t.utilisateur_id = u.id
    WHERE t.prospection_id = ?
    ORDER BY t.date_action DESC
");
$stmtTimeline->execute([$prospect_id]);
$timeline = $stmtTimeline->fetchAll(PDO::FETCH_ASSOC);

// Notes du prospect
$stmtNotes = $pdo->prepare("
    SELECT n.*, u.nom_complet AS utilisateur_nom
    FROM prospect_notes n
    INNER JOIN utilisateurs u ON n.utilisateur_id = u.id
    WHERE n.prospection_id = ?
    ORDER BY n.date_creation DESC
");
$stmtNotes->execute([$prospect_id]);
$notes = $stmtNotes->fetchAll(PDO::FETCH_ASSOC);

// Relances du prospect
$stmtRelances = $pdo->prepare("
    SELECT r.*, u.nom_complet AS utilisateur_nom
    FROM prospect_relances r
    INNER JOIN utilisateurs u ON r.utilisateur_id = u.id
    WHERE r.prospection_id = ?
    ORDER BY r.date_relance_prevue DESC
");
$stmtRelances->execute([$prospect_id]);
$relances = $stmtRelances->fetchAll(PDO::FETCH_ASSOC);

// Devis liés (si client_id existe)
$devis = [];
if ($prospect['client_id']) {
    $stmtDevis = $pdo->prepare("
        SELECT d.*, u.nom_complet AS commercial_nom
        FROM devis d
        INNER JOIN utilisateurs u ON d.utilisateur_id = u.id
        WHERE d.client_id = ?
        ORDER BY d.date_devis DESC
        LIMIT 10
    ");
    $stmtDevis->execute([$prospect['client_id']]);
    $devis = $stmtDevis->fetchAll(PDO::FETCH_ASSOC);
}

// Ventes liées (si client_id existe)
$ventes = [];
if ($prospect['client_id']) {
    $stmtVentes = $pdo->prepare("
        SELECT v.*, u.nom_complet AS commercial_nom
        FROM ventes v
        INNER JOIN utilisateurs u ON v.utilisateur_id = u.id
        WHERE v.client_id = ?
        ORDER BY v.date_vente DESC
        LIMIT 10
    ");
    $stmtVentes->execute([$prospect['client_id']]);
    $ventes = $stmtVentes->fetchAll(PDO::FETCH_ASSOC);
}

// Stats KPI du prospect
$ca_total = 0;
$nb_commandes = count($ventes);
foreach ($ventes as $v) {
    $ca_total += $v['montant_total_ttc'];
}

$pageTitle = "Fiche Prospect CRM - " . htmlspecialchars($prospect['prospect_nom']);
include __DIR__ . '/../partials/header.php';
include __DIR__ . '/../partials/sidebar.php';
?>

<!-- CSS intégré pour fiche CRM -->
<style>
.badge-statut {
    font-size: 0.95rem;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-weight: 600;
}
.timeline-item {
    position: relative;
    padding-left: 40px;
    padding-bottom: 20px;
    border-left: 2px solid #e0e0e0;
}
.timeline-item:last-child {
    border-left: none;
}
.timeline-icon {
    position: absolute;
    left: -12px;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
}
.card-action-rapide {
    transition: all 0.2s;
    cursor: pointer;
}
.card-action-rapide:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
</style>

<main id="main" class="main">
    <div class="pagetitle d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="bi bi-person-vcard"></i> Fiche Prospect CRM</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= url_for('dashboard.php') ?>">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="<?= url_for('terrain/prospections_list.php') ?>">Prospections</a></li>
                    <li class="breadcrumb-item active"><?= htmlspecialchars($prospect['prospect_nom']) ?></li>
                </ol>
            </nav>
        </div>
        <a href="<?= url_for('terrain/prospections_list.php') ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Retour
        </a>
    </div>

    <!-- KPI Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Statut</h6>
                    <span class="badge badge-statut bg-<?= $prospect['statut_crm'] === 'CLIENT_ACTIF' ? 'success' : ($prospect['statut_crm'] === 'PERDU' ? 'danger' : 'warning') ?>">
                        <?= str_replace('_', ' ', $prospect['statut_crm']) ?>
                    </span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Devis émis</h6>
                    <h3 class="mb-0"><?= count($devis) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Commandes</h6>
                    <h3 class="mb-0"><?= $nb_commandes ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h6 class="text-muted mb-2">CA généré</h6>
                    <h3 class="mb-0"><?= number_format($ca_total, 0, ',', ' ') ?> FCFA</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Colonne gauche : Infos + Actions -->
        <div class="col-lg-4">
            
            <!-- Carte Informations -->
            <div class="card mb-3">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-person-circle"></i> Informations</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Nom complet :</strong><br>
                        <span class="fs-5"><?= htmlspecialchars($prospect['prospect_nom']) ?></span>
                    </div>
                    <div class="mb-3">
                        <strong><i class="bi bi-telephone"></i> Téléphone :</strong><br>
                        <a href="tel:<?= htmlspecialchars($prospect['telephone']) ?>" class="text-decoration-none">
                            <?= htmlspecialchars($prospect['telephone']) ?>
                        </a>
                    </div>
                    <?php if ($prospect['email']): ?>
                    <div class="mb-3">
                        <strong><i class="bi bi-envelope"></i> Email :</strong><br>
                        <a href="mailto:<?= htmlspecialchars($prospect['email']) ?>"><?= htmlspecialchars($prospect['email']) ?></a>
                    </div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <strong><i class="bi bi-geo-alt"></i> Secteur/Zone :</strong><br>
                        <?= htmlspecialchars($prospect['secteur']) ?>
                    </div>
                    <div class="mb-3">
                        <strong><i class="bi bi-person-badge"></i> Commercial :</strong><br>
                        <?= htmlspecialchars($prospect['commercial_nom']) ?>
                    </div>
                    <?php if ($prospect['tag_activite']): ?>
                    <div class="mb-3">
                        <strong><i class="bi bi-tag"></i> Activité :</strong><br>
                        <span class="badge bg-info"><?= $prospect['tag_activite'] ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="mb-0">
                        <strong><i class="bi bi-calendar-date"></i> Date création :</strong><br>
                        <?= date('d/m/Y à H:i', strtotime($prospect['date_creation'])) ?>
                    </div>
                </div>
            </div>

            <!-- Actions Rapides -->
            <div class="card mb-3">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-lightning"></i> Actions Rapides</h5>
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-6">
                            <a href="tel:<?= htmlspecialchars($prospect['telephone']) ?>" 
                               class="card card-action-rapide text-center text-decoration-none border">
                                <div class="card-body py-3">
                                    <i class="bi bi-telephone fs-2 text-primary"></i>
                                    <div class="mt-2">Appeler</div>
                                </div>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="https://wa.me/237<?= ltrim($prospect['telephone'], '0') ?>" 
                               target="_blank" 
                               class="card card-action-rapide text-center text-decoration-none border">
                                <div class="card-body py-3">
                                    <i class="bi bi-whatsapp fs-2 text-success"></i>
                                    <div class="mt-2">WhatsApp</div>
                                </div>
                            </a>
                        </div>
                        <?php if ($prospect['email']): ?>
                        <div class="col-6">
                            <a href="mailto:<?= htmlspecialchars($prospect['email']) ?>" 
                               class="card card-action-rapide text-center text-decoration-none border">
                                <div class="card-body py-3">
                                    <i class="bi bi-envelope fs-2 text-info"></i>
                                    <div class="mt-2">Email</div>
                                </div>
                            </a>
                        </div>
                        <?php endif; ?>
                        <div class="col-6">
                            <a href="#" 
                               data-bs-toggle="modal" 
                               data-bs-target="#modalChangerStatut" 
                               class="card card-action-rapide text-center text-decoration-none border">
                                <div class="card-body py-3">
                                    <i class="bi bi-arrow-repeat fs-2 text-warning"></i>
                                    <div class="mt-2">Changer statut</div>
                                </div>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="#" 
                               data-bs-toggle="modal" 
                               data-bs-target="#modalAjouterNote" 
                               class="card card-action-rapide text-center text-decoration-none border">
                                <div class="card-body py-3">
                                    <i class="bi bi-pencil-square fs-2 text-secondary"></i>
                                    <div class="mt-2">Ajouter note</div>
                                </div>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="#" 
                               data-bs-toggle="modal" 
                               data-bs-target="#modalPlanifierRelance" 
                               class="card card-action-rapide text-center text-decoration-none border">
                                <div class="card-body py-3">
                                    <i class="bi bi-clock-history fs-2 text-danger"></i>
                                    <div class="mt-2">Planifier relance</div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Prochaine relance -->
            <?php if ($prospect['date_relance']): ?>
            <div class="alert alert-warning">
                <h6 class="alert-heading"><i class="bi bi-alarm"></i> Prochaine relance</h6>
                <strong><?= date('d/m/Y', strtotime($prospect['date_relance'])) ?></strong><br>
                Canal : <?= $prospect['canal_relance'] ?? 'Non défini' ?><br>
                <?php if ($prospect['message_relance']): ?>
                    Message : <?= htmlspecialchars($prospect['message_relance']) ?>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Réattribution (Admin uniquement) -->
            <?php if ($isAdmin && !empty($commerciaux)): ?>
            <div class="card mb-3 border-warning">
                <div class="card-header bg-warning text-dark">
                    <h6 class="mb-0"><i class="bi bi-arrow-left-right"></i> Réattribuer ce prospect</h6>
                </div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                        <input type="hidden" name="action" value="reattribuer">
                        <div class="mb-2">
                            <label class="form-label small">Nouveau commercial</label>
                            <select name="nouveau_commercial_id" class="form-select form-select-sm" required>
                                <option value="">-- Sélectionner --</option>
                                <?php foreach ($commerciaux as $c): ?>
                                    <?php if ($c['id'] != $prospect['commercial_id']): ?>
                                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nom_complet']) ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Motif (optionnel)</label>
                            <input type="text" name="motif_reattribution" class="form-control form-control-sm" 
                                   placeholder="Ex: Changement de zone">
                        </div>
                        <button type="submit" class="btn btn-sm btn-warning w-100" 
                                onclick="return confirm('Réattribuer ce prospect à un autre commercial ?')">
                            <i class="bi bi-arrow-left-right me-1"></i>Réattribuer
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

        </div>

        <!-- Colonne droite : Timeline + Notes + Relances -->
        <div class="col-lg-8">
            
            <!-- Onglets -->
            <ul class="nav nav-tabs" id="prospectTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="timeline-tab" data-bs-toggle="tab" data-bs-target="#timeline" type="button" role="tab">
                        <i class="bi bi-clock-history"></i> Timeline
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="notes-tab" data-bs-toggle="tab" data-bs-target="#notes" type="button" role="tab">
                        <i class="bi bi-pencil"></i> Notes (<?= count($notes) ?>)
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="relances-tab" data-bs-toggle="tab" data-bs-target="#relances" type="button" role="tab">
                        <i class="bi bi-alarm"></i> Relances (<?= count($relances) ?>)
                    </button>
                </li>
                <?php if (count($devis) > 0): ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="devis-tab" data-bs-toggle="tab" data-bs-target="#devis" type="button" role="tab">
                        <i class="bi bi-file-earmark-text"></i> Devis (<?= count($devis) ?>)
                    </button>
                </li>
                <?php endif; ?>
                <?php if (count($ventes) > 0): ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="ventes-tab" data-bs-toggle="tab" data-bs-target="#ventes" type="button" role="tab">
                        <i class="bi bi-cart-check"></i> Ventes (<?= count($ventes) ?>)
                    </button>
                </li>
                <?php endif; ?>
            </ul>

            <div class="tab-content p-4 border border-top-0 bg-white" id="prospectTabsContent">
                
                <!-- Onglet Timeline -->
                <div class="tab-pane fade show active" id="timeline" role="tabpanel">
                    <h5 class="mb-4">Historique complet des actions</h5>
                    <?php if (count($timeline) > 0): ?>
                        <?php foreach ($timeline as $t): ?>
                            <div class="timeline-item">
                                <div class="timeline-icon bg-<?= $t['type_action'] === 'CREATION' ? 'primary' : ($t['type_action'] === 'CHANGEMENT_STATUT' ? 'warning' : 'secondary') ?> text-white">
                                    <i class="bi bi-<?= $t['type_action'] === 'CREATION' ? 'plus' : ($t['type_action'] === 'CHANGEMENT_STATUT' ? 'arrow-repeat' : 'check') ?>"></i>
                                </div>
                                <div>
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <strong><?= htmlspecialchars($t['titre']) ?></strong>
                                            <div class="text-muted small">
                                                <?= htmlspecialchars($t['utilisateur_nom'] ?? 'Système') ?> • 
                                                <?= date('d/m/Y à H:i', strtotime($t['date_action'])) ?>
                                            </div>
                                        </div>
                                        <?php if ($t['ancien_statut'] && $t['nouveau_statut']): ?>
                                            <span class="badge bg-light text-dark">
                                                <?= str_replace('_', ' ', $t['ancien_statut']) ?> 
                                                <i class="bi bi-arrow-right"></i> 
                                                <?= str_replace('_', ' ', $t['nouveau_statut']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($t['description']): ?>
                                        <p class="mb-0 text-muted"><?= nl2br(htmlspecialchars($t['description'])) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> Aucune action enregistrée pour ce prospect.
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Onglet Notes -->
                <div class="tab-pane fade" id="notes" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Notes privées</h5>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalAjouterNote">
                            <i class="bi bi-plus-circle"></i> Ajouter note
                        </button>
                    </div>
                    <?php if (count($notes) > 0): ?>
                        <?php foreach ($notes as $note): ?>
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div class="text-muted small">
                                            <i class="bi bi-person-circle"></i> <?= htmlspecialchars($note['utilisateur_nom']) ?> • 
                                            <?= date('d/m/Y à H:i', strtotime($note['date_creation'])) ?>
                                        </div>
                                    </div>
                                    <p class="mb-0"><?= nl2br(htmlspecialchars($note['note'])) ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> Aucune note enregistrée pour ce prospect.
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Onglet Relances -->
                <div class="tab-pane fade" id="relances" role="tabpanel">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Relances planifiées</h5>
                        <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#modalPlanifierRelance">
                            <i class="bi bi-alarm"></i> Planifier relance
                        </button>
                    </div>
                    <?php if (count($relances) > 0): ?>
                        <?php foreach ($relances as $r): ?>
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-2">
                                                <i class="bi bi-calendar-date"></i> <?= date('d/m/Y', strtotime($r['date_relance_prevue'])) ?>
                                                <span class="badge bg-<?= $r['statut'] === 'FAIT' ? 'success' : ($r['statut'] === 'ANNULE' ? 'secondary' : 'warning') ?>">
                                                    <?= $r['statut'] ?>
                                                </span>
                                            </h6>
                                            <div class="text-muted small mb-2">
                                                Canal : <?= $r['canal'] ?> • 
                                                Par : <?= htmlspecialchars($r['utilisateur_nom']) ?>
                                            </div>
                                            <?php if ($r['message']): ?>
                                                <p class="mb-1"><?= nl2br(htmlspecialchars($r['message'])) ?></p>
                                            <?php endif; ?>
                                            <?php if ($r['statut'] === 'FAIT' && $r['resultat']): ?>
                                                <div class="alert alert-success small mb-0 mt-2">
                                                    <strong>Résultat :</strong> <?= nl2br(htmlspecialchars($r['resultat'])) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($r['statut'] === 'A_FAIRE'): ?>
                                            <button class="btn btn-sm btn-success" onclick="marquerRelanceFaite(<?= $r['id'] ?>)">
                                                <i class="bi bi-check-circle"></i> Marquer fait
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> Aucune relance planifiée pour ce prospect.
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Onglet Devis -->
                <?php if (count($devis) > 0): ?>
                <div class="tab-pane fade" id="devis" role="tabpanel">
                    <h5 class="mb-3">Devis émis</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Numéro</th>
                                    <th>Date</th>
                                    <th>Statut</th>
                                    <th>Montant TTC</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($devis as $d): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($d['numero']) ?></td>
                                        <td><?= date('d/m/Y', strtotime($d['date_devis'])) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $d['statut'] === 'ACCEPTE' ? 'success' : ($d['statut'] === 'REFUSE' ? 'danger' : 'warning') ?>">
                                                <?= $d['statut'] ?>
                                            </span>
                                        </td>
                                        <td><?= number_format($d['montant_total_ttc'], 0, ',', ' ') ?> FCFA</td>
                                        <td>
                                            <a href="<?= url_for('devis/show.php?id=' . $d['id']) ?>" class="btn btn-sm btn-primary" target="_blank">
                                                <i class="bi bi-eye"></i> Voir
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Onglet Ventes -->
                <?php if (count($ventes) > 0): ?>
                <div class="tab-pane fade" id="ventes" role="tabpanel">
                    <h5 class="mb-3">Ventes réalisées</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Numéro</th>
                                    <th>Date</th>
                                    <th>Statut</th>
                                    <th>Montant TTC</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ventes as $v): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($v['numero']) ?></td>
                                        <td><?= date('d/m/Y', strtotime($v['date_vente'])) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $v['statut'] === 'VALIDEE' ? 'success' : 'warning' ?>">
                                                <?= $v['statut'] ?>
                                            </span>
                                        </td>
                                        <td><?= number_format($v['montant_total_ttc'], 0, ',', ' ') ?> FCFA</td>
                                        <td>
                                            <a href="<?= url_for('ventes/show.php?id=' . $v['id']) ?>" class="btn btn-sm btn-primary" target="_blank">
                                                <i class="bi bi-eye"></i> Voir
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

            </div>

        </div>
    </div>

</main>

<!-- Modal Changer Statut -->
<div class="modal fade" id="modalChangerStatut" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Changer le statut</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formChangerStatut">
                <div class="modal-body">
                    <input type="hidden" name="prospection_id" value="<?= $prospect_id ?>">
                    <div class="mb-3">
                        <label class="form-label">Nouveau statut *</label>
                        <select name="statut_crm" class="form-select" required>
                            <option value="PROSPECT" <?= $prospect['statut_crm'] === 'PROSPECT' ? 'selected' : '' ?>>Prospect</option>
                            <option value="INTERESSE" <?= $prospect['statut_crm'] === 'INTERESSE' ? 'selected' : '' ?>>Intéressé</option>
                            <option value="PROSPECT_CHAUD" <?= $prospect['statut_crm'] === 'PROSPECT_CHAUD' ? 'selected' : '' ?>>Prospect chaud</option>
                            <option value="DEVIS_DEMANDE" <?= $prospect['statut_crm'] === 'DEVIS_DEMANDE' ? 'selected' : '' ?>>Devis demandé</option>
                            <option value="DEVIS_EMIS" <?= $prospect['statut_crm'] === 'DEVIS_EMIS' ? 'selected' : '' ?>>Devis émis</option>
                            <option value="COMMANDE_OBTENUE" <?= $prospect['statut_crm'] === 'COMMANDE_OBTENUE' ? 'selected' : '' ?>>Commande obtenue</option>
                            <option value="CLIENT_ACTIF" <?= $prospect['statut_crm'] === 'CLIENT_ACTIF' ? 'selected' : '' ?>>Client actif</option>
                            <option value="FIDELISATION" <?= $prospect['statut_crm'] === 'FIDELISATION' ? 'selected' : '' ?>>Fidélisation</option>
                            <option value="PERDU" <?= $prospect['statut_crm'] === 'PERDU' ? 'selected' : '' ?>>Perdu</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Ajouter Note -->
<div class="modal fade" id="modalAjouterNote" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ajouter une note</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formAjouterNote">
                <div class="modal-body">
                    <input type="hidden" name="prospection_id" value="<?= $prospect_id ?>">
                    <div class="mb-3">
                        <label class="form-label">Note *</label>
                        <textarea name="note" class="form-control" rows="5" required placeholder="Votre note privée..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Ajouter</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Planifier Relance -->
<div class="modal fade" id="modalPlanifierRelance" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Planifier une relance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formPlanifierRelance">
                <div class="modal-body">
                    <input type="hidden" name="prospection_id" value="<?= $prospect_id ?>">
                    <div class="mb-3">
                        <label class="form-label">Date de relance *</label>
                        <input type="date" name="date_relance" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Canal *</label>
                        <select name="canal" class="form-select" required>
                            <option value="">-- Choisir --</option>
                            <option value="WHATSAPP">WhatsApp</option>
                            <option value="APPEL">Appel téléphonique</option>
                            <option value="SMS">SMS</option>
                            <option value="EMAIL">Email</option>
                            <option value="VISITE">Visite terrain</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Message (optionnel)</label>
                        <textarea name="message" class="form-control" rows="3" placeholder="Message ou contexte de la relance..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-danger">Planifier</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>

<script>
// Changer statut
document.getElementById('formChangerStatut').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('<?= url_for("terrain/ajax_changer_statut.php") ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Erreur lors du changement de statut');
        }
    })
    .catch(error => {
        alert('Erreur réseau : ' + error.message);
    });
});

// Ajouter note
document.getElementById('formAjouterNote').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('<?= url_for("terrain/ajax_ajouter_note.php") ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Erreur lors de l\'ajout de la note');
        }
    })
    .catch(error => {
        alert('Erreur réseau : ' + error.message);
    });
});

// Planifier relance
document.getElementById('formPlanifierRelance').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('<?= url_for("terrain/ajax_planifier_relance.php") ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Erreur lors de la planification de la relance');
        }
    })
    .catch(error => {
        alert('Erreur réseau : ' + error.message);
    });
});

// Marquer relance faite
function marquerRelanceFaite(relanceId) {
    const resultat = prompt('Résultat de la relance (optionnel) :');
    if (resultat === null) return; // Annulé
    
    const formData = new FormData();
    formData.append('relance_id', relanceId);
    formData.append('resultat', resultat);
    
    fetch('<?= url_for("terrain/ajax_marquer_relance_faite.php") ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Erreur lors de la mise à jour');
        }
    })
    .catch(error => {
        alert('Erreur réseau : ' + error.message);
    });
}
</script>
