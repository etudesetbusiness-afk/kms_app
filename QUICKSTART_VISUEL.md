# 🚀 QUICKSTART VISUEL - En 5 minutes

## 1️⃣ LES 5 PAGES PRINCIPALES

### Page 1 : DASHBOARD 📊
```
URL : http://localhost/kms_app/coordination/dashboard.php

┌─────────────────────────────────────────┐
│ COORDINATION VENTES-LIVRAISONS-LITIGES  │
├─────────────────────────────────────────┤
│ 📈 Ventes (30j): 45        Montant: 5M  │
│ ✅ Livrées: 40              (89%)        │
│ ⚠️  Litiges EN_COURS: 3                 │
│ ❌ Anomalies: 2             À vérifier  │
├─────────────────────────────────────────┤
│ [Vue 360°] [Vérif Sync] [Gestion Lit]  │
└─────────────────────────────────────────┘
```

### Page 2 : VENTE 360° 👁️
```
URL : http://localhost/kms_app/ventes/detail_360.php?id=1

┌─ Vente #V202411001 ────────────┐
│ Client: ABC SA                 │
│ Date: 15/11/2024               │
│                                │
│ Synthèse:                      │
│ 💰 Montant: 10,000,000 FCFA   │
│ 📦 Livraison: 100%             │
│ 💳 Encaissement: 75%           │
│ 📋 Litiges: 1 (100,000 FCFA)   │
│ ✅ Sync: OK                    │
│                                │
│ [Infos] [Ordres] [Livraisons]  │
│ [Litiges] [Stock] [Trésor]     │
└────────────────────────────────┘
```

### Page 3 : LIVRAISON 🚚
```
URL : http://localhost/kms_app/livraisons/detail_navigation.php?id=5

┌─ Bon Livraison #BL20241115001 ─┐
│                   [← Vente #V...] │
│ Date: 15/11/2024                │
│ Statut: COMPLETEMENT_LIVREE      │
│ Montant: 10,000,000 FCFA        │
│                                 │
│ Produits: 45 livrés              │
│ ← Cliquer pour voir Vente       │
│                                 │
│ [Lignes] [Ordres] [Litiges]     │
│ [Stock]                         │
└─────────────────────────────────┘
```

### Page 4 : LITIGE 📝
```
URL : http://localhost/kms_app/coordination/litiges_navigation.php?id=8

┌─ Litige #8 ───────────────────┐
│            [← Vente #V...] │
│ Client: ABC SA                 │
│ Produit: Ref-0045              │
│ Type: ERREUR_LIVRAISON         │
│                                │
│ Statut: EN_COURS               │
│ Montant Remboursé: 100,000 F   │
│                                │
│ Motif: Produit endommagé       │
│ Solution: En attente...        │
│                                │
│ [Infos] [Vente] [Livraisons]   │
│ [Stock]                        │
└────────────────────────────────┘
```

### Page 5 : VÉRIFICATION ✅
```
URL : http://localhost/kms_app/coordination/verification_synchronisation.php

┌─────────────────────────────────────────┐
│ AUDIT SYNCHRONISATION                   │
├─────────────────────────────────────────┤
│ ✅ Ventes OK: 43                        │
│ ❌ Anomalies: 2                         │
│ 💰 Total encaissé: 7,500,000 FCFA      │
│ 📦 Total commandé: 1,250 articles      │
│                                         │
│ ┌─────────────────────────────────────┐ │
│ │ V202411001 ✅ OK                     │ │
│ │ V202411002 ❌ Montants ≠            │ │
│ │   └─ Livr. = 8,5M, Vente = 10M      │ │
│ │ V202411003 ✅ OK                     │ │
│ └─────────────────────────────────────┘ │
└─────────────────────────────────────────┘
```

---

## 2️⃣ LES FLUX PRINCIPAUX

### FLUX STANDARD (Vente → Livraison)
```
1. VENTE CRÉÉE
   └─ Montant TTC = Σ(Lignes × PU)
   └─ Écritures compta auto

2. ORDRE DE PRÉPARATION CRÉÉ
   └─ Produits marqués pour préparation

3. BON LIVRAISON CRÉÉ
   └─ Quantités livrées enregistrées
   └─ MOUVEMENTS STOCK AUTO (SORTIE)
   └─ Écritures compta (vente réalisée)

4. ENCAISSEMENT
   └─ Paiement saisi
   └─ Écritures caisse

RÉSULTAT: Vente 100% complète + synchronisée ✅
```

### FLUX ANOMALIE (Litige)
```
1. CLIENT RETOURNE PRODUIT
   └─ Problème constaté

2. LITIGE CRÉÉ
   ├─ Type: DEFAUT_PRODUIT | ERREUR_LIVRAISON | ...
   ├─ Motif documenté
   └─ Solution proposée

3. IMPACT FINANCIER
   ├─ Remboursement (caisse)
   ├─ Avoir commercial
   └─ Écritures compta

4. MOUVEMENT STOCK
   ├─ ENTRÉE si produit retourné
   └─ Stock mis à jour

RÉSULTAT: Litige résolu + Données cohérentes ✅
```

---

## 3️⃣ NAVIGATION CROISÉE

### DE VENTE → LIVRAISON
```
Vente 360° (detail_360.php?id=1)
    ↓
Onglet "Livraisons"
    ↓
Cliquer sur une BL
    ↓
Livraison Navigation (detail_navigation.php?id=5)
    ↓
Voir tous les détails
```

### DE LIVRAISON → VENTE
```
Livraison Navigation (detail_navigation.php?id=5)
    ↓
Bouton "← Vente #V202411001" (en haut droit)
    ↓
Vente 360° (detail_360.php?id=1)
    ↓
Voir contexte global
```

### DE LITIGE → VENTE → LIVRAISONS
```
Litige Navigation (litiges_navigation.php?id=8)
    ↓
Bouton "← Vente" (en haut droit)
    ↓
OU Onglet "Vente" → Voir la vente
    ↓
OU Onglet "Livraisons" → Voir toutes les BL
    ↓
OU Onglet "Stock" → Voir historique produit
```

---

## 4️⃣ CAS D'USAGE COURANTS

### CAS 1️⃣ : "Je veux voir TOUT d'une vente"
```
Actions:
1. Ouvrir : detail_360.php?id=123
2. Lire la synthèse (haut de page)
3. Parcourir les 6 onglets

Temps: ~30 secondes
```

### CAS 2️⃣ : "Une livraison est incorrecte"
```
Actions:
1. Ouvrir livraison : detail_navigation.php?id=456
2. Vérifier les quantités (Onglet Lignes)
3. Cliquer "← Vente" pour contexte
4. Vérifier autres BL, litiges, stock

Temps: ~2 minutes
```

### CAS 3️⃣ : "Je dois traiter un litige"
```
Actions:
1. Ouvrir litige : litiges_navigation.php?id=789
2. Voir la vente (Tab Vente)
3. Voir les livraisons (Tab Livraisons)
4. Voir l'historique du produit (Tab Stock)
5. Documenter la solution

Temps: ~5 minutes
```

### CAS 4️⃣ : "Vérifier la cohérence"
```
Actions:
1. Ouvrir : verification_synchronisation.php
2. Voir tableau (50 ventes)
3. Chercher les ERREUR en rouge
4. Cliquer sur le numéro pour investiguer

Temps: ~3 minutes
```

---

## 5️⃣ QU'EST-CE QUI S'AFFICHE OÙ

### VENTE 360° : 6 ONGLETS
```
1. INFORMATIONS
   ├─ Détails vente (numéro, date, client)
   └─ Lignes de vente (produits)

2. ORDRES DE PRÉPARATION
   └─ Tous les ordres créés

3. LIVRAISONS
   └─ Tous les bons de livraison

4. RETOURS/LITIGES
   └─ Tous les litiges

5. STOCK MOUVEMENTS
   └─ Entrées/sorties stock

6. TRÉSORERIE & COMPTABILITÉ
   ├─ Encaissements
   └─ Écritures comptables
```

### LIVRAISON : 4 ONGLETS
```
1. LIGNES
   └─ Produits livrés (Qté Cmd vs Qté Liv)

2. ORDRES PRÉPARATION
   └─ Ordres qui ont alimenté livraison

3. RETOURS/LITIGES
   └─ Litiges de la vente associée

4. STOCK MOUVEMENTS
   └─ Sorties enregistrées
```

### LITIGE : 4 ONGLETS
```
1. INFORMATIONS
   ├─ Type problème
   ├─ Motif et solution
   └─ Impact financier

2. VENTE ASSOCIÉE
   ├─ Infos vente
   └─ Produits (avec surligné)

3. LIVRAISONS
   └─ Tous les BL de la vente

4. STOCK
   └─ Historique du produit
```

---

## 6️⃣ INDICATEURS CLÉS

### KPIs AFFICHÉS
```
✅ Montant TTC : Montant total de la vente
✅ Taux Livraison (%) : % du montant livré
✅ Taux Encaissement (%) : % du montant encaissé
✅ Nombre Litiges : Nombre de retours/problèmes
✅ Montant Litiges : Total impacté par litiges
✅ Synchronisation : ✅ OK ou ⚠️ ERREUR

Status ✅ OK = Tout est cohérent
Status ⚠️ ERREUR = Anomalie détectée, à investiguer
```

### VÉRIFICATIONS AUTOMATIQUES
```
✅ Check 1: Montants cohérents ?
   └─ Σ(Livraisons) ≈ Montant Vente (±100 FCFA)

✅ Check 2: Quantités cohérentes ?
   └─ Quantités Livrées ≤ Quantités Commandées

✅ Check 3: Stock correct ?
   └─ Sorties Stock = Quantités Livrées

✅ Check 4: Comptabilité complète ?
   └─ Écritures comptables existent

Si tous OK → Status ✅
Si un KO → Status ⚠️ + Détails du problème
```

---

## 7️⃣ HOTKEYS & RACCOURCIS

```
Depuis Vente 360°
├─ Onglet 1 : CTRL+1 → Informations
├─ Onglet 2 : CTRL+2 → Ordres
├─ Onglet 3 : CTRL+3 → Livraisons
├─ Onglet 4 : CTRL+4 → Litiges
├─ Onglet 5 : CTRL+5 → Stock
└─ Onglet 6 : CTRL+6 → Trésor

(À ajouter en phase 2)
```

---

## 8️⃣ COMMANDES RAPIDES

```
Accès Direct par Favoris :
1. Dashboard     : localhost/kms_app/coordination/dashboard.php
2. Vente 360°    : localhost/kms_app/ventes/detail_360.php?id=123
3. Vérif Sync    : localhost/kms_app/coordination/verification_synchronisation.php

Cherchez vente rapidement :
1. Aller à Vente 360°
2. Utiliser recherche navigateur (CTRL+F)
3. Taper numéro vente
```

---

## 9️⃣ ASTUCES & TIPS

### Astuce 1 : Voir l'historique complet
```
Vente 360° → Onglet Stock
└─ Voir TOUS les mouvements stock (entrées/sorties)
```

### Astuce 2 : Investiguer anomalies
```
Vérification Synchronisation → Cliquer sur ❌ ERREUR
└─ Voir exactement quel est le problème
```

### Astuce 3 : Documenter un litige
```
Litige Navigation → Tab Infos
└─ Remplir: Motif, Solution, Montants
```

### Astuce 4 : Tracer un produit
```
Litige Navigation → Tab Stock
└─ Voir tout l'historique du produit dans cette vente
```

### Astuce 5 : Vérifier la caisse
```
Vente 360° → Tab Trésorerie
└─ Voir tous les encaissements + écritures
```

---

## 🔟 RÉSOLUTION D'ERREURS RAPIDE

```
❌ "Quantités livrées ≠ Commandées"
   └─ Goto: Vente 360° → Tab Livraisons
      └─ Vérifier BL individuelles

❌ "Montants incohérents"
   └─ Goto: Vente 360° → Tab Stock
      └─ Vérifier les mouvements
   └─ Goto: Vente 360° → Tab Litiges
      └─ Vérifier les retours

❌ "Encaissement manquant"
   └─ Goto: Vente 360° → Tab Trésor
      └─ Chercher encaissement caisse

❌ "Stock incorrect"
   └─ Goto: Litige Navigation → Tab Stock
      └─ Voir historique du produit

❌ "Comptabilité manquante"
   └─ Goto: Vente 360° → Tab Trésor
      └─ Vérifier écritures comptables
```

---

## 📞 BESOIN D'AIDE ?

```
Utilisateur normal?
→ Lire: GUIDE_NAVIGATION_INTERCONNEXION.md

Développeur?
→ Lire: README_INTERCONNEXION.md

Besoin de commencer?
→ Lire: ACTIVATION_INTERCONNEXION.md

Besoin de naviguer?
→ Lire: INDEX_INTERCONNEXION.md

Perdu?
→ Lire: Ce fichier (QUICKSTART_VISUEL.md)
```

---

**🚀 Vous êtes prêt ! Commencez par le Dashboard coordination.php**

*Temps estimé pour maîtriser le système : 15 minutes*
