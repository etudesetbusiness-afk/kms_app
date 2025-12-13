# 🔐 Guide Simple - Authentification 2FA par Email

## ✅ Qu'est-ce que c'est ?

L'authentification à deux facteurs (2FA) par **EMAIL** est la méthode la plus simple pour sécuriser votre compte KMS Gestion :

- **✨ Aucune application à installer**
- **📧 Tout se passe par email**
- **⏱️ Codes valables 5 minutes**
- **🛡️ Protection maximale contre le piratage**

---

## 📋 Comment activer le 2FA ?

### Étape 1 : Accéder aux paramètres

1. Connectez-vous à KMS Gestion : http://localhost/kms_app/login.php
2. Dans le menu de gauche (sidebar), tout en bas, cliquez sur **"Sécurité 2FA"**

### Étape 2 : Activer

1. Entrez votre **adresse email** (celle où vous voulez recevoir les codes)
2. Cliquez sur **"Activer le 2FA par Email"**
3. ✅ C'est fait !

---

## 🔑 Comment se connecter avec le 2FA activé ?

### Connexion en 2 étapes simples :

#### **Étape 1 : Login classique**
- Entrez votre **identifiant** (ex: `admin`)
- Entrez votre **mot de passe** (ex: `admin123`)
- Cliquez sur **"Se connecter"**

#### **Étape 2 : Code par email**
- Un email vous est immédiatement envoyé avec un **code à 6 chiffres**
- Exemple de code : `123456` ou `789012`
- Ouvrez votre boîte email
- Copiez le code reçu
- Collez-le dans le champ sur KMS Gestion
- Cliquez sur **"Vérifier le code"**
- ✅ **Vous êtes connecté !**

---

## 📧 À quoi ressemble l'email ?

Vous recevrez un email comme ceci :

```
De: KMS Gestion <noreply@kms-gestion.local>
Objet: KMS Gestion - Code de vérification

🔐 Code de vérification KMS Gestion

Bonjour admin,

Voici votre code de vérification pour vous connecter à KMS Gestion :

┌─────────────────────────┐
│      123456             │
│ Valable 5 minutes       │
└─────────────────────────┘

Si vous n'avez pas demandé ce code, ignorez cet email.

⚠️ Important : Ne partagez jamais ce code avec qui que ce soit.
```

---

## ⏱️ Combien de temps le code est-il valable ?

- **5 minutes** après réception
- Après 5 minutes, le code expire → Reconnectez-vous pour recevoir un nouveau code
- Vous avez **3 tentatives** pour saisir le bon code

---

## 🔧 Mode développement (XAMPP local)

En environnement local (XAMPP), la fonction PHP `mail()` ne fonctionne pas toujours.

### Solution 1 : Vérifier les logs

Le code est affiché dans les logs PHP. Vérifiez :
- **Fichier** : `C:\xampp\apache\logs\error.log`
- **Recherchez** : `MODE DÉVELOPPEMENT - Code 2FA Email`
- Le code s'affiche juste en dessous

### Solution 2 : Message affiché à l'écran

En mode développement, si l'email n'est pas envoyé, un message apparaît :

```
🔧 MODE DEV: Code = 123456 (vérifiez aussi les logs)
```

Utilisez directement ce code pour vous connecter.

---

## ❓ Questions fréquentes

### Je n'ai pas reçu l'email, que faire ?

1. **Vérifiez vos spams/courrier indésirable**
2. **Vérifiez l'adresse email** configurée dans les paramètres 2FA
3. **En mode local (XAMPP)** : Consultez les logs (voir ci-dessus)
4. **Reconnectez-vous** : Un nouveau code sera généré et envoyé

### Je me suis trompé de code 3 fois

- Le code est invalidé
- Retournez à la page de connexion
- Reconnectez-vous : un **nouveau code** sera envoyé

### Le code a expiré (5 minutes dépassées)

- Retournez à la page de connexion
- Reconnectez-vous avec votre login/mot de passe
- Un **nouveau code** sera automatiquement envoyé

### Comment désactiver le 2FA ?

1. Connectez-vous à KMS Gestion
2. Menu **"Sécurité 2FA"**
3. Cliquez sur **"Désactiver le 2FA"**
4. Confirmez avec votre **mot de passe**
5. ⚠️ **Attention** : Votre compte sera moins sécurisé

### Puis-je changer l'email de réception ?

1. **Désactivez** d'abord le 2FA
2. **Réactivez-le** avec la nouvelle adresse email

---

## 🛡️ Avantages du 2FA par Email

| Critère | Avec 2FA Email | Sans 2FA |
|---------|---------------|----------|
| **Protection** | ✅ Maximale | ❌ Faible |
| **Installation** | ✅ Aucune | - |
| **Complexité** | ✅ Simple | - |
| **Piratage mot de passe** | ✅ Bloqué | ❌ Accès direct |
| **Notification intrusion** | ✅ Email reçu | ❌ Aucune |

---

## 📞 Support

En cas de problème :
- Consultez les logs : `C:\xampp\apache\logs\error.log`
- Vérifiez la base de données : table `utilisateurs_2fa`
- Contactez l'administrateur système

---

**🎯 Recommandation** : Activez le 2FA sur **tous les comptes administrateurs** pour une sécurité maximale !
