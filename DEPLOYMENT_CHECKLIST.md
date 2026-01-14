# 📦 Fichiers à Uploader - Bluehost Deployment

## Fichiers à uploader VIA FTP

**Chemin destination:** `/public_html/kms_app/commercial/reporting_terrain/`

### 1. ✅ NOUVEAU FICHIER
```
edit.php
```
Localisation locale: `c:\xampp\htdocs\kms_app\commercial\reporting_terrain\edit.php`

### 2. ✅ FICHIERS À REMPLACER (MODIFIÉS)
```
create.php
store.php
index.php
show.php
print.php
```

---

## ✅ MIGRATIONS SQL à Exécuter

**Via cPanel → phpMyAdmin → onglet SQL**

### Migration 1 (Exécuter EN PREMIER)
**Fichier:** `db/migrations/004_terrain_reporting_statut.sql`

```sql
ALTER TABLE terrain_reporting 
ADD COLUMN statut ENUM('brouillon','soumis') NOT NULL DEFAULT 'soumis' AFTER updated_at;

ALTER TABLE terrain_reporting 
ADD INDEX idx_statut (statut);
```

### Migration 2 (Exécuter EN DEUXIÈME)
**Fichier:** `db/migrations/005_terrain_reporting_type_cible.sql`

```sql
ALTER TABLE terrain_reporting_zones
MODIFY COLUMN type_cible VARCHAR(255) DEFAULT NULL COMMENT 'Types de cibles séparés par virgules';
```

---

## 📝 Résumé des modifications

### edit.php (NOUVEAU)
- Affiche formulaire d'édition pour brouillons
- Pré-remplit toutes les données existantes
- Contrôle d'accès (propriétaire ou admin)
- Vérification statut = 'brouillon'

### create.php (MODIFIÉ)
- Section 2: Checkboxes au lieu de select pour type_cible
- 4 options: Menuiserie, Quincaillerie, Cabinet_BTP, Cabinet_etudes

### store.php (MODIFIÉ)
- Détecte créaton vs édition via `reporting_id` hidden input
- Gère UPDATE au lieu de INSERT pour éditions
- Sérialise checkboxes multiples → chaîne virgule séparée
- Messages adapté: "Brouillon modifié et enregistré" vs "Reporting modifié et soumis"

### index.php (MODIFIÉ)
- Ajoute bouton édition (crayon) visible SEULEMENT pour brouillons
- Fix vérification admin: `in_array('ADMIN', $_SESSION['roles'])`

### show.php (MODIFIÉ)
- Fix vérification admin pour affichage du bouton imprimer
- Admin peut voir/imprimer TOUS les rapports

### print.php (MODIFIÉ)
- Fix vérification admin pour imprimer TOUS les rapports
- Non-admin ne peut imprimer que leurs rapports

---

## 🔄 Ordre d'exécution OBLIGATOIRE

1. ✅ Sauvegarder BD via phpMyAdmin
2. ✅ Uploader 6 fichiers PHP via FTP
3. ✅ Exécuter Migration 004 (colonne statut)
4. ✅ Exécuter Migration 005 (type_cible VARCHAR)
5. ✅ Tester en accédant au site
6. ✅ Rafraîchir navigateur (Ctrl+F5)

**⚠️ NE PAS exécuter migrations avant uploads PHP = risque d'erreurs de page!**

---

## 🧪 Test rapide après déploiement

Via navigateur (production):
```
https://votredomaine.com/kms_app/commercial/reporting_terrain/
```

✅ **Test 1:** Voir un reporting brouillon → cliquer crayon → doit charger edit.php avec données
✅ **Test 2:** Créer nouveau → cocher 2+ checkboxes Section 2 → enregistrer → doit être sauvegardé
✅ **Test 3:** Éditer brouillon → modifier → cliquer Soumettre → doit être verrouillé (pas de crayon)

---

## 📋 Permissions FTP

Après upload, vérifier permissions:
```
create.php    → 644
edit.php      → 644
store.php     → 644
index.php     → 644
show.php      → 644
print.php     → 644
```

**Via cPanel File Manager:**
- Clic droit sur fichier → Change Permissions → 644

---

**Prêt à déployer?** ✅ Suivez le guide `DEPLOYMENT_GUIDE_BLUEHOST.md`
