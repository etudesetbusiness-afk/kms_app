<?php
/**
 * dashboard/kpis_manager.php - Manager KPIs avec refresh manuel
 * Tableau de bord des indicateurs clés avec cache
 */
require_once __DIR__ . '/../security.php';
require_once __DIR__ . '/../lib/kpi_cache.php';

exigerConnexion();
exigerPermission('DASHBOARD_LIRE');

global $pdo;

$kpi_cache = getKPICache($pdo);

// Récupérer les KPIs
$kpis = $kpi_cache->getAllKPIs();

// Formatage des montants
function format_montant($montant) {
    return number_format($montant, 2, ',', ' ') . ' FCFA';
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard KPIs - KMS Gestion</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        .kpi-card {
            border: none;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .kpi-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .kpi-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: #0d6efd;
        }
        .kpi-label {
            font-size: 0.85rem;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .kpi-icon {
            font-size: 2rem;
            opacity: 0.2;
        }
        .kpi-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 0.7rem;
            background: #e9ecef;
            padding: 3px 8px;
            border-radius: 20px;
        }
        .refresh-btn {
            margin-bottom: 1rem;
        }
        .top-clients-list {
            list-style: none;
            padding: 0;
        }
        .top-clients-list li {
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            font-size: 0.9rem;
        }
        .top-clients-list li:last-child {
            border-bottom: none;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .status-warning {
            background: #fff3cd;
            color: #856404;
        }
        .status-success {
            background: #d4edda;
            color: #155724;
        }
        .status-danger {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid p-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col">
                <h1 class="h3"><i class="bi bi-speedometer2"></i> Dashboard KPIs</h1>
                <small class="text-muted">Mise en cache à: <?= $kpis['cached_at'] ?></small>
            </div>
            <div class="col-auto">
                <button class="btn btn-primary refresh-btn" onclick="refreshAllKPIs()">
                    <i class="bi bi-arrow-clockwise"></i> Rafraîchir tout
                </button>
            </div>
        </div>

        <!-- KPIs Principaux -->
        <div class="row g-3 mb-4">
            <!-- CA Jour -->
            <div class="col-md-3">
                <div class="card kpi-card h-100 position-relative">
                    <div class="card-body">
                        <div class="kpi-icon float-end"><i class="bi bi-calendar-event"></i></div>
                        <div class="kpi-label">CA Aujourd'hui</div>
                        <div class="kpi-value"><?= format_montant($kpis['ca_today']['montant']) ?></div>
                        <small class="text-muted"><?= $kpis['ca_today']['nombre'] ?> commandes</small>
                    </div>
                    <div class="kpi-badge">1h</div>
                </div>
            </div>

            <!-- CA Mois -->
            <div class="col-md-3">
                <div class="card kpi-card h-100 position-relative">
                    <div class="card-body">
                        <div class="kpi-icon float-end"><i class="bi bi-calendar-month"></i></div>
                        <div class="kpi-label">CA Ce Mois</div>
                        <div class="kpi-value"><?= format_montant($kpis['ca_month']['montant']) ?></div>
                        <small class="text-muted"><?= $kpis['ca_month']['nombre'] ?> commandes</small>
                    </div>
                    <div class="kpi-badge">24h</div>
                </div>
            </div>

            <!-- CA Année -->
            <div class="col-md-3">
                <div class="card kpi-card h-100 position-relative">
                    <div class="card-body">
                        <div class="kpi-icon float-end"><i class="bi bi-calendar2"></i></div>
                        <div class="kpi-label">CA Cette Année</div>
                        <div class="kpi-value"><?= format_montant($kpis['ca_year']['montant']) ?></div>
                        <small class="text-muted"><?= $kpis['ca_year']['nombre'] ?> commandes</small>
                    </div>
                    <div class="kpi-badge">7j</div>
                </div>
            </div>

            <!-- Encaissement -->
            <div class="col-md-3">
                <div class="card kpi-card h-100 position-relative">
                    <div class="card-body">
                        <div class="kpi-icon float-end"><i class="bi bi-cash-coin"></i></div>
                        <div class="kpi-label">Encaissement Mois</div>
                        <div class="kpi-value"><?= $kpis['encaissement']['percentage'] ?>%</div>
                        <small class="text-muted"><?= format_montant($kpis['encaissement']['montant']) ?></small>
                    </div>
                    <div class="kpi-badge">5min</div>
                </div>
            </div>
        </div>

        <!-- KPIs Secondaires -->
        <div class="row g-3 mb-4">
            <!-- Clients Actifs -->
            <div class="col-md-3">
                <div class="card kpi-card h-100 position-relative">
                    <div class="card-body">
                        <div class="kpi-icon float-end"><i class="bi bi-people"></i></div>
                        <div class="kpi-label">Clients Actifs</div>
                        <div class="kpi-value"><?= $kpis['active_clients'] ?></div>
                        <small class="text-muted">Ce mois</small>
                    </div>
                    <div class="kpi-badge">24h</div>
                </div>
            </div>

            <!-- Ruptures Stock -->
            <div class="col-md-3">
                <div class="card kpi-card h-100 position-relative">
                    <div class="card-body">
                        <div class="kpi-icon float-end"><i class="bi bi-exclamation-triangle"></i></div>
                        <div class="kpi-label">Ruptures Stock</div>
                        <div class="kpi-value" style="color: <?= $kpis['ruptures'] > 0 ? '#dc3545' : '#28a745' ?>;">
                            <?= $kpis['ruptures'] ?>
                        </div>
                        <small class="text-muted">Produits</small>
                    </div>
                    <div class="kpi-badge">5min</div>
                </div>
            </div>

            <!-- Non Livrées -->
            <div class="col-md-3">
                <div class="card kpi-card h-100 position-relative">
                    <div class="card-body">
                        <div class="kpi-icon float-end"><i class="bi bi-truck"></i></div>
                        <div class="kpi-label">En Attente Livraison</div>
                        <div class="kpi-value" style="color: <?= $kpis['non_livrees'] > 0 ? '#ffc107' : '#28a745' ?>;">
                            <?= $kpis['non_livrees'] ?>
                        </div>
                        <small class="text-muted">Ventes</small>
                    </div>
                    <div class="kpi-badge">5min</div>
                </div>
            </div>

            <!-- Top Client -->
            <div class="col-md-3">
                <div class="card kpi-card h-100 position-relative">
                    <div class="card-body">
                        <div class="kpi-icon float-end"><i class="bi bi-star"></i></div>
                        <div class="kpi-label">Top Client</div>
                        <?php if (!empty($kpis['top_clients'])): ?>
                            <div class="kpi-value" style="font-size: 1.2rem;">
                                <?= htmlspecialchars(substr($kpis['top_clients'][0]['nom'], 0, 12)) ?>
                            </div>
                            <small class="text-muted">
                                <?= format_montant($kpis['top_clients'][0]['ca']) ?>
                            </small>
                        <?php else: ?>
                            <div class="text-muted">N/A</div>
                        <?php endif; ?>
                    </div>
                    <div class="kpi-badge">24h</div>
                </div>
            </div>
        </div>

        <!-- Top Clients -->
        <div class="row g-3">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="bi bi-graph-up"></i> Top 5 Clients (Ce Mois)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($kpis['top_clients'])): ?>
                            <ol class="top-clients-list">
                                <?php foreach ($kpis['top_clients'] as $i => $client): ?>
                                    <li>
                                        <span>
                                            <strong><?= htmlspecialchars($client['nom']) ?></strong>
                                            <span class="text-muted">(<?= (int)$client['nb_commandes'] ?> cmd)</span>
                                        </span>
                                        <span class="fw-bold text-primary">
                                            <?= format_montant($client['ca']) ?>
                                        </span>
                                    </li>
                                <?php endforeach; ?>
                            </ol>
                        <?php else: ?>
                            <div class="text-muted text-center py-3">Aucune donnée</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Actions Admin -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="bi bi-tools"></i> Gestion Cache</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">
                            Les KPIs sont mis en cache pour améliorer les performances. 
                            Cliquez sur les boutons ci-dessous pour forcer un recalcul.
                        </p>
                        
                        <div class="d-grid gap-2">
                            <button class="btn btn-sm btn-outline-primary" onclick="flushKPI('ca_today')">
                                <i class="bi bi-arrow-clockwise"></i> Rafraîchir CA Jour
                            </button>
                            <button class="btn btn-sm btn-outline-primary" onclick="flushKPI('ca_month')">
                                <i class="bi bi-arrow-clockwise"></i> Rafraîchir CA Mois
                            </button>
                            <button class="btn btn-sm btn-outline-primary" onclick="flushKPI('ruptures')">
                                <i class="bi bi-arrow-clockwise"></i> Rafraîchir Ruptures
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="flushAllKPIs()">
                                <i class="bi bi-trash"></i> Vider tout le cache KPI
                            </button>
                        </div>

                        <div class="alert alert-info mt-3 mb-0">
                            <small>
                                <i class="bi bi-info-circle"></i>
                                <strong>TTL:</strong> CA Jour (1h) | CA Mois (24h) | CA Année (7j) | Autres temps réel (5min)
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function flushKPI(kpi_name) {
        if (!confirm('Êtes-vous sûr?')) return;
        
        fetch('<?php echo url_for("api/kpis.php"); ?>?action=flush&kpi=' + kpi_name)
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Cache vidé avec succès');
                    location.reload();
                } else {
                    alert('❌ Erreur: ' + data.error);
                }
            });
    }
    
    function flushAllKPIs() {
        if (!confirm('Êtes-vous sûr de vider TOUS les KPIs?')) return;
        
        fetch('<?php echo url_for("api/kpis.php"); ?>?action=flush_all')
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Tous les KPIs vidés');
                    location.reload();
                } else {
                    alert('❌ Erreur: ' + data.error);
                }
            });
    }
    
    function refreshAllKPIs() {
        flushAllKPIs();
    }
    </script>
</body>
</html>
