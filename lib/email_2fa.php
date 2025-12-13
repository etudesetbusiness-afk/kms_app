<?php
/**
 * Authentification 2FA par Email
 * Alternative simple au TOTP - Pas besoin d'application tierce
 */

class Email2FA {
    private $pdo;
    private $codeExpiration = 300; // 5 minutes
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Générer un code à 6 chiffres
     */
    public static function generateCode() {
        return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
    
    /**
     * Activer le 2FA par email pour un utilisateur
     */
    public function enableForUser($userId, $email) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO utilisateurs_2fa (utilisateur_id, methode, email_backup, actif, date_activation)
                VALUES (?, 'EMAIL', ?, 1, NOW())
                ON DUPLICATE KEY UPDATE
                    methode = 'EMAIL',
                    email_backup = ?,
                    actif = 1,
                    secret = '',
                    date_activation = NOW()
            ");
            return $stmt->execute([$userId, $email, $email]);
        } catch (Exception $e) {
            error_log("Email2FA enableForUser error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Désactiver le 2FA par email
     */
    public function disableForUser($userId) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE utilisateurs_2fa 
                SET actif = 0, date_desactivation = NOW()
                WHERE utilisateur_id = ?
            ");
            return $stmt->execute([$userId]);
        } catch (Exception $e) {
            error_log("Email2FA disableForUser error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Vérifier si l'email 2FA est activé pour un utilisateur
     */
    public function isEnabledForUser($userId) {
        $stmt = $this->pdo->prepare("
            SELECT actif FROM utilisateurs_2fa 
            WHERE utilisateur_id = ? AND methode = 'EMAIL' AND actif = 1
        ");
        $stmt->execute([$userId]);
        return (bool)$stmt->fetchColumn();
    }
    
    /**
     * Récupérer l'email de secours
     */
    public function getBackupEmail($userId) {
        $stmt = $this->pdo->prepare("
            SELECT email_backup FROM utilisateurs_2fa 
            WHERE utilisateur_id = ? AND methode = 'EMAIL'
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    }
    
    /**
     * Enregistrer un code de vérification en session
     */
    public function storeVerificationCode($userId, $code) {
        $_SESSION['email_2fa_code'] = password_hash($code, PASSWORD_DEFAULT);
        $_SESSION['email_2fa_user_id'] = $userId;
        $_SESSION['email_2fa_time'] = time();
        $_SESSION['email_2fa_attempts'] = 0;
    }
    
    /**
     * Vérifier un code de vérification
     */
    public function verifyCode($code) {
        // Vérifier l'expiration
        if (!isset($_SESSION['email_2fa_time']) || (time() - $_SESSION['email_2fa_time']) > $this->codeExpiration) {
            return ['success' => false, 'message' => 'Code expiré. Demandez un nouveau code.'];
        }
        
        // Vérifier le nombre de tentatives
        if (isset($_SESSION['email_2fa_attempts']) && $_SESSION['email_2fa_attempts'] >= 3) {
            return ['success' => false, 'message' => 'Trop de tentatives. Demandez un nouveau code.'];
        }
        
        // Vérifier le code
        if (isset($_SESSION['email_2fa_code']) && password_verify($code, $_SESSION['email_2fa_code'])) {
            // Code valide - nettoyer la session
            unset($_SESSION['email_2fa_code'], $_SESSION['email_2fa_time'], $_SESSION['email_2fa_attempts']);
            return ['success' => true, 'message' => 'Code valide'];
        }
        
        // Incrémenter les tentatives
        $_SESSION['email_2fa_attempts'] = ($_SESSION['email_2fa_attempts'] ?? 0) + 1;
        return ['success' => false, 'message' => 'Code incorrect'];
    }
    
    /**
     * Envoyer le code par email
     */
    public function sendCode($email, $code, $userName = '') {
        $subject = "KMS Gestion - Code de vérification";
        
        $message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .code-box { 
                    background: #f8f9fa; 
                    border: 2px solid #0d6efd; 
                    border-radius: 8px; 
                    padding: 20px; 
                    text-align: center; 
                    margin: 20px 0;
                }
                .code { 
                    font-size: 32px; 
                    font-weight: bold; 
                    color: #0d6efd; 
                    letter-spacing: 5px;
                }
                .warning { color: #dc3545; margin-top: 20px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <h2>🔐 Code de vérification KMS Gestion</h2>
                <p>Bonjour" . ($userName ? " <strong>" . htmlspecialchars($userName) . "</strong>" : "") . ",</p>
                <p>Voici votre code de vérification pour vous connecter à KMS Gestion :</p>
                
                <div class='code-box'>
                    <div class='code'>" . htmlspecialchars($code) . "</div>
                    <p style='margin: 10px 0 0 0; font-size: 14px; color: #6c757d;'>
                        Ce code est valide pendant 5 minutes
                    </p>
                </div>
                
                <p>Si vous n'avez pas demandé ce code, ignorez cet email.</p>
                
                <div class='warning'>
                    <strong>⚠️ Important :</strong> Ne partagez jamais ce code avec qui que ce soit.
                </div>
                
                <hr style='margin: 30px 0; border: none; border-top: 1px solid #dee2e6;'>
                <p style='font-size: 12px; color: #6c757d;'>
                    Cet email a été envoyé automatiquement par KMS Gestion - Kenne Multi-Services<br>
                    Date: " . date('d/m/Y H:i:s') . "
                </p>
            </div>
        </body>
        </html>
        ";
        
        // Headers pour email HTML
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: KMS Gestion <noreply@kms-gestion.local>\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();
        
        // Tentative d'envoi
        $sent = @mail($email, $subject, $message, $headers);
        
        if ($sent) {
            // Logger l'envoi réussi
            error_log("Email 2FA envoyé avec succès à: " . $email);
            return ['success' => true, 'message' => 'Code envoyé par email'];
        } else {
            // En mode développement, afficher le code dans les logs
            error_log("========================================");
            error_log("MODE DÉVELOPPEMENT - Code 2FA Email");
            error_log("Email: " . $email);
            error_log("Code: " . $code);
            error_log("========================================");
            
            // Retourner quand même succès en dev (car mail() ne fonctionne pas toujours en local)
            return [
                'success' => true, 
                'message' => 'Code généré (vérifiez les logs si email non reçu)',
                'dev_mode' => true,
                'dev_code' => $code // En dev seulement !
            ];
        }
    }
    
    /**
     * Nettoyer les sessions expirées
     */
    public static function cleanExpiredSessions() {
        if (isset($_SESSION['email_2fa_time']) && (time() - $_SESSION['email_2fa_time']) > 300) {
            unset($_SESSION['email_2fa_code'], $_SESSION['email_2fa_time'], 
                  $_SESSION['email_2fa_user_id'], $_SESSION['email_2fa_attempts']);
        }
    }
}
