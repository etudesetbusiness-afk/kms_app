<?php
/**
 * Export Litiges & Retours en Excel XLSX
 * GET params: date_debut, date_fin, statut, type
 */

require_once __DIR__ . '/../security.php';
exigerConnexion();
exigerPermission('VENTES_LIRE');
require_once __DIR__ . '/../lib/export_xlsx.php';

global $pdo;

$dateDebut  = $_GET['date_debut'] ?? date('Y-m-01');
$dateFin    = $_GET['date_fin'] ?? date('Y-m-d');
$statut     = $_GET['statut'] ?? '';
$type       = $_GET['type'] ?? '';

// Requête
$where = "WHERE rl.date_retour >= :date_debut AND rl.date_retour <= :date_fin";
$params = [':date_debut' => $dateDebut, ':date_fin' => $dateFin];

if ($statut !== '' && in_array($statut, ['EN_COURS', 'RESOLU', 'ABANDONNE'], true)) {
    $where .= " AND rl.statut_traitement = :statut";
    $params[':statut'] = $statut;
}

if ($type !== '') {
    $where .= " AND rl.type_probleme = :type";
    $params[':type'] = $type;
}

$sql = "
    SELECT 
        rl.id,
        rl.date_retour,
        c.nom as client_nom,
        p.code_produit,
        p.designation as produit_nom,
        rl.type_probleme,
        rl.statut_traitement,
        rl.montant_rembourse,
        rl.montant_avoir,
        v.numero as vente_numero
    FROM retours_litiges rl
    JOIN clients c ON c.id = rl.client_id
    JOIN produits p ON p.id = rl.produit_id
    LEFT JOIN ventes v ON v.id = rl.vente_id
    $where
    ORDER BY rl.date_retour DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$litiges = $stmt->fetchAll();

// Préparer les données pour l'export
$data = [];

// En-tête du rapport
$data[] = ['RAPPORT DE LITIGES ET RETOURS'];
$data[] = ['Période: ' . date('d/m/Y', strtotime($dateDebut)) . ' au ' . date('d/m/Y', strtotime($dateFin))];
if ($statut) $data[] = ['Statut: ' . $statut];
if ($type) $data[] = ['Type: ' . $type];
$data[] = ['Date d\'export: ' . date('d/m/Y H:i')];
$data[] = [];

// En-têtes du tableau
$data[] = ['Date', 'Client', 'Vente', 'Produit', 'Type', 'Statut', 'Remboursement', 'Avoir', 'Total Impact'];

// Données des litiges
foreach ($litiges as $l) {
    $totalImpact = ($l['montant_rembourse'] ?? 0) + ($l['montant_avoir'] ?? 0);
    $data[] = [
        date('d/m/Y', strtotime($l['date_retour'])),
        $l['client_nom'],
        $l['vente_numero'] ?: '-',
        $l['code_produit'] . ' - ' . $l['produit_nom'],
        $l['type_probleme'],
        $l['statut_traitement'],
        $l['montant_rembourse'] ?? 0,
        $l['montant_avoir'] ?? 0,
        $totalImpact
    ];
}

// Générer le fichier XLSX
$filename = 'Litiges_' . date('Y-m-d') . '.xlsx';
ExportXLSX::generate($data, [], $filename, 'Litiges et Retours');

