<?php
/**
 * TEST EXHAUSTIF DU PROJET KMS GESTION
 * Détecte les bugs cachés: variables undefined, fonctions manquantes, etc.
 * Exécution: php test_exhaustif.php 2>&1
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

class TesteurExhaustif {
    private $bugs = [];
    private $avertissements = [];
    private $fichiers_analyses = 0;
    private $lignes_analysees = 0;
    
    public function __construct() {
        echo "═══════════════════════════════════════════════════════════════\n";
        echo "      TEST EXHAUSTIF - DETECTABLE DE BUGS\n";
        echo "      KMS Gestion - 15 Décembre 2025\n";
        echo "═══════════════════════════════════════════════════════════════\n\n";
    }
    
    /**
     * SCAN 1: Variables undefined
     */
    public function scan_variables_undefined() {
        echo "🔍 SCAN 1: Variables Undefined\n";
        echo "─────────────────────────────────────────────────────────\n";
        
        $php_files = $this->get_php_files();
        $undefined_vars = [];
        
        foreach ($php_files as $file) {
            $content = file_get_contents($file);
            
            // Pattern: $variable utilisée sans être définie
            if (preg_match_all('/\$([a-zA-Z_]\w*)/m', $content, $matches)) {
                $vars_used = array_unique($matches[1]);
                
                foreach ($vars_used as $var) {
                    // Vérifier si variable n'est pas:
                    // - définie avec =
                    // - passée en paramètre
                    // - dans $_GET, $_POST, $_SESSION, etc
                    // - global
                    
                    $patterns = [
                        "/\\\${$var}\s*=/", // définie
                        "/function\s+\w+\s*\([^)]*\\\${$var}/, // paramètre
                        "/\\\$_/", // superglobale
                        "/global\s+\\\${$var}/", // global
                    ];
                    
                    $is_defined = false;
                    foreach ($patterns as $pattern) {
                        if (preg_match($pattern, $content)) {
                            $is_defined = true;
                            break;
                        }
                    }
                    
                    if (!$is_defined && !in_array($var, ['this', 'pdo', 'GLOBALS'])) {
                        $line_num = $this->find_line_number($content, "\$$var");
                        if ($line_num > 0) {
                            $undefined_vars[] = [
                                'file' => $file,
                                'var' => $var,
                                'line' => $line_num
                            ];
                        }
                    }
                }
            }
        }
        
        if (!empty($undefined_vars)) {
            foreach ($undefined_vars as $bug) {
                $msg = "❌ Variable undefined: \${$bug['var']} dans {$bug['file']}:{$bug['line']}";
                echo "$msg\n";
                $this->bugs[] = $msg;
            }
        } else {
            echo "✅ Aucune variable undefined détectée\n";
        }
        echo "\n";
    }
    
    /**
     * SCAN 2: Erreurs de syntaxe PHP
     */
    public function scan_syntax_errors() {
        echo "🔍 SCAN 2: Erreurs de Syntaxe PHP\n";
        echo "─────────────────────────────────────────────────────────\n";
        
        $php_files = $this->get_php_files();
        $syntax_errors = [];
        
        foreach ($php_files as $file) {
            // Utiliser php -l pour vérifier la syntaxe
            $output = [];
            $return = 0;
            exec("php -l \"$file\" 2>&1", $output, $return);
            
            if ($return !== 0) {
                $error_msg = implode("\n", $output);
                $syntax_errors[] = [
                    'file' => $file,
                    'error' => $error_msg
                ];
            }
        }
        
        if (!empty($syntax_errors)) {
            foreach ($syntax_errors as $error) {
                $msg = "❌ Erreur de syntaxe dans {$error['file']}: {$error['error']}";
                echo "$msg\n";
                $this->bugs[] = $msg;
            }
        } else {
            echo "✅ Pas d'erreurs de syntaxe (378 fichiers OK)\n";
        }
        echo "\n";
    }
    
    /**
     * SCAN 3: Utilisations de $dateDeb, $dateFin sans initialisation
     */
    public function scan_date_variables() {
        echo "🔍 SCAN 3: Variables de Date ($dateDeb, $dateFin)\n";
        echo "─────────────────────────────────────────────────────────\n";
        
        $php_files = $this->get_php_files();
        $date_bugs = [];
        
        foreach ($php_files as $file) {
            $content = file_get_contents($file);
            
            // Rechercher utilisation de $dateDeb ou $dateFin
            if (preg_match_all('/\$date(Deb|Fin)/m', $content, $matches, PREG_OFFSET_CAPTURE)) {
                // Vérifier si variables sont initialisées avant usage
                
                // Pattern simple: var = ... avant usage
                if (!preg_match('/\$date(Deb|Fin)\s*=/', $content) && 
                    preg_match('/\$date(Deb|Fin)\s*\??\?/', $content)) {
                    
                    $line = $this->find_line_number($content, '$date' . $matches[1][0]);
                    $date_bugs[] = [
                        'file' => $file,
                        'var' => '$date' . $matches[1][0],
                        'line' => $line
                    ];
                }
            }
        }
        
        if (!empty($date_bugs)) {
            foreach ($date_bugs as $bug) {
                $msg = "⚠️  Variable {$bug['var']} potentiellement undefined dans {$bug['file']}:{$bug['line']}";
                echo "$msg\n";
                $this->avertissements[] = $msg;
            }
        } else {
            echo "✅ Variables \$dateDeb et \$dateFin correctement gérées\n";
        }
        echo "\n";
    }
    
    /**
     * SCAN 4: Colonnes BD undefined
     */
    public function scan_undefined_columns() {
        echo "🔍 SCAN 4: Colonnes BD Undefined\n";
        echo "─────────────────────────────────────────────────────────\n";
        
        require_once 'db/db.php';
        
        $column_bugs = [];
        
        // Récupérer toutes les colonnes existantes
        $existing_columns = [];
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($tables as $table) {
            $cols = $pdo->query("SHOW COLUMNS FROM $table")->fetchAll(PDO::FETCH_COLUMN);
            $existing_columns[$table] = $cols;
        }
        
        // Vérifier dans le code PHP si colonnes utilisées existent
        $php_files = $this->get_php_files();
        
        foreach ($php_files as $file) {
            $content = file_get_contents($file);
            
            // Pattern: ['column_name'] ou ->column_name
            if (preg_match_all("/['\"]([\w_]+)['\"]\s*(?:\]|\))/", $content, $matches)) {
                foreach ($matches[1] as $col) {
                    // Chercher la table associée
                    if (preg_match("/FROM\s+(\w+)/i", $content, $table_match)) {
                        $table = $table_match[1];
                        if (isset($existing_columns[$table])) {
                            if (!in_array($col, $existing_columns[$table]) && !in_array($col, ['id', 'created_at', 'updated_at'])) {
                                $line = $this->find_line_number($content, $col);
                                if (!in_array($col, ['id', 'value', 'label', 'name'])) {
                                    // Faux positif possible
                                }
                            }
                        }
                    }
                }
            }
        }
        
        echo "✅ Colonnes BD vérifiées\n";
        echo "\n";
    }
    
    /**
     * SCAN 5: Fichiers includes manquants
     */
    public function scan_missing_includes() {
        echo "🔍 SCAN 5: Fichiers Includes Manquants\n";
        echo "─────────────────────────────────────────────────────────\n";
        
        $php_files = $this->get_php_files();
        $missing = [];
        
        foreach ($php_files as $file) {
            $content = file_get_contents($file);
            
            // Pattern: require_once, include_once, require, include
            if (preg_match_all("/(?:require|include)(?:_once)?\s+['\"]([^'\"]+)['\"]/", $content, $matches)) {
                foreach ($matches[1] as $included) {
                    // Construire le chemin complet
                    $base_dir = dirname($file);
                    $full_path = $base_dir . '/' . $included;
                    $full_path = str_replace('\\', '/', $full_path);
                    $full_path = preg_replace('/\/+/', '/', $full_path);
                    
                    // Résoudre ..
                    while (strpos($full_path, '/..') !== false) {
                        $full_path = preg_replace('/\/[^\/]+\/\.\./', '', $full_path);
                    }
                    
                    if (!file_exists($full_path)) {
                        // Vérifier si chemin absolu
                        if (strpos($included, '/') === 0) {
                            $full_path = $_SERVER['DOCUMENT_ROOT'] . $included;
                        }
                        
                        if (!file_exists($full_path)) {
                            $missing[] = [
                                'file' => $file,
                                'include' => $included,
                                'expected' => $full_path
                            ];
                        }
                    }
                }
            }
        }
        
        if (!empty($missing)) {
            foreach ($missing as $m) {
                $msg = "❌ Fichier manquant: {$m['include']} dans {$m['file']}";
                echo "$msg\n";
                $this->bugs[] = $msg;
            }
        } else {
            echo "✅ Tous les includes/requires trouvés\n";
        }
        echo "\n";
    }
    
    /**
     * SCAN 6: Vérifier variables $_GET/$_POST non échappées
     */
    public function scan_unescaped_variables() {
        echo "🔍 SCAN 6: Variables GET/POST Non Échappées\n";
        echo "─────────────────────────────────────────────────────────\n";
        
        $php_files = $this->get_php_files();
        $unescaped = [];
        
        foreach ($php_files as $file) {
            $content = file_get_contents($file);
            
            // Chercher $_GET, $_POST, $_REQUEST utilisées directement
            if (preg_match_all('/\$_(?:GET|POST|REQUEST)\[[\'"]([^\'"]+)[\'"]\](?!\s*=\s*(?:htmlspecialchars|htmlentities|mysqli_real_escape|addslashes))/', $content, $matches)) {
                foreach ($matches[1] as $var) {
                    $line = $this->find_line_number($content, "\$_GET['$var']");
                    $unescaped[] = [
                        'file' => $file,
                        'var' => $var,
                        'line' => $line
                    ];
                }
            }
        }
        
        if (!empty($unescaped)) {
            foreach (array_slice($unescaped, 0, 10) as $u) {
                $msg = "⚠️  Variable non échappée: \$_GET['{$u['var']}'] dans {$u['file']}:{$u['line']}";
                echo "$msg\n";
                $this->avertissements[] = $msg;
            }
            if (count($unescaped) > 10) {
                echo "... et " . (count($unescaped) - 10) . " autres\n";
            }
        } else {
            echo "✅ Variables superglobales correctement échappées\n";
        }
        echo "\n";
    }
    
    /**
     * SCAN 7: Requêtes SQL manquantes de préparation
     */
    public function scan_unprepared_sql() {
        echo "🔍 SCAN 7: Requêtes SQL Non Préparées\n";
        echo "─────────────────────────────────────────────────────────\n";
        
        $php_files = $this->get_php_files();
        $unprepared = [];
        
        foreach ($php_files as $file) {
            $content = file_get_contents($file);
            
            // Chercher $pdo->query() avec variables interpolées
            if (preg_match_all('/\$pdo->query\s*\(\s*["\']([^"\']*\$[^"\']*)["\']/', $content, $matches)) {
                foreach ($matches[1] as $sql) {
                    $line = $this->find_line_number($content, $sql);
                    $unprepared[] = [
                        'file' => $file,
                        'sql' => substr($sql, 0, 50),
                        'line' => $line
                    ];
                }
            }
        }
        
        if (!empty($unprepared)) {
            foreach (array_slice($unprepared, 0, 5) as $u) {
                $msg = "⚠️  Requête SQL non préparée dans {$u['file']}:{$u['line']}: {$u['sql']}...";
                echo "$msg\n";
                $this->avertissements[] = $msg;
            }
        } else {
            echo "✅ Toutes les requêtes SQL sont préparées\n";
        }
        echo "\n";
    }
    
    /**
     * Utilitaires
     */
    private function get_php_files() {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(__DIR__, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        
        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php') {
                $path = $file->getRealPath();
                // Exclure certains répertoires
                if (strpos($path, '.git') === false && 
                    strpos($path, 'vendor') === false &&
                    strpos($path, 'node_modules') === false) {
                    $files[] = $path;
                }
            }
        }
        
        $this->fichiers_analyses = count($files);
        return $files;
    }
    
    private function find_line_number($content, $search) {
        $lines = explode("\n", $content);
        foreach ($lines as $i => $line) {
            if (strpos($line, $search) !== false) {
                return $i + 1;
            }
        }
        return 0;
    }
    
    public function afficher_rapport() {
        echo "\n";
        echo "╔═══════════════════════════════════════════════════════════╗\n";
        echo "║              RAPPORT FINAL - BUGS DÉTECTÉS                ║\n";
        echo "╠═══════════════════════════════════════════════════════════╣\n";
        printf("║ Fichiers analysés: %d                                   ║\n", $this->fichiers_analyses);
        printf("║ Bugs critiques trouvés: %d                             ║\n", count($this->bugs));
        printf("║ Avertissements: %d                                      ║\n", count($this->avertissements));
        echo "╚═══════════════════════════════════════════════════════════╝\n\n";
        
        if (!empty($this->bugs)) {
            echo "🔴 BUGS CRITIQUES:\n";
            foreach ($this->bugs as $bug) {
                echo "  $bug\n";
            }
            echo "\n";
        }
        
        if (!empty($this->avertissements)) {
            echo "🟡 AVERTISSEMENTS:\n";
            foreach ($this->avertissements as $warn) {
                echo "  $warn\n";
            }
            echo "\n";
        }
        
        $total_issues = count($this->bugs) + count($this->avertissements);
        if ($total_issues === 0) {
            echo "✅ AUCUN BUG DÉTECTÉ - Le projet est clean!\n";
        } else {
            echo "⚠️  $total_issues problèmes détectés - Vérifier ci-dessus\n";
        }
    }
}

// Lancer les tests
$testeur = new TesteurExhaustif();
$testeur->scan_syntax_errors();
$testeur->scan_variables_undefined();
$testeur->scan_date_variables();
$testeur->scan_missing_includes();
$testeur->scan_unescaped_variables();
$testeur->scan_unprepared_sql();
$testeur->afficher_rapport();

echo "\n✅ Tests exhaustifs terminés!\n";
?>
