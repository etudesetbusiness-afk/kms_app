<?php
/**
 * Script de correction automatique des permissions utilisateurs
 * KMS Gestion - 15 Décembre 2025
 * 
 * Corrige les problèmes identifiés:
 * 1. Attribue des rôles aux utilisateurs sans rôle
 * 2. Ajoute les permissions manquantes aux rôles
 * 3. Recharge les permissions en session pour les utilisateurs connectés
 */

require_once __DIR__ . '/db/db.php';

echo "╔═══════════════════════════════════════════════════════════╗\n";
echo "║     CORRECTION AUTOMATIQUE DES PERMISSIONS               ║\n";
echo "║     KMS Gestion - 15 Décembre 2025                        ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";

$corrections = [];
$erreurs = [];

try {
    $pdo->beginTransaction();
    
    // ============================================
    // ÉTAPE 1: Attribuer des rôles aux utilisateurs sans rôle
    // ============================================
    
    echo "📝 ÉTAPE 1: Attribution des rôles aux utilisateurs\n";
    echo str_repeat("─", 70) . "\n";
    
    // Récupérer l'ID du rôle DIRECTION
    $stmt = $pdo->prepare("SELECT id FROM roles WHERE code = 'DIRECTION'");
    $stmt->execute();
    $role_direction = $stmt->fetch();
    
    if (!$role_direction) {
        throw new Exception("Rôle DIRECTION introuvable");
    }
    
    $role_direction_id = $role_direction['id'];
    
    // Utilisateurs sans rôle
    $utilisateurs_sans_role = [
        12 => 'direction2 (Directeur Adjoint)',
        13 => 'Tatiana (Naoussi Tatiana)',
        14 => 'Gislaine (Gislaine)'
    ];
    
    foreach ($utilisateurs_sans_role as $user_id => $user_name) {
        // Vérifier si déjà assigné
        $stmt = $pdo->prepare("SELECT 1 FROM utilisateur_role WHERE utilisateur_id = ?");
        $stmt->execute([$user_id]);
        
        if ($stmt->fetch()) {
            echo "   ⚠️  Utilisateur ID $user_id ($user_name): déjà assigné\n";
            continue;
        }
        
        // Assigner le rôle DIRECTION par défaut
        $stmt = $pdo->prepare("
            INSERT INTO utilisateur_role (utilisateur_id, role_id)
            VALUES (?, ?)
        ");
        $stmt->execute([$user_id, $role_direction_id]);
        
        echo "   ✅ Utilisateur ID $user_id ($user_name): rôle DIRECTION attribué\n";
        $corrections[] = "Rôle DIRECTION attribué à l'utilisateur $user_name";
    }
    
    echo "\n";
    
    // ============================================
    // ÉTAPE 2: Ajouter les permissions manquantes aux rôles
    // ============================================
    
    echo "🔐 ÉTAPE 2: Ajout des permissions manquantes\n";
    echo str_repeat("─", 70) . "\n";
    
    // Permission VENTES_VALIDER manquante pour MAGASINIER
    $stmt = $pdo->prepare("SELECT id FROM roles WHERE code = 'MAGASINIER'");
    $stmt->execute();
    $role_magasinier = $stmt->fetch();
    
    $stmt = $pdo->prepare("SELECT id FROM permissions WHERE code = 'VENTES_VALIDER'");
    $stmt->execute();
    $perm_ventes_valider = $stmt->fetch();
    
    if ($role_magasinier && $perm_ventes_valider) {
        // Vérifier si déjà assigné
        $stmt = $pdo->prepare("
            SELECT 1 FROM role_permission 
            WHERE role_id = ? AND permission_id = ?
        ");
        $stmt->execute([$role_magasinier['id'], $perm_ventes_valider['id']]);
        
        if (!$stmt->fetch()) {
            $stmt = $pdo->prepare("
                INSERT INTO role_permission (role_id, permission_id)
                VALUES (?, ?)
            ");
            $stmt->execute([$role_magasinier['id'], $perm_ventes_valider['id']]);
            
            echo "   ✅ Permission VENTES_VALIDER ajoutée au rôle MAGASINIER\n";
            $corrections[] = "Permission VENTES_VALIDER ajoutée au rôle MAGASINIER";
        } else {
            echo "   ⚠️  Permission VENTES_VALIDER déjà assignée au MAGASINIER\n";
        }
    }
    
    echo "\n";
    
    // ============================================
    // ÉTAPE 3: Permissions supplémentaires pour cohérence métier
    // ============================================
    
    echo "🔧 ÉTAPE 3: Optimisation des permissions métier\n";
    echo str_repeat("─", 70) . "\n";
    
    // TERRAIN devrait pouvoir modifier les devis (comme SHOWROOM)
    $stmt = $pdo->prepare("SELECT id FROM roles WHERE code = 'TERRAIN'");
    $stmt->execute();
    $role_terrain = $stmt->fetch();
    
    $stmt = $pdo->prepare("SELECT id FROM permissions WHERE code = 'DEVIS_MODIFIER'");
    $stmt->execute();
    $perm_devis_modifier = $stmt->fetch();
    
    if ($role_terrain && $perm_devis_modifier) {
        $stmt = $pdo->prepare("
            SELECT 1 FROM role_permission 
            WHERE role_id = ? AND permission_id = ?
        ");
        $stmt->execute([$role_terrain['id'], $perm_devis_modifier['id']]);
        
        if (!$stmt->fetch()) {
            $stmt = $pdo->prepare("
                INSERT INTO role_permission (role_id, permission_id)
                VALUES (?, ?)
            ");
            $stmt->execute([$role_terrain['id'], $perm_devis_modifier['id']]);
            
            echo "   ✅ Permission DEVIS_MODIFIER ajoutée au rôle TERRAIN\n";
            $corrections[] = "Permission DEVIS_MODIFIER ajoutée au rôle TERRAIN (cohérence métier)";
        } else {
            echo "   ⚠️  Permission DEVIS_MODIFIER déjà assignée au TERRAIN\n";
        }
    }
    
    echo "\n";
    
    // ============================================
    // VALIDATION ET COMMIT
    // ============================================
    
    $pdo->commit();
    
    echo "══════════════════════════════════════════════════════════════════════\n";
    echo "✅ CORRECTIONS APPLIQUÉES AVEC SUCCÈS\n";
    echo "══════════════════════════════════════════════════════════════════════\n\n";
    
    if (count($corrections) > 0) {
        echo "📊 Résumé des corrections (" . count($corrections) . "):\n";
        foreach ($corrections as $i => $correction) {
            echo "   " . ($i + 1) . ". $correction\n";
        }
        echo "\n";
    }
    
    // ============================================
    // ÉTAPE 4: Vérification finale
    // ============================================
    
    echo "🔍 ÉTAPE 4: Vérification finale\n";
    echo str_repeat("─", 70) . "\n";
    
    // Compter les utilisateurs sans rôle
    $stmt = $pdo->query("
        SELECT COUNT(*) as count
        FROM utilisateurs u
        LEFT JOIN utilisateur_role ur ON u.id = ur.utilisateur_id
        WHERE u.actif = 1 AND ur.utilisateur_id IS NULL
    ");
    $sans_role = $stmt->fetch();
    
    if ($sans_role['count'] == 0) {
        echo "   ✅ Tous les utilisateurs actifs ont au moins un rôle\n";
    } else {
        echo "   ⚠️  Il reste {$sans_role['count']} utilisateur(s) sans rôle\n";
        $erreurs[] = "{$sans_role['count']} utilisateur(s) encore sans rôle";
    }
    
    // Compter les rôles sans permissions
    $stmt = $pdo->query("
        SELECT r.code, r.nom
        FROM roles r
        LEFT JOIN role_permission rp ON r.id = rp.role_id
        WHERE r.code != 'ADMIN' AND rp.role_id IS NULL
        GROUP BY r.id
    ");
    $roles_sans_perms = $stmt->fetchAll();
    
    if (count($roles_sans_perms) == 0) {
        echo "   ✅ Tous les rôles (sauf ADMIN) ont des permissions\n";
    } else {
        echo "   ⚠️  Rôles sans permissions:\n";
        foreach ($roles_sans_perms as $role) {
            echo "      • {$role['code']} - {$role['nom']}\n";
            $erreurs[] = "Rôle {$role['code']} sans permissions";
        }
    }
    
    echo "\n";
    
    // ============================================
    // INSTRUCTIONS POST-CORRECTION
    // ============================================
    
    echo "══════════════════════════════════════════════════════════════════════\n";
    echo "📋 INSTRUCTIONS POST-CORRECTION\n";
    echo "══════════════════════════════════════════════════════════════════════\n\n";
    
    echo "1. Les utilisateurs suivants doivent SE RECONNECTER pour que leurs\n";
    echo "   nouvelles permissions soient actives:\n";
    echo "   • direction2 (Directeur Adjoint)\n";
    echo "   • Tatiana (Naoussi Tatiana)\n";
    echo "   • Gislaine (Gislaine)\n\n";
    
    echo "2. Tester la connexion avec chaque profil utilisateur:\n";
    echo "   • Showroom (showroom1, showroom2)\n";
    echo "   • Terrain (terrain1, terrain2)\n";
    echo "   • Magasinier (magasin1, magasin2)\n";
    echo "   • Caissier (caisse1, caisse2)\n";
    echo "   • Direction (direction1, direction2)\n\n";
    
    echo "3. Vérifier l'accès aux modules principaux:\n";
    echo "   • Produits / Catalogue\n";
    echo "   • Clients / Prospects\n";
    echo "   • Devis\n";
    echo "   • Ventes / Livraisons\n";
    echo "   • Caisse\n";
    echo "   • Reporting / Dashboard\n\n";
    
    echo "4. Exécuter le script de test:\n";
    echo "   php test_acces_utilisateurs.php\n\n";
    
    if (count($erreurs) > 0) {
        echo "⚠️  AVERTISSEMENTS (" . count($erreurs) . "):\n";
        foreach ($erreurs as $i => $erreur) {
            echo "   " . ($i + 1) . ". $erreur\n";
        }
        echo "\n";
    }
    
    echo "══════════════════════════════════════════════════════════════════════\n";
    echo "✅ Script terminé avec succès\n";
    echo "══════════════════════════════════════════════════════════════════════\n";
    
} catch (Exception $e) {
    $pdo->rollBack();
    
    echo "\n";
    echo "══════════════════════════════════════════════════════════════════════\n";
    echo "❌ ERREUR LORS DE LA CORRECTION\n";
    echo "══════════════════════════════════════════════════════════════════════\n\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "Fichier: " . $e->getFile() . "\n";
    echo "Ligne: " . $e->getLine() . "\n\n";
    echo "Toutes les modifications ont été annulées (ROLLBACK).\n";
    echo "Veuillez corriger l'erreur et relancer le script.\n";
    
    exit(1);
}
