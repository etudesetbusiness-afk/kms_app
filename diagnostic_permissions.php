<?php
/**
 * Script de diagnostic des permissions utilisateurs
 * KMS Gestion - 15 Décembre 2025
 * 
 * Identifie les utilisateurs sans rôles et sans permissions
 */

require_once __DIR__ . '/db/db.php';

echo "╔═══════════════════════════════════════════════════════════╗\n";
echo "║     DIAGNOSTIC PERMISSIONS UTILISATEURS                  ║\n";
echo "║     KMS Gestion - 15 Décembre 2025                        ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";

// 1. Liste des utilisateurs actifs avec leurs rôles
echo "📊 UTILISATEURS ACTIFS ET LEURS RÔLES\n";
echo str_repeat("═", 80) . "\n\n";

$sql = "
    SELECT 
        u.id,
        u.login,
        u.nom_complet,
        GROUP_CONCAT(r.code ORDER BY r.code SEPARATOR ', ') as roles,
        GROUP_CONCAT(r.nom ORDER BY r.nom SEPARATOR ', ') as roles_noms
    FROM utilisateurs u
    LEFT JOIN utilisateur_role ur ON u.id = ur.utilisateur_id
    LEFT JOIN roles r ON ur.role_id = r.id
    WHERE u.actif = 1
    GROUP BY u.id
    ORDER BY u.id
";

$stmt = $pdo->query($sql);
$utilisateurs = $stmt->fetchAll(PDO::FETCH_ASSOC);

$sans_role = [];
$avec_role = [];

foreach ($utilisateurs as $user) {
    if (empty($user['roles'])) {
        $sans_role[] = $user;
        echo "❌ ID {$user['id']}: {$user['login']} ({$user['nom_complet']}) - AUCUN RÔLE\n";
    } else {
        $avec_role[] = $user;
        echo "✅ ID {$user['id']}: {$user['login']} ({$user['nom_complet']}) - Rôles: {$user['roles']}\n";
    }
}

echo "\n";
echo str_repeat("═", 80) . "\n\n";

echo "📈 STATISTIQUES\n";
echo "──────────────────────────────────────────────────────────────────────\n";
echo "Total utilisateurs actifs: " . count($utilisateurs) . "\n";
echo "Avec rôles: " . count($avec_role) . " (✅)\n";
echo "Sans rôles: " . count($sans_role) . " (❌ PROBLÈME)\n\n";

// 2. Détail des permissions par rôle
echo "\n";
echo "🔐 PERMISSIONS PAR RÔLE\n";
echo str_repeat("═", 80) . "\n\n";

$sql = "
    SELECT 
        r.id,
        r.code,
        r.nom,
        r.description,
        GROUP_CONCAT(p.code ORDER BY p.code SEPARATOR ', ') as permissions
    FROM roles r
    LEFT JOIN role_permission rp ON r.id = rp.role_id
    LEFT JOIN permissions p ON rp.permission_id = p.id
    GROUP BY r.id
    ORDER BY r.id
";

$stmt = $pdo->query($sql);
$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($roles as $role) {
    echo "📌 {$role['code']} - {$role['nom']}\n";
    echo "   Description: " . ($role['description'] ?? 'N/A') . "\n";
    if ($role['permissions']) {
        $perms = explode(', ', $role['permissions']);
        echo "   Permissions (" . count($perms) . "): \n";
        foreach ($perms as $perm) {
            echo "      • $perm\n";
        }
    } else {
        echo "   ⚠️  AUCUNE PERMISSION ATTRIBUÉE\n";
    }
    echo "\n";
}

// 3. Analyse des problèmes
echo "\n";
echo "⚠️  PROBLÈMES IDENTIFIÉS\n";
echo str_repeat("═", 80) . "\n\n";

if (count($sans_role) > 0) {
    echo "🔴 CRITIQUE: " . count($sans_role) . " utilisateur(s) sans rôle\n";
    echo "   Ces utilisateurs ne peuvent accéder à aucune page.\n\n";
    
    echo "   Liste:\n";
    foreach ($sans_role as $user) {
        echo "      • ID {$user['id']}: {$user['login']} ({$user['nom_complet']})\n";
    }
    echo "\n";
}

// 4. Vérifier les permissions manquantes pour chaque rôle
echo "\n📋 PERMISSIONS MANQUANTES PAR RÔLE (SELON LOGIQUE MÉTIER)\n";
echo str_repeat("═", 80) . "\n\n";

$permissions_attendues = [
    'SHOWROOM' => [
        'PRODUITS_LIRE', 'CLIENTS_LIRE', 'CLIENTS_CREER',
        'DEVIS_LIRE', 'DEVIS_CREER', 'DEVIS_MODIFIER',
        'VENTES_LIRE', 'VENTES_CREER', 'SATISFACTION_GERER'
    ],
    'TERRAIN' => [
        'PRODUITS_LIRE', 'CLIENTS_LIRE', 'CLIENTS_CREER',
        'DEVIS_LIRE', 'DEVIS_CREER',
        'VENTES_LIRE', 'VENTES_CREER', 'SATISFACTION_GERER'
    ],
    'MAGASINIER' => [
        'PRODUITS_LIRE', 'PRODUITS_MODIFIER',
        'VENTES_LIRE', 'VENTES_VALIDER'
    ],
    'CAISSIER' => [
        'VENTES_LIRE',
        'CAISSE_LIRE', 'CAISSE_ECRIRE',
        'REPORTING_LIRE'
    ],
    'DIRECTION' => [
        'PRODUITS_LIRE', 'CLIENTS_LIRE',
        'DEVIS_LIRE', 'VENTES_LIRE',
        'CAISSE_LIRE', 'HOTEL_GERER', 'FORMATION_GERER',
        'REPORTING_LIRE', 'SATISFACTION_GERER', 'UTILISATEURS_GERER'
    ]
];

foreach ($roles as $role) {
    if ($role['code'] === 'ADMIN') continue; // Admin a tout
    
    $perms_actuelles = $role['permissions'] ? explode(', ', $role['permissions']) : [];
    $perms_requises = $permissions_attendues[$role['code']] ?? [];
    
    $manquantes = array_diff($perms_requises, $perms_actuelles);
    $en_trop = array_diff($perms_actuelles, $perms_requises);
    
    if (count($manquantes) > 0 || count($en_trop) > 0) {
        echo "⚠️  {$role['code']} - {$role['nom']}\n";
        
        if (count($manquantes) > 0) {
            echo "   ❌ Permissions manquantes:\n";
            foreach ($manquantes as $perm) {
                echo "      • $perm\n";
            }
        }
        
        if (count($en_trop) > 0) {
            echo "   ⚠️  Permissions en trop:\n";
            foreach ($en_trop as $perm) {
                echo "      • $perm\n";
            }
        }
        
        echo "\n";
    } else {
        echo "✅ {$role['code']} - {$role['nom']}: OK\n\n";
    }
}

// 5. Recommandations
echo "\n";
echo "💡 RECOMMANDATIONS\n";
echo str_repeat("═", 80) . "\n\n";

echo "Pour corriger les problèmes:\n\n";

echo "1. Attribuer des rôles aux utilisateurs sans rôle:\n";
foreach ($sans_role as $user) {
    echo "   -- Utilisateur: {$user['login']}\n";
    echo "   INSERT INTO utilisateur_role (utilisateur_id, role_id) VALUES ({$user['id']}, <role_id>);\n\n";
}

echo "2. Exécuter le script: fix_permissions_utilisateurs.php\n";
echo "   Ce script va:\n";
echo "   - Attribuer les permissions manquantes à chaque rôle\n";
echo "   - Assigner un rôle par défaut aux utilisateurs sans rôle\n\n";

echo "3. Tester la connexion avec chaque profil utilisateur\n\n";

echo "══════════════════════════════════════════════════════════════════════\n";
echo "Diagnostic terminé.\n";
