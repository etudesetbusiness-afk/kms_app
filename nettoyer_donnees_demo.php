<?php
/**
 * Nettoyage des données de démonstration
 * Supprime toutes les données générées pour repartir de zéro
 */

require_once __DIR__ . '/db/db.php';

echo "\n=== NETTOYAGE DONNÉES DÉMO ===\n\n";
echo "⚠️  ATTENTION: Cette opération va supprimer toutes les données suivantes:\n";
echo "  - Encaissements caisse\n";
echo "  - Bons de livraison\n";
echo "  - Ventes\n";
echo "  - Devis\n";
echo "  - Mouvements de stock\n";
echo "  - Produits (sauf ceux créés manuellement)\n";
echo "  - Clients (sauf ceux créés manuellement)\n\n";

echo "Les données seront supprimées dans 3 secondes...\n";
sleep(1);
echo "2...\n";
sleep(1);
echo "1...\n";
sleep(1);

try {
    $pdo->beginTransaction();
    
        // Désactiver les contraintes FK temporairement
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    echo "\n🗑️  Suppression en cours...\n\n";
    
    // Ordre important pour respecter les contraintes FK
    $tables = [
        'caisse_journal' => 'Encaissements caisse',
        'bons_livraison_lignes' => 'Lignes BL',
        'bons_livraison' => 'Bons livraison',
        'ordres_preparation_lignes' => 'Lignes ordres',
        'ordres_preparation' => 'Ordres préparation',
        'ventes_lignes' => 'Lignes ventes',
        'ventes' => 'Ventes',
        'devis_lignes' => 'Lignes devis',
        'devis' => 'Devis',
        'stocks_mouvements' => 'Mouvements stock',
        'achats_lignes' => 'Lignes achats',
        'achats' => 'Achats'
    ];
    
    foreach ($tables as $table => $label) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            $pdo->exec("DELETE FROM $table");
            echo "   ✅ $label: $count supprimé(s)\n";
        }
    }
    
    // Produits commençant par les codes de menuiserie générés
    $stmt = $pdo->prepare("DELETE FROM produits WHERE code_produit REGEXP '^(PAN|MAC|QUI|ELM|ACC)-'");
    $stmt->execute();
    $count = $stmt->rowCount();
    if ($count > 0) {
        echo "   ✅ Produits démo: $count supprimé(s)\n";
    }
    
    // Clients démo (emails .ci)
    $stmt = $pdo->prepare("DELETE FROM clients WHERE email LIKE '%@email.ci'");
    $stmt->execute();
    $count = $stmt->rowCount();
    if ($count > 0) {
        echo "   ✅ Clients démo: $count supprimé(s)\n";
    }
    
    // Reset AUTO_INCREMENT pour repartir de 1
    foreach (array_keys($tables) as $table) {
        $pdo->exec("ALTER TABLE $table AUTO_INCREMENT = 1");
    }
    $pdo->exec("ALTER TABLE produits AUTO_INCREMENT = 1");
    $pdo->exec("ALTER TABLE clients AUTO_INCREMENT = 1");
    
    $pdo->commit();
    
        // Réactiver les contraintes FK
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "\n✅ NETTOYAGE TERMINÉ\n";
    echo "Vous pouvez relancer generer_donnees_demo_final.php\n\n";
    
} catch (Exception $e) {
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1"); // Réactiver même en cas d'erreur
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $msg = $e->getMessage();
    if (stripos($msg, 'no active transaction') !== false) {
        echo "\n✅ Nettoyage terminé (transaction déjà close)\n\n";
        exit(0);
    }
    echo "\n❌ ERREUR: " . $msg . "\n\n";
    exit(1);
}
