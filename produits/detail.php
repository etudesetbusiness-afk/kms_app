<?php
/**
 * produits/detail.php
 * Affichage détaillé d'un produit interne
 */
require_once __DIR__ . '/../security.php';
exigerConnexion();
exigerPermission('PRODUITS_LIRE');

global $pdo;

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    $_SESSION['flash_error'] = "Produit non trouvé";
    header('Location: ' . url_for('produits/list.php'));
    exit;
}

// Récupération du produit
$stmt = $pdo->prepare("
    SELECT p.*, 
           f.nom AS famille_nom,
           sc.nom AS sous_categorie_nom,
           fournisseur.nom AS fournisseur_nom
    FROM produits p
    LEFT JOIN familles_produits f ON p.famille_id = f.id
    LEFT JOIN sous_categories_produits sc ON p.sous_categorie_id = sc.id
    LEFT JOIN fournisseurs fournisseur ON p.fournisseur_id = fournisseur.id
    WHERE p.id = :id
");
$stmt->execute([':id' => $id]);
$produit = $stmt->fetch();

if (!$produit) {
    $_SESSION['flash_error'] = "Produit non trouvé";
    header('Location: ' . url_for('produits/list.php'));
    exit;
}

// Historique des mouvements de stock (derniers 20)
$stmtMvt = $pdo->prepare("
    SELECT sm.*, u.nom_complet AS utilisateur_nom
    FROM stocks_mouvements sm
    LEFT JOIN utilisateurs u ON sm.utilisateur_id = u.id
    WHERE sm.produit_id = :produit_id
    ORDER BY sm.date_mouvement DESC, sm.id DESC
    LIMIT 20
");
$stmtMvt->execute([':produit_id' => $id]);
$mouvements = $stmtMvt->fetchAll();

// Permissions
$peutModifier = peut('PRODUITS_MODIFIER');

include __DIR__ . '/../partials/header.php';
include __DIR__ . '/../partials/sidebar.php';
?>

<div class="container-fluid">
    <!-- En-tête -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-2">
                    <li class="breadcrumb-item"><a href="<?= url_for('produits/list.php') ?>">Produits</a></li>
                    <li class="breadcrumb-item active"><?= htmlspecialchars($produit['code_produit']) ?></li>
                </ol>
            </nav>
            <h1 class="h3 mb-0">
                <i class="bi bi-box-seam"></i> <?= htmlspecialchars($produit['designation']) ?>
            </h1>
        </div>
        <?php if ($peutModifier): ?>
            <div>
                <a href="<?= url_for('produits/edit.php?id=' . $id) ?>" class="btn btn-primary">
                    <i class="bi bi-pencil"></i> Modifier
                </a>
                <a href="<?= url_for('stock/mouvements.php?produit_id=' . $id) ?>" class="btn btn-success">
                    <i class="bi bi-arrow-left-right"></i> Ajuster stock
                </a>
            </div>
        <?php endif; ?>
    </div>

    <div class="row g-4">
        <!-- Informations principales -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Informations Produit</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="text-muted small">Code Produit</label>
                            <div class="fw-bold"><code><?= htmlspecialchars($produit['code_produit']) ?></code></div>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small">Désignation</label>
                            <div class="fw-bold"><?= htmlspecialchars($produit['designation']) ?></div>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small">Famille</label>
                            <div><span class="badge bg-secondary"><?= htmlspecialchars($produit['famille_nom'] ?? 'N/A') ?></span></div>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small">Sous-catégorie</label>
                            <div><?= htmlspecialchars($produit['sous_categorie_nom'] ?? 'N/A') ?></div>
                        </div>
                        <?php if ($produit['description']): ?>
                            <div class="col-12">
                                <label class="text-muted small">Description</label>
                                <div><?= nl2br(htmlspecialchars($produit['description'])) ?></div>
                            </div>
                        <?php endif; ?>
                        <?php if ($produit['caracteristiques']): ?>
                            <div class="col-12">
                                <label class="text-muted small">Caractéristiques</label>
                                <div><?= nl2br(htmlspecialchars($produit['caracteristiques'])) ?></div>
                            </div>
                        <?php endif; ?>
                        <div class="col-md-6">
                            <label class="text-muted small">Fournisseur</label>
                            <div><?= htmlspecialchars($produit['fournisseur_nom'] ?? 'N/A') ?></div>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted small">Localisation</label>
                            <div><?= htmlspecialchars($produit['localisation'] ?? 'N/A') ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Historique mouvements -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-clock-history"></i> Historique Stock (20 derniers)</h5>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($mouvements)): ?>
                        <div class="p-4 text-center text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                            Aucun mouvement enregistré
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th class="text-end">Quantité</th>
                                        <th>Motif</th>
                                        <th>Utilisateur</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($mouvements as $m): ?>
                                        <?php
                                        $badgeClass = '';
                                        $icon = '';
                                        switch ($m['type_mouvement']) {
                                            case 'ENTREE':
                                                $badgeClass = 'bg-success';
                                                $icon = 'bi-arrow-down-circle';
                                                break;
                                            case 'SORTIE':
                                                $badgeClass = 'bg-danger';
                                                $icon = 'bi-arrow-up-circle';
                                                break;
                                            case 'AJUSTEMENT':
                                                $badgeClass = 'bg-info';
                                                $icon = 'bi-arrows-angle-contract';
                                                break;
                                        }
                                        ?>
                                        <tr>
                                            <td class="small"><?= date('d/m/Y H:i', strtotime($m['date_mouvement'])) ?></td>
                                            <td>
                                                <span class="badge <?= $badgeClass ?>">
                                                    <i class="<?= $icon ?>"></i> <?= htmlspecialchars($m['type_mouvement']) ?>
                                                </span>
                                            </td>
                                            <td class="text-end fw-bold">
                                                <?= $m['type_mouvement'] === 'SORTIE' ? '-' : '+' ?><?= number_format($m['quantite'], 0, ',', ' ') ?>
                                            </td>
                                            <td class="small"><?= htmlspecialchars($m['motif'] ?? '-') ?></td>
                                            <td class="small"><?= htmlspecialchars($m['utilisateur_nom'] ?? 'N/A') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Image -->
            <?php if ($produit['image_path']): ?>
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body text-center">
                        <img src="<?= url_for($produit['image_path']) ?>" 
                             alt="<?= htmlspecialchars($produit['designation']) ?>" 
                             class="img-fluid rounded">
                    </div>
                </div>
            <?php endif; ?>

            <!-- Stock -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-box"></i> Stock</h5>
                </div>
                <div class="card-body">
                    <?php
                    $stock_actuel = (int)$produit['stock_actuel'];
                    $seuil_alerte = (int)$produit['seuil_alerte'];
                    
                    if ($stock_actuel <= 0) {
                        $stockClass = 'danger';
                        $stockLabel = 'RUPTURE';
                    } elseif ($stock_actuel <= $seuil_alerte) {
                        $stockClass = 'warning';
                        $stockLabel = 'ALERTE';
                    } else {
                        $stockClass = 'success';
                        $stockLabel = 'DISPONIBLE';
                    }
                    ?>
                    <div class="text-center mb-3">
                        <div class="display-4 fw-bold text-<?= $stockClass ?>"><?= $stock_actuel ?></div>
                        <div class="badge bg-<?= $stockClass ?> fs-6"><?= $stockLabel ?></div>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Seuil d'alerte:</span>
                        <strong><?= $seuil_alerte ?></strong>
                    </div>
                </div>
            </div>

            <!-- Prix -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-currency-exchange"></i> Tarification</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="text-muted small">Prix d'achat</label>
                        <div class="fs-5 fw-bold"><?= number_format($produit['prix_achat'], 0, ',', ' ') ?> FCFA</div>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small">Prix de vente</label>
                        <div class="fs-5 fw-bold text-success"><?= number_format($produit['prix_vente'], 0, ',', ' ') ?> FCFA</div>
                    </div>
                    <?php if ($produit['prix_achat'] > 0): ?>
                        <?php
                        $marge = $produit['prix_vente'] - $produit['prix_achat'];
                        $marge_pct = ($marge / $produit['prix_achat']) * 100;
                        ?>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Marge unitaire:</span>
                            <strong class="text-<?= $marge > 0 ? 'success' : 'danger' ?>">
                                <?= number_format($marge, 0, ',', ' ') ?> FCFA (<?= number_format($marge_pct, 1) ?>%)
                            </strong>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Statut -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Statut</h5>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <label class="text-muted small">État:</label>
                        <?php if ((int)$produit['actif'] === 1): ?>
                            <span class="badge bg-success ms-2">Actif</span>
                        <?php else: ?>
                            <span class="badge bg-secondary ms-2">Inactif</span>
                        <?php endif; ?>
                    </div>
                    <div class="mb-2">
                        <label class="text-muted small">Créé le:</label>
                        <span><?= date('d/m/Y', strtotime($produit['date_creation'])) ?></span>
                    </div>
                    <?php if ($produit['date_modification']): ?>
                        <div>
                            <label class="text-muted small">Modifié le:</label>
                            <span><?= date('d/m/Y', strtotime($produit['date_modification'])) ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>
