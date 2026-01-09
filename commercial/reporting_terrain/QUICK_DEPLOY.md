# ⚡ DÉPLOIEMENT RAPIDE - 5 ÉTAPES

## 🎯 Déployer le module "Reporting Terrain" sur Bluehost

---

## ÉTAPE 1️⃣ : Transférer les fichiers (FTP)

**Via FileZilla ou cPanel File Manager**

### Créer le dossier :
```
/public_html/kms_app/commercial/reporting_terrain/
```

### Copier ces 6 fichiers dedans :
```
✓ index.php
✓ create.php
✓ store.php
✓ show.php
✓ print.php
✓ README.md
```

---

## ÉTAPE 2️⃣ : Remplacer la sidebar

**Remplacer le fichier :**
```
/public_html/kms_app/partials/sidebar.php
```

Par votre nouvelle version locale de `sidebar.php`

---

## ÉTAPE 3️⃣ : Exécuter le SQL

1. **Aller dans cPanel → phpMyAdmin**
2. **Sélectionner la base** `kms_gestion`
3. **Cliquer sur l'onglet SQL**
4. **Copier-coller** tout le contenu de :
   ```
   db/migrations/003_terrain_reporting.sql
   ```
   *(Voir le fichier SQL_DEPLOY.md pour le contenu complet)*

5. **Cliquer "Go"**

**Résultat attendu :** "7 requêtes exécutées avec succès"

---

## ÉTAPE 4️⃣ : Vérifier les tables

Dans phpMyAdmin, exécuter :
```sql
SHOW TABLES LIKE 'terrain_reporting%';
```

**Vous devez voir 7 tables :**
- terrain_reporting
- terrain_reporting_activite
- terrain_reporting_arguments
- terrain_reporting_objections
- terrain_reporting_plan_action
- terrain_reporting_resultats
- terrain_reporting_zones

---

## ÉTAPE 5️⃣ : Tester en ligne

1. **Se connecter** à votre application KMS sur Bluehost
2. **Aller dans Sidebar → Commercial → Reporting terrain**
3. **Cliquer** "Nouveau Reporting"
4. **Remplir** le formulaire
5. **Soumettre** et vérifier que tout fonctionne

---

## ✅ C'EST FAIT !

Le module est déployé et opérationnel.

---

## 🆘 Problème ?

Consultez le fichier **DEPLOYMENT_GUIDE.md** pour le dépannage complet.

---

**Durée totale : 10-15 minutes**
