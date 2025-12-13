<?php
// login.php - Version améliorée avec 2FA Email et Rate Limiting
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/lib/rate_limiter.php';
require_once __DIR__ . '/lib/email_2fa.php';

// Calcule dynamiquement l'URL du dashboard
$scriptPath   = $_SERVER['SCRIPT_NAME'];
$basePath     = rtrim(dirname($scriptPath), '/\\');
$dashboardUrl = $basePath . '/index.php';

// Si déjà connecté, on renvoie au dashboard
if (utilisateurConnecte()) {
    header('Location: ' . $dashboardUrl);
    exit;
}

$erreur = null;
$etape = 'login'; // 'login' ou '2fa'
$userId2FA = null;

// Gestion de la soumission du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    verifierCsrf($csrf);

    // Étape 1: Vérification login/password
    if (isset($_POST['etape']) && $_POST['etape'] === 'login') {
        $login       = trim($_POST['login'] ?? '');
        $motDePasse  = $_POST['mot_de_passe'] ?? '';
        $ip          = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        if ($login === '' || $motDePasse === '') {
            $erreur = "Veuillez saisir votre identifiant et votre mot de passe.";
        } else {
            // Rate limiting par IP
            $rateLimitCheck = RateLimiter::checkLogin($ip);
            
            if (!$rateLimitCheck['allowed']) {
                $erreur = $rateLimitCheck['message'] ?? 'Trop de tentatives. Veuillez réessayer plus tard.';
                
                // Log l'abus
                global $pdo;
                enregistrerTentativeConnexion($pdo, $login, null, false, 'Rate limit dépassé');
            } else {
                global $pdo;

                $stmt = $pdo->prepare("
                    SELECT id, login, mot_de_passe_hash, actif, compte_verrouille, 
                           mdp_expire, force_changement_mdp
                    FROM utilisateurs
                    WHERE login = :login
                    LIMIT 1
                ");
                $stmt->execute(['login' => $login]);
                $user = $stmt->fetch();

                if ($user) {
                    $userId = (int)$user['id'];

                    // Vérifications de sécurité
                    if ((int)$user['actif'] !== 1) {
                        enregistrerTentativeConnexion($pdo, $login, $userId, false, 'Compte inactif');
                        $erreur = "Ce compte est désactivé.";
                    } elseif ((int)$user['compte_verrouille'] === 1) {
                        enregistrerTentativeConnexion($pdo, $login, $userId, false, 'Compte verrouillé');
                        $erreur = "Ce compte est verrouillé. Contactez l'administrateur.";
                    } elseif (password_verify($motDePasse, $user['mot_de_passe_hash'])) {
                        // Mot de passe correct
                        $email2FA = new Email2FA($pdo);
                        
                        // Vérifier si 2FA par email est activé
                        if ($email2FA->isEnabledForUser($userId)) {
                            // Générer et envoyer le code par email
                            $code = Email2FA::generateCode();
                            $emailBackup = $email2FA->getBackupEmail($userId);
                            
                            if ($emailBackup) {
                                $email2FA->storeVerificationCode($userId, $code);
                                $result = $email2FA->sendCode($emailBackup, $code, $login);
                                
                                if ($result['success']) {
                                    // Passer à l'étape 2FA
                                    $_SESSION['pending_2fa_user_id'] = $userId;
                                    $_SESSION['pending_2fa_login'] = $login;
                                    $_SESSION['pending_2fa_time'] = time();
                                    $etape = '2fa';
                                    
                                    // En mode dev, stocker le code pour affichage visible
                                    if (isset($result['dev_mode']) && $result['dev_mode']) {
                                        $_SESSION['dev_2fa_code'] = $result['dev_code'];
                                    }
                                } else {
                                    $erreur = "Erreur lors de l'envoi du code. Contactez l'administrateur.";
                                }
                            } else {
                                $erreur = "Configuration 2FA incomplète. Contactez l'administrateur.";
                            }
                        } else {
                            // Pas de 2FA, connexion directe
                            finaliserConnexion($pdo, $userId, $login, $dashboardUrl);
                        }
                    } else {
                        // Mot de passe incorrect
                        enregistrerTentativeConnexion($pdo, $login, $userId, false, 'Mot de passe incorrect');
                        $erreur = "Identifiants incorrects.";
                    }
                } else {
                    // Login inconnu
                    enregistrerTentativeConnexion($pdo, $login, null, false, 'Login inconnu');
                    $erreur = "Identifiants incorrects.";
                }
            }
        }
    }
    
    // Étape 2: Vérification du code 2FA Email
    elseif (isset($_POST['etape']) && $_POST['etape'] === '2fa') {
        $code2FA = trim($_POST['code_2fa'] ?? '');
        
        // Vérifier que la session 2FA est toujours valide (5 minutes max)
        if (!isset($_SESSION['pending_2fa_user_id']) || 
            !isset($_SESSION['pending_2fa_time']) ||
            (time() - $_SESSION['pending_2fa_time']) > 300) {
            $erreur = "Session expirée. Veuillez vous reconnecter.";
            unset($_SESSION['pending_2fa_user_id'], $_SESSION['pending_2fa_login'], $_SESSION['pending_2fa_time']);
            $etape = 'login';
        } else {
            $userId = (int)$_SESSION['pending_2fa_user_id'];
            $login = $_SESSION['pending_2fa_login'];
            global $pdo;
            
            $email2FA = new Email2FA($pdo);
            $result = $email2FA->verifyCode($code2FA);
            
            if ($result['success']) {
                // 2FA réussi - Nettoyer le code dev de la session
                unset($_SESSION['dev_2fa_code']);
                enregistrerTentativeConnexion($pdo, $login, $userId, true, null, 'EMAIL');
                finaliserConnexion($pdo, $userId, $login, $dashboardUrl);
            } else {
                // Code 2FA incorrect
                enregistrerTentativeConnexion($pdo, $login, $userId, false, '2FA email incorrect', 'EMAIL');
                $erreur = $result['message'] ?? "Code de vérification incorrect.";
                $etape = '2fa';
            }
        }
    }
}

// Si on est en étape 2FA mais pas de session pending, retour au login
if ($etape === '2fa' && !isset($_SESSION['pending_2fa_user_id'])) {
    $etape = 'login';
}

$csrfToken = getCsrfToken();

/**
 * Finalise la connexion utilisateur
 */
function finaliserConnexion(PDO $pdo, int $userId, string $login, string $dashboardUrl): void
{
    // Reset rate limiter
    RateLimiter::loginSuccess($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    
    // Mettre à jour date_derniere_connexion
    $upd = $pdo->prepare("
        UPDATE utilisateurs
        SET date_derniere_connexion = NOW()
        WHERE id = :id
    ");
    $upd->execute(['id' => $userId]);

    // Créer la session active
    creerSessionActive($pdo, $userId);

    // Charger permissions
    chargerPermissionsUtilisateur($userId);

    // Nettoyer les variables de session 2FA
    unset($_SESSION['pending_2fa_user_id'], $_SESSION['pending_2fa_login'], 
          $_SESSION['pending_2fa_time'], $_SESSION['dev_2fa_code']);

    // Redirection dashboard
    header('Location: ' . $dashboardUrl);
    exit;
}

/**
 * Crée une session active trackée en DB
 */
function creerSessionActive(PDO $pdo, int $userId): void
{
    $sessionId = session_id();
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    // Expiration dans 2 heures par défaut
    $expirationMinutes = 120;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO sessions_actives 
            (id, utilisateur_id, ip_address, user_agent, date_expiration, actif)
            VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE), 1)
            ON DUPLICATE KEY UPDATE
                date_derniere_activite = NOW(),
                date_expiration = DATE_ADD(NOW(), INTERVAL ? MINUTE),
                actif = 1
        ");
        $stmt->execute([$sessionId, $userId, $ip, $userAgent, $expirationMinutes, $expirationMinutes]);
    } catch (PDOException $e) {
        error_log("Erreur création session active: " . $e->getMessage());
    }
}

/**
 * Enregistre une tentative de connexion
 */
function enregistrerTentativeConnexion(
    PDO $pdo, 
    string $login, 
    ?int $userId, 
    bool $succes, 
    ?string $raisonEchec = null,
    ?string $methode2FA = null
): void {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO tentatives_connexion 
            (login_attempt, utilisateur_id, ip_address, user_agent, methode_2fa, succes, raison_echec)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $login,
            $userId,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $methode2FA,
            $succes ? 1 : 0,
            $raisonEchec
        ]);
    } catch (PDOException $e) {
        error_log("Erreur enregistrement tentative connexion: " . $e->getMessage());
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Connexion – KMS Back-office</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    
    <style>
        body {
            min-height: 100vh;
            background: radial-gradient(circle at top left, #1f2933, #020617 60%);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .kms-login-card {
            max-width: 420px;
            width: 100%;
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(15,23,42,0.45);
        }
        .kms-login-header {
            background: linear-gradient(135deg, #0f172a, #1d4ed8);
            color: #f9fafb;
        }
        .kms-login-logo {
            width: 52px;
            height: 52px;
            border-radius: 999px;
            background: rgba(15,23,42,0.7);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .form-control:focus {
            box-shadow: 0 0 0 0.2rem rgba(37,99,235,0.35);
            border-color: #2563eb;
        }
        .code-2fa-input {
            font-size: 1.5rem;
            text-align: center;
            letter-spacing: 0.5rem;
            font-weight: 600;
        }
        .badge-security {
            background: rgba(34,197,94,0.1);
            color: #16a34a;
            border: 1px solid rgba(34,197,94,0.3);
        }
    </style>
</head>
<body>

<div class="kms-login-card bg-white">
    <div class="kms-login-header p-4 d-flex gap-3 align-items-center">
        <div class="kms-login-logo">
            <i class="bi bi-shield-lock text-primary fs-3"></i>
        </div>
        <div>
            <h1 class="h5 mb-1">Kenne Multi-Services</h1>
            <p class="mb-0 small text-light opacity-75">
                <?= $etape === '2fa' ? 'Vérification en deux étapes' : 'Back-office marketing & commercial' ?>
            </p>
        </div>
    </div>

    <div class="p-4">
        <?php if ($erreur): ?>
            <div class="alert alert-danger py-2 mb-3">
                <i class="bi bi-exclamation-circle me-1"></i>
                <?= htmlspecialchars($erreur) ?>
            </div>
        <?php endif; ?>

        <?php if ($etape === 'login'): ?>
            <!-- Formulaire de connexion standard -->
            <p class="small text-muted mb-3">
                Connectez-vous pour accéder au tableau de bord.
            </p>

            <form method="post" novalidate>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="etape" value="login">

                <div class="mb-3">
                    <label for="login" class="form-label small">Identifiant</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" class="form-control" id="login" name="login" required autocomplete="username"
                               value="<?= isset($login) ? htmlspecialchars($login) : '' ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="mot_de_passe" class="form-label small">Mot de passe</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" class="form-control" id="mot_de_passe" name="mot_de_passe" required
                               autocomplete="current-password">
                        <button type="button" class="btn btn-outline-secondary" id="togglePassword" tabindex="-1">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 mt-2">
                    <i class="bi bi-box-arrow-in-right me-1"></i>
                    Se connecter
                </button>
            </form>

        <?php else: ?>
            <!-- Formulaire 2FA Email -->
            
            <?php if (isset($_SESSION['dev_2fa_code'])): ?>
                <!-- MODE DÉVELOPPEMENT - Affichage du code -->
                <div class="alert alert-warning text-center mb-3">
                    <h6 class="alert-heading">
                        <i class="bi bi-tools me-2"></i>
                        MODE DÉVELOPPEMENT (XAMPP)
                    </h6>
                    <hr>
                    <p class="mb-2">L'email n'a pas pu être envoyé (normal en local).</p>
                    <p class="mb-2"><strong>Votre code de vérification est :</strong></p>
                    <div class="p-3 bg-dark text-white rounded" style="font-size: 28px; font-weight: bold; letter-spacing: 8px;">
                        <?= htmlspecialchars($_SESSION['dev_2fa_code']) ?>
                    </div>
                    <p class="small text-muted mt-2 mb-0">
                        Copiez ce code et saisissez-le ci-dessous
                    </p>
                </div>
            <?php endif; ?>
            
            <div class="text-center mb-3">
                <div class="badge badge-security px-3 py-2 mb-3">
                    <i class="bi bi-shield-check me-1"></i>
                    Vérification en 2 étapes
                </div>
                <p class="small text-muted">
                    <i class="bi bi-envelope-fill text-primary me-1"></i>
                    Un code de vérification à 6 chiffres a été envoyé à votre adresse email.
                </p>
                <p class="small text-muted">
                    <strong>Le code expire dans 5 minutes.</strong>
                </p>
            </div>

            <form method="post" novalidate>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="etape" value="2fa">

                <div class="mb-3">
                    <label for="code_2fa" class="form-label small text-center d-block">Code reçu par email</label>
                    <input type="text" class="form-control code-2fa-input" id="code_2fa" name="code_2fa" 
                           required pattern="[0-9]{6}" maxlength="6" inputmode="numeric" autocomplete="one-time-code"
                           placeholder="000000" autofocus>
                    <div class="form-text text-center">
                        <small>Entrez le code à 6 chiffres</small>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-check-circle me-1"></i>
                    Vérifier le code
                </button>
            </form>

            <div class="text-center mt-3">
                <a href="<?= htmlspecialchars($basePath . '/login.php') ?>" class="btn btn-link btn-sm">
                    <i class="bi bi-arrow-left"></i>
                    Retour à la connexion
                </a>
            </div>
            
            <div class="alert alert-info mt-3">
                <small>
                    <i class="bi bi-info-circle me-1"></i>
                    <strong>Email non reçu ?</strong> Vérifiez vos spams ou reconnectez-vous pour recevoir un nouveau code.
                </small>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>
<script>
    // Toggle password visibility
    const togglePasswordBtn = document.getElementById('togglePassword');
    const pwdInput = document.getElementById('mot_de_passe');

    if (togglePasswordBtn && pwdInput) {
        togglePasswordBtn.addEventListener('click', () => {
            const type = pwdInput.getAttribute('type') === 'password' ? 'text' : 'password';
            pwdInput.setAttribute('type', type);
            togglePasswordBtn.querySelector('i').classList.toggle('bi-eye');
            togglePasswordBtn.querySelector('i').classList.toggle('bi-eye-slash');
        });
    }

    // Auto-focus et formatting du code 2FA
    const code2FAInput = document.getElementById('code_2fa');
    if (code2FAInput) {
        code2FAInput.addEventListener('input', (e) => {
            e.target.value = e.target.value.replace(/\D/g, '').substring(0, 6);
            
            // Auto-submit quand 6 chiffres sont saisis
            if (e.target.value.length === 6) {
                setTimeout(() => {
                    e.target.closest('form').submit();
                }, 300);
            }
        });
    }
</script>
</body>
</html>
