-- ============================================================
-- NETTOYAGE COMPLET DES ALERTES RUPTURE DE STOCK
-- ============================================================
-- 
-- Objectif: 
--   1. Supprimer les alertes de rupture enregistrées
--   2. Réinitialiser le stock des produits en rupture
-- 
-- Usage local: mysql -u root kms_gestion < clean_ruptures.sql
-- Usage production: mysql -u kdfvxvmy_WPEUF -p kdfvxvmy_kms_gestion < clean_ruptures.sql
--
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- Étape 1: Vider les tables de ruptures
DELETE FROM `ruptures_signalees`;
DELETE FROM `ruptures_stock`;

-- Étape 2: Réinitialiser les AUTO_INCREMENT
ALTER TABLE `ruptures_signalees` AUTO_INCREMENT = 1;
ALTER TABLE `ruptures_stock` AUTO_INCREMENT = 1;

-- Étape 3: Mettre à jour le stock des produits en rupture (stock_actuel = 0)
-- On leur donne un stock de 50 unités pour qu'ils ne soient plus en alerte
UPDATE `produits` 
SET `stock_actuel` = 50
WHERE `stock_actuel` = 0;

-- Étape 4: Mettre à jour les produits en alerte (stock_actuel <= seuil_alerte)
-- Les mettre au-dessus du seuil d'alerte
UPDATE `produits` 
SET `stock_actuel` = 50
WHERE `stock_actuel` > 0 
  AND `stock_actuel` <= `seuil_alerte`;

-- Étape 5 (optionnel): Réinitialiser TOUS les stocks à 50
-- Décommentez la ligne suivante pour mettre tous les produits à 50 unités
-- UPDATE `produits` SET `stock_actuel` = 50;

SET FOREIGN_KEY_CHECKS = 1;

-- Confirmation
SELECT '✅ Alertes supprimées et stocks réinitialisés' AS Status;
SELECT COUNT(*) AS 'Produits mis à jour' FROM `produits` WHERE `stock_actuel` = 10;
SELECT NOW() AS Timestamp;
