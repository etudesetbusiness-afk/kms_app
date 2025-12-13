<?php
// coordination/verification_synchronisation.php - Vérifier la cohérence ventes ↔ stock ↔ caisse ↔ compta
require_once __DIR__ . '/../security.php';
exigerConnexion();
exigerPermission('VENTES_LIRE');

global $pdo;

// Récupérer toutes les ventes avec leurs montants
$stmt = $pdo->prepare("
    SELECT v.id, v.numero, v.date_vente, v.montant_total_ttc, c.nom as client_nom,
           (SELECT COUNT(*) FROM bons_livraison bl WHERE bl.vente_id = v.id) as nb_livraisons,
           (SELECT SUM(quantite) FROM ventes_lignes vl WHERE vl.vente_id = v.id) as total_quantite_commandee
    FROM ventes v
    JOIN clients c ON c.id = v.client_id
    ORDER BY v.date_vente DESC
    LIMIT 50
");
$stmt->execute();
$ventes = $stmt->fetchAll();

// Classe pour stocker les résultats de vérification
class VerificationVente {
    public $venteId;
    public $venteNumero;
    public $montantVente;
    public $montantLivraisons = 0;
    public $montantEncaissements = 0;
    public $montantRetours = 0;
    public $quantiteCommandee = 0;
    public $quantiteLivree = 0;
    public $quantiteStockSortie = 0;
    public $ecrituresComptables = 0;
    
    public function getCoherence() {
        $problemes = [];
        
        // Vérif 1: Montant livraisons vs Montant vente
        $tolerance = 100; // 100 FCFA de tolérance
        if (abs($this->montantLivraisons - $this->montantVente) > $tolerance) {
            $problemes[] = "Livraisons (" . number_format($this->montantLivraisons, 0, ',', ' ') . ") ≠ Vente (" . number_format($this->montantVente, 0, ',', ' ') . ")";
        }
        
        // Vérif 2: Quantités livrées vs Commandées
        if ($this->quantiteLivree > $this->quantiteCommandee) {
            $problemes[] = "Quantités livrées (" . $this->quantiteLivree . ") > Commandées (" . $this->quantiteCommandee . ")";
        }
        
        // Vérif 3: Stock sorties vs Quantités livrées
        if ($this->quantiteStockSortie != $this->quantiteLivree) {
            $problemes[] = "Sorties stock (" . $this->quantiteStockSortie . ") ≠ Livraisons (" . $this->quantiteLivree . ")";
        }
        
        // Vérif 4: Écritures comptables obligatoires
        if ($this->ecrituresComptables == 0) {
            $problemes[] = "Aucune écriture comptable détectée";
        }
        
        return empty($problemes) ? true : $problemes;
    }
    
    public function getStatus() {
        $ok = $this->getCoherence();
        if ($ok === true) return 'OK';
        return 'ERREUR';
    }
}

// Analyser chaque vente
$analyses = [];
foreach ($ventes as $vente) {
    $verif = new VerificationVente();
    $verif->venteId = $vente['id'];
    $verif->venteNumero = $vente['numero'];
    $verif->montantVente = $vente['montant_total_ttc'];
    $verif->quantiteCommandee = $vente['total_quantite_commandee'] ?? 0;
    
    // Montant livraisons (estimation: somme des quantités livrées × prix de vente produit)
    $stmt = $pdo->prepare("SELECT SUM(bll.quantite * p.prix_vente) AS total
                           FROM bons_livraison bl
                           JOIN bons_livraison_lignes bll ON bll.bon_livraison_id = bl.id
                           JOIN produits p ON p.id = bll.produit_id
                           WHERE bl.vente_id = ?");
    $stmt->execute([$vente['id']]);
    $livr = $stmt->fetch();
    $verif->montantLivraisons = $livr['total'] ?? 0;
    
    // Quantités livrées
    $stmt = $pdo->prepare("SELECT SUM(bll.quantite) AS total
                           FROM bons_livraison_lignes bll
                           JOIN bons_livraison bl ON bl.id = bll.bon_livraison_id
                           WHERE bl.vente_id = ?");
    $stmt->execute([$vente['id']]);
    $qliv = $stmt->fetch();
    $verif->quantiteLivree = $qliv['total'] ?? 0;
    
    // Montant encaissements (journal de caisse minimal: utiliser source_type/source_id ou commentaire)
    $stmt = $pdo->prepare("SELECT SUM(montant) as total 
                           FROM caisse_journal 
                           WHERE (source_type = 'VENTE' AND source_id = ?) 
                              OR (commentaire LIKE ?)");
    $stmt->execute([$vente['id'], '%V' . str_pad($vente['id'], 6, '0', STR_PAD_LEFT) . '%']);
    $enc = $stmt->fetch();
    $verif->montantEncaissements = $enc['total'] ?? 0;
    
    // Montant retours
    $stmt = $pdo->prepare("SELECT SUM(montant_rembourse + montant_avoir) as total FROM retours_litiges WHERE vente_id = ?");
    $stmt->execute([$vente['id']]);
    $ret = $stmt->fetch();
    $verif->montantRetours = $ret['total'] ?? 0;
    
    // Sorties stock: utiliser la relation canonique source_type/source_id
    $stmt = $pdo->prepare("SELECT SUM(quantite) as total 
                           FROM stocks_mouvements 
                           WHERE source_type = 'VENTE' AND source_id = ? AND type_mouvement = 'SORTIE'");
    $stmt->execute([$vente['id']]);
    $stk = $stmt->fetch();
    $verif->quantiteStockSortie = $stk['total'] ?? 0;
    
    // Écritures comptables liées via compta_pieces (référence VENTE)
    $stmt = $pdo->prepare("SELECT COUNT(*) as count
                           FROM compta_ecritures ce
                           JOIN compta_pieces cp ON cp.id = ce.piece_id
                           WHERE (cp.reference_type = 'VENTE' AND cp.reference_id = ?)");
    $stmt->execute([$vente['id']]);
    $ecr = $stmt->fetch();
    $verif->ecrituresComptables = $ecr['count'] ?? 0;
    
    $analyses[] = $verif;
}

include __DIR__ . '/../partials/header.php';
include __DIR__ . '/../partials/sidebar.php';
?>

<div class="container-fluid p-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h3 mb-1">Vérification de Synchronisation</h1>
            <p class="text-muted">Cohérence entre ventes, livraisons, stock et trésorerie</p>
        </div>
    </div>

    <!-- Statistiques globales -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="kms-kpi-card">
                <div class="kms-kpi-icon"><i class="bi bi-check-circle text-success"></i></div>
                <div class="kms-kpi-label">Ventes OK</div>
                <div class="kms-kpi-value">
                    <?= count(array_filter($analyses, fn($a) => $a->getStatus() === 'OK')) ?>
                </div>
                <div class="kms-kpi-subtitle">/ <?= count($analyses) ?> ventes</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="kms-kpi-card">
                <div class="kms-kpi-icon"><i class="bi bi-exclamation-triangle text-danger"></i></div>
                <div class="kms-kpi-label">Anomalies</div>
                <div class="kms-kpi-value">
                    <?= count(array_filter($analyses, fn($a) => $a->getStatus() !== 'OK')) ?>
                </div>
                <div class="kms-kpi-subtitle">à vérifier</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="kms-kpi-card">
                <div class="kms-kpi-icon"><i class="bi bi-cash-coin text-info"></i></div>
                <div class="kms-kpi-label">Total Encaissé</div>
                <div class="kms-kpi-value">
                    <?= number_format(array_sum(array_column($analyses, 'montantEncaissements')), 0, ',', ' ') ?>
                </div>
                <div class="kms-kpi-subtitle">FCFA</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="kms-kpi-card">
                <div class="kms-kpi-icon"><i class="bi bi-boxes text-warning"></i></div>
                <div class="kms-kpi-label">Total Commandé</div>
                <div class="kms-kpi-value">
                    <?= array_sum(array_column($analyses, 'quantiteCommandee')) ?>
                </div>
                <div class="kms-kpi-subtitle">articles</div>
            </div>
        </div>
    </div>

    <!-- Tableau détaillé -->
    <div class="card">
        <div class="card-header">
            <strong>Détail des Ventes</strong>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 table-sm">
                <thead>
                    <tr>
                        <th>Vente #</th>
                        <th>Client</th>
                        <th>Montant</th>
                        <th>Livré</th>
                        <th>Encaissé</th>
                        <th>Qté Cmd</th>
                        <th>Qté Liv</th>
                        <th>Stock Out</th>
                        <th>Compta</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($analyses as $ana): ?>
                        <?php $problemes = $ana->getCoherence(); $hasError = $problemes !== true; ?>
                        <tr class="<?= $hasError ? 'table-danger' : 'table-success' ?>">
                            <td>
                                <a href="<?= url_for('ventes/detail_360.php?id=' . $ana->venteId) ?>" class="text-decoration-none fw-bold">
                                    <?= htmlspecialchars($ana->venteNumero) ?>
                                </a>
                            </td>
                            <td><?= htmlspecialchars(substr($ana->venteNumero, 0, 20)) ?></td>
                            <td class="text-end"><?= number_format($ana->montantVente, 0, ',', ' ') ?></td>
                            <td class="text-end"><?= number_format($ana->montantLivraisons, 0, ',', ' ') ?></td>
                            <td class="text-end text-success fw-bold"><?= number_format($ana->montantEncaissements, 0, ',', ' ') ?></td>
                            <td class="text-end"><?= (int)$ana->quantiteCommandee ?></td>
                            <td class="text-end"><?= (int)$ana->quantiteLivree ?></td>
                            <td class="text-end"><?= (int)$ana->quantiteStockSortie ?></td>
                            <td class="text-center">
                                <?php if ($ana->ecrituresComptables > 0): ?>
                                    <span class="badge bg-success"><?= $ana->ecrituresComptables ?></span>
                                <?php else: ?>
                                    <span class="badge bg-danger">0</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($hasError): ?>
                                    <span class="badge bg-danger">⚠️ ERREUR</span>
                                <?php else: ?>
                                    <span class="badge bg-success">✅ OK</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#details-<?= $ana->venteId ?>" title="Détails">
                                    <i class="bi bi-chevron-right"></i>
                                </button>
                            </td>
                        </tr>
                        <?php if ($hasError): ?>
                            <tr class="collapse" id="details-<?= $ana->venteId ?>">
                                <td colspan="11">
                                    <div class="alert alert-danger mb-0">
                                        <strong>Problèmes détectés :</strong>
                                        <ul class="mb-0 mt-2">
                                            <?php foreach ($problemes as $p): ?>
                                                <li><?= htmlspecialchars($p) ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Instructions -->
    <div class="alert alert-info mt-4">
        <strong><i class="bi bi-info-circle"></i> Comment utiliser ce rapport :</strong>
        <ul class="mb-0 mt-2">
            <li><strong>Status OK :</strong> La vente est complètement synchronisée</li>
            <li><strong>Status ERREUR :</strong> Il existe une incohérence - cliquez sur <i class="bi bi-chevron-right"></i> pour voir les détails</li>
            <li>Cliquez sur le numéro de vente pour voir la vue 360° complète</li>
            <li>Vérifiez que les quantités commandées = livrées = sorties du stock</li>
        </ul>
    </div>
</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>
