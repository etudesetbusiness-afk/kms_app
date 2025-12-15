<?php
/**
 * admin/catalogue/categories.php
 * Gestion des catégories du catalogue
 */
require_once __DIR__ . '/../../security.php';

exigerConnexion();
exigerPermission('PRODUITS_LIRE');

global $pdo;

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifierCsrf($_POST['csrf_token'] ?? '');
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create' && peut('PRODUITS_CREER')) {
        $nom = trim($_POST['nom'] ?? '');
        $actif = isset($_POST['actif']) ? 1 : 0;
        $ordre = (int)($_POST['ordre'] ?? 1);
        
        if (!empty($nom)) {
            $slug = generateSlug($nom);
            
            // Vérifier unicité du slug
            $stmt = $pdo->prepare("SELECT id FROM catalogue_categories WHERE slug = ?");
            $stmt->execute([$slug]);
            if ($stmt->fetch()) {
                $slug = $slug . '-' . uniqid();
            }
            
            $stmt = $pdo->prepare("INSERT INTO catalogue_categories (nom, slug, actif, ordre) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nom, $slug, $actif, $ordre]);
            
            $_SESSION['success'] = "Catégorie créée avec succès";
        } else {
            $_SESSION['error'] = "Le nom est obligatoire";
        }
        
        header('Location: ' . url_for('admin/catalogue/categories.php'));
        exit;
    }
    
    if ($action === 'update' && peut('PRODUITS_MODIFIER')) {
        $id = (int)($_POST['id'] ?? 0);
        $nom = trim($_POST['nom'] ?? '');
        $actif = isset($_POST['actif']) ? 1 : 0;
        $ordre = (int)($_POST['ordre'] ?? 1);
        
        if ($id > 0 && !empty($nom)) {
            $slug = generateSlug($nom);
            
            // Vérifier unicité du slug
            $stmt = $pdo->prepare("SELECT id FROM catalogue_categories WHERE slug = ? AND id != ?");
            $stmt->execute([$slug, $id]);
            if ($stmt->fetch()) {
                $slug = $slug . '-' . uniqid();
            }
            
            $stmt = $pdo->prepare("UPDATE catalogue_categories SET nom = ?, slug = ?, actif = ?, ordre = ? WHERE id = ?");
            $stmt->execute([$nom, $slug, $actif, $ordre, $id]);
            
            $_SESSION['success'] = "Catégorie modifiée avec succès";
        } else {
            $_SESSION['error'] = "Données invalides";
        }
        
        header('Location: ' . url_for('admin/catalogue/categories.php'));
        exit;
    }
    
    if ($action === 'delete' && peut('PRODUITS_SUPPRIMER')) {
        $id = (int)($_POST['id'] ?? 0);
        
        if ($id > 0) {
            // Vérifier si la catégorie contient des produits
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM catalogue_produits WHERE categorie_id = ?");
            $stmt->execute([$id]);
            $count = $stmt->fetchColumn();
            
            if ($count > 0) {
                $_SESSION['error'] = "Impossible de supprimer: cette catégorie contient $count produit(s)";
            } else {
                $stmt = $pdo->prepare("DELETE FROM catalogue_categories WHERE id = ?");
                $stmt->execute([$id]);
                $_SESSION['success'] = "Catégorie supprimée avec succès";
            }
        }
        
        header('Location: ' . url_for('admin/catalogue/categories.php'));
        exit;
    }
}

// Récupérer toutes les catégories
$categories = $pdo->query("
    SELECT c.*, COUNT(p.id) as nb_produits
    FROM catalogue_categories c
    LEFT JOIN catalogue_produits p ON p.categorie_id = c.id
    GROUP BY c.id
    ORDER BY c.ordre ASC, c.nom ASC
")->fetchAll();

function generateSlug($text) {
    $text = mb_strtolower($text, 'UTF-8');
    $text = preg_replace('/[^a-z0-9]+/u', '-', $text);
    $text = trim($text, '-');
    return $text;
}

include __DIR__ . '/../../partials/header.php';
include __DIR__ . '/../../partials/sidebar.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <!-- En-tête -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1">Catégories du Catalogue</h1>
                <p class="text-muted mb-0">Gérer les catégories de produits</p>
            </div>
            <?php if (peut('PRODUITS_CREER')): ?>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
                    <i class="bi bi-plus-circle me-1"></i> Nouvelle Catégorie
                </button>
            <?php endif; ?>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= $_SESSION['error'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= $_SESSION['success'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <!-- Table -->
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th style="width: 60px;">Ordre</th>
                                <th>Nom</th>
                                <th>Slug</th>
                                <th>Produits</th>
                                <th>Statut</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($categories) === 0): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        Aucune catégorie
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($categories as $cat): ?>
                                    <tr>
                                        <td><span class="badge bg-secondary"><?= $cat['ordre'] ?></span></td>
                                        <td><strong><?= htmlspecialchars($cat['nom']) ?></strong></td>
                                        <td><code><?= htmlspecialchars($cat['slug']) ?></code></td>
                                        <td><?= $cat['nb_produits'] ?> produit(s)</td>
                                        <td>
                                            <span class="badge bg-<?= $cat['actif'] ? 'success' : 'secondary' ?>">
                                                <?= $cat['actif'] ? 'Actif' : 'Inactif' ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <div class="btn-group btn-group-sm">
                                                <?php if (peut('PRODUITS_MODIFIER')): ?>
                                                    <button class="btn btn-outline-primary" 
                                                            onclick="editCategory(<?= htmlspecialchars(json_encode($cat)) ?>)">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <?php if (peut('PRODUITS_SUPPRIMER')): ?>
                                                    <button class="btn btn-outline-danger" 
                                                            onclick="deleteCategory(<?= $cat['id'] ?>, '<?= htmlspecialchars($cat['nom'], ENT_QUOTES) ?>')">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Création -->
<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken()) ?>">
            <input type="hidden" name="action" value="create">
            
            <div class="modal-header">
                <h5 class="modal-title">Nouvelle Catégorie</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Nom <span class="text-danger">*</span></label>
                    <input type="text" name="nom" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Ordre</label>
                    <input type="number" name="ordre" class="form-control" value="1">
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="actif" id="actif_create" checked>
                    <label class="form-check-label" for="actif_create">
                        Catégorie active
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" class="btn btn-primary">Créer</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Édition -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken()) ?>">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="edit_id">
            
            <div class="modal-header">
                <h5 class="modal-title">Modifier Catégorie</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Nom <span class="text-danger">*</span></label>
                    <input type="text" name="nom" id="edit_nom" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Ordre</label>
                    <input type="number" name="ordre" id="edit_ordre" class="form-control">
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="actif" id="edit_actif">
                    <label class="form-check-label" for="edit_actif">
                        Catégorie active
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" class="btn btn-primary">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<!-- Form Suppression (caché) -->
<form method="POST" id="deleteForm" style="display:none;">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken()) ?>">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="delete_id">
</form>

<script>
function editCategory(cat) {
    document.getElementById('edit_id').value = cat.id;
    document.getElementById('edit_nom').value = cat.nom;
    document.getElementById('edit_ordre').value = cat.ordre;
    document.getElementById('edit_actif').checked = cat.actif == 1;
    
    const modal = new bootstrap.Modal(document.getElementById('editModal'));
    modal.show();
}

function deleteCategory(id, nom) {
    if (confirm('Supprimer la catégorie "' + nom + '" ?\n\nCette action est irréversible.')) {
        document.getElementById('delete_id').value = id;
        document.getElementById('deleteForm').submit();
    }
}
</script>

<?php include __DIR__ . '/../../partials/footer.php'; ?>
