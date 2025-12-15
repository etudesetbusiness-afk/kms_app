<?php
// livraisons/list.php - avec recherche texte, tri, pagination, et préférences utilisateur
require_once __DIR__ . '/../security.php';
require_once __DIR__ . '/../lib/filters_helpers.php';
require_once __DIR__ . '/../lib/pagination.php';
require_once __DIR__ . '/../lib/user_preferences.php';
require_once __DIR__ . '/../lib/date_helpers.php';
require_once __DIR__ . '/../lib/cache.php';
exigerConnexion();
exigerPermission('VENTES_LIRE');

global $pdo;

$utilisateur = utilisateurConnecte();
$user_id = $utilisateur['id'] ?? null;

// Gestion des dates avec validation
$date_start = validateAndFormatDate($_GET['date_start'] ?? $_GET['date_debut'] ?? null);
$date_end = validateAndFormatDate($_GET['date_end'] ?? $_GET['date_fin'] ?? null);

// Si aucune date fournie, utiliser le préset par défaut (30 jours)
if (!$date_start || !$date_end) {
    $range = getDateRangePreset('last_30d');
    $date_start = $range['start'];
    $date_end = $range['end'];
}

$clientId = isset($_GET['client_id']) ? (int)$_GET['client_id'] : 0;
$signe    = $_GET['signe'] ?? ''; // '', '0', '1'
$search   = trim($_GET['search'] ?? '');

// Charger les préférences utilisateur et les appliquer
if ($user_id) {
    $prefs = updateUserPreferencesFromGet($user_id, 'livraisons', $_GET, ['date', 'client', 'statut']);
    $sortBy = $prefs['sort_by'];
    $sortDir = $prefs['sort_dir'];
    $per_page = $prefs['per_page'];
} else {
    $sortBy = $_GET['sort_by'] ?? 'date';
    $sortDir = ($_GET['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
    $per_page = 25;
}

// Clients pour filtre (cachés 24h)
$clients = cached('livraisons_clients_list', function() use ($pdo) {
    return $pdo->query("SELECT id, nom FROM clients ORDER BY nom")->fetchAll();
}, 86400);

$where  = [];
$params = [];

// Filtres de dates (appliqués par défaut avec préset 30j)
if ($date_start && $date_end) {
    $where[] = "b.date_bl >= ?";
    $params[] = $date_start;
    $where[] = "b.date_bl <= CONCAT(?, ' 23:59:59')";
    $params[] = $date_end;
}
if ($clientId > 0) {
    $where[] = "b.client_id = ?";
    $params[] = $clientId;
}
if ($signe !== '' && in_array($signe, ['0','1'], true)) {
    $where[] = "b.signe_client = ?";
    $params[] = (int)$signe;
}

// Recherche texte
if (!empty($search)) {
    $where[] = "(b.numero LIKE ? OR c.nom LIKE ? OR v.numero LIKE ?)";
    $searchTerm = '%' . $search . '%';
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

$whereSql = '';
if (!empty($where)) {
    $whereSql = 'WHERE ' . implode(' AND ', $where);
}

// Tri dynamique
$orderSql = 'ORDER BY b.date_bl DESC, b.id DESC';
if ($sortBy === 'client') {
    $orderSql = "ORDER BY c.nom $sortDir, b.date_bl DESC";
} elseif ($sortBy === 'numero') {
    $orderSql = "ORDER BY b.numero $sortDir, b.date_bl DESC";
} else {
    $orderSql = "ORDER BY b.date_bl $sortDir, b.id DESC";
}

// Récupérer le nombre total de lignes
$count_sql = "
    SELECT COUNT(*) as cnt
    FROM bons_livraison b
    JOIN clients c ON c.id = b.client_id
    LEFT JOIN ventes v ON v.id = b.vente_id
    $whereSql
";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_count = $count_stmt->fetch()['cnt'];

// Récupérer les paramètres de pagination
$pagination = getPaginationParams($_GET, $total_count, $per_page);

// Requête paginée
$sql = "
    SELECT
        b.*,
        c.nom AS client_nom,
        v.numero AS vente_numero
    FROM bons_livraison b
    JOIN clients c ON c.id = b.client_id
    LEFT JOIN ventes v ON v.id = b.vente_id
    $whereSql
    $orderSql
    " . getPaginationLimitClause($pagination['offset'], $pagination['per_page']);

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
            <span class="count-badge ms-2"><?= $pagination['total_count'] ?></span>
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
            <!-- Date Range Picker Component (Phase 3.3) -->
            <?php 
            $date_start_input = htmlspecialchars($date_start);
            $date_end_input = htmlspecialchars($date_end);
            include __DIR__ . '/../components/date_range_picker.html'; 
            ?>
            
            <form method="get" class="row g-3 align-items-end" id="filter_form">
                <!-- Recherche texte -->
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">
                        <i class="bi bi-search"></i> Rechercher
                    </label>
                    <input type="text" name="search" class="form-control" 
                           placeholder="N° BL, client, vente..."
                           value="<?= htmlspecialchars($search) ?>">
                    <small class="text-muted d-block mt-1">Cherche dans: N° BL, client, N° vente</small>
                </div>

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
                    <a href="<?= url_for('livraisons/export_excel.php?date_debut=' . urlencode($date_start) . '&date_fin=' . urlencode($date_end) . '&client_id=' . urlencode($clientId) . '&signe=' . urlencode($signe) . '&search=' . urlencode($search)) ?>" class="btn btn-success btn-filter">
                        <i class="bi bi-file-earmark-excel me-1"></i> Exporter
                    </a>
                </div>

                <!-- Affichage des filtres actifs -->
                <?php
                $activeFilters = [];
                if (!empty($search)) $activeFilters['Recherche'] = $search;
                if ($date_start) $activeFilters['Du'] = $date_start;
                if ($date_end) $activeFilters['Au'] = $date_end;
                if ($clientId > 0) {
                    $clientName = 'Inconnu';
                    foreach ($clients as $c) {
                        if ($c['id'] == $clientId) {
                            $clientName = $c['nom'];
                            break;
                        }
                    }
                    $activeFilters['Client'] = $clientName;
                }
                if ($signe === '1') $activeFilters['Signature'] = 'Signés';
                elseif ($signe === '0') $activeFilters['Signature'] = 'Non signés';
                ?>
                <?php if (!empty($activeFilters)): ?>
                    <div class="col-12 mt-2 pt-2 border-top">
                        <small class="text-muted d-block mb-2">Filtres actifs:</small>
                        <div class="d-flex gap-2 flex-wrap">
                            <?php foreach ($activeFilters as $label => $value): ?>
                                <span class="badge bg-info text-dark">
                                    <strong><?= htmlspecialchars($label) ?></strong>: 
                                    <?= htmlspecialchars(substr($value, 0, 25)) ?><?= strlen($value) > 25 ? '...' : '' ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
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
                            <th>
                                <a href="?<?= http_build_query(array_merge($_GET, ['sort_by' => 'date', 'sort_dir' => $sortBy === 'date' && $sortDir === 'desc' ? 'asc' : 'desc'])) ?>" 
                                   class="text-decoration-none text-dark">
                                    Date BL 
                                    <?php if ($sortBy === 'date'): ?>
                                        <i class="bi <?= $sortDir === 'desc' ? 'bi-arrow-down' : 'bi-arrow-up' ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th>Vente</th>
                            <th>
                                <a href="?<?= http_build_query(array_merge($_GET, ['sort_by' => 'client', 'sort_dir' => $sortBy === 'client' && $sortDir === 'desc' ? 'asc' : 'desc'])) ?>" 
                                   class="text-decoration-none text-dark">
                                    Client 
                                    <?php if ($sortBy === 'client'): ?>
                                        <i class="bi <?= $sortDir === 'desc' ? 'bi-arrow-down' : 'bi-arrow-up' ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
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
                
                <!-- Contrôles de pagination (Phase 3.1) -->
                <?php echo renderPaginationControls($pagination, $_GET); ?>
            <?php endif; ?>
        </div>
    </div>

</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>
