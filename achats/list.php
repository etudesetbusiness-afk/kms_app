<?php
// achats/list.php
require_once __DIR__ . '/../security.php';
exigerConnexion();
exigerPermission('ACHATS_GERER');

global $pdo;

// --- Filtres ---
$dateDebut  = $_GET['date_debut'] ?? date('Y-m-01');
$dateFin    = $_GET['date_fin'] ?? date('Y-m-d');
$statut     = $_GET['statut'] ?? '';
$searchFournisseur = trim($_GET['fournisseur'] ?? '');

$where  = [];
$params = [];

if ($dateDebut !== '') {
    $where[] = "a.date_achat >= :date_debut";
    $params['date_debut'] = $dateDebut;
}

if ($dateFin !== '') {
    $where[] = "a.date_achat <= :date_fin";
    $params['date_fin'] = $dateFin;
}

$statutsPossibles = ['EN_COURS','VALIDE','ANNULE'];
if ($statut !== '' && in_array($statut, $statutsPossibles, true)) {
    $where[] = "a.statut = :statut";
    $params['statut'] = $statut;
}

if ($searchFournisseur !== '') {
    $where[] = "(a.fournisseur_nom LIKE :fournisseur OR a.fournisseur_contact LIKE :fournisseur)";
    $params['fournisseur'] = '%' . $searchFournisseur . '%';
}

$whereSql = '';
if (!empty($where)) {
    $whereSql = 'WHERE ' . implode(' AND ', $where);
}

// --- Récupération des achats ---
$sql = "
    SELECT
        a.*,
        u.nom_complet AS utilisateur_nom
    FROM achats a
    LEFT JOIN utilisateurs u ON u.id = a.utilisateur_id
    $whereSql
    ORDER BY a.date_achat DESC, a.id DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$achats = $stmt->fetchAll();

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError   = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

include __DIR__ . '/../partials/header.php';
include __DIR__ . '/../partials/sidebar.php';
?>

<div class="container-fluid">
    <div class="list-page-header d-flex justify-content-between align-items-center">
        <h1 class="list-page-title h3">
            <i class="bi bi-basket-fill"></i>
            Achats & approvisionnements
            <span class="count-badge ms-2"><?= count($achats) ?></span>
        </h1>
        <a href="<?= url_for('achats/edit.php') ?>" class="btn btn-primary btn-add-new">
            <i class="bi bi-plus-circle me-2"></i> Nouvel achat
        </a>
    </div>

    <?php if ($flashSuccess): ?>
        <div class="alert alert-success alert-modern">
            <i class="bi bi-check-circle-fill"></i>
            <span><?= htmlspecialchars($flashSuccess) ?></span>
        </div>
    <?php endif; ?>

    <?php if ($flashError): ?>
        <div class="alert alert-danger alert-modern">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <span><?= htmlspecialchars($flashError) ?></span>
        </div>
    <?php endif; ?>

    <!-- Filtres -->
    <div class="card filter-card">
        <div class="card-body">
            <form method="get" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small">Du</label>
                    <input type="date" name="date_debut" class="form-control"
                           value="<?= htmlspecialchars($dateDebut) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Au</label>
                    <input type="date" name="date_fin" class="form-control"
                           value="<?= htmlspecialchars($dateFin) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Statut</label>
                    <select name="statut" class="form-select">
                        <option value="">Tous</option>
                        <?php foreach ($statutsPossibles as $s): ?>
                            <option value="<?= $s ?>" <?= $statut === $s ? 'selected' : '' ?>>
                                <?= $s ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Fournisseur (nom/contact)</label>
                    <input type="text" name="fournisseur" class="form-control"
                           placeholder="Rechercher un fournisseur..."
                           value="<?= htmlspecialchars($searchFournisseur) ?>">
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-filter">
                        <i class="bi bi-search me-1"></i> Filtrer
                    </button>
                    <a href="<?= url_for('achats/list.php') ?>" class="btn btn-outline-secondary btn-filter">
                        <i class="bi bi-arrow-clockwise me-1"></i> Réinitialiser
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Liste des achats -->
    <div class="card data-table-card">
        <div class="card-body">
            <?php if (empty($achats)): ?>
                <div class="empty-state">
                    <i class="bi bi-basket"></i>
                    <h5>Aucun achat trouvé</h5>
                    <p>Aucun achat ne correspond aux filtres sélectionnés.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table modern-table">
                        <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Numéro</th>
                            <th>Fournisseur</th>
                            <th class="text-end">Montant HT</th>
                            <th class="text-end">Montant TTC</th>
                            <th>Statut</th>
                            <th>Enregistré par</th>
                            <th class="text-end">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($achats as $a): ?>
                            <tr>
                                <td>
                                    <i class="bi bi-calendar3 me-1 text-muted"></i>
                                    <?= htmlspecialchars($a['date_achat']) ?>
                                </td>
                                <td>
                                    <span class="modern-badge badge-status-primary">
                                        <i class="bi bi-receipt"></i>
                                        <?= htmlspecialchars($a['numero']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($a['fournisseur_nom'])): ?>
                                        <div class="fw-semibold">
                                            <i class="bi bi-shop me-1 text-muted"></i>
                                            <?= htmlspecialchars($a['fournisseur_nom']) ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($a['fournisseur_contact'])): ?>
                                        <div class="text-muted small">
                                            <i class="bi bi-telephone me-1"></i>
                                            <?= htmlspecialchars($a['fournisseur_contact']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end fw-semibold">
                                    <?= number_format((float)$a['montant_total_ht'], 0, ',', ' ') ?> FCFA
                                </td>
                                <td class="text-end fw-bold text-success">
                                    <?= number_format((float)$a['montant_total_ttc'], 0, ',', ' ') ?> FCFA
                                </td>
                                <td>
                                    <?php
                                    $badgeClass = 'badge-status-secondary';
                                    $badgeIcon = 'bi-hourglass';
                                    if ($a['statut'] === 'EN_COURS') {
                                        $badgeClass = 'badge-status-warning';
                                        $badgeIcon = 'bi-hourglass-split';
                                    } elseif ($a['statut'] === 'VALIDE') {
                                        $badgeClass = 'badge-status-success';
                                        $badgeIcon = 'bi-check-circle-fill';
                                    } elseif ($a['statut'] === 'ANNULE') {
                                        $badgeClass = 'badge-status-danger';
                                        $badgeIcon = 'bi-x-circle-fill';
                                    }
                                    ?>
                                    <span class="modern-badge <?= $badgeClass ?>">
                                        <i class="<?= $badgeIcon ?>"></i>
                                        <?= htmlspecialchars($a['statut']) ?>
                                    </span>
                                </td>
                                <td>
                                    <i class="bi bi-person me-1 text-muted"></i>
                                    <?= htmlspecialchars($a['utilisateur_nom'] ?? '-') ?>
                                </td>
                                <td class="text-end">
                                    <div class="action-btn-group">
                                        <a href="<?= url_for('achats/edit.php?id=' . (int)$a['id']) ?>"
                                           class="btn btn-sm btn-outline-primary btn-action"
                                           title="Ouvrir l'achat">
                                            <i class="bi bi-pencil-square"></i> Ouvrir
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>
