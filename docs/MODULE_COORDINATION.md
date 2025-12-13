# Module Coordination Commerciale - Documentation

## Vue d'ensemble

Le module **Coordination** centralise le suivi complet du tunnel de vente :
**Devis → Vente → Préparation → Livraison → Retours/Litiges → Clôture**

## Structure mise en place

### 1. Base de données

#### Tables modifiées/créées

**bons_livraison** - Ajouts :
- `livreur_id` : Utilisateur qui effectue la livraison
- `date_livraison_effective` : Date réelle de livraison
- `statut` : EN_PREPARATION, PRET, EN_COURS_LIVRAISON, LIVRE, ANNULE
- `ordre_preparation_id` : Lien vers l'ordre de préparation

**bons_livraison_lignes** - Ajouts :
- `quantite_commandee` : Quantité initialement commandée
- `quantite_restante` : Quantité restant à livrer (support livraisons partielles)

**ventes** - Statut étendu :
- Ajout de `PARTIELLEMENT_LIVREE` pour gérer les livraisons partielles

**ordres_preparation** :
- `magasinier_id` existe déjà (préparateur/magasinier assigné)

### 2. Fichiers créés/modifiés

#### Module Coordination
- `coordination/index.php` - Point d'entrée (redirige vers dashboard)
- `coordination/dashboard.php` - Vue temps réel des ventes/livraisons/litiges (existe déjà, amélioré)
- `coordination/ordres_preparation.php` - Liste des ordres (existant)
- `coordination/ordres_preparation_edit.php` - **Modifié** : Ajout champ préparateur
- `coordination/litiges.php` - Gestion retours/litiges (existant)

#### Module Livraisons
- `livraisons/create.php` - **Créé** : Créer livraison partielle ou totale avec :
  - Sélection du livreur
  - Saisie des quantités à livrer par produit
  - Gestion automatique du stock (déstockage via lib/stock.php)
  - Mise à jour du statut de vente (PARTIELLEMENT_LIVREE ou LIVREE)
  - Lien avec ordre de préparation si applicable

- `livraisons/detail.php` - **Créé** : Détail complet d'une livraison avec :
  - Informations livraison (BL, livreur, transport, statut)
  - Articles livrés avec quantités commandées/livrées/restantes
  - Mouvements de stock associés
  - Navigation vers vente, ordre, stock

- `livraisons/list.php` - Liste des BL (existant)

#### Module Ventes
- `ventes/edit.php` - **Modifié** : Ajout boutons navigation rapide :
  - "Ordre préparation" → Créer un ordre pour cette vente
  - "Créer livraison" → Créer un BL pour cette vente
  - "Coordination" → Voir dans le dashboard coordination

#### Sidebar
- `partials/sidebar.php` - **Modifié** : Section "Coordination" avec :
  - Dashboard coordination
  - Ordres de préparation
  - Retours & litiges
  - Ruptures signalées

#### Migration SQL
- `migrations/add_livreur_livraisons_partielles.sql` - Script SQL exécuté

## Flux opérationnel

### Processus standard

1. **Devis accepté** → Devient **Vente** (statut: EN_ATTENTE_LIVRAISON)

2. **Vente** → Création **Ordre de préparation**
   - Depuis ventes/edit.php : bouton "Ordre préparation"
   - Ou depuis coordination/ordres_preparation_edit.php
   - Assignation d'un préparateur (magasinier_id)
   - Priorité (NORMALE, URGENTE, TRES_URGENTE)
   - Instructions spécifiques

3. **Ordre préparation** → Préparation effective
   - Magasinier prépare les articles
   - Statut : EN_ATTENTE → EN_PREPARATION → PRET

4. **Préparation terminée** → Création **Bon de livraison**
   - Depuis ventes/edit.php : bouton "Créer livraison"
   - Ou depuis livraisons/create.php?vente_id=X
   - Choix du livreur
   - Saisie des quantités à livrer (support partiel)
   - Déstockage automatique via lib/stock.php
   - Statut vente mis à jour (PARTIELLEMENT_LIVREE ou LIVREE)

5. **Livraison** → Suivi et signature
   - Statut BL : EN_COURS_LIVRAISON → LIVRE
   - Signature client (signe_client)
   - Consultation via livraisons/detail.php

6. **Post-livraison** → Gestion exceptions
   - Retours → coordination/litiges.php
   - Litiges → Traçabilité dans retours_litiges
   - Ruptures stock → coordination/ruptures.php

## Navigation inter-modules

### Depuis une Vente (ventes/edit.php)
- **Voir/Créer ordre préparation** → coordination/ordres_preparation_edit.php?vente_id=X
- **Créer livraison** → livraisons/create.php?vente_id=X
- **Dashboard coordination** → coordination/dashboard.php?vente_id=X

### Depuis un Ordre de préparation (coordination/ordres_preparation_edit.php)
- **Voir la vente** → ventes/edit.php?id=X
- **Voir le client** → clients/edit.php?id=X

### Depuis une Livraison (livraisons/detail.php)
- **Voir la vente** → ventes/edit.php?id=X
- **Voir l'ordre** → coordination/ordres_preparation_edit.php?id=X
- **Voir mouvements stock** → stock/mouvements.php?bon_livraison_id=X

### Depuis le Dashboard Coordination (coordination/dashboard.php)
- Vue centralisée de tous les dossiers en cours
- Alertes : ventes sans livraison, litiges en cours, incohérences
- Accès direct à chaque étape

## Fonctionnalités clés

### ✅ Livraisons partielles
- Permet de livrer une partie des quantités commandées
- Calcul automatique du reliquat (reste à livrer)
- Statut vente : PARTIELLEMENT_LIVREE si incomplet
- Possibilité de créer plusieurs BL pour une même vente

### ✅ Traçabilité complète
- Chaque action est datée et liée à un utilisateur
- Mouvements stock tracés (qui, quand, pourquoi)
- Historique des livraisons par vente
- Liaison vente ↔ ordre ↔ livraison ↔ stock

### ✅ Gestion des responsabilités
- **Commercial** (commercial_responsable_id) : crée la vente
- **Préparateur** (magasinier_id) : prépare la commande
- **Livreur** (livreur_id) : effectue la livraison
- **Caissier** : encaisse (module caisse)

### ✅ Alertes et cohérence
- Dashboard coordination affiche :
  - Ventes sans livraison
  - Livraisons en retard
  - Litiges en cours
  - Incohérences stock/vente

## Permissions requises

- `VENTES_LIRE` : Voir ventes et coordination
- `VENTES_MODIFIER` : Créer livraisons, ordres
- `STOCK_LIRE` : Voir mouvements stock
- `PRODUITS_LIRE` : Voir catalogue et ruptures

## Cas d'usage

### Cas 1 : Livraison totale immédiate
1. Vente créée → statut EN_ATTENTE_LIVRAISON
2. Créer BL avec toutes les quantités
3. Stock déstocké automatiquement
4. Statut vente → LIVREE

### Cas 2 : Livraison partielle
1. Vente 10 articles → commande acceptée
2. Stock disponible : 6 articles seulement
3. Créer BL avec 6 articles (quantité_restante = 4)
4. Statut vente → PARTIELLEMENT_LIVREE
5. Réapprovisionnement stock
6. Créer 2ème BL avec les 4 restants
7. Statut vente → LIVREE

### Cas 3 : Avec ordre de préparation
1. Vente créée
2. Créer ordre préparation (assigné à magasinier X)
3. Magasinier prépare → statut ordre = PRET
4. Créer BL lié à l'ordre
5. Livraison effectuée
6. Ordre et vente → statut LIVRE

### Cas 4 : Retour/Litige
1. Livraison effectuée
2. Client signale problème
3. Créer litige dans coordination/litiges.php
4. Traitement : remboursement, échange, avoir
5. Si retour physique : mouvement stock ENTREE_RETOUR
6. Clôture litige

## Tests recommandés

1. **Créer une vente** simple (3 produits)
2. **Créer ordre préparation** avec préparateur
3. **Créer livraison partielle** (2 produits sur 3)
   - Vérifier statut vente = PARTIELLEMENT_LIVREE
   - Vérifier stock déstocké correctement
4. **Créer 2ème livraison** (produit restant)
   - Vérifier statut vente = LIVREE
5. **Consulter coordination/dashboard.php** : tout doit être visible
6. **Navigation** : cliquer sur tous les liens inter-modules
7. **Vérifier mouvements stock** : cohérence quantités

## Améliorations futures possibles

- Signature électronique client (upload photo)
- Géolocalisation livreur
- SMS/email automatique lors de livraison
- Planification tournées livraison
- Impression étiquettes colis
- Scan codes-barres pour préparation
- Tableau de bord livreur mobile

---

**Date de mise en place** : 13 décembre 2025
**Développeur** : GitHub Copilot AI
**Statut** : ✅ Fonctionnel - Prêt pour tests utilisateur
