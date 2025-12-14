<?php
// livraisons/list.php
require_once __DIR__ . '/../security.php';
exigerConnexion();
exigerPermission('VENTES_LIRE');

global $pdo;

$dateDeb  = $_GET['date_debut'] ?? '';
$dateFin  = $_GET['date_fin'] ?? '';
$clientId = isset($_GET['client_id']) ? (int)$_GET['client_id'] : 0;
$signe    = $_GET['signe'] ?? ''; // '', '0', '1'

// Clients
$stmt = $pdo->query("SELECT id, nom FROM clients ORDER BY nom");
$clients = $stmt->fetchAll();

$where  = [];
$params = [];

if ($dateDeb !== '') {
    $where[] = "b.date_bl >= :date_debut";
    $params['date_debut'] = $dateDeb;
}
if ($dateFin !== '') {
    $where[] = "b.date_bl <= :date_fin";
    $params['date_fin'] = $dateFin;
}
if ($clientId > 0) {
    $where[] = "b.client_id = :client_id";
    $params['client_id'] = $clientId;
}
if ($signe !== '' && in_array($signe, ['0','1'], true)) {
    $where[] = "b.signe_client = :signe";
    $params['signe'] = (int)$signe;
}

$whereSql = '';
if (!empty($where)) {
    $whereSql = 'WHERE ' . implode(' AND ', $where);
}

$sql = "
    SELECT
        b.*,
        c.nom AS client_nom,
        v.numero AS vente_numero
    FROM bons_livraison b
    JOIN clients c ON c.id = b.client_id
    LEFT JOIN ventes v ON v.id = b.vente_id
    $whereSql
    ORDER BY b.date_bl DESC, b.id DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bons = $stmt->fetchAll();

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError   = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

include __DIR__ . '/../partials/header.php';
include __DIR__ . '/../partials/sidebar.php';
?>

<div class="container-fluid">
    <div class="list-page-header d-flex justify-content-between align-items-center">
        <h1 class="list-page-title h3">
            <i class="bi bi-truck"></i>
            Bons de livraison
            <span class="count-badge ms-2"><?= count($bons) ?></span>
        </h1>
        <a href="<?= url_for('ventes/list.php') ?>" class="btn btn-outline-secondary btn-filter">
            <i class="bi bi-arrow-left me-1"></i> Retour aux ventes
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

    <div class="card filter-card">
        <div class="card-body">
            <form method="get" class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="form-label small">Date BL ≥</label>
                    <input type="date" name="date_debut" class="form-control"
                           value="<?= htmlspecialchars($dateDeb) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Date BL ≤</label>
                    <input type="date" name="date_fin" class="form-control"
                           value="<?= htmlspecialchars($dateFin) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Client</label>
                    <select name="client_id" class="form-select">
                        <option value="0">Tous</option>
                        <?php foreach ($clients as $cl): ?>
                            <option value="<?= (int)$cl['id'] ?>"
                                <?= $clientId === (int)$cl['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cl['nom']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Signature client</label>
                    <select name="signe" class="form-select">
                        <option value="">Tous</option>
                        <option value="1" <?= $signe === '1' ? 'selected' : '' ?>>Signés</option>
                        <option value="0" <?= $signe === '0' ? 'selected' : '' ?>>Non signés</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-filter">
                        <i class="bi bi-search me-1"></i> Filtrer
                    </button>
                    <a href="<?= url_for('livraisons/list.php') ?>" class="btn btn-outline-secondary btn-filter">
                        <i class="bi bi-arrow-clockwise me-1"></i> Réinitialiser
                    </a>
                    <a href="<?= url_for('livraisons/export_excel.php?date_debut=' . urlencode($dateDeb) . '&date_fin=' . urlencode($dateFin) . '&client_id=' . urlencode($clientId) . '&signe=' . urlencode($signe)) ?>" class="btn btn-success btn-filter">
                        <i class="bi bi-file-earmark-excel me-1"></i> Exporter
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card data-table-card">
        <div class="card-body">
            <?php if (empty($bons)): ?>
                <div class="empty-state">
                    <i class="bi bi-truck"></i>
                    <h5>Aucun bon de livraison trouvé</h5>
                    <p>Aucun bon de livraison ne correspond aux filtres sélectionnés.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table modern-table">
                        <thead class="table-light">
                        <tr>
                            <th>N° BL</th>
                            <th>Date BL</th>
                            <th>Vente</th>
                            <th>Client</th>
                            <th>Transport</th>
                            <th>Signé client</th>
                            <th class="text-end">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($bons as $b): ?>
                            <tr>
                                <td>
                                    <span class="modern-badge badge-status-primary">
                                        <i class="bi bi-file-text"></i>
                                        <?= htmlspecialchars($b['numero']) ?>
                                    </span>
                                </td>
                                <td>
                                    <i class="bi bi-calendar3 me-1 text-muted"></i>
                                    <?= htmlspecialchars($b['date_bl']) ?>
                                </td>
                                <td>
                                    <?php if ($b['vente_numero']): ?>
                                        <a href="<?= url_for('ventes/detail.php') . '?id=' . (int)$b['vente_id'] ?>"
                                           class="table-link">
                                            <i class="bi bi-receipt me-1"></i>
                                            <?= htmlspecialchars($b['vente_numero']) ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <i class="bi bi-person me-1 text-muted"></i>
                                    <?= htmlspecialchars($b['client_nom']) ?>
                                </td>
                                <td>
                                    <?php if ($b['transport_assure_par']): ?>
                                        <span class="modern-badge badge-status-info">
                                            <i class="bi bi-box-seam"></i>
                                            <?= htmlspecialchars($b['transport_assure_par']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted small">Non spécifié</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ((int)$b['signe_client'] === 1): ?>
                                        <span class="modern-badge badge-status-success">
                                            <i class="bi bi-check2-circle"></i> Signé
                                        </span>
                                    <?php else: ?>
                                        <span class="modern-badge badge-status-warning">
                                            <i class="bi bi-exclamation-circle"></i> Non signé
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="action-btn-group">
                                        <?php if ((int)$b['signe_client'] === 0): ?>
                                            <form method="post" action="<?= url_for('livraisons/marquer_signe.php') ?>" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken()) ?>">
                                                <input type="hidden" name="bl_id" value="<?= (int)$b['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-success btn-action" title="Marquer comme signé">
                                                    <i class="bi bi-pen"></i> Marquer signé
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        <?php if ($b['vente_id']): ?>
                                            <a href="<?= url_for('ventes/detail.php') . '?id=' . (int)$b['vente_id'] ?>"
                                               class="btn btn-sm btn-outline-primary btn-action"
                                               title="Voir la vente associée">
                                                <i class="bi bi-eye"></i> Voir vente
                                            </a>
                                        <?php endif; ?>
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
