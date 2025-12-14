<?php
/**
 * Sauvegarde & Restore des filtres utilisateur
 * Stored in user session avec clé unique par page
 */

function saveUserFilters(string $pageKey, array $filters): void {
    if (!isset($_SESSION['user_filters'])) {
        $_SESSION['user_filters'] = [];
    }
    $_SESSION['user_filters'][$pageKey] = $filters;
}

function getUserFilters(string $pageKey): array {
    return $_SESSION['user_filters'][$pageKey] ?? [];
}

function clearUserFilters(string $pageKey): void {
    if (isset($_SESSION['user_filters'][$pageKey])) {
        unset($_SESSION['user_filters'][$pageKey]);
    }
}

/**
 * Construire un WHERE clause de recherche texte
 * Cherche dans plusieurs colonnes (flexible)
 */
function buildSearchWhereClause(string $searchTerm, array $searchColumns): array {
    if (empty($searchTerm) || empty($searchColumns)) {
        return ['', []];
    }
    
    $searchTerm = '%' . trim($searchTerm) . '%';
    $conditions = [];
    $params = [];
    
    foreach ($searchColumns as $col) {
        $conditions[] = "$col LIKE ?";
        $params[] = $searchTerm;
    }
    
    $where = '(' . implode(' OR ', $conditions) . ')';
    return [$where, $params];
}

// NOTE: getPaginationParams(), getPaginationUrl(), et renderPaginationControls()
// sont maintenant dans lib/pagination.php (Phase 3.1 - version améliorée)