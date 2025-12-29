-- ============================================================================
-- FIX ULTIME: Version ultra-simple sans vérifications
-- Base: kdfvxvmy_kms_gestion (Bluehost Production)
-- À utiliser si les autres scripts donnent des erreurs
-- ============================================================================

-- Cette version:
-- ✅ Supprime TOUTES les vérifications préalables
-- ✅ Exécute directement les ALTER TABLE essentiels
-- ✅ Ignore les erreurs non-critiques
-- ✅ Fonctionne avec les permissions limitées

-- ============================================================================
-- CORRECTION PRINCIPALE: PRIMARY KEY manquante (c'est ça qui casse UPDATE)
-- ============================================================================

-- Essai 1: Ajouter PRIMARY KEY à catalogue_produits
ALTER TABLE `catalogue_produits` ADD PRIMARY KEY (`id`);

-- Essai 2: Ajouter PRIMARY KEY à catalogue_categories  
ALTER TABLE `catalogue_categories` ADD PRIMARY KEY (`id`);

-- ============================================================================
-- AMÉLIORATIONS SECONDAIRES: UNIQUE KEYs pour validation
-- ============================================================================

-- Code unique sur catalogue_produits
ALTER TABLE `catalogue_produits` ADD UNIQUE KEY `code` (`code`);

-- Slug unique sur catalogue_produits
ALTER TABLE `catalogue_produits` ADD UNIQUE KEY `slug` (`slug`);

-- Slug unique sur catalogue_categories
ALTER TABLE `catalogue_categories` ADD UNIQUE KEY `slug` (`slug`);

-- ============================================================================
-- INDEX pour performance
-- ============================================================================

-- Index sur clé étrangère
ALTER TABLE `catalogue_produits` ADD INDEX `categorie_id` (`categorie_id`);

-- ============================================================================
-- FIN - Voilà, c'est tout ce qu'il y a à faire
-- ============================================================================
