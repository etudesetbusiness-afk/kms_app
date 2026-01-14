-- ============================================================================
-- Migration: Adapter type_cible pour supporter types multiples en checkboxes
-- Version: 1.0
-- Date: 2026-01-12
-- Description: Changement ENUM → VARCHAR pour stocker types multiples
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Convertir type_cible de ENUM à VARCHAR pour supporter les selections multiples
ALTER TABLE `terrain_reporting_zones`
  MODIFY COLUMN `type_cible` VARCHAR(255) DEFAULT NULL COMMENT 'Types de cibles séparés par virgules: Menuiserie,Quincaillerie,Cabinet_BTP,Cabinet_etudes';

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- FIN DE LA MIGRATION
-- ============================================================================
