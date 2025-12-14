# Phase 3.2 - Préférences Utilisateur

**Date :** 15 décembre 2025  
**Statut :** ✅ COMPLÉTÉE  
**Pass Rate :** 100% (18/18 tests)

---

## 📊 Résultats

| Test | Résultat |
|------|----------|
| Files & Table | ✅ 3/3 |
| Syntax | ✅ 2/2 |
| Functions | ✅ 7/7 |
| Function Tests | ✅ 6/6 |
| **Total** | **✅ 18/18** |

---

## 🎯 Fonctionnalités

### 1. **Table `user_preferences`**

Stocke les préférences par utilisateur et par page :

```sql
CREATE TABLE user_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL,
    page_name VARCHAR(100) NOT NULL,        -- 'ventes', 'livraisons', 'litiges'
    sort_by VARCHAR(50) DEFAULT 'date',     -- colonne de tri
    sort_dir VARCHAR(4) DEFAULT 'desc',     -- 'asc' ou 'desc'
    per_page INT DEFAULT 25,                -- 10, 25, 50, 100
    remember_filters BOOLEAN DEFAULT 1,     -- conserver les filtres
    default_date_range VARCHAR(20),         -- 'last_7d', 'last_30d', 'last_90d'
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    UNIQUE KEY unique_user_page (utilisateur_id, page_name)
);
```

### 2. **lib/user_preferences.php** - API

```php
// Récupérer les préférences (defaults si inexistant)
$prefs = getUserPagePreferences($user_id, 'ventes');
// => ['sort_by' => 'date', 'sort_dir' => 'desc', 'per_page' => 25, ...]

// Sauvegarder les préférences
saveUserPagePreferences($user_id, 'ventes', [
    'sort_by' => 'client',
    'sort_dir' => 'asc',
    'per_page' => 50
]); // => true

// Fusionner GET params avec préférences (GET has priority)
$merged = mergePreferencesWithGet(
    $_GET,                           // {'sort_by': 'montant'}
    $prefs,                          // {'sort_by': 'client', 'per_page': 50}
    ['date', 'client', 'montant']    // colonnes autorisées
);
// => ['sort_by' => 'montant', 'per_page' => 50] ✅ GET wins

// Mettre à jour les prefs automatiquement (si GET contient des changements)
$final = updateUserPreferencesFromGet($user_id, 'ventes', $_GET, ['date', 'client', 'montant']);
// 1. Compare GET vs prefs actuelles
// 2. Si différent, sauvegarde en BD
// 3. Retourne les prefs finales

// Récupérer toutes les prefs d'un utilisateur
$all_prefs = getUserAllPreferences($user_id);
// => [{'page_name': 'ventes', 'sort_by': 'date', ...}, ...]

// Supprimer une pref
deleteUserPagePreferences($user_id, 'ventes'); // => true

// Réinitialiser toutes les prefs
resetAllUserPreferences($user_id); // => true
```

---

## 🔄 Workflow Intégration

### Avant (sans prefs)
```php
$sortBy = $_GET['sort_by'] ?? 'date';
$sortDir = $_GET['sort_dir'] ?? 'desc';
```

### Après (avec prefs)
```php
require_once __DIR__ . '/../lib/user_preferences.php';

// 1. Charger + appliquer prefs
if ($user_id) {
    $prefs = updateUserPreferencesFromGet(
        $user_id, 
        'ventes', 
        $_GET, 
        ['date', 'client', 'montant']
    );
    $sortBy = $prefs['sort_by'];
    $sortDir = $prefs['sort_dir'];
    $per_page = $prefs['per_page'];
} else {
    $sortBy = $_GET['sort_by'] ?? 'date';
    $sortDir = $_GET['sort_dir'] ?? 'desc';
    $per_page = 25;
}
```

**Résultat :**
- 1er visite : GET params → defaults appliqués
- Changement tri/pagination → BD mise à jour
- 2e visite : prefs rechargées automatiquement ✅

---

## 📈 Cas d'Usage

### Utilisateur A (SHOWROOM)
1. Visite ventes/list.php
2. Change tri: `?sort_by=client` (preference sauvegardée)
3. Change per_page: `?per_page=50` (preference mise à jour)
4. Quitte la page
5. **Revisite ventes/list.php → tri=client, per_page=50 appliqués auto** ✅

### Utilisateur B (MAGASINIER)
1. Préfère les livraisons triées par date DESC (défaut)
2. Préfère 10 résultats par page
3. Clique: `livraisons/list.php?per_page=10`
4. Preference sauvegardée (per_page=10)
5. **Toutes futures visites:** per_page=10 appliqué ✅

---

## 🔒 Sécurité & Validation

| Paramètre | Whitelist | Défaut | Exemple |
|-----------|-----------|--------|---------|
| `sort_by` | Colonnes autorisées | 'date' | 'client', 'montant' |
| `sort_dir` | ['asc', 'desc'] | 'desc' | 'asc', 'desc' |
| `per_page` | [10, 25, 50, 100] | 25 | 25, 50 |
| `remember_filters` | [0, 1] | 1 | 0, 1 |

**Exemples de rejet :**
- `sort_by=hacker_injection` → 'date' (whitelist fail)
- `per_page=999` → 25 (not in [10,25,50,100])
- `sort_dir=invalid` → 'desc' (not in [asc,desc])

---

## 📋 Intégrations (Prêtes)

**Phase 3.2 implémentation :**
- ✅ `ventes/list.php` - Intégrée

**À ajouter (copy/paste) :**
- [ ] `livraisons/list.php`
- [ ] `coordination/litiges.php`
- [ ] Dashboard (optionnel)

---

## 🚀 Prochaines Phases

### Phase 3.3 - Date Picker Avancé
- Calendar UI (Flatpickr ou similaire)
- Presets (Last 7/30/90 days, This month, etc.)
- Date range selection

### Phase 3.4 - Optimisations
- Caching Redis des KPIs
- Indexation BD optimisée
- Compression Gzip

---

## 📚 Fichiers

- `db/003_user_preferences.sql` - Schema (19 LOC)
- `lib/user_preferences.php` - API (180 LOC)
- `ventes/list.php` - Intégration exemple (486 LOC)
- `test_phase3_2.php` - Tests (120 LOC)

---

**Status:** ✅ Phase 3.2 COMPLÉTÉE  
**Prochaine phase:** Phase 3.3 - Date Picker Avancé  
**Estimation:** 2-3 heures

