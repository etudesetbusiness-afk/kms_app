<?php
// terrain/reporting/view.php - Visualisation du rapport hebdomadaire terrain
require_once __DIR__ . '/../../security.php';
exigerConnexion();
exigerPermission('CLIENTS_CREER');

global $pdo;

$utilisateur  = utilisateurConnecte();
$userId       = (int)$utilisateur['id'];
$csrfToken    = getCsrfToken();
$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError   = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$voirTout = peutVoirToutesProspections();
$isAdmin  = estAdmin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    header('Location: ' . url_for('terrain/reporting/list.php'));
    exit;
}

// Charger le rapport
$stmt = $pdo->prepare("
    SELECT rt.*, u.nom_complet AS commercial_nom
    FROM reporting_terrain rt
    LEFT JOIN utilisateurs u ON rt.commercial_id = u.id
    WHERE rt.id = ?
");
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

// Charger les données liées
$zones = $pdo->prepare("SELECT * FROM reporting_terrain_zones WHERE reporting_id = ?");
$zones->execute([$id]);
$zones = $zones->fetchAll(PDO::FETCH_ASSOC);

$journal = $pdo->prepare("SELECT * FROM reporting_terrain_journal WHERE reporting_id = ? ORDER BY jour_semaine");
$journal->execute([$id]);
$journal = $journal->fetchAll(PDO::FETCH_ASSOC);

$produits = $pdo->prepare("SELECT * FROM reporting_terrain_produits WHERE reporting_id = ?");
$produits->execute([$id]);
$produits = $produits->fetchAll(PDO::FETCH_ASSOC);

$objections = $pdo->prepare("SELECT * FROM reporting_terrain_objections WHERE reporting_id = ?");
$objections->execute([$id]);
$objections = $objections->fetchAll(PDO::FETCH_ASSOC);

$arguments = $pdo->prepare("SELECT * FROM reporting_terrain_arguments WHERE reporting_id = ?");
$arguments->execute([$id]);
$arguments = $arguments->fetchAll(PDO::FETCH_ASSOC);

$actions = $pdo->prepare("SELECT * FROM reporting_terrain_actions WHERE reporting_id = ?");
$actions->execute([$id]);
$actions = $actions->fetchAll(PDO::FETCH_ASSOC);

// Calculer les totaux
$totalVisites = array_sum(array_column($journal, 'nb_visites'));
$totalRdv     = array_sum(array_column($journal, 'nb_rdv'));
$totalDevis   = array_sum(array_column($journal, 'nb_devis'));
$totalVentes  = array_sum(array_column($journal, 'nb_ventes'));
$totalCa      = array_sum(array_column($journal, 'ca_realise'));

// Traitement validation/rejet (Admin/Direction)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($isAdmin || estDirection())) {
    verifierCsrf($_POST['csrf_token'] ?? '');
    $action = $_POST['action'] ?? '';
    
    if ($action === 'valider' && $rapport['statut'] === 'SOUMIS') {
        $pdo->prepare("UPDATE reporting_terrain SET statut = 'VALIDE', validated_at = NOW(), validated_by = ? WHERE id = ?")
            ->execute([$userId, $id]);
        
        // Historique
        $pdo->prepare("INSERT INTO reporting_terrain_historique (reporting_id, action, ancien_statut, nouveau_statut, utilisateur_id, commentaire) VALUES (?, 'VALIDATION', 'SOUMIS', 'VALIDE', ?, ?)")
            ->execute([$id, $userId, $_POST['commentaire'] ?? '']);
        
        $_SESSION['flash_success'] = "Rapport validé avec succès.";
        header('Location: ' . url_for('terrain/reporting/view.php?id=' . $id));
        exit;
    }
    
    if ($action === 'rejeter' && $rapport['statut'] === 'SOUMIS') {
        $motif = trim($_POST['motif_rejet'] ?? '');
        if (empty($motif)) {
            $flashError = "Le motif de rejet est obligatoire.";
        } else {
            $pdo->prepare("UPDATE reporting_terrain SET statut = 'REJETE', commentaire_rejet = ? WHERE id = ?")
                ->execute([$motif, $id]);
            
            // Historique
            $pdo->prepare("INSERT INTO reporting_terrain_historique (reporting_id, action, ancien_statut, nouveau_statut, utilisateur_id, commentaire) VALUES (?, 'REJET', 'SOUMIS', 'REJETE', ?, ?)")
                ->execute([$id, $userId, $motif]);
            
            $_SESSION['flash_success'] = "Rapport rejeté.";
            header('Location: ' . url_for('terrain/reporting/view.php?id=' . $id));
            exit;
        }
    }
}

// Rafraîchir après action
if ($flashSuccess === null) {
    $stmt->execute([$id]);
    $rapport = $stmt->fetch(PDO::FETCH_ASSOC);
}

$statuts = [
    'BROUILLON'  => ['label' => 'Brouillon', 'badge' => 'secondary', 'icon' => 'pencil'],
    'SOUMIS'     => ['label' => 'Soumis', 'badge' => 'primary', 'icon' => 'send'],
    'VALIDE'     => ['label' => 'Validé', 'badge' => 'success', 'icon' => 'check-circle'],
    'REJETE'     => ['label' => 'Rejeté', 'badge' => 'danger', 'icon' => 'x-circle'],
];
$statutInfo = $statuts[$rapport['statut']] ?? $statuts['BROUILLON'];

$joursSemaine = ['', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];

$canEdit = in_array($rapport['statut'], ['BROUILLON', 'REJETE']) 
           && ($voirTout || $rapport['commercial_id'] == $userId);

require_once __DIR__ . '/../../templates/header.php';
?>

<div class="container-fluid py-3">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-start mb-3">
        <div>
            <a href="<?= url_for('terrain/reporting/list.php') ?>" class="text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i>Retour à la liste
            </a>
            <h4 class="mb-0 mt-2">
                <i class="bi bi-journal-text me-2"></i>
                Rapport S<?= $rapport['numero_semaine'] ?> / <?= $rapport['annee'] ?>
            </h4>
            <div class="text-muted">
                <small>
                    <?= htmlspecialchars($rapport['commercial_nom'] ?? 'N/A') ?> •
                    <?= $rapport['date_debut'] ? date('d/m/Y', strtotime($rapport['date_debut'])) : '' ?>
                    → <?= $rapport['date_fin'] ? date('d/m/Y', strtotime($rapport['date_fin'])) : '' ?>
                </small>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-<?= $statutInfo['badge'] ?> fs-6">
                <i class="bi bi-<?= $statutInfo['icon'] ?> me-1"></i><?= $statutInfo['label'] ?>
            </span>
            <div class="btn-group">
                <?php if ($canEdit): ?>
                    <a href="<?= url_for('terrain/reporting/edit.php?id=' . $id) ?>" class="btn btn-outline-primary">
                        <i class="bi bi-pencil me-1"></i>Modifier
                    </a>
                <?php endif; ?>
                <a href="<?= url_for('terrain/reporting/pdf.php?id=' . $id) ?>" class="btn btn-outline-dark" target="_blank">
                    <i class="bi bi-file-pdf me-1"></i>PDF
                </a>
            </div>
        </div>
    </div>

    <!-- Flash Messages -->
    <?php if ($flashSuccess): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($flashSuccess) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if ($flashError): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($flashError) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Message de rejet -->
    <?php if ($rapport['statut'] === 'REJETE' && !empty($rapport['commentaire_rejet'])): ?>
        <div class="alert alert-warning">
            <strong><i class="bi bi-exclamation-triangle me-2"></i>Motif du rejet :</strong>
            <?= nl2br(htmlspecialchars($rapport['commentaire_rejet'])) ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Colonne principale -->
        <div class="col-lg-8">
            <!-- KPI Summary -->
            <div class="row g-3 mb-3">
                <div class="col-6 col-md-3">
                    <div class="card text-center h-100">
                        <div class="card-body py-2">
                            <div class="text-muted small">Visites</div>
                            <div class="fs-4 fw-bold text-primary"><?= $totalVisites ?></div>
                            <?php if ($rapport['objectif_visites'] > 0): ?>
                                <small class="text-muted">/ <?= $rapport['objectif_visites'] ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card text-center h-100">
                        <div class="card-body py-2">
                            <div class="text-muted small">RDV</div>
                            <div class="fs-4 fw-bold text-info"><?= $totalRdv ?></div>
                            <?php if ($rapport['objectif_rdv'] > 0): ?>
                                <small class="text-muted">/ <?= $rapport['objectif_rdv'] ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card text-center h-100">
                        <div class="card-body py-2">
                            <div class="text-muted small">Devis/Ventes</div>
                            <div class="fs-4 fw-bold text-warning"><?= $totalDevis ?> / <?= $totalVentes ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card text-center h-100">
                        <div class="card-body py-2">
                            <div class="text-muted small">CA Réalisé</div>
                            <div class="fs-5 fw-bold text-success"><?= number_format($totalCa, 0, ',', ' ') ?> F</div>
                            <?php if ($rapport['objectif_ca'] > 0): ?>
                                <small class="text-muted">/ <?= number_format($rapport['objectif_ca'], 0, ',', ' ') ?></small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Journal quotidien -->
            <?php if (!empty($journal)): ?>
            <div class="card mb-3">
                <div class="card-header bg-white">
                    <i class="bi bi-calendar-week me-2"></i>Suivi journalier
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Jour</th>
                                <th class="text-center">Visites</th>
                                <th class="text-center">RDV</th>
                                <th class="text-center">Devis</th>
                                <th class="text-center">Ventes</th>
                                <th class="text-end">CA</th>
                                <th>Zone</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($journal as $j): ?>
                            <tr>
                                <td><strong><?= $joursSemaine[$j['jour_semaine']] ?? '' ?></strong></td>
                                <td class="text-center"><?= (int)$j['nb_visites'] ?></td>
                                <td class="text-center"><?= (int)$j['nb_rdv'] ?></td>
                                <td class="text-center"><?= (int)$j['nb_devis'] ?></td>
                                <td class="text-center"><?= (int)$j['nb_ventes'] ?></td>
                                <td class="text-end"><?= number_format((float)$j['ca_realise'], 0, ',', ' ') ?></td>
                                <td><small><?= htmlspecialchars($j['zone_couverte'] ?? '') ?></small></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td>Total</td>
                                <td class="text-center"><?= $totalVisites ?></td>
                                <td class="text-center"><?= $totalRdv ?></td>
                                <td class="text-center"><?= $totalDevis ?></td>
                                <td class="text-center"><?= $totalVentes ?></td>
                                <td class="text-end"><?= number_format($totalCa, 0, ',', ' ') ?></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Zones -->
            <?php if (!empty($zones)): ?>
            <div class="card mb-3">
                <div class="card-header bg-white">
                    <i class="bi bi-geo-alt me-2"></i>Zones prospectées
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        <?php foreach ($zones as $z): ?>
                        <div class="col-md-6">
                            <div class="border rounded p-2">
                                <strong><?= htmlspecialchars($z['nom_zone']) ?></strong>
                                <?php if ($z['type_cible']): ?>
                                    <span class="badge bg-light text-dark ms-1"><?= $z['type_cible'] ?></span>
                                <?php endif; ?>
                                <?php if ($z['potentiel']): ?>
                                    <span class="badge bg-<?= $z['potentiel'] === 'FORT' ? 'success' : ($z['potentiel'] === 'MOYEN' ? 'warning' : 'secondary') ?>">
                                        <?= $z['potentiel'] ?>
                                    </span>
                                <?php endif; ?>
                                <?php if ($z['remarques']): ?>
                                    <div class="small text-muted mt-1"><?= htmlspecialchars($z['remarques']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Produits vendus -->
            <?php if (!empty($produits)): ?>
            <div class="card mb-3">
                <div class="card-header bg-white">
                    <i class="bi bi-box-seam me-2"></i>Produits vendus
                </div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Produit</th>
                                <th class="text-center">Qté</th>
                                <th class="text-end">Montant</th>
                                <th>Type client</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($produits as $p): ?>
                            <tr>
                                <td><?= htmlspecialchars($p['produit_nom']) ?></td>
                                <td class="text-center"><?= (int)$p['quantite'] ?></td>
                                <td class="text-end"><?= number_format((float)$p['montant_total'], 0, ',', ' ') ?> F</td>
                                <td><span class="badge bg-light text-dark"><?= $p['type_client'] ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Synthèse -->
            <?php if (!empty($rapport['synthese'])): ?>
            <div class="card mb-3">
                <div class="card-header bg-white">
                    <i class="bi bi-file-text me-2"></i>Synthèse
                </div>
                <div class="card-body">
                    <p><?= nl2br(htmlspecialchars($rapport['synthese'])) ?></p>
                    
                    <?php if (!empty($rapport['points_forts'])): ?>
                    <div class="mb-2">
                        <strong class="text-success"><i class="bi bi-check-circle me-1"></i>Points forts :</strong>
                        <p class="mb-0"><?= nl2br(htmlspecialchars($rapport['points_forts'])) ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($rapport['difficultes'])): ?>
                    <div class="mb-2">
                        <strong class="text-warning"><i class="bi bi-exclamation-triangle me-1"></i>Difficultés :</strong>
                        <p class="mb-0"><?= nl2br(htmlspecialchars($rapport['difficultes'])) ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($rapport['besoins_support'])): ?>
                    <div>
                        <strong class="text-info"><i class="bi bi-life-preserver me-1"></i>Besoins de support :</strong>
                        <p class="mb-0"><?= nl2br(htmlspecialchars($rapport['besoins_support'])) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Colonne latérale -->
        <div class="col-lg-4">
            <!-- Objections -->
            <?php if (!empty($objections)): ?>
            <div class="card mb-3">
                <div class="card-header bg-white">
                    <i class="bi bi-chat-left-text me-2"></i>Objections
                </div>
                <ul class="list-group list-group-flush">
                    <?php foreach ($objections as $o): ?>
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between">
                            <strong><?= htmlspecialchars($o['objection']) ?></strong>
                            <span class="badge bg-<?= $o['frequence'] === 'FORTE' ? 'danger' : ($o['frequence'] === 'MOYENNE' ? 'warning' : 'secondary') ?>">
                                <?= $o['frequence'] ?>
                            </span>
                        </div>
                        <?php if ($o['reponse_apportee']): ?>
                        <small class="text-success"><i class="bi bi-arrow-return-right me-1"></i><?= htmlspecialchars($o['reponse_apportee']) ?></small>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <!-- Arguments -->
            <?php if (!empty($arguments)): ?>
            <div class="card mb-3">
                <div class="card-header bg-white">
                    <i class="bi bi-lightbulb me-2"></i>Arguments efficaces
                </div>
                <ul class="list-group list-group-flush">
                    <?php foreach ($arguments as $a): ?>
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between">
                            <span><?= htmlspecialchars($a['argument']) ?></span>
                            <span class="badge bg-<?= $a['efficacite'] === 'FORTE' ? 'success' : ($a['efficacite'] === 'MOYENNE' ? 'primary' : 'secondary') ?>">
                                <?= $a['efficacite'] ?>
                            </span>
                        </div>
                        <?php if ($a['contexte']): ?>
                        <small class="text-muted"><?= htmlspecialchars($a['contexte']) ?></small>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <!-- Actions -->
            <?php if (!empty($actions)): ?>
            <div class="card mb-3">
                <div class="card-header bg-white">
                    <i class="bi bi-check2-square me-2"></i>Plan d'actions
                </div>
                <ul class="list-group list-group-flush">
                    <?php foreach ($actions as $act): ?>
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <span><?= htmlspecialchars($act['description']) ?></span>
                                <?php if ($act['echeance']): ?>
                                <br><small class="text-muted">
                                    <i class="bi bi-calendar me-1"></i><?= date('d/m/Y', strtotime($act['echeance'])) ?>
                                </small>
                                <?php endif; ?>
                            </div>
                            <span class="badge bg-<?= $act['priorite'] === 'URGENTE' ? 'danger' : ($act['priorite'] === 'HAUTE' ? 'warning' : 'secondary') ?>">
                                <?= $act['priorite'] ?>
                            </span>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <!-- Actions Admin (validation/rejet) -->
            <?php if (($isAdmin || estDirection()) && $rapport['statut'] === 'SOUMIS'): ?>
            <div class="card border-primary">
                <div class="card-header bg-primary text-white">
                    <i class="bi bi-shield-check me-2"></i>Actions Direction
                </div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Commentaire (optionnel)</label>
                            <textarea name="commentaire" class="form-control" rows="2"></textarea>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" name="action" value="valider" class="btn btn-success">
                                <i class="bi bi-check-circle me-1"></i>Valider ce rapport
                            </button>
                        </div>
                    </form>
                    
                    <hr>
                    
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Motif du rejet <span class="text-danger">*</span></label>
                            <textarea name="motif_rejet" class="form-control" rows="2" required></textarea>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" name="action" value="rejeter" class="btn btn-outline-danger">
                                <i class="bi bi-x-circle me-1"></i>Rejeter
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
