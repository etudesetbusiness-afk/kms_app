<?php
/**
 * Configuration Email - KMS Gestion
 * 
 * Configuration SMTP pour Bluehost et autres hébergeurs
 * 
 * @version 1.0
 * @date 2025-12-20
 */

// ============================================================
// CONFIGURATION EMAIL SMTP - BLUEHOST
// ============================================================

define('EMAIL_SMTP_ENABLED', true);  // true = SMTP, false = mail()

// Configuration SMTP Bluehost
define('EMAIL_SMTP_HOST', 'mail.kennemulti-services.com');  // ou localhost sur Bluehost
define('EMAIL_SMTP_PORT', 465);      // 465 pour SSL, 587 pour TLS
define('EMAIL_SMTP_SECURE', 'ssl');  // 'ssl' ou 'tls'
define('EMAIL_SMTP_AUTH', true);

// Identifiants email Bluehost
define('EMAIL_SMTP_USERNAME', 'admin@kennemulti-services.com');  // Votre email créé dans cPanel
define('EMAIL_SMTP_PASSWORD', 'Kenne@#11');  // Mot de passe de cet email

// Expéditeur
define('EMAIL_FROM_ADDRESS', 'admin@kennemulti-services.com');
define('EMAIL_FROM_NAME', 'KMS Gestion');

// ============================================================
// CLASSE D'ENVOI EMAIL SMTP
// ============================================================

class EmailSender {
    
    /**
     * Envoyer un email via SMTP (compatible Bluehost)
     */
    public static function send($to, $subject, $htmlBody, $textBody = '') {
        if (!EMAIL_SMTP_ENABLED) {
            return self::sendViaMail($to, $subject, $htmlBody);
        }
        
        return self::sendViaSMTP($to, $subject, $htmlBody, $textBody);
    }
    
    /**
     * Envoi via SMTP natif PHP (sans PHPMailer)
     */
    private static function sendViaSMTP($to, $subject, $htmlBody, $textBody = '') {
        try {
            $boundary = md5(time());
            
            // Connexion au serveur SMTP
            $socket = self::connectSMTP();
            if (!$socket) {
                error_log("SMTP: Impossible de se connecter au serveur");
                return self::sendViaMail($to, $subject, $htmlBody); // Fallback
            }
            
            // Commandes SMTP
            self::smtpCommand($socket, "EHLO " . gethostname());
            
            if (EMAIL_SMTP_AUTH) {
                self::smtpCommand($socket, "AUTH LOGIN");
                self::smtpCommand($socket, base64_encode(EMAIL_SMTP_USERNAME));
                self::smtpCommand($socket, base64_encode(EMAIL_SMTP_PASSWORD));
            }
            
            self::smtpCommand($socket, "MAIL FROM: <" . EMAIL_FROM_ADDRESS . ">");
            self::smtpCommand($socket, "RCPT TO: <$to>");
            self::smtpCommand($socket, "DATA");
            
            // Construction du message
            $headers = "From: " . EMAIL_FROM_NAME . " <" . EMAIL_FROM_ADDRESS . ">\r\n";
            $headers .= "To: $to\r\n";
            $headers .= "Subject: $subject\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: multipart/alternative; boundary=\"$boundary\"\r\n";
            $headers .= "X-Mailer: KMS-Gestion/1.0\r\n";
            $headers .= "\r\n";
            
            $message = $headers;
            
            // Version texte
            if (empty($textBody)) {
                $textBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));
            }
            $message .= "--$boundary\r\n";
            $message .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
            $message .= $textBody . "\r\n";
            
            // Version HTML
            $message .= "--$boundary\r\n";
            $message .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
            $message .= $htmlBody . "\r\n";
            $message .= "--$boundary--\r\n";
            $message .= ".\r\n";
            
            fwrite($socket, $message);
            $response = fgets($socket, 256);
            
            self::smtpCommand($socket, "QUIT");
            fclose($socket);
            
            if (strpos($response, '250') !== false || strpos($response, '354') !== false) {
                error_log("SMTP: Email envoyé avec succès à $to");
                return ['success' => true, 'message' => 'Email envoyé'];
            } else {
                error_log("SMTP: Erreur envoi - $response");
                return self::sendViaMail($to, $subject, $htmlBody); // Fallback
            }
            
        } catch (Exception $e) {
            error_log("SMTP Exception: " . $e->getMessage());
            return self::sendViaMail($to, $subject, $htmlBody); // Fallback
        }
    }
    
    /**
     * Connexion au serveur SMTP
     */
    private static function connectSMTP() {
        $host = EMAIL_SMTP_HOST;
        if (EMAIL_SMTP_SECURE === 'ssl') {
            $host = 'ssl://' . $host;
        }
        
        $socket = @fsockopen($host, EMAIL_SMTP_PORT, $errno, $errstr, 30);
        if (!$socket) {
            error_log("SMTP Connect Error: $errstr ($errno)");
            return false;
        }
        
        // Lire le message de bienvenue
        fgets($socket, 256);
        
        // Si TLS, démarrer le chiffrement après connexion
        if (EMAIL_SMTP_SECURE === 'tls') {
            self::smtpCommand($socket, "STARTTLS");
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        }
        
        return $socket;
    }
    
    /**
     * Envoyer une commande SMTP
     */
    private static function smtpCommand($socket, $command) {
        fwrite($socket, $command . "\r\n");
        return fgets($socket, 256);
    }
    
    /**
     * Fallback: envoi via mail() natif PHP
     */
    private static function sendViaMail($to, $subject, $htmlBody) {
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: " . EMAIL_FROM_NAME . " <" . EMAIL_FROM_ADDRESS . ">\r\n";
        $headers .= "X-Mailer: KMS-Gestion/1.0";
        
        $sent = @mail($to, $subject, $htmlBody, $headers);
        
        if ($sent) {
            error_log("Mail(): Email envoyé à $to");
            return ['success' => true, 'message' => 'Email envoyé'];
        } else {
            error_log("Mail(): Échec envoi à $to");
            return ['success' => false, 'message' => 'Échec envoi email'];
        }
    }
    
    /**
     * Tester la connexion SMTP
     */
    public static function testConnection() {
        if (!EMAIL_SMTP_ENABLED) {
            return ['success' => true, 'message' => 'Mode mail() actif'];
        }
        
        $socket = self::connectSMTP();
        if ($socket) {
            self::smtpCommand($socket, "EHLO " . gethostname());
            
            if (EMAIL_SMTP_AUTH) {
                $authResult = self::smtpCommand($socket, "AUTH LOGIN");
                self::smtpCommand($socket, base64_encode(EMAIL_SMTP_USERNAME));
                $passResult = self::smtpCommand($socket, base64_encode(EMAIL_SMTP_PASSWORD));
                
                if (strpos($passResult, '235') !== false) {
                    fclose($socket);
                    return ['success' => true, 'message' => 'Connexion SMTP OK'];
                } else {
                    fclose($socket);
                    return ['success' => false, 'message' => 'Authentification SMTP échouée'];
                }
            }
            
            fclose($socket);
            return ['success' => true, 'message' => 'Connexion SMTP OK'];
        }
        
        return ['success' => false, 'message' => 'Impossible de se connecter au serveur SMTP'];
    }
}
