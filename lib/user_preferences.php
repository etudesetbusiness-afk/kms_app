<?php
/**
 * lib/user_preferences.php - Gestion des préférences utilisateur
 * Sauvegarde les préférences de tri, pagination, et filtres par page
 */

/**
 * Récupère les préférences de l'utilisateur pour une page
 * @param int $user_id ID utilisateur
 * @param string $page_name Nom de la page (ventes, livraisons, etc.)
 * @return array Préférences [sort_by, sort_dir, per_page, ...]
 */
function getUserPagePreferences($user_id, $page_name) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT sort_by, sort_dir, per_page, remember_filters, default_date_range
        FROM user_preferences
        WHERE utilisateur_id = ? AND page_name = ?
        LIMIT 1
    ");
    $stmt->execute([$user_id, $page_name]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Retourner les prefs ou les valeurs par défaut
    return $result ? $result : [
        'sort_by' => 'date',
        'sort_dir' => 'desc',
        'per_page' => 25,
        'remember_filters' => 1,
        'default_date_range' => null
    ];
}

/**
 * Sauvegarde les préférences de l'utilisateur pour une page
 * @param int $user_id ID utilisateur
 * @param string $page_name Nom de la page
 * @param array $preferences Préférences à sauvegarder
 * @return bool Succès
 */
function saveUserPagePreferences($user_id, $page_name, $preferences) {
    global $pdo;
    
    // Valider les données
    $sort_by = trim($preferences['sort_by'] ?? 'date');
    $sort_dir = in_array($preferences['sort_dir'] ?? 'desc', ['asc', 'desc']) ? $preferences['sort_dir'] : 'desc';
    $per_page = in_array((int)($preferences['per_page'] ?? 25), [10, 25, 50, 100]) ? (int)$preferences['per_page'] : 25;
    $remember_filters = (int)($preferences['remember_filters'] ?? 1);
    $default_date_range = $preferences['default_date_range'] ?? null;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO user_preferences 
            (utilisateur_id, page_name, sort_by, sort_dir, per_page, remember_filters, default_date_range)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            sort_by = ?,
            sort_dir = ?,
            per_page = ?,
            remember_filters = ?,
            default_date_range = ?,
            updated_at = CURRENT_TIMESTAMP
        ");
        
        return $stmt->execute([
            $user_id, $page_name, $sort_by, $sort_dir, $per_page, $remember_filters, $default_date_range,
            $sort_by, $sort_dir, $per_page, $remember_filters, $default_date_range
        ]);
    } catch (Exception $e) {
        error_log("Erreur saveUserPagePreferences: " . $e->getMessage());
        return false;
    }
}

/**
 * Applique les préférences aux paramètres GET
 * Les valeurs GET ont priorité sur les préférences
 * @param array $get $_GET array
 * @param array $prefs Préférences utilisateur
 * @param array $allowed_sort_columns Colonnes de tri autorisées
 * @return array Paramètres fusionnés
 */
function mergePreferencesWithGet($get, $prefs, $allowed_sort_columns = ['date', 'client', 'montant']) {
    // Utiliser GET si fourni, sinon utiliser les prefs
    $sort_by = $get['sort_by'] ?? $prefs['sort_by'] ?? 'date';
    $sort_dir = $get['sort_dir'] ?? $prefs['sort_dir'] ?? 'desc';
    $per_page = $get['per_page'] ?? $prefs['per_page'] ?? 25;
    
    // Valider sort_by
    if (!in_array($sort_by, $allowed_sort_columns)) {
        $sort_by = 'date';
    }
    
    // Valider sort_dir
    $sort_dir = in_array($sort_dir, ['asc', 'desc']) ? $sort_dir : 'desc';
    
    // Valider per_page
    $per_page = in_array((int)$per_page, [10, 25, 50, 100]) ? (int)$per_page : 25;
    
    return [
        'sort_by' => $sort_by,
        'sort_dir' => $sort_dir,
        'per_page' => $per_page
    ];
}

/**
 * Met à jour les préférences depuis les paramètres GET
 * Sauvegarde automatiquement les changements
 * @param int $user_id ID utilisateur
 * @param string $page_name Nom de la page
 * @param array $get $_GET array
 * @param array $allowed_columns Colonnes autorisées
 * @return array Paramètres appliqués
 */
function updateUserPreferencesFromGet($user_id, $page_name, $get, $allowed_columns = ['date', 'client', 'montant']) {
    // Charger les prefs actuelles
    $prefs = getUserPagePreferences($user_id, $page_name);
    
    // Vérifier si GET contient des changements de tri ou pagination
    if (!empty($get['sort_by']) || !empty($get['sort_dir']) || !empty($get['per_page'])) {
        $new_prefs = [
            'sort_by' => $get['sort_by'] ?? $prefs['sort_by'],
            'sort_dir' => $get['sort_dir'] ?? $prefs['sort_dir'],
            'per_page' => $get['per_page'] ?? $prefs['per_page'],
            'remember_filters' => $prefs['remember_filters'] ?? 1,
            'default_date_range' => $prefs['default_date_range'] ?? null
        ];
        
        // Sauvegarder les nouvelles prefs
        saveUserPagePreferences($user_id, $page_name, $new_prefs);
        
        return mergePreferencesWithGet($get, $new_prefs, $allowed_columns);
    }
    
    // Pas de changement, utiliser les prefs existantes
    return mergePreferencesWithGet($get, $prefs, $allowed_columns);
}

/**
 * Récupère les statistiques des préférences par page
 * Utile pour le debug et monitoring
 * @param int $user_id ID utilisateur
 * @return array Liste des pages et leurs préférences
 */
function getUserAllPreferences($user_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT page_name, sort_by, sort_dir, per_page, updated_at
        FROM user_preferences
        WHERE utilisateur_id = ?
        ORDER BY updated_at DESC
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Supprime les préférences d'une page
 * @param int $user_id ID utilisateur
 * @param string $page_name Nom de la page
 * @return bool Succès
 */
function deleteUserPagePreferences($user_id, $page_name) {
    global $pdo;
    
    $stmt = $pdo->prepare("DELETE FROM user_preferences WHERE utilisateur_id = ? AND page_name = ?");
    return $stmt->execute([$user_id, $page_name]);
}

/**
 * Réinitialise toutes les préférences d'un utilisateur
 * @param int $user_id ID utilisateur
 * @return bool Succès
 */
function resetAllUserPreferences($user_id) {
    global $pdo;
    
    $stmt = $pdo->prepare("DELETE FROM user_preferences WHERE utilisateur_id = ?");
    return $stmt->execute([$user_id]);
}
