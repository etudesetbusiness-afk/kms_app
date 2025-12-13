<?php
// hotel/chambres_list.php
require_once __DIR__ . '/../security.php';
exigerConnexion();
exigerPermission('HOTEL_GERER');

global $pdo;

// Récupération des chambres
$stmt = $pdo->query("
    SELECT *
    FROM chambres
    ORDER BY code ASC
");
$chambres = $stmt->fetchAll();

$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError   = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

include __DIR__ . '/../partials/header.php';
include __DIR__ . '/../partials/sidebar.php';
?>
<div class="container-fluid">
    <div class="list-page-header d-flex justify-content-between align-items-center">
        <h1 class="list-page-title h3">
            <i class="bi bi-door-closed-fill me-2"></i>
            Chambres
            <span class="count-badge ms-2"><?= count($chambres) ?></span>
        </h1>
        <a href="<?= url_for('hotel/chambres_edit.php') ?>" class="btn btn-primary btn-add-new">
            <i class="bi bi-plus-circle me-1"></i> Nouvelle chambre
        </a>
    </div>

    <?php if ($flashSuccess): ?>
        <div class="alert alert-success alert-modern">
            <i class="bi bi-check-circle me-1"></i><?= htmlspecialchars($flashSuccess) ?>
        </div>
    <?php endif; ?>

    <?php if ($flashError): ?>
        <div class="alert alert-danger alert-modern">
            <i class="bi bi-exclamation-triangle me-1"></i><?= htmlspecialchars($flashError) ?>
        </div>
    <?php endif; ?>

    <div class="card data-table-card">
        <div class="card-body">
            <?php if (empty($chambres)): ?>
                <p class="text-muted mb-0">Aucune chambre enregistrée.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table modern-table">
                        <thead class="table-light">
                        <tr>
                            <th>Code</th>
                            <th>Description</th>
                            <th class="text-end">Tarif nuité</th>
                            <th class="text-center">Statut</th>
                            <th class="text-end">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($chambres as $c): ?>
                            <tr>
                                <td class="fw-semibold"><?= htmlspecialchars($c['code']) ?></td>
                                <td><?= nl2br(htmlspecialchars($c['description'] ?? '')) ?></td>
                                <td class="text-end">
                                    <?= number_format((float)$c['tarif_nuite'], 0, ',', ' ') ?> FCFA
                                </td>
                                <td class="text-center">
                                    <?php if ((int)$c['actif'] === 1): ?>
                                        <span class="modern-badge badge-status-success">Active</span>
                                    <?php else: ?>
                                        <span class="modern-badge badge-status-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <a href="<?= url_for('hotel/chambres_edit.php') . '?id=' . (int)$c['id'] ?>"
                                       class="btn btn-sm btn-outline-secondary btn-action">
                                        <i class="bi bi-pencil-square me-1"></i> Modifier
                                    </a>
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
