-- Schéma du module Catalogue public (lecture seule)
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

-- Données de démonstration (optionnel)
INSERT INTO catalogue_categories (nom, slug, actif, ordre) VALUES
('Menuiserie', 'menuiserie', 1, 1),
('Machines', 'machines', 1, 2),
('Accessoires', 'accessoires', 1, 3)
ON DUPLICATE KEY UPDATE nom = VALUES(nom), actif = VALUES(actif), ordre = VALUES(ordre);

INSERT INTO catalogue_produits (
    code, slug, designation, categorie_id, prix_unite, prix_gros, description, caracteristiques_json, image_principale, galerie_images, actif
) VALUES
('PLQ-CTBX-18', 'plaque-ctbx-18mm', 'Panneau CTBX 18 mm', 1, 29500.00, 27500.00,
 'Panneau contreplaqué CTBX haute résistance, idéal pour milieux humides.',
 JSON_OBJECT('Epaisseur', '18 mm', 'Dimensions', '1220 x 2440 mm', 'Essence', 'Okoumé'),
 NULL,
 JSON_ARRAY(), 1
),
('SCIE-RBT-210', 'scie-ruban-210', 'Scie à ruban 210', 2, 185000.00, 172000.00,
 'Scie à ruban compacte pour ateliers, coupe précise bois et dérivés.',
 JSON_OBJECT('Hauteur coupe', '210 mm', 'Puissance', '1.5 kW', 'Alimentation', '220V'),
 NULL,
 JSON_ARRAY(), 1
),
('CHARN-INOX-90', 'charniere-inox-90', 'Charnière inox 90°', 3, 950.00, 850.00,
 'Charnière inox pour meubles et menuiseries, ouverture 90°.',
 JSON_OBJECT('Matière', 'Inox 304', 'Finition', 'Brossé', 'Usage', 'Meuble'),
 NULL,
 JSON_ARRAY(), 1
)
ON DUPLICATE KEY UPDATE designation = VALUES(designation), prix_unite = VALUES(prix_unite), prix_gros = VALUES(prix_gros), description = VALUES(description), caracteristiques_json = VALUES(caracteristiques_json), image_principale = VALUES(image_principale), galerie_images = VALUES(galerie_images), actif = VALUES(actif);
