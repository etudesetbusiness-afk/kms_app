-- Phase 3.2 - Table des préférences utilisateur
-- Stocke les préférences de tri, pagination, et filtres par page

CREATE TABLE IF NOT EXISTS user_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL,
    page_name VARCHAR(100) NOT NULL COMMENT 'ex: ventes, livraisons, litiges',
    sort_by VARCHAR(50) DEFAULT 'date' COMMENT 'colonne de tri',
    sort_dir VARCHAR(4) DEFAULT 'desc' COMMENT 'asc ou desc',
    per_page INT DEFAULT 25 COMMENT 'résultats par page (10, 25, 50, 100)',
    remember_filters BOOLEAN DEFAULT 1 COMMENT 'conserver les filtres',
    default_date_range VARCHAR(20) DEFAULT NULL COMMENT 'last_7d, last_30d, last_90d, this_month',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_user_page (utilisateur_id, page_name),
    INDEX idx_utilisateur (utilisateur_id),
    INDEX idx_page (page_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Préférences utilisateur par page (tri, pagination, filtres)';
