# Module PRODUITS Internes - Documentation

## Vue d'ensemble

Le module **Produits Internes** gère le catalogue privé de l'entreprise, utilisé pour les opérations de stock, ventes, achats et gestion magasin. Il est distinct du **Catalogue Public** (site web).

## Architecture

### Deux Catalogues Distincts

#### 1. Catalogue Public (Site Web)
- **Tables**: `catalogue_produits`, `catalogue_categories`
- **Usage**: Affichage sur le site web pour les clients
- **Gestion**: Via `admin/catalogue/produits.php`
- **Caractéristiques**: Prix publics, descriptions marketing, images, caractéristiques JSON

#### 2. Catalogue Privé (Interne)
- **Tables**: `produits`, `familles_produits`, `sous_categories_produits`
- **Usage**: Stock, ventes, achats, gestion magasin
- **Gestion**: Via `produits/list.php` (CE MODULE)
- **Caractéristiques**: Prix d'achat/vente, stock, seuils, mouvements

### Lien Entre Les Deux
- `catalogue_produits.produit_id` → `produits.id` (FK optionnelle)
- Un produit public PEUT être lié à un produit interne
- Synchronisation manuelle via `sync_catalogue_to_produits.php`

## Structure Base de Données

### Table `produits` (Catalogue Interne)

```sql
CREATE TABLE `produits` (
  `id` int UNSIGNED NOT NULL,
  `code_produit` varchar(100) NOT NULL,
  `famille_id` int UNSIGNED NOT NULL,
  `sous_categorie_id` int UNSIGNED DEFAULT NULL,
  `designation` varchar(255) NOT NULL,
  `caracteristiques` text,
  `description` text,
  `fournisseur_id` int UNSIGNED DEFAULT NULL,
  `localisation` varchar(150) DEFAULT NULL,
  `prix_achat` decimal(15,2) NOT NULL DEFAULT '0.00',
  `prix_vente` decimal(15,2) NOT NULL DEFAULT '0.00',
  `stock_actuel` int NOT NULL DEFAULT '0',
  `seuil_alerte` int NOT NULL DEFAULT '0',
  `image_path` varchar(255) DEFAULT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT '1',
  `date_creation` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modification` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code_produit` (`code_produit`),
  KEY `famille_id` (`famille_id`),
  KEY `sous_categorie_id` (`sous_categorie_id`),
  KEY `fournisseur_id` (`fournisseur_id`)
) ENGINE=InnoDB;
```

### Tables Associées

- **`familles_produits`**: Familles de produits (Meubles, Électricité, etc.)
- **`sous_categories_produits`**: Sous-catégories par famille
- **`stocks_mouvements`**: Historique des mouvements de stock
- **`fournisseurs`**: Fournisseurs associés aux produits

## Fichiers du Module

### 1. `produits/list.php` (430 lignes)

**Fonction**: Liste complète des produits internes avec filtres avancés

**Fonctionnalités**:
- ✅ Statistiques dashboard (total, ruptures, alertes, valeur stock)
- ✅ Filtres: recherche, famille, statut actif/inactif
- ✅ Tri sur toutes colonnes (code, désignation, famille, prix, stock)
- ✅ Pagination professionnelle (25/50/100 par page)
- ✅ Badges visuels pour stock (Rupture/Alerte/OK)
- ✅ Affichage images produits (thumbnail 40x40)
- ✅ Actions: Modifier, Ajuster stock

**Permissions**:
- Lecture: `PRODUITS_LIRE`
- Création: `PRODUITS_CREER`
- Modification: `PRODUITS_MODIFIER`

**URL**: `http://localhost/kms_app/produits/list.php`

---

### 2. `produits/edit.php` (456 lignes)

**Fonction**: Création et modification de produits internes

**Fonctionnalités**:
- ✅ Formulaire complet tous champs
- ✅ Upload image produit
- ✅ Sélection famille + sous-catégorie (dépendantes)
- ✅ Sélection fournisseur
- ✅ Prix d'achat / Prix de vente
- ✅ Stock initial (création uniquement)
- ✅ Ajustement stock (modification)
- ✅ Seuil d'alerte personnalisable
- ✅ Statut actif/inactif

**Validation**:
- Code produit unique obligatoire
- Désignation obligatoire
- Famille obligatoire
- Prix >= 0

**Permissions**:
- Création: `PRODUITS_CREER`
- Modification: `PRODUITS_MODIFIER`

**URL Création**: `http://localhost/kms_app/produits/edit.php`  
**URL Modification**: `http://localhost/kms_app/produits/edit.php?id=1`

---

### 3. `produits/detail.php` (260 lignes)

**Fonction**: Affichage détaillé d'un produit avec historique

**Fonctionnalités**:
- ✅ Informations complètes produit
- ✅ Affichage image grande taille
- ✅ Statistiques stock (actuel, seuil, statut)
- ✅ Tarification (PA, PV, marge, marge %)
- ✅ Historique mouvements stock (20 derniers)
- ✅ Badges visuels par type mouvement
- ✅ Liens rapides: Modifier, Ajuster stock

**Permissions**:
- Lecture: `PRODUITS_LIRE`

**URL**: `http://localhost/kms_app/produits/detail.php?id=1`

---

### 4. `sync_catalogue_to_produits.php` (220 lignes)

**Fonction**: Synchronisation catalogue public → catalogue privé

**Fonctionnalités**:
- ✅ Import automatique produits catalogue public vers catalogue interne
- ✅ Mode aperçu (dry run) avant exécution
- ✅ Création automatique familles manquantes
- ✅ Établissement du lien `produit_id`
- ✅ Protection par confirmation
- ✅ Statistiques détaillées (total, créés, déjà liés, erreurs)

**Sécurité**:
- Token confirmation: `?confirm=YES`
- Permission: `PRODUITS_CREER`
- Transaction SQL (rollback en cas d'erreur)

**Usage**:
1. Accéder: `http://localhost/kms_app/sync_catalogue_to_produits.php`
2. Clic "Aperçu" pour voir ce qui sera créé
3. Clic "Lancer synchronisation" pour exécuter
4. Vérifier résultats et statistiques

**URL**: `http://localhost/kms_app/sync_catalogue_to_produits.php`

---

### 5. `stock/mouvements.php` (Existant)

**Fonction**: Ajustements manuels de stock

**Fonctionnalités**:
- ✅ Ajout mouvements ENTREE/SORTIE/AJUSTEMENT
- ✅ Historique complet par produit
- ✅ Utilise `lib/stock.php` pour cohérence
- ✅ Validation utilisateur et motif

**Permissions**:
- Modification: `PRODUITS_MODIFIER`

**URL**: `http://localhost/kms_app/stock/mouvements.php?produit_id=1`

## Workflows Utilisateur

### Workflow 1: Créer un Nouveau Produit Interne

1. Accéder à [produits/list.php](http://localhost/kms_app/produits/list.php)
2. Clic bouton "Nouveau produit"
3. Remplir:
   - Code produit (unique)
   - Désignation
   - Famille (obligatoire)
   - Sous-catégorie (optionnel)
   - Prix d'achat / Prix de vente
   - Stock initial
   - Seuil d'alerte
   - Fournisseur (optionnel)
   - Image (optionnel)
4. Clic "Enregistrer"
5. Redirection vers liste avec message succès

### Workflow 2: Ajuster le Stock d'un Produit

1. Depuis [produits/list.php](http://localhost/kms_app/produits/list.php)
2. Clic bouton "Ajuster stock" (icône flèches) sur la ligne produit
3. Ou depuis [produits/detail.php](http://localhost/kms_app/produits/detail.php?id=1), clic "Ajuster stock"
4. Sélectionner type: ENTREE / SORTIE / AJUSTEMENT
5. Saisir quantité et motif
6. Clic "Enregistrer"
7. Le mouvement est enregistré dans `stocks_mouvements`
8. `produits.stock_actuel` est mis à jour automatiquement

### Workflow 3: Synchroniser Catalogue Public → Privé

1. Accéder à [sync_catalogue_to_produits.php](http://localhost/kms_app/sync_catalogue_to_produits.php)
2. Lire informations et avertissements
3. Clic "Aperçu (Dry Run)" pour simuler
4. Vérifier statistiques:
   - Total produits catalogue
   - Déjà liés (ignorés)
   - Nouveaux à créer
   - Familles à créer
5. Si OK, clic "Lancer la synchronisation"
6. Attendre fin (affichage statistiques finales)
7. Vérifier dans [produits/list.php](http://localhost/kms_app/produits/list.php) que les produits apparaissent

### Workflow 4: Rechercher un Produit

1. Depuis [produits/list.php](http://localhost/kms_app/produits/list.php)
2. Utiliser filtres:
   - **Recherche**: Code, désignation, description
   - **Famille**: Dropdown toutes familles
   - **Statut**: Actifs/Inactifs/Tous
3. Clic "Filtrer"
4. Résultats affichés avec nombre total
5. Trier colonnes (clic en-têtes)
6. Ajuster pagination si besoin

## Sécurité

### Permissions Requises

| Action | Permission | Rôles Autorisés |
|--------|-----------|-----------------|
| Voir liste produits | `PRODUITS_LIRE` | ADMIN, SHOWROOM, TERRAIN, MAGASINIER |
| Voir détail produit | `PRODUITS_LIRE` | ADMIN, SHOWROOM, TERRAIN, MAGASINIER |
| Créer produit | `PRODUITS_CREER` | ADMIN |
| Modifier produit | `PRODUITS_MODIFIER` | ADMIN, MAGASINIER |
| Supprimer produit | `PRODUITS_SUPPRIMER` | ADMIN |
| Ajuster stock | `PRODUITS_MODIFIER` | ADMIN, MAGASINIER |
| Synchroniser catalogues | `PRODUITS_CREER` | ADMIN |

### Protection CSRF

- ✅ Tous les formulaires POST utilisent `verifierCsrf()`
- ✅ Tokens générés via `getCsrfToken()`
- ✅ Validation automatique côté serveur

### Validation SQL

- ✅ Prepared statements pour toutes les requêtes
- ✅ Pas de concaténation SQL directe
- ✅ Binding de paramètres systématique

## Conventions Respect Projet

### Routing
```php
// ✅ CORRECT
header('Location: ' . url_for('produits/list.php'));
<a href="<?= url_for('produits/edit.php?id=' . $id) ?>">

// ❌ INCORRECT
header('Location: /produits/list.php');
<a href="/produits/edit.php?id=<?= $id ?>">
```

### Sécurité
```php
// ✅ CORRECT - Toutes les pages
require_once __DIR__ . '/../security.php';
exigerConnexion();
exigerPermission('PRODUITS_LIRE');
global $pdo;

// ✅ CORRECT - Formulaires POST
verifierCsrf($_POST['csrf_token'] ?? '');
```

### Base de Données
```php
// ✅ CORRECT - Prepared statements
$stmt = $pdo->prepare("SELECT * FROM produits WHERE id = :id");
$stmt->execute([':id' => $id]);

// ❌ INCORRECT - Interpolation directe
$stmt = $pdo->query("SELECT * FROM produits WHERE id = $id");
```

### Stock
```php
// ✅ CORRECT - Utiliser lib/stock.php
require_once __DIR__ . '/../lib/stock.php';
stock_ajouter_mouvement($pdo, $produit_id, 'ENTREE', $quantite, 'Achat', $user_id);

// ❌ INCORRECT - UPDATE direct
$pdo->exec("UPDATE produits SET stock_actuel = stock_actuel + $quantite WHERE id = $produit_id");
```

## Intégration Avec Autres Modules

### ➡️ Ventes
- Sélection produits depuis `produits` table
- Génération bons de livraison
- Sortie stock automatique via `lib/stock.php`
- Mise à jour `produits.stock_actuel`

### ➡️ Achats
- Création achats liés à `produits`
- Entrée stock automatique
- Mise à jour prix d'achat (optionnel)

### ➡️ Devis
- Sélection produits internes
- Affichage prix de vente
- Conversion devis → vente

### ➡️ Stock/Magasin
- Module `stock/mouvements.php` pour ajustements
- Module `stock/etat.php` pour état global
- Alertes ruptures via `ruptures_signalees`

### ➡️ Comptabilité
- Entrées/sorties stock génèrent écritures comptables
- Valeur stock calculée (stock_actuel × prix_achat)
- Intégration via `lib/compta.php`

## Tests Suggérés

### Tests Fonctionnels

- [ ] **Liste produits**: Filtres, tri, pagination fonctionnent
- [ ] **Créer produit**: Formulaire complet, validation, redirection
- [ ] **Modifier produit**: Données pré-remplies, sauvegarde OK
- [ ] **Ajuster stock**: Mouvement enregistré, stock_actuel mis à jour
- [ ] **Détail produit**: Affichage complet, historique mouvements
- [ ] **Synchronisation**: Dry run affiche stats, execution crée produits
- [ ] **Images**: Upload, affichage, suppression
- [ ] **Badges stock**: Rupture (≤0), Alerte (≤seuil), OK (>seuil)

### Tests Sécurité

- [ ] **Permissions**: Accès refusé si permission manquante
- [ ] **CSRF**: Formulaires rejettent soumissions sans token
- [ ] **SQL Injection**: Tentatives bloquées par prepared statements
- [ ] **XSS**: Données utilisateur échappées via htmlspecialchars()

### Tests Intégration

- [ ] **Ventes**: Créer vente avec produit → stock diminue
- [ ] **Achats**: Créer achat avec produit → stock augmente
- [ ] **Ruptures**: Produit stock=0 → Apparaît dans alertes dashboard
- [ ] **Catalogue public**: Sync créé produits internes avec lien correct

## Maintenance

### Backup Base de Données

Avant toute opération critique (sync, suppression masse):
```bash
# Local (XAMPP)
mysqldump -u root kms_gestion > backup_$(date +%Y%m%d_%H%M%S).sql

# Production (Bluehost)
mysqldump -u kdfvxvmy_WPEUF -p kdfvxvmy_kms_gestion > backup_$(date +%Y%m%d_%H%M%S).sql
```

### Nettoyage Images

Supprimer images orphelines (produits supprimés):
```bash
# À implémenter si besoin
php clean_orphan_images.php
```

### Réindexation Stock

Si incohérences stock_actuel vs stocks_mouvements:
```sql
-- Recalculer stock_actuel depuis mouvements
UPDATE produits p
SET p.stock_actuel = (
    SELECT COALESCE(SUM(
        CASE
            WHEN sm.type_mouvement = 'ENTREE' THEN sm.quantite
            WHEN sm.type_mouvement = 'SORTIE' THEN -sm.quantite
            WHEN sm.type_mouvement = 'AJUSTEMENT' THEN sm.quantite
            ELSE 0
        END
    ), 0)
    FROM stocks_mouvements sm
    WHERE sm.produit_id = p.id
);
```

## FAQ

**Q: Quelle différence entre Catalogue Public et Produits Internes ?**  
R: Le Catalogue Public (`catalogue_produits`) est pour le site web client. Les Produits Internes (`produits`) sont pour la gestion stock/ventes/achats en interne.

**Q: Comment lier un produit public à un produit interne ?**  
R: Utiliser `sync_catalogue_to_produits.php` pour automatiser, ou manuellement modifier `catalogue_produits.produit_id`.

**Q: Puis-je supprimer un produit interne utilisé dans une vente ?**  
R: Non recommandé. Mieux vaut le désactiver (`actif = 0`) pour conserver l'historique.

**Q: Comment gérer les stocks négatifs ?**  
R: Interdits par défaut. Si négatif apparaît, vérifier mouvements et ajuster via `stock/mouvements.php`.

**Q: Pourquoi mon stock ne se met pas à jour ?**  
R: Vérifier que vous utilisez `lib/stock.php` pour les mouvements, pas d'UPDATE direct sur `produits.stock_actuel`.

## Améliorations Futures

- [ ] Export produits vers Excel/CSV
- [ ] Import masse produits depuis CSV
- [ ] Gestion variantes produits (tailles, couleurs)
- [ ] Codes-barres / QR codes pour scannage
- [ ] Historique prix (courbe évolution)
- [ ] Alertes email ruptures automatiques
- [ ] Suggestion réapprovisionnement intelligent
- [ ] Intégration avec système de caisse

## Support

Pour toute question technique:
- Consulter `historique.md` pour historique projet
- Consulter `compta/README_COMPTA.md` pour intégration comptable
- Vérifier `lib/stock.php` pour API stock
- Contacter administrateur système

---

**Date création**: 29 décembre 2025  
**Version**: 1.0  
**Auteur**: Équipe KMS Gestion
