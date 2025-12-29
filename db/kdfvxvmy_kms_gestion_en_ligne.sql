-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Dec 29, 2025 at 08:11 AM
-- Server version: 8.0.44-35
-- PHP Version: 8.3.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `kdfvxvmy_kms_gestion`
--

-- --------------------------------------------------------

--
-- Table structure for table `achats`
--

CREATE TABLE `achats` (
  `id` int UNSIGNED NOT NULL,
  `numero` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_achat` date NOT NULL,
  `fournisseur_nom` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fournisseur_contact` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `montant_total_ht` decimal(15,2) NOT NULL DEFAULT '0.00',
  `montant_total_ttc` decimal(15,2) NOT NULL DEFAULT '0.00',
  `statut` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'EN_COURS',
  `utilisateur_id` int UNSIGNED DEFAULT NULL,
  `commentaires` text COLLATE utf8mb4_unicode_ci,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `achats_lignes`
--

CREATE TABLE `achats_lignes` (
  `id` int UNSIGNED NOT NULL,
  `achat_id` int UNSIGNED NOT NULL,
  `produit_id` int UNSIGNED NOT NULL,
  `quantite` decimal(15,3) NOT NULL DEFAULT '0.000',
  `prix_unitaire` decimal(15,2) NOT NULL DEFAULT '0.00',
  `remise` decimal(15,2) NOT NULL DEFAULT '0.00',
  `montant_ligne_ht` decimal(15,2) NOT NULL DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `id` bigint UNSIGNED NOT NULL,
  `utilisateur_id` int UNSIGNED DEFAULT NULL COMMENT 'NULL si action syst??me',
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Type action: LOGIN, LOGOUT, CREATE, UPDATE, DELETE',
  `module` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Module concern??: PRODUITS, VENTES, CAISSE, etc.',
  `entite_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Type entit??: produit, vente, client',
  `entite_id` int UNSIGNED DEFAULT NULL COMMENT 'ID de l''entit??',
  `details` longtext COLLATE utf8mb4_unicode_ci COMMENT 'D??tails de l''action',
  `ancienne_valeur` longtext COLLATE utf8mb4_unicode_ci COMMENT 'Valeur avant modification',
  `nouvelle_valeur` longtext COLLATE utf8mb4_unicode_ci COMMENT 'Valeur apr??s modification',
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_agent` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_action` datetime DEFAULT CURRENT_TIMESTAMP,
  `niveau` enum('INFO','WARNING','ERROR','CRITICAL') COLLATE utf8mb4_unicode_ci DEFAULT 'INFO'
) ;

-- --------------------------------------------------------

--
-- Table structure for table `blocages_ip`
--

CREATE TABLE `blocages_ip` (
  `id` int UNSIGNED NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `raison` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type_blocage` enum('TEMPORAIRE','PERMANENT') COLLATE utf8mb4_unicode_ci DEFAULT 'TEMPORAIRE',
  `tentatives_echouees` int UNSIGNED DEFAULT '0',
  `date_blocage` datetime DEFAULT CURRENT_TIMESTAMP,
  `date_expiration` datetime DEFAULT NULL COMMENT 'NULL si permanent',
  `debloque_par` int UNSIGNED DEFAULT NULL COMMENT 'Admin qui a d??bloqu??',
  `date_deblocage` datetime DEFAULT NULL,
  `actif` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Liste des adresses IP bloqu??es';

-- --------------------------------------------------------

--
-- Table structure for table `bons_livraison`
--

CREATE TABLE `bons_livraison` (
  `id` int UNSIGNED NOT NULL,
  `numero` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_bl` date NOT NULL,
  `date_livraison_effective` datetime DEFAULT NULL,
  `vente_id` int UNSIGNED DEFAULT NULL,
  `ordre_preparation_id` int UNSIGNED DEFAULT NULL,
  `client_id` int UNSIGNED NOT NULL,
  `transport_assure_par` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `observations` text COLLATE utf8mb4_unicode_ci,
  `signe_client` tinyint(1) NOT NULL DEFAULT '0',
  `statut` enum('EN_PREPARATION','PRET','EN_COURS_LIVRAISON','LIVRE','ANNULE') COLLATE utf8mb4_unicode_ci DEFAULT 'EN_PREPARATION',
  `magasinier_id` int UNSIGNED NOT NULL,
  `livreur_id` int UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bons_livraison_lignes`
--

CREATE TABLE `bons_livraison_lignes` (
  `id` int UNSIGNED NOT NULL,
  `bon_livraison_id` int UNSIGNED NOT NULL,
  `produit_id` int UNSIGNED NOT NULL,
  `quantite` int NOT NULL,
  `quantite_commandee` decimal(10,2) NOT NULL DEFAULT '0.00',
  `quantite_restante` decimal(10,2) NOT NULL DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `caisses_clotures`
--

CREATE TABLE `caisses_clotures` (
  `id` int UNSIGNED NOT NULL,
  `date_cloture` date NOT NULL,
  `total_recettes` decimal(15,2) NOT NULL DEFAULT '0.00',
  `total_depenses` decimal(15,2) NOT NULL DEFAULT '0.00',
  `solde_calcule` decimal(15,2) NOT NULL DEFAULT '0.00',
  `montant_especes_declare` decimal(15,2) DEFAULT '0.00',
  `montant_cheques_declare` decimal(15,2) DEFAULT '0.00',
  `montant_virements_declare` decimal(15,2) DEFAULT '0.00',
  `montant_mobile_declare` decimal(15,2) DEFAULT '0.00',
  `total_declare` decimal(15,2) NOT NULL DEFAULT '0.00',
  `ecart` decimal(15,2) NOT NULL DEFAULT '0.00',
  `justification_ecart` text COLLATE utf8mb4_general_ci,
  `nb_operations` int DEFAULT '0',
  `nb_ventes` int DEFAULT '0',
  `nb_annulations` int DEFAULT '0',
  `statut` enum('BROUILLON','VALIDE','ANNULE') COLLATE utf8mb4_general_ci DEFAULT 'BROUILLON',
  `caissier_id` int UNSIGNED DEFAULT NULL,
  `validateur_id` int UNSIGNED DEFAULT NULL,
  `date_validation` datetime DEFAULT NULL,
  `observations` text COLLATE utf8mb4_general_ci,
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `caisse_journal`
--

CREATE TABLE `caisse_journal` (
  `id` int NOT NULL,
  `date_ecriture` datetime NOT NULL,
  `sens` enum('ENTREE','SORTIE') COLLATE utf8mb4_unicode_ci NOT NULL,
  `montant` decimal(15,2) NOT NULL,
  `source_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_id` int DEFAULT NULL,
  `commentaire` text COLLATE utf8mb4_unicode_ci,
  `utilisateur_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `canaux_vente`
--

CREATE TABLE `canaux_vente` (
  `id` int UNSIGNED NOT NULL,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `libelle` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `canaux_vente`
--

INSERT INTO `canaux_vente` (`id`, `code`, `libelle`) VALUES
(1, 'SHOWROOM', 'Vente showroom'),
(2, 'TERRAIN', 'Vente terrain'),
(3, 'DIGITAL', 'Vente digital / en ligne'),
(4, 'HOTEL', 'Vente liée é l\'hôtel'),
(5, 'FORMATION', 'Vente liée aux formations');

-- --------------------------------------------------------

--
-- Table structure for table `catalogue_categories`
--

CREATE TABLE `catalogue_categories` (
  `id` int NOT NULL,
  `nom` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(180) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  `ordre` int NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `catalogue_categories`
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
-- Table structure for table `catalogue_produits`
--

CREATE TABLE `catalogue_produits` (
  `id` int NOT NULL,
  `produit_id` int DEFAULT NULL,
  `code` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(180) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `designation` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `categorie_id` int NOT NULL,
  `prix_unite` decimal(15,2) DEFAULT NULL,
  `prix_gros` decimal(15,2) DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `caracteristiques_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `image_principale` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `galerie_images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ;

--
-- Dumping data for table `catalogue_produits`
--

INSERT INTO `catalogue_produits` (`id`, `produit_id`, `code`, `slug`, `designation`, `categorie_id`, `prix_unite`, `prix_gros`, `description`, `caracteristiques_json`, `image_principale`, `galerie_images`, `actif`, `created_at`, `updated_at`) VALUES
(296, 73, 'MAC-HLD-1100', 'machine-de-percage-de-serrure-hld-1100', 'Machine de perçage de serrure HLD-1100 (Handle Lock Drilling Tool)', 20, 1000000.00, 950000.00, 'Cette machine de perçage de serrure permet un perçage rapide et précis des logements de serrures et poignées de portes. Idéale pour les menuisiers et fabricants de portes, elle améliore la productivité et garantit des finitions propres. Son moteur puissant et sa double compatibilité électrique en font un outil fiable en atelier comme sur chantier.', '{\"alimentation\": \"380 V triphasé ou 220 V monophasé, 50 Hz\", \"applications\": \"perçage précis des logements de serrures et poignées sur portes bois\", \"construction\": \"robuste pour usage intensif\", \"compatibilite\": \"différents diamètres de mèches\", \"puissance_moteur\": \"1100 W\"}', NULL, '[]', 1, '2025-12-22 06:57:53', '2025-12-29 13:21:01'),
(297, 74, 'MAC-STM-2200', 'tenonneuse-stm-2200', 'TENONNEUSE STM-2200 2,2KW (Square Tenoning Machine)', 20, 1200000.00, 1150000.00, 'Cette mortaiseuse à mèche carrée est conçue pour réaliser rapidement des tenons et mortaises précis dans le bois massif. Sa puissance de 2,2 kW et sa structure robuste assurent une excellente stabilité, même sur des pièces épaisses. C\'est un outil indispensable pour les ateliers de menuiserie cherchant productivité et précision dans l\'assemblage bois.', '{\"dimensions\": \"600 x 860 x 1470 mm\", \"utilisation\": \"usinage de tenons et mortaises carrées sur bois massif et panneaux\", \"alimentation\": \"380 V triphasé ou 220 V monophasé, 50 Hz\", \"construction\": \"lourde et stable pour usage en atelier\", \"puissance_moteur\": \"2,2 kW\"}', NULL, '[]', 1, '2025-12-22 06:57:53', '2025-12-29 13:21:01'),
(298, 75, 'MAC-MKD-550', 'defonceuse-manuelle-mkd-550', 'DEFONCEUSE MANUELLE MKD-550 550W (Manual Keyhole Drilling Machine)', 20, 300000.00, 285000.00, 'Cette machine manuelle de perçage de trous de serrure est conçue pour les menuisiers recherchant précision et simplicité. Grâce à son moteur de 550 W et à son système de guidage, elle permet de réaliser facilement des logements de serrure nets et bien alignés. Idéale pour les ateliers et les travaux de personnalisation de portes, elle combine compacité, fiabilité et facilité d\'utilisation.', '{\"commande\": \"manuelle avec guides pour exactitude du positionnement\", \"compacte\": \"adaptée aux petits ateliers\", \"fonction\": \"perçage précis de trous de serrure ou de logements allongés dans les portes en bois\", \"alimentation\": \"380 V triphasé ou 220 V monophasé, 50 Hz\", \"puissance_moteur\": \"550 W\"}', NULL, '[]', 1, '2025-12-22 06:57:53', '2025-12-29 13:21:01'),
(299, 76, 'MAC-HDM-6415', 'machine-de-percage-de-charnieres-hdm-6415', 'Machine de perçage de charnières HDM-6415 – tête simple (Hinge Drilling Machine)', 20, 750000.00, 720000.00, 'La machine de perçage de charnières à tête simple permet un perçage rapide et précis des logements de charnières. Sa structure robuste et son système de guidage garantissent un positionnement exact, réduisant les erreurs. Idéale pour les ateliers de menuiserie et les fabricants de meubles, elle améliore la productivité tout en assurant une finition professionnelle.', '{\"tete\": \"simple (perçage individuel de charnières 35 mm)\", \"table\": \"réglable avec guides pour précision de positionnement\", \"conception\": \"robuste avec collecteur de poussières possible selon modèles\", \"dimensions\": \"700 x 560 x 1500 mm\", \"alimentation\": \"380 V triphasé ou 220 V monophasé, 50 Hz\"}', NULL, '[]', 1, '2025-12-22 06:57:53', '2025-12-29 13:21:01'),
(300, 77, 'MAC-PLAQUEUSE-802', 'plaqueuse-automatique-de-chants-802', 'PLAQUEUSE AUTOMATIQUE DE CHANTS – MODÈLE 802', 20, 1500000.00, 1450000.00, 'La plaqueuse automatique de chants 802 est idéale pour les ateliers de menuiserie, offrant rapidité, précision et finition professionnelle. Ses caractéristiques incluent un encollage double face, un polissage double et un système d\'aspiration intégré pour un collage propre et durable, même sur de grands panneaux. Compacte et robuste, elle permet à un seul opérateur de réaliser un plaquage esthétique tout en gardant un environnement de travail propre.', '{\"coupe\": \"automatique du chant\", \"poids\": \"150 kg\", \"systeme\": \"d\'aspiration intégré\", \"tension\": \"220 V\", \"encollage\": \"double face\", \"polissage\": \"double\", \"dimensions\": \"130 × 60 × 95 cm\", \"puissance_nominale\": \"4000 W\"}', NULL, '[]', 1, '2025-12-22 06:57:53', '2025-12-29 13:21:01'),
(301, 78, 'MAC-EDGE-702', 'full-automatic-edge-banding-machine-702', '702 – FULL AUTOMATIC EDGE BANDING MACHINE', 20, 1500000.00, 1450000.00, 'La plaqueuse automatique de chants 702 est une machine compacte, performante et économique, idéale pour les ateliers. Elle offre un plaquage fluide avec une finition soignée grâce à son double polissage et son aspiration intégrée. Adaptée à la production en série ou personnalisée, elle permet un gain de temps tout en garantissant une qualité constante sur divers panneaux.', '{\"coupe\": \"automatique du chant\", \"poids\": \"105 kg\", \"systeme\": \"d\'aspiration intégré\", \"tension\": \"220 V\", \"plaquage\": \"multiple de panneaux\", \"polissage\": \"double\", \"dimensions\": \"120 × 55 × 110 cm\", \"entrainement\": \"continu des panneaux\", \"puissance_nominale\": \"3800 W\"}', NULL, '[]', 1, '2025-12-22 06:57:53', '2025-12-29 13:21:01'),
(302, 79, 'MAC-SCIE-TABLE', 'scie-a-table-portable-multifonction', 'SCIE À TABLE PORTABLE MULTIFONCTION', 20, 1500000.00, 1450000.00, 'La scie à table portable multifonction est idéale pour les menuisiers et artisans, offrant une solution mobile et précise pour la découpe. Avec sa double lame, elle assure des coupes nettes sur divers matériaux sans éclats. Son design pliable, son poids léger et ses roues intégrées en font un outil pratique pour les chantiers, tout en respectant l\'environnement grâce à son système de collecte de poussière.', '{\"poids_total\": \"24 kg\", \"dimensions_table\": \"500 × 300 × 8 mm\", \"vitesse_scie_inciseur\": \"13 000 tr/min\", \"puissance_scie_inciseur\": \"1450 W\", \"vitesse_scie_principale\": \"3800 tr/min\", \"diametre_lame_principale\": \"200 mm (extérieur) / 25,4 mm (intérieur)\", \"puissance_scie_principale\": \"2000 W\", \"puissance_collecteur_poussiere\": \"1200 W\"}', NULL, '[]', 1, '2025-12-22 06:57:53', '2025-12-29 13:21:01'),
(303, 80, 'QUI-GLIS-RAL-NOIR', 'glissiere-avec-ralenti-noir', 'Glissière avec ralenti noir (cartons de 20 paires)', 21, 1800.00, 1650.00, 'La glissière avec ralenti noire améliore le confort d\'utilisation des tiroirs grâce à son système de fermeture amortie, assurant une fermeture douce et silencieuse, et évitant les dommages aux meubles. Appréciée dans les cuisines modernes et les meubles à usage fréquent, elle offre une sensation de qualité supérieure et garantit la stabilité du tiroir même sous charges répétées, tout en assurant une glisse fluide.', '{\"tarifs\": [{\"taille\": \"25 cm\", \"pv_detail\": 1800, \"pv_super_gros\": 33000}, {\"taille\": \"30 cm\", \"pv_detail\": 1800, \"pv_super_gros\": 33000}, {\"taille\": \"35 cm\", \"pv_detail\": 2800, \"pv_super_gros\": 53000}, {\"taille\": \"40 cm\", \"pv_detail\": 2800, \"pv_super_gros\": 53000}, {\"taille\": \"50 cm\", \"pv_detail\": 4500, \"pv_super_gros\": 87000}], \"conditionnement\": \"Cartons de 20 paires\"}', NULL, '[]', 1, '2025-12-22 06:57:53', '2025-12-29 13:21:01'),
(304, 81, 'QUI-GLIS-NORM-NOIR', 'glissiere-normale-noir', 'Glissière normale noir (cartons de 20 paires)', 21, 1150.00, 1000.00, 'La glissière noire normale est une solution économique et fiable pour les tiroirs standards, idéale pour les meubles de rangement et bureaux. Elle est facile à installer et à entretenir, offrant une glisse stable, adaptée aux productions en série et ateliers de menuiserie. C\'est un choix rationnel pour des projets nécessitant robustesse et maîtrise des coûts.', '{\"tarifs\": [{\"taille\": \"25 cm\", \"pv_detail\": 1150, \"pv_super_gros\": 20000}, {\"taille\": \"30 cm\", \"pv_detail\": 1350, \"pv_super_gros\": 24000}, {\"taille\": \"35 cm\", \"pv_detail\": 1550, \"pv_super_gros\": 28000}, {\"taille\": \"40 cm\", \"pv_detail\": 1650, \"pv_super_gros\": 30000}, {\"taille\": \"50 cm\", \"pv_detail\": 1850, \"pv_super_gros\": 34000}], \"conditionnement\": \"Cartons de 20 paires\"}', NULL, '[]', 1, '2025-12-22 06:57:53', '2025-12-29 13:21:01'),
(305, 82, 'QUI-SLIDE-POUSSE-NOIR', 'slide-pousse-lache-noir', 'Slide pousse lache noir (cartons de 20 paires)', 21, 1800.00, 1650.00, 'La slide pousse-lâche noire est parfaite pour les meubles modernes, offrant une ouverture sans poignée par pression sur la façade, idéale pour les cuisines contemporaines. Elle allie esthétique et praticité en éliminant les poignées visibles tout en assurant une ouverture fluide, garantissant un usage quotidien confortable et un rendu visuel harmonieux.', '{\"tarifs\": [{\"taille\": \"25 cm\", \"pv_detail\": 1800, \"pv_super_gros\": 33000}, {\"taille\": \"30 cm\", \"pv_detail\": 1800, \"pv_super_gros\": 33000}, {\"taille\": \"35 cm\", \"pv_detail\": 2800, \"pv_super_gros\": 53000}, {\"taille\": \"40 cm\", \"pv_detail\": 2800, \"pv_super_gros\": 53000}, {\"taille\": \"50 cm\", \"pv_detail\": 4500, \"pv_super_gros\": 87000}], \"conditionnement\": \"Cartons de 20 paires\"}', NULL, '[]', 1, '2025-12-22 06:57:53', '2025-12-29 13:21:01'),
(306, 83, 'QUI-GLIS-RAL-ACIER', 'glissiere-ralenti-acier', 'Glissière ralenti acier (cartons de 20 paires)', 21, 2500.00, 2350.00, 'La glissière ralenti en aluminium combine fermeture amortie et renfort, offrant durabilité et résistance à l\'usure, idéale pour meubles intensifs ou haut de gamme. Son système de ralenti intégré protège la structure et améliore le confort, parfaite pour cuisines premium, tiroirs larges, nécessitant fiabilité, silence et longévité.', '{\"tarifs\": [{\"taille\": \"25 cm\", \"pv_detail\": 2500, \"pv_super_gros\": 47000}, {\"taille\": \"30 cm\", \"pv_detail\": 2500, \"pv_super_gros\": 47000}, {\"taille\": \"35 cm\", \"pv_detail\": 3500, \"pv_super_gros\": 67000}, {\"taille\": \"40 cm\", \"pv_detail\": 3500, \"pv_super_gros\": 67000}, {\"taille\": \"50 cm\", \"pv_detail\": 5000, \"pv_super_gros\": 97000}], \"conditionnement\": \"Cartons de 20 paires\"}', NULL, '[]', 1, '2025-12-22 06:57:53', '2025-12-29 13:21:01'),
(307, 84, 'QUI-GLIS-NORM-ACIER', 'glissiere-normale-acier', 'Glissière normale acier (cartons de 20 paires)', 21, 1800.00, 1650.00, 'La glissière normale en aluminium offre une résistance mécanique supérieure aux modèles standards, tout en restant simple à utiliser, idéale pour des tiroirs fréquemment sollicités en milieu domestique et professionnel. Son matériau en aluminium assure durabilité, limitant déformations et usure prématurée. C\'est une solution équilibrée entre solidité, fiabilité et coût, prisée dans les ateliers de menuiserie.', '{\"tarifs\": [{\"taille\": \"25 cm\", \"pv_detail\": 1800, \"pv_super_gros\": 33000}, {\"taille\": \"30 cm\", \"pv_detail\": 1800, \"pv_super_gros\": 33000}, {\"taille\": \"35 cm\", \"pv_detail\": 2500, \"pv_super_gros\": 47000}, {\"taille\": \"40 cm\", \"pv_detail\": 2500, \"pv_super_gros\": 47000}, {\"taille\": \"50 cm\", \"pv_detail\": 2800, \"pv_super_gros\": 53000}], \"conditionnement\": \"Cartons de 20 paires\"}', NULL, '[]', 1, '2025-12-22 06:57:53', '2025-12-29 13:21:01'),
(308, 85, 'QUI-GLIS-POUSSE-ALU', 'glissiere-pousse-lache-alu', 'Glissière pousse lache alu (cartons de 20 paires)', 21, 2500.00, 2350.00, 'La glissière pousse-lâche en aluminium permet une ouverture sans poignée pour les meubles modernes, alliant élégance et robustesse. Elle assure une ouverture fiable par pression et une stabilité optimale du tiroir, idéale pour des projets nécessitant esthétique, durabilité et précision.', '{\"tarifs\": [{\"taille\": \"25 cm\", \"pv_detail\": 2500, \"pv_super_gros\": 47000}, {\"taille\": \"30 cm\", \"pv_detail\": 2500, \"pv_super_gros\": 47000}, {\"taille\": \"35 cm\", \"pv_detail\": 3500, \"pv_super_gros\": 67000}, {\"taille\": \"40 cm\", \"pv_detail\": 3500, \"pv_super_gros\": 67000}, {\"taille\": \"50 cm\", \"pv_detail\": 5500, \"pv_super_gros\": 107000}], \"conditionnement\": \"Cartons de 20 paires\"}', NULL, '[]', 1, '2025-12-22 06:57:53', '2025-12-29 13:21:01'),
(309, 86, 'ACC-VIS-NOIR', 'vis-noir', 'Vis noir (paquet de 2 kg)', 22, 5000.00, 4500.00, 'Les vis noires sont destinées à l\'assemblage courant des meubles et structures en bois. Elles offrent une bonne tenue mécanique, une pose facile et une finition discrète adaptée aux meubles intérieurs. Elles conviennent parfaitement aux travaux de menuiserie générale, à l\'assemblage de panneaux et aux montages standards en atelier.', '{\"tarifs\": [{\"dimension\": \"4 x 30\", \"pv_detail\": 5000, \"pv_super_gros\": 4500}, {\"dimension\": \"4 x 40\", \"pv_detail\": 5000, \"pv_super_gros\": 4500}, {\"dimension\": \"5 x 70\", \"pv_detail\": 5000, \"pv_super_gros\": 4500}, {\"dimension\": \"5 x 50\", \"pv_detail\": 5000, \"pv_super_gros\": 4500}, {\"dimension\": \"3 x 16\", \"pv_detail\": 5000, \"pv_super_gros\": 4500}, {\"dimension\": \"4 x 25\", \"pv_detail\": 5000, \"pv_super_gros\": 4500}], \"conditionnement\": \"Paquet de 2 kg\"}', NULL, '[]', 1, '2025-12-22 06:57:53', '2025-12-29 13:21:01'),
(310, 87, 'ACC-VIS-PLAQUE-OR', 'vis-plaque-or', 'Vis plaque or (paquet de 2 kg)', 22, 4500.00, 4000.00, 'Les vis plaque or sont spécialement conçues pour la fixation des panneaux et plaques décoratives. Leur finition dorée offre une meilleure résistance à la corrosion et un rendu plus soigné lorsque la vis reste apparente. Elles sont adaptées aux travaux de menuiserie intérieure, à la pose de panneaux et aux assemblages nécessitant une fixation fiable et propre.', '{\"tarifs\": [{\"dimension\": \"4 x 30\", \"pv_detail\": 4500, \"pv_super_gros\": 4000}, {\"dimension\": \"4 x 40\", \"pv_detail\": 4500, \"pv_super_gros\": 4000}, {\"dimension\": \"5 x 70\", \"pv_detail\": 4500, \"pv_super_gros\": 4000}, {\"dimension\": \"5 x 50\", \"pv_detail\": 4500, \"pv_super_gros\": 4000}, {\"dimension\": \"3 x 16\", \"pv_detail\": 4500, \"pv_super_gros\": 4000}, {\"dimension\": \"4 x 25\", \"pv_detail\": 4500, \"pv_super_gros\": 4000}], \"conditionnement\": \"Paquet de 2 kg\"}', NULL, '[]', 1, '2025-12-22 06:57:53', '2025-12-29 13:21:01'),
(311, 88, 'ACC-MINI-FIX', 'mini-fix-connecteur-excentrique', 'Mini-fix ou connecteur excentrique', 22, 250.00, 215.00, 'Cette charnière dissimulée 3D en acier doux assure un mouvement fluide et silencieux grâce à sa fonction de fermeture douce. Le réglage 3D permet un alignement précis de la porte avec des vis intégrées, sans la retirer. Fonctionnelle et durable, elle améliore l\'esthétique de votre mobilier.', '{\"tarifs\": [{\"prix\": 215, \"type_vente\": \"PV super gros\"}, {\"prix\": 250, \"type_vente\": \"PV détail\"}], \"conditionnement\": \"Cartons de 100 paires\"}', NULL, '[]', 1, '2025-12-22 06:57:53', '2025-12-29 13:21:01'),
(312, 89, 'ACC-CHAR-3D', 'charniere-3d', 'Charnière 3D', 22, 900.00, 800.00, 'Charnière hydraulique à réglage 3D permettant une fermeture douce et silencieuse des portes de meubles. Son système de réglage en hauteur, profondeur et latéral facilite l\'alignement précis des portes après installation. Adaptée aux cuisines, dressings et meubles à usage fréquent.', '{\"tarifs\": [{\"prix\": 80000, \"type_vente\": \"PV super gros\"}, {\"prix\": 900, \"type_vente\": \"PV détail\"}], \"conditionnement\": \"Cartons de 100 paires\"}', NULL, '[]', 1, '2025-12-22 06:57:53', '2025-12-29 13:21:01'),
(313, 90, 'ACC-CHAR-GRENOUILLE', 'charniere-grenouille', 'Charnière grenouille', 22, 1400.00, 1250.00, 'Charnière hydraulique conçue pour assurer une fermeture douce et silencieuse des portes de meubles. Son système d\'amortissement intégré limite les chocs et prolonge la durée de vie des portes. Le réglage 2D permet un ajustement précis après installation, idéal pour cuisines, placards et meubles standards.', '{\"tarifs\": [{\"prix\": 125000, \"type_vente\": \"PV super gros\"}, {\"prix\": 1400, \"type_vente\": \"PV détail\"}], \"conditionnement\": \"Cartons de 100 paires\"}', NULL, '[]', 1, '2025-12-22 06:57:53', '2025-12-29 13:21:01'),
(314, 91, 'ACC-CHAR-90', 'charniere-meuble-90-degres', 'Charnière meuble à ouverture 90 degrés', 22, 1300.00, 1150.00, 'La charnière de meuble à ouverture 90 degrés est utilisée pour les portes d\'armoires, placards, cuisines et meubles TV. Elle permet un accès facile au contenu et peut inclure un système de fermeture douce pour un fonctionnement silencieux et contrôlé, selon la taille et le poids de la porte.', '{\"tarifs\": [{\"prix\": 115000, \"type_vente\": \"PV super gros\"}, {\"prix\": 1300, \"type_vente\": \"PV détail\"}], \"conditionnement\": \"Cartons de 100 paires\"}', NULL, '[]', 1, '2025-12-22 06:57:53', '2025-12-29 13:21:01'),
(315, 92, 'ACC-CHAR-3D-NOIRE', 'charniere-3d-noire', 'Charnière 3D noire', 22, 1000.00, 800.00, 'Charnière hydraulique noire à réglage 3D pour portes de meubles, offrant un ajustement précis en hauteur, profondeur et latéral après installation. Son système d\'amortissement permet une fermeture douce et silencieuse, parfaite pour cuisines et meubles modernes.', '{\"tarifs\": [{\"prix\": 80000, \"type_vente\": \"PV super gros\"}, {\"prix\": 1000, \"type_vente\": \"PV détail\"}], \"conditionnement\": \"Cartons de 100 paires\"}', NULL, '[]', 1, '2025-12-22 06:57:53', '2025-12-29 13:21:01'),
(316, 93, 'ACC-PAUM-PAP-ALU', 'paumelles-papillon-alu', 'Paumelles papillon alu', 22, 1200.00, 1000.00, 'Paumelle papillon en aluminium argenté, idéale pour meubles et placards. Son design moderne s\'adapte facilement à tout intérieur. Fabriquée en métal durable, elle garantit longévité et installation facile pour les travaux de menuiserie.', '{\"tarifs\": [{\"prix\": 50000, \"type_vente\": \"PV super gros\"}, {\"prix\": 1200, \"type_vente\": \"PV détail\"}], \"conditionnement\": \"Cartons de 50 paires\"}', NULL, '[]', 1, '2025-12-22 06:57:53', '2025-12-29 13:21:01'),
(317, 94, 'ACC-PAUM-PAP-NOIR', 'paumelles-papillon-noir', 'Paumelles papillon noir', 22, 1300.00, 1100.00, 'Paumelle papillon noire à finition mate, idéale pour les meubles au style moderne ou industriel. Elle permet un assemblage fiable des portes tout en apportant un contraste esthétique marqué. Sa finition traitée offre une bonne résistance à l\'usure et à la corrosion pour un usage intérieur fréquent.', '{\"tarifs\": [{\"prix\": 55000, \"type_vente\": \"PV super gros\"}, {\"prix\": 1300, \"type_vente\": \"PV détail\"}], \"conditionnement\": \"Cartons de 50 paires\"}', NULL, '[]', 1, '2025-12-22 06:57:53', '2025-12-29 13:21:01'),
(318, 95, 'ACC-PAUM-PAP-BLANCHE', 'paumelles-papillon-blanche', 'Paumelles papillon blanche', 22, 1850.00, 1500.00, 'Paumelle papillon blanche à finition mate, conçue pour se fondre discrètement dans les portes et cadres clairs. Elle est particulièrement adaptée aux cuisines, placards et meubles à design épuré. Sa finition peinte protège efficacement le métal pour un usage intérieur standard.', '{\"tarifs\": [{\"prix\": 75000, \"type_vente\": \"PV super gros\"}, {\"prix\": 1850, \"type_vente\": \"PV détail\"}], \"conditionnement\": \"Cartons de 50 paires\"}', NULL, '[]', 1, '2025-12-22 06:57:53', '2025-12-29 13:21:01'),
(319, 96, 'ACC-PAUM-PAP-OR', 'paumelles-papillon-or', 'Paumelles papillon or', 22, 1850.00, 1500.00, 'Paumelle papillon finition or, destinée aux meubles décoratifs et aux aménagements nécessitant une touche élégante et valorisante. Elle est souvent utilisée pour les meubles haut de gamme, vitrines et portes visibles. Sa finition décorative assure un bon compromis entre esthétique et durabilité.', '{\"tarifs\": [{\"prix\": 75000, \"type_vente\": \"PV super gros\"}, {\"prix\": 1850, \"type_vente\": \"PV détail\"}], \"conditionnement\": \"Cartons de 50 paires\"}', NULL, '[]', 1, '2025-12-22 06:57:53', '2025-12-29 13:21:01'),
(320, 97, 'ACC-PAUM-PAP-ANTIQ', 'paumelles-papillon-antique', 'Paumelles papillon antique (cuivre)', 22, 1850.00, 1500.00, 'Paumelle papillon finition antique, conçue pour les meubles de style classique, rustique ou vintage. Elle apporte un aspect traditionnel et authentique tout en assurant une fixation solide des portes. Sa finition patinée est appréciée pour les projets décoratifs et artisanaux.', '{\"tarifs\": [{\"prix\": 75000, \"type_vente\": \"PV super gros\"}, {\"prix\": 1850, \"type_vente\": \"PV détail\"}], \"conditionnement\": \"Cartons de 50 paires\"}', NULL, '[]', 1, '2025-12-22 06:57:53', '2025-12-29 13:21:01'),
(321, 98, 'QUI-SERR-COURTE-138-22', 'serrure-courte-138-22', 'Serrure courte 138-22', 21, 900.00, 850.00, 'Serrure de meuble courte modèle 138-22, idéale pour armoires et tiroirs. Mécanisme simple garantissant une fermeture sécurisée. Fabriquée en métal nickelé, elle résiste à l\'usure et à la corrosion, convenant à un usage intérieur en menuiserie.', '{\"tarifs\": [{\"prix\": 10200, \"type_vente\": \"PV super gros\"}, {\"prix\": 900, \"type_vente\": \"PV détail\"}]}', NULL, '[]', 1, '2025-12-22 06:57:53', '2025-12-29 13:21:01'),
(322, 99, 'QUI-SERR-LONGUE-139-32', 'serrure-longue-139-32', 'Serrure longue 139-32', 21, 1000.00, 950.00, 'La serrure de meuble longue modèle 139-32 est idéale pour les armoires et placards. Elle offre une portée de verrouillage profonde et un mécanisme robuste pour une fermeture sécurisée. Fabriquée en métal nickelé, elle résiste à l\'usure et à la corrosion, adaptée aux installations intérieures résidentielles et professionnelles.', '{\"tarifs\": [{\"prix\": 11400, \"type_vente\": \"PV super gros\"}, {\"prix\": 1000, \"type_vente\": \"PV détail\"}]}', NULL, '[]', 1, '2025-12-22 06:57:53', '2025-12-29 13:21:01'),
(323, 100, 'QUI-VERROU-PORTE-COUL', 'verrou-de-porte-coulissante', 'Verrou de porte coulissante', 21, 1000.00, 900.00, 'Paumelle papillon blanche à finition mate, conçue pour se fondre discrètement dans les portes et cadres clairs. Elle est particulièrement adaptée aux cuisines, placards et meubles à design épuré. Sa finition peinte protège efficacement le métal pour un usage intérieur standard.', '{\"tarifs\": [{\"prix\": 90000, \"type_vente\": \"PV super gros\"}, {\"prix\": 1000, \"type_vente\": \"PV détail\"}]}', NULL, '[]', 1, '2025-12-22 06:57:53', '2025-12-29 13:21:01'),
(324, 101, 'ACC-LOQUET-MAG', 'loqueteau-magnetique', 'Loqueteau magnétique', 22, 450.00, 250.00, 'Loqueteau magnétique pour maintenir discrètement et efficacement les portes de meubles fermées. Il offre une fermeture douce et silencieuse, tout en permettant une ouverture facile. Adapté aux meubles de cuisine et installations de menuiserie, c\'est une solution simple pour l\'alignement des portes.', '{\"tarifs\": [{\"prix\": 25000, \"type_vente\": \"PV super gros\"}, {\"prix\": 450, \"type_vente\": \"PV détail\"}]}', NULL, '[]', 1, '2025-12-22 06:57:53', '2025-12-29 13:21:01'),
(325, 102, 'ACC-CROCHET-OR', 'crochet-mural-or', 'Crochet mural or', 22, 1300.00, 1000.00, 'Mural en crochet conçu pour ranger et suspendre des objets quotidiens comme vêtements, sacs, serviettes ou accessoires. Sa structure en métal assure résistance et sa finition dorée ajoute une touche décorative. Idéal pour les chambres, cuisines, salles de bain ou autres espaces.', '{\"tarifs\": [{\"prix\": 50000, \"type_vente\": \"PV super gros\"}, {\"prix\": 1300, \"type_vente\": \"PV détail\"}]}', NULL, '[]', 1, '2025-12-22 06:57:53', '2025-12-29 13:21:01'),
(326, 103, 'ACC-CROCHET-NOIR-MATT', 'crochet-mural-noir-matt', 'Crochet mural noir matt', 22, 1300.00, 1000.00, 'Mural en crochet noir mat, idéal pour ranger et suspendre des objets quotidiens comme vêtements et accessoires. Son design moderne s\'intègre bien aux intérieurs contemporains. Fabriqué en métal durable, il garantit longévité et fiabilité dans les milieux résidentiels et professionnels.', '{\"tarifs\": [{\"prix\": 50000, \"type_vente\": \"PV super gros\"}, {\"prix\": 1300, \"type_vente\": \"PV détail\"}]}', NULL, '[]', 1, '2025-12-22 06:57:53', '2025-12-29 13:21:01'),
(327, 104, 'ACC-POIG-BOUT-OR-ROND', 'poignee-bouton-or-rond', 'Poignée bouton or rond', 22, 1000.00, 960.00, 'POIGNÉE bouton en finition dorée, idéal pour portes et tiroirs de meubles comme armoires et commodes. Sa forme ronde assure une prise confortable, et la finition texturée ajoute une touche moderne. Fabriqué en métal robuste, il est durable et convient aux projets résidentiels et professionnels.', '{\"tarifs\": [{\"prix\": 48000, \"type_vente\": \"PV super gros\"}, {\"prix\": 1000, \"type_vente\": \"PV détail\"}]}', NULL, '[]', 1, '2025-12-22 06:57:53', '2025-12-29 13:21:01'),
(328, 105, 'ACC-POIG-BOUT-ARGENT-6C', 'poignees-bouton-argent-6c', 'Poignées bouton argent 6-C', 22, 1000.00, 960.00, 'Bouton de POIGNÉE argenté, modèle 6-C, conçu pour portes et tiroirs de meubles (placards, armoires, cuisine). Sa forme géométrique moderne assure une prise en main confortable. Fabriqué en métal résistant, il offre un design sobre et contemporain, adapté aux espaces résidentiels et professionnels.', '{\"tarifs\": [{\"prix\": 48000, \"type_vente\": \"PV super gros\"}, {\"prix\": 1000, \"type_vente\": \"PV détail\"}]}', NULL, '[]', 1, '2025-12-22 06:57:53', '2025-12-29 13:21:01'),
(329, 106, 'ACC-POIG-BOUT-NOIR-6C', 'poignee-bouton-noir-6c', 'Poignée bouton noir 6-C', 22, 1000.00, 960.00, 'LA POIGNÉE bouton 6-C, en finition noire mate, est conçu pour les portes et tiroirs de meubles. Sa forme géométrique offre un style moderne et épuré, s\'intégrant bien aux aménagements contemporains et industriels. Fabriqué en métal résistant, il garantit confort et durabilité.', '{\"tarifs\": [{\"prix\": 48000, \"type_vente\": \"PV super gros\"}, {\"prix\": 1000, \"type_vente\": \"PV détail\"}]}', NULL, '[]', 1, '2025-12-22 06:57:53', '2025-12-29 13:21:01'),
(330, 107, 'ACC-POIG-BOUT-OR-6C', 'poignee-bouton-or-6c', 'Poignée bouton or 6-C', 22, 1000.00, 960.00, 'LA POIGNÉE bouton finition or, modèle 6-C, est conçu pour portes et tiroirs de meubles. Avec sa forme géométrique moderne et sa finition dorée, il ajoute une touche élégante aux intérieurs. Fabriqué en métal robuste, il assure une bonne prise en main et une durabilité, s\'intégrant dans des meubles contemporains.', '{\"tarifs\": [{\"prix\": 48000, \"type_vente\": \"PV super gros\"}, {\"prix\": 1000, \"type_vente\": \"PV détail\"}]}', NULL, '[]', 1, '2025-12-22 06:57:53', '2025-12-29 13:21:01'),
(331, 108, 'ACC-POIG-VERRE-NOIR', 'poignee-meuble-porte-en-verre-noir', 'Poignée meuble porte en verre – finition noir', 22, 1200.00, 700.00, 'Cette charnière à pince pour porte en verre est conçue pour les meubles vitrés (cuisines, vitrines, meubles TV, dressings). Elle permet de fixer et d\'articuler une porte en verre sans perçage, grâce à un système de serrage sécurisé. Sa finition noir mat apporte un rendu moderne et discret, parfaitement adapté aux aménagements contemporains.', '{\"tarifs\": [{\"dimension\": \"7,5 cm\", \"pv_detail\": 1200, \"pv_super_gros\": 70000}, {\"dimension\": \"10,5 cm\", \"pv_detail\": 1300, \"pv_super_gros\": 60000}]}', NULL, '[]', 1, '2025-12-22 06:57:53', '2025-12-29 13:21:01'),
(332, 109, 'ACC-POIG-VERRE-OR', 'poignee-meuble-porte-en-verre-or', 'Poignée meuble porte en verre – finition or', 22, 1200.00, 700.00, 'Charnière à pince pour porte en verre avec finition OR, idéale pour les meubles à forte valeur esthétique. Elle assure une fixation fiable du verre et une ouverture fluide, sans fragiliser le panneau. Sa finition dorée apporte une touche premium, très appréciée pour les vitrines, cuisines modernes et meubles décoratifs.', '{\"tarifs\": [{\"dimension\": \"7,5 cm\", \"pv_detail\": 1200, \"pv_super_gros\": 70000}, {\"dimension\": \"10,5 cm\", \"pv_detail\": 1300, \"pv_super_gros\": 60000}]}', NULL, '[]', 1, '2025-12-22 06:57:53', '2025-12-29 13:21:01'),
(333, 110, 'ACC-POIG-D-CARRE-NOIR-DORE', 'poignees-d-carre-noir-dore', 'Poignées D carré – noir doré', 22, 1000.00, 760.00, 'Les POIGNÉES D Carré Noir Doré ajoutent une touche contemporaine et élégante aux meubles de cuisine, armoires et dressings. Leur forme carrée offre une bonne prise en main, tandis que le contraste noir et doré est apprécié dans les intérieurs modernes. Fabriqués en aluminium, ils sont durables et résistants à l\'usure pour un usage quotidien.', '{\"tarifs\": [{\"dimension\": \"11,5 cm\", \"pv_detail\": 1000, \"pv_super_gros\": 38000}, {\"dimension\": \"15 cm\", \"pv_detail\": 1100, \"pv_super_gros\": 42000}, {\"dimension\": \"18 cm\", \"pv_detail\": 1300, \"pv_super_gros\": 76000}]}', NULL, '[]', 1, '2025-12-22 06:57:53', '2025-12-29 13:21:01'),
(334, 111, 'ACC-POIG-ENCAST-ALU', 'poignees-encastrees-aluminium', 'Poignées encastrées aluminium – design profile', 22, 1000.00, 1000.00, 'Les poignées encastrées en aluminium s\'intègrent dans le panneau, offrant un design moderne et discret, idéal pour les meubles contemporains. Fabriquées en aluminium, elles sont résistantes, durables et confortables à utiliser. Leur conception limite les chocs et facilite le nettoyage, renforçant l\'esthétique minimaliste. Disponibles en plusieurs longueurs (10,5 cm et 14 cm) et finitions (Noir, Gris, Or) pour s\'adapte à des styles de mobilier.', '{\"tarifs\": [{\"longueur\": \"10,5 cm\", \"pv_carton\": 50000, \"designation\": \"Poignets Encastrés Noir\", \"pv_unitaire\": 1000}, {\"longueur\": \"14 cm\", \"pv_carton\": 70000, \"designation\": \"Poignets Encastrés Noir\", \"pv_unitaire\": 2000}, {\"longueur\": \"10,5 cm\", \"pv_carton\": 100000, \"designation\": \"Poignets Encastrés Gris\", \"pv_unitaire\": 1700}, {\"longueur\": \"14 cm\", \"pv_carton\": 73000, \"designation\": \"Poignets Encastrés Gris\", \"pv_unitaire\": 1500}, {\"longueur\": \"10,5 cm\", \"pv_carton\": 118000, \"designation\": \"Poignets Encastrés Or\", \"pv_unitaire\": 2000}, {\"longueur\": \"14 cm\", \"pv_carton\": 148000, \"designation\": \"Poignets Encastrés Or\", \"pv_unitaire\": 3000}]}', NULL, '[]', 1, '2025-12-22 06:57:53', '2025-12-29 13:21:01'),
(335, 112, 'ACC-POIG-ENCAST-PLAT', 'poignees-encastrees-plates-aluminium', 'Poignées encastrées plates aluminium – ligne contemporaine', 22, 1300.00, 955.00, 'Les poignées encastrées plates en aluminium offrent une intégration élégante et moderne dans les meubles. Leur design plat convient aux cuisines, dressings et rangements contemporains. Fabriquées en aluminium robuste, elles garantissent résistance et confort, tout en éliminant les saillies pour réduire les chocs. Disponibles en 20 cm et 23 cm, avec diverses finitions (Noir, Gris, Beige brillant), elles s\'adaptent à des styles minimalistes ou luxueux.', '{\"tarifs\": [{\"finition\": \"Beige brillant\", \"longueur\": \"23 cm\", \"pv_carton\": 95500, \"designation\": \"Poignets Encastré Plat\", \"pv_unitaire\": 1300}, {\"finition\": \"Noir mat\", \"longueur\": \"20 cm\", \"pv_carton\": 148000, \"designation\": \"Poignets Encastré Plat\", \"pv_unitaire\": 1500}, {\"finition\": \"Gris aluminium\", \"longueur\": \"20 cm\", \"pv_carton\": 168000, \"designation\": \"Poignets Encastré Plat\", \"pv_unitaire\": 1700}, {\"finition\": \"Noir mat\", \"longueur\": \"23 cm\", \"pv_carton\": 110500, \"designation\": \"Poignets Encastré Noir\", \"pv_unitaire\": 1500}]}', NULL, '[]', 1, '2025-12-22 06:57:53', '2025-12-29 13:21:01'),
(336, 113, 'ACC-GALETS-PORTE', 'galets-de-porte-coulissante', 'Galets de porte coulissante (modèles 038 / 039 / 040 / 041)', 22, 400.00, 300.00, 'Les galets de porte coulissante assurent un guidage fluide et silencieux pour les portes en bois, aluminium ou légères. Utilisés dans placards et vitrines, chaque galet combine un support métallique robuste et une roue en nylon durable, offrant stabilité et réduction des frottements. Leur conception compacte facilite l\'intégration dans divers systèmes, adaptés aux applications résidentielles et professionnelles.', '{\"tarifs\": [{\"pv_gros\": 30000, \"pv_detail\": 400, \"reference\": \"38\", \"designation\": \"Galet 038\"}, {\"pv_gros\": 25000, \"pv_detail\": 350, \"reference\": \"39\", \"designation\": \"Galet 039\"}, {\"pv_gros\": 40000, \"pv_detail\": 500, \"reference\": \"40\", \"designation\": \"Galet 040\"}, {\"pv_gros\": 40000, \"pv_detail\": 500, \"reference\": \"41\", \"designation\": \"Galet 041\"}], \"materiaux\": \"support métallique + roue nylon\", \"applications\": \"portes coulissantes de meubles, placards, vitrines\", \"conditionnement\": \"Selon lot fournisseur\"}', NULL, '[]', 1, '2025-12-22 06:57:53', '2025-12-29 13:21:01'),
(337, 114, 'ACC-PASSE-CABLES', 'passe-cables-de-meubles', 'Passe-câbles de meubles (50 mm & 60 mm)', 22, 500.00, 480.00, 'Ces passe-câbles assurent un passage propre et discret des câbles à travers les meubles, réduisant l\'encombrement visuel et protégeant les câbles. Fabriqués en plastique rigide de qualité, ils résistent à l\'usage quotidien et se fixent facilement grâce à leur capot amovible. Disponibles en plusieurs diamètres et couleurs, ils s\'adaptent à tout style de mobilier.', '{\"tarifs\": [{\"pv_gros\": 48000, \"pv_detail\": 500, \"reference\": \"PC-50-N\", \"designation\": \"Passe-câbles Noir – Ø 50 mm\"}, {\"pv_gros\": 48000, \"pv_detail\": 500, \"reference\": \"PC-50-B\", \"designation\": \"Passe-câbles Blanc / Noir – Ø 50 mm\"}, {\"pv_gros\": 78000, \"pv_detail\": 800, \"reference\": \"PC-60-BN\", \"designation\": \"Passe-câbles Blanc / Noir – Ø 60 mm\"}, {\"pv_gros\": 78000, \"pv_detail\": 800, \"reference\": \"PC-60-N\", \"designation\": \"Passe-câbles Noir – Ø 60 mm\"}]}', NULL, '[]', 1, '2025-12-22 06:57:53', '2025-12-29 13:21:01'),
(338, 115, 'ACC-ARRET-PORTE-MAG', 'arret-de-porte-magnetique', 'Arrêt de porte magnétique', 22, 3000.00, 0.00, 'L\'arrêt de porte magnétique est un accessoire discret et robuste conçu pour maintenir efficacement les portes en position ouverte. Grâce à son système magnétique puissant, il évite les claquements involontaires, protège les murs et prolonge la durée de vie des portes. Son design soigné et sa finition métallique lui permettent de s\'intégrer aussi bien dans les intérieurs modernes que classiques. Idéal pour les portes de cuisine, de bureau, de chambre ou d\'espaces commerciaux.', '{\"tarifs\": [{\"finition\": \"Gris\", \"pv_detail\": 3000}, {\"finition\": \"Bronzé\", \"pv_detail\": 3000}]}', NULL, '[]', 1, '2025-12-22 06:57:53', '2025-12-29 13:21:01'),
(339, 116, 'QUI-SERR-COMP-PORTE', 'serrure-complete-pour-porte', 'Serrure complète pour porte', 21, 10000.00, 0.00, 'La serrure pour porte avec poignée plate intégrée est une solution élégante et fonctionnelle pour portes intérieures modernes. Elle combine sécurité confort et esthétique épurée avec un design minimaliste. Idéale pour chambres, bureaux ou espaces professionnels, elle s\'intègre parfaitement aux portes en bois ou panneaux composites, assurant une manipulation fluide.', '{\"tarifs\": [{\"prix\": 12000, \"designation\": \"POIGNET PLAT\"}, {\"prix\": 10000, \"designation\": \"Poignet Rectangle\"}]}', NULL, '[]', 1, '2025-12-22 06:57:53', '2025-12-29 13:21:01'),
(340, 117, 'ACC-PATINS-MOYEN', 'patins-moyen', 'Patins moyen Ø 22 mm', 22, 100.00, 96.00, 'Les patins Moyen 22 protègent les sols et améliorent la stabilité des meubles. Placés sous les pieds de mobilier, ils diminuent frottements, bruits et rayures sur les surfaces délicates comme le carrelage ou le parquet. Fabriqués en matériau durable, ils conviennent aux usages domestiques et professionnels.', '{\"tarifs\": [{\"prix\": 48000, \"type_vente\": \"PV super gros\"}, {\"prix\": 100, \"type_vente\": \"PV détail\"}]}', NULL, '[]', 1, '2025-12-22 06:57:53', '2025-12-29 13:21:01'),
(341, 118, 'ACC-EQUERRES-PLAST', 'equerres-plastiques-de-fixation', 'Équerres plastiques de fixation – Angle intérieur', 22, 150.00, 120.00, 'Ces équerres en plastique maintiennent discrètement les angles intérieurs des meubles, stabilisant les assemblages sans alourdir la structure. Durables et légères, elles sont faciles à installer et disponibles en plusieurs coloris (Blanc, Marron, Noir). Leur format compact de 27 mm convient aux meubles de cuisine, dressings et étagères, et elles restent discrètes après montage.', '{\"tarifs\": [{\"prix\": 12000, \"type_vente\": \"PV super gros\"}, {\"prix\": 150, \"type_vente\": \"PV détail\"}]}', NULL, '[]', 1, '2025-12-22 06:57:53', '2025-12-29 13:21:01'),
(342, 119, 'ACC-GARN-004', 'garniture-004', 'Garniture 004 – Ferrure de montage pour lits', 22, 300.00, 266.67, 'La Garniture 004 est une ferrure métallique pour assembler lits en reliant longerons et traverses. Son réglage par tige filetée permet un ajustement précis, idéale pour les lits démontables. Fabriquée en acier galvanisé, elle résiste à l\'usure et aux montages répétés, essentielle pour menuisiers et fabricants de lits.', '{\"tarifs\": [{\"prix\": 8000, \"type_vente\": \"PV gros\"}, {\"prix\": 300, \"type_vente\": \"PV détail\"}]}', NULL, '[]', 1, '2025-12-22 06:57:53', '2025-12-29 13:21:01'),
(343, 120, 'ACC-CROCHET-RANG', 'crochets-de-rangement-metal', 'Crochets de rangement – métal', 22, 2400.00, 0.00, 'Les crochets de rangement métalliques organisent efficacement les espaces en suspendant des objets quotidiens comme vêtements et accessoires. Leur structure assure une bonne capacité de charge. Ils conviennent aux espaces domestiques et professionnels, offrant une solution esthétique pour le rangement vertical. Les versions noires ajoutent une touche moderne, tandis que les modèles standards sont polyvalents.', '{\"tarifs\": [{\"finition\": \"Noir mat\", \"longueur\": \"21 cm\", \"pv_detail\": 2400}, {\"finition\": \"Noir mat\", \"longueur\": \"31 cm\", \"pv_detail\": 3000}, {\"finition\": \"Noir mat\", \"longueur\": \"16,5 cm\", \"pv_detail\": 2100}, {\"finition\": \"Noir mat\", \"longueur\": \"26 cm\", \"pv_detail\": 2700}]}', NULL, '[]', 1, '2025-12-22 06:57:53', '2025-12-29 13:21:01'),
(344, 121, 'ACC-TRINGLE-VET', 'tringles-pour-vetements', 'Tringles pour vêtements – légère & lourde sans support', 22, 2000.00, 0.00, 'La tringle pour vêtements est essentielle pour une rangement ordonné dans les armoires. Fabriquées en métal robuste avec un profil ovale, elles évitent la rotation des cintres. Deux versions sont proposées : Tringle légère pour vêtements standards. Tringle lourde pour charges importantes. Sa finition métallique moderne s\'adapte à différents styles de meubles, et des supports de tringle adaptés sont nécessaires pour le montage.', '{\"tarifs\": [{\"type\": \"Métal ovale\", \"usage\": \"Vêtements standards (chemises, pantalons, robes)\", \"designation\": \"Tringle légère\", \"pv_unitaire\": 2000}, {\"type\": \"Métal ovale renforcé\", \"usage\": \"Vêtements lourds (manteaux, vestes, costumes)\", \"designation\": \"Tringle lourde\", \"pv_unitaire\": 3500}]}', NULL, '[]', 1, '2025-12-22 06:57:53', '2025-12-29 13:21:01'),
(345, 122, 'ACC-BARRE-T', 'barre-en-t-metallique', 'Barre en T métallique – profil / rail d’assemblage et de finition', 22, 2000.00, 0.00, 'Les barres en T métalliques, ou rails en T, assurent des jonctions esthétiques entre panneaux et éléments de mobilier. Elles conviennent aux meubles modernes et aménagements intérieurs, offrant plusieurs avantages : Cacher les joints, Absorber les écarts d\'alignement, Apporter une finition nette. Les variantes GP et CP proposent des finitions métalliques adaptées à différents styles des meubles.', '{\"tarifs\": [{\"type\": \"Barre en T - GP\", \"largeur\": \"8 mm\", \"pv_detail\": 2000}, {\"type\": \"Barre en T - CP\", \"largeur\": \"8 mm\", \"pv_detail\": 2000}, {\"type\": \"Barre en T - GP\", \"largeur\": \"10 mm\", \"pv_detail\": 2500}, {\"type\": \"Barre en T - CP\", \"largeur\": \"10 mm\", \"pv_detail\": 2500}, {\"type\": \"Barre en T - GP\", \"largeur\": \"20 mm\", \"pv_detail\": 3000}, {\"type\": \"Barre en T - CP\", \"largeur\": \"20 mm\", \"pv_detail\": 3000}]}', NULL, '[]', 1, '2025-12-22 06:57:53', '2025-12-29 13:21:01'),
(346, 123, 'ACC-VERIN-GAZ', 'verin-a-gaz-pour-meuble', 'Vérin à gaz / vérin pneumatique pour meuble', 22, 1500.00, 1700.00, 'Les vérins de meuble sont des dispositifs à gaz qui facilitent l\'ouverture, maintiennent la porte ouverte et évitent les fermetures brusques. Ils sont parfaits pour les cuisines, placards et armoires. Grâce à leur mécanisme pneumatique, ils prolongent la durée de vie des charnières et des panneaux. Les petites versions sont idéales pour les portes légères, tandis que les longues conviennent aux portes lourdes.', '{\"tarifs\": [{\"type\": \"Vérin Petit\", \"usage\": \"Portes légères, petits meubles, niches\", \"pv_gros\": 34000, \"pv_detail\": 1500}, {\"type\": \"Vérin Long\", \"usage\": \"Portes larges, meubles hauts, coffres\", \"pv_gros\": 46000, \"pv_detail\": 2000}]}', NULL, '[]', 1, '2025-12-22 06:57:53', '2025-12-29 13:21:01'),
(347, 124, 'ACC-SUPPORT-TRINGLE', 'supports-de-tringle-16mm', 'Supports de tringle – Ø16 mm', 22, 250.00, 230.00, 'Le support de tringle est crucial pour l\'installation des tringles dans les armoires et placards, offrant maintien et stabilité. Fabriqué en alliage de zinc, il est durable et conçu pour des tringles ovales ou rondes de 16 mm. Son design discret s\'intègre harmonieusement dans les meubles, et son montage est simple grâce aux points de fixation.', '{\"tarifs\": [{\"prix\": 23000, \"type_vente\": \"PV gros\"}, {\"prix\": 250, \"type_vente\": \"PV détail\"}]}', NULL, '[]', 1, '2025-12-22 06:57:53', '2025-12-29 13:21:01'),
(348, 125, 'ACC-BANDE-DECO-ALU', 'bande-decorative-aluminium', 'Bande décorative aluminium – 16 mm & 18 mm', 22, 4500.00, 0.00, 'La bande décorative en aluminium est un accessoire de finition pour la menuiserie moderne, améliorant l\'esthétique des meubles comme les cuisines et placards. Elle offre un design élégant en façade ou comme séparation visuelle. Fabriquée en aluminium extrudé, elle est durable, résistante aux chocs, et disponible en finition dorée ou métallique, créant un contraste premium avec les surfaces en bois.', '{\"tarifs\": [{\"prix\": 4500, \"largeur\": \"16mm\"}, {\"prix\": 6000, \"largeur\": \"18mm\"}]}', NULL, '[]', 1, '2025-12-22 06:57:53', '2025-12-29 13:21:01'),
(349, 126, 'ACC-BUTEE-PORTE', 'butee-de-porte-au-sol', 'Butée de porte au sol – 16 mm (gris & noir)', 22, 1500.00, 0.00, 'La butée de porte au sol de 16 mm protège les portes, murs et meubles des chocs tout en réduisant le bruit grâce à un tampon amortisseur. En acier inoxydable, elle est résistante à l\'usure et aux chocs, avec un design compact pour les intérieurs modernes. Facile à installer, elle convient aux portes intérieures et aux meubles.', '{\"tarifs\": [{\"prix\": 1500, \"couleur\": \"Noir\"}, {\"prix\": 1500, \"couleur\": \"Argent\"}]}', NULL, '[]', 1, '2025-12-22 06:57:53', '2025-12-29 13:21:01'),
(350, 127, 'ACC-POIG-LINEAIRE', 'poignees-lineaires-aluminium-1m', 'Poignées linéaires aluminium – 1 mètre', 22, 10000.00, 0.00, 'Les poignées linéaires de 1 mètre conviennent aux portes hautes d\'armoires et de meubles modernes. Leur design allongé offre une prise confortable et un style contemporain. Fabriquées en aluminium rigide, elles sont durables, même pour des portes lourdes. La version noire est élégante. La version beige brillant apporte une touche lumineuse idéale pour des meubles clairs.', '{\"tarifs\": [{\"couleur\": \"Noir\", \"pv_detail\": 10000}, {\"couleur\": \"Beige brillant\", \"pv_detail\": 10000}]}', NULL, '[]', 1, '2025-12-22 06:57:53', '2025-12-29 13:21:01'),
(351, 128, 'QUI-VERROU-ANTI-VOL', 'verrou-anti-vol-pour-porte-et-meuble', 'Verrou anti-vol pour porte et meuble', 21, 2500.00, 0.00, 'Ce verrou anti-vol à chaîne offre une sécurité renforcée pour portes, fenêtres et meubles. Fabriqué en acier robuste, il empêche les intrusions même si la porte est entrouverte. Sa chaîne métallique renforcée résiste à l\'arrachement, idéal pour divers espaces. L\'installation est simple et son design s\'intègre facilement dans différents environnements.', '{\"tarifs\": [{\"prix\": 2500, \"type_vente\": \"PV détail\"}]}', NULL, '[]', 1, '2025-12-22 06:57:53', '2025-12-29 13:21:01'),
(352, 129, 'ACC-BRAS-PORTANT', 'bras-portant-gris', 'Bras portant gris (support de présentation)', 22, 1500.00, 0.00, 'Le bras portant gris est un accessoire métallique conçu pour la présentation et le rangement dans divers environnements, comme les dressings et boutiques. Fabriqué en métal robuste, il supporte bien les charges avec stabilité. Sa finition grise s\'adapte aux espaces domestiques et commerciaux, et il se fixe au mur avec un angle incliné pour améliorer la visibilité des articles suspendus.', '{\"tarifs\": [{\"prix\": 1500, \"type_vente\": \"PV détail\"}]}', NULL, '[]', 1, '2025-12-22 06:57:53', '2025-12-29 13:21:01'),
(353, 130, 'ACC-POIG-BATONS', 'poignees-batons', 'Poignées bâtons', 22, 2300.00, 0.00, 'Les poignées modernes, parfaites pour les cuisines et meubles contemporains, offrent une prise confortable grâce à leur forme allongée. Leur noir mat et les extrémités métalliques ajoutent une touche d\'élégance. Elles conviennent aux projets résidentiels haut de gamme, avec une installation facile et une durabilité élevée.', '{\"tarifs\": [{\"prix\": 2300, \"longueur\": \"21 cm\"}, {\"prix\": 1500, \"longueur\": \"19.5 cm\"}, {\"prix\": 3000, \"longueur\": \"23 cm\"}]}', NULL, '[]', 1, '2025-12-22 06:57:53', '2025-12-29 13:21:01'),
(354, 131, 'ACC-ANGLE-WPC', 'angle-corniere-wpc-exterieur', 'Angle / Cornière WPC extérieur', 22, 13000.00, 15000.00, 'Cette cornière d\'angle en WPC (Wood Plastic Composite) est un profilé de finition conçu spécialement pour les aménagements extérieurs : terrasses, bardages, habillages muraux et contours de panneaux composites. Fabriquée à partir d\'un mélange de fibres de bois et de plastique, elle offre une excellente résistance aux intempéries, à l\'humidité, aux UV et aux variations de température, tout en conservant un aspect élégant et moderne. Sa finition noire / anthracite, avec un grain bois discret, permet d\'obtenir des angles nets et professionnels, tout en masquant les coupes et jonctions des panneaux WPC. La pose est simple et rapide : collage ou vissage, avec des outils standards de menuiserie. Produit idéal pour des finitions durables, propres et esthétiques en extérieur, sans entretien lourd.', '{\"tarifs\": [{\"pv_pro\": 15000, \"pv_detail\": 13000}]}', NULL, '[]', 1, '2025-12-22 06:57:53', '2025-12-29 13:21:01'),
(355, 132, 'FIN-CHANT-PVC-BRILL', 'chant-pvc-brillant-or-et-argent', 'Chant PVC brillant – Or & Argent', 24, 1000.00, 800.00, 'Le chant PVC brillant couleur Or et Argent est un produit décoratif haut de gamme, parfait pour meubles premium, cuisines modernes, aménagements intérieurs élégants.', '{\"tarifs\": [{\"prix\": 800, \"type_vente\": \"PV super gros\"}, {\"prix\": 1000, \"type_vente\": \"PV détail\"}]}', NULL, '[]', 1, '2025-12-22 06:57:53', '2025-12-29 13:21:01'),
(356, 133, 'PLQ-CP-MELAMINE', 'cp-melamines', 'CP Mélaminés', 19, 0.00, 0.00, 'Contreplaqués mélaminés de différentes couleurs et épaisseurs pour menuiserie.', '{\"tarifs\": [{\"epaisseur\": \"18 mm\", \"designation\": \"CPM Gris sombre(2f)\", \"prix_panneau\": 45000, \"prix_unitaire\": 40000}, {\"epaisseur\": \"18 mm\", \"designation\": \"CPM Chêne blanc(2f)\", \"prix_panneau\": 45000, \"prix_unitaire\": 40000}, {\"epaisseur\": \"18 mm\", \"designation\": \"CPM Gris clair(2f)\", \"prix_panneau\": 45000, \"prix_unitaire\": 40000}, {\"epaisseur\": \"18 mm\", \"designation\": \"CPM Bilinga(2f)\", \"prix_panneau\": 45000, \"prix_unitaire\": 40000}, {\"epaisseur\": \"18 mm\", \"designation\": \"CPM Chaleur(2f)\", \"prix_panneau\": 45000, \"prix_unitaire\": 40000}, {\"epaisseur\": \"18 mm\", \"designation\": \"CPM Wengué(2f)\", \"prix_panneau\": 45000, \"prix_unitaire\": 40000}, {\"epaisseur\": \"18 mm\", \"designation\": \"CPM Vert fle(2f)\", \"prix_panneau\": 45000, \"prix_unitaire\": 40000}, {\"epaisseur\": \"2 mm\", \"designation\": \"CPM (1f) 2440 × 2 mm\", \"prix_panneau\": 9000, \"prix_unitaire\": 11000}, {\"epaisseur\": \"18mm\", \"designation\": \"Noir /Blanc gloss(2f)2440 × 18 mm\", \"prix_panneau\": 63000, \"prix_unitaire\": 65000}]}', NULL, '[]', 1, '2025-12-22 06:57:53', '2025-12-29 13:21:01'),
(357, 134, 'FIN-COLLE-THERMO', 'colle-thermofusible', 'Colle thermofusible (Hot Melt Glue)', 24, 65000.00, 61750.00, 'La colle thermofusible est un adhésif professionnel utilisé en menuiserie pour la pose de bandes de chant mélaminées et le collage rapide de composants en bois. Elle durcit rapidement après fusion à chaud, offrant prise immédiate, excellente adhérence sur bois, CPM, MDF, bonne tenue mécanique et thermique. Adaptée aux plaqueuses de chants et aux ateliers de fabrication de meubles, ses applications courantes comprennent pose de bandes de chant, assemblage rapide de panneaux, travaux de finition en menuiserie. Conditionnée en seau ou bidon industriel pour un usage professionnel.', '{\"usage\": \"Bandes de chant & menuiserie\"}', NULL, '[]', 1, '2025-12-22 06:57:53', '2025-12-29 13:21:01');
INSERT INTO `catalogue_produits` (`id`, `produit_id`, `code`, `slug`, `designation`, `categorie_id`, `prix_unite`, `prix_gros`, `description`, `caracteristiques_json`, `image_principale`, `galerie_images`, `actif`, `created_at`, `updated_at`) VALUES
(358, 135, 'PLQ-DECOR-PVC', 'decoration-pvc', 'Décoration PVC', 19, 0.00, 0.00, 'Divers panneaux PVC et WPC pour décoration et habillage.', '{\"tarifs\": [{\"prix1\": 38000, \"prix2\": 40000, \"designation\": \"PVC acoustique - 600 x 3000 x 21 mm\"}, {\"prix1\": 12500, \"prix2\": 13000, \"designation\": \"Cacao des marins- 147 x 17 x 3050 mm\"}, {\"prix1\": 11500, \"prix2\": 12000, \"designation\": \"Gris gentleman - 130 x 12 x 2750 mm\"}, {\"prix1\": 12500, \"prix2\": 13000, \"designation\": \"Gris caliphorien- 147 x 17 x 3050 mm\"}, {\"prix1\": 12500, \"prix2\": 13000, \"designation\": \"Chaîne fumé - 147 x 12 x 3050 mm\"}, {\"prix1\": 28000, \"prix2\": 30000, \"designation\": \"Panneau WPC extérieur - 219 x 26 x 3050 mm\"}, {\"prix1\": 18000, \"prix2\": 20000, \"designation\": \"WPC café 156*31*3000mm\"}, {\"prix1\": 18000, \"prix2\": 20000, \"designation\": \"WPC café 219*31*3000mm\"}]}', NULL, '[]', 1, '2025-12-22 06:57:53', '2025-12-29 13:21:01'),
(359, 136, 'FIN-BANDE-CHANT-MELAM', 'bandes-de-chant-melaminees', 'Bandes de chant mélaminées', 24, 0.00, 0.00, 'Les bandes de chant mélaminées sont utilisées pour la finition des bords des panneaux mélaminés, CPM et MDF. Elles permettent de protéger les arêtes, d\'améliorer la durabilité des meubles et d\'assurer une finition esthétique homogène, parfaitement assortie au panneau. Adaptées aux meubles de cuisine, dressing, armoires, bureaux et rangements, ces bandes se posent par encollage à chaud (colle hot melt), manuellement ou à la plaqueuse. Disponibles dans une large gamme de couleurs standards, elles s\'intègrent facilement à tous les styles de menuiserie : moderne, classique ou contemporain.', '{\"couleurs\": \"Vert fleuri · Noir marbré · Noir gloss · Chaîne beige · Bilinga lumineux · Bilinga · Chaleur · Blanc mat · Padouk · Chaîne blanc · Chaleur sombre · Bilinga marbré · Gris clair · Noir mat · Wengué marron\", \"prix_metre\": 300, \"conditionnement\": \"Rouleaux de 100 mètres / 200 mètres\"}', NULL, '[]', 1, '2025-12-22 06:57:53', '2025-12-29 13:21:01');

-- --------------------------------------------------------

--
-- Table structure for table `chambres`
--

CREATE TABLE `chambres` (
  `id` int UNSIGNED NOT NULL,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `tarif_nuite` decimal(15,2) NOT NULL DEFAULT '0.00',
  `actif` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

CREATE TABLE `clients` (
  `id` int UNSIGNED NOT NULL,
  `nom` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type_client_id` int UNSIGNED NOT NULL,
  `telephone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `adresse` text COLLATE utf8mb4_unicode_ci,
  `source` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `statut` enum('PROSPECT','CLIENT','APPRENANT','HOTE') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PROSPECT',
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `compta_comptes`
--

CREATE TABLE `compta_comptes` (
  `id` int UNSIGNED NOT NULL,
  `numero_compte` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `libelle` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `classe` char(1) COLLATE utf8mb4_unicode_ci NOT NULL,
  `est_analytique` tinyint(1) DEFAULT '0',
  `compte_parent_id` int UNSIGNED DEFAULT NULL,
  `type_compte` enum('ACTIF','PASSIF','CHARGE','PRODUIT','MIXTE','ANALYTIQUE') COLLATE utf8mb4_unicode_ci DEFAULT 'ACTIF',
  `nature` enum('CREANCE','DETTE','STOCK','IMMOBILISATION','TRESORERIE','VENTE','CHARGE_VARIABLE','CHARGE_FIXE','AUTRE') COLLATE utf8mb4_unicode_ci DEFAULT 'AUTRE',
  `est_actif` tinyint(1) DEFAULT '1',
  `observations` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `compta_comptes`
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
-- Table structure for table `compta_ecritures`
--

CREATE TABLE `compta_ecritures` (
  `id` int UNSIGNED NOT NULL,
  `piece_id` int UNSIGNED NOT NULL,
  `compte_id` int UNSIGNED NOT NULL,
  `libelle_ecriture` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `debit` decimal(15,2) DEFAULT '0.00',
  `credit` decimal(15,2) DEFAULT '0.00',
  `tiers_client_id` int UNSIGNED DEFAULT NULL,
  `tiers_fournisseur_id` int UNSIGNED DEFAULT NULL,
  `centre_analytique_id` int UNSIGNED DEFAULT NULL,
  `ordre_ligne` int DEFAULT NULL,
  `observations` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `compta_exercices`
--

CREATE TABLE `compta_exercices` (
  `id` int UNSIGNED NOT NULL,
  `annee` int NOT NULL,
  `date_ouverture` date NOT NULL,
  `date_cloture` date DEFAULT NULL,
  `est_clos` tinyint(1) DEFAULT '0',
  `observations` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
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
  `id` int UNSIGNED NOT NULL,
  `code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `libelle` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('VENTE','ACHAT','TRESORERIE','OPERATION_DIVERSE','PAIE') COLLATE utf8mb4_unicode_ci DEFAULT 'OPERATION_DIVERSE',
  `compte_contre_partie` int UNSIGNED DEFAULT NULL,
  `observations` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `compta_mapping_operations`
--

CREATE TABLE `compta_mapping_operations` (
  `id` int UNSIGNED NOT NULL,
  `source_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code_operation` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `journal_id` int UNSIGNED NOT NULL,
  `compte_debit_id` int UNSIGNED DEFAULT NULL,
  `compte_credit_id` int UNSIGNED DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `actif` tinyint(1) DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `compta_operations_trace`
--

CREATE TABLE `compta_operations_trace` (
  `id` int UNSIGNED NOT NULL,
  `source_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `source_id` int UNSIGNED NOT NULL,
  `piece_id` int UNSIGNED DEFAULT NULL,
  `status` enum('success','error','en_attente') COLLATE utf8mb4_unicode_ci DEFAULT 'en_attente',
  `messages` text COLLATE utf8mb4_unicode_ci,
  `executed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `compta_pieces`
--

CREATE TABLE `compta_pieces` (
  `id` int UNSIGNED NOT NULL,
  `exercice_id` int UNSIGNED NOT NULL,
  `journal_id` int UNSIGNED NOT NULL,
  `numero_piece` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `libelle` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_piece` date NOT NULL,
  `reference_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_id` int UNSIGNED DEFAULT NULL,
  `tiers_client_id` int UNSIGNED DEFAULT NULL,
  `tiers_fournisseur_id` int UNSIGNED DEFAULT NULL,
  `observations` text COLLATE utf8mb4_unicode_ci,
  `utilisateur_id` int UNSIGNED DEFAULT NULL,
  `est_validee` tinyint(1) DEFAULT '0',
  `validee_par_id` int UNSIGNED DEFAULT NULL,
  `date_validation` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `connexions_utilisateur`
--

CREATE TABLE `connexions_utilisateur` (
  `id` int UNSIGNED NOT NULL,
  `utilisateur_id` int UNSIGNED NOT NULL,
  `date_connexion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `adresse_ip` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `succes` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `conversions_pipeline`
--

CREATE TABLE `conversions_pipeline` (
  `id` int UNSIGNED NOT NULL,
  `source_type` enum('SHOWROOM','TERRAIN','DIGITAL') COLLATE utf8mb4_unicode_ci NOT NULL,
  `source_id` int UNSIGNED NOT NULL COMMENT 'ID visiteur/prospection/lead',
  `client_id` int UNSIGNED NOT NULL,
  `date_conversion` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `canal_vente_id` int UNSIGNED DEFAULT NULL,
  `devis_id` int UNSIGNED DEFAULT NULL,
  `vente_id` int UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `devis`
--

CREATE TABLE `devis` (
  `id` int UNSIGNED NOT NULL,
  `numero` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_devis` date NOT NULL,
  `client_id` int UNSIGNED NOT NULL,
  `canal_vente_id` int UNSIGNED NOT NULL,
  `statut` enum('EN_ATTENTE','ACCEPTE','REFUSE','ANNULE') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'EN_ATTENTE',
  `est_converti` tinyint(1) NOT NULL DEFAULT '0',
  `date_relance` date DEFAULT NULL,
  `utilisateur_id` int UNSIGNED NOT NULL,
  `montant_total_ht` decimal(15,2) NOT NULL DEFAULT '0.00',
  `montant_total_ttc` decimal(15,2) NOT NULL DEFAULT '0.00',
  `remise_global` decimal(15,2) NOT NULL DEFAULT '0.00',
  `conditions` text COLLATE utf8mb4_unicode_ci,
  `commentaires` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `devis_lignes`
--

CREATE TABLE `devis_lignes` (
  `id` int UNSIGNED NOT NULL,
  `devis_id` int UNSIGNED NOT NULL,
  `produit_id` int UNSIGNED NOT NULL,
  `quantite` int NOT NULL,
  `prix_unitaire` decimal(15,2) NOT NULL,
  `remise` decimal(15,2) NOT NULL DEFAULT '0.00',
  `montant_ligne_ht` decimal(15,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `familles_produits`
--

CREATE TABLE `familles_produits` (
  `id` int UNSIGNED NOT NULL,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `familles_produits`
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
(73, 'Accessoires'),
(74, 'Machines & Outils'),
(75, 'Accessoires Menuiserie'),
(76, 'Finitions & Vernis'),
(77, 'Panneaux & Contreplaqués');

-- --------------------------------------------------------

--
-- Table structure for table `formations`
--

CREATE TABLE `formations` (
  `id` int UNSIGNED NOT NULL,
  `nom` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `tarif_total` decimal(15,2) NOT NULL DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fournisseurs`
--

CREATE TABLE `fournisseurs` (
  `id` int UNSIGNED NOT NULL,
  `nom` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telephone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `adresse` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inscriptions_formation`
--

CREATE TABLE `inscriptions_formation` (
  `id` int UNSIGNED NOT NULL,
  `date_inscription` date NOT NULL,
  `apprenant_nom` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `client_id` int UNSIGNED DEFAULT NULL,
  `formation_id` int UNSIGNED NOT NULL,
  `montant_paye` decimal(15,2) NOT NULL DEFAULT '0.00',
  `solde_du` decimal(15,2) NOT NULL DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `journal_caisse`
--

CREATE TABLE `journal_caisse` (
  `id` int UNSIGNED NOT NULL,
  `date_operation` date NOT NULL,
  `numero_piece` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nature_operation` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `client_id` int UNSIGNED DEFAULT NULL,
  `fournisseur_id` int UNSIGNED DEFAULT NULL,
  `sens` enum('RECETTE','DEPENSE') COLLATE utf8mb4_unicode_ci NOT NULL,
  `montant` decimal(15,2) NOT NULL,
  `mode_paiement_id` int UNSIGNED NOT NULL,
  `vente_id` int UNSIGNED DEFAULT NULL,
  `reservation_id` int UNSIGNED DEFAULT NULL,
  `inscription_formation_id` int UNSIGNED DEFAULT NULL,
  `responsable_encaissement_id` int UNSIGNED NOT NULL,
  `observations` text COLLATE utf8mb4_unicode_ci,
  `est_annule` tinyint(1) NOT NULL DEFAULT '0',
  `date_annulation` datetime DEFAULT NULL,
  `annule_par_id` int UNSIGNED DEFAULT NULL,
  `type_operation` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `kpis_quotidiens`
--

CREATE TABLE `kpis_quotidiens` (
  `id` int UNSIGNED NOT NULL,
  `date` date NOT NULL,
  `canal` enum('SHOWROOM','TERRAIN','DIGITAL','HOTEL','FORMATION','GLOBAL') COLLATE utf8mb4_unicode_ci NOT NULL,
  `nb_visiteurs` int DEFAULT '0',
  `nb_leads` int DEFAULT '0',
  `nb_devis` int DEFAULT '0',
  `nb_ventes` int DEFAULT '0',
  `ca_realise` decimal(15,2) DEFAULT '0.00',
  `date_maj` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leads_digital`
--

CREATE TABLE `leads_digital` (
  `id` int UNSIGNED NOT NULL,
  `date_lead` date NOT NULL,
  `nom_prospect` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telephone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source` enum('FACEBOOK','INSTAGRAM','WHATSAPP','SITE_WEB','TIKTOK','LINKEDIN','AUTRE') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'FACEBOOK',
  `message_initial` text COLLATE utf8mb4_unicode_ci,
  `produit_interet` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `statut` enum('NOUVEAU','CONTACTE','QUALIFIE','DEVIS_ENVOYE','CONVERTI','PERDU') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'NOUVEAU',
  `score_prospect` int DEFAULT '0' COMMENT 'Score 0-100 selon int??r??t/qualit??',
  `date_dernier_contact` datetime DEFAULT NULL,
  `prochaine_action` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_prochaine_action` date DEFAULT NULL,
  `client_id` int UNSIGNED DEFAULT NULL COMMENT 'Rempli apr??s conversion',
  `utilisateur_responsable_id` int UNSIGNED DEFAULT NULL,
  `campagne` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Nom de la campagne publicitaire',
  `cout_acquisition` decimal(15,2) DEFAULT '0.00' COMMENT 'Co??t pub si applicable',
  `observations` text COLLATE utf8mb4_unicode_ci,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_conversion` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Leads digitaux (Facebook, Instagram, WhatsApp, etc.)';

-- --------------------------------------------------------

--
-- Table structure for table `modes_paiement`
--

CREATE TABLE `modes_paiement` (
  `id` int UNSIGNED NOT NULL,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `libelle` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `modes_paiement`
--

INSERT INTO `modes_paiement` (`id`, `code`, `libelle`) VALUES
(1, 'CASH', 'Espéces'),
(2, 'VIREMENT', 'Virement bancaire'),
(3, 'MOBILE_MONEY', 'Mobile Money'),
(4, 'CHEQUE', 'Chéque');

-- --------------------------------------------------------

--
-- Table structure for table `mouvements_stock_backup_20251209_161710`
--

CREATE TABLE `mouvements_stock_backup_20251209_161710` (
  `id` int UNSIGNED NOT NULL,
  `date_mouvement` date NOT NULL,
  `type_mouvement` enum('ENTREE','SORTIE','CORRECTION') COLLATE utf8mb4_unicode_ci NOT NULL,
  `produit_id` int UNSIGNED NOT NULL,
  `quantite` int NOT NULL,
  `source_module` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_id` int UNSIGNED DEFAULT NULL,
  `utilisateur_id` int UNSIGNED DEFAULT NULL,
  `commentaire` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `objectifs_commerciaux`
--

CREATE TABLE `objectifs_commerciaux` (
  `id` int UNSIGNED NOT NULL,
  `annee` int NOT NULL,
  `mois` int DEFAULT NULL COMMENT 'NULL = objectif annuel',
  `canal` enum('SHOWROOM','TERRAIN','DIGITAL','HOTEL','FORMATION','GLOBAL') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'GLOBAL',
  `objectif_ca` decimal(15,2) NOT NULL DEFAULT '0.00',
  `objectif_nb_ventes` int DEFAULT NULL,
  `objectif_nb_leads` int DEFAULT NULL,
  `realise_ca` decimal(15,2) DEFAULT '0.00',
  `realise_nb_ventes` int DEFAULT '0',
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ordres_preparation`
--

CREATE TABLE `ordres_preparation` (
  `id` int UNSIGNED NOT NULL,
  `numero_ordre` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_ordre` date NOT NULL,
  `vente_id` int UNSIGNED DEFAULT NULL,
  `devis_id` int UNSIGNED DEFAULT NULL,
  `client_id` int UNSIGNED NOT NULL,
  `type_commande` enum('VENTE_SHOWROOM','VENTE_TERRAIN','VENTE_DIGITAL','RESERVATION_HOTEL','AUTRE') COLLATE utf8mb4_unicode_ci DEFAULT 'VENTE_SHOWROOM',
  `commercial_responsable_id` int UNSIGNED NOT NULL,
  `statut` enum('EN_ATTENTE','EN_PREPARATION','PRET','LIVRE','ANNULE') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'EN_ATTENTE',
  `date_preparation_demandee` date DEFAULT NULL,
  `priorite` enum('NORMALE','URGENTE','TRES_URGENTE') COLLATE utf8mb4_unicode_ci DEFAULT 'NORMALE',
  `observations` text COLLATE utf8mb4_unicode_ci,
  `signature_resp_marketing` tinyint(1) DEFAULT '0' COMMENT 'Validation RESP MARKETING',
  `date_signature_marketing` datetime DEFAULT NULL,
  `magasinier_id` int UNSIGNED DEFAULT NULL,
  `date_preparation_effectuee` datetime DEFAULT NULL,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Ordres de pr??paration (liaison marketing-magasin)';

-- --------------------------------------------------------

--
-- Table structure for table `ordres_preparation_lignes`
--

CREATE TABLE `ordres_preparation_lignes` (
  `id` int UNSIGNED NOT NULL,
  `ordre_preparation_id` int UNSIGNED NOT NULL,
  `produit_id` int UNSIGNED NOT NULL,
  `quantite_demandee` decimal(15,3) NOT NULL,
  `quantite_preparee` decimal(15,3) DEFAULT '0.000',
  `observations` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `parametres_securite`
--

CREATE TABLE `parametres_securite` (
  `id` int UNSIGNED NOT NULL,
  `cle` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valeur` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('STRING','INT','BOOL','JSON') COLLATE utf8mb4_unicode_ci DEFAULT 'STRING',
  `description` text COLLATE utf8mb4_unicode_ci,
  `modifie_par` int UNSIGNED DEFAULT NULL,
  `date_modification` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Configuration de s??curit?? globale';

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` int UNSIGNED NOT NULL,
  `code` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions`
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
-- Table structure for table `produits`
--

CREATE TABLE `produits` (
  `id` int UNSIGNED NOT NULL,
  `code_produit` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `famille_id` int UNSIGNED NOT NULL,
  `sous_categorie_id` int UNSIGNED DEFAULT NULL,
  `designation` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `caracteristiques` text COLLATE utf8mb4_unicode_ci,
  `description` text COLLATE utf8mb4_unicode_ci,
  `fournisseur_id` int UNSIGNED DEFAULT NULL,
  `localisation` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prix_achat` decimal(15,2) NOT NULL DEFAULT '0.00',
  `prix_vente` decimal(15,2) NOT NULL DEFAULT '0.00',
  `stock_actuel` int NOT NULL DEFAULT '0',
  `seuil_alerte` int NOT NULL DEFAULT '0',
  `image_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `produits`
--

INSERT INTO `produits` (`id`, `code_produit`, `famille_id`, `sous_categorie_id`, `designation`, `caracteristiques`, `description`, `fournisseur_id`, `localisation`, `prix_achat`, `prix_vente`, `stock_actuel`, `seuil_alerte`, `image_path`, `actif`, `date_creation`, `date_modification`) VALUES
(1, 'MEU-CH-001', 1, 1, 'Lit 2 places avec chevets', 'Dimensions 160x200', 'Lit moderne pour chambre parentale', 1, 'Showroom Douala', 120000.00, 180000.00, -1, 2, '/assets/img/produits/MEU-CH-001.png', 1, '2025-11-18 11:00:22', '2025-12-02 15:58:23'),
(73, 'MAC-HLD-1100', 74, NULL, 'Machine de perçage de serrure HLD-1100 (Handle Lock Drilling Tool)', NULL, 'Cette machine de perçage de serrure permet un perçage rapide et précis des logements de serrures et poignées de portes. Idéale pour les menuisiers et fabricants de portes, elle améliore la productivité et garantit des finitions propres. Son moteur puissant et sa double compatibilité électrique en font un outil fiable en atelier comme sur chantier.', NULL, NULL, 0.00, 1000000.00, 0, 10, NULL, 1, '2025-12-29 06:21:01', NULL),
(74, 'MAC-STM-2200', 74, NULL, 'TENONNEUSE STM-2200 2,2KW (Square Tenoning Machine)', NULL, 'Cette mortaiseuse à mèche carrée est conçue pour réaliser rapidement des tenons et mortaises précis dans le bois massif. Sa puissance de 2,2 kW et sa structure robuste assurent une excellente stabilité, même sur des pièces épaisses. C\'est un outil indispensable pour les ateliers de menuiserie cherchant productivité et précision dans l\'assemblage bois.', NULL, NULL, 0.00, 1200000.00, 0, 10, NULL, 1, '2025-12-29 06:21:01', NULL),
(75, 'MAC-MKD-550', 74, NULL, 'DEFONCEUSE MANUELLE MKD-550 550W (Manual Keyhole Drilling Machine)', NULL, 'Cette machine manuelle de perçage de trous de serrure est conçue pour les menuisiers recherchant précision et simplicité. Grâce à son moteur de 550 W et à son système de guidage, elle permet de réaliser facilement des logements de serrure nets et bien alignés. Idéale pour les ateliers et les travaux de personnalisation de portes, elle combine compacité, fiabilité et facilité d\'utilisation.', NULL, NULL, 0.00, 300000.00, 0, 10, NULL, 1, '2025-12-29 06:21:01', NULL),
(76, 'MAC-HDM-6415', 74, NULL, 'Machine de perçage de charnières HDM-6415 – tête simple (Hinge Drilling Machine)', NULL, 'La machine de perçage de charnières à tête simple permet un perçage rapide et précis des logements de charnières. Sa structure robuste et son système de guidage garantissent un positionnement exact, réduisant les erreurs. Idéale pour les ateliers de menuiserie et les fabricants de meubles, elle améliore la productivité tout en assurant une finition professionnelle.', NULL, NULL, 0.00, 750000.00, 0, 10, NULL, 1, '2025-12-29 06:21:01', NULL),
(77, 'MAC-PLAQUEUSE-802', 74, NULL, 'PLAQUEUSE AUTOMATIQUE DE CHANTS – MODÈLE 802', NULL, 'La plaqueuse automatique de chants 802 est idéale pour les ateliers de menuiserie, offrant rapidité, précision et finition professionnelle. Ses caractéristiques incluent un encollage double face, un polissage double et un système d\'aspiration intégré pour un collage propre et durable, même sur de grands panneaux. Compacte et robuste, elle permet à un seul opérateur de réaliser un plaquage esthétique tout en gardant un environnement de travail propre.', NULL, NULL, 0.00, 1500000.00, 0, 10, NULL, 1, '2025-12-29 06:21:01', NULL),
(78, 'MAC-EDGE-702', 74, NULL, '702 – FULL AUTOMATIC EDGE BANDING MACHINE', NULL, 'La plaqueuse automatique de chants 702 est une machine compacte, performante et économique, idéale pour les ateliers. Elle offre un plaquage fluide avec une finition soignée grâce à son double polissage et son aspiration intégrée. Adaptée à la production en série ou personnalisée, elle permet un gain de temps tout en garantissant une qualité constante sur divers panneaux.', NULL, NULL, 0.00, 1500000.00, 0, 10, NULL, 1, '2025-12-29 06:21:01', NULL),
(79, 'MAC-SCIE-TABLE', 74, NULL, 'SCIE À TABLE PORTABLE MULTIFONCTION', NULL, 'La scie à table portable multifonction est idéale pour les menuisiers et artisans, offrant une solution mobile et précise pour la découpe. Avec sa double lame, elle assure des coupes nettes sur divers matériaux sans éclats. Son design pliable, son poids léger et ses roues intégrées en font un outil pratique pour les chantiers, tout en respectant l\'environnement grâce à son système de collecte de poussière.', NULL, NULL, 0.00, 1500000.00, 0, 10, NULL, 1, '2025-12-29 06:21:01', NULL),
(80, 'QUI-GLIS-RAL-NOIR', 68, NULL, 'Glissière avec ralenti noir (cartons de 20 paires)', NULL, 'La glissière avec ralenti noire améliore le confort d\'utilisation des tiroirs grâce à son système de fermeture amortie, assurant une fermeture douce et silencieuse, et évitant les dommages aux meubles. Appréciée dans les cuisines modernes et les meubles à usage fréquent, elle offre une sensation de qualité supérieure et garantit la stabilité du tiroir même sous charges répétées, tout en assurant une glisse fluide.', NULL, NULL, 0.00, 1800.00, 0, 10, NULL, 1, '2025-12-29 06:21:01', NULL),
(81, 'QUI-GLIS-NORM-NOIR', 68, NULL, 'Glissière normale noir (cartons de 20 paires)', NULL, 'La glissière noire normale est une solution économique et fiable pour les tiroirs standards, idéale pour les meubles de rangement et bureaux. Elle est facile à installer et à entretenir, offrant une glisse stable, adaptée aux productions en série et ateliers de menuiserie. C\'est un choix rationnel pour des projets nécessitant robustesse et maîtrise des coûts.', NULL, NULL, 0.00, 1150.00, 0, 10, NULL, 1, '2025-12-29 06:21:01', NULL),
(82, 'QUI-SLIDE-POUSSE-NOIR', 68, NULL, 'Slide pousse lache noir (cartons de 20 paires)', NULL, 'La slide pousse-lâche noire est parfaite pour les meubles modernes, offrant une ouverture sans poignée par pression sur la façade, idéale pour les cuisines contemporaines. Elle allie esthétique et praticité en éliminant les poignées visibles tout en assurant une ouverture fluide, garantissant un usage quotidien confortable et un rendu visuel harmonieux.', NULL, NULL, 0.00, 1800.00, 0, 10, NULL, 1, '2025-12-29 06:21:01', NULL),
(83, 'QUI-GLIS-RAL-ACIER', 68, NULL, 'Glissière ralenti acier (cartons de 20 paires)', NULL, 'La glissière ralenti en aluminium combine fermeture amortie et renfort, offrant durabilité et résistance à l\'usure, idéale pour meubles intensifs ou haut de gamme. Son système de ralenti intégré protège la structure et améliore le confort, parfaite pour cuisines premium, tiroirs larges, nécessitant fiabilité, silence et longévité.', NULL, NULL, 0.00, 2500.00, 0, 10, NULL, 1, '2025-12-29 06:21:01', NULL),
(84, 'QUI-GLIS-NORM-ACIER', 68, NULL, 'Glissière normale acier (cartons de 20 paires)', NULL, 'La glissière normale en aluminium offre une résistance mécanique supérieure aux modèles standards, tout en restant simple à utiliser, idéale pour des tiroirs fréquemment sollicités en milieu domestique et professionnel. Son matériau en aluminium assure durabilité, limitant déformations et usure prématurée. C\'est une solution équilibrée entre solidité, fiabilité et coût, prisée dans les ateliers de menuiserie.', NULL, NULL, 0.00, 1800.00, 0, 10, NULL, 1, '2025-12-29 06:21:01', NULL),
(85, 'QUI-GLIS-POUSSE-ALU', 68, NULL, 'Glissière pousse lache alu (cartons de 20 paires)', NULL, 'La glissière pousse-lâche en aluminium permet une ouverture sans poignée pour les meubles modernes, alliant élégance et robustesse. Elle assure une ouverture fiable par pression et une stabilité optimale du tiroir, idéale pour des projets nécessitant esthétique, durabilité et précision.', NULL, NULL, 0.00, 2500.00, 0, 10, NULL, 1, '2025-12-29 06:21:01', NULL),
(86, 'ACC-VIS-NOIR', 75, NULL, 'Vis noir (paquet de 2 kg)', NULL, 'Les vis noires sont destinées à l\'assemblage courant des meubles et structures en bois. Elles offrent une bonne tenue mécanique, une pose facile et une finition discrète adaptée aux meubles intérieurs. Elles conviennent parfaitement aux travaux de menuiserie générale, à l\'assemblage de panneaux et aux montages standards en atelier.', NULL, NULL, 0.00, 5000.00, 0, 10, NULL, 1, '2025-12-29 06:21:01', NULL),
(87, 'ACC-VIS-PLAQUE-OR', 75, NULL, 'Vis plaque or (paquet de 2 kg)', NULL, 'Les vis plaque or sont spécialement conçues pour la fixation des panneaux et plaques décoratives. Leur finition dorée offre une meilleure résistance à la corrosion et un rendu plus soigné lorsque la vis reste apparente. Elles sont adaptées aux travaux de menuiserie intérieure, à la pose de panneaux et aux assemblages nécessitant une fixation fiable et propre.', NULL, NULL, 0.00, 4500.00, 0, 10, '/kms_app/assets/img/produits/ACC-VIS-PLAQUE-OR.jpg', 1, '2025-12-29 06:21:01', NULL),
(88, 'ACC-MINI-FIX', 75, NULL, 'Mini-fix ou connecteur excentrique', NULL, 'Cette charnière dissimulée 3D en acier doux assure un mouvement fluide et silencieux grâce à sa fonction de fermeture douce. Le réglage 3D permet un alignement précis de la porte avec des vis intégrées, sans la retirer. Fonctionnelle et durable, elle améliore l\'esthétique de votre mobilier.', NULL, NULL, 0.00, 250.00, 0, 10, NULL, 1, '2025-12-29 06:21:01', NULL),
(89, 'ACC-CHAR-3D', 75, NULL, 'Charnière 3D', NULL, 'Charnière hydraulique à réglage 3D permettant une fermeture douce et silencieuse des portes de meubles. Son système de réglage en hauteur, profondeur et latéral facilite l\'alignement précis des portes après installation. Adaptée aux cuisines, dressings et meubles à usage fréquent.', NULL, NULL, 0.00, 900.00, 0, 10, NULL, 1, '2025-12-29 06:21:01', NULL),
(90, 'ACC-CHAR-GRENOUILLE', 75, NULL, 'Charnière grenouille', NULL, 'Charnière hydraulique conçue pour assurer une fermeture douce et silencieuse des portes de meubles. Son système d\'amortissement intégré limite les chocs et prolonge la durée de vie des portes. Le réglage 2D permet un ajustement précis après installation, idéal pour cuisines, placards et meubles standards.', NULL, NULL, 0.00, 1400.00, 0, 10, NULL, 1, '2025-12-29 06:21:01', NULL),
(91, 'ACC-CHAR-90', 75, NULL, 'Charnière meuble à ouverture 90 degrés', NULL, 'La charnière de meuble à ouverture 90 degrés est utilisée pour les portes d\'armoires, placards, cuisines et meubles TV. Elle permet un accès facile au contenu et peut inclure un système de fermeture douce pour un fonctionnement silencieux et contrôlé, selon la taille et le poids de la porte.', NULL, NULL, 0.00, 1300.00, 0, 10, NULL, 1, '2025-12-29 06:21:01', NULL),
(92, 'ACC-CHAR-3D-NOIRE', 75, NULL, 'Charnière 3D noire', NULL, 'Charnière hydraulique noire à réglage 3D pour portes de meubles, offrant un ajustement précis en hauteur, profondeur et latéral après installation. Son système d\'amortissement permet une fermeture douce et silencieuse, parfaite pour cuisines et meubles modernes.', NULL, NULL, 0.00, 1000.00, 0, 10, NULL, 1, '2025-12-29 06:21:01', NULL),
(93, 'ACC-PAUM-PAP-ALU', 75, NULL, 'Paumelles papillon alu', NULL, 'Paumelle papillon en aluminium argenté, idéale pour meubles et placards. Son design moderne s\'adapte facilement à tout intérieur. Fabriquée en métal durable, elle garantit longévité et installation facile pour les travaux de menuiserie.', NULL, NULL, 0.00, 1200.00, 0, 10, NULL, 1, '2025-12-29 06:21:01', NULL),
(94, 'ACC-PAUM-PAP-NOIR', 75, NULL, 'Paumelles papillon noir', NULL, 'Paumelle papillon noire à finition mate, idéale pour les meubles au style moderne ou industriel. Elle permet un assemblage fiable des portes tout en apportant un contraste esthétique marqué. Sa finition traitée offre une bonne résistance à l\'usure et à la corrosion pour un usage intérieur fréquent.', NULL, NULL, 0.00, 1300.00, 0, 10, NULL, 1, '2025-12-29 06:21:01', NULL),
(95, 'ACC-PAUM-PAP-BLANCHE', 75, NULL, 'Paumelles papillon blanche', NULL, 'Paumelle papillon blanche à finition mate, conçue pour se fondre discrètement dans les portes et cadres clairs. Elle est particulièrement adaptée aux cuisines, placards et meubles à design épuré. Sa finition peinte protège efficacement le métal pour un usage intérieur standard.', NULL, NULL, 0.00, 1850.00, 0, 10, NULL, 1, '2025-12-29 06:21:01', NULL),
(96, 'ACC-PAUM-PAP-OR', 75, NULL, 'Paumelles papillon or', NULL, 'Paumelle papillon finition or, destinée aux meubles décoratifs et aux aménagements nécessitant une touche élégante et valorisante. Elle est souvent utilisée pour les meubles haut de gamme, vitrines et portes visibles. Sa finition décorative assure un bon compromis entre esthétique et durabilité.', NULL, NULL, 0.00, 1850.00, 0, 10, NULL, 1, '2025-12-29 06:21:01', NULL),
(97, 'ACC-PAUM-PAP-ANTIQ', 75, NULL, 'Paumelles papillon antique (cuivre)', NULL, 'Paumelle papillon finition antique, conçue pour les meubles de style classique, rustique ou vintage. Elle apporte un aspect traditionnel et authentique tout en assurant une fixation solide des portes. Sa finition patinée est appréciée pour les projets décoratifs et artisanaux.', NULL, NULL, 0.00, 1850.00, 0, 10, NULL, 1, '2025-12-29 06:21:01', NULL),
(98, 'QUI-SERR-COURTE-138-22', 68, NULL, 'Serrure courte 138-22', NULL, 'Serrure de meuble courte modèle 138-22, idéale pour armoires et tiroirs. Mécanisme simple garantissant une fermeture sécurisée. Fabriquée en métal nickelé, elle résiste à l\'usure et à la corrosion, convenant à un usage intérieur en menuiserie.', NULL, NULL, 0.00, 900.00, 0, 10, NULL, 1, '2025-12-29 06:21:01', NULL),
(99, 'QUI-SERR-LONGUE-139-32', 68, NULL, 'Serrure longue 139-32', NULL, 'La serrure de meuble longue modèle 139-32 est idéale pour les armoires et placards. Elle offre une portée de verrouillage profonde et un mécanisme robuste pour une fermeture sécurisée. Fabriquée en métal nickelé, elle résiste à l\'usure et à la corrosion, adaptée aux installations intérieures résidentielles et professionnelles.', NULL, NULL, 0.00, 1000.00, 0, 10, NULL, 1, '2025-12-29 06:21:01', NULL),
(100, 'QUI-VERROU-PORTE-COUL', 68, NULL, 'Verrou de porte coulissante', NULL, 'Paumelle papillon blanche à finition mate, conçue pour se fondre discrètement dans les portes et cadres clairs. Elle est particulièrement adaptée aux cuisines, placards et meubles à design épuré. Sa finition peinte protège efficacement le métal pour un usage intérieur standard.', NULL, NULL, 0.00, 1000.00, 0, 10, NULL, 1, '2025-12-29 06:21:01', NULL),
(101, 'ACC-LOQUET-MAG', 75, NULL, 'Loqueteau magnétique', NULL, 'Loqueteau magnétique pour maintenir discrètement et efficacement les portes de meubles fermées. Il offre une fermeture douce et silencieuse, tout en permettant une ouverture facile. Adapté aux meubles de cuisine et installations de menuiserie, c\'est une solution simple pour l\'alignement des portes.', NULL, NULL, 0.00, 450.00, 0, 10, NULL, 1, '2025-12-29 06:21:01', NULL),
(102, 'ACC-CROCHET-OR', 75, NULL, 'Crochet mural or', NULL, 'Mural en crochet conçu pour ranger et suspendre des objets quotidiens comme vêtements, sacs, serviettes ou accessoires. Sa structure en métal assure résistance et sa finition dorée ajoute une touche décorative. Idéal pour les chambres, cuisines, salles de bain ou autres espaces.', NULL, NULL, 0.00, 1300.00, 0, 10, NULL, 1, '2025-12-29 06:21:01', NULL),
(103, 'ACC-CROCHET-NOIR-MATT', 75, NULL, 'Crochet mural noir matt', NULL, 'Mural en crochet noir mat, idéal pour ranger et suspendre des objets quotidiens comme vêtements et accessoires. Son design moderne s\'intègre bien aux intérieurs contemporains. Fabriqué en métal durable, il garantit longévité et fiabilité dans les milieux résidentiels et professionnels.', NULL, NULL, 0.00, 1300.00, 0, 10, NULL, 1, '2025-12-29 06:21:01', NULL),
(104, 'ACC-POIG-BOUT-OR-ROND', 75, NULL, 'Poignée bouton or rond', NULL, 'POIGNÉE bouton en finition dorée, idéal pour portes et tiroirs de meubles comme armoires et commodes. Sa forme ronde assure une prise confortable, et la finition texturée ajoute une touche moderne. Fabriqué en métal robuste, il est durable et convient aux projets résidentiels et professionnels.', NULL, NULL, 0.00, 1000.00, 0, 10, NULL, 1, '2025-12-29 06:21:01', NULL),
(105, 'ACC-POIG-BOUT-ARGENT-6C', 75, NULL, 'Poignées bouton argent 6-C', NULL, 'Bouton de POIGNÉE argenté, modèle 6-C, conçu pour portes et tiroirs de meubles (placards, armoires, cuisine). Sa forme géométrique moderne assure une prise en main confortable. Fabriqué en métal résistant, il offre un design sobre et contemporain, adapté aux espaces résidentiels et professionnels.', NULL, NULL, 0.00, 1000.00, 0, 10, NULL, 1, '2025-12-29 06:21:01', NULL),
(106, 'ACC-POIG-BOUT-NOIR-6C', 75, NULL, 'Poignée bouton noir 6-C', NULL, 'LA POIGNÉE bouton 6-C, en finition noire mate, est conçu pour les portes et tiroirs de meubles. Sa forme géométrique offre un style moderne et épuré, s\'intégrant bien aux aménagements contemporains et industriels. Fabriqué en métal résistant, il garantit confort et durabilité.', NULL, NULL, 0.00, 1000.00, 0, 10, NULL, 1, '2025-12-29 06:21:01', NULL),
(107, 'ACC-POIG-BOUT-OR-6C', 75, NULL, 'Poignée bouton or 6-C', NULL, 'LA POIGNÉE bouton finition or, modèle 6-C, est conçu pour portes et tiroirs de meubles. Avec sa forme géométrique moderne et sa finition dorée, il ajoute une touche élégante aux intérieurs. Fabriqué en métal robuste, il assure une bonne prise en main et une durabilité, s\'intégrant dans des meubles contemporains.', NULL, NULL, 0.00, 1000.00, 0, 10, NULL, 1, '2025-12-29 06:21:01', NULL),
(108, 'ACC-POIG-VERRE-NOIR', 75, NULL, 'Poignée meuble porte en verre – finition noir', NULL, 'Cette charnière à pince pour porte en verre est conçue pour les meubles vitrés (cuisines, vitrines, meubles TV, dressings). Elle permet de fixer et d\'articuler une porte en verre sans perçage, grâce à un système de serrage sécurisé. Sa finition noir mat apporte un rendu moderne et discret, parfaitement adapté aux aménagements contemporains.', NULL, NULL, 0.00, 1200.00, 0, 10, NULL, 1, '2025-12-29 06:21:01', NULL),
(109, 'ACC-POIG-VERRE-OR', 75, NULL, 'Poignée meuble porte en verre – finition or', NULL, 'Charnière à pince pour porte en verre avec finition OR, idéale pour les meubles à forte valeur esthétique. Elle assure une fixation fiable du verre et une ouverture fluide, sans fragiliser le panneau. Sa finition dorée apporte une touche premium, très appréciée pour les vitrines, cuisines modernes et meubles décoratifs.', NULL, NULL, 0.00, 1200.00, 0, 10, NULL, 1, '2025-12-29 06:21:01', NULL),
(110, 'ACC-POIG-D-CARRE-NOIR-DORE', 75, NULL, 'Poignées D carré – noir doré', NULL, 'Les POIGNÉES D Carré Noir Doré ajoutent une touche contemporaine et élégante aux meubles de cuisine, armoires et dressings. Leur forme carrée offre une bonne prise en main, tandis que le contraste noir et doré est apprécié dans les intérieurs modernes. Fabriqués en aluminium, ils sont durables et résistants à l\'usure pour un usage quotidien.', NULL, NULL, 0.00, 1000.00, 0, 10, NULL, 1, '2025-12-29 06:21:01', NULL),
(111, 'ACC-POIG-ENCAST-ALU', 75, NULL, 'Poignées encastrées aluminium – design profile', NULL, 'Les poignées encastrées en aluminium s\'intègrent dans le panneau, offrant un design moderne et discret, idéal pour les meubles contemporains. Fabriquées en aluminium, elles sont résistantes, durables et confortables à utiliser. Leur conception limite les chocs et facilite le nettoyage, renforçant l\'esthétique minimaliste. Disponibles en plusieurs longueurs (10,5 cm et 14 cm) et finitions (Noir, Gris, Or) pour s\'adapte à des styles de mobilier.', NULL, NULL, 0.00, 1000.00, 0, 10, NULL, 1, '2025-12-29 06:21:01', NULL),
(112, 'ACC-POIG-ENCAST-PLAT', 75, NULL, 'Poignées encastrées plates aluminium – ligne contemporaine', NULL, 'Les poignées encastrées plates en aluminium offrent une intégration élégante et moderne dans les meubles. Leur design plat convient aux cuisines, dressings et rangements contemporains. Fabriquées en aluminium robuste, elles garantissent résistance et confort, tout en éliminant les saillies pour réduire les chocs. Disponibles en 20 cm et 23 cm, avec diverses finitions (Noir, Gris, Beige brillant), elles s\'adaptent à des styles minimalistes ou luxueux.', NULL, NULL, 0.00, 1300.00, 0, 10, NULL, 1, '2025-12-29 06:21:01', NULL),
(113, 'ACC-GALETS-PORTE', 75, NULL, 'Galets de porte coulissante (modèles 038 / 039 / 040 / 041)', NULL, 'Les galets de porte coulissante assurent un guidage fluide et silencieux pour les portes en bois, aluminium ou légères. Utilisés dans placards et vitrines, chaque galet combine un support métallique robuste et une roue en nylon durable, offrant stabilité et réduction des frottements. Leur conception compacte facilite l\'intégration dans divers systèmes, adaptés aux applications résidentielles et professionnelles.', NULL, NULL, 0.00, 400.00, 0, 10, NULL, 1, '2025-12-29 06:21:01', NULL),
(114, 'ACC-PASSE-CABLES', 75, NULL, 'Passe-câbles de meubles (50 mm & 60 mm)', NULL, 'Ces passe-câbles assurent un passage propre et discret des câbles à travers les meubles, réduisant l\'encombrement visuel et protégeant les câbles. Fabriqués en plastique rigide de qualité, ils résistent à l\'usage quotidien et se fixent facilement grâce à leur capot amovible. Disponibles en plusieurs diamètres et couleurs, ils s\'adaptent à tout style de mobilier.', NULL, NULL, 0.00, 500.00, 0, 10, NULL, 1, '2025-12-29 06:21:01', NULL),
(115, 'ACC-ARRET-PORTE-MAG', 75, NULL, 'Arrêt de porte magnétique', NULL, 'L\'arrêt de porte magnétique est un accessoire discret et robuste conçu pour maintenir efficacement les portes en position ouverte. Grâce à son système magnétique puissant, il évite les claquements involontaires, protège les murs et prolonge la durée de vie des portes. Son design soigné et sa finition métallique lui permettent de s\'intégrer aussi bien dans les intérieurs modernes que classiques. Idéal pour les portes de cuisine, de bureau, de chambre ou d\'espaces commerciaux.', NULL, NULL, 0.00, 3000.00, 0, 10, NULL, 1, '2025-12-29 06:21:01', NULL),
(116, 'QUI-SERR-COMP-PORTE', 68, NULL, 'Serrure complète pour porte', NULL, 'La serrure pour porte avec poignée plate intégrée est une solution élégante et fonctionnelle pour portes intérieures modernes. Elle combine sécurité confort et esthétique épurée avec un design minimaliste. Idéale pour chambres, bureaux ou espaces professionnels, elle s\'intègre parfaitement aux portes en bois ou panneaux composites, assurant une manipulation fluide.', NULL, NULL, 0.00, 10000.00, 0, 10, NULL, 1, '2025-12-29 06:21:01', NULL),
(117, 'ACC-PATINS-MOYEN', 75, NULL, 'Patins moyen Ø 22 mm', NULL, 'Les patins Moyen 22 protègent les sols et améliorent la stabilité des meubles. Placés sous les pieds de mobilier, ils diminuent frottements, bruits et rayures sur les surfaces délicates comme le carrelage ou le parquet. Fabriqués en matériau durable, ils conviennent aux usages domestiques et professionnels.', NULL, NULL, 0.00, 100.00, 0, 10, NULL, 1, '2025-12-29 06:21:01', NULL),
(118, 'ACC-EQUERRES-PLAST', 75, NULL, 'Équerres plastiques de fixation – Angle intérieur', NULL, 'Ces équerres en plastique maintiennent discrètement les angles intérieurs des meubles, stabilisant les assemblages sans alourdir la structure. Durables et légères, elles sont faciles à installer et disponibles en plusieurs coloris (Blanc, Marron, Noir). Leur format compact de 27 mm convient aux meubles de cuisine, dressings et étagères, et elles restent discrètes après montage.', NULL, NULL, 0.00, 150.00, 0, 10, NULL, 1, '2025-12-29 06:21:01', NULL),
(119, 'ACC-GARN-004', 75, NULL, 'Garniture 004 – Ferrure de montage pour lits', NULL, 'La Garniture 004 est une ferrure métallique pour assembler lits en reliant longerons et traverses. Son réglage par tige filetée permet un ajustement précis, idéale pour les lits démontables. Fabriquée en acier galvanisé, elle résiste à l\'usure et aux montages répétés, essentielle pour menuisiers et fabricants de lits.', NULL, NULL, 0.00, 300.00, 0, 10, NULL, 1, '2025-12-29 06:21:01', NULL),
(120, 'ACC-CROCHET-RANG', 75, NULL, 'Crochets de rangement – métal', NULL, 'Les crochets de rangement métalliques organisent efficacement les espaces en suspendant des objets quotidiens comme vêtements et accessoires. Leur structure assure une bonne capacité de charge. Ils conviennent aux espaces domestiques et professionnels, offrant une solution esthétique pour le rangement vertical. Les versions noires ajoutent une touche moderne, tandis que les modèles standards sont polyvalents.', NULL, NULL, 0.00, 2400.00, 0, 10, NULL, 1, '2025-12-29 06:21:01', NULL),
(121, 'ACC-TRINGLE-VET', 75, NULL, 'Tringles pour vêtements – légère & lourde sans support', NULL, 'La tringle pour vêtements est essentielle pour une rangement ordonné dans les armoires. Fabriquées en métal robuste avec un profil ovale, elles évitent la rotation des cintres. Deux versions sont proposées : Tringle légère pour vêtements standards. Tringle lourde pour charges importantes. Sa finition métallique moderne s\'adapte à différents styles de meubles, et des supports de tringle adaptés sont nécessaires pour le montage.', NULL, NULL, 0.00, 2000.00, 0, 10, NULL, 1, '2025-12-29 06:21:01', NULL),
(122, 'ACC-BARRE-T', 75, NULL, 'Barre en T métallique – profil / rail d’assemblage et de finition', NULL, 'Les barres en T métalliques, ou rails en T, assurent des jonctions esthétiques entre panneaux et éléments de mobilier. Elles conviennent aux meubles modernes et aménagements intérieurs, offrant plusieurs avantages : Cacher les joints, Absorber les écarts d\'alignement, Apporter une finition nette. Les variantes GP et CP proposent des finitions métalliques adaptées à différents styles des meubles.', NULL, NULL, 0.00, 2000.00, 0, 10, NULL, 1, '2025-12-29 06:21:01', NULL),
(123, 'ACC-VERIN-GAZ', 75, NULL, 'Vérin à gaz / vérin pneumatique pour meuble', NULL, 'Les vérins de meuble sont des dispositifs à gaz qui facilitent l\'ouverture, maintiennent la porte ouverte et évitent les fermetures brusques. Ils sont parfaits pour les cuisines, placards et armoires. Grâce à leur mécanisme pneumatique, ils prolongent la durée de vie des charnières et des panneaux. Les petites versions sont idéales pour les portes légères, tandis que les longues conviennent aux portes lourdes.', NULL, NULL, 0.00, 1500.00, 0, 10, NULL, 1, '2025-12-29 06:21:01', NULL),
(124, 'ACC-SUPPORT-TRINGLE', 75, NULL, 'Supports de tringle – Ø16 mm', NULL, 'Le support de tringle est crucial pour l\'installation des tringles dans les armoires et placards, offrant maintien et stabilité. Fabriqué en alliage de zinc, il est durable et conçu pour des tringles ovales ou rondes de 16 mm. Son design discret s\'intègre harmonieusement dans les meubles, et son montage est simple grâce aux points de fixation.', NULL, NULL, 0.00, 250.00, 0, 10, NULL, 1, '2025-12-29 06:21:01', NULL),
(125, 'ACC-BANDE-DECO-ALU', 75, NULL, 'Bande décorative aluminium – 16 mm & 18 mm', NULL, 'La bande décorative en aluminium est un accessoire de finition pour la menuiserie moderne, améliorant l\'esthétique des meubles comme les cuisines et placards. Elle offre un design élégant en façade ou comme séparation visuelle. Fabriquée en aluminium extrudé, elle est durable, résistante aux chocs, et disponible en finition dorée ou métallique, créant un contraste premium avec les surfaces en bois.', NULL, NULL, 0.00, 4500.00, 0, 10, NULL, 1, '2025-12-29 06:21:01', NULL),
(126, 'ACC-BUTEE-PORTE', 75, NULL, 'Butée de porte au sol – 16 mm (gris & noir)', NULL, 'La butée de porte au sol de 16 mm protège les portes, murs et meubles des chocs tout en réduisant le bruit grâce à un tampon amortisseur. En acier inoxydable, elle est résistante à l\'usure et aux chocs, avec un design compact pour les intérieurs modernes. Facile à installer, elle convient aux portes intérieures et aux meubles.', NULL, NULL, 0.00, 1500.00, 0, 10, NULL, 1, '2025-12-29 06:21:01', NULL),
(127, 'ACC-POIG-LINEAIRE', 75, NULL, 'Poignées linéaires aluminium – 1 mètre', NULL, 'Les poignées linéaires de 1 mètre conviennent aux portes hautes d\'armoires et de meubles modernes. Leur design allongé offre une prise confortable et un style contemporain. Fabriquées en aluminium rigide, elles sont durables, même pour des portes lourdes. La version noire est élégante. La version beige brillant apporte une touche lumineuse idéale pour des meubles clairs.', NULL, NULL, 0.00, 10000.00, 0, 10, NULL, 1, '2025-12-29 06:21:01', NULL),
(128, 'QUI-VERROU-ANTI-VOL', 68, NULL, 'Verrou anti-vol pour porte et meuble', NULL, 'Ce verrou anti-vol à chaîne offre une sécurité renforcée pour portes, fenêtres et meubles. Fabriqué en acier robuste, il empêche les intrusions même si la porte est entrouverte. Sa chaîne métallique renforcée résiste à l\'arrachement, idéal pour divers espaces. L\'installation est simple et son design s\'intègre facilement dans différents environnements.', NULL, NULL, 0.00, 2500.00, 0, 10, NULL, 1, '2025-12-29 06:21:01', NULL),
(129, 'ACC-BRAS-PORTANT', 75, NULL, 'Bras portant gris (support de présentation)', NULL, 'Le bras portant gris est un accessoire métallique conçu pour la présentation et le rangement dans divers environnements, comme les dressings et boutiques. Fabriqué en métal robuste, il supporte bien les charges avec stabilité. Sa finition grise s\'adapte aux espaces domestiques et commerciaux, et il se fixe au mur avec un angle incliné pour améliorer la visibilité des articles suspendus.', NULL, NULL, 0.00, 1500.00, 0, 10, NULL, 1, '2025-12-29 06:21:01', NULL),
(130, 'ACC-POIG-BATONS', 75, NULL, 'Poignées bâtons', NULL, 'Les poignées modernes, parfaites pour les cuisines et meubles contemporains, offrent une prise confortable grâce à leur forme allongée. Leur noir mat et les extrémités métalliques ajoutent une touche d\'élégance. Elles conviennent aux projets résidentiels haut de gamme, avec une installation facile et une durabilité élevée.', NULL, NULL, 0.00, 2300.00, 0, 10, NULL, 1, '2025-12-29 06:21:01', NULL),
(131, 'ACC-ANGLE-WPC', 75, NULL, 'Angle / Cornière WPC extérieur', NULL, 'Cette cornière d\'angle en WPC (Wood Plastic Composite) est un profilé de finition conçu spécialement pour les aménagements extérieurs : terrasses, bardages, habillages muraux et contours de panneaux composites. Fabriquée à partir d\'un mélange de fibres de bois et de plastique, elle offre une excellente résistance aux intempéries, à l\'humidité, aux UV et aux variations de température, tout en conservant un aspect élégant et moderne. Sa finition noire / anthracite, avec un grain bois discret, permet d\'obtenir des angles nets et professionnels, tout en masquant les coupes et jonctions des panneaux WPC. La pose est simple et rapide : collage ou vissage, avec des outils standards de menuiserie. Produit idéal pour des finitions durables, propres et esthétiques en extérieur, sans entretien lourd.', NULL, NULL, 0.00, 13000.00, 0, 10, NULL, 1, '2025-12-29 06:21:01', NULL),
(132, 'FIN-CHANT-PVC-BRILL', 76, NULL, 'Chant PVC brillant – Or & Argent', NULL, 'Le chant PVC brillant couleur Or et Argent est un produit décoratif haut de gamme, parfait pour meubles premium, cuisines modernes, aménagements intérieurs élégants.', NULL, NULL, 0.00, 1000.00, 0, 10, NULL, 1, '2025-12-29 06:21:01', NULL),
(133, 'PLQ-CP-MELAMINE', 77, NULL, 'CP Mélaminés', NULL, 'Contreplaqués mélaminés de différentes couleurs et épaisseurs pour menuiserie.', NULL, NULL, 0.00, 0.00, 0, 10, NULL, 1, '2025-12-29 06:21:01', NULL),
(134, 'FIN-COLLE-THERMO', 76, NULL, 'Colle thermofusible (Hot Melt Glue)', NULL, 'La colle thermofusible est un adhésif professionnel utilisé en menuiserie pour la pose de bandes de chant mélaminées et le collage rapide de composants en bois. Elle durcit rapidement après fusion à chaud, offrant prise immédiate, excellente adhérence sur bois, CPM, MDF, bonne tenue mécanique et thermique. Adaptée aux plaqueuses de chants et aux ateliers de fabrication de meubles, ses applications courantes comprennent pose de bandes de chant, assemblage rapide de panneaux, travaux de finition en menuiserie. Conditionnée en seau ou bidon industriel pour un usage professionnel.', NULL, NULL, 0.00, 65000.00, 0, 10, NULL, 1, '2025-12-29 06:21:01', NULL),
(135, 'PLQ-DECOR-PVC', 77, NULL, 'Décoration PVC', NULL, 'Divers panneaux PVC et WPC pour décoration et habillage.', NULL, NULL, 0.00, 0.00, 0, 10, NULL, 1, '2025-12-29 06:21:01', NULL),
(136, 'FIN-BANDE-CHANT-MELAM', 76, NULL, 'Bandes de chant mélaminées', NULL, 'Les bandes de chant mélaminées sont utilisées pour la finition des bords des panneaux mélaminés, CPM et MDF. Elles permettent de protéger les arêtes, d\'améliorer la durabilité des meubles et d\'assurer une finition esthétique homogène, parfaitement assortie au panneau. Adaptées aux meubles de cuisine, dressing, armoires, bureaux et rangements, ces bandes se posent par encollage à chaud (colle hot melt), manuellement ou à la plaqueuse. Disponibles dans une large gamme de couleurs standards, elles s\'intègrent facilement à tous les styles de menuiserie : moderne, classique ou contemporain.', NULL, NULL, 0.00, 0.00, 0, 10, NULL, 1, '2025-12-29 06:21:01', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `promotions`
--

CREATE TABLE `promotions` (
  `id` int UNSIGNED NOT NULL,
  `nom` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `pourcentage_remise` decimal(5,2) DEFAULT NULL,
  `montant_remise` decimal(15,2) DEFAULT NULL,
  `date_debut` date NOT NULL,
  `date_fin` date NOT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `promotion_produit`
--

CREATE TABLE `promotion_produit` (
  `promotion_id` int UNSIGNED NOT NULL,
  `produit_id` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prospections_terrain`
--

CREATE TABLE `prospections_terrain` (
  `id` int UNSIGNED NOT NULL,
  `date_prospection` date NOT NULL,
  `heure_prospection` time DEFAULT NULL,
  `prospect_nom` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `secteur` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `adresse_gps` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `besoin_identifie` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `action_menee` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `resultat` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `prochaine_etape` text COLLATE utf8mb4_unicode_ci,
  `client_id` int UNSIGNED DEFAULT NULL,
  `commercial_id` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prospects_formation`
--

CREATE TABLE `prospects_formation` (
  `id` int UNSIGNED NOT NULL,
  `date_prospect` date NOT NULL,
  `nom_prospect` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `source` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `statut_actuel` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `client_id` int UNSIGNED DEFAULT NULL,
  `utilisateur_id` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `relances_devis`
--

CREATE TABLE `relances_devis` (
  `id` int UNSIGNED NOT NULL,
  `devis_id` int UNSIGNED NOT NULL,
  `date_relance` date NOT NULL,
  `type_relance` enum('TELEPHONE','EMAIL','SMS','WHATSAPP','VISITE') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'TELEPHONE',
  `utilisateur_id` int UNSIGNED NOT NULL,
  `commentaires` text COLLATE utf8mb4_unicode_ci,
  `prochaine_action` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_prochaine_action` date DEFAULT NULL,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rendezvous_terrain`
--

CREATE TABLE `rendezvous_terrain` (
  `id` int UNSIGNED NOT NULL,
  `date_rdv` date NOT NULL,
  `heure_rdv` time NOT NULL,
  `client_prospect_nom` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `lieu` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `objectif` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `statut` enum('PLANIFIE','CONFIRME','ANNULE','HONORE') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PLANIFIE',
  `client_id` int UNSIGNED DEFAULT NULL,
  `commercial_id` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reservations_hotel`
--

CREATE TABLE `reservations_hotel` (
  `id` int UNSIGNED NOT NULL,
  `date_reservation` date NOT NULL,
  `client_id` int UNSIGNED NOT NULL,
  `chambre_id` int UNSIGNED NOT NULL,
  `date_debut` date NOT NULL,
  `date_fin` date NOT NULL,
  `nb_nuits` int NOT NULL,
  `montant_total` decimal(15,2) NOT NULL DEFAULT '0.00',
  `statut` enum('EN_COURS','TERMINEE','ANNULEE') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'EN_COURS',
  `mode_paiement_id` int UNSIGNED DEFAULT NULL,
  `concierge_id` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `retours_litiges`
--

CREATE TABLE `retours_litiges` (
  `id` int UNSIGNED NOT NULL,
  `date_retour` date NOT NULL,
  `client_id` int UNSIGNED NOT NULL,
  `produit_id` int UNSIGNED NOT NULL,
  `vente_id` int UNSIGNED DEFAULT NULL,
  `motif` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `type_probleme` enum('DEFAUT_PRODUIT','ERREUR_LIVRAISON','INSATISFACTION_CLIENT','AUTRE') COLLATE utf8mb4_unicode_ci DEFAULT 'AUTRE',
  `responsable_suivi_id` int UNSIGNED NOT NULL,
  `statut_traitement` enum('EN_COURS','RESOLU','ABANDONNE') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'EN_COURS',
  `solution` text COLLATE utf8mb4_unicode_ci,
  `montant_rembourse` decimal(15,2) DEFAULT '0.00',
  `montant_avoir` decimal(15,2) DEFAULT '0.00',
  `date_resolution` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int UNSIGNED NOT NULL,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
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
-- Table structure for table `role_permission`
--

CREATE TABLE `role_permission` (
  `role_id` int UNSIGNED NOT NULL,
  `permission_id` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role_permission`
--

INSERT INTO `role_permission` (`role_id`, `permission_id`) VALUES
(1, 1),
(2, 1),
(3, 1),
(4, 1),
(6, 1),
(1, 2),
(1, 3),
(4, 3),
(1, 4),
(1, 5),
(2, 5),
(3, 5),
(6, 5),
(1, 6),
(2, 6),
(3, 6),
(1, 7),
(2, 7),
(3, 7),
(6, 7),
(1, 8),
(2, 8),
(3, 8),
(1, 9),
(2, 9),
(3, 9),
(1, 10),
(2, 10),
(3, 10),
(4, 10),
(5, 10),
(6, 10),
(1, 11),
(2, 11),
(3, 11),
(1, 12),
(4, 12),
(1, 13),
(5, 13),
(6, 13),
(1, 14),
(5, 14),
(1, 15),
(1, 16),
(6, 16),
(1, 17),
(6, 17),
(1, 18),
(2, 18),
(3, 18),
(4, 18),
(5, 18),
(6, 18),
(1, 19),
(2, 19),
(3, 19),
(6, 19),
(1, 20),
(1, 21),
(1, 22),
(1, 23),
(6, 23);

-- --------------------------------------------------------

--
-- Table structure for table `ruptures_signalees`
--

CREATE TABLE `ruptures_signalees` (
  `id` int UNSIGNED NOT NULL,
  `date_signalement` date NOT NULL,
  `produit_id` int UNSIGNED NOT NULL,
  `seuil_alerte` decimal(15,3) NOT NULL,
  `stock_actuel` decimal(15,3) NOT NULL,
  `impact_commercial` text COLLATE utf8mb4_unicode_ci COMMENT 'Ventes perdues, clients m??contents, etc.',
  `action_proposee` text COLLATE utf8mb4_unicode_ci COMMENT 'R??appro urgent, promotion, produit alternatif',
  `magasinier_id` int UNSIGNED NOT NULL,
  `statut_traitement` enum('SIGNALE','EN_COURS','RESOLU','ABANDONNE') COLLATE utf8mb4_unicode_ci DEFAULT 'SIGNALE',
  `date_resolution` datetime DEFAULT NULL,
  `commentaire_resolution` text COLLATE utf8mb4_unicode_ci,
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Alertes ruptures stock (magasin ??? marketing)';

-- --------------------------------------------------------

--
-- Table structure for table `ruptures_stock`
--

CREATE TABLE `ruptures_stock` (
  `id` int UNSIGNED NOT NULL,
  `date_rapport` date NOT NULL,
  `produit_id` int UNSIGNED NOT NULL,
  `seuil_alerte` int NOT NULL,
  `stock_actuel` int NOT NULL,
  `impact_commercial` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `action_proposee` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `magasinier_id` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `satisfaction_clients`
--

CREATE TABLE `satisfaction_clients` (
  `id` int UNSIGNED NOT NULL,
  `date_satisfaction` date NOT NULL,
  `client_id` int UNSIGNED DEFAULT NULL,
  `nom_client` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `service_utilise` enum('SHOWROOM','HOTEL','FORMATION','TERRAIN','DIGITAL') COLLATE utf8mb4_unicode_ci NOT NULL,
  `note` int NOT NULL,
  `commentaire` text COLLATE utf8mb4_unicode_ci,
  `utilisateur_id` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sessions_actives`
--

CREATE TABLE `sessions_actives` (
  `id` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Session ID',
  `utilisateur_id` int UNSIGNED NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_agent` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `device_fingerprint` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Empreinte du device',
  `pays` varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Code pays ISO',
  `ville` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  `date_derniere_activite` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `date_expiration` datetime NOT NULL,
  `actif` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Sessions actives avec tracking d??taill??';

--
-- Dumping data for table `sessions_actives`
--

INSERT INTO `sessions_actives` (`id`, `utilisateur_id`, `ip_address`, `user_agent`, `device_fingerprint`, `pays`, `ville`, `date_creation`, `date_derniere_activite`, `date_expiration`, `actif`) VALUES
('0173ceaac06061745fbc9e486a56a63a', 2, '143.105.153.219', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', NULL, NULL, NULL, '2025-12-29 04:19:31', '2025-12-29 04:19:31', '2025-12-29 06:19:31', 1),
('81b4277bd52d9dc276b069d3dbcbdf9b', 2, '143.105.152.69', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:146.0) Gecko/20100101 Firefox/146.0', NULL, NULL, NULL, '2025-12-29 07:23:33', '2025-12-29 07:23:33', '2025-12-29 09:23:33', 1),
('a746f9971e84767ad1a3eb986a1877db', 1, '143.105.153.219', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', NULL, NULL, NULL, '2025-12-29 06:26:44', '2025-12-29 06:26:44', '2025-12-29 08:26:44', 1),
('e3fdc004b11d5f6823485180179fbd2f', 2, '143.105.153.219', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', NULL, NULL, NULL, '2025-12-29 04:23:15', '2025-12-29 04:23:15', '2025-12-29 06:23:15', 1);

-- --------------------------------------------------------

--
-- Table structure for table `sms_2fa_codes`
--

CREATE TABLE `sms_2fa_codes` (
  `id` int UNSIGNED NOT NULL,
  `utilisateur_id` int NOT NULL,
  `code_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Hash du code ?? 6 chiffres',
  `telephone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Num??ro au format international',
  `expire_a` datetime NOT NULL COMMENT 'Date d''expiration (5 min)',
  `utilise` tinyint(1) DEFAULT '0' COMMENT '0 = non utilis??, 1 = utilis??',
  `utilise_a` datetime DEFAULT NULL COMMENT 'Date d''utilisation',
  `cree_a` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Codes SMS temporaires pour authentification 2FA';

-- --------------------------------------------------------

--
-- Table structure for table `sms_tracking`
--

CREATE TABLE `sms_tracking` (
  `id` int UNSIGNED NOT NULL,
  `utilisateur_id` int NOT NULL,
  `telephone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `envoye_a` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Historique d''envoi des SMS pour d??tection d''abus';

-- --------------------------------------------------------

--
-- Table structure for table `sous_categories_produits`
--

CREATE TABLE `sous_categories_produits` (
  `id` int UNSIGNED NOT NULL,
  `famille_id` int UNSIGNED NOT NULL,
  `nom` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sous_categories_produits`
--

INSERT INTO `sous_categories_produits` (`id`, `famille_id`, `nom`) VALUES
(1, 1, 'Chambres é coucher'),
(2, 1, 'Salons'),
(3, 2, 'Quincaillerie standard'),
(4, 3, 'Machines de découpe'),
(5, 4, 'Panneaux mélaminés');

-- --------------------------------------------------------

--
-- Table structure for table `stocks_mouvements`
--

CREATE TABLE `stocks_mouvements` (
  `id` int UNSIGNED NOT NULL,
  `produit_id` int UNSIGNED NOT NULL,
  `date_mouvement` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `type_mouvement` enum('ENTREE','SORTIE','AJUSTEMENT') COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantite` int NOT NULL,
  `source_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_id` int DEFAULT NULL,
  `commentaire` text COLLATE utf8mb4_unicode_ci,
  `utilisateur_id` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tentatives_connexion`
--

CREATE TABLE `tentatives_connexion` (
  `id` bigint UNSIGNED NOT NULL,
  `login_attempt` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Login tent??',
  `utilisateur_id` int UNSIGNED DEFAULT NULL COMMENT 'NULL si login invalide',
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_agent` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `methode_2fa` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'TOTP, RECOVERY, NONE',
  `succes` tinyint(1) NOT NULL,
  `raison_echec` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Mot de passe incorrect, 2FA invalide, compte bloqu??',
  `pays` varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ville` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_tentative` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Historique d??taill?? des tentatives de connexion';

-- --------------------------------------------------------

--
-- Table structure for table `types_client`
--

CREATE TABLE `types_client` (
  `id` int UNSIGNED NOT NULL,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `libelle` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `types_client`
--

INSERT INTO `types_client` (`id`, `code`, `libelle`) VALUES
(1, 'SHOWROOM', 'Client / prospect showroom'),
(2, 'TERRAIN', 'Client / prospect terrain'),
(3, 'DIGITAL', 'Client issu du digital (réseaux sociaux, site, CRM)'),
(4, 'HOTEL', 'Client hébergement / hôtel'),
(5, 'FORMATION', 'Apprenant / client formation');

-- --------------------------------------------------------

--
-- Table structure for table `upsell_hotel`
--

CREATE TABLE `upsell_hotel` (
  `id` int UNSIGNED NOT NULL,
  `reservation_id` int UNSIGNED NOT NULL,
  `service_additionnel` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `montant` decimal(15,2) NOT NULL DEFAULT '0.00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_preferences`
--

CREATE TABLE `user_preferences` (
  `id` int NOT NULL,
  `utilisateur_id` int NOT NULL,
  `page_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ex: ventes, livraisons, litiges',
  `sort_by` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'date' COMMENT 'colonne de tri',
  `sort_dir` varchar(4) COLLATE utf8mb4_unicode_ci DEFAULT 'desc' COMMENT 'asc ou desc',
  `per_page` int DEFAULT '25' COMMENT 'résultats par page (10, 25, 50, 100)',
  `remember_filters` tinyint(1) DEFAULT '1' COMMENT 'conserver les filtres',
  `default_date_range` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'last_7d, last_30d, last_90d, this_month',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Préférences utilisateur par page (tri, pagination, filtres)';

--
-- Dumping data for table `user_preferences`
--

INSERT INTO `user_preferences` (`id`, `utilisateur_id`, `page_name`, `sort_by`, `sort_dir`, `per_page`, `remember_filters`, `default_date_range`, `created_at`, `updated_at`) VALUES
(1, 2, 'produits_internes', 'date', 'desc', 25, 1, NULL, '2025-12-29 13:21:28', '2025-12-29 13:25:22');

-- --------------------------------------------------------

--
-- Table structure for table `utilisateurs`
--

CREATE TABLE `utilisateurs` (
  `id` int UNSIGNED NOT NULL,
  `login` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mot_de_passe_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nom_complet` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telephone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_derniere_connexion` datetime DEFAULT NULL,
  `date_changement_mdp` datetime DEFAULT NULL COMMENT 'Date dernier changement mot de passe',
  `mdp_expire` tinyint(1) DEFAULT '0' COMMENT 'Mot de passe expir??',
  `force_changement_mdp` tinyint(1) DEFAULT '0' COMMENT 'Forcer changement au prochain login',
  `compte_verrouille` tinyint(1) DEFAULT '0' COMMENT 'Compte verrouill?? (manuel)',
  `raison_verrouillage` text COLLATE utf8mb4_unicode_ci,
  `date_verrouillage` datetime DEFAULT NULL,
  `sessions_simultanees_actuelles` int DEFAULT '0' COMMENT 'Compteur sessions actives'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `utilisateurs`
--

INSERT INTO `utilisateurs` (`id`, `login`, `mot_de_passe_hash`, `nom_complet`, `email`, `telephone`, `actif`, `date_creation`, `date_derniere_connexion`, `date_changement_mdp`, `mdp_expire`, `force_changement_mdp`, `compte_verrouille`, `raison_verrouillage`, `date_verrouillage`, `sessions_simultanees_actuelles`) VALUES
(1, 'admin', '$2b$10$j6YYUX.QLOxOoBn9eB4rJu8/ye4/NOEXPvRjcYhUY4mBiaZZFUrTi', 'Administrateur KMS', 'admin@kms.local', NULL, 1, '2025-11-18 10:59:28', '2025-12-29 06:26:44', NULL, 0, 0, 0, NULL, NULL, 0),
(2, 'admin2', '$2y$12$G5l1FkhT.T1k1xctjbB4d.AJr14YyDKXvBLx1PxRwLNhiapkAn2ZW', 'Administrateur Systéme', 'peghstartup.assistance@gmail.com', '695657613', 1, '2025-12-11 11:56:20', '2025-12-29 07:23:33', NULL, 0, 0, 0, NULL, NULL, 0),
(3, 'showroom1', '$2y$12$/j.JTzSkqzPx5YmxqDzvceOSQhFewvVtQZ6uLxzBKhT8lRIxe2gQm', 'Marie Kouadio', 'marie.kouadio@kms.local', NULL, 1, '2025-12-11 11:56:20', '2025-12-29 04:11:39', NULL, 0, 0, 0, NULL, NULL, 0),
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
-- Table structure for table `utilisateurs_2fa`
--

CREATE TABLE `utilisateurs_2fa` (
  `id` int UNSIGNED NOT NULL,
  `utilisateur_id` int UNSIGNED NOT NULL,
  `secret` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Secret TOTP encod??',
  `actif` tinyint(1) DEFAULT '0',
  `date_activation` datetime DEFAULT NULL,
  `date_desactivation` datetime DEFAULT NULL,
  `methode` enum('TOTP','SMS','EMAIL') COLLATE utf8mb4_unicode_ci DEFAULT 'TOTP',
  `telephone_backup` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_backup` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  `methode_2fa` enum('totp','sms') COLLATE utf8mb4_unicode_ci DEFAULT 'totp' COMMENT 'M??thode 2FA: TOTP ou SMS',
  `telephone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Num??ro de t??l??phone au format international'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Configuration 2FA par utilisateur';

--
-- Dumping data for table `utilisateurs_2fa`
--

INSERT INTO `utilisateurs_2fa` (`id`, `utilisateur_id`, `secret`, `actif`, `date_activation`, `date_desactivation`, `methode`, `telephone_backup`, `email_backup`, `date_creation`, `methode_2fa`, `telephone`) VALUES
(1, 1, '', 0, '2025-12-13 13:17:56', '2025-12-29 04:38:36', 'EMAIL', NULL, 'peghiembouoromial@gmail.com', '2025-12-13 13:17:56', 'totp', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `utilisateurs_2fa_recovery`
--

CREATE TABLE `utilisateurs_2fa_recovery` (
  `id` int UNSIGNED NOT NULL,
  `utilisateur_id` int UNSIGNED NOT NULL,
  `code_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Hash du code de r??cup??ration',
  `utilise` tinyint(1) DEFAULT '0',
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  `date_utilisation` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Codes de r??cup??ration 2FA (backup)';

-- --------------------------------------------------------

--
-- Table structure for table `utilisateur_role`
--

CREATE TABLE `utilisateur_role` (
  `utilisateur_id` int UNSIGNED NOT NULL,
  `role_id` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `utilisateur_role`
--

INSERT INTO `utilisateur_role` (`utilisateur_id`, `role_id`) VALUES
(1, 1),
(2, 1),
(2, 2),
(3, 2),
(4, 2),
(13, 2),
(2, 3),
(3, 3),
(5, 3),
(6, 3),
(13, 3),
(2, 4),
(7, 4),
(8, 4),
(2, 5),
(3, 5),
(9, 5),
(10, 5),
(13, 5),
(2, 6),
(11, 6),
(12, 6),
(14, 6);

-- --------------------------------------------------------

--
-- Table structure for table `ventes`
--

CREATE TABLE `ventes` (
  `id` int UNSIGNED NOT NULL,
  `numero` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_vente` date NOT NULL,
  `client_id` int UNSIGNED NOT NULL,
  `canal_vente_id` int UNSIGNED NOT NULL,
  `devis_id` int UNSIGNED DEFAULT NULL,
  `statut` enum('DEVIS','DEVIS_ACCEPTE','EN_ATTENTE_LIVRAISON','EN_PREPARATION','PRET_LIVRAISON','PARTIELLEMENT_LIVREE','LIVREE','FACTUREE','PAYEE','ANNULEE') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'EN_ATTENTE_LIVRAISON',
  `statut_encaissement` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT 'ATTENTE_PAIEMENT',
  `journal_caisse_id` int UNSIGNED DEFAULT NULL,
  `montant_total_ht` decimal(15,2) NOT NULL DEFAULT '0.00',
  `montant_total_ttc` decimal(15,2) NOT NULL DEFAULT '0.00',
  `utilisateur_id` int UNSIGNED NOT NULL,
  `commentaires` text COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ventes_lignes`
--

CREATE TABLE `ventes_lignes` (
  `id` int UNSIGNED NOT NULL,
  `vente_id` int UNSIGNED NOT NULL,
  `produit_id` int UNSIGNED NOT NULL,
  `quantite` int NOT NULL,
  `prix_unitaire` decimal(15,2) NOT NULL,
  `remise` decimal(15,2) NOT NULL DEFAULT '0.00',
  `montant_ligne_ht` decimal(15,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `visiteurs_hotel`
--

CREATE TABLE `visiteurs_hotel` (
  `id` int UNSIGNED NOT NULL,
  `date_visite` date NOT NULL,
  `nom_visiteur` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telephone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `motif` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `service_solicite` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `orientation` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `concierge_id` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `visiteurs_showroom`
--

CREATE TABLE `visiteurs_showroom` (
  `id` int UNSIGNED NOT NULL,
  `date_visite` date NOT NULL,
  `client_nom` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `produit_interet` text COLLATE utf8mb4_unicode_ci,
  `orientation` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `client_id` int UNSIGNED DEFAULT NULL,
  `utilisateur_id` int UNSIGNED NOT NULL,
  `converti_en_devis` tinyint(1) NOT NULL DEFAULT '0',
  `converti_en_vente` tinyint(1) NOT NULL DEFAULT '0',
  `date_conversion` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_pipeline_commercial`
-- (See below for the actual view)
--
CREATE TABLE `v_pipeline_commercial` (
`canal` varchar(8)
,`source_id` int unsigned
,`prospect_nom` varchar(150)
,`date_entree` date
,`converti_en_devis` bigint
,`converti_en_vente` bigint
,`statut_pipeline` enum('NOUVEAU','CONTACTE','QUALIFIE','DEVIS_ENVOYE','CONVERTI','PERDU')
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_ventes_livraison_encaissement`
-- (See below for the actual view)
--
CREATE TABLE `v_ventes_livraison_encaissement` (
`id` int unsigned
,`numero` varchar(50)
,`date_vente` date
,`montant_total_ttc` decimal(15,2)
,`statut_vente` enum('DEVIS','DEVIS_ACCEPTE','EN_ATTENTE_LIVRAISON','EN_PREPARATION','PRET_LIVRAISON','PARTIELLEMENT_LIVREE','LIVREE','FACTUREE','PAYEE','ANNULEE')
,`statut_livraison` varchar(9)
,`montant_encaisse` decimal(37,2)
,`solde_du` decimal(38,2)
);

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
-- Indexes for table `caisses_clotures`
--
ALTER TABLE `caisses_clotures`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_date` (`date_cloture`),
  ADD KEY `idx_statut` (`statut`),
  ADD KEY `idx_caissier` (`caissier_id`),
  ADD KEY `validateur_id` (`validateur_id`);

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
-- Indexes for table `user_preferences`
--
ALTER TABLE `user_preferences`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_page` (`utilisateur_id`,`page_name`),
  ADD KEY `idx_utilisateur` (`utilisateur_id`),
  ADD KEY `idx_page` (`page_name`);

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
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `achats_lignes`
--
ALTER TABLE `achats_lignes`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `blocages_ip`
--
ALTER TABLE `blocages_ip`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bons_livraison`
--
ALTER TABLE `bons_livraison`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bons_livraison_lignes`
--
ALTER TABLE `bons_livraison_lignes`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `caisses_clotures`
--
ALTER TABLE `caisses_clotures`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `caisse_journal`
--
ALTER TABLE `caisse_journal`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `canaux_vente`
--
ALTER TABLE `canaux_vente`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `catalogue_categories`
--
ALTER TABLE `catalogue_categories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `catalogue_produits`
--
ALTER TABLE `catalogue_produits`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chambres`
--
ALTER TABLE `chambres`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `compta_comptes`
--
ALTER TABLE `compta_comptes`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=118;

--
-- AUTO_INCREMENT for table `compta_ecritures`
--
ALTER TABLE `compta_ecritures`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `compta_exercices`
--
ALTER TABLE `compta_exercices`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `compta_journaux`
--
ALTER TABLE `compta_journaux`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `compta_mapping_operations`
--
ALTER TABLE `compta_mapping_operations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `compta_operations_trace`
--
ALTER TABLE `compta_operations_trace`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `compta_pieces`
--
ALTER TABLE `compta_pieces`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `connexions_utilisateur`
--
ALTER TABLE `connexions_utilisateur`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `conversions_pipeline`
--
ALTER TABLE `conversions_pipeline`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `devis`
--
ALTER TABLE `devis`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `devis_lignes`
--
ALTER TABLE `devis_lignes`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `familles_produits`
--
ALTER TABLE `familles_produits`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- AUTO_INCREMENT for table `formations`
--
ALTER TABLE `formations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fournisseurs`
--
ALTER TABLE `fournisseurs`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inscriptions_formation`
--
ALTER TABLE `inscriptions_formation`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `journal_caisse`
--
ALTER TABLE `journal_caisse`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `kpis_quotidiens`
--
ALTER TABLE `kpis_quotidiens`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leads_digital`
--
ALTER TABLE `leads_digital`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `modes_paiement`
--
ALTER TABLE `modes_paiement`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `mouvements_stock_backup_20251209_161710`
--
ALTER TABLE `mouvements_stock_backup_20251209_161710`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `objectifs_commerciaux`
--
ALTER TABLE `objectifs_commerciaux`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ordres_preparation`
--
ALTER TABLE `ordres_preparation`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ordres_preparation_lignes`
--
ALTER TABLE `ordres_preparation_lignes`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `parametres_securite`
--
ALTER TABLE `parametres_securite`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `produits`
--
ALTER TABLE `produits`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=137;

--
-- AUTO_INCREMENT for table `promotions`
--
ALTER TABLE `promotions`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prospections_terrain`
--
ALTER TABLE `prospections_terrain`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prospects_formation`
--
ALTER TABLE `prospects_formation`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `relances_devis`
--
ALTER TABLE `relances_devis`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `rendezvous_terrain`
--
ALTER TABLE `rendezvous_terrain`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reservations_hotel`
--
ALTER TABLE `reservations_hotel`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `retours_litiges`
--
ALTER TABLE `retours_litiges`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `ruptures_signalees`
--
ALTER TABLE `ruptures_signalees`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ruptures_stock`
--
ALTER TABLE `ruptures_stock`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `satisfaction_clients`
--
ALTER TABLE `satisfaction_clients`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sms_2fa_codes`
--
ALTER TABLE `sms_2fa_codes`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sms_tracking`
--
ALTER TABLE `sms_tracking`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sous_categories_produits`
--
ALTER TABLE `sous_categories_produits`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `stocks_mouvements`
--
ALTER TABLE `stocks_mouvements`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tentatives_connexion`
--
ALTER TABLE `tentatives_connexion`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `types_client`
--
ALTER TABLE `types_client`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `upsell_hotel`
--
ALTER TABLE `upsell_hotel`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_preferences`
--
ALTER TABLE `user_preferences`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `utilisateurs_2fa`
--
ALTER TABLE `utilisateurs_2fa`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `utilisateurs_2fa_recovery`
--
ALTER TABLE `utilisateurs_2fa_recovery`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ventes`
--
ALTER TABLE `ventes`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ventes_lignes`
--
ALTER TABLE `ventes_lignes`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `visiteurs_hotel`
--
ALTER TABLE `visiteurs_hotel`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `visiteurs_showroom`
--
ALTER TABLE `visiteurs_showroom`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

-- --------------------------------------------------------

--
-- Structure for view `v_pipeline_commercial`
--
DROP TABLE IF EXISTS `v_pipeline_commercial`;

CREATE OR REPLACE VIEW `v_pipeline_commercial` AS SELECT 'SHOWROOM' AS `canal`, `vs`.`id` AS `source_id`, `vs`.`client_nom` AS `prospect_nom`, `vs`.`date_visite` AS `date_entree`, 0 AS `converti_en_devis`, 0 AS `converti_en_vente`, NULL AS `statut_pipeline` FROM `visiteurs_showroom` AS `vs`union all select 'TERRAIN' AS `canal`,`pt`.`id` AS `source_id`,`pt`.`prospect_nom` AS `prospect_nom`,`pt`.`date_prospection` AS `date_entree`,0 AS `converti_en_devis`,0 AS `converti_en_vente`,NULL AS `statut_pipeline` from `prospections_terrain` `pt` union all select 'DIGITAL' AS `canal`,`ld`.`id` AS `source_id`,`ld`.`nom_prospect` AS `prospect_nom`,`ld`.`date_lead` AS `date_entree`,(`ld`.`statut` in ('DEVIS_ENVOYE','CONVERTI')) AS `converti_en_devis`,(`ld`.`statut` = 'CONVERTI') AS `converti_en_vente`,`ld`.`statut` AS `statut_pipeline` from `leads_digital` `ld`  ;

-- --------------------------------------------------------

--
-- Structure for view `v_ventes_livraison_encaissement`
--
DROP TABLE IF EXISTS `v_ventes_livraison_encaissement`;

CREATE OR REPLACE VIEW `v_ventes_livraison_encaissement` AS SELECT `v`.`id` AS `id`, `v`.`numero` AS `numero`, `v`.`date_vente` AS `date_vente`, `v`.`montant_total_ttc` AS `montant_total_ttc`, `v`.`statut` AS `statut_vente`, (case when exists(select 1 from `bons_livraison` `bl` where ((`bl`.`vente_id` = `v`.`id`) and (`bl`.`signe_client` = 1))) then 'LIVRE' else 'NON_LIVRE' end) AS `statut_livraison`, coalesce((select sum(`jc`.`montant`) from `journal_caisse` `jc` where (`jc`.`vente_id` = `v`.`id`)),0) AS `montant_encaisse`, (`v`.`montant_total_ttc` - coalesce((select sum(`jc`.`montant`) from `journal_caisse` `jc` where (`jc`.`vente_id` = `v`.`id`)),0)) AS `solde_du` FROM `ventes` AS `v` ;

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
-- Constraints for table `caisses_clotures`
--
ALTER TABLE `caisses_clotures`
  ADD CONSTRAINT `caisses_clotures_ibfk_1` FOREIGN KEY (`caissier_id`) REFERENCES `utilisateurs` (`id`),
  ADD CONSTRAINT `caisses_clotures_ibfk_2` FOREIGN KEY (`validateur_id`) REFERENCES `utilisateurs` (`id`);

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
