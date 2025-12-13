<?php
require_once __DIR__ . '/../../security.php';

/**
 * Retourne les catégories actives (triées par ordre puis nom).
 */
function catalogue_get_categories(): array
{
    global $pdo;
    $sql = "
        SELECT id, nom, slug
        FROM catalogue_categories
        WHERE actif = 1
        ORDER BY ordre ASC, nom ASC
    ";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll() ?: [];
}

/**
 * Retourne une page de produits catalogue (lecture seule).
 * @return array{items: array<int,array>, total:int, has_more:bool}
 */
function catalogue_get_products(array $filters = [], int $page = 1, int $pageSize = 12): array
{
    global $pdo;

    $page = max(1, $page);
    $pageSize = max(1, min($pageSize, 50));
    $offset = ($page - 1) * $pageSize;

    $where = ['p.actif = 1', 'c.actif = 1'];
    $params = [];

    if (!empty($filters['q'])) {
        $q = '%' . $filters['q'] . '%';
        $where[] = '(p.designation LIKE ? OR p.code LIKE ?)';
        $params[] = $q;
        $params[] = $q;
    }

    if (!empty($filters['categorie_id'])) {
        $where[] = 'p.categorie_id = ?';
        $params[] = (int)$filters['categorie_id'];
    }

    $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    // Total pour savoir s'il reste des pages
    $countSql = "
        SELECT COUNT(*) AS total
        FROM catalogue_produits p
        JOIN catalogue_categories c ON c.id = p.categorie_id
        $whereSql
    ";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $total = (int)($countStmt->fetchColumn() ?: 0);

    $sql = "
        SELECT p.*, c.nom AS categorie_nom, c.slug AS categorie_slug
        FROM catalogue_produits p
        JOIN catalogue_categories c ON c.id = p.categorie_id
        $whereSql
        ORDER BY p.designation ASC
        LIMIT $pageSize OFFSET $offset
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $items = $stmt->fetchAll() ?: [];
    $hasMore = $total > ($offset + $pageSize);

    return [
        'items'    => $items,
        'total'    => $total,
        'has_more' => $hasMore,
    ];
}

/**
 * Suggestions de recherche (autocomplete).
 */
function catalogue_search_suggestions(string $q, int $limit = 10): array
{
    global $pdo;

    if (mb_strlen($q) < 2) {
        return [];
    }

    $like = '%' . $q . '%';
    $limit = max(1, min($limit, 20));

    $sql = "
        SELECT p.id, p.slug, p.designation, p.code, p.prix_unite, p.image_principale
        FROM catalogue_produits p
        JOIN catalogue_categories c ON c.id = p.categorie_id
        WHERE p.actif = 1 AND c.actif = 1
          AND (p.designation LIKE ? OR p.code LIKE ?)
        ORDER BY p.designation ASC
        LIMIT $limit
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$like, $like]);

    return $stmt->fetchAll() ?: [];
}

/**
 * Fiche produit par slug.
 */
function catalogue_get_product_by_slug(string $slug): ?array
{
    global $pdo;
    $sql = "
        SELECT p.*, c.nom AS categorie_nom, c.slug AS categorie_slug
        FROM catalogue_produits p
        JOIN catalogue_categories c ON c.id = p.categorie_id
        WHERE p.slug = ? AND p.actif = 1 AND c.actif = 1
        LIMIT 1
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$slug]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Produits associés (même catégorie, exclus l'ID courant).
 */
function catalogue_get_related(int $categorieId, int $excludeId, int $limit = 12): array
{
    global $pdo;
    $limit = max(1, min($limit, 20));
    $sql = "
        SELECT p.id, p.slug, p.designation, p.code, p.prix_unite, p.image_principale
        FROM catalogue_produits p
        WHERE p.actif = 1 AND p.categorie_id = ? AND p.id <> ?
        ORDER BY p.designation ASC
        LIMIT $limit
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$categorieId, $excludeId]);
    return $stmt->fetchAll() ?: [];
}

/**
 * Chemin image (retourne un placeholder si absent).
 */
function catalogue_image_path(?string $path): string
{
    if ($path && file_exists($_SERVER['DOCUMENT_ROOT'] . $path)) {
        return $path;
    }
    return url_for('assets/img/logo-kms.png');
}
