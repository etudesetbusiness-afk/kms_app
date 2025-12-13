<?php
// Script de liaison catalogue_produits.produit_id → produits.id
// Basé sur correspondance slug ↔ code_produit
require __DIR__ . '/../db/db.php';

$mapping = [
    // Panneaux bois
    'plaque-ctbx-18mm'          => 'PAN-CTBX18',
    'plaque-ctbx-12mm'          => 'PAN-MDF16',
    'mdf-25mm'                  => 'PAN-MDF16',
    'mdf-16mm'                  => 'PAN-MDF16',
    'multiplex-21mm'            => 'PAN-MULTI21',
    'hdf-3mm-laminate'          => null,
    
    // Machines menuiserie
    'scie-ruban-210'            => 'MAC-SCIE210',
    'raboteuse-305mm'           => 'MAC-RABOTEUSE',
    'toupie-wood-2200'          => 'MAC-TOUPIE',
    'decolleteur-400'           => null,
    'sableuse-orbitale-225'     => null,
    'perceuse-percussion-16'    => null,
    'visseuse-sans-fil-18v'     => null,
    'meuleuse-125mm-900w'       => null,
    
    // Quincaillerie
    'charniere-inox-90'         => 'QUI-CHARN90',
    'charniere-soft-close-35'   => null,
    'glissiere-telescopique-500' => 'QUI-GLISS50',
    'poignee-aluminium-160'     => 'QUI-POIGN160',
    'serrure-push-open'         => null,
    
    // Électroménager
    'four-encastrable'          => 'ELM-FOUR',
    'plaque-vitroceramique'     => 'ELM-PLAQUE',
    
    // Accessoires
    'vis-noire'                 => 'ACC-VIS430',
    'colle-bois'                => 'ACC-COLLE',
    'vernis-brillant'           => 'ACC-VERNIS',
];

$updated = 0;
$notFound = 0;
$skipped = 0;

foreach ($mapping as $slug => $codeProduit) {
    if (!$codeProduit) { 
        $skipped++;
        continue; 
    }
    
    $stmt = $pdo->prepare('SELECT id FROM produits WHERE code_produit = ?');
    $stmt->execute([$codeProduit]);
    $prodId = $stmt->fetchColumn();
    
    if (!$prodId) { 
        echo "⚠️  Produit non trouvé: {$codeProduit} (slug: {$slug})\n";
        $notFound++;
        continue; 
    }
    
    $upd = $pdo->prepare('UPDATE catalogue_produits SET produit_id = ? WHERE slug = ?');
    $upd->execute([$prodId, $slug]);
    
    if ($upd->rowCount() > 0) {
        echo "✅ Lié: {$slug} → {$codeProduit} (ID: {$prodId})\n";
        $updated++;
    }
}

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Liens catalogue → produits mis à jour: {$updated}\n";
echo "Produits non trouvés: {$notFound}\n";
echo "Produits catalogue sans équivalent (ignorés): {$skipped}\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
