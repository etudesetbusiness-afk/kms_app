<?php
/**
 * admin/clear_cache.php - Vide le cache (fichiers + Redis)
 */
require_once __DIR__ . '/../security.php';
require_once __DIR__ . '/../lib/cache.php';

exigerConnexion();
exigerPermission('ADMIN');

$cache = getCache();
$stats_before = $cache->getStats();

// Vider le cache
$success = $cache->flush();

$stats_after = $cache->getStats();

// Message de succès
if ($success) {
    $_SESSION['flashSuccess'] = sprintf(
        "✅ Cache vidé avec succès! (%d fichiers, %.2f MB supprimés)",
        $stats_before['files'],
        $stats_before['size_mb']
    );
} else {
    $_SESSION['flashError'] = "❌ Erreur lors du vidage du cache";
}

// Rediriger vers le tableau de bord d'optimisation
header('Location: ' . url_for('admin/database_optimization.php'));
exit;
