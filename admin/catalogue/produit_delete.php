<?php
/**
 * admin/catalogue/produit_delete.php
 * Suppression d'un produit catalogue
 */
require_once __DIR__ . '/../../security.php';

exigerConnexion();
exigerPermission('PRODUITS_SUPPRIMER');

global $pdo;

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($id <= 0) {
    $_SESSION['error'] = "ID invalide";
    header('Location: ' . url_for('admin/catalogue/produits.php'));
    exit;
}

verifierCsrf($_POST['csrf_token'] ?? '');

// Récupérer le produit
$stmt = $pdo->prepare("SELECT * FROM catalogue_produits WHERE id = ?");
$stmt->execute([$id]);
$produit = $stmt->fetch();

if (!$produit) {
    $_SESSION['error'] = "Produit non trouvé";
    header('Location: ' . url_for('admin/catalogue/produits.php'));
    exit;
}

try {
    // Supprimer les images physiques
    if ($produit['image_principale']) {
        $filepath = __DIR__ . '/../../uploads/catalogue/' . $produit['image_principale'];
        if (file_exists($filepath)) {
            @unlink($filepath);
        }
    }
    
    if ($produit['galerie_images']) {
        $galerie = json_decode($produit['galerie_images'], true);
        if (is_array($galerie)) {
            foreach ($galerie as $img) {
                $filepath = __DIR__ . '/../../uploads/catalogue/' . $img;
                if (file_exists($filepath)) {
                    @unlink($filepath);
                }
            }
        }
    }
    
    // Supprimer le produit
    $stmt = $pdo->prepare("DELETE FROM catalogue_produits WHERE id = ?");
    $stmt->execute([$id]);
    
    $_SESSION['success'] = "Produit supprimé avec succès";
} catch (Exception $e) {
    $_SESSION['error'] = "Erreur lors de la suppression: " . $e->getMessage();
}

header('Location: ' . url_for('admin/catalogue/produits.php'));
exit;
?>
