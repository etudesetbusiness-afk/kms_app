<?php
require_once __DIR__ . '/../../security.php';
require_once __DIR__ . '/../controllers/catalogue_controller.php';

header('Content-Type: application/json; charset=utf-8');

$q = trim($_GET['q'] ?? '');
$categorieId = isset($_GET['categorie_id']) ? (int)$_GET['categorie_id'] : 0;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$pageSize = isset($_GET['page_size']) ? (int)$_GET['page_size'] : 12;

$data = catalogue_get_products([
    'q'            => $q,
    'categorie_id' => $categorieId,
], $page, $pageSize);

echo json_encode([
    'items'     => $data['items'],
    'total'     => $data['total'],
    'has_more'  => $data['has_more'],
    'page'      => $page,
    'page_size' => $pageSize,
]);
