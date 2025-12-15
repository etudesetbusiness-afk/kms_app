<?php
/**
 * api/get_date_preset.php - Endpoint pour obtenir les plages de dates
 */
require_once __DIR__ . '/../security.php';
require_once __DIR__ . '/../lib/date_helpers.php';

header('Content-Type: application/json');

// Vérifier que c'est une requête POST JSON
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Récupérer et valider le preset
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$preset = $input['preset'] ?? 'last_30d';

// Valider le preset (whitelist)
$allowed_presets = ['today', 'last_7d', 'last_30d', 'last_90d', 'this_month', 'last_month', 'this_year'];
if (!in_array($preset, $allowed_presets)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid preset']);
    exit;
}

// Récupérer la plage
$range = getDateRangePreset($preset);

echo json_encode([
    'preset' => $preset,
    'start' => $range['start'],
    'end' => $range['end'],
    'label' => getPresetLabel($preset)
]);
