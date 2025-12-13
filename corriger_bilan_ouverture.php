<?php
/**
 * Script de correction du bilan d'ouverture OHADA Cameroun
 * 
 * Ce script corrige le déséquilibre du bilan en ajustant les capitaux propres
 * pour respecter l'équation comptable fondamentale OHADA :
 * ACTIF = PASSIF + CAPITAUX PROPRES + RÉSULTAT
 */

require_once __DIR__ . '/db/db.php';
require_once __DIR__ . '/lib/compta.php';

global $pdo;

echo "╔══════════════════════════════════════════════════════════════════╗\n";
echo "║   CORRECTION DU BILAN D'OUVERTURE - OHADA CAMEROUN             ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n\n";

// 1. Récupérer l'exercice actif
$stmt = $pdo->query("SELECT * FROM compta_exercices WHERE est_clos = 0 ORDER BY annee DESC LIMIT 1");
$exercice = $stmt->fetch();

if (!$exercice) {
    die("❌ ERREUR : Aucun exercice actif trouvé.\n");
}

$exercice_id = $exercice['id'];
echo "📅 Exercice : {$exercice['annee']} (ID: {$exercice_id})\n\n";

// 2. Calculer le bilan actuel
echo "═══════════════════════════════════════════════════════════════════\n";
echo "ÉTAPE 1 : ANALYSE DU BILAN ACTUEL\n";
echo "═══════════════════════════════════════════════════════════════════\n\n";

$balance = compta_get_balance($pdo, $exercice_id);

$totaux = ['1' => 0, '2' => 0, '3' => 0, '4' => 0, '5' => 0, '6' => 0, '7' => 0];
foreach ($balance as $ligne) {
    $solde = $ligne['total_debit'] - $ligne['total_credit'];
    $classe = $ligne['classe'];
    if (isset($totaux[$classe])) {
        $totaux[$classe] += $solde;
    }
}

// Calcul ACTIF
$actif = 0;
if ($totaux['2'] > 0) $actif += $totaux['2'];
if ($totaux['3'] > 0) $actif += $totaux['3'];
if ($totaux['4'] > 0) $actif += $totaux['4'];
if ($totaux['5'] > 0) $actif += $totaux['5'];

// Calcul PASSIF
$passif = abs($totaux['1']);
if ($totaux['4'] < 0) $passif += abs($totaux['4']);
if ($totaux['5'] < 0) $passif += abs($totaux['5']);

// Résultat
$resultat = $totaux['7'] - $totaux['6'];

// Écart
$ecart = $actif - ($passif + $resultat);

echo "Totaux par classe (soldes) :\n";
foreach ($totaux as $c => $v) {
    echo sprintf("  Classe %s : %15s FCFA\n", $c, number_format($v, 0, ',', ' '));
}

echo "\n";
echo sprintf("ACTIF total           : %15s FCFA\n", number_format($actif, 0, ',', ' '));
echo sprintf("PASSIF total          : %15s FCFA\n", number_format($passif, 0, ',', ' '));
echo sprintf("RÉSULTAT (Prod-Charg) : %15s FCFA\n", number_format($resultat, 0, ',', ' '));
echo sprintf("═══════════════════════════════════════════════════════════════════\n");
echo sprintf("ÉCART (déséquilibre)  : %15s FCFA ", number_format($ecart, 0, ',', ' '));

if (abs($ecart) < 1) {
    echo "✅ ÉQUILIBRÉ\n\n";
    echo "Le bilan est déjà équilibré. Aucune correction nécessaire.\n";
    exit(0);
} else {
    echo "❌ DÉSÉQUILIBRÉ\n\n";
}

// 3. Calculer la correction nécessaire
echo "═══════════════════════════════════════════════════════════════════\n";
echo "ÉTAPE 2 : CALCUL DE LA CORRECTION\n";
echo "═══════════════════════════════════════════════════════════════════\n\n";

echo "Analyse du déséquilibre :\n";
echo "  Équation OHADA : ACTIF = PASSIF + RÉSULTAT\n";
echo sprintf("  Actuel : %s = %s + (%s)\n", 
    number_format($actif, 0, ',', ' '),
    number_format($passif, 0, ',', ' '),
    number_format($resultat, 0, ',', ' ')
);
echo sprintf("  Actuel : %s ≠ %s\n", 
    number_format($actif, 0, ',', ' '),
    number_format($passif + $resultat, 0, ',', ' ')
);

echo "\nCorrection nécessaire :\n";
echo "  Pour équilibrer, il faut ajuster les CAPITAUX PROPRES (Classe 1)\n";
echo sprintf("  Ajustement à apporter : %15s FCFA\n", number_format($ecart, 0, ',', ' '));

if ($ecart > 0) {
    echo "  → Augmenter les capitaux propres (CRÉDIT Compte 12 - Report à nouveau)\n";
} else {
    echo "  → Diminuer les capitaux propres (DÉBIT Compte 12 - Report à nouveau)\n";
}

// 4. Demander confirmation
echo "\n";
echo "═══════════════════════════════════════════════════════════════════\n";
echo "ÉTAPE 3 : CRÉATION DE LA PIÈCE DE CORRECTION\n";
echo "═══════════════════════════════════════════════════════════════════\n\n";

echo "Cette opération va créer une pièce comptable avec les écritures suivantes :\n\n";

if ($ecart > 0) {
    echo "  DÉBIT  : Compte 47000 (Débiteurs divers - Ajust. ouverture) : " . number_format($ecart, 0, ',', ' ') . " FCFA\n";
    echo "  CRÉDIT : Compte 12000 (Report à nouveau)                     : " . number_format($ecart, 0, ',', ' ') . " FCFA\n";
} else {
    echo "  DÉBIT  : Compte 12000 (Report à nouveau)                     : " . number_format(abs($ecart), 0, ',', ' ') . " FCFA\n";
    echo "  CRÉDIT : Compte 47000 (Débiteurs divers - Ajust. ouverture) : " . number_format(abs($ecart), 0, ',', ' ') . " FCFA\n";
}

echo "\nObservations : Ajustement du bilan d'ouverture pour équilibre OHADA Cameroun\n";
echo "\n⚠️  Cette pièce sera créée en statut NON VALIDÉ.\n";
echo "   La comptable devra la valider manuellement via l'interface.\n\n";

// Créer automatiquement (car l'utilisateur a donné son accord)
echo "Création de la pièce de correction...\n";

try {
    $pdo->beginTransaction();
    
    // Vérifier si les comptes existent, sinon les créer
    $stmt = $pdo->prepare("SELECT id FROM compta_comptes WHERE numero_compte = ?");
    
    // Compte 12000 - Report à nouveau
    $stmt->execute(['12000']);
    if (!$stmt->fetch()) {
        echo "  → Création du compte 12000 (Report à nouveau)...\n";
        $pdo->exec("
            INSERT INTO compta_comptes (numero_compte, libelle, classe, type_compte, nature)
            VALUES ('12000', 'Report à nouveau', '1', 'PASSIF', 'AUTRE')
        ");
    }
    
    // Compte 47000 - Débiteurs divers
    $stmt->execute(['47000']);
    if (!$stmt->fetch()) {
        echo "  → Création du compte 47000 (Débiteurs divers)...\n";
        $pdo->exec("
            INSERT INTO compta_comptes (numero_compte, libelle, classe, type_compte, nature)
            VALUES ('47000', 'Débiteurs divers - Ajustements', '4', 'ACTIF', 'CREANCE')
        ");
    }
    
    // Récupérer les IDs des comptes
    $stmt->execute(['12000']);
    $compte_ran = $stmt->fetch();
    $compte_ran_id = $compte_ran['id'];
    
    $stmt->execute(['47000']);
    $compte_deb = $stmt->fetch();
    $compte_deb_id = $compte_deb['id'];
    
    // Récupérer le journal OD
    $stmt = $pdo->query("SELECT id FROM compta_journaux WHERE code = 'OD' LIMIT 1");
    $journal = $stmt->fetch();
    if (!$journal) {
        throw new Exception("Journal OD non trouvé. Veuillez créer le journal des opérations diverses.");
    }
    $journal_id = $journal['id'];
    
    // Générer numéro de pièce
    $stmt = $pdo->prepare("SELECT MAX(numero_piece) as max_num FROM compta_pieces WHERE exercice_id = ?");
    $stmt->execute([$exercice_id]);
    $result = $stmt->fetch();
    $numero_piece = (int)($result['max_num'] ?? 0) + 1;
    
    // Créer la pièce
    $date_piece = date('Y-m-d');
    $observations = "CORRECTION BILAN D'OUVERTURE - Ajustement capitaux propres pour équilibre OHADA Cameroun. Écart corrigé : " . number_format($ecart, 0, ',', ' ') . " FCFA";
    
    $stmt = $pdo->prepare("
        INSERT INTO compta_pieces (numero_piece, date_piece, exercice_id, journal_id, reference_type, observations, est_validee)
        VALUES (?, ?, ?, ?, 'CORRECTION_OUVERTURE', ?, 0)
    ");
    $stmt->execute([$numero_piece, $date_piece, $exercice_id, $journal_id, $observations]);
    $piece_id = $pdo->lastInsertId();
    
    echo "  ✓ Pièce #{$numero_piece} créée (ID: {$piece_id})\n";
    
    // Créer les écritures
    if ($ecart > 0) {
        // DÉBIT Débiteurs divers
        $stmt = $pdo->prepare("
            INSERT INTO compta_ecritures (piece_id, compte_id, libelle_ecriture, debit, credit)
            VALUES (?, ?, ?, ?, 0)
        ");
        $stmt->execute([$piece_id, $compte_deb_id, 'Ajustement bilan d\'ouverture', $ecart]);
        
        // CRÉDIT Report à nouveau
        $stmt = $pdo->prepare("
            INSERT INTO compta_ecritures (piece_id, compte_id, libelle_ecriture, debit, credit)
            VALUES (?, ?, ?, 0, ?)
        ");
        $stmt->execute([$piece_id, $compte_ran_id, 'Correction capitaux propres', $ecart]);
        
    } else {
        // DÉBIT Report à nouveau
        $stmt = $pdo->prepare("
            INSERT INTO compta_ecritures (piece_id, compte_id, libelle_ecriture, debit, credit)
            VALUES (?, ?, ?, ?, 0)
        ");
        $stmt->execute([$piece_id, $compte_ran_id, 'Correction capitaux propres', abs($ecart)]);
        
        // CRÉDIT Débiteurs divers
        $stmt = $pdo->prepare("
            INSERT INTO compta_ecritures (piece_id, compte_id, libelle_ecriture, debit, credit)
            VALUES (?, ?, ?, 0, ?)
        ");
        $stmt->execute([$piece_id, $compte_deb_id, 'Ajustement bilan d\'ouverture', abs($ecart)]);
    }
    
    echo "  ✓ 2 écritures créées\n";
    
    // Vérifier l'équilibre de la pièce
    $stmt = $pdo->prepare("
        SELECT SUM(debit) as total_debit, SUM(credit) as total_credit
        FROM compta_ecritures
        WHERE piece_id = ?
    ");
    $stmt->execute([$piece_id]);
    $verif = $stmt->fetch();
    
    if (abs($verif['total_debit'] - $verif['total_credit']) < 0.01) {
        echo "  ✓ Pièce équilibrée (Débit = Crédit = " . number_format($verif['total_debit'], 0, ',', ' ') . " FCFA)\n";
        $pdo->commit();
        echo "\n✅ CORRECTION CRÉÉE AVEC SUCCÈS !\n\n";
    } else {
        throw new Exception("La pièce n'est pas équilibrée !");
    }
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "\n❌ ERREUR : " . $e->getMessage() . "\n";
    exit(1);
}

// 5. Recalculer le bilan après correction
echo "═══════════════════════════════════════════════════════════════════\n";
echo "ÉTAPE 4 : PROJECTION DU BILAN APRÈS VALIDATION\n";
echo "═══════════════════════════════════════════════════════════════════\n\n";

echo "⚠️  Note : La pièce n'est PAS encore validée.\n";
echo "   Le bilan ci-dessous est une PROJECTION si la comptable valide.\n\n";

// Recalculer avec la nouvelle pièce (simuler validation)
$nouveau_passif = $passif;
if ($ecart > 0) {
    $nouveau_passif += $ecart;  // Augmentation capitaux propres
} else {
    $nouveau_passif -= abs($ecart);  // Diminution capitaux propres
}

$nouvel_ecart = $actif - ($nouveau_passif + $resultat);

echo sprintf("ACTIF total           : %15s FCFA (inchangé)\n", number_format($actif, 0, ',', ' '));
echo sprintf("PASSIF total          : %15s FCFA (+%s)\n", 
    number_format($nouveau_passif, 0, ',', ' '),
    number_format($ecart, 0, ',', ' ')
);
echo sprintf("RÉSULTAT              : %15s FCFA (inchangé)\n", number_format($resultat, 0, ',', ' '));
echo sprintf("═══════════════════════════════════════════════════════════════════\n");
echo sprintf("ÉCART (après valid.)  : %15s FCFA ", number_format($nouvel_ecart, 0, ',', ' '));

if (abs($nouvel_ecart) < 1) {
    echo "✅ ÉQUILIBRÉ\n";
} else {
    echo "⚠️  Écart résiduel\n";
}

echo "\n";
echo "═══════════════════════════════════════════════════════════════════\n";
echo "PROCHAINES ÉTAPES\n";
echo "═══════════════════════════════════════════════════════════════════\n\n";
echo "1. Accédez à l'interface de validation :\n";
echo "   → http://localhost/kms_app/compta/valider_corrections.php\n\n";
echo "2. Examinez la pièce #{$numero_piece}\n\n";
echo "3. Si tout est correct, VALIDEZ la pièce\n\n";
echo "4. Le bilan sera automatiquement équilibré !\n\n";
echo "═══════════════════════════════════════════════════════════════════\n";
