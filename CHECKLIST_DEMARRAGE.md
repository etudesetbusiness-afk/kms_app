╔════════════════════════════════════════════════════════════════════════╗
║                 CHECKLIST DE DÉMARRAGE - IMPORT EXCEL                   ║
║                    Validez que tout fonctionne                          ║
╚════════════════════════════════════════════════════════════════════════╝


## ✅ CHECKLIST DE MISE EN PLACE

Avant de commencer à importer, vérifiez:

- [ ] Page d'import accessible
  URL: http://localhost/kms_app/admin/catalogue/import.php
  
- [ ] Vous êtes connecté en tant qu'Admin ou Magasinier
  Permission requise: PRODUITS_CREER

- [ ] Bouton "Importer Excel" visible dans Admin → Catalogue

- [ ] Fichiers d'exemple disponibles:
  - [ ] uploads/exemple_import.csv (12 produits)
  - [ ] uploads/exemple_complet.csv (18 produits)


## 🧪 CHECKLIST DE TEST - 1ER IMPORT

### Préparation
- [ ] Ouvrez page d'import
- [ ] Fichier prêt (CSV, XLSX ou XLS)

### Étape 1: Upload
- [ ] Cliquez "Sélectionner un fichier"
- [ ] Choisissez uploads/exemple_import.csv
- [ ] Cliquez "Continuer →"

### Étape 2: Aperçu
- [ ] 12 lignes affichées
- [ ] Colonnes: code, designation, categorie_id, prix_unite
- [ ] Données correctes
- [ ] Cliquez "Continuer →"

### Étape 3: Confirmation
- [ ] Lisez l'avertissement
- [ ] Cliquez "Importer les produits"

### Résultat
- [ ] Message: "✓ 12 produit(s) importé(s) avec succès"
- [ ] Redirection vers liste produits
- [ ] 12 nouveaux produits visibles


## 🔍 CHECKLIST DE VÉRIFICATION BD

Après l'import, vérifiez en base de données:

```sql
-- 1. Vérifier les nouveaux produits
SELECT code, designation, categorie_id, prix_unite 
FROM catalogue_produits 
WHERE code LIKE 'BOIS-%' OR code LIKE 'TOOL-%' OR code LIKE 'PIN-%'
ORDER BY created_at DESC 
LIMIT 12;

-- 2. Compter les produits
SELECT COUNT(*) as total FROM catalogue_produits;

-- 3. Vérifier les slugs
SELECT code, slug FROM catalogue_produits 
WHERE code LIKE 'BOIS-%' OR code LIKE 'TOOL-%' OR code LIKE 'PIN-%'
ORDER BY created_at DESC 
LIMIT 12;
```

**Vérifications:**
- [ ] 12 produits retournés
- [ ] Codes correctes (BOIS-*, TOOL-*, PIN-*)
- [ ] Slugs uniques et générés correctement
- [ ] Prix formatés correctement
- [ ] Catégories assignées


## 📝 CHECKLIST AVANCÉE

### Test: Réimport (codes dupliqués)
- [ ] Exécutez l'import 2 fois avec même fichier
- [ ] Résultat attendu: Erreur "Code X déjà existant"
- [ ] Vérifiez que les doublons ne sont pas créés

### Test: Fichier Excel (XLSX)
- [ ] Convertissez CSV en Excel
- [ ] Importez le fichier XLSX
- [ ] Vérifiez que les produits sont importés
- [ ] Slugs générés correctement

### Test: Données incomplètes
- [ ] Créez CSV avec colonne manquante
- [ ] Importez
- [ ] Attendu: Erreur explicite

### Test: Catégorie invalide
- [ ] Créez CSV avec categorie_id = 999
- [ ] Importez
- [ ] Attendu: Utilise catégorie par défaut (1)


## 📊 CHECKLIST DE PRODUCTION

Avant d'utiliser en production:

- [ ] Tous les tests ci-dessus passent
- [ ] Équipe formée à l'utilisation
- [ ] Format CSV documenté et compris
- [ ] Droits d'accès correctement configurés
- [ ] Sauvegarde BD avant premier import
- [ ] Processus de contrôle qualité établi
- [ ] Plan de rollback défini


## 🎯 CHECKLIST UTILISATEUR

Avant d'importer vos données:

- [ ] Fichier préparé au format CSV
- [ ] Colonnes: code | designation | categorie_id | prix_unite
- [ ] Codes sont uniques
- [ ] Pas de caractères spéciaux problématiques
- [ ] Encodage: UTF-8
- [ ] Fichier < 10 MB

### Format de test rapide:
```csv
code,designation,categorie_id,prix_unite
TEST-001,Test produit 1,1,1000.00
TEST-002,Test produit 2,1,2000.00
```

- [ ] Créez ce fichier
- [ ] Importez-le
- [ ] Vérifiez que 2 produits sont créés


## 🆘 DÉPANNAGE

### Problème: Page ne charge pas
- [ ] Vérifier permissions (PRODUITS_CREER)
- [ ] Vérifier logs PHP (/logs ou console)
- [ ] Essayer avec un autre navigateur

### Problème: Erreur "Format non supporté"
- [ ] Vérifier extension (.csv, .xlsx, .xls)
- [ ] Essayer avec fichier d'exemple

### Problème: Erreur "Code déjà existant"
- [ ] C'est normal si vous réimportez
- [ ] Utilisez un code différent pour tester

### Problème: Aucun produit créé
- [ ] Vérifier le message de succès
- [ ] Vérifier en BD: SELECT COUNT(*) FROM catalogue_produits
- [ ] Consulter le guide "Dépannage" (TEST_IMPORT_GUIDE.md)


## 📚 DOCUMENTATION À CONSULTER

Si vous avez des questions, consultez:

- **Format du fichier:** GUIDE_IMPORT_CATALOGUE.md
- **Guide de test:** TEST_IMPORT_GUIDE.md
- **Architecture technique:** admin/catalogue/README_IMPORT.md
- **Troubleshooting complet:** TEST_IMPORT_GUIDE.md (section Dépannage)


## ✨ TIPS & BONNES PRATIQUES

✓ Testez d'abord avec le fichier d'exemple
✓ Vérifiez l'aperçu avant de confirmer
✓ Sauvegardez votre BD avant import important
✓ Utilisez CSV pour compatibilité maximale
✓ Vérifiez les codes en BD après import
✓ Contactez support en cas d'erreur


## 🎉 VOUS ÊTES PRÊT!

Si toutes les cases sont cochées:

1. Créez votre fichier CSV
2. Allez à: http://localhost/kms_app/admin/catalogue/import.php
3. Importez!

Pour besoin d'aide: Consultez DOCUMENTATION_INDEX.md


═════════════════════════════════════════════════════════════════════════════

Toutes les cases cochées?  ✅ C'EST BON, VOUS POUVEZ COMMENCER!

═════════════════════════════════════════════════════════════════════════════
