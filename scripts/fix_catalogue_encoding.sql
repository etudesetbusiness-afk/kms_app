-- Normalisation encodage des tables catalogue
SET NAMES utf8mb4;

ALTER TABLE catalogue_categories CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE catalogue_produits  CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE canaux_vente         CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE familles_produits    CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE produits             CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE clients              CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Corrections textuelles ciblées pour mojibake
UPDATE canaux_vente SET libelle = REPLACE(libelle, 'li??e', 'liée') WHERE libelle LIKE '%li??e%';
UPDATE canaux_vente SET libelle = REPLACE(libelle, '??', 'é') WHERE libelle LIKE '%??%';
UPDATE catalogue_categories SET nom = REPLACE(nom, '??', 'é') WHERE nom LIKE '%??%';
UPDATE catalogue_produits SET designation = REPLACE(designation, '??', 'é') WHERE designation LIKE '%??%';
UPDATE catalogue_produits SET description = REPLACE(description, '??', 'é') WHERE description LIKE '%??%';
UPDATE catalogue_produits SET caracteristiques_json = REPLACE(caracteristiques_json, '??', 'é') WHERE caracteristiques_json LIKE '%??%';

-- Corrections spécifiques observées dans dump
UPDATE catalogue_categories SET nom = 'Panneaux & Contreplaqués' WHERE nom LIKE '%Panneau%' AND nom LIKE '%Contreplaqu%';
UPDATE catalogue_categories SET nom = 'Machines & Outils' WHERE nom LIKE '%Machine%';
UPDATE catalogue_produits SET description = REPLACE(description, 'r??sistance', 'résistance');
UPDATE catalogue_produits SET description = REPLACE(description, 'pr??cise', 'précise');
UPDATE catalogue_produits SET description = REPLACE(description, 'int??rieur', 'intérieur');
UPDATE catalogue_produits SET description = REPLACE(description, 'ext??rieur', 'extérieur');
UPDATE catalogue_produits SET description = REPLACE(description, 'm??lamin??', 'mélaminé');
UPDATE catalogue_produits SET description = REPLACE(description, '??tag??res', 'étagères');
UPDATE catalogue_produits SET description = REPLACE(description, 'd??riv??s', 'dérivés');
UPDATE catalogue_produits SET description = REPLACE(description, 'pr??cision', 'précision');
UPDATE catalogue_produits SET description = REPLACE(description, '??paisseur', 'épaisseur');
UPDATE catalogue_produits SET caracteristiques_json = REPLACE(caracteristiques_json, '??paisseur', 'Épaisseur');
UPDATE catalogue_produits SET caracteristiques_json = REPLACE(caracteristiques_json, 'Densit??', 'Densité');
UPDATE catalogue_produits SET caracteristiques_json = REPLACE(caracteristiques_json, 'Rev??tement', 'Revêtement');
UPDATE catalogue_produits SET caracteristiques_json = REPLACE(caracteristiques_json, 'M??lamin??', 'Mélaminé');
UPDATE catalogue_produits SET caracteristiques_json = REPLACE(caracteristiques_json, 'Capacit??', 'Capacité');
UPDATE catalogue_produits SET caracteristiques_json = REPLACE(caracteristiques_json, 't??lescopique', 'télescopique');
UPDATE catalogue_produits SET caracteristiques_json = REPLACE(caracteristiques_json, '??l??gante', 'élégante');
UPDATE catalogue_produits SET caracteristiques_json = REPLACE(caracteristiques_json, 'd??corative', 'décorative');
