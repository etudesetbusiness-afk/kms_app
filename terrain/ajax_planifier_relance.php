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
$date_relance = isset($_POST['date_relance']) ? trim($_POST['date_relance']) : '';
$canal = isset($_POST['canal']) ? trim($_POST['canal']) : '';
$message = isset($_POST['message']) ? trim($_POST['message']) : null;

$canauxValides = ['WHATSAPP', 'APPEL', 'SMS', 'EMAIL', 'VISITE'];

if ($prospection_id <= 0 || empty($date_relance) || !in_array($canal, $canauxValides)) {
    echo json_encode(['success' => false, 'message' => 'Données invalides']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    $utilisateur = utilisateurConnecte();
    
    // Insertion de la relance
    $stmt = $pdo->prepare("
        INSERT INTO prospect_relances (prospection_id, utilisateur_id, date_relance_prevue, canal, message, statut, date_creation)
        VALUES (?, ?, ?, ?, ?, 'A_FAIRE', NOW())
    ");
    $stmt->execute([$prospection_id, $utilisateur['id'], $date_relance, $canal, $message]);
    
    // Mise à jour du prospect avec la prochaine relance
    $stmtUpdate = $pdo->prepare("
        UPDATE prospections_terrain 
        SET date_relance = ?, canal_relance = ?, message_relance = ?
        WHERE id = ?
    ");
    $stmtUpdate->execute([$date_relance, $canal, $message, $prospection_id]);
    
    // Ajout à la timeline
    $stmtTimeline = $pdo->prepare("
        INSERT INTO prospect_timeline (prospection_id, utilisateur_id, type_action, titre, description, date_action)
        VALUES (?, ?, 'RELANCE', 'Relance planifiée', ?, NOW())
    ");
    $stmtTimeline->execute([
        $prospection_id, 
        $utilisateur['id'], 
        "Relance planifiée le $date_relance via $canal"
    ]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Relance planifiée avec succès'
    ]);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la planification de la relance : ' . $e->getMessage()
    ]);
}
