<?php
// terrain/reporting/pdf.php - Version imprimable du rapport hebdomadaire terrain
require_once __DIR__ . '/../../security.php';
exigerConnexion();
exigerPermission('CLIENTS_CREER');

global $pdo;

$utilisateur = utilisateurConnecte();
$userId      = (int)$utilisateur['id'];
$voirTout    = peutVoirToutesProspections();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    header('Location: ' . url_for('terrain/reporting/list.php'));
    exit;
}

// Charger le rapport
$stmt = $pdo->prepare("
    SELECT rt.*, u.nom_complet AS commercial_nom
    FROM reporting_terrain rt
    LEFT JOIN utilisateurs u ON rt.commercial_id = u.id
    WHERE rt.id = ?
");
$stmt->execute([$id]);
$rapport = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$rapport) {
    $_SESSION['flash_error'] = "Rapport introuvable.";
    header('Location: ' . url_for('terrain/reporting/list.php'));
    exit;
}

// Vérifier les droits d'accès
if (!$voirTout && $rapport['commercial_id'] != $userId) {
    $_SESSION['flash_error'] = "Vous n'avez pas accès à ce rapport.";
    header('Location: ' . url_for('terrain/reporting/list.php'));
    exit;
}

// Charger les données liées
$zones = $pdo->prepare("SELECT * FROM reporting_terrain_zones WHERE reporting_id = ?");
$zones->execute([$id]);
$zones = $zones->fetchAll(PDO::FETCH_ASSOC);

$journal = $pdo->prepare("SELECT * FROM reporting_terrain_journal WHERE reporting_id = ? ORDER BY jour_semaine");
$journal->execute([$id]);
$journal = $journal->fetchAll(PDO::FETCH_ASSOC);

$produits = $pdo->prepare("SELECT * FROM reporting_terrain_produits WHERE reporting_id = ?");
$produits->execute([$id]);
$produits = $produits->fetchAll(PDO::FETCH_ASSOC);

$objections = $pdo->prepare("SELECT * FROM reporting_terrain_objections WHERE reporting_id = ?");
$objections->execute([$id]);
$objections = $objections->fetchAll(PDO::FETCH_ASSOC);

$arguments = $pdo->prepare("SELECT * FROM reporting_terrain_arguments WHERE reporting_id = ?");
$arguments->execute([$id]);
$arguments = $arguments->fetchAll(PDO::FETCH_ASSOC);

$actions = $pdo->prepare("SELECT * FROM reporting_terrain_actions WHERE reporting_id = ?");
$actions->execute([$id]);
$actions = $actions->fetchAll(PDO::FETCH_ASSOC);

// Calculer les totaux
$totalVisites = array_sum(array_column($journal, 'nb_visites'));
$totalRdv     = array_sum(array_column($journal, 'nb_rdv'));
$totalDevis   = array_sum(array_column($journal, 'nb_devis'));
$totalVentes  = array_sum(array_column($journal, 'nb_ventes'));
$totalCa      = array_sum(array_column($journal, 'ca_realise'));

$joursSemaine = ['', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];

$statuts = [
    'BROUILLON' => 'Brouillon',
    'SOUMIS'    => 'Soumis',
    'VALIDE'    => 'Validé',
    'REJETE'    => 'Rejeté',
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapport Terrain S<?= $rapport['numero_semaine'] ?>/<?= $rapport['annee'] ?> - <?= htmlspecialchars($rapport['commercial_nom']) ?></title>
    <style>
        @page {
            size: A4;
            margin: 15mm;
        }
        * {
            box-sizing: border-box;
            font-family: 'Segoe UI', Arial, sans-serif;
        }
        body {
            font-size: 10pt;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #0d6efd;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .header h1 {
            margin: 0;
            font-size: 16pt;
            color: #0d6efd;
        }
        .header .meta {
            text-align: right;
            font-size: 9pt;
            color: #666;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 8pt;
            font-weight: bold;
        }
        .badge-success { background: #198754; color: white; }
        .badge-primary { background: #0d6efd; color: white; }
        .badge-secondary { background: #6c757d; color: white; }
        .badge-danger { background: #dc3545; color: white; }
        
        .section {
            margin-bottom: 15px;
            page-break-inside: avoid;
        }
        .section-title {
            background: #f8f9fa;
            border-left: 4px solid #0d6efd;
            padding: 5px 10px;
            font-weight: bold;
            font-size: 11pt;
            margin-bottom: 8px;
        }
        
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 10px;
            margin-bottom: 15px;
        }
        .kpi-box {
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 8px;
            text-align: center;
        }
        .kpi-box .label {
            font-size: 8pt;
            color: #666;
        }
        .kpi-box .value {
            font-size: 14pt;
            font-weight: bold;
            color: #0d6efd;
        }
        .kpi-box .target {
            font-size: 8pt;
            color: #999;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
        }
        th, td {
            border: 1px solid #dee2e6;
            padding: 5px 8px;
        }
        th {
            background: #f8f9fa;
            font-weight: bold;
            text-align: left;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        
        .two-cols {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .list-item {
            border-bottom: 1px solid #eee;
            padding: 5px 0;
        }
        .list-item:last-child {
            border-bottom: none;
        }
        
        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #dee2e6;
            font-size: 8pt;
            color: #999;
            text-align: center;
        }
        
        @media print {
            body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
            .no-print { display: none; }
        }
        
        .print-btn {
            position: fixed;
            top: 10px;
            right: 10px;
            padding: 10px 20px;
            background: #0d6efd;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12pt;
        }
        .print-btn:hover { background: #0b5ed7; }
    </style>
</head>
<body>
    <button class="print-btn no-print" onclick="window.print()">🖨️ Imprimer</button>

    <div class="header">
        <div>
            <h1>RAPPORT HEBDOMADAIRE TERRAIN</h1>
            <div style="margin-top:5px">
                <strong>Semaine <?= $rapport['numero_semaine'] ?> / <?= $rapport['annee'] ?></strong>
                &nbsp;•&nbsp;
                <?= date('d/m/Y', strtotime($rapport['date_debut'])) ?> → <?= date('d/m/Y', strtotime($rapport['date_fin'])) ?>
            </div>
        </div>
        <div class="meta">
            <div><strong><?= htmlspecialchars($rapport['commercial_nom']) ?></strong></div>
            <div>
                <span class="badge badge-<?= $rapport['statut'] === 'VALIDE' ? 'success' : ($rapport['statut'] === 'SOUMIS' ? 'primary' : 'secondary') ?>">
                    <?= $statuts[$rapport['statut']] ?? $rapport['statut'] ?>
                </span>
            </div>
            <div style="margin-top:5px">Édité le <?= date('d/m/Y H:i') ?></div>
        </div>
    </div>

    <!-- KPIs -->
    <div class="kpi-grid">
        <div class="kpi-box">
            <div class="label">Visites</div>
            <div class="value"><?= $totalVisites ?></div>
            <?php if ($rapport['objectif_visites'] > 0): ?>
                <div class="target">/ <?= $rapport['objectif_visites'] ?></div>
            <?php endif; ?>
        </div>
        <div class="kpi-box">
            <div class="label">RDV</div>
            <div class="value"><?= $totalRdv ?></div>
            <?php if ($rapport['objectif_rdv'] > 0): ?>
                <div class="target">/ <?= $rapport['objectif_rdv'] ?></div>
            <?php endif; ?>
        </div>
        <div class="kpi-box">
            <div class="label">Devis</div>
            <div class="value"><?= $totalDevis ?></div>
        </div>
        <div class="kpi-box">
            <div class="label">Ventes</div>
            <div class="value"><?= $totalVentes ?></div>
        </div>
        <div class="kpi-box">
            <div class="label">CA Réalisé</div>
            <div class="value" style="font-size:12pt"><?= number_format($totalCa, 0, ',', ' ') ?> F</div>
            <?php if ($rapport['objectif_ca'] > 0): ?>
                <div class="target">/ <?= number_format($rapport['objectif_ca'], 0, ',', ' ') ?></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Journal quotidien -->
    <?php if (!empty($journal)): ?>
    <div class="section">
        <div class="section-title">📅 SUIVI JOURNALIER</div>
        <table>
            <thead>
                <tr>
                    <th>Jour</th>
                    <th class="text-center">Visites</th>
                    <th class="text-center">RDV</th>
                    <th class="text-center">Devis</th>
                    <th class="text-center">Ventes</th>
                    <th class="text-right">CA</th>
                    <th>Zone</th>
                    <th>Remarques</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($journal as $j): ?>
                <tr>
                    <td><strong><?= $joursSemaine[$j['jour_semaine']] ?? '' ?></strong></td>
                    <td class="text-center"><?= (int)$j['nb_visites'] ?></td>
                    <td class="text-center"><?= (int)$j['nb_rdv'] ?></td>
                    <td class="text-center"><?= (int)$j['nb_devis'] ?></td>
                    <td class="text-center"><?= (int)$j['nb_ventes'] ?></td>
                    <td class="text-right"><?= number_format((float)$j['ca_realise'], 0, ',', ' ') ?></td>
                    <td><?= htmlspecialchars($j['zone_couverte'] ?? '') ?></td>
                    <td><?= htmlspecialchars($j['remarques'] ?? '') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background:#e9ecef; font-weight:bold">
                    <td>TOTAL</td>
                    <td class="text-center"><?= $totalVisites ?></td>
                    <td class="text-center"><?= $totalRdv ?></td>
                    <td class="text-center"><?= $totalDevis ?></td>
                    <td class="text-center"><?= $totalVentes ?></td>
                    <td class="text-right"><?= number_format($totalCa, 0, ',', ' ') ?></td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
    </div>
    <?php endif; ?>

    <!-- Zones -->
    <?php if (!empty($zones)): ?>
    <div class="section">
        <div class="section-title">📍 ZONES PROSPECTÉES</div>
        <table>
            <thead>
                <tr>
                    <th>Zone</th>
                    <th>Type cible</th>
                    <th>Potentiel</th>
                    <th>Remarques</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($zones as $z): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($z['nom_zone']) ?></strong></td>
                    <td><?= $z['type_cible'] ?></td>
                    <td><?= $z['potentiel'] ?></td>
                    <td><?= htmlspecialchars($z['remarques'] ?? '') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- Produits vendus -->
    <?php if (!empty($produits)): ?>
    <div class="section">
        <div class="section-title">📦 PRODUITS VENDUS</div>
        <table>
            <thead>
                <tr>
                    <th>Produit</th>
                    <th class="text-center">Quantité</th>
                    <th class="text-right">Montant</th>
                    <th>Type client</th>
                    <th>Remarques</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $totalProduits = 0;
                foreach ($produits as $p): 
                    $totalProduits += (float)$p['montant_total'];
                ?>
                <tr>
                    <td><?= htmlspecialchars($p['produit_nom']) ?></td>
                    <td class="text-center"><?= (int)$p['quantite'] ?></td>
                    <td class="text-right"><?= number_format((float)$p['montant_total'], 0, ',', ' ') ?> F</td>
                    <td><?= $p['type_client'] ?></td>
                    <td><?= htmlspecialchars($p['remarques'] ?? '') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="background:#e9ecef; font-weight:bold">
                    <td colspan="2">TOTAL</td>
                    <td class="text-right"><?= number_format($totalProduits, 0, ',', ' ') ?> F</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
    </div>
    <?php endif; ?>

    <div class="two-cols">
        <!-- Objections -->
        <?php if (!empty($objections)): ?>
        <div class="section">
            <div class="section-title">💬 OBJECTIONS RENCONTRÉES</div>
            <?php foreach ($objections as $o): ?>
            <div class="list-item">
                <strong><?= htmlspecialchars($o['objection']) ?></strong>
                <span style="color:#666">(<?= $o['frequence'] ?>)</span>
                <?php if ($o['reponse_apportee']): ?>
                <br><small style="color:#198754">↳ <?= htmlspecialchars($o['reponse_apportee']) ?></small>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Arguments -->
        <?php if (!empty($arguments)): ?>
        <div class="section">
            <div class="section-title">💡 ARGUMENTS EFFICACES</div>
            <?php foreach ($arguments as $a): ?>
            <div class="list-item">
                <strong><?= htmlspecialchars($a['argument']) ?></strong>
                <span style="color:#666">(<?= $a['efficacite'] ?>)</span>
                <?php if ($a['contexte']): ?>
                <br><small style="color:#666"><?= htmlspecialchars($a['contexte']) ?></small>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Actions -->
    <?php if (!empty($actions)): ?>
    <div class="section">
        <div class="section-title">✅ PLAN D'ACTIONS</div>
        <table>
            <thead>
                <tr>
                    <th>Action</th>
                    <th>Échéance</th>
                    <th>Priorité</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($actions as $act): ?>
                <tr>
                    <td><?= htmlspecialchars($act['description']) ?></td>
                    <td><?= $act['echeance'] ? date('d/m/Y', strtotime($act['echeance'])) : '-' ?></td>
                    <td><?= $act['priorite'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- Synthèse -->
    <?php if (!empty($rapport['synthese'])): ?>
    <div class="section">
        <div class="section-title">📝 SYNTHÈSE DE LA SEMAINE</div>
        <div style="padding:10px; background:#f8f9fa; border-radius:5px">
            <p style="margin:0"><?= nl2br(htmlspecialchars($rapport['synthese'])) ?></p>
            
            <?php if (!empty($rapport['points_forts'])): ?>
            <div style="margin-top:10px">
                <strong style="color:#198754">✓ Points forts :</strong>
                <p style="margin:5px 0 0 0"><?= nl2br(htmlspecialchars($rapport['points_forts'])) ?></p>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($rapport['difficultes'])): ?>
            <div style="margin-top:10px">
                <strong style="color:#ffc107">⚠ Difficultés :</strong>
                <p style="margin:5px 0 0 0"><?= nl2br(htmlspecialchars($rapport['difficultes'])) ?></p>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($rapport['besoins_support'])): ?>
            <div style="margin-top:10px">
                <strong style="color:#0dcaf0">ℹ Besoins de support :</strong>
                <p style="margin:5px 0 0 0"><?= nl2br(htmlspecialchars($rapport['besoins_support'])) ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="footer">
        <strong>KMS GESTION</strong> • Rapport généré automatiquement • 
        Document interne - Ne pas diffuser
    </div>
</body>
</html>
