# CRM PROSPECTIONS TERRAIN - GUIDE D'UTILISATION

## 🚀 Introduction

Le module **CRM Prospections Terrain** transforme la simple saisie de prospection en un système complet de gestion de la relation client (CRM) optimisé pour les commerciaux terrain.

---

## 📋 ÉTAPE 1 : Installation SQL

### Exécuter la migration

```bash
# Depuis phpMyAdmin ou ligne de commande MySQL :
mysql -u utilisateur -p nom_base < migration/004_prospections_crm.sql
```

### Vérification post-migration

```sql
-- Vérifier que les nouvelles colonnes existent
DESCRIBE prospections_terrain;

-- Vérifier les nouvelles tables
SHOW TABLES LIKE 'prospect_%';

-- Devrait retourner : prospect_notes, prospect_relances, prospect_timeline
```

---

## 📱 ÉTAPE 2 : Utilisation du CRM

### A. Page Liste Prospections (`prospections_list.php`)

#### **Formulaire rapide (20-30 secondes)**

Le formulaire est structuré en **3 sections accordéons** pour optimiser la vitesse de saisie :

##### **Section 1 : ESSENTIEL (obligatoire)**
- ✅ **Nom du prospect** (texte)
- ✅ **Téléphone** (9 chiffres obligatoires - format Cameroun)
- ✅ **Secteur/Zone géographique** (texte libre ou sélection)

##### **Section 2 : DÉTAILS (optionnel)**
- Email (optionnel)
- Besoin identifié
- Action menée
- Résultat
- Prochaine étape
- Tag activité (Quincaillerie / Menuiserie / Autre)

##### **Section 3 : RELANCE (optionnel)**
- Date de relance future
- Canal de relance (WhatsApp, Appel, SMS, Email, Visite)
- Message pour la relance

#### **Géolocalisation automatique**
- Bouton "📍 Utiliser ma position GPS"
- Capture automatique latitude, longitude, adresse GPS
- Fonctionne sur mobile avec autorisation navigateur

#### **Validation téléphone stricte**
```
Formats acceptés :
- 9 chiffres : 695657613
- Avec indicatif : 237695657613
- Avec + : +237695657613

❌ REJET si :
- Moins de 9 chiffres
- Lettres ou caractères spéciaux
- Numéro déjà existant (déduplication)
```

---

### B. Liste des Prospects

#### **Filtres avancés**

```php
// Filtres disponibles :
- Dates : Du [date] Au [date]
- Statut CRM : Dropdown tous statuts
- Commercial : Sélection utilisateur
- Zone/Secteur : Texte libre
- Tag activité : Quincaillerie/Menuiserie/Autre
- Relances en retard : Checkbox (affiche prospects avec relances passées)
```

#### **Actions rapides par ligne**

Chaque prospect affiché propose des **actions en un clic** :

| Icône | Action | Description |
|-------|--------|-------------|
| 📞 | Appeler | Ouvre `tel:` sur mobile |
| 💬 | WhatsApp | Ouvre `wa.me/` |
| ✉️ | Email | Ouvre `mailto:` |
| 👁️ | Fiche CRM | Ouvre `prospect_detail.php?id=X` |
| ✅ | Changer statut | Modal dropdown changement statut |
| 📝 | Note rapide | Modal ajout note |
| ⏰ | Relance | Modal planification relance |

---

### C. Fiche Prospect Détaillée (`prospect_detail.php`)

#### **1. En-tête KPI (4 cards)**

```
┌────────────────┬────────────────┬────────────────┬────────────────┐
│ STATUT         │ DEVIS ÉMIS     │ COMMANDES      │ CA GÉNÉRÉ      │
│ CLIENT ACTIF   │       3        │       2        │   1 500 000 F  │
└────────────────┴────────────────┴────────────────┴────────────────┘
```

#### **2. Colonne gauche : Informations + Actions**

**Carte Informations :**
- Nom complet
- Téléphone (cliquable `tel:`)
- Email (cliquable `mailto:`)
- Secteur/Zone
- Commercial responsable
- Tag activité
- Date création

**Carte Actions Rapides (6 boutons) :**
- 📞 Appeler (tel:)
- 💬 WhatsApp (wa.me/)
- ✉️ Email (mailto:)
- ✅ Changer statut (modal)
- 📝 Ajouter note (modal)
- ⏰ Planifier relance (modal)

**Alerte Prochaine Relance :**
- Si relance programmée, affichage en orange
- Date, canal, message

#### **3. Colonne droite : Onglets**

##### **Onglet Timeline** (historique complet)
Timeline chronologique inversée avec icônes :
- 🟢 Création prospect
- 🟡 Changement de statut (avec ancien → nouveau)
- 📝 Note ajoutée
- 📞 Appel effectué
- 💬 WhatsApp
- ✉️ Email envoyé
- 🏠 Visite terrain
- 📄 Devis créé/envoyé
- 💰 Vente conclue

Chaque élément affiche :
- Titre de l'action
- Utilisateur + Date/heure
- Description détaillée
- Badges de statut si pertinent

##### **Onglet Notes** (notes privées)
Liste des notes chronologiques :
- Auteur + Date/heure
- Contenu de la note (multilignes)
- Bouton "+ Ajouter note" en haut

##### **Onglet Relances** (planification + suivi)
Liste des relances avec statut :
- 🟡 À FAIRE (relance future)
- 🟢 FAIT (relance effectuée + résultat)
- ⚫ ANNULÉ

Pour chaque relance À FAIRE :
- Bouton "✅ Marquer fait" → Prompt pour saisir résultat

##### **Onglet Devis** (si client_id existe)
Table des devis émis :
- Numéro, Date, Statut, Montant TTC
- Bouton "Voir" vers `devis/show.php`

##### **Onglet Ventes** (si client_id existe)
Table des ventes réalisées :
- Numéro, Date, Statut, Montant TTC
- Bouton "Voir" vers `ventes/show.php`

---

## 🔄 WORKFLOW COMPLET

### Scénario 1 : Nouvelle Prospection → Conversion

```
JOUR 1 : Prospection terrain
├─ Commercial visite menuiserie "Atelier Bois Plus"
├─ Saisie rapide (30 sec) :
│   ├─ Nom : "Jean-Paul KAMGA"
│   ├─ Tél : 695123456 ✅ vérifié unique
│   ├─ Secteur : "Bonabéri Zone Industrielle"
│   └─ Géoloc GPS automatique
├─ Statut initial : PROSPECT
└─ Timeline : ✅ Prospect créé

JOUR 2 : Relance WhatsApp
├─ Commercial ouvre fiche CRM
├─ Clic action rapide "💬 WhatsApp"
├─ Envoie message : "Bonjour M. KAMGA, suite à notre entretien..."
├─ Retour dans CRM → "Planifier relance"
│   ├─ Date : 2025-01-05
│   ├─ Canal : APPEL
│   └─ Message : "Rappeler pour devis contreplaqué"
└─ Timeline : 📞 Relance WhatsApp effectuée

JOUR 5 : Appel + Devis demandé
├─ Commercial voit alerte "Relance prévue aujourd'hui"
├─ Clic "📞 Appeler"
├─ Après appel → "Changer statut"
│   └─ PROSPECT → DEVIS_DEMANDE
├─ Ajoute note : "Client intéressé par CTBX 18mm, commande 50 panneaux"
└─ Timeline : 
    ├─ 🟡 Changement PROSPECT → DEVIS_DEMANDE
    └─ 📝 Note ajoutée

JOUR 7 : Devis émis
├─ Commercial crée devis dans module Devis
├─ Lien client_id → prospection
├─ Changement statut : DEVIS_EMIS
└─ Timeline : 📄 Devis DEV-20250107-XXX créé (50 000 F)

JOUR 10 : Commande obtenue
├─ Client accepte devis
├─ Vente créée dans module Ventes
├─ Changement statut : COMMANDE_OBTENUE
└─ Timeline : 💰 Vente VTE-20250110-XXX (50 000 F)

JOUR 15 : Client actif
├─ Livraison effectuée
├─ Changement statut : CLIENT_ACTIF
├─ Planifier relance fidélisation :
│   ├─ Date : dans 30 jours
│   ├─ Canal : VISITE
│   └─ Message : "Visiter pour nouveaux besoins"
└─ KPI mis à jour :
    ├─ Devis : 1
    ├─ Commandes : 1
    └─ CA : 50 000 F
```

---

## 📊 DASHBOARD KPI (Prochaine étape)

### KPI Cards Enrichis (en cours de développement)

```
┌──────────────────────────────────────────────────────────┐
│  PROSPECTS TOTAL  │  RDV OBTENUS  │  DEVIS ÉMIS  │  COMMANDES  │
│       142         │      23       │      18      │      12     │
│  +5 cette semaine │   ↑ 15%       │   ↓ 8%       │   ↑ 25%     │
└──────────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────────┐
│  MONTANT COMMANDES │  TAUX CONVERSION  │  RELANCES EN RETARD │
│    2 500 000 F     │      8.45%        │         7           │
│    ↑ 35%           │      ↑ 2.1 pts    │      ⚠️ Urgent       │
└──────────────────────────────────────────────────────────┘
```

---

## 🎯 STATUTS CRM & TRANSITIONS

### Pipeline Commercial (Funnel)

```
PROSPECT (Nouveau contact)
    ↓
INTERESSE (A manifesté intérêt)
    ↓
PROSPECT_CHAUD (Très intéressé, échange actif)
    ↓
DEVIS_DEMANDE (A demandé chiffrage)
    ↓
DEVIS_EMIS (Devis envoyé en attente)
    ↓
COMMANDE_OBTENUE (Devis accepté → vente)
    ↓
CLIENT_ACTIF (Vente livrée, client actif)
    ↓
FIDELISATION (Suivi long terme)

    ❌ PERDU (Prospect perdu, abandonné)
```

### Règles de transition

**Automatiques (via modules KMS) :**
- DEVIS_EMIS : Auto quand devis créé avec `client_id`
- COMMANDE_OBTENUE : Auto quand vente créée avec `client_id`
- CLIENT_ACTIF : Auto quand BL livré

**Manuelles (commercial) :**
- Toutes autres transitions via dropdown "Changer statut"
- Chaque changement enregistré dans timeline

---

## 🔧 MAINTENANCE & DÉBOGAGE

### Vérifier intégrité données

```sql
-- Prospects sans téléphone (anomalie)
SELECT id, prospect_nom, telephone FROM prospections_terrain WHERE telephone = '' OR telephone IS NULL;

-- Doublons téléphone (déduplication manquée)
SELECT telephone, COUNT(*) as nb
FROM prospections_terrain
GROUP BY telephone
HAVING nb > 1;

-- Relances en retard non traitées
SELECT * FROM v_relances_en_retard;
```

### Réindexation si performances dégradées

```sql
OPTIMIZE TABLE prospections_terrain;
OPTIMIZE TABLE prospect_notes;
OPTIMIZE TABLE prospect_relances;
OPTIMIZE TABLE prospect_timeline;
```

---

## 📖 API POUR DÉVELOPPEURS

### Endpoints AJAX disponibles

#### `ajax_changer_statut.php`
```php
POST terrain/ajax_changer_statut.php
Body : {
  prospection_id: int,
  statut_crm: enum
}
Response : {
  success: boolean,
  message: string
}
```

#### `ajax_ajouter_note.php`
```php
POST terrain/ajax_ajouter_note.php
Body : {
  prospection_id: int,
  note: string (max 5000 chars)
}
Response : {
  success: boolean,
  message: string
}
```

#### `ajax_planifier_relance.php`
```php
POST terrain/ajax_planifier_relance.php
Body : {
  prospection_id: int,
  date_relance: date (YYYY-MM-DD),
  canal: enum(WHATSAPP, APPEL, SMS, EMAIL, VISITE),
  message: string (optional)
}
Response : {
  success: boolean,
  message: string
}
```

#### `ajax_marquer_relance_faite.php`
```php
POST terrain/ajax_marquer_relance_faite.php
Body : {
  relance_id: int,
  resultat: string (optional)
}
Response : {
  success: boolean,
  message: string
}
```

---

## ✅ CHECKLIST DE DÉMARRAGE

### Avant première utilisation

- [x] Migration SQL 004 exécutée
- [x] Tables `prospect_notes`, `prospect_relances`, `prospect_timeline` créées
- [x] Triggers activés (vérifier avec `SHOW TRIGGERS`)
- [x] Permissions `CLIENTS_CREER` attribuées aux commerciaux
- [x] Tester ajout prospect avec téléphone valide
- [x] Tester géolocalisation GPS sur mobile
- [x] Tester actions rapides (appel, WhatsApp, email)
- [x] Vérifier fiche CRM accessible
- [x] Tester ajout note + timeline mise à jour
- [x] Tester planification relance + alerte affichée

### Formation utilisateurs (30 min)

1. **Démonstration formulaire rapide** (5 min)
   - Remplir section Essentiel uniquement
   - Clic bouton GPS
   - Soumettre en moins de 30 secondes

2. **Tour de la fiche CRM** (10 min)
   - Expliquer KPI cards
   - Montrer actions rapides
   - Dérouler timeline
   - Ajouter note test
   - Planifier relance test

3. **Workflow complet** (10 min)
   - Créer prospect fictif
   - Changer statut PROSPECT → INTERESSE
   - Ajouter note "Client rappelé"
   - Planifier relance dans 3 jours
   - Montrer notification relance

4. **Filtres & recherche** (5 min)
   - Filtrer par statut "DEVIS_EMIS"
   - Activer "Relances en retard"
   - Export CSV

---

## 🆘 FAQ & DÉPANNAGE

### ❓ Le formulaire n'enregistre pas le prospect

**Causes possibles :**
1. Téléphone invalide (< 9 chiffres ou avec lettres)
2. Téléphone en doublon (déjà existant en base)
3. Token CSRF expiré (recharger page)

**Solution :**
- Vérifier message d'erreur en haut de page
- Tester avec téléphone unique : `699999999`

---

### ❓ Géolocalisation ne fonctionne pas

**Causes possibles :**
1. Navigateur ne supporte pas Geolocation API
2. Utilisateur a refusé autorisation GPS
3. Connexion GPS indisponible (intérieur bâtiment)

**Solution :**
- Autoriser localisation dans paramètres navigateur
- Tester en extérieur avec signal GPS clair
- Utiliser Chrome/Firefox récent sur mobile

---

### ❓ Actions rapides (WhatsApp, Appel) ne fonctionnent pas

**Cause :**
- Téléphone mal formaté en base (avec espaces ou caractères)

**Solution :**
```sql
-- Nettoyer téléphones existants
UPDATE prospections_terrain 
SET telephone = REPLACE(REPLACE(REPLACE(telephone, ' ', ''), '+237', ''), '+', '')
WHERE telephone LIKE '% %' OR telephone LIKE '+%';
```

---

### ❓ Timeline vide sur fiche CRM

**Cause :**
- Triggers non activés lors de migration
- Prospect créé avant migration

**Solution :**
```sql
-- Vérifier triggers
SHOW TRIGGERS LIKE 'prospections_terrain';

-- Créer manuellement entrée timeline pour anciens prospects
INSERT INTO prospect_timeline (prospection_id, type_action, titre, description, date_action)
SELECT id, 'CREATION', 'Prospect créé', CONCAT('Prospect créé par ', (SELECT nom FROM utilisateurs WHERE id = commercial_id LIMIT 1)), date_creation
FROM prospections_terrain
WHERE id NOT IN (SELECT prospection_id FROM prospect_timeline);
```

---

## 🚀 ROADMAP FUTURES AMÉLIORATIONS

### Phase 2 (Q1 2025)
- [ ] Notifications push relances en retard
- [ ] Envoi SMS automatique depuis CRM
- [ ] Intégration WhatsApp Business API
- [ ] Export rapport PDF prospect
- [ ] Calendrier visuel des relances

### Phase 3 (Q2 2025)
- [ ] Application mobile native (PWA)
- [ ] Scan cartes de visite OCR
- [ ] IA prédiction probabilité conversion
- [ ] Dashboard analytics avancé (ChartJS)
- [ ] Campagnes marketing automatisées

---

## 📞 SUPPORT

**Équipe développement KMS Gestion**
- 📧 Email : dev@kennemulti-services.com
- 📱 WhatsApp : +237 XXX XXX XXX
- 🌐 Documentation : https://docs.kms-gestion.local

**Tickets de support :**
Utiliser GitHub Issues avec template `[CRM] Titre du problème`

---

**Version : 1.0.0**  
**Dernière mise à jour : 16/12/2025**  
**Auteur : KMS Dev Team**
