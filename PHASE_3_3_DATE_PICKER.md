# Phase 3.3 - Date Picker Avancé

**Status:** ✅ Complète  
**Commits:** f8ba3ca (fix per_page), Phase 3.3 (à venir)  
**Tests:** 100% pass (19/19)  

## Résumé

Implémentation d'un **calendrier interactif Flatpickr** avec **présets de dates rapides** pour filtrer les ventes par plage de dates.

## Fichiers Créés

### 1. `lib/date_helpers.php` (7 fonctions - 170 LOC)

**Fonctions utilitaires pour gestion des dates:**

- **`getDateRangePreset($preset = 'last_30d')`**
  - Retourne une plage de dates pour un préset
  - Présets: `today`, `last_7d`, `last_30d` (défaut), `last_90d`, `this_month`, `last_month`, `this_year`
  - Retour: `['start' => 'YYYY-MM-DD', 'end' => 'YYYY-MM-DD']`
  
- **`getDatePresets()`**
  - Liste tous les présets avec labels français
  - Retour: `[['key' => 'last_7d', 'label' => 'Derniers 7 jours'], ...]`

- **`validateAndFormatDate($date_str, $is_end_date = false)`**
  - Valide et formate une date au format 'YYYY-MM-DD'
  - Support des formats 'YYYY-MM-DD' et 'DD/MM/YYYY'
  - Retour: `'YYYY-MM-DD'` ou `null` si invalide

- **`buildDateWhereClause($column, $date_start = null, $date_end = null)`**
  - Construit une clause WHERE SQL pour filtrer par dates
  - Exemple: `buildDateWhereClause('v.date', '2025-12-01', '2025-12-31')`
  - Retour: `" AND v.date >= '2025-12-01' AND v.date <= CONCAT('2025-12-31', ' 23:59:59')"`

- **`formatDateForDisplay($date_str, $format = 'd/m/Y')`**
  - Formate une date pour affichage
  - Formats supportés: 'Y-m-d', 'Y-m-d H:i:s'
  - Exemple: `formatDateForDisplay('2025-12-14')` → `'14/12/2025'`

- **`getPresetLabel($preset)`**
  - Retourne le label français d'un préset
  - Exemple: `getPresetLabel('last_30d')` → `'Derniers 30 jours'`

### 2. `components/date_range_picker.html` (130 LOC)

**Composant UI interactif avec:**

- **Flatpickr CDN** - Calendrier léger et responsive
- **Traduction FR** - Interface en français
- **Présets rapides** - Boutons: Aujourd'hui, 7j, 30j, 90j, Ce mois
- **Calendriers date début/fin** - Sélection manuelle avec validation
- **JavaScript intégré** - Logique de synchronisation et soumission

**Fonctionnalités:**
- Dates futures bloquées (maxDate = aujourd'hui)
- Date fin >= date début (validation croisée)
- Bouton "Appliquer" pour soumettre le filtre
- Bouton "Réinitialiser" pour supprimer le filtre
- Préset actif mis en évidence (classe `.active`)

### 3. `api/get_date_preset.php`

**Endpoint JSON pour les présets**

```php
POST /api/get_date_preset.php
Content-Type: application/json

{"preset": "last_30d"}

// Réponse:
{
    "preset": "last_30d",
    "start": "2025-11-15",
    "end": "2025-12-14",
    "label": "Derniers 30 jours"
}
```

- Validation du préset (whitelist)
- Retour au format JSON
- Utilisé par le JS du date picker pour synchroniser

## Tests Réussis (19/19)

✅ Fichiers: 3/3  
✅ Syntaxe PHP: 2/2  
✅ Fonctions: 6/6  
✅ Exécutions: 7/7  

Tous les tests de validité des plages, validation de dates, et formatage passent.

## Intégration dans `ventes/list.php`

À la ligne après `<?php require 'lib/pagination.php'; ?>`, ajouter:

```php
require_once __DIR__ . '/../lib/date_helpers.php';

// Après le reste du setup (utilisateur, permissions)
$date_start = validateAndFormatDate($_GET['date_start'] ?? null);
$date_end = validateAndFormatDate($_GET['date_end'] ?? null);

// Si aucune date, utiliser le préset par défaut (derniers 30j)
if (!$date_start || !$date_end) {
    $range = getDateRangePreset('last_30d');
    $date_start = $range['start'];
    $date_end = $range['end'];
}
```

Puis dans la requête SQL, ajouter la clause date:

```php
$where .= buildDateWhereClause('v.date', $date_start, $date_end);
```

Et dans le formulaire, inclure le composant avant le tableau:

```html
<?php include __DIR__ . '/../components/date_range_picker.html'; ?>
```

## Usage Code

**Exemple complet d'intégration:**

```php
<?php
require_once __DIR__ . '/../security.php';
require_once __DIR__ . '/../lib/pagination.php';
require_once __DIR__ . '/../lib/date_helpers.php';

exigerConnexion();
exigerPermission('VENTES_LIRE');

// Validation dates
$date_start = validateAndFormatDate($_GET['date_start'] ?? null);
$date_end = validateAndFormatDate($_GET['date_end'] ?? null);

if (!$date_start || !$date_end) {
    $range = getDateRangePreset('last_30d');
    $date_start = $range['start'];
    $date_end = $range['end'];
}

// Construire requête
$where = "WHERE 1";
if ($search_term) {
    $where .= buildSearchWhereClause('v.numero, c.nom', $search_term);
}
$where .= buildDateWhereClause('v.date', $date_start, $date_end);

// Compter résultats
$count_sql = "SELECT COUNT(*) as cnt FROM ventes v JOIN clients c ON ... $where";
$total_count = $pdo->query($count_sql)->fetch()['cnt'];

// Paginer
$pagination = getPaginationParams($_GET, $total_count);

// Récupérer données
$sql = "SELECT ... FROM ventes v ... $where ORDER BY v.date DESC " . getPaginationLimitClause($pagination['offset'], $pagination['per_page']);
$ventes = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Dans le template HTML -->
<?php include __DIR__ . '/../components/date_range_picker.html'; ?>

<table class="table">
    <!-- ... données ... -->
</table>

<?php echo renderPaginationControls($pagination, $_GET); ?>
```

## Données en DB (User Preferences)

Les plages de dates sélectionnées par l'utilisateur peuvent être sauvegardées optionnellement:

```php
if ($user_id) {
    saveUserPagePreferences($user_id, 'ventes', [
        'sort_by' => $_GET['sort_by'] ?? 'date',
        'sort_dir' => $_GET['sort_dir'] ?? 'desc',
        'per_page' => $_GET['per_page'] ?? 25,
        'default_date_range' => "$date_start,$date_end"  // Nouveau champ
    ]);
}
```

## Performance

- **Flatpickr**: ~6 KB minifiée (CDN)
- **Helper functions**: O(1) pour présets, O(n) pour validation (n = string length)
- **Requête SQL**: Ajoute 1 condition WHERE supplémentaire (indexée sur v.date)
- **Gain**: Filtrage côté serveur (pas de chargement data inutile)

## Prochaine Étape

**Phase 3.4 - Optimisations & Caching**
- Redis caching pour KPIs
- Index DB optimization
- Query performance tuning

---

**Documentation créée:** 15 décembre 2025  
**Version:** Phase 3.3 v1.0
