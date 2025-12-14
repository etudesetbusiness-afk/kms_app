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

/**
 * Extraire & valider les paramètres de pagination
 */
function getPaginationParams(): array {
    $page = (int)($_GET['page'] ?? 1);
    $perPage = (int)($_GET['per_page'] ?? 25);
    
    // Valider
    if ($page < 1) $page = 1;
    if ($perPage < 10 || $perPage > 100) $perPage = 25;
    
    $offset = ($page - 1) * $perPage;
    
    return [
        'page' => $page,
        'perPage' => $perPage,
        'offset' => $offset
    ];
}

/**
 * Formatter les URLs de pagination
 */
function getPaginationUrl(string $baseUrl, int $page): string {
    $separator = strpos($baseUrl, '?') !== false ? '&' : '?';
    return $baseUrl . $separator . 'page=' . $page;
}

/**
 * Construire le HTML pour les liens de pagination
 */
function renderPaginationControls(int $currentPage, int $totalPages, string $baseUrl): string {
    if ($totalPages <= 1) return '';
    
    $html = '<nav aria-label="Pagination" class="d-flex justify-content-center mt-4"><ul class="pagination">';
    
    // Lien précédent
    if ($currentPage > 1) {
        $prevUrl = getPaginationUrl($baseUrl, $currentPage - 1);
        $html .= '<li class="page-item"><a class="page-link" href="' . htmlspecialchars($prevUrl) . '">← Précédent</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">← Précédent</span></li>';
    }
    
    // Numéros de pages (afficher max 5 pages)
    $startPage = max(1, $currentPage - 2);
    $endPage = min($totalPages, $currentPage + 2);
    
    if ($startPage > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . htmlspecialchars(getPaginationUrl($baseUrl, 1)) . '">1</a></li>';
        if ($startPage > 2) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }
    
    for ($i = $startPage; $i <= $endPage; $i++) {
        if ($i === $currentPage) {
            $html .= '<li class="page-item active"><span class="page-link">' . $i . '</span></li>';
        } else {
            $url = getPaginationUrl($baseUrl, $i);
            $html .= '<li class="page-item"><a class="page-link" href="' . htmlspecialchars($url) . '">' . $i . '</a></li>';
        }
    }
    
    if ($endPage < $totalPages) {
        if ($endPage < $totalPages - 1) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
        $html .= '<li class="page-item"><a class="page-link" href="' . htmlspecialchars(getPaginationUrl($baseUrl, $totalPages)) . '">' . $totalPages . '</a></li>';
    }
    
    // Lien suivant
    if ($currentPage < $totalPages) {
        $nextUrl = getPaginationUrl($baseUrl, $currentPage + 1);
        $html .= '<li class="page-item"><a class="page-link" href="' . htmlspecialchars($nextUrl) . '">Suivant →</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">Suivant →</span></li>';
    }
    
    $html .= '</ul></nav>';
    return $html;
}
