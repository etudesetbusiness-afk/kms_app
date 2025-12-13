<?php
/**
 * KMS Gestion - Helpers de Cache
 * 
 * Fonctions utilitaires pour le cache des données fréquemment accédées
 */

require_once __DIR__ . '/redis.php';

class CacheHelper
{
    private const TTL_COURT = 300;      // 5 minutes
    private const TTL_MOYEN = 1800;     // 30 minutes
    private const TTL_LONG = 3600;      // 1 heure
    private const TTL_TRES_LONG = 86400; // 24 heures

    /**
     * Cache les résultats d'une requête SQL
     */
    public static function remember(string $key, callable $callback, int $ttl = self::TTL_MOYEN)
    {
        $cached = RedisManager::get($key);
        
        if ($cached !== null) {
            return $cached;
        }

        $result = $callback();
        RedisManager::set($key, $result, $ttl);
        
        return $result;
    }

    /**
     * Cache la liste des produits actifs
     */
    public static function getProduits(PDO $pdo, ?string $recherche = null): array
    {
        if ($recherche) {
            // Ne pas cacher les recherches
            $stmt = $pdo->prepare("
                SELECT * FROM produits 
                WHERE actif = 1 AND (code_produit LIKE ? OR designation LIKE ?)
                ORDER BY designation ASC
            ");
            $search = "%{$recherche}%";
            $stmt->execute([$search, $search]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return self::remember('produits:actifs', function() use ($pdo) {
            $stmt = $pdo->query("
                SELECT p.*, f.nom as famille_nom, sc.nom as sous_categorie_nom
                FROM produits p
                LEFT JOIN familles_produits f ON p.famille_id = f.id
                LEFT JOIN sous_categories_produits sc ON p.sous_categorie_id = sc.id
                WHERE p.actif = 1
                ORDER BY p.designation ASC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }, self::TTL_LONG);
    }

    /**
     * Cache un produit spécifique
     */
    public static function getProduit(PDO $pdo, int $id): ?array
    {
        return self::remember("produit:{$id}", function() use ($pdo, $id) {
            $stmt = $pdo->prepare("
                SELECT p.*, f.nom as famille_nom, sc.nom as sous_categorie_nom,
                       fr.nom as fournisseur_nom
                FROM produits p
                LEFT JOIN familles_produits f ON p.famille_id = f.id
                LEFT JOIN sous_categories_produits sc ON p.sous_categorie_id = sc.id
                LEFT JOIN fournisseurs fr ON p.fournisseur_id = fr.id
                WHERE p.id = ?
            ");
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        }, self::TTL_LONG);
    }

    /**
     * Invalide le cache d'un produit
     */
    public static function invalidateProduit(int $id): void
    {
        RedisManager::delete("produit:{$id}");
        RedisManager::delete("produits:actifs");
        RedisManager::delete("produits:ruptures");
    }

    /**
     * Cache les clients actifs
     */
    public static function getClients(PDO $pdo, ?string $type = null): array
    {
        $cacheKey = $type ? "clients:type:{$type}" : "clients:tous";
        
        return self::remember($cacheKey, function() use ($pdo, $type) {
            $sql = "
                SELECT c.*, tc.libelle as type_libelle
                FROM clients c
                LEFT JOIN types_client tc ON c.type_client_id = tc.id
            ";
            
            if ($type) {
                $sql .= " WHERE tc.code = ?";
                $stmt = $pdo->prepare($sql . " ORDER BY c.nom ASC");
                $stmt->execute([$type]);
            } else {
                $stmt = $pdo->query($sql . " ORDER BY c.nom ASC");
            }
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }, self::TTL_MOYEN);
    }

    /**
     * Invalide le cache des clients
     */
    public static function invalidateClients(): void
    {
        RedisManager::deletePattern("clients:*");
    }

    /**
     * Cache les permissions d'un utilisateur
     */
    public static function getUserPermissions(PDO $pdo, int $userId): array
    {
        return self::remember("user:{$userId}:permissions", function() use ($pdo, $userId) {
            $stmt = $pdo->prepare("
                SELECT DISTINCT p.code
                FROM permissions p
                JOIN role_permission rp ON p.id = rp.permission_id
                JOIN utilisateur_role ur ON rp.role_id = ur.role_id
                WHERE ur.utilisateur_id = ?
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        }, self::TTL_TRES_LONG);
    }

    /**
     * Invalide les permissions d'un utilisateur
     */
    public static function invalidateUserPermissions(int $userId): void
    {
        RedisManager::delete("user:{$userId}:permissions");
    }

    /**
     * Cache les statistiques du dashboard
     */
    public static function getDashboardStats(PDO $pdo, string $date = null): array
    {
        $date = $date ?? date('Y-m-d');
        
        return self::remember("dashboard:stats:{$date}", function() use ($pdo, $date) {
            $stats = [];
            
            // Ventes du jour
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as nb_ventes, COALESCE(SUM(montant_total_ttc), 0) as ca_jour
                FROM ventes WHERE DATE(date_vente) = ?
            ");
            $stmt->execute([$date]);
            $stats['ventes'] = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Devis du jour
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM devis WHERE DATE(date_devis) = ?");
            $stmt->execute([$date]);
            $stats['nb_devis'] = $stmt->fetchColumn();
            
            // Visiteurs showroom
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM visiteurs_showroom WHERE date_visite = ?");
            $stmt->execute([$date]);
            $stats['nb_visiteurs'] = $stmt->fetchColumn();
            
            // Encaissements
            $stmt = $pdo->prepare("
                SELECT COALESCE(SUM(montant), 0)
                FROM journal_caisse
                WHERE DATE(date_operation) = ? AND sens = 'RECETTE'
            ");
            $stmt->execute([$date]);
            $stats['encaissements'] = $stmt->fetchColumn();
            
            return $stats;
        }, self::TTL_COURT); // Cache court pour les stats du jour
    }

    /**
     * Cache les produits en rupture de stock
     */
    public static function getProduitsRupture(PDO $pdo): array
    {
        return self::remember("produits:ruptures", function() use ($pdo) {
            $stmt = $pdo->query("
                SELECT p.*, f.nom as famille_nom
                FROM produits p
                LEFT JOIN familles_produits f ON p.famille_id = f.id
                WHERE p.actif = 1 AND p.stock_actuel <= p.seuil_alerte
                ORDER BY p.stock_actuel ASC, p.designation ASC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }, self::TTL_COURT);
    }

    /**
     * Cache les familles de produits
     */
    public static function getFamillesProduits(PDO $pdo): array
    {
        return self::remember("familles:produits", function() use ($pdo) {
            $stmt = $pdo->query("SELECT * FROM familles_produits ORDER BY nom ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }, self::TTL_TRES_LONG);
    }

    /**
     * Cache les modes de paiement
     */
    public static function getModesPaiement(PDO $pdo): array
    {
        return self::remember("modes:paiement", function() use ($pdo) {
            $stmt = $pdo->query("SELECT * FROM modes_paiement ORDER BY libelle ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }, self::TTL_TRES_LONG);
    }

    /**
     * Cache les canaux de vente
     */
    public static function getCanauxVente(PDO $pdo): array
    {
        return self::remember("canaux:vente", function() use ($pdo) {
            $stmt = $pdo->query("SELECT * FROM canaux_vente ORDER BY libelle ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }, self::TTL_TRES_LONG);
    }

    /**
     * Invalide tout le cache
     */
    public static function flush(): bool
    {
        return RedisManager::flush();
    }

    /**
     * Récupère les statistiques du cache
     */
    public static function getStats(): array
    {
        return RedisManager::stats();
    }

    /**
     * Vérifie si Redis est actif
     */
    public static function isEnabled(): bool
    {
        return RedisManager::isEnabled();
    }

    /**
     * Warmup: pré-charge les données fréquemment utilisées
     */
    public static function warmup(PDO $pdo): array
    {
        $cached = [];
        
        $cached['produits'] = self::getProduits($pdo);
        $cached['familles'] = self::getFamillesProduits($pdo);
        $cached['modes_paiement'] = self::getModesPaiement($pdo);
        $cached['canaux_vente'] = self::getCanauxVente($pdo);
        $cached['clients'] = self::getClients($pdo);
        
        return [
            'success' => true,
            'items_cached' => count($cached),
            'keys' => array_keys($cached)
        ];
    }
}
