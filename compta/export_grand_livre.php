<?php
// compta/export_grand_livre.php - Export du grand livre en XLSX
require_once __DIR__ . '/../security.php';
exigerConnexion();
exigerPermission('COMPTABILITE_LIRE');
require_once __DIR__ . '/../lib/export_xlsx.php';

// Garantir que $pdo est disponible
global $pdo;
if (!isset($pdo)) {
    require_once __DIR__ . '/../db/db.php';
}

$compte_id = (int)($_GET['compte_id'] ?? 0);
$exercice_id = (int)($_GET['exercice_id'] ?? 0);

if (!$compte_id) {
    die('Compte non spécifié');
}

// Récupérer le compte
$stmt = $pdo->prepare("SELECT * FROM compta_comptes WHERE id = ?");
$stmt->execute([$compte_id]);
$compte = $stmt->fetch();

if (!$compte) {
    die('Compte introuvable');
}

// Récupérer l'exercice
// Colonnes: id, annee, date_ouverture, date_cloture, est_clos
if ($exercice_id) {
    $stmt = $pdo->prepare("SELECT id, annee, date_ouverture, date_cloture, est_clos FROM compta_exercices WHERE id = ?");
    $stmt->execute([$exercice_id]);
    $exercice = $stmt->fetch();
} else {
    $stmt = $pdo->query("SELECT id, annee, date_ouverture, date_cloture, est_clos FROM compta_exercices WHERE est_clos = 0 LIMIT 1");
    $exercice = $stmt->fetch();
    $exercice_id = $exercice['id'] ?? 0;
}

// Récupérer les écritures
$sql = "
    SELECT 
        p.date_piece,
        p.numero_piece,
        j.code as journal_code,
        j.libelle as journal_libelle,
        e.libelle,
        e.debit,
        e.credit
    FROM compta_ecritures e
    JOIN compta_pieces p ON e.piece_id = p.id
    JOIN compta_journaux j ON p.journal_id = j.id
    WHERE e.compte_id = ? AND p.exercice_id = ? AND p.est_validee = 1
    ORDER BY p.date_piece, p.numero_piece
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$compte_id, $exercice_id]);
$ecritures = $stmt->fetchAll();

// Calculer solde
$solde = 0;
$total_debit = 0;
$total_credit = 0;

foreach ($ecritures as $ecriture) {
    $total_debit += $ecriture['debit'];
    $total_credit += $ecriture['credit'];
}

$solde = $total_debit - $total_credit;

// Préparer les données pour l'export XLSX
$data = [];

// En-têtes du rapport
$data[] = ['GRAND LIVRE - KENNE MULTI-SERVICES'];
$data[] = ['Compte: ' . $compte['numero'] . ' - ' . $compte['libelle']];
$data[] = ['Exercice: ' . ($exercice['annee'] ?? 'N/A')];
$data[] = ['Édité le ' . date('d/m/Y H:i')];
$data[] = [];

// En-têtes des colonnes
$data[] = ['Date', 'N° Pièce', 'Journal', 'Libellé', 'Débit', 'Crédit', 'Solde'];

// Écritures
$solde_cumule = 0;
foreach ($ecritures as $ecriture) {
    $solde_cumule += $ecriture['debit'] - $ecriture['credit'];
    
    $data[] = [
        date('d/m/Y', strtotime($ecriture['date_piece'])),
        $ecriture['numero_piece'],
        $ecriture['journal_code'],
        $ecriture['libelle'],
        $ecriture['debit'] > 0 ? $ecriture['debit'] : '',
        $ecriture['credit'] > 0 ? $ecriture['credit'] : '',
        $solde_cumule
    ];
}

// Totaux
$data[] = [];
$data[] = [
    '',
    '',
    '',
    'TOTAUX',
    $total_debit,
    $total_credit,
    $solde
];

// Solde final
$data[] = [];
$data[] = [
    'Solde: ' . $solde . ' FCFA (' . ($solde >= 0 ? 'Débiteur' : 'Créditeur') . ')'
];

// Générer le fichier XLSX
$filename = 'grand_livre_' . $compte['numero'] . '_' . date('Y-m-d_His') . '.xlsx';
ExportXLSX::generate($data, [], $filename, 'Grand Livre');

