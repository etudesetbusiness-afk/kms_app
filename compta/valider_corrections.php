<?php
/**
 * Page d'interaction pour validation des corrections comptables OHADA Cameroun
 * Permet à la comptable de visualiser et valider les corrections
 */

require_once __DIR__ . '/../security.php';
exigerConnexion();
exigerPermission('COMPTABILITE_MODIFIER');

require_once __DIR__ . '/../lib/compta.php';

global $pdo;

// Récupérer les pièces de correction en attente (non validées)
$stmt = $pdo->prepare("
    SELECT cp.*, cj.code as code_journal, cj.libelle as libelle_journal
    FROM compta_pieces cp
    JOIN compta_journaux cj ON cp.journal_id = cj.id
    WHERE cp.reference_type LIKE 'CORRECTION%' 
    AND cp.est_validee = 0
    AND cp.exercice_id = (SELECT id FROM compta_exercices WHERE est_clos = 0 ORDER BY annee DESC LIMIT 1)
    ORDER BY cp.date_piece DESC
");
$stmt->execute();
$corrections_en_attente = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Traitement de la validation
$message_validation = '';
$type_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verifierCsrf($_POST['csrf_token'] ?? '');
    
    $piece_id = (int)$_POST['piece_id'] ?? 0;
    $action = $_POST['action'];
    
    if ($action === 'valider') {
        // Valider la pièce
        $stmt = $pdo->prepare("UPDATE compta_pieces SET est_validee = 1 WHERE id = ?");
        $stmt->execute([$piece_id]);
        $message_validation = "✅ Pièce #{$piece_id} validée avec succès !";
        $type_message = 'success';
        
        // Recharger la liste
        $stmt->execute();
        $corrections_en_attente = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt = $pdo->prepare("
            SELECT cp.*, cj.code as code_journal, cj.libelle as libelle_journal
            FROM compta_pieces cp
            JOIN compta_journaux cj ON cp.journal_id = cj.id
            WHERE cp.reference_type LIKE 'CORRECTION%' 
            AND cp.est_validee = 0
            AND cp.exercice_id = (SELECT id FROM compta_exercices WHERE est_clos = 0 ORDER BY annee DESC LIMIT 1)
            ORDER BY cp.date_piece DESC
        ");
        $stmt->execute();
        $corrections_en_attente = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } elseif ($action === 'rejeter') {
        // Rejeter la pièce (supprimer les écritures)
        $stmt = $pdo->prepare("DELETE FROM compta_ecritures WHERE piece_id = ?");
        $stmt->execute([$piece_id]);
        
        $stmt = $pdo->prepare("DELETE FROM compta_pieces WHERE id = ?");
        $stmt->execute([$piece_id]);
        
        $message_validation = "❌ Pièce #{$piece_id} rejetée et supprimée";
        $type_message = 'danger';
        
        // Recharger
        $stmt = $pdo->prepare("
            SELECT cp.*, cj.code as code_journal, cj.libelle as libelle_journal
            FROM compta_pieces cp
            JOIN compta_journaux cj ON cp.journal_id = cj.id
            WHERE cp.reference_type LIKE 'CORRECTION%' 
            AND cp.est_validee = 0
            AND cp.exercice_id = (SELECT id FROM compta_exercices WHERE est_clos = 0 ORDER BY annee DESC LIMIT 1)
            ORDER BY cp.date_piece DESC
        ");
        $stmt->execute();
        $corrections_en_attente = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

include __DIR__ . '/../partials/header.php';
include __DIR__ . '/../partials/sidebar.php';
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3 mb-1"><i class="bi bi-pencil-square text-warning"></i> Validation des corrections comptables</h1>
            <p class="text-muted">Pièces en attente de validation OHADA Cameroun</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="<?= url_for('compta/balance.php') ?>" class="btn btn-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Retour au bilan
            </a>
        </div>
    </div>

    <?php if ($message_validation): ?>
    <div class="alert alert-<?= $type_message ?> alert-dismissible fade show" role="alert">
        <?= $message_validation ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if (empty($corrections_en_attente)): ?>
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i> Aucune pièce de correction en attente de validation.
        <a href="<?= url_for('compta/balance.php') ?>" class="alert-link">Voir le bilan</a>
    </div>
    <?php else: ?>

    <div class="row g-3">
        <?php foreach ($corrections_en_attente as $piece): ?>
        
        <!-- Carte de la pièce -->
        <div class="col-12">
            <div class="card border-warning shadow-sm">
                <div class="card-header bg-warning bg-opacity-10 border-warning">
                    <div class="row align-items-center">
                        <div class="col">
                            <h5 class="mb-1">
                                <i class="bi bi-file-earmark-text"></i> 
                                Pièce <?= htmlspecialchars($piece['numero_piece']) ?>
                            </h5>
                            <small class="text-muted">
                                Journal: <?= htmlspecialchars($piece['code_journal'] . ' - ' . $piece['libelle_journal']) ?> 
                                | Date: <?= date('d/m/Y', strtotime($piece['date_piece'])) ?>
                            </small>
                        </div>
                        <div class="col-md-3 text-end">
                            <span class="badge bg-warning">EN ATTENTE</span>
                        </div>
                    </div>
                </div>

                <!-- Détails de la pièce -->
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p class="mb-2">
                                <strong>Description :</strong><br>
                                <code><?= htmlspecialchars($piece['observations'] ?? 'Correction anomalie comptable') ?></code>
                            </p>
                        </div>
                    </div>

                    <!-- Écritures de la pièce -->
                    <div class="table-responsive mb-3">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Compte</th>
                                    <th>Libellé du compte</th>
                                    <th class="text-end">Débit</th>
                                    <th class="text-end">Crédit</th>
                                    <th>Libellé écriture</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                            // Récupérer les écritures de la pièce
                            $stmt2 = $pdo->prepare("
                                SELECT ce.*, cc.numero_compte, cc.libelle
                                FROM compta_ecritures ce
                                JOIN compta_comptes cc ON ce.compte_id = cc.id
                                WHERE ce.piece_id = ?
                                ORDER BY ce.ordre_ligne
                            ");
                            $stmt2->execute([$piece['id']]);
                            $ecritures = $stmt2->fetchAll(PDO::FETCH_ASSOC);
                            
                            $total_debit = 0;
                            $total_credit = 0;
                            
                            foreach ($ecritures as $ec):
                                $total_debit += $ec['debit'];
                                $total_credit += $ec['credit'];
                            ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($ec['numero_compte']) ?></strong></td>
                                    <td><?= htmlspecialchars($ec['libelle']) ?></td>
                                    <td class="text-end">
                                        <?php if ($ec['debit'] > 0): ?>
                                            <strong class="text-success"><?= number_format($ec['debit'], 2, ',', ' ') ?></strong>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($ec['credit'] > 0): ?>
                                            <strong class="text-danger"><?= number_format($ec['credit'], 2, ',', ' ') ?></strong>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td><small><?= htmlspecialchars($ec['libelle_ecriture'] ?? '') ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                                <tr class="table-light fw-bold">
                                    <td colspan="2">TOTAL</td>
                                    <td class="text-end text-success"><?= number_format($total_debit, 2, ',', ' ') ?></td>
                                    <td class="text-end text-danger"><?= number_format($total_credit, 2, ',', ' ') ?></td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Vérification équilibre -->
                    <?php 
                    $ecart = abs($total_debit - $total_credit);
                    $est_equilibree = $ecart < 0.01;
                    ?>
                    <div class="alert <?= $est_equilibree ? 'alert-success' : 'alert-danger' ?>">
                        <i class="bi <?= $est_equilibree ? 'bi-check-circle' : 'bi-exclamation-triangle' ?>"></i>
                        <strong><?= $est_equilibree ? 'Pièce équilibrée' : 'Pièce déséquilibrée' ?></strong>
                        (Écart: <?= number_format($ecart, 2, ',', ' ') ?> FCFA)
                    </div>

                    <!-- Actions -->
                    <div class="d-flex gap-2 justify-content-end">
                        <form method="POST" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir REJETER cette correction ?');">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken()) ?>">
                            <input type="hidden" name="piece_id" value="<?= $piece['id'] ?>">
                            <input type="hidden" name="action" value="rejeter">
                            <button type="submit" class="btn btn-danger btn-sm">
                                <i class="bi bi-trash"></i> Rejeter
                            </button>
                        </form>

                        <?php if ($est_equilibree): ?>
                        <form method="POST" class="d-inline" onsubmit="return confirm('Valider cette correction comptable ?');">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken()) ?>">
                            <input type="hidden" name="piece_id" value="<?= $piece['id'] ?>">
                            <input type="hidden" name="action" value="valider">
                            <button type="submit" class="btn btn-success btn-sm">
                                <i class="bi bi-check-lg"></i> Valider
                            </button>
                        </form>
                        <?php else: ?>
                        <button class="btn btn-success btn-sm" disabled title="La pièce doit être équilibrée">
                            <i class="bi bi-check-lg"></i> Valider (désactivé)
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php endforeach; ?>
    </div>

    <!-- Section d'information -->
    <div class="row mt-5">
        <div class="col-12">
            <div class="card bg-light border-0">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Informations sur les corrections</h5>
                </div>
                <div class="card-body">
                    <h6>📋 Qu'est-ce qu'une pièce de correction ?</h6>
                    <p class="text-muted mb-3">
                        Les pièces de correction permettent de rectifier les anomalies comptables détectées lors de l'analyse du bilan. 
                        Elles doivent être équilibrées (Débit = Crédit) avant validation.
                    </p>

                    <h6>🇨🇲 Normes OHADA Cameroun respectées :</h6>
                    <ul class="text-muted">
                        <li><strong>Classe 2 (Immobilisations) :</strong> Actif immobilisé seulement (pas de stocks)</li>
                        <li><strong>Classe 3 (Stocks) :</strong> Marchandises, produits finis, matières premières</li>
                        <li><strong>Classe 5 (Trésorerie) :</strong> Caisse, banque toujours débitrice (solde positif)</li>
                        <li><strong>Double-entrée :</strong> Chaque débit a un crédit équivalent</li>
                    </ul>

                    <h6 class="mt-3">✅ Après validation :</h6>
                    <ul class="text-muted">
                        <li>La pièce sera marquée comme validée</li>
                        <li>Les écritures seront prises en compte dans le bilan</li>
                        <li>Le bilan sera recalculé automatiquement</li>
                        <li>Vous pourrez exporter le bilan corrigé en Excel</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <?php endif; ?>

</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>
