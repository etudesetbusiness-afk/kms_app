<?php
/**
 * Export Bons de Livraison en Excel
 * GET params: date_debut, date_fin, statut, client_id
 */

require_once __DIR__ . '/../security.php';
exigerConnexion();
exigerPermission('VENTES_LIRE');

global $pdo;

$dateDebut  = $_GET['date_debut'] ?? date('Y-m-01');
$dateFin    = $_GET['date_fin'] ?? date('Y-m-d');
$statut     = $_GET['statut'] ?? '';
$clientId   = isset($_GET['client_id']) ? (int)$_GET['client_id'] : 0;
$signe      = $_GET['signe'] ?? '';

// Requête
$where = "WHERE b.date_bl >= :date_debut AND b.date_bl <= :date_fin";
$params = [':date_debut' => $dateDebut, ':date_fin' => $dateFin];

if ($statut !== '' && in_array($statut, ['EN_PREPARATION', 'PRET', 'EN_COURS_LIVRAISON', 'LIVRE', 'ANNULE'], true)) {
    $where .= " AND b.statut = :statut";
    $params[':statut'] = $statut;
}

if ($clientId > 0) {
    $where .= " AND b.client_id = :client_id";
    $params[':client_id'] = $clientId;
}

if ($signe !== '' && in_array($signe, ['0', '1'], true)) {
    $where .= " AND b.signe_client = :signe";
    $params[':signe'] = (int)$signe;
}

$sql = "
    SELECT 
        b.numero,
        b.date_bl,
        c.nom as client_nom,
        b.statut,
        b.signe_client,
        b.transport_assure_par,
        v.numero as vente_numero,
        COUNT(bll.id) as nb_lignes
    FROM bons_livraison b
    JOIN clients c ON c.id = b.client_id
    LEFT JOIN ventes v ON v.id = b.vente_id
    LEFT JOIN bons_livraison_lignes bll ON bll.bon_livraison_id = b.id
    $where
    GROUP BY b.id
    ORDER BY b.date_bl DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bons = $stmt->fetchAll();

// Header Excel
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="BonsLivraison_' . date('Y-m-d') . '.xlsx"');
header('Cache-Control: no-cache, no-store, must-revalidate');

$output = fopen('php://output', 'w');
fputcsv($output, ['N° BL', 'Date', 'Client', 'Vente', 'Statut', 'Signé', 'Transport', 'Articles'], ';');

foreach ($bons as $b) {
    fputcsv($output, [
        $b['numero'],
        date('d/m/Y', strtotime($b['date_bl'])),
        $b['client_nom'],
        $b['vente_numero'] ?: '-',
        $b['statut'],
        $b['signe_client'] ? 'OUI' : 'NON',
        $b['transport_assure_par'] ?: '-',
        $b['nb_lignes']
    ], ';');
}

fclose($output);
exit;
