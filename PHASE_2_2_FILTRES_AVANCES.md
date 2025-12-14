# Phase 2.2 - Filtres Avancés & Recherche Texte

## Objectif
Améliorer la navigation & découverte des données via **recherche texte** et **tri dynamique** des colonnes.

## Implémentation Complète

### 1. **Recherche Texte Rapide**
Chaque liste principale intègre une barre de recherche qui cherche dans plusieurs colonnes:

- **ventes/list.php**: N° vente, client, observations
- **livraisons/list.php**: N° BL, client, N° vente
- **coordination/litiges.php**: N° litige, client, description

**Pattern SQL**:
```php
if (!empty($search)) {
    $where[] = "(v.numero LIKE ? OR c.nom LIKE ? OR v.observations LIKE ?)";
    $searchTerm = '%' . $search . '%';
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}
```

### 2. **Tri Dynamique des Colonnes**
Les en-têtes du tableau sont cliquables et permettent le tri ascendant/descendant:

- **Date** (DESC par défaut)
- **Client** (A-Z par défaut)
- **Montant/Totaux** (DESC par défaut)

**Pattern d'URL**:
```
?sort_by=date&sort_dir=desc
?sort_by=client&sort_dir=asc
```

**Rendu HTML**:
```html
<th>
    <a href="?sort_by=date&sort_dir=desc&...">
        Date 
        <i class="bi bi-arrow-down"></i>  <!-- Affichée si tri actif -->
    </a>
</th>
```

### 3. **Affichage des Filtres Actifs**
Un bandeau affiche les filtres et recherches appliqués:

```
Filtres actifs: 
[Recherche: "café"] [Du: 2024-01-01] [Au: 2024-12-31] [Statut: LIVREE]
```

Permet l'**audit** et la **réinitialisation facile**.

### 4. **Persistance des Filtres via URL**
Tous les paramètres sont préservés dans les URLs:

- Lors du tri
- Lors de l'export Excel
- Lors de la pagination (future)

**Exemple**:
```php
// Tri conserve les filtres
<a href="?<?= http_build_query(array_merge($_GET, ['sort_by' => 'date', 'sort_dir' => 'asc'])) ?>">
    Date
</a>

// Export inclut les filtres appliqués
<a href="export_excel.php?date_debut=2024-01-01&search=test&sort_by=date">
    Exporter Excel
</a>
```

## Fichiers Modifiés

| Fichier | Changements |
|---------|------------|
| `ventes/list.php` | Recherche, tri par date/client/montant, affichage filtres |
| `livraisons/list.php` | Recherche, tri par date/client/numero, affichage filtres |
| `lib/filters_helpers.php` | Helper functions pour recherche, pagination |
| `components/sortable_header.php` | Composant réutilisable de tri |

## Prochaines Étapes (Phase 2.2+)

- [ ] **Pagination**: Ajouter limite 25 résultats/page avec contrôles
- [ ] **Sauvegarde Preferences**: Enregistrer sort_by/sort_dir en session
- [ ] **Filtres Avancés**: Date range picker, multi-select client, tags
- [ ] **Export Intelligent**: Exporter uniquement les colonnes visibles

## Test Manual

1. **Recherche**: Taper "test" → doit trouver correspondances dans colonnes cibles
2. **Tri**: Cliquer en-tête "Date" → tri ascendant; recliquer → tri descendant
3. **Filtres Actifs**: Appliquer filtre → badge doit afficher en rouge
4. **Export**: Filtrer + chercher → Excel doit inclure uniquement les résultats filtrés
5. **Réinitialiser**: Cliquer "Réinitialiser" → tous les filtres doivent disparaître

## Performance Considérations

- **LIKE searches**: Index sur `numero`, `client.nom`, `observations` fortement recommandé
- **Large datasets**: Ajouter pagination (25 résultats) pour éviter timeouts
- **Partial index**: `INDEX (numero(10), client_id)` pour optimiser recherche

## Sécurité

- ✅ Tous les inputs `$_GET` sont validés/échappés (`htmlspecialchars`)
- ✅ Requêtes SQL utilisent prepared statements
- ✅ Tri limité à colonnes whitelist (date, client, montant)
