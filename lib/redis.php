<?php
/**
 * KMS Gestion - Gestionnaire de Cache Redis
 * 
 * Wrapper pour Redis avec fallback en cas d'indisponibilité
 */

class RedisManager
{
    private static ?Redis $instance = null;
    private static bool $enabled = false;
    private static array $fallbackCache = [];

    /**
     * Initialise la connexion Redis
     */
    public static function init(): bool
    {
        if (!class_exists('Redis')) {
            error_log('KMS Cache: Extension Redis non installée - Mode fallback activé');
            return false;
        }

        try {
            self::$instance = new Redis();
            $connected = self::$instance->connect(
                $_ENV['REDIS_HOST'] ?? '127.0.0.1',
                (int)($_ENV['REDIS_PORT'] ?? 6379),
                2 // timeout 2 secondes
            );

            if ($connected && !empty($_ENV['REDIS_PASSWORD'])) {
                self::$instance->auth($_ENV['REDIS_PASSWORD']);
            }

            if ($connected) {
                self::$instance->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);
                self::$instance->setOption(Redis::OPT_PREFIX, 'kms:');
                self::$enabled = true;
                error_log('KMS Cache: Redis connecté avec succès');
                return true;
            }
        } catch (Exception $e) {
            error_log('KMS Cache: Erreur connexion Redis - ' . $e->getMessage());
        }

        return false;
    }

    /**
     * Récupère l'instance Redis
     */
    public static function getInstance(): ?Redis
    {
        if (!self::$enabled && self::$instance === null) {
            self::init();
        }
        return self::$enabled ? self::$instance : null;
    }

    /**
     * Stocke une valeur dans le cache
     */
    public static function set(string $key, $value, int $ttl = 3600): bool
    {
        if (self::$enabled && self::$instance) {
            try {
                return self::$instance->setex($key, $ttl, $value);
            } catch (Exception $e) {
                error_log('KMS Cache: Erreur SET - ' . $e->getMessage());
            }
        }

        // Fallback en mémoire PHP (limité à la requête)
        self::$fallbackCache[$key] = [
            'value' => $value,
            'expires' => time() + $ttl
        ];
        return true;
    }

    /**
     * Récupère une valeur du cache
     */
    public static function get(string $key)
    {
        if (self::$enabled && self::$instance) {
            try {
                $value = self::$instance->get($key);
                return $value !== false ? $value : null;
            } catch (Exception $e) {
                error_log('KMS Cache: Erreur GET - ' . $e->getMessage());
            }
        }

        // Fallback
        if (isset(self::$fallbackCache[$key])) {
            $cached = self::$fallbackCache[$key];
            if ($cached['expires'] > time()) {
                return $cached['value'];
            }
            unset(self::$fallbackCache[$key]);
        }

        return null;
    }

    /**
     * Supprime une clé du cache
     */
    public static function delete(string $key): bool
    {
        if (self::$enabled && self::$instance) {
            try {
                return self::$instance->del($key) > 0;
            } catch (Exception $e) {
                error_log('KMS Cache: Erreur DELETE - ' . $e->getMessage());
            }
        }

        unset(self::$fallbackCache[$key]);
        return true;
    }

    /**
     * Supprime toutes les clés correspondant à un pattern
     */
    public static function deletePattern(string $pattern): int
    {
        if (self::$enabled && self::$instance) {
            try {
                $keys = self::$instance->keys($pattern);
                if (!empty($keys)) {
                    return self::$instance->del($keys);
                }
            } catch (Exception $e) {
                error_log('KMS Cache: Erreur DELETE PATTERN - ' . $e->getMessage());
            }
        }

        return 0;
    }

    /**
     * Incrémente un compteur (pour rate limiting)
     */
    public static function increment(string $key, int $ttl = 60): int
    {
        if (self::$enabled && self::$instance) {
            try {
                $value = self::$instance->incr($key);
                if ($value == 1) {
                    self::$instance->expire($key, $ttl);
                }
                return $value;
            } catch (Exception $e) {
                error_log('KMS Cache: Erreur INCREMENT - ' . $e->getMessage());
            }
        }

        // Fallback simple
        if (!isset(self::$fallbackCache[$key])) {
            self::$fallbackCache[$key] = [
                'value' => 0,
                'expires' => time() + $ttl
            ];
        }
        
        if (self::$fallbackCache[$key]['expires'] > time()) {
            self::$fallbackCache[$key]['value']++;
            return self::$fallbackCache[$key]['value'];
        }

        self::$fallbackCache[$key] = [
            'value' => 1,
            'expires' => time() + $ttl
        ];
        return 1;
    }

    /**
     * Vérifie si une clé existe
     */
    public static function exists(string $key): bool
    {
        if (self::$enabled && self::$instance) {
            try {
                return self::$instance->exists($key) > 0;
            } catch (Exception $e) {
                error_log('KMS Cache: Erreur EXISTS - ' . $e->getMessage());
            }
        }

        return isset(self::$fallbackCache[$key]) && 
               self::$fallbackCache[$key]['expires'] > time();
    }

    /**
     * Vide tout le cache
     */
    public static function flush(): bool
    {
        if (self::$enabled && self::$instance) {
            try {
                return self::$instance->flushDB();
            } catch (Exception $e) {
                error_log('KMS Cache: Erreur FLUSH - ' . $e->getMessage());
            }
        }

        self::$fallbackCache = [];
        return true;
    }

    /**
     * Retourne le statut de Redis
     */
    public static function isEnabled(): bool
    {
        return self::$enabled;
    }

    /**
     * Statistiques du cache
     */
    public static function stats(): array
    {
        if (self::$enabled && self::$instance) {
            try {
                return self::$instance->info();
            } catch (Exception $e) {
                error_log('KMS Cache: Erreur STATS - ' . $e->getMessage());
            }
        }

        return [
            'mode' => 'fallback',
            'keys' => count(self::$fallbackCache)
        ];
    }
}

// Auto-initialisation
RedisManager::init();
