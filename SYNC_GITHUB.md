# Instructions pour synchroniser avec GitHub

## Étape 1 : Créer un dépôt sur GitHub

1. Allez sur https://github.com/new
2. Nom du dépôt : `kms-gestion` (ou autre nom de votre choix)
3. Description : "Application de gestion commerciale KMS - Kenne Multi-Services"
4. Visibilité : **Privé** (recommandé pour code d'entreprise)
5. **NE PAS** cocher "Initialize with README" (le projet existe déjà)
6. Cliquez sur "Create repository"

## Étape 2 : Connecter et pousser le code

Une fois le dépôt créé sur GitHub, exécutez ces commandes :

```powershell
# Remplacer VOTRE-USERNAME par votre nom d'utilisateur GitHub
git remote add origin https://github.com/VOTRE-USERNAME/kms-gestion.git

# Renommer la branche principale en 'main' (standard GitHub)
git branch -M main

# Pousser le code vers GitHub
git push -u origin main
```

## Étape 3 : Vérification

Après le push, vérifiez sur GitHub que tous les fichiers sont bien présents.

## Pour les prochaines synchronisations

Une fois configuré, pour synchroniser vos futurs changements :

```powershell
# Ajouter les fichiers modifiés
git add .

# Créer un commit avec un message descriptif
git commit -m "Description des changements"

# Pousser vers GitHub
git push
```

## Exemple de messages de commit

- `feat: Ajout module de facturation`
- `fix: Correction calcul TVA dans ventes`
- `docs: Mise à jour documentation utilisateur`
- `style: Amélioration design dashboard`
- `refactor: Optimisation requêtes SQL`

## État actuel du projet

✅ Dépôt Git initialisé
✅ Premier commit créé (279 fichiers, 129,556 lignes)
✅ Configuration Git locale définie
⏳ En attente de connexion à GitHub

## Contenu du commit initial

Le commit inclut :
- ✅ 24 pages list.php modernisées
- ✅ 13 pages edit.php modernisées
- ✅ Framework CSS moderne (modern-lists.css + modern-forms.css)
- ✅ Framework JS interactif (modern-lists.js + modern-forms.js)
- ✅ Documentation complète (GUIDE_MODERNISATION_*.md)
- ✅ Tous les modules (ventes, achats, stock, compta, etc.)
- ✅ Système de sécurité et permissions
- ✅ Module comptabilité SYSCOHADA
- ✅ Interconnexion des modules

## Fichiers exclus (.gitignore)

Les fichiers suivants ne sont PAS versionnés :
- Configuration base de données (config/database.php)
- Uploads et fichiers temporaires
- Logs
- Cache et sessions
- Fichiers IDE (.vscode, .idea)
- Variables d'environnement (.env)

## Support

Pour toute question sur Git/GitHub, consultez :
- https://docs.github.com/fr
- https://git-scm.com/doc
