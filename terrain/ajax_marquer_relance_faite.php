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

$relance_id = isset($_POST['relance_id']) ? (int)$_POST['relance_id'] : 0;
$resultat = isset($_POST['resultat']) ? trim($_POST['resultat']) : '';

if ($relance_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Données invalides']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Mise à jour de la relance
    $stmt = $pdo->prepare("
        UPDATE prospect_relances 
        SET statut = 'FAIT', date_realisation = NOW(), resultat = ?
        WHERE id = ?
    ");
    $stmt->execute([$resultat, $relance_id]);
    
    // Récupération info relance pour timeline
    $stmtInfo = $pdo->prepare("SELECT prospection_id, canal FROM prospect_relances WHERE id = ?");
    $stmtInfo->execute([$relance_id]);
    $relance = $stmtInfo->fetch(PDO::FETCH_ASSOC);
    
    if ($relance) {
        $utilisateur = utilisateurConnecte();
        
        // Ajout à la timeline
        $stmtTimeline = $pdo->prepare("
            INSERT INTO prospect_timeline (prospection_id, utilisateur_id, type_action, titre, description, date_action)
            VALUES (?, ?, ?, 'Relance effectuée', ?, NOW())
        ");
        $typeAction = strtoupper($relance['canal']);
        $description = "Relance effectuée via " . $relance['canal'];
        if (!empty($resultat)) {
            $description .= " - Résultat : " . $resultat;
        }
        $stmtTimeline->execute([$relance['prospection_id'], $utilisateur['id'], $typeAction, $description]);
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Relance marquée comme effectuée'
    ]);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de la mise à jour : ' . $e->getMessage()
    ]);
}
