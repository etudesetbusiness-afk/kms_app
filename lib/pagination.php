<?php
/**
 * lib/pagination.php - Système de pagination avancé
 * Gère la pagination avec persistance des filtres
 */

/**
 * Récupère les paramètres de pagination
 * @param array $get $_GET array
 * @param int $total_count Nombre total d'éléments
 * @param int $default_per_page Résultats par page par défaut (25)
 * @return array ['page' => int, 'per_page' => int, 'offset' => int, 'total_pages' => int]
 */
function getPaginationParams($get, $total_count, $default_per_page = 25) {
    // Page actuelle (min 1)
    $page = max(1, (int)($get['page'] ?? 1));
    
    // Résultats par page (whitelist)
    $allowed_per_page = [10, 25, 50, 100];
    $per_page_input = $get['per_page'] ?? $default_per_page;
    $per_page = in_array((int)$per_page_input, $allowed_per_page) 
        ? (int)$per_page_input
        : $default_per_page;
    
    // Total de pages
    $total_pages = max(1, ceil($total_count / $per_page));
    
    // Vérifier que la page n'est pas hors limites
    if ($page > $total_pages) {
        $page = $total_pages;
    }
    
    // Offset pour la requête SQL
    $offset = ($page - 1) * $per_page;
    
    return [
        'page' => $page,
        'per_page' => $per_page,
        'offset' => $offset,
        'total_pages' => $total_pages,
        'total_count' => $total_count
    ];
}

/**
 * Construit une URL de pagination avec filtres persistants
 * @param array $get $_GET array
 * @param int $page Nouvelle page
 * @param int $per_page Résultats par page
 * @return string URL query string
 */
function buildPaginationUrl($get, $page = 1, $per_page = null) {
    $params = $get;
    $params['page'] = $page;
    if ($per_page) $params['per_page'] = $per_page;
    
    return http_build_query($params);
}

/**
 * Génère les contrôles de pagination en HTML
 * @param array $pagination Résultats de getPaginationParams()
 * @param array $get $_GET array
 * @param array $options Options de rendu
 * @return string HTML
 */
function renderPaginationControls($pagination, $get, $options = []) {
    $page = $pagination['page'];
    $total_pages = $pagination['total_pages'];
    $per_page = $pagination['per_page'];
    $total_count = $pagination['total_count'];
    
    $start_item = ($page - 1) * $per_page + 1;
    $end_item = min($page * $per_page, $total_count);
    
    $html = '<div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">';
    
    // Affichage du compteur
    $html .= '<small class="text-muted">';
    $html .= "Résultats <strong>$start_item</strong> à <strong>$end_item</strong> sur <strong>$total_count</strong>";
    $html .= '</small>';
    
    // Sélecteur résultats par page
    $html .= '<div class="d-flex gap-2 align-items-center">';
    $html .= '<small class="text-muted">Par page:</small>';
    $html .= '<select class="form-select form-select-sm" style="width: auto;" onchange="window.location.href=\'?' . buildPaginationUrl($get, 1) . '&per_page=\' + this.value">';
    foreach ([10, 25, 50, 100] as $pp) {
        $selected = $pp === $per_page ? 'selected' : '';
        $html .= "<option value=\"$pp\" $selected>$pp</option>";
    }
    $html .= '</select>';
    $html .= '</div>';
    
    // Navigation pagination
    $html .= '<nav aria-label="Pagination">';
    $html .= '<ul class="pagination pagination-sm mb-0">';
    
    // Bouton Précédent
    if ($page > 1) {
        $prev_url = buildPaginationUrl($get, $page - 1, $per_page);
        $html .= '<li class="page-item"><a class="page-link" href="?' . $prev_url . '">← Précédent</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">← Précédent</span></li>';
    }
    
    // Pages numérotées (avec smart pagination)
    $start_page = max(1, $page - 2);
    $end_page = min($total_pages, $page + 2);
    
    if ($start_page > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="?' . buildPaginationUrl($get, 1, $per_page) . '">1</a></li>';
        if ($start_page > 2) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }
    
    for ($p = $start_page; $p <= $end_page; $p++) {
        if ($p === $page) {
            $html .= "<li class=\"page-item active\"><span class=\"page-link\">$p</span></li>";
        } else {
            $page_url = buildPaginationUrl($get, $p, $per_page);
            $html .= "<li class=\"page-item\"><a class=\"page-link\" href=\"?$page_url\">$p</a></li>";
        }
    }
    
    if ($end_page < $total_pages) {
        if ($end_page < $total_pages - 1) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
        $html .= '<li class="page-item"><a class="page-link" href="?' . buildPaginationUrl($get, $total_pages, $per_page) . '">' . $total_pages . '</a></li>';
    }
    
    // Bouton Suivant
    if ($page < $total_pages) {
        $next_url = buildPaginationUrl($get, $page + 1, $per_page);
        $html .= '<li class="page-item"><a class="page-link" href="?' . $next_url . '">Suivant →</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">Suivant →</span></li>';
    }
    
    $html .= '</ul></nav>';
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Ajoute une LIMIT clause à une requête SQL pour la pagination
 * @param int $offset Offset
 * @param int $per_page Résultats par page
 * @return string SQL LIMIT clause
 */
function getPaginationLimitClause($offset, $per_page) {
    return "LIMIT $offset, $per_page";
}
