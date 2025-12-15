<?php
/**
 * admin/catalogue/produits.php
 * Liste et gestion des produits du catalogue (back-office)
 */
require_once __DIR__ . '/../../security.php';
require_once __DIR__ . '/../../lib/pagination.php';
require_once __DIR__ . '/../../lib/user_preferences.php';

exigerConnexion();
exigerPermission('PRODUITS_LIRE');

global $pdo;

$utilisateur = utilisateurConnecte();
$user_id = $utilisateur['id'] ?? null;

// Paramètres de filtrage
$search = trim($_GET['search'] ?? '');
$categorie_id = isset($_GET['categorie_id']) ? (int)$_GET['categorie_id'] : 0;
$actif = $_GET['actif'] ?? '';

// Préférences utilisateur
if ($user_id) {
    $prefs = updateUserPreferencesFromGet($user_id, 'catalogue_produits', $_GET, ['categorie', 'actif']);
    $sortBy = $prefs['sort_by'];
    $sortDir = $prefs['sort_dir'];
    $per_page = $prefs['per_page'];
} else {
    $sortBy = $_GET['sort_by'] ?? 'designation';
    $sortDir = ($_GET['sort_dir'] ?? 'asc') === 'asc' ? 'asc' : 'desc';
    $per_page = 25;
}

// Récupérer les catégories pour le filtre
$categories = $pdo->query("SELECT id, nom FROM catalogue_categories ORDER BY nom ASC")->fetchAll();

// Construction de la requête
$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(cp.designation LIKE ? OR cp.code LIKE ? OR cp.description LIKE ?)";
    $searchTerm = '%' . $search . '%';
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($categorie_id > 0) {
    $where[] = "cp.categorie_id = ?";
    $params[] = $categorie_id;
}

if ($actif !== '') {
    $where[] = "cp.actif = ?";
    $params[] = (int)$actif;
}

$whereSql = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

// Tri
$allowedSort = ['designation', 'code', 'prix_unite', 'categorie', 'actif', 'created_at'];
if (!in_array($sortBy, $allowedSort)) {
    $sortBy = 'designation';
}

$orderSql = "ORDER BY ";
if ($sortBy === 'categorie') {
    $orderSql .= "cc.nom $sortDir, cp.designation ASC";
} else {
    $orderSql .= "cp.$sortBy $sortDir";
}

// Compter total
$count_sql = "
    SELECT COUNT(*) as cnt
    FROM catalogue_produits cp
    LEFT JOIN catalogue_categories cc ON cp.categorie_id = cc.id
    $whereSql
";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_count = $count_stmt->fetch()['cnt'];

// Pagination
$pagination = getPaginationParams($_GET, $total_count, $per_page);

// Requête principale
$sql = "
    SELECT cp.*, cc.nom AS categorie_nom
    FROM catalogue_produits cp
    LEFT JOIN catalogue_categories cc ON cp.categorie_id = cc.id
    $whereSql
    $orderSql
    " . getPaginationLimitClause($pagination['offset'], $pagination['per_page']);

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$produits = $stmt->fetchAll();

include __DIR__ . '/../../partials/header.php';
include __DIR__ . '/../../partials/sidebar.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <!-- En-tête -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1">Gestion Catalogue - Produits</h1>
                <p class="text-muted mb-0">Gérer les produits affichés dans le catalogue public</p>
            </div>
            <?php if (aPermission('PRODUITS_CREER')): ?>
                <a href="<?= url_for('admin/catalogue/produit_edit.php') ?>" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-1"></i> Nouveau Produit
                </a>
            <?php endif; ?>
        </div>

        <!-- Filtres -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <!-- Recherche -->
                    <div class="col-md-4">
                        <label class="form-label small text-muted">Recherche</label>
                        <input type="search" name="search" class="form-control" 
                               placeholder="Code, désignation, description..." 
                               value="<?= htmlspecialchars($search) ?>">
                    </div>

                    <!-- Catégorie -->
                    <div class="col-md-3">
                        <label class="form-label small text-muted">Catégorie</label>
                        <select name="categorie_id" class="form-select">
                            <option value="0">Toutes les catégories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= $categorie_id == $cat['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['nom']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Statut -->
                    <div class="col-md-2">
                        <label class="form-label small text-muted">Statut</label>
                        <select name="actif" class="form-select">
                            <option value="">Tous</option>
                            <option value="1" <?= $actif === '1' ? 'selected' : '' ?>>Actifs</option>
                            <option value="0" <?= $actif === '0' ? 'selected' : '' ?>>Inactifs</option>
                        </select>
                    </div>

                    <!-- Boutons -->
                    <div class="col-md-3 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary flex-fill">
                            <i class="bi bi-search me-1"></i> Filtrer
                        </button>
                        <a href="<?= url_for('admin/catalogue/produits.php') ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-clockwise"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Statistiques -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h6 class="card-title">Total Produits</h6>
                        <h2 class="mb-0"><?= $total_count ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h6 class="card-title">Produits Actifs</h6>
                        <?php
                        $stmt = $pdo->query("SELECT COUNT(*) FROM catalogue_produits WHERE actif = 1");
                        $actifs = $stmt->fetchColumn();
                        ?>
                        <h2 class="mb-0"><?= $actifs ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h6 class="card-title">Catégories</h6>
                        <h2 class="mb-0"><?= count($categories) ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th style="width: 60px;">Image</th>
                                <th>
                                    <a href="?<?= buildPaginationUrl(['sort_by' => 'code', 'sort_dir' => $sortBy === 'code' && $sortDir === 'asc' ? 'desc' : 'asc']) ?>">
                                        Code <?php if ($sortBy === 'code'): ?>
                                            <i class="bi bi-arrow-<?= $sortDir === 'asc' ? 'up' : 'down' ?>"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="?<?= buildPaginationUrl(['sort_by' => 'designation', 'sort_dir' => $sortBy === 'designation' && $sortDir === 'asc' ? 'desc' : 'asc']) ?>">
                                        Désignation <?php if ($sortBy === 'designation'): ?>
                                            <i class="bi bi-arrow-<?= $sortDir === 'asc' ? 'up' : 'down' ?>"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th>Catégorie</th>
                                <th>
                                    <a href="?<?= buildPaginationUrl(['sort_by' => 'prix_unite', 'sort_dir' => $sortBy === 'prix_unite' && $sortDir === 'asc' ? 'desc' : 'asc']) ?>">
                                        Prix Unitaire <?php if ($sortBy === 'prix_unite'): ?>
                                            <i class="bi bi-arrow-<?= $sortDir === 'asc' ? 'up' : 'down' ?>"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th>Statut</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($produits) === 0): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                        Aucun produit trouvé
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($produits as $p): ?>
                                    <tr>
                                        <td>
                                            <?php if ($p['image_principale']): ?>
                                                <img src="<?= htmlspecialchars(url_for('uploads/catalogue/' . $p['image_principale'])) ?>" 
                                                     alt="" class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;">
                                            <?php else: ?>
                                                <div class="bg-light d-flex align-items-center justify-content-center" 
                                                     style="width: 50px; height: 50px; border-radius: 4px;">
                                                    <i class="bi bi-image text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td><code><?= htmlspecialchars($p['code']) ?></code></td>
                                        <td>
                                            <strong><?= htmlspecialchars($p['designation']) ?></strong>
                                            <?php if ($p['description']): ?>
                                                <br><small class="text-muted"><?= htmlspecialchars(mb_substr($p['description'], 0, 80)) ?>...</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary"><?= htmlspecialchars($p['categorie_nom'] ?? 'N/A') ?></span>
                                        </td>
                                        <td>
                                            <?php if ($p['prix_unite']): ?>
                                                <?= number_format($p['prix_unite'], 0, ',', ' ') ?> FCFA
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $p['actif'] ? 'success' : 'secondary' ?>">
                                                <?= $p['actif'] ? 'Actif' : 'Inactif' ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm">
                                                <a href="<?= url_for('catalogue/fiche.php?slug=' . urlencode($p['slug'])) ?>" 
                                                   class="btn btn-outline-secondary" target="_blank" title="Voir public">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <?php if (aPermission('PRODUITS_MODIFIER')): ?>
                                                    <a href="<?= url_for('admin/catalogue/produit_edit.php?id=' . $p['id']) ?>" 
                                                       class="btn btn-outline-primary" title="Modifier">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <?php if (aPermission('PRODUITS_SUPPRIMER')): ?>
                                                    <button class="btn btn-outline-danger" 
                                                            onclick="if(confirm('Supprimer ce produit ?')) window.location.href='<?= url_for('admin/catalogue/produit_delete.php?id=' . $p['id'] . '&csrf_token=' . genererCsrf()) ?>'"
                                                            title="Supprimer">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
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
                <?php if ($total_count > 0): ?>
                    <?= renderPaginationControls($pagination, $total_count) ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../partials/footer.php'; ?>
