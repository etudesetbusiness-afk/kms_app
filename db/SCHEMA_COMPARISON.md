# Comparaison Schéma : XAMPP Local vs Bluehost Production

## ANALYSE DÉTAILLÉE

### TABLE `catalogue_categories`

**LOCAL (XAMPP - kms_gestion_local.sql)**
```sql
CREATE TABLE `catalogue_categories` (
  `id` int(11) NOT NULL,
  `nom` varchar(150) NOT NULL,
  `slug` varchar(180) NOT NULL,
  `actif` tinyint(1) DEFAULT '1',
  `ordre` int(11) DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**PRODUCTION (Bluehost - kdfvxvmy_kms_gestion_en_ligne.sql)**
```sql
CREATE TABLE `catalogue_categories` (
  `id` int NOT NULL,
  `nom` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(180) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  `ordre` int NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**DIFFÉRENCES IDENTIFIÉES:**
1. ✅ Charset: utf8mb4 - IDENTIQUE
2. ❌ **PRIMARY KEY manquante en production** (LOCAL: `PRIMARY KEY (id)`)
3. ❌ **UNIQUE KEY manquante en production** (LOCAL: `UNIQUE KEY slug (slug)`)
4. ❌ **CHARACTER SET explicite manquant en production** sur colonnes varchar
5. ✅ Types de données: IDENTIQUES (sauf int(11) vs int - MySQL 8 supprime les parenthèses)

---

### TABLE `catalogue_produits`

**LOCAL (XAMPP - kms_gestion_local.sql)**
```sql
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
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  UNIQUE KEY `slug` (`slug`),
  KEY `categorie_id` (`categorie_id`),
  CONSTRAINT `catalogue_produits_ibfk_1` FOREIGN KEY (`categorie_id`) REFERENCES `catalogue_categories` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**PRODUCTION (Bluehost - kdfvxvmy_kms_gestion_en_ligne.sql)**
```sql
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
```

**DIFFÉRENCES CRITIQUES IDENTIFIÉES:**
1. ❌ **CHECK constraints COMPLÈTEMENT MANQUANTES en production** 
   - LOCAL: `CHECK (json_valid(caracteristiques_json))`
   - LOCAL: `CHECK (json_valid(galerie_images))`
   - PRODUCTION: AUCUNE CHECK constraint
   
2. ❌ **PRIMARY KEY manquante en production**
   - LOCAL: `PRIMARY KEY (id)`
   - PRODUCTION: Aucune clé primaire déclarée

3. ❌ **UNIQUE KEYS manquantes en production**
   - LOCAL: `UNIQUE KEY code (code)`
   - LOCAL: `UNIQUE KEY slug (slug)`
   - PRODUCTION: Aucune

4. ❌ **FOREIGN KEY manquée en production**
   - LOCAL: `CONSTRAINT catalogue_produits_ibfk_1 FOREIGN KEY (categorie_id) REFERENCES catalogue_categories (id)`
   - PRODUCTION: Aucune FK

5. ❌ **INDEX manquant en production**
   - LOCAL: `KEY categorie_id (categorie_id)`
   - PRODUCTION: Aucun

6. ✅ Charset: utf8mb4 - IDENTIQUE (mais explicitement déclaré par colonne en production)

---

## IMPACT SUR LES OPÉRATIONS UPDATE

### Pourquoi les UPDATE échouent silencieusement:

1. **Absence de PRIMARY KEY**: MySQL ne sait pas comment identifier la ligne à mettre à jour. Sans clé primaire, les UPDATE peuvent affecter plusieurs lignes ou aucune.

2. **Absence de FOREIGN KEY**: Les contraintes d'intégrité référentielle manquent, mais ce n'est pas le problème principal pour UPDATE.

3. **CHECK constraints manquantes**: Les contraintes JSON ne bloquent pas les UPDATE mais peuvent être la source de validation échouée silencieusement.

4. **Absence d'INDEX**: Les requêtes UPDATE seront plus lentes, possibles table scans.

### Symptômes observés:
- ✅ Les modifications sont soumises au formulaire
- ✅ Les images sont chargées (file upload fonctionne)
- ❌ Les changements n'apparaissent pas en base de données
- ❌ Aucun message d'erreur

**Cause la plus probable:** Absence de PRIMARY KEY = UPDATE ne cible aucune ligne ou cible des lignes incorrectes.

---

## PLAN DE CORRECTION

Les corrections doivent s'effectuer dans cet ordre:

1. ✅ Ajouter PRIMARY KEY à `catalogue_categories`
2. ✅ Ajouter UNIQUE KEY `slug` à `catalogue_categories`
3. ✅ Ajouter PRIMARY KEY à `catalogue_produits`
4. ✅ Ajouter UNIQUE KEY `code` à `catalogue_produits`
5. ✅ Ajouter UNIQUE KEY `slug` à `catalogue_produits`
6. ✅ Ajouter INDEX `categorie_id` à `catalogue_produits`
7. ✅ Ajouter FOREIGN KEY contrainte (si possible, sinon documenter)
8. ✅ Ajouter CHECK constraints JSON sur les deux colonnes JSON

---

## MYSQL VERSION DIFFERENCE

- **XAMPP (Local)**: MariaDB 10.4.32 (base sur MySQL 5.7)
- **Bluehost (Production)**: MySQL 8.0.44-35

MySQL 8.0 introduit quelques changements:
- Suppression des parenthèses dans `int(11)` → `int`
- Charset par colonne peut être déclaré explicitement
- CHECK constraints supportées natif (depuis 8.0.15)

**IMPORTANT:** MySQL 8.0.44 supporte pleinement les CHECK constraints, donc c'est correctible.

