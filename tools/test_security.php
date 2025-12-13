<?php
/**
 * Script de test de la configuration Sécurité & Performance
 * 
 * Vérifie que Redis, 2FA et Rate Limiting fonctionnent correctement
 */

require_once __DIR__ . '/../db/db.php';
require_once __DIR__ . '/../lib/redis.php';
require_once __DIR__ . '/../lib/rate_limiter.php';
require_once __DIR__ . '/../lib/two_factor_auth.php';
require_once __DIR__ . '/../lib/cache_helper.php';

echo "==========================================================\n";
echo "  KMS GESTION - Test Sécurité & Performance\n";
echo "==========================================================\n\n";

$allGood = true;

// Test 1: Extension Redis
echo "[1] Test Extension Redis PHP\n";
if (class_exists('Redis')) {
    echo "    ✓ Extension Redis installée\n";
} else {
    echo "    ✗ Extension Redis NON installée\n";
    echo "      → Installer php_redis.dll et activer dans php.ini\n";
    $allGood = false;
}
echo "\n";

// Test 2: Connexion Redis
echo "[2] Test Connexion Redis Server\n";
if (RedisManager::isEnabled()) {
    echo "    ✓ Redis connecté et opérationnel\n";
    $stats = RedisManager::stats();
    echo "    → Version: " . ($stats['redis_version'] ?? 'N/A') . "\n";
    echo "    → Clés actives: " . ($stats['db0'] ?? '0') . "\n";
} else {
    echo "    ⚠ Redis non disponible (mode fallback PHP)\n";
    echo "      → Vérifier que redis-server est lancé\n";
    echo "      → Port par défaut: 6379\n";
}
echo "\n";

// Test 3: Cache fonctionnel
echo "[3] Test Cache avec Redis\n";
try {
    $testKey = 'test:' . time();
    $testValue = 'KMS Test Value';
    
    RedisManager::set($testKey, $testValue, 60);
    $retrieved = RedisManager::get($testKey);
    
    if ($retrieved === $testValue) {
        echo "    ✓ Cache SET/GET fonctionne\n";
        RedisManager::delete($testKey);
    } else {
        echo "    ✗ Cache SET/GET ne fonctionne pas correctement\n";
        $allGood = false;
    }
} catch (Exception $e) {
    echo "    ✗ Erreur cache: " . $e->getMessage() . "\n";
    $allGood = false;
}
echo "\n";

// Test 4: Rate Limiting
echo "[4] Test Rate Limiting\n";
try {
    $testIP = 'test.ip.' . rand(1000, 9999);
    
    for ($i = 1; $i <= 6; $i++) {
        $result = RateLimiter::check($testIP, 'test', 5, 60);
        
        if ($i <= 5) {
            if ($result['allowed']) {
                echo "    ✓ Tentative {$i}/5 autorisée\n";
            } else {
                echo "    ✗ Tentative {$i}/5 devrait être autorisée\n";
                $allGood = false;
            }
        } else {
            if (!$result['allowed']) {
                echo "    ✓ Tentative 6/5 bloquée comme prévu\n";
            } else {
                echo "    ✗ Tentative 6/5 devrait être bloquée\n";
                $allGood = false;
            }
        }
    }
    
    RateLimiter::reset($testIP, 'test');
} catch (Exception $e) {
    echo "    ✗ Erreur rate limiting: " . $e->getMessage() . "\n";
    $allGood = false;
}
echo "\n";

// Test 5: 2FA TOTP
echo "[5] Test 2FA (TOTP)\n";
try {
    $secret = TwoFactorAuth::generateSecret();
    echo "    ✓ Génération secret: " . substr($secret, 0, 10) . "...\n";
    
    $code = TwoFactorAuth::generateCode($secret);
    echo "    ✓ Génération code: {$code}\n";
    
    $verified = TwoFactorAuth::verifyCode($secret, $code);
    if ($verified) {
        echo "    ✓ Vérification code réussie\n";
    } else {
        echo "    ✗ Vérification code échouée\n";
        $allGood = false;
    }
    
    $url = TwoFactorAuth::getQrCodeUrl($secret, 'test@kms.cm');
    if (str_starts_with($url, 'otpauth://')) {
        echo "    ✓ URL QR code générée correctement\n";
    } else {
        echo "    ✗ URL QR code invalide\n";
        $allGood = false;
    }
} catch (Exception $e) {
    echo "    ✗ Erreur 2FA: " . $e->getMessage() . "\n";
    $allGood = false;
}
echo "\n";

// Test 6: Tables de la base de données
echo "[6] Test Tables Base de Données\n";
$requiredTables = [
    'utilisateurs_2fa',
    'utilisateurs_2fa_recovery',
    'sessions_actives',
    'audit_log',
    'tentatives_connexion',
    'blocages_ip',
    'parametres_securite'
];

foreach ($requiredTables as $table) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
        if ($stmt->rowCount() > 0) {
            echo "    ✓ Table '{$table}' existe\n";
        } else {
            echo "    ✗ Table '{$table}' manquante\n";
            echo "      → Exécuter db/migrations/002_security_enhancements.sql\n";
            $allGood = false;
        }
    } catch (PDOException $e) {
        echo "    ✗ Erreur vérification table '{$table}': " . $e->getMessage() . "\n";
        $allGood = false;
    }
}
echo "\n";

// Test 7: Cache Helper
echo "[7] Test Cache Helper\n";
try {
    $familles = CacheHelper::getFamillesProduits($pdo);
    echo "    ✓ Cache familles produits: " . count($familles) . " items\n";
    
    $modes = CacheHelper::getModesPaiement($pdo);
    echo "    ✓ Cache modes paiement: " . count($modes) . " items\n";
    
    $canaux = CacheHelper::getCanauxVente($pdo);
    echo "    ✓ Cache canaux vente: " . count($canaux) . " items\n";
} catch (Exception $e) {
    echo "    ✗ Erreur Cache Helper: " . $e->getMessage() . "\n";
    $allGood = false;
}
echo "\n";

// Test 8: Paramètres de sécurité
echo "[8] Test Paramètres de Sécurité\n";
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM parametres_securite");
    $count = $stmt->fetchColumn();
    
    if ($count >= 10) {
        echo "    ✓ Paramètres de sécurité initialisés ({$count} paramètres)\n";
    } else {
        echo "    ⚠ Nombre de paramètres faible ({$count})\n";
    }
    
    $stmt = $pdo->query("SELECT * FROM parametres_securite WHERE cle = '2fa_obligatoire_admin'");
    $param = $stmt->fetch();
    if ($param) {
        echo "    ✓ 2FA admin: " . ($param['valeur'] == '1' ? 'OBLIGATOIRE' : 'Optionnel') . "\n";
    }
} catch (PDOException $e) {
    echo "    ✗ Erreur paramètres: " . $e->getMessage() . "\n";
    $allGood = false;
}
echo "\n";

// Résumé final
echo "==========================================================\n";
if ($allGood) {
    echo "  ✓✓✓ TOUS LES TESTS SONT PASSÉS ✓✓✓\n";
    echo "  Le système de sécurité est opérationnel.\n";
} else {
    echo "  ⚠⚠⚠ CERTAINS TESTS ONT ÉCHOUÉ ⚠⚠⚠\n";
    echo "  Consultez les messages ci-dessus pour les corrections.\n";
}
echo "==========================================================\n\n";

// Informations supplémentaires
echo "Informations système:\n";
echo "  PHP Version: " . PHP_VERSION . "\n";
echo "  Extensions: " . (extension_loaded('redis') ? 'Redis ✓' : 'Redis ✗') . "\n";
echo "  PDO Driver: " . implode(', ', PDO::getAvailableDrivers()) . "\n";
echo "\n";

echo "Pour plus d'informations, consulter:\n";
echo "  → docs/INSTALLATION_SECURITE.md\n";
echo "\n";

exit($allGood ? 0 : 1);
