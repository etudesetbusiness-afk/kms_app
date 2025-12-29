# 🔧 FIX BLUEHOST - Erreur #1044 Access Denied

## Problème Reçu

```
MySQL said: Documentation
#1044 - Access denied for user 'cpses_kdd6wpiijx'@'localhost' to database 'information_schema'
```

---

## Explication

**Cause:** Votre utilisateur Bluehost (`cpses_kdd6wpiijx`) n'a pas les permissions d'accéder à la base de données `information_schema` de MySQL.

**Important:** Cette erreur est **NORMALE sur Bluehost** avec les permissions limitées de l'hébergement partagé.

Le vrai problème (PRIMARY KEY manquante) peut quand même être corrigé!

---

## Solution: 2 Scripts à Exécuter en Séquence

### Script 1: Nettoyer les données (OPTIONNEL mais RECOMMANDÉ)

**Fichier:** `db/fix_catalogue_cleanup_data.sql`

**À faire:**
1. Ouvrez phpMyAdmin sur Bluehost
2. Sélectionnez la base `kdfvxvmy_kms_gestion`
3. Onglet **SQL**
4. Copiez le contenu de `fix_catalogue_cleanup_data.sql`
5. Collez dans phpMyAdmin
6. **Cliquez "Go"**

**Qu'il fait:**
- Vérifie les codes dupliqués
- Vérifie les slugs dupliqués
- Corrige automatiquement les doublons
- Corrige les produits orphelins
- Prépare les données pour les contraintes

**Durée:** 5-10 secondes

---

### Script 2: Ajouter les Contraintes (ESSENTIEL)

**Fichier:** `db/fix_catalogue_schema_v2.sql`

**À faire:**
1. Restez dans phpMyAdmin
2. Onglet **SQL** (vider le contenu précédent si besoin)
3. Copiez le contenu de `fix_catalogue_schema_v2.sql`
4. Collez dans phpMyAdmin
5. **Cliquez "Go"**

**Qu'il fait:**
- ✅ Ajoute PRIMARY KEY à `catalogue_categories`
- ✅ Ajoute PRIMARY KEY à `catalogue_produits` ← **CECI CORRIGE LE PROBLÈME**
- ✅ Ajoute UNIQUE KEYs (code, slug)
- ✅ Ajoute INDEXes
- ✅ Ajoute CHECK constraints JSON
- ✅ Ajoute FOREIGN KEY

**Durée:** 5-10 secondes

---

## Erreurs Attendues vs Problèmes

### ✅ Ces erreurs sont NORMALES (vous pouvez ignorer):

```
#1064 - Syntax error
#1022 - Can't write; duplicate key in table '#sql-...'
#1091 - Can't DROP 'slug'; check that column/key exists
Duplicate key name 'code'
Duplicate key name 'slug'
```

**Pourquoi?** Les contraintes existent peut-être déjà. phpMyAdmin continue après les erreurs attendues.

### ❌ Ces erreurs DOIVENT être corrigées:

```
#1045 - Access denied for user
#1046 - No database selected
#1064 - Syntax error in the SQL statement (real error)
```

**Solution:** 
- Vérifiez que vous êtes dans la bonne base (`kdfvxvmy_kms_gestion`)
- Vérifiez que vous avez les bonnes permissions pour ALTER TABLE
- Essayez le nettoyage des données d'abord

---

## Processus Complet Étape par Étape

### Étape 1: Accès phpMyAdmin
```
Bluehost Control Panel
  ↓
Databases
  ↓
phpMyAdmin
  ↓
Sélectionner kdfvxvmy_kms_gestion (colonne gauche)
```

### Étape 2: Nettoyer les données (5 min)
```
1. Cliquer sur onglet "SQL"
2. Copier fix_catalogue_cleanup_data.sql
3. Coller dans phpMyAdmin
4. Cliquer "Go"
5. Attendre quelques secondes
6. Vérifier qu'il n'y a pas d'erreurs rouges graves
```

**Résultat attendu:**
```
Queries executed successfully
Showing rows 0 - 25 (0 total, Query took 0.0002 sec)
```

### Étape 3: Ajouter les contraintes (5 min)
```
1. Cliquer sur onglet "SQL" (effacer le contenu)
2. Copier fix_catalogue_schema_v2.sql
3. Coller dans phpMyAdmin
4. Cliquer "Go"
5. Attendre quelques secondes
6. Vérifier qu'aucune erreur #1045, #1046, #1064 n'apparaît
```

**Résultat attendu:**
```
Your SQL query has been executed successfully
```

### Étape 4: Vérifier
```
1. Aller à l'onglet "Structure" de la table catalogue_produits
2. Vérifier que "PRIMARY" apparaît sur la colonne id
3. Vérifier qu'il y a des clés "code" et "slug"
```

### Étape 5: Tester dans l'application
```
1. Aller sur https://app.kennemulti-services.com
2. Login admin
3. Admin → Catalogue Produits
4. Éditer un produit
5. Changer le nom
6. Cliquer "Modifier"
7. Rafraîchir (F5)
8. ✅ Le changement doit persister
```

---

## Si Ça Ne Fonctionne Pas

### Cas 1: Erreur "Duplicate key name"
```
Cause: La clé existe déjà
Action: C'est OK, c'est que le fix a partiellement fonctionné
Vérification: Aller dans Structure de la table et vérifier les clés
```

### Cas 2: Erreur #1045 "Access denied"
```
Cause: Vous n'avez pas les permissions suffisantes
Action: Contactez Bluehost support pour demander les permissions ALTER TABLE
```

### Cas 3: Erreur #1064 "Syntax error"
```
Cause: Copie/collage mal fait ou caractères spéciaux
Action: 
1. Ouvrir le fichier directement depuis VS Code
2. Copier depuis là (Ctrl+A, Ctrl+C)
3. Coller dans phpMyAdmin
```

### Cas 4: Les changements ne persistent toujours pas
```
Cause: Le PRIMARY KEY n'a pas été ajouté correctement
Action:
1. Aller à phpMyAdmin → Structure de catalogue_produits
2. Vérifier que PRIMARY KEY existe sur `id`
3. Si absent, essayer le script à nouveau
4. Si toujours absent après 2 tentatives, contactez Bluehost
```

---

## Fichiers à Utiliser

| Fichier | Moment | Action |
|---------|--------|--------|
| fix_catalogue_cleanup_data.sql | 1er | Exécuter en premier (optionnel mais recommandé) |
| fix_catalogue_schema_v2.sql | 2e | Exécuter en second (ESSENTIEL) |

---

## Commands Utiles pour Vérification

**Après exécution des scripts, copie-collez ces commandes dans phpMyAdmin pour vérifier:**

### Vérifier que PRIMARY KEY existe:
```sql
SHOW KEYS FROM `catalogue_produits` WHERE Key_name = 'PRIMARY';
```
**Résultat attendu:** 1 ligne avec `Key_name = PRIMARY`

### Vérifier que UNIQUE KEYs existent:
```sql
SHOW KEYS FROM `catalogue_produits` WHERE Key_name IN ('code', 'slug');
```
**Résultat attendu:** 2 lignes (code et slug)

### Vérifier la structure complète:
```sql
SHOW CREATE TABLE `catalogue_produits`;
```
**Cherchez dans le résultat:** `PRIMARY KEY`, `UNIQUE KEY code`, `UNIQUE KEY slug`

---

## Résumé Rapide

**TL;DR:**

1. ✅ Exécutez `fix_catalogue_cleanup_data.sql` (nettoie les données)
2. ✅ Exécutez `fix_catalogue_schema_v2.sql` (ajoute les clés)
3. ✅ Testez dans l'application (éditer un produit)
4. ✅ C'est bon! Les modifications persistent maintenant

**Durée totale:** 10-15 minutes

---

## FAQ Bluehost Spécifique

**Q: Pourquoi j'ai accès limité à information_schema?**
A: C'est normal sur l'hébergement partagé Bluehost. Ils limitent les permissions pour des raisons de sécurité.

**Q: Les scripts vont fonctionner même sans accès à information_schema?**
A: OUI! Les scripts `_v2.sql` et `_cleanup_data.sql` ne dépendent pas d'information_schema.

**Q: Puis-je exécuter les scripts en un seul coup?**
A: Oui, mais le nettoyage en premier est plus sûr. Vous pouvez combiner les deux si vous êtes pressé.

**Q: Et si j'ai une erreur à l'étape 3?**
A: Vérifiez d'abord que vous êtes dans la bonne base. Puis essayez encore une fois. Si erreur persiste, exécutez cleanup_data.sql à nouveau.

---

**Status:** 🔴 AVANT → 🟢 APRÈS les scripts

**Prêt?** Exécutez les scripts dans cet ordre:
1. fix_catalogue_cleanup_data.sql
2. fix_catalogue_schema_v2.sql

**Puis testez:** Édition produit → Modifier → Rafraîchir → Changement persiste ✅
