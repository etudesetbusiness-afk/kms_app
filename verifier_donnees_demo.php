<?php
/**
 * Page de vérification des données démo
 * Affiche un tableau de bord avec les statistiques
 */

require_once __DIR__ . '/db/db.php';

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification Données Démo - KMS Gestion</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .stat-card {
            border-left: 4px solid;
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
        }
        .check-ok { border-color: #28a745; }
        .check-warning { border-color: #ffc107; }
        .check-error { border-color: #dc3545; }
    </style>
</head>
<body class="bg-light">
    <div class="container py-5">
        <h1 class="mb-4 text-center">🔍 Vérification Données Démo</h1>
        <p class="text-center text-muted mb-5">Tableau de bord des données générées pour KMS Gestion</p>

        <?php
        // === STATISTIQUES GÉNÉRALES ===
        $stats = [];
        $checks = [];
        
        try {
            // Compter les enregistrements
            $tables = [
                'clients' => 'Clients',
                'produits' => 'Produits',
                'devis' => 'Devis',
                'ventes' => 'Ventes',
                'bons_livraison' => 'Livraisons',
                'stocks_mouvements' => 'Mouvements Stock',
                'caisse_journal' => 'Encaissements'
            ];
            
            foreach ($tables as $table => $label) {
                $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
                $stats[$label] = $stmt->fetchColumn();
            }
            
            // === VÉRIFICATIONS COHÉRENCE ===
            
            // 1. Stocks négatifs
            $stmt = $pdo->query("SELECT COUNT(*) FROM produits WHERE stock_actuel < 0");
            $stocksNegatifs = $stmt->fetchColumn();
            $checks['stocks_negatifs'] = [
                'label' => 'Stocks négatifs',
                'value' => $stocksNegatifs,
                'status' => $stocksNegatifs == 0 ? 'ok' : 'error',
                'message' => $stocksNegatifs == 0 ? 'Aucun stock négatif' : "$stocksNegatifs produits en stock négatif!"
            ];
            
            // 2. Ventes sans montant
            $stmt = $pdo->query("SELECT COUNT(*) FROM ventes WHERE montant_total_ttc = 0");
            $ventesZero = $stmt->fetchColumn();
            $checks['ventes_zero'] = [
                'label' => 'Ventes à 0€',
                'value' => $ventesZero,
                'status' => $ventesZero == 0 ? 'ok' : 'warning',
                'message' => $ventesZero == 0 ? 'Toutes les ventes ont un montant' : "$ventesZero ventes sans montant"
            ];
            
            // 3. Devis sans lignes
            $stmt = $pdo->query("
                SELECT COUNT(*) FROM devis d 
                WHERE NOT EXISTS (SELECT 1 FROM devis_lignes WHERE devis_id = d.id)
            ");
            $devisSansLignes = $stmt->fetchColumn();
            $checks['devis_vides'] = [
                'label' => 'Devis sans lignes',
                'value' => $devisSansLignes,
                'status' => $devisSansLignes == 0 ? 'ok' : 'warning',
                'message' => $devisSansLignes == 0 ? 'Tous les devis ont des lignes' : "$devisSansLignes devis vides"
            ];
            
            // 4. Ventes sans livraison
            $stmt = $pdo->query("
                SELECT COUNT(*) FROM ventes v 
                WHERE statut = 'LIVREE'
                AND NOT EXISTS (SELECT 1 FROM bons_livraison WHERE vente_id = v.id)
            ");
            $ventesSansBL = $stmt->fetchColumn();
            $checks['ventes_sans_bl'] = [
                'label' => 'Ventes livrées sans BL',
                'value' => $ventesSansBL,
                'status' => $ventesSansBL == 0 ? 'ok' : 'warning',
                'message' => $ventesSansBL == 0 ? 'Cohérence ventes/BL OK' : "$ventesSansBL ventes livrées sans BL"
            ];
            
            // 5. Taux de conversion devis
            $stmt = $pdo->query("SELECT COUNT(*) FROM devis WHERE statut = 'ACCEPTE'");
            $devisAcceptes = $stmt->fetchColumn();
            $tauxConversion = $stats['Devis'] > 0 ? round(($devisAcceptes / $stats['Devis']) * 100, 1) : 0;
            $checks['taux_conversion'] = [
                'label' => 'Taux conversion devis',
                'value' => $tauxConversion . '%',
                'status' => $tauxConversion > 0 ? 'ok' : 'warning',
                'message' => "$devisAcceptes devis acceptés sur {$stats['Devis']} total"
            ];
            
        } catch (Exception $e) {
            echo '<div class="alert alert-danger">Erreur: ' . $e->getMessage() . '</div>';
        }
        ?>

        <!-- Statistiques générales -->
        <div class="row g-4 mb-5">
            <?php foreach ($stats as $label => $count): ?>
            <div class="col-md-6 col-lg-3">
                <div class="card stat-card check-ok h-100">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2 text-muted"><?= $label ?></h6>
                        <div class="stat-number text-success"><?= number_format($count, 0, ',', ' ') ?></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Vérifications cohérence -->
        <h2 class="mb-4">✅ Vérifications de cohérence</h2>
        <div class="row g-4 mb-5">
            <?php foreach ($checks as $key => $check): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card stat-card check-<?= $check['status'] ?> h-100">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2 text-muted"><?= $check['label'] ?></h6>
                        <div class="stat-number <?= $check['status'] == 'ok' ? 'text-success' : ($check['status'] == 'warning' ? 'text-warning' : 'text-danger') ?>">
                            <?= $check['value'] ?>
                        </div>
                        <p class="card-text mt-2">
                            <small><?= $check['message'] ?></small>
                        </p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Exemples de données -->
        <h2 class="mb-4">📋 Aperçu des données</h2>
        
        <div class="accordion" id="dataAccordion">
            <!-- Derniers devis -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#devis">
                        5 derniers devis
                    </button>
                </h2>
                <div id="devis" class="accordion-collapse collapse" data-bs-parent="#dataAccordion">
                    <div class="accordion-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Numéro</th>
                                        <th>Date</th>
                                        <th>Client</th>
                                        <th>Montant TTC</th>
                                        <th>Statut</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $stmt = $pdo->query("
                                        SELECT d.numero, d.date_devis, c.nom, d.montant_total_ttc, d.statut
                                        FROM devis d
                                        JOIN clients c ON c.id = d.client_id
                                        ORDER BY d.date_devis DESC
                                        LIMIT 5
                                    ");
                                    while ($row = $stmt->fetch()):
                                    ?>
                                    <tr>
                                        <td><?= $row['numero'] ?></td>
                                        <td><?= date('d/m/Y', strtotime($row['date_devis'])) ?></td>
                                        <td><?= $row['nom'] ?></td>
                                        <td><?= number_format($row['montant_total_ttc'], 0, ',', ' ') ?> F</td>
                                        <td>
                                            <span class="badge bg-<?= $row['statut'] == 'ACCEPTE' ? 'success' : 'warning' ?>">
                                                <?= $row['statut'] ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dernières ventes -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#ventes">
                        5 dernières ventes
                    </button>
                </h2>
                <div id="ventes" class="accordion-collapse collapse" data-bs-parent="#dataAccordion">
                    <div class="accordion-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Numéro</th>
                                        <th>Date</th>
                                        <th>Client</th>
                                        <th>Montant TTC</th>
                                        <th>Statut</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $stmt = $pdo->query("
                                        SELECT v.numero, v.date_vente, c.nom, v.montant_total_ttc, v.statut
                                        FROM ventes v
                                        JOIN clients c ON c.id = v.client_id
                                        ORDER BY v.date_vente DESC
                                        LIMIT 5
                                    ");
                                    while ($row = $stmt->fetch()):
                                    ?>
                                    <tr>
                                        <td><?= $row['numero'] ?></td>
                                        <td><?= date('d/m/Y', strtotime($row['date_vente'])) ?></td>
                                        <td><?= $row['nom'] ?></td>
                                        <td><?= number_format($row['montant_total_ttc'], 0, ',', ' ') ?> F</td>
                                        <td>
                                            <span class="badge bg-<?= $row['statut'] == 'LIVREE' ? 'success' : 'info' ?>">
                                                <?= str_replace('_', ' ', $row['statut']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Produits avec stock faible -->
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#stocks">
                        Produits avec stock faible
                    </button>
                </h2>
                <div id="stocks" class="accordion-collapse collapse" data-bs-parent="#dataAccordion">
                    <div class="accordion-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Code</th>
                                        <th>Désignation</th>
                                        <th>Stock actuel</th>
                                        <th>Seuil alerte</th>
                                        <th>Statut</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $stmt = $pdo->query("
                                        SELECT code_produit, designation, stock_actuel, seuil_alerte
                                        FROM produits
                                        WHERE stock_actuel <= seuil_alerte
                                        ORDER BY stock_actuel ASC
                                        LIMIT 5
                                    ");
                                    $hasLowStock = false;
                                    while ($row = $stmt->fetch()):
                                        $hasLowStock = true;
                                    ?>
                                    <tr>
                                        <td><?= $row['code_produit'] ?></td>
                                        <td><?= $row['designation'] ?></td>
                                        <td><?= $row['stock_actuel'] ?></td>
                                        <td><?= $row['seuil_alerte'] ?></td>
                                        <td>
                                            <span class="badge bg-<?= $row['stock_actuel'] < $row['seuil_alerte'] ? 'danger' : 'warning' ?>">
                                                <?= $row['stock_actuel'] < $row['seuil_alerte'] ? 'RUPTURE' : 'FAIBLE' ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                    <?php if (!$hasLowStock): ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">
                                            <em>Aucun produit en stock faible</em>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center mt-5">
            <a href="index.php" class="btn btn-primary btn-lg">Retour à l'application</a>
            <a href="menu_donnees_demo.bat" class="btn btn-secondary btn-lg">Menu données démo</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
