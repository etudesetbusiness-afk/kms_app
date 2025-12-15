<?php
/**
 * admin/database_optimization.php - Outil d'optimisation de la base de données
 * Affiche les statistiques et suggestions d'optimisation
 */

require_once __DIR__ . '/../security.php';
require_once __DIR__ . '/../lib/query_optimizer.php';

exigerConnexion();
exigerPermission('ADMIN');

global $pdo;

$optimizer = new QueryOptimizer($pdo);
$action = $_GET['action'] ?? '';

// Récupérer le nom de la database actuelle
$db_name = $pdo->query("SELECT DATABASE() as db")->fetch()['db'];

// Récupérer les suggestions d'index
$suggestions = $optimizer->suggestIndexes();

// Récupérer les stats des tables
$table_stats = $optimizer->getTableStats($db_name);

// Récupérer les requêtes lentes
$slow_queries = $optimizer->getSlowQueries(10);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Optimisation Base de Données - KMS Gestion</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .stat-card {
            border-left: 4px solid #0d6efd;
        }
        .table-stats {
            font-size: 0.875rem;
        }
        .index-suggestion {
            background-color: #fff3cd;
            border-left: 3px solid #ffc107;
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            border-radius: 0.25rem;
        }
        .code-sql {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 0.5rem;
            border-radius: 0.25rem;
            font-family: 'Courier New', monospace;
            font-size: 0.85rem;
            overflow-x: auto;
        }
        .badge-large {
            padding: 0.5rem 0.75rem;
            font-size: 0.9rem;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid p-4">
        <div class="row mb-4">
            <div class="col">
                <h1 class="h3"><i class="bi bi-database-gear"></i> Optimisation Base de Données</h1>
                <small class="text-muted">Database: <strong><?= htmlspecialchars($db_name) ?></strong></small>
            </div>
        </div>

        <!-- Onglets -->
        <ul class="nav nav-tabs mb-4" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="stats-tab" data-bs-toggle="tab" data-bs-target="#stats" type="button">
                    <i class="bi bi-graph-up"></i> Statistiques
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="suggestions-tab" data-bs-toggle="tab" data-bs-target="#suggestions" type="button">
                    <i class="bi bi-lightbulb"></i> Suggestions Indexes
                    <?php if (!empty($suggestions)): ?>
                        <span class="badge bg-warning ms-2"><?= count($suggestions) ?></span>
                    <?php endif; ?>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="slow-tab" data-bs-toggle="tab" data-bs-target="#slow" type="button">
                    <i class="bi bi-exclamation-triangle"></i> Requêtes Lentes
                    <?php if (!empty($slow_queries)): ?>
                        <span class="badge bg-danger ms-2"><?= count($slow_queries) ?></span>
                    <?php endif; ?>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="cache-tab" data-bs-toggle="tab" data-bs-target="#cache" type="button">
                    <i class="bi bi-cache"></i> Cache
                </button>
            </li>
        </ul>

        <!-- Contenu onglets -->
        <div class="tab-content">
            <!-- ONGLET: STATISTIQUES -->
            <div class="tab-pane fade show active" id="stats" role="tabpanel">
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="text-muted small">Tables</div>
                                <div class="h4 mb-0"><?= count($table_stats) ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="text-muted small">Lignes Totales</div>
                                <div class="h4 mb-0"><?= number_format(array_sum(array_column($table_stats, 'TABLE_ROWS'))) ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="text-muted small">Taille Données</div>
                                <div class="h4 mb-0"><?= array_sum(array_column($table_stats, 'data_mb')) ?> MB</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="text-muted small">Taille Indexes</div>
                                <div class="h4 mb-0"><?= array_sum(array_column($table_stats, 'index_mb')) ?> MB</div>
                            </div>
                        </div>
                    </div>
                </div>

                <h5 class="mb-3">Détails des Tables</h5>
                <div class="table-responsive">
                    <table class="table table-sm table-hover table-stats">
                        <thead class="table-light">
                            <tr>
                                <th>Table</th>
                                <th class="text-end">Lignes</th>
                                <th class="text-end">Données</th>
                                <th class="text-end">Indexes</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($table_stats as $stat): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($stat['TABLE_NAME']) ?></strong></td>
                                    <td class="text-end text-muted"><?= number_format($stat['TABLE_ROWS']) ?></td>
                                    <td class="text-end"><?= $stat['data_mb'] ?> MB</td>
                                    <td class="text-end"><?= $stat['index_mb'] ?> MB</td>
                                    <td class="text-end"><strong><?= $stat['size_mb'] ?> MB</strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ONGLET: SUGGESTIONS -->
            <div class="tab-pane fade" id="suggestions" role="tabpanel">
                <?php if (!empty($suggestions)): ?>
                    <div class="alert alert-warning mb-4">
                        <i class="bi bi-exclamation-triangle"></i>
                        <strong><?= count($suggestions) ?> suggestion(s) d'index</strong> pour améliorer les performances
                    </div>

                    <?php foreach ($suggestions as $sugg): ?>
                        <div class="index-suggestion">
                            <div class="row">
                                <div class="col">
                                    <strong><?= htmlspecialchars($sugg['table']) . '.' . htmlspecialchars($sugg['column']) ?></strong>
                                    <div class="text-muted small mt-1"><?= htmlspecialchars($sugg['reason']) ?></div>
                                </div>
                            </div>
                            <div class="code-sql mt-2">
                                <?= htmlspecialchars($sugg['sql']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle"></i> Aucune suggestion d'index manquant détectée
                    </div>
                <?php endif; ?>
            </div>

            <!-- ONGLET: REQUÊTES LENTES -->
            <div class="tab-pane fade" id="slow" role="tabpanel">
                <?php if (!empty($slow_queries)): ?>
                    <div class="alert alert-info mb-4">
                        <i class="bi bi-info-circle"></i>
                        <strong><?= count($slow_queries) ?> requête(s) lente(s)</strong> détectée(s) (seuil: 100ms)
                    </div>

                    <div class="code-sql">
                        <?php foreach ($slow_queries as $log): ?>
                            <div class="mb-3"><?= htmlspecialchars($log) ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-success">
                        <i class="bi bi-check-circle"></i> Aucune requête lente détectée
                    </div>
                <?php endif; ?>
            </div>

            <!-- ONGLET: CACHE -->
            <div class="tab-pane fade" id="cache" role="tabpanel">
                <p class="text-muted">Système de cache pour KPIs et requêtes fréquentes</p>
                <div class="alert alert-info">
                    <strong>Statut:</strong> Cache fichiers actif | Redis optionnel
                </div>
                <a href="<?= url_for('admin/clear_cache.php') ?>" class="btn btn-danger btn-sm">
                    <i class="bi bi-trash"></i> Vider le cache
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
