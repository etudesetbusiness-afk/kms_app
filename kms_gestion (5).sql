-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 13, 2025 at 05:41 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `kms_gestion`
--

DELIMITER $$
--
-- Procedures
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
-- Table structure for table `achats`
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
-- Table structure for table `achats_lignes`
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
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `utilisateur_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'NULL si action syst??me',
  `action` varchar(100) NOT NULL COMMENT 'Type action: LOGIN, LOGOUT, CREATE, UPDATE, DELETE',
  `module` varchar(50) NOT NULL COMMENT 'Module concern??: PRODUITS, VENTES, CAISSE, etc.',
  `entite_type` varchar(50) DEFAULT NULL COMMENT 'Type entit??: produit, vente, client',
  `entite_id` int(10) UNSIGNED DEFAULT NULL COMMENT 'ID de l''entit??',
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'D??tails de l''action' CHECK (json_valid(`details`)),
  `ancienne_valeur` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Valeur avant modification' CHECK (json_valid(`ancienne_valeur`)),
  `nouvelle_valeur` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Valeur apr??s modification' CHECK (json_valid(`nouvelle_valeur`)),
  `ip_address` varchar(45) NOT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `date_action` datetime DEFAULT current_timestamp(),
  `niveau` enum('INFO','WARNING','ERROR','CRITICAL') DEFAULT 'INFO'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Journal d''audit complet du syst??me';

-- --------------------------------------------------------

--
-- Table structure for table `blocages_ip`
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
-- Table structure for table `bons_livraison`
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
-- Dumping data for table `bons_livraison`
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
(61, 'BL-20251108-017', '2025-11-08', NULL, 87, NULL, 83, NULL, NULL, 1, 'LIVRE', 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `bons_livraison_lignes`
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
-- Dumping data for table `bons_livraison_lignes`
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
(191, 61, 65, 7, 7.00, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `caisse_journal`
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `caisse_journal`
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
-- Table structure for table `canaux_vente`
--

CREATE TABLE `canaux_vente` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` varchar(50) NOT NULL,
  `libelle` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `canaux_vente`
--

INSERT INTO `canaux_vente` (`id`, `code`, `libelle`) VALUES
(1, 'SHOWROOM', 'Vente showroom'),
(2, 'TERRAIN', 'Vente terrain'),
(3, 'DIGITAL', 'Vente digital / en ligne'),
(4, 'HOTEL', 'Vente liée à l\'hôtel'),
(5, 'FORMATION', 'Vente liée aux formations');

-- --------------------------------------------------------

--
-- Table structure for table `catalogue_categories`
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
-- Dumping data for table `catalogue_categories`
--

INSERT INTO `catalogue_categories` (`id`, `nom`, `slug`, `actif`, `ordre`, `created_at`, `updated_at`) VALUES
(19, 'Panneaux & Contreplaqués', 'panneaux', 1, 1, '2025-12-12 23:53:33', '2025-12-13 16:08:56'),
(20, 'Machines & Outils', 'machines', 1, 2, '2025-12-12 23:53:33', '2025-12-12 23:53:33'),
(21, 'Quincaillerie', 'quincaillerie', 1, 3, '2025-12-12 23:53:33', '2025-12-12 23:53:33'),
(22, 'Accessoires Menuiserie', 'accessoires', 1, 4, '2025-12-12 23:53:33', '2025-12-12 23:53:33'),
(23, 'Bois Brut', 'bois-brut', 1, 5, '2025-12-12 23:53:33', '2025-12-12 23:53:33'),
(24, 'Finitions & Vernis', 'finitions', 1, 6, '2025-12-12 23:53:33', '2025-12-12 23:53:33');

-- --------------------------------------------------------

--
-- Table structure for table `catalogue_produits`
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
-- Dumping data for table `catalogue_produits`
--

INSERT INTO `catalogue_produits` (`id`, `produit_id`, `code`, `slug`, `designation`, `categorie_id`, `prix_unite`, `prix_gros`, `description`, `caracteristiques_json`, `image_principale`, `galerie_images`, `actif`, `created_at`, `updated_at`) VALUES
(118, 31, 'PLQ-CTBX-18', 'plaque-ctbx-18mm', 'Panneau CTBX 18 mm', 19, 29500.00, 27500.00, 'Panneau contreplaqué CTBX haute résistance, idéal pour milieux humides et intérieurs modernes.', '{\"ïpaisseur\": \"18 mm\", \"Dimensions\": \"1220 x 2440 mm\", \"Essence\": \"Okoumï\", \"Classe\": \"Extïrieur\"}', NULL, '[]', 1, '2025-12-12 23:56:13', '2025-12-13 16:18:29'),
(119, 32, 'PLQ-CTBX-12', 'plaque-ctbx-12mm', 'Panneau CTBX 12 mm', 19, 22000.00, 20500.00, 'Contreplaqué fin CTBX pour mobilier intérieur et agencements légers.', '{\"ïpaisseur\": \"12 mm\", \"Dimensions\": \"1220 x 2440 mm\", \"Essence\": \"Okoumï\", \"Finition\": \"Brut\"}', NULL, '[]', 1, '2025-12-12 23:56:13', '2025-12-13 16:18:29'),
(120, 32, 'MDF-25', 'mdf-25mm', 'Panneau MDF 25 mm', 19, 18500.00, 17000.00, 'Medium Density Fiberboard, parfait pour menuiserie intérieure, portes et placards.', '{\"ïpaisseur\": \"25 mm\", \"Dimensions\": \"1220 x 2440 mm\", \"Densitï\": \"730 kg/mï\", \"Usage\": \"Intïrieur\"}', NULL, '[]', 1, '2025-12-12 23:56:13', '2025-12-13 16:18:29'),
(121, 32, 'MDF-16', 'mdf-16mm', 'Panneau MDF 16 mm', 19, 13200.00, 12300.00, 'MDF standard pour mobilier et revêtements intérieurs. Facile à usiner et peindre.', '{\"ïpaisseur\": \"16 mm\", \"Dimensions\": \"1220 x 2440 mm\", \"Densitï\": \"720 kg/mï\"}', NULL, '[]', 1, '2025-12-12 23:56:13', '2025-12-13 16:18:29'),
(122, NULL, 'HDF-3MM', 'hdf-3mm-laminate', 'Panneau HDF 3 mm laminé', 19, 8900.00, 8200.00, 'Panneau haute densité avec revêtement mélaminé pour plans de travail et surfaces de travail.', '{\"ïpaisseur\": \"3 mm\", \"Dimensions\": \"1220 x 2440 mm\", \"Revïtement\": \"Mïlaminï\", \"Finition\": \"Brillant\"}', NULL, '[]', 1, '2025-12-12 23:56:13', '2025-12-13 16:18:29'),
(123, 33, 'MULTIPLEX-21', 'multiplex-21mm', 'Multiplex 21 mm', 19, 24500.00, 22800.00, 'Contreplaqué multiplis pour construction légère, étagères et agencement intérieur.', '{\"ïpaisseur\": \"21 mm\", \"Dimensions\": \"1220 x 2440 mm\", \"Plis\": \"13\", \"Grade\": \"BB\"}', NULL, '[]', 1, '2025-12-12 23:56:13', '2025-12-13 16:18:29'),
(124, 34, 'SCIE-RBT-210', 'scie-ruban-210', 'Scie à Ruban 210 W', 20, 185000.00, 172000.00, 'Scie à ruban compacte et performante pour ateliers professionnels. Coupe précise bois, dérivés et matériaux composites.', '{\"Hauteur coupe\": \"210 mm\", \"Puissance\": \"1.5 kW\", \"Alimentation\": \"220V\", \"Capacitï\": \"Bois jusquï 150 mm\"}', NULL, '[]', 1, '2025-12-12 23:56:13', '2025-12-13 16:18:29'),
(125, NULL, 'DECOLLET-400', 'decolleteur-400', 'Décolleteur 400 mm', 20, 245000.00, 225000.00, 'Machine de découpe précise pour panneaux, contreplaqué et composites. Guide de profondeur ajustable.', '{\"Diamïtre lame\": \"400 mm\", \"Puissance\": \"2.2 kW\", \"Vitesse\": \"42 rpm\", \"Prïcision\": \"ï0.5 mm\"}', NULL, '[]', 1, '2025-12-12 23:56:13', '2025-12-13 16:18:29'),
(126, 35, 'RABOTEUSE-305', 'raboteuse-305mm', 'Raboteuse 305 mm', 20, 320000.00, 295000.00, 'Raboteuse professionnelle pour lissage de pièces brutes. Système d\'alimentation variable.', '{\"Largeur travail\": \"305 mm\", \"Puissance\": \"3 kW\", \"Capacitï ïpaisseur\": \"150 mm\", \"Rendement\": \"8 m/min\"}', NULL, '[]', 1, '2025-12-12 23:56:13', '2025-12-13 16:18:29'),
(127, 36, 'TOUPIE-2200', 'toupie-wood-2200', 'Toupie 2200 W', 20, 425000.00, 395000.00, 'Toupillage haute puissance pour fraisage, rainurage et profilage. Moteur brushless haute vitesse.', '{\"Puissance\": \"2200 W\", \"Vitesse\": \"8000-24000 rpm\", \"Capacitï\": \"Mïches 6-12 mm\", \"Table\": \"Acier\"}', NULL, '[]', 1, '2025-12-12 23:56:13', '2025-12-13 16:16:30'),
(128, NULL, 'SABLEUSE-ORBITALE', 'sableuse-orbitale-225', 'Sableuse Orbitale 225 mm', 20, 48900.00, 45000.00, 'Sableuse orbitale pour finition haute qualité. Vibration minimale et système d\'aspiration intégré.', '{\"Disque\": \"225 mm\", \"Puissance\": \"520 W\", \"Mouvements/min\": \"4800\", \"Aspiration\": \"36 L/min\"}', NULL, '[]', 1, '2025-12-12 23:56:13', '2025-12-13 16:18:29'),
(129, NULL, 'PERCEUSE-16', 'perceuse-percussion-16', 'Perceuse à Percussion 16 mm', 20, 35500.00, 32800.00, 'Perceuse-visseuse professionnelle avec mode percussion pour travaux lourds en atelier.', '{\"Capacitï\": \"16 mm\", \"Puissance\": \"900 W\", \"Couple\": \"45 Nm\", \"Vitesses\": \"Variable\"}', NULL, '[]', 1, '2025-12-12 23:56:13', '2025-12-13 16:17:22'),
(130, NULL, 'VISSEUSE-ECO', 'visseuse-sans-fil-18v', 'Visseuse sans-fil 18V', 20, 18900.00, 17500.00, 'Visseuse compacte avec batterie Li-Ion pour assemblage et finition intérieure.', '{\"Tension\": \"18 V\", \"Batterie\": \"Li-Ion 1.5 Ah\", \"Couple\": \"30 Nm\", \"Poids\": \"1.2 kg\"}', NULL, '[]', 1, '2025-12-12 23:56:13', '2025-12-13 16:18:29'),
(131, NULL, 'MEULEUSE-900', 'meuleuse-125mm-900w', 'Meuleuse 125 mm 900 W', 20, 22300.00, 20500.00, 'Meuleuse d\'angle compact pour découpe, meulage et travaux de finition rapides.', '{\"Diamïtre disque\": \"125 mm\", \"Puissance\": \"900 W\", \"Vitesse\": \"12000 rpm\", \"Poignïe\": \"Latïrale\"}', NULL, '[]', 1, '2025-12-12 23:56:13', '2025-12-13 16:18:29'),
(132, 37, 'CHARN-INOX-90', 'charniere-inox-90', 'Charnière Inox 90°', 21, 950.00, 850.00, 'Charnière pour portes meubles en acier inoxydable 304. Fermeture douce sans bruit.', '{\"Matiïre\": \"Inox 304\", \"Finition\": \"Brossï\", \"Angle\": \"90ï\", \"Capacitï\": \"30 kg\"}', NULL, '[]', 1, '2025-12-12 23:56:13', '2025-12-13 16:18:29'),
(133, NULL, 'CHARN-SOFT-CLOSE', 'charniere-soft-close-35', 'Charnière Soft-Close 35 mm', 21, 2800.00, 2550.00, 'Système de fermeture douce intégré. Fermeture progressive et silencieuse pour tous types de portes.', '{\"Type\": \"Overlay\", \"Ouverture\": \"110ï\", \"Capacitï\": \"40 kg\", \"Installation\": \"Invisible\"}', NULL, '[]', 1, '2025-12-12 23:56:13', '2025-12-13 16:18:29'),
(134, 39, 'POIGNEE-ALU-160', 'poignee-aluminium-160', 'Poignée Aluminium 160 mm', 21, 1200.00, 1050.00, 'Poignée contemporaine en aluminium anodisé. Design épuré pour tous styles de mobilier.', '{\"Longueur\": \"160 mm\", \"Matiïre\": \"Aluminium anodisï\", \"Finition\": \"Noir/Argent\", \"Distance trous\": \"128 mm\"}', NULL, '[]', 1, '2025-12-12 23:56:13', '2025-12-13 16:18:29'),
(135, NULL, 'SERRURE-PUSH', 'serrure-push-open', 'Serrure Push-to-Open', 21, 3500.00, 3200.00, 'Système d\'ouverture sans poignée par simple pression. Intégration discrète dans le mobilier.', '{\"Tension\": \"24 V\", \"Charge\": \"60 kg\", \"Temps fermeture\": \"3 sec\", \"Installation\": \"Dissimulïe\"}', NULL, '[]', 1, '2025-12-12 23:56:13', '2025-12-13 16:18:29'),
(136, 38, 'GLISSIERE-TELESCOP', 'glissiere-telescopique-500', 'Glissière Télescopique 500 mm', 21, 4200.00, 3850.00, 'Rails de qualité supérieure pour tiroirs professionnels. Mécanisme d\'extension complète 100%.', '{\"Course\": \"500 mm\", \"Charge\": \"80 kg\", \"Roulements\": \"Billes\", \"Fermeture\": \"Soft-close\"}', NULL, '[]', 1, '2025-12-12 23:56:13', '2025-12-13 16:18:29'),
(137, NULL, 'LOQUETEAUX-MAGNETI', 'loqueteau-magnetique-doux', 'Loqueteau Magnïtique Doux', 21, 680.00, 580.00, 'Fermeture magnïtique avec amortissement. Parfait pour portes vitrïes et faïades lïgïres.', '{\"Force\": \"5 kg\", \"Matiïre\": \"Alliage mïtallique\", \"Installation\": \"Facile\", \"Finition\": \"Chromï\"}', NULL, '[]', 1, '2025-12-12 23:56:13', '2025-12-13 16:16:30'),
(138, NULL, 'CLOUS-ACIER-65', 'clous-acier-zinc-65mm', 'Clous Acier Zinguï 65 mm', 21, 450.00, 380.00, 'Clous acier galvanisï pour assemblage robuste. Rïsistance ï la corrosion garantie.', '{\"Longueur\": \"65 mm\", \"Diamïtre\": \"3.75 mm\", \"Galvanisï\": \"Oui\", \"Emballage\": \"1 kg\"}', NULL, '[]', 1, '2025-12-12 23:56:13', '2025-12-13 16:16:30'),
(139, NULL, 'JOINT-SILICONE', 'joint-silicone-translucide', 'Joint Silicone Translucide', 22, 890.00, 750.00, 'Scellant silicone haute flexibilitï pour joints bois et menuiseries. Impermïable et durable.', '{\"Volume\": \"300 ml\", \"Temps prise\": \"24 h\", \"Couleur\": \"Translucide\", \"Flexibilitï\": \"Haute\"}', NULL, '[]', 1, '2025-12-12 23:56:13', '2025-12-13 16:16:30'),
(140, NULL, 'COLLE-WOOD-EXPRESS', 'colle-bois-rapide-500', 'Colle Bois Express 500 ml', 22, 2200.00, 1950.00, 'Colle polyurïthane pour assemblage bois professionnel. Prise rapide (15 min), rïsistance max.', '{\"Volume\": \"500 ml\", \"Prise\": \"15 minutes\", \"Temps travail\": \"8-10 min\", \"Rïsistance\": \"Maximum\"}', NULL, '[]', 1, '2025-12-12 23:56:13', '2025-12-13 16:16:30'),
(141, NULL, 'PATTE-FIXATION-ZINC', 'patte-fixation-epoxy', 'Patte de Fixation ïpoxy', 22, 1100.00, 950.00, 'ïquerre d\'assemblage en acier ïpoxy pour renforcement bois. Charge 50 kg par point.', '{\"Matiïre\": \"Acier ïpoxy\", \"Charge\": \"50 kg\", \"Dimensions\": \"35 x 35 mm\", \"Finition\": \"Noir mat\"}', NULL, '[]', 1, '2025-12-12 23:56:13', '2025-12-13 16:16:30'),
(142, NULL, 'TAQUET-REGLABLE', 'taquet-reglable-18', 'Taquet Rïglable 18 mm', 22, 380.00, 320.00, 'Taquet pour poteaux standards. Rïglable en hauteur pour un positionnement flexible.', '{\"Adapte ï\": \"Poteaux 18 mm\", \"Charge\": \"25 kg\", \"Matiïre\": \"Zinc\", \"Rïglage\": \"ï15 mm\"}', NULL, '[]', 1, '2025-12-12 23:56:13', '2025-12-13 16:16:30'),
(143, NULL, 'CACHE-TROU-ACACIA', 'cache-trou-acacia-20', 'Cache-Trou Acacia 20 mm', 22, 280.00, 240.00, 'Bouchon en bois massif pour cacher les trous de vis et chevilles. Finition naturelle.', '{\"Diamïtre\": \"20 mm\", \"Bois\": \"Acacia massif\", \"Finition\": \"Brut\", \"Boïte\": \"100 piïces\"}', NULL, '[]', 1, '2025-12-12 23:56:13', '2025-12-13 16:16:30'),
(144, NULL, 'VERROUS-S??CURIT??', 'verrou-securite-brass', 'Verrou de Sïcuritï Laiton', 22, 1650.00, 1480.00, 'Verrou de bonne qualitï pour armoires et portes sensibles. Fermeture ï clï 3 positions.', '{\"Matiïre\": \"Laiton\", \"Clïs\": \"Sïcurisïe\", \"Positions\": \"3\", \"Installation\": \"Externe\"}', NULL, '[]', 1, '2025-12-12 23:56:13', '2025-12-13 16:16:30'),
(145, NULL, 'SAPIN-RABOT-27', 'sapin-rabot-27x70', 'Sapin Rabotï 27 x 70 mm', 23, 2800.00, 2500.00, 'Bois de sapin massif rabote pour menuiserie, cadres et structures lïgïres. Sïchï et rabotï.', '{\"Section\": \"27 x 70 mm\", \"Longueur\": \"Au mïtre\", \"Essence\": \"Sapin du Nord\", \"Humiditï\": \"Rïgulïe\"}', NULL, '[]', 1, '2025-12-12 23:56:13', '2025-12-13 16:16:30'),
(146, NULL, 'CHENE-MASSIF-35', 'chene-massif-35x150', 'Chïne Massif 35 x 150 mm', 23, 8500.00, 7800.00, 'Chïne blanc massif de belle qualitï pour mobilier noble et agencements haut de gamme.', '{\"Section\": \"35 x 150 mm\", \"Essence\": \"Chïne blanc\", \"Sïchage\": \"Naturel\", \"Grade\": \"Sïlectionnï\"}', NULL, '[]', 1, '2025-12-12 23:56:13', '2025-12-13 16:16:30'),
(147, NULL, 'MERISIER-LAMES', 'merisier-lames-parquet', 'Lames Merisier Parquet', 23, 6200.00, 5700.00, 'Lames de merisier pour sols, revïtement ou agencement. Aspect chaud et naturel.', '{\"ïpaisseur\": \"18 mm\", \"Largeur\": \"90-140 mm\", \"Essence\": \"Merisier\", \"Finition\": \"Brut poncï\"}', NULL, '[]', 1, '2025-12-12 23:56:13', '2025-12-13 16:16:30'),
(148, NULL, 'TECK-EXOTIQUE-40', 'teck-exotique-40x80', 'Teck Exotique 40 x 80 mm', 23, 15500.00, 14200.00, 'Bois teck premium pour applications haut de gamme. Extrïmement durable et imputrescible.', '{\"Section\": \"40 x 80 mm\", \"Essence\": \"Teck Birmanie\", \"Durabilitï\": \"Classe 1\", \"Traitement\": \"Naturel\"}', NULL, '[]', 1, '2025-12-12 23:56:13', '2025-12-13 16:16:30'),
(149, NULL, 'EPICEA-RABOTE-20', 'epicea-rabote-20x40', 'ïpicïa Rabotï 20 x 40 mm', 23, 1500.00, 1350.00, 'ïpicïa blanc rabotï pour petits travaux de menuiserie, cadres et assemblage gïnïral.', '{\"Section\": \"20 x 40 mm\", \"Essence\": \"ïpicïa blanc\", \"Longueur\": \"Au mïtre\", \"Humiditï\": \"Rïgulïe\"}', NULL, '[]', 1, '2025-12-12 23:56:13', '2025-12-13 16:16:30'),
(150, NULL, 'VERNIS-POLYURETH', 'vernis-polyur??thane-brillant', 'Vernis Polyurïthane Brillant 1L', 24, 3800.00, 3400.00, 'Vernis haute rïsistance pour bois intïrieur et extïrieur. Finition brillante et durable.', '{\"Volume\": \"1 litre\", \"Brillance\": \"Brillant\", \"Temps sïchage\": \"6 heures\", \"Rendement\": \"8-10 mï/L\"}', NULL, '[]', 1, '2025-12-12 23:56:13', '2025-12-13 16:16:30'),
(151, NULL, 'LASURE-BOIS-INCOLORE', 'lasure-bois-incolore', 'Lasure Bois Incolore 2.5L', 24, 4500.00, 4100.00, 'Lasure incolore pour protection bois brut extïrieur. Laisse voir le grain naturel.', '{\"Volume\": \"2.5 litres\", \"Coloration\": \"Incolore\", \"Temps sïchage\": \"4 heures\", \"Durabilitï\": \"5-7 ans\"}', NULL, '[]', 1, '2025-12-12 23:56:13', '2025-12-13 16:16:30'),
(152, NULL, 'PEINTURE-EPOXY', 'peinture-epoxy-gris-acier', 'Peinture ïpoxy Gris Acier 1L', 24, 2600.00, 2350.00, 'Peinture ïpoxy haute performance pour mobilier et surface intense. Finition lisse mat.', '{\"Volume\": \"1 litre\", \"Couleur\": \"Gris acier\", \"Brillance\": \"Mat\", \"Rïsistance\": \"Extrïme\"}', NULL, '[]', 1, '2025-12-12 23:56:13', '2025-12-13 16:16:30'),
(153, NULL, 'CIRE-BOIS-NATURELLE', 'cire-bois-naturelle-500', 'Cire Bois Naturelle 500 ml', 24, 1900.00, 1650.00, 'Cire naturelle ï base d\'huiles essentielles pour entretien bois. Effet satinï protecteur.', '{\"Volume\": \"500 ml\", \"Base\": \"Naturelle 100%\", \"Aspect\": \"Satinï\", \"Odeur\": \"Naturelle\"}', NULL, '[]', 1, '2025-12-12 23:56:13', '2025-12-13 16:16:30'),
(154, NULL, 'DECAPANT-CHIMIQUE', 'decapant-chimique-pro-1l', 'Dïcapant Chimique Pro 1L', 24, 3200.00, 2900.00, 'Dïcapant puissant pour enlever peinture et vernis ancien. ïcologique et efficace.', '{\"Volume\": \"1 litre\", \"Type\": \"Chimique non-toxique\", \"Temps action\": \"30 minutes\", \"Rendement\": \"1-2 mï\"}', NULL, '[]', 1, '2025-12-12 23:56:13', '2025-12-13 16:16:30');

-- --------------------------------------------------------

--
-- Table structure for table `chambres`
--

CREATE TABLE `chambres` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `tarif_nuite` decimal(15,2) NOT NULL DEFAULT 0.00,
  `actif` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `chambres`
--

INSERT INTO `chambres` (`id`, `code`, `description`, `tarif_nuite`, `actif`) VALUES
(1, 'CH-101', 'Chambre standard lit double', 20000.00, 1),
(2, 'APP-201', 'Appartement meubl?? 2 pi??ces', 35000.00, 1);

-- --------------------------------------------------------

--
-- Table structure for table `clients`
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
-- Dumping data for table `clients`
--

INSERT INTO `clients` (`id`, `nom`, `type_client_id`, `telephone`, `email`, `adresse`, `source`, `statut`, `date_creation`) VALUES
(1, 'Client Showroom Test', 1, '+237650000001', 'client.showroom@test.local', 'Douala', 'Showroom', 'CLIENT', '2025-11-18 11:00:22'),
(2, 'Client Terrain Test', 2, '+237650000002', 'client.terrain@test.local', 'Bonab??ri', 'Terrain', 'PROSPECT', '2025-11-18 11:00:22'),
(3, 'Client Digital Test', 3, '+237650000003', 'client.digital@test.local', 'Yaound??', 'Facebook', 'CLIENT', '2025-11-18 11:00:22'),
(4, 'Client H??tel Test', 4, '+237650000004', 'client.hotel@test.local', 'Douala', 'R??servation directe', 'HOTE', '2025-11-18 11:00:22'),
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
(96, 'Touré Kouadio', 3, '0368743996', 'touré.kouadio@email.ci', 'Abidjan, Cocody', 'Showroom', 'CLIENT', '2025-12-13 17:33:50');

-- --------------------------------------------------------

--
-- Table structure for table `compta_comptes`
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
-- Dumping data for table `compta_comptes`
--

INSERT INTO `compta_comptes` (`id`, `numero_compte`, `libelle`, `classe`, `est_analytique`, `compte_parent_id`, `type_compte`, `nature`, `est_actif`, `observations`, `created_at`, `updated_at`) VALUES
(1, '1', 'Immobilisations', '1', 0, NULL, 'ACTIF', 'IMMOBILISATION', 1, NULL, '2025-12-10 13:32:46', '2025-12-10 13:32:46'),
(2, '2', 'Stocks', '2', 0, NULL, 'ACTIF', 'STOCK', 1, NULL, '2025-12-10 13:32:46', '2025-12-10 13:32:46'),
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
(15, '110', 'R??serves', '1', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 05:10:03', '2025-12-11 05:10:03'),
(16, '150', 'Provisions', '1', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 05:10:03', '2025-12-11 05:10:03'),
(17, '200', 'Amortissements', '1', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 05:10:03', '2025-12-11 05:10:03'),
(18, '301', 'Mati??res premi??res', '2', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 05:10:03', '2025-12-11 05:10:03'),
(19, '512', 'Banque', '5', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 05:10:03', '2025-12-11 05:14:24'),
(20, '571', 'Caisse si??ge social', '5', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 05:10:03', '2025-12-11 15:45:09'),
(21, '601', 'Achats de mati??res premi??res', '6', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 05:10:03', '2025-12-11 05:10:03'),
(22, '608', 'Frais de transport', '6', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 05:10:03', '2025-12-11 05:10:03'),
(23, '622', 'R??mun??rations du personnel', '6', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 05:10:03', '2025-12-11 05:10:03'),
(24, '631', 'Imp??ts et taxes', '6', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 05:10:03', '2025-12-11 05:10:03'),
(25, '701', 'Ventes de produits finis', '7', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 05:10:03', '2025-12-11 05:10:03'),
(26, '708', 'Revenus annexes', '7', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 05:10:03', '2025-12-11 05:10:03'),
(27, '10', 'Capital', '1', 0, NULL, 'PASSIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(28, '11', 'R??serves', '1', 0, NULL, 'PASSIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(29, '12', 'Report ?? nouveau', '1', 0, NULL, 'PASSIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(30, '13', 'R??sultat net de l\'exercice', '1', 0, NULL, 'PASSIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(31, '14', 'Subventions d\'investissement', '1', 0, NULL, 'PASSIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(32, '15', 'Provisions r??glement??es', '1', 0, NULL, 'PASSIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(33, '16', 'Emprunts et dettes assimil??es', '1', 0, NULL, 'PASSIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(34, '17', 'Dettes de location-acquisition', '1', 0, NULL, 'PASSIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(35, '18', 'Dettes li??es ?? des participations', '1', 0, NULL, 'PASSIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(36, '19', 'Provisions financi??res pour risques et charges', '1', 0, NULL, 'PASSIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(37, '20', 'Charges immobilis??es', '2', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(38, '21', 'Immobilisations incorporelles', '2', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(39, '22', 'Terrains', '2', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(40, '23', 'B??timents, installations techniques et agencements', '2', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(41, '24', 'Mat??riel, mobilier et actifs biologiques', '2', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(42, '25', 'Avances et acomptes vers??s sur immobilisations', '2', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(43, '26', 'Titres de participation', '2', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(44, '27', 'Autres immobilisations financi??res', '2', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(45, '28', 'Amortissements', '2', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(46, '29', 'Provisions pour d??pr??ciation des immobilisations', '2', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(47, '31', 'Marchandises', '3', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(48, '32', 'Mati??res premi??res et fournitures li??es', '3', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(49, '33', 'Autres approvisionnements', '3', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(50, '34', 'Produits en cours', '3', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(51, '35', 'Services en cours', '3', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(52, '36', 'Produits finis', '3', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(53, '37', 'Produits interm??diaires et r??siduels', '3', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(54, '38', 'Stocks en cours de route, en consignation ou en d??p??t', '3', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(55, '39', 'D??pr??ciations des stocks', '3', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(56, '40', 'Fournisseurs et comptes rattach??s', '4', 0, NULL, 'PASSIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(57, '41', 'Clients et comptes rattach??s', '4', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(58, '42', 'Personnel', '4', 0, NULL, 'PASSIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(59, '43', 'Organismes sociaux', '4', 0, NULL, 'PASSIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(60, '44', '??tat et collectivit??s publiques', '4', 0, NULL, 'MIXTE', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(61, '45', 'Organismes internationaux', '4', 0, NULL, 'MIXTE', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(62, '46', 'Associ??s et groupe', '4', 0, NULL, 'MIXTE', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(63, '47', 'D??biteurs et cr??diteurs divers', '4', 0, NULL, 'MIXTE', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(64, '48', 'Cr??ances et dettes hors activit??s ordinaires', '4', 0, NULL, 'MIXTE', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(65, '49', 'D??pr??ciations et risques provisionn??s', '4', 0, NULL, 'MIXTE', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(66, '50', 'Titres de placement', '5', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(67, '51', 'Valeurs ?? encaisser', '5', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(68, '52', 'Banques, ??tablissements financiers et assimil??s', '5', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(69, '53', '??tablissements financiers et assimil??s', '5', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(70, '54', 'Instruments de tr??sorerie', '5', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(71, '56', 'Cr??dits de tr??sorerie', '5', 0, NULL, 'PASSIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(72, '57', 'Caisse', '5', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(73, '58', 'R??gies d\'avances, accr??ditifs et virements internes', '5', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(74, '59', 'D??pr??ciations et risques provisionn??s', '5', 0, NULL, 'ACTIF', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(75, '60', 'Achats et variations de stocks', '6', 0, NULL, 'CHARGE', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(76, '61', 'Transports', '6', 0, NULL, 'CHARGE', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(77, '62', 'Services ext??rieurs A', '6', 0, NULL, 'CHARGE', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(78, '63', 'Autres services ext??rieurs B', '6', 0, NULL, 'CHARGE', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(79, '64', 'Imp??ts et taxes', '6', 0, NULL, 'CHARGE', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(80, '65', 'Autres charges', '6', 0, NULL, 'CHARGE', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(81, '66', 'Charges de personnel', '6', 0, NULL, 'CHARGE', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(82, '67', 'Frais financiers et charges assimil??es', '6', 0, NULL, 'CHARGE', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(83, '68', 'Dotations aux amortissements', '6', 0, NULL, 'CHARGE', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(84, '69', 'Dotations aux provisions', '6', 0, NULL, 'CHARGE', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(85, '70', 'Ventes', '7', 0, NULL, 'PRODUIT', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(86, '71', 'Subventions d\'exploitation', '7', 0, NULL, 'PRODUIT', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(87, '72', 'Production immobilis??e', '7', 0, NULL, 'PRODUIT', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(88, '73', 'Variations des stocks de biens et de services produits', '7', 0, NULL, 'PRODUIT', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(89, '75', 'Autres produits', '7', 0, NULL, 'PRODUIT', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(90, '77', 'Revenus financiers et produits assimil??s', '7', 0, NULL, 'PRODUIT', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(91, '78', 'Transferts de charges', '7', 0, NULL, 'PRODUIT', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(92, '79', 'Reprises de provisions', '7', 0, NULL, 'PRODUIT', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(93, '81', 'Valeurs comptables des cessions d\'immobilisations', '8', 0, NULL, 'CHARGE', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(94, '82', 'Produits des cessions d\'immobilisations', '8', 0, NULL, 'PRODUIT', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(95, '83', 'Charges hors activit??s ordinaires', '8', 0, NULL, 'CHARGE', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(96, '84', 'Produits hors activit??s ordinaires', '8', 0, NULL, 'PRODUIT', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(97, '85', 'Dotations hors activit??s ordinaires', '8', 0, NULL, 'CHARGE', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(98, '86', 'Reprises hors activit??s ordinaires', '8', 0, NULL, 'PRODUIT', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(99, '87', 'Participations des travailleurs', '8', 0, NULL, 'CHARGE', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(100, '88', 'Subventions d\'??quilibre', '8', 0, NULL, 'PRODUIT', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(101, '89', 'Imp??ts sur le r??sultat', '8', 0, NULL, 'CHARGE', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(102, '90', 'Engagements donn??s ou re??us', '9', 0, NULL, 'ANALYTIQUE', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(103, '91', 'Contrepartie des engagements', '9', 0, NULL, 'ANALYTIQUE', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(104, '92', 'Comptes r??fl??chis du bilan', '9', 0, NULL, 'ANALYTIQUE', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(105, '93', 'Comptes r??fl??chis de gestion', '9', 0, NULL, 'ANALYTIQUE', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(106, '94', 'Comptes de stocks', '9', 0, NULL, 'ANALYTIQUE', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(107, '95', 'Comptes de co??ts', '9', 0, NULL, 'ANALYTIQUE', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(108, '96', 'Comptes d\'??carts sur co??ts', '9', 0, NULL, 'ANALYTIQUE', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(109, '97', 'Comptes de r??sultats analytiques', '9', 0, NULL, 'ANALYTIQUE', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(110, '98', 'Comptes de liaisons internes', '9', 0, NULL, 'ANALYTIQUE', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56'),
(111, '99', 'Comptes de l\'activit??', '9', 0, NULL, 'ANALYTIQUE', 'AUTRE', 1, NULL, '2025-12-11 15:26:56', '2025-12-11 15:26:56');

-- --------------------------------------------------------

--
-- Table structure for table `compta_ecritures`
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
-- Dumping data for table `compta_ecritures`
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
(23, 12, 2, 'Stock initial valoris??', 9485000.00, 0.00, NULL, NULL, NULL, 1, NULL, '2025-12-11 05:08:27'),
(24, 12, 28, 'Stock initial valoris??', 0.00, 9485000.00, NULL, NULL, NULL, 2, NULL, '2025-12-11 05:08:27'),
(25, 13, 9, 'Vente mobilier d??coration', 3500000.00, 0.00, NULL, NULL, NULL, 1, NULL, '2025-12-11 05:10:03'),
(26, 13, 25, 'Vente mobilier d??coration', 0.00, 3500000.00, NULL, NULL, NULL, 2, NULL, '2025-12-11 05:10:03'),
(27, 14, 9, 'Vente accessoires', 2100000.00, 0.00, NULL, NULL, NULL, 1, NULL, '2025-12-11 05:10:03'),
(28, 14, 25, 'Vente accessoires', 0.00, 2100000.00, NULL, NULL, NULL, 2, NULL, '2025-12-11 05:10:03'),
(29, 15, 9, 'Vente panneaux', 1850000.00, 0.00, NULL, NULL, NULL, 1, NULL, '2025-12-11 05:10:04'),
(30, 15, 25, 'Vente panneaux', 0.00, 1850000.00, NULL, NULL, NULL, 2, NULL, '2025-12-11 05:10:04'),
(31, 16, 12, 'Achat mati??res premi??res', 1500000.00, 0.00, NULL, NULL, NULL, 1, NULL, '2025-12-11 05:10:04'),
(32, 16, 11, 'Achat mati??res premi??res', 0.00, 1500000.00, NULL, NULL, NULL, 2, NULL, '2025-12-11 05:10:04'),
(33, 17, 12, 'Achat accessoires', 900000.00, 0.00, NULL, NULL, NULL, 1, NULL, '2025-12-11 05:10:04'),
(34, 17, 11, 'Achat accessoires', 0.00, 900000.00, NULL, NULL, NULL, 2, NULL, '2025-12-11 05:10:04'),
(35, 18, 19, 'Paiement fournisseurs', 2509000.00, 0.00, NULL, NULL, NULL, 1, NULL, '2025-12-11 05:10:04'),
(36, 18, 11, 'Paiement fournisseurs', 0.00, 2509000.00, NULL, NULL, NULL, 2, NULL, '2025-12-11 05:10:04'),
(37, 19, 19, 'Encaissement clients', 3000000.00, 0.00, NULL, NULL, NULL, 1, NULL, '2025-12-11 05:10:04'),
(38, 19, 9, 'Encaissement clients', 0.00, 3000000.00, NULL, NULL, NULL, 2, NULL, '2025-12-11 05:10:04'),
(39, 20, 23, 'Salaires d??cembre', 450000.00, 0.00, NULL, NULL, NULL, 1, NULL, '2025-12-11 05:10:04'),
(40, 20, 20, 'Salaires d??cembre', 0.00, 450000.00, NULL, NULL, NULL, 2, NULL, '2025-12-11 05:10:04'),
(41, 21, 23, 'Frais de transport', 150000.00, 0.00, NULL, NULL, NULL, 1, NULL, '2025-12-11 05:10:04'),
(42, 21, 20, 'Frais de transport', 0.00, 150000.00, NULL, NULL, NULL, 2, NULL, '2025-12-11 05:10:04'),
(45, 23, 19, 'Encaissement partiel clients', 2000000.00, 0.00, NULL, NULL, NULL, 1, NULL, '2025-12-11 05:10:46'),
(46, 23, 9, 'Encaissement partiel clients', 0.00, 2000000.00, NULL, NULL, NULL, 2, NULL, '2025-12-11 05:10:46'),
(47, 24, 19, 'Encaissement clients', 1500000.00, 0.00, NULL, NULL, NULL, 1, NULL, '2025-12-11 05:10:46'),
(48, 24, 9, 'Encaissement clients', 0.00, 1500000.00, NULL, NULL, NULL, 2, NULL, '2025-12-11 05:10:46'),
(51, 22, 19, 'Capital social apport??', 10000000.00, 0.00, NULL, NULL, NULL, 1, NULL, '2025-12-11 05:11:30'),
(52, 22, 27, 'Capital social apport??', 0.00, 10000000.00, NULL, NULL, NULL, 2, NULL, '2025-12-11 05:11:30'),
(53, 25, 19, 'Solde initial banque', 2000000.00, 0.00, NULL, NULL, NULL, 1, NULL, '2025-12-11 05:11:30'),
(54, 25, 28, 'Solde initial banque', 0.00, 2000000.00, NULL, NULL, NULL, 2, NULL, '2025-12-11 05:11:30'),
(55, 26, 9, 'Client facture V-20251126-170324', 89437.50, 0.00, 2, NULL, NULL, 1, NULL, '2025-12-11 12:40:06'),
(56, 26, 10, 'Vente produits facture V-20251126-170324', 0.00, 89437.50, NULL, NULL, NULL, 2, NULL, '2025-12-11 12:40:06');

-- --------------------------------------------------------

--
-- Table structure for table `compta_exercices`
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
-- Dumping data for table `compta_exercices`
--

INSERT INTO `compta_exercices` (`id`, `annee`, `date_ouverture`, `date_cloture`, `est_clos`, `observations`, `created_at`, `updated_at`) VALUES
(1, 2024, '2024-01-01', NULL, 0, 'Exercice 2024', '2025-12-10 13:32:46', '2025-12-10 13:32:46'),
(2, 2025, '2025-01-01', NULL, 0, 'Exercice 2025', '2025-12-10 13:32:46', '2025-12-10 13:32:46');

-- --------------------------------------------------------

--
-- Table structure for table `compta_journaux`
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
-- Dumping data for table `compta_journaux`
--

INSERT INTO `compta_journaux` (`id`, `code`, `libelle`, `type`, `compte_contre_partie`, `observations`, `created_at`, `updated_at`) VALUES
(1, 'VE', 'Ventes', 'VENTE', NULL, NULL, '2025-12-10 13:32:46', '2025-12-10 13:32:46'),
(2, 'AC', 'Achats', 'ACHAT', NULL, NULL, '2025-12-10 13:32:46', '2025-12-10 13:32:46'),
(3, 'TR', 'Tresorerie', 'TRESORERIE', NULL, NULL, '2025-12-10 13:32:46', '2025-12-10 13:32:46'),
(4, 'OD', 'Operations Diverses', 'OPERATION_DIVERSE', NULL, NULL, '2025-12-10 13:32:46', '2025-12-10 13:32:46'),
(5, 'PA', 'Paie', 'PAIE', NULL, NULL, '2025-12-10 13:32:46', '2025-12-10 13:32:46');

-- --------------------------------------------------------

--
-- Table structure for table `compta_mapping_operations`
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
-- Dumping data for table `compta_mapping_operations`
--

INSERT INTO `compta_mapping_operations` (`id`, `source_type`, `code_operation`, `journal_id`, `compte_debit_id`, `compte_credit_id`, `description`, `actif`, `created_at`, `updated_at`) VALUES
(1, 'VENTE', 'VENTE_PRODUITS', 1, 9, 10, 'Ecritures vente standard', 1, '2025-12-10 15:31:08', '2025-12-10 15:31:08'),
(2, 'ACHAT', 'ACHAT_STOCK', 2, 12, 11, 'Ecritures achat standard', 1, '2025-12-10 15:46:34', '2025-12-10 15:46:34');

-- --------------------------------------------------------

--
-- Table structure for table `compta_operations_trace`
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
-- Dumping data for table `compta_operations_trace`
--

INSERT INTO `compta_operations_trace` (`id`, `source_type`, `source_id`, `piece_id`, `status`, `messages`, `executed_at`, `created_at`) VALUES
(1, 'VENTE', 1, 1, 'success', 'Mapping VENTE/VENTE_PRODUITS non configur??', NULL, '2025-12-10 15:24:17'),
(2, 'VENTE', 2, 2, 'success', 'Mapping VENTE/VENTE_PRODUITS non configur??', NULL, '2025-12-10 15:24:17'),
(3, 'VENTE', 3, 3, 'success', 'Mapping VENTE/VENTE_PRODUITS non configur??', NULL, '2025-12-10 15:24:17'),
(4, 'VENTE', 4, 4, 'success', 'Mapping VENTE/VENTE_PRODUITS non configur??', NULL, '2025-12-10 15:24:17'),
(5, 'VENTE', 16, 5, 'success', 'Mapping VENTE/VENTE_PRODUITS non configur??', NULL, '2025-12-10 15:24:17'),
(6, 'VENTE', 17, 6, 'success', 'Mapping VENTE/VENTE_PRODUITS non configur??', NULL, '2025-12-10 15:24:17'),
(7, 'VENTE', 19, 7, 'success', 'Mapping VENTE/VENTE_PRODUITS non configur??', NULL, '2025-12-10 15:24:17'),
(8, 'VENTE', 20, 26, 'success', 'Mapping VENTE/VENTE_PRODUITS non configur??', NULL, '2025-12-10 15:24:17'),
(17, 'ACHAT', 1, 9, 'success', NULL, NULL, '2025-12-10 15:49:48'),
(18, 'ACHAT', 2, 10, 'success', NULL, NULL, '2025-12-10 15:49:48'),
(19, 'ACHAT', 3, 11, 'success', NULL, NULL, '2025-12-10 15:49:48');

-- --------------------------------------------------------

--
-- Table structure for table `compta_pieces`
--

CREATE TABLE `compta_pieces` (
  `id` int(10) UNSIGNED NOT NULL,
  `exercice_id` int(10) UNSIGNED NOT NULL,
  `journal_id` int(10) UNSIGNED NOT NULL,
  `numero_piece` varchar(50) NOT NULL,
  `date_piece` date NOT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` int(10) UNSIGNED DEFAULT NULL,
  `tiers_client_id` int(10) UNSIGNED DEFAULT NULL,
  `tiers_fournisseur_id` int(10) UNSIGNED DEFAULT NULL,
  `observations` text DEFAULT NULL,
  `est_validee` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `compta_pieces`
--

INSERT INTO `compta_pieces` (`id`, `exercice_id`, `journal_id`, `numero_piece`, `date_piece`, `reference_type`, `reference_id`, `tiers_client_id`, `tiers_fournisseur_id`, `observations`, `est_validee`, `created_at`, `updated_at`) VALUES
(1, 2, 1, 'XX-2025-00001', '2025-11-18', 'VENTE', 1, 3, NULL, 'Facture vente n?? V-20251118-114131', 1, '2025-12-10 15:32:08', '2025-12-11 04:58:14'),
(2, 2, 1, 'VE-2025-00002', '2025-11-18', 'VENTE', 2, 2, NULL, 'Facture vente n?? V-20251118-122137', 1, '2025-12-10 15:32:08', '2025-12-11 04:58:14'),
(3, 2, 1, 'VE-2025-00003', '2025-11-18', 'VENTE', 3, 5, NULL, 'Facture vente n?? V-20251118-135949', 1, '2025-12-10 15:32:08', '2025-12-11 04:58:15'),
(4, 2, 1, 'VE-2025-00004', '2025-11-18', 'VENTE', 4, 5, NULL, 'Facture vente n?? V-20251118-151825', 1, '2025-12-10 15:32:08', '2025-12-11 04:58:15'),
(5, 2, 1, 'VE-2025-00005', '2025-11-20', 'VENTE', 16, 2, NULL, 'Facture vente n?? V-20251120-122303', 1, '2025-12-10 15:32:08', '2025-12-11 04:58:15'),
(6, 2, 1, 'VE-2025-00006', '2025-11-21', 'VENTE', 17, 6, NULL, 'Facture vente n?? V-20251121-112325', 1, '2025-12-10 15:32:08', '2025-12-11 04:58:16'),
(7, 2, 1, 'VE-2025-00007', '2025-11-26', 'VENTE', 19, 6, NULL, 'Facture vente n?? V-20251126-154749', 1, '2025-12-10 15:32:08', '2025-12-11 04:58:16'),
(8, 2, 1, 'VE-2025-00008', '2025-11-26', 'VENTE', 20, 2, NULL, 'Facture vente n?? V-20251126-170324', 1, '2025-12-10 15:32:08', '2025-12-11 04:58:16'),
(9, 2, 2, 'XX-2025-00001', '2025-11-21', 'ACHAT', 1, NULL, NULL, 'Facture achat n?? ACH-20251121-162559', 1, '2025-12-10 15:49:48', '2025-12-11 04:58:16'),
(10, 2, 2, 'AC-2025-00002', '2025-11-26', 'ACHAT', 2, NULL, NULL, 'Facture achat n?? AC-20251126-170544', 1, '2025-12-10 15:49:48', '2025-12-11 04:58:16'),
(11, 2, 2, 'AC-2025-00003', '2025-12-02', 'ACHAT', 3, NULL, NULL, 'Facture achat n?? AC-20251202-154014', 1, '2025-12-10 15:49:48', '2025-12-11 04:58:16'),
(12, 2, 4, 'INV-2025-00001', '2025-12-11', NULL, NULL, NULL, NULL, 'Stock initial valoris??', 1, '2025-12-11 05:07:51', '2025-12-11 05:08:59'),
(13, 2, 1, 'VE-2025-00009', '2025-12-05', NULL, NULL, NULL, NULL, 'Vente mobilier d??coration', 1, '2025-12-11 05:10:03', '2025-12-11 05:10:03'),
(14, 2, 1, 'VE-2025-00010', '2025-12-06', NULL, NULL, NULL, NULL, 'Vente accessoires', 1, '2025-12-11 05:10:03', '2025-12-11 05:10:03'),
(15, 2, 1, 'VE-2025-00011', '2025-12-07', NULL, NULL, NULL, NULL, 'Vente panneaux', 1, '2025-12-11 05:10:04', '2025-12-11 05:10:04'),
(16, 2, 2, 'AC-2025-00004', '2025-12-03', NULL, NULL, NULL, NULL, 'Achat mati??res premi??res', 1, '2025-12-11 05:10:04', '2025-12-11 05:10:04'),
(17, 2, 2, 'AC-2025-00005', '2025-12-04', NULL, NULL, NULL, NULL, 'Achat accessoires', 1, '2025-12-11 05:10:04', '2025-12-11 05:10:04'),
(18, 2, 3, 'TR-2025-00001', '2025-12-05', NULL, NULL, NULL, NULL, 'Paiement fournisseurs', 1, '2025-12-11 05:10:04', '2025-12-11 05:10:04'),
(19, 2, 3, 'TR-2025-00002', '2025-12-08', NULL, NULL, NULL, NULL, 'Encaissement clients', 1, '2025-12-11 05:10:04', '2025-12-11 05:10:04'),
(20, 2, 4, 'CH-2025-00001', '2025-12-06', NULL, NULL, NULL, NULL, 'Salaires d??cembre', 1, '2025-12-11 05:10:04', '2025-12-11 05:10:04'),
(21, 2, 4, 'CH-2025-00002', '2025-12-08', NULL, NULL, NULL, NULL, 'Frais de transport', 1, '2025-12-11 05:10:04', '2025-12-11 05:10:04'),
(22, 2, 4, 'CAP-2025-00001', '2025-01-01', NULL, NULL, NULL, NULL, 'Capital social initial', 1, '2025-12-11 05:10:46', '2025-12-11 05:10:46'),
(23, 2, 3, 'TR-2025-00003', '2025-12-09', NULL, NULL, NULL, NULL, 'Encaissement partiel clients', 1, '2025-12-11 05:10:46', '2025-12-11 05:10:46'),
(24, 2, 3, 'TR-2025-00004', '2025-12-10', NULL, NULL, NULL, NULL, 'Encaissement clients', 1, '2025-12-11 05:10:46', '2025-12-11 05:10:46'),
(25, 2, 3, 'BNQ-2025-00001', '2025-01-01', NULL, NULL, NULL, NULL, 'Solde initial banque', 1, '2025-12-11 05:10:46', '2025-12-11 05:10:46'),
(26, 2, 1, 'VE-2025-00012', '2025-11-26', 'VENTE', 20, 2, NULL, 'Facture vente n?? V-20251126-170324', 0, '2025-12-11 12:40:06', '2025-12-11 12:40:06');

-- --------------------------------------------------------

--
-- Table structure for table `connexions_utilisateur`
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
-- Dumping data for table `connexions_utilisateur`
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
-- Table structure for table `conversions_pipeline`
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
-- Table structure for table `devis`
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
-- Dumping data for table `devis`
--

INSERT INTO `devis` (`id`, `numero`, `date_devis`, `client_id`, `canal_vente_id`, `statut`, `est_converti`, `date_relance`, `utilisateur_id`, `montant_total_ht`, `montant_total_ttc`, `remise_global`, `conditions`, `commentaires`) VALUES
(51, 'DEV-20251027-001', '2025-10-27', 93, 1, 'EN_ATTENTE', 0, NULL, 1, 303300.00, 303300.00, 0.00, NULL, NULL),
(52, 'DEV-20251104-002', '2025-11-04', 94, 1, 'ACCEPTE', 0, NULL, 1, 3043100.00, 3043100.00, 0.00, NULL, NULL),
(53, 'DEV-20251112-003', '2025-11-12', 70, 1, 'EN_ATTENTE', 0, NULL, 1, 760800.00, 760800.00, 0.00, NULL, NULL),
(54, 'DEV-20251021-004', '2025-10-21', 93, 1, 'ACCEPTE', 0, NULL, 1, 5276600.00, 5276600.00, 0.00, NULL, NULL),
(55, 'DEV-20251116-005', '2025-11-16', 72, 1, 'ACCEPTE', 0, NULL, 1, 766500.00, 766500.00, 0.00, NULL, NULL),
(56, 'DEV-20251129-006', '2025-11-29', 86, 1, 'ACCEPTE', 0, NULL, 1, 1447000.00, 1447000.00, 0.00, NULL, NULL),
(57, 'DEV-20251121-007', '2025-11-21', 91, 1, 'EN_ATTENTE', 0, NULL, 1, 40000.00, 40000.00, 0.00, NULL, NULL),
(58, 'DEV-20251118-008', '2025-11-18', 89, 1, 'ACCEPTE', 0, NULL, 1, 2945000.00, 2945000.00, 0.00, NULL, NULL),
(59, 'DEV-20251116-009', '2025-11-16', 71, 1, 'ACCEPTE', 0, NULL, 1, 8130000.00, 8130000.00, 0.00, NULL, NULL),
(60, 'DEV-20251109-010', '2025-11-09', 85, 1, 'EN_ATTENTE', 0, NULL, 1, 3699100.00, 3699100.00, 0.00, NULL, NULL),
(61, 'DEV-20251121-011', '2025-11-21', 81, 1, 'EN_ATTENTE', 0, NULL, 1, 14400.00, 14400.00, 0.00, NULL, NULL),
(62, 'DEV-20251109-012', '2025-11-09', 71, 1, 'ACCEPTE', 0, NULL, 1, 3065800.00, 3065800.00, 0.00, NULL, NULL),
(63, 'DEV-20251202-013', '2025-12-02', 84, 1, 'EN_ATTENTE', 0, NULL, 1, 1725800.00, 1725800.00, 0.00, NULL, NULL),
(64, 'DEV-20251123-014', '2025-11-23', 89, 1, 'ACCEPTE', 0, NULL, 1, 2159000.00, 2159000.00, 0.00, NULL, NULL),
(65, 'DEV-20251115-015', '2025-11-15', 96, 1, 'ACCEPTE', 0, NULL, 1, 102950.00, 102950.00, 0.00, NULL, NULL),
(66, 'DEV-20251115-016', '2025-11-15', 88, 1, 'EN_ATTENTE', 0, NULL, 1, 674500.00, 674500.00, 0.00, NULL, NULL),
(67, 'DEV-20251114-017', '2025-11-14', 95, 1, 'ACCEPTE', 0, NULL, 1, 512500.00, 512500.00, 0.00, NULL, NULL),
(68, 'DEV-20251029-018', '2025-10-29', 76, 1, 'EN_ATTENTE', 0, NULL, 1, 4307000.00, 4307000.00, 0.00, NULL, NULL),
(69, 'DEV-20251104-019', '2025-11-04', 91, 1, 'ACCEPTE', 0, NULL, 1, 1151500.00, 1151500.00, 0.00, NULL, NULL),
(70, 'DEV-20251020-020', '2025-10-20', 95, 1, 'ACCEPTE', 0, NULL, 1, 5891900.00, 5891900.00, 0.00, NULL, NULL),
(71, 'DEV-20251203-021', '2025-12-03', 81, 1, 'ACCEPTE', 0, NULL, 1, 51800.00, 51800.00, 0.00, NULL, NULL),
(72, 'DEV-20251101-022', '2025-11-01', 70, 1, 'EN_ATTENTE', 0, NULL, 1, 3912000.00, 3912000.00, 0.00, NULL, NULL),
(73, 'DEV-20251014-023', '2025-10-14', 82, 1, 'ACCEPTE', 0, NULL, 1, 1147000.00, 1147000.00, 0.00, NULL, NULL),
(74, 'DEV-20251207-024', '2025-12-07', 67, 1, 'ACCEPTE', 0, NULL, 1, 2744000.00, 2744000.00, 0.00, NULL, NULL),
(75, 'DEV-20251123-025', '2025-11-23', 78, 1, 'ACCEPTE', 0, NULL, 1, 2095000.00, 2095000.00, 0.00, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `devis_lignes`
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
-- Dumping data for table `devis_lignes`
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
-- Table structure for table `familles_produits`
--

CREATE TABLE `familles_produits` (
  `id` int(10) UNSIGNED NOT NULL,
  `nom` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `familles_produits`
--

INSERT INTO `familles_produits` (`id`, `nom`) VALUES
(1, 'Meubles & am??nagements int??rieurs'),
(2, 'Accessoires & quincaillerie de menuiserie'),
(3, 'Machines & ??quipements de menuiserie'),
(4, 'Panneaux & mat??riaux d??????agencement'),
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
-- Table structure for table `formations`
--

CREATE TABLE `formations` (
  `id` int(10) UNSIGNED NOT NULL,
  `nom` varchar(150) NOT NULL,
  `description` text NOT NULL,
  `tarif_total` decimal(15,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `formations`
--

INSERT INTO `formations` (`id`, `nom`, `description`, `tarif_total`) VALUES
(1, 'Menuiserie moderne', 'Formation pratique en menuiserie et agencement', 150000.00),
(2, 'Agencement int??rieur', 'Techniques d???agencement et d??coration int??rieure', 180000.00);

-- --------------------------------------------------------

--
-- Table structure for table `fournisseurs`
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
-- Dumping data for table `fournisseurs`
--

INSERT INTO `fournisseurs` (`id`, `nom`, `contact`, `telephone`, `email`, `adresse`) VALUES
(1, 'Fournisseur G??n??ral KMS', 'Service commercial', '+237600000001', 'fournisseur@kms.local', 'Douala'),
(2, 'Import Mat??riaux Pro', 'Responsable achat', '+237600000002', 'imports@kms.local', 'Douala - Zone industrielle');

-- --------------------------------------------------------

--
-- Table structure for table `inscriptions_formation`
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
-- Dumping data for table `inscriptions_formation`
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
-- Triggers `inscriptions_formation`
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
                -- Annuler l'ancienne écriture
                DELETE FROM caisse_journal 
                WHERE source_type = 'inscription_formation' AND source_id = NEW.id;
                
                -- Créer nouvelle écriture si montant > 0
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
-- Table structure for table `journal_caisse`
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
  `annule_par_id` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `journal_caisse`
--

INSERT INTO `journal_caisse` (`id`, `date_operation`, `numero_piece`, `nature_operation`, `client_id`, `fournisseur_id`, `sens`, `montant`, `mode_paiement_id`, `vente_id`, `reservation_id`, `inscription_formation_id`, `responsable_encaissement_id`, `observations`, `est_annule`, `date_annulation`, `annule_par_id`) VALUES
(1, '2025-11-18', 'RES-1', 'Encaissement r??servation h??tel', NULL, NULL, 'RECETTE', 35000.00, 4, NULL, 1, NULL, 1, '', 0, NULL, NULL),
(2, '2025-11-18', '011', 'r??glement fournissuer', NULL, NULL, 'RECETTE', 10000.00, 1, NULL, NULL, NULL, 1, NULL, 0, NULL, NULL),
(3, '2025-11-19', 'INSCR-1', 'Encaissement inscription formation', NULL, NULL, 'RECETTE', 50000.00, 3, NULL, NULL, 1, 1, NULL, 0, NULL, NULL),
(4, '2025-11-20', '5', 'r??glement fournissuer', NULL, NULL, 'RECETTE', 10000.00, 4, NULL, NULL, NULL, 1, NULL, 0, NULL, NULL),
(5, '2025-11-20', '', 'sorepco', NULL, NULL, 'RECETTE', 100000.00, 4, NULL, NULL, NULL, 1, 'recouvrement', 1, '2025-11-20 18:53:38', 1),
(6, '2025-11-20', '', 'versement mupeci', NULL, NULL, 'RECETTE', 1000000.00, 4, NULL, NULL, NULL, 1, 'recouvrement', 0, NULL, NULL),
(7, '2025-11-21', '', 'recouvrement sorepco', NULL, NULL, 'RECETTE', 150000.00, 4, NULL, NULL, NULL, 1, NULL, 0, NULL, NULL),
(8, '2025-12-13', 'a14', '', NULL, NULL, 'DEPENSE', 5000.00, 1, NULL, NULL, NULL, 1, NULL, 1, '2025-12-13 17:35:32', 1);

-- --------------------------------------------------------

--
-- Table structure for table `kpis_quotidiens`
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
-- Table structure for table `leads_digital`
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
-- Table structure for table `modes_paiement`
--

CREATE TABLE `modes_paiement` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` varchar(50) NOT NULL,
  `libelle` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `modes_paiement`
--

INSERT INTO `modes_paiement` (`id`, `code`, `libelle`) VALUES
(1, 'CASH', 'Esp??ces'),
(2, 'VIREMENT', 'Virement bancaire'),
(3, 'MOBILE_MONEY', 'Mobile Money'),
(4, 'CHEQUE', 'Ch??que');

-- --------------------------------------------------------

--
-- Table structure for table `mouvements_stock_backup_20251209_161710`
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
-- Dumping data for table `mouvements_stock_backup_20251209_161710`
--

INSERT INTO `mouvements_stock_backup_20251209_161710` (`id`, `date_mouvement`, `type_mouvement`, `produit_id`, `quantite`, `source_module`, `source_id`, `utilisateur_id`, `commentaire`, `date_creation`) VALUES
(1, '2025-11-21', 'ENTREE', 1, 22, 'ACHAT', 55222, NULL, NULL, '2025-11-21 12:50:59'),
(3, '2025-11-26', 'SORTIE', 3, 3, 'VENTE', 20, NULL, 'Sortie suite ?? la vente V-20251126-170324', '2025-11-26 17:04:15'),
(4, '2025-11-26', 'ENTREE', 2, 25, 'ACHAT', 2, NULL, 'Entr??e suite ?? l???achat AC-20251126-170544', '2025-11-26 17:05:44'),
(5, '2025-12-02', 'ENTREE', 3, 25, 'ACHAT', 3, NULL, 'Entr??e suite ?? l???achat AC-20251202-154014', '2025-12-02 15:40:14');

-- --------------------------------------------------------

--
-- Table structure for table `objectifs_commerciaux`
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
-- Table structure for table `ordres_preparation`
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

-- --------------------------------------------------------

--
-- Table structure for table `ordres_preparation_lignes`
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
-- Table structure for table `parametres_securite`
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
-- Dumping data for table `parametres_securite`
--

INSERT INTO `parametres_securite` (`id`, `cle`, `valeur`, `type`, `description`, `modifie_par`, `date_modification`) VALUES
(1, '2fa_obligatoire_admin', '1', 'BOOL', 'Forcer 2FA pour tous les administrateurs', NULL, '2025-12-13 12:40:26'),
(2, '2fa_obligatoire_tous', '0', 'BOOL', 'Forcer 2FA pour tous les utilisateurs', NULL, '2025-12-13 12:40:26'),
(3, 'session_timeout_minutes', '120', 'INT', 'Dur??e de session inactive en minutes', NULL, '2025-12-13 12:40:26'),
(4, 'max_sessions_simultanees', '3', 'INT', 'Nombre max de sessions simultan??es par utilisateur', NULL, '2025-12-13 12:40:26'),
(5, 'login_max_attempts', '5', 'INT', 'Tentatives de connexion max avant blocage', NULL, '2025-12-13 12:40:26'),
(6, 'login_block_duration_minutes', '60', 'INT', 'Dur??e de blocage apr??s ??checs r??p??t??s', NULL, '2025-12-13 12:40:26'),
(7, 'password_min_length', '8', 'INT', 'Longueur minimale du mot de passe', NULL, '2025-12-13 12:40:26'),
(8, 'password_require_special', '1', 'BOOL', 'Exiger caract??res sp??ciaux dans mot de passe', NULL, '2025-12-13 12:40:26'),
(9, 'password_require_number', '1', 'BOOL', 'Exiger chiffres dans mot de passe', NULL, '2025-12-13 12:40:26'),
(10, 'password_require_uppercase', '1', 'BOOL', 'Exiger majuscules dans mot de passe', NULL, '2025-12-13 12:40:26'),
(11, 'password_expiration_days', '90', 'INT', 'Expiration mot de passe (0 = jamais)', NULL, '2025-12-13 12:40:26'),
(12, 'audit_retention_days', '365', 'INT', 'Dur??e conservation logs audit', NULL, '2025-12-13 12:40:26'),
(13, 'redis_enabled', '1', 'BOOL', 'Activer le cache Redis', NULL, '2025-12-13 12:40:26'),
(14, 'rate_limit_enabled', '1', 'BOOL', 'Activer le rate limiting', NULL, '2025-12-13 12:40:26'),
(15, 'sms_provider', 'mock', 'STRING', 'Provider SMS (twilio, vonage, mock)', NULL, '2025-12-13 12:52:56'),
(16, 'sms_max_tentatives_jour', '10', 'STRING', 'Nombre max de codes SMS par jour et par utilisateur', NULL, '2025-12-13 12:52:56'),
(17, 'sms_delai_renvoi_secondes', '60', 'STRING', 'D??lai minimum entre 2 envois de code (en secondes)', NULL, '2025-12-13 12:52:56');

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `code`, `description`) VALUES
(1, 'PRODUITS_LIRE', 'Consulter le catalogue produits et les stocks'),
(2, 'PRODUITS_CREER', 'Cr??er de nouveaux produits'),
(3, 'PRODUITS_MODIFIER', 'Modifier les produits existants'),
(4, 'PRODUITS_SUPPRIMER', 'Supprimer des produits'),
(5, 'CLIENTS_LIRE', 'Consulter les clients / prospects'),
(6, 'CLIENTS_CREER', 'Cr??er ou modifier des clients'),
(7, 'DEVIS_LIRE', 'Lister et consulter les devis'),
(8, 'DEVIS_CREER', 'Cr??er des devis'),
(9, 'DEVIS_MODIFIER', 'Modifier le statut ou le contenu des devis'),
(10, 'VENTES_LIRE', 'Consulter les ventes et bons de livraison'),
(11, 'VENTES_CREER', 'Cr??er des ventes'),
(12, 'VENTES_VALIDER', 'Valider des ventes / livraisons'),
(13, 'CAISSE_LIRE', 'Consulter le journal de caisse'),
(14, 'CAISSE_ECRIRE', 'Enregistrer des op??rations de caisse'),
(15, 'PROMOTIONS_GERER', 'Cr??er et g??rer les promotions'),
(16, 'HOTEL_GERER', 'G??rer les r??servations h??tel et upsell'),
(17, 'FORMATION_GERER', 'G??rer les formations et inscriptions'),
(18, 'REPORTING_LIRE', 'Acc??der aux tableaux de bord et reporting'),
(19, 'SATISFACTION_GERER', 'G??rer les enqu??tes de satisfaction client'),
(20, 'ACHATS_GERER', 'G??rer les achats et approvisionnements'),
(21, 'COMPTABILITE_LIRE', 'Consulter le module comptabilit??'),
(22, 'COMPTABILITE_ECRIRE', 'Enregistrer des ??critures comptables'),
(23, 'UTILISATEURS_GERER', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `produits`
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
-- Dumping data for table `produits`
--

INSERT INTO `produits` (`id`, `code_produit`, `famille_id`, `sous_categorie_id`, `designation`, `caracteristiques`, `description`, `fournisseur_id`, `localisation`, `prix_achat`, `prix_vente`, `stock_actuel`, `seuil_alerte`, `image_path`, `actif`, `date_creation`, `date_modification`) VALUES
(1, 'MEU-CH-001', 1, 1, 'Lit 2 places avec chevets', 'Dimensions 160x200', 'Lit moderne pour chambre parentale', 1, 'Showroom Douala', 120000.00, 180000.00, 21, 2, '/assets/img/produits/MEU-CH-001.png', 1, '2025-11-18 11:00:22', '2025-12-02 15:58:23'),
(2, 'MEU-SAL-001', 1, 2, 'Salon 5 places', 'Structure bois, mousse haute densit??', 'Salon complet 3+1+1', 1, 'Showroom Douala', 200000.00, 280000.00, 27, 1, NULL, 1, '2025-11-18 11:00:22', NULL),
(17, 'TEST-PRD-001', 1, NULL, 'Produit test automatis????', NULL, NULL, NULL, NULL, 0.00, 1500.00, 3, 0, NULL, 0, '2025-12-10 13:09:46', NULL),
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
(59, 'PAN-CTBX18', 70, NULL, 'Panneau CTBX 18mm 1220x2440', NULL, NULL, NULL, NULL, 22000.00, 29500.00, 0, 10, NULL, 1, '2025-12-13 17:33:50', NULL),
(60, 'PAN-MDF16', 70, NULL, 'Panneau MDF 16mm 1220x2440', NULL, NULL, NULL, NULL, 9500.00, 13200.00, 0, 10, NULL, 1, '2025-12-13 17:33:50', NULL),
(61, 'PAN-MULTI21', 70, NULL, 'Multiplex 21mm 1220x2440', NULL, NULL, NULL, NULL, 18000.00, 24500.00, 0, 10, NULL, 1, '2025-12-13 17:33:50', NULL),
(62, 'MAC-SCIE210', 71, NULL, 'Scie a ruban 210W professionnelle', NULL, NULL, NULL, NULL, 145000.00, 185000.00, 0, 10, NULL, 1, '2025-12-13 17:33:50', NULL),
(63, 'MAC-RABOTEUSE', 71, NULL, 'Raboteuse 305mm', NULL, NULL, NULL, NULL, 260000.00, 320000.00, 0, 10, NULL, 1, '2025-12-13 17:33:50', NULL),
(64, 'MAC-TOUPIE', 71, NULL, 'Toupie 2200W', NULL, NULL, NULL, NULL, 350000.00, 425000.00, 0, 10, NULL, 1, '2025-12-13 17:33:50', NULL),
(65, 'QUI-CHARN90', 68, NULL, 'Charniere inox 90deg (paire)', NULL, NULL, NULL, NULL, 600.00, 950.00, 0, 10, NULL, 1, '2025-12-13 17:33:50', NULL),
(66, 'QUI-GLISS50', 68, NULL, 'Glissiere telescopique 500mm', NULL, NULL, NULL, NULL, 3000.00, 4200.00, 0, 10, NULL, 1, '2025-12-13 17:33:50', NULL),
(67, 'QUI-POIGN160', 68, NULL, 'Poignee aluminium 160mm', NULL, NULL, NULL, NULL, 750.00, 1200.00, 0, 10, NULL, 1, '2025-12-13 17:33:50', NULL),
(68, 'ELM-FOUR', 72, NULL, 'Four encastrable inox 60cm', NULL, NULL, NULL, NULL, 145000.00, 185000.00, 0, 10, NULL, 1, '2025-12-13 17:33:50', NULL),
(69, 'ELM-PLAQUE', 72, NULL, 'Plaque vitroceramique 4 feux', NULL, NULL, NULL, NULL, 72000.00, 95000.00, 0, 10, NULL, 1, '2025-12-13 17:33:50', NULL),
(70, 'ACC-VIS430', 73, NULL, 'Vis noire 4x30mm (boite 100)', NULL, NULL, NULL, NULL, 1200.00, 2000.00, 0, 10, NULL, 1, '2025-12-13 17:33:50', NULL),
(71, 'ACC-COLLE', 73, NULL, 'Colle bois pro 750ml', NULL, NULL, NULL, NULL, 5500.00, 8500.00, 0, 10, NULL, 1, '2025-12-13 17:33:50', NULL),
(72, 'ACC-VERNIS', 73, NULL, 'Vernis brillant 1L', NULL, NULL, NULL, NULL, 8000.00, 12500.00, 0, 10, NULL, 1, '2025-12-13 17:33:50', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `promotions`
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
-- Table structure for table `promotion_produit`
--

CREATE TABLE `promotion_produit` (
  `promotion_id` int(10) UNSIGNED NOT NULL,
  `produit_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prospections_terrain`
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
-- Dumping data for table `prospections_terrain`
--

INSERT INTO `prospections_terrain` (`id`, `date_prospection`, `heure_prospection`, `prospect_nom`, `secteur`, `latitude`, `longitude`, `adresse_gps`, `besoin_identifie`, `action_menee`, `resultat`, `prochaine_etape`, `client_id`, `commercial_id`) VALUES
(1, '2025-12-11', '12:26:30', 'MR Yves', 'Pindo', 4.05880337, 9.78497912, 'Pindo, Douala III, Communaut?? urbaine de Douala, Wouri, R??gion du Littoral, Cameroun', 'Deligneuse', 'Prospection et remise de la fiche produit', 'Int??ress?? - ?? recontacter', 'relancer', NULL, 1),
(2, '2025-12-12', '15:17:00', 'Zoboo', 'Ndogmbe', 4.04000000, 9.75000000, 'Ndogmbe, Douala III, Communaut?? urbaine de Douala, Wouri, Littoral, Cameroon', 'machines de m??nuiserie', 'prospection et prise de rendez-vous au centre commercial', '?? rappeler plus tard', 'Relancer dans une semaine', NULL, 1),
(3, '2025-12-12', '15:24:38', 'Kossi', 'Non renseign??', 4.04000000, 9.75000000, NULL, 'efezfe', 'fezfzeefd', 'Devis demand??', 'zerfzfze', NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `prospects_formation`
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
-- Dumping data for table `prospects_formation`
--

INSERT INTO `prospects_formation` (`id`, `date_prospect`, `nom_prospect`, `contact`, `source`, `statut_actuel`, `client_id`, `utilisateur_id`) VALUES
(1, '2025-11-01', 'Anicet Mballa', '655585502', 'facebook', 'En cours', NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `relances_devis`
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
-- Table structure for table `rendezvous_terrain`
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
-- Table structure for table `reservations_hotel`
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
-- Dumping data for table `reservations_hotel`
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
-- Triggers `reservations_hotel`
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
                    CONCAT('Réservation hôtel #', NEW.id)
                );
            END IF;
        END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `after_reservation_hotel_update` AFTER UPDATE ON `reservations_hotel` FOR EACH ROW BEGIN
            IF NEW.montant_total != OLD.montant_total THEN
                -- Annuler l'ancienne écriture
                DELETE FROM caisse_journal 
                WHERE source_type = 'reservation_hotel' AND source_id = NEW.id;
                
                -- Créer nouvelle écriture si montant > 0
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
                        CONCAT('Réservation hôtel #', NEW.id)
                    );
                END IF;
            END IF;
        END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `retours_litiges`
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

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` varchar(50) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `code`, `nom`, `description`) VALUES
(1, 'ADMIN', 'Administrateur', 'Acc??s complet ?? toute l??????application'),
(2, 'SHOWROOM', 'Commercial Showroom', 'Gestion des visiteurs, devis et ventes en showroom'),
(3, 'TERRAIN', 'Commercial Terrain', 'Prospection terrain, devis et ventes terrain'),
(4, 'MAGASINIER', 'Magasinier', 'Gestion des stocks, livraisons, ruptures'),
(5, 'CAISSIER', 'Caissier', 'Journal de caisse et encaissements'),
(6, 'DIRECTION', 'Direction', 'Consultation des reportings et indicateurs globaux');

-- --------------------------------------------------------

--
-- Table structure for table `role_permission`
--

CREATE TABLE `role_permission` (
  `role_id` int(10) UNSIGNED NOT NULL,
  `permission_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role_permission`
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
-- Table structure for table `ruptures_signalees`
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
-- Table structure for table `ruptures_stock`
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
-- Table structure for table `satisfaction_clients`
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
-- Dumping data for table `satisfaction_clients`
--

INSERT INTO `satisfaction_clients` (`id`, `date_satisfaction`, `client_id`, `nom_client`, `service_utilise`, `note`, `commentaire`, `utilisateur_id`) VALUES
(1, '2025-11-19', NULL, 'apprenant', 'FORMATION', 4, '', 1),
(2, '2025-11-20', 4, 'Client H??tel Test', 'FORMATION', 2, 'grincheux et de??u', 1);

-- --------------------------------------------------------

--
-- Table structure for table `sessions_actives`
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
-- Dumping data for table `sessions_actives`
--

INSERT INTO `sessions_actives` (`id`, `utilisateur_id`, `ip_address`, `user_agent`, `device_fingerprint`, `pays`, `ville`, `date_creation`, `date_derniere_activite`, `date_expiration`, `actif`) VALUES
('4i7r6spbtrkholn08ncn2bnu96', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', NULL, NULL, NULL, '2025-12-13 15:24:50', '2025-12-13 15:24:50', '2025-12-13 17:24:50', 1),
('nr7ld1kfh8rh8i9hr40f2db8te', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL, NULL, NULL, '2025-12-13 13:03:12', '2025-12-13 13:03:12', '2025-12-13 15:03:12', 1),
('u78v44an2rnvh1vjml3r74u03m', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', NULL, NULL, NULL, '2025-12-13 13:26:22', '2025-12-13 13:26:22', '2025-12-13 15:26:22', 1);

-- --------------------------------------------------------

--
-- Table structure for table `sms_2fa_codes`
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
-- Table structure for table `sms_tracking`
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
-- Table structure for table `sous_categories_produits`
--

CREATE TABLE `sous_categories_produits` (
  `id` int(10) UNSIGNED NOT NULL,
  `famille_id` int(10) UNSIGNED NOT NULL,
  `nom` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sous_categories_produits`
--

INSERT INTO `sous_categories_produits` (`id`, `famille_id`, `nom`) VALUES
(1, 1, 'Chambres ?? coucher'),
(2, 1, 'Salons'),
(3, 2, 'Quincaillerie standard'),
(4, 3, 'Machines de d??coupe'),
(5, 4, 'Panneaux m??lamin??s');

-- --------------------------------------------------------

--
-- Table structure for table `stocks_mouvements`
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
-- Dumping data for table `stocks_mouvements`
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
(233, 65, '2025-12-13 17:33:50', '', -7, 'bon_livraison', 61, 'Livraison BL-20251108-017', 1);

-- --------------------------------------------------------

--
-- Table structure for table `tentatives_connexion`
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
-- Dumping data for table `tentatives_connexion`
--

INSERT INTO `tentatives_connexion` (`id`, `login_attempt`, `utilisateur_id`, `ip_address`, `user_agent`, `methode_2fa`, `succes`, `raison_echec`, `pays`, `ville`, `date_tentative`) VALUES
(1, 'admin', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL, 0, 'Mot de passe incorrect', NULL, NULL, '2025-12-13 13:03:02'),
(2, 'admin', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', NULL, 0, 'Mot de passe incorrect', NULL, NULL, '2025-12-13 13:18:28'),
(3, 'admin', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 'EMAIL', 1, NULL, NULL, NULL, '2025-12-13 13:26:22'),
(4, 'admin', 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', 'EMAIL', 1, NULL, NULL, NULL, '2025-12-13 15:24:50');

-- --------------------------------------------------------

--
-- Table structure for table `types_client`
--

CREATE TABLE `types_client` (
  `id` int(10) UNSIGNED NOT NULL,
  `code` varchar(50) NOT NULL,
  `libelle` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `types_client`
--

INSERT INTO `types_client` (`id`, `code`, `libelle`) VALUES
(1, 'SHOWROOM', 'Client / prospect showroom'),
(2, 'TERRAIN', 'Client / prospect terrain'),
(3, 'DIGITAL', 'Client issu du digital (r??seaux sociaux, site, CRM)'),
(4, 'HOTEL', 'Client h??bergement / h??tel'),
(5, 'FORMATION', 'Apprenant / client formation');

-- --------------------------------------------------------

--
-- Table structure for table `upsell_hotel`
--

CREATE TABLE `upsell_hotel` (
  `id` int(10) UNSIGNED NOT NULL,
  `reservation_id` int(10) UNSIGNED NOT NULL,
  `service_additionnel` varchar(150) NOT NULL,
  `montant` decimal(15,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `utilisateurs`
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
-- Dumping data for table `utilisateurs`
--

INSERT INTO `utilisateurs` (`id`, `login`, `mot_de_passe_hash`, `nom_complet`, `email`, `telephone`, `actif`, `date_creation`, `date_derniere_connexion`, `date_changement_mdp`, `mdp_expire`, `force_changement_mdp`, `compte_verrouille`, `raison_verrouillage`, `date_verrouillage`, `sessions_simultanees_actuelles`) VALUES
(1, 'admin', '$2b$10$j6YYUX.QLOxOoBn9eB4rJu8/ye4/NOEXPvRjcYhUY4mBiaZZFUrTi', 'Administrateur KMS', 'admin@kms.local', NULL, 1, '2025-11-18 10:59:28', '2025-12-13 15:24:50', NULL, 0, 0, 0, NULL, NULL, 0),
(2, 'admin2', '$2y$10$G6sGiMHX75v9PYTAqIZCPObMQV.3InGlXpNGyrKWKK/gM8iln0Tfu', 'Administrateur Syst??me', 'admin2@kms.local', NULL, 1, '2025-12-11 11:56:20', NULL, NULL, 0, 0, 0, NULL, NULL, 0),
(3, 'showroom1', '$2y$10$G6sGiMHX75v9PYTAqIZCPObMQV.3InGlXpNGyrKWKK/gM8iln0Tfu', 'Marie Kouadio', 'marie.kouadio@kms.local', NULL, 1, '2025-12-11 11:56:20', NULL, NULL, 0, 0, 0, NULL, NULL, 0),
(4, 'showroom2', '$2y$10$G6sGiMHX75v9PYTAqIZCPObMQV.3InGlXpNGyrKWKK/gM8iln0Tfu', 'Yao Kouassi', 'yao.kouassi@kms.local', NULL, 1, '2025-12-11 11:56:20', NULL, NULL, 0, 0, 0, NULL, NULL, 0),
(5, 'terrain1', '$2y$10$G6sGiMHX75v9PYTAqIZCPObMQV.3InGlXpNGyrKWKK/gM8iln0Tfu', 'Konan Yao', 'konan.yao@kms.local', NULL, 1, '2025-12-11 11:56:20', NULL, NULL, 0, 0, 0, NULL, NULL, 0),
(6, 'terrain2', '$2y$10$G6sGiMHX75v9PYTAqIZCPObMQV.3InGlXpNGyrKWKK/gM8iln0Tfu', 'Aya N\'Guessan', 'aya.nguessan@kms.local', NULL, 1, '2025-12-11 11:56:20', NULL, NULL, 0, 0, 0, NULL, NULL, 0),
(7, 'magasin1', '$2y$10$G6sGiMHX75v9PYTAqIZCPObMQV.3InGlXpNGyrKWKK/gM8iln0Tfu', 'Ibrahim Traor??', 'ibrahim.traore@kms.local', NULL, 1, '2025-12-11 11:56:20', NULL, NULL, 0, 0, 0, NULL, NULL, 0),
(8, 'magasin2', '$2y$10$G6sGiMHX75v9PYTAqIZCPObMQV.3InGlXpNGyrKWKK/gM8iln0Tfu', 'Moussa Diallo', 'moussa.diallo@kms.local', NULL, 1, '2025-12-11 11:56:20', NULL, NULL, 0, 0, 0, NULL, NULL, 0),
(9, 'caisse1', '$2y$10$G6sGiMHX75v9PYTAqIZCPObMQV.3InGlXpNGyrKWKK/gM8iln0Tfu', 'Aminata Kon??', 'aminata.kone@kms.local', NULL, 1, '2025-12-11 11:56:20', NULL, NULL, 0, 0, 0, NULL, NULL, 0),
(10, 'caisse2', '$2y$10$G6sGiMHX75v9PYTAqIZCPObMQV.3InGlXpNGyrKWKK/gM8iln0Tfu', 'Fatou Camara', 'fatou.camara@kms.local', NULL, 1, '2025-12-11 11:56:20', NULL, NULL, 0, 0, 0, NULL, NULL, 0),
(11, 'direction1', '$2y$10$G6sGiMHX75v9PYTAqIZCPObMQV.3InGlXpNGyrKWKK/gM8iln0Tfu', 'Directeur G??n??ral', 'dg@kms.local', NULL, 1, '2025-12-11 11:56:20', NULL, NULL, 0, 0, 0, NULL, NULL, 0),
(12, 'direction2', '$2y$10$G6sGiMHX75v9PYTAqIZCPObMQV.3InGlXpNGyrKWKK/gM8iln0Tfu', 'Directeur Adjoint', 'da@kms.local', NULL, 1, '2025-12-11 11:56:20', NULL, NULL, 0, 0, 0, NULL, NULL, 0),
(13, 'Tatiana', '$2y$10$PI9HMfk.ET49yrr31htsKOHMhnZSNaITlwbcbcL5lJawUzejgOm7a', 'Naoussi Tatiana', 'naoussitatiana@gmail.com', '695657613', 1, '2025-12-11 12:07:02', NULL, NULL, 0, 0, 0, NULL, NULL, 0),
(14, 'Gislaine', '$2y$10$WwVYPLCm6FFKjE/CY4QLh.sN1gc3y2J3KsgHoLGh9u33r/b72mHKW', 'Gislaine', NULL, NULL, 1, '2025-12-11 12:09:27', NULL, NULL, 0, 0, 0, NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `utilisateurs_2fa`
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
-- Dumping data for table `utilisateurs_2fa`
--

INSERT INTO `utilisateurs_2fa` (`id`, `utilisateur_id`, `secret`, `actif`, `date_activation`, `date_desactivation`, `methode`, `telephone_backup`, `email_backup`, `date_creation`, `methode_2fa`, `telephone`) VALUES
(1, 1, '', 1, '2025-12-13 13:17:56', NULL, 'EMAIL', NULL, 'peghiembouoromial@gmail.com', '2025-12-13 13:17:56', 'totp', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `utilisateurs_2fa_recovery`
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
-- Table structure for table `utilisateur_role`
--

CREATE TABLE `utilisateur_role` (
  `utilisateur_id` int(10) UNSIGNED NOT NULL,
  `role_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `utilisateur_role`
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
-- Table structure for table `ventes`
--

CREATE TABLE `ventes` (
  `id` int(10) UNSIGNED NOT NULL,
  `numero` varchar(50) NOT NULL,
  `date_vente` date NOT NULL,
  `client_id` int(10) UNSIGNED NOT NULL,
  `canal_vente_id` int(10) UNSIGNED NOT NULL,
  `devis_id` int(10) UNSIGNED DEFAULT NULL,
  `statut` enum('DEVIS','DEVIS_ACCEPTE','EN_ATTENTE_LIVRAISON','EN_PREPARATION','PRET_LIVRAISON','PARTIELLEMENT_LIVREE','LIVREE','FACTUREE','PAYEE','ANNULEE') NOT NULL DEFAULT 'EN_ATTENTE_LIVRAISON',
  `montant_total_ht` decimal(15,2) NOT NULL DEFAULT 0.00,
  `montant_total_ttc` decimal(15,2) NOT NULL DEFAULT 0.00,
  `utilisateur_id` int(10) UNSIGNED NOT NULL,
  `commentaires` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ventes`
--

INSERT INTO `ventes` (`id`, `numero`, `date_vente`, `client_id`, `canal_vente_id`, `devis_id`, `statut`, `montant_total_ht`, `montant_total_ttc`, `utilisateur_id`, `commentaires`) VALUES
(57, 'VTE-20251109-001', '2025-11-09', 94, 1, 52, 'EN_ATTENTE_LIVRAISON', 3043100.00, 3043100.00, 1, NULL),
(58, 'VTE-20251023-002', '2025-10-23', 93, 1, 54, 'LIVREE', 5276600.00, 5276600.00, 1, NULL),
(59, 'VTE-20251117-003', '2025-11-17', 72, 1, 55, 'EN_ATTENTE_LIVRAISON', 766500.00, 766500.00, 1, NULL),
(60, 'VTE-20251202-004', '2025-12-02', 86, 1, 56, 'EN_ATTENTE_LIVRAISON', 1447000.00, 1447000.00, 1, NULL),
(61, 'VTE-20251125-005', '2025-11-25', 89, 1, 58, 'EN_ATTENTE_LIVRAISON', 2945000.00, 2945000.00, 1, NULL),
(62, 'VTE-20251123-006', '2025-11-23', 71, 1, 59, 'EN_ATTENTE_LIVRAISON', 8130000.00, 8130000.00, 1, NULL),
(63, 'VTE-20251111-007', '2025-11-11', 71, 1, 62, 'LIVREE', 3065800.00, 3065800.00, 1, NULL),
(64, 'VTE-20251130-008', '2025-11-30', 89, 1, 64, 'EN_ATTENTE_LIVRAISON', 2159000.00, 2159000.00, 1, NULL),
(65, 'VTE-20251121-009', '2025-11-21', 96, 1, 65, 'EN_ATTENTE_LIVRAISON', 102950.00, 102950.00, 1, NULL),
(66, 'VTE-20251117-010', '2025-11-17', 95, 1, 67, 'LIVREE', 512500.00, 512500.00, 1, NULL),
(67, 'VTE-20251109-011', '2025-11-09', 91, 1, 69, 'LIVREE', 1151500.00, 1151500.00, 1, NULL),
(68, 'VTE-20251027-012', '2025-10-27', 95, 1, 70, 'EN_ATTENTE_LIVRAISON', 5891900.00, 5891900.00, 1, NULL),
(69, 'VTE-20251204-013', '2025-12-04', 81, 1, 71, 'EN_ATTENTE_LIVRAISON', 51800.00, 51800.00, 1, NULL),
(70, 'VTE-20251015-014', '2025-10-15', 82, 1, 73, 'LIVREE', 1147000.00, 1147000.00, 1, NULL),
(71, 'VTE-20251214-015', '2025-12-14', 67, 1, 74, 'LIVREE', 2744000.00, 2744000.00, 1, NULL),
(72, 'VTE-20251127-016', '2025-11-27', 78, 1, 75, 'LIVREE', 2095000.00, 2095000.00, 1, NULL),
(73, 'VTE-20251211-017', '2025-12-11', 94, 1, NULL, 'LIVREE', 94400.00, 94400.00, 1, NULL),
(74, 'VTE-20251021-018', '2025-10-21', 87, 1, NULL, 'EN_ATTENTE_LIVRAISON', 5730000.00, 5730000.00, 1, NULL),
(75, 'VTE-20251027-019', '2025-10-27', 83, 1, NULL, 'LIVREE', 59000.00, 59000.00, 1, NULL),
(76, 'VTE-20251129-020', '2025-11-29', 93, 1, NULL, 'LIVREE', 91500.00, 91500.00, 1, NULL),
(77, 'VTE-20251212-021', '2025-12-12', 79, 1, NULL, 'EN_ATTENTE_LIVRAISON', 2387850.00, 2387850.00, 1, NULL),
(78, 'VTE-20251015-022', '2025-10-15', 68, 1, NULL, 'EN_ATTENTE_LIVRAISON', 43400.00, 43400.00, 1, NULL),
(79, 'VTE-20251015-023', '2025-10-15', 92, 1, NULL, 'LIVREE', 4355600.00, 4355600.00, 1, NULL),
(80, 'VTE-20251024-024', '2025-10-24', 75, 1, NULL, 'EN_ATTENTE_LIVRAISON', 61000.00, 61000.00, 1, NULL),
(81, 'VTE-20251208-025', '2025-12-08', 94, 1, NULL, 'LIVREE', 950000.00, 950000.00, 1, NULL),
(82, 'VTE-20251208-026', '2025-12-08', 67, 1, NULL, 'LIVREE', 1280000.00, 1280000.00, 1, NULL),
(83, 'VTE-20251113-027', '2025-11-13', 86, 1, NULL, 'LIVREE', 4452000.00, 4452000.00, 1, NULL),
(84, 'VTE-20251213-028', '2025-12-13', 69, 1, NULL, 'LIVREE', 4253500.00, 4253500.00, 1, NULL),
(85, 'VTE-20251120-029', '2025-11-20', 74, 1, NULL, 'EN_ATTENTE_LIVRAISON', 77300.00, 77300.00, 1, NULL),
(86, 'VTE-20251205-030', '2025-12-05', 77, 1, NULL, 'LIVREE', 987500.00, 987500.00, 1, NULL),
(87, 'VTE-20251107-031', '2025-11-07', 83, 1, NULL, 'LIVREE', 1379650.00, 1379650.00, 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `ventes_lignes`
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
-- Dumping data for table `ventes_lignes`
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
(267, 87, 65, 7, 950.00, 0.00, 6650.00);

-- --------------------------------------------------------

--
-- Table structure for table `visiteurs_hotel`
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
-- Table structure for table `visiteurs_showroom`
--

CREATE TABLE `visiteurs_showroom` (
  `id` int(10) UNSIGNED NOT NULL,
  `date_visite` date NOT NULL,
  `client_nom` varchar(150) NOT NULL,
  `contact` varchar(100) NOT NULL,
  `produit_interet` text DEFAULT NULL,
  `orientation` varchar(100) DEFAULT NULL,
  `client_id` int(10) UNSIGNED DEFAULT NULL,
  `utilisateur_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_pipeline_commercial`
-- (See below for the actual view)
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
-- Stand-in structure for view `v_ventes_livraison_encaissement`
-- (See below for the actual view)
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
-- Structure for view `v_pipeline_commercial`
--
DROP TABLE IF EXISTS `v_pipeline_commercial`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_pipeline_commercial`  AS SELECT 'SHOWROOM' AS `canal`, `vs`.`id` AS `source_id`, `vs`.`client_nom` AS `prospect_nom`, `vs`.`date_visite` AS `date_entree`, 0 AS `converti_en_devis`, 0 AS `converti_en_vente`, NULL AS `statut_pipeline` FROM `visiteurs_showroom` AS `vs`union all select 'TERRAIN' AS `canal`,`pt`.`id` AS `source_id`,`pt`.`prospect_nom` AS `prospect_nom`,`pt`.`date_prospection` AS `date_entree`,0 AS `converti_en_devis`,0 AS `converti_en_vente`,NULL AS `statut_pipeline` from `prospections_terrain` `pt` union all select 'DIGITAL' AS `canal`,`ld`.`id` AS `source_id`,`ld`.`nom_prospect` AS `prospect_nom`,`ld`.`date_lead` AS `date_entree`,`ld`.`statut` in ('DEVIS_ENVOYE','CONVERTI') AS `converti_en_devis`,`ld`.`statut` = 'CONVERTI' AS `converti_en_vente`,`ld`.`statut` AS `statut_pipeline` from `leads_digital` `ld`  ;

-- --------------------------------------------------------

--
-- Structure for view `v_ventes_livraison_encaissement`
--
DROP TABLE IF EXISTS `v_ventes_livraison_encaissement`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_ventes_livraison_encaissement`  AS SELECT `v`.`id` AS `id`, `v`.`numero` AS `numero`, `v`.`date_vente` AS `date_vente`, `v`.`montant_total_ttc` AS `montant_total_ttc`, `v`.`statut` AS `statut_vente`, CASE WHEN exists(select 1 from `bons_livraison` `bl` where `bl`.`vente_id` = `v`.`id` AND `bl`.`signe_client` = 1 limit 1) THEN 'LIVRE' ELSE 'NON_LIVRE' END AS `statut_livraison`, coalesce((select sum(`jc`.`montant`) from `journal_caisse` `jc` where `jc`.`vente_id` = `v`.`id`),0) AS `montant_encaisse`, `v`.`montant_total_ttc`- coalesce((select sum(`jc`.`montant`) from `journal_caisse` `jc` where `jc`.`vente_id` = `v`.`id`),0) AS `solde_du` FROM `ventes` AS `v` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `achats`
--
ALTER TABLE `achats`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_achats_utilisateur` (`utilisateur_id`);

--
-- Indexes for table `achats_lignes`
--
ALTER TABLE `achats_lignes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_achats_lignes_achat` (`achat_id`),
  ADD KEY `fk_achats_lignes_produit` (`produit_id`);

--
-- Indexes for table `audit_log`
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
-- Indexes for table `blocages_ip`
--
ALTER TABLE `blocages_ip`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_ip` (`ip_address`),
  ADD KEY `idx_blocage_actif` (`actif`),
  ADD KEY `idx_blocage_expiration` (`date_expiration`);

--
-- Indexes for table `bons_livraison`
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
-- Indexes for table `bons_livraison_lignes`
--
ALTER TABLE `bons_livraison_lignes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_bl_lignes_bl` (`bon_livraison_id`),
  ADD KEY `fk_bl_lignes_produit` (`produit_id`);

--
-- Indexes for table `caisse_journal`
--
ALTER TABLE `caisse_journal`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `canaux_vente`
--
ALTER TABLE `canaux_vente`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `catalogue_categories`
--
ALTER TABLE `catalogue_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `catalogue_produits`
--
ALTER TABLE `catalogue_produits`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `fk_catalogue_categorie` (`categorie_id`);

--
-- Indexes for table `chambres`
--
ALTER TABLE `chambres`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_clients_type` (`type_client_id`),
  ADD KEY `idx_clients_nom` (`nom`);

--
-- Indexes for table `compta_comptes`
--
ALTER TABLE `compta_comptes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero_compte` (`numero_compte`),
  ADD KEY `compte_parent_id` (`compte_parent_id`),
  ADD KEY `idx_numero` (`numero_compte`),
  ADD KEY `idx_classe` (`classe`),
  ADD KEY `idx_nature` (`nature`);

--
-- Indexes for table `compta_ecritures`
--
ALTER TABLE `compta_ecritures`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tiers_client_id` (`tiers_client_id`),
  ADD KEY `tiers_fournisseur_id` (`tiers_fournisseur_id`),
  ADD KEY `idx_compte` (`compte_id`),
  ADD KEY `idx_piece` (`piece_id`),
  ADD KEY `idx_debit_credit` (`debit`,`credit`);

--
-- Indexes for table `compta_exercices`
--
ALTER TABLE `compta_exercices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `annee` (`annee`);

--
-- Indexes for table `compta_journaux`
--
ALTER TABLE `compta_journaux`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `compte_contre_partie` (`compte_contre_partie`);

--
-- Indexes for table `compta_mapping_operations`
--
ALTER TABLE `compta_mapping_operations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_mapping` (`source_type`,`code_operation`),
  ADD KEY `journal_id` (`journal_id`),
  ADD KEY `compte_debit_id` (`compte_debit_id`),
  ADD KEY `compte_credit_id` (`compte_credit_id`),
  ADD KEY `idx_source` (`source_type`,`code_operation`);

--
-- Indexes for table `compta_operations_trace`
--
ALTER TABLE `compta_operations_trace`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_trace` (`source_type`,`source_id`),
  ADD KEY `piece_id` (`piece_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `compta_pieces`
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
-- Indexes for table `connexions_utilisateur`
--
ALTER TABLE `connexions_utilisateur`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_connexions_utilisateur_utilisateur` (`utilisateur_id`),
  ADD KEY `idx_connexions_utilisateur_date` (`date_connexion`);

--
-- Indexes for table `conversions_pipeline`
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
-- Indexes for table `devis`
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
-- Indexes for table `devis_lignes`
--
ALTER TABLE `devis_lignes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_devis_lignes_devis` (`devis_id`),
  ADD KEY `fk_devis_lignes_produit` (`produit_id`);

--
-- Indexes for table `familles_produits`
--
ALTER TABLE `familles_produits`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `formations`
--
ALTER TABLE `formations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `fournisseurs`
--
ALTER TABLE `fournisseurs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inscriptions_formation`
--
ALTER TABLE `inscriptions_formation`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_inscription_client` (`client_id`),
  ADD KEY `fk_inscription_formation` (`formation_id`),
  ADD KEY `idx_inscription_date` (`date_inscription`);

--
-- Indexes for table `journal_caisse`
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
-- Indexes for table `kpis_quotidiens`
--
ALTER TABLE `kpis_quotidiens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_kpis_unique` (`date`,`canal`),
  ADD KEY `idx_kpis_date` (`date`);

--
-- Indexes for table `leads_digital`
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
-- Indexes for table `modes_paiement`
--
ALTER TABLE `modes_paiement`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `mouvements_stock_backup_20251209_161710`
--
ALTER TABLE `mouvements_stock_backup_20251209_161710`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_mouvements_stock_produit` (`produit_id`),
  ADD KEY `idx_mouvements_stock_utilisateur` (`utilisateur_id`);

--
-- Indexes for table `objectifs_commerciaux`
--
ALTER TABLE `objectifs_commerciaux`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_objectifs_unique` (`annee`,`mois`,`canal`),
  ADD KEY `idx_objectifs_periode` (`annee`,`mois`);

--
-- Indexes for table `ordres_preparation`
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
-- Indexes for table `ordres_preparation_lignes`
--
ALTER TABLE `ordres_preparation_lignes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ordres_lignes_ordre` (`ordre_preparation_id`),
  ADD KEY `fk_ordres_lignes_produit` (`produit_id`);

--
-- Indexes for table `parametres_securite`
--
ALTER TABLE `parametres_securite`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cle` (`cle`),
  ADD KEY `modifie_par` (`modifie_par`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `produits`
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
-- Indexes for table `promotions`
--
ALTER TABLE `promotions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `promotion_produit`
--
ALTER TABLE `promotion_produit`
  ADD PRIMARY KEY (`promotion_id`,`produit_id`),
  ADD KEY `fk_promo_produit_produit` (`produit_id`);

--
-- Indexes for table `prospections_terrain`
--
ALTER TABLE `prospections_terrain`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_prospections_client` (`client_id`),
  ADD KEY `fk_prospections_commercial` (`commercial_id`),
  ADD KEY `idx_prospections_date` (`date_prospection`);

--
-- Indexes for table `prospects_formation`
--
ALTER TABLE `prospects_formation`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_prospect_formation_client` (`client_id`),
  ADD KEY `fk_prospect_formation_utilisateur` (`utilisateur_id`),
  ADD KEY `idx_prospect_formation_date` (`date_prospect`);

--
-- Indexes for table `relances_devis`
--
ALTER TABLE `relances_devis`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_relances_devis` (`devis_id`),
  ADD KEY `idx_relances_date` (`date_relance`),
  ADD KEY `fk_relances_utilisateur` (`utilisateur_id`);

--
-- Indexes for table `rendezvous_terrain`
--
ALTER TABLE `rendezvous_terrain`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_rdv_client` (`client_id`),
  ADD KEY `fk_rdv_commercial` (`commercial_id`),
  ADD KEY `idx_rdv_date` (`date_rdv`);

--
-- Indexes for table `reservations_hotel`
--
ALTER TABLE `reservations_hotel`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_reservation_client` (`client_id`),
  ADD KEY `fk_reservation_chambre` (`chambre_id`),
  ADD KEY `fk_reservation_mode_paiement` (`mode_paiement_id`),
  ADD KEY `fk_reservation_concierge` (`concierge_id`),
  ADD KEY `idx_reservation_dates` (`date_debut`,`date_fin`);

--
-- Indexes for table `retours_litiges`
--
ALTER TABLE `retours_litiges`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_litiges_client` (`client_id`),
  ADD KEY `fk_litiges_produit` (`produit_id`),
  ADD KEY `fk_litiges_vente` (`vente_id`),
  ADD KEY `fk_litiges_responsable` (`responsable_suivi_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `role_permission`
--
ALTER TABLE `role_permission`
  ADD PRIMARY KEY (`role_id`,`permission_id`),
  ADD KEY `fk_role_permission_permission` (`permission_id`);

--
-- Indexes for table `ruptures_signalees`
--
ALTER TABLE `ruptures_signalees`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ruptures_date` (`date_signalement`),
  ADD KEY `idx_ruptures_produit` (`produit_id`),
  ADD KEY `idx_ruptures_statut` (`statut_traitement`),
  ADD KEY `fk_ruptures_sig_magasinier` (`magasinier_id`);

--
-- Indexes for table `ruptures_stock`
--
ALTER TABLE `ruptures_stock`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ruptures_produit` (`produit_id`),
  ADD KEY `fk_ruptures_magasinier` (`magasinier_id`),
  ADD KEY `idx_ruptures_date` (`date_rapport`);

--
-- Indexes for table `satisfaction_clients`
--
ALTER TABLE `satisfaction_clients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_satisfaction_client` (`client_id`),
  ADD KEY `fk_satisfaction_utilisateur` (`utilisateur_id`),
  ADD KEY `idx_satisfaction_date` (`date_satisfaction`);

--
-- Indexes for table `sessions_actives`
--
ALTER TABLE `sessions_actives`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_session_user` (`utilisateur_id`),
  ADD KEY `idx_session_expiration` (`date_expiration`),
  ADD KEY `idx_session_actif` (`actif`);

--
-- Indexes for table `sms_2fa_codes`
--
ALTER TABLE `sms_2fa_codes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_utilisateur` (`utilisateur_id`),
  ADD KEY `idx_expiration` (`expire_a`),
  ADD KEY `idx_utilise` (`utilise`);

--
-- Indexes for table `sms_tracking`
--
ALTER TABLE `sms_tracking`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_utilisateur` (`utilisateur_id`),
  ADD KEY `idx_telephone` (`telephone`),
  ADD KEY `idx_date` (`envoye_a`);

--
-- Indexes for table `sous_categories_produits`
--
ALTER TABLE `sous_categories_produits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_sous_categories_famille` (`famille_id`);

--
-- Indexes for table `stocks_mouvements`
--
ALTER TABLE `stocks_mouvements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_mouvements_produit` (`produit_id`),
  ADD KEY `fk_mouvements_utilisateur` (`utilisateur_id`),
  ADD KEY `idx_mouvements_date` (`date_mouvement`);

--
-- Indexes for table `tentatives_connexion`
--
ALTER TABLE `tentatives_connexion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tentative_date` (`date_tentative`),
  ADD KEY `idx_tentative_ip` (`ip_address`),
  ADD KEY `idx_tentative_succes` (`succes`),
  ADD KEY `idx_tentative_user` (`utilisateur_id`);

--
-- Indexes for table `types_client`
--
ALTER TABLE `types_client`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `upsell_hotel`
--
ALTER TABLE `upsell_hotel`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_upsell_reservation` (`reservation_id`);

--
-- Indexes for table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `login` (`login`),
  ADD KEY `idx_compte_verrouille` (`compte_verrouille`),
  ADD KEY `idx_mdp_expire` (`mdp_expire`);

--
-- Indexes for table `utilisateurs_2fa`
--
ALTER TABLE `utilisateurs_2fa`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_2fa` (`utilisateur_id`),
  ADD KEY `idx_2fa_actif` (`actif`),
  ADD KEY `idx_methode` (`methode_2fa`);

--
-- Indexes for table `utilisateurs_2fa_recovery`
--
ALTER TABLE `utilisateurs_2fa_recovery`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_recovery_user` (`utilisateur_id`),
  ADD KEY `idx_recovery_utilise` (`utilise`);

--
-- Indexes for table `utilisateur_role`
--
ALTER TABLE `utilisateur_role`
  ADD PRIMARY KEY (`utilisateur_id`,`role_id`),
  ADD KEY `fk_utilisateur_role_role` (`role_id`);

--
-- Indexes for table `ventes`
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
-- Indexes for table `ventes_lignes`
--
ALTER TABLE `ventes_lignes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ventes_lignes_vente` (`vente_id`),
  ADD KEY `fk_ventes_lignes_produit` (`produit_id`);

--
-- Indexes for table `visiteurs_hotel`
--
ALTER TABLE `visiteurs_hotel`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_visiteurs_hotel_concierge` (`concierge_id`),
  ADD KEY `idx_visiteurs_hotel_date` (`date_visite`);

--
-- Indexes for table `visiteurs_showroom`
--
ALTER TABLE `visiteurs_showroom`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_visiteurs_client` (`client_id`),
  ADD KEY `fk_visiteurs_utilisateur` (`utilisateur_id`),
  ADD KEY `idx_visiteurs_date` (`date_visite`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `achats`
--
ALTER TABLE `achats`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `achats_lignes`
--
ALTER TABLE `achats_lignes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `blocages_ip`
--
ALTER TABLE `blocages_ip`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bons_livraison`
--
ALTER TABLE `bons_livraison`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT for table `bons_livraison_lignes`
--
ALTER TABLE `bons_livraison_lignes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=192;

--
-- AUTO_INCREMENT for table `caisse_journal`
--
ALTER TABLE `caisse_journal`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `canaux_vente`
--
ALTER TABLE `canaux_vente`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `catalogue_categories`
--
ALTER TABLE `catalogue_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `catalogue_produits`
--
ALTER TABLE `catalogue_produits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=155;

--
-- AUTO_INCREMENT for table `chambres`
--
ALTER TABLE `chambres`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=97;

--
-- AUTO_INCREMENT for table `compta_comptes`
--
ALTER TABLE `compta_comptes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=112;

--
-- AUTO_INCREMENT for table `compta_ecritures`
--
ALTER TABLE `compta_ecritures`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT for table `compta_exercices`
--
ALTER TABLE `compta_exercices`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `compta_journaux`
--
ALTER TABLE `compta_journaux`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `compta_mapping_operations`
--
ALTER TABLE `compta_mapping_operations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `compta_operations_trace`
--
ALTER TABLE `compta_operations_trace`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `compta_pieces`
--
ALTER TABLE `compta_pieces`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `connexions_utilisateur`
--
ALTER TABLE `connexions_utilisateur`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `conversions_pipeline`
--
ALTER TABLE `conversions_pipeline`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `devis`
--
ALTER TABLE `devis`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

--
-- AUTO_INCREMENT for table `devis_lignes`
--
ALTER TABLE `devis_lignes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=251;

--
-- AUTO_INCREMENT for table `familles_produits`
--
ALTER TABLE `familles_produits`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- AUTO_INCREMENT for table `formations`
--
ALTER TABLE `formations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `fournisseurs`
--
ALTER TABLE `fournisseurs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `inscriptions_formation`
--
ALTER TABLE `inscriptions_formation`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `journal_caisse`
--
ALTER TABLE `journal_caisse`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `kpis_quotidiens`
--
ALTER TABLE `kpis_quotidiens`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leads_digital`
--
ALTER TABLE `leads_digital`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `modes_paiement`
--
ALTER TABLE `modes_paiement`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `mouvements_stock_backup_20251209_161710`
--
ALTER TABLE `mouvements_stock_backup_20251209_161710`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `objectifs_commerciaux`
--
ALTER TABLE `objectifs_commerciaux`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ordres_preparation`
--
ALTER TABLE `ordres_preparation`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ordres_preparation_lignes`
--
ALTER TABLE `ordres_preparation_lignes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `parametres_securite`
--
ALTER TABLE `parametres_securite`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `produits`
--
ALTER TABLE `produits`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT for table `promotions`
--
ALTER TABLE `promotions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prospections_terrain`
--
ALTER TABLE `prospections_terrain`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `prospects_formation`
--
ALTER TABLE `prospects_formation`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `relances_devis`
--
ALTER TABLE `relances_devis`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rendezvous_terrain`
--
ALTER TABLE `rendezvous_terrain`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reservations_hotel`
--
ALTER TABLE `reservations_hotel`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `retours_litiges`
--
ALTER TABLE `retours_litiges`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `ruptures_signalees`
--
ALTER TABLE `ruptures_signalees`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ruptures_stock`
--
ALTER TABLE `ruptures_stock`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `satisfaction_clients`
--
ALTER TABLE `satisfaction_clients`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `sms_2fa_codes`
--
ALTER TABLE `sms_2fa_codes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `sms_tracking`
--
ALTER TABLE `sms_tracking`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sous_categories_produits`
--
ALTER TABLE `sous_categories_produits`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `stocks_mouvements`
--
ALTER TABLE `stocks_mouvements`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=234;

--
-- AUTO_INCREMENT for table `tentatives_connexion`
--
ALTER TABLE `tentatives_connexion`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `types_client`
--
ALTER TABLE `types_client`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `upsell_hotel`
--
ALTER TABLE `upsell_hotel`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `utilisateurs_2fa`
--
ALTER TABLE `utilisateurs_2fa`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `utilisateurs_2fa_recovery`
--
ALTER TABLE `utilisateurs_2fa_recovery`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ventes`
--
ALTER TABLE `ventes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=88;

--
-- AUTO_INCREMENT for table `ventes_lignes`
--
ALTER TABLE `ventes_lignes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=268;

--
-- AUTO_INCREMENT for table `visiteurs_hotel`
--
ALTER TABLE `visiteurs_hotel`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `visiteurs_showroom`
--
ALTER TABLE `visiteurs_showroom`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `achats`
--
ALTER TABLE `achats`
  ADD CONSTRAINT `fk_achats_utilisateur` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `achats_lignes`
--
ALTER TABLE `achats_lignes`
  ADD CONSTRAINT `fk_achats_lignes_achat` FOREIGN KEY (`achat_id`) REFERENCES `achats` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_achats_lignes_produit` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD CONSTRAINT `audit_log_ibfk_1` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `bons_livraison`
--
ALTER TABLE `bons_livraison`
  ADD CONSTRAINT `fk_bl_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_bl_magasinier` FOREIGN KEY (`magasinier_id`) REFERENCES `utilisateurs` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_bl_vente` FOREIGN KEY (`vente_id`) REFERENCES `ventes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `bons_livraison_lignes`
--
ALTER TABLE `bons_livraison_lignes`
  ADD CONSTRAINT `fk_bl_lignes_bl` FOREIGN KEY (`bon_livraison_id`) REFERENCES `bons_livraison` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_bl_lignes_produit` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `catalogue_produits`
--
ALTER TABLE `catalogue_produits`
  ADD CONSTRAINT `fk_catalogue_categorie` FOREIGN KEY (`categorie_id`) REFERENCES `catalogue_categories` (`id`);

--
-- Constraints for table `clients`
--
ALTER TABLE `clients`
  ADD CONSTRAINT `fk_clients_type` FOREIGN KEY (`type_client_id`) REFERENCES `types_client` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `compta_comptes`
--
ALTER TABLE `compta_comptes`
  ADD CONSTRAINT `compta_comptes_ibfk_1` FOREIGN KEY (`compte_parent_id`) REFERENCES `compta_comptes` (`id`);

--
-- Constraints for table `compta_ecritures`
--
ALTER TABLE `compta_ecritures`
  ADD CONSTRAINT `compta_ecritures_ibfk_1` FOREIGN KEY (`piece_id`) REFERENCES `compta_pieces` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `compta_ecritures_ibfk_2` FOREIGN KEY (`compte_id`) REFERENCES `compta_comptes` (`id`),
  ADD CONSTRAINT `compta_ecritures_ibfk_3` FOREIGN KEY (`tiers_client_id`) REFERENCES `clients` (`id`),
  ADD CONSTRAINT `compta_ecritures_ibfk_4` FOREIGN KEY (`tiers_fournisseur_id`) REFERENCES `fournisseurs` (`id`);

--
-- Constraints for table `compta_journaux`
--
ALTER TABLE `compta_journaux`
  ADD CONSTRAINT `compta_journaux_ibfk_1` FOREIGN KEY (`compte_contre_partie`) REFERENCES `compta_comptes` (`id`);

--
-- Constraints for table `compta_mapping_operations`
--
ALTER TABLE `compta_mapping_operations`
  ADD CONSTRAINT `compta_mapping_operations_ibfk_1` FOREIGN KEY (`journal_id`) REFERENCES `compta_journaux` (`id`),
  ADD CONSTRAINT `compta_mapping_operations_ibfk_2` FOREIGN KEY (`compte_debit_id`) REFERENCES `compta_comptes` (`id`),
  ADD CONSTRAINT `compta_mapping_operations_ibfk_3` FOREIGN KEY (`compte_credit_id`) REFERENCES `compta_comptes` (`id`);

--
-- Constraints for table `compta_operations_trace`
--
ALTER TABLE `compta_operations_trace`
  ADD CONSTRAINT `compta_operations_trace_ibfk_1` FOREIGN KEY (`piece_id`) REFERENCES `compta_pieces` (`id`);

--
-- Constraints for table `compta_pieces`
--
ALTER TABLE `compta_pieces`
  ADD CONSTRAINT `compta_pieces_ibfk_1` FOREIGN KEY (`exercice_id`) REFERENCES `compta_exercices` (`id`),
  ADD CONSTRAINT `compta_pieces_ibfk_2` FOREIGN KEY (`journal_id`) REFERENCES `compta_journaux` (`id`),
  ADD CONSTRAINT `compta_pieces_ibfk_3` FOREIGN KEY (`tiers_client_id`) REFERENCES `clients` (`id`),
  ADD CONSTRAINT `compta_pieces_ibfk_4` FOREIGN KEY (`tiers_fournisseur_id`) REFERENCES `fournisseurs` (`id`);

--
-- Constraints for table `connexions_utilisateur`
--
ALTER TABLE `connexions_utilisateur`
  ADD CONSTRAINT `fk_connexions_utilisateur_utilisateur` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `conversions_pipeline`
--
ALTER TABLE `conversions_pipeline`
  ADD CONSTRAINT `fk_conversions_canal` FOREIGN KEY (`canal_vente_id`) REFERENCES `canaux_vente` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_conversions_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_conversions_devis` FOREIGN KEY (`devis_id`) REFERENCES `devis` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_conversions_vente` FOREIGN KEY (`vente_id`) REFERENCES `ventes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `devis`
--
ALTER TABLE `devis`
  ADD CONSTRAINT `fk_devis_canal` FOREIGN KEY (`canal_vente_id`) REFERENCES `canaux_vente` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_devis_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_devis_utilisateur` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `devis_lignes`
--
ALTER TABLE `devis_lignes`
  ADD CONSTRAINT `fk_devis_lignes_devis` FOREIGN KEY (`devis_id`) REFERENCES `devis` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_devis_lignes_produit` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `inscriptions_formation`
--
ALTER TABLE `inscriptions_formation`
  ADD CONSTRAINT `fk_inscription_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_inscription_formation` FOREIGN KEY (`formation_id`) REFERENCES `formations` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `journal_caisse`
--
ALTER TABLE `journal_caisse`
  ADD CONSTRAINT `fk_caisse_inscription` FOREIGN KEY (`inscription_formation_id`) REFERENCES `inscriptions_formation` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_caisse_mode_paiement` FOREIGN KEY (`mode_paiement_id`) REFERENCES `modes_paiement` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_caisse_reservation` FOREIGN KEY (`reservation_id`) REFERENCES `reservations_hotel` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_caisse_responsable` FOREIGN KEY (`responsable_encaissement_id`) REFERENCES `utilisateurs` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_caisse_vente` FOREIGN KEY (`vente_id`) REFERENCES `ventes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_journal_caisse_annule_par` FOREIGN KEY (`annule_par_id`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `leads_digital`
--
ALTER TABLE `leads_digital`
  ADD CONSTRAINT `fk_leads_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_leads_utilisateur` FOREIGN KEY (`utilisateur_responsable_id`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `mouvements_stock_backup_20251209_161710`
--
ALTER TABLE `mouvements_stock_backup_20251209_161710`
  ADD CONSTRAINT `fk_mouvements_stock_produit` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_mouvements_stock_utilisateur` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `ordres_preparation`
--
ALTER TABLE `ordres_preparation`
  ADD CONSTRAINT `fk_ordres_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ordres_commercial` FOREIGN KEY (`commercial_responsable_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ordres_devis` FOREIGN KEY (`devis_id`) REFERENCES `devis` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ordres_magasinier` FOREIGN KEY (`magasinier_id`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ordres_vente` FOREIGN KEY (`vente_id`) REFERENCES `ventes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `ordres_preparation_lignes`
--
ALTER TABLE `ordres_preparation_lignes`
  ADD CONSTRAINT `fk_ordres_lignes_ordre` FOREIGN KEY (`ordre_preparation_id`) REFERENCES `ordres_preparation` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ordres_lignes_produit` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `parametres_securite`
--
ALTER TABLE `parametres_securite`
  ADD CONSTRAINT `parametres_securite_ibfk_1` FOREIGN KEY (`modifie_par`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `produits`
--
ALTER TABLE `produits`
  ADD CONSTRAINT `fk_produits_famille` FOREIGN KEY (`famille_id`) REFERENCES `familles_produits` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_produits_fournisseur` FOREIGN KEY (`fournisseur_id`) REFERENCES `fournisseurs` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_produits_sous_categorie` FOREIGN KEY (`sous_categorie_id`) REFERENCES `sous_categories_produits` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `promotion_produit`
--
ALTER TABLE `promotion_produit`
  ADD CONSTRAINT `fk_promo_produit_produit` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_promo_produit_promo` FOREIGN KEY (`promotion_id`) REFERENCES `promotions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `prospections_terrain`
--
ALTER TABLE `prospections_terrain`
  ADD CONSTRAINT `fk_prospections_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_prospections_commercial` FOREIGN KEY (`commercial_id`) REFERENCES `utilisateurs` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `prospects_formation`
--
ALTER TABLE `prospects_formation`
  ADD CONSTRAINT `fk_prospect_formation_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_prospect_formation_utilisateur` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `relances_devis`
--
ALTER TABLE `relances_devis`
  ADD CONSTRAINT `fk_relances_devis` FOREIGN KEY (`devis_id`) REFERENCES `devis` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_relances_utilisateur` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `rendezvous_terrain`
--
ALTER TABLE `rendezvous_terrain`
  ADD CONSTRAINT `fk_rdv_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rdv_commercial` FOREIGN KEY (`commercial_id`) REFERENCES `utilisateurs` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `reservations_hotel`
--
ALTER TABLE `reservations_hotel`
  ADD CONSTRAINT `fk_reservation_chambre` FOREIGN KEY (`chambre_id`) REFERENCES `chambres` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_reservation_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_reservation_concierge` FOREIGN KEY (`concierge_id`) REFERENCES `utilisateurs` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_reservation_mode_paiement` FOREIGN KEY (`mode_paiement_id`) REFERENCES `modes_paiement` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `retours_litiges`
--
ALTER TABLE `retours_litiges`
  ADD CONSTRAINT `fk_litiges_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_litiges_produit` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_litiges_responsable` FOREIGN KEY (`responsable_suivi_id`) REFERENCES `utilisateurs` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_litiges_vente` FOREIGN KEY (`vente_id`) REFERENCES `ventes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `role_permission`
--
ALTER TABLE `role_permission`
  ADD CONSTRAINT `fk_role_permission_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_role_permission_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `ruptures_signalees`
--
ALTER TABLE `ruptures_signalees`
  ADD CONSTRAINT `fk_ruptures_sig_magasinier` FOREIGN KEY (`magasinier_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ruptures_sig_produit` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `ruptures_stock`
--
ALTER TABLE `ruptures_stock`
  ADD CONSTRAINT `fk_ruptures_magasinier` FOREIGN KEY (`magasinier_id`) REFERENCES `utilisateurs` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ruptures_produit` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `satisfaction_clients`
--
ALTER TABLE `satisfaction_clients`
  ADD CONSTRAINT `fk_satisfaction_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_satisfaction_utilisateur` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `sessions_actives`
--
ALTER TABLE `sessions_actives`
  ADD CONSTRAINT `sessions_actives_ibfk_1` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sous_categories_produits`
--
ALTER TABLE `sous_categories_produits`
  ADD CONSTRAINT `fk_sous_categories_famille` FOREIGN KEY (`famille_id`) REFERENCES `familles_produits` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `stocks_mouvements`
--
ALTER TABLE `stocks_mouvements`
  ADD CONSTRAINT `fk_mouvements_produit` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_mouvements_utilisateur` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `tentatives_connexion`
--
ALTER TABLE `tentatives_connexion`
  ADD CONSTRAINT `tentatives_connexion_ibfk_1` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `upsell_hotel`
--
ALTER TABLE `upsell_hotel`
  ADD CONSTRAINT `fk_upsell_reservation` FOREIGN KEY (`reservation_id`) REFERENCES `reservations_hotel` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `utilisateurs_2fa`
--
ALTER TABLE `utilisateurs_2fa`
  ADD CONSTRAINT `utilisateurs_2fa_ibfk_1` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `utilisateurs_2fa_recovery`
--
ALTER TABLE `utilisateurs_2fa_recovery`
  ADD CONSTRAINT `utilisateurs_2fa_recovery_ibfk_1` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `utilisateur_role`
--
ALTER TABLE `utilisateur_role`
  ADD CONSTRAINT `fk_utilisateur_role_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_utilisateur_role_utilisateur` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `ventes`
--
ALTER TABLE `ventes`
  ADD CONSTRAINT `fk_ventes_canal` FOREIGN KEY (`canal_vente_id`) REFERENCES `canaux_vente` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ventes_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ventes_devis` FOREIGN KEY (`devis_id`) REFERENCES `devis` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ventes_utilisateur` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `ventes_lignes`
--
ALTER TABLE `ventes_lignes`
  ADD CONSTRAINT `fk_ventes_lignes_produit` FOREIGN KEY (`produit_id`) REFERENCES `produits` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ventes_lignes_vente` FOREIGN KEY (`vente_id`) REFERENCES `ventes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `visiteurs_hotel`
--
ALTER TABLE `visiteurs_hotel`
  ADD CONSTRAINT `fk_visiteurs_hotel_concierge` FOREIGN KEY (`concierge_id`) REFERENCES `utilisateurs` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `visiteurs_showroom`
--
ALTER TABLE `visiteurs_showroom`
  ADD CONSTRAINT `fk_visiteurs_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_visiteurs_utilisateur` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
