-- ========================================================
-- SCRIPT CRÉATION COMMERCIAUX TERRAIN (IDEMPOTENT)
-- ========================================================
-- Objectif : Créer 6 comptes commerciaux avec rôle TERRAIN
-- Commerciaux : Geneviève, Emmanuel, Daisy, Ghislaine, Tatiana, Élodie
-- Sécurité : INSERT IGNORE empêche les doublons
-- Réexécutable : Oui, sans erreur si utilisateurs existent déjà
-- ========================================================

-- ========================================================
-- INSERTION DES 6 COMMERCIAUX TERRAIN (AVEC VÉRIFICATION)
-- ========================================================

-- Password hash pour mot de passe initial "demo1234"
-- À noter : tous devront changer le mot de passe à la première connexion
INSERT IGNORE INTO utilisateurs (login, mot_de_passe_hash, nom_complet, email, telephone, actif, date_creation, force_changement_mdp)
VALUES 
  ('genevieve', '$2y$10$G6sGiMHX75v9PYTAqIZCPObMQV.3InGlXpNGyrKWKK/gM8iln0Tfu', 'Geneviève', 'genevieve@kms.local', NULL, 1, NOW(), 1),
  ('emmanuel', '$2y$10$G6sGiMHX75v9PYTAqIZCPObMQV.3InGlXpNGyrKWKK/gM8iln0Tfu', 'Emmanuel', 'emmanuel@kms.local', NULL, 1, NOW(), 1),
  ('daisy', '$2y$10$G6sGiMHX75v9PYTAqIZCPObMQV.3InGlXpNGyrKWKK/gM8iln0Tfu', 'Daisy', 'daisy@kms.local', NULL, 1, NOW(), 1),
  ('ghislaine', '$2y$10$G6sGiMHX75v9PYTAqIZCPObMQV.3InGlXpNGyrKWKK/gM8iln0Tfu', 'Ghislaine', 'ghislaine@kms.local', NULL, 1, NOW(), 1),
  ('tatiana', '$2y$10$G6sGiMHX75v9PYTAqIZCPObMQV.3InGlXpNGyrKWKK/gM8iln0Tfu', 'Tatiana', 'tatiana@kms.local', NULL, 1, NOW(), 1),
  ('elodie', '$2y$10$G6sGiMHX75v9PYTAqIZCPObMQV.3InGlXpNGyrKWKK/gM8iln0Tfu', 'Élodie', 'elodie@kms.local', NULL, 1, NOW(), 1);

-- ========================================================
-- ATTRIBUTION DU RÔLE TERRAIN À CHAQUE COMMERCIAL
-- ========================================================
-- Rôle TERRAIN = id 3 (Commercial Terrain)

INSERT IGNORE INTO utilisateur_role (utilisateur_id, role_id)
SELECT u.id, 3
FROM utilisateurs u
WHERE u.login IN ('genevieve', 'emmanuel', 'daisy', 'ghislaine', 'tatiana', 'elodie')
  AND u.id NOT IN (
    SELECT utilisateur_id FROM utilisateur_role 
    WHERE role_id = 3
  );

-- ========================================================
-- VÉRIFICATION FINALE : LISTER LES COMMERCIAUX CRÉÉS
-- ========================================================

SELECT 
  '📊 RÉSUMÉ CRÉATION COMMERCIAUX' AS titre;

SELECT 
  u.login,
  u.nom_complet,
  u.email,
  COALESCE(r.code, 'SANS RÔLE') AS role,
  CASE WHEN u.actif = 1 THEN '✓ Actif' ELSE '✗ Inactif' END AS statut,
  CASE WHEN u.force_changement_mdp = 1 THEN '✓ À changer' ELSE '✗ Non forcé' END AS changement_mdp,
  u.date_creation
FROM utilisateurs u
LEFT JOIN utilisateur_role ur ON u.id = ur.utilisateur_id
LEFT JOIN roles r ON ur.role_id = r.id
WHERE u.login IN ('genevieve', 'emmanuel', 'daisy', 'ghislaine', 'tatiana', 'elodie')
ORDER BY u.date_creation DESC;

-- ========================================================
-- INFORMATIONS IMPORTANTES
-- ========================================================
-- ✓ Mot de passe initial : demo1234
-- ✓ Les utilisateurs devront changer leur mot de passe à la première connexion
-- ✓ Tous les comptes sont activés par défaut
-- ✓ Tous ont le rôle TERRAIN (Commercial Terrain)
-- ✓ Script idempotent : INSERT IGNORE empêche les doublons
-- ========================================================
