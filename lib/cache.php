<?php
/**
 * lib/cache.php - Système de cache avec Redis + fallback fichiers
 * Gère le caching pour KPIs, requêtes fréquentes, et données statiques
 */

class CacheManager {
    private $redis = null;
    private $cache_dir = null;
    private $ttl = 3600; // TTL par défaut: 1 heure
    private $use_redis = false;
    
    /**
     * Initialiser le cache
     * @param array $config Configuration Redis ['host' => '127.0.0.1', 'port' => 6379]
     * @param string $cache_dir Répertoire pour cache fichiers
     */
    public function __construct($config = [], $cache_dir = null) {
        $this->cache_dir = $cache_dir ?? __DIR__ . '/../cache';
        
        // Créer répertoire cache s'il n'existe pas
        if (!is_dir($this->cache_dir)) {
            @mkdir($this->cache_dir, 0755, true);
        }
        
        // Tentative connexion Redis (optionnel)
        if (!empty($config) && extension_loaded('redis')) {
            try {
                $this->redis = new Redis();
                $host = $config['host'] ?? 'localhost';
                $port = $config['port'] ?? 6379;
                $timeout = $config['timeout'] ?? 2; // Timeout court pour ne pas bloquer
                
                if ($this->redis->connect($host, $port, $timeout)) {
                    $this->use_redis = true;
                }
            } catch (Exception $e) {
                $this->redis = null;
                $this->use_redis = false;
            }
        }
    }
    
    /**
     * Récupère une valeur du cache
     * @param string $key Clé du cache
     * @return mixed Valeur ou null si non trouvée/expirée
     */
    public function get($key) {
        // Essayer Redis d'abord
        if ($this->use_redis && $this->redis) {
            try {
                $value = $this->redis->get($this->getKey($key));
                if ($value !== false) {
                    return unserialize($value);
                }
            } catch (Exception $e) {
                // Fallback silencieux
            }
        }
        
        // Fallback: fichier
        return $this->getFromFile($key);
    }
    
    /**
     * Stocke une valeur dans le cache
     * @param string $key Clé du cache
     * @param mixed $value Valeur à stocker
     * @param int $ttl Durée de vie en secondes
     * @return bool Succès
     */
    public function set($key, $value, $ttl = null) {
        $ttl = $ttl ?? $this->ttl;
        $serialized = serialize($value);
        
        $success = false;
        
        // Redis d'abord
        if ($this->use_redis && $this->redis) {
            try {
                $this->redis->setex($this->getKey($key), $ttl, $serialized);
                $success = true;
            } catch (Exception $e) {
                // Continue vers fichier
            }
        }
        
        // Aussi stocker en fichier (pour persistance)
        return $this->setToFile($key, $serialized, $ttl) || $success;
    }
    
    /**
     * Supprime une clé du cache
     * @param string $key Clé à supprimer
     * @return bool Succès
     */
    public function delete($key) {
        $success = false;
        
        if ($this->use_redis && $this->redis) {
            try {
                $this->redis->del($this->getKey($key));
                $success = true;
            } catch (Exception $e) {
                // Continue
            }
        }
        
        // Fichier
        $file = $this->getFilePath($key);
        if (file_exists($file)) {
            unlink($file);
            $success = true;
        }
        
        return $success;
    }
    
    /**
     * Vide complètement le cache
     * @return bool Succès
     */
    public function flush() {
        $success = false;
        
        if ($this->use_redis && $this->redis) {
            try {
                // Ne vider que les clés de cette app (pattern)
                $keys = $this->redis->keys('kms_*');
                if (!empty($keys)) {
                    $this->redis->del($keys);
                }
                $success = true;
            } catch (Exception $e) {
                // Continue
            }
        }
        
        // Vider répertoire fichiers
        $files = glob($this->cache_dir . '/*.cache');
        foreach ($files as $file) {
            @unlink($file);
        }
        
        return true;
    }
    
    /**
     * Récupère les statistiques du cache
     * @return array ['size' => bytes, 'files' => count, 'redis' => bool]
     */
    public function getStats() {
        $files = glob($this->cache_dir . '/*.cache');
        $size = 0;
        
        foreach ($files as $file) {
            $size += filesize($file);
        }
        
        return [
            'size' => $size,
            'size_mb' => round($size / (1024 * 1024), 2),
            'files' => count($files),
            'redis_connected' => $this->use_redis,
            'cache_dir' => $this->cache_dir
        ];
    }
    
    /**
     * Récupère une valeur ou l'exécute et la cache
     * Pattern: remember() - très utile pour requêtes
     * @param string $key Clé du cache
     * @param callable $callback Fonction à exécuter si non cachée
     * @param int $ttl TTL en secondes
     * @return mixed Valeur cachée ou résultat du callback
     */
    public function remember($key, callable $callback, $ttl = null) {
        $value = $this->get($key);
        
        if ($value === null) {
            $value = $callback();
            if ($value !== null) {
                $this->set($key, $value, $ttl);
            }
        }
        
        return $value;
    }
    
    // ==================== PRIVÉ ====================
    
    private function getKey($key) {
        // Préfixe pour éviter collisions
        return 'kms_' . $key;
    }
    
    private function getFilePath($key) {
        // Remplacer caractères spéciaux
        $safe_key = preg_replace('/[^a-z0-9_\-]/i', '_', $key);
        return $this->cache_dir . '/' . $safe_key . '.cache';
    }
    
    private function getFromFile($key) {
        $file = $this->getFilePath($key);
        
        if (!file_exists($file)) {
            return null;
        }
        
        // Vérifier expiration
        $mtime = filemtime($file);
        if ($mtime + $this->ttl < time()) {
            @unlink($file);
            return null;
        }
        
        // Lire et désérialiser
        try {
            $content = file_get_contents($file);
            return unserialize($content);
        } catch (Exception $e) {
            return null;
        }
    }
    
    private function setToFile($key, $serialized, $ttl) {
        $file = $this->getFilePath($key);
        
        try {
            return file_put_contents($file, $serialized) !== false;
        } catch (Exception $e) {
            return false;
        }
    }
}

/**
 * Singleton global pour cache
 */
$GLOBALS['cache_manager'] = $GLOBALS['cache_manager'] ?? new CacheManager();

/**
 * Fonction helper pour accéder au cache
 * @return CacheManager
 */
function getCache() {
    return $GLOBALS['cache_manager'];
}

/**
 * Helper: Récupère ou exécute avec cache
 * @param string $key Clé cache
 * @param callable $callback Fonction
 * @param int $ttl Durée de vie
 * @return mixed Résultat
 */
function cached($key, callable $callback, $ttl = 3600) {
    return getCache()->remember($key, $callback, $ttl);
}
