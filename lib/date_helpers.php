<?php
/**
 * lib/date_helpers.php - Utilitaires pour gestion des dates et plages
 * Gère les présets, validation, formatage des dates
 */

/**
 * Retourne la plage de dates pour un préset donné
 * @param string $preset 'today', 'last_7d', 'last_30d', 'last_90d', 'this_month', 'this_year'
 * @return array ['start' => 'YYYY-MM-DD', 'end' => 'YYYY-MM-DD']
 */
function getDateRangePreset($preset = 'last_30d') {
    $today = new DateTime('now');
    $start = clone $today;
    $end = clone $today;
    
    switch ($preset) {
        case 'today':
            $start = clone $today;
            $end = clone $today;
            break;
        case 'last_7d':
            $start->modify('-7 days');
            break;
        case 'last_30d':
            $start->modify('-30 days');
            break;
        case 'last_90d':
            $start->modify('-90 days');
            break;
        case 'this_month':
            $start->modify('first day of this month');
            break;
        case 'this_year':
            $start->modify('first day of january');
            break;
        case 'last_month':
            $start->modify('first day of last month');
            $end->modify('last day of last month');
            break;
        default:
            // Si format 'YYYY-MM-DD,YYYY-MM-DD', parser directement
            if (strpos($preset, ',') !== false) {
                [$start_str, $end_str] = explode(',', $preset);
                return [
                    'start' => trim($start_str),
                    'end' => trim($end_str)
                ];
            }
            return ['start' => $start->format('Y-m-d'), 'end' => $end->format('Y-m-d')];
    }
    
    return [
        'start' => $start->format('Y-m-d'),
        'end' => $end->format('Y-m-d')
    ];
}

/**
 * Liste tous les présets disponibles
 * @return array [['key' => 'last_7d', 'label' => 'Derniers 7 jours'], ...]
 */
function getDatePresets() {
    return [
        ['key' => 'today', 'label' => "Aujourd'hui"],
        ['key' => 'last_7d', 'label' => 'Derniers 7 jours'],
        ['key' => 'last_30d', 'label' => 'Derniers 30 jours'],
        ['key' => 'last_90d', 'label' => 'Derniers 90 jours'],
        ['key' => 'this_month', 'label' => 'Ce mois'],
        ['key' => 'last_month', 'label' => 'Le mois dernier'],
        ['key' => 'this_year', 'label' => 'Cette année']
    ];
}

/**
 * Valide et nettoie une date au format 'YYYY-MM-DD'
 * @param string $date_str Date à valider
 * @param bool $is_end_date Si true, la date invalide devient "aujourd'hui"
 * @return string|null Date au format 'YYYY-MM-DD' ou null
 */
function validateAndFormatDate($date_str, $is_end_date = false) {
    if (empty($date_str)) {
        return null;
    }
    
    try {
        $date = DateTime::createFromFormat('Y-m-d', $date_str);
        if ($date === false) {
            // Essayer d'autres formats courants
            $date = DateTime::createFromFormat('d/m/Y', $date_str);
        }
        if ($date === false) {
            return null;
        }
        return $date->format('Y-m-d');
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Construit une clause WHERE pour les dates
 * @param string $column Nom de la colonne (ex: 'v.date')
 * @param string $date_start Date début (YYYY-MM-DD)
 * @param string $date_end Date fin (YYYY-MM-DD)
 * @return string Clause WHERE SQL
 */
function buildDateWhereClause($column, $date_start = null, $date_end = null) {
    $where = '';
    
    if (!empty($date_start) && validateAndFormatDate($date_start)) {
        $where .= " AND $column >= '{$date_start}'";
    }
    
    if (!empty($date_end) && validateAndFormatDate($date_end)) {
        // Ajouter 23:59:59 pour inclure tout le jour
        $where .= " AND $column <= CONCAT('{$date_end}', ' 23:59:59')";
    }
    
    return $where;
}

/**
 * Formate une date pour affichage lisible
 * @param string $date_str Date au format 'YYYY-MM-DD' ou 'YYYY-MM-DD HH:MM:SS'
 * @param string $format Format de sortie (ex: 'd/m/Y' pour '14/12/2025')
 * @return string Date formatée
 */
function formatDateForDisplay($date_str, $format = 'd/m/Y') {
    try {
        $date = DateTime::createFromFormat('Y-m-d H:i:s', $date_str) 
             ?: DateTime::createFromFormat('Y-m-d', $date_str);
        if ($date === false) {
            return $date_str;
        }
        return $date->format($format);
    } catch (Exception $e) {
        return $date_str;
    }
}

/**
 * Obtient le label pour un preset
 * @param string $preset Clé du preset
 * @return string Label lisible
 */
function getPresetLabel($preset) {
    $presets = getDatePresets();
    foreach ($presets as $p) {
        if ($p['key'] === $preset) {
            return $p['label'];
        }
    }
    return 'Plage personnalisée';
}
