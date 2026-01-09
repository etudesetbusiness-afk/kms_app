# Module Reporting Terrain

## Description
Le module **Reporting Hebdomadaire – Activité commerciale terrain** permet aux commerciaux de KMS de créer des rapports hebdomadaires structurés sur leur activité terrain.

## Fonctionnalités

### 1. Liste des reportings (`index.php`)
- Affichage paginé des reportings
- Vue mobile (cards) et desktop (tableau)
- Filtrage automatique : les commerciaux voient uniquement leurs reportings, les admins voient tous
- Actions : Voir, Imprimer

### 2. Création de reporting (`create.php`)
- Formulaire mobile-first avec sections accordéon Bootstrap
- 8 sections de saisie :
  1. **Identification** : Commercial, période, ville, responsable
  2. **Zones & Cibles** : Zones visitées par jour (Lun-Sam)
  3. **Suivi Journalier** : Contacts, décideurs, échantillons, grille prix, RDV
  4. **Résultats Commerciaux** : 6 indicateurs avec objectif/réalisé/écart auto
  5. **Objections** : Types d'objections rencontrées avec fréquence
  6. **Arguments** : Arguments efficaces avec impact
  7. **Plan d'action** : 3 priorités pour la semaine suivante
  8. **Synthèse** : Résumé libre (max 900 caractères)
  9. **Signature** : Nom pour validation

### 3. Affichage détaillé (`show.php`)
- Vue complète du reporting en lecture seule
- Design responsive avec cards et tableaux
- Bouton d'impression

### 4. Version imprimable (`print.php`)
- Layout A4 optimisé pour `window.print()`
- CSS print dédié
- En-tête avec logo KMS et date d'impression
- Toutes les sections sur format A4

## Structure des tables

```sql
-- Table principale
terrain_reporting
├── id (PK)
├── user_id (FK → utilisateurs)
├── commercial_nom
├── semaine_debut / semaine_fin
├── ville, responsable_nom
├── synthese (max 900 chars)
├── signature_nom
└── created_at / updated_at

-- Tables enfants (FK avec ON DELETE CASCADE)
terrain_reporting_zones           -- 6 lignes par reporting (Lun-Sam)
terrain_reporting_activite        -- 6 lignes par reporting
terrain_reporting_resultats       -- 6 indicateurs
terrain_reporting_objections      -- 0-6 lignes (seulement actives)
terrain_reporting_arguments       -- 0-5 lignes (seulement actives)
terrain_reporting_plan_action     -- 0-3 lignes
```

## Installation

### 1. Exécuter la migration SQL
```bash
# Via PHP
php install_terrain_reporting.php

# Ou via MySQL CLI
mysql -u root -p kms_gestion < db/migrations/003_terrain_reporting.sql
```

### 2. Vérifier les tables créées
```sql
SHOW TABLES LIKE 'terrain_reporting%';
```

## Accès et permissions

- **Permission requise** : `VENTES_LIRE`
- **Lien sidebar** : Commercial → Reporting terrain
- **Accès lecture** :
  - Admin : tous les reportings
  - Commercial : ses propres reportings uniquement

## Fichiers du module

```
commercial/reporting_terrain/
├── index.php      # Liste des reportings
├── create.php     # Formulaire de création
├── store.php      # Traitement POST
├── show.php       # Vue détaillée
├── print.php      # Version imprimable A4
└── README.md      # Cette documentation
```

## Design

- **Mobile-first** : Accordéons pour navigation facile sur téléphone
- **Bootstrap 5** : Composants standard de l'application
- **Responsive** : Tables sur desktop, cards sur mobile
- **Print CSS** : Optimisé format A4 via `@media print`

## Workflow utilisateur

1. Commercial → Sidebar → Commercial → Reporting terrain
2. Clic sur "Nouveau Reporting"
3. Remplit les sections une par une (accordéon)
4. Soumet le formulaire → Redirection vers `show.php`
5. Peut imprimer via le bouton ou `Ctrl+P`

## Notes techniques

- Toutes les insertions dans une **transaction** (rollback en cas d'erreur)
- **CSRF** validé sur chaque POST
- **Écart auto-calculé** en JavaScript côté client
- **Compteur de caractères** pour la synthèse
- **Dates pré-remplies** : semaine courante (Lundi → Samedi)
