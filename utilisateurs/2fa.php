<?php
/**
 * Configuration 2FA pour l'utilisateur connecté
 * Support Email (Simple - Pas d'application requise)
 */
require_once __DIR__ . '/../security.php';
require_once __DIR__ . '/../lib/email_2fa.php';

exigerConnexion();

$utilisateur = utilisateurConnecte();
$userId = (int)$utilisateur['id'];

global $pdo;

$message = null;
$messageType = 'success';
$email2FA = new Email2FA($pdo);

// Vérifier l'état actuel du 2FA Email
$twoFactorActif = $email2FA->isEnabledForUser($userId);
$emailBackup = $email2FA->getBackupEmail($userId);

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifierCsrf($_POST['csrf_token'] ?? '');
    $action = $_POST['action'] ?? '';

    if ($action === 'activer') {
        // Activer le 2FA par email
        $email = trim($_POST['email'] ?? '');
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "Adresse email invalide.";
            $messageType = 'danger';
        } else {
            if ($email2FA->enableForUser($userId, $email)) {
                $message = "Authentification par email activée avec succès ! Vous recevrez un code à chaque connexion.";
                $messageType = 'success';
                $twoFactorActif = true;
                $emailBackup = $email;
            } else {
                $message = "Erreur lors de l'activation.";
                $messageType = 'danger';
            }
        }
        
    } elseif ($action === 'desactiver') {
        // Désactiver le 2FA
        $motDePasse = $_POST['mot_de_passe'] ?? '';
        
        // Vérifier le mot de passe avant de désactiver
        $stmt = $pdo->prepare("SELECT mot_de_passe_hash FROM utilisateurs WHERE id = ?");
        $stmt->execute([$userId]);
        $hash = $stmt->fetchColumn();
        
        if (password_verify($motDePasse, $hash)) {
            if ($email2FA->disableForUser($userId)) {
                $message = "Authentification à deux facteurs désactivée.";
                $messageType = 'warning';
                $twoFactorActif = false;
                $emailBackup = null;
            } else {
                $message = "Erreur lors de la désactivation.";
                $messageType = 'danger';
            }
        } else {
            $message = "Mot de passe incorrect.";
            $messageType = 'danger';
        }
    }
}

$csrfToken = getCsrfToken();
$titre = "Sécurité - Authentification à deux facteurs";

include __DIR__ . '/../partials/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 offset-md-2 col-lg-6 offset-lg-3">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-shield-check me-2"></i>
                        Authentification à Deux Facteurs (2FA)
                    </h5>
                </div>
                
                <div class="card-body">
                    <?php if ($message): ?>
                        <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
                            <?= htmlspecialchars($message) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Statut actuel -->
                    <div class="text-center mb-4">
                        <?php if ($twoFactorActif): ?>
                            <div class="badge bg-success px-4 py-3 mb-3 fs-6">
                                <i class="bi bi-shield-check me-2"></i>
                                2FA ACTIVÉ
                            </div>
                            <p class="text-muted mb-2">
                                <strong>Méthode :</strong> Email
                            </p>
                            <p class="text-muted">
                                <i class="bi bi-envelope-fill text-primary me-1"></i>
                                <strong><?= htmlspecialchars($emailBackup) ?></strong>
                            </p>
                        <?php else: ?>
                            <div class="badge bg-warning text-dark px-4 py-3 mb-3 fs-6">
                                <i class="bi bi-shield-exclamation me-2"></i>
                                2FA DÉSACTIVÉ
                            </div>
                            <p class="text-muted">
                                Renforcez la sécurité de votre compte en activant l'authentification à deux facteurs.
                            </p>
                        <?php endif; ?>
                    </div>

                    <!-- Explication simple -->
                    <?php if (!$twoFactorActif): ?>
                        <div class="alert alert-info">
                            <h6 class="alert-heading">
                                <i class="bi bi-info-circle me-2"></i>
                                Comment ça marche ?
                            </h6>
                            <hr>
                            <ol class="mb-0 ps-3">
                                <li>Vous activez le 2FA avec votre email</li>
                                <li>À chaque connexion, un code à 6 chiffres vous est envoyé par email</li>
                                <li>Vous saisissez ce code pour accéder à votre compte</li>
                            </ol>
                            <hr class="my-2">
                            <p class="mb-0 small">
                                <strong>✨ Avantage :</strong> Aucune application à installer ! Vous recevez tout par email.
                            </p>
                        </div>

                        <!-- Formulaire d'activation -->
                        <h6 class="mb-3">Activer l'authentification par email</h6>
                        
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                            <input type="hidden" name="action" value="activer">
                            
                            <div class="mb-4">
                                <label for="email" class="form-label">
                                    <i class="bi bi-envelope me-1"></i>
                                    Adresse email pour recevoir les codes
                                </label>
                                <input 
                                    type="email" 
                                    class="form-control form-control-lg" 
                                    id="email" 
                                    name="email" 
                                    value="<?= htmlspecialchars($utilisateur['email'] ?? '') ?>"
                                    placeholder="votre@email.com" 
                                    required 
                                    autofocus
                                >
                                <div class="form-text">
                                    Un code de vérification sera envoyé à cette adresse à chaque connexion
                                </div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="bi bi-shield-check me-2"></i>
                                    Activer le 2FA par Email
                                </button>
                            </div>
                        </form>
                        
                    <?php else: ?>
                        <!-- Actions pour compte avec 2FA actif -->
                        <div class="alert alert-success">
                            <h6 class="alert-heading">
                                <i class="bi bi-check-circle me-2"></i>
                                Votre compte est protégé
                            </h6>
                            <p class="mb-0">
                                À chaque connexion, un code de sécurité sera envoyé à votre adresse email.
                                Vous aurez 5 minutes pour le saisir.
                            </p>
                        </div>

                        <div class="d-grid gap-2 mt-4">
                            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#disableModal">
                                <i class="bi bi-shield-slash me-2"></i>
                                Désactiver le 2FA
                            </button>
                        </div>
                    <?php endif; ?>

                </div>
            </div>

            <!-- Info supplémentaire -->
            <?php if (!$twoFactorActif): ?>
                <div class="card mt-3 border-0 bg-light">
                    <div class="card-body">
                        <h6 class="text-muted mb-3">
                            <i class="bi bi-question-circle me-2"></i>
                            Pourquoi activer le 2FA ?
                        </h6>
                        <ul class="small text-muted mb-0">
                            <li>Protection contre le vol de mot de passe</li>
                            <li>Alertes en cas de tentative de connexion suspecte</li>
                            <li>Sécurité renforcée sans application tierce</li>
                            <li>Conforme aux bonnes pratiques de sécurité</li>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Désactivation -->
<div class="modal fade" id="disableModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Désactiver le 2FA
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="action" value="desactiver">
                    
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Attention :</strong> Désactiver le 2FA réduira considérablement la sécurité de votre compte.
                    </div>
                    
                    <div class="mb-3">
                        <label for="pwd_disable" class="form-label">
                            Confirmez votre mot de passe :
                        </label>
                        <input 
                            type="password" 
                            class="form-control" 
                            id="pwd_disable" 
                            name="mot_de_passe" 
                            required
                            autocomplete="current-password"
                        >
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Annuler
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-shield-slash me-2"></i>
                        Désactiver le 2FA
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../partials/footer.php'; ?>
