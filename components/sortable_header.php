<?php
/**
 * Composant réutilisable: En-tête de colonne triable
 * Usage: <?= renderSortableHeader('Date', 'date', $sortBy, $sortDir, $_GET) ?>
 */
function renderSortableHeader(string $label, string $fieldKey, string $currentSortBy, string $currentSortDir, array $queryParams): string {
    $newDir = ($currentSortBy === $fieldKey && $currentSortDir === 'desc') ? 'asc' : 'desc';
    $query = array_merge($queryParams, ['sort_by' => $fieldKey, 'sort_dir' => $newDir]);
    $url = '?' . http_build_query($query);
    $icon = '';
    
    if ($currentSortBy === $fieldKey) {
        $icon = '<i class="bi ' . ($currentSortDir === 'desc' ? 'bi-arrow-down' : 'bi-arrow-up') . '"></i>';
    }
    
    return sprintf(
        '<a href="%s" class="text-decoration-none text-dark">%s %s</a>',
        htmlspecialchars($url),
        htmlspecialchars($label),
        $icon
    );
}

/**
 * Composer une URL de paginnation/filtre avec tous les paramètres
 */
function buildFilterUrl(array $filters, array $additionalParams = []): string {
    $params = array_merge($filters, $additionalParams);
    // Supprimer les paramètres vides
    $params = array_filter($params, fn($v) => $v !== '' && $v !== '0' && $v !== null);
    return '?' . http_build_query($params);
}

/**
 * Afficher les badges de filtres actifs
 */
function renderActiveFilterBadges(array $activeFilters): string {
    if (empty($activeFilters)) {
        return '';
    }
    
    $html = '<div class="col-12 mt-2 pt-2 border-top"><small class="text-muted d-block mb-2">Filtres actifs:</small><div class="d-flex gap-2 flex-wrap">';
    
    foreach ($activeFilters as $label => $value) {
        $html .= sprintf(
            '<span class="badge bg-info text-dark"><strong>%s</strong>: %s%s</span>',
            htmlspecialchars($label),
            htmlspecialchars(substr($value, 0, 25)),
            strlen($value) > 25 ? '...' : ''
        );
    }
    
    $html .= '</div></div>';
    return $html;
}
