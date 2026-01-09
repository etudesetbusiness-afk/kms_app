-- ========================================================
-- SCRIPT CORRECTION : SUPPRIMER LES NOMS FICTIFS
-- ========================================================
-- Objectif : Corriger les noms des commerciaux
-- Supprimer les noms de famille fictifs et garder uniquement les prénoms
-- ========================================================

UPDATE utilisateurs SET nom_complet = 'Geneviève' WHERE login = 'genevieve';
UPDATE utilisateurs SET nom_complet = 'Emmanuel' WHERE login = 'emmanuel';
UPDATE utilisateurs SET nom_complet = 'Daisy' WHERE login = 'daisy';
UPDATE utilisateurs SET nom_complet = 'Ghislaine' WHERE login = 'ghislaine';
UPDATE utilisateurs SET nom_complet = 'Tatiana' WHERE login = 'tatiana';
UPDATE utilisateurs SET nom_complet = 'Élodie' WHERE login = 'elodie';

-- ========================================================
-- VÉRIFICATION : LISTER LES COMMERCIAUX CORRIGÉS
-- ========================================================

SELECT 
  login,
  nom_complet,
  email,
  actif,
  date_creation
FROM utilisateurs
WHERE login IN ('genevieve', 'emmanuel', 'daisy', 'ghislaine', 'tatiana', 'elodie')
ORDER BY login;
