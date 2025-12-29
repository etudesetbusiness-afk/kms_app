-- ============================================================================
-- FIX: Restaurer les clés primaires, contraintes et indexes manquants
-- Base: kdfvxvmy_kms_gestion (Bluehost Production)
-- Version 2: Compatible avec permissions limitées Bluehost
-- Script: Exécutable directement dans phpMyAdmin sans vérifications préalables
-- ============================================================================

-- IMPORTANT: Ce script fonctionne même sans accès à information_schema
-- Les erreurs "Duplicate key name" sont normales et attendues si
-- les contraintes existent déjà - elles seront simplement ignorées

-- ============================================================================
-- ÉTAPE 1: Ajouter PRIMARY KEY à catalogue_categories
-- ============================================================================
-- Si la clé existe déjà, MySQL affichera une erreur, c'est NORMAL
ALTER TABLE `catalogue_categories` ADD PRIMARY KEY (`id`);

-- ============================================================================
-- ÉTAPE 2: Ajouter UNIQUE KEY slug à catalogue_categories
-- ============================================================================
ALTER TABLE `catalogue_categories` ADD UNIQUE KEY `slug` (`slug`);

-- ============================================================================
-- ÉTAPE 3: Ajouter PRIMARY KEY à catalogue_produits
-- ============================================================================
-- C'est CETTE clé qui est manquante et qui cause le problème UPDATE
ALTER TABLE `catalogue_produits` ADD PRIMARY KEY (`id`);

-- ============================================================================
-- ÉTAPE 4: Ajouter UNIQUE KEY code à catalogue_produits
-- ============================================================================
ALTER TABLE `catalogue_produits` ADD UNIQUE KEY `code` (`code`);

-- ============================================================================
-- ÉTAPE 5: Ajouter UNIQUE KEY slug à catalogue_produits
-- ============================================================================
ALTER TABLE `catalogue_produits` ADD UNIQUE KEY `slug` (`slug`);

-- ============================================================================
-- ÉTAPE 6: Ajouter INDEX sur categorie_id à catalogue_produits
-- ============================================================================
ALTER TABLE `catalogue_produits` ADD INDEX `categorie_id` (`categorie_id`);

-- ============================================================================
-- ÉTAPE 7: Ajouter CHECK constraint sur caracteristiques_json
-- ============================================================================
-- NOTE: Si vous recevez une erreur "Constraint name already exists",
-- c'est que la contrainte existe déjà, c'est OK
ALTER TABLE `catalogue_produits` 
ADD CONSTRAINT `chk_caracteristiques_json_valid` 
CHECK (JSON_VALID(`caracteristiques_json`) OR `caracteristiques_json` IS NULL);

-- ============================================================================
-- ÉTAPE 8: Ajouter CHECK constraint sur galerie_images
-- ============================================================================
ALTER TABLE `catalogue_produits` 
ADD CONSTRAINT `chk_galerie_images_valid` 
CHECK (JSON_VALID(`galerie_images`) OR `galerie_images` IS NULL);

-- ============================================================================
-- ÉTAPE 9: Ajouter FOREIGN KEY constraint
-- ============================================================================
-- IMPORTANT: Cette ligne peut échouer si des produits référencent
-- une catégorie inexistante. Si c'est le cas, voir TROUBLESHOOTING
ALTER TABLE `catalogue_produits` 
ADD CONSTRAINT `catalogue_produits_ibfk_1` 
FOREIGN KEY (`categorie_id`) REFERENCES `catalogue_categories` (`id`);

-- ============================================================================
-- FIN DU SCRIPT
-- ============================================================================
-- Les modifications ont été appliquées. 
-- Pour vérifier: allez dans l'onglet "Structure" de chaque table
-- et confirmez que les clés primaires et indexes sont présents.
