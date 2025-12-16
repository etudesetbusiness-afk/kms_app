<?php
// compta/export_bilan.php - Export du bilan comptable en vrai Excel XLSX
require_once __DIR__ . '/../security.php';
exigerConnexion();
exigerPermission('COMPTABILITE_LIRE');
require_once __DIR__ . '/../lib/export_xlsx.php';

global $pdo;

$exerciceId = isset($_GET['exercice_id']) ? (int)$_GET['exercice_id'] : 0;

// Récupérer l'exercice actif ou celui demandé
if ($exerciceId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM compta_exercices WHERE id = ?");
    $stmt->execute([$exerciceId]);
    $exercice = $stmt->fetch();
} else {
    $stmt = $pdo->query("SELECT * FROM compta_exercices WHERE est_actif = 1 LIMIT 1");
    $exercice = $stmt->fetch();
}

if (!$exercice) {
    die("Aucun exercice trouvé");
}

// Récupérer le bilan
$sql = "
    SELECT 
        cc.numero_compte,
        cc.libelle,
        cc.classe,
        COALESCE(SUM(CASE WHEN ce.debit > 0 THEN ce.debit ELSE 0 END), 0) as total_debit,
        COALESCE(SUM(CASE WHEN ce.credit > 0 THEN ce.credit ELSE 0 END), 0) as total_credit
    FROM compta_comptes cc
    LEFT JOIN compta_ecritures ce ON ce.compte_id = cc.id
    LEFT JOIN compta_pieces cp ON cp.id = ce.piece_id AND cp.est_validee = 1
    WHERE cc.classe IN ('1', '2', '3', '4', '5')
    GROUP BY cc.id, cc.numero_compte, cc.libelle, cc.classe
    HAVING (total_debit - total_credit) != 0
    ORDER BY cc.numero_compte
";
$stmt = $pdo->query($sql);
$comptes = $stmt->fetchAll();

// Organiser par classe
$actif = [];
$passif = [];

foreach ($comptes as $compte) {
    $solde = $compte['total_debit'] - $compte['total_credit'];
    $compte['solde'] = abs($solde);
    
    // Classe 1 = Capitaux propres (Passif)
    // Classe 2 = Immobilisations (Actif)
    // Classe 3 = Stocks (Actif)
    // Classe 4 = Tiers (Actif si débiteur, Passif si créditeur)
    // Classe 5 = Trésorerie (Actif si débiteur, Passif si créditeur)
    
    if ($compte['classe'] == '1') {
        $passif[] = $compte;
    } elseif (in_array($compte['classe'], ['2', '3'])) {
        $actif[] = $compte;
    } elseif (in_array($compte['classe'], ['4', '5'])) {
        if ($solde >= 0) {
            $actif[] = $compte;
        } else {
            $passif[] = $compte;
        }
    }
}

$totalActif = array_sum(array_column($actif, 'solde'));
$totalPassif = array_sum(array_column($passif, 'solde'));

// Préparer les données pour l'export XLSX
$data = [];

// En-têtes du rapport
$data[] = ['KENNE MULTI-SERVICES'];
$data[] = ['BILAN COMPTABLE'];
$data[] = ['Exercice: ' . $exercice['annee']];
$data[] = ['Du ' . date('d/m/Y', strtotime($exercice['date_debut'])) . ' au ' . date('d/m/Y', strtotime($exercice['date_fin']))];
$data[] = ['Date d\'export: ' . date('d/m/Y H:i')];
$data[] = [];

// Section ACTIF
$data[] = ['ACTIF'];
$data[] = ['N° Compte', 'Libellé', 'Classe', 'Montant (FCFA)'];

$classeLib = [
    '2' => 'IMMOBILISATIONS',
    '3' => 'STOCKS',
    '4' => 'CRÉANCES',
    '5' => 'TRÉSORERIE - ACTIF'
];

$currentClasse = '';
foreach ($actif as $compte) {
    if ($currentClasse != $compte['classe']) {
        $currentClasse = $compte['classe'];
        $data[] = ['CLASSE ' . $currentClasse . ' - ' . ($classeLib[$currentClasse] ?? '')];
    }
    $data[] = [$compte['numero_compte'], $compte['libelle'], $compte['classe'], $compte['solde']];
}

$data[] = ['TOTAL ACTIF', '', '', $totalActif];
$data[] = [];

// Section PASSIF
$data[] = ['PASSIF'];
$data[] = ['N° Compte', 'Libellé', 'Classe', 'Montant (FCFA)'];

$classeLib = [
    '1' => 'CAPITAUX PROPRES',
    '4' => 'DETTES',
    '5' => 'TRÉSORERIE - PASSIF'
];

$currentClasse = '';
foreach ($passif as $compte) {
    if ($currentClasse != $compte['classe']) {
        $currentClasse = $compte['classe'];
        $data[] = ['CLASSE ' . $currentClasse . ' - ' . ($classeLib[$currentClasse] ?? '')];
    }
    $data[] = [$compte['numero_compte'], $compte['libelle'], $compte['classe'], $compte['solde']];
}

$data[] = ['TOTAL PASSIF', '', '', $totalPassif];
$data[] = [];

// Équilibre du bilan
$ecart = abs($totalActif - $totalPassif);
$data[] = ['ÉQUILIBRE DU BILAN'];
$data[] = ['Écart (FCFA)', $ecart];
$data[] = ['Statut', $ecart < 1 ? 'Bilan équilibré' : 'Bilan non équilibré'];

// Générer le fichier XLSX
$filename = 'bilan_comptable_' . $exercice['annee'] . '.xlsx';
ExportXLSX::generate($data, [], $filename, 'Bilan Comptable');
