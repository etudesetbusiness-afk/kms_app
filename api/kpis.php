<?php
/**
 * api/kpis.php - API JSON pour récupérer les KPIs
 * Endpoint pour dashboard et widgets externes
 */
require_once __DIR__ . '/../security.php';
require_once __DIR__ . '/../lib/kpi_cache.php';

exigerConnexion();
exigerPermission('DASHBOARD_LIRE');

global $pdo;
header('Content-Type: application/json');

$kpi_cache = getKPICache($pdo);
$action = $_GET['action'] ?? 'all';

try {
    switch ($action) {
        case 'all':
            // Tous les KPIs
            $kpis = $kpi_cache->getAllKPIs();
            echo json_encode(['success' => true, 'data' => $kpis]);
            break;
            
        case 'ca_today':
            $data = $kpi_cache->getCAToday();
            echo json_encode(['success' => true, 'data' => $data]);
            break;
            
        case 'ca_month':
            $data = $kpi_cache->getCAMonth();
            echo json_encode(['success' => true, 'data' => $data]);
            break;
            
        case 'ca_year':
            $data = $kpi_cache->getCAYear();
            echo json_encode(['success' => true, 'data' => $data]);
            break;
            
        case 'encaissement':
            $data = $kpi_cache->getEncaissementMonth();
            echo json_encode(['success' => true, 'data' => $data]);
            break;
            
        case 'ruptures':
            $data = $kpi_cache->getStockRuptures();
            echo json_encode(['success' => true, 'data' => ['count' => $data]]);
            break;
            
        case 'non_livrees':
            $data = $kpi_cache->getNonLivrées();
            echo json_encode(['success' => true, 'data' => ['count' => $data]]);
            break;
            
        case 'flush_all':
            // Réinitialiser tous les KPIs (admin seulement)
            exigerPermission('ADMIN');
            $kpi_cache->flushAll();
            echo json_encode(['success' => true, 'message' => 'KPIs cache flushed']);
            break;
            
        case 'flush':
            // Réinitialiser un KPI spécifique
            exigerPermission('ADMIN');
            $kpi_name = $_GET['kpi'] ?? 'ca_today';
            $kpi_cache->flush($kpi_name);
            echo json_encode(['success' => true, 'message' => "KPI $kpi_name flushed"]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Unknown action: ' . $action]);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
