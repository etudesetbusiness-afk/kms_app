<?php
require_once __DIR__ . '/../../security.php';
require_once __DIR__ . '/../controllers/catalogue_controller.php';

header('Content-Type: application/json; charset=utf-8');

$q = trim($_GET['q'] ?? '');
$items = catalogue_search_suggestions($q, 10);

echo json_encode([
    'items' => $items,
]);
