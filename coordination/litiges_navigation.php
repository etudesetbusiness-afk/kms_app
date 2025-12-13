<?php
// coordination/litiges_navigation.php - Détail litige avec navigation vers vente, livraison, stock
require_once __DIR__ . '/../security.php';
exigerConnexion();
exigerPermission('VENTES_LIRE');

global $pdo;

$litigeId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($litigeId === 0) {
    http_response_code(404);
    die('Litige non trouvé.');
}

// Récupérer le litige
$stmt = $pdo->prepare("
    SELECT rl.*, p.code_produit as produit_code, p.designation as produit_nom, c.nom as client_nom,
           v.numero as vente_numero, v.id as vente_id,
           u.nom_complet as responsable_nom
    FROM retours_litiges rl
    JOIN produits p ON p.id = rl.produit_id
    JOIN clients c ON c.id = rl.client_id
    LEFT JOIN ventes v ON v.id = rl.vente_id
    LEFT JOIN utilisateurs u ON u.id = rl.responsable_suivi_id
    WHERE rl.id = ?
");
$stmt->execute([$litigeId]);
$litige = $stmt->fetch();

if (!$litige) {
    http_response_code(404);
    die('Litige non trouvé.');
}

// Récupérer les informations de vente associée
$vente = null;
$livraisons = [];
$lignesVente = [];

if ($litige['vente_id']) {
    $stmt = $pdo->prepare("
        SELECT v.*, cv.libelle as canal_nom
        FROM ventes v
        JOIN canaux_vente cv ON cv.id = v.canal_vente_id
        WHERE v.id = ?
    ");
    $stmt->execute([$litige['vente_id']]);
    $vente = $stmt->fetch();
    
    // Livraisons de cette vente
    $stmt = $pdo->prepare("
        SELECT bl.*, COUNT(bll.id) as nb_lignes
        FROM bons_livraison bl
        LEFT JOIN bons_livraison_lignes bll ON bll.bon_livraison_id = bl.id
        WHERE bl.vente_id = ?
        GROUP BY bl.id
        ORDER BY bl.date_bl DESC
    ");
    $stmt->execute([$litige['vente_id']]);
    $livraisons = $stmt->fetchAll();
    
    // Lignes de vente
    $stmt = $pdo->prepare("
        SELECT vl.*, pr.code, pr.designation
        FROM ventes_lignes vl
        JOIN produits pr ON pr.id = vl.produit_id
        WHERE vl.vente_id = ?
        ORDER BY vl.id ASC
    ");
    $stmt->execute([$litige['vente_id']]);
    $lignesVente = $stmt->fetchAll();
}

// Mouvements de stock pour ce produit dans cette vente
$mouvementsStock = [];
if ($litige['vente_id']) {
    $stmt = $pdo->prepare("
        SELECT sm.*, p.code_produit, p.designation
        FROM stocks_mouvements sm
        JOIN produits p ON p.id = sm.produit_id
        WHERE (sm.source_id = ? AND sm.source_type = 'VENTE') AND sm.produit_id = ?
        ORDER BY sm.date_mouvement DESC
    ");
    $stmt->execute([$litige['vente_id'], $litige['produit_id']]);
    $mouvementsStock = $stmt->fetchAll();
}

include __DIR__ . '/../partials/header.php';
include __DIR__ . '/../partials/sidebar.php';
?>

<div class="container-fluid p-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3 mb-1">
                <i class="bi bi-exclamation-triangle text-danger"></i> Litige/Retour #<?= $litigeId ?>
            </h1>
            <p class="text-muted mb-0">
                <i class="bi bi-calendar"></i> <?= date('d/m/Y', strtotime($litige['date_retour'])) ?>
                • <i class="bi bi-person"></i> <?= htmlspecialchars($litige['client_nom']) ?>
            </p>
        </div>
        <div class="col-md-4 text-end">
            <?php if ($litige['vente_id']): ?>
                <a href="<?= url_for('ventes/detail_360.php?id=' . $litige['vente_id']) ?>" class="btn btn-primary">
                    <i class="bi bi-arrow-up-right"></i> Vente #<?= htmlspecialchars($litige['vente_numero']) ?>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Synthèse -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="kms-kpi-card">
                <div class="kms-kpi-icon"><i class="bi bi-info-circle"></i></div>
                <div class="kms-kpi-label">Statut</div>
                <div class="kms-kpi-value">
                    <?php
                    $badgeClass = match($litige['statut_traitement']) {
                        'EN_COURS' => 'bg-warning',
                        'RESOLU' => 'bg-success',
                        'ABANDONNE' => 'bg-danger',
                        default => 'bg-secondary'
                    };
                    ?>
                    <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($litige['statut_traitement']) ?></span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="kms-kpi-card">
                <div class="kms-kpi-icon"><i class="bi bi-type"></i></div>
                <div class="kms-kpi-label">Type Problème</div>
                <div class="kms-kpi-value"><?= htmlspecialchars($litige['type_probleme']) ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="kms-kpi-card">
                <div class="kms-kpi-icon"><i class="bi bi-cash-coin"></i></div>
                <div class="kms-kpi-label">Remboursé</div>
                <div class="kms-kpi-value text-danger"><?= number_format($litige['montant_rembourse'] ?? 0, 0, ',', ' ') ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="kms-kpi-card">
                <div class="kms-kpi-icon"><i class="bi bi-percent"></i></div>
                <div class="kms-kpi-label">Avoir</div>
                <div class="kms-kpi-value text-warning"><?= number_format($litige['montant_avoir'] ?? 0, 0, ',', ' ') ?></div>
            </div>
        </div>
    </div>

    <!-- Onglets -->
    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tab-infos" data-bs-toggle="tab" data-bs-target="#infos" type="button">
                <i class="bi bi-info-circle"></i> Informations
            </button>
        </li>
        <?php if ($vente): ?>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-vente" data-bs-toggle="tab" data-bs-target="#vente" type="button">
                    <i class="bi bi-receipt"></i> Vente Associée
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-livraisons" data-bs-toggle="tab" data-bs-target="#livraisons" type="button">
                    <i class="bi bi-truck"></i> Livraisons (<?= count($livraisons) ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tab-stock" data-bs-toggle="tab" data-bs-target="#stock" type="button">
                    <i class="bi bi-boxes"></i> Stock
                </button>
            </li>
        <?php endif; ?>
    </ul>

    <div class="tab-content">
        <!-- TAB: Informations -->
        <div class="tab-pane fade show active" id="infos" role="tabpanel">
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-header">
                            <strong>Détails du Litige</strong>
                        </div>
                        <div class="card-body">
                            <dl class="row">
                                <dt class="col-sm-5">Client :</dt>
                                <dd class="col-sm-7">
                                    <a href="<?= url_for('clients/detail.php?id=' . $litige['client_id']) ?>" class="text-decoration-none">
                                        <?= htmlspecialchars($litige['client_nom']) ?>
                                    </a>
                                </dd>
                                
                                <dt class="col-sm-5">Produit :</dt>
                                <dd class="col-sm-7">
                                    <a href="<?= url_for('produits/edit.php?id=' . $litige['produit_id']) ?>" class="text-decoration-none">
                                        <?= htmlspecialchars($litige['produit_code']) ?> - <?= htmlspecialchars($litige['produit_nom']) ?>
                                    </a>
                                </dd>
                                
                                <dt class="col-sm-5">Type :</dt>
                                <dd class="col-sm-7"><?= htmlspecialchars($litige['type_probleme']) ?></dd>
                                
                                <dt class="col-sm-5">Statut :</dt>
                                <dd class="col-sm-7">
                                    <span class="<?= $badgeClass ?>"><?= htmlspecialchars($litige['statut_traitement']) ?></span>
                                </dd>
                                
                                <dt class="col-sm-5">Responsable :</dt>
                                <dd class="col-sm-7"><?= htmlspecialchars($litige['responsable_nom'] ?? 'Non assigné') ?></dd>
                                
                                <dt class="col-sm-5">Date de retour :</dt>
                                <dd class="col-sm-7"><?= date('d/m/Y', strtotime($litige['date_retour'])) ?></dd>
                                
                                <dt class="col-sm-5">Date de résolution :</dt>
                                <dd class="col-sm-7">
                                    <?= $litige['date_resolution'] ? date('d/m/Y', strtotime($litige['date_resolution'])) : 'En attente' ?>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card mb-3">
                        <div class="card-header">
                            <strong>Motif & Solution</strong>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label"><strong>Motif du retour :</strong></label>
                                <p class="form-control-plaintext"><?= htmlspecialchars($litige['motif'] ?? 'Non spécifié') ?></p>
                            </div>
                            <div>
                                <label class="form-label"><strong>Solution apportée :</strong></label>
                                <p class="form-control-plaintext"><?= htmlspecialchars($litige['solution'] ?? 'Non spécifiée') ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <strong>Impact Financier</strong>
                        </div>
                        <div class="card-body">
                            <dl class="row">
                                <dt class="col-sm-6">Montant remboursé :</dt>
                                <dd class="col-sm-6 text-end text-danger">
                                    <?= number_format($litige['montant_rembourse'] ?? 0, 2, ',', ' ') ?> FCFA
                                </dd>
                                
                                <dt class="col-sm-6">Avoir commercial :</dt>
                                <dd class="col-sm-6 text-end text-warning">
                                    <?= number_format($litige['montant_avoir'] ?? 0, 2, ',', ' ') ?> FCFA
                                </dd>
                                
                                <dt class="col-sm-6"><strong>Total impact :</strong></dt>
                                <dd class="col-sm-6 text-end text-danger">
                                    <strong>
                                        <?= number_format(($litige['montant_rembourse'] ?? 0) + ($litige['montant_avoir'] ?? 0), 2, ',', ' ') ?> FCFA
                                    </strong>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB: Vente Associée -->
        <?php if ($vente): ?>
            <div class="tab-pane fade" id="vente" role="tabpanel">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <strong>Vente #<?= htmlspecialchars($vente['numero']) ?></strong>
                            </div>
                            <div class="card-body">
                                <dl class="row">
                                    <dt class="col-sm-5">Date :</dt>
                                    <dd class="col-sm-7"><?= date('d/m/Y', strtotime($vente['date_vente'])) ?></dd>
                                    
                                    <dt class="col-sm-5">Canal :</dt>
                                    <dd class="col-sm-7"><?= htmlspecialchars($vente['canal_nom']) ?></dd>
                                    
                                    <dt class="col-sm-5">Statut :</dt>
                                    <dd class="col-sm-7"><?= htmlspecialchars($vente['statut']) ?></dd>
                                    
                                    <dt class="col-sm-5">Montant TTC :</dt>
                                    <dd class="col-sm-7 text-end"><?= number_format($vente['montant_total_ttc'], 2, ',', ' ') ?> FCFA</dd>
                                </dl>
                                <a href="<?= url_for('ventes/detail_360.php?id=' . $vente['id']) ?>" class="btn btn-sm btn-primary">
                                    Voir détails complets
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <strong>Produits dans cette vente</strong>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm mb-0">
                                    <thead>
                                        <tr>
                                            <th>Code</th>
                                            <th>Designation</th>
                                            <th class="text-end">Qté</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($lignesVente as $ligne): ?>
                                            <tr class="<?= ($ligne['produit_id'] == $litige['produit_id']) ? 'table-warning' : '' ?>">
                                                <td><?= htmlspecialchars($ligne['code']) ?></td>
                                                <td><?= htmlspecialchars($ligne['designation']) ?></td>
                                                <td class="text-end"><?= (int)$ligne['quantite'] ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB: Livraisons -->
            <div class="tab-pane fade" id="livraisons" role="tabpanel">
                <?php if (empty($livraisons)): ?>
                    <div class="alert alert-warning">
                        Aucune livraison pour cette vente.
                    </div>
                <?php else: ?>
                    <div class="row g-3">
                        <?php foreach ($livraisons as $livraison): ?>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                        <strong>BL #<?= htmlspecialchars($livraison['numero']) ?></strong>
                                        <span class="badge bg-success"><?= htmlspecialchars($livraison['statut']) ?></span>
                                    </div>
                                    <div class="card-body">
                                        <p class="small text-muted mb-2">
                                            <i class="bi bi-calendar"></i> <?= date('d/m/Y', strtotime($livraison['date_livraison'])) ?>
                                        </p>
                                        <p class="small mb-3">
                                            <?= $livraison['nb_lignes'] ?> produits livrés
                                        </p>
                                        <a href="<?= url_for('livraisons/detail_navigation.php?id=' . $livraison['id']) ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i> Détails
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- TAB: Stock -->
            <div class="tab-pane fade" id="stock" role="tabpanel">
                <?php if (empty($mouvementsStock)): ?>
                    <div class="alert alert-warning">
                        Aucun mouvement de stock pour ce produit dans cette vente.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th class="text-end">Quantité</th>
                                    <th>Raison</th>
                                    <th class="text-end">Stock Résultant</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($mouvementsStock as $mvt): ?>
                                    <tr>
                                        <td><?= date('d/m/Y H:i', strtotime($mvt['date_mouvement'])) ?></td>
                                        <td><span class="badge bg-primary"><?= htmlspecialchars($mvt['type_mouvement']) ?></span></td>
                                        <td class="text-end"><?= (int)$mvt['quantite'] ?></td>
                                        <td><?= htmlspecialchars($mvt['raison']) ?></td>
                                        <td class="text-end"><span class="badge bg-info"><?= (int)$mvt['stock_resultant'] ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Actions -->
    <div class="mt-4 d-flex gap-2">
        <a href="<?= url_for('coordination/litiges.php') ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Retour à la liste
        </a>
        <?php if ($litige['vente_id']): ?>
            <a href="<?= url_for('ventes/detail_360.php?id=' . $litige['vente_id']) ?>" class="btn btn-outline-primary">
                <i class="bi bi-arrow-up-right"></i> Vente complète
            </a>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>
