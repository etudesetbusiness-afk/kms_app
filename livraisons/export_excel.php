<?php
/**
 * Export Bons de Livraison en Excel XLSX
 * GET params: date_debut, date_fin, statut, client_id, signe
 */

require_once __DIR__ . '/../security.php';
exigerConnexion();
exigerPermission('VENTES_LIRE');
require_once __DIR__ . '/../lib/export_xlsx.php';

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

// Préparer les données pour l'export
$data = [];

// En-tête du rapport
$data[] = ['RAPPORT DE BONS DE LIVRAISON'];
$data[] = ['Période: ' . date('d/m/Y', strtotime($dateDebut)) . ' au ' . date('d/m/Y', strtotime($dateFin))];
if ($statut) $data[] = ['Statut: ' . $statut];
if ($clientId > 0) {
    $stmtClient = $pdo->prepare("SELECT nom FROM clients WHERE id = ?");
    $stmtClient->execute([$clientId]);
    $client = $stmtClient->fetch();
    if ($client) $data[] = ['Client: ' . $client['nom']];
}
$data[] = ['Date d\'export: ' . date('d/m/Y H:i')];
$data[] = [];

// En-têtes du tableau
$data[] = ['N° BL', 'Date', 'Client', 'Vente', 'Statut', 'Signé', 'Transport', 'Articles'];

// Données des bons de livraison
foreach ($bons as $b) {
    $data[] = [
        $b['numero'],
        date('d/m/Y', strtotime($b['date_bl'])),
        $b['client_nom'],
        $b['vente_numero'] ?: '-',
        $b['statut'],
        $b['signe_client'] ? 'OUI' : 'NON',
        $b['transport_assure_par'] ?: '-',
        $b['nb_lignes']
    ];
}

// Générer le fichier XLSX
$filename = 'BonsLivraison_' . date('Y-m-d') . '.xlsx';
ExportXLSX::generate($data, [], $filename, 'Bons de Livraison');

fclose($output);
exit;
