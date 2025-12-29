<?php
// produits/list.php
require_once __DIR__ . '/../security.php';
exigerConnexion();
exigerPermission('PRODUITS_LIRE');

global $pdo;

// ---------------------------------------------------------
// Filtres de recherche
// ---------------------------------------------------------
$q      = trim($_GET['q'] ?? '');
$actif  = $_GET['actif'] ?? '1'; // 1 = seulement actifs, '' = tous

$where  = [];
$params = [];

// Recherche texte sur code_produit ou designation
if ($q !== '') {
    $where[]        = '(p.code_produit LIKE :q OR p.designation LIKE :q)';
    $params['q']    = '%' . $q . '%';
}

// Filtre "actifs seulement"
if ($actif === '1') {
    $where[] = 'p.actif = 1';
}

$whereSql = '';
if (!empty($where)) {
    $whereSql = 'WHERE ' . implode(' AND ', $where);
}

// ---------------------------------------------------------
// Récupération des produits
// ---------------------------------------------------------
$sql = "
    SELECT
        p.id,
        p.code_produit,
        p.designation,
        p.prix_vente,
        p.actif
    FROM produits p
    $whereSql
    ORDER BY p.designation
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$produits = $stmt->fetchAll();

// ---------------------------------------------------------
// Récupération des stocks réels par produit
// ---------------------------------------------------------
// On agrège tous les mouvements de stock : SUM(quantite) par produit_id
$stocks = [];
// Calculer les stocks à partir de la table canonique `stocks_mouvements`.
// Utiliser TRY/CATCH : si la table n'existe pas, on retombe sur `produits.stock_actuel`.
try {
    $stmtStock = $pdo->query(
        "SELECT produit_id, COALESCE(SUM(
            CASE
                WHEN type_mouvement = 'ENTREE' THEN quantite
                WHEN type_mouvement = 'SORTIE' THEN -quantite
                WHEN type_mouvement = 'AJUSTEMENT' THEN quantite
                ELSE 0
            END
        ),0) AS stock_qte
        FROM stocks_mouvements
        GROUP BY produit_id"
    );

    foreach ($stmtStock->fetchAll() as $row) {
        $stocks[(int)$row['produit_id']] = (float)$row['stock_qte'];
    }
} catch (Exception $e) {
    // fallback : utiliser produits.stock_actuel si la table des mouvements est absente
    foreach ($produits as $p) {
        $stocks[(int)$p['id']] = (int)$p['stock_actuel'];
    }
}

include __DIR__ . '/../partials/header.php';
include __DIR__ . '/../partials/sidebar.php';
?>

<div class="container-fluid">

    <div class="list-page-header d-flex justify-content-between align-items-center">
        <h1 class="list-page-title h3">
            <i class="bi bi-box-seam-fill"></i>
            Produits & stock
            <span class="count-badge ms-2"><?= count($produits) ?></span>
        </h1>
        <a href="<?= url_for('produits/edit.php') ?>" class="btn btn-primary btn-add-new">
            <i class="bi bi-plus-circle me-2"></i> Nouveau produit
        </a>
    </div>

    <!-- Filtres -->
    <div class="card filter-card">
        <div class="card-body">
            <form method="get" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small">Recherche</label>
                    <input type="text"
                           name="q"
                           class="form-control"
                           placeholder="Code produit, désignation..."
                           value="<?= htmlspecialchars($q) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Statut</label>
                    <select name="actif" class="form-select">
                        <option value="1" <?= $actif === '1' ? 'selected' : '' ?>>Produits actifs uniquement</option>
                        <option value="" <?= $actif === '' ? 'selected' : '' ?>>Tous les produits</option>
                    </select>
                </div>
                <div class="col-md-5 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-filter">
                        <i class="bi bi-search me-1"></i> Filtrer
                    </button>
                    <a href="<?= url_for('produits/list.php') ?>" class="btn btn-outline-secondary btn-filter">
                        <i class="bi bi-arrow-clockwise me-1"></i> Réinitialiser
                    </a>
                    <a href="<?= url_for('stock/etat.php') ?>" class="btn btn-info btn-filter ms-auto">
                        <i class="bi bi-box-seam me-1"></i> État global du stock
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Liste des produits -->
    <div class="card data-table-card">
        <div class="card-body">
            <?php if (empty($produits)): ?>
                <div class="empty-state">
                    <i class="bi bi-box-seam"></i>
                    <h5>Aucun produit trouvé</h5>
                    <p>Aucun produit ne correspond aux filtres sélectionnés.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table modern-table">
                        <thead class="table-light">
                        <tr>
                            <th style="width: 12%;">Code</th>
                            <th>Libellé / désignation</th>
                            <th style="width: 12%;" class="text-end">Prix vente</th>
                            <th style="width: 12%;" class="text-end">Stock actuel</th>
                            <th style="width: 8%;"  class="text-center">Statut</th>
                            <th style="width: 10%;" class="text-end">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($produits as $p): ?>
                            <?php
                            $id           = (int)$p['id'];
                            $stockActuel  = $stocks[$id] ?? 0;
                            ?>
                            <tr>
                                <td>
                                    <span class="modern-badge badge-status-primary">
                                        <i class="bi bi-upc-scan"></i>
                                        <?= htmlspecialchars($p['code_produit']) ?>
                                    </span>
                                </td>
                                <td class="fw-semibold">
                                    <i class="bi bi-box me-1 text-muted"></i>
                                    <?= htmlspecialchars($p['designation']) ?>
                                </td>
                                <td class="text-end">
                                    <span class="text-success fw-bold">
                                        <?= number_format((float)$p['prix_vente'], 0, ',', ' ') ?> FCFA
                                    </span>
                                </td>
                                <td class="text-end">
                                    <span class="badge <?= $stockActuel > 0 ? 'bg-success' : 'bg-danger' ?> fs-6 px-3 py-2">
                                        <?= number_format($stockActuel, 3, ',', ' ') ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <?php if ((int)$p['actif'] === 1): ?>
                                        <span class="modern-badge badge-status-success">
                                            <i class="bi bi-check-circle-fill"></i>
                                            Actif
                                        </span>
                                    <?php else: ?>
                                        <span class="modern-badge badge-status-secondary">
                                            <i class="bi bi-x-circle-fill"></i>
                                            Inactif
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="action-btn-group">
                                        <a href="<?= url_for('produits/edit.php?id=' . $id) ?>" 
                                           class="btn btn-sm btn-outline-primary btn-action"
                                           title="Modifier">
                                            <i class="bi bi-pencil-square"></i> Modifier
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
