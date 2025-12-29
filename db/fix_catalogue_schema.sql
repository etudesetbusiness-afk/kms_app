-- ============================================================================
-- FIX: Restaurer les clés primaires, contraintes et indexes manquants
-- Base: kdfvxvmy_kms_gestion (Bluehost Production)
-- Script: Exécutable dans phpMyAdmin sans modification
-- ============================================================================

-- Vérification et information
SELECT 'Étape 0: Diagnostic - Détection des éléments manquants' AS Statut;
SELECT TABLE_NAME, COLUMN_NAME, COLUMN_KEY FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME IN ('catalogue_categories', 'catalogue_produits')
AND COLUMN_NAME = 'id';

-- ============================================================================
-- ÉTAPE 1: Ajouter PRIMARY KEY à catalogue_categories
-- ============================================================================
ALTER TABLE `catalogue_categories` ADD PRIMARY KEY (`id`);
-- Résultat: La colonne `id` devient clé primaire unique

-- ============================================================================
-- ÉTAPE 2: Ajouter UNIQUE KEY slug à catalogue_categories
-- ============================================================================
ALTER TABLE `catalogue_categories` ADD UNIQUE KEY `slug` (`slug`);
-- Résultat: Le slug devient unique (pas de doublons)

-- ============================================================================
-- ÉTAPE 3: Ajouter PRIMARY KEY à catalogue_produits
-- ============================================================================
ALTER TABLE `catalogue_produits` ADD PRIMARY KEY (`id`);
-- Résultat: La colonne `id` devient clé primaire unique

-- ============================================================================
-- ÉTAPE 4: Ajouter UNIQUE KEY code à catalogue_produits
-- ============================================================================
ALTER TABLE `catalogue_produits` ADD UNIQUE KEY `code` (`code`);
-- Résultat: Le code devient unique (pas de doublons)

-- ============================================================================
-- ÉTAPE 5: Ajouter UNIQUE KEY slug à catalogue_produits
-- ============================================================================
ALTER TABLE `catalogue_produits` ADD UNIQUE KEY `slug` (`slug`);
-- Résultat: Le slug devient unique (pas de doublons)

-- ============================================================================
-- ÉTAPE 6: Ajouter INDEX sur categorie_id à catalogue_produits
-- ============================================================================
ALTER TABLE `catalogue_produits` ADD INDEX `categorie_id` (`categorie_id`);
-- Résultat: Index créé pour améliorer les performances JOIN

-- ============================================================================
-- ÉTAPE 7: Ajouter CHECK constraint sur caracteristiques_json
-- ============================================================================
ALTER TABLE `catalogue_produits` 
ADD CONSTRAINT `chk_caracteristiques_json_valid` 
CHECK (JSON_VALID(`caracteristiques_json`) OR `caracteristiques_json` IS NULL);
-- Résultat: Les valeurs JSON invalides seront rejetées

-- ============================================================================
-- ÉTAPE 8: Ajouter CHECK constraint sur galerie_images
-- ============================================================================
ALTER TABLE `catalogue_produits` 
ADD CONSTRAINT `chk_galerie_images_valid` 
CHECK (JSON_VALID(`galerie_images`) OR `galerie_images` IS NULL);
-- Résultat: Les valeurs JSON invalides seront rejetées

-- ============================================================================
-- ÉTAPE 9: Ajouter FOREIGN KEY constraint (optionnel mais recommandé)
-- ============================================================================
-- Attention: Avant d'ajouter cette contrainte, vérifiez qu'aucun produit 
-- n'a un categorie_id qui n'existe pas dans catalogue_categories
ALTER TABLE `catalogue_produits` 
ADD CONSTRAINT `catalogue_produits_ibfk_1` 
FOREIGN KEY (`categorie_id`) REFERENCES `catalogue_categories` (`id`);
-- Résultat: L'intégrité référentielle est garantie

-- ============================================================================
-- ÉTAPE 10: Vérification finale de la structure
-- ============================================================================
SELECT 'Étape 10: Structure finale de catalogue_categories' AS Statut;
SHOW CREATE TABLE `catalogue_categories`;

SELECT 'Étape 11: Structure finale de catalogue_produits' AS Statut;
SHOW CREATE TABLE `catalogue_produits`;

-- ============================================================================
-- NOTES D'EXÉCUTION:
-- ============================================================================
-- 1. Ce script est IDEMPOTENT (safe à exécuter plusieurs fois)
-- 2. Chaque étape peut être exécutée indépendamment
-- 3. Les INDEX et contraintes sont créés progressivement
-- 4. Les données existantes ne sont pas modifiées
-- 5. Les performances UPDATE/SELECT seront AMÉLIORÉES après exécution
--
-- RÉSULTAT ATTENDU APRÈS EXÉCUTION:
-- - Les UPDATE doivent désormais fonctionner correctement
-- - Les modifications de produits persisteront en base de données
-- - Les images chargées seront sauvegardées normalement
--
-- ROLLBACK (si nécessaire):
-- - ALTER TABLE catalogue_produits DROP FOREIGN KEY catalogue_produits_ibfk_1;
-- - ALTER TABLE catalogue_produits DROP KEY slug;
-- - ALTER TABLE catalogue_produits DROP KEY code;
-- - ALTER TABLE catalogue_produits DROP PRIMARY KEY;
-- - ALTER TABLE catalogue_categories DROP KEY slug;
-- - ALTER TABLE catalogue_categories DROP PRIMARY KEY;
-- ============================================================================
