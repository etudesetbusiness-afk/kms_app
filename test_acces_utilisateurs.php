<?php
/**
 * Script de test des accès utilisateurs
 * KMS Gestion - 15 Décembre 2025
 * 
 * Teste l'accès aux pages principales pour chaque rôle utilisateur
 * Identifie les pages qui retournent "Accès refusé" abusivement
 */

require_once __DIR__ . '/db/db.php';
require_once __DIR__ . '/security.php';

echo "╔═══════════════════════════════════════════════════════════╗\n";
echo "║     TEST DES ACCÈS UTILISATEURS PAR RÔLE                 ║\n";
echo "║     KMS Gestion - 15 Décembre 2025                        ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";

// Pages à tester par module avec permissions requises
$pages_a_tester = [
    'Produits / Catalogue' => [
        ['url' => 'produits/list.php', 'permission' => 'PRODUITS_LIRE', 'description' => 'Liste produits'],
        ['url' => 'produits/add.php', 'permission' => 'PRODUITS_CREER', 'description' => 'Créer produit'],
        ['url' => 'produits/edit.php?id=1', 'permission' => 'PRODUITS_MODIFIER', 'description' => 'Modifier produit'],
    ],
    'Clients / Prospects' => [
        ['url' => 'clients/list.php', 'permission' => 'CLIENTS_LIRE', 'description' => 'Liste clients'],
        ['url' => 'clients/add.php', 'permission' => 'CLIENTS_CREER', 'description' => 'Créer client'],
    ],
    'Devis' => [
        ['url' => 'devis/list.php', 'permission' => 'DEVIS_LIRE', 'description' => 'Liste devis'],
        ['url' => 'devis/create.php', 'permission' => 'DEVIS_CREER', 'description' => 'Créer devis'],
    ],
    'Ventes / Livraisons' => [
        ['url' => 'ventes/list.php', 'permission' => 'VENTES_LIRE', 'description' => 'Liste ventes'],
        ['url' => 'ventes/create.php', 'permission' => 'VENTES_CREER', 'description' => 'Créer vente'],
        ['url' => 'livraisons/list.php', 'permission' => 'VENTES_LIRE', 'description' => 'Liste livraisons'],
    ],
    'Caisse' => [
        ['url' => 'caisse/journal.php', 'permission' => 'CAISSE_LIRE', 'description' => 'Journal caisse'],
        ['url' => 'caisse/nouvelle_operation.php', 'permission' => 'CAISSE_ECRIRE', 'description' => 'Nouvelle opération'],
    ],
    'Reporting' => [
        ['url' => 'reporting/dashboard.php', 'permission' => 'REPORTING_LIRE', 'description' => 'Dashboard'],
        ['url' => 'reporting/ca_produits.php', 'permission' => 'REPORTING_LIRE', 'description' => 'CA produits'],
    ],
];

// Utilisateurs à tester (1 par rôle)
$utilisateurs_test = [
    ['id' => 3, 'login' => 'showroom1', 'role' => 'SHOWROOM'],
    ['id' => 5, 'login' => 'terrain1', 'role' => 'TERRAIN'],
    ['id' => 7, 'login' => 'magasin1', 'role' => 'MAGASINIER'],
    ['id' => 9, 'login' => 'caisse1', 'role' => 'CAISSIER'],
    ['id' => 11, 'login' => 'direction1', 'role' => 'DIRECTION'],
];

$resultats = [];
$total_tests = 0;
$total_ok = 0;
$total_ko = 0;

foreach ($utilisateurs_test as $user) {
    echo "═══════════════════════════════════════════════════════════════════════\n";
    echo "👤 UTILISATEUR: {$user['login']} (RÔLE: {$user['role']})\n";
    echo "═══════════════════════════════════════════════════════════════════════\n\n";
    
    // Charger les permissions de l'utilisateur
    $sql = "
        SELECT DISTINCT p.code
        FROM permissions p
        JOIN role_permission rp ON rp.permission_id = p.id
        JOIN utilisateur_role ur ON ur.role_id = rp.role_id
        WHERE ur.utilisateur_id = ?
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user['id']]);
    $permissions_user = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'code');
    
    echo "🔐 Permissions: " . implode(', ', $permissions_user) . "\n\n";
    
    foreach ($pages_a_tester as $module => $pages) {
        echo "📦 MODULE: $module\n";
        echo str_repeat("─", 70) . "\n";
        
        foreach ($pages as $page) {
            $total_tests++;
            $permission_requise = $page['permission'];
            $a_permission = in_array($permission_requise, $permissions_user);
            
            if ($a_permission) {
                echo "   ✅ {$page['description']} ({$page['url']})\n";
                echo "      Permission: $permission_requise ✓\n";
                $total_ok++;
                $resultats[$user['role']][$module][$page['description']] = 'OK';
            } else {
                echo "   ❌ {$page['description']} ({$page['url']})\n";
                echo "      Permission: $permission_requise ✗ (ACCÈS REFUSÉ)\n";
                $total_ko++;
                $resultats[$user['role']][$module][$page['description']] = 'REFUSÉ';
            }
        }
        
        echo "\n";
    }
    
    echo "\n";
}

// ============================================
// SYNTHÈSE GÉNÉRALE
// ============================================

echo "╔═══════════════════════════════════════════════════════════╗\n";
echo "║                    SYNTHÈSE GÉNÉRALE                      ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";

echo "📊 Statistiques globales:\n";
echo "──────────────────────────────────────────────────────────────────────\n";
echo "Total tests effectués: $total_tests\n";
echo "Accès autorisés: $total_ok (✅)\n";
echo "Accès refusés: $total_ko (❌)\n";
$taux_reussite = round(($total_ok / $total_tests) * 100, 2);
echo "Taux de réussite: $taux_reussite%\n\n";

// Matrice d'accès par rôle
echo "📋 MATRICE D'ACCÈS PAR RÔLE\n";
echo str_repeat("═", 80) . "\n\n";

$tableau = [];
$roles = [];
foreach ($utilisateurs_test as $user) {
    $roles[] = $user['role'];
}

foreach ($pages_a_tester as $module => $pages) {
    echo "MODULE: $module\n";
    echo str_repeat("─", 80) . "\n";
    
    foreach ($pages as $page) {
        $ligne = str_pad($page['description'], 30) . " | ";
        
        foreach ($roles as $role) {
            $status = $resultats[$role][$module][$page['description']] ?? 'N/A';
            $symbole = $status === 'OK' ? '✅' : '❌';
            $ligne .= str_pad("$symbole", 8);
        }
        
        echo $ligne . "\n";
    }
    
    echo "\n";
}

// ============================================
// PROBLÈMES IDENTIFIÉS
// ============================================

echo "\n";
echo "⚠️  PROBLÈMES IDENTIFIÉS ET SOLUTIONS\n";
echo str_repeat("═", 80) . "\n\n";

$problemes = [];

// Analyser les accès refusés
foreach ($resultats as $role => $modules) {
    foreach ($modules as $module => $pages) {
        foreach ($pages as $page => $statut) {
            if ($statut === 'REFUSÉ') {
                $problemes[] = [
                    'role' => $role,
                    'module' => $module,
                    'page' => $page
                ];
            }
        }
    }
}

if (count($problemes) > 0) {
    echo "🔴 " . count($problemes) . " accès refusé(s) détecté(s):\n\n";
    
    $problemes_par_role = [];
    foreach ($problemes as $pb) {
        $problemes_par_role[$pb['role']][] = "{$pb['module']} → {$pb['page']}";
    }
    
    foreach ($problemes_par_role as $role => $pbs) {
        echo "Rôle $role (" . count($pbs) . " problème(s)):\n";
        foreach ($pbs as $pb) {
            echo "   • $pb\n";
        }
        echo "\n";
    }
    
    echo "💡 SOLUTIONS:\n";
    echo "──────────────────────────────────────────────────────────────────────\n";
    echo "1. Si ces accès sont NÉCESSAIRES pour le rôle:\n";
    echo "   → Ajouter les permissions manquantes dans la table role_permission\n\n";
    echo "2. Si ces accès sont INUTILES pour le rôle:\n";
    echo "   → OK, comportement normal\n\n";
    echo "3. Vérifier la logique métier:\n";
    echo "   → Les commerciaux doivent pouvoir créer des devis et ventes\n";
    echo "   → Les magasiniers doivent pouvoir valider des livraisons\n";
    echo "   → Les caissiers doivent pouvoir consulter les ventes\n\n";
    
} else {
    echo "✅ AUCUN PROBLÈME DÉTECTÉ\n";
    echo "Tous les rôles ont accès aux pages nécessaires à leur activité.\n\n";
}

// ============================================
// RECOMMANDATIONS FINALES
// ============================================

echo "\n";
echo "📝 RECOMMANDATIONS FINALES\n";
echo str_repeat("═", 80) . "\n\n";

echo "1. ✅ Tester manuellement la connexion avec chaque compte:\n";
foreach ($utilisateurs_test as $user) {
    echo "   • Login: {$user['login']} / Mot de passe: [voir BD]\n";
}
echo "\n";

echo "2. ✅ Vérifier que les menus s'affichent correctement selon les rôles\n\n";

echo "3. ✅ Tester les actions (créer, modifier, supprimer) pas seulement la lecture\n\n";

echo "4. ✅ Si vous trouvez des pages avec \"Accès refusé\" abusif:\n";
echo "   → Noter la page exacte (URL)\n";
echo "   → Noter le rôle utilisateur\n";
echo "   → Vérifier quelle permission est requise (dans le code de la page)\n";
echo "   → Ajouter la permission au rôle si nécessaire\n\n";

echo "══════════════════════════════════════════════════════════════════════\n";
echo "✅ Test terminé avec succès\n";
echo "══════════════════════════════════════════════════════════════════════\n";
