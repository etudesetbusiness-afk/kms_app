# 📥 Guide Import Excel/CSV - Module Catalogue

## Vue d'ensemble

La fonctionnalité d'import permet d'ajouter rapidement **plusieurs produits** à la fois depuis un fichier Excel ou CSV.

## Accès

1. Allez dans **Gestion Catalogue → Produits**
2. Cliquez sur le bouton **Importer Excel**

## Format attendu

### Colonnes obligatoires

| Colonne | Type | Description | Exemple |
|---------|------|-------------|---------|
| **code** | Texte | Code unique du produit | `CODE-001` |
| **designation** | Texte | Nom du produit | `Panneau MDF 25mm` |
| **categorie_id** | Nombre | ID de la catégorie | `1` |
| **prix_unite** | Nombre | Prix à l'unité (FCFA) | `1500.50` |

### Exemple CSV

```csv
code,designation,categorie_id,prix_unite
BOIS-PIN-1,Pin Maritim 2x4 cm,1,450.50
BOIS-CHENE-1,Chêne massif 4x4 cm,1,1250.00
VERNIS-BRILLANT-1,Vernis Polyuréthane Brillant 1L,2,8500.00
```

## Formats supportés

✅ **CSV** (Excel enregistré en CSV)  
✅ **XLSX** (Excel 2007+)  
✅ **XLS** (Excel 97-2003)

## Processus d'import

### Étape 1: Sélectionner un fichier
- Cliquez sur "Choisir un fichier"
- Sélectionnez votre fichier (CSV, XLSX ou XLS)
- Taille max: **10 MB**
- Cliquez sur "Continuer →"

### Étape 2: Aperçu des données
- Les premières 10 lignes sont affichées
- Vérifiez que les données sont correctes
- Les erreurs de format sont affichées ici
- Cliquez sur "Continuer →" pour procéder

### Étape 3: Confirmation
- Vérifiez les détails
- Cliquez sur "Importer les produits"

## Validations

Le système vérifie automatiquement:

✅ Format du fichier  
✅ Présence des colonnes obligatoires  
✅ Unicité du code (doublons ignorés)  
✅ Unicité du slug (suffixe ajouté si nécessaire)  
✅ Catégorie valide  

## En cas d'erreur

Les erreurs sont affichées avec le numéro de ligne:

```
❌ Ligne 5: Code 'BOIS-001' déjà existant
❌ Ligne 7: Code et Désignation obligatoires
```

Les lignes valides sont importées même si d'autres échouent.

## Conseils d'utilisation

1. **Préparer dans Excel:**
   - Créez les colonnes: code, designation, categorie_id, prix_unite
   - Une ligne = un produit
   - Les colonnes supplémentaires sont ignorées

2. **Vérifier les codes:**
   - Les codes doivent être uniques
   - Les codes existants seront ignorés (pas remplacés)

3. **Catégories:**
   - Les ID de catégories doivent exister
   - Par défaut, catégorie 1 est utilisée si vide

4. **Prix:**
   - Format: nombre décimal (ex: 1500.50 ou 1500,50)
   - Obligatoire pour chaque produit

## Exemple complet

### 1. Fichier CSV (exemple_produits.csv)

```
code,designation,categorie_id,prix_unite
PANNEAUX-MDF-25,Panneau MDF 25 mm,1,5500.00
PANNEAUX-MDF-16,Panneau MDF 16 mm,1,3800.00
PANNEAUX-CTBX-18,Panneau Contreplaqué 18 mm,1,6200.00
VERNIS-BRILLANT-1L,Vernis Polyuréthane Brillant 1L,2,8500.00
PEINTURE-BLANC-2L,Peinture Acrylique Blanc 2L,3,5500.00
COLLE-BOIS-500ML,Colle à bois 500ml,4,2000.00
PERCEUSE-20V,Perceuse à percussion 20V,5,45000.00
```

### 2. Importer
- Accédez à **Catalogue → Importer Excel**
- Upload le fichier CSV
- Vérifiez l'aperçu (7 produits détectés)
- Confirmez l'import

### 3. Résultat
- ✅ 7 produits créés
- Accessibles dans la liste Produits
- Disponibles immédiatement dans le catalogue public

## Après l'import

Les produits importés:
- ✅ Ont un slug généré automatiquement
- ✅ Sont actifs par défaut
- ✅ Peuvent être modifiés individuellement
- ✅ Acceptent des images après création
- ✅ Apparaissent dans le catalogue public

Pour ajouter des images:
1. Allez dans **Modifier** le produit
2. Téléchargez l'image principale
3. Ajoutez des images de galerie si besoin
4. Sauvegardez

## Dépannage

### "Fichier trop volumineux"
- Limitez à max 10 MB
- Supprimez les colonnes inutiles
- Compressez le fichier

### "Code déjà existant"
- Modifiez les codes dans le fichier
- Les lignes restantes seront importées
- Vérifiez les codes avant import

### "Categorie invalide"
- Vérifiez que l'ID de catégorie existe
- Allez dans **Catégories** pour voir les IDs
- Par défaut, catégorie 1 est utilisée

## Limitations actuelles

⚠️ L'import ne supporte pas encore:
- Les images (à ajouter après)
- Les caractéristiques spéciales
- Les galeries d'images
- Les prix en gros

Ces éléments peuvent être ajoutés manuellement après import.

---

**Version:** 1.0  
**Date:** Décembre 2025  
**Support:** Admin catalogue
