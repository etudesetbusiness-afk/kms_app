<?php
/**
 * produits/list.php
 * Liste des produits internes (catalogue privé)
 * Module de gestion stock/ventes/achats
 */
require_once __DIR__ . '/../security.php';
require_once __DIR__ . '/../lib/pagination.php';
require_once __DIR__ . '/../lib/user_preferences.php';

exigerConnexion();
exigerPermission('PRODUITS_LIRE');

global $pdo;

$utilisateur = utilisateurConnecte();
$user_id = $utilisateur['id'] ?? null;

// ============================================================
// FILTRES & PAGINATION
// ============================================================
$search = trim($_GET['search'] ?? '');
$famille_id = isset($_GET['famille_id']) ? (int)$_GET['famille_id'] : 0;
$actif = $_GET['actif'] ?? '1'; // 1 = actifs, 0 = inactifs, '' = tous

// Préférences utilisateur (tri, pagination)
if ($user_id) {
    $prefs = updateUserPreferencesFromGet($user_id, 'produits_internes', $_GET, ['famille', 'actif']);
    $sortBy = $prefs['sort_by'];
    $sortDir = $prefs['sort_dir'];
    $per_page = $prefs['per_page'];
} else {
    $sortBy = $_GET['sort_by'] ?? 'designation';
    $sortDir = $_GET['sort_dir'] ?? 'ASC';
    $per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 25;
}

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

// Colonnes triables
$sortableColumns = [
    'code_produit' => 'p.code_produit',
    'designation' => 'p.designation',
    'famille' => 'f.nom',
    'prix_vente' => 'p.prix_vente',
    'stock_actuel' => 'p.stock_actuel',
    'actif' => 'p.actif'
];

$orderColumn = $sortableColumns[$sortBy] ?? 'p.designation';
$orderDir = strtoupper($sortDir) === 'DESC' ? 'DESC' : 'ASC';

// ============================================================
// REQUÊTE PRINCIPALE
// ============================================================
$where = [];
$params = [];

if ($search !== '') {
    $where[] = '(p.code_produit LIKE :search OR p.designation LIKE :search OR p.description LIKE :search)';
    $params['search'] = '%' . $search . '%';
}

if ($famille_id > 0) {
    $where[] = 'p.famille_id = :famille_id';
    $params['famille_id'] = $famille_id;
}

if ($actif === '1') {
    $where[] = 'p.actif = 1';
} elseif ($actif === '0') {
    $where[] = 'p.actif = 0';
}

$whereSql = '';
if (!empty($where)) {
    $whereSql = 'WHERE ' . implode(' AND ', $where);
}

// Comptage total
$sqlCount = "
    SELECT COUNT(*) 
    FROM produits p
    $whereSql
";
$stmtCount = $pdo->prepare($sqlCount);
$stmtCount->execute($params);
$total_items = (int)$stmtCount->fetchColumn();

// Pagination
$pagination = getPaginationParams($_GET, $total_items, $per_page);
$page = $pagination['page'];
$per_page = $pagination['per_page'];
$offset = $pagination['offset'];

// Récupération des produits
$sql = "
    SELECT 
        p.id,
        p.code_produit,
        p.designation,
        p.famille_id,
        p.prix_achat,
        p.prix_vente,
        p.stock_actuel,
        p.seuil_alerte,
        p.actif,
        p.image_path,
        f.nom AS famille_nom,
        fournisseur.nom AS fournisseur_nom
    FROM produits p
    LEFT JOIN familles_produits f ON p.famille_id = f.id
    LEFT JOIN fournisseurs fournisseur ON p.fournisseur_id = fournisseur.id
    $whereSql
    ORDER BY $orderColumn $orderDir
    LIMIT :limit OFFSET :offset
";

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $val) {
    $stmt->bindValue(':' . $key, $val);
}
$stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$produits = $stmt->fetchAll();

// ============================================================
// STATISTIQUES
// ============================================================
// Total produits
$stmtStats = $pdo->query("SELECT COUNT(*) FROM produits");
$stats_total = (int)$stmtStats->fetchColumn();

// Produits actifs
$stmtStats = $pdo->query("SELECT COUNT(*) FROM produits WHERE actif = 1");
$stats_actifs = (int)$stmtStats->fetchColumn();

// Produits en rupture (stock_actuel <= 0)
$stmtStats = $pdo->query("SELECT COUNT(*) FROM produits WHERE stock_actuel <= 0");
$stats_rupture = (int)$stmtStats->fetchColumn();

// Produits en alerte (0 < stock_actuel <= seuil_alerte)
$stmtStats = $pdo->query("SELECT COUNT(*) FROM produits WHERE stock_actuel > 0 AND stock_actuel <= seuil_alerte");
$stats_alerte = (int)$stmtStats->fetchColumn();

// Valeur totale du stock (stock_actuel * prix_achat)
$stmtStats = $pdo->query("SELECT SUM(stock_actuel * prix_achat) FROM produits WHERE actif = 1");
$valeur_stock = (float)$stmtStats->fetchColumn();

// Familles
$stmtFamilles = $pdo->query("SELECT id, nom FROM familles_produits ORDER BY nom");
$familles = $stmtFamilles->fetchAll();

// ============================================================
// PERMISSIONS
// ============================================================
$peutCreer = peut('PRODUITS_CREER');
$peutModifier = peut('PRODUITS_MODIFIER');
$peutSupprimer = peut('PRODUITS_SUPPRIMER');

include __DIR__ . '/../partials/header.php';
include __DIR__ . '/../partials/sidebar.php';
?>

<div class="container-fluid">
    <!-- En-tête -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">
                <i class="bi bi-box-seam-fill"></i> Produits Internes
            </h1>
            <p class="text-muted small mb-0">Gestion du catalogue privé (stock, ventes, achats)</p>
        </div>
        <?php if ($peutCreer): ?>
            <a href="<?= url_for('produits/edit.php') ?>" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Nouveau produit
            </a>
        <?php endif; ?>
    </div>

    <!-- Statistiques -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="rounded-circle bg-primary bg-opacity-10 p-3">
                                <i class="bi bi-box-seam fs-4 text-primary"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Total Produits</h6>
                            <h3 class="mb-0"><?= $stats_total ?></h3>
                            <small class="text-muted"><?= $stats_actifs ?> actifs</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="rounded-circle bg-danger bg-opacity-10 p-3">
                                <i class="bi bi-exclamation-triangle fs-4 text-danger"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Ruptures</h6>
                            <h3 class="mb-0 text-danger"><?= $stats_rupture ?></h3>
                            <small class="text-muted">Stock ≤ 0</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="rounded-circle bg-warning bg-opacity-10 p-3">
                                <i class="bi bi-bell fs-4 text-warning"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Alertes</h6>
                            <h3 class="mb-0 text-warning"><?= $stats_alerte ?></h3>
                            <small class="text-muted">Seuil atteint</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="rounded-circle bg-success bg-opacity-10 p-3">
                                <i class="bi bi-currency-dollar fs-4 text-success"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Valeur Stock</h6>
                            <h3 class="mb-0"><?= number_format($valeur_stock, 0, ',', ' ') ?></h3>
                            <small class="text-muted">FCFA (PA)</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label small">Recherche</label>
                    <input type="text" name="search" class="form-control" 
                           value="<?= htmlspecialchars($search) ?>" 
                           placeholder="Code, désignation, description...">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Famille</label>
                    <select name="famille_id" class="form-select">
                        <option value="0">Toutes les familles</option>
                        <?php foreach ($familles as $f): ?>
                            <option value="<?= $f['id'] ?>" <?= $famille_id === (int)$f['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($f['nom']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small">Statut</label>
                    <select name="actif" class="form-select">
                        <option value="">Tous</option>
                        <option value="1" <?= $actif === '1' ? 'selected' : '' ?>>Actifs</option>
                        <option value="0" <?= $actif === '0' ? 'selected' : '' ?>>Inactifs</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Filtrer
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tableau -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <span><strong><?= $total_items ?></strong> résultat(s)</span>
            <div>
                <label class="small me-2">Par page:</label>
                <select class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()" name="per_page" form="filterForm">
                    <option value="25" <?= $per_page === 25 ? 'selected' : '' ?>>25</option>
                    <option value="50" <?= $per_page === 50 ? 'selected' : '' ?>>50</option>
                    <option value="100" <?= $per_page === 100 ? 'selected' : '' ?>>100</option>
                </select>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Image</th>
                        <th>Code</th>
                        <th>Désignation</th>
                        <th>Famille</th>
                        <th class="text-end">Prix Vente</th>
                        <th class="text-center">Stock</th>
                        <th class="text-center">Seuil</th>
                        <th class="text-center">Statut</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($produits)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                Aucun produit trouvé
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($produits as $p): ?>
                            <?php
                            $stock_actuel = (int)$p['stock_actuel'];
                            $seuil_alerte = (int)$p['seuil_alerte'];
                            
                            // Badge stock
                            if ($stock_actuel <= 0) {
                                $stockBadge = '<span class="badge bg-danger">Rupture</span>';
                            } elseif ($stock_actuel <= $seuil_alerte) {
                                $stockBadge = '<span class="badge bg-warning text-dark">Alerte</span>';
                            } else {
                                $stockBadge = '<span class="badge bg-success">OK</span>';
                            }
                            ?>
                            <tr>
                                <td>
                                    <?php if ($p['image_path']): ?>
                                        <img src="<?= url_for($p['image_path']) ?>" 
                                             alt="<?= htmlspecialchars($p['designation']) ?>" 
                                             class="rounded" 
                                             style="width: 40px; height: 40px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="bg-light rounded d-flex align-items-center justify-content-center" 
                                             style="width: 40px; height: 40px;">
                                            <i class="bi bi-image text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><code class="small"><?= htmlspecialchars($p['code_produit']) ?></code></td>
                                <td>
                                    <div class="fw-semibold"><?= htmlspecialchars($p['designation']) ?></div>
                                    <?php if ($p['fournisseur_nom']): ?>
                                        <small class="text-muted">Fournisseur: <?= htmlspecialchars($p['fournisseur_nom']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-secondary bg-opacity-10 text-dark">
                                        <?= htmlspecialchars($p['famille_nom'] ?? 'N/A') ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <strong><?= number_format($p['prix_vente'], 0, ',', ' ') ?></strong> FCFA
                                    <?php if ($p['prix_achat'] > 0): ?>
                                        <br><small class="text-muted">PA: <?= number_format($p['prix_achat'], 0, ',', ' ') ?> FCFA</small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="fw-bold fs-5"><?= $stock_actuel ?></div>
                                    <?= $stockBadge ?>
                                </td>
                                <td class="text-center text-muted">
                                    <?= $seuil_alerte ?>
                                </td>
                                <td class="text-center">
                                    <?php if ((int)$p['actif'] === 1): ?>
                                        <span class="badge bg-success">Actif</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactif</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <?php if ($peutModifier): ?>
                                            <a href="<?= url_for('produits/edit.php?id=' . $p['id']) ?>" 
                                               class="btn btn-outline-primary" 
                                               title="Modifier">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="<?= url_for('stock/mouvements.php?produit_id=' . $p['id']) ?>" 
                                               class="btn btn-outline-success" 
                                               title="Ajuster stock">
                                                <i class="bi bi-arrow-left-right"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($pagination['total_pages'] > 1): ?>
            <div class="card-footer bg-white">
                <?= renderPaginationControls($pagination, $_GET) ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Form invisible pour per_page -->
<form id="filterForm" method="GET" action="" style="display:none;">
    <?php foreach ($_GET as $key => $value): ?>
        <?php if ($key !== 'per_page'): ?>
            <input type="hidden" name="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars($value) ?>">
        <?php endif; ?>
    <?php endforeach; ?>
</form>

<?php include __DIR__ . '/../partials/footer.php'; ?>
