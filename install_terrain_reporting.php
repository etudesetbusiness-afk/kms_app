<?php
/**
 * Script d'installation des tables pour le module Reporting Terrain
 * Exécuter une seule fois: php install_terrain_reporting.php
 */

require_once __DIR__ . '/db/db.php';

$sql = file_get_contents(__DIR__ . '/db/migrations/003_terrain_reporting.sql');

try {
    // Préparer les requêtes en gardant SET et en filtrant les commentaires
    $queries = [];
    $lines = explode("\n", $sql);
    $currentQuery = '';
    
    foreach ($lines as $line) {
        $line = trim($line);
        // Ignorer les lignes vides et les commentaires purs
        if (empty($line) || strpos($line, '--') === 0) {
            continue;
        }
        
        $currentQuery .= ' ' . $line;
        
        // Si la ligne finit par un point-virgule, c'est la fin de la requête
        if (substr($line, -1) === ';') {
            $query = trim($currentQuery);
            if (!empty($query)) {
                $queries[] = $query;
            }
            $currentQuery = '';
        }
    }
    
    echo "Exécution de " . count($queries) . " requêtes...\n";
    
    foreach ($queries as $i => $query) {
        try {
            $pdo->exec($query);
            // Afficher les CREATE TABLE
            if (preg_match('/CREATE TABLE.*`([^`]+)`/', $query, $m)) {
                echo "  ✓ Table créée: {$m[1]}\n";
            }
        } catch (PDOException $e) {
            // Ignorer les erreurs "table already exists"
            if (strpos($e->getMessage(), 'already exists') === false) {
                echo "  ⚠ Requête $i: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\n✅ Migration terminée!\n";
    
    // Vérification finale
    $tables = $pdo->query("SHOW TABLES LIKE 'terrain_reporting%'")->fetchAll(PDO::FETCH_COLUMN);
    echo "\nTables terrain_reporting dans la base: " . count($tables) . "\n";
    foreach ($tables as $t) {
        echo "  - $t\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}
