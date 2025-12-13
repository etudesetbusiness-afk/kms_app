<?php
// Script de correction de l'encodage via PHP (plus fiable que SQL depuis PowerShell)
require __DIR__ . '/../db/db.php';

// Force UTF-8 sur la connexion
$pdo->exec('SET NAMES utf8mb4');
$pdo->exec('SET CHARACTER SET utf8mb4');

echo "=== CORRECTION ENCODAGE VIA PHP ===\n\n";

// Correction canaux_vente
$stmt = $pdo->prepare("UPDATE canaux_vente SET libelle = ? WHERE id = 4");
$stmt->execute(["Vente liée à l'hôtel"]);
echo "✅ Canal vente 4 corrigé\n";

// Corrections catalogue_produits - double encodage UTF-8 → Latin1 → UTF-8
$corrections = [
    // Accents aigus
    'é' => 'é',  // é mal encodé
    'É' => 'É',
    // Accents graves  
    'é' => 'à',  // à mal encodé
    'é' => 'è',  // è mal encodé
    // Circonflexes
    'é' => 'ê',  // ê mal encodé
    'é' => 'ô',  // ô mal encodé
    'é' => 'î',  // î mal encodé
    'é' => 'û',  // û mal encodé
    'é' => 'â',  // â mal encodé
    // Cédille
    'ç' => 'ç',  // ç mal encodé
    // Tréma
    'é' => 'ï',  // ï mal encodé
    // Autres patterns observés
    'é Ruban' => 'à Ruban',
    'iére' => 'ière',
    '90é' => '90°',
];

$count = 0;
foreach ($corrections as $bad => $good) {
    // designation
    $stmt = $pdo->prepare("UPDATE catalogue_produits SET designation = REPLACE(designation, ?, ?) WHERE designation LIKE ?");
    $stmt->execute([$bad, $good, "%{$bad}%"]);
    $count += $stmt->rowCount();
    
    // description
    $stmt = $pdo->prepare("UPDATE catalogue_produits SET description = REPLACE(description, ?, ?) WHERE description LIKE ?");
    $stmt->execute([$bad, $good, "%{$bad}%"]);
    $count += $stmt->rowCount();
    
    // caracteristiques_json
    $stmt = $pdo->prepare("UPDATE catalogue_produits SET caracteristiques_json = REPLACE(caracteristiques_json, ?, ?) WHERE caracteristiques_json LIKE ?");
    $stmt->execute([$bad, $good, "%{$bad}%"]);
    $count += $stmt->rowCount();
}

echo "✅ {$count} corrections appliquées dans catalogue_produits\n\n";

// Vérification
echo "=== VÉRIFICATION ===\n";
$result = $pdo->query("SELECT libelle FROM canaux_vente")->fetchAll(PDO::FETCH_COLUMN);
foreach ($result as $libelle) {
    echo "  • {$libelle}\n";
}

echo "\n✅ Encodage corrigé avec succès\n";
