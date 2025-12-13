# Guide de Modernisation des Formulaires (edit.php)

## Vue d'ensemble

Ce guide documente le framework CSS/JS créé pour moderniser toutes les pages de formulaires (`edit.php`) de KMS Gestion. Le framework applique un design moderne, cohérent et interactif à l'ensemble des formulaires d'édition.

## Fichiers du Framework

### 1. CSS : `assets/css/modern-forms.css` (635 lignes)

**Sections principales :**
- Headers de formulaire avec gradients
- Cards de formulaire avec effets hover
- Champs de formulaire stylisés
- Boutons d'action (save, cancel, delete)
- Messages d'alerte
- Sections et organisateurs
- Animations et transitions
- Responsive design

### 2. JavaScript : `assets/js/modern-forms.js` (350 lignes)

**Fonctionnalités :**
- Validation en temps réel
- Compteurs de caractères
- Auto-sauvegarde (localStorage)
- Raccourcis clavier (Ctrl+S, Escape)
- Confirmations d'actions
- Champs dynamiques
- Upload de fichiers
- Notifications

## Structure HTML Standard

### Header de Page

```php
<div class="form-page-header">
    <div>
        <h1 class="form-page-title">
            <i class="bi bi-icon-name"></i>
            Titre du formulaire
        </h1>
        <p class="form-page-subtitle mb-0">Description courte</p>
    </div>
    <a href="<?= url_for('module/list.php') ?>" class="btn btn-cancel">
        <i class="bi bi-arrow-left me-2"></i> Retour
    </a>
</div>
```

**Icônes recommandées par module :**
- Clients : `bi-person-fill`
- Produits : `bi-box-seam-fill`
- Ventes : `bi-cart-check-fill`
- Achats : `bi-basket-fill`
- Digital : `bi-megaphone-fill`
- Hôtel : `bi-door-closed-fill`
- Formation : `bi-mortarboard-fill`

### Messages d'Alerte

```php
<?php if (!empty($erreurs)): ?>
    <div class="alert alert-danger form-alert">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <ul class="mb-0">
            <?php foreach ($erreurs as $err): ?>
                <li><?= htmlspecialchars($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>
```

**Classes disponibles :**
- `alert-success form-alert` : succès (vert)
- `alert-danger form-alert` : erreur (rouge)
- `alert-warning form-alert` : avertissement (jaune)
- `alert-info form-alert` : information (bleu)

### Formulaire Principal

```php
<form method="post" class="card form-card" id="formNom">
    <div class="card-header">
        <i class="bi bi-icon"></i> Titre de section
    </div>
    <div class="card-body">
        <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
        
        <div class="row g-3">
            <!-- Champs ici -->
        </div>
    </div>
    
    <div class="form-actions">
        <a href="<?= url_for('module/list.php') ?>" class="btn btn-cancel">
            <i class="bi bi-x-circle me-2"></i>Annuler
        </a>
        <button type="submit" class="btn btn-save">
            <i class="bi bi-check-circle me-2"></i>Enregistrer
        </button>
    </div>
</form>
```

**Points importants :**
- Toujours ajouter un `id` au formulaire pour l'auto-save
- `card-header` optionnel mais recommandé pour grandes sections
- `form-actions` remplace les footers personnalisés

### Champs de Formulaire

#### Champ Requis avec Icône

```php
<div class="col-md-6">
    <label class="form-label required">
        <i class="bi bi-person"></i> Nom du client
    </label>
    <input type="text" name="nom" class="form-control" required
           value="<?= htmlspecialchars($nom) ?>">
</div>
```

#### Champ Optionnel

```php
<div class="col-md-4">
    <label class="form-label">
        <i class="bi bi-telephone"></i> Téléphone
    </label>
    <input type="tel" name="telephone" class="form-control"
           value="<?= htmlspecialchars($telephone) ?>">
</div>
```

#### Select / Dropdown

```php
<div class="col-md-3">
    <label class="form-label required">
        <i class="bi bi-tag"></i> Catégorie
    </label>
    <select name="categorie_id" class="form-select" required>
        <option value="">Sélectionner...</option>
        <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>" 
                <?= $categorie_id === $cat['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($cat['nom']) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>
```

#### Textarea avec Compteur

```php
<div class="col-md-12">
    <label class="form-label">
        <i class="bi bi-chat-text"></i> Commentaires
    </label>
    <textarea name="commentaires" class="form-control" 
              rows="3" maxlength="500"><?= htmlspecialchars($commentaires) ?></textarea>
    <!-- Le compteur s'affiche automatiquement via JS -->
</div>
```

#### Input Group

```php
<div class="col-md-4">
    <label class="form-label required">
        <i class="bi bi-currency-dollar"></i> Prix
    </label>
    <div class="input-group">
        <input type="number" name="prix" class="form-control" 
               step="0.01" min="0" required value="<?= $prix ?>">
        <span class="input-group-text">FCFA</span>
    </div>
</div>
```

#### Switch / Toggle

```php
<div class="col-md-3">
    <div class="form-check form-switch">
        <input type="checkbox" name="actif" value="1" 
               class="form-check-input" id="actifSwitch"
               <?= $actif ? 'checked' : '' ?>>
        <label class="form-check-label" for="actifSwitch">
            Actif
        </label>
    </div>
</div>
```

### Boutons d'Action

#### Bouton Enregistrer (Principal)

```php
<button type="submit" class="btn btn-save">
    <i class="bi bi-check-circle me-2"></i>Enregistrer
</button>
```

#### Bouton Annuler

```php
<a href="<?= url_for('module/list.php') ?>" class="btn btn-cancel">
    <i class="bi bi-x-circle me-2"></i>Annuler
</a>
```

#### Bouton Supprimer

```php
<?php if ($modeEdition): ?>
    <button type="submit" name="action" value="delete" class="btn btn-delete">
        <i class="bi bi-trash me-2"></i>Supprimer
    </button>
<?php endif; ?>
```

## Icônes Bootstrap Icons

### Par Type de Champ

| Champ | Icône | Code |
|-------|-------|------|
| Nom / Personne | 👤 | `bi-person` / `bi-person-fill` |
| Email | ✉️ | `bi-envelope` / `bi-envelope-fill` |
| Téléphone | 📞 | `bi-telephone` / `bi-telephone-fill` |
| Date | 📅 | `bi-calendar` / `bi-calendar-event` |
| Heure | 🕐 | `bi-clock` / `bi-clock-fill` |
| Montant / Prix | 💵 | `bi-currency-dollar` |
| Adresse | 📍 | `bi-geo-alt` / `bi-geo-alt-fill` |
| Code / SKU | 📊 | `bi-upc-scan` |
| Tag / Catégorie | 🏷️ | `bi-tag` / `bi-tag-fill` |
| Description | 📝 | `bi-text-paragraph` |
| Commentaire | 💬 | `bi-chat-text` |
| Image | 🖼️ | `bi-image` / `bi-image-fill` |
| Fichier | 📎 | `bi-paperclip` / `bi-file-earmark` |
| Recherche | 🔍 | `bi-search` |
| Statut | 🚦 | `bi-flag` / `bi-flag-fill` |
| Quantité | 🔢 | `bi-123` |
| Pourcentage | % | `bi-percent` |

### Par Action

| Action | Icône | Code |
|--------|-------|------|
| Enregistrer | ✅ | `bi-check-circle` |
| Créer | ➕ | `bi-plus-circle` |
| Modifier | ✏️ | `bi-pencil-square` |
| Supprimer | 🗑️ | `bi-trash` |
| Annuler | ❌ | `bi-x-circle` |
| Retour | ⬅️ | `bi-arrow-left` |
| Suivant | ➡️ | `bi-arrow-right` |
| Valider | ✔️ | `bi-check-lg` |
| Rejeter | ✖️ | `bi-x-lg` |

## Sections de Formulaire

Pour les formulaires complexes avec plusieurs sections :

```php
<div class="form-section">
    <h2 class="form-section-title">
        <i class="bi bi-info-circle"></i>
        Informations générales
    </h2>
    
    <div class="row g-3">
        <!-- Champs de cette section -->
    </div>
</div>

<div class="form-row-separator"></div>

<div class="form-section">
    <h2 class="form-section-title">
        <i class="bi bi-gear"></i>
        Paramètres avancés
    </h2>
    
    <div class="row g-3">
        <!-- Autres champs -->
    </div>
</div>
```

## Badges de Statut

Pour afficher un statut dans le formulaire :

```php
<div class="mb-3">
    <span class="status-badge badge-active">
        <i class="bi bi-check-circle"></i> Actif
    </span>
</div>
```

**Classes disponibles :**
- `badge-active` : actif (vert)
- `badge-inactive` : inactif (rouge)
- `badge-draft` : brouillon (gris)

## Fonctionnalités JavaScript

### Auto-sauvegarde

Activée automatiquement si le formulaire a un `id` :

```php
<form method="post" id="formClient">
    <!-- Le formulaire sera auto-sauvegardé toutes les 30s -->
</form>
```

Les données sont stockées dans `localStorage` et restaurées automatiquement.

### Validation en Temps Réel

Automatique pour :
- Champs requis (`required`)
- Emails (`type="email"`)
- Téléphones (`type="tel"`)
- Nombres avec min/max (`type="number"`)

Affiche `.is-valid` ou `.is-invalid` automatiquement.

### Raccourcis Clavier

- **Ctrl+S** : Soumettre le formulaire (enregistrer)
- **Escape** : Annuler (avec confirmation si modifications)

### Confirmations

- Suppression : confirmation automatique sur `.btn-delete`
- Quitter : avertissement si modifications non sauvegardées

### Compteurs de Caractères

Activés automatiquement sur `<textarea maxlength="...">` :
- Affiche `X / MAX`
- Change de couleur selon le remplissage (vert → jaune → rouge)

## Responsive Design

Le framework est entièrement responsive :

- **Desktop (>992px)** : 3-4 colonnes
- **Tablet (768-992px)** : 2 colonnes
- **Mobile (<768px)** : 1 colonne, boutons empilés

### Breakpoints Recommandés

```php
<div class="row g-3">
    <div class="col-md-6 col-lg-4">
        <!-- 100% mobile, 50% tablet, 33% desktop -->
    </div>
    <div class="col-md-6 col-lg-8">
        <!-- 100% mobile, 50% tablet, 67% desktop -->
    </div>
</div>
```

## Animations

### Disponibles par Défaut

- **slideInDown** : header et alertes
- **fadeInUp** : cartes de formulaire
- **pulse** : boutons au survol
- **spin** : état de chargement

### Délais en Cascade

Les cartes s'animent automatiquement avec un délai de 0.1s entre chaque.

## Accessibilité

### Labels Obligatoires

Toujours associer un `<label>` à chaque champ :

```php
<label class="form-label" for="nomClient">Nom</label>
<input type="text" id="nomClient" name="nom" class="form-control">
```

### Indication des Champs Requis

Utiliser la classe `.required` qui ajoute automatiquement un `*` rouge :

```php
<label class="form-label required">Nom du client</label>
```

Ajouter aussi un indicateur général :

```php
<p class="required-indicator">
    <i class="bi bi-asterisk"></i> Champs obligatoires
</p>
```

### Textes d'Aide

```php
<small class="form-text-muted">
    <i class="bi bi-info-circle"></i>
    Entrez le numéro au format international
</small>
```

## Upload de Fichiers

```php
<div class="file-upload-wrapper">
    <input type="file" name="image" id="imageInput" 
           class="file-upload-input" accept="image/*">
    <label for="imageInput" class="file-upload-label">
        <i class="bi bi-cloud-upload"></i>
        <span>Choisir une image</span>
    </label>
</div>
```

Le nom du fichier s'affiche automatiquement après sélection.

## Exemples Complets

### Formulaire Simple (Client)

```php
<div class="container-fluid">
    <div class="form-page-header">
        <div>
            <h1 class="form-page-title">
                <i class="bi bi-person-fill"></i>
                <?= $modeEdition ? 'Modifier un client' : 'Nouveau client' ?>
            </h1>
            <p class="form-page-subtitle mb-0">Gestion des clients</p>
        </div>
        <a href="<?= url_for('clients/list.php') ?>" class="btn btn-cancel">
            <i class="bi bi-arrow-left me-2"></i> Retour
        </a>
    </div>

    <form method="post" class="card form-card" id="formClient">
        <div class="card-body">
            <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
            
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label required">
                        <i class="bi bi-person"></i> Nom
                    </label>
                    <input type="text" name="nom" class="form-control" required>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">
                        <i class="bi bi-telephone"></i> Téléphone
                    </label>
                    <input type="tel" name="telephone" class="form-control">
                </div>
                
                <div class="col-md-12">
                    <label class="form-label">
                        <i class="bi bi-envelope"></i> Email
                    </label>
                    <input type="email" name="email" class="form-control">
                </div>
            </div>
        </div>
        
        <div class="form-actions">
            <a href="<?= url_for('clients/list.php') ?>" class="btn btn-cancel">
                <i class="bi bi-x-circle me-2"></i>Annuler
            </a>
            <button type="submit" class="btn btn-save">
                <i class="bi bi-check-circle me-2"></i>Enregistrer
            </button>
        </div>
    </form>
</div>
```

### Formulaire Multi-Sections (Produit)

```php
<form method="post" class="card form-card" id="formProduit">
    <div class="card-body">
        <!-- Section 1 : Informations générales -->
        <div class="form-section">
            <h2 class="form-section-title">
                <i class="bi bi-info-circle"></i>
                Informations générales
            </h2>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label required">
                        <i class="bi bi-upc-scan"></i> Code produit
                    </label>
                    <input type="text" name="code" class="form-control" required>
                </div>
                <!-- Autres champs -->
            </div>
        </div>

        <div class="form-row-separator"></div>

        <!-- Section 2 : Prix et stock -->
        <div class="form-section">
            <h2 class="form-section-title">
                <i class="bi bi-currency-dollar"></i>
                Prix et stock
            </h2>
            <div class="row g-3">
                <!-- Champs de prix -->
            </div>
        </div>
    </div>
    
    <div class="form-actions">
        <!-- Boutons -->
    </div>
</form>
```

## Checklist de Modernisation

Pour moderniser une page `edit.php` :

- [ ] Ajouter `class="form-page-header"` au header
- [ ] Ajouter icône et subtitle dans le titre
- [ ] Changer boutons header en `.btn-cancel`
- [ ] Ajouter `class="form-card"` et `id="formNom"` au formulaire
- [ ] Remplacer `.alert` par `.form-alert`
- [ ] Ajouter icônes dans tous les labels importants
- [ ] Utiliser `.form-label.required` pour champs requis
- [ ] Remplacer footer par `<div class="form-actions">`
- [ ] Utiliser `.btn-save`, `.btn-cancel`, `.btn-delete`
- [ ] Ajouter icônes dans tous les boutons
- [ ] Tester la validation automatique
- [ ] Tester les raccourcis clavier
- [ ] Vérifier le responsive mobile

## Maintenance

### Ajout d'une Nouvelle Page

1. Créer le formulaire avec la structure standard
2. Ajouter un ID unique pour l'auto-save
3. Utiliser les classes du framework
4. Tester sur mobile/tablet/desktop

### Personnalisation

Pour des besoins spécifiques, créer un fichier CSS additionnel :

```php
<link rel="stylesheet" href="assets/css/custom-forms-module.css">
```

Ne **jamais** modifier `modern-forms.css` directement.

## Compatibilité

- **Navigateurs** : Chrome, Firefox, Safari, Edge (dernières versions)
- **Bootstrap** : 5.3.3
- **Bootstrap Icons** : 1.11.3
- **PHP** : 8.0+

## Performance

- **CSS** : 635 lignes, ~18KB (minifié ~12KB)
- **JS** : 350 lignes, ~12KB (minifié ~8KB)
- **Temps de chargement** : < 50ms
- **Impact FCP** : Minimal (inline critical CSS possible)

## Support

Pour toute question ou problème, consulter :
- Ce guide
- Code source dans `assets/css/modern-forms.css`
- Code source dans `assets/js/modern-forms.js`
- Exemples dans `clients/edit.php`, `produits/edit.php`, `digital/leads_edit.php`

---

**Version** : 1.0  
**Dernière mise à jour** : Décembre 2025  
**Auteur** : KMS Gestion - Équipe Dev
