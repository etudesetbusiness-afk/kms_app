<?php
require_once __DIR__ . '/../security.php';
require_once __DIR__ . '/controllers/catalogue_controller.php';

$q = trim($_GET['q'] ?? '');
$categorieId = isset($_GET['categorie_id']) ? (int)$_GET['categorie_id'] : 0;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$pageSize = 12;

$categories = catalogue_get_categories();
$productsData = catalogue_get_products([
    'q'            => $q,
    'categorie_id' => $categorieId,
], $page, $pageSize);
$products = $productsData['items'];
$hasMore = $productsData['has_more'];
$total = $productsData['total'];

$searchEndpoint = url_for('catalogue/api/search.php');
$listEndpoint = url_for('catalogue/api/produits.php');
$detailBase = url_for('catalogue/fiche.php');
$assetCss = url_for('catalogue/assets/css/catalogue.css');
$assetJs = url_for('catalogue/assets/js/catalogue.js');
?>
<?php require_once __DIR__ . '/../partials/header.php'; ?>

<link rel="stylesheet" href="<?= htmlspecialchars($assetCss) ?>">
<div class="container-fluid py-5" id="catalogue-app" data-search-endpoint="<?= htmlspecialchars($searchEndpoint) ?>" data-list-endpoint="<?= htmlspecialchars($listEndpoint) ?>" data-detail-base="<?= htmlspecialchars($detailBase) ?>">
    <div class="row justify-content-center mb-5">
        <div class="col-12 col-lg-9">
            <div class="mb-4">
                <p class="text-muted fw-semibold text-uppercase mb-2" style="font-size: 0.85rem; letter-spacing: 0.5px;">Découvrir nos produits</p>
                <h1 class="display-5 fw-700 mb-4" style="color: #1a1a2e;">Catalogue KMS</h1>
                <p class="lead text-muted mb-4">Parcourez notre sélection complète de produits de qualité.</p>
            </div>
            
            <div class="catalogue-searchbar position-relative">
                <input type="search" class="form-control form-control-lg" name="q" id="catalogue-search" 
                       placeholder="🔍 Rechercher un produit..." autocomplete="off" 
                       value="<?= htmlspecialchars($q) ?>" style="border-radius: 12px; padding: 14px 18px; font-size: 1rem;">
                <div class="catalogue-suggestions d-none" id="catalogue-suggestions"></div>
            </div>
        </div>
    </div>

    <div class="row justify-content-center mb-4">
        <div class="col-12 col-lg-9">
            <div class="catalogue-filters">
                <a class="catalogue-filter-btn <?= $categorieId === 0 ? 'active' : '' ?>" 
                   href="<?= htmlspecialchars(url_for('catalogue/index.php')) ?>">
                    Tous les produits (<?= htmlspecialchars($total) ?>)
                </a>
                <?php foreach ($categories as $cat): ?>
                    <?php $isActive = $categorieId === (int)$cat['id']; ?>
                    <a class="catalogue-filter-btn <?= $isActive ? 'active' : '' ?>" 
                       href="<?= htmlspecialchars(url_for('catalogue/index.php') . '?categorie_id=' . urlencode($cat['id']) . ($q !== '' ? ('&q=' . urlencode($q)) : '')) ?>">
                        <?= htmlspecialchars($cat['nom']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-12 col-lg-9">
            <?php if (!empty($products)): ?>
                <div class="catalogue-grid" id="catalogue-grid">
                    <?php foreach ($products as $prod): ?>
                        <?php
                            $img = catalogue_image_path($prod['image_principale']);
                            $detailUrl = $detailBase . '?slug=' . urlencode($prod['slug']);
                            $prixUnite = $prod['prix_unite'] !== null ? number_format((float)$prod['prix_unite'], 2, ',', ' ') . ' FCFA' : '—';
                            $prixGros = $prod['prix_gros'] !== null ? number_format((float)$prod['prix_gros'], 2, ',', ' ') . ' FCFA' : '—';
                        ?>
                        <a class="catalogue-card" href="<?= htmlspecialchars($detailUrl) ?>" title="<?= htmlspecialchars($prod['designation']) ?>">
                            <div class="catalogue-card__img" style="background-image: url('<?= htmlspecialchars($img) ?>');"></div>
                            <div class="catalogue-card__body">
                                <p class="catalogue-card__code"><?= htmlspecialchars($prod['code']) ?></p>
                                <h2 class="catalogue-card__title"><?= htmlspecialchars($prod['designation']) ?></h2>
                                <p class="catalogue-card__cat"><?= htmlspecialchars($prod['categorie_nom']) ?></p>
                                <div class="catalogue-card__prices">
                                    <span class="catalogue-card__price-badge">U: <?= htmlspecialchars($prixUnite) ?></span>
                                    <span class="catalogue-card__price-badge highlight">G: <?= htmlspecialchars($prixGros) ?></span>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>

                <?php if ($hasMore): ?>
                    <div class="catalogue-pagination">
                        <a href="<?= htmlspecialchars(url_for('catalogue/index.php') . '?page=' . ($page + 1) . ($q !== '' ? ('&q=' . urlencode($q)) : '') . ($categorieId ? ('&categorie_id=' . $categorieId) : '')) ?>">
                            Charger plus de produits
                        </a>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="catalogue-empty">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20.325 15.582c.805-1.696.805-2.466 0-4.162C19.395 5.743 16.618 3 12.5 3c-4.117 0-6.895 2.743-7.825 8.42-.805 1.696-.805 2.466 0 4.162C5.605 21.257 8.382 24 12.5 24c4.117 0 6.895-2.743 7.825-8.42z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.5 12a3.5 3.5 0 1 1-7 0 3.5 3.5 0 0 1 7 0z" />
                    </svg>
                    <h3>Aucun produit trouvé</h3>
                    <p>Essayez une autre recherche ou une autre catégorie</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script defer src="<?= htmlspecialchars($assetJs) ?>"></script>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>
