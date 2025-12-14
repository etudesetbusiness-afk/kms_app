# Phase 2.4 - Tests Utilisateur & Polish

**Date :** 15 décembre 2025  
**Objectif :** Valider UX des filtres/dashboards et optimiser les perfs  
**Statut :** 🚀 En cours

---

## 📋 Checklist de Test Utilisateur

### 1️⃣ Recherche & Filtres (Ventes)

**Page :** `ventes/list.php`

- [ ] **Recherche texte**
  - [ ] Chercher par numéro de vente (ex: "VEN001")
  - [ ] Chercher par nom client (ex: "ACME")
  - [ ] Chercher par observations
  - [ ] Chercher avec espaces (trim automatique ✅)
  - [ ] Recherche vide → affiche toutes les ventes

- [ ] **Tri dynamique (Sortable headers)**
  - [ ] Cliquer "Date" → trier ASC
  - [ ] Cliquer "Date" à nouveau → trier DESC
  - [ ] Cliquer "Client" → trier A→Z
  - [ ] Cliquer "Montant" → trier montants croissants
  - [ ] Icônes de direction affichées ✅

- [ ] **Persistance des filtres**
  - [ ] Appliquer search + sort
  - [ ] Cliquer "Export Excel"
  - [ ] URL conserve les paramètres (search=..., sort_by=..., sort_dir=...)

- [ ] **Affichage des filtres actifs**
  - [ ] Badges visibles avec filtres appliqués
  - [ ] Badge "search: ACME" affiche bien
  - [ ] Badge "tri: Date ↓" affiche bien

**Résultat attendu :** ✅ Tous les filtres fonctionnent, URL persistante, badges clairs

---

### 2️⃣ Recherche & Filtres (Livraisons)

**Page :** `livraisons/list.php`

- [ ] **Recherche texte**
  - [ ] Chercher par BL numéro
  - [ ] Chercher par nom client
  - [ ] Chercher par vente numéro
  - [ ] Résultats mis à jour live

- [ ] **Tri dynamique**
  - [ ] Cliquer "Date" → trier DESC par défaut
  - [ ] Cliquer "Client" → trier A→Z
  - [ ] Cliquer "Numéro" → trier numériquement

- [ ] **Filtres spécifiques**
  - [ ] Filtre "Signature" → Signé / Non signé
  - [ ] Filtre "Date" → Conserve plage
  - [ ] Affichage "5 BL non signés" correct

**Résultat attendu :** ✅ Recherche + tri + signature filtre combinés

---

### 3️⃣ Recherche & Filtres (Litiges)

**Page :** `coordination/litiges.php`

- [ ] **Recherche multi-colonnes**
  - [ ] Chercher par client
  - [ ] Chercher par produit
  - [ ] Chercher par vente
  - [ ] Chercher par description du litige

- [ ] **Tri** 
  - [ ] Cliquer "Date" → DESC
  - [ ] Cliquer "Client" → A→Z
  - [ ] Statistics (nb litiges) mis à jour après filtre

- [ ] **Affichage statistiques**
  - [ ] "5 litiges" affiche bien
  - [ ] Badges d'alertes visibles
  - [ ] Compteurs statut correct

**Résultat attendu :** ✅ Recherche 4-colonnes, tri 2-ways, stats live

---

### 4️⃣ Dashboard

**Page :** `dashboard.php`

- [ ] **KPI Cards**
  - [ ] CA jour affiché (montant + source breakdown)
  - [ ] CA mois affiché (montant + moyenne jour)
  - [ ] Encaissement % visible (montant + %)
  - [ ] BL signés % visible (count + %)
  - [ ] Ruptures de stock affichées
  - [ ] Stock faible alerté
  - [ ] Valeur stock calculée

- [ ] **Charts (Chart.js)**
  - [ ] Graphique CA 30j charge correctement
  - [ ] 3 datasets (Ventes teal, Hôtel orange, Formation purple)
  - [ ] Doughnut encaissement affiche statuts
  - [ ] Responsive sur mobile ✅

- [ ] **Alertes critiques**
  - [ ] Affichage si > 0 alertes
  - [ ] Icônes corrects (warning, danger)
  - [ ] Compte dévis expiés (>30j)
  - [ ] Compte litiges en retard (>7j)
  - [ ] Compte ruptures stock
  - [ ] Compte clients inactifs (>60j)

- [ ] **Activité récente**
  - [ ] 5 dernières ventes affichées
  - [ ] 5 derniers BLs affichés
  - [ ] Dates formatées correctement
  - [ ] Montants formatés (1.2M FCFA)

**Résultat attendu :** ✅ Tous les KPIs chargent, charts affichent bien, alertes correctes

---

### 5️⃣ Performance & Chargement

**Tous les formulaires**

- [ ] **Temps de chargement**
  - [ ] ventes/list.php → < 2s (initial)
  - [ ] livraisons/list.php → < 2s
  - [ ] dashboard.php → < 3s (charts inclus)
  - [ ] coordination/litiges.php → < 2s

- [ ] **Responsive design**
  - [ ] Écran desktop : layout correct
  - [ ] Écran mobile (500px) : colonnes stack bien
  - [ ] Écran tablet (800px) : lisible
  - [ ] Tables scrollable sur mobile

- [ ] **Pas d'erreurs console**
  - [ ] F12 → Console → Pas d'erreurs JS
  - [ ] Pas d'erreurs 404 (assets manquants)
  - [ ] Pas de warnings CORS

---

### 6️⃣ Export Excel

**Ventes, Livraisons, Litiges**

- [ ] **Génération**
  - [ ] Cliquer "Exporter en Excel"
  - [ ] Fichier télécharge (ventes_YYYYMMDD.xlsx)
  - [ ] Pas d'erreur 500

- [ ] **Contenu**
  - [ ] Toutes les colonnes présentes
  - [ ] En-têtes formatés (gras, couleur fond)
  - [ ] Données conservent filtres appliqués ✅

- [ ] **Formatage**
  - [ ] Montants en nombres (pas texte)
  - [ ] Dates lisibles (DD/MM/YYYY)
  - [ ] Couleurs alternées (lisibilité)

**Résultat attendu :** ✅ Export rapide, bien formaté, filtered data

---

### 7️⃣ Sécurité & Permissions

**Tous les formulaires**

- [ ] **CSRF tokens**
  - [ ] Formulaires POST ont token
  - [ ] Modification données → POST+CSRF requis

- [ ] **Permissions par rôle**
  - [ ] ADMIN : Peut tout voir
  - [ ] SHOWROOM : Voir ventes/livraisons/dashboard
  - [ ] MAGASINIER : Voir stock/BLs
  - [ ] CAISSIER : Voir caisse/ventes
  - [ ] Utilisateurs sans perms → 403 Forbidden

- [ ] **Pas d'injection SQL**
  - [ ] Chercher `1' OR '1'='1` → Pas de résultats inattendus
  - [ ] Chercher avec caractères spéciaux → Échappés correctement

**Résultat attendu :** ✅ CSRF actif, perms validées, SQL safe

---

## 🔧 Optimisations de Performance

### Base de données
- [ ] INDEX sur colonnes recherche (numero, nom, vente_id)
- [ ] INDEX sur dates (date_creation, date_livraison)
- [ ] Vérifier EXPLAIN PLAN sur queries lourdes

### PHP
- [ ] Cache les résultats KPI (5 min)
- [ ] Lazy load les charts (async)
- [ ] Compression Gzip activée

### Frontend
- [ ] Minifier JS/CSS
- [ ] Defer les scripts lourds
- [ ] Chart.js en CDN (already loaded)
- [ ] Optimiser images SVG

---

## 📱 UX Polish Checklist

- [ ] **Cohérence visuelle**
  - [ ] Couleurs Bootstrap 5 respectées
  - [ ] Spacing/padding régulier
  - [ ] Typo lisible (16px min mobile)
  - [ ] Buttons hover states visibles

- [ ] **Accessibilité**
  - [ ] Contrastes respectés (WCAG AA)
  - [ ] Labels sur tous les inputs
  - [ ] Tabulation logique (keyboard nav)
  - [ ] Alt-text sur images

- [ ] **Feedback utilisateur**
  - [ ] Messages success visibles
  - [ ] Erreurs bien expliquées
  - [ ] Loading spinners sur requêtes lourdes
  - [ ] Confirmations avant suppression

---

## 📚 Documentation Utilisateur

**À créer :**
- [ ] Guide recherche & filtres (screenshots)
- [ ] Guide dashboard (interprétation KPIs)
- [ ] Guide export Excel
- [ ] FAQ troubleshooting

---

## ✅ Sign-off Utilisateur

| Rôle | Testé | Validé | Notes |
|------|-------|--------|-------|
| SHOWROOM | ☐ | ☐ | |
| MAGASINIER | ☐ | ☐ | |
| CAISSIER | ☐ | ☐ | |
| DIRECTION | ☐ | ☐ | |

---

## 🚀 Prochaines étapes

- ✅ Phase 2.4 complète → Phase 3.1 (Pagination)
- Bug fixes basés sur feedback utilisateur
- Performance optimizations
- Mobile polish (si feedback)

