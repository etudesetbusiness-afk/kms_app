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
$statut_crm = isset($_POST['statut_crm']) ? trim($_POST['statut_crm']) : '';

$statutsValides = ['PROSPECT', 'INTERESSE', 'PROSPECT_CHAUD', 'DEVIS_DEMANDE', 'DEVIS_EMIS', 'COMMANDE_OBTENUE', 'CLIENT_ACTIF', 'FIDELISATION', 'PERDU'];

if ($prospection_id <= 0 || !in_array($statut_crm, $statutsValides)) {
    echo json_encode(['success' => false, 'message' => 'Données invalides']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Mise à jour du statut
    $stmt = $pdo->prepare("UPDATE prospections_terrain SET statut_crm = ? WHERE id = ?");
    $stmt->execute([$statut_crm, $prospection_id]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Statut mis à jour avec succès'
    ]);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la mise à jour : ' . $e->getMessage()
    ]);
}
