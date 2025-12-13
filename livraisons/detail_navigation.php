<?php
// livraisons/detail_navigation.php - Détail livraison avec navigation vers vente, ordres, litiges
require_once __DIR__ . '/../security.php';
exigerConnexion();
exigerPermission('VENTES_LIRE');

global $pdo;

$bonId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($bonId === 0) {
    http_response_code(404);
    die('Bon de livraison non trouvé.');
}

// Récupérer le bon de livraison
$stmt = $pdo->prepare("
    SELECT bl.*, v.numero as vente_numero, v.id as vente_id, c.nom as client_nom, u.nom_complet as magasinier_nom
    FROM bons_livraison bl
    JOIN ventes v ON v.id = bl.vente_id
    JOIN clients c ON c.id = v.client_id
    LEFT JOIN utilisateurs u ON u.id = bl.magasinier_id
    WHERE bl.id = ?
");
$stmt->execute([$bonId]);
$bon = $stmt->fetch();

if (!$bon) {
    http_response_code(404);
    die('Bon de livraison non trouvé.');
}

// Lignes de livraison
$stmt = $pdo->prepare("
    SELECT bll.*, p.code_produit, p.designation, vl.quantite as quantite_commandee
    FROM bons_livraison_lignes bll
    JOIN produits p ON p.id = bll.produit_id
    LEFT JOIN ventes_lignes vl ON vl.vente_id = ? AND vl.produit_id = bll.produit_id
    WHERE bll.bon_livraison_id = ?
    ORDER BY bll.id ASC
");
$stmt->execute([$bon['vente_id'], $bonId]);
$lignes = $stmt->fetchAll();

// Ordres de préparation associés
$stmt = $pdo->prepare("
    SELECT op.*, COUNT(opl.id) as nb_lignes, SUM(opl.quantite_preparee) as total_prepare
    FROM ordres_preparation op
    LEFT JOIN ordres_preparation_lignes opl ON opl.ordre_preparation_id = op.id
    WHERE op.vente_id = ?
    GROUP BY op.id
    ORDER BY op.date_ordre DESC
");
$stmt->execute([$bon['vente_id']]);
$ordres = $stmt->fetchAll();

// Retours/Litiges relatifs à cette livraison
$stmt = $pdo->prepare("
    SELECT rl.*, p.code_produit, p.designation
    FROM retours_litiges rl
    JOIN produits p ON p.id = rl.produit_id
    WHERE rl.vente_id = ?
    ORDER BY rl.date_retour DESC
");
$stmt->execute([$bon['vente_id']]);
$litiges = $stmt->fetchAll();

// Mouvements de stock liés à cette livraison
$stmt = $pdo->prepare("
    SELECT sm.*, p.code_produit, p.designation
    FROM stocks_mouvements sm
    JOIN produits p ON p.id = sm.produit_id
    WHERE (sm.source_id = ? AND sm.source_type = 'LIVRAISON') AND sm.type_mouvement = 'SORTIE' AND DATE(sm.date_mouvement) = DATE(?)
    ORDER BY sm.date_mouvement DESC
");
$stmt->execute([$bonId, $bon['date_bl']]);
$mouvementsStock = $stmt->fetchAll();

include __DIR__ . '/../partials/header.php';
include __DIR__ . '/../partials/sidebar.php';
?>

<div class="container-fluid p-4">
    <!-- Header avec lien vers la vente -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3 mb-1">Bon de Livraison #<?= htmlspecialchars($bon['numero']) ?></h1>
            <p class="text-muted mb-0">
                <i class="bi bi-calendar"></i> <?= date('d/m/Y', strtotime($bon['date_bl'])) ?>
                • <i class="bi bi-person"></i> <?= htmlspecialchars($bon['client_nom']) ?>
            </p>
        </div>
        <div class="col-md-4 text-end">
            <a href="<?= url_for('ventes/detail_360.php?id=' . $bon['vente_id']) ?>" class="btn btn-primary">
                <i class="bi bi-arrow-up-right"></i> Vente #<?= htmlspecialchars($bon['vente_numero']) ?>
            </a>
        </div>
    </div>

    <!-- Synthèse -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="kms-kpi-card">
                <div class="kms-kpi-icon"><i class="bi bi-info-circle"></i></div>
                <div class="kms-kpi-label">Statut</div>
                <div class="kms-kpi-value"><?= htmlspecialchars($bon['statut']) ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="kms-kpi-card">
                <div class="kms-kpi-icon"><i class="bi bi-person"></i></div>
                <div class="kms-kpi-label">Magasinier</div>
                <div class="kms-kpi-value text-info"><?= htmlspecialchars($bon['magasinier_nom'] ?? 'Non assigné') ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="kms-kpi-card">
                <div class="kms-kpi-icon"><i class="bi bi-box"></i></div>
                <div class="kms-kpi-label">Lignes</div>
                <div class="kms-kpi-value"><?= count($lignes) ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="kms-kpi-card">
                <div class="kms-kpi-icon"><i class="bi bi-exclamation-triangle"></i></div>
                <div class="kms-kpi-label">Litiges</div>
                <div class="kms-kpi-value text-warning"><?= count($litiges) ?></div>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tab-lignes" data-bs-toggle="tab" data-bs-target="#lignes" type="button">
                <i class="bi bi-list-check"></i> Lignes (<?= count($lignes) ?>)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-ordres" data-bs-toggle="tab" data-bs-target="#ordres" type="button">
                <i class="bi bi-boxes"></i> Ordres de préparation (<?= count($ordres) ?>)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-litiges" data-bs-toggle="tab" data-bs-target="#litiges" type="button">
                <i class="bi bi-exclamation-circle"></i> Retours/Litiges (<?= count($litiges) ?>)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-stock" data-bs-toggle="tab" data-bs-target="#stock" type="button">
                <i class="bi bi-boxes"></i> Mouvements Stock
            </button>
        </li>
    </ul>

    <div class="tab-content">
        <!-- TAB: Lignes -->
        <div class="tab-pane fade show active" id="lignes" role="tabpanel">
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Produit</th>
                                <th class="text-end">Qté Commandée</th>
                                <th class="text-end">Qté Livrée</th>
                                <th class="text-end">PU HT</th>
                                <th class="text-end">Total HT</th>
                                <th>Observations</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lignes as $ligne): ?>
                                <tr>
                                    <td>
                                        <a href="<?= url_for('produits/edit.php?id=' . $ligne['produit_id']) ?>" class="text-decoration-none">
                                            <?= htmlspecialchars($ligne['code']) ?>
                                        </a>
                                    </td>
                                    <td><?= htmlspecialchars($ligne['designation']) ?></td>
                                    <td class="text-end"><?= (int)($ligne['quantite_commandee'] ?? 0) ?></td>
                                    <td class="text-end">
                                        <strong><?= (int)$ligne['quantite_livree'] ?></strong>
                                        <?php if (((int)$ligne['quantite_livree']) > ((int)($ligne['quantite_commandee'] ?? 0))): ?>
                                            <span class="badge bg-warning">+</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end"><?= number_format($ligne['prix_unitaire'], 2, ',', ' ') ?></td>
                                    <td class="text-end"><?= number_format($ligne['montant_ht'], 2, ',', ' ') ?></td>
                                    <td><?= htmlspecialchars(substr($ligne['observations'] ?? '', 0, 40)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB: Ordres de préparation -->
        <div class="tab-pane fade" id="ordres" role="tabpanel">
            <?php if (empty($ordres)): ?>
                <div class="alert alert-info">
                    Aucun ordre de préparation pour cette vente.
                </div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($ordres as $ordre): ?>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <div class="d-flex justify-content-between">
                                        <strong>Ordre #<?= htmlspecialchars($ordre['numero']) ?></strong>
                                        <span class="badge bg-warning"><?= htmlspecialchars($ordre['statut']) ?></span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <p class="small text-muted">
                                        <i class="bi bi-calendar"></i> <?= date('d/m/Y', strtotime($ordre['date_creation'])) ?>
                                    </p>
                                    <p class="small">
                                        <?= $ordre['nb_lignes'] ?> lignes | 
                                        <?= $ordre['total_prepare'] ?> préparées
                                    </p>
                                    <a href="<?= url_for('coordination/ordres_preparation.php?id=' . $ordre['id']) ?>" class="btn btn-sm btn-outline-primary">
                                        Voir détails
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- TAB: Retours/Litiges -->
        <div class="tab-pane fade" id="litiges" role="tabpanel">
            <?php if (empty($litiges)): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle"></i> Aucun litige pour cette livraison.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Produit</th>
                                <th>Type</th>
                                <th>Statut</th>
                                <th class="text-end">Montant</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($litiges as $litige): ?>
                                <tr>
                                    <td><?= date('d/m/Y', strtotime($litige['date_retour'])) ?></td>
                                    <td><?= htmlspecialchars($litige['code']) ?> - <?= htmlspecialchars($litige['designation']) ?></td>
                                    <td><span class="badge bg-warning"><?= htmlspecialchars($litige['type_probleme']) ?></span></td>
                                    <td><span class="badge bg-info"><?= htmlspecialchars($litige['statut_traitement']) ?></span></td>
                                    <td class="text-end">
                                        <?= number_format(($litige['montant_rembourse'] ?? 0) + ($litige['montant_avoir'] ?? 0), 0, ',', ' ') ?> FCFA
                                    </td>
                                    <td>
                                        <a href="<?= url_for('coordination/litiges.php?id=' . $litige['id']) ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- TAB: Mouvements Stock -->
        <div class="tab-pane fade" id="stock" role="tabpanel">
            <?php if (empty($mouvementsStock)): ?>
                <div class="alert alert-warning">
                    Aucun mouvement de stock enregistré pour cette livraison.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Heure</th>
                                <th>Produit</th>
                                <th class="text-end">Quantité</th>
                                <th>Type</th>
                                <th>Raison</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($mouvementsStock as $mvt): ?>
                                <tr>
                                    <td><?= date('H:i', strtotime($mvt['date_mouvement'])) ?></td>
                                    <td><?= htmlspecialchars($mvt['code']) ?></td>
                                    <td class="text-end"><?= (int)$mvt['quantite'] ?></td>
                                    <td><span class="badge bg-primary"><?= htmlspecialchars($mvt['type_mouvement']) ?></span></td>
                                    <td><?= htmlspecialchars($mvt['raison']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Actions -->
    <div class="mt-4 d-flex gap-2">
        <a href="<?= url_for('livraisons/list.php') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Retour à la liste
        </a>
        <a href="<?= url_for('ventes/detail_360.php?id=' . $bon['vente_id']) ?>" class="btn btn-outline-primary">
            <i class="bi bi-arrow-up-right"></i> Vente complète
        </a>
    </div>
</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>
