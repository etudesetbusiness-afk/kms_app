-- ============================================================
-- SCRIPT DE RÉINITIALISATION - PRODUCTION (Bluehost)
-- ============================================================
-- 
-- Base: kdfvxvmy_kms_gestion
-- Objectif: Vider TOUTES les tables SAUF utilisateurs et catalogue
-- Date: 2025-12-29
-- Usage: mysql -u kdfvxvmy_WPEUF -p kdfvxvmy_kms_gestion < reset_production.sql
--
-- ⚠️ ATTENTION: Cette action est IRRÉVERSIBLE!
-- Elle va supprimer TOUTES les données opérationnelles (ventes, clients, etc.)
-- en conservant uniquement les utilisateurs et le catalogue
--
-- ============================================================

-- Désactiver les vérifications de contraintes FK
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- TABLES À VIDER (51 tables)
-- ============================================================

-- Sécurité & Logs (avant clients/fournisseurs/utilisateurs)
DELETE FROM `audit_log`;
DELETE FROM `tentatives_connexion`;
DELETE FROM `connexions_utilisateur`;
DELETE FROM `sessions_actives`;
DELETE FROM `sms_2fa_codes`;
DELETE FROM `sms_tracking`;
DELETE FROM `blocages_ip`;

-- Commerce (Ventes) - Tables enfants d'abord
DELETE FROM `ventes_lignes`;
DELETE FROM `ventes`;
DELETE FROM `devis_lignes`;
DELETE FROM `devis`;
DELETE FROM `relances_devis`;
DELETE FROM `retours_litiges`;
DELETE FROM `satisfaction_clients`;

-- Livraisons
DELETE FROM `bons_livraison_lignes`;
DELETE FROM `bons_livraison`;

-- Ordres de préparation
DELETE FROM `ordres_preparation_lignes`;
DELETE FROM `ordres_preparation`;

-- Achats
DELETE FROM `achats_lignes`;
DELETE FROM `achats`;

-- Stock & Mouvements
DELETE FROM `stocks_mouvements`;
DELETE FROM `ruptures_signalees`;
DELETE FROM `ruptures_stock`;
DELETE FROM `mouvements_stock_backup_20251209_161710`;

-- Comptabilité (tables enfants d'abord)
DELETE FROM `journal_caisse`;
DELETE FROM `caisse_journal`;
DELETE FROM `caisses_clotures`;
DELETE FROM `compta_operations_trace`;
DELETE FROM `compta_pieces`;
DELETE FROM `compta_ecritures`;
DELETE FROM `compta_mapping_operations`;
DELETE FROM `compta_journaux`;

-- Clients & Prospects & Conversions
DELETE FROM `conversions_pipeline`;
DELETE FROM `clients`;
DELETE FROM `fournisseurs`;
DELETE FROM `prospections_terrain`;
DELETE FROM `leads_digital`;
DELETE FROM `rendezvous_terrain`;
DELETE FROM `visiteurs_showroom`;
DELETE FROM `visiteurs_hotel`;

-- Hotel
DELETE FROM `reservations_hotel`;
DELETE FROM `upsell_hotel`;
DELETE FROM `chambres`;

-- Formations
DELETE FROM `inscriptions_formation`;
DELETE FROM `prospects_formation`;
DELETE FROM `formations`;

-- Promotions
DELETE FROM `promotion_produit`;
DELETE FROM `promotions`;

-- KPIs & Préférences
DELETE FROM `kpis_quotidiens`;
DELETE FROM `objectifs_commerciaux`;
DELETE FROM `user_preferences`;
DELETE FROM `parametres_securite`;

-- ============================================================
-- RÉINITIALISER LES AUTO_INCREMENT (51 tables)
-- ============================================================

-- Sécurité & Logs
ALTER TABLE `audit_log` AUTO_INCREMENT = 1;
ALTER TABLE `tentatives_connexion` AUTO_INCREMENT = 1;
ALTER TABLE `connexions_utilisateur` AUTO_INCREMENT = 1;
ALTER TABLE `sessions_actives` AUTO_INCREMENT = 1;
ALTER TABLE `sms_2fa_codes` AUTO_INCREMENT = 1;
ALTER TABLE `sms_tracking` AUTO_INCREMENT = 1;
ALTER TABLE `blocages_ip` AUTO_INCREMENT = 1;

-- Commerce
ALTER TABLE `ventes_lignes` AUTO_INCREMENT = 1;
ALTER TABLE `ventes` AUTO_INCREMENT = 1;
ALTER TABLE `devis_lignes` AUTO_INCREMENT = 1;
ALTER TABLE `devis` AUTO_INCREMENT = 1;
ALTER TABLE `relances_devis` AUTO_INCREMENT = 1;
ALTER TABLE `retours_litiges` AUTO_INCREMENT = 1;
ALTER TABLE `satisfaction_clients` AUTO_INCREMENT = 1;

-- Livraisons
ALTER TABLE `bons_livraison_lignes` AUTO_INCREMENT = 1;
ALTER TABLE `bons_livraison` AUTO_INCREMENT = 1;

-- Ordres de préparation
ALTER TABLE `ordres_preparation_lignes` AUTO_INCREMENT = 1;
ALTER TABLE `ordres_preparation` AUTO_INCREMENT = 1;

-- Achats
ALTER TABLE `achats_lignes` AUTO_INCREMENT = 1;
ALTER TABLE `achats` AUTO_INCREMENT = 1;

-- Stock
ALTER TABLE `stocks_mouvements` AUTO_INCREMENT = 1;
ALTER TABLE `ruptures_signalees` AUTO_INCREMENT = 1;
ALTER TABLE `ruptures_stock` AUTO_INCREMENT = 1;
ALTER TABLE `mouvements_stock_backup_20251209_161710` AUTO_INCREMENT = 1;

-- Comptabilité
ALTER TABLE `journal_caisse` AUTO_INCREMENT = 1;
ALTER TABLE `caisse_journal` AUTO_INCREMENT = 1;
ALTER TABLE `caisses_clotures` AUTO_INCREMENT = 1;
ALTER TABLE `compta_operations_trace` AUTO_INCREMENT = 1;
ALTER TABLE `compta_pieces` AUTO_INCREMENT = 1;
ALTER TABLE `compta_ecritures` AUTO_INCREMENT = 1;
ALTER TABLE `compta_mapping_operations` AUTO_INCREMENT = 1;
ALTER TABLE `compta_journaux` AUTO_INCREMENT = 1;

-- Clients & Prospects & Conversions
ALTER TABLE `conversions_pipeline` AUTO_INCREMENT = 1;
ALTER TABLE `clients` AUTO_INCREMENT = 1;
ALTER TABLE `fournisseurs` AUTO_INCREMENT = 1;
ALTER TABLE `prospections_terrain` AUTO_INCREMENT = 1;
ALTER TABLE `leads_digital` AUTO_INCREMENT = 1;
ALTER TABLE `rendezvous_terrain` AUTO_INCREMENT = 1;
ALTER TABLE `visiteurs_showroom` AUTO_INCREMENT = 1;
ALTER TABLE `visiteurs_hotel` AUTO_INCREMENT = 1;

-- Hotel
ALTER TABLE `reservations_hotel` AUTO_INCREMENT = 1;
ALTER TABLE `upsell_hotel` AUTO_INCREMENT = 1;
ALTER TABLE `chambres` AUTO_INCREMENT = 1;

-- Formations
ALTER TABLE `inscriptions_formation` AUTO_INCREMENT = 1;
ALTER TABLE `prospects_formation` AUTO_INCREMENT = 1;
ALTER TABLE `formations` AUTO_INCREMENT = 1;

-- Promotions
ALTER TABLE `promotion_produit` AUTO_INCREMENT = 1;
ALTER TABLE `promotions` AUTO_INCREMENT = 1;

-- KPIs & Préférences
ALTER TABLE `kpis_quotidiens` AUTO_INCREMENT = 1;
ALTER TABLE `objectifs_commerciaux` AUTO_INCREMENT = 1;
ALTER TABLE `user_preferences` AUTO_INCREMENT = 1;
ALTER TABLE `parametres_securite` AUTO_INCREMENT = 1;

-- ============================================================
-- TABLES CONSERVÉES (18 tables)
-- ============================================================
-- 
-- Catalogue public (configuration produits) - 8 tables:
-- ✅ catalogue_categories - Catégories produits
-- ✅ catalogue_produits - Fiches produits du catalogue
-- ✅ familles_produits - Familles de produits
-- ✅ sous_categories_produits - Sous-catégories
-- ✅ produits - Table maître des produits
-- ✅ canaux_vente - Types de canaux (SHOWROOM, TERRAIN, DIGITAL, etc.)
-- ✅ types_client - Types de clients
-- ✅ modes_paiement - Modes de paiement
-- 
-- Utilisateurs & Sécurité (comptes d'accès) - 7 tables:
-- ✅ utilisateurs - Tous les comptes utilisateurs
-- ✅ utilisateurs_2fa - Configuration 2FA
-- ✅ utilisateurs_2fa_recovery - Codes de récupération 2FA
-- ✅ utilisateur_role - Assignation des rôles
-- ✅ roles - Définition des rôles
-- ✅ permissions - Définition des permissions
-- ✅ role_permission - Mapping rôle-permission
--
-- Comptabilité structure (3 tables):
-- ✅ compta_comptes - Plan comptable OHADA (104 comptes)
-- ✅ compta_exercices - Exercices comptables (2024, 2025)
--
-- ============================================================

-- Réactiver les vérifications de contraintes FK
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- RÉSUMÉ DES ACTIONS
-- ============================================================
-- 
-- ✅ Tables VIDÉES : 51 tables
-- ✅ Tables CONSERVÉES : 18 tables
-- ✅ AUTO_INCREMENT réinitialisés : 51 tables
-- ✅ Schéma inchangé (pas de DROP TABLE)
-- ✅ Foreign keys restaurées
--
-- DONNÉES SUPPRIMÉES :
--   ❌ Toutes les ventes, devis, factures, bons de livraison
--   ❌ Tous les clients et fournisseurs
--   ❌ Tous les achats et mouvements de stock
--   ❌ Toutes les écritures comptables et pièces
--   ❌ Tous les logs, sessions, codes 2FA
--   ❌ Toutes les formations et réservations hôtel
--   ❌ Tous les KPIs et historiques
--   ❌ Tous les leads et prospections
--
-- DONNÉES CONSERVÉES :
--   ✅ Catalogue de 154 produits avec images
--   ✅ Utilisateurs et leurs permissions/rôles
--   ✅ Plan comptable OHADA (104 comptes)
--   ✅ Exercices comptables (2024, 2025)
--   ✅ Configuration du système (canaux, types client, modes paiement)
--
-- ============================================================

SELECT '✅ RÉINITIALISATION PRODUCTION COMPLÈTE' AS Status;
SELECT CONCAT('Horodatage: ', NOW()) AS Timestamp;
SELECT '⚠️ Toutes les données opérationnelles ont été supprimées' AS Warning;
SELECT '✅ Utilisateurs et catalogue conservés' AS Confirmation;
