<?php
/**
 * Configuration 2FA pour l'utilisateur connecté
 */
require_once __DIR__ . '/../security.php';
require_once __DIR__ . '/../lib/two_factor_auth.php';

exigerConnexion();

$utilisateur = utilisateurConnecte();
$userId = (int)$utilisateur['id'];

global $pdo;

$message = null;
$messageType = 'success';
$etape = 'status'; // 'status', 'activate', 'confirm', 'codes'

// Vérifier l'état actuel du 2FA
$twoFactorActif = TwoFactorAuth::isEnabledForUser($pdo, $userId);

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifierCsrf($_POST['csrf_token'] ?? '');
    $action = $_POST['action'] ?? '';

    if ($action === 'generer_secret') {
        // Générer un nouveau secret
        $secret = TwoFactorAuth::generateSecret();
        $_SESSION['pending_2fa_secret'] = $secret;
        $_SESSION['pending_2fa_setup_time'] = time();
        $etape = 'activate';
        
    } elseif ($action === 'confirmer_activation') {
        // Vérifier le code TOTP pour confirmer l'activation
        $code = trim($_POST['code'] ?? '');
        $secret = $_SESSION['pending_2fa_secret'] ?? null;
        
        if (!$secret || (time() - ($_SESSION['pending_2fa_setup_time'] ?? 0)) > 600) {
            $message = "Session expirée. Veuillez recommencer.";
            $messageType = 'danger';
            unset($_SESSION['pending_2fa_secret'], $_SESSION['pending_2fa_setup_time']);
        } elseif (TwoFactorAuth::verifyCode($secret, $code)) {
            // Code valide, activer le 2FA
            if (TwoFactorAuth::enableForUser($pdo, $userId, $secret)) {
                // Générer les codes de récupération
                $recoveryCodes = TwoFactorAuth::generateRecoveryCodes(10);
                TwoFactorAuth::saveRecoveryCodes($pdo, $userId, $recoveryCodes);
                
                $_SESSION['recovery_codes'] = $recoveryCodes;
                unset($_SESSION['pending_2fa_secret'], $_SESSION['pending_2fa_setup_time']);
                
                $message = "Authentification à deux facteurs activée avec succès !";
                $messageType = 'success';
                $etape = 'codes';
                $twoFactorActif = true;
            } else {
                $message = "Erreur lors de l'activation. Veuillez réessayer.";
                $messageType = 'danger';
            }
        } else {
            $message = "Code incorrect. Vérifiez l'heure de votre appareil.";
            $messageType = 'danger';
            $etape = 'activate';
        }
        
    } elseif ($action === 'desactiver') {
        // Désactiver le 2FA
        $motDePasse = $_POST['mot_de_passe'] ?? '';
        
        // Vérifier le mot de passe avant de désactiver
        $stmt = $pdo->prepare("SELECT mot_de_passe_hash FROM utilisateurs WHERE id = ?");
        $stmt->execute([$userId]);
        $hash = $stmt->fetchColumn();
        
        if (password_verify($motDePasse, $hash)) {
            if (TwoFactorAuth::disableForUser($pdo, $userId)) {
                $message = "Authentification à deux facteurs désactivée.";
                $messageType = 'warning';
                $twoFactorActif = false;
            } else {
                $message = "Erreur lors de la désactivation.";
                $messageType = 'danger';
            }
        } else {
            $message = "Mot de passe incorrect.";
            $messageType = 'danger';
        }
        
    } elseif ($action === 'regenerer_codes') {
        // Régénérer les codes de récupération
        $motDePasse = $_POST['mot_de_passe'] ?? '';
        
        $stmt = $pdo->prepare("SELECT mot_de_passe_hash FROM utilisateurs WHERE id = ?");
        $stmt->execute([$userId]);
        $hash = $stmt->fetchColumn();
        
        if (password_verify($motDePasse, $hash)) {
            $recoveryCodes = TwoFactorAuth::generateRecoveryCodes(10);
            if (TwoFactorAuth::saveRecoveryCodes($pdo, $userId, $recoveryCodes)) {
                $_SESSION['recovery_codes'] = $recoveryCodes;
                $message = "Nouveaux codes de récupération générés.";
                $messageType = 'success';
                $etape = 'codes';
            }
        } else {
            $message = "Mot de passe incorrect.";
            $messageType = 'danger';
        }
    }
}

$csrfToken = getCsrfToken();

// Si on est en étape activate, récupérer le secret
$qrCodeUrl = null;
$secret = null;
if ($etape === 'activate' && isset($_SESSION['pending_2fa_secret'])) {
    $secret = $_SESSION['pending_2fa_secret'];
    $label = $utilisateur['login'] ?? $utilisateur['nom_complet'];
    $qrCodeUrl = TwoFactorAuth::getQrCodeImageUrl($secret, $label);
}

// Si on vient d'activer, récupérer les codes
$recoveryCodes = null;
if ($etape === 'codes' && isset($_SESSION['recovery_codes'])) {
    $recoveryCodes = $_SESSION['recovery_codes'];
}

include __DIR__ . '/../partials/header.php';
include __DIR__ . '/../partials/sidebar.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12 col-lg-8 mx-auto">
            <div class="d-flex align-items-center mb-4">
                <i class="bi bi-shield-lock text-primary fs-2 me-3"></i>
                <div>
                    <h1 class="h4 mb-0">Authentification à deux facteurs (2FA)</h1>
                    <p class="text-muted small mb-0">Renforcez la sécurité de votre compte</p>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?= htmlspecialchars($messageType) ?> alert-dismissible fade show">
                    <i class="bi bi-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-1"></i>
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($etape === 'status'): ?>
                <!-- État actuel -->
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div>
                                <h5 class="card-title mb-1">
                                    État: 
                                    <?php if ($twoFactorActif): ?>
                                        <span class="badge bg-success">
                                            <i class="bi bi-shield-check"></i> Activé
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">
                                            <i class="bi bi-shield-x"></i> Désactivé
                                        </span>
                                    <?php endif; ?>
                                </h5>
                            </div>
                        </div>

                        <p class="text-muted mb-4">
                            L'authentification à deux facteurs ajoute une couche de sécurité supplémentaire à votre compte.
                            Même si votre mot de passe est compromis, personne ne pourra se connecter sans votre appareil mobile.
                        </p>

                        <?php if ($twoFactorActif): ?>
                            <div class="alert alert-info mb-3">
                                <i class="bi bi-info-circle me-1"></i>
                                <strong>2FA activé</strong> - Vous devez scanner le code avec votre application à chaque connexion.
                            </div>

                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#regenerateCodesModal">
                                    <i class="bi bi-arrow-clockwise me-1"></i>
                                    Régénérer les codes de récupération
                                </button>
                                
                                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#disableModal">
                                    <i class="bi bi-shield-x me-1"></i>
                                    Désactiver le 2FA
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning mb-3">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                <strong>Recommandation:</strong> Activez le 2FA pour protéger votre compte contre les accès non autorisés.
                            </div>

                            <form method="post">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                <input type="hidden" name="action" value="generer_secret">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-shield-plus me-1"></i>
                                    Activer l'authentification à deux facteurs
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Info applications -->
                <div class="card shadow-sm mt-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="bi bi-phone me-1"></i> Applications recommandées</h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="text-center">
                                    <i class="bi bi-google text-primary fs-2"></i>
                                    <p class="mb-0 mt-2 small"><strong>Google Authenticator</strong></p>
                                    <p class="text-muted small">iOS & Android</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center">
                                    <i class="bi bi-microsoft text-info fs-2"></i>
                                    <p class="mb-0 mt-2 small"><strong>Microsoft Authenticator</strong></p>
                                    <p class="text-muted small">iOS & Android</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center">
                                    <i class="bi bi-key text-warning fs-2"></i>
                                    <p class="mb-0 mt-2 small"><strong>Authy</strong></p>
                                    <p class="text-muted small">Multi-plateforme</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            <?php elseif ($etape === 'activate'): ?>
                <!-- Configuration du 2FA -->
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-qr-code me-2"></i>Configuration du 2FA</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 text-center mb-4">
                                <h6>Étape 1: Scannez le QR code</h6>
                                <?php if ($qrCodeUrl): ?>
                                    <img src="<?= htmlspecialchars($qrCodeUrl) ?>" alt="QR Code" class="img-fluid mb-3" style="max-width: 250px;">
                                <?php endif; ?>
                                <p class="small text-muted">
                                    Utilisez votre application d'authentification pour scanner ce code.
                                </p>
                            </div>
                            <div class="col-md-6">
                                <h6>Étape 2: Ou saisissez manuellement</h6>
                                <div class="alert alert-secondary">
                                    <small class="text-muted d-block mb-1">Clé secrète:</small>
                                    <code class="fs-6"><?= htmlspecialchars($secret ?? '') ?></code>
                                    <button type="button" class="btn btn-sm btn-outline-secondary mt-2 w-100" 
                                            onclick="navigator.clipboard.writeText('<?= htmlspecialchars($secret ?? '') ?>')">
                                        <i class="bi bi-clipboard"></i> Copier
                                    </button>
                                </div>

                                <h6 class="mt-4">Étape 3: Vérifiez le code</h6>
                                <form method="post">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                    <input type="hidden" name="action" value="confirmer_activation">
                                    
                                    <div class="mb-3">
                                        <label for="code" class="form-label">Code à 6 chiffres</label>
                                        <input type="text" class="form-control form-control-lg text-center" id="code" name="code" 
                                               required pattern="[0-9]{6}" maxlength="6" inputmode="numeric" 
                                               style="letter-spacing: 0.5em; font-size: 1.5rem;" autofocus>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-success w-100">
                                        <i class="bi bi-check-circle me-1"></i>
                                        Confirmer et activer
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

            <?php elseif ($etape === 'codes' && $recoveryCodes): ?>
                <!-- Codes de récupération -->
                <div class="card shadow-sm border-success">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="bi bi-key me-2"></i>Codes de récupération</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            <strong>Important:</strong> Conservez ces codes en lieu sûr ! Chaque code ne peut être utilisé qu'une seule fois.
                        </div>

                        <div class="row">
                            <?php foreach ($recoveryCodes as $index => $code): ?>
                                <div class="col-md-6 mb-2">
                                    <div class="p-2 bg-light border rounded">
                                        <code class="fs-6"><?= ($index + 1) ?>. <?= htmlspecialchars($code) ?></code>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <button type="button" class="btn btn-outline-primary" onclick="imprimerCodes()">
                                <i class="bi bi-printer me-1"></i>
                                Imprimer
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="copierCodes()">
                                <i class="bi bi-clipboard me-1"></i>
                                Copier
                            </button>
                            <a href="<?= url_for('utilisateurs/2fa.php') ?>" class="btn btn-success ms-auto">
                                <i class="bi bi-check-circle me-1"></i>
                                Terminé
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal désactivation -->
<div class="modal fade" id="disableModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Désactiver le 2FA</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="action" value="desactiver">
                    
                    <p>Confirmez votre mot de passe pour désactiver l'authentification à deux facteurs :</p>
                    
                    <div class="mb-3">
                        <label for="mot_de_passe_disable" class="form-label">Mot de passe</label>
                        <input type="password" class="form-control" id="mot_de_passe_disable" name="mot_de_passe" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-danger">Désactiver</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal régénération codes -->
<div class="modal fade" id="regenerateCodesModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Régénérer les codes de récupération</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="action" value="regenerer_codes">
                    
                    <p>Les anciens codes seront invalidés. Confirmez votre mot de passe :</p>
                    
                    <div class="mb-3">
                        <label for="mot_de_passe_regen" class="form-label">Mot de passe</label>
                        <input type="password" class="form-control" id="mot_de_passe_regen" name="mot_de_passe" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Régénérer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function copierCodes() {
    const codes = <?= json_encode($recoveryCodes ?? []) ?>;
    const text = codes.map((code, i) => `${i + 1}. ${code}`).join('\n');
    navigator.clipboard.writeText(text).then(() => {
        alert('Codes copiés dans le presse-papier !');
    });
}

function imprimerCodes() {
    window.print();
}

// Auto-format du code à 6 chiffres
const codeInput = document.getElementById('code');
if (codeInput) {
    codeInput.addEventListener('input', (e) => {
        e.target.value = e.target.value.replace(/\D/g, '').substring(0, 6);
    });
}
</script>

<?php
// Nettoyer les codes de la session après affichage
if ($etape === 'codes') {
    unset($_SESSION['recovery_codes']);
}

include __DIR__ . '/../partials/footer.php';
?>
