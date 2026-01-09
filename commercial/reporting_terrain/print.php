<?php
/**
 * Reporting Terrain - Version Imprimable A4
 * Module: commercial/reporting_terrain/print.php
 * Layout optimisé pour impression via window.print()
 */

require_once __DIR__ . '/../../security.php';
exigerConnexion();

global $pdo;
$utilisateur = utilisateurConnecte();

// Récupérer l'ID du reporting
$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    $_SESSION['flash_error'] = 'Reporting non trouvé.';
    header('Location: ' . url_for('commercial/reporting_terrain/'));
    exit;
}

// Charger le reporting principal
$stmt = $pdo->prepare("
    SELECT r.*, u.nom_complet as user_nom
    FROM terrain_reporting r
    LEFT JOIN utilisateurs u ON r.user_id = u.id
    WHERE r.id = ?
");
$stmt->execute([$id]);
$reporting = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reporting) {
    $_SESSION['flash_error'] = 'Reporting non trouvé.';
    header('Location: ' . url_for('commercial/reporting_terrain/'));
    exit;
}

// Vérifier les droits d'accès (admin ou propriétaire)
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'ADMIN';
if (!$isAdmin && $reporting['user_id'] != $utilisateur['id']) {
    $_SESSION['flash_error'] = 'Vous n\'avez pas accès à ce reporting.';
    header('Location: ' . url_for('commercial/reporting_terrain/'));
    exit;
}

// Charger toutes les données
$stmtZones = $pdo->prepare("SELECT * FROM terrain_reporting_zones WHERE reporting_id = ? ORDER BY FIELD(jour, 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam')");
$stmtZones->execute([$id]);
$zones = $stmtZones->fetchAll(PDO::FETCH_ASSOC);
$zones_by_jour = [];
foreach ($zones as $z) $zones_by_jour[$z['jour']] = $z;

$stmtAct = $pdo->prepare("SELECT * FROM terrain_reporting_activite WHERE reporting_id = ? ORDER BY FIELD(jour, 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam')");
$stmtAct->execute([$id]);
$activites = $stmtAct->fetchAll(PDO::FETCH_ASSOC);
$activites_by_jour = [];
foreach ($activites as $a) $activites_by_jour[$a['jour']] = $a;

$stmtRes = $pdo->prepare("SELECT * FROM terrain_reporting_resultats WHERE reporting_id = ?");
$stmtRes->execute([$id]);
$resultats = $stmtRes->fetchAll(PDO::FETCH_ASSOC);
$resultats_by_ind = [];
foreach ($resultats as $r) $resultats_by_ind[$r['indicateur']] = $r;

$stmtObj = $pdo->prepare("SELECT * FROM terrain_reporting_objections WHERE reporting_id = ?");
$stmtObj->execute([$id]);
$objections = $stmtObj->fetchAll(PDO::FETCH_ASSOC);

$stmtArg = $pdo->prepare("SELECT * FROM terrain_reporting_arguments WHERE reporting_id = ?");
$stmtArg->execute([$id]);
$arguments = $stmtArg->fetchAll(PDO::FETCH_ASSOC);

$stmtPlan = $pdo->prepare("SELECT * FROM terrain_reporting_plan_action WHERE reporting_id = ? ORDER BY priorite");
$stmtPlan->execute([$id]);
$plan_actions = $stmtPlan->fetchAll(PDO::FETCH_ASSOC);

// Labels
$jours = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'];
$indicateurs_labels = [
    'visites_terrain' => 'Visites terrain',
    'contacts_qualifies' => 'Contacts qualifiés',
    'devis_emis' => 'Devis émis',
    'commandes_obtenues' => 'Commandes obtenues',
    'montant_commandes' => 'Montant commandes (FCFA)',
    'encaissements' => 'Encaissements (FCFA)'
];
$objections_labels = [
    'prix_eleve' => 'Prix jugé élevé',
    'qualite_pas_regardee' => 'Client final ne regarde pas qualité',
    'similaire_moins_cher' => 'Produits similaires moins chers',
    'pas_tresorerie' => 'Pas le bon moment / trésorerie',
    'decideur_absent' => 'Décideur absent',
    'autre' => 'Autre'
];
$arguments_labels = [
    'qualite_durabilite' => 'Qualité & durabilité',
    'marge_possible' => 'Marge possible',
    'echantillons_visibles' => 'Échantillons visibles',
    'stock_disponible' => 'Stock disponible',
    'autre' => 'Autre'
];

$semaine_debut_fmt = date('d/m/Y', strtotime($reporting['semaine_debut']));
$semaine_fin_fmt = date('d/m/Y', strtotime($reporting['semaine_fin']));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporting Terrain - <?= htmlspecialchars($reporting['commercial_nom']) ?> - <?= $semaine_debut_fmt ?></title>
    <style>
        /* Reset & base */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 11pt;
            line-height: 1.4;
            color: #333;
            background: #fff;
        }
        
        /* Page layout for A4 */
        @page {
            size: A4;
            margin: 15mm;
        }
        
        .page-container {
            max-width: 210mm;
            margin: 0 auto;
            padding: 15mm;
        }
        
        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 3px solid #0d6efd;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .header-left h1 {
            font-size: 16pt;
            color: #0d6efd;
            margin-bottom: 2px;
        }
        .header-left p {
            font-size: 9pt;
            color: #666;
        }
        .header-right {
            text-align: right;
            font-size: 9pt;
            color: #666;
        }
        .logo-text {
            font-weight: bold;
            font-size: 14pt;
            color: #0d6efd;
        }
        
        /* Section styling */
        .section {
            margin-bottom: 12px;
            page-break-inside: avoid;
        }
        .section-title {
            background: linear-gradient(135deg, #0d6efd 0%, #4a6cf7 100%);
            color: white;
            padding: 5px 10px;
            font-size: 10pt;
            font-weight: bold;
            border-radius: 3px;
            margin-bottom: 8px;
        }
        
        /* Tables */
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
        }
        th, td {
            border: 1px solid #dee2e6;
            padding: 4px 6px;
            text-align: left;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
        }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .fw-bold { font-weight: bold; }
        
        /* Data display */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            font-size: 9pt;
        }
        .info-item {
            padding: 5px;
            border-left: 3px solid #0d6efd;
            background: #f8f9fa;
        }
        .info-label {
            font-size: 8pt;
            color: #666;
            text-transform: uppercase;
        }
        .info-value {
            font-weight: 600;
        }
        
        /* Two columns */
        .two-cols {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        /* Lists */
        .item-list {
            font-size: 9pt;
        }
        .item-list .item {
            display: flex;
            align-items: flex-start;
            padding: 3px 0;
            border-bottom: 1px dotted #dee2e6;
        }
        .item-list .item:last-child {
            border-bottom: none;
        }
        .item-bullet {
            width: 16px;
            flex-shrink: 0;
        }
        .badge {
            display: inline-block;
            padding: 1px 6px;
            border-radius: 3px;
            font-size: 7pt;
            font-weight: 600;
            margin-left: 5px;
        }
        .badge-danger { background: #dc3545; color: white; }
        .badge-warning { background: #ffc107; color: #333; }
        .badge-secondary { background: #6c757d; color: white; }
        .badge-success { background: #198754; color: white; }
        .badge-info { background: #0dcaf0; color: #333; }
        
        /* Ecarts */
        .ecart-positif { color: #198754; font-weight: bold; }
        .ecart-negatif { color: #dc3545; font-weight: bold; }
        
        /* Synthesis */
        .synthese-box {
            background: #f8f9fa;
            padding: 10px;
            border-left: 4px solid #0d6efd;
            font-size: 9pt;
            white-space: pre-line;
        }
        
        /* Signature */
        .signature-box {
            text-align: center;
            margin-top: 20px;
            padding: 15px;
            border-top: 2px solid #dee2e6;
        }
        .signature-name {
            font-size: 14pt;
            font-weight: bold;
            margin-top: 5px;
        }
        
        /* Print specific */
        @media print {
            body {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .page-container {
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
        }
        
        /* Screen buttons */
        .print-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 25px;
            background: #0d6efd;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            z-index: 1000;
        }
        .print-btn:hover {
            background: #0b5ed7;
        }
        .back-btn {
            position: fixed;
            top: 20px;
            right: 140px;
            padding: 10px 25px;
            background: #6c757d;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            z-index: 1000;
        }
        .back-btn:hover {
            background: #5c636a;
            color: white;
        }
        
        /* Plan action cards */
        .plan-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }
        .plan-card {
            border: 1px solid #dee2e6;
            padding: 8px;
            border-radius: 4px;
            font-size: 9pt;
        }
        .plan-card-header {
            font-weight: bold;
            color: #0d6efd;
            border-bottom: 1px solid #eee;
            padding-bottom: 4px;
            margin-bottom: 4px;
        }
    </style>
</head>
<body>
    <a href="<?= url_for('commercial/reporting_terrain/show.php?id=' . $id) ?>" class="back-btn no-print">← Retour</a>
    <button onclick="window.print()" class="print-btn no-print">🖨️ Imprimer</button>

    <div class="page-container">
        <!-- EN-TÊTE -->
        <div class="header">
            <div class="header-left">
                <h1>📋 Reporting Hebdomadaire Terrain</h1>
                <p>Activité commerciale – Semaine du <?= $semaine_debut_fmt ?> au <?= $semaine_fin_fmt ?></p>
            </div>
            <div class="header-right">
                <div class="logo-text">KMS</div>
                <div>Kenne Multi-Services</div>
                <div>Imprimé le <?= date('d/m/Y à H:i') ?></div>
            </div>
        </div>

        <!-- 1. IDENTIFICATION -->
        <div class="section">
            <div class="section-title">1. IDENTIFICATION</div>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Commercial</div>
                    <div class="info-value"><?= htmlspecialchars($reporting['commercial_nom']) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Période</div>
                    <div class="info-value"><?= $semaine_debut_fmt ?> → <?= $semaine_fin_fmt ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Ville</div>
                    <div class="info-value"><?= htmlspecialchars($reporting['ville'] ?: '–') ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Responsable</div>
                    <div class="info-value"><?= htmlspecialchars($reporting['responsable_nom'] ?: '–') ?></div>
                </div>
            </div>
        </div>

        <!-- 2. ZONES & CIBLES -->
        <div class="section">
            <div class="section-title">2. ZONES & CIBLES COUVERTES</div>
            <table>
                <thead>
                    <tr>
                        <th style="width:50px;">Jour</th>
                        <th>Zone / Quartier</th>
                        <th style="width:100px;">Type cible</th>
                        <th style="width:70px;" class="text-center">Points</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total_points = 0;
                    foreach ($jours as $jour): 
                        $z = $zones_by_jour[$jour] ?? null;
                        $pts = $z ? intval($z['nb_points']) : 0;
                        $total_points += $pts;
                    ?>
                    <tr>
                        <td class="fw-bold"><?= $jour ?></td>
                        <td><?= htmlspecialchars($z['zone_quartier'] ?? '–') ?></td>
                        <td><?= htmlspecialchars($z['type_cible'] ?? '–') ?></td>
                        <td class="text-center"><?= $pts ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr style="background:#e7f1ff;">
                        <td colspan="3" class="fw-bold">Total points visités</td>
                        <td class="text-center fw-bold"><?= $total_points ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- 3. SUIVI JOURNALIER -->
        <div class="section">
            <div class="section-title">3. SUIVI JOURNALIER</div>
            <table>
                <thead>
                    <tr>
                        <th style="width:40px;">Jour</th>
                        <th class="text-center">Contacts</th>
                        <th class="text-center">Décideurs</th>
                        <th class="text-center">Échant.</th>
                        <th class="text-center">Grille</th>
                        <th class="text-center">RDV</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $totaux = ['contacts' => 0, 'decideurs' => 0, 'echant' => 0, 'grille' => 0, 'rdv' => 0];
                    foreach ($jours as $jour): 
                        $a = $activites_by_jour[$jour] ?? null;
                        if ($a) {
                            $totaux['contacts'] += intval($a['contacts_qualifies']);
                            $totaux['decideurs'] += intval($a['decideurs_rencontres']);
                            $totaux['echant'] += intval($a['echantillons_presentes']);
                            $totaux['grille'] += intval($a['grille_prix_remise']);
                            $totaux['rdv'] += intval($a['rdv_obtenus']);
                        }
                    ?>
                    <tr>
                        <td class="fw-bold"><?= $jour ?></td>
                        <td class="text-center"><?= $a ? intval($a['contacts_qualifies']) : 0 ?></td>
                        <td class="text-center"><?= $a ? intval($a['decideurs_rencontres']) : 0 ?></td>
                        <td class="text-center"><?= ($a && $a['echantillons_presentes']) ? '✓' : '–' ?></td>
                        <td class="text-center"><?= ($a && $a['grille_prix_remise']) ? '✓' : '–' ?></td>
                        <td class="text-center"><?= $a ? intval($a['rdv_obtenus']) : 0 ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr style="background:#e7f1ff;">
                        <td class="fw-bold">Total</td>
                        <td class="text-center fw-bold"><?= $totaux['contacts'] ?></td>
                        <td class="text-center fw-bold"><?= $totaux['decideurs'] ?></td>
                        <td class="text-center fw-bold"><?= $totaux['echant'] ?></td>
                        <td class="text-center fw-bold"><?= $totaux['grille'] ?></td>
                        <td class="text-center fw-bold"><?= $totaux['rdv'] ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- 4. RÉSULTATS COMMERCIAUX -->
        <div class="section">
            <div class="section-title">4. RÉSULTATS COMMERCIAUX SEMAINE</div>
            <table>
                <thead>
                    <tr>
                        <th>Indicateur</th>
                        <th class="text-end" style="width:90px;">Objectif</th>
                        <th class="text-end" style="width:90px;">Réalisé</th>
                        <th class="text-end" style="width:90px;">Écart</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($indicateurs_labels as $code => $label): 
                        $r = $resultats_by_ind[$code] ?? null;
                        $obj = $r ? floatval($r['objectif']) : 0;
                        $real = $r ? floatval($r['realise']) : 0;
                        $ecart = $real - $obj;
                        $ecart_class = $ecart >= 0 ? 'ecart-positif' : 'ecart-negatif';
                        $is_montant = in_array($code, ['montant_commandes', 'encaissements']);
                    ?>
                    <tr>
                        <td><?= $label ?></td>
                        <td class="text-end"><?= $is_montant ? number_format($obj, 0, ',', ' ') : $obj ?></td>
                        <td class="text-end"><?= $is_montant ? number_format($real, 0, ',', ' ') : $real ?></td>
                        <td class="text-end <?= $ecart_class ?>">
                            <?= ($ecart >= 0 ? '+' : '') . ($is_montant ? number_format($ecart, 0, ',', ' ') : $ecart) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- 5 & 6. OBJECTIONS & ARGUMENTS (deux colonnes) -->
        <div class="section">
            <div class="two-cols">
                <div>
                    <div class="section-title">5. OBJECTIONS RENCONTRÉES</div>
                    <?php if (empty($objections)): ?>
                        <p style="font-size:9pt;color:#666;">Aucune objection signalée.</p>
                    <?php else: ?>
                        <div class="item-list">
                            <?php foreach ($objections as $obj): 
                                $label = $objections_labels[$obj['objection_code']] ?? $obj['objection_code'];
                                $badge_class = $obj['frequence'] === 'Élevée' ? 'badge-danger' : 
                                              ($obj['frequence'] === 'Moyenne' ? 'badge-warning' : 'badge-secondary');
                            ?>
                            <div class="item">
                                <span class="item-bullet">⚠️</span>
                                <div>
                                    <?= htmlspecialchars($label) ?>
                                    <?php if ($obj['objection_code'] === 'autre' && $obj['autre_texte']): ?>
                                        : <?= htmlspecialchars($obj['autre_texte']) ?>
                                    <?php endif; ?>
                                    <span class="badge <?= $badge_class ?>"><?= $obj['frequence'] ?></span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div>
                    <div class="section-title">6. ARGUMENTS EFFICACES</div>
                    <?php if (empty($arguments)): ?>
                        <p style="font-size:9pt;color:#666;">Aucun argument signalé.</p>
                    <?php else: ?>
                        <div class="item-list">
                            <?php foreach ($arguments as $arg): 
                                $label = $arguments_labels[$arg['argument_code']] ?? $arg['argument_code'];
                                $badge_class = $arg['impact'] === 'Fort' ? 'badge-success' : 
                                              ($arg['impact'] === 'Moyen' ? 'badge-info' : 'badge-secondary');
                            ?>
                            <div class="item">
                                <span class="item-bullet">✅</span>
                                <div>
                                    <?= htmlspecialchars($label) ?>
                                    <?php if ($arg['argument_code'] === 'autre' && $arg['autre_texte']): ?>
                                        : <?= htmlspecialchars($arg['autre_texte']) ?>
                                    <?php endif; ?>
                                    <span class="badge <?= $badge_class ?>">Impact <?= $arg['impact'] ?></span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- 7. PLAN D'ACTION -->
        <div class="section">
            <div class="section-title">7. PLAN D'ACTION SEMAINE SUIVANTE</div>
            <?php if (empty($plan_actions)): ?>
                <p style="font-size:9pt;color:#666;">Aucun plan d'action défini.</p>
            <?php else: ?>
                <div class="plan-grid">
                    <?php foreach ($plan_actions as $pa): ?>
                    <div class="plan-card">
                        <div class="plan-card-header">Priorité <?= $pa['priorite'] ?></div>
                        <div><strong><?= htmlspecialchars($pa['action_concrete'] ?: '–') ?></strong></div>
                        <div>📍 <?= htmlspecialchars($pa['zone_cible'] ?: '–') ?></div>
                        <?php if ($pa['echeance']): ?>
                        <div>📅 <?= date('d/m/Y', strtotime($pa['echeance'])) ?></div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- 8. SYNTHÈSE -->
        <?php if ($reporting['synthese']): ?>
        <div class="section">
            <div class="section-title">8. SYNTHÈSE</div>
            <div class="synthese-box"><?= htmlspecialchars($reporting['synthese']) ?></div>
        </div>
        <?php endif; ?>

        <!-- SIGNATURE -->
        <?php if ($reporting['signature_nom']): ?>
        <div class="signature-box">
            <div style="font-size:9pt;color:#666;">Signé par</div>
            <div class="signature-name"><?= htmlspecialchars($reporting['signature_nom']) ?></div>
            <div style="font-size:9pt;color:#666;">Le <?= date('d/m/Y à H:i', strtotime($reporting['created_at'])) ?></div>
        </div>
        <?php endif; ?>

    </div>
</body>
</html>
