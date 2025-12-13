-- Script de correction radicale de l'encodage
-- Force UTF-8 pour la connexion et corrige les données corrompues

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- Corriger canaux_vente (corruption détectée: "é é" au lieu de "à l'hôtel")
UPDATE canaux_vente SET libelle = 'Vente liée à l''hôtel' WHERE id = 4;

-- Vérifier et corriger toutes les lignes de catalogue_produits avec mojibake
UPDATE catalogue_produits SET designation = REPLACE(designation, 'é', 'à') WHERE designation LIKE '%é%';
UPDATE catalogue_produits SET designation = REPLACE(designation, 'é', 'é') WHERE designation LIKE '%é%';
UPDATE catalogue_produits SET designation = REPLACE(designation, 'è', 'è') WHERE designation LIKE '%è%';
UPDATE catalogue_produits SET designation = REPLACE(designation, 'ê', 'ê') WHERE designation LIKE '%ê%';

UPDATE catalogue_produits SET description = REPLACE(description, 'é', 'à') WHERE description LIKE '%é%';
UPDATE catalogue_produits SET description = REPLACE(description, 'é', 'é') WHERE description LIKE '%é%';
UPDATE catalogue_produits SET description = REPLACE(description, 'è', 'è') WHERE description LIKE '%è%';
UPDATE catalogue_produits SET description = REPLACE(description, 'ê', 'ê') WHERE description LIKE '%ê%';
UPDATE catalogue_produits SET description = REPLACE(description, 'ç', 'ç') WHERE description LIKE '%ç%';

UPDATE catalogue_produits SET caracteristiques_json = REPLACE(caracteristiques_json, 'é', 'à') WHERE caracteristiques_json LIKE '%é%';
UPDATE catalogue_produits SET caracteristiques_json = REPLACE(caracteristiques_json, 'é', 'é') WHERE caracteristiques_json LIKE '%é%';
UPDATE catalogue_produits SET caracteristiques_json = REPLACE(caracteristiques_json, 'è', 'è') WHERE caracteristiques_json LIKE '%è%';

-- Afficher le résultat
SELECT '✅ Corrections encodage appliquées' as Statut;
