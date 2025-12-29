-- ============================================================================
-- FIX: Corriger les erreurs "Duplicate key" si elles surviennent
-- Base: kdfvxvmy_kms_gestion (Bluehost Production)
-- À exécuter SEULEMENT si vous recevez des erreurs "Duplicate key"
-- ============================================================================

-- Si vous recevez "ERROR 1022: Can't write; duplicate key in table"
-- ou "ERROR 1064: Syntax error", exécutez ceci:

-- ÉTAPE 0: Nettoyer les valeurs NULL dans les colonnes concernées
-- (Les NULL dupliqués peuvent poser problème)
UPDATE `catalogue_categories` SET `id` = `id` WHERE `id` IS NULL;
UPDATE `catalogue_produits` SET `id` = `id` WHERE `id` IS NULL;
UPDATE `catalogue_produits` SET `code` = CONCAT('AUTO_', `id`) WHERE `code` IS NULL OR `code` = '';
UPDATE `catalogue_produits` SET `slug` = CONCAT('auto-', `id`) WHERE `slug` IS NULL OR `slug` = '';

-- ÉTAPE 1: Vérifier les doublons dans les codes
-- Si cette requête retourne des résultats, les codes en doublon doivent être corrigés manuellement
SELECT `code`, COUNT(*) as `count` 
FROM `catalogue_produits` 
GROUP BY `code` 
HAVING COUNT(*) > 1;

-- ÉTAPE 2: Vérifier les doublons dans les slugs
SELECT `slug`, COUNT(*) as `count` 
FROM `catalogue_produits` 
GROUP BY `slug` 
HAVING COUNT(*) > 1;

-- ÉTAPE 3: Si des doublons existent, les corriger automatiquement
-- Ajouter un suffixe unique aux codes en doublon (excepté le premier)
UPDATE `catalogue_produits` 
SET `code` = CONCAT(`code`, '_', `id`)
WHERE `code` IN (
  SELECT `code` 
  FROM (SELECT `code` FROM `catalogue_produits` GROUP BY `code` HAVING COUNT(*) > 1) t
)
AND `id` NOT IN (
  SELECT MIN(`id`) 
  FROM `catalogue_produits` 
  GROUP BY `code` 
  HAVING COUNT(*) > 1
);

-- ÉTAPE 4: Même chose pour les slugs
UPDATE `catalogue_produits` 
SET `slug` = CONCAT(`slug`, '-', `id`)
WHERE `slug` IN (
  SELECT `slug` 
  FROM (SELECT `slug` FROM `catalogue_produits` GROUP BY `slug` HAVING COUNT(*) > 1) t
)
AND `id` NOT IN (
  SELECT MIN(`id`) 
  FROM `catalogue_produits` 
  GROUP BY `slug` 
  HAVING COUNT(*) > 1
);

-- ÉTAPE 5: Vérifier les catégories orphelines et les corriger
-- (un produit référence une catégorie qui n'existe pas)
SELECT COUNT(*) as ORPHANED_COUNT
FROM `catalogue_produits`
WHERE `categorie_id` NOT IN (SELECT `id` FROM `catalogue_categories`);

-- Corriger les produits orphelins en les assignant à la catégorie 19 (Panneaux & Contreplaqués)
UPDATE `catalogue_produits` 
SET `categorie_id` = 19
WHERE `categorie_id` NOT IN (SELECT `id` FROM `catalogue_categories`);

-- ÉTAPE 6: Après nettoyage des données, réessayer d'ajouter les clés
-- (Exécutez le script fix_catalogue_schema_v2.sql après ceci)
