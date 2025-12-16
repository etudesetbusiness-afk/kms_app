<?php
// Test diagnostic pour utilisateurs/edit.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Test dans le contexte utilisateurs/</h2>";

echo "<h3>1. Avant require security.php</h3>";
echo "PDO defini: " . (isset($pdo) ? 'OUI' : 'NON') . "<br>";

require_once __DIR__ . '/../security.php';

echo "<h3>2. Apres require security.php (sans global)</h3>";
echo "PDO defini: " . (isset($pdo) ? 'OUI' : 'NON') . "<br>";
if (isset($pdo)) {
    echo "PDO type: " . gettype($pdo) . "<br>";
    echo "PDO instanceof PDO: " . ($pdo instanceof PDO ? 'OUI' : 'NON') . "<br>";
}

echo "<h3>3. Apres global \$pdo</h3>";
global $pdo;
echo "PDO defini: " . (isset($pdo) ? 'OUI' : 'NON') . "<br>";
if (isset($pdo)) {
    echo "PDO type: " . gettype($pdo) . "<br>";
    echo "PDO instanceof PDO: " . ($pdo instanceof PDO ? 'OUI' : 'NON') . "<br>";
}

echo "<h3>4. Essai empty() et instanceof</h3>";
echo "empty(\$pdo): " . (empty($pdo) ? 'OUI' : 'NON') . "<br>";
echo "\$pdo instanceof PDO: " . ($pdo instanceof PDO ? 'OUI' : 'NON') . "<br>";

echo "<h3>5. Si PDO OK, requete roles</h3>";
if ($pdo instanceof PDO) {
    try {
        $stmt = $pdo->query("SELECT id, code, nom FROM roles ORDER BY code");
        $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "Nombre de roles: " . count($roles) . "<br>";
        echo "<ul>";
        foreach ($roles as $r) {
            echo "<li>{$r['code']} - {$r['nom']}</li>";
        }
        echo "</ul>";
    } catch (Exception $e) {
        echo "Erreur: " . $e->getMessage() . "<br>";
    }
} else {
    echo "PDO non disponible!<br>";
    
    // Essayer d'inclure directement
    echo "<h3>6. Include direct db.php</h3>";
    require_once __DIR__ . '/../db/db.php';
    echo "Apres include - PDO defini: " . (isset($pdo) ? 'OUI' : 'NON') . "<br>";
    if ($pdo instanceof PDO) {
        $stmt = $pdo->query("SELECT id, code, nom FROM roles ORDER BY code");
        $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "Nombre de roles apres include direct: " . count($roles) . "<br>";
    }
}
