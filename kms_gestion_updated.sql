-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : lun. 15 déc. 2025 à 02:22
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `kms_gestion`
--

DELIMITER $$
--
-- Procédures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `cleanup_sms_codes` ()   BEGIN
    DELETE FROM sms_2fa_codes 
    WHERE expire_a < DATE_SUB(NOW(), INTERVAL 1 DAY);
    
    DELETE FROM sms_tracking 
    WHERE envoye_a < DATE_SUB(NOW(), INTERVAL 30 DAY);
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `achats`
--

CREATE TABLE `achats` (
  `id` int(10) UNSIGNED NOT NULL,
  `numero` varchar(50) NOT NULL,
  `date_achat` date NOT NULL,
  `fournisseur_nom` varchar(255) DEFAULT NULL,
  `fournisseur_contact` varchar(255) DEFAULT NULL,
  `montant_total_ht` decimal(15,2) NOT NULL DEFAULT 0.00,
  `montant_total_ttc` decimal(15,2) NOT NULL DEFAULT 0.00,
  `statut` varchar(30) NOT NULL DEFAULT 'EN_COURS',
  `utilisateur_id` int(10) UNSIGNED DEFAULT NULL,
  `commentaires` text DEFAULT NULL,
  `date_creation` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `achats_lignes`
--

CREATE TABLE `achats_lignes` (
  `id` int(10) UNSIGNED NOT NULL,
  `achat_id` int(10) UNSIGNED NOT NULL,
  `produit_id` int(10) UNSIGNED NOT NULL,
  `quantite` decimal(15,3) NOT NULL DEFAULT 0.000,
  `prix_unitaire` decimal(15,2) NOT NULL DEFAULT 0.00,
  `remise` decimal(15,2) NOT NULL DEFAULT 0.00,
  `montant_ligne_ht` decimal(15,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `audit_log`
--

CREATE TABLE `audit_log` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `utilisateur_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'NULL si action syst??me',
  `action` varchar(100) NOT NULL COMMENT 'Type action: LOGIN, LOGOUT, CREATE, UPDATE, DELETE',
  `module` varchar(50) NOT NULL COMMENT 'Module concern??: PRODUITS, VENTES, CAISSE, etc.',
  `entite_type` varchar(50) DEFAULT NULL COMMENT 'Type entit??: produit, vente, client',
  `entite_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'ID de l''entit??',
  `details` longtext DEFAULT NULL COMMENT 'D??tails de l''action' CHECK (json_valid(`details`)),
  `ancienne_valeur` longtext DEFAULT NULL COMMENT 'Valeur avant modification' CHECK (json_valid(`ancienne_valeur`)),
  `nouvelle_valeur` longtext DEFAULT NULL COMMENT 'Valeur apr??s modification' CHECK (json_valid(`nouvelle_valeur`)),
  `ip_address` varchar(45) NOT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `date_action` datetime DEFAULT current_timestamp(),
  `niveau` enum('INFO','WARNING','ERROR','CRITICAL') DEFAULT 'INFO'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Journal d''audit complet du syst??me';

-- --------------------------------------------------------

--
-- Structure de la table `blocages_ip`
--

CREATE TABLE `blocages_ip` (
  `id` int(10) UNSIGNED NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `raison` varchar(255) NOT NULL,
  `type_blocage` enum('TEMPORAIRE','PERMANENT') DEFAULT 'TEMPORAIRE',
  `tentatives_echouees` int(10) UNSIGNED DEFAULT 0,
  `date_blocage` datetime DEFAULT current_timestamp(),
  `date_expiration` datetime DEFAULT NULL COMMENT 'NULL si permanent',
  `debloque_par` int(10) UNSIGNED DEFAULT NULL COMMENT 'Admin qui a d??bloqu??',
  `date_deblocage` datetime DEFAULT NULL,
  `actif` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Liste des adresses IP bloqu??es';

-- --------------------------------------------------------

--
-- Structure de la table `bons_livraison`
--

CREATE TABLE `bons_livraison` (
  `id` int(10) UNSIGNED NOT NULL,
  `numero` varchar(50) NOT NULL,
  `date_bl` date NOT NULL,
  `date_livraison_effective` datetime DEFAULT NULL,
  `vente_id` int(10) UNSIGNED DEFAULT NULL,
  `ordre_preparation_id` int(10) UNSIGNED DEFAULT NULL,
  `client_id` int(10) UNSIGNED NOT NULL,
  `transport_assure_par` varchar(150) DEFAULT NULL,
  `observations` text DEFAULT NULL,
  `signe_client` tinyint(1) NOT NULL DEFAULT 0,
  `statut` enum('EN_PREPARATION','PRET','EN_COURS_LIVRAISON','LIVRE','ANNULE') DEFAULT 'EN_PREPARATION',
  `magasinier_id` int(10) UNSIGNED NOT NULL,
  `livreur_id` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `bons_livraison`
--

INSERT INTO `bons_livraison` (`id`, `numero`, `date_bl`, `date_livraison_effective`, `vente_id`, `ordre_preparation_id`, `client_id`, `transport_assure_par`, `observations`, `signe_client`, `statut`, `magasinier_id`, `livreur_id`) VALUES
(45, 'BL-20251025-001', '2025-10-25', NULL, 58, NULL, 93, NULL, NULL, 1, 'LIVRE', 1, 1),
(46, 'BL-20251113-002', '2025-11-13', NULL, 63, NULL, 71, NULL, NULL, 1, 'LIVRE', 1, 1),
(47, 'BL-20251122-003', '2025-11-22', NULL, 66, NULL, 95, NULL, NULL, 1, 'LIVRE', 1, 1),
(48, 'BL-20251111-004', '2025-11-11', NULL, 67, NULL, 91, NULL, NULL, 1, 'LIVRE', 1, 1),
(49, 'BL-20251017-005', '2025-10-17', NULL, 70, NULL, 82, NULL, NULL, 1, 'LIVRE', 1, 1),
(50, 'BL-20251215-006', '2025-12-15', NULL, 71, NULL, 67, NULL, NULL, 1, 'LIVRE', 1, 1),
(51, 'BL-20251130-007', '2025-11-30', NULL, 72, NULL, 78, NULL, NULL, 1, 'LIVRE', 1, 1),
(52, 'BL-20251212-008', '2025-12-12', NULL, 73, NULL, 94, NULL, NULL, 1, 'LIVRE', 1, 1),
(53, 'BL-20251030-009', '2025-10-30', NULL, 75, NULL, 83, NULL, NULL, 1, 'LIVRE', 1, 1),
(54, 'BL-20251202-010', '2025-12-02', NULL, 76, NULL, 93, NULL, NULL, 1, 'LIVRE', 1, 1),
(55, 'BL-20251016-011', '2025-10-16', NULL, 79, NULL, 92, NULL, NULL, 1, 'LIVRE', 1, 1),
(56, 'BL-20251212-012', '2025-12-12', NULL, 81, NULL, 94, NULL, NULL, 1, 'LIVRE', 1, 1),
(57, 'BL-20251210-013', '2025-12-10', NULL, 82, NULL, 67, NULL, NULL, 1, 'LIVRE', 1, 1),
(58, 'BL-20251115-014', '2025-11-15', NULL, 83, NULL, 86, NULL, NULL, 1, 'LIVRE', 1, 1),
(59, 'BL-20251215-015', '2025-12-15', NULL, 84, NULL, 69, NULL, NULL, 1, 'LIVRE', 1, 1),
(60, 'BL-20251209-016', '2025-12-09', NULL, 86, NULL, 77, NULL, NULL, 1, 'LIVRE', 1, 1),
(61, 'BL-20251108-017', '2025-11-08', NULL, 87, NULL, 83, NULL, NULL, 1, 'LIVRE', 1, 1),
(72, 'BL-20251213-212733', '2025-12-13', NULL, 64, NULL, 89, NULL, NULL, 1, 'EN_PREPARATION', 1, NULL),
(73, 'BL-AUTO-20251213-0002', '2025-12-12', NULL, 77, NULL, 79, NULL, NULL, 1, 'LIVRE', 1, NULL),
(82, 'BL-20251214-0001', '2025-12-14', '2025-12-14 23:34:00', 90, NULL, 6, 'John', '', 1, 'EN_COURS_LIVRAISON', 1, 6);

-- --------------------------------------------------------

--
-- Structure de la table `bons_livraison_lignes`
--

CREATE TABLE `bons_livraison_lignes` (
  `id` int(10) UNSIGNED NOT NULL,
  `bon_livraison_id` int(10) UNSIGNED NOT NULL,
  `produit_id` int(10) UNSIGNED NOT NULL,
  `quantite` int(11) NOT NULL,
  `quantite_commandee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `quantite_restante` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `bons_livraison_lignes`
--

INSERT INTO `bons_livraison_lignes` (`id`, `bon_livraison_id`, `produit_id`, `quantite`, `quantite_commandee`, `quantite_restante`) VALUES
(140, 45, 69, 4, 4.00, 0.00),
(141, 45, 64, 11, 11.00, 0.00),
(142, 45, 60, 13, 13.00, 0.00),
(143, 45, 72, 4, 4.00, 0.00),
(144, 46, 62, 12, 12.00, 0.00),
(145, 46, 59, 3, 3.00, 0.00),
(146, 46, 65, 4, 4.00, 0.00),
(147, 46, 59, 13, 13.00, 0.00),
(148, 46, 62, 2, 2.00, 0.00),
(149, 47, 61, 11, 11.00, 0.00),
(150, 47, 69, 1, 1.00, 0.00),
(151, 47, 71, 3, 3.00, 0.00),
(152, 47, 61, 5, 5.00, 0.00),
(153, 48, 72, 12, 12.00, 0.00),
(154, 48, 68, 5, 5.00, 0.00),
(155, 48, 71, 9, 9.00, 0.00),
(156, 49, 69, 11, 11.00, 0.00),
(157, 49, 71, 12, 12.00, 0.00),
(158, 50, 72, 6, 6.00, 0.00),
(159, 50, 59, 14, 14.00, 0.00),
(160, 50, 68, 12, 12.00, 0.00),
(161, 50, 70, 14, 14.00, 0.00),
(162, 50, 70, 4, 4.00, 0.00),
(163, 51, 64, 1, 1.00, 0.00),
(164, 51, 61, 10, 10.00, 0.00),
(165, 51, 69, 15, 15.00, 0.00),
(166, 52, 70, 9, 9.00, 0.00),
(167, 52, 71, 8, 8.00, 0.00),
(168, 52, 67, 7, 7.00, 0.00),
(169, 53, 59, 2, 2.00, 0.00),
(170, 54, 72, 7, 7.00, 0.00),
(171, 54, 70, 2, 2.00, 0.00),
(172, 55, 64, 10, 10.00, 0.00),
(173, 55, 67, 3, 3.00, 0.00),
(174, 55, 67, 8, 8.00, 0.00),
(175, 55, 60, 7, 7.00, 0.00),
(176, 56, 69, 10, 10.00, 0.00),
(177, 57, 63, 4, 4.00, 0.00),
(178, 58, 64, 9, 9.00, 0.00),
(179, 58, 65, 10, 10.00, 0.00),
(180, 58, 72, 5, 5.00, 0.00),
(181, 58, 68, 3, 3.00, 0.00),
(182, 59, 68, 7, 7.00, 0.00),
(183, 59, 71, 9, 9.00, 0.00),
(184, 59, 70, 1, 1.00, 0.00),
(185, 59, 63, 9, 9.00, 0.00),
(186, 60, 70, 5, 5.00, 0.00),
(187, 60, 69, 9, 9.00, 0.00),
(188, 60, 61, 5, 5.00, 0.00),
(189, 61, 64, 3, 3.00, 0.00),
(190, 61, 61, 4, 4.00, 0.00),
(191, 61, 65, 7, 7.00, 0.00),
(201, 72, 64, 4, 0.00, 0.00),
(202, 72, 71, 4, 0.00, 0.00),
(203, 72, 64, 1, 0.00, 0.00),
(204, 73, 64, 3, 0.00, 0.00),
(205, 73, 65, 3, 0.00, 0.00),
(206, 73, 68, 6, 0.00, 0.00),
(208, 82, 2, 2, 2.00, 0.00);

-- --------------------------------------------------------

--
-- Structure de la table `caisses_clotures`
--

CREATE TABLE `caisses_clotures` (
  `id` int(10) UNSIGNED NOT NULL,
  `date_cloture` date NOT NULL,
  `total_recettes` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_depenses` decimal(15,2) NOT NULL DEFAULT 0.00,
  `solde_calcule` decimal(15,2) NOT NULL DEFAULT 0.00,
  `montant_especes_declare` decimal(15,2) DEFAULT 0.00,
  `montant_cheques_declare` decimal(15,2) DEFAULT 0.00,
  `montant_virements_declare` decimal(15,2) DEFAULT 0.00,
  `montant_mobile_declare` decimal(15,2) DEFAULT 0.00,
  `total_declare` decimal(15,2) NOT NULL DEFAULT 0.00,
  `ecart` decimal(15,2) NOT NULL DEFAULT 0.00,
  `justification_ecart` text DEFAULT NULL,
  `nb_operations` int(11) DEFAULT 0,
  `nb_ventes` int(11) DEFAULT 0,
  `nb_annulations` int(11) DEFAULT 0,
  `statut` enum('BROUILLON','VALIDE','ANNULE') DEFAULT 'BROUILLON',
  `caissier_id` int(10) UNSIGNED DEFAULT NULL,
  `validateur_id` int(10) UNSIGNED DEFAULT NULL,
  `date_validation` datetime DEFAULT NULL,
  `observations` text DEFAULT NULL,
  `date_creation` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `caisses_clotures`
--

INSERT INTO `caisses_clotures` (`id`, `date_cloture`, `total_recettes`, `total_depenses`, `solde_calcule`, `montant_especes_declare`, `montant_cheques_declare`, `montant_virements_declare`, `montant_mobile_declare`, `total_declare`, `ecart`, `justification_ecart`, `nb_operations`, `nb_ventes`, `nb_annulations`, `statut`, `caissier_id`, `validateur_id`, `date_validation`, `observations`, `date_creation`) VALUES
(1, '2025-12-14', 5882140.00, 170000.00, 5712140.00, 245000.00, 50000.00, 150000.00, 35000.00, 480000.00, -5232140.00, 'Différence de comptage en espèces', 21, 0, 0, 'VALIDE', 1, 1, '2025-12-14 20:03:05', 'Test de clôture automatique', '2025-12-14 20:03:05');

-- --------------------------------------------------------

--
-- Structure de la table `caisse_journal`
--

CREATE TABLE `caisse_journal` (
  `id` int(11) NOT NULL,
  `date_ecriture` datetime NOT NULL,
  `sens` enum('ENTREE','SORTIE') NOT NULL,
  `montant` decimal(15,2) NOT NULL,
  `source_type` varchar(50) DEFAULT NULL,
  `source_id` int(11) DEFAULT NULL,
  `commentaire` text DEFAULT NULL,
  `utilisateur_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `caisse_journal`
--

INSERT INTO `caisse_journal` (`id`, `date_ecriture`, `sens`, `montant`, `source_type`, `source_id`, `commentaire`, `utilisateur_id`) VALUES
(17, '2025-11-16 00:00:00', 'ENTREE', 60351.00, 'reservation_hotel', 20, 'Réservation hôtel #20', 1),
(18, '2025-11-21 00:00:00', 'ENTREE', 161240.00, 'reservation_hotel', 21, 'Réservation hôtel #21', 1),
(19, '2025-10-17 00:00:00', 'ENTREE', 20910.00, 'reservation_hotel', 22, 'Réservation hôtel #22', 1),
(20, '2025-11-28 00:00:00', 'ENTREE', 89710.00, 'reservation_hotel', 23, 'Réservation hôtel #23', 1),
(21, '2025-10-21 00:00:00', 'ENTREE', 59508.00, 'reservation_hotel', 24, 'Réservation hôtel #24', 1),
(22, '2025-10-31 00:00:00', 'ENTREE', 50382.00, 'reservation_hotel', 25, 'Réservation hôtel #25', 1),
(23, '2025-11-24 00:00:00', 'ENTREE', 102837.00, 'reservation_hotel', 26, 'Réservation hôtel #26', 1),
(24, '2025-10-25 00:00:00', 'ENTREE', 204625.00, 'reservation_hotel', 27, 'Réservation hôtel #27', 1),
(25, '2025-12-01 00:00:00', 'ENTREE', 132720.00, 'inscription_formation', 4, 'Inscription formation #4', 1),
(26, '2025-10-15 00:00:00', 'ENTREE', 106409.00, 'inscription_formation', 5, 'Inscription formation #5', 1),
(27, '2025-10-14 00:00:00', 'ENTREE', 94989.00, 'inscription_formation', 6, 'Inscription formation #6', 1),
(28, '2025-10-24 00:00:00', 'ENTREE', 162388.00, 'inscription_formation', 7, 'Inscription formation #7', 1),
(29, '2025-10-24 00:00:00', 'ENTREE', 156104.00, 'inscription_formation', 8, 'Inscription formation #8', 1),
(30, '2025-10-31 00:00:00', 'ENTREE', 99184.00, 'inscription_formation', 9, 'Inscription formation #9', 1),
(31, '2025-11-17 00:00:00', 'ENTREE', 107932.00, 'inscription_formation', 10, 'Inscription formation #10', 1),
(32, '2025-11-05 00:00:00', 'ENTREE', 40173.00, 'inscription_formation', 11, 'Inscription formation #11', 1),
(33, '2025-11-01 00:00:00', 'ENTREE', 49095.00, 'inscription_formation', 12, 'Inscription formation #12', 1),
(34, '2025-10-21 00:00:00', 'ENTREE', 110909.00, 'inscription_formation', 13, 'Inscription formation #13', 1),
(35, '2025-10-28 00:00:00', 'ENTREE', 5276600.00, 'vente', 58, 'Paiement vente', 1),
(36, '2025-11-15 00:00:00', 'ENTREE', 3065800.00, 'vente', 63, 'Paiement vente', 1),
(37, '2025-11-23 00:00:00', 'ENTREE', 512500.00, 'vente', 66, 'Paiement vente', 1),
(38, '2025-12-21 00:00:00', 'ENTREE', 2744000.00, 'vente', 71, 'Paiement vente', 1),
(39, '2025-12-01 00:00:00', 'ENTREE', 2095000.00, 'vente', 72, 'Paiement vente', 1),
(40, '2025-12-05 00:00:00', 'ENTREE', 91500.00, 'vente', 76, 'Paiement vente', 1),
(41, '2025-12-09 00:00:00', 'ENTREE', 1280000.00, 'vente', 82, 'Paiement vente', 1),
(42, '2025-11-20 00:00:00', 'ENTREE', 4452000.00, 'vente', 83, 'Paiement vente', 1),
(43, '2025-12-08 00:00:00', 'ENTREE', 987500.00, 'vente', 86, 'Paiement vente', 1),
(44, '2025-11-08 00:00:00', 'ENTREE', 1379650.00, 'vente', 87, 'Paiement vente', 1);

-- --------------------------------------------------------

--
-- Structure de la table `canaux_vente`
--

CREATE TABLE `canaux_vente` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` varchar(50) NOT NULL,
  `libelle` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `canaux_vente`
--

INSERT INTO `canaux_vente` (`id`, `code`, `libelle`) VALUES
(1, 'SHOWROOM', 'Vente showroom'),
(2, 'TERRAIN', 'Vente terrain'),
(3, 'DIGITAL', 'Vente digital / en ligne'),
(4, 'HOTEL', 'Vente liée é l\'hôtel'),
(5, 'FORMATION', 'Vente liée aux formations');

-- --------------------------------------------------------

--
-- Structure de la table `catalogue_categories`
--

CREATE TABLE `catalogue_categories` (
  `id` int(11) NOT NULL,
  `nom` varchar(150) NOT NULL,
  `slug` varchar(180) NOT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `ordre` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `catalogue_categories`
--

INSERT INTO `catalogue_categories` (`id`, `nom`, `slug`, `actif`, `ordre`, `created_at`, `updated_at`) VALUES
(19, 'Panneaux & Contreplaqués', 'panneaux', 1, 1, '2025-12-12 23:53:33', '2025-12-13 19:28:47'),
(20, 'Machines & Outils', 'machines', 1, 2, '2025-12-12 23:53:33', '2025-12-12 23:53:33'),
(21, 'Quincaillerie', 'quincaillerie', 1, 3, '2025-12-12 23:53:33', '2025-12-12 23:53:33'),
(22, 'Accessoires Menuiserie', 'accessoires', 1, 4, '2025-12-12 23:53:33', '2025-12-12 23:53:33'),
(23, 'Bois Brut', 'bois-brut', 1, 5, '2025-12-12 23:53:33', '2025-12-12 23:53:33'),
(24, 'Finitions & Vernis', 'finitions', 1, 6, '2025-12-12 23:53:33', '2025-12-12 23:53:33');

-- --------------------------------------------------------

--
-- Structure de la table `catalogue_produits`
--

CREATE TABLE `catalogue_produits` (
  `id` int(11) NOT NULL,
  `produit_id` int(11) DEFAULT NULL,
  `code` varchar(100) NOT NULL,
  `slug` varchar(180) NOT NULL,
  `designation` varchar(255) NOT NULL,
  `categorie_id` int(11) NOT NULL,
  `prix_unite` decimal(15,2) DEFAULT NULL,
  `prix_gros` decimal(15,2) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `caracteristiques_json` longtext DEFAULT NULL CHECK (json_valid(`caracteristiques_json`)),
  `image_principale` varchar(255) DEFAULT NULL,
  `galerie_images` longtext DEFAULT NULL CHECK (json_valid(`galerie_images`)),
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `catalogue_produits`
--

INSERT INTO `catalogue_produits` (`id`, `produit_id`, `code`, `slug`, `designation`, `categorie_id`, `prix_unite`, `prix_gros`, `description`, `caracteristiques_json`, `image_principale`, `galerie_images`, `actif`, `created_at`, `updated_at`) VALUES
(118, 31, 'PLQ-CTBX-18', 'plaque-ctbx-18mm', 'Panneau CTBX 18 mm', 19, 29500.00, 27500.00, 'Panneau contreplaqué CTBX haute résistance, idéal pour milieux humides et intérieurs modernes.', '{\"épaisseur\": \"18 mm\", \"Dimensions\": \"1220 x 2440 mm\", \"Essence\": \"Okoumé\", \"Classe\": \"Extérieur\"}', NULL, '[]', 1, '2025-12-12 23:56:13', '2025-12-13 19:28:47'),
(119, 32, 'PLQ-CTBX-12', 'plaque-ctbx-12mm', 'Panneau CTBX 12 mm', 19, 22000.00, 20500.00, 'Contreplaqué fin CTBX pour mobilier intérieur et agencements légers.', '{\"épaisseur\": \"12 mm\", \"Dimensions\": \"1220 x 2440 mm\", \"Essence\": \"Okoumé\", \"Finition\": \"Brut\"}', NULL, '[]', 1, '2025-12-12 23:56:13', '2025-12-13 19:28:47'),
(120, 32, 'MDF-25', 'mdf-25mm', 'Panneau MDF 25 mm', 19, 18500.00, 17000.00, 'Medium Density Fiberboard, parfait pour menuiserie intérieure, portes et placards.', '{\"épaisseur\": \"25 mm\", \"Dimensions\": \"1220 x 2440 mm\", \"Densité\": \"730 kg/mé\", \"Usage\": \"Intérieur\"}', NULL, '[]', 1, '2025-12-12 23:56:13', '2025-12-13 19:28:47'),
(121, 32, 'MDF-16', 'mdf-16mm', 'Panneau MDF 16 mm', 19, 13200.00, 12300.00, 'MDF standard pour mobilier et revétements intérieurs. Facile é usiner et peindre.', '{\"épaisseur\": \"16 mm\", \"Dimensions\": \"1220 x 2440 mm\", \"Densité\": \"720 kg/mé\"}', NULL, '[]', 1, '2025-12-12 23:56:13', '2025-12-13 19:28:47'),
(122, NULL, 'HDF-3MM', 'hdf-3mm-laminate', 'Panneau HDF 3 mm laminé', 19, 8900.00, 8200.00, 'Panneau haute densité avec revétement mélaminé pour plans de travail et surfaces de travail.', '{\"épaisseur\": \"3 mm\", \"Dimensions\": \"1220 x 2440 mm\", \"Revétement\": \"Mélaminé\", \"Finition\": \"Brillant\"}', NULL, '[]', 1, '2025-12-12 23:56:13', '2025-12-13 19:28:47'),
(123, 33, 'MULTIPLEX-21', 'multiplex-21mm', 'Multiplex 21 mm', 19, 24500.00, 22800.00, 'Contreplaqué multiplis pour construction légére, étagéres et agencement intérieur.', '{\"épaisseur\": \"21 mm\", \"Dimensions\": \"1220 x 2440 mm\", \"Plis\": \"13\", \"Grade\": \"BB\"}', NULL, '[]', 1, '2025-12-12 23:56:13', '2025-12-13 19:28:47'),
(124, 34, 'SCIE-RBT-210', 'scie-ruban-210', 'Scie é Ruban 210 W', 20, 185000.00, 172000.00, 'Scie é ruban compacte et performante pour ateliers professionnels. Coupe précise bois, dérivés et matériaux composites.', '{\"Hauteur coupe\": \"210 mm\", \"Puissance\": \"1.5 kW\", \"Alimentation\": \"220V\", \"Capacité\": \"Bois jusqué 150 mm\"}', NULL, '[]', 1, '2025-12-12 23:56:13', '2025-12-13 19:28:47'),
(125, NULL, 'DECOLLET-400', 'decolleteur-400', 'Décolleteur 400 mm', 20, 245000.00, 225000.00, 'Machine de découpe précise pour panneaux, contreplaqué et composites. Guide de profondeur ajustable.', '{\"Diamétre lame\": \"400 mm\", \"Puissance\": \"2.2 kW\", \"Vitesse\": \"42 rpm\", \"Précision\": \"é0.5 mm\"}', NULL, '[]', 1, '2025-12-12 23:56:13', '2025-12-13 19:28:47'),
(126, 35, 'RABOTEUSE-305', 'raboteuse-305mm', 'Raboteuse 305 mm', 20, 320000.00, 295000.00, 'Raboteuse professionnelle pour lissage de piéces brutes. Systéme d\'alimentation variable.', '{\"Largeur travail\": \"305 mm\", \"Puissance\": \"3 kW\", \"Capacité épaisseur\": \"150 mm\", \"Rendement\": \"8 m/min\"}', NULL, '[]', 1, '2025-12-12 23:56:13', '2025-12-13 19:28:47'),
(127, 36, 'TOUPIE-2200', 'toupie-wood-2200', 'Toupie 2200 W', 20, 425000.00, 395000.00, 'Toupillage haute puissance pour fraisage, rainurage et profilage. Moteur brushless haute vitesse.', '{\"Puissance\": \"2200 W\", \"Vitesse\": \"8000-24000 rpm\", \"Capacité\": \"Méches 6-12 mm\", \"Table\": \"Acier\"}', NULL, '[]', 1, '2025-12-12 23:56:13', '2025-12-13 19:28:47'),
(128, NULL, 'SABLEUSE-ORBITALE', 'sableuse-orbitale-225', 'Sableuse Orbitale 225 mm', 20, 48900.00, 45000.00, 'Sableuse orbitale pour finition haute qualité. Vibration minimale et systéme d\'aspiration intégré.', '{\"Disque\": \"225 mm\", \"Puissance\": \"520 W\", \"Mouvements/min\": \"4800\", \"Aspiration\": \"36 L/min\"}', NULL, '[]', 1, '2025-12-12 23:56:13', '2025-12-13 19:28:47'),
(129, NULL, 'PERCEUSE-16', 'perceuse-percussion-16', 'Perceuse é Percussion 16 mm', 20, 35500.00, 32800.00, 'Perceuse-visseuse professionnelle avec mode percussion pour travaux lourds en atelier.', '{\"Capacité\": \"16 mm\", \"Puissance\": \"900 W\", \"Couple\": \"45 Nm\", \"Vitesses\": \"Variable\"}', NULL, '[]', 1, '2025-12-12 23:56:13', '2025-12-13 19:28:47'),
(130, NULL, 'VISSEUSE-ECO', 'visseuse-sans-fil-18v', 'Visseuse sans-fil 18V', 20, 18900.00, 17500.00, 'Visseuse compacte avec batterie Li-Ion pour assemblage et finition intérieure.', '{\"Tension\":\"18 V\",\"Batterie\":\"Li-Ion 1.5 Ah\",\"Couple\":\"30 Nm\",\"Poids\":\"1.2 kg\"}', 'img_693f5f3375ecb4.13819869.webp', NULL, 1, '2025-12-12 23:56:13', '2025-12-15 01:06:59'),
(131, NULL, 'MEULEUSE-900', 'meuleuse-125mm-900w', 'Meuleuse 125 mm 900 W', 20, 22300.00, 20500.00, 'Meuleuse d\'angle compact pour découpe, meulage et travaux de finition rapides.', '{\"Diamétre disque\": \"125 mm\", \"Puissance\": \"900 W\", \"Vitesse\": \"12000 rpm\", \"Poignée\": \"Latérale\"}', NULL, '[]', 1, '2025-12-12 23:56:13', '2025-12-13 19:28:47'),
(132, 37, 'CHARN-INOX-90', 'charniere-inox-90', 'Charniére Inox 90é', 21, 950.00, 850.00, 'Charniére pour portes meubles en acier inoxydable 304. Fermeture douce sans bruit.', '{\"Matiére\": \"Inox 304\", \"Finition\": \"Brossé\", \"Angle\": \"90é\", \"Capacité\": \"30 kg\"}', NULL, '[]', 1, '2025-12-12 23:56:13', '2025-12-13 19:28:47'),
(133, NULL, 'CHARN-SOFT-CLOSE', 'charniere-soft-close-35', 'Charniére Soft-Close 35 mm', 21, 2800.00, 2550.00, 'Systéme de fermeture douce intégré. Fermeture progressive et silencieuse pour tous types de portes.', '{\"Type\": \"Overlay\", \"Ouverture\": \"110é\", \"Capacité\": \"40 kg\", \"Installation\": \"Invisible\"}', NULL, '[]', 1, '2025-12-12 23:56:13', '2025-12-13 19:28:47'),
(134, 39, 'POIGNEE-ALU-160', 'poignee-aluminium-160', 'Poignée Aluminium 160 mm', 21, 1200.00, 1050.00, 'Poignée contemporaine en aluminium anodisé. Design épuré pour tous styles de mobilier.', '{\"Longueur\": \"160 mm\", \"Matiére\": \"Aluminium anodisé\", \"Finition\": \"Noir/Argent\", \"Distance trous\": \"128 mm\"}', NULL, '[]', 1, '2025-12-12 23:56:13', '2025-12-13 19:28:47'),
(135, NULL, 'SERRURE-PUSH', 'serrure-push-open', 'Serrure Push-to-Open', 21, 3500.00, 3200.00, 'Systéme d\'ouverture sans poignée par simple pression. Intégration discréte dans le mobilier.', '{\"Tension\": \"24 V\", \"Charge\": \"60 kg\", \"Temps fermeture\": \"3 sec\", \"Installation\": \"Dissimulée\"}', NULL, '[]', 1, '2025-12-12 23:56:13', '2025-12-13 19:28:47'),
(136, 38, 'GLISSIERE-TELESCOP', 'glissiere-telescopique-500', 'Glissiére Télescopique 500 mm', 21, 4200.00, 3850.00, 'Rails de qualité supérieure pour tiroirs professionnels. Mécanisme d\'extension compléte 100%.', '{\"Course\": \"500 mm\", \"Charge\": \"80 kg\", \"Roulements\": \"Billes\", \"Fermeture\": \"Soft-close\"}', NULL, '[]', 1, '2025-12-12 23:56:13', '2025-12-13 19:28:47'),
(137, NULL, 'LOQUETEAUX-MAGNETI', 'loqueteau-magnetique-doux', 'Loqueteau Magnétique Doux', 21, 680.00, 580.00, 'Fermeture magnétique avec amortissement. Parfait pour portes vitrées et faéades légéres.', '{\"Force\": \"5 kg\", \"Matiére\": \"Alliage métallique\", \"Installation\": \"Facile\", \"Finition\": \"Chromé\"}', NULL, '[]', 1, '2025-12-12 23:56:13', '2025-12-13 19:28:47'),
(138, NULL, 'CLOUS-ACIER-65', 'clous-acier-zinc-65mm', 'Clous Acier Zingué 65 mm', 21, 450.00, 380.00, 'Clous acier galvanisé pour assemblage robuste. Résistance é la corrosion garantie.', '{\"Longueur\": \"65 mm\", \"Diamétre\": \"3.75 mm\", \"Galvanisé\": \"Oui\", \"Emballage\": \"1 kg\"}', NULL, '[]', 1, '2025-12-12 23:56:13', '2025-12-13 19:28:47'),
(139, NULL, 'JOINT-SILICONE', 'joint-silicone-translucide', 'Joint Silicone Translucide', 22, 890.00, 750.00, 'Scellant silicone haute flexibilité pour joints bois et menuiseries. Imperméable et durable.', '{\"Volume\": \"300 ml\", \"Temps prise\": \"24 h\", \"Couleur\": \"Translucide\", \"Flexibilité\": \"Haute\"}', NULL, '[]', 1, '2025-12-12 23:56:13', '2025-12-13 19:28:47'),
(140, NULL, 'COLLE-WOOD-EXPRESS', 'colle-bois-rapide-500', 'Colle Bois Express 500 ml', 22, 2200.00, 1950.00, 'Colle polyuréthane pour assemblage bois professionnel. Prise rapide (15 min), résistance max.', '{\"Volume\": \"500 ml\", \"Prise\": \"15 minutes\", \"Temps travail\": \"8-10 min\", \"Résistance\": \"Maximum\"}', NULL, '[]', 1, '2025-12-12 23:56:13', '2025-12-13 19:28:47'),
(141, NULL, 'PATTE-FIXATION-ZINC', 'patte-fixation-epoxy', 'Patte de Fixation époxy', 22, 1100.00, 950.00, 'équerre d\'assemblage en acier époxy pour renforcement bois. Charge 50 kg par point.', '{\"Matiére\": \"Acier époxy\", \"Charge\": \"50 kg\", \"Dimensions\": \"35 x 35 mm\", \"Finition\": \"Noir mat\"}', NULL, '[]', 1, '2025-12-12 23:56:13', '2025-12-13 19:28:47'),
(142, NULL, 'TAQUET-REGLABLE', 'taquet-reglable-18', 'Taquet Réglable 18 mm', 22, 380.00, 320.00, 'Taquet pour poteaux standards. Réglable en hauteur pour un positionnement flexible.', '{\"Adapte é\": \"Poteaux 18 mm\", \"Charge\": \"25 kg\", \"Matiére\": \"Zinc\", \"Réglage\": \"é15 mm\"}', NULL, '[]', 1, '2025-12-12 23:56:13', '2025-12-13 19:28:47'),
(143, NULL, 'CACHE-TROU-ACACIA', 'cache-trou-acacia-20', 'Cache-Trou Acacia 20 mm', 22, 280.00, 240.00, 'Bouchon en bois massif pour cacher les trous de vis et chevilles. Finition naturelle.', '{\"Diamétre\": \"20 mm\", \"Bois\": \"Acacia massif\", \"Finition\": \"Brut\", \"Boéte\": \"100 piéces\"}', NULL, '[]', 1, '2025-12-12 23:56:13', '2025-12-13 19:28:47'),
(144, NULL, 'VERROUS-SéCURITé', 'verrou-de-s-curit-laiton', 'Verrou de Sécurité Laiton', 22, 1650.00, 1480.00, 'Verrou de bonne qualité pour armoires et portes sensibles. Fermeture é clé 3 positions.', '{\"Matiére\":\"Laiton\",\"Clés\":\"Sécurisée\",\"Positions\":\"3\",\"Installation\":\"Externe\"}', NULL, '[\"img_693f5b1e8fe606.50750016.PNG\"]', 1, '2025-12-12 23:56:13', '2025-12-15 00:49:49'),
(145, NULL, 'SAPIN-RABOT-27', 'sapin-rabot-27x70', 'Sapin Raboté 27 x 70 mm', 23, 2800.00, 2500.00, 'Bois de sapin massif rabote pour menuiserie, cadres et structures légéres. Séché et raboté.', '{\"Section\": \"27 x 70 mm\", \"Longueur\": \"Au métre\", \"Essence\": \"Sapin du Nord\", \"Humidité\": \"Régulée\"}', NULL, '[]', 1, '2025-12-12 23:56:13', '2025-12-13 19:28:47'),
(146, NULL, 'CHENE-MASSIF-35', 'chene-massif-35x150', 'Chéne Massif 35 x 150 mm', 23, 8500.00, 7800.00, 'Chéne blanc massif de belle qualité pour mobilier noble et agencements haut de gamme.', '{\"Section\": \"35 x 150 mm\", \"Essence\": \"Chéne blanc\", \"Séchage\": \"Naturel\", \"Grade\": \"Sélectionné\"}', NULL, '[]', 1, '2025-12-12 23:56:13', '2025-12-13 19:28:47'),
(147, NULL, 'MERISIER-LAMES', 'merisier-lames-parquet', 'Lames Merisier Parquet', 23, 6200.00, 5700.00, 'Lames de merisier pour sols, revétement ou agencement. Aspect chaud et naturel.', '{\"épaisseur\": \"18 mm\", \"Largeur\": \"90-140 mm\", \"Essence\": \"Merisier\", \"Finition\": \"Brut poncé\"}', NULL, '[]', 1, '2025-12-12 23:56:13', '2025-12-13 19:28:47'),
(148, NULL, 'TECK-EXOTIQUE-40', 'teck-exotique-40-x-80-mm', 'Teck Exotique 40 x 80 mm', 23, 15500.00, 14200.00, 'Bois teck premium pour applications haut de gamme. Extrémement durable et imputrescible.', '{\"Section\":\"40 x 80 mm\",\"Essence\":\"Teck Birmanie\",\"Durabilité\":\"Classe 1\",\"Traitement\":\"Naturel\"}', 'img_693f5d41644ed7.14889283.jpeg', NULL, 1, '2025-12-12 23:56:13', '2025-12-15 00:58:41'),
(149, NULL, 'EPICEA-RABOTE-20', 'epicea-rabote-20x40', 'épicéa Raboté 20 x 40 mm', 23, 1500.00, 1350.00, 'épicéa blanc raboté pour petits travaux de menuiserie, cadres et assemblage général.', '{\"Section\": \"20 x 40 mm\", \"Essence\": \"épicéa blanc\", \"Longueur\": \"Au métre\", \"Humidité\": \"Régulée\"}', NULL, '[]', 1, '2025-12-12 23:56:13', '2025-12-13 19:28:47'),
(150, NULL, 'VERNIS-POLYURETH', 'vernis-polyur-thane-brillant-1l', 'Vernis Polyuréthane Brillant 1L', 24, 3800.00, 3400.00, 'Vernis haute résistance pour bois intérieur et extérieur. Finition brillante et durable.', '{\"Volume\":\"1 litre\",\"Brillance\":\"Brillant\",\"Temps séchage\":\"6 heures\",\"Rendement\":\"8-10 mé\\/L\"}', 'img_693f5da9342203.95665761.jpeg', '[\"img_693f5da9345571.70214857.jpg\"]', 1, '2025-12-12 23:56:13', '2025-12-15 01:00:25'),
(151, NULL, 'LASURE-BOIS-INCOLORE', 'lasure-bois-incolore', 'Lasure Bois Incolore 2.5L', 24, 4500.00, 4100.00, 'Lasure incolore pour protection bois brut extérieur. Laisse voir le grain naturel.', '{\"Volume\": \"2.5 litres\", \"Coloration\": \"Incolore\", \"Temps séchage\": \"4 heures\", \"Durabilité\": \"5-7 ans\"}', NULL, '[]', 1, '2025-12-12 23:56:13', '2025-12-13 19:28:47'),
(152, NULL, 'PEINTURE-EPOXY', 'peinture-epoxy-gris-acier', 'Peinture époxy Gris Acier 1L', 24, 2600.00, 2350.00, 'Peinture époxy haute performance pour mobilier et surface intense. Finition lisse mat.', '{\"Volume\": \"1 litre\", \"Couleur\": \"Gris acier\", \"Brillance\": \"Mat\", \"Résistance\": \"Extréme\"}', NULL, '[]', 1, '2025-12-12 23:56:13', '2025-12-13 19:28:47'),
(153, NULL, 'CIRE-BOIS-NATURELLE', 'cire-bois-naturelle-500', 'Cire Bois Naturelle 500 ml', 24, 1900.00, 1650.00, 'Cire naturelle é base d\'huiles essentielles pour entretien bois. Effet satiné protecteur.', '{\"Volume\": \"500 ml\", \"Base\": \"Naturelle 100%\", \"Aspect\": \"Satiné\", \"Odeur\": \"Naturelle\"}', NULL, '[]', 1, '2025-12-12 23:56:13', '2025-12-13 19:28:47'),
(154, NULL, 'DECAPANT-CHIMIQUE', 'decapant-chimique-pro-1l', 'Décapant Chimique Pro 1L', 24, 3200.00, 2900.00, 'Décapant puissant pour enlever peinture et vernis ancien. écologique et efficace.', '{\"Volume\": \"1 litre\", \"Type\": \"Chimique non-toxique\", \"Temps action\": \"30 minutes\", \"Rendement\": \"1-2 mé\"}', NULL, '[]', 1, '2025-12-12 23:56:13', '2025-12-13 19:28:47');

-- --------------------------------------------------------

--
-- Structure de la table `chambres`
--

CREATE TABLE `chambres` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `tarif_nuite` decimal(15,2) NOT NULL DEFAULT 0.00,
  `actif` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `chambres`
--

INSERT INTO `chambres` (`id`, `code`, `description`, `tarif_nuite`, `actif`) VALUES
(1, 'CH-101', 'Chambre standard lit double', 20000.00, 1),
(2, 'APP-201', 'Appartement meublé 2 piéces', 35000.00, 1);

-- --------------------------------------------------------

--
-- Structure de la table `clients`
--

CREATE TABLE `clients` (
  `id` int(10) UNSIGNED NOT NULL,
  `nom` varchar(150) NOT NULL,
  `type_client_id` int(10) UNSIGNED NOT NULL,
  `telephone` varchar(50) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `adresse` text DEFAULT NULL,
  `source` varchar(100) DEFAULT NULL,
  `statut` enum('PROSPECT','CLIENT','APPRENANT','HOTE') NOT NULL DEFAULT 'PROSPECT',
  `date_creation` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `clients`
--

INSERT INTO `clients` (`id`, `nom`, `type_client_id`, `telephone`, `email`, `adresse`, `source`, `statut`, `date_creation`) VALUES
(1, 'Client Showroom Test', 1, '+237650000001', 'client.showroom@test.local', 'Douala', 'Showroom', 'CLIENT', '2025-11-18 11:00:22'),
(2, 'Client Terrain Test', 2, '+237650000002', 'client.terrain@test.local', 'Bonabéri', 'Terrain', 'PROSPECT', '2025-11-18 11:00:22'),
(3, 'Client Digital Test', 3, '+237650000003', 'client.digital@test.local', 'Yaoundé', 'Facebook', 'CLIENT', '2025-11-18 11:00:22'),
(4, 'Client Hétel Test', 4, '+237650000004', 'client.hotel@test.local', 'Douala', 'Réservation directe', 'HOTE', '2025-11-18 11:00:22'),
(5, 'Apprenant Formation', 5, '+237650000005', 'apprenant@test.local', 'Bafoussam', 'WhatsApp', 'APPRENANT', '2025-11-18 11:00:22'),
(6, 'romy', 5, '695657613', 'cm@kennemulti-services.com', NULL, 'facebook', 'PROSPECT', '2025-11-20 09:02:31'),
(67, 'Ouattara Marie', 1, '0478965788', 'ouattara.marie@email.ci', 'Abidjan, Cocody', 'Terrain', 'CLIENT', '2025-12-13 17:33:50'),
(68, 'Coulibaly Kouadio', 2, '0390572888', 'coulibaly.kouadio@email.ci', 'Abidjan, Cocody', 'Showroom', 'CLIENT', '2025-12-13 17:33:50'),
(69, 'Yao Fatou', 2, '0496564644', 'yao.fatou@email.ci', 'Abidjan, Cocody', 'Showroom', 'CLIENT', '2025-12-13 17:33:50'),
(70, 'Koné Marie', 3, '0440047667', 'koné.marie@email.ci', 'Abidjan, Cocody', 'Showroom', 'PROSPECT', '2025-12-13 17:33:50'),
(71, 'Traoré Aya', 1, '0140238155', 'traoré.aya@email.ci', 'Abidjan, Cocody', 'Showroom', 'PROSPECT', '2025-12-13 17:33:50'),
(72, 'Yao Kouadio', 4, '0776354415', 'yao.kouadio@email.ci', 'Abidjan, Cocody', 'Showroom', 'PROSPECT', '2025-12-13 17:33:50'),
(73, 'Touré Fatou', 2, '0372709450', 'touré.fatou@email.ci', 'Abidjan, Cocody', 'Showroom', 'PROSPECT', '2025-12-13 17:33:50'),
(74, 'Coulibaly Aminata', 4, '0320933123', 'coulibaly.aminata@email.ci', 'Abidjan, Cocody', 'Showroom', 'PROSPECT', '2025-12-13 17:33:50'),
(75, 'Koné Mamadou', 2, '0739719179', 'koné.mamadou@email.ci', 'Abidjan, Cocody', 'Showroom', 'CLIENT', '2025-12-13 17:33:50'),
(76, 'Kouassi Ibrahim', 1, '0371713936', 'kouassi.ibrahim@email.ci', 'Abidjan, Cocody', 'Showroom', 'CLIENT', '2025-12-13 17:33:50'),
(77, 'Yao Aminata', 4, '0165653443', 'yao.aminata@email.ci', 'Abidjan, Cocody', 'Showroom', 'PROSPECT', '2025-12-13 17:33:50'),
(78, 'Ouattara Aya', 4, '0125766755', 'ouattara.aya@email.ci', 'Abidjan, Cocody', 'Terrain', 'CLIENT', '2025-12-13 17:33:50'),
(79, 'Coulibaly Ibrahim', 4, '0347030143', 'coulibaly.ibrahim@email.ci', 'Abidjan, Cocody', 'Showroom', 'CLIENT', '2025-12-13 17:33:50'),
(80, 'Coulibaly Mamadou', 3, '0118500425', 'coulibaly.mamadou@email.ci', 'Abidjan, Cocody', 'Terrain', 'CLIENT', '2025-12-13 17:33:50'),
(81, 'Ouattara Fatou', 4, '0218253827', 'ouattara.fatou@email.ci', 'Abidjan, Cocody', 'Terrain', 'PROSPECT', '2025-12-13 17:33:50'),
(82, 'Kouassi Fatou', 4, '0780869280', 'kouassi.fatou@email.ci', 'Abidjan, Cocody', 'Terrain', 'CLIENT', '2025-12-13 17:33:50'),
(83, 'Bamba Mamadou', 2, '0289505099', 'bamba.mamadou@email.ci', 'Abidjan, Cocody', 'Terrain', 'CLIENT', '2025-12-13 17:33:50'),
(84, 'Kouassi Marie', 3, '0346644905', 'kouassi.marie@email.ci', 'Abidjan, Cocody', 'Showroom', 'CLIENT', '2025-12-13 17:33:50'),
(85, 'Traoré Ibrahim', 1, '0716360698', 'traoré.ibrahim@email.ci', 'Abidjan, Cocody', 'Showroom', 'CLIENT', '2025-12-13 17:33:50'),
(86, 'Bamba Aya', 1, '0268163113', 'bamba.aya@email.ci', 'Abidjan, Cocody', 'Terrain', 'CLIENT', '2025-12-13 17:33:50'),
(87, 'Kouassi Aya', 1, '0232287535', 'kouassi.aya@email.ci', 'Abidjan, Cocody', 'Showroom', 'CLIENT', '2025-12-13 17:33:50'),
(88, 'Coulibaly Fatou', 2, '0547773861', 'coulibaly.fatou@email.ci', 'Abidjan, Cocody', 'Terrain', 'PROSPECT', '2025-12-13 17:33:50'),
(89, 'Ouattara Aya', 3, '0231718090', 'ouattara.aya@email.ci', 'Abidjan, Cocody', 'Showroom', 'PROSPECT', '2025-12-13 17:33:50'),
(90, 'Ouattara Kouadio', 1, '0625182667', 'ouattara.kouadio@email.ci', 'Abidjan, Cocody', 'Terrain', 'CLIENT', '2025-12-13 17:33:50'),
(91, 'Ouattara Aya', 2, '0676393379', 'ouattara.aya@email.ci', 'Abidjan, Cocody', 'Terrain', 'PROSPECT', '2025-12-13 17:33:50'),
(92, 'Yao Mamadou', 1, '0676798295', 'yao.mamadou@email.ci', 'Abidjan, Cocody', 'Showroom', 'CLIENT', '2025-12-13 17:33:50'),
(93, 'Touré Aya', 4, '0693878644', 'touré.aya@email.ci', 'Abidjan, Cocody', 'Showroom', 'CLIENT', '2025-12-13 17:33:50'),
(94, 'Touré Kouadio', 4, '0515213148', 'touré.kouadio@email.ci', 'Abidjan, Cocody', 'Showroom', 'CLIENT', '2025-12-13 17:33:50'),
(95, 'Kouassi Aya', 4, '0625430495', 'kouassi.aya@email.ci', 'Abidjan, Cocody', 'Showroom', 'CLIENT', '2025-12-13 17:33:50'),
(96, 'Touré Kouadio', 3, '0368743996', 'touré.kouadio@email.ci', 'Abidjan, Cocody', 'Showroom', 'CLIENT', '2025-12-13 17:33:50'),
(97, 'Janvier Soh', 1, '233567555', NULL, NULL, NULL, 'PROSPECT', '2025-12-13 21:02:23'),
(98, 'Janvier Soh', 1, '233567555', NULL, NULL, NULL, 'PROSPECT', '2025-12-13 21:02:27'),
(99, 'Janvier Soh', 1, '233567555', NULL, NULL, NULL, 'PROSPECT', '2025-12-13 21:02:33'),
(100, 'Janvier Soh', 1, '233567555', NULL, NULL, NULL, 'PROSPECT', '2025-12-13 21:04:14'),
(101, 'Janvier Soh', 1, '233567555', NULL, NULL, NULL, 'PROSPECT', '2025-12-13 21:04:22'),
(102, 'Janvier Soh', 1, '233567555', NULL, NULL, NULL, 'PROSPECT', '2025-12-13 21:04:23'),
(103, 'Janvier Soh', 1, '233567555', NULL, NULL, NULL, 'PROSPECT', '2025-12-13 21:04:27'),
(104, 'Janvier Soh', 1, '233567555', NULL, NULL, NULL, 'PROSPECT', '2025-12-13 21:04:29'),
(105, 'Janvier Soh', 1, '233567555', NULL, NULL, NULL, 'CLIENT', '2025-12-13 21:04:32');

-- --------------------------------------------------------

--
-- Structure de la table `compta_comptes`
--

CREATE TABLE `compta_comptes` (
  `id` int(10) UNSIGNED NOT NULL,
  `numero_compte` varchar(20) NOT NULL,
  `libelle` varchar(150) NOT NULL,
  `classe` char(1) NOT NULL,
  `est_analytique` tinyint(1) DEFAULT 0,
  `compte_parent_id` int(10) UNSIGNED DEFAULT NULL,
  `type_compte` enum('ACTIF','PASSIF','CHARGE','PRODUIT','MIXTE','ANALYTIQUE') DEFAULT 'ACTIF',
  `nature` enum('CREANCE','DETTE','STOCK','IMMOBILISATION','TRESORERIE','VENTE','CHARGE_VARIABLE','CHARGE_FIXE','AUTRE') DEFAULT 'AUTRE',
  `est_actif` tinyint(1) DEFAULT 1,
  `observations` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `compta_comptes`
--

INSERT INTO `compta_comptes` (`id`, `numero_compte`, `libelle`, `classe`, `est_analytique`, `compte_parent_id`, `type_compte`, `nature`, `est_actif`, `observations`, `created_at`, `updated_at`) VALUES
(1, '1', 'Immobilisations', '1', 0, NULL, 'ACTIF', 'IMMOBILISATION', 1, NULL, '2025-12-10 13:32:46', '2025-12-10 13:32:46'),
(2, '2', 'Stocks', '2', 0, NULL, 'ACTIF', 'STOCK', 0, NULL, '2025-12-10 13:32:46', '2025-12-13 21:58:11'),
(3, '3', 'Tiers', '3', 0, NULL, 'PASSIF', 'AUTRE', 1, NULL, '2025-12-10 13:32:46', '2025-12-10 13:32:46'),
(4, '4', 'Capitaux', '4', 0, NULL, 'PASSIF', 'AUTRE', 1, NULL, '2025-12-10 13:32:46', '2025-12-10 13:32:46'),
(5, '5', 'Resultats', '5', 0, NULL, 'PASSIF', 'AUTRE', 1, NULL, '2025-12-10 13:32:46', '2025-12-10 13:32:46'),
(6, '6', 'Charges', '6', 0, NULL, 'CHARGE', 'CHARGE_VARIABLE', 1, NULL, '2025-12-10 13:32:46', '2025-12-10 13:32:46'),
(7, '7', 'Produits', '7', 0, NULL, 'PRODUIT', 'VENTE', 1, NULL, '2025-12-10 13:32:46', '2025-12-10 13:32:46'),
(8, '8', 'Speciaux', '8', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-10 13:32:46', '2025-12-10 13:32:46'),
(9, '411', 'Clients', '4', 0, NULL, 'ACTIF', 'CREANCE', 1, NULL, '2025-12-10 15:28:25', '2025-12-11 16:15:37'),
(10, '707', 'Ventes de marchandises', '7', 0, NULL, 'PRODUIT', 'VENTE', 1, NULL, '2025-12-10 15:28:25', '2025-12-10 15:28:25'),
(11, '401', 'Fournisseurs', '4', 0, NULL, 'PASSIF', 'DETTE', 1, NULL, '2025-12-10 15:46:34', '2025-12-11 16:15:37'),
(12, '607', 'Achats de marchandises', '6', 0, NULL, 'CHARGE', 'CHARGE_VARIABLE', 1, NULL, '2025-12-10 15:46:34', '2025-12-10 15:46:34'),
(15, '110', 'Réserves', '1', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 05:10:03', '2025-12-13 19:28:48'),
(16, '150', 'Provisions', '1', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 05:10:03', '2025-12-11 05:10:03'),
(17, '200', 'Amortissements', '1', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 05:10:03', '2025-12-11 05:10:03'),
(18, '301', 'Matiéres premiéres', '2', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 05:10:03', '2025-12-13 19:28:48'),
(19, '512', 'Banque', '5', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 05:10:03', '2025-12-11 05:14:24'),
(20, '571', 'Caisse siége social', '5', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 05:10:03', '2025-12-13 19:28:48'),
(21, '601', 'Achats de matiéres premiéres', '6', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 05:10:03', '2025-12-13 19:28:48'),
(22, '608', 'Frais de transport', '6', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 05:10:03', '2025-12-11 05:10:03'),
(23, '622', 'Rémunérations du personnel', '6', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 05:10:03', '2025-12-13 19:28:48'),
(24, '631', 'Impéts et taxes', '6', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 05:10:03', '2025-12-13 19:28:48'),
(25, '701', 'Ventes de produits finis', '7', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 05:10:03', '2025-12-11 05:10:03'),
(26, '708', 'Revenus annexes', '7', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 05:10:03', '2025-12-11 05:10:03'),
(27, '10', 'Capital', '1', 0, NULL, 'PASSIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(28, '11', 'Réserves', '1', 0, NULL, 'PASSIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-13 19:28:48'),
(29, '12', 'Report é nouveau', '1', 0, NULL, 'PASSIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-13 19:28:48'),
(30, '13', 'Résultat net de l\'exercice', '1', 0, NULL, 'PASSIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-13 19:28:48'),
(31, '14', 'Subventions d\'investissement', '1', 0, NULL, 'PASSIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(32, '15', 'Provisions réglementées', '1', 0, NULL, 'PASSIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-13 19:28:48'),
(33, '16', 'Emprunts et dettes assimilées', '1', 0, NULL, 'PASSIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-13 19:28:48'),
(34, '17', 'Dettes de location-acquisition', '1', 0, NULL, 'PASSIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(35, '18', 'Dettes liées é des participations', '1', 0, NULL, 'PASSIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-13 19:28:48'),
(36, '19', 'Provisions financiéres pour risques et charges', '1', 0, NULL, 'PASSIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-13 19:28:48'),
(37, '20', 'Charges immobilisées', '2', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-13 19:28:48'),
(38, '21', 'Immobilisations incorporelles', '2', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(39, '22', 'Terrains', '2', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(40, '23', 'Bétiments, installations techniques et agencements', '2', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-13 19:28:48'),
(41, '24', 'Matériel, mobilier et actifs biologiques', '2', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-13 19:28:48'),
(42, '25', 'Avances et acomptes versés sur immobilisations', '2', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-13 19:28:48'),
(43, '26', 'Titres de participation', '2', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(44, '27', 'Autres immobilisations financiéres', '2', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-13 19:28:48'),
(45, '28', 'Amortissements', '2', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(46, '29', 'Provisions pour dépréciation des immobilisations', '2', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-13 19:28:48'),
(47, '31', 'Marchandises', '3', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(48, '32', 'Matiéres premiéres et fournitures liées', '3', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-13 19:28:48'),
(49, '33', 'Autres approvisionnements', '3', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(50, '34', 'Produits en cours', '3', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(51, '35', 'Services en cours', '3', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(52, '36', 'Produits finis', '3', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(53, '37', 'Produits intermédiaires et résiduels', '3', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-13 19:28:48'),
(54, '38', 'Stocks en cours de route, en consignation ou en dépét', '3', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-13 19:28:48'),
(55, '39', 'Dépréciations des stocks', '3', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-13 19:28:48'),
(56, '40', 'Fournisseurs et comptes rattachés', '4', 0, NULL, 'PASSIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-13 19:28:48'),
(57, '41', 'Clients et comptes rattachés', '4', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-13 19:28:48'),
(58, '42', 'Personnel', '4', 0, NULL, 'PASSIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(59, '43', 'Organismes sociaux', '4', 0, NULL, 'PASSIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(60, '44', 'état et collectivités publiques', '4', 0, NULL, 'MIXTE', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-13 19:28:48'),
(61, '45', 'Organismes internationaux', '4', 0, NULL, 'MIXTE', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(62, '46', 'Associés et groupe', '4', 0, NULL, 'MIXTE', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-13 19:28:48'),
(63, '47', 'Débiteurs et créditeurs divers', '4', 0, NULL, 'MIXTE', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-13 19:28:48'),
(64, '48', 'Créances et dettes hors activités ordinaires', '4', 0, NULL, 'MIXTE', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-13 19:28:48'),
(65, '49', 'Dépréciations et risques provisionnés', '4', 0, NULL, 'MIXTE', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-13 19:28:48'),
(66, '50', 'Titres de placement', '5', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(67, '51', 'Valeurs é encaisser', '5', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-13 19:28:48'),
(68, '52', 'Banques, établissements financiers et assimilés', '5', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-13 19:28:48'),
(69, '53', 'établissements financiers et assimilés', '5', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-13 19:28:48'),
(70, '54', 'Instruments de trésorerie', '5', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-13 19:28:48'),
(71, '56', 'Crédits de trésorerie', '5', 0, NULL, 'PASSIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-13 19:28:48'),
(72, '57', 'Caisse', '5', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(73, '58', 'Régies d\'avances, accréditifs et virements internes', '5', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-13 19:28:48'),
(74, '59', 'Dépréciations et risques provisionnés', '5', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-13 19:28:48'),
(75, '60', 'Achats et variations de stocks', '6', 0, NULL, 'CHARGE', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(76, '61', 'Transports', '6', 0, NULL, 'CHARGE', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(77, '62', 'Services extérieurs A', '6', 0, NULL, 'CHARGE', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-13 19:28:48'),
(78, '63', 'Autres services extérieurs B', '6', 0, NULL, 'CHARGE', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-13 19:28:48'),
(79, '64', 'Impéts et taxes', '6', 0, NULL, 'CHARGE', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-13 19:28:48'),
(80, '65', 'Autres charges', '6', 0, NULL, 'CHARGE', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(81, '66', 'Charges de personnel', '6', 0, NULL, 'CHARGE', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(82, '67', 'Frais financiers et charges assimilées', '6', 0, NULL, 'CHARGE', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-13 19:28:48'),
(83, '68', 'Dotations aux amortissements', '6', 0, NULL, 'CHARGE', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(84, '69', 'Dotations aux provisions', '6', 0, NULL, 'CHARGE', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(85, '70', 'Ventes', '7', 0, NULL, 'PRODUIT', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(86, '71', 'Subventions d\'exploitation', '7', 0, NULL, 'PRODUIT', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(87, '72', 'Production immobilisée', '7', 0, NULL, 'PRODUIT', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-13 19:28:48'),
(88, '73', 'Variations des stocks de biens et de services produits', '7', 0, NULL, 'PRODUIT', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(89, '75', 'Autres produits', '7', 0, NULL, 'PRODUIT', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(90, '77', 'Revenus financiers et produits assimilés', '7', 0, NULL, 'PRODUIT', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-13 19:28:48'),
(91, '78', 'Transferts de charges', '7', 0, NULL, 'PRODUIT', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(92, '79', 'Reprises de provisions', '7', 0, NULL, 'PRODUIT', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(93, '81', 'Valeurs comptables des cessions d\'immobilisations', '8', 0, NULL, 'CHARGE', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(94, '82', 'Produits des cessions d\'immobilisations', '8', 0, NULL, 'PRODUIT', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(95, '83', 'Charges hors activités ordinaires', '8', 0, NULL, 'CHARGE', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-13 19:28:48'),
(96, '84', 'Produits hors activités ordinaires', '8', 0, NULL, 'PRODUIT', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-13 19:28:48'),
(97, '85', 'Dotations hors activités ordinaires', '8', 0, NULL, 'CHARGE', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-13 19:28:48'),
(98, '86', 'Reprises hors activités ordinaires', '8', 0, NULL, 'PRODUIT', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-13 19:28:48'),
(99, '87', 'Participations des travailleurs', '8', 0, NULL, 'CHARGE', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(100, '88', 'Subventions d\'équilibre', '8', 0, NULL, 'PRODUIT', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-13 19:28:48'),
(101, '89', 'Impéts sur le résultat', '8', 0, NULL, 'CHARGE', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-13 19:28:48'),
(102, '90', 'Engagements donnés ou reéus', '9', 0, NULL, 'ANALYTIQUE', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-13 19:28:48'),
(103, '91', 'Contrepartie des engagements', '9', 0, NULL, 'ANALYTIQUE', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(104, '92', 'Comptes réfléchis du bilan', '9', 0, NULL, 'ANALYTIQUE', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-13 19:28:48'),
(105, '93', 'Comptes réfléchis de gestion', '9', 0, NULL, 'ANALYTIQUE', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-13 19:28:48'),
(106, '94', 'Comptes de stocks', '9', 0, NULL, 'ANALYTIQUE', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(107, '95', 'Comptes de coéts', '9', 0, NULL, 'ANALYTIQUE', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-13 19:28:48'),
(108, '96', 'Comptes d\'écarts sur coéts', '9', 0, NULL, 'ANALYTIQUE', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-13 19:28:48'),
(109, '97', 'Comptes de résultats analytiques', '9', 0, NULL, 'ANALYTIQUE', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-13 19:28:48'),
(110, '98', 'Comptes de liaisons internes', '9', 0, NULL, 'ANALYTIQUE', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(111, '99', 'Comptes de l\'activité', '9', 0, NULL, 'ANALYTIQUE', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-13 19:28:48'),
(116, '12000', 'Report à nouveau', '1', 0, NULL, 'PASSIF', 'AUTRE', 1, NULL, '2025-12-13 22:24:20', '2025-12-13 22:24:20'),
(117, '47000', 'Débiteurs divers - Ajustements', '4', 0, NULL, 'ACTIF', 'CREANCE', 1, NULL, '2025-12-13 22:24:20', '2025-12-13 22:24:20');

-- --------------------------------------------------------

--
-- Structure de la table `compta_ecritures`
--

CREATE TABLE `compta_ecritures` (
  `id` int(10) UNSIGNED NOT NULL,
  `piece_id` int(10) UNSIGNED NOT NULL,
  `compte_id` int(10) UNSIGNED NOT NULL,
  `libelle_ecriture` varchar(200) DEFAULT NULL,
  `debit` decimal(15,2) DEFAULT 0.00,
  `credit` decimal(15,2) DEFAULT 0.00,
  `tiers_client_id` int(10) UNSIGNED DEFAULT NULL,
  `tiers_fournisseur_id` int(10) UNSIGNED DEFAULT NULL,
  `centre_analytique_id` int(10) UNSIGNED DEFAULT NULL,
  `ordre_ligne` int(11) DEFAULT NULL,
  `observations` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `compta_ecritures`
--

INSERT INTO `compta_ecritures` (`id`, `piece_id`, `compte_id`, `libelle_ecriture`, `debit`, `credit`, `tiers_client_id`, `tiers_fournisseur_id`, `centre_analytique_id`, `ordre_ligne`, `observations`, `created_at`) VALUES
(1, 1, 9, 'Client facture V-20251118-114131', 238500.00, 0.00, 3, NULL, NULL, 1, NULL, '2025-12-10 15:32:08'),
(2, 1, 10, 'Vente produits facture V-20251118-114131', 0.00, 238500.00, NULL, NULL, NULL, 2, NULL, '2025-12-10 15:32:08'),
(3, 2, 9, 'Client facture V-20251118-122137', 1788742.85, 0.00, 2, NULL, NULL, 1, NULL, '2025-12-10 15:32:08'),
(4, 2, 10, 'Vente produits facture V-20251118-122137', 0.00, 1788742.85, NULL, NULL, NULL, 2, NULL, '2025-12-10 15:32:08'),
(5, 3, 9, 'Client facture V-20251118-135949', 50000.00, 0.00, 5, NULL, NULL, 1, NULL, '2025-12-10 15:32:08'),
(6, 3, 10, 'Vente produits facture V-20251118-135949', 0.00, 50000.00, NULL, NULL, NULL, 2, NULL, '2025-12-10 15:32:08'),
(7, 4, 9, 'Client facture V-20251118-151825', 50000.00, 0.00, 5, NULL, NULL, 1, NULL, '2025-12-10 15:32:08'),
(8, 4, 10, 'Vente produits facture V-20251118-151825', 0.00, 50000.00, NULL, NULL, NULL, 2, NULL, '2025-12-10 15:32:08'),
(9, 5, 9, 'Client facture V-20251120-122303', 38000.00, 0.00, 2, NULL, NULL, 1, NULL, '2025-12-10 15:32:08'),
(10, 5, 10, 'Vente produits facture V-20251120-122303', 0.00, 38000.00, NULL, NULL, NULL, 2, NULL, '2025-12-10 15:32:08'),
(11, 6, 9, 'Client facture V-20251121-112325', 1568137.50, 0.00, 6, NULL, NULL, 1, NULL, '2025-12-10 15:32:08'),
(12, 6, 10, 'Vente produits facture V-20251121-112325', 0.00, 1568137.50, NULL, NULL, NULL, 2, NULL, '2025-12-10 15:32:08'),
(13, 7, 9, 'Client facture V-20251126-154749', 429300.00, 0.00, 6, NULL, NULL, 1, NULL, '2025-12-10 15:32:08'),
(14, 7, 10, 'Vente produits facture V-20251126-154749', 0.00, 429300.00, NULL, NULL, NULL, 2, NULL, '2025-12-10 15:32:08'),
(15, 8, 9, 'Client facture V-20251126-170324', 89437.50, 0.00, 2, NULL, NULL, 1, NULL, '2025-12-10 15:32:08'),
(16, 8, 10, 'Vente produits facture V-20251126-170324', 0.00, 89437.50, NULL, NULL, NULL, 2, NULL, '2025-12-10 15:32:08'),
(17, 9, 12, 'Achat articles facture ACH-20251121-162559', 9000.00, 0.00, NULL, NULL, NULL, 1, NULL, '2025-12-10 15:49:48'),
(18, 9, 11, 'Fournisseur facture ACH-20251121-162559', 0.00, 9000.00, NULL, NULL, NULL, 2, NULL, '2025-12-10 15:49:48'),
(19, 10, 12, 'Achat articles facture AC-20251126-170544', 1250000.00, 0.00, NULL, NULL, NULL, 1, NULL, '2025-12-10 15:49:48'),
(20, 10, 11, 'Fournisseur facture AC-20251126-170544', 0.00, 1250000.00, NULL, NULL, NULL, 2, NULL, '2025-12-10 15:49:48'),
(21, 11, 12, 'Achat articles facture AC-20251202-154014', 1250000.00, 0.00, NULL, NULL, NULL, 1, NULL, '2025-12-10 15:49:48'),
(22, 11, 11, 'Fournisseur facture AC-20251202-154014', 0.00, 1250000.00, NULL, NULL, NULL, 2, NULL, '2025-12-10 15:49:48'),
(23, 12, 47, 'Stock initial valorisé', 9485000.00, 0.00, NULL, NULL, NULL, 1, NULL, '2025-12-11 05:08:27'),
(24, 12, 28, 'Stock initial valorisé', 0.00, 9485000.00, NULL, NULL, NULL, 2, NULL, '2025-12-11 05:08:27'),
(25, 13, 9, 'Vente mobilier décoration', 3500000.00, 0.00, NULL, NULL, NULL, 1, NULL, '2025-12-11 05:10:03'),
(26, 13, 25, 'Vente mobilier décoration', 0.00, 3500000.00, NULL, NULL, NULL, 2, NULL, '2025-12-11 05:10:03'),
(27, 14, 9, 'Vente accessoires', 2100000.00, 0.00, NULL, NULL, NULL, 1, NULL, '2025-12-11 05:10:03'),
(28, 14, 25, 'Vente accessoires', 0.00, 2100000.00, NULL, NULL, NULL, 2, NULL, '2025-12-11 05:10:03'),
(29, 15, 9, 'Vente panneaux', 1850000.00, 0.00, NULL, NULL, NULL, 1, NULL, '2025-12-11 05:10:04'),
(30, 15, 25, 'Vente panneaux', 0.00, 1850000.00, NULL, NULL, NULL, 2, NULL, '2025-12-11 05:10:04'),
(31, 16, 12, 'Achat matiéres premiéres', 1500000.00, 0.00, NULL, NULL, NULL, 1, NULL, '2025-12-11 05:10:04'),
(32, 16, 11, 'Achat matiéres premiéres', 0.00, 1500000.00, NULL, NULL, NULL, 2, NULL, '2025-12-11 05:10:04'),
(33, 17, 12, 'Achat accessoires', 900000.00, 0.00, NULL, NULL, NULL, 1, NULL, '2025-12-11 05:10:04'),
(34, 17, 11, 'Achat accessoires', 0.00, 900000.00, NULL, NULL, NULL, 2, NULL, '2025-12-11 05:10:04'),
(35, 18, 19, 'Paiement fournisseurs', 2509000.00, 0.00, NULL, NULL, NULL, 1, NULL, '2025-12-11 05:10:04'),
(36, 18, 11, 'Paiement fournisseurs', 0.00, 2509000.00, NULL, NULL, NULL, 2, NULL, '2025-12-11 05:10:04'),
(37, 19, 19, 'Encaissement clients', 3000000.00, 0.00, NULL, NULL, NULL, 1, NULL, '2025-12-11 05:10:04'),
(38, 19, 9, 'Encaissement clients', 0.00, 3000000.00, NULL, NULL, NULL, 2, NULL, '2025-12-11 05:10:04'),
(39, 20, 23, 'Salaires décembre', 450000.00, 0.00, NULL, NULL, NULL, 1, NULL, '2025-12-11 05:10:04'),
(40, 20, 20, 'Salaires décembre', 0.00, 450000.00, NULL, NULL, NULL, 2, NULL, '2025-12-11 05:10:04'),
(41, 21, 23, 'Frais de transport', 150000.00, 0.00, NULL, NULL, NULL, 1, NULL, '2025-12-11 05:10:04'),
(42, 21, 20, 'Frais de transport', 0.00, 150000.00, NULL, NULL, NULL, 2, NULL, '2025-12-11 05:10:04'),
(45, 23, 19, 'Encaissement partiel clients', 2000000.00, 0.00, NULL, NULL, NULL, 1, NULL, '2025-12-11 05:10:46'),
(46, 23, 9, 'Encaissement partiel clients', 0.00, 2000000.00, NULL, NULL, NULL, 2, NULL, '2025-12-11 05:10:46'),
(47, 24, 19, 'Encaissement clients', 1500000.00, 0.00, NULL, NULL, NULL, 1, NULL, '2025-12-11 05:10:46'),
(48, 24, 9, 'Encaissement clients', 0.00, 1500000.00, NULL, NULL, NULL, 2, NULL, '2025-12-11 05:10:46'),
(51, 22, 19, 'Capital social apporté', 10000000.00, 0.00, NULL, NULL, NULL, 1, NULL, '2025-12-11 05:11:30'),
(52, 22, 27, 'Capital social apporté', 0.00, 10000000.00, NULL, NULL, NULL, 2, NULL, '2025-12-11 05:11:30'),
(53, 25, 19, 'Solde initial banque', 2000000.00, 0.00, NULL, NULL, NULL, 1, NULL, '2025-12-11 05:11:30'),
(54, 25, 28, 'Solde initial banque', 0.00, 2000000.00, NULL, NULL, NULL, 2, NULL, '2025-12-11 05:11:30'),
(55, 26, 9, 'Client facture V-20251126-170324', 89437.50, 0.00, 2, NULL, NULL, 1, NULL, '2025-12-11 12:40:06'),
(56, 26, 10, 'Vente produits facture V-20251126-170324', 0.00, 89437.50, NULL, NULL, NULL, 2, NULL, '2025-12-11 12:40:06'),
(57, 27, 9, 'Client facture VTE-20251214-015', 2744000.00, 0.00, 67, NULL, NULL, 1, NULL, '2025-12-13 21:02:10'),
(58, 27, 10, 'Vente produits facture VTE-20251214-015', 0.00, 2744000.00, NULL, NULL, NULL, 2, NULL, '2025-12-13 21:02:11'),
(59, 28, 9, 'Client facture VTE-20251213-028', 4253500.00, 0.00, 69, NULL, NULL, 1, NULL, '2025-12-13 21:22:52'),
(60, 28, 10, 'Vente produits facture VTE-20251213-028', 0.00, 4253500.00, NULL, NULL, NULL, 2, NULL, '2025-12-13 21:22:52'),
(61, 29, 9, 'Client facture VTE-20251212-021', 2387850.00, 0.00, 79, NULL, NULL, 1, NULL, '2025-12-13 21:25:52'),
(62, 29, 10, 'Vente produits facture VTE-20251212-021', 0.00, 2387850.00, NULL, NULL, NULL, 2, NULL, '2025-12-13 21:25:52'),
(63, 30, 20, 'Correction : Annulation crédit caisse', 600000.00, 0.00, NULL, NULL, NULL, NULL, NULL, '2025-12-13 21:58:11'),
(64, 30, 89, 'Gain sur ajustement trésorerie', 0.00, 600000.00, NULL, NULL, NULL, NULL, NULL, '2025-12-13 21:58:11'),
(65, 32, 117, 'Ajustement bilan d\'ouverture', 24604235.70, 0.00, NULL, NULL, NULL, NULL, NULL, '2025-12-13 22:24:20'),
(66, 32, 116, 'Correction capitaux propres', 0.00, 24604235.70, NULL, NULL, NULL, NULL, NULL, '2025-12-13 22:24:20'),
(67, 33, 9, 'Client facture VTE-20251211-017', 94400.00, 0.00, 94, NULL, NULL, 1, NULL, '2025-12-14 18:23:11'),
(68, 33, 10, 'Vente produits facture VTE-20251211-017', 0.00, 94400.00, NULL, NULL, NULL, 2, NULL, '2025-12-14 18:23:11'),
(69, 34, 9, 'Client facture V-TEST-20251214-221409', 214650.00, 0.00, 5, NULL, NULL, 1, NULL, '2025-12-14 21:14:10'),
(70, 34, 10, 'Vente produits facture V-TEST-20251214-221409', 0.00, 214650.00, NULL, NULL, NULL, 2, NULL, '2025-12-14 21:14:10');

-- --------------------------------------------------------

--
-- Structure de la table `compta_exercices`
--

CREATE TABLE `compta_exercices` (
  `id` int(10) UNSIGNED NOT NULL,
  `annee` int(11) NOT NULL,
  `date_ouverture` date NOT NULL,
  `date_cloture` date DEFAULT NULL,
  `est_clos` tinyint(1) DEFAULT 0,
  `observations` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `compta_exercices`
--

INSERT INTO `compta_exercices` (`id`, `annee`, `date_ouverture`, `date_cloture`, `est_clos`, `observations`, `created_at`, `updated_at`) VALUES
(1, 2024, '2024-01-01', NULL, 0, 'Exercice 2024', '2025-12-10 13:32:46', '2025-12-10 13:32:46'),
(2, 2025, '2025-01-01', NULL, 0, 'Exercice 2025', '2025-12-10 13:32:46', '2025-12-10 13:32:46');

-- --------------------------------------------------------

--
-- Structure de la table `compta_journaux`
--

CREATE TABLE `compta_journaux` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` varchar(10) NOT NULL,
  `libelle` varchar(100) NOT NULL,
  `type` enum('VENTE','ACHAT','TRESORERIE','OPERATION_DIVERSE','PAIE') DEFAULT 'OPERATION_DIVERSE',
  `compte_contre_partie` int(10) UNSIGNED DEFAULT NULL,
  `observations` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `compta_journaux`
--

INSERT INTO `compta_journaux` (`id`, `code`, `libelle`, `type`, `compte_contre_partie`, `observations`, `created_at`, `updated_at`) VALUES
(1, 'VE', 'Ventes', 'VENTE', NULL, NULL, '2025-12-10 13:32:46', '2025-12-10 13:32:46'),
(2, 'AC', 'Achats', 'ACHAT', NULL, NULL, '2025-12-10 13:32:46', '2025-12-10 13:32:46'),
(3, 'TR', 'Tresorerie', 'TRESORERIE', NULL, NULL, '2025-12-10 13:32:46', '2025-12-10 13:32:46'),
(4, 'OD', 'Operations Diverses', 'OPERATION_DIVERSE', NULL, NULL, '2025-12-10 13:32:46', '2025-12-10 13:32:46'),
(5, 'PA', 'Paie', 'PAIE', NULL, NULL, '2025-12-10 13:32:46', '2025-12-10 13:32:46');

-- --------------------------------------------------------

--
-- Structure de la table `compta_mapping_operations`
--

CREATE TABLE `compta_mapping_operations` (
  `id` int(10) UNSIGNED NOT NULL,
  `source_type` varchar(50) NOT NULL,
  `code_operation` varchar(50) NOT NULL,
  `journal_id` int(10) UNSIGNED NOT NULL,
  `compte_debit_id` int(10) UNSIGNED DEFAULT NULL,
  `compte_credit_id` int(10) UNSIGNED DEFAULT NULL,
  `description` text DEFAULT NULL,
  `actif` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `compta_mapping_operations`
--

INSERT INTO `compta_mapping_operations` (`id`, `source_type`, `code_operation`, `journal_id`, `compte_debit_id`, `compte_credit_id`, `description`, `actif`, `created_at`, `updated_at`) VALUES
(1, 'VENTE', 'VENTE_PRODUITS', 1, 9, 10, 'Ecritures vente standard', 1, '2025-12-10 15:31:08', '2025-12-10 15:31:08'),
(2, 'ACHAT', 'ACHAT_STOCK', 2, 12, 11, 'Ecritures achat standard', 1, '2025-12-10 15:46:34', '2025-12-10 15:46:34');

-- --------------------------------------------------------

--
-- Structure de la table `compta_operations_trace`
--

CREATE TABLE `compta_operations_trace` (
  `id` int(10) UNSIGNED NOT NULL,
  `source_type` varchar(50) NOT NULL,
  `source_id` int(10) UNSIGNED NOT NULL,
  `piece_id` int(10) UNSIGNED DEFAULT NULL,
  `status` enum('success','error','en_attente') DEFAULT 'en_attente',
  `messages` text DEFAULT NULL,
  `executed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `compta_operations_trace`
--

INSERT INTO `compta_operations_trace` (`id`, `source_type`, `source_id`, `piece_id`, `status`, `messages`, `executed_at`, `created_at`) VALUES
(1, 'VENTE', 1, 1, 'success', 'Mapping VENTE/VENTE_PRODUITS non configuré', NULL, '2025-12-10 15:24:17'),
(2, 'VENTE', 2, 2, 'success', 'Mapping VENTE/VENTE_PRODUITS non configuré', NULL, '2025-12-10 15:24:17'),
(3, 'VENTE', 3, 3, 'success', 'Mapping VENTE/VENTE_PRODUITS non configuré', NULL, '2025-12-10 15:24:17'),
(4, 'VENTE', 4, 4, 'success', 'Mapping VENTE/VENTE_PRODUITS non configuré', NULL, '2025-12-10 15:24:17'),
(5, 'VENTE', 16, 5, 'success', 'Mapping VENTE/VENTE_PRODUITS non configuré', NULL, '2025-12-10 15:24:17'),
(6, 'VENTE', 17, 6, 'success', 'Mapping VENTE/VENTE_PRODUITS non configuré', NULL, '2025-12-10 15:24:17'),
(7, 'VENTE', 19, 7, 'success', 'Mapping VENTE/VENTE_PRODUITS non configuré', NULL, '2025-12-10 15:24:17'),
(8, 'VENTE', 20, 26, 'success', 'Mapping VENTE/VENTE_PRODUITS non configuré', NULL, '2025-12-10 15:24:17'),
(17, 'ACHAT', 1, 9, 'success', NULL, NULL, '2025-12-10 15:49:48'),
(18, 'ACHAT', 2, 10, 'success', NULL, NULL, '2025-12-10 15:49:48'),
(19, 'ACHAT', 3, 11, 'success', NULL, NULL, '2025-12-10 15:49:48'),
(21, 'VENTE', 71, 27, 'success', NULL, NULL, '2025-12-13 21:02:11'),
(22, 'VENTE', 84, 28, 'success', NULL, NULL, '2025-12-13 21:22:52'),
(23, 'VENTE', 77, 29, 'success', NULL, NULL, '2025-12-13 21:25:52'),
(24, 'VENTE', 73, 33, 'success', NULL, NULL, '2025-12-14 18:23:11'),
(25, 'VENTE', 91, 34, 'success', NULL, NULL, '2025-12-14 21:14:10');

-- --------------------------------------------------------

--
-- Structure de la table `compta_pieces`
--

CREATE TABLE `compta_pieces` (
  `id` int(10) UNSIGNED NOT NULL,
  `exercice_id` int(10) UNSIGNED NOT NULL,
  `journal_id` int(10) UNSIGNED NOT NULL,
  `numero_piece` varchar(50) NOT NULL,
  `libelle` varchar(255) DEFAULT NULL,
  `date_piece` date NOT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` int(10) UNSIGNED DEFAULT NULL,
  `tiers_client_id` int(10) UNSIGNED DEFAULT NULL,
  `tiers_fournisseur_id` int(10) UNSIGNED DEFAULT NULL,
  `observations` text DEFAULT NULL,
  `utilisateur_id` int(10) UNSIGNED DEFAULT NULL,
  `est_validee` tinyint(1) DEFAULT 0,
  `validee_par_id` int(10) UNSIGNED DEFAULT NULL,
  `date_validation` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `compta_pieces`
--

INSERT INTO `compta_pieces` (`id`, `exercice_id`, `journal_id`, `numero_piece`, `libelle`, `date_piece`, `reference_type`, `reference_id`, `tiers_client_id`, `tiers_fournisseur_id`, `observations`, `utilisateur_id`, `est_validee`, `validee_par_id`, `date_validation`, `created_at`, `updated_at`) VALUES
(1, 2, 1, 'XX-2025-00001', NULL, '2025-11-18', 'VENTE', 1, 3, NULL, 'Facture vente né V-20251118-114131', NULL, 1, NULL, NULL, '2025-12-10 15:32:08', '2025-12-13 19:28:48'),
(2, 2, 1, 'VE-2025-00002', NULL, '2025-11-18', 'VENTE', 2, 2, NULL, 'Facture vente né V-20251118-122137', NULL, 1, NULL, NULL, '2025-12-10 15:32:08', '2025-12-13 19:28:48'),
(3, 2, 1, 'VE-2025-00003', NULL, '2025-11-18', 'VENTE', 3, 5, NULL, 'Facture vente né V-20251118-135949', NULL, 1, NULL, NULL, '2025-12-10 15:32:08', '2025-12-13 19:28:48'),
(4, 2, 1, 'VE-2025-00004', NULL, '2025-11-18', 'VENTE', 4, 5, NULL, 'Facture vente né V-20251118-151825', NULL, 1, NULL, NULL, '2025-12-10 15:32:08', '2025-12-13 19:28:48'),
(5, 2, 1, 'VE-2025-00005', NULL, '2025-11-20', 'VENTE', 16, 2, NULL, 'Facture vente né V-20251120-122303', NULL, 1, NULL, NULL, '2025-12-10 15:32:08', '2025-12-13 19:28:48'),
(6, 2, 1, 'VE-2025-00006', NULL, '2025-11-21', 'VENTE', 17, 6, NULL, 'Facture vente né V-20251121-112325', NULL, 1, NULL, NULL, '2025-12-10 15:32:08', '2025-12-13 19:28:48'),
(7, 2, 1, 'VE-2025-00007', NULL, '2025-11-26', 'VENTE', 19, 6, NULL, 'Facture vente né V-20251126-154749', NULL, 1, NULL, NULL, '2025-12-10 15:32:08', '2025-12-13 19:28:48'),
(8, 2, 1, 'VE-2025-00008', NULL, '2025-11-26', 'VENTE', 20, 2, NULL, 'Facture vente né V-20251126-170324', NULL, 1, NULL, NULL, '2025-12-10 15:32:08', '2025-12-13 19:28:48'),
(9, 2, 2, 'XX-2025-00001', NULL, '2025-11-21', 'ACHAT', 1, NULL, NULL, 'Facture achat né ACH-20251121-162559', NULL, 1, NULL, NULL, '2025-12-10 15:49:48', '2025-12-13 19:28:48'),
(10, 2, 2, 'AC-2025-00002', NULL, '2025-11-26', 'ACHAT', 2, NULL, NULL, 'Facture achat né AC-20251126-170544', NULL, 1, NULL, NULL, '2025-12-10 15:49:48', '2025-12-13 19:28:48'),
(11, 2, 2, 'AC-2025-00003', NULL, '2025-12-02', 'ACHAT', 3, NULL, NULL, 'Facture achat né AC-20251202-154014', NULL, 1, NULL, NULL, '2025-12-10 15:49:48', '2025-12-13 19:28:48'),
(12, 2, 4, 'INV-2025-00001', NULL, '2025-12-11', NULL, NULL, NULL, NULL, 'Stock initial valorisé', NULL, 1, NULL, NULL, '2025-12-11 05:07:51', '2025-12-13 19:28:48'),
(13, 2, 1, 'VE-2025-00009', NULL, '2025-12-05', NULL, NULL, NULL, NULL, 'Vente mobilier décoration', NULL, 1, NULL, NULL, '2025-12-11 05:10:03', '2025-12-13 19:28:48'),
(14, 2, 1, 'VE-2025-00010', NULL, '2025-12-06', NULL, NULL, NULL, NULL, 'Vente accessoires', NULL, 1, NULL, NULL, '2025-12-11 05:10:03', '2025-12-11 05:10:03'),
(15, 2, 1, 'VE-2025-00011', NULL, '2025-12-07', NULL, NULL, NULL, NULL, 'Vente panneaux', NULL, 1, NULL, NULL, '2025-12-11 05:10:04', '2025-12-11 05:10:04'),
(16, 2, 2, 'AC-2025-00004', NULL, '2025-12-03', NULL, NULL, NULL, NULL, 'Achat matiéres premiéres', NULL, 1, NULL, NULL, '2025-12-11 05:10:04', '2025-12-13 19:28:48'),
(17, 2, 2, 'AC-2025-00005', NULL, '2025-12-04', NULL, NULL, NULL, NULL, 'Achat accessoires', NULL, 1, NULL, NULL, '2025-12-11 05:10:04', '2025-12-11 05:10:04'),
(18, 2, 3, 'TR-2025-00001', NULL, '2025-12-05', NULL, NULL, NULL, NULL, 'Paiement fournisseurs', NULL, 1, NULL, NULL, '2025-12-11 05:10:04', '2025-12-11 05:10:04'),
(19, 2, 3, 'TR-2025-00002', NULL, '2025-12-08', NULL, NULL, NULL, NULL, 'Encaissement clients', NULL, 1, NULL, NULL, '2025-12-11 05:10:04', '2025-12-11 05:10:04'),
(20, 2, 4, 'CH-2025-00001', NULL, '2025-12-06', NULL, NULL, NULL, NULL, 'Salaires décembre', NULL, 1, NULL, NULL, '2025-12-11 05:10:04', '2025-12-13 19:28:48'),
(21, 2, 4, 'CH-2025-00002', NULL, '2025-12-08', NULL, NULL, NULL, NULL, 'Frais de transport', NULL, 1, NULL, NULL, '2025-12-11 05:10:04', '2025-12-11 05:10:04'),
(22, 2, 4, 'CAP-2025-00001', NULL, '2025-01-01', NULL, NULL, NULL, NULL, 'Capital social initial', NULL, 1, NULL, NULL, '2025-12-11 05:10:46', '2025-12-11 05:10:46'),
(23, 2, 3, 'TR-2025-00003', NULL, '2025-12-09', NULL, NULL, NULL, NULL, 'Encaissement partiel clients', NULL, 1, NULL, NULL, '2025-12-11 05:10:46', '2025-12-11 05:10:46'),
(24, 2, 3, 'TR-2025-00004', NULL, '2025-12-10', NULL, NULL, NULL, NULL, 'Encaissement clients', NULL, 1, NULL, NULL, '2025-12-11 05:10:46', '2025-12-11 05:10:46'),
(25, 2, 3, 'BNQ-2025-00001', NULL, '2025-01-01', NULL, NULL, NULL, NULL, 'Solde initial banque', NULL, 1, NULL, NULL, '2025-12-11 05:10:46', '2025-12-11 05:10:46'),
(26, 2, 1, 'VE-2025-00012', NULL, '2025-11-26', 'VENTE', 20, 2, NULL, 'Facture vente né V-20251126-170324', NULL, 0, NULL, NULL, '2025-12-11 12:40:06', '2025-12-13 19:28:48'),
(27, 2, 1, 'VE-2025-00013', NULL, '2025-12-14', 'VENTE', 71, 67, NULL, 'Facture vente n° VTE-20251214-015', NULL, 0, NULL, NULL, '2025-12-13 21:02:10', '2025-12-13 21:02:10'),
(28, 2, 1, 'VE-2025-00014', NULL, '2025-12-13', 'VENTE', 84, 69, NULL, 'Facture vente n° VTE-20251213-028', NULL, 0, NULL, NULL, '2025-12-13 21:22:52', '2025-12-13 21:22:52'),
(29, 2, 1, 'VE-2025-00015', NULL, '2025-12-12', 'VENTE', 77, 79, NULL, 'Facture vente n° VTE-20251212-021', NULL, 0, NULL, NULL, '2025-12-13 21:25:52', '2025-12-13 21:25:52'),
(30, 2, 3, 'CORR-CAISSE-20251213', NULL, '2025-12-13', 'CORRECTION', NULL, NULL, NULL, 'Correction caisse créditrice OHADA', NULL, 1, NULL, NULL, '2025-12-13 21:58:11', '2025-12-13 22:09:24'),
(32, 2, 4, '1', NULL, '2025-12-13', 'CORRECTION_OUVERTURE', NULL, NULL, NULL, 'CORRECTION BILAN D\'OUVERTURE - Ajustement capitaux propres pour équilibre OHADA Cameroun. Écart corrigé : 24 604 236 FCFA', NULL, 1, NULL, NULL, '2025-12-13 22:24:20', '2025-12-13 22:31:49'),
(33, 2, 1, 'VE-2025-00016', NULL, '2025-12-11', 'VENTE', 73, 94, NULL, 'Facture vente n° VTE-20251211-017', NULL, 0, NULL, NULL, '2025-12-14 18:23:11', '2025-12-14 18:23:11'),
(34, 2, 1, 'VE-2025-00017', NULL, '2025-12-14', 'VENTE', 91, 5, NULL, 'Facture vente n° V-TEST-20251214-221409', NULL, 0, NULL, NULL, '2025-12-14 21:14:10', '2025-12-14 21:14:10');

-- --------------------------------------------------------

--
-- Structure de la table `connexions_utilisateur`
--

CREATE TABLE `connexions_utilisateur` (
  `id` int(10) UNSIGNED NOT NULL,
  `utilisateur_id` int(10) UNSIGNED NOT NULL,
  `date_connexion` datetime NOT NULL DEFAULT current_timestamp(),
  `adresse_ip` varchar(100) NOT NULL,
  `user_agent` varchar(255) NOT NULL,
  `succes` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `connexions_utilisateur`
--

INSERT INTO `connexions_utilisateur` (`id`, `utilisateur_id`, `date_connexion`, `adresse_ip`, `user_agent`, `succes`) VALUES
(0, 1, '2025-12-10 23:12:52', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 1),
(1, 1, '2025-11-18 11:14:36', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', 1),
(2, 1, '2025-11-18 11:30:13', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', 1),
(3, 1, '2025-11-18 11:30:21', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', 1),
(4, 1, '2025-11-18 11:47:19', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', 1),
(5, 1, '2025-11-18 12:08:16', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', 1),
(6, 1, '2025-11-18 14:28:18', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', 1),
(7, 1, '2025-11-18 14:59:18', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', 1),
(8, 1, '2025-11-18 15:16:01', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', 1),
(9, 1, '2025-11-19 09:43:53', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', 1),
(10, 1, '2025-11-19 10:07:11', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', 1),
(11, 1, '2025-11-20 09:17:11', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', 1),
(12, 1, '2025-11-20 11:07:05', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', 1),
(13, 1, '2025-11-21 11:09:04', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', 1),
(14, 1, '2025-11-21 13:10:29', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', 1),
(15, 1, '2025-11-21 15:31:29', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', 1),
(16, 1, '2025-11-26 14:35:12', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', 1),
(17, 1, '2025-11-27 09:21:40', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', 0),
(18, 1, '2025-11-27 09:21:50', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', 1),
(19, 1, '2025-12-02 15:31:49', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', 1),
(20, 1, '2025-12-02 15:44:39', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', 1),
(21, 1, '2025-12-06 12:30:16', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 1),
(22, 1, '2025-12-09 10:45:31', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:145.0) Gecko/20100101 Firefox/145.0', 1),
(23, 1, '2025-12-09 15:56:24', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 0),
(24, 1, '2025-12-09 15:56:29', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 1),
(25, 1, '2025-12-10 10:51:14', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 1),
(26, 1, '2025-12-10 14:44:08', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 1),
(27, 1, '2025-12-10 14:53:30', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 1),
(28, 1, '2025-12-10 15:26:40', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 1),
(29, 1, '2025-12-11 09:54:06', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 1),
(30, 1, '2025-12-11 11:33:37', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 1),
(31, 1, '2025-12-11 11:48:04', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 1),
(32, 1, '2025-12-11 12:49:35', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 1),
(33, 1, '2025-12-11 12:51:55', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 1),
(34, 1, '2025-12-12 15:03:13', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 1),
(35, 1, '2025-12-12 15:26:26', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 1),
(36, 1, '2025-12-13 00:32:49', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 1),
(37, 1, '2025-12-13 12:24:50', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 1),
(38, 1, '2025-12-13 12:37:18', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 1);

-- --------------------------------------------------------

--
-- Structure de la table `conversions_pipeline`
--

CREATE TABLE `conversions_pipeline` (
  `id` int(10) UNSIGNED NOT NULL,
  `source_type` enum('SHOWROOM','TERRAIN','DIGITAL') NOT NULL,
  `source_id` int(10) UNSIGNED NOT NULL COMMENT 'ID visiteur/prospection/lead',
  `client_id` int(10) UNSIGNED NOT NULL,
  `date_conversion` datetime NOT NULL DEFAULT current_timestamp(),
  `canal_vente_id` int(10) UNSIGNED DEFAULT NULL,
  `devis_id` int(10) UNSIGNED DEFAULT NULL,
  `vente_id` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `devis`
--

CREATE TABLE `devis` (
  `id` int(10) UNSIGNED NOT NULL,
  `numero` varchar(50) NOT NULL,
  `date_devis` date NOT NULL,
  `client_id` int(10) UNSIGNED NOT NULL,
  `canal_vente_id` int(10) UNSIGNED NOT NULL,
  `statut` enum('EN_ATTENTE','ACCEPTE','REFUSE','ANNULE') NOT NULL DEFAULT 'EN_ATTENTE',
  `est_converti` tinyint(1) NOT NULL DEFAULT 0,
  `date_relance` date DEFAULT NULL,
  `utilisateur_id` int(10) UNSIGNED NOT NULL,
  `montant_total_ht` decimal(15,2) NOT NULL DEFAULT 0.00,
  `montant_total_ttc` decimal(15,2) NOT NULL DEFAULT 0.00,
  `remise_global` decimal(15,2) NOT NULL DEFAULT 0.00,
  `conditions` text DEFAULT NULL,
  `commentaires` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `devis`
--

INSERT INTO `devis` (`id`, `numero`, `date_devis`, `client_id`, `canal_vente_id`, `statut`, `est_converti`, `date_relance`, `utilisateur_id`, `montant_total_ht`, `montant_total_ttc`, `remise_global`, `conditions`, `commentaires`) VALUES
(51, 'DEV-20251027-001', '2025-10-27', 93, 1, 'EN_ATTENTE', 0, NULL, 1, 303300.00, 303300.00, 0.00, NULL, NULL),
(52, 'DEV-20251104-002', '2025-11-04', 94, 1, 'ACCEPTE', 0, NULL, 1, 3043100.00, 3043100.00, 0.00, NULL, NULL),
(53, 'DEV-20251112-003', '2025-11-12', 70, 1, 'EN_ATTENTE', 0, NULL, 1, 760800.00, 760800.00, 0.00, NULL, NULL),
(54, 'DEV-20251021-004', '2025-10-21', 93, 1, 'ACCEPTE', 0, NULL, 1, 5276600.00, 5276600.00, 0.00, NULL, NULL),
(55, 'DEV-20251116-005', '2025-11-16', 72, 1, 'ACCEPTE', 0, NULL, 1, 766500.00, 766500.00, 0.00, NULL, NULL),
(56, 'DEV-20251129-006', '2025-11-29', 86, 1, 'ACCEPTE', 1, NULL, 1, 1447000.00, 1447000.00, 0.00, NULL, NULL),
(57, 'DEV-20251121-007', '2025-11-21', 91, 1, 'EN_ATTENTE', 0, NULL, 1, 40000.00, 40000.00, 0.00, NULL, NULL),
(58, 'DEV-20251118-008', '2025-11-18', 89, 1, 'ACCEPTE', 0, NULL, 1, 2945000.00, 2945000.00, 0.00, NULL, NULL),
(59, 'DEV-20251116-009', '2025-11-16', 71, 1, 'ACCEPTE', 0, NULL, 1, 8130000.00, 8130000.00, 0.00, NULL, NULL),
(60, 'DEV-20251109-010', '2025-11-09', 85, 1, 'EN_ATTENTE', 0, NULL, 1, 3699100.00, 3699100.00, 0.00, NULL, NULL),
(61, 'DEV-20251121-011', '2025-11-21', 81, 1, 'EN_ATTENTE', 0, NULL, 1, 14400.00, 14400.00, 0.00, NULL, NULL),
(62, 'DEV-20251109-012', '2025-11-09', 71, 1, 'ACCEPTE', 0, NULL, 1, 3065800.00, 3065800.00, 0.00, NULL, NULL),
(63, 'DEV-20251202-013', '2025-12-02', 84, 1, 'EN_ATTENTE', 0, NULL, 1, 1725800.00, 1725800.00, 0.00, NULL, NULL),
(64, 'DEV-20251123-014', '2025-11-23', 89, 1, 'ACCEPTE', 1, NULL, 1, 2159000.00, 2159000.00, 0.00, NULL, NULL),
(65, 'DEV-20251115-015', '2025-11-15', 96, 1, 'ACCEPTE', 0, NULL, 1, 102950.00, 102950.00, 0.00, NULL, NULL),
(66, 'DEV-20251115-016', '2025-11-15', 88, 1, 'EN_ATTENTE', 0, NULL, 1, 674500.00, 674500.00, 0.00, NULL, NULL),
(67, 'DEV-20251114-017', '2025-11-14', 95, 1, 'ACCEPTE', 1, NULL, 1, 512500.00, 512500.00, 0.00, NULL, NULL),
(68, 'DEV-20251029-018', '2025-10-29', 76, 1, 'EN_ATTENTE', 0, NULL, 1, 4307000.00, 4307000.00, 0.00, NULL, NULL),
(69, 'DEV-20251104-019', '2025-11-04', 91, 1, 'ACCEPTE', 0, NULL, 1, 1151500.00, 1151500.00, 0.00, NULL, NULL),
(70, 'DEV-20251020-020', '2025-10-20', 95, 1, 'ACCEPTE', 0, NULL, 1, 5891900.00, 5891900.00, 0.00, NULL, NULL),
(71, 'DEV-20251203-021', '2025-12-03', 81, 1, 'ACCEPTE', 1, NULL, 1, 51800.00, 51800.00, 0.00, NULL, NULL),
(72, 'DEV-20251101-022', '2025-11-01', 70, 1, 'EN_ATTENTE', 0, NULL, 1, 3912000.00, 3912000.00, 0.00, NULL, NULL),
(73, 'DEV-20251014-023', '2025-10-14', 82, 1, 'ACCEPTE', 0, NULL, 1, 1147000.00, 1147000.00, 0.00, NULL, NULL),
(74, 'DEV-20251207-024', '2025-12-07', 67, 1, 'ACCEPTE', 1, NULL, 1, 2744000.00, 2744000.00, 0.00, NULL, NULL),
(75, 'DEV-20251123-025', '2025-11-23', 78, 1, 'ACCEPTE', 1, NULL, 1, 2095000.00, 2095000.00, 0.00, NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `devis_lignes`
--

CREATE TABLE `devis_lignes` (
  `id` int(10) UNSIGNED NOT NULL,
  `devis_id` int(10) UNSIGNED NOT NULL,
  `produit_id` int(10) UNSIGNED NOT NULL,
  `quantite` int(11) NOT NULL,
  `prix_unitaire` decimal(15,2) NOT NULL,
  `remise` decimal(15,2) NOT NULL DEFAULT 0.00,
  `montant_ligne_ht` decimal(15,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `devis_lignes`
--

INSERT INTO `devis_lignes` (`id`, `devis_id`, `produit_id`, `quantite`, `prix_unitaire`, `remise`, `montant_ligne_ht`) VALUES
(165, 51, 66, 9, 4200.00, 0.00, 37800.00),
(166, 51, 59, 9, 29500.00, 0.00, 265500.00),
(167, 52, 59, 3, 29500.00, 0.00, 88500.00),
(168, 52, 62, 13, 185000.00, 0.00, 2405000.00),
(169, 52, 60, 8, 13200.00, 0.00, 105600.00),
(170, 52, 71, 9, 8500.00, 0.00, 76500.00),
(171, 52, 61, 15, 24500.00, 0.00, 367500.00),
(172, 53, 65, 8, 950.00, 0.00, 7600.00),
(173, 53, 68, 4, 185000.00, 0.00, 740000.00),
(174, 53, 67, 11, 1200.00, 0.00, 13200.00),
(175, 54, 69, 4, 95000.00, 0.00, 380000.00),
(176, 54, 64, 11, 425000.00, 0.00, 4675000.00),
(177, 54, 60, 13, 13200.00, 0.00, 171600.00),
(178, 54, 72, 4, 12500.00, 0.00, 50000.00),
(179, 55, 59, 13, 29500.00, 0.00, 383500.00),
(180, 55, 67, 7, 1200.00, 0.00, 8400.00),
(181, 55, 60, 3, 13200.00, 0.00, 39600.00),
(182, 55, 72, 12, 12500.00, 0.00, 150000.00),
(183, 55, 62, 1, 185000.00, 0.00, 185000.00),
(184, 56, 59, 13, 29500.00, 0.00, 383500.00),
(185, 56, 71, 12, 8500.00, 0.00, 102000.00),
(186, 56, 72, 13, 12500.00, 0.00, 162500.00),
(187, 56, 59, 2, 29500.00, 0.00, 59000.00),
(188, 56, 62, 4, 185000.00, 0.00, 740000.00),
(189, 57, 67, 15, 1200.00, 0.00, 18000.00),
(190, 57, 70, 11, 2000.00, 0.00, 22000.00),
(191, 58, 68, 9, 185000.00, 0.00, 1665000.00),
(192, 58, 63, 4, 320000.00, 0.00, 1280000.00),
(193, 59, 68, 4, 185000.00, 0.00, 740000.00),
(194, 59, 69, 14, 95000.00, 0.00, 1330000.00),
(195, 59, 64, 4, 425000.00, 0.00, 1700000.00),
(196, 59, 63, 3, 320000.00, 0.00, 960000.00),
(197, 59, 64, 8, 425000.00, 0.00, 3400000.00),
(198, 60, 60, 8, 13200.00, 0.00, 105600.00),
(199, 60, 61, 3, 24500.00, 0.00, 73500.00),
(200, 60, 63, 11, 320000.00, 0.00, 3520000.00),
(201, 61, 70, 3, 2000.00, 0.00, 6000.00),
(202, 61, 66, 2, 4200.00, 0.00, 8400.00),
(203, 62, 62, 12, 185000.00, 0.00, 2220000.00),
(204, 62, 59, 3, 29500.00, 0.00, 88500.00),
(205, 62, 65, 4, 950.00, 0.00, 3800.00),
(206, 62, 59, 13, 29500.00, 0.00, 383500.00),
(207, 62, 62, 2, 185000.00, 0.00, 370000.00),
(208, 63, 66, 9, 4200.00, 0.00, 37800.00),
(209, 63, 64, 3, 425000.00, 0.00, 1275000.00),
(210, 63, 59, 14, 29500.00, 0.00, 413000.00),
(211, 64, 64, 4, 425000.00, 0.00, 1700000.00),
(212, 64, 71, 4, 8500.00, 0.00, 34000.00),
(213, 64, 64, 1, 425000.00, 0.00, 425000.00),
(214, 65, 71, 8, 8500.00, 0.00, 68000.00),
(215, 65, 65, 1, 950.00, 0.00, 950.00),
(216, 65, 70, 14, 2000.00, 0.00, 28000.00),
(217, 65, 67, 5, 1200.00, 0.00, 6000.00),
(218, 66, 70, 8, 2000.00, 0.00, 16000.00),
(219, 66, 59, 3, 29500.00, 0.00, 88500.00),
(220, 66, 69, 6, 95000.00, 0.00, 570000.00),
(221, 67, 61, 11, 24500.00, 0.00, 269500.00),
(222, 67, 69, 1, 95000.00, 0.00, 95000.00),
(223, 67, 71, 3, 8500.00, 0.00, 25500.00),
(224, 67, 61, 5, 24500.00, 0.00, 122500.00),
(225, 68, 59, 6, 29500.00, 0.00, 177000.00),
(226, 68, 62, 8, 185000.00, 0.00, 1480000.00),
(227, 68, 61, 10, 24500.00, 0.00, 245000.00),
(228, 68, 68, 13, 185000.00, 0.00, 2405000.00),
(229, 69, 72, 12, 12500.00, 0.00, 150000.00),
(230, 69, 68, 5, 185000.00, 0.00, 925000.00),
(231, 69, 71, 9, 8500.00, 0.00, 76500.00),
(232, 70, 71, 15, 8500.00, 0.00, 127500.00),
(233, 70, 67, 12, 1200.00, 0.00, 14400.00),
(234, 70, 64, 6, 425000.00, 0.00, 2550000.00),
(235, 70, 63, 10, 320000.00, 0.00, 3200000.00),
(236, 71, 66, 9, 4200.00, 0.00, 37800.00),
(237, 71, 70, 7, 2000.00, 0.00, 14000.00),
(238, 72, 70, 11, 2000.00, 0.00, 22000.00),
(239, 72, 63, 8, 320000.00, 0.00, 2560000.00),
(240, 72, 69, 14, 95000.00, 0.00, 1330000.00),
(241, 73, 69, 11, 95000.00, 0.00, 1045000.00),
(242, 73, 71, 12, 8500.00, 0.00, 102000.00),
(243, 74, 72, 6, 12500.00, 0.00, 75000.00),
(244, 74, 59, 14, 29500.00, 0.00, 413000.00),
(245, 74, 68, 12, 185000.00, 0.00, 2220000.00),
(246, 74, 70, 14, 2000.00, 0.00, 28000.00),
(247, 74, 70, 4, 2000.00, 0.00, 8000.00),
(248, 75, 64, 1, 425000.00, 0.00, 425000.00),
(249, 75, 61, 10, 24500.00, 0.00, 245000.00),
(250, 75, 69, 15, 95000.00, 0.00, 1425000.00);

-- --------------------------------------------------------

--
-- Structure de la table `familles_produits`
--

CREATE TABLE `familles_produits` (
  `id` int(10) UNSIGNED NOT NULL,
  `nom` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `familles_produits`
--

INSERT INTO `familles_produits` (`id`, `nom`) VALUES
(1, 'Meubles & aménagements intérieurs'),
(2, 'Accessoires & quincaillerie de menuiserie'),
(3, 'Machines & équipements de menuiserie'),
(4, 'Panneaux & matériaux déééagencement'),
(65, 'Electricite'),
(66, 'Plomberie'),
(67, 'Peinture'),
(68, 'Quincaillerie'),
(69, 'Construction'),
(70, 'Panneaux Bois'),
(71, 'Machines Menuiserie'),
(72, 'Electromenager'),
(73, 'Accessoires');

-- --------------------------------------------------------

--
-- Structure de la table `formations`
--

CREATE TABLE `formations` (
  `id` int(10) UNSIGNED NOT NULL,
  `nom` varchar(150) NOT NULL,
  `description` text NOT NULL,
  `tarif_total` decimal(15,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `formations`
--

INSERT INTO `formations` (`id`, `nom`, `description`, `tarif_total`) VALUES
(1, 'Menuiserie moderne', 'Formation pratique en menuiserie et agencement', 150000.00),
(2, 'Agencement intérieur', 'Techniques dé?agencement et décoration intérieure', 180000.00);

-- --------------------------------------------------------

--
-- Structure de la table `fournisseurs`
--

CREATE TABLE `fournisseurs` (
  `id` int(10) UNSIGNED NOT NULL,
  `nom` varchar(150) NOT NULL,
  `contact` varchar(150) DEFAULT NULL,
  `telephone` varchar(50) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `adresse` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `fournisseurs`
--

INSERT INTO `fournisseurs` (`id`, `nom`, `contact`, `telephone`, `email`, `adresse`) VALUES
(1, 'Fournisseur Général KMS', 'Service commercial', '+237600000001', 'fournisseur@kms.local', 'Douala'),
(2, 'Import Matériaux Pro', 'Responsable achat', '+237600000002', 'imports@kms.local', 'Douala - Zone industrielle');

-- --------------------------------------------------------

--
-- Structure de la table `inscriptions_formation`
--

CREATE TABLE `inscriptions_formation` (
  `id` int(10) UNSIGNED NOT NULL,
  `date_inscription` date NOT NULL,
  `apprenant_nom` varchar(150) NOT NULL,
  `client_id` int(10) UNSIGNED DEFAULT NULL,
  `formation_id` int(10) UNSIGNED NOT NULL,
  `montant_paye` decimal(15,2) NOT NULL DEFAULT 0.00,
  `solde_du` decimal(15,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `inscriptions_formation`
--

INSERT INTO `inscriptions_formation` (`id`, `date_inscription`, `apprenant_nom`, `client_id`, `formation_id`, `montant_paye`, `solde_du`) VALUES
(1, '2025-11-19', 'Martial', NULL, 2, 50000.00, 130000.00),
(2, '2025-11-19', 'Tendop', 3, 2, 150000.00, 30000.00),
(3, '2025-11-20', 'Nkolo', NULL, 1, 80000.00, 70000.00),
(4, '2025-12-01', 'Yao Kouadio', 72, 2, 132720.00, 0.00),
(5, '2025-10-15', 'Coulibaly Kouadio', 68, 1, 106409.00, 23793.00),
(6, '2025-10-14', 'Traoré Aya', 71, 1, 94989.00, 32320.00),
(7, '2025-10-24', 'Touré Aya', 93, 2, 162388.00, 0.00),
(8, '2025-10-24', 'Coulibaly Aminata', 74, 2, 156104.00, 24804.00),
(9, '2025-10-31', 'Ouattara Aya', 78, 1, 99184.00, 24711.00),
(10, '2025-11-17', 'Coulibaly Aminata', 74, 1, 107932.00, 0.00),
(11, '2025-11-05', 'Yao Aminata', 77, 1, 40173.00, 107653.00),
(12, '2025-11-01', 'Kouassi Ibrahim', 76, 2, 49095.00, 81406.00),
(13, '2025-10-21', 'Ouattara Aya', 91, 2, 110909.00, 0.00);

--
-- Déclencheurs `inscriptions_formation`
--
DELIMITER $$
CREATE TRIGGER `after_inscription_formation_insert` AFTER INSERT ON `inscriptions_formation` FOR EACH ROW BEGIN
            IF NEW.montant_paye > 0 THEN
                INSERT INTO caisse_journal 
                (date_ecriture, montant, sens, source_type, source_id, utilisateur_id, commentaire)
                VALUES (
                    NEW.date_inscription,
                    NEW.montant_paye,
                    'ENTREE',
                    'inscription_formation',
                    NEW.id,
                    1,
                    CONCAT('Inscription formation #', NEW.id)
                );
            END IF;
        END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_inscription_formation_update` AFTER UPDATE ON `inscriptions_formation` FOR EACH ROW BEGIN
            IF NEW.montant_paye != OLD.montant_paye THEN
                
                DELETE FROM caisse_journal 
                WHERE source_type = 'inscription_formation' AND source_id = NEW.id;
                
                
                IF NEW.montant_paye > 0 THEN
                    INSERT INTO caisse_journal 
                    (date_ecriture, montant, sens, source_type, source_id, utilisateur_id, commentaire)
                    VALUES (
                        NEW.date_inscription,
                        NEW.montant_paye,
                        'ENTREE',
                        'inscription_formation',
                        NEW.id,
                        1,
                        CONCAT('Inscription formation #', NEW.id)
                    );
                END IF;
            END IF;
        END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `journal_caisse`
--

CREATE TABLE `journal_caisse` (
  `id` int(10) UNSIGNED NOT NULL,
  `date_operation` date NOT NULL,
  `numero_piece` varchar(50) NOT NULL,
  `nature_operation` text NOT NULL,
  `client_id` int(10) UNSIGNED DEFAULT NULL,
  `fournisseur_id` int(10) UNSIGNED DEFAULT NULL,
  `sens` enum('RECETTE','DEPENSE') NOT NULL,
  `montant` decimal(15,2) NOT NULL,
  `mode_paiement_id` int(10) UNSIGNED NOT NULL,
  `vente_id` int(10) UNSIGNED DEFAULT NULL,
  `reservation_id` int(10) UNSIGNED DEFAULT NULL,
  `inscription_formation_id` int(10) UNSIGNED DEFAULT NULL,
  `responsable_encaissement_id` int(10) UNSIGNED NOT NULL,
  `observations` text DEFAULT NULL,
  `est_annule` tinyint(1) NOT NULL DEFAULT 0,
  `date_annulation` datetime DEFAULT NULL,
  `annule_par_id` int(10) UNSIGNED DEFAULT NULL,
  `type_operation` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `journal_caisse`
--

INSERT INTO `journal_caisse` (`id`, `date_operation`, `numero_piece`, `nature_operation`, `client_id`, `fournisseur_id`, `sens`, `montant`, `mode_paiement_id`, `vente_id`, `reservation_id`, `inscription_formation_id`, `responsable_encaissement_id`, `observations`, `est_annule`, `date_annulation`, `annule_par_id`, `type_operation`) VALUES
(1, '2025-11-18', 'RES-1', 'Encaissement réservation hôtel', NULL, NULL, 'RECETTE', 35000.00, 4, NULL, 1, NULL, 1, '', 0, NULL, NULL, NULL),
(2, '2025-11-18', '011', 'réglement fournissuer', NULL, NULL, 'RECETTE', 10000.00, 1, NULL, NULL, NULL, 1, NULL, 0, NULL, NULL, NULL),
(3, '2025-11-19', 'INSCR-1', 'Encaissement inscription formation', NULL, NULL, 'RECETTE', 50000.00, 3, NULL, NULL, 1, 1, NULL, 0, NULL, NULL, NULL),
(4, '2025-11-20', '5', 'réglement fournissuer', NULL, NULL, 'RECETTE', 10000.00, 4, NULL, NULL, NULL, 1, NULL, 0, NULL, NULL, NULL),
(5, '2025-11-20', '', 'sorepco', NULL, NULL, 'RECETTE', 100000.00, 4, NULL, NULL, NULL, 1, 'recouvrement', 1, '2025-11-20 18:53:38', 1, NULL),
(6, '2025-11-20', '', 'versement mupeci', NULL, NULL, 'RECETTE', 1000000.00, 4, NULL, NULL, NULL, 1, 'recouvrement', 0, NULL, NULL, NULL),
(7, '2025-11-21', '', 'recouvrement sorepco', NULL, NULL, 'RECETTE', 150000.00, 4, NULL, NULL, NULL, 1, NULL, 0, NULL, NULL, NULL),
(8, '2025-12-13', 'a14', '', NULL, NULL, 'DEPENSE', 5000.00, 1, NULL, NULL, NULL, 1, NULL, 1, '2025-12-13 17:35:32', 1, NULL),
(15, '2025-11-16', 'MIG-17', 'Réservation hôtel #20', NULL, NULL, 'RECETTE', 60351.00, 1, NULL, 20, NULL, 1, 'Réservation hôtel #20', 0, NULL, NULL, 'RESERVATION_HOTEL'),
(16, '2025-11-21', 'MIG-18', 'Réservation hôtel #21', NULL, NULL, 'RECETTE', 161240.00, 1, NULL, 21, NULL, 1, 'Réservation hôtel #21', 0, NULL, NULL, 'RESERVATION_HOTEL'),
(17, '2025-10-17', 'MIG-19', 'Réservation hôtel #22', NULL, NULL, 'RECETTE', 20910.00, 1, NULL, 22, NULL, 1, 'Réservation hôtel #22', 0, NULL, NULL, 'RESERVATION_HOTEL'),
(18, '2025-11-28', 'MIG-20', 'Réservation hôtel #23', NULL, NULL, 'RECETTE', 89710.00, 1, NULL, 23, NULL, 1, 'Réservation hôtel #23', 0, NULL, NULL, 'RESERVATION_HOTEL'),
(19, '2025-10-21', 'MIG-21', 'Réservation hôtel #24', NULL, NULL, 'RECETTE', 59508.00, 1, NULL, 24, NULL, 1, 'Réservation hôtel #24', 0, NULL, NULL, 'RESERVATION_HOTEL'),
(20, '2025-10-31', 'MIG-22', 'Réservation hôtel #25', NULL, NULL, 'RECETTE', 50382.00, 1, NULL, 25, NULL, 1, 'Réservation hôtel #25', 0, NULL, NULL, 'RESERVATION_HOTEL'),
(21, '2025-11-24', 'MIG-23', 'Réservation hôtel #26', NULL, NULL, 'RECETTE', 102837.00, 1, NULL, 26, NULL, 1, 'Réservation hôtel #26', 0, NULL, NULL, 'RESERVATION_HOTEL'),
(22, '2025-10-25', 'MIG-24', 'Réservation hôtel #27', NULL, NULL, 'RECETTE', 204625.00, 1, NULL, 27, NULL, 1, 'Réservation hôtel #27', 0, NULL, NULL, 'RESERVATION_HOTEL'),
(23, '2025-12-01', 'MIG-25', 'Inscription formation #4', NULL, NULL, 'RECETTE', 132720.00, 1, NULL, NULL, 4, 1, 'Inscription formation #4', 0, NULL, NULL, 'INSCRIPTION_FORMATION'),
(24, '2025-10-15', 'MIG-26', 'Inscription formation #5', NULL, NULL, 'RECETTE', 106409.00, 1, NULL, NULL, 5, 1, 'Inscription formation #5', 0, NULL, NULL, 'INSCRIPTION_FORMATION'),
(25, '2025-10-14', 'MIG-27', 'Inscription formation #6', NULL, NULL, 'RECETTE', 94989.00, 1, NULL, NULL, 6, 1, 'Inscription formation #6', 0, NULL, NULL, 'INSCRIPTION_FORMATION'),
(26, '2025-10-24', 'MIG-28', 'Inscription formation #7', NULL, NULL, 'RECETTE', 162388.00, 1, NULL, NULL, 7, 1, 'Inscription formation #7', 0, NULL, NULL, 'INSCRIPTION_FORMATION'),
(27, '2025-10-24', 'MIG-29', 'Inscription formation #8', NULL, NULL, 'RECETTE', 156104.00, 1, NULL, NULL, 8, 1, 'Inscription formation #8', 0, NULL, NULL, 'INSCRIPTION_FORMATION'),
(28, '2025-10-31', 'MIG-30', 'Inscription formation #9', NULL, NULL, 'RECETTE', 99184.00, 1, NULL, NULL, 9, 1, 'Inscription formation #9', 0, NULL, NULL, 'INSCRIPTION_FORMATION'),
(29, '2025-11-17', 'MIG-31', 'Inscription formation #10', NULL, NULL, 'RECETTE', 107932.00, 1, NULL, NULL, 10, 1, 'Inscription formation #10', 0, NULL, NULL, 'INSCRIPTION_FORMATION'),
(30, '2025-11-05', 'MIG-32', 'Inscription formation #11', NULL, NULL, 'RECETTE', 40173.00, 1, NULL, NULL, 11, 1, 'Inscription formation #11', 0, NULL, NULL, 'INSCRIPTION_FORMATION'),
(31, '2025-11-01', 'MIG-33', 'Inscription formation #12', NULL, NULL, 'RECETTE', 49095.00, 1, NULL, NULL, 12, 1, 'Inscription formation #12', 0, NULL, NULL, 'INSCRIPTION_FORMATION'),
(32, '2025-10-21', 'MIG-34', 'Inscription formation #13', NULL, NULL, 'RECETTE', 110909.00, 1, NULL, NULL, 13, 1, 'Inscription formation #13', 0, NULL, NULL, 'INSCRIPTION_FORMATION'),
(33, '2025-10-28', 'MIG-35', 'Paiement vente', NULL, NULL, 'RECETTE', 5276600.00, 1, 58, NULL, NULL, 1, 'Paiement vente', 0, NULL, NULL, 'VENTE'),
(34, '2025-11-15', 'MIG-36', 'Paiement vente', NULL, NULL, 'RECETTE', 3065800.00, 1, 63, NULL, NULL, 1, 'Paiement vente', 0, NULL, NULL, 'VENTE'),
(35, '2025-11-23', 'MIG-37', 'Paiement vente', NULL, NULL, 'RECETTE', 512500.00, 1, 66, NULL, NULL, 1, 'Paiement vente', 0, NULL, NULL, 'VENTE'),
(36, '2025-12-21', 'MIG-38', 'Paiement vente', NULL, NULL, 'RECETTE', 2744000.00, 1, 71, NULL, NULL, 1, 'Paiement vente', 0, NULL, NULL, 'VENTE'),
(37, '2025-12-01', 'MIG-39', 'Paiement vente', NULL, NULL, 'RECETTE', 2095000.00, 1, 72, NULL, NULL, 1, 'Paiement vente', 0, NULL, NULL, 'VENTE'),
(38, '2025-12-05', 'MIG-40', 'Paiement vente', NULL, NULL, 'RECETTE', 91500.00, 1, 76, NULL, NULL, 1, 'Paiement vente', 0, NULL, NULL, 'VENTE'),
(39, '2025-12-09', 'MIG-41', 'Paiement vente', NULL, NULL, 'RECETTE', 1280000.00, 1, 82, NULL, NULL, 1, 'Paiement vente', 0, NULL, NULL, 'VENTE'),
(40, '2025-11-20', 'MIG-42', 'Paiement vente', NULL, NULL, 'RECETTE', 4452000.00, 1, 83, NULL, NULL, 1, 'Paiement vente', 0, NULL, NULL, 'VENTE'),
(41, '2025-12-08', 'MIG-43', 'Paiement vente', NULL, NULL, 'RECETTE', 987500.00, 1, 86, NULL, NULL, 1, 'Paiement vente', 0, NULL, NULL, 'VENTE'),
(42, '2025-11-08', 'MIG-44', 'Paiement vente', NULL, NULL, 'RECETTE', 1379650.00, 1, 87, NULL, NULL, 1, 'Paiement vente', 0, NULL, NULL, 'VENTE'),
(46, '2025-12-14', 'TEST-145206-1', 'Vente client A', NULL, NULL, 'RECETTE', 25000.00, 1, NULL, NULL, NULL, 1, 'Vente client A', 0, NULL, NULL, 'VENTE'),
(47, '2025-12-14', 'TEST-145206-2', 'Vente client B', NULL, NULL, 'RECETTE', 30000.00, 1, NULL, NULL, NULL, 1, 'Vente client B', 0, NULL, NULL, 'VENTE'),
(48, '2025-12-14', 'TEST-145206-3', 'Encaissement formation', NULL, NULL, 'RECETTE', 15000.00, 1, NULL, NULL, NULL, 1, 'Encaissement formation', 0, NULL, NULL, 'VENTE'),
(49, '2025-12-14', 'TEST-145206-4', 'Frais opérationnels', NULL, NULL, 'DEPENSE', 5000.00, 1, NULL, NULL, NULL, 1, 'Frais opérationnels', 0, NULL, NULL, 'AUTRE'),
(50, '2025-12-14', 'V-20251214-143828', 'Encaissement vente V-20251214-143828', NULL, NULL, 'RECETTE', 665415.00, 1, NULL, NULL, NULL, 1, 'Encaissement vente V-20251214-143828', 0, NULL, NULL, 'VENTE'),
(51, '2025-12-14', 'V-20251214-143828', 'Encaissement vente V-20251214-143828', NULL, NULL, 'RECETTE', 665415.00, 1, NULL, NULL, NULL, 1, 'Encaissement vente V-20251214-143828', 0, NULL, NULL, 'VENTE'),
(52, '2025-12-14', 'V-20251214-143828', 'Encaissement vente V-20251214-143828', NULL, NULL, 'RECETTE', 665415.00, 1, NULL, NULL, NULL, 1, 'Encaissement vente V-20251214-143828', 0, NULL, NULL, 'VENTE'),
(53, '2025-12-14', 'V-20251214-143828', 'Encaissement vente V-20251214-143828', NULL, NULL, 'RECETTE', 665415.00, 1, NULL, NULL, NULL, 1, 'Encaissement vente V-20251214-143828', 0, NULL, NULL, 'VENTE'),
(54, '2025-12-14', 'V-20251214-143828', 'Encaissement vente V-20251214-143828', NULL, NULL, 'RECETTE', 665415.00, 1, NULL, NULL, NULL, 1, 'Encaissement vente V-20251214-143828', 0, NULL, NULL, 'VENTE'),
(55, '2025-12-14', 'V-20251214-143828', 'Encaissement vente V-20251214-143828', NULL, NULL, 'RECETTE', 665415.00, 1, 90, NULL, NULL, 1, 'Encaissement vente V-20251214-143828', 0, NULL, NULL, 'VENTE'),
(56, '2025-12-14', 'VTE-20251107-031', 'Encaissement vente VTE-20251107-031', NULL, NULL, 'RECETTE', 1379650.00, 1, 87, NULL, NULL, 1, 'Encaissement vente VTE-20251107-031', 0, NULL, NULL, 'VENTE'),
(58, '2025-12-14', 'CAI-20251214-0001', 'Vente comptoir #1001', NULL, NULL, 'RECETTE', 45000.00, 1, NULL, NULL, NULL, 1, 'Données de test', 0, NULL, NULL, 'TEST'),
(59, '2025-12-14', 'CAI-20251214-0002', 'Vente comptoir #1002', NULL, NULL, 'RECETTE', 25000.00, 1, NULL, NULL, NULL, 1, 'Données de test', 0, NULL, NULL, 'TEST'),
(60, '2025-12-14', 'CAI-20251214-0003', 'Vente entreprise #1003', NULL, NULL, 'RECETTE', 150000.00, 2, NULL, NULL, NULL, 1, 'Données de test', 0, NULL, NULL, 'TEST'),
(61, '2025-12-14', 'CAI-20251214-0004', 'Vente mobile #1004', NULL, NULL, 'RECETTE', 35000.00, 3, NULL, NULL, NULL, 1, 'Données de test', 0, NULL, NULL, 'TEST'),
(62, '2025-12-14', 'CAI-20251214-0005', 'Vente #1005', NULL, NULL, 'RECETTE', 50000.00, 4, NULL, NULL, NULL, 1, 'Données de test', 0, NULL, NULL, 'TEST'),
(63, '2025-12-14', 'CAI-20251214-0006', 'Remise espèces en banque', NULL, NULL, 'DEPENSE', 100000.00, 1, NULL, NULL, NULL, 1, 'Données de test', 0, NULL, NULL, 'TEST'),
(64, '2025-12-14', 'CAI-20251214-0007', 'Achat fournitures bureau', NULL, NULL, 'DEPENSE', 15000.00, 1, NULL, NULL, NULL, 1, 'Données de test', 0, NULL, NULL, 'TEST'),
(65, '2025-12-14', 'CAI-20251214-0008', 'Vente #1006', NULL, NULL, 'RECETTE', 75000.00, 1, NULL, NULL, NULL, 1, 'Données de test', 0, NULL, NULL, 'TEST'),
(66, '2025-12-14', 'CAI-20251214-0009', 'Vente #1007', NULL, NULL, 'RECETTE', 60000.00, 1, NULL, NULL, NULL, 1, 'Données de test', 0, NULL, NULL, 'TEST'),
(67, '2025-12-14', 'CAI-20251214-0010', 'Paiement fournisseur', NULL, NULL, 'DEPENSE', 50000.00, 2, NULL, NULL, NULL, 1, 'Données de test', 0, NULL, NULL, 'TEST'),
(68, '2025-12-14', 'V-TEST-20251214-221409', 'Vente V-TEST-20251214-221409', NULL, NULL, 'RECETTE', 214650.00, 1, NULL, NULL, NULL, 1, 'Vente V-TEST-20251214-221409', 0, NULL, NULL, 'VENTE');

-- --------------------------------------------------------

--
-- Structure de la table `kpis_quotidiens`
--

CREATE TABLE `kpis_quotidiens` (
  `id` int(10) UNSIGNED NOT NULL,
  `date` date NOT NULL,
  `canal` enum('SHOWROOM','TERRAIN','DIGITAL','HOTEL','FORMATION','GLOBAL') NOT NULL,
  `nb_visiteurs` int(11) DEFAULT 0,
  `nb_leads` int(11) DEFAULT 0,
  `nb_devis` int(11) DEFAULT 0,
  `nb_ventes` int(11) DEFAULT 0,
  `ca_realise` decimal(15,2) DEFAULT 0.00,
  `date_maj` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `leads_digital`
--

CREATE TABLE `leads_digital` (
  `id` int(10) UNSIGNED NOT NULL,
  `date_lead` date NOT NULL,
  `nom_prospect` varchar(150) NOT NULL,
  `telephone` varchar(50) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `source` enum('FACEBOOK','INSTAGRAM','WHATSAPP','SITE_WEB','TIKTOK','LINKEDIN','AUTRE') NOT NULL DEFAULT 'FACEBOOK',
  `message_initial` text DEFAULT NULL,
  `produit_interet` varchar(255) DEFAULT NULL,
  `statut` enum('NOUVEAU','CONTACTE','QUALIFIE','DEVIS_ENVOYE','CONVERTI','PERDU') NOT NULL DEFAULT 'NOUVEAU',
  `score_prospect` int(11) DEFAULT 0 COMMENT 'Score 0-100 selon int??r??t/qualit??',
  `date_dernier_contact` datetime DEFAULT NULL,
  `prochaine_action` varchar(255) DEFAULT NULL,
  `date_prochaine_action` date DEFAULT NULL,
  `client_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'Rempli apr??s conversion',
  `utilisateur_responsable_id` int(10) UNSIGNED DEFAULT NULL,
  `campagne` varchar(150) DEFAULT NULL COMMENT 'Nom de la campagne publicitaire',
  `cout_acquisition` decimal(15,2) DEFAULT 0.00 COMMENT 'Co??t pub si applicable',
  `observations` text DEFAULT NULL,
  `date_creation` datetime NOT NULL DEFAULT current_timestamp(),
  `date_conversion` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Leads digitaux (Facebook, Instagram, WhatsApp, etc.)';

-- --------------------------------------------------------

--
-- Structure de la table `modes_paiement`
--

CREATE TABLE `modes_paiement` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` varchar(50) NOT NULL,
  `libelle` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `modes_paiement`
--

INSERT INTO `modes_paiement` (`id`, `code`, `libelle`) VALUES
(1, 'CASH', 'Espéces'),
(2, 'VIREMENT', 'Virement bancaire'),
(3, 'MOBILE_MONEY', 'Mobile Money'),
(4, 'CHEQUE', 'Chéque');

-- --------------------------------------------------------

--
-- Structure de la table `mouvements_stock_backup_20251209_161710`
--

CREATE TABLE `mouvements_stock_backup_20251209_161710` (
  `id` int(10) UNSIGNED NOT NULL,
  `date_mouvement` date NOT NULL,
  `type_mouvement` enum('ENTREE','SORTIE','CORRECTION') NOT NULL,
  `produit_id` int(10) UNSIGNED NOT NULL,
  `quantite` int(11) NOT NULL,
  `source_module` varchar(50) DEFAULT NULL,
  `source_id` int(10) UNSIGNED DEFAULT NULL,
  `utilisateur_id` int(10) UNSIGNED DEFAULT NULL,
  `commentaire` varchar(255) DEFAULT NULL,
  `date_creation` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `mouvements_stock_backup_20251209_161710`
--

INSERT INTO `mouvements_stock_backup_20251209_161710` (`id`, `date_mouvement`, `type_mouvement`, `produit_id`, `quantite`, `source_module`, `source_id`, `utilisateur_id`, `commentaire`, `date_creation`) VALUES
(1, '2025-11-21', 'ENTREE', 1, 22, 'ACHAT', 55222, NULL, NULL, '2025-11-21 12:50:59'),
(3, '2025-11-26', 'SORTIE', 3, 3, 'VENTE', 20, NULL, 'Sortie suite é la vente V-20251126-170324', '2025-11-26 17:04:15'),
(4, '2025-11-26', 'ENTREE', 2, 25, 'ACHAT', 2, NULL, 'Entrée suite é lé?achat AC-20251126-170544', '2025-11-26 17:05:44'),
(5, '2025-12-02', 'ENTREE', 3, 25, 'ACHAT', 3, NULL, 'Entrée suite é lé?achat AC-20251202-154014', '2025-12-02 15:40:14');

-- --------------------------------------------------------

--
-- Structure de la table `objectifs_commerciaux`
--

CREATE TABLE `objectifs_commerciaux` (
  `id` int(10) UNSIGNED NOT NULL,
  `annee` int(11) NOT NULL,
  `mois` int(11) DEFAULT NULL COMMENT 'NULL = objectif annuel',
  `canal` enum('SHOWROOM','TERRAIN','DIGITAL','HOTEL','FORMATION','GLOBAL') NOT NULL DEFAULT 'GLOBAL',
  `objectif_ca` decimal(15,2) NOT NULL DEFAULT 0.00,
  `objectif_nb_ventes` int(11) DEFAULT NULL,
  `objectif_nb_leads` int(11) DEFAULT NULL,
  `realise_ca` decimal(15,2) DEFAULT 0.00,
  `realise_nb_ventes` int(11) DEFAULT 0,
  `date_creation` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `ordres_preparation`
--

CREATE TABLE `ordres_preparation` (
  `id` int(10) UNSIGNED NOT NULL,
  `numero_ordre` varchar(50) NOT NULL,
  `date_ordre` date NOT NULL,
  `vente_id` int(10) UNSIGNED DEFAULT NULL,
  `devis_id` int(10) UNSIGNED DEFAULT NULL,
  `client_id` int(10) UNSIGNED NOT NULL,
  `type_commande` enum('VENTE_SHOWROOM','VENTE_TERRAIN','VENTE_DIGITAL','RESERVATION_HOTEL','AUTRE') DEFAULT 'VENTE_SHOWROOM',
  `commercial_responsable_id` int(10) UNSIGNED NOT NULL,
  `statut` enum('EN_ATTENTE','EN_PREPARATION','PRET','LIVRE','ANNULE') NOT NULL DEFAULT 'EN_ATTENTE',
  `date_preparation_demandee` date DEFAULT NULL,
  `priorite` enum('NORMALE','URGENTE','TRES_URGENTE') DEFAULT 'NORMALE',
  `observations` text DEFAULT NULL,
  `signature_resp_marketing` tinyint(1) DEFAULT 0 COMMENT 'Validation RESP MARKETING',
  `date_signature_marketing` datetime DEFAULT NULL,
  `magasinier_id` int(10) UNSIGNED DEFAULT NULL,
  `date_preparation_effectuee` datetime DEFAULT NULL,
  `date_creation` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Ordres de pr??paration (liaison marketing-magasin)';

--
-- Déchargement des données de la table `ordres_preparation`
--

INSERT INTO `ordres_preparation` (`id`, `numero_ordre`, `date_ordre`, `vente_id`, `devis_id`, `client_id`, `type_commande`, `commercial_responsable_id`, `statut`, `date_preparation_demandee`, `priorite`, `observations`, `signature_resp_marketing`, `date_signature_marketing`, `magasinier_id`, `date_preparation_effectuee`, `date_creation`) VALUES
(1, 'OP-20251213-0001', '2025-12-13', 89, NULL, 105, 'VENTE_SHOWROOM', 1, 'EN_ATTENTE', '2025-12-13', 'NORMALE', '', 0, NULL, 6, NULL, '2025-12-13 21:45:38');

-- --------------------------------------------------------

--
-- Structure de la table `ordres_preparation_lignes`
--

CREATE TABLE `ordres_preparation_lignes` (
  `id` int(10) UNSIGNED NOT NULL,
  `ordre_preparation_id` int(10) UNSIGNED NOT NULL,
  `produit_id` int(10) UNSIGNED NOT NULL,
  `quantite_demandee` decimal(15,3) NOT NULL,
  `quantite_preparee` decimal(15,3) DEFAULT 0.000,
  `observations` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `parametres_securite`
--

CREATE TABLE `parametres_securite` (
  `id` int(10) UNSIGNED NOT NULL,
  `cle` varchar(100) NOT NULL,
  `valeur` text NOT NULL,
  `type` enum('STRING','INT','BOOL','JSON') DEFAULT 'STRING',
  `description` text DEFAULT NULL,
  `modifie_par` int(10) UNSIGNED DEFAULT NULL,
  `date_modification` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Configuration de s??curit?? globale';

--
-- Déchargement des données de la table `parametres_securite`
--

INSERT INTO `parametres_securite` (`id`, `cle`, `valeur`, `type`, `description`, `modifie_par`, `date_modification`) VALUES
(1, '2fa_obligatoire_admin', '1', 'BOOL', 'Forcer 2FA pour tous les administrateurs', NULL, '2025-12-13 12:40:26'),
(2, '2fa_obligatoire_tous', '0', 'BOOL', 'Forcer 2FA pour tous les utilisateurs', NULL, '2025-12-13 12:40:26'),
(3, 'session_timeout_minutes', '120', 'INT', 'Durée de session inactive en minutes', NULL, '2025-12-13 20:28:50'),
(4, 'max_sessions_simultanees', '3', 'INT', 'Nombre max de sessions simultanées par utilisateur', NULL, '2025-12-13 20:28:50'),
(5, 'login_max_attempts', '5', 'INT', 'Tentatives de connexion max avant blocage', NULL, '2025-12-13 12:40:26'),
(6, 'login_block_duration_minutes', '60', 'INT', 'Durée de blocage aprés échecs répétés', NULL, '2025-12-13 20:28:50'),
(7, 'password_min_length', '8', 'INT', 'Longueur minimale du mot de passe', NULL, '2025-12-13 12:40:26'),
(8, 'password_require_special', '1', 'BOOL', 'Exiger caractéres spéciaux dans mot de passe', NULL, '2025-12-13 20:28:50'),
(9, 'password_require_number', '1', 'BOOL', 'Exiger chiffres dans mot de passe', NULL, '2025-12-13 12:40:26'),
(10, 'password_require_uppercase', '1', 'BOOL', 'Exiger majuscules dans mot de passe', NULL, '2025-12-13 12:40:26'),
(11, 'password_expiration_days', '90', 'INT', 'Expiration mot de passe (0 = jamais)', NULL, '2025-12-13 12:40:26'),
(12, 'audit_retention_days', '365', 'INT', 'Durée conservation logs audit', NULL, '2025-12-13 20:28:50'),
(13, 'redis_enabled', '1', 'BOOL', 'Activer le cache Redis', NULL, '2025-12-13 12:40:26'),
(14, 'rate_limit_enabled', '1', 'BOOL', 'Activer le rate limiting', NULL, '2025-12-13 12:40:26'),
(15, 'sms_provider', 'mock', 'STRING', 'Provider SMS (twilio, vonage, mock)', NULL, '2025-12-13 12:52:56'),
(16, 'sms_max_tentatives_jour', '10', 'STRING', 'Nombre max de codes SMS par jour et par utilisateur', NULL, '2025-12-13 12:52:56'),
(17, 'sms_delai_renvoi_secondes', '60', 'STRING', 'Délai minimum entre 2 envois de code (en secondes)', NULL, '2025-12-13 20:28:50');

-- --------------------------------------------------------

--
-- Structure de la table `permissions`
--

CREATE TABLE `permissions` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `permissions`
--

INSERT INTO `permissions` (`id`, `code`, `description`) VALUES
(1, 'PRODUITS_LIRE', 'Consulter le catalogue produits et les stocks'),
(2, 'PRODUITS_CREER', 'Créer de nouveaux produits'),
(3, 'PRODUITS_MODIFIER', 'Modifier les produits existants'),
(4, 'PRODUITS_SUPPRIMER', 'Supprimer des produits'),
(5, 'CLIENTS_LIRE', 'Consulter les clients / prospects'),
(6, 'CLIENTS_CREER', 'Créer ou modifier des clients'),
(7, 'DEVIS_LIRE', 'Lister et consulter les devis'),
(8, 'DEVIS_CREER', 'Créer des devis'),
(9, 'DEVIS_MODIFIER', 'Modifier le statut ou le contenu des devis'),
(10, 'VENTES_LIRE', 'Consulter les ventes et bons de livraison'),
(11, 'VENTES_CREER', 'Créer des ventes'),
(12, 'VENTES_VALIDER', 'Valider des ventes / livraisons'),
(13, 'CAISSE_LIRE', 'Consulter le journal de caisse'),
(14, 'CAISSE_ECRIRE', 'Enregistrer des opérations de caisse'),
(15, 'PROMOTIONS_GERER', 'Créer et gérer les promotions'),
(16, 'HOTEL_GERER', 'Gérer les réservations hôtel et upsell'),
(17, 'FORMATION_GERER', 'Gérer les formations et inscriptions'),
(18, 'REPORTING_LIRE', 'Accéder aux tableaux de bord et reporting'),
(19, 'SATISFACTION_GERER', 'Gérer les enquétes de satisfaction client'),
(20, 'ACHATS_GERER', 'Gérer les achats et approvisionnements'),
(21, 'COMPTABILITE_LIRE', 'Consulter le module comptabilité'),
(22, 'COMPTABILITE_ECRIRE', 'Enregistrer des écritures comptables'),
(23, 'UTILISATEURS_GERER', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `produits`
--

CREATE TABLE `produits` (
  `id` int(10) UNSIGNED NOT NULL,
  `code_produit` varchar(100) NOT NULL,
  `famille_id` int(10) UNSIGNED NOT NULL,
  `sous_categorie_id` int(10) UNSIGNED DEFAULT NULL,
  `designation` varchar(255) NOT NULL,
  `caracteristiques` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `fournisseur_id` int(10) UNSIGNED DEFAULT NULL,
  `localisation` varchar(150) DEFAULT NULL,
  `prix_achat` decimal(15,2) NOT NULL DEFAULT 0.00,
  `prix_vente` decimal(15,2) NOT NULL DEFAULT 0.00,
  `stock_actuel` int(11) NOT NULL DEFAULT 0,
  `seuil_alerte` int(11) NOT NULL DEFAULT 0,
  `image_path` varchar(255) DEFAULT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `date_creation` datetime NOT NULL DEFAULT current_timestamp(),
  `date_modification` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `produits`
--

INSERT INTO `produits` (`id`, `code_produit`, `famille_id`, `sous_categorie_id`, `designation`, `caracteristiques`, `description`, `fournisseur_id`, `localisation`, `prix_achat`, `prix_vente`, `stock_actuel`, `seuil_alerte`, `image_path`, `actif`, `date_creation`, `date_modification`) VALUES
(1, 'MEU-CH-001', 1, 1, 'Lit 2 places avec chevets', 'Dimensions 160x200', 'Lit moderne pour chambre parentale', 1, 'Showroom Douala', 120000.00, 180000.00, -1, 2, '/assets/img/produits/MEU-CH-001.png', 1, '2025-11-18 11:00:22', '2025-12-02 15:58:23'),
(2, 'MEU-SAL-001', 1, 2, 'Salon 5 places', 'Structure bois, mousse haute densité', 'Salon complet 3+1+1', 1, 'Showroom Douala', 200000.00, 280000.00, 0, 1, NULL, 1, '2025-11-18 11:00:22', NULL),
(17, 'TEST-PRD-001', 1, NULL, 'Produit test automatiséé', NULL, NULL, NULL, NULL, 0.00, 1500.00, 3, 0, NULL, 0, '2025-12-10 13:09:46', NULL),
(18, 'CBL-001', 65, NULL, 'Cable electrique 2.5mm2', NULL, NULL, NULL, NULL, 25000.00, 45000.00, 0, 10, NULL, 1, '2025-12-13 16:21:47', NULL),
(19, 'DISJ-001', 65, NULL, 'Disjoncteur 16A', NULL, NULL, NULL, NULL, 5000.00, 8500.00, 0, 10, NULL, 1, '2025-12-13 16:21:47', NULL),
(20, 'PRISE-001', 65, NULL, 'Prise double', NULL, NULL, NULL, NULL, 1500.00, 2500.00, 0, 10, NULL, 1, '2025-12-13 16:21:47', NULL),
(21, 'TUY-001', 66, NULL, 'Tube PVC 110mm', NULL, NULL, NULL, NULL, 7000.00, 12000.00, 0, 10, NULL, 1, '2025-12-13 16:21:47', NULL),
(22, 'ROB-001', 66, NULL, 'Robinet chrome', NULL, NULL, NULL, NULL, 9000.00, 15000.00, 0, 10, NULL, 1, '2025-12-13 16:21:47', NULL),
(23, 'WC-001', 66, NULL, 'WC complet', NULL, NULL, NULL, NULL, 50000.00, 85000.00, 0, 10, NULL, 1, '2025-12-13 16:21:47', NULL),
(24, 'PEIN-001', 67, NULL, 'Peinture int 25L', NULL, NULL, NULL, NULL, 20000.00, 35000.00, 0, 10, NULL, 1, '2025-12-13 16:21:47', NULL),
(25, 'PEIN-002', 67, NULL, 'Peinture ext 25L', NULL, NULL, NULL, NULL, 25000.00, 42000.00, 0, 10, NULL, 1, '2025-12-13 16:21:47', NULL),
(26, 'MART-001', 68, NULL, 'Marteau 500g', NULL, NULL, NULL, NULL, 3500.00, 6500.00, 0, 10, NULL, 1, '2025-12-13 16:21:47', NULL),
(27, 'SCIE-001', 68, NULL, 'Scie metaux', NULL, NULL, NULL, NULL, 5000.00, 8500.00, 0, 10, NULL, 1, '2025-12-13 16:21:47', NULL),
(28, 'CIM-001', 69, NULL, 'Ciment 50kg', NULL, NULL, NULL, NULL, 3200.00, 5500.00, 0, 10, NULL, 1, '2025-12-13 16:21:47', NULL),
(29, 'BRIQUE-001', 69, NULL, 'Brique creuse', NULL, NULL, NULL, NULL, 150.00, 250.00, 0, 10, NULL, 1, '2025-12-13 16:21:47', NULL),
(30, 'CARR-001', 69, NULL, 'Carreau 40x40', NULL, NULL, NULL, NULL, 5000.00, 8500.00, 0, 10, NULL, 1, '2025-12-13 16:21:47', NULL),
(59, 'PAN-CTBX18', 70, NULL, 'Panneau CTBX 18mm 1220x2440', NULL, NULL, NULL, NULL, 22000.00, 29500.00, -14, 10, NULL, 1, '2025-12-13 17:33:50', NULL),
(60, 'PAN-MDF16', 70, NULL, 'Panneau MDF 16mm 1220x2440', NULL, NULL, NULL, NULL, 9500.00, 13200.00, -13, 10, NULL, 1, '2025-12-13 17:33:50', NULL),
(61, 'PAN-MULTI21', 70, NULL, 'Multiplex 21mm 1220x2440', NULL, NULL, NULL, NULL, 18000.00, 24500.00, 0, 10, NULL, 1, '2025-12-13 17:33:50', NULL),
(62, 'MAC-SCIE210', 71, NULL, 'Scie a ruban 210W professionnelle', NULL, NULL, NULL, NULL, 145000.00, 185000.00, 0, 10, NULL, 1, '2025-12-13 17:33:50', NULL),
(63, 'MAC-RABOTEUSE', 71, NULL, 'Raboteuse 305mm', NULL, NULL, NULL, NULL, 260000.00, 320000.00, -9, 10, NULL, 1, '2025-12-13 17:33:50', NULL),
(64, 'MAC-TOUPIE', 71, NULL, 'Toupie 2200W', NULL, NULL, NULL, NULL, 350000.00, 425000.00, -19, 10, NULL, 1, '2025-12-13 17:33:50', NULL),
(65, 'QUI-CHARN90', 68, NULL, 'Charniere inox 90deg (paire)', NULL, NULL, NULL, NULL, 600.00, 950.00, -3, 10, NULL, 1, '2025-12-13 17:33:50', NULL),
(66, 'QUI-GLISS50', 68, NULL, 'Glissiere telescopique 500mm', NULL, NULL, NULL, NULL, 3000.00, 4200.00, 0, 10, NULL, 1, '2025-12-13 17:33:50', NULL),
(67, 'QUI-POIGN160', 68, NULL, 'Poignee aluminium 160mm', NULL, NULL, NULL, NULL, 750.00, 1200.00, -7, 10, NULL, 1, '2025-12-13 17:33:50', NULL),
(68, 'ELM-FOUR', 72, NULL, 'Four encastrable inox 60cm', NULL, NULL, NULL, NULL, 145000.00, 185000.00, -25, 10, NULL, 1, '2025-12-13 17:33:50', NULL),
(69, 'ELM-PLAQUE', 72, NULL, 'Plaque vitroceramique 4 feux', NULL, NULL, NULL, NULL, 72000.00, 95000.00, -4, 10, NULL, 1, '2025-12-13 17:33:50', NULL),
(70, 'ACC-VIS430', 73, NULL, 'Vis noire 4x30mm (boite 100)', NULL, NULL, NULL, NULL, 1200.00, 2000.00, -28, 10, NULL, 1, '2025-12-13 17:33:50', NULL),
(71, 'ACC-COLLE', 73, NULL, 'Colle bois pro 750ml', NULL, NULL, NULL, NULL, 5500.00, 8500.00, -21, 10, NULL, 1, '2025-12-13 17:33:50', NULL),
(72, 'ACC-VERNIS', 73, NULL, 'Vernis brillant 1L', NULL, NULL, NULL, NULL, 8000.00, 12500.00, -10, 10, NULL, 1, '2025-12-13 17:33:50', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `promotions`
--

CREATE TABLE `promotions` (
  `id` int(10) UNSIGNED NOT NULL,
  `nom` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `pourcentage_remise` decimal(5,2) DEFAULT NULL,
  `montant_remise` decimal(15,2) DEFAULT NULL,
  `date_debut` date NOT NULL,
  `date_fin` date NOT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `promotion_produit`
--

CREATE TABLE `promotion_produit` (
  `promotion_id` int(10) UNSIGNED NOT NULL,
  `produit_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `prospections_terrain`
--

CREATE TABLE `prospections_terrain` (
  `id` int(10) UNSIGNED NOT NULL,
  `date_prospection` date NOT NULL,
  `heure_prospection` time DEFAULT NULL,
  `prospect_nom` varchar(150) NOT NULL,
  `secteur` varchar(150) NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `adresse_gps` varchar(500) DEFAULT NULL,
  `besoin_identifie` text NOT NULL,
  `action_menee` text NOT NULL,
  `resultat` text NOT NULL,
  `prochaine_etape` text DEFAULT NULL,
  `client_id` int(10) UNSIGNED DEFAULT NULL,
  `commercial_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `prospections_terrain`
--

INSERT INTO `prospections_terrain` (`id`, `date_prospection`, `heure_prospection`, `prospect_nom`, `secteur`, `latitude`, `longitude`, `adresse_gps`, `besoin_identifie`, `action_menee`, `resultat`, `prochaine_etape`, `client_id`, `commercial_id`) VALUES
(1, '2025-12-11', '12:26:30', 'MR Yves', 'Pindo', 4.05880337, 9.78497912, 'Pindo, Douala III, Communauté urbaine de Douala, Wouri, Région du Littoral, Cameroun', 'Deligneuse', 'Prospection et remise de la fiche produit', 'Intéressé - é recontacter', 'relancer', NULL, 1),
(2, '2025-12-12', '15:17:00', 'Zoboo', 'Ndogmbe', 4.04000000, 9.75000000, 'Ndogmbe, Douala III, Communauté urbaine de Douala, Wouri, Littoral, Cameroon', 'machines de ménuiserie', 'prospection et prise de rendez-vous au centre commercial', 'é rappeler plus tard', 'Relancer dans une semaine', NULL, 1),
(3, '2025-12-12', '15:24:38', 'Kossi', 'Non renseigné', 4.04000000, 9.75000000, NULL, 'efezfe', 'fezfzeefd', 'Devis demandé', 'zerfzfze', NULL, 1);

-- --------------------------------------------------------

--
-- Structure de la table `prospects_formation`
--

CREATE TABLE `prospects_formation` (
  `id` int(10) UNSIGNED NOT NULL,
  `date_prospect` date NOT NULL,
  `nom_prospect` varchar(150) NOT NULL,
  `contact` varchar(100) NOT NULL,
  `source` varchar(100) DEFAULT NULL,
  `statut_actuel` varchar(100) NOT NULL,
  `client_id` int(10) UNSIGNED DEFAULT NULL,
  `utilisateur_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `prospects_formation`
--

INSERT INTO `prospects_formation` (`id`, `date_prospect`, `nom_prospect`, `contact`, `source`, `statut_actuel`, `client_id`, `utilisateur_id`) VALUES
(1, '2025-11-01', 'Anicet Mballa', '655585502', 'facebook', 'En cours', NULL, 1);

-- --------------------------------------------------------

--
-- Structure de la table `relances_devis`
--

CREATE TABLE `relances_devis` (
  `id` int(10) UNSIGNED NOT NULL,
  `devis_id` int(10) UNSIGNED NOT NULL,
  `date_relance` date NOT NULL,
  `type_relance` enum('TELEPHONE','EMAIL','SMS','WHATSAPP','VISITE') NOT NULL DEFAULT 'TELEPHONE',
  `utilisateur_id` int(10) UNSIGNED NOT NULL,
  `commentaires` text DEFAULT NULL,
  `prochaine_action` varchar(255) DEFAULT NULL,
  `date_prochaine_action` date DEFAULT NULL,
  `date_creation` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `rendezvous_terrain`
--

CREATE TABLE `rendezvous_terrain` (
  `id` int(10) UNSIGNED NOT NULL,
  `date_rdv` date NOT NULL,
  `heure_rdv` time NOT NULL,
  `client_prospect_nom` varchar(150) NOT NULL,
  `lieu` varchar(150) NOT NULL,
  `objectif` text NOT NULL,
  `statut` enum('PLANIFIE','CONFIRME','ANNULE','HONORE') NOT NULL DEFAULT 'PLANIFIE',
  `client_id` int(10) UNSIGNED DEFAULT NULL,
  `commercial_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `reservations_hotel`
--

CREATE TABLE `reservations_hotel` (
  `id` int(10) UNSIGNED NOT NULL,
  `date_reservation` date NOT NULL,
  `client_id` int(10) UNSIGNED NOT NULL,
  `chambre_id` int(10) UNSIGNED NOT NULL,
  `date_debut` date NOT NULL,
  `date_fin` date NOT NULL,
  `nb_nuits` int(11) NOT NULL,
  `montant_total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `statut` enum('EN_COURS','TERMINEE','ANNULEE') NOT NULL DEFAULT 'EN_COURS',
  `mode_paiement_id` int(10) UNSIGNED DEFAULT NULL,
  `concierge_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `reservations_hotel`
--

INSERT INTO `reservations_hotel` (`id`, `date_reservation`, `client_id`, `chambre_id`, `date_debut`, `date_fin`, `nb_nuits`, `montant_total`, `statut`, `mode_paiement_id`, `concierge_id`) VALUES
(1, '2025-11-18', 5, 2, '2025-11-18', '2025-11-18', 1, 35000.00, 'EN_COURS', 4, 1),
(2, '2025-11-18', 4, 2, '2025-11-18', '2025-11-20', 2, 70000.00, 'EN_COURS', NULL, 1),
(3, '2025-12-13', 23, 1, '2025-12-13', '2025-12-13', 1, 20000.00, 'EN_COURS', 1, 1),
(20, '2025-11-16', 76, 2, '2025-11-21', '2025-11-24', 3, 60351.00, '', NULL, 1),
(21, '2025-11-21', 95, 1, '2025-11-26', '2025-11-30', 4, 161240.00, '', NULL, 1),
(22, '2025-10-17', 85, 2, '2025-10-22', '2025-10-23', 1, 20910.00, '', NULL, 1),
(23, '2025-11-28', 67, 1, '2025-12-03', '2025-12-05', 2, 89710.00, '', NULL, 1),
(24, '2025-10-21', 88, 2, '2025-10-26', '2025-10-28', 2, 59508.00, '', NULL, 1),
(25, '2025-10-31', 88, 1, '2025-11-05', '2025-11-07', 2, 50382.00, '', NULL, 1),
(26, '2025-11-24', 74, 1, '2025-11-29', '2025-12-02', 3, 102837.00, '', NULL, 1),
(27, '2025-10-25', 82, 1, '2025-10-30', '2025-11-04', 5, 204625.00, '', NULL, 1);

--
-- Déclencheurs `reservations_hotel`
--
DELIMITER $$
CREATE TRIGGER `after_reservation_hotel_insert` AFTER INSERT ON `reservations_hotel` FOR EACH ROW BEGIN
            IF NEW.montant_total > 0 THEN
                INSERT INTO caisse_journal 
                (date_ecriture, montant, sens, source_type, source_id, utilisateur_id, commentaire)
                VALUES (
                    NEW.date_reservation,
                    NEW.montant_total,
                    'ENTREE',
                    'reservation_hotel',
                    NEW.id,
                    COALESCE(NEW.concierge_id, 1),
                    CONCAT('R??servation h??tel #', NEW.id)
                );
            END IF;
        END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_reservation_hotel_update` AFTER UPDATE ON `reservations_hotel` FOR EACH ROW BEGIN
            IF NEW.montant_total != OLD.montant_total THEN
                
                DELETE FROM caisse_journal 
                WHERE source_type = 'reservation_hotel' AND source_id = NEW.id;
                
                
                IF NEW.montant_total > 0 THEN
                    INSERT INTO caisse_journal 
                    (date_ecriture, montant, sens, source_type, source_id, utilisateur_id, commentaire)
                    VALUES (
                        NEW.date_reservation,
                        NEW.montant_total,
                        'ENTREE',
                        'reservation_hotel',
                        NEW.id,
                        COALESCE(NEW.concierge_id, 1),
                        CONCAT('R??servation h??tel #', NEW.id)
                    );
                END IF;
            END IF;
        END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `retours_litiges`
--

CREATE TABLE `retours_litiges` (
  `id` int(10) UNSIGNED NOT NULL,
  `date_retour` date NOT NULL,
  `client_id` int(10) UNSIGNED NOT NULL,
  `produit_id` int(10) UNSIGNED NOT NULL,
  `vente_id` int(10) UNSIGNED DEFAULT NULL,
  `motif` text NOT NULL,
  `type_probleme` enum('DEFAUT_PRODUIT','ERREUR_LIVRAISON','INSATISFACTION_CLIENT','AUTRE') DEFAULT 'AUTRE',
  `responsable_suivi_id` int(10) UNSIGNED NOT NULL,
  `statut_traitement` enum('EN_COURS','RESOLU','ABANDONNE') NOT NULL DEFAULT 'EN_COURS',
  `solution` text DEFAULT NULL,
  `montant_rembourse` decimal(15,2) DEFAULT 0.00,
  `montant_avoir` decimal(15,2) DEFAULT 0.00,
  `date_resolution` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `retours_litiges`
--

INSERT INTO `retours_litiges` (`id`, `date_retour`, `client_id`, `produit_id`, `vente_id`, `motif`, `type_probleme`, `responsable_suivi_id`, `statut_traitement`, `solution`, `montant_rembourse`, `montant_avoir`, `date_resolution`) VALUES
(1, '2025-12-14', 67, 70, 71, 'gérer le retour', 'AUTRE', 1, 'RESOLU', 'étaient vraiment défectueux', 0.00, 0.00, '2025-12-14 18:21:48'),
(2, '2025-12-14', 6, 2, 90, 'mal fait', 'INSATISFACTION_CLIENT', 1, 'EN_COURS', NULL, 0.00, 0.00, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `roles`
--

CREATE TABLE `roles` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` varchar(50) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `roles`
--

INSERT INTO `roles` (`id`, `code`, `nom`, `description`) VALUES
(1, 'ADMIN', 'Administrateur', 'Accés complet é toute léééapplication'),
(2, 'SHOWROOM', 'Commercial Showroom', 'Gestion des visiteurs, devis et ventes en showroom'),
(3, 'TERRAIN', 'Commercial Terrain', 'Prospection terrain, devis et ventes terrain'),
(4, 'MAGASINIER', 'Magasinier', 'Gestion des stocks, livraisons, ruptures'),
(5, 'CAISSIER', 'Caissier', 'Journal de caisse et encaissements'),
(6, 'DIRECTION', 'Direction', 'Consultation des reportings et indicateurs globaux');

-- --------------------------------------------------------

--
-- Structure de la table `role_permission`
--

CREATE TABLE `role_permission` (
  `role_id` int(10) UNSIGNED NOT NULL,
  `permission_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `role_permission`
--

INSERT INTO `role_permission` (`role_id`, `permission_id`) VALUES
(1, 1),
(1, 2),
(1, 3),
(1, 4),
(1, 5),
(1, 6),
(1, 7),
(1, 8),
(1, 9),
(1, 10),
(1, 11),
(1, 12),
(1, 13),
(1, 14),
(1, 15),
(1, 16),
(1, 17),
(1, 18),
(1, 19),
(1, 20),
(1, 21),
(1, 22),
(1, 23),
(2, 1),
(2, 5),
(2, 6),
(2, 7),
(2, 8),
(2, 9),
(2, 10),
(2, 11),
(2, 19),
(3, 1),
(3, 5),
(3, 6),
(3, 7),
(3, 8),
(3, 10),
(3, 11),
(3, 19),
(4, 1),
(4, 3),
(4, 10),
(5, 10),
(5, 13),
(5, 14),
(5, 18),
(6, 1),
(6, 5),
(6, 7),
(6, 10),
(6, 13),
(6, 16),
(6, 17),
(6, 18),
(6, 19),
(6, 23);

-- --------------------------------------------------------

--
-- Structure de la table `ruptures_signalees`
--

CREATE TABLE `ruptures_signalees` (
  `id` int(10) UNSIGNED NOT NULL,
  `date_signalement` date NOT NULL,
  `produit_id` int(10) UNSIGNED NOT NULL,
  `seuil_alerte` decimal(15,3) NOT NULL,
  `stock_actuel` decimal(15,3) NOT NULL,
  `impact_commercial` text DEFAULT NULL COMMENT 'Ventes perdues, clients m??contents, etc.',
  `action_proposee` text DEFAULT NULL COMMENT 'R??appro urgent, promotion, produit alternatif',
  `magasinier_id` int(10) UNSIGNED NOT NULL,
  `statut_traitement` enum('SIGNALE','EN_COURS','RESOLU','ABANDONNE') DEFAULT 'SIGNALE',
  `date_resolution` datetime DEFAULT NULL,
  `commentaire_resolution` text DEFAULT NULL,
  `date_creation` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Alertes ruptures stock (magasin ??? marketing)';

-- --------------------------------------------------------

--
-- Structure de la table `ruptures_stock`
--

CREATE TABLE `ruptures_stock` (
  `id` int(10) UNSIGNED NOT NULL,
  `date_rapport` date NOT NULL,
  `produit_id` int(10) UNSIGNED NOT NULL,
  `seuil_alerte` int(11) NOT NULL,
  `stock_actuel` int(11) NOT NULL,
  `impact_commercial` text NOT NULL,
  `action_proposee` text NOT NULL,
  `magasinier_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `satisfaction_clients`
--

CREATE TABLE `satisfaction_clients` (
  `id` int(10) UNSIGNED NOT NULL,
  `date_satisfaction` date NOT NULL,
  `client_id` int(10) UNSIGNED DEFAULT NULL,
  `nom_client` varchar(150) NOT NULL,
  `service_utilise` enum('SHOWROOM','HOTEL','FORMATION','TERRAIN','DIGITAL') NOT NULL,
  `note` int(11) NOT NULL,
  `commentaire` text DEFAULT NULL,
  `utilisateur_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `satisfaction_clients`
--

INSERT INTO `satisfaction_clients` (`id`, `date_satisfaction`, `client_id`, `nom_client`, `service_utilise`, `note`, `commentaire`, `utilisateur_id`) VALUES
(1, '2025-11-19', NULL, 'apprenant', 'FORMATION', 4, '', 1),
(2, '2025-11-20', 4, 'Client Hétel Test', 'FORMATION', 2, 'grincheux et deéu', 1);

-- --------------------------------------------------------

--
-- Structure de la table `sessions_actives`
--

CREATE TABLE `sessions_actives` (
  `id` varchar(128) NOT NULL COMMENT 'Session ID',
  `utilisateur_id` int(10) UNSIGNED NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `device_fingerprint` varchar(64) DEFAULT NULL COMMENT 'Empreinte du device',
  `pays` varchar(2) DEFAULT NULL COMMENT 'Code pays ISO',
  `ville` varchar(100) DEFAULT NULL,
  `date_creation` datetime DEFAULT current_timestamp(),
  `date_derniere_activite` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `date_expiration` datetime NOT NULL,
  `actif` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Sessions actives avec tracking d??taill??';

--
-- Déchargement des données de la table `sessions_actives`
--

INSERT INTO `sessions_actives` (`id`, `utilisateur_id`, `ip_address`, `user_agent`, `device_fingerprint`, `pays`, `ville`, `date_creation`, `date_derniere_activite`, `date_expiration`, `actif`) VALUES
('2c2rdbbf2jld2h35aialouqgp6', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', NULL, NULL, NULL, '2025-12-13 20:20:15', '2025-12-13 20:20:15', '2025-12-13 22:20:15', 1),
('2emevl7tg15j0ubg8seblkc64f', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', NULL, NULL, NULL, '2025-12-14 13:08:09', '2025-12-14 13:08:09', '2025-12-14 15:08:09', 1),
('34snnh13nrm0ega1dvlu1puofp', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', NULL, NULL, NULL, '2025-12-14 20:34:38', '2025-12-14 20:34:38', '2025-12-14 22:34:38', 1),
('4i7r6spbtrkholn08ncn2bnu96', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', NULL, NULL, NULL, '2025-12-13 15:24:50', '2025-12-13 15:24:50', '2025-12-13 17:24:50', 1),
('i38s74r5arcjro7imlbdouhfq4', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', NULL, NULL, NULL, '2025-12-13 21:07:51', '2025-12-13 21:07:51', '2025-12-13 23:07:51', 1),
('nr7ld1kfh8rh8i9hr40f2db8te', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL, NULL, NULL, '2025-12-13 13:03:12', '2025-12-13 13:03:12', '2025-12-13 15:03:12', 1),
('u78v44an2rnvh1vjml3r74u03m', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', NULL, NULL, NULL, '2025-12-13 13:26:22', '2025-12-13 13:26:22', '2025-12-13 15:26:22', 1);

-- --------------------------------------------------------

--
-- Structure de la table `sms_2fa_codes`
--

CREATE TABLE `sms_2fa_codes` (
  `id` int(10) UNSIGNED NOT NULL,
  `utilisateur_id` int(11) NOT NULL,
  `code_hash` varchar(255) NOT NULL COMMENT 'Hash du code ?? 6 chiffres',
  `telephone` varchar(20) NOT NULL COMMENT 'Num??ro au format international',
  `expire_a` datetime NOT NULL COMMENT 'Date d''expiration (5 min)',
  `utilise` tinyint(1) DEFAULT 0 COMMENT '0 = non utilis??, 1 = utilis??',
  `utilise_a` datetime DEFAULT NULL COMMENT 'Date d''utilisation',
  `cree_a` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Codes SMS temporaires pour authentification 2FA';

-- --------------------------------------------------------

--
-- Structure de la table `sms_tracking`
--

CREATE TABLE `sms_tracking` (
  `id` int(10) UNSIGNED NOT NULL,
  `utilisateur_id` int(11) NOT NULL,
  `telephone` varchar(20) NOT NULL,
  `envoye_a` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Historique d''envoi des SMS pour d??tection d''abus';

-- --------------------------------------------------------

--
-- Structure de la table `sous_categories_produits`
--

CREATE TABLE `sous_categories_produits` (
  `id` int(10) UNSIGNED NOT NULL,
  `famille_id` int(10) UNSIGNED NOT NULL,
  `nom` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `sous_categories_produits`
--

INSERT INTO `sous_categories_produits` (`id`, `famille_id`, `nom`) VALUES
(1, 1, 'Chambres é coucher'),
(2, 1, 'Salons'),
(3, 2, 'Quincaillerie standard'),
(4, 3, 'Machines de découpe'),
(5, 4, 'Panneaux mélaminés');

-- --------------------------------------------------------

--
-- Structure de la table `stocks_mouvements`
--

CREATE TABLE `stocks_mouvements` (
  `id` int(10) UNSIGNED NOT NULL,
  `produit_id` int(10) UNSIGNED NOT NULL,
  `date_mouvement` datetime NOT NULL DEFAULT current_timestamp(),
  `type_mouvement` enum('ENTREE','SORTIE','AJUSTEMENT') NOT NULL,
  `quantite` int(11) NOT NULL,
  `source_type` varchar(50) DEFAULT NULL,
  `source_id` int(11) DEFAULT NULL,
  `commentaire` text DEFAULT NULL,
  `utilisateur_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `stocks_mouvements`
--

INSERT INTO `stocks_mouvements` (`id`, `produit_id`, `date_mouvement`, `type_mouvement`, `quantite`, `source_type`, `source_id`, `commentaire`, `utilisateur_id`) VALUES
(168, 59, '2025-12-13 17:33:50', '', 50, NULL, NULL, 'Stock initial', 1),
(169, 60, '2025-12-13 17:33:50', '', 80, NULL, NULL, 'Stock initial', 1),
(170, 61, '2025-12-13 17:33:50', '', 40, NULL, NULL, 'Stock initial', 1),
(171, 62, '2025-12-13 17:33:50', '', 5, NULL, NULL, 'Stock initial', 1),
(172, 63, '2025-12-13 17:33:50', '', 3, NULL, NULL, 'Stock initial', 1),
(173, 64, '2025-12-13 17:33:50', '', 2, NULL, NULL, 'Stock initial', 1),
(174, 65, '2025-12-13 17:33:50', '', 200, NULL, NULL, 'Stock initial', 1),
(175, 66, '2025-12-13 17:33:50', '', 100, NULL, NULL, 'Stock initial', 1),
(176, 67, '2025-12-13 17:33:50', '', 150, NULL, NULL, 'Stock initial', 1),
(177, 68, '2025-12-13 17:33:50', '', 8, NULL, NULL, 'Stock initial', 1),
(178, 69, '2025-12-13 17:33:50', '', 10, NULL, NULL, 'Stock initial', 1),
(179, 70, '2025-12-13 17:33:50', '', 300, NULL, NULL, 'Stock initial', 1),
(180, 71, '2025-12-13 17:33:50', '', 80, NULL, NULL, 'Stock initial', 1),
(181, 72, '2025-12-13 17:33:50', '', 60, NULL, NULL, 'Stock initial', 1),
(182, 69, '2025-12-13 17:33:50', '', -4, 'bon_livraison', 45, 'Livraison BL-20251025-001', 1),
(183, 64, '2025-12-13 17:33:50', '', -11, 'bon_livraison', 45, 'Livraison BL-20251025-001', 1),
(184, 60, '2025-12-13 17:33:50', '', -13, 'bon_livraison', 45, 'Livraison BL-20251025-001', 1),
(185, 72, '2025-12-13 17:33:50', '', -4, 'bon_livraison', 45, 'Livraison BL-20251025-001', 1),
(186, 62, '2025-12-13 17:33:50', '', -12, 'bon_livraison', 46, 'Livraison BL-20251113-002', 1),
(187, 59, '2025-12-13 17:33:50', '', -3, 'bon_livraison', 46, 'Livraison BL-20251113-002', 1),
(188, 65, '2025-12-13 17:33:50', '', -4, 'bon_livraison', 46, 'Livraison BL-20251113-002', 1),
(189, 59, '2025-12-13 17:33:50', '', -13, 'bon_livraison', 46, 'Livraison BL-20251113-002', 1),
(190, 62, '2025-12-13 17:33:50', '', -2, 'bon_livraison', 46, 'Livraison BL-20251113-002', 1),
(191, 61, '2025-12-13 17:33:50', '', -11, 'bon_livraison', 47, 'Livraison BL-20251122-003', 1),
(192, 69, '2025-12-13 17:33:50', '', -1, 'bon_livraison', 47, 'Livraison BL-20251122-003', 1),
(193, 71, '2025-12-13 17:33:50', '', -3, 'bon_livraison', 47, 'Livraison BL-20251122-003', 1),
(194, 61, '2025-12-13 17:33:50', '', -5, 'bon_livraison', 47, 'Livraison BL-20251122-003', 1),
(195, 72, '2025-12-13 17:33:50', '', -12, 'bon_livraison', 48, 'Livraison BL-20251111-004', 1),
(196, 68, '2025-12-13 17:33:50', '', -5, 'bon_livraison', 48, 'Livraison BL-20251111-004', 1),
(197, 71, '2025-12-13 17:33:50', '', -9, 'bon_livraison', 48, 'Livraison BL-20251111-004', 1),
(198, 69, '2025-12-13 17:33:50', '', -11, 'bon_livraison', 49, 'Livraison BL-20251017-005', 1),
(199, 71, '2025-12-13 17:33:50', '', -12, 'bon_livraison', 49, 'Livraison BL-20251017-005', 1),
(200, 72, '2025-12-13 17:33:50', '', -6, 'bon_livraison', 50, 'Livraison BL-20251215-006', 1),
(201, 59, '2025-12-13 17:33:50', '', -14, 'bon_livraison', 50, 'Livraison BL-20251215-006', 1),
(202, 68, '2025-12-13 17:33:50', '', -12, 'bon_livraison', 50, 'Livraison BL-20251215-006', 1),
(203, 70, '2025-12-13 17:33:50', '', -14, 'bon_livraison', 50, 'Livraison BL-20251215-006', 1),
(204, 70, '2025-12-13 17:33:50', '', -4, 'bon_livraison', 50, 'Livraison BL-20251215-006', 1),
(205, 64, '2025-12-13 17:33:50', '', -1, 'bon_livraison', 51, 'Livraison BL-20251130-007', 1),
(206, 61, '2025-12-13 17:33:50', '', -10, 'bon_livraison', 51, 'Livraison BL-20251130-007', 1),
(207, 69, '2025-12-13 17:33:50', '', -15, 'bon_livraison', 51, 'Livraison BL-20251130-007', 1),
(208, 70, '2025-12-13 17:33:50', '', -9, 'bon_livraison', 52, 'Livraison BL-20251212-008', 1),
(209, 71, '2025-12-13 17:33:50', '', -8, 'bon_livraison', 52, 'Livraison BL-20251212-008', 1),
(210, 67, '2025-12-13 17:33:50', '', -7, 'bon_livraison', 52, 'Livraison BL-20251212-008', 1),
(211, 59, '2025-12-13 17:33:50', '', -2, 'bon_livraison', 53, 'Livraison BL-20251030-009', 1),
(212, 72, '2025-12-13 17:33:50', '', -7, 'bon_livraison', 54, 'Livraison BL-20251202-010', 1),
(213, 70, '2025-12-13 17:33:50', '', -2, 'bon_livraison', 54, 'Livraison BL-20251202-010', 1),
(214, 64, '2025-12-13 17:33:50', '', -10, 'bon_livraison', 55, 'Livraison BL-20251016-011', 1),
(215, 67, '2025-12-13 17:33:50', '', -3, 'bon_livraison', 55, 'Livraison BL-20251016-011', 1),
(216, 67, '2025-12-13 17:33:50', '', -8, 'bon_livraison', 55, 'Livraison BL-20251016-011', 1),
(217, 60, '2025-12-13 17:33:50', '', -7, 'bon_livraison', 55, 'Livraison BL-20251016-011', 1),
(218, 69, '2025-12-13 17:33:50', '', -10, 'bon_livraison', 56, 'Livraison BL-20251212-012', 1),
(219, 63, '2025-12-13 17:33:50', '', -4, 'bon_livraison', 57, 'Livraison BL-20251210-013', 1),
(220, 64, '2025-12-13 17:33:50', '', -9, 'bon_livraison', 58, 'Livraison BL-20251115-014', 1),
(221, 65, '2025-12-13 17:33:50', '', -10, 'bon_livraison', 58, 'Livraison BL-20251115-014', 1),
(222, 72, '2025-12-13 17:33:50', '', -5, 'bon_livraison', 58, 'Livraison BL-20251115-014', 1),
(223, 68, '2025-12-13 17:33:50', '', -3, 'bon_livraison', 58, 'Livraison BL-20251115-014', 1),
(224, 68, '2025-12-13 17:33:50', '', -7, 'bon_livraison', 59, 'Livraison BL-20251215-015', 1),
(225, 71, '2025-12-13 17:33:50', '', -9, 'bon_livraison', 59, 'Livraison BL-20251215-015', 1),
(226, 70, '2025-12-13 17:33:50', '', -1, 'bon_livraison', 59, 'Livraison BL-20251215-015', 1),
(227, 63, '2025-12-13 17:33:50', '', -9, 'bon_livraison', 59, 'Livraison BL-20251215-015', 1),
(228, 70, '2025-12-13 17:33:50', '', -5, 'bon_livraison', 60, 'Livraison BL-20251209-016', 1),
(229, 69, '2025-12-13 17:33:50', '', -9, 'bon_livraison', 60, 'Livraison BL-20251209-016', 1),
(230, 61, '2025-12-13 17:33:50', '', -5, 'bon_livraison', 60, 'Livraison BL-20251209-016', 1),
(231, 64, '2025-12-13 17:33:50', '', -3, 'bon_livraison', 61, 'Livraison BL-20251108-017', 1),
(232, 61, '2025-12-13 17:33:50', '', -4, 'bon_livraison', 61, 'Livraison BL-20251108-017', 1),
(233, 65, '2025-12-13 17:33:50', '', -7, 'bon_livraison', 61, 'Livraison BL-20251108-017', 1),
(234, 64, '2025-12-13 21:27:33', 'SORTIE', 4, 'VENTE', 64, 'Sortie via BL BL-20251213-212733', 1),
(235, 71, '2025-12-13 21:27:33', 'SORTIE', 4, 'VENTE', 64, 'Sortie via BL BL-20251213-212733', 1),
(236, 64, '2025-12-13 21:27:33', 'SORTIE', 1, 'VENTE', 64, 'Sortie via BL BL-20251213-212733', 1),
(237, 72, '2025-12-14 00:00:00', 'SORTIE', 6, 'VENTE', 71, 'Correction : Sortie vente VTE-20251214-015', 1),
(238, 59, '2025-12-14 00:00:00', 'SORTIE', 14, 'VENTE', 71, 'Correction : Sortie vente VTE-20251214-015', 1),
(239, 68, '2025-12-14 00:00:00', 'SORTIE', 12, 'VENTE', 71, 'Correction : Sortie vente VTE-20251214-015', 1),
(240, 70, '2025-12-14 00:00:00', 'SORTIE', 14, 'VENTE', 71, 'Correction : Sortie vente VTE-20251214-015', 1),
(241, 70, '2025-12-13 22:21:44', 'SORTIE', 4, 'VENTE', 71, 'Ajustement : Correction écart livraison-stock (Vis noire 4x30mm (boite 100), écart: 4)', 1),
(242, 68, '2025-12-13 00:00:00', 'SORTIE', 7, 'VENTE', 84, 'Correction : Sortie vente VTE-20251213-028', 1),
(243, 71, '2025-12-13 00:00:00', 'SORTIE', 9, 'VENTE', 84, 'Correction : Sortie vente VTE-20251213-028', 1),
(244, 70, '2025-12-13 00:00:00', 'SORTIE', 1, 'VENTE', 84, 'Correction : Sortie vente VTE-20251213-028', 1),
(245, 63, '2025-12-13 00:00:00', 'SORTIE', 9, 'VENTE', 84, 'Correction : Sortie vente VTE-20251213-028', 1),
(246, 64, '2025-12-12 00:00:00', 'SORTIE', 3, 'VENTE', 77, 'Correction : Sortie vente VTE-20251212-021', 1),
(247, 65, '2025-12-12 00:00:00', 'SORTIE', 3, 'VENTE', 77, 'Correction : Sortie vente VTE-20251212-021', 1),
(248, 68, '2025-12-12 00:00:00', 'SORTIE', 6, 'VENTE', 77, 'Correction : Sortie vente VTE-20251212-021', 1),
(249, 70, '2025-12-14 00:00:00', 'ENTREE', 5, 'LITIGE', 1, 'Retour produit défectueux - Litige #1 (remplacement)', 1),
(250, 70, '2025-12-14 00:00:00', 'SORTIE', 5, 'LITIGE', 1, 'Livraison remplacement - Litige #1', 1),
(251, 70, '2025-12-12 00:00:00', 'SORTIE', 9, 'VENTE', 73, 'Correction : Sortie vente VTE-20251211-017', 1),
(252, 71, '2025-12-12 00:00:00', 'SORTIE', 8, 'VENTE', 73, 'Correction : Sortie vente VTE-20251211-017', 1),
(253, 67, '2025-12-12 00:00:00', 'SORTIE', 7, 'VENTE', 73, 'Correction : Sortie vente VTE-20251211-017', 1),
(254, 60, '2025-10-23 00:00:00', 'SORTIE', 13, 'VENTE', 58, 'Sortie suite à la vente VTE-20251023-002', 1),
(255, 64, '2025-10-23 00:00:00', 'SORTIE', 11, 'VENTE', 58, 'Sortie suite à la vente VTE-20251023-002', 1),
(256, 69, '2025-10-23 00:00:00', 'SORTIE', 4, 'VENTE', 58, 'Sortie suite à la vente VTE-20251023-002', 1),
(257, 72, '2025-10-23 00:00:00', 'SORTIE', 4, 'VENTE', 58, 'Sortie suite à la vente VTE-20251023-002', 1),
(266, 1, '2025-12-14 00:00:00', 'SORTIE', 1, 'VENTE', 91, 'Sortie suite à la vente V-TEST-20251214-221409', 1),
(275, 2, '2025-12-14 00:00:00', '', -2, 'BL', 82, 'Livraison BL-20251214-0001 (Vente V-20251214-143828)', 1);

-- --------------------------------------------------------

--
-- Structure de la table `tentatives_connexion`
--

CREATE TABLE `tentatives_connexion` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `login_attempt` varchar(100) NOT NULL COMMENT 'Login tent??',
  `utilisateur_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'NULL si login invalide',
  `ip_address` varchar(45) NOT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `methode_2fa` varchar(20) DEFAULT NULL COMMENT 'TOTP, RECOVERY, NONE',
  `succes` tinyint(1) NOT NULL,
  `raison_echec` varchar(200) DEFAULT NULL COMMENT 'Mot de passe incorrect, 2FA invalide, compte bloqu??',
  `pays` varchar(2) DEFAULT NULL,
  `ville` varchar(100) DEFAULT NULL,
  `date_tentative` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Historique d??taill?? des tentatives de connexion';

--
-- Déchargement des données de la table `tentatives_connexion`
--

INSERT INTO `tentatives_connexion` (`id`, `login_attempt`, `utilisateur_id`, `ip_address`, `user_agent`, `methode_2fa`, `succes`, `raison_echec`, `pays`, `ville`, `date_tentative`) VALUES
(1, 'admin', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL, 0, 'Mot de passe incorrect', NULL, NULL, '2025-12-13 13:03:02'),
(2, 'admin', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', NULL, 0, 'Mot de passe incorrect', NULL, NULL, '2025-12-13 13:18:28'),
(3, 'admin', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 'EMAIL', 1, NULL, NULL, NULL, '2025-12-13 13:26:22'),
(4, 'admin', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 'EMAIL', 1, NULL, NULL, NULL, '2025-12-13 15:24:50'),
(5, 'admin', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 'EMAIL', 0, '2FA email incorrect', NULL, NULL, '2025-12-13 20:20:09'),
(6, 'admin', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 'EMAIL', 1, NULL, NULL, NULL, '2025-12-13 20:20:15'),
(7, 'admin', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 'EMAIL', 1, NULL, NULL, NULL, '2025-12-13 21:07:50'),
(8, 'admin', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', NULL, 0, 'Mot de passe incorrect', NULL, NULL, '2025-12-14 13:07:54'),
(9, 'admin', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 'EMAIL', 1, NULL, NULL, NULL, '2025-12-14 13:08:08'),
(10, 'admin', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 'EMAIL', 1, NULL, NULL, NULL, '2025-12-14 20:34:36');

-- --------------------------------------------------------

--
-- Structure de la table `types_client`
--

CREATE TABLE `types_client` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` varchar(50) NOT NULL,
  `libelle` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `types_client`
--

INSERT INTO `types_client` (`id`, `code`, `libelle`) VALUES
(1, 'SHOWROOM', 'Client / prospect showroom'),
(2, 'TERRAIN', 'Client / prospect terrain'),
(3, 'DIGITAL', 'Client issu du digital (réseaux sociaux, site, CRM)'),
(4, 'HOTEL', 'Client hébergement / hôtel'),
(5, 'FORMATION', 'Apprenant / client formation');

-- --------------------------------------------------------

--
-- Structure de la table `upsell_hotel`
--

CREATE TABLE `upsell_hotel` (
  `id` int(10) UNSIGNED NOT NULL,
  `reservation_id` int(10) UNSIGNED NOT NULL,
  `service_additionnel` varchar(150) NOT NULL,
  `montant` decimal(15,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `user_preferences`
--

CREATE TABLE `user_preferences` (
  `id` int(11) NOT NULL,
  `utilisateur_id` int(11) NOT NULL,
  `page_name` varchar(100) NOT NULL COMMENT 'ex: ventes, livraisons, litiges',
  `sort_by` varchar(50) DEFAULT 'date' COMMENT 'colonne de tri',
  `sort_dir` varchar(4) DEFAULT 'desc' COMMENT 'asc ou desc',
  `per_page` int(11) DEFAULT 25 COMMENT 'résultats par page (10, 25, 50, 100)',
  `remember_filters` tinyint(1) DEFAULT 1 COMMENT 'conserver les filtres',
  `default_date_range` varchar(20) DEFAULT NULL COMMENT 'last_7d, last_30d, last_90d, this_month',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Préférences utilisateur par page (tri, pagination, filtres)';

--
-- Déchargement des données de la table `user_preferences`
--

INSERT INTO `user_preferences` (`id`, `utilisateur_id`, `page_name`, `sort_by`, `sort_dir`, `per_page`, `remember_filters`, `default_date_range`, `created_at`, `updated_at`) VALUES
(3, 1, 'ventes', 'date', 'desc', 10, 1, NULL, '2025-12-14 23:53:07', '2025-12-14 23:58:22'),
(6, 1, 'litiges', 'date', 'desc', 50, 1, NULL, '2025-12-15 00:19:06', '2025-12-15 00:19:06');

-- --------------------------------------------------------

--
-- Structure de la table `utilisateurs`
--

CREATE TABLE `utilisateurs` (
  `id` int(10) UNSIGNED NOT NULL,
  `login` varchar(100) NOT NULL,
  `mot_de_passe_hash` varchar(255) NOT NULL,
  `nom_complet` varchar(150) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `telephone` varchar(50) DEFAULT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `date_creation` datetime NOT NULL DEFAULT current_timestamp(),
  `date_derniere_connexion` datetime DEFAULT NULL,
  `date_changement_mdp` datetime DEFAULT NULL COMMENT 'Date dernier changement mot de passe',
  `mdp_expire` tinyint(1) DEFAULT 0 COMMENT 'Mot de passe expir??',
  `force_changement_mdp` tinyint(1) DEFAULT 0 COMMENT 'Forcer changement au prochain login',
  `compte_verrouille` tinyint(1) DEFAULT 0 COMMENT 'Compte verrouill?? (manuel)',
  `raison_verrouillage` text DEFAULT NULL,
  `date_verrouillage` datetime DEFAULT NULL,
  `sessions_simultanees_actuelles` int(11) DEFAULT 0 COMMENT 'Compteur sessions actives'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `utilisateurs`
--

INSERT INTO `utilisateurs` (`id`, `login`, `mot_de_passe_hash`, `nom_complet`, `email`, `telephone`, `actif`, `date_creation`, `date_derniere_connexion`, `date_changement_mdp`, `mdp_expire`, `force_changement_mdp`, `compte_verrouille`, `raison_verrouillage`, `date_verrouillage`, `sessions_simultanees_actuelles`) VALUES
(1, 'admin', '$2b$10$j6YYUX.QLOxOoBn9eB4rJu8/ye4/NOEXPvRjcYhUY4mBiaZZFUrTi', 'Administrateur KMS', 'admin@kms.local', NULL, 1, '2025-11-18 10:59:28', '2025-12-14 20:34:37', NULL, 0, 0, 0, NULL, NULL, 0),
(2, 'admin2', '$2y$10$G6sGiMHX75v9PYTAqIZCPObMQV.3InGlXpNGyrKWKK/gM8iln0Tfu', 'Administrateur Systéme', 'admin2@kms.local', NULL, 1, '2025-12-11 11:56:20', NULL, NULL, 0, 0, 0, NULL, NULL, 0),
(3, 'showroom1', '$2y$10$G6sGiMHX75v9PYTAqIZCPObMQV.3InGlXpNGyrKWKK/gM8iln0Tfu', 'Marie Kouadio', 'marie.kouadio@kms.local', NULL, 1, '2025-12-11 11:56:20', NULL, NULL, 0, 0, 0, NULL, NULL, 0),
(4, 'showroom2', '$2y$10$G6sGiMHX75v9PYTAqIZCPObMQV.3InGlXpNGyrKWKK/gM8iln0Tfu', 'Yao Kouassi', 'yao.kouassi@kms.local', NULL, 1, '2025-12-11 11:56:20', NULL, NULL, 0, 0, 0, NULL, NULL, 0),
(5, 'terrain1', '$2y$10$G6sGiMHX75v9PYTAqIZCPObMQV.3InGlXpNGyrKWKK/gM8iln0Tfu', 'Konan Yao', 'konan.yao@kms.local', NULL, 1, '2025-12-11 11:56:20', NULL, NULL, 0, 0, 0, NULL, NULL, 0),
(6, 'terrain2', '$2y$10$G6sGiMHX75v9PYTAqIZCPObMQV.3InGlXpNGyrKWKK/gM8iln0Tfu', 'Aya N\'Guessan', 'aya.nguessan@kms.local', NULL, 1, '2025-12-11 11:56:20', NULL, NULL, 0, 0, 0, NULL, NULL, 0),
(7, 'magasin1', '$2y$10$G6sGiMHX75v9PYTAqIZCPObMQV.3InGlXpNGyrKWKK/gM8iln0Tfu', 'Ibrahim Traoré', 'ibrahim.traore@kms.local', NULL, 1, '2025-12-11 11:56:20', NULL, NULL, 0, 0, 0, NULL, NULL, 0),
(8, 'magasin2', '$2y$10$G6sGiMHX75v9PYTAqIZCPObMQV.3InGlXpNGyrKWKK/gM8iln0Tfu', 'Moussa Diallo', 'moussa.diallo@kms.local', NULL, 1, '2025-12-11 11:56:20', NULL, NULL, 0, 0, 0, NULL, NULL, 0),
(9, 'caisse1', '$2y$10$G6sGiMHX75v9PYTAqIZCPObMQV.3InGlXpNGyrKWKK/gM8iln0Tfu', 'Aminata Koné', 'aminata.kone@kms.local', NULL, 1, '2025-12-11 11:56:20', NULL, NULL, 0, 0, 0, NULL, NULL, 0),
(10, 'caisse2', '$2y$10$G6sGiMHX75v9PYTAqIZCPObMQV.3InGlXpNGyrKWKK/gM8iln0Tfu', 'Fatou Camara', 'fatou.camara@kms.local', NULL, 1, '2025-12-11 11:56:20', NULL, NULL, 0, 0, 0, NULL, NULL, 0),
(11, 'direction1', '$2y$10$G6sGiMHX75v9PYTAqIZCPObMQV.3InGlXpNGyrKWKK/gM8iln0Tfu', 'Directeur Général', 'dg@kms.local', NULL, 1, '2025-12-11 11:56:20', NULL, NULL, 0, 0, 0, NULL, NULL, 0),
(12, 'direction2', '$2y$10$G6sGiMHX75v9PYTAqIZCPObMQV.3InGlXpNGyrKWKK/gM8iln0Tfu', 'Directeur Adjoint', 'da@kms.local', NULL, 1, '2025-12-11 11:56:20', NULL, NULL, 0, 0, 0, NULL, NULL, 0),
(13, 'Tatiana', '$2y$10$PI9HMfk.ET49yrr31htsKOHMhnZSNaITlwbcbcL5lJawUzejgOm7a', 'Naoussi Tatiana', 'naoussitatiana@gmail.com', '695657613', 1, '2025-12-11 12:07:02', NULL, NULL, 0, 0, 0, NULL, NULL, 0),
(14, 'Gislaine', '$2y$10$WwVYPLCm6FFKjE/CY4QLh.sN1gc3y2J3KsgHoLGh9u33r/b72mHKW', 'Gislaine', NULL, NULL, 1, '2025-12-11 12:09:27', NULL, NULL, 0, 0, 0, NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Structure de la table `utilisateurs_2fa`
--

CREATE TABLE `utilisateurs_2fa` (
  `id` int(10) UNSIGNED NOT NULL,
  `utilisateur_id` int(10) UNSIGNED NOT NULL,
  `secret` varchar(255) NOT NULL COMMENT 'Secret TOTP encod??',
  `actif` tinyint(1) DEFAULT 0,
  `date_activation` datetime DEFAULT NULL,
  `date_desactivation` datetime DEFAULT NULL,
  `methode` enum('TOTP','SMS','EMAIL') DEFAULT 'TOTP',
  `telephone_backup` varchar(50) DEFAULT NULL,
  `email_backup` varchar(150) DEFAULT NULL,
  `date_creation` datetime DEFAULT current_timestamp(),
  `methode_2fa` enum('totp','sms') DEFAULT 'totp' COMMENT 'M??thode 2FA: TOTP ou SMS',
  `telephone` varchar(20) DEFAULT NULL COMMENT 'Num??ro de t??l??phone au format international'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Configuration 2FA par utilisateur';

--
-- Déchargement des données de la table `utilisateurs_2fa`
--

INSERT INTO `utilisateurs_2fa` (`id`, `utilisateur_id`, `secret`, `actif`, `date_activation`, `date_desactivation`, `methode`, `telephone_backup`, `email_backup`, `date_creation`, `methode_2fa`, `telephone`) VALUES
(1, 1, '', 1, '2025-12-13 13:17:56', NULL, 'EMAIL', NULL, 'peghiembouoromial@gmail.com', '2025-12-13 13:17:56', 'totp', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `utilisateurs_2fa_recovery`
--

CREATE TABLE `utilisateurs_2fa_recovery` (
  `id` int(10) UNSIGNED NOT NULL,
  `utilisateur_id` int(10) UNSIGNED NOT NULL,
  `code_hash` varchar(255) NOT NULL COMMENT 'Hash du code de r??cup??ration',
  `utilise` tinyint(1) DEFAULT 0,
  `date_creation` datetime DEFAULT current_timestamp(),
  `date_utilisation` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Codes de r??cup??ration 2FA (backup)';

-- --------------------------------------------------------

--
-- Structure de la table `utilisateur_role`
--

CREATE TABLE `utilisateur_role` (
  `utilisateur_id` int(10) UNSIGNED NOT NULL,
  `role_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `utilisateur_role`
--

INSERT INTO `utilisateur_role` (`utilisateur_id`, `role_id`) VALUES
(1, 1),
(2, 1),
(3, 2),
(4, 2),
(5, 3),
(6, 3),
(7, 4),
(8, 4),
(9, 5),
(10, 5),
(11, 6),
(12, 6),
(13, 2),
(13, 3),
(13, 4);

-- --------------------------------------------------------

--
-- Structure de la table `ventes`
--

CREATE TABLE `ventes` (
  `id` int(10) UNSIGNED NOT NULL,
  `numero` varchar(50) NOT NULL,
  `date_vente` date NOT NULL,
  `client_id` int(10) UNSIGNED NOT NULL,
  `canal_vente_id` int(10) UNSIGNED NOT NULL,
  `devis_id` int(10) UNSIGNED DEFAULT NULL,
  `statut` enum('DEVIS','DEVIS_ACCEPTE','EN_ATTENTE_LIVRAISON','EN_PREPARATION','PRET_LIVRAISON','PARTIELLEMENT_LIVREE','LIVREE','FACTUREE','PAYEE','ANNULEE') NOT NULL DEFAULT 'EN_ATTENTE_LIVRAISON',
  `statut_encaissement` varchar(30) DEFAULT 'ATTENTE_PAIEMENT',
  `journal_caisse_id` int(10) UNSIGNED DEFAULT NULL,
  `montant_total_ht` decimal(15,2) NOT NULL DEFAULT 0.00,
  `montant_total_ttc` decimal(15,2) NOT NULL DEFAULT 0.00,
  `utilisateur_id` int(10) UNSIGNED NOT NULL,
  `commentaires` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `ventes`
--

INSERT INTO `ventes` (`id`, `numero`, `date_vente`, `client_id`, `canal_vente_id`, `devis_id`, `statut`, `statut_encaissement`, `journal_caisse_id`, `montant_total_ht`, `montant_total_ttc`, `utilisateur_id`, `commentaires`) VALUES
(57, 'VTE-20251109-001', '2025-11-09', 94, 1, 52, 'EN_ATTENTE_LIVRAISON', 'ATTENTE_PAIEMENT', NULL, 3043100.00, 3043100.00, 1, NULL),
(58, 'VTE-20251023-002', '2025-10-23', 93, 1, 54, 'LIVREE', 'ATTENTE_PAIEMENT', NULL, 5276600.00, 5276600.00, 1, NULL),
(59, 'VTE-20251117-003', '2025-11-17', 72, 1, 55, 'EN_ATTENTE_LIVRAISON', 'ATTENTE_PAIEMENT', NULL, 766500.00, 766500.00, 1, NULL),
(60, 'VTE-20251202-004', '2025-12-02', 86, 1, 56, 'EN_ATTENTE_LIVRAISON', 'ATTENTE_PAIEMENT', NULL, 1447000.00, 1447000.00, 1, NULL),
(61, 'VTE-20251125-005', '2025-11-25', 89, 1, 58, 'EN_ATTENTE_LIVRAISON', 'ATTENTE_PAIEMENT', NULL, 2945000.00, 2945000.00, 1, NULL),
(62, 'VTE-20251123-006', '2025-11-23', 71, 1, 59, 'EN_ATTENTE_LIVRAISON', 'ATTENTE_PAIEMENT', NULL, 8130000.00, 8130000.00, 1, NULL),
(63, 'VTE-20251111-007', '2025-11-11', 71, 1, 62, 'LIVREE', 'ATTENTE_PAIEMENT', NULL, 3065800.00, 3065800.00, 1, NULL),
(64, 'VTE-20251130-008', '2025-11-30', 89, 1, 64, 'LIVREE', 'ATTENTE_PAIEMENT', NULL, 2159000.00, 2159000.00, 1, NULL),
(65, 'VTE-20251121-009', '2025-11-21', 96, 1, 65, 'EN_ATTENTE_LIVRAISON', 'ATTENTE_PAIEMENT', NULL, 102950.00, 102950.00, 1, NULL),
(66, 'VTE-20251117-010', '2025-11-17', 95, 1, 67, 'LIVREE', 'ATTENTE_PAIEMENT', NULL, 512500.00, 512500.00, 1, NULL),
(67, 'VTE-20251109-011', '2025-11-09', 91, 1, 69, 'LIVREE', 'ATTENTE_PAIEMENT', NULL, 1151500.00, 1151500.00, 1, NULL),
(68, 'VTE-20251027-012', '2025-10-27', 95, 1, 70, 'EN_ATTENTE_LIVRAISON', 'ATTENTE_PAIEMENT', NULL, 5891900.00, 5891900.00, 1, NULL),
(69, 'VTE-20251204-013', '2025-12-04', 81, 1, 71, 'EN_ATTENTE_LIVRAISON', 'ATTENTE_PAIEMENT', NULL, 51800.00, 51800.00, 1, NULL),
(70, 'VTE-20251015-014', '2025-10-15', 82, 1, 73, 'LIVREE', 'ATTENTE_PAIEMENT', NULL, 1147000.00, 1147000.00, 1, NULL),
(71, 'VTE-20251214-015', '2025-12-14', 67, 1, 74, 'LIVREE', 'ATTENTE_PAIEMENT', NULL, 2744000.00, 2744000.00, 1, NULL),
(72, 'VTE-20251127-016', '2025-11-27', 78, 1, 75, 'LIVREE', 'ATTENTE_PAIEMENT', NULL, 2095000.00, 2095000.00, 1, NULL),
(73, 'VTE-20251211-017', '2025-12-11', 94, 1, NULL, 'LIVREE', 'ATTENTE_PAIEMENT', NULL, 94400.00, 94400.00, 1, NULL),
(74, 'VTE-20251021-018', '2025-10-21', 87, 1, NULL, 'EN_ATTENTE_LIVRAISON', 'ATTENTE_PAIEMENT', NULL, 5730000.00, 5730000.00, 1, NULL),
(75, 'VTE-20251027-019', '2025-10-27', 83, 1, NULL, 'LIVREE', 'ATTENTE_PAIEMENT', NULL, 59000.00, 59000.00, 1, NULL),
(76, 'VTE-20251129-020', '2025-11-29', 93, 1, NULL, 'LIVREE', 'ATTENTE_PAIEMENT', NULL, 91500.00, 91500.00, 1, NULL),
(77, 'VTE-20251212-021', '2025-12-12', 79, 1, NULL, 'LIVREE', 'ATTENTE_PAIEMENT', NULL, 2387850.00, 2387850.00, 1, NULL),
(78, 'VTE-20251015-022', '2025-10-15', 68, 1, NULL, 'EN_ATTENTE_LIVRAISON', 'ATTENTE_PAIEMENT', NULL, 43400.00, 43400.00, 1, NULL),
(79, 'VTE-20251015-023', '2025-10-15', 92, 1, NULL, 'LIVREE', 'ATTENTE_PAIEMENT', NULL, 4355600.00, 4355600.00, 1, NULL),
(80, 'VTE-20251024-024', '2025-10-24', 75, 1, NULL, 'EN_ATTENTE_LIVRAISON', 'ATTENTE_PAIEMENT', NULL, 61000.00, 61000.00, 1, NULL),
(81, 'VTE-20251208-025', '2025-12-08', 94, 1, NULL, 'LIVREE', 'ATTENTE_PAIEMENT', NULL, 950000.00, 950000.00, 1, NULL),
(82, 'VTE-20251208-026', '2025-12-08', 67, 1, NULL, 'LIVREE', 'ATTENTE_PAIEMENT', NULL, 1280000.00, 1280000.00, 1, NULL),
(83, 'VTE-20251113-027', '2025-11-13', 86, 1, NULL, 'LIVREE', 'ATTENTE_PAIEMENT', NULL, 4452000.00, 4452000.00, 1, NULL),
(84, 'VTE-20251213-028', '2025-12-13', 69, 1, NULL, 'LIVREE', 'ATTENTE_PAIEMENT', NULL, 4253500.00, 4253500.00, 1, NULL),
(85, 'VTE-20251120-029', '2025-11-20', 74, 1, NULL, 'EN_ATTENTE_LIVRAISON', 'ATTENTE_PAIEMENT', NULL, 77300.00, 77300.00, 1, NULL),
(86, 'VTE-20251205-030', '2025-12-05', 77, 1, NULL, 'LIVREE', 'ATTENTE_PAIEMENT', NULL, 987500.00, 987500.00, 1, NULL),
(87, 'VTE-20251107-031', '2025-11-07', 83, 1, NULL, 'LIVREE', 'ENCAISSE', 56, 1379650.00, 1379650.00, 1, NULL),
(88, 'V-20251213-210414', '2025-12-13', 100, 1, NULL, 'EN_ATTENTE_LIVRAISON', 'ATTENTE_PAIEMENT', NULL, 0.00, 0.00, 1, 'Généré depuis visite showroom du 13/12/2025\nProduit d\'intérêt : N/A'),
(89, 'V-20251213-210432', '2025-12-13', 105, 1, NULL, 'EN_ATTENTE_LIVRAISON', 'ATTENTE_PAIEMENT', NULL, 0.00, 0.00, 1, 'Généré depuis visite showroom du 13/12/2025\nProduit d\'intérêt : N/A'),
(90, 'V-20251214-143828', '2025-12-14', 6, 1, NULL, 'LIVREE', 'ENCAISSE', 55, 558000.00, 665415.00, 1, NULL),
(91, 'V-TEST-20251214-221409', '2025-12-14', 5, 1, NULL, 'LIVREE', 'ATTENTE_PAIEMENT', NULL, 180000.00, 214650.00, 1, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `ventes_lignes`
--

CREATE TABLE `ventes_lignes` (
  `id` int(10) UNSIGNED NOT NULL,
  `vente_id` int(10) UNSIGNED NOT NULL,
  `produit_id` int(10) UNSIGNED NOT NULL,
  `quantite` int(11) NOT NULL,
  `prix_unitaire` decimal(15,2) NOT NULL,
  `remise` decimal(15,2) NOT NULL DEFAULT 0.00,
  `montant_ligne_ht` decimal(15,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `ventes_lignes`
--

INSERT INTO `ventes_lignes` (`id`, `vente_id`, `produit_id`, `quantite`, `prix_unitaire`, `remise`, `montant_ligne_ht`) VALUES
(169, 57, 59, 3, 29500.00, 0.00, 88500.00),
(170, 57, 62, 13, 185000.00, 0.00, 2405000.00),
(171, 57, 60, 8, 13200.00, 0.00, 105600.00),
(172, 57, 71, 9, 8500.00, 0.00, 76500.00),
(173, 57, 61, 15, 24500.00, 0.00, 367500.00),
(174, 58, 69, 4, 95000.00, 0.00, 380000.00),
(175, 58, 64, 11, 425000.00, 0.00, 4675000.00),
(176, 58, 60, 13, 13200.00, 0.00, 171600.00),
(177, 58, 72, 4, 12500.00, 0.00, 50000.00),
(178, 59, 59, 13, 29500.00, 0.00, 383500.00),
(179, 59, 67, 7, 1200.00, 0.00, 8400.00),
(180, 59, 60, 3, 13200.00, 0.00, 39600.00),
(181, 59, 72, 12, 12500.00, 0.00, 150000.00),
(182, 59, 62, 1, 185000.00, 0.00, 185000.00),
(183, 60, 59, 13, 29500.00, 0.00, 383500.00),
(184, 60, 71, 12, 8500.00, 0.00, 102000.00),
(185, 60, 72, 13, 12500.00, 0.00, 162500.00),
(186, 60, 59, 2, 29500.00, 0.00, 59000.00),
(187, 60, 62, 4, 185000.00, 0.00, 740000.00),
(188, 61, 68, 9, 185000.00, 0.00, 1665000.00),
(189, 61, 63, 4, 320000.00, 0.00, 1280000.00),
(190, 62, 68, 4, 185000.00, 0.00, 740000.00),
(191, 62, 69, 14, 95000.00, 0.00, 1330000.00),
(192, 62, 64, 4, 425000.00, 0.00, 1700000.00),
(193, 62, 63, 3, 320000.00, 0.00, 960000.00),
(194, 62, 64, 8, 425000.00, 0.00, 3400000.00),
(195, 63, 62, 12, 185000.00, 0.00, 2220000.00),
(196, 63, 59, 3, 29500.00, 0.00, 88500.00),
(197, 63, 65, 4, 950.00, 0.00, 3800.00),
(198, 63, 59, 13, 29500.00, 0.00, 383500.00),
(199, 63, 62, 2, 185000.00, 0.00, 370000.00),
(200, 64, 64, 4, 425000.00, 0.00, 1700000.00),
(201, 64, 71, 4, 8500.00, 0.00, 34000.00),
(202, 64, 64, 1, 425000.00, 0.00, 425000.00),
(203, 65, 71, 8, 8500.00, 0.00, 68000.00),
(204, 65, 65, 1, 950.00, 0.00, 950.00),
(205, 65, 70, 14, 2000.00, 0.00, 28000.00),
(206, 65, 67, 5, 1200.00, 0.00, 6000.00),
(207, 66, 61, 11, 24500.00, 0.00, 269500.00),
(208, 66, 69, 1, 95000.00, 0.00, 95000.00),
(209, 66, 71, 3, 8500.00, 0.00, 25500.00),
(210, 66, 61, 5, 24500.00, 0.00, 122500.00),
(211, 67, 72, 12, 12500.00, 0.00, 150000.00),
(212, 67, 68, 5, 185000.00, 0.00, 925000.00),
(213, 67, 71, 9, 8500.00, 0.00, 76500.00),
(214, 68, 71, 15, 8500.00, 0.00, 127500.00),
(215, 68, 67, 12, 1200.00, 0.00, 14400.00),
(216, 68, 64, 6, 425000.00, 0.00, 2550000.00),
(217, 68, 63, 10, 320000.00, 0.00, 3200000.00),
(218, 69, 66, 9, 4200.00, 0.00, 37800.00),
(219, 69, 70, 7, 2000.00, 0.00, 14000.00),
(220, 70, 69, 11, 95000.00, 0.00, 1045000.00),
(221, 70, 71, 12, 8500.00, 0.00, 102000.00),
(222, 71, 72, 6, 12500.00, 0.00, 75000.00),
(223, 71, 59, 14, 29500.00, 0.00, 413000.00),
(224, 71, 68, 12, 185000.00, 0.00, 2220000.00),
(225, 71, 70, 14, 2000.00, 0.00, 28000.00),
(226, 71, 70, 4, 2000.00, 0.00, 8000.00),
(227, 72, 64, 1, 425000.00, 0.00, 425000.00),
(228, 72, 61, 10, 24500.00, 0.00, 245000.00),
(229, 72, 69, 15, 95000.00, 0.00, 1425000.00),
(230, 73, 70, 9, 2000.00, 0.00, 18000.00),
(231, 73, 71, 8, 8500.00, 0.00, 68000.00),
(232, 73, 67, 7, 1200.00, 0.00, 8400.00),
(233, 74, 62, 8, 185000.00, 0.00, 1480000.00),
(234, 74, 64, 10, 425000.00, 0.00, 4250000.00),
(235, 75, 59, 2, 29500.00, 0.00, 59000.00),
(236, 76, 72, 7, 12500.00, 0.00, 87500.00),
(237, 76, 70, 2, 2000.00, 0.00, 4000.00),
(238, 77, 64, 3, 425000.00, 0.00, 1275000.00),
(239, 77, 65, 3, 950.00, 0.00, 2850.00),
(240, 77, 68, 6, 185000.00, 0.00, 1110000.00),
(241, 78, 71, 2, 8500.00, 0.00, 17000.00),
(242, 78, 60, 2, 13200.00, 0.00, 26400.00),
(243, 79, 64, 10, 425000.00, 0.00, 4250000.00),
(244, 79, 67, 3, 1200.00, 0.00, 3600.00),
(245, 79, 67, 8, 1200.00, 0.00, 9600.00),
(246, 79, 60, 7, 13200.00, 0.00, 92400.00),
(247, 80, 66, 8, 4200.00, 0.00, 33600.00),
(248, 80, 72, 2, 12500.00, 0.00, 25000.00),
(249, 80, 67, 2, 1200.00, 0.00, 2400.00),
(250, 81, 69, 10, 95000.00, 0.00, 950000.00),
(251, 82, 63, 4, 320000.00, 0.00, 1280000.00),
(252, 83, 64, 9, 425000.00, 0.00, 3825000.00),
(253, 83, 65, 10, 950.00, 0.00, 9500.00),
(254, 83, 72, 5, 12500.00, 0.00, 62500.00),
(255, 83, 68, 3, 185000.00, 0.00, 555000.00),
(256, 84, 68, 7, 185000.00, 0.00, 1295000.00),
(257, 84, 71, 9, 8500.00, 0.00, 76500.00),
(258, 84, 70, 1, 2000.00, 0.00, 2000.00),
(259, 84, 63, 9, 320000.00, 0.00, 2880000.00),
(260, 85, 60, 4, 13200.00, 0.00, 52800.00),
(261, 85, 61, 1, 24500.00, 0.00, 24500.00),
(262, 86, 70, 5, 2000.00, 0.00, 10000.00),
(263, 86, 69, 9, 95000.00, 0.00, 855000.00),
(264, 86, 61, 5, 24500.00, 0.00, 122500.00),
(265, 87, 64, 3, 425000.00, 0.00, 1275000.00),
(266, 87, 61, 4, 24500.00, 0.00, 98000.00),
(267, 87, 65, 7, 950.00, 0.00, 6650.00),
(268, 90, 2, 2, 280000.00, 2000.00, 558000.00),
(269, 91, 1, 1, 180000.00, 0.00, 180000.00);

-- --------------------------------------------------------

--
-- Structure de la table `visiteurs_hotel`
--

CREATE TABLE `visiteurs_hotel` (
  `id` int(10) UNSIGNED NOT NULL,
  `date_visite` date NOT NULL,
  `nom_visiteur` varchar(150) NOT NULL,
  `telephone` varchar(50) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `motif` text NOT NULL,
  `service_solicite` varchar(150) NOT NULL,
  `orientation` varchar(150) DEFAULT NULL,
  `concierge_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `visiteurs_showroom`
--

CREATE TABLE `visiteurs_showroom` (
  `id` int(10) UNSIGNED NOT NULL,
  `date_visite` date NOT NULL,
  `client_nom` varchar(150) NOT NULL,
  `contact` varchar(100) NOT NULL,
  `produit_interet` text DEFAULT NULL,
  `orientation` varchar(100) DEFAULT NULL,
  `client_id` int(10) UNSIGNED DEFAULT NULL,
  `utilisateur_id` int(10) UNSIGNED NOT NULL,
  `converti_en_devis` tinyint(1) NOT NULL DEFAULT 0,
  `converti_en_vente` tinyint(1) NOT NULL DEFAULT 0,
  `date_conversion` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `visiteurs_showroom`
--

INSERT INTO `visiteurs_showroom` (`id`, `date_visite`, `client_nom`, `contact`, `produit_interet`, `orientation`, `client_id`, `utilisateur_id`, `converti_en_devis`, `converti_en_vente`, `date_conversion`) VALUES
(1, '2025-12-13', 'MR tsimi', '657882566', 'Il voulait un meuble Tv', 'Autre', NULL, 1, 0, 0, NULL),
(2, '2025-12-13', 'Janvier Soh', '233567555', NULL, 'Autre', 105, 1, 0, 1, '2025-12-13');

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `v_pipeline_commercial`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `v_pipeline_commercial` (
`canal` varchar(8)
,`source_id` int(10) unsigned
,`prospect_nom` varchar(150)
,`date_entree` date
,`converti_en_devis` int(1)
,`converti_en_vente` int(1)
,`statut_pipeline` enum('NOUVEAU','CONTACTE','QUALIFIE','DEVIS_ENVOYE','CONVERTI','PERDU')
);

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `v_ventes_livraison_encaissement`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `v_ventes_livraison_encaissement` (
`id` int(10) unsigned
,`numero` varchar(50)
,`date_vente` date
,`montant_total_ttc` decimal(15,2)
,`statut_vente` enum('DEVIS','DEVIS_ACCEPTE','EN_ATTENTE_LIVRAISON','EN_PREPARATION','PRET_LIVRAISON','PARTIELLEMENT_LIVREE','LIVREE','FACTUREE','PAYEE','ANNULEE')
,`statut_livraison` varchar(9)
,`montant_encaisse` decimal(37,2)
,`solde_du` decimal(38,2)
);

-- --------------------------------------------------------

--
-- Structure de la vue `v_pipeline_commercial`
--
DROP TABLE IF EXISTS `v_pipeline_commercial`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_pipeline_commercial`  AS SELECT 'SHOWROOM' AS `canal`, `vs`.`id` AS `source_id`, `vs`.`client_nom` AS `prospect_nom`, `vs`.`date_visite` AS `date_entree`, 0 AS `converti_en_devis`, 0 AS `converti_en_vente`, NULL AS `statut_pipeline` FROM `visiteurs_showroom` AS `vs`union all select 'TERRAIN' AS `canal`,`pt`.`id` AS `source_id`,`pt`.`prospect_nom` AS `prospect_nom`,`pt`.`date_prospection` AS `date_entree`,0 AS `converti_en_devis`,0 AS `converti_en_vente`,NULL AS `statut_pipeline` from `prospections_terrain` `pt` union all select 'DIGITAL' AS `canal`,`ld`.`id` AS `source_id`,`ld`.`nom_prospect` AS `prospect_nom`,`ld`.`date_lead` AS `date_entree`,`ld`.`statut` in ('DEVIS_ENVOYE','CONVERTI') AS `converti_en_devis`,`ld`.`statut` = 'CONVERTI' AS `converti_en_vente`,`ld`.`statut` AS `statut_pipeline` from `leads_digital` `ld`  ;

-- --------------------------------------------------------

--
-- Structure de la vue `v_ventes_livraison_encaissement`
--
DROP TABLE IF EXISTS `v_ventes_livraison_encaissement`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_ventes_livraison_encaissement`  AS SELECT `v`.`id` AS `id`, `v`.`numero` AS `numero`, `v`.`date_vente` AS `date_vente`, `v`.`montant_total_ttc` AS `montant_total_ttc`, `v`.`statut` AS `statut_vente`, CASE WHEN exists(select 1 from `bons_livraison` `bl` where `bl`.`vente_id` = `v`.`id` AND `bl`.`signe_client` = 1 limit 1) THEN 'LIVRE' ELSE 'NON_LIVRE' END AS `statut_livraison`, coalesce((select sum(`jc`.`montant`) from `journal_caisse` `jc` where `jc`.`vente_id` = `v`.`id`),0) AS `montant_encaisse`, `v`.`montant_total_ttc`- coalesce((select sum(`jc`.`montant`) from `journal_caisse` `jc` where `jc`.`vente_id` = `v`.`id`),0) AS `solde_du` FROM `ventes` AS `v` ;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `achats`
--
ALTER TABLE `achats`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_achats_utilisateur` (`utilisateur_id`);

--
-- Index pour la table `achats_lignes`
--
ALTER TABLE `achats_lignes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_achats_lignes_achat` (`achat_id`),
  ADD KEY `fk_achats_lignes_produit` (`produit_id`);

--
-- Index pour la table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_audit_user` (`utilisateur_id`),
  ADD KEY `idx_audit_date` (`date_action`),
  ADD KEY `idx_audit_module` (`module`),
  ADD KEY `idx_audit_action` (`action`),
  ADD KEY `idx_audit_niveau` (`niveau`),
  ADD KEY `idx_audit_entite` (`entite_type`,`entite_id`);

--
-- Index pour la table `blocages_ip`
--
ALTER TABLE `blocages_ip`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_ip` (`ip_address`),
  ADD KEY `idx_blocage_actif` (`actif`),
  ADD KEY `idx_blocage_expiration` (`date_expiration`);

--
-- Index pour la table `bons_livraison`
--
ALTER TABLE `bons_livraison`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero` (`numero`),
  ADD KEY `fk_bl_vente` (`vente_id`),
  ADD KEY `fk_bl_client` (`client_id`),
  ADD KEY `fk_bl_magasinier` (`magasinier_id`),
  ADD KEY `idx_bl_date` (`date_bl`),
  ADD KEY `idx_livreur` (`livreur_id`),
  ADD KEY `idx_statut` (`statut`),
  ADD KEY `idx_ordre_preparation` (`ordre_preparation_id`);

--
-- Index pour la table `bons_livraison_lignes`
--
ALTER TABLE `bons_livraison_lignes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_bl_lignes_bl` (`bon_livraison_id`),
  ADD KEY `fk_bl_lignes_produit` (`produit_id`);

--
-- Index pour la table `caisses_clotures`
--
ALTER TABLE `caisses_clotures`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_date` (`date_cloture`),
  ADD KEY `idx_statut` (`statut`),
  ADD KEY `idx_caissier` (`caissier_id`),
  ADD KEY `validateur_id` (`validateur_id`);

--
-- Index pour la table `caisse_journal`
--
ALTER TABLE `caisse_journal`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `canaux_vente`
--
ALTER TABLE `canaux_vente`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Index pour la table `catalogue_categories`
--
ALTER TABLE `catalogue_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Index pour la table `catalogue_produits`
--
ALTER TABLE `catalogue_produits`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `fk_catalogue_categorie` (`categorie_id`);

--
-- Index pour la table `chambres`
--
ALTER TABLE `chambres`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Index pour la table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_clients_type` (`type_client_id`),
  ADD KEY `idx_clients_nom` (`nom`);

--
-- Index pour la table `compta_comptes`
--
ALTER TABLE `compta_comptes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero_compte` (`numero_compte`),
  ADD KEY `compte_parent_id` (`compte_parent_id`),
  ADD KEY `idx_numero` (`numero_compte`),
  ADD KEY `idx_classe` (`classe`),
  ADD KEY `idx_nature` (`nature`);

--
-- Index pour la table `compta_ecritures`
--
ALTER TABLE `compta_ecritures`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tiers_client_id` (`tiers_client_id`),
  ADD KEY `tiers_fournisseur_id` (`tiers_fournisseur_id`),
  ADD KEY `idx_compte` (`compte_id`),
  ADD KEY `idx_piece` (`piece_id`),
  ADD KEY `idx_debit_credit` (`debit`,`credit`);

--
-- Index pour la table `compta_exercices`
--
ALTER TABLE `compta_exercices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `annee` (`annee`);

--
-- Index pour la table `compta_journaux`
--
ALTER TABLE `compta_journaux`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `compte_contre_partie` (`compte_contre_partie`);

--
-- Index pour la table `compta_mapping_operations`
--
ALTER TABLE `compta_mapping_operations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_mapping` (`source_type`,`code_operation`),
  ADD KEY `journal_id` (`journal_id`),
  ADD KEY `compte_debit_id` (`compte_debit_id`),
  ADD KEY `compte_credit_id` (`compte_credit_id`),
  ADD KEY `idx_source` (`source_type`,`code_operation`);

--
-- Index pour la table `compta_operations_trace`
--
ALTER TABLE `compta_operations_trace`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_trace` (`source_type`,`source_id`),
  ADD KEY `piece_id` (`piece_id`),
  ADD KEY `idx_status` (`status`);

--
-- Index pour la table `compta_pieces`
--
ALTER TABLE `compta_pieces`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_piece` (`exercice_id`,`journal_id`,`numero_piece`),
  ADD KEY `journal_id` (`journal_id`),
  ADD KEY `tiers_client_id` (`tiers_client_id`),
  ADD KEY `tiers_fournisseur_id` (`tiers_fournisseur_id`),
  ADD KEY `idx_date` (`date_piece`),
  ADD KEY `idx_ref` (`reference_type`,`reference_id`);

--
-- Index pour la table `connexions_utilisateur`
--
ALTER TABLE `connexions_utilisateur`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_connexions_utilisateur_utilisateur` (`utilisateur_id`),
  ADD KEY `idx_connexions_utilisateur_date` (`date_connexion`);

--
-- Index pour la table `conversions_pipeline`
--
ALTER TABLE `conversions_pipeline`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_conversions_source` (`source_type`,`source_id`),
  ADD KEY `idx_conversions_client` (`client_id`),
  ADD KEY `idx_conversions_date` (`date_conversion`),
  ADD KEY `fk_conversions_canal` (`canal_vente_id`),
  ADD KEY `fk_conversions_devis` (`devis_id`),
  ADD KEY `fk_conversions_vente` (`vente_id`);

--
-- Index pour la table `devis`
--
ALTER TABLE `devis`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero` (`numero`),
  ADD KEY `fk_devis_client` (`client_id`),
  ADD KEY `fk_devis_canal` (`canal_vente_id`),
  ADD KEY `fk_devis_utilisateur` (`utilisateur_id`),
  ADD KEY `idx_devis_date` (`date_devis`),
  ADD KEY `idx_devis_statut` (`statut`);

--
-- Index pour la table `devis_lignes`
--
ALTER TABLE `devis_lignes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_devis_lignes_devis` (`devis_id`),
  ADD KEY `fk_devis_lignes_produit` (`produit_id`);

--
-- Index pour la table `familles_produits`
--
ALTER TABLE `familles_produits`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `formations`
--
ALTER TABLE `formations`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `fournisseurs`
--
ALTER TABLE `fournisseurs`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `inscriptions_formation`
--
ALTER TABLE `inscriptions_formation`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_inscription_client` (`client_id`),
  ADD KEY `fk_inscription_formation` (`formation_id`),
  ADD KEY `idx_inscription_date` (`date_inscription`);

--
-- Index pour la table `journal_caisse`
--
ALTER TABLE `journal_caisse`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_caisse_mode_paiement` (`mode_paiement_id`),
  ADD KEY `fk_caisse_vente` (`vente_id`),
  ADD KEY `fk_caisse_reservation` (`reservation_id`),
  ADD KEY `fk_caisse_inscription` (`inscription_formation_id`),
  ADD KEY `fk_caisse_responsable` (`responsable_encaissement_id`),
  ADD KEY `idx_caisse_date` (`date_operation`),
  ADD KEY `fk_journal_caisse_annule_par` (`annule_par_id`);

--
-- Index pour la table `kpis_quotidiens`
--
ALTER TABLE `kpis_quotidiens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_kpis_unique` (`date`,`canal`),
  ADD KEY `idx_kpis_date` (`date`);

--
-- Index pour la table `leads_digital`
--
ALTER TABLE `leads_digital`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_leads_source` (`source`),
  ADD KEY `idx_leads_statut` (`statut`),
  ADD KEY `idx_leads_date` (`date_lead`),
  ADD KEY `idx_leads_prochaine_action` (`date_prochaine_action`),
  ADD KEY `fk_leads_client` (`client_id`),
  ADD KEY `fk_leads_utilisateur` (`utilisateur_responsable_id`);

--
-- Index pour la table `modes_paiement`
--
ALTER TABLE `modes_paiement`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Index pour la table `mouvements_stock_backup_20251209_161710`
--
ALTER TABLE `mouvements_stock_backup_20251209_161710`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_mouvements_stock_produit` (`produit_id`),
  ADD KEY `idx_mouvements_stock_utilisateur` (`utilisateur_id`);

--
-- Index pour la table `objectifs_commerciaux`
--
ALTER TABLE `objectifs_commerciaux`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_objectifs_unique` (`annee`,`mois`,`canal`),
  ADD KEY `idx_objectifs_periode` (`annee`,`mois`);

--
-- Index pour la table `ordres_preparation`
--
ALTER TABLE `ordres_preparation`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero_ordre` (`numero_ordre`),
  ADD KEY `idx_ordres_date` (`date_ordre`),
  ADD KEY `idx_ordres_statut` (`statut`),
  ADD KEY `idx_ordres_commercial` (`commercial_responsable_id`),
  ADD KEY `fk_ordres_vente` (`vente_id`),
  ADD KEY `fk_ordres_devis` (`devis_id`),
  ADD KEY `fk_ordres_client` (`client_id`),
  ADD KEY `fk_ordres_magasinier` (`magasinier_id`);

--
-- Index pour la table `ordres_preparation_lignes`
--
ALTER TABLE `ordres_preparation_lignes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ordres_lignes_ordre` (`ordre_preparation_id`),
  ADD KEY `fk_ordres_lignes_produit` (`produit_id`);

--
-- Index pour la table `parametres_securite`
--
ALTER TABLE `parametres_securite`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cle` (`cle`),
  ADD KEY `modifie_par` (`modifie_par`);

--
-- Index pour la table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Index pour la table `produits`
--
ALTER TABLE `produits`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code_produit` (`code_produit`),
  ADD KEY `fk_produits_famille` (`famille_id`),
  ADD KEY `fk_produits_sous_categorie` (`sous_categorie_id`),
  ADD KEY `fk_produits_fournisseur` (`fournisseur_id`),
  ADD KEY `idx_produits_designation` (`designation`),
  ADD KEY `idx_produits_code` (`code_produit`);

--
-- Index pour la table `promotions`
--
ALTER TABLE `promotions`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `promotion_produit`
--
ALTER TABLE `promotion_produit`
  ADD PRIMARY KEY (`promotion_id`,`produit_id`),
  ADD KEY `fk_promo_produit_produit` (`produit_id`);

--
-- Index pour la table `prospections_terrain`
--
ALTER TABLE `prospections_terrain`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_prospections_client` (`client_id`),
  ADD KEY `fk_prospections_commercial` (`commercial_id`),
  ADD KEY `idx_prospections_date` (`date_prospection`);

--
-- Index pour la table `prospects_formation`
--
ALTER TABLE `prospects_formation`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_prospect_formation_client` (`client_id`),
  ADD KEY `fk_prospect_formation_utilisateur` (`utilisateur_id`),
  ADD KEY `idx_prospect_formation_date` (`date_prospect`);

--
-- Index pour la table `relances_devis`
--
ALTER TABLE `relances_devis`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_relances_devis` (`devis_id`),
  ADD KEY `idx_relances_date` (`date_relance`),
  ADD KEY `fk_relances_utilisateur` (`utilisateur_id`);

--
-- Index pour la table `rendezvous_terrain`
--
ALTER TABLE `rendezvous_terrain`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_rdv_client` (`client_id`),
  ADD KEY `fk_rdv_commercial` (`commercial_id`),
  ADD KEY `idx_rdv_date` (`date_rdv`);

--
-- Index pour la table `reservations_hotel`
--
ALTER TABLE `reservations_hotel`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_reservation_client` (`client_id`),
  ADD KEY `fk_reservation_chambre` (`chambre_id`),
  ADD KEY `fk_reservation_mode_paiement` (`mode_paiement_id`),
  ADD KEY `fk_reservation_concierge` (`concierge_id`),
  ADD KEY `idx_reservation_dates` (`date_debut`,`date_fin`);

--
-- Index pour la table `retours_litiges`
--
ALTER TABLE `retours_litiges`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_litiges_client` (`client_id`),
  ADD KEY `fk_litiges_produit` (`produit_id`),
  ADD KEY `fk_litiges_vente` (`vente_id`),
  ADD KEY `fk_litiges_responsable` (`responsable_suivi_id`);

--
-- Index pour la table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Index pour la table `role_permission`
--
ALTER TABLE `role_permission`
  ADD PRIMARY KEY (`role_id`,`permission_id`),
  ADD KEY `fk_role_permission_permission` (`permission_id`);

--
-- Index pour la table `ruptures_signalees`
--
ALTER TABLE `ruptures_signalees`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ruptures_date` (`date_signalement`),
  ADD KEY `idx_ruptures_produit` (`produit_id`),
  ADD KEY `idx_ruptures_statut` (`statut_traitement`),
  ADD KEY `fk_ruptures_sig_magasinier` (`magasinier_id`);

--
-- Index pour la table `ruptures_stock`
--
ALTER TABLE `ruptures_stock`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ruptures_produit` (`produit_id`),
  ADD KEY `fk_ruptures_magasinier` (`magasinier_id`),
  ADD KEY `idx_ruptures_date` (`date_rapport`);

--
-- Index pour la table `satisfaction_clients`
--
ALTER TABLE `satisfaction_clients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_satisfaction_client` (`client_id`),
  ADD KEY `fk_satisfaction_utilisateur` (`utilisateur_id`),
  ADD KEY `idx_satisfaction_date` (`date_satisfaction`);

--
-- Index pour la table `sessions_actives`
--
ALTER TABLE `sessions_actives`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_session_user` (`utilisateur_id`),
  ADD KEY `idx_session_expiration` (`date_expiration`),
  ADD KEY `idx_session_actif` (`actif`);

--
-- Index pour la table `sms_2fa_codes`
--
ALTER TABLE `sms_2fa_codes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_utilisateur` (`utilisateur_id`),
  ADD KEY `idx_expiration` (`expire_a`),
  ADD KEY `idx_utilise` (`utilise`);

--
-- Index pour la table `sms_tracking`
--
ALTER TABLE `sms_tracking`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_utilisateur` (`utilisateur_id`),
  ADD KEY `idx_telephone` (`telephone`),
  ADD KEY `idx_date` (`envoye_a`);

--
-- Index pour la table `sous_categories_produits`
--
ALTER TABLE `sous_categories_produits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_sous_categories_famille` (`famille_id`);

--
-- Index pour la table `stocks_mouvements`
--
ALTER TABLE `stocks_mouvements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_mouvements_produit` (`produit_id`),
  ADD KEY `fk_mouvements_utilisateur` (`utilisateur_id`),
  ADD KEY `idx_mouvements_date` (`date_mouvement`);

--
-- Index pour la table `tentatives_connexion`
--
ALTER TABLE `tentatives_connexion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tentative_date` (`date_tentative`),
  ADD KEY `idx_tentative_ip` (`ip_address`),
  ADD KEY `idx_tentative_succes` (`succes`),
  ADD KEY `idx_tentative_user` (`utilisateur_id`);

--
-- Index pour la table `types_client`
--
ALTER TABLE `types_client`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Index pour la table `upsell_hotel`
--
ALTER TABLE `upsell_hotel`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_upsell_reservation` (`reservation_id`);

--
-- Index pour la table `user_preferences`
--
ALTER TABLE `user_preferences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_page` (`utilisateur_id`,`page_name`),
  ADD KEY `idx_utilisateur` (`utilisateur_id`),
  ADD KEY `idx_page` (`page_name`);

--
-- Index pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `login` (`login`),
  ADD KEY `idx_compte_verrouille` (`compte_verrouille`),
  ADD KEY `idx_mdp_expire` (`mdp_expire`);

--
-- Index pour la table `utilisateurs_2fa`
--
ALTER TABLE `utilisateurs_2fa`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_2fa` (`utilisateur_id`),
  ADD KEY `idx_2fa_actif` (`actif`),
  ADD KEY `idx_methode` (`methode_2fa`);

--
-- Index pour la table `utilisateurs_2fa_recovery`
--
ALTER TABLE `utilisateurs_2fa_recovery`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_recovery_user` (`utilisateur_id`),
  ADD KEY `idx_recovery_utilise` (`utilise`);

--
-- Index pour la table `utilisateur_role`
--
ALTER TABLE `utilisateur_role`
  ADD PRIMARY KEY (`utilisateur_id`,`role_id`),
  ADD KEY `fk_utilisateur_role_role` (`role_id`);

--
-- Index pour la table `ventes`
--
ALTER TABLE `ventes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero` (`numero`),
  ADD KEY `fk_ventes_client` (`client_id`),
  ADD KEY `fk_ventes_canal` (`canal_vente_id`),
  ADD KEY `fk_ventes_devis` (`devis_id`),
  ADD KEY `fk_ventes_utilisateur` (`utilisateur_id`),
  ADD KEY `idx_ventes_date` (`date_vente`),
  ADD KEY `idx_ventes_statut` (`statut`);

--
-- Index pour la table `ventes_lignes`
--
ALTER TABLE `ventes_lignes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ventes_lignes_vente` (`vente_id`),
  ADD KEY `fk_ventes_lignes_produit` (`produit_id`);

--
-- Index pour la table `visiteurs_hotel`
--
ALTER TABLE `visiteurs_hotel`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_visiteurs_hotel_concierge` (`concierge_id`),
  ADD KEY `idx_visiteurs_hotel_date` (`date_visite`);

--
-- Index pour la table `visiteurs_showroom`
--
ALTER TABLE `visiteurs_showroom`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_visiteurs_client` (`client_id`),
  ADD KEY `fk_visiteurs_utilisateur` (`utilisateur_id`),
  ADD KEY `idx_visiteurs_date` (`date_visite`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `achats`
--
ALTER TABLE `achats`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `achats_lignes`
--
ALTER TABLE `achats_lignes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `blocages_ip`
--
ALTER TABLE `blocages_ip`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `bons_livraison`
--
ALTER TABLE `bons_livraison`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=83;

--
-- AUTO_INCREMENT pour la table `bons_livraison_lignes`
--
ALTER TABLE `bons_livraison_lignes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=209;

--
-- AUTO_INCREMENT pour la table `caisses_clotures`
--
ALTER TABLE `caisses_clotures`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `caisse_journal`
--
ALTER TABLE `caisse_journal`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT pour la table `canaux_vente`
--
ALTER TABLE `canaux_vente`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `catalogue_categories`
--
ALTER TABLE `catalogue_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT pour la table `catalogue_produits`
--
ALTER TABLE `catalogue_produits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=168;

--
-- AUTO_INCREMENT pour la table `chambres`
--
ALTER TABLE `chambres`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=106;

--
-- AUTO_INCREMENT pour la table `compta_comptes`
--
ALTER TABLE `compta_comptes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=118;

--
-- AUTO_INCREMENT pour la table `compta_ecritures`
--
ALTER TABLE `compta_ecritures`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT pour la table `compta_exercices`
--
ALTER TABLE `compta_exercices`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `compta_journaux`
--
ALTER TABLE `compta_journaux`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `compta_mapping_operations`
--
ALTER TABLE `compta_mapping_operations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `compta_operations_trace`
--
ALTER TABLE `compta_operations_trace`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT pour la table `compta_pieces`
--
ALTER TABLE `compta_pieces`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT pour la table `connexions_utilisateur`
--
ALTER TABLE `connexions_utilisateur`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT pour la table `conversions_pipeline`
--
ALTER TABLE `conversions_pipeline`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `devis`
--
ALTER TABLE `devis`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

--
-- AUTO_INCREMENT pour la table `devis_lignes`
--
ALTER TABLE `devis_lignes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=251;

--
-- AUTO_INCREMENT pour la table `familles_produits`
--
ALTER TABLE `familles_produits`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- AUTO_INCREMENT pour la table `formations`
--
ALTER TABLE `formations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `fournisseurs`
--
ALTER TABLE `fournisseurs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT pour la table `inscriptions_formation`
--
ALTER TABLE `inscriptions_formation`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT pour la table `journal_caisse`
--
ALTER TABLE `journal_caisse`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;

--
-- AUTO_INCREMENT pour la table `kpis_quotidiens`
--
ALTER TABLE `kpis_quotidiens`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `leads_digital`
--
ALTER TABLE `leads_digital`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `modes_paiement`
--
ALTER TABLE `modes_paiement`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `mouvements_stock_backup_20251209_161710`
--
ALTER TABLE `mouvements_stock_backup_20251209_161710`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `objectifs_commerciaux`
--
ALTER TABLE `objectifs_commerciaux`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `ordres_preparation`
--
ALTER TABLE `ordres_preparation`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `ordres_preparation_lignes`
--
ALTER TABLE `ordres_preparation_lignes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `parametres_securite`
--
ALTER TABLE `parametres_securite`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT pour la table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT pour la table `produits`
--
ALTER TABLE `produits`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT pour la table `promotions`
--
ALTER TABLE `promotions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `prospections_terrain`
--
ALTER TABLE `prospections_terrain`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `prospects_formation`
--
ALTER TABLE `prospects_formation`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `relances_devis`
--
ALTER TABLE `relances_devis`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `rendezvous_terrain`
--
ALTER TABLE `rendezvous_terrain`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `reservations_hotel`
--
ALTER TABLE `reservations_hotel`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT pour la table `retours_litiges`
--
ALTER TABLE `retours_litiges`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `ruptures_signalees`
--
ALTER TABLE `ruptures_signalees`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `ruptures_stock`
--
ALTER TABLE `ruptures_stock`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `satisfaction_clients`
--
ALTER TABLE `satisfaction_clients`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `sms_2fa_codes`
--
ALTER TABLE `sms_2fa_codes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `sms_tracking`
--
ALTER TABLE `sms_tracking`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `sous_categories_produits`
--
ALTER TABLE `sous_categories_produits`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `stocks_mouvements`
--
ALTER TABLE `stocks_mouvements`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=276;

--
-- AUTO_INCREMENT pour la table `tentatives_connexion`
--
ALTER TABLE `tentatives_connexion`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT pour la table `types_client`
--
ALTER TABLE `types_client`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT pour la table `upsell_hotel`
--
ALTER TABLE `upsell_hotel`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `user_preferences`
--
ALTER TABLE `user_preferences`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT pour la table `utilisateurs_2fa`
--
ALTER TABLE `utilisateurs_2fa`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `utilisateurs_2fa_recovery`
--
ALTER TABLE `utilisateurs_2fa_recovery`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `ventes`
--
ALTER TABLE `ventes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=92;

--
-- AUTO_INCREMENT pour la table `ventes_lignes`
--
ALTER TABLE `ventes_lignes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=270;

--
-- AUTO_INCREMENT pour la table `visiteurs_hotel`
--
ALTER TABLE `visiteurs_hotel`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `visiteurs_showroom`
--
ALTER TABLE `visiteurs_showroom`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `achats`
--
ALTER TABLE `achats`
  ADD CONSTRAINT `fk_achats_utilisateur` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `achats_lignes`
--
ALTER TABLE `achats_lignes`
  ADD CONSTRAINT `fk_achats_lignes_achat` FOREIGN KEY (`achat_id`) REFERENCES `achats` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_achats_lignes_produit` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `audit_log`
--
ALTER TABLE `audit_log`
  ADD CONSTRAINT `audit_log_ibfk_1` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `bons_livraison`
--
ALTER TABLE `bons_livraison`
  ADD CONSTRAINT `fk_bl_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_bl_magasinier` FOREIGN KEY (`magasinier_id`) REFERENCES `utilisateurs` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_bl_vente` FOREIGN KEY (`vente_id`) REFERENCES `ventes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `bons_livraison_lignes`
--
ALTER TABLE `bons_livraison_lignes`
  ADD CONSTRAINT `fk_bl_lignes_bl` FOREIGN KEY (`bon_livraison_id`) REFERENCES `bons_livraison` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_bl_lignes_produit` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `caisses_clotures`
--
ALTER TABLE `caisses_clotures`
  ADD CONSTRAINT `caisses_clotures_ibfk_1` FOREIGN KEY (`caissier_id`) REFERENCES `utilisateurs` (`id`),
  ADD CONSTRAINT `caisses_clotures_ibfk_2` FOREIGN KEY (`validateur_id`) REFERENCES `utilisateurs` (`id`);

--
-- Contraintes pour la table `catalogue_produits`
--
ALTER TABLE `catalogue_produits`
  ADD CONSTRAINT `fk_catalogue_categorie` FOREIGN KEY (`categorie_id`) REFERENCES `catalogue_categories` (`id`);

--
-- Contraintes pour la table `clients`
--
ALTER TABLE `clients`
  ADD CONSTRAINT `fk_clients_type` FOREIGN KEY (`type_client_id`) REFERENCES `types_client` (`id`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `compta_comptes`
--
ALTER TABLE `compta_comptes`
  ADD CONSTRAINT `compta_comptes_ibfk_1` FOREIGN KEY (`compte_parent_id`) REFERENCES `compta_comptes` (`id`);

--
-- Contraintes pour la table `compta_ecritures`
--
ALTER TABLE `compta_ecritures`
  ADD CONSTRAINT `compta_ecritures_ibfk_1` FOREIGN KEY (`piece_id`) REFERENCES `compta_pieces` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `compta_ecritures_ibfk_2` FOREIGN KEY (`compte_id`) REFERENCES `compta_comptes` (`id`),
  ADD CONSTRAINT `compta_ecritures_ibfk_3` FOREIGN KEY (`tiers_client_id`) REFERENCES `clients` (`id`),
  ADD CONSTRAINT `compta_ecritures_ibfk_4` FOREIGN KEY (`tiers_fournisseur_id`) REFERENCES `fournisseurs` (`id`);

--
-- Contraintes pour la table `compta_journaux`
--
ALTER TABLE `compta_journaux`
  ADD CONSTRAINT `compta_journaux_ibfk_1` FOREIGN KEY (`compte_contre_partie`) REFERENCES `compta_comptes` (`id`);

--
-- Contraintes pour la table `compta_mapping_operations`
--
ALTER TABLE `compta_mapping_operations`
  ADD CONSTRAINT `compta_mapping_operations_ibfk_1` FOREIGN KEY (`journal_id`) REFERENCES `compta_journaux` (`id`),
  ADD CONSTRAINT `compta_mapping_operations_ibfk_2` FOREIGN KEY (`compte_debit_id`) REFERENCES `compta_comptes` (`id`),
  ADD CONSTRAINT `compta_mapping_operations_ibfk_3` FOREIGN KEY (`compte_credit_id`) REFERENCES `compta_comptes` (`id`);

--
-- Contraintes pour la table `compta_operations_trace`
--
ALTER TABLE `compta_operations_trace`
  ADD CONSTRAINT `compta_operations_trace_ibfk_1` FOREIGN KEY (`piece_id`) REFERENCES `compta_pieces` (`id`);

--
-- Contraintes pour la table `compta_pieces`
--
ALTER TABLE `compta_pieces`
  ADD CONSTRAINT `compta_pieces_ibfk_1` FOREIGN KEY (`exercice_id`) REFERENCES `compta_exercices` (`id`),
  ADD CONSTRAINT `compta_pieces_ibfk_2` FOREIGN KEY (`journal_id`) REFERENCES `compta_journaux` (`id`),
  ADD CONSTRAINT `compta_pieces_ibfk_3` FOREIGN KEY (`tiers_client_id`) REFERENCES `clients` (`id`),
  ADD CONSTRAINT `compta_pieces_ibfk_4` FOREIGN KEY (`tiers_fournisseur_id`) REFERENCES `fournisseurs` (`id`);

--
-- Contraintes pour la table `connexions_utilisateur`
--
ALTER TABLE `connexions_utilisateur`
  ADD CONSTRAINT `fk_connexions_utilisateur_utilisateur` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `conversions_pipeline`
--
ALTER TABLE `conversions_pipeline`
  ADD CONSTRAINT `fk_conversions_canal` FOREIGN KEY (`canal_vente_id`) REFERENCES `canaux_vente` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_conversions_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_conversions_devis` FOREIGN KEY (`devis_id`) REFERENCES `devis` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_conversions_vente` FOREIGN KEY (`vente_id`) REFERENCES `ventes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `devis`
--
ALTER TABLE `devis`
  ADD CONSTRAINT `fk_devis_canal` FOREIGN KEY (`canal_vente_id`) REFERENCES `canaux_vente` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_devis_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_devis_utilisateur` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `devis_lignes`
--
ALTER TABLE `devis_lignes`
  ADD CONSTRAINT `fk_devis_lignes_devis` FOREIGN KEY (`devis_id`) REFERENCES `devis` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_devis_lignes_produit` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `inscriptions_formation`
--
ALTER TABLE `inscriptions_formation`
  ADD CONSTRAINT `fk_inscription_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_inscription_formation` FOREIGN KEY (`formation_id`) REFERENCES `formations` (`id`) ON UPDATE CASCADE;

--
-- Contraintes pour la table `journal_caisse`
--
ALTER TABLE `journal_caisse`
  ADD CONSTRAINT `fk_caisse_inscription` FOREIGN KEY (`inscription_formation_id`) REFERENCES `inscriptions_formation` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_caisse_mode_paiement` FOREIGN KEY (`mode_paiement_id`) REFERENCES `modes_paiement` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_caisse_reservation` FOREIGN KEY (`reservation_id`) REFERENCES `reservations_hotel` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_caisse_responsable` FOREIGN KEY (`responsable_encaissement_id`) REFERENCES `utilisateurs` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_caisse_vente` FOREIGN KEY (`vente_id`) REFERENCES `ventes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_journal_caisse_annule_par` FOREIGN KEY (`annule_par_id`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `leads_digital`
--
ALTER TABLE `leads_digital`
  ADD CONSTRAINT `fk_leads_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_leads_utilisateur` FOREIGN KEY (`utilisateur_responsable_id`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
