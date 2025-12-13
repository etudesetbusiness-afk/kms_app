<?php
// coordination/index.php - Point d'entrée du module coordination
require_once __DIR__ . '/../security.php';
exigerConnexion();

// Rediriger vers le dashboard
header('Location: ' . url_for('coordination/dashboard.php'));
exit;
