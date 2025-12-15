# Module Administration Catalogue

## Vue d'ensemble

Module back-office complet pour gérer le catalogue de produits KMS. Permet la gestion des produits (CRUD), des catégories, et des images.

## Structure

```
admin/catalogue/
├── README.md                  # Ce fichier
├── produits.php               # Liste des produits (filtres, tri, pagination)
├── produit_edit.php           # Formulaire création/édition produit
├── produit_delete.php         # Suppression produit (+ images)
├── categories.php             # Gestion catégories (CRUD inline)
└── images/                    # Dossier organisation (optionnel)
```

## Permissions requises

| Action | Permission |
|--------|-----------|
| Consulter produits/catégories | `PRODUITS_LIRE` |
| Créer produit/catégorie | `PRODUITS_CREER` |
| Modifier produit/catégorie | `PRODUITS_MODIFIER` |
| Supprimer produit/catégorie | `PRODUITS_SUPPRIMER` |

## Fonctionnalités

### 1. Gestion Produits (`produits.php`)

**Filtres:**
- Recherche textuelle (code, désignation, description)
- Filtre par catégorie (dropdown dynamique)
- Filtre par statut (actif/inactif)

**Tri:**
- 6 colonnes triables: code, désignation, prix unitaire, catégorie, statut, date création
- Ordre croissant/décroissant avec indicateur visuel

**Affichage:**
- Miniature image principale (50x50px)
- Informations: code, désignation, catégorie, prix unitaire, statut
- Actions: Voir public, Éditer, Supprimer

**Statistiques:**
- Total produits
- Produits actifs
- Nombre de catégories

**Pagination:**
- Intégrée aux préférences utilisateur
- Options: 25, 50, 100 par page

### 2. Formulaire Produit (`produit_edit.php`)

**Informations de base:**
- Code (unique, requis)
- Catégorie (dropdown, requis)
- Désignation (requis)
- Description (optionnel, textarea)
- Prix unitaire (optionnel, décimal)
- Prix gros (optionnel, décimal)

**Caractéristiques:**
- Paires clé-valeur dynamiques
- Stockage JSON: `{"Epaisseur": "18 mm", "Dimensions": "1220 x 2440 mm"}`
- Boutons Ajouter/Supprimer (JavaScript)
- Nombre illimité de caractéristiques

**Gestion Images:**
- **Image principale:** Upload unique, remplace l'ancienne
- **Galerie:** Upload multiple, s'ajoute à l'existant
- **Formats acceptés:** JPEG, JPG, PNG, GIF, WEBP
- **Taille max:** 5 MB par fichier
- **Stockage:** `uploads/catalogue/`
- **Nommage:** `img_[uniqid].[extension]`
- **Prévisualisation:** Affichage des images existantes

**Automatismes:**
- Génération automatique du slug (depuis désignation)
- Vérification unicité du code
- Vérification unicité du slug (ajoute uniqid si collision)
- Suppression ancienne image principale lors du remplacement
- Conservation galerie existante lors de l'ajout

**Validation:**
- Champs requis: code, désignation, categorie_id
- Type fichier validé (MIME type check)
- Taille fichier validée (max 5 MB)
- Messages d'erreur clairs et groupés

**Sidebar:**
- Case "Actif" (visible dans catalogue public)
- Timestamps (date création/modification)
- Bouton Enregistrer
- Lien "Voir sur le site" (si édition)

### 3. Suppression Produit (`produit_delete.php`)

**Traitement:**
1. Vérification permission `PRODUITS_SUPPRIMER`
2. Vérification CSRF
3. Récupération des infos produit
4. Suppression image principale (si existe)
5. Suppression galerie complète (si existe)
6. Suppression enregistrement DB
7. Redirect avec message succès

**Sécurité:**
- Confirmation JavaScript avant submit
- Vérification ID valide
- Message d'erreur si produit introuvable

### 4. Gestion Catégories (`categories.php`)

**Affichage:**
- Table avec: Ordre, Nom, Slug, Nb produits, Statut, Actions
- Tri par ordre puis nom
- Badges visuels (ordre, statut)

**CRUD inline:**
- **Créer:** Modal Bootstrap avec form (nom, ordre, actif)
- **Modifier:** Modal pré-rempli avec données catégorie
- **Supprimer:** Confirmation + vérification produits associés
- Empêche suppression si catégorie contient produits

**Automatismes:**
- Génération automatique slug (depuis nom)
- Vérification unicité slug (ajoute uniqid si collision)
- Comptage dynamique produits par catégorie

## Base de données

### Table `catalogue_produits`

```sql
CREATE TABLE catalogue_produits (
    id INT PRIMARY KEY AUTO_INCREMENT,
    produit_id INT NULL,                        -- FK vers produits.id (stock)
    code VARCHAR(100) UNIQUE NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    designation VARCHAR(255) NOT NULL,
    categorie_id INT NOT NULL,
    prix_unite DECIMAL(15,2),
    prix_gros DECIMAL(15,2),
    description TEXT,
    caracteristiques_json JSON,                -- {"Epaisseur": "18 mm", ...}
    image_principale VARCHAR(255),             -- filename only
    galerie_images JSON,                       -- ["img1.jpg", "img2.jpg", ...]
    actif TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (categorie_id) REFERENCES catalogue_categories(id)
);
```

### Table `catalogue_categories`

```sql
CREATE TABLE catalogue_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    actif TINYINT(1) DEFAULT 1,
    ordre INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

## Usage

### Créer un nouveau produit

1. Accéder à **Produits & Stock > Produits** dans le menu
2. Cliquer sur **Nouveau Produit**
3. Remplir les champs obligatoires (code, catégorie, désignation)
4. Ajouter prix, description si souhaité
5. Ajouter caractéristiques avec bouton **+** (ex: Dimensions, Matériau, etc.)
6. Charger image principale (obligatoire pour affichage catalogue)
7. Charger galerie (optionnel, plusieurs images)
8. Cocher **Actif** pour rendre visible dans catalogue public
9. Cliquer **Enregistrer**

### Modifier un produit existant

1. Dans la liste produits, cliquer sur **Éditer** (icône crayon)
2. Modifier les champs souhaités
3. Remplacer image principale: Charger nouvelle image (ancienne supprimée auto)
4. Ajouter images galerie: Charger nouvelles images (s'ajoutent à l'existant)
5. Supprimer caractéristique: Cliquer sur **× Supprimer** à droite de la ligne
6. Cliquer **Enregistrer**

### Supprimer un produit

1. Dans la liste produits, cliquer sur **Supprimer** (icône poubelle)
2. Confirmer dans la popup JavaScript
3. Le produit, l'image principale et la galerie complète sont supprimés

### Gérer les catégories

1. Accéder à **Produits & Stock > Catégories**
2. **Créer:** Cliquer **Nouvelle Catégorie**, remplir modal, valider
3. **Modifier:** Cliquer icône crayon, modifier dans modal, valider
4. **Supprimer:** Cliquer icône poubelle, confirmer
   - Si catégorie contient produits → Erreur "Impossible de supprimer"
   - Réassigner ou supprimer les produits d'abord

### Ordonner les catégories

- Le champ **Ordre** définit l'affichage dans les dropdowns et le catalogue public
- Ordre croissant: 1, 2, 3, etc.
- Catégories même ordre: tri alphabétique nom

## Intégration catalogue public

**Séparation complète:**
- Catalogue admin: `admin/catalogue/` (CRUD back-office)
- Catalogue public: `catalogue/index.php` (lecture seule)

**Aucune modification:**
- Le catalogue public continue de fonctionner à l'identique
- Utilise le même controller: `catalogue/controllers/catalogue_controller.php`
- Fonctions: `catalogue_get_categories()`, `catalogue_get_products()`

**Visibilité:**
- Seuls les produits avec `actif = 1` apparaissent dans le catalogue public
- Seules les catégories avec `actif = 1` apparaissent dans les filtres

## Architecture

### Uploads

```
uploads/catalogue/
├── img_[uniqid1].jpg    # Images produits
├── img_[uniqid2].png
└── ...
```

**Convention nommage:**
- Prefix: `img_`
- Unique ID: `uniqid('img_', true)`
- Extension: Originale (jpg, png, gif, webp)
- Exemple: `img_676f1a2b4e5d8.jpg`

### Slug generation

```php
function generateSlug($text) {
    $text = mb_strtolower($text, 'UTF-8');
    $text = preg_replace('/[^a-z0-9]+/u', '-', $text);
    $text = trim($text, '-');
    return $text;
}
```

**Exemples:**
- "Contreplaqué Okoumé 18mm" → `contreplaque-okoume-18mm`
- "Scie circulaire Bosch" → `scie-circulaire-bosch`

**Gestion collisions:**
- Si slug existe déjà: Ajout `uniqid()` à la fin
- Exemple: `contreplaque-okoume-18mm-676f1a2b4e5d8`

### JSON Storage

**Caractéristiques (object):**
```json
{
  "Epaisseur": "18 mm",
  "Dimensions": "1220 x 2440 mm",
  "Essence": "Okoumé",
  "Nombre de plis": "7"
}
```

**Galerie (array):**
```json
[
  "img_676f1a2b4e5d8.jpg",
  "img_676f1a3c5f6e9.jpg",
  "img_676f1a4d7g8f1.jpg"
]
```

## Tests suggérés

### Tests fonctionnels

1. **Produits:**
   - [ ] Créer produit avec toutes données
   - [ ] Créer produit minimal (code, catégorie, désignation)
   - [ ] Modifier produit existant
   - [ ] Remplacer image principale
   - [ ] Ajouter images galerie
   - [ ] Ajouter/supprimer caractéristiques dynamiques
   - [ ] Supprimer produit (vérifier suppression images)
   - [ ] Vérifier code unique (erreur si doublon)
   - [ ] Tester upload image > 5 MB (doit échouer)
   - [ ] Tester upload format non supporté (doit échouer)

2. **Catégories:**
   - [ ] Créer catégorie
   - [ ] Modifier catégorie
   - [ ] Supprimer catégorie vide
   - [ ] Tenter supprimer catégorie avec produits (doit échouer)
   - [ ] Vérifier tri par ordre

3. **Filtres et tri:**
   - [ ] Filtrer par catégorie
   - [ ] Filtrer par statut (actif/inactif)
   - [ ] Recherche textuelle (code, désignation, description)
   - [ ] Tri chaque colonne (croissant/décroissant)
   - [ ] Pagination (25, 50, 100 par page)

4. **Permissions:**
   - [ ] User avec PRODUITS_LIRE uniquement (ne voit que liste)
   - [ ] User sans PRODUITS_CREER (pas de bouton Nouveau)
   - [ ] User sans PRODUITS_MODIFIER (pas de bouton Éditer)
   - [ ] User sans PRODUITS_SUPPRIMER (pas de bouton Supprimer)

5. **Intégration publique:**
   - [ ] Produit actif apparaît dans catalogue public
   - [ ] Produit inactif n'apparaît PAS dans catalogue public
   - [ ] Images affichées correctement
   - [ ] Caractéristiques affichées correctement

### Tests sécurité

- [ ] Accès direct URL sans permission (redirect)
- [ ] POST sans CSRF token (échoue)
- [ ] Upload script PHP déguisé en image (validation MIME)
- [ ] SQL injection dans recherche/filtres (prepared statements)

## Améliorations futures

### Court terme
- [ ] Suppression individuelle images galerie (actuellement: remplacement complet)
- [ ] Réorganisation ordre galerie (drag & drop)
- [ ] Duplication produit (clone)
- [ ] Import CSV/Excel produits
- [ ] Export CSV/Excel produits

### Moyen terme
- [ ] Synchronisation stock (`produit_id` FK vers `produits.id`)
- [ ] Redimensionnement automatique images (max 800x800)
- [ ] Compression images (optimisation poids)
- [ ] Rich text editor description (TinyMCE)
- [ ] Prévisualisation avant enregistrement

### Long terme
- [ ] Bulk actions (activer/désactiver/supprimer multiple)
- [ ] Analytics (produits les plus vus)
- [ ] Dashboard produits (statistiques)
- [ ] Gestion variantes produits
- [ ] Historique modifications (audit trail)

## Support

**Documentation:**
- Ce fichier: `admin/catalogue/README.md`
- Schema DB: `catalogue/catalogue_schema.sql`
- Controller: `catalogue/controllers/catalogue_controller.php`

**Bugs/Questions:**
Contacter l'équipe technique ou ouvrir une issue dans le système de gestion de projet.

---

**Version:** 1.0.0  
**Date:** 15 décembre 2025  
**Auteur:** GitHub Copilot AI Agent
