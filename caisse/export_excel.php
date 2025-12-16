<?php
// caisse/export_excel.php - Export du journal de caisse en Excel
require_once __DIR__ . '/../security.php';
exigerConnexion();
exigerPermission('CAISSE_LIRE');

global $pdo;

$dateDebut = $_GET['date_debut'] ?? date('Y-m-d');
$dateFin = $_GET['date_fin'] ?? date('Y-m-d');

// Récupérer les données
$sql = "
    SELECT 
        jc.date_operation,
        jc.type_operation,
        jc.reference,
        jc.libelle,
        jc.montant,
        mp.libelle as mode_paiement,
        u.nom_complet as caissier,
        c.nom as client_nom
    FROM journal_caisse jc
    LEFT JOIN modes_paiement mp ON jc.mode_paiement_id = mp.id
    LEFT JOIN utilisateurs u ON jc.utilisateur_id = u.id
    LEFT JOIN clients c ON jc.client_id = c.id
    WHERE jc.date_operation BETWEEN :date_debut AND :date_fin
    ORDER BY jc.date_operation, jc.id
";
$stmt = $pdo->prepare($sql);
$stmt->execute([
    'date_debut' => $dateDebut,
    'date_fin' => $dateFin
]);
$operations = $stmt->fetchAll();

// Calculs
$totalEncaissements = 0;
$totalDecaissements = 0;
foreach ($operations as $op) {
    if ($op['type_operation'] === 'ENCAISSEMENT') {
        $totalEncaissements += $op['montant'];
    } else {
        $totalDecaissements += $op['montant'];
    }
}
$solde = $totalEncaissements - $totalDecaissements;

// Headers pour téléchargement Excel - Utiliser le format CSV compatible Excel moderne
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="journal_caisse_' . $dateDebut . '_' . $dateFin . '.csv"');
header('Pragma: no-cache');
header('Expires: 0');
header('Cache-Control: no-cache, no-store, must-revalidate');

// BOM UTF-8 pour meilleure compatibilité Excel
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');

// En-tête
fputcsv($output, ['KENNE MULTI-SERVICES - JOURNAL DE CAISSE'], ';');
fputcsv($output, ['Période : ' . date('d/m/Y', strtotime($dateDebut)) . ' au ' . date('d/m/Y', strtotime($dateFin))], ';');
fputcsv($output, ['Édité le : ' . date('d/m/Y H:i')], ';');
fputcsv($output, [], ';');

// Colonnes
fputcsv($output, [
    'Date',
    'Type',
    'Référence',
    'Libellé',
    'Client',
    'Mode paiement',
    'Montant (FCFA)',
    'Caissier'
], ';');

// Données
foreach ($operations as $op) {
    fputcsv($output, [
        date('d/m/Y H:i', strtotime($op['date_operation'])),
        htmlspecialchars($op['type_operation']),
        htmlspecialchars($op['reference'] ?? ''),
        htmlspecialchars($op['libelle']),
        htmlspecialchars($op['client_nom'] ?? ''),
        htmlspecialchars($op['mode_paiement'] ?? ''),
        number_format($op['montant'], 0, ',', ' '),
        htmlspecialchars($op['caissier'] ?? '')
    ], ';');
}

// Totaux
fputcsv($output, [], ';');
fputcsv($output, ['', '', '', '', '', 'Total Encaissements:', number_format($totalEncaissements, 0, ',', ' ')], ';');
fputcsv($output, ['', '', '', '', '', 'Total Décaissements:', number_format($totalDecaissements, 0, ',', ' ')], ';');
fputcsv($output, ['', '', '', '', '', 'SOLDE NET:', number_format($solde, 0, ',', ' ')], ';');

fclose($output);
exit;

