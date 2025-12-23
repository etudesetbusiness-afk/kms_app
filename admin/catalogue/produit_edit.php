<?php
/**
 * admin/catalogue/produit_edit.php
 * Création/modification d'un produit catalogue
 */
require_once __DIR__ . '/../../security.php';

exigerConnexion();

global $pdo;

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$is_edit = $id > 0;

if ($is_edit) {
    exigerPermission('PRODUITS_MODIFIER');
} else {
    exigerPermission('PRODUITS_CREER');
}

// Récupérer le produit si édition
$produit = null;
if ($is_edit) {
    $stmt = $pdo->prepare("SELECT * FROM catalogue_produits WHERE id = ?");
    $stmt->execute([$id]);
    $produit = $stmt->fetch();
    
    if (!$produit) {
        $_SESSION['error'] = "Produit non trouvé";
        header('Location: ' . url_for('admin/catalogue/produits.php'));
        exit;
    }
}

// Récupérer les catégories
$categories = $pdo->query("SELECT id, nom FROM catalogue_categories ORDER BY nom ASC")->fetchAll();

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifierCsrf($_POST['csrf_token'] ?? '');
    
    $code = trim($_POST['code'] ?? '');
    $designation = trim($_POST['designation'] ?? '');
    $categorie_id = (int)($_POST['categorie_id'] ?? 0);
    $prix_unite = !empty($_POST['prix_unite']) ? (float)$_POST['prix_unite'] : null;
    $prix_gros = !empty($_POST['prix_gros']) ? (float)$_POST['prix_gros'] : null;
    $description = trim($_POST['description'] ?? '');
    $actif = isset($_POST['actif']) ? 1 : 0;
    
    // Validation
    $errors = [];
    
    if (empty($code)) {
        $errors[] = "Le code est obligatoire";
    }
    
    if (empty($designation)) {
        $errors[] = "La désignation est obligatoire";
    }
    
    if ($categorie_id <= 0) {
        $errors[] = "La catégorie est obligatoire";
    }
    
    // Vérifier unicité du code
    $stmt = $pdo->prepare("SELECT id FROM catalogue_produits WHERE code = ? AND id != ?");
    $stmt->execute([$code, $id]);
    if ($stmt->fetch()) {
        $errors[] = "Ce code existe déjà";
    }
    
    if (empty($errors)) {
        // Générer slug depuis designation
        $slug = generateSlug($designation);
        
        // Vérifier unicité du slug
        $stmt = $pdo->prepare("SELECT id FROM catalogue_produits WHERE slug = ? AND id != ?");
        $stmt->execute([$slug, $id]);
        if ($stmt->fetch()) {
            // Ajouter un suffixe numérique
            $slug = $slug . '-' . uniqid();
        }
        
        // Traitement de l'image principale
        $image_principale = $produit['image_principale'] ?? null;
        
        if (isset($_FILES['image_principale']) && $_FILES['image_principale']['error'] === UPLOAD_ERR_OK) {
            $upload_result = handleImageUpload($_FILES['image_principale'], 'catalogue');
            if ($upload_result['success']) {
                // Supprimer l'ancienne image si elle existe
                if ($image_principale && file_exists(__DIR__ . '/../../uploads/catalogue/' . $image_principale)) {
                    @unlink(__DIR__ . '/../../uploads/catalogue/' . $image_principale);
                }
                $image_principale = $upload_result['filename'];
            } else {
                $errors[] = $upload_result['error'];
            }
        }
        
        // Traitement de la galerie d'images
        $galerie_images = $produit['galerie_images'] ? json_decode($produit['galerie_images'], true) : [];
        
        if (isset($_FILES['galerie_images'])) {
            foreach ($_FILES['galerie_images']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['galerie_images']['error'][$key] === UPLOAD_ERR_OK) {
                    $file = [
                        'name' => $_FILES['galerie_images']['name'][$key],
                        'type' => $_FILES['galerie_images']['type'][$key],
                        'tmp_name' => $tmp_name,
                        'error' => $_FILES['galerie_images']['error'][$key],
                        'size' => $_FILES['galerie_images']['size'][$key]
                    ];
                    $upload_result = handleImageUpload($file, 'catalogue');
                    if ($upload_result['success']) {
                        $galerie_images[] = $upload_result['filename'];
                    }
                }
            }
        }
        
        $galerie_json = !empty($galerie_images) ? json_encode($galerie_images) : null;
        
        // Caractéristiques JSON (optionnel)
        $caracteristiques_json = null;
        if (!empty($_POST['caracteristiques'])) {
            $carac = [];
            foreach ($_POST['caracteristiques'] as $item) {
                if (!empty($item['cle']) && !empty($item['valeur'])) {
                    $carac[$item['cle']] = $item['valeur'];
                }
            }
            if (!empty($carac)) {
                $caracteristiques_json = json_encode($carac, JSON_UNESCAPED_UNICODE);
            }
        }
        
        if (empty($errors)) {
            try {
                if ($is_edit) {
                    // Mise à jour
                    $sql = "UPDATE catalogue_produits SET
                        code = ?, slug = ?, designation = ?, categorie_id = ?,
                        prix_unite = ?, prix_gros = ?, description = ?,
                        caracteristiques_json = ?, image_principale = ?, galerie_images = ?,
                        actif = ?, updated_at = NOW()
                        WHERE id = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        $code, $slug, $designation, $categorie_id,
                        $prix_unite, $prix_gros, $description,
                        $caracteristiques_json, $image_principale, $galerie_json,
                        $actif, $id
                    ]);
                    
                    $_SESSION['success'] = "Produit modifié avec succès";
                } else {
                    // Création
                    $sql = "INSERT INTO catalogue_produits 
                        (code, slug, designation, categorie_id, prix_unite, prix_gros, description, 
                         caracteristiques_json, image_principale, galerie_images, actif)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        $code, $slug, $designation, $categorie_id,
                        $prix_unite, $prix_gros, $description,
                        $caracteristiques_json, $image_principale, $galerie_json,
                        $actif
                    ]);
                    
                    $_SESSION['success'] = "Produit créé avec succès";
                    $id = $pdo->lastInsertId();
                }
                
                header('Location: ' . url_for('admin/catalogue/produit_edit.php?id=' . $id));
                exit;
                
            } catch (Exception $e) {
                $errors[] = "Erreur: " . $e->getMessage();
            }
        }
    }
    
    if (!empty($errors)) {
        $_SESSION['error'] = implode('<br>', $errors);
    }
}

// Helper pour générer un slug
function generateSlug($text) {
    $text = mb_strtolower($text, 'UTF-8');
    $text = preg_replace('/[^a-z0-9]+/u', '-', $text);
    $text = trim($text, '-');
    return $text;
}

// Helper pour upload d'images
function handleImageUpload($file, $folder = 'catalogue') {
    $upload_dir = __DIR__ . '/../../uploads/' . $folder . '/';
    
    // Créer le dossier si nécessaire
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Vérifications
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $max_size = 5 * 1024 * 1024; // 5 MB
    
    if (!in_array($file['type'], $allowed_types)) {
        return ['success' => false, 'error' => 'Type de fichier non autorisé'];
    }
    
    if ($file['size'] > $max_size) {
        return ['success' => false, 'error' => 'Fichier trop volumineux (max 5 MB)'];
    }
    
    // Générer un nom unique
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('img_', true) . '.' . $extension;
    $filepath = $upload_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filename' => $filename];
    }
    
    return ['success' => false, 'error' => 'Erreur lors de l\'upload'];
}

// Déserialiser galerie
$galerie_images = [];
if ($produit && $produit['galerie_images']) {
    $galerie_images = json_decode($produit['galerie_images'], true) ?: [];
}

// Déserialiser caractéristiques
$caracteristiques = [];
if ($produit && $produit['caracteristiques_json']) {
    $carac_obj = json_decode($produit['caracteristiques_json'], true);
    if (is_array($carac_obj)) {
        foreach ($carac_obj as $k => $v) {
            $caracteristiques[] = ['cle' => $k, 'valeur' => $v];
        }
    }
}

include __DIR__ . '/../../partials/header.php';
include __DIR__ . '/../../partials/sidebar.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <!-- En-tête -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-1"><?= $is_edit ? 'Modifier' : 'Nouveau' ?> Produit Catalogue</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="<?= url_for('admin/catalogue/produits.php') ?>">Catalogue</a></li>
                        <li class="breadcrumb-item active"><?= $is_edit ? 'Modification' : 'Création' ?></li>
                    </ol>
                </nav>
            </div>
            <a href="<?= url_for('admin/catalogue/produits.php') ?>" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Retour
            </a>
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

        <?php
        // Trace visible et log pour vérifier que le bon fichier est servi en prod
        $renderId = $is_edit ? 'edit-' . $id : 'create-new';
        error_log('[produit_edit] Render start ' . $renderId . ' on ' . php_sapi_name());
        ?>
        <div class="alert alert-info py-2 px-3 mb-3" style="border-left: 4px solid #0d6efd;">
            DEBUG PAGE: produit_edit.php (<?= htmlspecialchars($renderId) ?>)
        </div>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken()) ?>">
            
            <div class="row">
                <!-- Colonne principale -->
                <div class="col-lg-8">
                    <!-- Informations de base -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">Informations de base</h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Code <span class="text-danger">*</span></label>
                                    <input type="text" name="code" class="form-control" required
                                           value="<?= htmlspecialchars($produit['code'] ?? '') ?>"
                                           placeholder="Ex: PLQ-CTBX-18">
                                    <small class="text-muted">Identifiant unique du produit</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Catégorie <span class="text-danger">*</span></label>
                                    <select name="categorie_id" class="form-select" required>
                                        <option value="">-- Choisir --</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?= $cat['id'] ?>" 
                                                <?= ($produit['categorie_id'] ?? 0) == $cat['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($cat['nom']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Désignation <span class="text-danger">*</span></label>
                                <input type="text" name="designation" class="form-control" required
                                       value="<?= htmlspecialchars($produit['designation'] ?? '') ?>"
                                       placeholder="Ex: Panneau CTBX 18 mm">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="4"
                                          placeholder="Description détaillée du produit..."><?= htmlspecialchars($produit['description'] ?? '') ?></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Prix Unitaire (FCFA)</label>
                                    <input type="number" name="prix_unite" class="form-control" step="0.01"
                                           value="<?= $produit['prix_unite'] ?? '' ?>"
                                           placeholder="Ex: 29500">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Prix Gros (FCFA)</label>
                                    <input type="number" name="prix_gros" class="form-control" step="0.01"
                                           value="<?= $produit['prix_gros'] ?? '' ?>"
                                           placeholder="Ex: 27500">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Caractéristiques -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Caractéristiques</h5>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="add-carac">
                                <i class="bi bi-plus"></i> Ajouter
                            </button>
                        </div>
                        <div class="card-body">
                            <div id="caracteristiques-container">
                                <?php if (!empty($caracteristiques)): ?>
                                    <?php foreach ($caracteristiques as $idx => $carac): ?>
                                        <div class="row mb-2 carac-item">
                                            <div class="col-md-5">
                                                <input type="text" name="caracteristiques[<?= $idx ?>][cle]" 
                                                       class="form-control form-control-sm" placeholder="Clé"
                                                       value="<?= htmlspecialchars($carac['cle']) ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <input type="text" name="caracteristiques[<?= $idx ?>][valeur]" 
                                                       class="form-control form-control-sm" placeholder="Valeur"
                                                       value="<?= htmlspecialchars($carac['valeur']) ?>">
                                            </div>
                                            <div class="col-md-1">
                                                <button type="button" class="btn btn-sm btn-outline-danger remove-carac">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-muted">Aucune caractéristique. Cliquez sur "Ajouter".</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <?php
                    // Wrap the Images block to surface any runtime errors instead of silently stopping the render
                    error_log('[produit_edit] About to render images block for ' . ($produit['id'] ?? 'new'));
                    error_log('[produit_edit] $produit status: ' . ($produit ? 'EXISTS' : 'NULL'));
                    error_log('[produit_edit] image_principale: ' . ($produit['image_principale'] ?? 'NONE'));
                    error_log('[produit_edit] galerie_images raw: ' . ($produit['galerie_images'] ?? 'NONE'));
                    error_log('[produit_edit] $galerie_images array: ' . json_encode($galerie_images));
                    
                    try {
                    ?>
                        <!-- ===== DEBUG: SECTION IMAGES COMMENCE ICI ===== -->
                        <div class="alert alert-warning mb-3">
                            <strong>DEBUG:</strong> Si vous voyez ce message, c'est que le PHP fonctionne jusqu'ici. 
                            La section Images devrait apparaître juste en dessous.
                            Mode: <?= $is_edit ? 'EDIT' : 'CREATE' ?>
                        </div>
                        
                        <!-- Images -->
                        <div class="card shadow-sm mb-4 border-danger border-3" id="section-images" style="background: #fff3cd;">
                            <div class="card-header text-white" style="background-color: #dc3545 !important;">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-images me-2"></i>
                                    🔴 IMAGES DU PRODUIT (SI VOUS VOYEZ CECI, ÇA MARCHE)
                                </h5>
                            </div>
                            <div class="card-body">
                                <!-- Image principale -->
                                <div class="mb-4 p-3 border rounded" style="background-color: #f8f9fa;">
                                    <label class="form-label fw-bold">
                                        <i class="bi bi-image text-primary me-1"></i>
                                        Image principale
                                    </label>
                                    <?php if ($produit && !empty($produit['image_principale'])): ?>
                                        <div class="mb-3">
                                            <?php
                                            error_log('[produit_edit] Displaying existing image: ' . $produit['image_principale']);
                                            $img_url = url_for('uploads/catalogue/' . $produit['image_principale']);
                                            error_log('[produit_edit] Image URL: ' . $img_url);
                                            ?>
                                            <img src="<?= htmlspecialchars($img_url) ?>" 
                                                 alt="" class="img-thumbnail" style="max-width: 200px;">
                                            <p class="text-muted small mt-1 mb-0">
                                                <i class="bi bi-info-circle"></i> Image actuelle (sera remplacée si vous uploadez une nouvelle image)
                                            </p>
                                        </div>
                                    <?php endif; ?>
                                    <input type="file" name="image_principale" class="form-control form-control-lg" accept="image/*">
                                    <small class="text-muted d-block mt-2">
                                        <i class="bi bi-file-earmark-image"></i> Format accepté: JPG, PNG, GIF, WEBP (max 5 MB)
                                    </small>
                                </div>

                                <!-- Galerie -->
                                <div class="p-3 border rounded" style="background-color: #f8f9fa;">
                                    <label class="form-label fw-bold">
                                        <i class="bi bi-images text-primary me-1"></i>
                                        Galerie d'images
                                    </label>
                                    <?php if (!empty($galerie_images) && is_array($galerie_images)): ?>
                                        <?php error_log('[produit_edit] Displaying gallery, count: ' . count($galerie_images)); ?>
                                        <div class="row mb-3">
                                            <?php foreach ($galerie_images as $idx => $img): ?>
                                                <?php error_log('[produit_edit] Gallery image ' . $idx . ': ' . $img); ?>
                                                <div class="col-md-3 mb-2">
                                                    <img src="<?= htmlspecialchars(url_for('uploads/catalogue/' . $img)) ?>" 
                                                         alt="" class="img-thumbnail w-100">
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <p class="text-muted small mb-2">
                                            <i class="bi bi-info-circle"></i> Images actuelles de la galerie
                                        </p>
                                    <?php endif; ?>
                                    <input type="file" name="galerie_images[]" class="form-control form-control-lg" accept="image/*" multiple>
                                    <small class="text-muted d-block mt-2">
                                        <i class="bi bi-card-image"></i> Vous pouvez sélectionner plusieurs fichiers en même temps
                                    </small>
                                </div>
                            </div>
                        </div>
                    <?php
                    } catch (Throwable $e) {
                        error_log('[produit_edit] Section images ERROR for produit id ' . ($produit['id'] ?? 'new') . ': ' . $e->getMessage());
                        error_log('[produit_edit] Error trace: ' . $e->getTraceAsString());
                    ?>
                        <div class="alert alert-danger border-3 border-danger">
                            <strong>❌ Section Images en erreur :</strong><br>
                            <?= htmlspecialchars($e->getMessage()) ?><br>
                            <small>Ligne: <?= $e->getLine() ?> dans <?= basename($e->getFile()) ?></small>
                        </div>
                    <?php
                    }
                    ?>
                </div>

                <!-- Colonne latérale -->
                <div class="col-lg-4">
                    <!-- Statut -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">Publication</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="actif" id="actif"
                                       <?= ($produit['actif'] ?? 1) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="actif">
                                    Produit actif (visible dans le catalogue public)
                                </label>
                            </div>

                            <?php if ($is_edit): ?>
                                <hr>
                                <small class="text-muted">
                                    Créé le: <?= date('d/m/Y H:i', strtotime($produit['created_at'])) ?><br>
                                    Modifié le: <?= date('d/m/Y H:i', strtotime($produit['updated_at'])) ?>
                                </small>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <button type="submit" class="btn btn-primary w-100 mb-2">
                                <i class="bi bi-save me-1"></i> Enregistrer
                            </button>
                            <?php if ($is_edit): ?>
                                <a href="<?= url_for('catalogue/fiche.php?slug=' . urlencode($produit['slug'])) ?>" 
                                   class="btn btn-outline-secondary w-100" target="_blank">
                                    <i class="bi bi-eye me-1"></i> Voir public
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// Gestion des caractéristiques
let caracIndex = <?= count($caracteristiques) ?>;

document.getElementById('add-carac').addEventListener('click', function() {
    const container = document.getElementById('caracteristiques-container');
    const emptyMsg = container.querySelector('p.text-muted');
    if (emptyMsg) emptyMsg.remove();
    
    const div = document.createElement('div');
    div.className = 'row mb-2 carac-item';
    div.innerHTML = `
        <div class="col-md-5">
            <input type="text" name="caracteristiques[${caracIndex}][cle]" 
                   class="form-control form-control-sm" placeholder="Clé">
        </div>
        <div class="col-md-6">
            <input type="text" name="caracteristiques[${caracIndex}][valeur]" 
                   class="form-control form-control-sm" placeholder="Valeur">
        </div>
        <div class="col-md-1">
            <button type="button" class="btn btn-sm btn-outline-danger remove-carac">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    `;
    container.appendChild(div);
    caracIndex++;
});

document.addEventListener('click', function(e) {
    if (e.target.closest('.remove-carac')) {
        e.target.closest('.carac-item').remove();
    }
});
</script>

<?php include __DIR__ . '/../../partials/footer.php'; ?>
