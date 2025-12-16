<?php
/**
 * Dashboard d'analyse des corrections OHADA Cameroun
 * Affiche le bilan avant/après corrections avec comparaison
 */

require_once __DIR__ . '/../security.php';
exigerConnexion();
exigerPermission('COMPTABILITE_LIRE');

require_once __DIR__ . '/../lib/compta.php';

global $pdo;

// Récupérer l'exercice actif
$exercice = compta_get_exercice_actif($pdo);
$exercice_id = $exercice['id'] ?? 2;

// Fonction pour calculer le bilan
function calculer_bilan($pdo, $exercice_id, $inclure_non_validees = false) {
    $balance = compta_get_balance($pdo, $exercice_id);
    
    $totaux = [
        '1' => 0, '2' => 0, '3' => 0, '4' => 0, 
        '5' => 0, '6' => 0, '7' => 0
    ];
    
    foreach ($balance as $ligne) {
        $solde = $ligne['total_debit'] - $ligne['total_credit'];
        $classe = $ligne['classe'];
        if (isset($totaux[$classe])) {
            $totaux[$classe] += $solde;
        }
    }
    
    $total_actif = 0;
    $total_passif = 0;
    
    // Classe 2 : Immobilisations (ACTIF)
    if ($totaux['2'] > 0) {
        $total_actif += $totaux['2'];
    }
    
    // Classe 3 : Stocks (ACTIF)
    if ($totaux['3'] > 0) {
        $total_actif += $totaux['3'];
    }
    
    // Classe 4 : Tiers (ACTIF si débiteur > 0, PASSIF si créditeur < 0)
    if ($totaux['4'] > 0) {
        $total_actif += $totaux['4'];
    } else {
        $total_passif += abs($totaux['4']);
    }
    
    // Classe 5 : Trésorerie (ACTIF si > 0, PASSIF si < 0)
    if ($totaux['5'] > 0) {
        $total_actif += $totaux['5'];
    } else {
        $total_passif += abs($totaux['5']);
    }
    
    // Classe 1 : Capitaux propres (PASSIF - toujours créditrice, donc négative en solde)
    // Le solde de la classe 1 est négatif (créditeur), donc on prend la valeur absolue
    $total_passif += abs($totaux['1']);
    
    // Classe 6 : Charges (solde positif = charges)
    // Classe 7 : Produits (solde négatif = produits)
    // Résultat = Produits - Charges
    // Solde classe 7 est négatif (créditeur/produits) donc |solde7| = produits
    // Solde classe 6 est positif (débiteur/charges)
    $resultat = abs($totaux['7']) - $totaux['6'];
    
    return [
        'totaux' => $totaux,
        'total_actif' => $total_actif,
        'total_passif' => $total_passif,
        'resultat' => $resultat,
        'ecart' => $total_actif - ($total_passif + $resultat)
    ];
}

// Bilan avec toutes les écritures validées
$bilan_complet = calculer_bilan($pdo, $exercice_id, true);

// Vérifier si corrections en attente
$stmt = $pdo->prepare("
    SELECT COUNT(*) as nb_corrections
    FROM compta_pieces cp
    WHERE cp.reference_type LIKE 'CORRECTION%' 
    AND cp.est_validee = 0
    AND cp.exercice_id = ?
");
$stmt->execute([$exercice_id]);
$result_corr = $stmt->fetch(PDO::FETCH_ASSOC);
$nb_corrections = $result_corr['nb_corrections'] ?? 0;

// Récupérer détails des corrections
$corrections = [];
if ($nb_corrections > 0) {
    $stmt = $pdo->prepare("
        SELECT cp.id, cp.numero_piece, cp.observations, cp.reference_type,
               SUM(ce.debit) as total_debit, SUM(ce.credit) as total_credit
        FROM compta_pieces cp
        LEFT JOIN compta_ecritures ce ON cp.id = ce.piece_id
        WHERE cp.reference_type LIKE 'CORRECTION%' 
        AND cp.est_validee = 0
        AND cp.exercice_id = ?
        GROUP BY cp.id, cp.numero_piece, cp.observations, cp.reference_type
    ");
    $stmt->execute([$exercice_id]);
    $corrections = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

include __DIR__ . '/../partials/header.php';
include __DIR__ . '/../partials/sidebar.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3 mb-1"><i class="bi bi-bar-chart-line text-primary"></i> Analyse du bilan OHADA Cameroun</h1>
            <p class="text-muted">Exercice <?= $exercice['annee'] ?? '2025' ?> - État actuel et corrections en attente</p>
        </div>
        <div class="col-md-4 text-end">
            <?php if ($nb_corrections > 0): ?>
            <a href="<?= url_for('compta/valider_corrections.php') ?>" class="btn btn-warning btn-sm">
                <i class="bi bi-pencil-square"></i> <?= $nb_corrections ?> correction(s) en attente
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- KPI : État du bilan -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-success bg-opacity-10">
                <div class="card-body">
                    <h6 class="text-muted small mb-2"><i class="bi bi-bank"></i> TOTAL ACTIF</h6>
                    <h3 class="text-success">
                        <?= number_format($bilan_complet['total_actif'], 0, ',', ' ') ?>
                    </h3>
                    <small class="text-muted">Ressources disponibles</small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-danger bg-opacity-10">
                <div class="card-body">
                    <h6 class="text-muted small mb-2"><i class="bi bi-shield-exclamation"></i> PASSIF + RÉSULTAT</h6>
                    <h3 class="text-danger">
                        <?= number_format($bilan_complet['total_passif'] + $bilan_complet['resultat'], 0, ',', ' ') ?>
                    </h3>
                    <small class="text-muted">Passif: <?= number_format($bilan_complet['total_passif'], 0, ',', ' ') ?> + Rés: <?= number_format($bilan_complet['resultat'], 0, ',', ' ') ?></small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-primary bg-opacity-10">
                <div class="card-body">
                    <h6 class="text-muted small mb-2"><i class="bi bi-graph-up"></i> RÉSULTAT EXERCICE</h6>
                    <h3 class="<?= $bilan_complet['resultat'] >= 0 ? 'text-success' : 'text-danger' ?>">
                        <?= number_format($bilan_complet['resultat'], 0, ',', ' ') ?>
                    </h3>
                    <small class="text-muted"><?= $bilan_complet['resultat'] >= 0 ? 'Bénéfice' : 'Perte' ?></small>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm <?= abs($bilan_complet['ecart']) < 0.01 ? 'bg-info' : 'bg-warning' ?> bg-opacity-10">
                <div class="card-body">
                    <h6 class="text-muted small mb-2"><i class="bi bi-shuffle"></i> ÉCART</h6>
                    <h3 class="<?= abs($bilan_complet['ecart']) < 0.01 ? 'text-info' : 'text-warning' ?>">
                        <?= number_format(abs($bilan_complet['ecart']), 0, ',', ' ') ?>
                    </h3>
                    <small class="text-muted">
                        <?= abs($bilan_complet['ecart']) < 0.01 ? '✅ Équilibré' : '⚠️ Déséquilibré' ?>
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Alertes et status -->
    <?php if ($nb_corrections > 0): ?>
    <div class="alert alert-warning alert-dismissible fade show mb-4">
        <i class="bi bi-exclamation-triangle"></i>
        <strong><?= $nb_corrections ?> correction(s) en attente de validation</strong>
        <p class="mb-0 small mt-2">
            Vous avez <?= $nb_corrections === 1 ? 'une pièce' : 'des pièces' ?> de correction détectée(s) lors de la vérification OHADA.
            <a href="<?= url_for('compta/valider_corrections.php') ?>" class="alert-link">Valider les corrections →</a>
        </p>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php else: ?>
    <div class="alert alert-success mb-4">
        <i class="bi bi-check-circle"></i> Aucune correction en attente. Le bilan est à jour.
    </div>
    <?php endif; ?>

    <!-- Détails des corrections en attente -->
    <?php if ($nb_corrections > 0): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-warning shadow-sm">
                <div class="card-header bg-warning bg-opacity-10 border-warning">
                    <h5 class="mb-0"><i class="bi bi-clipboard-check"></i> Corrections OHADA en attente</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Pièce</th>
                                    <th>Description</th>
                                    <th class="text-center">Montant</th>
                                    <th class="text-center">Statut</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($corrections as $c): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($c['numero_piece']) ?></strong></td>
                                    <td><?= htmlspecialchars($c['observations'] ?? 'Correction comptable') ?></td>
                                    <td class="text-center">
                                        <code><?= number_format($c['total_debit'] ?? 0, 0, ',', ' ') ?> FCFA</code>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-warning text-dark">EN ATTENTE</span>
                                    </td>
                                    <td>
                                        <a href="<?= url_for('compta/valider_corrections.php') ?>" class="btn btn-sm btn-outline-warning">
                                            <i class="bi bi-arrow-right"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Tableau détaillé par classe -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="bi bi-diagram-3"></i> Balance par classe (OHADA)</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Classe</th>
                                    <th>Description OHADA</th>
                                    <th class="text-end">Montant</th>
                                    <th class="text-center">Type</th>
                                    <th>Remarques</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php 
                            $classes_ohada = [
                                '1' => ['Capitaux propres', 'PASSIF', 'Capital + Réserves'],
                                '2' => ['Immobilisations', 'ACTIF', 'Bâtiments, matériel, incorporel'],
                                '3' => ['Stocks & En-cours', 'ACTIF', 'Marchandises, produits finis'],
                                '4' => ['Tiers', 'ACTIF/PASSIF', 'Clients, fournisseurs, personnel'],
                                '5' => ['Trésorerie', 'ACTIF/PASSIF', 'Banque, caisse, placements'],
                                '6' => ['Charges', 'CHARGE', 'Coûts d\'exploitation'],
                                '7' => ['Produits', 'PRODUIT', 'Revenus d\'exploitation']
                            ];
                            
                            foreach ($classes_ohada as $num => $info):
                                $val = $bilan_complet['totaux'][$num] ?? 0;
                                
                                // Déterminer le type d'affichage selon la classe
                                if ($num === '1') {
                                    $type_affichage = 'PASSIF';
                                } elseif ($num === '2' || $num === '3') {
                                    $type_affichage = $val > 0 ? 'ACTIF' : 'PASSIF';
                                } elseif ($num === '4' || $num === '5') {
                                    $type_affichage = $val > 0 ? 'ACTIF' : 'PASSIF';
                                } elseif ($num === '6') {
                                    $type_affichage = 'CHARGE';
                                } elseif ($num === '7') {
                                    $type_affichage = 'PRODUIT';
                                } else {
                                    $type_affichage = 'AUTRE';
                                }
                                
                                $couleur = match($type_affichage) {
                                    'ACTIF' => 'text-success',
                                    'PASSIF' => 'text-danger',
                                    'CHARGE' => 'text-warning',
                                    'PRODUIT' => 'text-info',
                                    default => 'text-secondary'
                                };
                            ?>
                                <tr>
                                    <td><strong class="text-primary"><?= $num ?></strong></td>
                                    <td><?= $info[0] ?></td>
                                    <td class="text-end"><strong class="<?= $couleur ?>">
                                        <?= number_format(abs($val), 0, ',', ' ') ?>
                                    </strong></td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary"><?= $type_affichage ?></span>
                                    </td>
                                    <td>
                                        <small class="text-muted"><?= $info[2] ?></small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                                <tr class="table-light fw-bold">
                                    <td colspan="2">RÉSULTAT EXERCICE</td>
                                    <td class="text-end text-info">
                                        <?= number_format($bilan_complet['resultat'], 0, ',', ' ') ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-info">RÉSULTAT</span>
                                    </td>
                                    <td>Produits - Charges</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Guide d'action -->
    <div class="row">
        <div class="col-12">
            <div class="card bg-light border-0">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-lightbulb"></i> Prochaines étapes</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Corrections en attente :</h6>
                            <?php if ($nb_corrections > 0): ?>
                            <ol class="text-muted small">
                                <li>Accédez à la page "Corrections en attente" via le menu</li>
                                <li>Vérifiez les détails de chaque pièce</li>
                                <li>Validez ou rejetez chaque correction</li>
                                <li>Le bilan sera automatiquement recalculé</li>
                                <li>Exportez le bilan en Excel depuis la page Balance & Bilan</li>
                            </ol>
                            <?php else: ?>
                            <p class="text-muted small">✅ Toutes les corrections ont été validées</p>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <h6>Normes OHADA Cameroun :</h6>
                            <ul class="text-muted small">
                                <li>✅ Classe 3 pour tous les stocks</li>
                                <li>✅ Classe 5 avec soldes débiteurs (caisse positive)</li>
                                <li>✅ Double-entrée équilibrée</li>
                                <li>✅ Bilan équilibré (Actif = Passif + Résultat)</li>
                                <li>✅ Conformité SYSCOHADA</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>
