# Guide d'Intégration - Module Catalogue Admin

**Version:** 1.0.0  
**Date:** 15 décembre 2025  
**Statut:** ✅ Production Ready  
**Tests:** 44/44 PASSÉS

---

## 🚀 Déploiement Immédiat

Le module est **production-ready**. Aucune action requise avant utilisation.

### Vérification Post-Déploiement

```bash
# 1. Vérifier les fichiers sont présents
ls -la admin/catalogue/
# Résultat attendu: 4 fichiers PHP + 1 README

# 2. Vérifier les dossiers uploads
ls -la uploads/catalogue/
# Résultat attendu: Dossier vide, writable

# 3. Tester depuis le navigateur
# Menu: Produits & Stock > Produits
# Menu: Produits & Stock > Catégories
```

---

## 👥 Permissions Utilisateurs

### Roles Requis

Pour utiliser le module, les utilisateurs doivent avoir les permissions:

| Permission | Fonction | Défaut |
|-----------|----------|--------|
| PRODUITS_LIRE | Voir liste produits/catégories | ADMIN, SHOWROOM |
| PRODUITS_CREER | Créer produits/catégories | ADMIN |
| PRODUITS_MODIFIER | Éditer produits/catégories | ADMIN |
| PRODUITS_SUPPRIMER | Supprimer produits/catégories | ADMIN |

### Assigner Permissions

**Via l'interface utilisateurs:**
1. Aller à **Administration > Utilisateurs**
2. Éditer utilisateur
3. Assigner permissions PRODUITS_*
4. Enregistrer

**Via SQL (si nécessaire):**
```sql
-- Donner accès lecture catalogue au rôle SHOWROOM
INSERT INTO role_permission (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p
WHERE r.code = 'SHOWROOM' AND p.code = 'PRODUITS_LIRE';

-- Donner accès complet au rôle ADMIN (déjà fait par défaut)
```

---

## 📋 Checklist Déploiement

- [x] Fichiers PHP syntaxiquement valides
- [x] Tables BD présentes (catalogue_produits, catalogue_categories)
- [x] Colonnes JSON fonctionnelles
- [x] Foreign keys actives
- [x] Permissions créées en BD
- [x] Dossier uploads/catalogue accessible en écriture
- [x] Menu sidebar intégré
- [x] Tests unitaires: 44/44 PASSÉS
- [x] Tests de contraintes: OK
- [x] Tests d'upload: OK
- [x] CSRF protection: Actif
- [x] SQL injection protection: Prepared statements
- [x] Permissions: Vérifiées
- [ ] Formation utilisateurs (À faire)
- [ ] Validation utilisateurs finaux (À faire)

---

## 🎯 Workflow Standard

### 1. Créer les Catégories

1. Aller à **Produits & Stock > Catégories**
2. Cliquer **Nouvelle Catégorie**
3. Remplir:
   - **Nom:** "Menuiserie", "Machines", "Accessoires", etc.
   - **Ordre:** 1, 2, 3 (pour tri d'affichage)
   - Cocher **Actif** pour rendre visible
4. Valider

**Résultat:** Catégories disponibles dans dropdown produits

### 2. Ajouter Produits

1. Aller à **Produits & Stock > Produits**
2. Cliquer **Nouveau Produit**
3. Remplir:
   - **Code:** Unique (ex: "PLY-OKO-18")
   - **Désignation:** Nom produit (ex: "Contreplaqué Okoumé 18mm")
   - **Catégorie:** Sélectionner (ex: "Menuiserie")
   - **Description:** Texte descriptif (optionnel)
   - **Prix unitaire:** ex 150.00 (optionnel)
   - **Prix gros:** ex 130.00 (optionnel)

4. Ajouter caractéristiques (bouton +):
   - **Clé:** "Epaisseur" → **Valeur:** "18 mm"
   - **Clé:** "Dimensions" → **Valeur:** "1220 x 2440 mm"
   - **Clé:** "Essence" → **Valeur:** "Okoumé"
   - etc.

5. Charger image principale:
   - Cliquer **Parcourir** sous "Image principale"
   - Sélectionner fichier (JPEG/PNG/GIF/WEBP, max 5 MB)
   - Image remplace l'ancienne si édition

6. Charger galerie (optionnel):
   - Cliquer **Parcourir** sous "Galerie"
   - Sélectionner plusieurs fichiers
   - Images s'ajoutent à l'existant

7. Cocher **Actif** pour rendre visible en catalogue public

8. Cliquer **Enregistrer**

**Résultat:** Produit visible dans catalogue public

### 3. Gérer Produits

**Liste:**
- Filtrer par **Recherche** (code, désignation)
- Filtrer par **Catégorie** (dropdown)
- Filtrer par **Statut** (actif/inactif)
- Trier colonnes (croissant/décroissant)
- Pagination (25, 50, 100 par page)

**Actions:**
- **Voir public** (icône œil) - Ouvre produit en nouveau tab
- **Éditer** (icône crayon) - Ouvre formulaire
- **Supprimer** (icône poubelle) - Supprime produit + images

**Modification:**
- Accès via **Éditer** dans liste
- Tous champs modifiables
- Image principale: Nouvelle image remplace l'ancienne
- Galerie: Ajouter nouvelles images (anciens restent)
- Caractéristiques: Ajouter/supprimer lignes avec boutons +/×
- Enregistrer pour valider

**Suppression:**
- Confirmation pop-up obligatoire
- Image principale et galerie supprimées
- Produit retiré de catalogue public

---

## 🔍 Vérification Catalogue Public

Après création produits, vérifier le catalogue public:

**URL:** `http://localhost/kms_app/catalogue/index.php`

✓ **Doit afficher:**
- Catégories créées
- Produits actifs uniquement
- Images principale visible
- Filtres et recherche fonctionnels

✓ **Ne doit PAS afficher:**
- Produits inactifs
- Catégories inactives

---

## 📞 Support

### Documentation
- **[admin/catalogue/README.md](admin/catalogue/README.md)** - Guide complet 374 lignes
- **[admin/catalogue/DEPLOY_SUMMARY.md](admin/catalogue/DEPLOY_SUMMARY.md)** - Récapitulatif technique
- **[TEST_REPORT_CATALOGUE.md](TEST_REPORT_CATALOGUE.md)** - Rapport de test 44/44

### Tests
- **[test_catalogue_cli.php](test_catalogue_cli.php)** - Suite de tests CLI (exécutable)
- Exécution: `php test_catalogue_cli.php`
- Résultat attendu: 44 tests PASSÉS (100%)

### Bugs/Questions
1. Consulter la documentation
2. Vérifier permissions utilisateur
3. Vérifier folder uploads/catalogue/ exists and writable
4. Exécuter test_catalogue_cli.php pour diagnostic

---

## 🔐 Sécurité

### Validations Actives

✅ **Uploads:**
- Types: JPEG, PNG, GIF, WEBP uniquement
- Taille: Max 5 MB
- Nommage: Unique (uniqid prefix)
- Pas d'exécution (uploads/*.php impossible)

✅ **Base de données:**
- Prepared statements (pas d'interpolation)
- Vérification foreign keys
- Code produit unique
- Slug unique (avec collision detection)

✅ **Web:**
- CSRF tokens (tous formulaires)
- Vérification permissions
- Sessions + cookies sécurisés
- Pas d'affichage erreurs en prod

---

## ⚠️ Limitations Connues

**Aucune limitation critique détectée.**

### Future Improvements (Phase 2+)
- Suppression individuelle images galerie
- Réorganisation ordre galerie (drag & drop)
- Import/Export CSV
- Redimensionnement auto images
- Rich text editor description
- Synchronisation stock (produit_id)

---

## 📊 Monitoring

### Vérifier la santé du module

```bash
# Tester via CLI
php test_catalogue_cli.php
# Résultat attendu: 44 tests PASSÉS

# Vérifier dossier uploads
ls -lah uploads/catalogue/
# Résultat attendu: (vide ou fichiers .jpg/.png)

# Vérifier BD
mysql -u root kms_gestion -e "
  SELECT COUNT(*) as produits FROM catalogue_produits;
  SELECT COUNT(*) as categories FROM catalogue_categories;
"
```

### Logs

Aucun log spécifique au module. Utiliser:
- Logs PHP: `php_errors.log` (serveur)
- Browser console: F12 → Console (erreurs JS)
- DB logs: MySQL logs (si erreurs BD)

---

## ✅ Checklist Utilisateur Avant Production

- [ ] Permissions utilisateurs attribuées
- [ ] Formation équipe complétée
- [ ] Catégories créées
- [ ] Au moins 1 produit testé
- [ ] Images uploadées et visibles
- [ ] Catalogue public accédé et validé
- [ ] Test_catalogue_cli.php exécuté (44/44)
- [ ] Aucune erreur dans browser console
- [ ] Menu sidebar visible
- [ ] Fonctionnalités testées par utilisateurs finaux

---

## 🎓 Formation Équipe

### Pour les Administrateurs
Durée: 15 minutes

Topics:
1. Accès module (menu)
2. Créer catégories
3. Créer produits
4. Upload images
5. Modifier/Supprimer
6. Vérifier catalogue public

### Pour les Utilisateurs Final (SHOWROOM/TERRAIN)
Durée: 10 minutes

Topics (lecture seule):
1. Accès catalogue public
2. Recherche produits
3. Filtres et catégories
4. Affichage caractéristiques

---

## 📈 Métriques à Surveiller

| Métrique | Baseline | Alert |
|----------|----------|-------|
| Produits créés | 0 | N/A |
| Catégories | 0 | N/A |
| Images uploadées | 0 | N/A |
| Erreurs upload | 0 | > 0 |
| Temps réponse liste | <500ms | >2000ms |

---

## Conclusion

✅ **Le module est prêt pour production.**

- Tests: 44/44 PASSÉS (100%)
- Documentation: Complète
- Sécurité: Validée
- Architecture: Solide
- Intégration: Réussie

**Déploiement recommandé:** Immédiat

---

**Support:** [admin/catalogue/README.md](admin/catalogue/README.md)  
**Tests:** `php test_catalogue_cli.php`  
**Version:** 1.0.0  
**Date:** 15 décembre 2025
