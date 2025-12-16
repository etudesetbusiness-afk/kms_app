<?php
// caisse/export_excel.php - Export du journal de caisse en vrai Excel XLSX
require_once __DIR__ . '/../security.php';
exigerConnexion();
exigerPermission('CAISSE_LIRE');
require_once __DIR__ . '/../lib/export_xlsx.php';

// Garantir que $pdo est disponible
global $pdo;
if (!isset($pdo)) {
    require_once __DIR__ . '/../db/db.php';
}

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

// Préparer les données pour l'export
$data = [];
foreach ($operations as $op) {
    $data[] = [
        date('d/m/Y H:i', strtotime($op['date_operation'])),
        $op['type_operation'],
        $op['reference'] ?? '',
        $op['libelle'],
        $op['client_nom'] ?? '',
        $op['mode_paiement'] ?? '',
        $op['montant'],
        $op['caissier'] ?? ''
    ];
}

// Ajouter les lignes de totaux
$data[] = ['', '', '', '', '', 'Total Encaissements:', $totalEncaissements, ''];
$data[] = ['', '', '', '', '', 'Total Décaissements:', $totalDecaissements, ''];
$data[] = ['', '', '', '', '', 'SOLDE NET:', $solde, ''];

// En-têtes
$headers = [
    'Date',
    'Type',
    'Référence',
    'Libellé',
    'Client',
    'Mode paiement',
    'Montant (FCFA)',
    'Caissier'
];

// Générer le fichier XLSX
$filename = 'journal_caisse_' . $dateDebut . '_' . $dateFin . '.xlsx';
ExportXLSX::generate($data, $headers, $filename, 'Journal de Caisse');


