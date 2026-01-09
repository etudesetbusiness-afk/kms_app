# 📦 PACKAGE DE DÉPLOIEMENT - Module Reporting Terrain

## Contenu du package

Ce dossier contient tous les fichiers nécessaires pour déployer le module "Reporting Hebdomadaire Terrain" sur Bluehost.

---

## 📁 Structure des fichiers

```
reporting_terrain_deploy/
│
├── 📖 GUIDES/
│   ├── QUICK_DEPLOY.md              ← Démarrage rapide (5 étapes)
│   ├── DEPLOYMENT_GUIDE.md          ← Guide détaillé avec troubleshooting
│   ├── DEPLOYMENT_CHECKLIST.md      ← Checklist de vérification
│   └── SQL_DEPLOY.md                ← Script SQL à copier-coller
│
├── 📄 MODULE/ (à copier dans /commercial/reporting_terrain/)
│   ├── index.php                    ← Liste des reportings
│   ├── create.php                   ← Formulaire de création
│   ├── store.php                    ← Traitement POST
│   ├── show.php                     ← Vue détaillée
│   ├── print.php                    ← Version imprimable
│   └── README.md                    ← Documentation du module
│
├── 🔧 CONFIG/ (fichiers à remplacer)
│   └── sidebar.php                  ← À copier dans /partials/
│
└── 🗄️ SQL/
    └── 003_terrain_reporting.sql    ← Migration à exécuter dans phpMyAdmin
```

---

## 🚀 Instructions de déploiement

### Option 1 : Démarrage rapide (utilisateurs avancés)
→ Lire `GUIDES/QUICK_DEPLOY.md`

### Option 2 : Guide pas à pas (recommandé)
→ Lire `GUIDES/DEPLOYMENT_GUIDE.md`

---

## ✅ Fichiers à transférer sur le serveur

### Via FTP/cPanel :

1. **Créer** le dossier :
   ```
   /public_html/kms_app/commercial/reporting_terrain/
   ```

2. **Copier** les 6 fichiers du dossier `MODULE/` dans ce nouveau dossier

3. **Remplacer** le fichier :
   ```
   /public_html/kms_app/partials/sidebar.php
   ```
   Par le fichier `CONFIG/sidebar.php` de ce package

4. **Exécuter** le script SQL :
   - Ouvrir phpMyAdmin
   - Copier-coller le contenu de `SQL/003_terrain_reporting.sql`
   - Exécuter

---

## 📊 Tables créées (7)

- `terrain_reporting` — Table principale
- `terrain_reporting_zones` — Zones visitées
- `terrain_reporting_activite` — Activité journalière
- `terrain_reporting_resultats` — Indicateurs commerciaux
- `terrain_reporting_objections` — Objections clients
- `terrain_reporting_arguments` — Arguments de vente
- `terrain_reporting_plan_action` — Plan d'action

---

## 🔐 Permissions requises

- **Permission utilisateur :** `VENTES_LIRE`
- **Permissions fichiers :** 644 (fichiers) / 755 (dossiers)

---

## 🌐 URL d'accès après déploiement

```
https://[votre-domaine]/kms_app/commercial/reporting_terrain/
```

Accessible via : **Sidebar → Commercial → Reporting terrain**

---

## 📞 Support

En cas de problème, consulter la section **DÉPANNAGE** dans `DEPLOYMENT_GUIDE.md`

---

**Package créé le :** 9 janvier 2026  
**Version du module :** 1.0  
**Compatible avec :** KMS Gestion (PHP 8+, MySQL 5.7+)
