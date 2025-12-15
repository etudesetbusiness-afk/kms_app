# ✅ CHECKLIST PARCOURS UTILISATEURS - KMS Gestion

**Date:** 15 décembre 2025  
**Statut:** À exécuter  
**Objectif:** Tester tous les parcours métier en condition réelle

---

## 🎯 PARCOURS 1: GESTION DES VENTES

### 1.1 Création de Devis
- [ ] Aller sur `ventes/` → "Nouveau Devis"
- [ ] Remplir: Client, Produits, Quantités, Prix
- [ ] Vérifier calcul montant total (correcte)
- [ ] Ajouter notes client
- [ ] Sauvegarder ✓
- [ ] Vérifier la devis est créée avec statut "BROUILLON"
- [ ] Email de notification envoyé (si configuré)

### 1.2 Validation Devis
- [ ] Ouvrir une devis existante
- [ ] Cliquer "Valider"
- [ ] Vérifier statut change à "VALIDÉE"
- [ ] Vérifier timestamp de validation
- [ ] Vérifier numéro de reference

### 1.3 Création Vente depuis Devis
- [ ] Ouvrir une devis validée
- [ ] Cliquer "Créer Vente"
- [ ] Vérifier les lignes sont pré-remplies
- [ ] Vérifier montant total identique
- [ ] Ajouter références paiement
- [ ] Sauvegarder ✓

### 1.4 Liste des Ventes
- [ ] Aller sur `ventes/list.php`
- [ ] Vérifier affichage liste (pagination 25 par défaut)
- [ ] **Pagination:**
  - [ ] Cliquer page 2 → charge bien
  - [ ] Changer per_page à 50 → recharge correctement
  - [ ] Changer per_page à 100 → recharge correctement
  - [ ] Retour à page 1 → fonctionne
- [ ] **Filtres Date:**
  - [ ] Preset "Last 7 days" → dates correctes
  - [ ] Preset "Last 30 days" → dates correctes
  - [ ] Preset "Last 90 days" → dates correctes
  - [ ] Filtrer par date custom → SQL exécuté correctement
- [ ] **Recherche:**
  - [ ] Rechercher par numéro vente → résultat
  - [ ] Rechercher par nom client → résultats
  - [ ] Recherche vide → tous les résultats
- [ ] **Tri:**
  - [ ] Tri par date ↑ → ordre correct
  - [ ] Tri par date ↓ → ordre correct
  - [ ] Tri par montant ↑ → ordre correct
  - [ ] Tri par montant ↓ → ordre correct
- [ ] **Export:**
  - [ ] Cliquer export Excel → télécharge
  - [ ] Ouvrir fichier → données correctes
  - [ ] Vérifier tous les filtres appliqués dans export

### 1.5 Modification Vente
- [ ] Ouvrir une vente
- [ ] Modifier client → sauvegarde ✓
- [ ] Ajouter lignes → sauvegarde ✓
- [ ] Supprimer lignes → sauvegarde ✓
- [ ] Vérifier recalcul montant
- [ ] Ajouter notes → sauvegarde ✓

### 1.6 Changement Statut Vente
- [ ] Ouvrir vente (statut: "EN_COURS")
- [ ] Cliquer "Marquer comme livrée"
- [ ] Vérifier statut → "LIVRÉE"
- [ ] Vérifier historique enregistré
- [ ] Vérifier impact stock (si automatique)

---

## 🚚 PARCOURS 2: GESTION DES LIVRAISONS

### 2.1 Création Bon de Livraison
- [ ] Aller sur `livraisons/` → "Nouveau BL"
- [ ] Sélectionner une vente
- [ ] Vérifier lignes pré-remplies
- [ ] Modifier quantités livrées si besoin
- [ ] Ajouter notes
- [ ] Sauvegarder ✓
- [ ] Vérifier BL créé avec numéro séquentiel

### 2.2 Signature BL (Parcours Terrain)
- [ ] Ouvrir BL non signé
- [ ] Remplir: Signature client, Date
- [ ] Cliquer "Signer"
- [ ] Vérifier statut → "SIGNÉ"
- [ ] Vérifier timestamp
- [ ] Email confirmation envoyé (si configuré)

### 2.3 Liste Livraisons avec Filtres
- [ ] Aller sur `livraisons/list.php`
- [ ] **Pagination:**
  - [ ] per_page=25 par défaut ✓
  - [ ] Changer à 50 → recharge ✓
  - [ ] Changer à 100 → recharge ✓
- [ ] **Filtres Date:**
  - [ ] "Last 30 days" (preset par défaut) ✓
  - [ ] Changer à custom date range ✓
  - [ ] Vérifier WHERE exécuté correctement
- [ ] **Filtres Client:**
  - [ ] Sélectionner client → filtre ✓
  - [ ] Combiné avec date → intersection correcte ✓
- [ ] **Filtre Signature:**
  - [ ] Afficher signés uniquement ✓
  - [ ] Afficher non-signés uniquement ✓
- [ ] **Recherche:**
  - [ ] Par numéro BL ✓
  - [ ] Par nom client ✓
  - [ ] Par numéro vente ✓
- [ ] **Tri:**
  - [ ] Par date ↑/↓ ✓
  - [ ] Par client ↑/↓ ✓
  - [ ] Par numéro ↑/↓ ✓
- [ ] **Export:**
  - [ ] Cliquer export Excel ✓
  - [ ] Fichier contient filtres appliqués ✓

### 2.4 Gestion Stock à la Livraison
- [ ] Créer BL pour vente
- [ ] Vérifier stock du produit avant
- [ ] Créer BL → marquer comme livré
- [ ] Vérifier stock du produit diminué
- [ ] Vérifier mouvement stock enregistré
- [ ] Consulter historique stocks

---

## ⚠️ PARCOURS 3: GESTION LITIGES

### 3.1 Création Litige
- [ ] Aller sur `coordination/litiges.php` → "Nouveau Litige"
- [ ] Sélectionner client
- [ ] Sélectionner produit/vente concernée
- [ ] Saisir motif
- [ ] Saisir type problème (enum)
- [ ] Saisir responsable suivi
- [ ] Sauvegarder ✓

### 3.2 Liste Litiges avec Filtres
- [ ] Aller sur `coordination/litiges.php`
- [ ] **Vérifier filtre date par défaut:** 90 jours ✓
- [ ] **Pagination:**
  - [ ] Page 1 → charge ✓
  - [ ] per_page=25 par défaut ✓
  - [ ] per_page=50 → recharge ✓
- [ ] **Filtres Date:**
  - [ ] "Last 90 days" (par défaut) ✓
  - [ ] "Last 30 days" → résultats filtrés ✓
  - [ ] Custom date range → SQL exécuté ✓
- [ ] **Filtre Statut:**
  - [ ] EN_COURS → affiche uniquement ✓
  - [ ] RESOLU → affiche uniquement ✓
  - [ ] ABANDONNE → affiche uniquement ✓
  - [ ] Tous → combine avec date ✓
- [ ] **Filtre Type:**
  - [ ] DEFAUT_PRODUIT → filtre ✓
  - [ ] ERREUR_LIVRAISON → filtre ✓
  - [ ] Combiné avec statut → intersection ✓
- [ ] **Recherche:**
  - [ ] Par nom client ✓
  - [ ] Par motif ✓
  - [ ] Par numéro vente ✓
  - [ ] Par code produit ✓
- [ ] **Affichage Filtres Actifs:**
  - [ ] Vérifier "Du: 2025-09-15" affichée ✓
  - [ ] Vérifier "Au: 2025-12-15" affichée ✓
  - [ ] Vérifier "Statut: EN_COURS" si filtrée ✓
  - [ ] Cliquer sur filtre → réinitialise ✓

### 3.3 Gestion Litige (Statut + Résolution)
- [ ] Ouvrir un litige (EN_COURS)
- [ ] Ajouter notes suivi
- [ ] Cliquer "Marquer comme résolu"
- [ ] Saisir solution + montant remboursé
- [ ] Sauvegarder ✓
- [ ] Vérifier statut → RESOLU
- [ ] Vérifier date_resolution enregistrée
- [ ] Vérifier montant rembourse dans compta

### 3.4 Statistiques Litiges
- [ ] Vérifier affichage "Total litiges"
- [ ] Vérifier "En cours"
- [ ] Vérifier "Résolus"
- [ ] Vérifier "Total remboursé"
- [ ] Vérifier calculs sont corrects (SUM des montants)
- [ ] Vérifier filtres appliqués aux stats

---

## 💰 PARCOURS 4: COMPTABILITÉ

### 4.1 Activation Exercice
- [ ] Aller sur `compta/exercices.php`
- [ ] Vérifier exercice courant
- [ ] Créer nouvel exercice (2026)
- [ ] Sélectionner comme actif
- [ ] Vérifier "Exercice actif" = 2026

### 4.2 Création Pièce Comptable (depuis Vente)
- [ ] Créer une vente
- [ ] Vérifier pièce comptable auto-créée
- [ ] Accéder à `compta/valider_piece.php`
- [ ] Vérifier lignes débit/crédit
- [ ] Vérifier équilibre (débit = crédit)
- [ ] Valider pièce
- [ ] Vérifier statut → VALIDÉE

### 4.3 Balance Comptable
- [ ] Aller sur `compta/balance.php`
- [ ] Vérifier comptes listés
- [ ] Vérifier soldes calculés
- [ ] Vérifier balance équilibrée
- [ ] Filtrer par compte → résultats
- [ ] Export balance → Excel

### 4.4 Grand Livre
- [ ] Aller sur `compta/grand_livre.php`
- [ ] Sélectionner compte
- [ ] Vérifier écritures chronologiques
- [ ] Vérifier cumul soldes
- [ ] Filtrer par date → résultats
- [ ] Vérifier "Solde final" correct

### 4.5 Bilan
- [ ] Aller sur `compta/bilan.php`
- [ ] Vérifier ACTIF = PASSIF + CAPITAL
- [ ] Vérifier détails actif circulant
- [ ] Vérifier détails passif
- [ ] Vérifier ratios de solvabilité
- [ ] Export PDF → fichier valide

---

## 💳 PARCOURS 5: CAISSE

### 5.1 Enregistrement Opération
- [ ] Aller sur `caisse/journal.php`
- [ ] Cliquer "Nouvelle opération"
- [ ] Type: Encaissement
- [ ] Montant: 500,000
- [ ] Référence: vente #123
- [ ] Moyen paiement: Espèces
- [ ] Sauvegarder ✓
- [ ] Vérifier dans liste

### 5.2 Liste Caisse avec Filtres
- [ ] Aller sur `caisse/journal.php`
- [ ] **Pagination:**
  - [ ] per_page=25 par défaut ✓
  - [ ] Changer à 50 → recharge ✓
- [ ] **Filtres Date:**
  - [ ] Aujourd'hui → résultats ✓
  - [ ] Semaine → résultats ✓
  - [ ] Mois → résultats ✓
- [ ] **Filtres Moyen:**
  - [ ] Espèces → filtre ✓
  - [ ] Chèque → filtre ✓
  - [ ] Virement → filtre ✓
- [ ] **Recherche:**
  - [ ] Par référence vente ✓
  - [ ] Par montant ✓

### 5.3 Rapprochement Caisse
- [ ] Aller sur `caisse/rapprochement.php`
- [ ] Saisir solde initial
- [ ] Saisir solde réel
- [ ] Système calcule différence
- [ ] Vérifier écarts expliqués
- [ ] Valider rapprochement

### 5.4 Clôture Journal Caisse
- [ ] Aller sur `caisse/cloture.php`
- [ ] Sélectionner période
- [ ] Vérifier total encaissements
- [ ] Vérifier total décaissements
- [ ] Cliquer "Clôturer"
- [ ] Vérifier statut période → CLÔTURÉ

---

## 📦 PARCOURS 6: GESTION STOCK

### 6.1 Consultation Stock
- [ ] Aller sur `produits/list.php`
- [ ] Vérifier stock_actuel pour chaque produit
- [ ] Vérifier flag "Rupture" si stock < seuil
- [ ] Cliquer sur produit → détails

### 6.2 Ajustement Stock
- [ ] Aller sur `produits/edit.php?id=X`
- [ ] Cliquer "Ajuster stock"
- [ ] Saisir: Quantité, Motif, Notes
- [ ] Sauvegarder ✓
- [ ] Vérifier stock_actuel mis à jour
- [ ] Vérifier mouvement enregistré
- [ ] Vérifier historique visible

### 6.3 Historique Mouvements
- [ ] Aller sur `admin/stocks_mouvements.php` (ou équivalent)
- [ ] Lister tous mouvements (ventes, achat, ajustements)
- [ ] Filtrer par produit → résultats
- [ ] Filtrer par date → résultats
- [ ] Filtrer par type → résultats
- [ ] Vérifier quantités correctes
- [ ] Export → Excel

### 6.4 Alertes Ruptures
- [ ] Aller sur Dashboard KPI
- [ ] Vérifier "Stock ruptures" KPI
- [ ] Cliquer pour voir détails
- [ ] Vérifier liste produits en rupture
- [ ] Vérifier seuil respecté

---

## 👥 PARCOURS 7: GESTION CLIENTS

### 7.1 Création Client
- [ ] Aller sur `clients/` → "Nouveau Client"
- [ ] Remplir: Nom, Email, Téléphone, Type
- [ ] Remplir: Adresse, Contact, Notes
- [ ] Sauvegarder ✓
- [ ] Vérifier client créé avec ID

### 7.2 Fiche Client
- [ ] Ouvrir client existant
- [ ] Vérifier infos générales
- [ ] Vérifier historique ventes
- [ ] Vérifier historique litiges
- [ ] Vérifier solde crédit
- [ ] Modifier notes → sauvegarde ✓

### 7.3 Changement Statut
- [ ] Ouvrir client ACTIF
- [ ] Cliquer "Marquer inactif"
- [ ] Vérifier statut → INACTIF
- [ ] Vérifier impact: Exclusion des filtres ventes?

---

## 📊 PARCOURS 8: DASHBOARDS & KPIs

### 8.1 Dashboard Principal
- [ ] Aller sur `dashboard.php`
- [ ] Vérifier affichage KPIs (doivent charger rapidement)
- [ ] **KPIs Affichés:**
  - [ ] CA Aujourd'hui (montant + nombre)
  - [ ] CA Ce mois (montant + nombre)
  - [ ] CA Cette année (montant + nombre)
  - [ ] Encaissement % (montant + %)
  - [ ] Clients actifs
  - [ ] Stock ruptures
  - [ ] Non livrées
  - [ ] Top client

### 8.2 Caching & Performance
- [ ] Ouvrir dashboard
- [ ] Mesurer temps chargement (doit être < 1s)
- [ ] Recharger → même temps (cache hit)
- [ ] Cliquer "Rafraîchir tout" → recharge depuis DB
- [ ] Vérifier données à jour
- [ ] Cliquer "Flush KPI" (CA jour) → recharge spécifique

### 8.3 Admin Panel
- [ ] Aller sur `admin/database_optimization.php`
- [ ] Vérifier "Cache Statistics"
- [ ] Vérifier "Slow Queries"
- [ ] Cliquer "Clear All Cache"
- [ ] Vérifier confirmation
- [ ] Vérifier dashboard se recharge

---

## 🔐 PARCOURS 9: SÉCURITÉ & PERMISSIONS

### 9.1 Test Authentification
- [ ] Déloguer (si connecté)
- [ ] Accéder `/ventes/list.php` → redirection login
- [ ] Accéder `/admin/health.php` → redirection login
- [ ] Se loguer avec admin
- [ ] Accéder pages → OK
- [ ] Se loguer avec utilisateur normal
- [ ] Accéder `/admin/*` → accès refusé (permission required)

### 9.2 Test Permissions
- [ ] Utilisateur SHOWROOM:
  - [ ] Peut voir ventes ✓
  - [ ] Peut voir livraisons ✓
  - [ ] Peut voir clients ✓
  - [ ] Peut voir compta? ✗ (si pas permission)
- [ ] Utilisateur TERRAIN:
  - [ ] Peut voir/signer livraisons ✓
  - [ ] Peut voir litiges ✓
  - [ ] Peut voir clients? ✓
  - [ ] Peut voir compta? ✗
- [ ] Utilisateur CAISSIER:
  - [ ] Peut voir caisse ✓
  - [ ] Peut enregistrer opérations ✓
  - [ ] Peut voir ventes? ✓
  - [ ] Peut voir compta? ✗
- [ ] Utilisateur DIRECTION:
  - [ ] Peut voir tout ✓
  - [ ] Peut exporter ✓
  - [ ] Peut valider ✓

### 9.3 Test CSRF
- [ ] Ouvrir formulaire vente
- [ ] Inspecter: token CSRF présent?
- [ ] Soumettre formulaire → OK (token valide)
- [ ] Essayer soumettre sans token → erreur CSRF

### 9.4 Test SQL Injection
- [ ] Recherche: Taper `' OR '1'='1`
- [ ] Résultat: Aucun résultat (prepared statement)
- [ ] Recherche: Taper `<script>alert(1)</script>`
- [ ] Résultat: XSS bloqué (htmlspecialchars)

---

## 🐛 PARCOURS 10: CAS LIMITES & ERREURS

### 10.1 Valeurs Numériques
- [ ] Créer vente avec montant = 0 → erreur?
- [ ] Créer vente avec montant = 999,999,999.99 → calcul OK
- [ ] Stock négatif → acceptable?
- [ ] Remise > 100% → erreur?

### 10.2 Chaînes de Caractères
- [ ] Nom client très long (500 chars) → enregistré?
- [ ] Caractères spéciaux: é, è, à → stocké correctement
- [ ] Caractères Unicode: 中文, العربية → stocké?
- [ ] Guillemets simples/doubles → échappe correctement

### 10.3 Dates
- [ ] Date début > date fin → erreur?
- [ ] Date future → acceptable?
- [ ] Date très ancienne (1900) → acceptable?
- [ ] Date invalide (30 février) → erreur?

### 10.4 Pagination & Filtres
- [ ] Page 0 → redirection page 1
- [ ] Page 999999 → redirection dernière page
- [ ] per_page = 0 → par défaut 25
- [ ] per_page = 10000 → limité à 1000?
- [ ] Combiner plusieurs filtres → intersection correcte

### 10.5 Suppressions
- [ ] Supprimer vente avec litiges → erreur foreign key?
- [ ] Supprimer produit avec stock → erreur?
- [ ] Supprimer client avec ventes → erreur?

---

## 📋 CHECKLIST FINALE

**Avant déploiement en production, vérifier:**

- [ ] Tous les parcours testés (10 × 100% ✓)
- [ ] Aucune erreur PHP affichée
- [ ] Aucune erreur SQL dans logs
- [ ] Aucune erreur JavaScript console
- [ ] Pagination fonctionne (all per_page values)
- [ ] Filtres date appliqués correctement
- [ ] Export Excel génère des fichiers valides
- [ ] Cache fonctionne (perf < 1s pour dashboards)
- [ ] Permissions respectées (par rôle)
- [ ] CSRF tokens présents et validés
- [ ] Pas de SQL injection (prepared statements)
- [ ] Pas de XSS (htmlspecialchars)
- [ ] Mots de passe hashés (password_hash)
- [ ] Sessions sécurisées (httponly, secure)

---

**Résultat:** ✅ Prêt pour production (une fois tous les tests passés)

