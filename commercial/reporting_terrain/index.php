<?php
/**
 * Reporting Terrain - Liste des reportings
 * Module: commercial/reporting_terrain/index.php
 */

require_once __DIR__ . '/../../security.php';
exigerConnexion();

global $pdo;
$utilisateur = utilisateurConnecte();
$isAdmin = estAdmin(); // Utilise la fonction de security.php

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 15;
$offset = ($page - 1) * $limit;

// Filtrer par user_id sauf si admin
$whereClause = $isAdmin ? "1=1" : "tr.user_id = :user_id";

// Compter le total
$countSql = "SELECT COUNT(*) FROM terrain_reporting tr WHERE $whereClause";
$countStmt = $pdo->prepare($countSql);
if (!$isAdmin) {
    $countStmt->bindValue(':user_id', $utilisateur['id'], PDO::PARAM_INT);
}
$countStmt->execute();
$total = $countStmt->fetchColumn();
$totalPages = max(1, ceil($total / $limit));

// Récupérer les reportings
$sql = "
    SELECT tr.*, u.nom_complet as user_nom
    FROM terrain_reporting tr
    LEFT JOIN utilisateurs u ON tr.user_id = u.id
    WHERE $whereClause
    ORDER BY tr.semaine_debut DESC
    LIMIT :limit OFFSET :offset
";
$stmt = $pdo->prepare($sql);
if (!$isAdmin) {
    $stmt->bindValue(':user_id', $utilisateur['id'], PDO::PARAM_INT);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$reportings = $stmt->fetchAll();

include __DIR__ . '/../../partials/header.php';
include __DIR__ . '/../../partials/sidebar.php';
?>

<div class="container-fluid py-4">
    <!-- En-tête -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
        <div>
            <h1 class="h3 mb-1">
                <i class="bi bi-clipboard-data text-primary me-2"></i>
                Reporting Terrain
            </h1>
            <p class="text-muted mb-0">Suivi hebdomadaire de l'activité commerciale terrain</p>
        </div>
        <a href="<?= url_for('commercial/reporting_terrain/create.php') ?>" class="btn btn-primary btn-lg mt-3 mt-md-0">
            <i class="bi bi-plus-lg me-1"></i>
            Nouveau reporting
        </a>
    </div>

    <!-- Message flash -->
    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>
            <?= htmlspecialchars($_SESSION['flash_success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <!-- Liste des reportings -->
    <?php if (empty($reportings)): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="bi bi-clipboard-x display-1 text-muted"></i>
                <h5 class="mt-3">Aucun reporting pour le moment</h5>
                <p class="text-muted">Créez votre premier reporting hebdomadaire</p>
                <a href="<?= url_for('commercial/reporting_terrain/create.php') ?>" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i>
                    Créer un reporting
                </a>
            </div>
        </div>
    <?php else: ?>
        <!-- Version mobile: cards -->
        <div class="d-md-none">
            <?php foreach ($reportings as $r): ?>
                <div class="card mb-3 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h6 class="mb-1">
                                    Semaine du <?= date('d/m/Y', strtotime($r['semaine_debut'])) ?>
                                </h6>
                                <small class="text-muted">
                                    au <?= date('d/m/Y', strtotime($r['semaine_fin'])) ?>
                                </small>
                            </div>
                            <span class="badge bg-primary"><?= htmlspecialchars($r['ville'] ?? '-') ?></span>
                        </div>
                        <p class="mb-2 small">
                            <i class="bi bi-person me-1"></i>
                            <?= htmlspecialchars($r['commercial_nom']) ?>
                        </p>
                        <div class="d-flex gap-2">
                            <a href="<?= url_for('commercial/reporting_terrain/show.php?id=' . $r['id']) ?>" 
                               class="btn btn-outline-primary btn-sm flex-fill">
                                <i class="bi bi-eye me-1"></i>Voir
                            </a>
                            <a href="<?= url_for('commercial/reporting_terrain/print.php?id=' . $r['id']) ?>" 
                               class="btn btn-outline-secondary btn-sm flex-fill" target="_blank">
                                <i class="bi bi-printer me-1"></i>Imprimer
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Version desktop: table -->
        <div class="card border-0 shadow-sm d-none d-md-block">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Semaine</th>
                            <th>Commercial</th>
                            <th>Ville</th>
                            <th>Responsable</th>
                            <th>Créé le</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reportings as $r): ?>
                            <tr>
                                <td>
                                    <strong><?= date('d/m', strtotime($r['semaine_debut'])) ?></strong>
                                    <span class="text-muted">→</span>
                                    <strong><?= date('d/m/Y', strtotime($r['semaine_fin'])) ?></strong>
                                </td>
                                <td><?= htmlspecialchars($r['commercial_nom']) ?></td>
                                <td><?= htmlspecialchars($r['ville'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($r['responsable_nom'] ?? '-') ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></td>
                                <td class="text-end">
                                    <a href="<?= url_for('commercial/reporting_terrain/show.php?id=' . $r['id']) ?>" 
                                       class="btn btn-sm btn-outline-primary" title="Voir">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="<?= url_for('commercial/reporting_terrain/print.php?id=' . $r['id']) ?>" 
                                       class="btn btn-sm btn-outline-secondary" title="Imprimer" target="_blank">
                                        <i class="bi bi-printer"></i>
                                    </a>
                                    <?php if ((!$isAdmin && ($r['user_id'] ?? null) === $utilisateur['id'] && ($r['statut'] ?? 'soumis') === 'brouillon') || ($isAdmin && ($r['statut'] ?? 'soumis') === 'brouillon')): ?>
                                        <a href="<?= url_for('commercial/reporting_terrain/edit.php?id=' . $r['id']) ?>" 
                                           class="btn btn-sm btn-outline-warning" title="Modifier brouillon">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <nav class="mt-4" aria-label="Pagination">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $page - 1 ?>">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $page + 1 ?>">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../partials/footer.php'; ?>
