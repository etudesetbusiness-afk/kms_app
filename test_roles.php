<?php
// Test pour diagnostiquer le problème des rôles
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Test Diagnostic Rôles</h2>";

echo "<h3>1. Avant require security.php</h3>";
echo "PDO défini: " . (isset($pdo) ? 'OUI' : 'NON') . "<br>";

require_once __DIR__ . '/security.php';

echo "<h3>2. Après require security.php</h3>";
echo "PDO défini: " . (isset($pdo) ? 'OUI' : 'NON') . "<br>";
echo "Type: " . gettype($pdo) . "<br>";

if ($pdo instanceof PDO) {
    echo "PDO est bien une instance PDO<br>";
    
    echo "<h3>3. Test requête rôles</h3>";
    try {
        $stmt = $pdo->query("SELECT id, code, nom FROM roles ORDER BY code");
        $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "Nombre de rôles: " . count($roles) . "<br>";
        echo "<ul>";
        foreach ($roles as $r) {
            echo "<li>{$r['code']} - {$r['nom']}</li>";
        }
        echo "</ul>";
    } catch (Exception $e) {
        echo "Erreur: " . $e->getMessage() . "<br>";
    }
} else {
    echo "PDO N'EST PAS une instance PDO!<br>";
    
    echo "<h3>Tentative avec global</h3>";
    global $pdo;
    echo "Après global - PDO défini: " . (isset($pdo) ? 'OUI' : 'NON') . "<br>";
    echo "Après global - Type: " . gettype($pdo) . "<br>";
    
    if (!($pdo instanceof PDO)) {
        echo "<h3>Include direct db.php</h3>";
        require_once __DIR__ . '/db/db.php';
        echo "Après include - PDO défini: " . (isset($pdo) ? 'OUI' : 'NON') . "<br>";
        echo "Après include - Type: " . gettype($pdo) . "<br>";
    }
}
