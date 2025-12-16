<?php
/**
 * Export Ventes en Excel XLSX
 * GET params: date_debut, date_fin, statut, client_id
 */

require_once __DIR__ . '/../security.php';
exigerConnexion();
exigerPermission('VENTES_LIRE');
require_once __DIR__ . '/../lib/export_xlsx.php';

// Garantir que $pdo est disponible
global $pdo;
if (!isset($pdo)) {
    require_once __DIR__ . '/../db/db.php';
}

$dateDebut  = $_GET['date_debut'] ?? date('Y-m-01');
$dateFin    = $_GET['date_fin'] ?? date('Y-m-d');
$statut     = $_GET['statut'] ?? '';
$clientId   = isset($_GET['client_id']) ? (int)$_GET['client_id'] : 0;

// Requête
$where = "WHERE v.date_vente >= :date_debut AND v.date_vente <= :date_fin";
$params = [':date_debut' => $dateDebut, ':date_fin' => $dateFin];

if ($statut !== '' && in_array($statut, ['DEVIS', 'CONFIRMEE', 'PARTIELLEMENT_LIVREE', 'LIVREE', 'FACTUREE', 'ANNULEE'], true)) {
    $where .= " AND v.statut = :statut";
    $params[':statut'] = $statut;
}

if ($clientId > 0) {
    $where .= " AND v.client_id = :client_id";
    $params[':client_id'] = $clientId;
}

$sql = "
    SELECT 
        v.id,
        v.numero,
        v.date_vente,
        c.nom as client_nom,
        v.statut,
        v.montant_total_ttc,
        v.statut_encaissement,
        COUNT(bl.id) as nb_bl,
        MAX(bl.date_bl) as derniere_livraison
    FROM ventes v
    JOIN clients c ON c.id = v.client_id
    LEFT JOIN bons_livraison bl ON bl.vente_id = v.id
    $where
    GROUP BY v.id
    ORDER BY v.date_vente DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$ventes = $stmt->fetchAll();

// Préparer les données pour l'export
$data = [];

// En-tête du rapport
$data[] = ['RAPPORT DE VENTES'];
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
$data[] = ['N° Vente', 'Date', 'Client', 'Montant TTC (FCFA)', 'Statut', 'Encaissement', 'BL', 'Dernière Livraison'];

// Données des ventes
foreach ($ventes as $v) {
    $data[] = [
        $v['numero'],
        date('d/m/Y', strtotime($v['date_vente'])),
        $v['client_nom'],
        $v['montant_total_ttc'],
        $v['statut'],
        $v['statut_encaissement'],
        $v['nb_bl'],
        $v['derniere_livraison'] ? date('d/m/Y', strtotime($v['derniere_livraison'])) : '-'
    ];
}

// Générer le fichier XLSX
$filename = 'Ventes_' . date('Y-m-d') . '.xlsx';
ExportXLSX::generate($data, [], $filename, 'Ventes');

