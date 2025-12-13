<?php
/**
 * Script de migration de base de données
 * Met à jour la structure de la BD locale pour correspondre à kms_gestion (5).sql
 * Préserve les données existantes
 */

require_once __DIR__ . '/db/db.php';

set_time_limit(300); // 5 minutes max

echo "=== MIGRATION DE LA BASE DE DONNÉES ===\n\n";

// 1. Vérifier la connexion
try {
    $pdo->query("SELECT 1");
    echo "✓ Connexion à la base de données réussie\n";
} catch (PDOException $e) {
    die("✗ Erreur de connexion : " . $e->getMessage() . "\n");
}

// 2. Lire le fichier SQL
$sqlFile = __DIR__ . '/kms_gestion (5).sql';
if (!file_exists($sqlFile)) {
    die("✗ Fichier SQL introuvable : $sqlFile\n");
}

echo "✓ Fichier SQL trouvé\n\n";

// 3. Parser le fichier SQL pour extraire uniquement les CREATE TABLE
$sqlContent = file_get_contents($sqlFile);

// Supprimer les commentaires
$sqlContent = preg_replace('/--.*$/m', '', $sqlContent);

// Extraire toutes les instructions CREATE TABLE
preg_match_all('/CREATE TABLE\s+`([^`]+)`\s*\((.*?)\)\s*ENGINE/si', $sqlContent, $matches, PREG_SET_ORDER);

echo "📊 " . count($matches) . " tables trouvées dans le fichier SQL\n\n";

$createdTables = 0;
$existingTables = 0;
$errors = [];

foreach ($matches as $match) {
    $tableName = $match[1];
    $tableDefinition = $match[2];
    
    // Vérifier si la table existe
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '$tableName'");
        $exists = $stmt->rowCount() > 0;
        
        if (!$exists) {
            // Créer la table complète
            $createSQL = "CREATE TABLE `$tableName` ($tableDefinition) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            $pdo->exec($createSQL);
            echo "✓ Table créée : $tableName\n";
            $createdTables++;
        } else {
            echo "• Table existe déjà : $tableName\n";
            $existingTables++;
        }
    } catch (PDOException $e) {
        $errorMsg = "Erreur sur $tableName : " . $e->getMessage();
        echo "✗ $errorMsg\n";
        $errors[] = $errorMsg;
    }
}

echo "\n=== RÉSUMÉ ===\n";
echo "Tables créées : $createdTables\n";
echo "Tables existantes : $existingTables\n";
echo "Erreurs : " . count($errors) . "\n";

if (!empty($errors)) {
    echo "\nDétail des erreurs :\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
}

// 4. Extraire et créer les procédures stockées
echo "\n=== PROCÉDURES STOCKÉES ===\n";
preg_match_all('/CREATE\s+DEFINER.*?PROCEDURE\s+`([^`]+)`.*?BEGIN(.*?)END\$\$/si', file_get_contents($sqlFile), $procMatches, PREG_SET_ORDER);

foreach ($procMatches as $procMatch) {
    $procName = $procMatch[1];
    
    try {
        // Supprimer la procédure si elle existe
        $pdo->exec("DROP PROCEDURE IF EXISTS `$procName`");
        
        // Recréer la procédure (utiliser la définition complète du fichier)
        $procSQL = trim($procMatch[0]);
        $procSQL = str_replace('DEFINER=`root`@`localhost`', '', $procSQL);
        $procSQL = rtrim($procSQL, '$$');
        
        $pdo->exec($procSQL);
        echo "✓ Procédure créée/mise à jour : $procName\n";
    } catch (PDOException $e) {
        echo "✗ Erreur procédure $procName : " . $e->getMessage() . "\n";
    }
}

echo "\n=== MIGRATION TERMINÉE ===\n";
echo "\nPour une migration complète avec les données d'exemple, importez le fichier SQL complet via phpMyAdmin.\n";
