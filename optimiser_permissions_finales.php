<?php
/**
 * Script d'optimisation finale des permissions
 * KMS Gestion - 15 Décembre 2025
 * 
 * Ajoute les permissions manquantes selon la logique métier:
 * - Showroom et Terrain devraient pouvoir consulter le dashboard pour voir leurs performances
 * - Magasinier devrait voir le dashboard pour les stocks
 */

require_once __DIR__ . '/db/db.php';

echo "╔═══════════════════════════════════════════════════════════╗\n";
echo "║     OPTIMISATION FINALE DES PERMISSIONS MÉTIER           ║\n";
echo "║     KMS Gestion - 15 Décembre 2025                        ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";

try {
    $pdo->beginTransaction();
    
    $corrections = [];
    
    // ============================================
    // AJOUTS DE PERMISSIONS SELON LOGIQUE MÉTIER
    // ============================================
    
    echo "🔧 OPTIMISATIONS MÉTIER\n";
    echo str_repeat("─", 70) . "\n\n";
    
    // Configuration des permissions à ajouter
    $permissions_a_ajouter = [
        'SHOWROOM' => [
            'REPORTING_LIRE' // Pour consulter leur CA et performances
        ],
        'TERRAIN' => [
            'REPORTING_LIRE' // Pour consulter leur CA et performances  
        ],
        'MAGASINIER' => [
            'REPORTING_LIRE' // Pour consulter les niveaux de stock
        ]
    ];
    
    foreach ($permissions_a_ajouter as $role_code => $permissions) {
        // Récupérer l'ID du rôle
        $stmt = $pdo->prepare("SELECT id FROM roles WHERE code = ?");
        $stmt->execute([$role_code]);
        $role = $stmt->fetch();
        
        if (!$role) {
            echo "   ⚠️  Rôle $role_code introuvable\n";
            continue;
        }
        
        $role_id = $role['id'];
        
        foreach ($permissions as $perm_code) {
            // Récupérer l'ID de la permission
            $stmt = $pdo->prepare("SELECT id FROM permissions WHERE code = ?");
            $stmt->execute([$perm_code]);
            $perm = $stmt->fetch();
            
            if (!$perm) {
                echo "   ⚠️  Permission $perm_code introuvable\n";
                continue;
            }
            
            $perm_id = $perm['id'];
            
            // Vérifier si déjà assignée
            $stmt = $pdo->prepare("
                SELECT 1 FROM role_permission 
                WHERE role_id = ? AND permission_id = ?
            ");
            $stmt->execute([$role_id, $perm_id]);
            
            if ($stmt->fetch()) {
                echo "   ⚠️  $role_code → $perm_code: déjà assignée\n";
                continue;
            }
            
            // Ajouter la permission
            $stmt = $pdo->prepare("
                INSERT INTO role_permission (role_id, permission_id)
                VALUES (?, ?)
            ");
            $stmt->execute([$role_id, $perm_id]);
            
            echo "   ✅ $role_code → $perm_code: ajoutée\n";
            $corrections[] = "Permission $perm_code ajoutée au rôle $role_code";
        }
    }
    
    echo "\n";
    
    $pdo->commit();
    
    echo "══════════════════════════════════════════════════════════════════════\n";
    echo "✅ OPTIMISATIONS APPLIQUÉES AVEC SUCCÈS\n";
    echo "══════════════════════════════════════════════════════════════════════\n\n";
    
    if (count($corrections) > 0) {
        echo "📊 Résumé (" . count($corrections) . " modification(s)):\n";
        foreach ($corrections as $i => $correction) {
            echo "   " . ($i + 1) . ". $correction\n";
        }
        echo "\n";
    } else {
        echo "✅ Toutes les permissions étaient déjà optimales\n\n";
    }
    
    // Résumé final des permissions par rôle
    echo "📋 PERMISSIONS FINALES PAR RÔLE\n";
    echo str_repeat("═", 80) . "\n\n";
    
    $sql = "
        SELECT 
            r.code,
            r.nom,
            GROUP_CONCAT(p.code ORDER BY p.code SEPARATOR ', ') as permissions,
            COUNT(p.id) as nb_permissions
        FROM roles r
        LEFT JOIN role_permission rp ON r.id = rp.role_id
        LEFT JOIN permissions p ON rp.permission_id = p.id
        GROUP BY r.id
        ORDER BY r.id
    ";
    
    $stmt = $pdo->query($sql);
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($roles as $role) {
        echo "📌 {$role['code']} - {$role['nom']} ({$role['nb_permissions']} permissions)\n";
        if ($role['permissions']) {
            $perms = explode(', ', $role['permissions']);
            foreach ($perms as $perm) {
                echo "   • $perm\n";
            }
        }
        echo "\n";
    }
    
    echo "══════════════════════════════════════════════════════════════════════\n";
    echo "✅ Optimisation terminée\n";
    echo "══════════════════════════════════════════════════════════════════════\n\n";
    
    echo "📝 PROCHAINES ÉTAPES:\n";
    echo "1. Relancer le test: php test_acces_utilisateurs.php\n";
    echo "2. Tester manuellement avec chaque compte utilisateur\n";
    echo "3. Les utilisateurs déjà connectés doivent se reconnecter\n\n";
    
} catch (Exception $e) {
    $pdo->rollBack();
    
    echo "\n══════════════════════════════════════════════════════════════════════\n";
    echo "❌ ERREUR\n";
    echo "══════════════════════════════════════════════════════════════════════\n";
    echo "Message: " . $e->getMessage() . "\n";
    exit(1);
}
