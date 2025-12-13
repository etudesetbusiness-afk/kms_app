-- Schéma du module Catalogue public avec jeu de données enrichi
-- Tables dédiées, sans impact sur le stock / ventes / comptabilité.

CREATE TABLE IF NOT EXISTS catalogue_categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nom VARCHAR(150) NOT NULL,
  slug VARCHAR(180) NOT NULL UNIQUE,
  actif TINYINT(1) NOT NULL DEFAULT 1,
  ordre INT NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS catalogue_produits (
  id INT AUTO_INCREMENT PRIMARY KEY,
  produit_id INT NULL,
  code VARCHAR(100) NOT NULL UNIQUE,
  slug VARCHAR(180) NOT NULL UNIQUE,
  designation VARCHAR(255) NOT NULL,
  categorie_id INT NOT NULL,
  prix_unite DECIMAL(15,2) NULL,
  prix_gros DECIMAL(15,2) NULL,
  description TEXT NULL,
  caracteristiques_json JSON NULL,
  image_principale VARCHAR(255) NULL,
  galerie_images JSON NULL,
  actif TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_catalogue_categorie FOREIGN KEY (categorie_id) REFERENCES catalogue_categories(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Données de démonstration enrichies
INSERT INTO catalogue_categories (nom, slug, actif, ordre) VALUES
('Panneaux & Contreplaqués', 'panneaux', 1, 1),
('Machines & Outils', 'machines', 1, 2),
('Quincaillerie', 'quincaillerie', 1, 3),
('Accessoires Menuiserie', 'accessoires', 1, 4),
('Bois Brut', 'bois-brut', 1, 5),
('Finitions & Vernis', 'finitions', 1, 6)
ON DUPLICATE KEY UPDATE nom = VALUES(nom), actif = VALUES(actif), ordre = VALUES(ordre);

INSERT INTO catalogue_produits (
    code, slug, designation, categorie_id, prix_unite, prix_gros, description, caracteristiques_json, image_principale, galerie_images, actif
) VALUES

-- Panneaux & Contreplaqués (6 produits)
('PLQ-CTBX-18', 'plaque-ctbx-18mm', 'Panneau CTBX 18 mm', 19, 29500.00, 27500.00,
 'Panneau contreplaqué CTBX haute résistance, idéal pour milieux humides et intérieurs modernes.',
 JSON_OBJECT('Épaisseur', '18 mm', 'Dimensions', '1220 x 2440 mm', 'Essence', 'Okoumé', 'Classe', 'Extérieur'),
 NULL, JSON_ARRAY(), 1),

('PLQ-CTBX-12', 'plaque-ctbx-12mm', 'Panneau CTBX 12 mm', 19, 22000.00, 20500.00,
 'Contreplaqué fin CTBX pour mobilier intérieur et agencements légers.',
 JSON_OBJECT('Épaisseur', '12 mm', 'Dimensions', '1220 x 2440 mm', 'Essence', 'Okoumé', 'Finition', 'Brut'),
 NULL, JSON_ARRAY(), 1),

('MDF-25', 'mdf-25mm', 'Panneau MDF 25 mm', 19, 18500.00, 17000.00,
 'Medium Density Fiberboard, parfait pour menuiserie intérieure, portes et placards.',
 JSON_OBJECT('Épaisseur', '25 mm', 'Dimensions', '1220 x 2440 mm', 'Densité', '730 kg/m³', 'Usage', 'Intérieur'),
 NULL, JSON_ARRAY(), 1),

('MDF-16', 'mdf-16mm', 'Panneau MDF 16 mm', 19, 13200.00, 12300.00,
 'MDF standard pour mobilier et revêtements intérieurs. Facile à usiner et peindre.',
 JSON_OBJECT('Épaisseur', '16 mm', 'Dimensions', '1220 x 2440 mm', 'Densité', '720 kg/m³'),
 NULL, JSON_ARRAY(), 1),

('HDF-3MM', 'hdf-3mm-laminate', 'Panneau HDF 3 mm laminé', 19, 8900.00, 8200.00,
 'Panneau haute densité avec revêtement mélaminé pour plans de travail et surfaces de travail.',
 JSON_OBJECT('Épaisseur', '3 mm', 'Dimensions', '1220 x 2440 mm', 'Revêtement', 'Mélaminé', 'Finition', 'Brillant'),
 NULL, JSON_ARRAY(), 1),

('MULTIPLEX-21', 'multiplex-21mm', 'Multiplex 21 mm', 19, 24500.00, 22800.00,
 'Contreplaqué multiplis pour construction légère, étagères et agencement intérieur.',
 JSON_OBJECT('Épaisseur', '21 mm', 'Dimensions', '1220 x 2440 mm', 'Plis', '13', 'Grade', 'BB'),
 NULL, JSON_ARRAY(), 1),

-- Machines & Outils (8 produits)
('SCIE-RBT-210', 'scie-ruban-210', 'Scie à Ruban 210 W', 20, 185000.00, 172000.00,
 'Scie à ruban compacte et performante pour ateliers professionnels. Coupe précise bois, dérivés et matériaux composites.',
 JSON_OBJECT('Hauteur coupe', '210 mm', 'Puissance', '1.5 kW', 'Alimentation', '220V', 'Capacité', 'Bois jusquà 150 mm'),
 NULL, JSON_ARRAY(), 1),

('DECOLLET-400', 'decolleteur-400', 'Décolleteuse 400 mm', 20, 245000.00, 225000.00,
 'Machine de découpe précise pour panneaux, contreplaqué et composites. Guide de profondeur ajustable.',
 JSON_OBJECT('Diamètre lame', '400 mm', 'Puissance', '2.2 kW', 'Vitesse', '42 rpm', 'Précision', '±0.5 mm'),
 NULL, JSON_ARRAY(), 1),

('RABOTEUSE-305', 'raboteuse-305mm', 'Raboteuse 305 mm', 20, 320000.00, 295000.00,
 'Raboteuse professionnelle pour lissage de pièces brutes. Système d\'alimentation variable.',
 JSON_OBJECT('Largeur travail', '305 mm', 'Puissance', '3 kW', 'Capacité épaisseur', '150 mm', 'Rendement', '8 m/min'),
 NULL, JSON_ARRAY(), 1),

('TOUPIE-2200', 'toupie-wood-2200', 'Toupie 2200 W', 20, 425000.00, 395000.00,
 'Toupillage haute puissance pour fraisage, rainurage et profilage. Moteur brushless haute vitesse.',
 JSON_OBJECT('Puissance', '2200 W', 'Vitesse', '8000-24000 rpm', 'Capacité', 'Mèches 6-12 mm', 'Table', 'Acier'),
 NULL, JSON_ARRAY(), 1),

('SABLEUSE-ORBITALE', 'sableuse-orbitale-225', 'Sableuse Orbitale 225 mm', 20, 48900.00, 45000.00,
 'Sableuse orbitale pour finition haute qualité. Vibration minimale et système d\'aspiration intégré.',
 JSON_OBJECT('Disque', '225 mm', 'Puissance', '520 W', 'Mouvements/min', '4800', 'Aspiration', '36 L/min'),
 NULL, JSON_ARRAY(), 1),

('PERCEUSE-16', 'perceuse-percussion-16', 'Perceuse à Percussion 16 mm', 20, 35500.00, 32800.00,
 'Perceuse-visseuse professionnelle avec mode percussion pour travaux lourds en atelier.',
 JSON_OBJECT('Capacité', '16 mm', 'Puissance', '900 W', 'Couple', '45 Nm', 'Vitesses', 'Variable'),
 NULL, JSON_ARRAY(), 1),

('VISSEUSE-ECO', 'visseuse-sans-fil-18v', 'Visseuse sans-fil 18V', 20, 18900.00, 17500.00,
 'Visseuse compacte avec batterie Li-Ion pour assemblage et finition intérieure.',
 JSON_OBJECT('Tension', '18 V', 'Batterie', 'Li-Ion 1.5 Ah', 'Couple', '30 Nm', 'Poids', '1.2 kg'),
 NULL, JSON_ARRAY(), 1),

('MEULEUSE-900', 'meuleuse-125mm-900w', 'Meuleuse 125 mm 900 W', 20, 22300.00, 20500.00,
 'Meuleuse d\'angle compact pour découpe, meulage et travaux de finition rapides.',
 JSON_OBJECT('Diamètre disque', '125 mm', 'Puissance', '900 W', 'Vitesse', '12000 rpm', 'Poignée', 'Latérale'),
 NULL, JSON_ARRAY(), 1),

-- Quincaillerie (7 produits)
('CHARN-INOX-90', 'charniere-inox-90', 'Charnière Inox 90°', 21, 950.00, 850.00,
 'Charnière pour portes meubles en acier inoxydable 304. Fermeture douce sans bruit.',
 JSON_OBJECT('Matière', 'Inox 304', 'Finition', 'Brossé', 'Angle', '90°', 'Capacité', '30 kg'),
 NULL, JSON_ARRAY(), 1),

('CHARN-SOFT-CLOSE', 'charniere-soft-close-35', 'Charnière Soft-Close 35 mm', 21, 2800.00, 2550.00,
 'Système de fermeture douce intégré. Fermeture progressive et silencieuse pour tous types de portes.',
 JSON_OBJECT('Type', 'Overlay', 'Ouverture', '110°', 'Capacité', '40 kg', 'Installation', 'Invisible'),
 NULL, JSON_ARRAY(), 1),

('POIGNEE-ALU-160', 'poignee-aluminium-160', 'Poignée Aluminium 160 mm', 21, 1200.00, 1050.00,
 'Poignée contemporaine en aluminium anodisé. Design épuré pour tous styles de mobilier.',
 JSON_OBJECT('Longueur', '160 mm', 'Matière', 'Aluminium anodisé', 'Finition', 'Noir/Argent', 'Distance trous', '128 mm'),
 NULL, JSON_ARRAY(), 1),

('SERRURE-PUSH', 'serrure-push-open', 'Serrure Push-to-Open', 21, 3500.00, 3200.00,
 'Système d\'ouverture sans poignée par simple pression. Intégration discrète dans le mobilier.',
 JSON_OBJECT('Tension', '24 V', 'Charge', '60 kg', 'Temps fermeture', '3 sec', 'Installation', 'Dissimulée'),
 NULL, JSON_ARRAY(), 1),

('GLISSIERE-TELESCOP', 'glissiere-telescopique-500', 'Glissière Télescopique 500 mm', 21, 4200.00, 3850.00,
 'Rails de qualité supérieure pour tiroirs professionnels. Mécanisme d\'extension complète 100%.',
 JSON_OBJECT('Course', '500 mm', 'Charge', '80 kg', 'Roulements', 'Billes', 'Fermeture', 'Soft-close'),
 NULL, JSON_ARRAY(), 1),

('LOQUETEAUX-MAGNETI', 'loqueteau-magnetique-doux', 'Loqueteau Magnétique Doux', 21, 680.00, 580.00,
 'Fermeture magnétique avec amortissement. Parfait pour portes vitrées et façades légères.',
 JSON_OBJECT('Force', '5 kg', 'Matière', 'Alliage métallique', 'Installation', 'Facile', 'Finition', 'Chromé'),
 NULL, JSON_ARRAY(), 1),

('CLOUS-ACIER-65', 'clous-acier-zinc-65mm', 'Clous Acier Zingué 65 mm', 21, 450.00, 380.00,
 'Clous acier galvanisé pour assemblage robuste. Résistance à la corrosion garantie.',
 JSON_OBJECT('Longueur', '65 mm', 'Diamètre', '3.75 mm', 'Galvanisé', 'Oui', 'Emballage', '1 kg'),
 NULL, JSON_ARRAY(), 1),

-- Accessoires Menuiserie (6 produits)
('JOINT-SILICONE', 'joint-silicone-translucide', 'Joint Silicone Translucide', 22, 890.00, 750.00,
 'Scellant silicone haute flexibilité pour joints bois et menuiseries. Imperméable et durable.',
 JSON_OBJECT('Volume', '300 ml', 'Temps prise', '24 h', 'Couleur', 'Translucide', 'Flexibilité', 'Haute'),
 NULL, JSON_ARRAY(), 1),

('COLLE-WOOD-EXPRESS', 'colle-bois-rapide-500', 'Colle Bois Express 500 ml', 22, 2200.00, 1950.00,
 'Colle polyuréthane pour assemblage bois professionnel. Prise rapide (15 min), résistance max.',
 JSON_OBJECT('Volume', '500 ml', 'Prise', '15 minutes', 'Temps travail', '8-10 min', 'Résistance', 'Maximum'),
 NULL, JSON_ARRAY(), 1),

('PATTE-FIXATION-ZINC', 'patte-fixation-epoxy', 'Patte de Fixation Époxy', 22, 1100.00, 950.00,
 'Équerre d\'assemblage en acier époxy pour renforcement bois. Charge 50 kg par point.',
 JSON_OBJECT('Matière', 'Acier époxy', 'Charge', '50 kg', 'Dimensions', '35 x 35 mm', 'Finition', 'Noir mat'),
 NULL, JSON_ARRAY(), 1),

('TAQUET-REGLABLE', 'taquet-reglable-18', 'Taquet Réglable 18 mm', 22, 380.00, 320.00,
 'Taquet pour poteaux standards. Réglable en hauteur pour un positionnement flexible.',
 JSON_OBJECT('Adapte à', 'Poteaux 18 mm', 'Charge', '25 kg', 'Matière', 'Zinc', 'Réglage', '±15 mm'),
 NULL, JSON_ARRAY(), 1),

('CACHE-TROU-ACACIA', 'cache-trou-acacia-20', 'Cache-Trou Acacia 20 mm', 22, 280.00, 240.00,
 'Bouchon en bois massif pour cacher les trous de vis et chevilles. Finition naturelle.',
 JSON_OBJECT('Diamètre', '20 mm', 'Bois', 'Acacia massif', 'Finition', 'Brut', 'Boîte', '100 pièces'),
 NULL, JSON_ARRAY(), 1),

('VERROUS-SÉCURITÉ', 'verrou-securite-brass', 'Verrou de Sécurité Laiton', 22, 1650.00, 1480.00,
 'Verrou de bonne qualité pour armoires et portes sensibles. Fermeture à clé 3 positions.',
 JSON_OBJECT('Matière', 'Laiton', 'Clés', 'Sécurisée', 'Positions', '3', 'Installation', 'Externe'),
 NULL, JSON_ARRAY(), 1),

-- Bois Brut (5 produits)
('SAPIN-RABOT-27', 'sapin-rabot-27x70', 'Sapin Raboté 27 x 70 mm', 23, 2800.00, 2500.00,
 'Bois de sapin massif rabote pour menuiserie, cadres et structures légères. Séché et raboté.',
 JSON_OBJECT('Section', '27 x 70 mm', 'Longueur', 'Au mètre', 'Essence', 'Sapin du Nord', 'Humidité', 'Régulée'),
 NULL, JSON_ARRAY(), 1),

('CHENE-MASSIF-35', 'chene-massif-35x150', 'Chêne Massif 35 x 150 mm', 23, 8500.00, 7800.00,
 'Chêne blanc massif de belle qualité pour mobilier noble et agencements haut de gamme.',
 JSON_OBJECT('Section', '35 x 150 mm', 'Essence', 'Chêne blanc', 'Séchage', 'Naturel', 'Grade', 'Sélectionné'),
 NULL, JSON_ARRAY(), 1),

('MERISIER-LAMES', 'merisier-lames-parquet', 'Lames Merisier Parquet', 23, 6200.00, 5700.00,
 'Lames de merisier pour sols, revêtement ou agencement. Aspect chaud et naturel.',
 JSON_OBJECT('Épaisseur', '18 mm', 'Largeur', '90-140 mm', 'Essence', 'Merisier', 'Finition', 'Brut poncé'),
 NULL, JSON_ARRAY(), 1),

('TECK-EXOTIQUE-40', 'teck-exotique-40x80', 'Teck Exotique 40 x 80 mm', 23, 15500.00, 14200.00,
 'Bois teck premium pour applications haut de gamme. Extrêmement durable et imputrescible.',
 JSON_OBJECT('Section', '40 x 80 mm', 'Essence', 'Teck Birmanie', 'Durabilité', 'Classe 1', 'Traitement', 'Naturel'),
 NULL, JSON_ARRAY(), 1),

('EPICEA-RABOTE-20', 'epicea-rabote-20x40', 'Épicéa Raboté 20 x 40 mm', 23, 1500.00, 1350.00,
 'Épicéa blanc raboté pour petits travaux de menuiserie, cadres et assemblage général.',
 JSON_OBJECT('Section', '20 x 40 mm', 'Essence', 'Épicéa blanc', 'Longueur', 'Au mètre', 'Humidité', 'Régulée'),
 NULL, JSON_ARRAY(), 1),

-- Finitions & Vernis (5 produits)
('VERNIS-POLYURETH', 'vernis-polyuréthane-brillant', 'Vernis Polyuréthane Brillant 1L', 24, 3800.00, 3400.00,
 'Vernis haute résistance pour bois intérieur et extérieur. Finition brillante et durable.',
 JSON_OBJECT('Volume', '1 litre', 'Brillance', 'Brillant', 'Temps séchage', '6 heures', 'Rendement', '8-10 m²/L'),
 NULL, JSON_ARRAY(), 1),

('LASURE-BOIS-INCOLORE', 'lasure-bois-incolore', 'Lasure Bois Incolore 2.5L', 24, 4500.00, 4100.00,
 'Lasure incolore pour protection bois brut extérieur. Laisse voir le grain naturel.',
 JSON_OBJECT('Volume', '2.5 litres', 'Coloration', 'Incolore', 'Temps séchage', '4 heures', 'Durabilité', '5-7 ans'),
 NULL, JSON_ARRAY(), 1),

('PEINTURE-EPOXY', 'peinture-epoxy-gris-acier', 'Peinture Époxy Gris Acier 1L', 24, 2600.00, 2350.00,
 'Peinture époxy haute performance pour mobilier et surface intense. Finition lisse mat.',
 JSON_OBJECT('Volume', '1 litre', 'Couleur', 'Gris acier', 'Brillance', 'Mat', 'Résistance', 'Extrême'),
 NULL, JSON_ARRAY(), 1),

('CIRE-BOIS-NATURELLE', 'cire-bois-naturelle-500', 'Cire Bois Naturelle 500 ml', 24, 1900.00, 1650.00,
 'Cire naturelle à base d\'huiles essentielles pour entretien bois. Effet satiné protecteur.',
 JSON_OBJECT('Volume', '500 ml', 'Base', 'Naturelle 100%', 'Aspect', 'Satiné', 'Odeur', 'Naturelle'),
 NULL, JSON_ARRAY(), 1),

('DECAPANT-CHIMIQUE', 'decapant-chimique-pro-1l', 'Décapant Chimique Pro 1L', 24, 3200.00, 2900.00,
 'Décapant puissant pour enlever peinture et vernis ancien. Écologique et efficace.',
 JSON_OBJECT('Volume', '1 litre', 'Type', 'Chimique non-toxique', 'Temps action', '30 minutes', 'Rendement', '1-2 m²'),
 NULL, JSON_ARRAY(), 1)

ON DUPLICATE KEY UPDATE 
    designation = VALUES(designation), 
    prix_unite = VALUES(prix_unite), 
    prix_gros = VALUES(prix_gros), 
    description = VALUES(description), 
    caracteristiques_json = VALUES(caracteristiques_json), 
    image_principale = VALUES(image_principale), 
    galerie_images = VALUES(galerie_images), 
    actif = VALUES(actif);
