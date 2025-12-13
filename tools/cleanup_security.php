<?php
/**
 * Script de nettoyage des données de sécurité
 * 
 * Nettoie :
 * - Sessions expirées
 * - Tentatives de connexion anciennes
 * - Logs d'audit obsolètes
 * - IP débloquées automatiquement
 */

require_once __DIR__ . '/../db/db.php';
require_once __DIR__ . '/../lib/redis.php';

echo "==========================================================\n";
echo "  KMS GESTION - Nettoyage Sécurité\n";
echo "  " . date('Y-m-d H:i:s') . "\n";
echo "==========================================================\n\n";

$stats = [
    'sessions_supprimees' => 0,
    'tentatives_supprimees' => 0,
    'audits_supprimes' => 0,
    'ip_debloquees' => 0,
    'cache_nettoye' => false
];

try {
    // 1. Nettoyer les sessions expirées
    echo "[1] Nettoyage des sessions expirées...\n";
    $stmt = $pdo->prepare("
        DELETE FROM sessions_actives 
        WHERE date_expiration < NOW() OR actif = 0
    ");
    $stmt->execute();
    $stats['sessions_supprimees'] = $stmt->rowCount();
    echo "    → {$stats['sessions_supprimees']} sessions supprimées\n\n";

    // 2. Nettoyer les tentatives de connexion > 90 jours
    echo "[2] Nettoyage des anciennes tentatives de connexion...\n";
    $stmt = $pdo->prepare("
        DELETE FROM tentatives_connexion 
        WHERE date_tentative < DATE_SUB(NOW(), INTERVAL 90 DAY)
    ");
    $stmt->execute();
    $stats['tentatives_supprimees'] = $stmt->rowCount();
    echo "    → {$stats['tentatives_supprimees']} tentatives supprimées\n\n";

    // 3. Nettoyer les logs d'audit selon la politique de rétention
    echo "[3] Nettoyage des logs d'audit...\n";
    $stmt = $pdo->prepare("
        SELECT valeur FROM parametres_securite 
        WHERE cle = 'audit_retention_days'
    ");
    $stmt->execute();
    $retentionDays = (int)($stmt->fetchColumn() ?? 365);
    
    $stmt = $pdo->prepare("
        DELETE FROM audit_log 
        WHERE date_action < DATE_SUB(NOW(), INTERVAL ? DAY)
        AND niveau = 'INFO'
    ");
    $stmt->execute([$retentionDays]);
    $stats['audits_supprimes'] = $stmt->rowCount();
    echo "    → {$stats['audits_supprimes']} logs supprimés (rétention: {$retentionDays} jours)\n\n";

    // 4. Débloquer automatiquement les IP temporaires expirées
    echo "[4] Déblocage des IP temporaires expirées...\n";
    $stmt = $pdo->prepare("
        UPDATE blocages_ip 
        SET actif = 0, date_deblocage = NOW()
        WHERE type_blocage = 'TEMPORAIRE' 
        AND date_expiration < NOW() 
        AND actif = 1
    ");
    $stmt->execute();
    $stats['ip_debloquees'] = $stmt->rowCount();
    echo "    → {$stats['ip_debloquees']} IP débloquées\n\n";

    // 5. Mettre à jour le compteur de sessions actives
    echo "[5] Mise à jour des compteurs de sessions...\n";
    $stmt = $pdo->prepare("
        UPDATE utilisateurs u
        SET sessions_simultanees_actuelles = (
            SELECT COUNT(*) 
            FROM sessions_actives s 
            WHERE s.utilisateur_id = u.id 
            AND s.actif = 1 
            AND s.date_expiration > NOW()
        )
    ");
    $stmt->execute();
    echo "    → Compteurs mis à jour\n\n";

    // 6. Nettoyer les codes de récupération 2FA utilisés > 1 an
    echo "[6] Nettoyage des codes de récupération 2FA...\n";
    $stmt = $pdo->prepare("
        DELETE FROM utilisateurs_2fa_recovery 
        WHERE utilise = 1 
        AND date_utilisation < DATE_SUB(NOW(), INTERVAL 1 YEAR)
    ");
    $stmt->execute();
    $codesSupprimes = $stmt->rowCount();
    echo "    → {$codesSupprimes} codes de récupération supprimés\n\n";

    // 7. Optimiser les tables
    echo "[7] Optimisation des tables...\n";
    $tables = [
        'sessions_actives',
        'tentatives_connexion',
        'audit_log',
        'blocages_ip',
        'utilisateurs_2fa_recovery'
    ];
    
    foreach ($tables as $table) {
        try {
            $pdo->exec("OPTIMIZE TABLE {$table}");
            echo "    → Table '{$table}' optimisée\n";
        } catch (PDOException $e) {
            echo "    ⚠ Erreur optimisation '{$table}': " . $e->getMessage() . "\n";
        }
    }
    echo "\n";

    // 8. (Optionnel) Nettoyer le cache Redis
    if (RedisManager::isEnabled()) {
        echo "[8] Nettoyage du cache Redis...\n";
        $prompt = "Voulez-vous vider le cache Redis ? (y/N): ";
        
        // En mode automatique (cron), ne pas vider
        if (PHP_SAPI === 'cli') {
            $handle = fopen("php://stdin", "r");
            echo $prompt;
            $line = fgets($handle);
            if (trim(strtolower($line)) === 'y') {
                RedisManager::flush();
                $stats['cache_nettoye'] = true;
                echo "    → Cache Redis vidé\n";
            } else {
                echo "    → Cache conservé\n";
            }
            fclose($handle);
        } else {
            echo "    → Cache conservé (mode automatique)\n";
        }
    } else {
        echo "[8] Redis non disponible, cache ignoré\n";
    }
    echo "\n";

    // Résumé
    echo "==========================================================\n";
    echo "  NETTOYAGE TERMINÉ AVEC SUCCÈS\n";
    echo "==========================================================\n";
    echo "  Sessions supprimées:        {$stats['sessions_supprimees']}\n";
    echo "  Tentatives supprimées:      {$stats['tentatives_supprimees']}\n";
    echo "  Logs audit supprimés:       {$stats['audits_supprimes']}\n";
    echo "  IP débloquées:              {$stats['ip_debloquees']}\n";
    echo "  Codes récup. supprimés:     {$codesSupprimes}\n";
    echo "  Cache Redis nettoyé:        " . ($stats['cache_nettoye'] ? 'OUI' : 'NON') . "\n";
    echo "==========================================================\n\n";

    // Log l'opération de nettoyage dans audit_log
    $stmt = $pdo->prepare("
        INSERT INTO audit_log 
        (utilisateur_id, action, module, details, ip_address, niveau)
        VALUES (NULL, 'CLEANUP', 'SECURITE', ?, '127.0.0.1', 'INFO')
    ");
    $stmt->execute([json_encode($stats)]);

    exit(0);

} catch (Exception $e) {
    echo "\n";
    echo "==========================================================\n";
    echo "  ERREUR LORS DU NETTOYAGE\n";
    echo "==========================================================\n";
    echo "  " . $e->getMessage() . "\n";
    echo "==========================================================\n\n";
    exit(1);
}
