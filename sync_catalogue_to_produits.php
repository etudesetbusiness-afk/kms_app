<?php
/**
 * sync_catalogue_to_produits.php
 * Script d'alignement: Importe les produits du catalogue public vers le catalogue privé
 * 
 * OBJECTIF: Rendre disponibles en interne les produits du catalogue public
 * 
 * USAGE: 
 * - Via navigateur: http://localhost/kms_app/sync_catalogue_to_produits.php?confirm=YES
 * - Via CLI: php sync_catalogue_to_produits.php
 */

require_once __DIR__ . '/security.php';
exigerConnexion();
exigerPermission('PRODUITS_CREER');

global $pdo;

// Protection: Require confirmation token
$confirm = $_GET['confirm'] ?? $_POST['confirm'] ?? '';
$dryRun = isset($_GET['dry_run']) || isset($_POST['dry_run']);

if ($confirm !== 'YES' && !$dryRun) {
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Synchronisation Catalogue Public → Privé</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    </head>
    <body class="bg-light">
        <div class="container py-5">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card shadow">
                        <div class="card-header bg-primary text-white">
                            <h3 class="mb-0"><i class="bi bi-arrow-left-right"></i> Synchronisation Catalogues</h3>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <h5 class="alert-heading"><i class="bi bi-info-circle"></i> À propos</h5>
                                <p>Ce script importe les produits du <strong>catalogue public</strong> (site web) vers le <strong>catalogue privé</strong> (gestion interne).</p>
                                <hr>
                                <ul class="mb-0">
                                    <li>Uniquement les produits <strong>non déjà liés</strong> seront traités</li>
                                    <li>Un produit interne sera créé pour chaque produit public</li>
                                    <li>Le lien <code>catalogue_produits.produit_id</code> sera établi</li>
                                    <li>Les <strong>familles manquantes</strong> seront créées automatiquement</li>
                                </ul>
                            </div>

                            <div class="alert alert-warning">
                                <h5 class="alert-heading"><i class="bi bi-exclamation-triangle"></i> Attention</h5>
                                <p class="mb-0">Cette opération modifie la base de données. Assurez-vous d'avoir une sauvegarde récente.</p>
                            </div>

                            <div class="d-grid gap-3">
                                <a href="?dry_run=1" class="btn btn-secondary btn-lg">
                                    <i class="bi bi-eye"></i> Aperçu (Dry Run)
                                </a>
                                <a href="?confirm=YES" class="btn btn-success btn-lg">
                                    <i class="bi bi-check-circle"></i> Lancer la synchronisation
                                </a>
                                <a href="<?= url_for('produits/list.php') ?>" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-left"></i> Retour aux produits
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// ============================================================
// DÉBUT DU SCRIPT
// ============================================================

$stats = [
    'total_catalogue' => 0,
    'deja_lies' => 0,
    'nouveaux_crees' => 0,
    'familles_creees' => 0,
    'erreurs' => []
];

try {
    $pdo->beginTransaction();

    // Étape 1: Compter les produits du catalogue public
    $stmt = $pdo->query("SELECT COUNT(*) FROM catalogue_produits");
    $stats['total_catalogue'] = (int)$stmt->fetchColumn();

    // Étape 2: Compter les produits déjà liés
    $stmt = $pdo->query("SELECT COUNT(*) FROM catalogue_produits WHERE produit_id IS NOT NULL");
    $stats['deja_lies'] = (int)$stmt->fetchColumn();

    // Étape 3: Récupérer les produits du catalogue public NON liés
    $stmtCatalogue = $pdo->query("
        SELECT cp.*, cc.nom AS categorie_nom
        FROM catalogue_produits cp
        LEFT JOIN catalogue_categories cc ON cp.categorie_id = cc.id
        WHERE cp.produit_id IS NULL
        ORDER BY cp.id
    ");
    $produitsCatalogue = $stmtCatalogue->fetchAll();

    // Étape 4: Pour chaque produit catalogue, créer un produit interne
    foreach ($produitsCatalogue as $catProduit) {
        // Mapper la catégorie catalogue → famille produit
        $familleId = null;
        
        // Chercher si une famille existe avec le nom de la catégorie
        $stmtFamille = $pdo->prepare("SELECT id FROM familles_produits WHERE nom = :nom");
        $stmtFamille->execute([':nom' => $catProduit['categorie_nom'] ?? 'Divers']);
        $famille = $stmtFamille->fetch();
        
        if ($famille) {
            $familleId = (int)$famille['id'];
        } else {
            // Créer la famille
            if (!$dryRun) {
                $stmtInsertFamille = $pdo->prepare("INSERT INTO familles_produits (nom) VALUES (:nom)");
                $stmtInsertFamille->execute([':nom' => $catProduit['categorie_nom'] ?? 'Divers']);
                $familleId = (int)$pdo->lastInsertId();
                $stats['familles_creees']++;
            } else {
                $familleId = 999; // Fake ID pour dry run
                $stats['familles_creees']++;
            }
        }

        // Préparer les données du produit interne
        $codeProduit = $catProduit['code'];
        $designation = $catProduit['designation'];
        $description = $catProduit['description'];
        $prixVente = $catProduit['prix_unite'] ?? 0;
        $imagePath = $catProduit['image_principale'];

        // Créer le produit interne
        if (!$dryRun) {
            $stmtInsertProduit = $pdo->prepare("
                INSERT INTO produits (
                    code_produit, famille_id, designation, description,
                    prix_achat, prix_vente, stock_actuel, seuil_alerte,
                    image_path, actif, date_creation
                ) VALUES (
                    :code_produit, :famille_id, :designation, :description,
                    0, :prix_vente, 0, 10,
                    :image_path, 1, NOW()
                )
            ");
            $stmtInsertProduit->execute([
                ':code_produit' => $codeProduit,
                ':famille_id' => $familleId,
                ':designation' => $designation,
                ':description' => $description,
                ':prix_vente' => $prixVente,
                ':image_path' => $imagePath
            ]);
            $produitId = (int)$pdo->lastInsertId();

            // Lier le produit catalogue au produit interne
            $stmtUpdate = $pdo->prepare("UPDATE catalogue_produits SET produit_id = :produit_id WHERE id = :id");
            $stmtUpdate->execute([
                ':produit_id' => $produitId,
                ':id' => $catProduit['id']
            ]);
        }

        $stats['nouveaux_crees']++;
    }

    if ($dryRun) {
        $pdo->rollBack();
    } else {
        $pdo->commit();
    }

} catch (Exception $e) {
    $pdo->rollBack();
    $stats['erreurs'][] = $e->getMessage();
}

// ============================================================
// AFFICHAGE DES RÉSULTATS
// ============================================================
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Résultats Synchronisation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header <?= $dryRun ? 'bg-secondary' : 'bg-success' ?> text-white">
                        <h3 class="mb-0">
                            <i class="bi bi-<?= $dryRun ? 'eye' : 'check-circle' ?>"></i> 
                            <?= $dryRun ? 'Aperçu' : 'Synchronisation Terminée' ?>
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php if ($dryRun): ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> <strong>Mode aperçu</strong> - Aucune modification n'a été appliquée à la base de données.
                            </div>
                        <?php endif; ?>

                        <h5>Statistiques</h5>
                        <table class="table table-bordered">
                            <tbody>
                                <tr>
                                    <td><strong>Total produits catalogue:</strong></td>
                                    <td class="text-end"><?= $stats['total_catalogue'] ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Déjà liés (ignorés):</strong></td>
                                    <td class="text-end text-muted"><?= $stats['deja_lies'] ?></td>
                                </tr>
                                <tr class="table-success">
                                    <td><strong>Nouveaux produits internes créés:</strong></td>
                                    <td class="text-end fw-bold"><?= $stats['nouveaux_crees'] ?></td>
                                </tr>
                                <tr class="table-info">
                                    <td><strong>Familles créées:</strong></td>
                                    <td class="text-end"><?= $stats['familles_creees'] ?></td>
                                </tr>
                            </tbody>
                        </table>

                        <?php if (!empty($stats['erreurs'])): ?>
                            <div class="alert alert-danger">
                                <h5 class="alert-heading"><i class="bi bi-exclamation-triangle"></i> Erreurs</h5>
                                <ul>
                                    <?php foreach ($stats['erreurs'] as $erreur): ?>
                                        <li><?= htmlspecialchars($erreur) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <div class="d-grid gap-2 mt-4">
                            <?php if ($dryRun): ?>
                                <a href="?confirm=YES" class="btn btn-success btn-lg">
                                    <i class="bi bi-check-circle"></i> Appliquer les modifications
                                </a>
                            <?php else: ?>
                                <div class="alert alert-success">
                                    <i class="bi bi-check-circle-fill"></i> Les produits ont été synchronisés avec succès !
                                </div>
                            <?php endif; ?>
                            <a href="<?= url_for('produits/list.php') ?>" class="btn btn-primary">
                                <i class="bi bi-box-seam"></i> Voir les produits internes
                            </a>
                            <a href="<?= url_for('admin/catalogue/produits.php') ?>" class="btn btn-outline-secondary">
                                <i class="bi bi-shop"></i> Voir le catalogue public
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
