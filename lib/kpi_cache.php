<?php
/**
 * lib/kpi_cache.php - Système de cache pour KPIs (métriques commerciales)
 * Gère le caching des indicateurs clés de performance
 */

require_once __DIR__ . '/cache.php';

class KPICache {
    private $cache = null;
    private $pdo = null;
    
    // TTLs par type de KPI
    private $ttls = [
        'daily' => 3600,      // 1h (CA daily, ventes jour)
        'monthly' => 86400,   // 24h (CA month, clients month)
        'yearly' => 604800,   // 7j (CA year, stats year)
        'realtime' => 300     // 5min (ruptures, encaissement)
    ];
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->cache = getCache();
    }
    
    /**
     * Récupère ou calcule le CA du jour
     * @return array ['montant' => float, 'nombre' => int, 'cached' => bool]
     */
    public function getCAToday() {
        return $this->remember('kpi_ca_today', 'daily', function() {
            $sql = "
                SELECT 
                    SUM(montant_total_ttc) as montant,
                    COUNT(*) as nombre
                FROM ventes
                WHERE DATE(date_vente) = CURDATE()
                  AND statut NOT IN ('ANNULEE', 'SUSPENDUE')
            ";
            $result = $this->pdo->query($sql)->fetch(PDO::FETCH_ASSOC);
            return [
                'montant' => (float)($result['montant'] ?? 0),
                'nombre' => (int)($result['nombre'] ?? 0)
            ];
        });
    }
    
    /**
     * Récupère le CA du mois
     * @return array ['montant' => float, 'nombre' => int]
     */
    public function getCAMonth() {
        return $this->remember('kpi_ca_month', 'monthly', function() {
            $sql = "
                SELECT 
                    SUM(montant_total_ttc) as montant,
                    COUNT(*) as nombre
                FROM ventes
                WHERE YEAR(date_vente) = YEAR(CURDATE())
                  AND MONTH(date_vente) = MONTH(CURDATE())
                  AND statut NOT IN ('ANNULEE', 'SUSPENDUE')
            ";
            $result = $this->pdo->query($sql)->fetch(PDO::FETCH_ASSOC);
            return [
                'montant' => (float)($result['montant'] ?? 0),
                'nombre' => (int)($result['nombre'] ?? 0)
            ];
        });
    }
    
    /**
     * Récupère le CA de l'année
     * @return array ['montant' => float, 'nombre' => int]
     */
    public function getCAYear() {
        return $this->remember('kpi_ca_year', 'yearly', function() {
            $sql = "
                SELECT 
                    SUM(montant_total_ttc) as montant,
                    COUNT(*) as nombre
                FROM ventes
                WHERE YEAR(date_vente) = YEAR(CURDATE())
                  AND statut NOT IN ('ANNULEE', 'SUSPENDUE')
            ";
            $result = $this->pdo->query($sql)->fetch(PDO::FETCH_ASSOC);
            return [
                'montant' => (float)($result['montant'] ?? 0),
                'nombre' => (int)($result['nombre'] ?? 0)
            ];
        });
    }
    
    /**
     * Clients actifs (ayant acheté ce mois)
     * @return int
     */
    public function getActiveClientsMonth() {
        return $this->remember('kpi_active_clients_month', 'monthly', function() {
            $sql = "
                SELECT COUNT(DISTINCT client_id) as cnt
                FROM ventes
                WHERE YEAR(date_vente) = YEAR(CURDATE())
                  AND MONTH(date_vente) = MONTH(CURDATE())
                  AND statut NOT IN ('ANNULEE', 'SUSPENDUE')
            ";
            return (int)($this->pdo->query($sql)->fetch()['cnt'] ?? 0);
        });
    }
    
    /**
     * Ruptures de stock actuelles
     * @return int Nombre de produits en rupture
     */
    public function getStockRuptures() {
        return $this->remember('kpi_ruptures', 'realtime', function() {
            $sql = "
                SELECT COUNT(*) as cnt
                FROM produits
                WHERE stock_actuel <= seuil_minimum
                  AND actif = 1
            ";
            return (int)($this->pdo->query($sql)->fetch()['cnt'] ?? 0);
        });
    }
    
    /**
     * Encaissement actuel du mois
     * @return array ['montant' => float, 'percentage' => int]
     */
    public function getEncaissementMonth() {
        return $this->remember('kpi_encaissement_month', 'realtime', function() {
            $sql = "
                SELECT 
                    SUM(CASE WHEN statut_encaissement = 'ENCAISSE' THEN montant_total_ttc ELSE 0 END) as montant_encaisse,
                    SUM(montant_total_ttc) as montant_total
                FROM ventes
                WHERE YEAR(date_vente) = YEAR(CURDATE())
                  AND MONTH(date_vente) = MONTH(CURDATE())
                  AND statut NOT IN ('ANNULEE', 'SUSPENDUE')
            ";
            $result = $this->pdo->query($sql)->fetch(PDO::FETCH_ASSOC);
            
            $total = (float)($result['montant_total'] ?? 0);
            $encaisse = (float)($result['montant_encaisse'] ?? 0);
            
            return [
                'montant' => $encaisse,
                'percentage' => $total > 0 ? (int)(($encaisse / $total) * 100) : 0
            ];
        });
    }
    
    /**
     * Top clients du mois
     * @param int $limit Nombre de clients à retourner
     * @return array
     */
    public function getTopClientsMonth($limit = 5) {
        return $this->remember('kpi_top_clients_month', 'monthly', function() use ($limit) {
            $sql = "
                SELECT 
                    c.id,
                    c.nom,
                    COUNT(*) as nb_commandes,
                    SUM(v.montant_total_ttc) as ca
                FROM clients c
                JOIN ventes v ON c.id = v.client_id
                WHERE YEAR(v.date_vente) = YEAR(CURDATE())
                  AND MONTH(v.date_vente) = MONTH(CURDATE())
                  AND v.statut NOT IN ('ANNULEE', 'SUSPENDUE')
                GROUP BY c.id, c.nom
                ORDER BY ca DESC
                LIMIT ?
            ";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        });
    }
    
    /**
     * Ventes non livrées (EN_ATTENTE_LIVRAISON)
     * @return int
     */
    public function getNonLivrées() {
        return $this->remember('kpi_non_livrees', 'realtime', function() {
            $sql = "
                SELECT COUNT(*) as cnt
                FROM ventes
                WHERE statut IN ('EN_ATTENTE_LIVRAISON', 'PARTIELLEMENT_LIVREE')
            ";
            return (int)($this->pdo->query($sql)->fetch()['cnt'] ?? 0);
        });
    }
    
    /**
     * Récupère tous les KPIs principaux
     * @return array KPIs complets
     */
    public function getAllKPIs() {
        return [
            'ca_today' => $this->getCAToday(),
            'ca_month' => $this->getCAMonth(),
            'ca_year' => $this->getCAYear(),
            'active_clients' => $this->getActiveClientsMonth(),
            'ruptures' => $this->getStockRuptures(),
            'encaissement' => $this->getEncaissementMonth(),
            'top_clients' => $this->getTopClientsMonth(5),
            'non_livrees' => $this->getNonLivrées(),
            'cached_at' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Vide le cache de tous les KPIs
     * @return bool
     */
    public function flushAll() {
        $keys = [
            'kpi_ca_today',
            'kpi_ca_month',
            'kpi_ca_year',
            'kpi_active_clients_month',
            'kpi_ruptures',
            'kpi_encaissement_month',
            'kpi_top_clients_month',
            'kpi_non_livrees'
        ];
        
        foreach ($keys as $key) {
            $this->cache->delete($key);
        }
        
        return true;
    }
    
    /**
     * Vide un KPI spécifique
     * @param string $kpi_name Nom du KPI
     * @return bool
     */
    public function flush($kpi_name) {
        $key = 'kpi_' . $kpi_name;
        return $this->cache->delete($key);
    }
    
    // ==================== PRIVÉ ====================
    
    /**
     * Pattern remember avec TTL basé sur type
     * @param string $key Clé cache
     * @param string $ttl_type Type de TTL (daily, monthly, yearly, realtime)
     * @param callable $callback Fonction pour calculer
     * @return mixed Résultat
     */
    private function remember($key, $ttl_type, callable $callback) {
        $ttl = $this->ttls[$ttl_type] ?? 3600;
        $value = $this->cache->get($key);
        
        if ($value === null) {
            $value = $callback();
            $this->cache->set($key, $value, $ttl);
        }
        
        return $value;
    }
}

/**
 * Helper pour accéder à KPICache
 */
function getKPICache($pdo) {
    if (!isset($GLOBALS['kpi_cache'])) {
        $GLOBALS['kpi_cache'] = new KPICache($pdo);
    }
    return $GLOBALS['kpi_cache'];
}

/**
 * Helper: Récupère tous les KPIs
 */
function getAllKPIs($pdo) {
    return getKPICache($pdo)->getAllKPIs();
}
