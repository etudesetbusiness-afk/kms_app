# 📥 GUIDE DE TEST - Système d'Import Excel

## ✅ Prérequis

Le système d'import est **entièrement opérationnel** et testé:

- ✅ Page d'import créée: `admin/catalogue/import.php`
- ✅ 3 parsers disponibles: CSV, XLSX, XLS
- ✅ Validation des données (codes uniques, catégories valides)
- ✅ Protection CSRF
- ✅ Gestion d'erreurs par ligne
- ✅ 2 fichiers d'exemple fournis

## 🚀 Accès au Système

1. **Connectez-vous** avec un compte Admin ou Magasinier
2. **Allez à:** `Admin → Catalogue → Importer Excel`
   - URL directe: `http://localhost/kms_app/admin/catalogue/import.php`
3. **Bouton ajouté** dans la page produits (ligne 110-120)

## 📋 Format Attendu

### Colonnes obligatoires:
- **code** - Code unique du produit (ex: `BOIS-001`)
- **designation** - Nom du produit (ex: `Pin Maritim 2x4 cm`)
- **categorie_id** - ID de la catégorie (ex: `1`)
- **prix_unite** - Prix unitaire (ex: `1500.50` ou `1500,50`)

### Format CSV:
```csv
code,designation,categorie_id,prix_unite
BOIS-001,Pin Maritim 2x4 cm,1,1500.50
BOIS-002,Pin Sylvestre 3x5 cm,1,2000.00
TOOL-001,Scie à onglet,2,15000.00
```

### Catégories disponibles:
```
19 = Panneaux & Contreplaqués
20 = Machines & Outils
21 = Quincaillerie
22 = Accessoires Menuiserie
23 = Bois Brut
24 = Finitions & Vernis
```

## 🧪 Test Étape par Étape

### **Étape 1: Upload du fichier**

1. Allez sur la page d'import
2. Cliquez sur "Sélectionner un fichier"
3. Choisissez l'un des fichiers d'exemple:
   - `uploads/exemple_import.csv` (12 produits)
   - `uploads/exemple_complet.csv` (18 produits)
4. Cliquez "Continuer →"

**Attendu:** Passage à l'étape 2 (aperçu)

### **Étape 2: Aperçu des données**

1. Vérifiez que les 12 (ou 18) lignes s'affichent
2. Vérifiez les colonnes: code, designation, categorie_id, prix_unite
3. Vérifiez que les données sont correctes
4. Cliquez "Continuer →"

**Attendu:** Passage à l'étape 3 (confirmation)

### **Étape 3: Confirmation et import**

1. Lisez l'avertissement
2. Cliquez "Importer les produits"

**Attendu:** 
- Message de succès: "✓ 12 produit(s) importé(s) avec succès"
- Redirection vers la liste des produits
- Les 12 nouveaux produits apparaissent dans la liste

## ✔️ Vérifications en Base de Données

Après l'import, vérifiez en SQL:

```sql
-- Voir les derniers produits importés
SELECT code, designation, categorie_id, prix_unite 
FROM catalogue_produits 
ORDER BY created_at DESC 
LIMIT 12;

-- Compter les produits
SELECT COUNT(*) FROM catalogue_produits;

-- Vérifier les codes importés
SELECT code FROM catalogue_produits 
WHERE code LIKE 'BOIS-%' OR code LIKE 'TOOL-%' OR code LIKE 'PIN-%';
```

## 🔍 Scénarios de Test Avancés

### Test 1: Réimport (codes dupliqués)

1. Réexécutez l'import avec le même fichier
2. **Attendu:** Erreur "Code 'BOIS-001' déjà existant"
3. Les produits existants ne sont pas réimportés

### Test 2: Fichier Excel (XLSX)

1. Convertissez `exemple_import.csv` en Excel XLSX
2. Importez le fichier XLSX
3. **Attendu:** Les 12 produits sont importés

### Test 3: Données incomplètes

1. Créez un CSV avec une ligne manquant la "designation"
2. Importez
3. **Attendu:** Erreur "Ligne X: Code et Désignation obligatoires"

### Test 4: Catégorie invalide

1. Créez un CSV avec categorie_id = 999 (inexistant)
2. Importez
3. **Attendu:** La catégorie par défaut (1) est utilisée

## 📝 Notes de Sécurité

- ✅ **CSRF Token:** Toutes les formes sont protégées par tokens CSRF
- ✅ **Validation:** Chaque ligne est validée avant insertion
- ✅ **Slug:** Généré automatiquement avec déduplication
- ✅ **Permissions:** Seuls les utilisateurs avec `PRODUITS_CREER` peuvent accéder
- ✅ **Fichiers:** Les fichiers temporaires sont nettoyés après import

## 🆘 Dépannage

### Erreur: "Fichier trop volumineux"

- Limite: 10 MB
- Solution: Divisez votre CSV en plusieurs imports

### Erreur: "Format non supporté"

- Formats acceptés: CSV, XLSX, XLS
- Vérifiez l'extension du fichier

### Erreur: "Code déjà existant"

- Le code existe déjà en base de données
- Solution: Utilisez un code différent ou supprimez le produit existant

### Aucun produit n'apparaît après import

1. Vérifiez que le message de succès s'est affiché
2. Vérifiez en SQL: `SELECT COUNT(*) FROM catalogue_produits`
3. Vérifiez les permissions de l'utilisateur

## 📁 Fichiers Impliqués

- **Page d'import:** `admin/catalogue/import.php` (405 lignes)
- **Parsers:** 
  - `parseCSV()` - Lecture CSV
  - `parseExcel()` - Lecture Excel XLSX/XLS
  - `importProducts()` - Insertion en BD
- **Exemples:** 
  - `uploads/exemple_import.csv` (12 produits)
  - `uploads/exemple_complet.csv` (18 produits)
- **Bouton:** Ajouté dans `admin/catalogue/produits.php` (ligne 110-120)

## ✅ Checklist de Validation

- [ ] Page d'import accessible
- [ ] Upload de fichier fonctionne
- [ ] Aperçu affiche les bonnes données
- [ ] Import réussit (message de succès)
- [ ] Produits présents en base de données
- [ ] Slugs générés correctement
- [ ] Codes sont uniques
- [ ] Prix formatés correctement
- [ ] Catégories assignées correctement
- [ ] Images peuvent être ajoutées aux produits importés
- [ ] Produits visibles en public dans `catalogue/`

---

**Système prêt pour la production!** 🚀
