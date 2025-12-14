<?php
/**
 * Installe la table user_preferences
 */
require_once __DIR__ . '/security.php';

global $pdo;

echo "Installation Phase 3.2 - User Preferences\n";
echo str_repeat("=", 50) . "\n\n";

try {
    $sql = file_get_contents(__DIR__ . '/db/003_user_preferences.sql');
    $pdo->exec($sql);
    
    echo "✅ Table user_preferences créée avec succès\n";
    
    // Vérifier
    $result = $pdo->query("SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'kms_gestion' AND TABLE_NAME = 'user_preferences'");
    $exists = $result->fetch()['cnt'];
    
    if ($exists) {
        echo "✅ Vérification: table existe\n\n";
    } else {
        echo "❌ Vérification échouée\n\n";
    }
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n\n";
}

echo "Installation terminée.\n";
