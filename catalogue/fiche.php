<?php
require_once __DIR__ . '/../security.php';
require_once __DIR__ . '/controllers/catalogue_controller.php';

$slug = trim($_GET['slug'] ?? '');
if ($slug === '') {
    http_response_code(404);
    echo 'Produit introuvable.';
    exit;
}

$produit = catalogue_get_product_by_slug($slug);
if (!$produit) {
    http_response_code(404);
    echo 'Produit introuvable.';
    exit;
}

$caracs = [];
if (!empty($produit['caracteristiques_json'])) {
    $decoded = json_decode($produit['caracteristiques_json'], true);
    if (is_array($decoded)) {
        $caracs = $decoded;
    }
}

$galerie = [];
if (!empty($produit['galerie_images'])) {
    $decoded = json_decode($produit['galerie_images'], true);
    if (is_array($decoded)) {
        $galerie = $decoded;
    }
}

$related = catalogue_get_related((int)$produit['categorie_id'], (int)$produit['id'], 12);

$imgPrincipal = catalogue_image_path($produit['image_principale']);
$prixUnite = $produit['prix_unite'] !== null ? number_format((float)$produit['prix_unite'], 2, ',', ' ') . ' FCFA' : '—';
$prixGros = $produit['prix_gros'] !== null ? number_format((float)$produit['prix_gros'], 2, ',', ' ') . ' FCFA' : '—';

$assetCss = url_for('catalogue/assets/css/catalogue.css');
$assetJs = url_for('catalogue/assets/js/catalogue.js');
$detailBase = url_for('catalogue/fiche.php');
?>
<?php require_once __DIR__ . '/../partials/header.php'; ?>
<link rel="stylesheet" href="<?= htmlspecialchars($assetCss) ?>">

<div class="container-fluid py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-9">
            <a href="<?= htmlspecialchars(url_for('catalogue/index.php')) ?>" class="catalogue-back-btn">
                <span>←</span> Retour au catalogue
            </a>

            <div class="row g-4 mb-4">
                <div class="col-12 col-lg-6">
                    <div class="catalogue-hero">
                        <img src="<?= htmlspecialchars($imgPrincipal) ?>" alt="<?= htmlspecialchars($produit['designation']) ?>" class="img-fluid rounded" style="width: 100%; display: block;">
                        <?php if (!empty($galerie)): ?>
                            <div class="catalogue-thumbs">
                                <?php foreach ($galerie as $thumb): ?>
                                    <?php $thumbPath = catalogue_image_path($thumb); ?>
                                    <img src="<?= htmlspecialchars($thumbPath) ?>" alt="vignette" class="catalogue-thumb" loading="lazy">
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-12 col-lg-6">
                    <div class="catalogue-fiche-header">
                        <span class="code-badge"><?= htmlspecialchars($produit['code']) ?></span>
                        <p class="breadcrumb-text"><?= htmlspecialchars($produit['categorie_nom']) ?></p>
                        <h1><?= htmlspecialchars($produit['designation']) ?></h1>
                        
                        <div class="catalogue-fiche-prices">
                            <div class="catalogue-fiche-price">
                                <label>Prix à l'unité</label>
                                <div class="price"><?= htmlspecialchars($prixUnite) ?></div>
                            </div>
                            <div class="catalogue-fiche-price">
                                <label>Prix en gros</label>
                                <div class="price"><?= htmlspecialchars($prixGros) ?></div>
                            </div>
                        </div>

                        <?php if (!empty($produit['description'])): ?>
                            <div class="mt-4">
                                <h3 style="font-size: 1.1rem; font-weight: 700; color: #1a1a2e; margin-bottom: 12px;">Description</h3>
                                <p class="text-muted" style="line-height: 1.6;">
                                    <?= nl2br(htmlspecialchars($produit['description'])) ?>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if (!empty($caracs)): ?>
                <div class="catalogue-characteristics">
                    <h3>Caractéristiques techniques</h3>
                    <table>
                        <tbody>
                        <?php foreach ($caracs as $key => $val): ?>
                            <tr>
                                <th><?= htmlspecialchars((string)$key) ?></th>
                                <td><?= htmlspecialchars(is_scalar($val) ? (string)$val : json_encode($val)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <?php if (!empty($related)): ?>
                <div class="mt-5">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 style="font-size: 1.5rem; font-weight: 700; color: #1a1a2e; margin: 0;">Produits associés</h2>
                        <div class="catalogue-carousel__controls">
                            <button class="btn" id="carousel-prev" aria-label="Précédent">‹</button>
                            <button class="btn" id="carousel-next" aria-label="Suivant">›</button>
                        </div>
                    </div>
                    <div class="catalogue-carousel" id="catalogue-carousel" data-detail-base="<?= htmlspecialchars($detailBase) ?>">
                        <?php foreach ($related as $rel): ?>
                            <?php
                                $img = catalogue_image_path($rel['image_principale']);
                                $detailUrl = $detailBase . '?slug=' . urlencode($rel['slug']);
                                $prix = $rel['prix_unite'] !== null ? number_format((float)$rel['prix_unite'], 2, ',', ' ') . ' FCFA' : '—';
                            ?>
                            <a class="catalogue-card catalogue-card--sm" href="<?= htmlspecialchars($detailUrl) ?>" title="<?= htmlspecialchars($rel['designation']) ?>">
                                <div class="catalogue-card__img" style="background-image: url('<?= htmlspecialchars($img) ?>');"></div>
                                <div class="catalogue-card__body">
                                    <p class="catalogue-card__code"><?= htmlspecialchars($rel['code']) ?></p>
                                    <h2 class="catalogue-card__title"><?= htmlspecialchars($rel['designation']) ?></h2>
                                    <p class="catalogue-card__cat"><?= htmlspecialchars($prix) ?></p>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script defer src="<?= htmlspecialchars($assetJs) ?>"></script>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>
