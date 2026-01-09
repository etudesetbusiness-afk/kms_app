<?php
require_once __DIR__ . '/../security.php';
exigerConnexion();
exigerPermission('CLIENTS_CREER');

header('Content-Type: application/json; charset=utf-8');

global $pdo;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$prospection_id = isset($_POST['prospection_id']) ? (int)$_POST['prospection_id'] : 0;
$note = isset($_POST['note']) ? trim($_POST['note']) : '';

if ($prospection_id <= 0 || empty($note)) {
    echo json_encode(['success' => false, 'message' => 'Données invalides']);
    exit;
}

try {
    $utilisateur = utilisateurConnecte();
    
    $stmt = $pdo->prepare("
        INSERT INTO prospect_notes (prospection_id, utilisateur_id, note, date_creation)
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->execute([$prospection_id, $utilisateur['id'], $note]);
    
    // Ajout à la timeline
    $stmtTimeline = $pdo->prepare("
        INSERT INTO prospect_timeline (prospection_id, utilisateur_id, type_action, titre, description, date_action)
        VALUES (?, ?, 'NOTE', 'Note ajoutée', ?, NOW())
    ");
    $stmtTimeline->execute([$prospection_id, $utilisateur['id'], $note]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Note ajoutée avec succès'
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de l\'ajout de la note : ' . $e->getMessage()
    ]);
}
