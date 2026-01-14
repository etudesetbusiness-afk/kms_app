-- ============================================================================
-- Migration: Ajout du statut (brouillon/soumis) aux reportings terrain
-- Version: 1.1
-- Date: 2026-01-12
-- Description: Ajoute la colonne `statut` à la table `terrain_reporting`
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Ajouter la colonne `statut` avec valeur par défaut 'soumis'
ALTER TABLE `terrain_reporting`
  ADD COLUMN `statut` ENUM('brouillon','soumis') NOT NULL DEFAULT 'soumis' AFTER `updated_at`;

-- Index pour filtrage rapide
ALTER TABLE `terrain_reporting`
  ADD INDEX `idx_statut` (`statut`);

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- FIN DE LA MIGRATION
-- ============================================================================
