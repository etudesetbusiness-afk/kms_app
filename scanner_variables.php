<?php
/**
 * SCANNER SPÉCIALISÉ - Variables Undefined
 * Analyse fine du problème identifié en ventes/list.php
 */

class ScannerVariablesUndefined {
    private $resultats = [];
    private $fichiers_php = [];
    
    public function scanner() {
        echo "═══════════════════════════════════════════════════════════\n";
        echo "   SCANNER VARIABLES UNDEFINED\n";
        echo "═══════════════════════════════════════════════════════════\n\n";
        
        // Étape 1: Lister tous les fichiers
        $this->load_php_files();
        echo "📊 Fichiers trouvés: " . count($this->fichiers_php) . "\n\n";
        
        // Étape 2: Analyser fichier par fichier
        foreach ($this->fichiers_php as $file) {
            $this->analyser_fichier($file);
        }
        
        // Étape 3: Afficher résultats
        $this->afficher_resultats();
    }
    
    private function load_php_files() {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(__DIR__, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        
        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php') {
                $path = realpath($file->getPathname());
                // Exclure
                if (!preg_match('/(\.git|vendor|node_modules|test_)/', $path)) {
                    $this->fichiers_php[] = $path;
                }
            }
        }
        
        sort($this->fichiers_php);
    }
    
    private function analyser_fichier($filepath) {
        $content = file_get_contents($filepath);
        $lines = explode("\n", $content);
        
        // Extraire toutes les variables
        preg_match_all('/\$([a-zA-Z_]\w*)/', $content, $all_vars);
        $vars_used = array_unique($all_vars[1]);
        
        // Extraire les variables définies
        $vars_defined = $this->get_defined_vars($content, $vars_used);
        
        // Trouver les undefined
        foreach ($vars_used as $var) {
            if (!isset($vars_defined[$var])) {
                // Trouver la ligne d'usage
                foreach ($lines as $line_num => $line) {
                    if (preg_match('/\$' . preg_quote($var) . '\b/', $line)) {
                        // Ignorer commentaires
                        if (strpos(trim($line), '//') === 0 || strpos(trim($line), '#') === 0) {
                            continue;
                        }
                        
                        $this->resultats[] = [
                            'file' => $filepath,
                            'var' => $var,
                            'line' => $line_num + 1,
                            'code' => trim($line)
                        ];
                        break;
                    }
                }
            }
        }
    }
    
    private function get_defined_vars($content, $vars) {
        $defined = [];
        
        foreach ($vars as $var) {
            // Vérifier si défini avec =
            if (preg_match('/\$' . preg_quote($var) . '\s*=/', $content)) {
                $defined[$var] = 'assignment';
            }
            
            // Vérifier superglobales
            if (in_array($var, ['GLOBALS', 'SERVER', 'GET', 'POST', 'COOKIE', 'SESSION', 'FILES', 'REQUEST', 'ENV', 'argc', 'argv'])) {
                $defined[$var] = 'superglobal';
            }
            
            // Vérifier paramètres de fonction
            if (preg_match('/function\s+\w+\s*\([^)]*\$' . preg_quote($var) . '/', $content)) {
                $defined[$var] = 'parameter';
            }
            
            // Vérifier global
            if (preg_match('/global\s+\$' . preg_quote($var) . '\b/', $content)) {
                $defined[$var] = 'global';
            }
            
            // Vérifier foreach
            if (preg_match('/foreach\s*\([^)]*\$' . preg_quote($var) . '[^)]*/', $content)) {
                $defined[$var] = 'foreach';
            }
        }
        
        return $defined;
    }
    
    private function afficher_resultats() {
        if (empty($this->resultats)) {
            echo "✅ Aucune variable undefined trouvée!\n";
            return;
        }
        
        // Grouper par fichier
        $by_file = [];
        foreach ($this->resultats as $bug) {
            $by_file[$bug['file']][] = $bug;
        }
        
        echo "🔴 VARIABLES UNDEFINED DÉTECTÉES:\n";
        echo "─────────────────────────────────────────────────────────\n\n";
        
        foreach ($by_file as $file => $bugs) {
            echo "📄 " . str_replace(__DIR__ . '/', '', $file) . "\n";
            
            foreach ($bugs as $bug) {
                echo "  ❌ Ligne {$bug['line']}: \${$bug['var']}\n";
                echo "     Code: " . substr($bug['code'], 0, 70) . "\n";
            }
            echo "\n";
        }
        
        echo "─────────────────────────────────────────────────────────\n";
        echo "Total: " . count($this->resultats) . " variables undefined\n";
    }
}

// Lancer le scanner
$scanner = new ScannerVariablesUndefined();
$scanner->scanner();
?>
