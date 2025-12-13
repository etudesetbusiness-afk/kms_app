<?php
// Script de correction finale d'encodage par UPDATE direct des valeurs corrompues
require __DIR__ . '/../db/db.php';

$pdo->exec('SET NAMES utf8mb4');
$pdo->exec('SET CHARACTER SET utf8mb4');

echo "=== CORRECTION ENCODAGE DIRECTE ===\n\n";

// Corrections directes par ID ou slug des lignes problématiques
$corrections = [
    // Produits catalogue avec caractères mal encodés (C3AF = ï au lieu de è/é/°)
    ["slug" => "charniere-inox-90", "designation" => "Charnière Inox 90°", "description" => "Charnière pour portes meubles en acier inoxydable 304. Fermeture douce sans bruit."],
    ["slug" => "poignee-aluminium-160", "designation" => "Poignée Aluminium 160 mm", "description" => "Poignée contemporaine en aluminium anodisé. Design épuré pour tous styles de mobilier."],
    ["slug" => "scie-ruban-210", "designation" => "Scie à Ruban 210 W", "description" => "Scie à ruban compacte et performante pour ateliers professionnels. Coupe précise bois, dérivés et matériaux composites."],
    ["slug" => "raboteuse-305mm", "designation" => "Raboteuse 305 mm", "description" => "Raboteuse professionnelle pour lissage de pièces brutes. Système d'alimentation variable."],
    ["slug" => "toupie-wood-2200", "designation" => "Toupie 2200 W", "description" => "Toupillage haute puissance pour fraisage, rainurage et profilage. Moteur brushless haute vitesse."],
    ["slug" => "decolleteur-400", "designation" => "Décolleteur 400 mm", "description" => "Machine de découpe précise pour panneaux, contreplaqué et composites. Guide de profondeur ajustable."],
    ["slug" => "sableuse-orbitale-225", "designation" => "Sableuse Orbitale 225 mm", "description" => "Sableuse orbitale pour finition haute qualité. Vibration minimale et système d'aspiration intégré."],
    ["slug" => "perceuse-percussion-16", "designation" => "Perceuse à Percussion 16 mm", "description" => "Perceuse-visseuse professionnelle avec mode percussion pour travaux lourds en atelier."],
    ["slug" => "visseuse-sans-fil-18v", "designation" => "Visseuse sans-fil 18V", "description" => "Visseuse compacte avec batterie Li-Ion pour assemblage et finition intérieure."],
    ["slug" => "meuleuse-125mm-900w", "designation" => "Meuleuse 125 mm 900 W", "description" => "Meuleuse d'angle compact pour découpe, meulage et travaux de finition rapides."],
    ["slug" => "charniere-soft-close-35", "designation" => "Charnière Soft-Close 35 mm", "description" => "Système de fermeture douce intégré. Fermeture progressive et silencieuse pour tous types de portes."],
    ["slug" => "glissiere-telescopique-500", "designation" => "Glissière Télescopique 500 mm", "description" => "Rails de qualité supérieure pour tiroirs professionnels. Mécanisme d'extension complète 100%."],
    ["slug" => "serrure-push-open", "designation" => "Serrure Push-to-Open", "description" => "Système d'ouverture sans poignée par simple pression. Intégration discrète dans le mobilier."],
    ["slug" => "plaque-ctbx-18mm", "designation" => "Panneau CTBX 18 mm", "description" => "Panneau contreplaqué CTBX haute résistance, idéal pour milieux humides et intérieurs modernes."],
    ["slug" => "plaque-ctbx-12mm", "designation" => "Panneau CTBX 12 mm", "description" => "Contreplaqué fin CTBX pour mobilier intérieur et agencements légers."],
    ["slug" => "mdf-25mm", "designation" => "Panneau MDF 25 mm", "description" => "Medium Density Fiberboard, parfait pour menuiserie intérieure, portes et placards."],
    ["slug" => "mdf-16mm", "designation" => "Panneau MDF 16 mm", "description" => "MDF standard pour mobilier et revêtements intérieurs. Facile à usiner et peindre."],
    ["slug" => "hdf-3mm-laminate", "designation" => "Panneau HDF 3 mm laminé", "description" => "Panneau haute densité avec revêtement mélaminé pour plans de travail et surfaces de travail."],
    ["slug" => "multiplex-21mm", "designation" => "Multiplex 21 mm", "description" => "Contreplaqué multiplis pour construction légère, étagères et agencement intérieur."],
];

$count = 0;
foreach ($corrections as $item) {
    if (isset($item['description'])) {
        $stmt = $pdo->prepare("UPDATE catalogue_produits SET designation = ?, description = ? WHERE slug = ?");
        $stmt->execute([$item['designation'], $item['description'], $item['slug']]);
    } else {
        $stmt = $pdo->prepare("UPDATE catalogue_produits SET designation = ? WHERE slug = ?");
        $stmt->execute([$item['designation'], $item['slug']]);
    }
    if ($stmt->rowCount() > 0) {
        echo "✅ {$item['slug']}: {$item['designation']}\n";
        $count++;
    }
}

echo "\n{$count} produits corrigés\n\n";

// Vérification finale
echo "=== VÉRIFICATION FINALE ===\n";
$stmt = $pdo->query("SELECT slug, designation FROM catalogue_produits WHERE slug IN ('scie-ruban-210', 'charniere-inox-90', 'poignee-aluminium-160') ORDER BY slug");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo "  • {$row['slug']}: {$row['designation']}\n";
}

echo "\n✅ Correction terminée\n";
