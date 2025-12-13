# Guide de Navigation - Système de Ventes Interconnecté

## Vue d'ensemble du parcours utilisateur

```
VENTE
  ↓
  ├─→ Détails (320 avec tous les éléments liés)
  │
  ├─→ Ordres de Préparation
  │    ↓
  │    └─→ Statut de préparation
  │         └─→ Lien vers livraisons
  │
  ├─→ Bons de Livraison
  │    ↓
  │    ├─→ Quantités livrées
  │    ├─→ Mouvements stock
  │    └─→ Litiges relatifs
  │
  ├─→ Retours & Litiges
  │    ↓
  │    ├─→ Remboursements
  │    ├─→ Avoirs
  │    └─→ Impacta stock
  │
  ├─→ Encaissements
  │    ↓
  │    └─→ Modes de paiement
  │
  └─→ Comptabilité
       ↓
       └─→ Écritures générées
```

## Pages Principales

### 1. **Détail Vente 360° - `/ventes/detail_360.php?id=ID`**
Vue unifiée de TOUTE la vente avec ses 6 onglets.

**Données affichées :**
- Informations générales (numéro, date, client, commercial)
- Montants (HT, TTC, encaissé, retours)
- Synthèse : Statut livraison, taux encaissement, nombre de litiges
- **Onglet 1 : Informations**
  - Lignes de vente complètes
  - Liens vers client et produits
  
- **Onglet 2 : Ordres de Préparation**
  - Tous les ordres liés
  - Lien vers détail de chaque ordre
  
- **Onglet 3 : Livraisons**
  - Tous les bons de livraison
  - Quantités livrées vs commandées
  - Lien vers détail livraison
  
- **Onglet 4 : Retours & Litiges**
  - Tableau de tous les litiges
  - Types et statuts
  - Montants de remboursement/avoir
  - Lien vers détail litige
  
- **Onglet 5 : Mouvements Stock**
  - Historique complet des sorties stock
  - Quantités et raisons
  
- **Onglet 6 : Trésorerie & Comptabilité**
  - Encaissements avec modes de paiement
  - Écritures comptables générées
  
**Actions disponibles :**
- Retour à la liste
- Modifier la vente
- Créer nouvel ordre de préparation

---

### 2. **Détail Livraison avec Navigation - `/livraisons/detail_navigation.php?id=ID`**
Détail du bon de livraison avec lien bidirectionnel vers la vente.

**Données affichées :**
- Informations livraison (numéro, date, livreur, statut)
- **Onglet 1 : Lignes**
  - Quantités commandées vs livrées
  - Détection des surlivraisons
  
- **Onglet 2 : Ordres de Préparation**
  - Les ordres qui ont alimenté cette livraison
  
- **Onglet 3 : Retours & Litiges**
  - Les litiges de cette vente
  - Montants impacts
  
- **Onglet 4 : Mouvements Stock**
  - Les sorties enregistrées à cette date
  
**Navigation :**
- Bouton en haut à droite : **Retour vers la Vente 360°**
- Bouton en bas : Vente complète

---

### 3. **Détail Litige avec Navigation - `/coordination/litiges_navigation.php?id=ID`**
Gestion complète du litige avec accès à la vente, livraisons et impact stock.

**Données affichées :**
- Informations litige (date, client, type, statut)
- Détails du problème et solution
- Impact financier (remboursement + avoir)
- **Onglet 1 : Informations**
  - Motif et solution
  - Responsable suivi
  - Montants (remboursement, avoir, total impact)
  
- **Onglet 2 : Vente Associée** (si existe)
  - Infos complètes de la vente
  - Liste des produits de la vente (produit du litige surligné)
  
- **Onglet 3 : Livraisons** (si existe vente)
  - Tous les bons de livraison de cette vente
  
- **Onglet 4 : Stock** (si existe vente)
  - Historique des mouvements pour ce produit
  
**Navigation :**
- Bouton en haut à droite : **Lien vers la Vente 360°**
- Bouton en bas : Retour vente + accès à la liste

---

## Vérification de Synchronisation

### Page : `/coordination/verification_synchronisation.php`
Audit complet des cohérences entre ventes, stock, caisse et comptabilité.

**Vérifications automatiques :**
1. ✅ Montant livraisons = Montant vente
2. ✅ Quantités livrées ≤ Commandées
3. ✅ Sorties stock = Quantités livrées
4. ✅ Écritures comptables créées

**Affichage :**
- Tableau des 50 dernières ventes
- Status OK ou ERREUR avec détails des problèmes
- KPIs : Nombre de ventes OK, anomalies à traiter, total encaissé, total commandé
- Clic sur le numéro = Accès au détail 360°

**Actions recommandées :**
- Audit mensuel
- Vérification avant clôture de période
- Investigation des ventes en ERREUR

---

## Schéma de Synchronisation Validée

```
VENTE
  ├─ Montant TTC = Σ(Lignes * PU)
  │
  └─ LIVRAISON(s)
      ├─ Montant TTC = Montant Vente (tolérance 100 FCFA)
      │
      ├─ Lignes
      │  └─ Qté Livrée ≤ Qté Commandée
      │
      └─ MOUVEMENTS STOCK
          ├─ Type = SORTIE
          ├─ Qté Sortie = Qté Livrée
          └─ Raison = "Livraison BL #XXX"

LITIGE(s)
  ├─ Type = DEFAUT_PRODUIT | ERREUR_LIVRAISON | INSATISFACTION_CLIENT | AUTRE
  ├─ Montant Impact = Remboursement + Avoir
  │
  └─ MOUVEMENTS STOCK (retour)
      ├─ Type = ENTREE (si produit retourné)
      └─ Raison = "Retour litige #XXX"

CAISSE
  ├─ Encaissements = Somme des paiements vente
  └─ Remboursements = Montant remboursé litiges

COMPTABILITÉ
  └─ Écritures = Vente + Livraisons + Retours + Paiements
```

---

## Cas d'usage courants

### 1️⃣ "J'ai une vente, je veux voir son statut complet"
→ Accéder à : **`ventes/detail_360.php?id=ID`**
- Voir tous les ordres de préparation
- Voir toutes les livraisons
- Voir tous les litiges/retours
- Voir tous les encaissements
- Vérifier la comptabilité

### 2️⃣ "Une livraison a un problème, je veux tracer d'où il vient"
→ Accéder à : **`livraisons/detail_navigation.php?id=ID`**
- Voir l'ordre de préparation initial
- Vérifier les quantités
- Cliquer **Vente** pour voir le contexte global
- Consulter les litiges associés
- Vérifier les sorties stock

### 3️⃣ "Je dois résoudre un litige/retour"
→ Accéder à : **`coordination/litiges_navigation.php?id=ID`**
- Voir le produit exactement
- Voir la vente concernée
- Voir la livraison
- Voir l'historique stock du produit
- Noter la solution et les montants

### 4️⃣ "Je veux vérifier la cohérence global du système"
→ Accéder à : **`coordination/verification_synchronisation.php`**
- Lancer l'audit automatique
- Identifier les ventes avec problèmes
- Cliquer sur une vente problématique → Voir le détail complet

---

## Points de synchronisation clés à vérifier

| Point | Où vérifier | Réconciliation |
|-------|------------|---|
| Montants | Onglet 1 : Synthèse + Montants | TTC Vente = TTC Livraison |
| Quantités | Onglet 3 : Livraisons | Σ(Livraisons) ≤ Σ(Commande) |
| Stock | Onglet 5 : Mouvements | Sorties = Livrées |
| Caisse | Onglet 6 : Encaissements | Total Encaissé vs TTC |
| Comptabilité | Onglet 6 : Écritures | Nombre d'écritures > 0 |
| Litiges | Onglet 4 : Retours | Impact financier tracé |

---

## URLs rapides pour accès directs

```php
// Détail vente (360°)
url_for('ventes/detail_360.php?id=123')

// Détail livraison
url_for('livraisons/detail_navigation.php?id=456')

// Détail litige
url_for('coordination/litiges_navigation.php?id=789')

// Vérification globale
url_for('coordination/verification_synchronisation.php')
```

---

## Maintenance & Troubleshooting

### Problème : "Quantités livrées ≠ Commandées"
**Diagnostic :**
1. Accéder à la vente (360°)
2. Onglet Livraisons : Vérifier les quantités
3. Onglet Stock : Vérifier les mouvements
4. → Probable : Livraison partielle ou erreur de saisie

**Solution :**
- Modifier la livraison
- Créer une BL supplémentaire
- Documenter le motif

### Problème : "Encaissement manquant"
**Diagnostic :**
1. Accéder à la vente (360°)
2. Onglet Trésorerie : Vérifier encaissements
3. Vérifier mode de paiement
4. → Probable : Paiement non saisi

**Solution :**
- Créer l'encaissement dans caisse
- Vérifier la source (chèque, espèces, virement)
- Réconcilier

### Problème : "Litige sans lien vers vente"
**Diagnostic :**
1. Accéder au litige
2. Vérifier colonne "vente_id"
3. → Probable : Litige créé sans vente

**Solution :**
- Lier manuellement à la vente
- Ou créer un nouveau litige correctement

---

## Permissions requises

Toutes ces pages nécessitent :
- Connexion utilisateur
- Permission `VENTES_LIRE` minimum
- Pour modifications : permissions spécifiques au module (VENTES_MODIFIER, etc.)

---

## Version et dernière mise à jour
- **Version** : 1.0 - Système interconnecté complet
- **Créé** : [Date]
- **Dernière MAJ** : [Date]
