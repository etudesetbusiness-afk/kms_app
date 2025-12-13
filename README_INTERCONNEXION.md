# Système d'Interconnexion Ventes ↔ Livraisons ↔ Litiges ↔ Stock

## Vue d'ensemble

Ce système crée une **cohésion complète** entre tous les éléments du cycle vente, du moment où la commande est passée jusqu'au traitement des litiges, avec vérification constante de la synchronisation entre stock, caisse et comptabilité.

### Principe fondamental
> "Je dois pouvoir accéder, depuis une même interface ou via des liens clairs, depuis une vente → accéder à la livraison → consulter un litige → revenir au stock ou à la caisse."

---

## Architecture - 4 Pages Principales

### 1. **Vente 360° (`ventes/detail_360.php?id=ID`)**
La page **maître** qui montre TOUT pour une vente donnée.

**Contient 6 onglets :**
- **Informations** : Détails vente + lignes produits
- **Ordres de Préparation** : Tous les ordres créés pour cette vente
- **Livraisons** : Tous les bons de livraison avec quantités
- **Retours/Litiges** : Tous les litiges associés
- **Stock** : Tous les mouvements de stock (entrées/sorties)
- **Trésorerie & Comptabilité** : Encaissements + écritures comptables

**Synthèse en haut :**
- Montant TTC
- État livraison (%)
- Encaissement (%)
- Litiges (nombre + montant)
- Statut synchronisation (✅ OK ou ⚠️ ERREUR)

---

### 2. **Livraison Détail (`livraisons/detail_navigation.php?id=ID`)**
Vue détaillée d'un bon de livraison avec navigation complète.

**Caractéristiques :**
- Bouton direct vers la **Vente associée** en haut à droite
- Affichage des lignes avec quantités commandées vs livrées
- Détection des surlivraisons (badge)
- Onglets : Lignes, Ordres préparation, Litiges, Stock
- Toutes les opérations stock de cette date

---

### 3. **Litige Détail (`coordination/litiges_navigation.php?id=ID`)**
Gestion complète d'un retour/litige avec traçabilité totale.

**Caractéristiques :**
- Accès rapide à la **Vente** en haut à droite
- Détails du problème (type, motif, solution)
- Impact financier (remboursement + avoir)
- Onglets :
  - Informations (motif, solution, montants)
  - Vente Associée (infos + liste produits avec produit du litige surligné)
  - Livraisons (toutes les BL de cette vente)
  - Stock (historique mouvement de ce produit)

---

### 4. **Vérification Synchronisation (`coordination/verification_synchronisation.php`)**
Audit automatique de la cohérence du système.

**Vérifications effectuées :**
1. Montant livraisons = Montant vente (tolérance 100 FCFA)
2. Quantités livrées ≤ Quantités commandées
3. Sorties stock = Quantités livrées
4. Écritures comptables créées

**Affichage :**
- Tableau des 50 dernières ventes avec status (OK/ERREUR)
- KPIs : Ventes OK, Anomalies, Total encaissé, Total commandé
- Détails des erreurs expandables
- Clic sur numéro = Accès au détail 360°

---

### 5. **Dashboard Coordination (`coordination/dashboard.php`)**
Point d'entrée centralisé avec vue d'ensemble et alertes.

**Contient :**
- KPIs : Ventes (30j), Livrées, Litiges en cours, Anomalies
- Alertes critiques (ventes avec problèmes)
- Navigation rapide vers les pages principales
- Onglets : Dernières ventes, Flux de travail, Guide rapide

---

## Fonctions Helper (`lib/navigation_helpers.php`)

Ensemble de fonctions réutilisables pour accéder aux données liées :

```php
// Récupérer les litiges d'une vente
get_litiges_by_vente($pdo, $venteId)

// Récupérer les livraisons d'une vente
get_livraisons_by_vente($pdo, $venteId)

// Récupérer les ordres de préparation d'une vente
get_ordres_by_vente($pdo, $venteId)

// Montant encaissé pour une vente
get_montant_encaisse($pdo, $venteId)

// Montant des retours pour une vente
get_montant_retours($pdo, $venteId)

// Vérifier la cohérence d'une vente
verify_vente_coherence($pdo, $venteId) // retourne ['ok' => bool, 'problemes' => array]

// Récupérer un résumé statistique
get_vente_summary($pdo, $venteId)

// Récupérer la vente associée à une livraison
get_vente_by_livraison($pdo, $bonId)

// Récupérer la vente associée à un litige
get_vente_by_litige($pdo, $litigeId)

// Générer une mini-carte de navigation
generate_vente_nav_card($pdo, $venteId) // retourne HTML
```

---

## Flux de Synchronisation Automatique

### Vente → Stock → Caisse → Comptabilité

```
1. VENTE CRÉÉE
   ├─ Montant TTC = Σ(Lignes × PU)
   └─ Écritures comptables auto (créances client)

2. ORDRE DE PRÉPARATION
   └─ Marque les produits à préparer

3. BON DE LIVRAISON
   ├─ Quantités livrées ≤ Quantités commandées
   ├─ MOUVEMENTS STOCK AUTO (SORTIE)
   │  └─ Qté Sortie = Qté Livrée
   │  └─ Raison = "Livraison BL #XXX"
   └─ Écritures comptables (vente réalisée)

4. ENCAISSEMENT
   ├─ Montant ≤ Montant TTC
   └─ Écritures comptables (trésorerie)

5. LITIGE/RETOUR (optionnel)
   ├─ Type = DEFAUT_PRODUIT | ERREUR_LIVRAISON | INSATISFACTION_CLIENT | AUTRE
   ├─ Impact = Remboursement OU Avoir OU Les deux
   ├─ MOUVEMENTS STOCK AUTO (ENTREE)
   │  └─ Si produit retourné
   └─ Écritures comptables (ajustement)
```

---

## Points de Vérification Clés

| Point | Calcul | Où vérifier | Action si erreur |
|-------|--------|-------------|------------------|
| **Montants** | TTC Livr = TTC Vente | Vente 360° / Tab Synthèse | Vérifier BL et retours |
| **Quantités** | Σ(BL) ≤ Σ(Commande) | Livraison 360° | Livraison supplémentaire? |
| **Stock** | Sortie = Livrée | Vente 360° / Tab Stock | Mouvement manquant |
| **Caisse** | Encaissé ≤ TTC | Vente 360° / Tab Trésor | Paiement non saisi |
| **Comptabilité** | Écritures > 0 | Vente 360° / Tab Trésor | Configuration mappings |
| **Litiges** | Impact ≤ TTC | Vente 360° / Tab Litiges | Montants excessifs |

---

## Scénarios d'Utilisation

### Scénario 1 : "Je veux voir le statut complet d'une vente"
1. Aller à **Vente 360°**
2. Chercher la vente (par numéro ou client)
3. Voir immédiatement :
   - Livraison ? (%)
   - Encaissement ? (%)
   - Litiges ? (nombre)
   - Synchronisation ? (OK/ERREUR)
4. Cliquer sur les onglets pour détails

### Scénario 2 : "Une livraison est incorrecte"
1. Ouvrir la livraison
2. Cliquer **← Vente** pour voir le contexte
3. Vérifier :
   - Quantités commandées
   - Autres BL
   - Litiges associés
4. Identifier l'erreur (surlivraison? sous-livraison? produit incorrect?)

### Scénario 3 : "Un litige arrive"
1. Créer le litige dans **Coordin → Litiges**
2. Ouvrir le litige en détail
3. Voir automatiquement :
   - Vente d'origine
   - Toutes les BL de cette vente
   - Historique stock du produit
4. Documenter : motif, solution, impact financier
5. Marquer résolu

### Scénario 4 : "L'audit détecte une anomalie"
1. Accéder à **Vérification Synchronisation**
2. Voir la vente avec ERREUR en rouge
3. Cliquer pour voir les problèmes détectés
4. Cliquer sur le numéro → Vente 360°
5. Investiguer et corriger

---

## Implémentation Technique

### Fichiers créés/modifiés

**Pages principales :**
- `ventes/detail_360.php` - Vue 360° d'une vente
- `livraisons/detail_navigation.php` - Détail livraison avec navigation
- `coordination/litiges_navigation.php` - Détail litige avec navigation
- `coordination/verification_synchronisation.php` - Audit synchronisation
- `coordination/dashboard.php` - Dashboard coordination

**Helpers :**
- `lib/navigation_helpers.php` - Fonctions réutilisables

**Documentation :**
- `GUIDE_NAVIGATION_INTERCONNEXION.md` - Guide complet utilisateur
- `README_INTERCONNEXION.md` - Ce fichier

### Dépendances

**Fichiers requis :**
- `security.php` - Authentification et permissions
- `partials/header.php` - Navigation globale
- `partials/sidebar.php` - Menu latéral
- `assets/css/custom.css` - Styles (design system)

**Tables utilisées :**
- `ventes`
- `ventes_lignes`
- `bons_livraison`
- `bons_livraison_lignes`
- `ordres_preparation`
- `ordres_preparation_lignes`
- `retours_litiges`
- `stocks_mouvements`
- `caisse_journal`
- `compta_ecritures`
- `produits`
- `clients`
- `utilisateurs`

---

## Configuration Requise

### Permissions
Toutes les pages requièrent :
- Connexion : ✅ Obligatoire
- Permission : `VENTES_LIRE` minimum

Pour modifications :
- Créer/modifier ventes : `VENTES_MODIFIER`
- Créer/modifier livraisons : `LIVRAISONS_MODIFIER`
- Gérer litiges : `LITIGES_MODIFIER`

### Base de données
Aucune migration nécessaire. Les tables existent déjà.

Les relations FK suivantes doivent être présentes :
- `bons_livraison.vente_id` → `ventes.id`
- `retours_litiges.vente_id` → `ventes.id`
- `stocks_mouvements.reference_vente` → `ventes.id`

---

## Exemple d'Intégration dans le Sidebar

```php
// partials/sidebar.php - Ajouter dans la section coordination
<li class="sidebar-item">
    <a href="<?= url_for('coordination/dashboard.php') ?>" class="sidebar-link <?= is_active('coordination/dashboard.php') ? 'active' : '' ?>">
        <i class="bi bi-diagram-3"></i>
        <span>Coordination Ventes</span>
    </a>
</li>

// Sous-éléments optionnels
<li class="sidebar-item-nested">
    <a href="<?= url_for('coordination/verification_synchronisation.php') ?>" class="sidebar-link small">
        <i class="bi bi-check-all"></i> Vérifier Synchronisation
    </a>
</li>
```

---

## Amélioration Future

### Phase 2 (à envisager)
- [ ] Export audit (PDF)
- [ ] Graphiques temps réel (KPIs)
- [ ] Notifications (litiges, anomalies)
- [ ] Rapports de synchronisation planifiés
- [ ] Intégration caisse (rafraîchissement auto)
- [ ] API pour accès programmatique
- [ ] Dashboard mobile optimisé

### Phase 3
- [ ] Prédiction des retards livraison
- [ ] Analyse des litiges (tendances)
- [ ] Score de qualité par commercial
- [ ] Intégration EDI avec clients

---

## Support & Troubleshooting

### Question : "Où vais-je pour voir tout d'une vente ?"
→ **`ventes/detail_360.php?id=ID`**

### Question : "Comment vérifier si tout est cohérent ?"
→ **`coordination/verification_synchronisation.php`**

### Question : "Je veux juste voir les dernières ventes ?"
→ **`coordination/dashboard.php`** (Tab "Dernières Ventes")

### Problème : Vente sans livraison
→ Vérifier onglet Livraisons, créer une BL

### Problème : Quantités incohérentes
→ Vérifier Vente 360° → onglet Stock → mouvements

### Problème : Encaissement manquant
→ Vérifier Vente 360° → onglet Trésorerie → créer encaissement

---

## Changelog

### v1.0 - Système complet
- ✅ Vente 360° avec 6 onglets
- ✅ Navigation bidirectionnelle (vente ↔ livraison ↔ litige)
- ✅ Vérification automatique synchronisation
- ✅ Helpers réutilisables
- ✅ Dashboard coordination
- ✅ Guide utilisateur complet
- ✅ Design système intégré

---

## Contact & Questions

Pour toute question sur ce système, consulter :
- `GUIDE_NAVIGATION_INTERCONNEXION.md` - Guide détaillé
- `lib/navigation_helpers.php` - Documentations des fonctions
- Code source des pages (commentaires)

---

**Bienvenue dans le système d'interconnexion complète de KMS Gestion ! 🚀**
