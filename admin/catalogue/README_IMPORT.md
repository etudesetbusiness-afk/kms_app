# 📥 Import Produits Catalogue - Documentation Technique

## 📌 Vue d'ensemble

Fonctionnalité permettant d'importer en masse des produits depuis un fichier CSV ou Excel. Cette fonctionnalité élimine la saisie manuelle répétitive.

## 📁 Fichiers

### Frontend/Pages

| Fichier | Rôle |
|---------|------|
| `admin/catalogue/import.php` | Page d'import avec étapes 1-3 |

### Données d'exemple

| Fichier | Description |
|---------|-------------|
| `uploads/exemple_import.csv` | CSV simple avec 12 produits |
| `uploads/exemple_complet.csv` | CSV détaillé avec descriptions |

### Documentation

| Fichier | Contenu |
|---------|---------|
| `GUIDE_IMPORT_CATALOGUE.md` | Guide utilisateur |
| `admin/catalogue/README_IMPORT.md` | Documentation technique |

## 🎯 Fonctionnalités

### Étape 1: Upload
- Accepte CSV, XLSX, XLS
- Max 10 MB
- Validation du format

### Étape 2: Aperçu
- Affiche les 10 premières lignes
- Montre les erreurs détectées
- Compte total des lignes

### Étape 3: Import
- Insertion en base de données
- Gestion des doublons
- Messages d'erreur par ligne
- Génération automatique de slugs

## 📊 Format du fichier

### Colonnes obligatoires

```
code          | designation           | categorie_id | prix_unite
CODE-001      | Produit exemple       | 1            | 1500.50
CODE-002      | Autre produit         | 2            | 2000.00
```

### Notes importantes

- **code:** Unique, max 50 caractères
- **designation:** Nom du produit, max 255 caractères
- **categorie_id:** ID valide de catégorie (par défaut 1)
- **prix_unite:** Format numérique (1500.50 ou 1500,50)

## 🔧 Parsers supportés

### CSV
```php
$data = parseCSV($filepath);
```
- Délimiteur: virgule `,`
- Encodage: UTF-8 recommandé
- Headers: première ligne

### Excel (XLSX)
```php
$data = parseExcel($filepath);
```
- Lit la feuille 1 (sheet1.xml)
- Compatible ZipArchive
- Headers: première ligne

### Excel (XLS)
```php
$data = parseExcel($filepath);
```
- Format ancien Excel
- Même traitement que XLSX
- Converti en ZIP/XML

## 🛡️ Validations

### Fichier
- ✅ Extension: CSV, XLSX, XLS
- ✅ Taille: < 10 MB
- ✅ Parsable: format valide

### Données
- ✅ Code obligatoire
- ✅ Designation obligatoire
- ✅ Code unique (doublons ignorés)
- ✅ Slug unique (suffixe auto si collision)
- ✅ Catégorie valide (par défaut 1)

### Insertion
- ✅ PDO prepared statements (sécurité)
- ✅ Gestion des erreurs par ligne
- ✅ Transaction-less (chaque ligne indépendante)

## 🔐 Sécurité

### Authentification
- Requiert connexion utilisateur
- Permission `PRODUITS_CREER` obligatoire

### CSRF
- Pas de vérification CSRF (GET/POST combinés)
- Session temporaire pour le fichier

### SQL Injection
- Prepared statements pour toutes les requêtes
- Pas de concaténation de variables

### Upload
- Fichier stocké en temp directory
- Supprimé après import
- Pas d'accès public

## 📝 Flux de données

```
1. Upload fichier
   └─> Validation extension/taille
   └─> Sauvegarde temporaire
   └─> Redirection étape 2

2. Aperçu données
   └─> Parsing fichier (CSV ou Excel)
   └─> Affichage premières lignes
   └─> Redirection étape 3

3. Import
   └─> Parsing à nouveau
   └─> Validation chaque ligne
   └─> Insertion en BD
   └─> Rapport résultat
   └─> Suppression fichier temp
```

## 🐛 Gestion d'erreurs

### Niveau fichier
```
"Veuillez sélectionner un fichier valide"
"Format non supporté. Utilisez CSV, XLSX ou XLS"
"Fichier trop volumineux (max 10 MB)"
"Erreur lors de l'upload du fichier"
```

### Niveau donnée
```
"Ligne 5: Code et Désignation obligatoires"
"Ligne 7: Code 'CODE-001' déjà existant"
"Ligne 9: Erreur lors de l'insertion"
```

## 🚀 Utilisation

### Via UI
1. Accédez à **Admin → Catalogue → Produits**
2. Cliquez **Importer Excel**
3. Suivez les 3 étapes

### Via API (future)
```php
$result = importProducts($rows, $pdo);
// $result = ['success' => true, 'count' => 10, 'errors' => []]
```

## 📈 Limitations connues

❌ Pas d'import d'images  
❌ Pas d'import de catégories  
❌ Pas de mise à jour (insert only)  
❌ Pas de transactions (rollback if error)  
❌ Pas de prix en gros  
❌ Pas de caractéristiques  

## ✅ À faire dans le futur

- [ ] Support de la mise à jour (update if code exists)
- [ ] Import d'images (ZIP avec images)
- [ ] Export de produits (inverse)
- [ ] Batch import de catégories
- [ ] Transactions avec rollback
- [ ] Historique d'imports
- [ ] Drags & drops de fichiers
- [ ] Aperçu en temps réel

## 🧪 Tests

### Test CSV
```bash
php test_import_csv.php
```

### Test page
```bash
php test_import_page.php
```

## 📞 Support

Pour toute question:
- Consultez `GUIDE_IMPORT_CATALOGUE.md` (utilisateur)
- Vérifiez le format CSV dans les exemples
- Testez avec `exemple_complet.csv`

---

**Version:** 1.0  
**Date:** Décembre 2025  
**Status:** Production ready
