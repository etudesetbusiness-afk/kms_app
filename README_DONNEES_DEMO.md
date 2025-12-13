# Générateur de Données de Démonstration - KMS Gestion

## 📋 Vue d'ensemble

Ce générateur crée des **données cohérentes et interconnectées** pour tester tous les workflows de l'application **KMS Gestion** (menuiserie professionnelle).

**Important :** Toutes les données respectent strictement le contexte métier de **Kenne Multi-Services** :
- ✅ **Menuiserie bois** (panneaux, contreplaqués, MDF)
- ✅ **Machines de menuiserie** (scies, raboteuses, toupies)
- ✅ **Quincaillerie menuiserie** (charnières, glissières, poignées)
- ✅ **Électroménagers** (pour aménagement de cuisine)
- ✅ **Accessoires menuiserie** (vis, colle, vernis)

❌ **Aucune donnée hors contexte** (pas d'électricité, plomberie, construction générale)

---

## 📦 Modules couverts

- **Clients & Prospects** (showroom, terrain, digital)
- **Produits & Stock** (familles, mouvements, ruptures)
- **Devis & Ventes** (acceptés, refusés, convertis)
- **Livraisons** (complètes et partielles)
- **Encaissements** (espèces, mobile money, virement)
- **Stock** (entrées, sorties, cohérence garantie)

---

## 🚀 Utilisation

### 1. Générer des données de démonstration

```bash
php generer_donnees_demo_final.php
```

**Ce script crée :**
- ✅ 30 clients réalistes (noms ivoiriens, téléphones, emails)
- ✅ 15 produits de **menuiserie professionnelle** en 5 familles :
   - **Panneaux Bois** (CTBX, MDF, Multiplex)
   - **Machines** (scies, raboteuses, toupie)
   - **Quincaillerie** (charnières, glissières, poignées)
   - **Électroménager** (four, plaque pour cuisines)
   - **Accessoires** (vis, colle bois, vernis)
- ✅ 25 devis (50% acceptés, 50% en attente)
- ✅ 30 ventes (dont ventes issues de devis + ventes directes)
- ✅ ~20 livraisons avec déstockage automatique
- ✅ ~17 encaissements en caisse

**Période couverte :** 60 derniers jours

---

### 2. Nettoyer les données avant régénération

```bash
php nettoyer_donnees_demo.php
```

**Ce script supprime :**
- Encaissements caisse
- Bons de livraison et leurs lignes
- Ventes et lignes de ventes
- Devis et lignes de devis
- Mouvements de stock
- Achats
- Produits de menuiserie créés par le générateur (codes : `PAN-`, `MAC-`, `QUI-`, `ELM-`, `ACC-`)
- Clients démo (emails en `@email.ci`)

⚠️ **Attention :** Compteur de 3 secondes avant suppression pour éviter les erreurs.

---

## ✅ Validation & Cohérence

Le générateur vérifie automatiquement :

1. **Aucun stock négatif** : tous les mouvements de stock sont cohérents
2. **Toutes les ventes ont un montant** : pas de vente à 0€
3. **Workflow complet** : 
   - Devis → Vente → Livraison → Stock → Caisse
   - Traçabilité complète entre modules

---

## 📊 Données générées (exemple)

```
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
RÉSUMÉ GÉNÉRATION
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Clients             :   30
Produits            :   13
Devis               :   25
Ventes              :   30
Livraisons          :   20
Encaissements       :   17
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
```

---

## 🔄 Workflows testables

Après génération, vous pouvez tester :

### Module Ventes
- Navigation devis → ventes
- Création vente directe (sans devis)
- Suivi statuts (EN_ATTENTE_LIVRAISON, LIVREE, PARTIELLEMENT_LIVREE)

### Module Stock
- Consultation mouvements (entrées/sorties)
- Vérification stocks actuels
- Détection ruptures (stock < seuil_alerte)

### Module Livraisons
- Création bon de livraison depuis vente
- Livraisons complètes et partielles
- Impact automatique sur stock

### Module Caisse
- Consultation encaissements
- Répartition par mode de paiement
- Lien avec ventes

### Coordination
- Vue globale : devis → vente → livraison → stock → caisse
- Traçabilité complète des opérations

---

## 📁 Structure des fichiers

```
kms_app/
├── generer_donnees_demo_final.php    # Générateur principal
├── nettoyer_donnees_demo.php         # Script de nettoyage
├── README_DONNEES_DEMO.md            # Cette documentation
└── lib/
    ├── stock.php                      # Gestion mouvements stock
    └── caisse.php                     # Gestion journal caisse
```

---

## ⚙️ Configuration technique

### Dépendances
- PHP 8.0+
- MySQL/MariaDB
- Extensions : PDO, pdo_mysql

### Tables concernées
- `clients`, `types_client`
- `familles_produits`, `produits`
- `devis`, `devis_lignes`
- `ventes`, `ventes_lignes`
- `bons_livraison`, `bons_livraison_lignes`
- `stocks_mouvements`
- `caisse_journal`

### Contraintes respectées
- Clés étrangères (FK) toutes respectées
- Intégrité référentielle garantie
- Transactions ACID (rollback en cas d'erreur)

---

## 🐛 Dépannage

### Erreur : "Duplicate entry for key 'code_produit'"
**Cause :** Produits déjà présents dans la base  
**Solution :** Lancer `nettoyer_donnees_demo.php` avant de régénérer

### Erreur : "Column not found"
**Cause :** Structure de base non synchronisée avec le générateur  
**Solution :** Vérifier que `kms_gestion.sql` est bien importé

### Stocks négatifs détectés
**Cause :** Quantités livrées > stock disponible  
**Solution :** Le générateur ajuste automatiquement, mais si le problème persiste, nettoyer et régénérer

---

## 📌 Notes importantes

1. **Période réaliste** : Les dates sont générées sur 60 jours glissants pour simuler une activité récente

2. **Probabilités réalistes** :
   - 50% des devis sont acceptés
   - 70% des ventes sont livrées
   - 70% des livraisons sont encaissées

3. **Stock cohérent** : 
   - Entrée initiale = stock_actuel du produit
   - Chaque livraison décrémente le stock via `stock_enregistrer_mouvement()`

4. **Codes uniques** :
   - Devis : `DEV-YYYYMMDD-001`
   - Ventes : `VTE-YYYYMMDD-001`
   - Livraisons : `BL-YYYYMMDD-001`

---

## 🎯 Objectif final

**Disposer d'une base de données de démonstration fiable** reflétant une activité réelle de Kenne Multi-Services, permettant de :

✅ Valider la robustesse de l'application  
✅ Tester les workflows de bout en bout  
✅ Détecter les anomalies fonctionnelles ou techniques  
✅ Former les utilisateurs sur données réalistes  
✅ Démontrer l'application aux clients/investisseurs  

---

**Date de création :** 2025-12-13  
**Version :** 1.0  
**Auteur :** KMS Development Team
