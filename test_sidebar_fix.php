<?php
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['utilisateur'] = ['id' => 1, 'login' => 'admin', 'nom_complet' => 'Admin'];
$_SESSION['permissions'] = ['PRODUITS_LIRE'];
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

require 'security.php';
require 'partials/sidebar.php';

echo "✓ Tous les fichiers chargés sans erreur\n";
echo "✓ Function peut() disponible: " . (function_exists('peut') ? "OUI" : "NON") . "\n";
?>
