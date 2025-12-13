# CONTEXTE MÉTIER - KENNE MULTI-SERVICES (KMS)

**Date de mise à jour :** 13 décembre 2025

---

## 🏢 PRÉSENTATION

**Kenne Multi-Services (KMS)** est une **menuiserie professionnelle** située en Côte d'Ivoire.

L'application **KMS Gestion** est un système de gestion commerciale spécialisé pour le métier de la menuiserie.

---

## ✅ PÉRIMÈTRE MÉTIER AUTORISÉ

### 1. 🪵 Menuiserie Bois
- Panneaux contreplaqués (CTBX, Multiplex, Lattés)
- Panneaux MDF (Medium Density Fiberboard)
- Panneaux HDF (High Density Fiberboard)
- Bois brut et dérivés du bois
- Placages et feuilles de bois

### 2. ⚙️ Machines de Menuiserie
- Scies (scies à ruban, scies circulaires, scies sauteuses)
- Raboteuses et dégauchisseuses
- Toupies et défonceuses
- Décolleteurs et mortaiseuses
- Ponceuses et sableuses
- Perceuses et visseuses professionnelles
- Meuleuses

### 3. 🔩 Quincaillerie de Menuiserie
- Charnières (inox, soft-close, 2D, 3D)
- Glissières télescopiques pour tiroirs
- Poignées et boutons (aluminium, inox, laiton)
- Serrures et systèmes de fermeture
- Ferrures d'assemblage

### 4. 🍳 Électroménagers (Aménagement Cuisine)
- Fours encastrables
- Plaques de cuisson (vitrocéramique, induction, gaz)
- Hottes aspirantes
- Réfrigérateurs encastrables
- Lave-vaisselle encastrables
- Micro-ondes encastrables

### 5. 🛠️ Accessoires et Consommables
- Visserie spécialisée menuiserie
- Colles à bois professionnelles
- Vernis et finitions bois
- Lasures et saturateurs
- Cires et huiles pour bois
- Abrasifs et papiers de verre
- Mastics et joints

### 6. 📐 Accessoires de Pose
- Tasseaux et profils
- Cornières et angles
- Rails et systèmes de suspension
- Paumelles et charnières piano

---

## ❌ HORS PÉRIMÈTRE (INTERDIT)

Les catégories suivantes **NE FONT PAS PARTIE** du métier KMS et **NE DOIVENT JAMAIS** apparaître dans les données :

### ⚡ Électricité Générale
- ❌ Câbles électriques
- ❌ Disjoncteurs et tableaux électriques
- ❌ Prises et interrupteurs
- ❌ Luminaires (hors éclairage intégré meuble)

### 🚰 Plomberie
- ❌ Tubes PVC/PER
- ❌ Robinetterie (hors cuisine)
- ❌ Sanitaires (WC, lavabos)
- ❌ Chauffe-eau

### 🏗️ Construction BTP
- ❌ Ciment et béton
- ❌ Briques et parpaings
- ❌ Carrelage et faïence
- ❌ Peinture murale (hors finition bois)

### 🏡 Jardinage
- ❌ Outils de jardinage
- ❌ Produits phytosanitaires

---

## 📋 RÈGLES POUR LES DONNÉES

### Génération de Données
Lors de la création de données de test/démonstration :

1. ✅ **Toujours** utiliser des produits du périmètre autorisé
2. ✅ **Toujours** nommer les familles de manière cohérente avec la menuiserie
3. ✅ **Toujours** utiliser des codes produits parlants (`PAN-`, `MAC-`, `QUI-`, `ELM-`, `ACC-`)
4. ❌ **Jamais** inventer des catégories génériques
5. ❌ **Jamais** créer des produits hors menuiserie

### Exemples de Produits Valides ✅
```
PAN-CTBX18     → Panneau CTBX 18mm 1220x2440
MAC-SCIE210    → Scie à ruban 210W professionnelle
QUI-CHARN90    → Charnière inox 90° (paire)
ELM-FOUR       → Four encastrable inox 60cm
ACC-COLLE      → Colle bois pro 750ml
ACC-VIS430     → Vis noire 4x30mm (boîte 100)
```

### Exemples de Produits Interdits ❌
```
❌ CBL-001  → Câble électrique 2.5mm²
❌ DISJ-001 → Disjoncteur 16A
❌ TUY-001  → Tube PVC 110mm
❌ WC-001   → WC complet
❌ CIM-001  → Ciment 50kg
❌ CARR-001 → Carreau 40x40
```

---

## 🎯 FAMILLES DE PRODUITS STANDARDS

Les familles suivantes sont recommandées pour structurer le catalogue :

1. **Panneaux Bois**
2. **Machines Menuiserie**
3. **Quincaillerie**
4. **Électroménager**
5. **Accessoires**
6. **Bois Brut** (optionnel)
7. **Finitions & Vernis** (optionnel)

---

## 📝 CHECKLIST VALIDATION

Avant de générer ou d'intégrer des données, vérifier :

- [ ] Tous les produits sont-ils liés à la menuiserie ?
- [ ] Les familles sont-elles cohérentes avec le métier ?
- [ ] Les codes produits sont-ils parlants (`PAN-`, `MAC-`, etc.) ?
- [ ] Aucun produit d'électricité générale n'est présent ?
- [ ] Aucun produit de plomberie n'est présent ?
- [ ] Aucun produit de construction BTP n'est présent ?
- [ ] Les machines sont-elles des équipements de menuiserie ?
- [ ] Les accessoires sont-ils spécifiques au travail du bois ?

---

## 🔗 WORKFLOWS MÉTIER

### Vente de Meubles sur Mesure
```
Devis → Validation → Vente → Fabrication → Livraison → Encaissement
```

### Vente de Matériaux (Panneaux)
```
Vente directe → Livraison → Encaissement → Déstockage
```

### Vente de Machines
```
Devis → Négociation → Vente → Livraison → Formation client → Encaissement
```

---

## ⚠️ IMPORTANT

Ce contexte métier est **IMPÉRATIF** et doit être respecté dans :

- ✅ Génération de données de test
- ✅ Création de produits dans l'interface
- ✅ Exemples dans la documentation
- ✅ Rapports et statistiques
- ✅ Modules de démonstration

**KMS Gestion est une application métier spécialisée, pas un ERP générique.**

---

*Document de référence pour tous les développements et interventions sur KMS Gestion*
