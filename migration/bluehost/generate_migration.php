<?php
/**
 * Script de génération du fichier SQL de migration pour Bluehost
 * 
 * Ce script lit le fichier kms_gestion.sql et génère une version compatible
 * avec les hébergements mutualisés (sans privilèges SUPER requis).
 * 
 * Usage : 
 *   - CLI: php generate_migration.php
 *   - WEB: http://localhost/kms_app/migration/bluehost/generate_migration.php
 * 
 * @author KMS Gestion
 * @version 1.1
 */

// Mode web ou CLI
$isWeb = php_sapi_name() !== 'cli';
if ($isWeb) {
    header('Content-Type: text/plain; charset=utf-8');
}

$sourceFile = __DIR__ . '/../../kms_gestion.sql';
$outputFile = __DIR__ . '/migration_kms_gestion.sql';

if (!file_exists($sourceFile)) {
    die("❌ Fichier source non trouvé: $sourceFile\n");
}

echo "🔄 Lecture du fichier source...\n";
$sql = file_get_contents($sourceFile);

// Compteurs pour le rapport
$stats = [
    'procedures_removed' => 0,
    'triggers_removed' => 0,
    'views_cleaned' => 0,
    'definers_removed' => 0,
];

// ============================================================
// 1. SUPPRIMER LES PROCÉDURES STOCKÉES
// ============================================================
echo "🔧 Suppression des procédures stockées...\n";

// Pattern pour les procédures avec DEFINER (seulement la procédure, pas les triggers qui suivent)
$sql = preg_replace_callback(
    '/DELIMITER \$\$\s*\n--\s*\n-- Procedures\s*\n--\s*\nCREATE DEFINER=[^\n]+BEGIN[\s\S]*?END\$\$\s*\nDELIMITER ;/s',
    function($matches) use (&$stats) {
        $stats['procedures_removed']++;
        return "-- ============================================================\n" .
               "-- PROCÉDURES STOCKÉES SUPPRIMÉES (incompatibles hébergement mutualisé)\n" .
               "-- Les fonctionnalités sont gérées côté PHP dans lib/cleanup_sms.php\n" .
               "-- ============================================================\n";
    },
    $sql
);

// ============================================================
// 2. SUPPRIMER LES TRIGGERS (UN PAR UN)
// ============================================================
echo "🔧 Suppression des triggers...\n";

// Supprimer TOUS les blocs DELIMITER $$ contenant CREATE TRIGGER, un par un
$count = 0;
$pattern = '/DELIMITER \$\$\s*\nCREATE TRIGGER[\s\S]*?END\s*\$\$\s*\nDELIMITER ;\s*\n?/';
while (preg_match($pattern, $sql)) {
    $sql = preg_replace($pattern, '', $sql, 1);
    $count++;
    if ($count > 10) break; // Sécurité anti-boucle infinie
}
$stats['triggers_removed'] = $count;

// Supprimer les sections headers vides "-- Triggers `xxx`"
$sql = preg_replace(
    '/--\s*\n-- Triggers `[^`]+`\s*\n--\s*\n\n?(?=--|\n)/s',
    '',
    $sql
);

// ============================================================
// 3. NETTOYER LES VUES (retirer DEFINER)
// ============================================================
echo "🔧 Nettoyage des vues (suppression DEFINER)...\n";

// Remplacer DEFINER=`root`@`localhost` par rien
$sql = preg_replace_callback(
    '/CREATE\s+ALGORITHM=UNDEFINED\s+DEFINER=`[^`]+`@`[^`]+`\s+SQL\s+SECURITY\s+DEFINER\s+VIEW/i',
    function($matches) use (&$stats) {
        $stats['views_cleaned']++;
        return 'CREATE VIEW';
    },
    $sql
);

// ============================================================
// 4. SUPPRIMER TOUS LES DEFINER RESTANTS (sécurité)
// ============================================================
echo "🔧 Suppression des DEFINER restants...\n";

$sql = preg_replace_callback(
    '/DEFINER=`[^`]+`@`[^`]+`\s*/i',
    function($matches) use (&$stats) {
        $stats['definers_removed']++;
        return '';
    },
    $sql
);

// ============================================================
// 5. AJOUTER EN-TÊTE DE MIGRATION
// ============================================================
$header = <<<SQL
-- ============================================================
-- MIGRATION KMS GESTION - VERSION BLUEHOST COMPATIBLE
-- ============================================================
-- 
-- Ce fichier a été généré automatiquement pour être compatible
-- avec les hébergements mutualisés (Bluehost, cPanel, etc.)
-- 
-- Modifications apportées :
-- - Procédures stockées supprimées (gérées côté PHP)
-- - Triggers supprimés (gérées côté PHP) 
-- - DEFINER retirés des vues
-- - Aucun privilège SUPER requis
--
-- Date de génération : %s
-- Source : kms_gestion.sql
--
-- INSTRUCTIONS :
-- 1. Créer la base de données dans cPanel
-- 2. Créer un utilisateur MySQL et lui attribuer TOUS les privilèges
-- 3. Importer ce fichier via phpMyAdmin
-- 4. Mettre à jour db/db.php avec les nouveaux identifiants
--
-- ============================================================


SQL;

$sql = sprintf($header, date('Y-m-d H:i:s')) . $sql;

// ============================================================
// 6. ÉCRIRE LE FICHIER DE SORTIE
// ============================================================
echo "💾 Écriture du fichier de migration...\n";

if (file_put_contents($outputFile, $sql) === false) {
    die("❌ Erreur lors de l'écriture du fichier: $outputFile\n");
}

// ============================================================
// RAPPORT
// ============================================================
echo "\n✅ Migration générée avec succès!\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📄 Fichier créé: $outputFile\n";
echo "📊 Statistiques:\n";
echo "   • Procédures supprimées: {$stats['procedures_removed']}\n";
echo "   • Triggers supprimés: {$stats['triggers_removed']}\n";
echo "   • Vues nettoyées: {$stats['views_cleaned']}\n";
echo "   • DEFINER supprimés: {$stats['definers_removed']}\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "\n📋 Prochaines étapes:\n";
echo "1. Vérifier le fichier migration_kms_gestion.sql\n";
echo "2. Lire README.md pour les instructions d'import\n";
echo "3. Créer le fichier config-db-migration.php à partir de l'exemple\n";
echo "\n";
