<?php
/**
 * KMS Gestion - Authentification à Deux Facteurs (2FA)
 * 
 * Implémentation TOTP (Time-based One-Time Password) compatible Google Authenticator
 */

class TwoFactorAuth
{
    private const SECRET_LENGTH = 32;
    private const CODE_LENGTH = 6;
    private const TIME_STEP = 30; // secondes

    /**
     * Génère un secret pour un nouvel utilisateur
     */
    public static function generateSecret(): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; // Base32
        $secret = '';
        
        for ($i = 0; $i < self::SECRET_LENGTH; $i++) {
            $secret .= $chars[random_int(0, strlen($chars) - 1)];
        }
        
        return $secret;
    }

    /**
     * Génère un code TOTP pour un secret donné
     */
    public static function generateCode(string $secret, ?int $timestamp = null): string
    {
        if ($timestamp === null) {
            $timestamp = time();
        }

        $timeCounter = floor($timestamp / self::TIME_STEP);
        $secretKey = self::base32Decode($secret);
        
        // Pack le compteur en binaire
        $time = pack('N*', 0) . pack('N*', $timeCounter);
        
        // HMAC hash
        $hash = hash_hmac('sha1', $time, $secretKey, true);
        
        // Extraction dynamique
        $offset = ord($hash[19]) & 0xf;
        $code = (
            ((ord($hash[$offset]) & 0x7f) << 24) |
            ((ord($hash[$offset + 1]) & 0xff) << 16) |
            ((ord($hash[$offset + 2]) & 0xff) << 8) |
            (ord($hash[$offset + 3]) & 0xff)
        ) % pow(10, self::CODE_LENGTH);
        
        return str_pad((string)$code, self::CODE_LENGTH, '0', STR_PAD_LEFT);
    }

    /**
     * Vérifie un code TOTP
     * 
     * @param string $secret Secret de l'utilisateur
     * @param string $code Code fourni par l'utilisateur
     * @param int $discrepancy Nombre de fenêtres de temps à vérifier (tolérance)
     * @return bool
     */
    public static function verifyCode(string $secret, string $code, int $discrepancy = 1): bool
    {
        $timestamp = time();
        
        // Vérifie le code actuel et les fenêtres adjacentes (tolérance de décalage d'horloge)
        for ($i = -$discrepancy; $i <= $discrepancy; $i++) {
            $testTime = $timestamp + ($i * self::TIME_STEP);
            $testCode = self::generateCode($secret, $testTime);
            
            if (hash_equals($testCode, $code)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Génère une URL pour QR code (compatible Google Authenticator)
     */
    public static function getQrCodeUrl(string $secret, string $label, string $issuer = 'KMS Gestion'): string
    {
        $params = http_build_query([
            'secret' => $secret,
            'issuer' => $issuer,
            'algorithm' => 'SHA1',
            'digits' => self::CODE_LENGTH,
            'period' => self::TIME_STEP
        ]);
        
        $label = rawurlencode($label);
        $issuer = rawurlencode($issuer);
        
        return "otpauth://totp/{$issuer}:{$label}?{$params}";
    }

    /**
     * Génère l'URL d'image du QR code via une API externe
     */
    public static function getQrCodeImageUrl(string $secret, string $label, string $issuer = 'KMS Gestion'): string
    {
        $otpUrl = self::getQrCodeUrl($secret, $label, $issuer);
        $encodedUrl = urlencode($otpUrl);
        
        // Utilise l'API Google Charts (ou vous pouvez utiliser une lib PHP comme endroid/qr-code)
        return "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={$encodedUrl}";
    }

    /**
     * Active le 2FA pour un utilisateur
     */
    public static function enableForUser(PDO $pdo, int $userId, string $secret): bool
    {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO utilisateurs_2fa (utilisateur_id, secret, actif, date_activation)
                VALUES (?, ?, 1, NOW())
                ON DUPLICATE KEY UPDATE
                    secret = VALUES(secret),
                    actif = 1,
                    date_activation = NOW()
            ");
            
            return $stmt->execute([$userId, $secret]);
        } catch (PDOException $e) {
            error_log("Erreur activation 2FA: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Désactive le 2FA pour un utilisateur
     */
    public static function disableForUser(PDO $pdo, int $userId): bool
    {
        try {
            $stmt = $pdo->prepare("
                UPDATE utilisateurs_2fa 
                SET actif = 0, date_desactivation = NOW()
                WHERE utilisateur_id = ?
            ");
            
            return $stmt->execute([$userId]);
        } catch (PDOException $e) {
            error_log("Erreur désactivation 2FA: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Vérifie si le 2FA est activé pour un utilisateur
     */
    public static function isEnabledForUser(PDO $pdo, int $userId): bool
    {
        try {
            $stmt = $pdo->prepare("
                SELECT actif FROM utilisateurs_2fa 
                WHERE utilisateur_id = ? AND actif = 1
            ");
            $stmt->execute([$userId]);
            
            return $stmt->fetchColumn() === 1;
        } catch (PDOException $e) {
            error_log("Erreur vérification 2FA: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère le secret 2FA d'un utilisateur
     */
    public static function getUserSecret(PDO $pdo, int $userId): ?string
    {
        try {
            $stmt = $pdo->prepare("
                SELECT secret FROM utilisateurs_2fa 
                WHERE utilisateur_id = ? AND actif = 1
            ");
            $stmt->execute([$userId]);
            
            $secret = $stmt->fetchColumn();
            return $secret ?: null;
        } catch (PDOException $e) {
            error_log("Erreur récupération secret 2FA: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Génère des codes de récupération
     */
    public static function generateRecoveryCodes(int $count = 10): array
    {
        $codes = [];
        
        for ($i = 0; $i < $count; $i++) {
            $code = sprintf(
                '%04d-%04d-%04d',
                random_int(0, 9999),
                random_int(0, 9999),
                random_int(0, 9999)
            );
            $codes[] = $code;
        }
        
        return $codes;
    }

    /**
     * Stocke les codes de récupération
     */
    public static function saveRecoveryCodes(PDO $pdo, int $userId, array $codes): bool
    {
        try {
            $pdo->beginTransaction();
            
            // Supprime les anciens codes
            $stmt = $pdo->prepare("DELETE FROM utilisateurs_2fa_recovery WHERE utilisateur_id = ?");
            $stmt->execute([$userId]);
            
            // Insère les nouveaux
            $stmt = $pdo->prepare("
                INSERT INTO utilisateurs_2fa_recovery (utilisateur_id, code_hash, date_creation)
                VALUES (?, ?, NOW())
            ");
            
            foreach ($codes as $code) {
                $hash = password_hash($code, PASSWORD_DEFAULT);
                $stmt->execute([$userId, $hash]);
            }
            
            $pdo->commit();
            return true;
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Erreur sauvegarde codes récupération: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Vérifie et utilise un code de récupération
     */
    public static function verifyRecoveryCode(PDO $pdo, int $userId, string $code): bool
    {
        try {
            $stmt = $pdo->prepare("
                SELECT id, code_hash FROM utilisateurs_2fa_recovery 
                WHERE utilisateur_id = ? AND utilise = 0
            ");
            $stmt->execute([$userId]);
            $codes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($codes as $row) {
                if (password_verify($code, $row['code_hash'])) {
                    // Marque le code comme utilisé
                    $updateStmt = $pdo->prepare("
                        UPDATE utilisateurs_2fa_recovery 
                        SET utilise = 1, date_utilisation = NOW()
                        WHERE id = ?
                    ");
                    $updateStmt->execute([$row['id']]);
                    
                    return true;
                }
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Erreur vérification code récupération: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Décodage Base32
     */
    private static function base32Decode(string $secret): string
    {
        $base32chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $base32charsFlipped = array_flip(str_split($base32chars));
        
        $paddingCharCount = substr_count($secret, '=');
        $allowedValues = [6, 4, 3, 1, 0];
        
        if (!in_array($paddingCharCount, $allowedValues)) {
            return '';
        }
        
        for ($i = 0; $i < 4; $i++) {
            if ($paddingCharCount == $allowedValues[$i] &&
                substr($secret, -($allowedValues[$i])) != str_repeat('=', $allowedValues[$i])) {
                return '';
            }
        }
        
        $secret = str_replace('=', '', $secret);
        $secret = str_split($secret);
        $binaryString = '';
        
        for ($i = 0; $i < count($secret); $i += 8) {
            $x = '';
            for ($j = 0; $j < 8; $j++) {
                if (!isset($secret[$i + $j]) || !isset($base32charsFlipped[$secret[$i + $j]])) {
                    $x .= '00000';
                } else {
                    $x .= str_pad(decbin($base32charsFlipped[$secret[$i + $j]]), 5, '0', STR_PAD_LEFT);
                }
            }
            
            $eightBits = str_split($x, 8);
            
            for ($z = 0; $z < count($eightBits); $z++) {
                $binaryString .= (($y = chr(bindec($eightBits[$z]))) || ord($y) == 48) ? $y : '';
            }
        }
        
        return $binaryString;
    }
}
