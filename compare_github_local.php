<?php
/**
 * compare_github_local.php
 * Compare récursivement le dépôt GitHub et le projet local
 */

echo "╔════════════════════════════════════════════════════════════════════════════╗\n";
echo "║        COMPARAISON - DÉPÔT GITHUB vs PROJET LOCAL                         ║\n";
echo "╚════════════════════════════════════════════════════════════════════════════╝\n\n";

$results = [
    'git_status' => '',
    'local_commits' => 0,
    'remote_commits' => 0,
    'uncommitted_changes' => 0,
    'untracked_files' => 0,
    'diverged' => false,
    'ahead' => 0,
    'behind' => 0,
];

// ==================== 1. STATUS GIT ====================
echo "PHASE 1: Status Git\n";
echo "════════════════════════════════════════════════════════════════════════════\n";

// Récupérer les infos distantes
$git_fetch = shell_exec("git fetch origin 2>&1");
echo "  ✓ Mis à jour depuis origin\n";

// Status local
$git_status = shell_exec("git status --porcelain 2>&1");
$lines = explode("\n", trim($git_status));

$modified = 0;
$added = 0;
$deleted = 0;
$untracked = 0;

foreach ($lines as $line) {
    if (empty($line)) continue;
    $status = substr($line, 0, 2);
    
    if ($status === 'M ') $modified++;      // Modified
    elseif ($status === 'A ') $added++;      // Added
    elseif ($status === 'D ') $deleted++;    // Deleted
    elseif ($status === '??') $untracked++;  // Untracked
}

$results['uncommitted_changes'] = $modified + $added + $deleted;
$results['untracked_files'] = $untracked;

echo "  Changements locaux non commitées:\n";
echo "    - Modifiées: $modified\n";
echo "    - Ajoutées: $added\n";
echo "    - Supprimées: $deleted\n";
echo "    - Non tracées: $untracked\n";

echo "\n";

// ==================== 2. DIVERGENCE ====================
echo "PHASE 2: Divergence (local vs remote)\n";
echo "════════════════════════════════════════════════════════════════════════════\n";

// Vérifier si en avance ou en retard
$ahead_behind = shell_exec("git rev-list --count --left-right @{u}...HEAD 2>&1");
$ahead_behind = trim($ahead_behind);

if (empty($ahead_behind) || strpos($ahead_behind, 'fatal') !== false) {
    echo "  ✓ Local et remote synchronisés\n";
    $results['ahead'] = 0;
    $results['behind'] = 0;
} else {
    $parts = explode("\t", $ahead_behind);
    $results['behind'] = isset($parts[0]) ? (int)$parts[0] : 0;
    $results['ahead'] = isset($parts[1]) ? (int)$parts[1] : 0;
    
    if ($results['ahead'] > 0 && $results['behind'] > 0) {
        echo "  ⚠️  DIVERGÉ: En avance de {$results['ahead']} et en retard de {$results['behind']}\n";
        $results['diverged'] = true;
    } elseif ($results['ahead'] > 0) {
        echo "  ℹ️  En avance de {$results['ahead']} commit(s)\n";
    } elseif ($results['behind'] > 0) {
        echo "  ⚠️  En retard de {$results['behind']} commit(s)\n";
    } else {
        echo "  ✓ Synchronisés\n";
    }
}

echo "\n";

// ==================== 3. COMMITS LOCAUX VS DISTANTS ====================
echo "PHASE 3: Historique des commits\n";
echo "════════════════════════════════════════════════════════════════════════════\n";

$local_log = shell_exec("git log --oneline -5 2>&1");
echo "  Derniers commits locaux:\n";
foreach (explode("\n", trim($local_log)) as $line) {
    if (!empty($line)) {
        echo "    " . substr($line, 0, 50) . "\n";
        $results['local_commits']++;
    }
}

echo "\n";

$remote_log = shell_exec("git log --oneline -5 origin/main 2>&1");
echo "  Derniers commits distants (origin/main):\n";
foreach (explode("\n", trim($remote_log)) as $line) {
    if (!empty($line)) {
        echo "    " . substr($line, 0, 50) . "\n";
        $results['remote_commits']++;
    }
}

echo "\n";

// ==================== 4. FICHIERS DIFFÉRENTS ====================
echo "PHASE 4: Différences de fichiers\n";
echo "════════════════════════════════════════════════════════════════════════════\n";

// Fichiers modifiés vs remote
$diff_files = shell_exec("git diff --name-status origin/main 2>&1");

if (empty(trim($diff_files))) {
    echo "  ✓ Aucune différence\n";
} else {
    echo "  Fichiers différents de origin/main:\n";
    $diff_lines = explode("\n", trim($diff_files));
    $file_diff_count = 0;
    foreach ($diff_lines as $line) {
        if (!empty($line)) {
            echo "    " . substr($line, 0, 100) . "\n";
            $file_diff_count++;
            if ($file_diff_count >= 10) {
                echo "    ... et " . (count($diff_lines) - 10) . " autres\n";
                break;
            }
        }
    }
}

echo "\n";

// ==================== 5. BRANCHES ====================
echo "PHASE 5: Branches\n";
echo "════════════════════════════════════════════════════════════════════════════\n";

$branches = shell_exec("git branch -a 2>&1");
echo "  Branches locales et distantes:\n";
foreach (explode("\n", trim($branches)) as $line) {
    if (!empty($line)) {
        echo "    " . trim($line) . "\n";
    }
}

echo "\n";

// ==================== 6. CONFIGURATION DISTANTE ====================
echo "PHASE 6: Configuration Git\n";
echo "════════════════════════════════════════════════════════════════════════════\n";

$remote = shell_exec("git remote -v 2>&1");
echo "  Remote(s) configuré(s):\n";
foreach (explode("\n", trim($remote)) as $line) {
    if (!empty($line)) {
        // Masquer le token si présent
        $line = preg_replace('/ghp_[a-zA-Z0-9]+/', '[TOKEN_HIDDEN]', $line);
        echo "    " . $line . "\n";
    }
}

echo "\n";

// ==================== RÉSUMÉ ====================
echo "╔════════════════════════════════════════════════════════════════════════════╗\n";
echo "║                          RÉSUMÉ COMPARAISON                               ║\n";
echo "╚════════════════════════════════════════════════════════════════════════════╝\n\n";

echo "STATUS SYNCHRONISATION\n";
echo "─────────────────────────────────────────────────────────────────────────────\n";

if ($results['uncommitted_changes'] === 0 && $results['untracked_files'] === 0 && 
    $results['ahead'] === 0 && $results['behind'] === 0 && !$results['diverged']) {
    echo "✅ DÉPÔT LOCAL COMPLÈTEMENT SYNCHRONISÉ\n\n";
    echo "  ✓ Aucun changement non commitée\n";
    echo "  ✓ Aucun fichier non tracé\n";
    echo "  ✓ Aucun commit local à pousser\n";
    echo "  ✓ Aucun commit distant à récupérer\n";
    echo "  ✓ Pas de divergence\n";
} else {
    echo "⚠️  DIFFÉRENCES DÉTECTÉES\n\n";
    
    if ($results['uncommitted_changes'] > 0) {
        echo "  ✗ Changements non commitées: {$results['uncommitted_changes']}\n";
    }
    if ($results['untracked_files'] > 0) {
        echo "  ⚠️  Fichiers non tracés: {$results['untracked_files']}\n";
    }
    if ($results['ahead'] > 0) {
        echo "  ↑ Commits en avance: {$results['ahead']}\n";
    }
    if ($results['behind'] > 0) {
        echo "  ↓ Commits en retard: {$results['behind']}\n";
    }
    if ($results['diverged']) {
        echo "  ⚠️  DIVERGENCE DÉTECTÉE!\n";
    }
}

echo "\n";

// ==================== STATS ====================
echo "STATISTIQUES\n";
echo "─────────────────────────────────────────────────────────────────────────────\n";

echo "  Commits locaux: {$results['local_commits']}\n";
echo "  Commits distants: {$results['remote_commits']}\n";
echo "  Changements locaux: {$results['uncommitted_changes']}\n";
echo "  Fichiers non tracés: {$results['untracked_files']}\n";

echo "\n";

// ==================== VERDICT ====================
echo "VERDICT\n";
echo "─────────────────────────────────────────────────────────────────────────────\n";

if ($results['uncommitted_changes'] === 0 && $results['untracked_files'] === 0 && 
    $results['ahead'] === 0 && $results['behind'] === 0 && !$results['diverged']) {
    echo "✅ LOCAL = GITHUB (identiques)\n";
    echo "\n  Aucune action requise\n";
} else {
    if ($results['uncommitted_changes'] > 0 || $results['untracked_files'] > 0) {
        echo "⚠️  ACTION REQUISE: Committer et pousser les changements\n";
        if ($results['uncommitted_changes'] > 0) {
            echo "    → git add -A && git commit -m '...'\n";
        }
        if ($results['ahead'] > 0) {
            echo "    → git push origin main\n";
        }
    } elseif ($results['behind'] > 0) {
        echo "⚠️  ACTION REQUISE: Récupérer les changements distants\n";
        echo "    → git pull origin main\n";
    } elseif ($results['ahead'] > 0) {
        echo "ℹ️  Commits locaux à pousser\n";
        echo "    → git push origin main\n";
    }
}

echo "\n";
?>
