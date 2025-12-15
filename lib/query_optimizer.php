<?php
/**
 * lib/query_optimizer.php - Optimisations des requêtes SQL
 * Aide à identifier et optimiser les requêtes lentes
 */

class QueryOptimizer {
    private $pdo = null;
    private $slow_query_threshold = 0.1; // 100ms
    private $queries_log = [];
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Exécute une requête avec profiling
     * @param string $sql Requête SQL
     * @param array $params Paramètres
     * @return PDOStatement
     */
    public function executeWithProfiling($sql, $params = []) {
        $start = microtime(true);
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            $duration = microtime(true) - $start;
            
            // Logger requête lente
            if ($duration > $this->slow_query_threshold) {
                $this->logSlowQuery($sql, $params, $duration);
            }
            
            return $stmt;
        } catch (Exception $e) {
            throw $e;
        }
    }
    
    /**
     * Analyse les indexes d'une table
     * @param string $table Nom de la table
     * @return array Indexes existants
     */
    public function analyzeTableIndexes($table) {
        $sql = "SHOW INDEXES FROM `$table`";
        $stmt = $this->pdo->query($sql);
        $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $result = [];
        foreach ($indexes as $idx) {
            $result[$idx['Key_name']][] = $idx['Column_name'];
        }
        
        return $result;
    }
    
    /**
     * Analyse une requête avec EXPLAIN
     * @param string $sql Requête SELECT
     * @return array Résultat EXPLAIN
     */
    public function explainQuery($sql) {
        $explain_sql = "EXPLAIN " . $sql;
        $stmt = $this->pdo->query($explain_sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtient les statistiques des tables
     * @param string $database Nom de la database
     * @return array Stats par table
     */
    public function getTableStats($database) {
        $sql = "
            SELECT 
                TABLE_NAME,
                TABLE_ROWS,
                ROUND(((data_length + index_length) / 1024 / 1024), 2) as size_mb,
                ROUND((data_length / 1024 / 1024), 2) as data_mb,
                ROUND((index_length / 1024 / 1024), 2) as index_mb
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = ?
            ORDER BY TABLE_ROWS DESC
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$database]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Suggère des indexes manquants basé sur les requêtes
     * @return array Suggestions
     */
    public function suggestIndexes() {
        $suggestions = [];
        
        // Vérifier indexes courants manquants
        $tables_and_columns = [
            'ventes' => ['client_id', 'date_vente', 'canal_vente_id', 'statut'],
            'livraisons' => ['vente_id', 'date_livraison', 'statut'],
            'produits' => ['code', 'categorie'],
            'clients' => ['code', 'type'],
            'stocks_mouvements' => ['produit_id', 'date_mouvement', 'type_mouvement'],
            'pieces_comptables' => ['numero_piece', 'date_piece', 'exercice_id'],
            'journal_comptable' => ['date_operation', 'journal_id', 'compte_id']
        ];
        
        foreach ($tables_and_columns as $table => $columns) {
            // Vérifier si table existe
            $check = "SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?";
            $stmt = $this->pdo->prepare($check);
            $stmt->execute([$table]);
            
            if (!$stmt->fetch()) {
                continue; // Table n'existe pas
            }
            
            $indexes = $this->analyzeTableIndexes($table);
            
            foreach ($columns as $col) {
                // Vérifier si index existe
                $has_index = false;
                foreach ($indexes as $idx_name => $idx_cols) {
                    if (in_array($col, $idx_cols)) {
                        $has_index = true;
                        break;
                    }
                }
                
                if (!$has_index && !in_array('PRIMARY', array_keys($indexes))) {
                    $suggestions[] = [
                        'table' => $table,
                        'column' => $col,
                        'sql' => "ALTER TABLE `$table` ADD INDEX idx_{$col} (`$col`);",
                        'reason' => "Colonne fréquemment filtrée"
                    ];
                }
            }
        }
        
        return $suggestions;
    }
    
    /**
     * Optimise les requêtes JOIN fréquentes
     * @param string $query Requête à analyser
     * @return array Suggestions
     */
    public function optimizeJoins($query) {
        $suggestions = [];
        
        // Compter JOINs
        $join_count = substr_count(strtoupper($query), 'JOIN');
        if ($join_count > 3) {
            $suggestions[] = "⚠️ Requête avec " . $join_count . " JOINs - envisager de fragmenter ou utiliser des sous-requêtes cachées";
        }
        
        // Détécter JOINs sans ON
        if (stripos($query, 'JOIN') !== false && stripos($query, 'ON') === false) {
            $suggestions[] = "❌ JOIN trouvé sans clause ON - cause probable de CROSS JOIN";
        }
        
        return $suggestions;
    }
    
    /**
     * Enregistre une requête lente
     */
    private function logSlowQuery($sql, $params, $duration) {
        $log_file = __DIR__ . '/../logs/slow_queries.log';
        
        // Créer répertoire logs
        $logs_dir = dirname($log_file);
        if (!is_dir($logs_dir)) {
            @mkdir($logs_dir, 0755, true);
        }
        
        $log_entry = sprintf(
            "[%s] [%.3fs] %s | Params: %s\n",
            date('Y-m-d H:i:s'),
            $duration,
            $sql,
            json_encode($params)
        );
        
        @file_put_contents($log_file, $log_entry, FILE_APPEND);
    }
    
    /**
     * Obtient les requêtes lentes loggées
     * @param int $limit Nombre max
     * @return array
     */
    public function getSlowQueries($limit = 20) {
        $log_file = __DIR__ . '/../logs/slow_queries.log';
        
        if (!file_exists($log_file)) {
            return [];
        }
        
        $lines = file($log_file, FILE_IGNORE_NEW_LINES);
        return array_slice($lines, -$limit);
    }
    
    /**
     * Définit le seuil de requête lente
     * @param float $seconds Seuil en secondes
     */
    public function setSlowQueryThreshold($seconds) {
        $this->slow_query_threshold = $seconds;
    }
}

/**
 * Helper pour initialiser l'optimizer
 */
function getQueryOptimizer($pdo) {
    if (!isset($GLOBALS['query_optimizer'])) {
        $GLOBALS['query_optimizer'] = new QueryOptimizer($pdo);
    }
    return $GLOBALS['query_optimizer'];
}
