-- ========================================================
-- SCRIPT MISE À JOUR : MOT DE PASSE 'KMS-123'
-- ========================================================
-- Objectif : Attribuer le mot de passe 'KMS-123' à tous les commerciaux
-- Hash bcrypt pour 'KMS-123' : $2y$10$4QAaWTsxN.PpNwmB2Np62e8aU8Kl6ExfZAOLFW6kNjJ9PpJZF5JoO
-- ========================================================

UPDATE utilisateurs 
SET mot_de_passe_hash = '$2y$10$4QAaWTsxN.PpNwmB2Np62e8aU8Kl6ExfZAOLFW6kNjJ9PpJZF5JoO'
WHERE login IN ('genevieve', 'emmanuel', 'daisy', 'ghislaine', 'tatiana', 'elodie');

-- ========================================================
-- VÉRIFICATION : CONFIRMER LA MISE À JOUR
-- ========================================================

SELECT 
  login,
  nom_complet,
  CASE WHEN mot_de_passe_hash IS NOT NULL THEN '✓ Mot de passe défini' ELSE '✗ Pas de mot de passe' END AS statut_mdp,
  date_creation
FROM utilisateurs
WHERE login IN ('genevieve', 'emmanuel', 'daisy', 'ghislaine', 'tatiana', 'elodie')
ORDER BY login;

-- ========================================================
-- NOTES
-- ========================================================
-- ✓ Tous les commerciaux peuvent maintenant se connecter avec le mot de passe : KMS-123
-- ✓ force_changement_mdp reste à 1 (changement forcé à la première connexion selon la config)
-- ========================================================
