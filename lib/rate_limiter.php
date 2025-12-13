<?php
/**
 * KMS Gestion - Rate Limiter
 * 
 * Système de limitation du nombre de requêtes pour prévenir les abus
 */

require_once __DIR__ . '/redis.php';

class RateLimiter
{
    /**
     * Vérifie si une action est autorisée selon le rate limit
     * 
     * @param string $identifier Identifiant unique (IP, user_id, etc.)
     * @param string $action Type d'action (login, api, search, etc.)
     * @param int $maxAttempts Nombre max de tentatives
     * @param int $windowSeconds Fenêtre de temps en secondes
     * @return array ['allowed' => bool, 'remaining' => int, 'reset' => int]
     */
    public static function check(
        string $identifier,
        string $action = 'default',
        int $maxAttempts = 5,
        int $windowSeconds = 60
    ): array {
        $key = self::buildKey($identifier, $action);
        $attempts = RedisManager::increment($key, $windowSeconds);
        
        $allowed = $attempts <= $maxAttempts;
        $remaining = max(0, $maxAttempts - $attempts);
        $reset = time() + $windowSeconds;

        // Logging des abus
        if (!$allowed) {
            self::logAbuse($identifier, $action, $attempts);
        }

        return [
            'allowed' => $allowed,
            'remaining' => $remaining,
            'reset' => $reset,
            'attempts' => $attempts
        ];
    }

    /**
     * Réinitialise le compteur pour un identifiant
     */
    public static function reset(string $identifier, string $action = 'default'): bool
    {
        $key = self::buildKey($identifier, $action);
        return RedisManager::delete($key);
    }

    /**
     * Bloque temporairement un identifiant
     */
    public static function block(string $identifier, string $action, int $duration = 3600): bool
    {
        $blockKey = "block:{$action}:{$identifier}";
        return RedisManager::set($blockKey, true, $duration);
    }

    /**
     * Vérifie si un identifiant est bloqué
     */
    public static function isBlocked(string $identifier, string $action): bool
    {
        $blockKey = "block:{$action}:{$identifier}";
        return RedisManager::exists($blockKey);
    }

    /**
     * Débloquer un identifiant
     */
    public static function unblock(string $identifier, string $action): bool
    {
        $blockKey = "block:{$action}:{$identifier}";
        return RedisManager::delete($blockKey);
    }

    /**
     * Rate limiting spécifique pour les tentatives de connexion
     */
    public static function checkLogin(string $identifier): array
    {
        // Si déjà bloqué (trop de tentatives échouées)
        if (self::isBlocked($identifier, 'login')) {
            return [
                'allowed' => false,
                'remaining' => 0,
                'reset' => time() + 3600,
                'blocked' => true,
                'message' => 'Compte temporairement bloqué. Réessayez dans 1 heure.'
            ];
        }

        // 5 tentatives par minute
        $result = self::check($identifier, 'login', 5, 60);

        // Si limite atteinte, bloquer pour 1 heure
        if (!$result['allowed']) {
            self::block($identifier, 'login', 3600);
            $result['blocked'] = true;
            $result['message'] = 'Trop de tentatives. Compte bloqué pour 1 heure.';
        }

        return $result;
    }

    /**
     * Enregistre une tentative de connexion réussie (reset le compteur)
     */
    public static function loginSuccess(string $identifier): void
    {
        self::reset($identifier, 'login');
        self::unblock($identifier, 'login');
    }

    /**
     * Rate limiting pour les recherches/API
     */
    public static function checkApi(string $identifier): array
    {
        // 100 requêtes par minute pour les API
        return self::check($identifier, 'api', 100, 60);
    }

    /**
     * Rate limiting pour les exports
     */
    public static function checkExport(string $identifier): array
    {
        // 10 exports par heure
        return self::check($identifier, 'export', 10, 3600);
    }

    /**
     * Construit la clé Redis
     */
    private static function buildKey(string $identifier, string $action): string
    {
        return "ratelimit:{$action}:{$identifier}";
    }

    /**
     * Log les tentatives d'abus
     */
    private static function logAbuse(string $identifier, string $action, int $attempts): void
    {
        $logFile = __DIR__ . '/../logs/rate_limit_abuse.log';
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        $logEntry = sprintf(
            "[%s] RATE_LIMIT_EXCEEDED - Action: %s, Identifier: %s, Attempts: %d, IP: %s, UA: %s\n",
            date('Y-m-d H:i:s'),
            $action,
            $identifier,
            $attempts,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        );

        @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Retourne les statistiques de rate limiting pour un identifiant
     */
    public static function getStats(string $identifier): array
    {
        $actions = ['login', 'api', 'export', 'search'];
        $stats = [];

        foreach ($actions as $action) {
            $key = self::buildKey($identifier, $action);
            $blocked = self::isBlocked($identifier, $action);
            
            $stats[$action] = [
                'blocked' => $blocked,
                'key' => $key
            ];
        }

        return $stats;
    }
}
