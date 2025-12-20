<?php
/**
 * Configuration de connexion à la base de données - Bluehost Production
 * 
 * Ce fichier est prêt à être déployé sur Bluehost.
 * Copiez-le vers : db/db.php sur le serveur Bluehost
 * 
 * @version Migration Bluehost 1.0
 * @date 2025-12-20
 */

// ============================================================
// CONFIGURATION BLUEHOST
// ============================================================

$host     = 'localhost';
$dbname   = 'kdfvxvmy_kms_gestion';
$username = 'kdfvxvmy_WPEUF';
$password = 'adminKMs_app#2025';
$charset  = 'utf8mb4';

// ============================================================
// CONNEXION PDO
// ============================================================

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=$charset",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
    
} catch (PDOException $e) {
    // Production : logger l'erreur sans exposer les détails
    error_log("Erreur BDD KMS: " . $e->getMessage());
    die("Erreur de connexion à la base de données. Contactez l'administrateur.");
}

// Fuseau horaire Cameroun (UTC+1)
$pdo->exec("SET time_zone = '+01:00'");
