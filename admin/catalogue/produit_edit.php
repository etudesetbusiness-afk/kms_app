<?php
// Edition/creation d'un produit catalogue avec gestion fiable des JSON et images
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

// Helpers
function generateSlug($text) {
    $text = mb_strtolower($text, 'UTF-8');
    $text = preg_replace('/[^a-z0-9]+/u', '-', $text);
    return trim($text, '-');
}

function safeJsonDecode($json, $default) {
    if ($json === null || $json === '') {
        return $default;
    }
    $decoded = json_decode($json, true);
    return is_array($decoded) ? $decoded : $default;
}

function handleImageUpload($file, $folder = 'catalogue') {
    $upload_dir = __DIR__ . '/../../uploads/' . $folder . '/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $max_size = 5 * 1024 * 1024;

    if (!in_array($file['type'], $allowed_types)) {
        return ['success' => false, 'error' => 'Type de fichier non autorisé'];
    }
    if ($file['size'] > $max_size) {
        return ['success' => false, 'error' => 'Fichier trop volumineux (max 5 MB)'];
    }

    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('img_', true) . '.' . $extension;
    $filepath = $upload_dir . $filename;

    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filename' => $filename];
    }

    return ['success' => false, 'error' => "Erreur lors de l'upload"];
}

// Charger produit
$produit = null;
if ($is_edit) {
    $stmt = $pdo->prepare('SELECT * FROM catalogue_produits WHERE id = ?');
    $stmt->execute([$id]);
    $produit = $stmt->fetch();
    if (!$produit) {
        $_SESSION['error'] = 'Produit non trouvé';
        header('Location: ' . url_for('admin/catalogue/produits.php'));
        exit;
    }
}

// Préparer données par défaut
$decoded_galerie = safeJsonDecode($produit['galerie_images'] ?? null, []);
$decoded_caracs = safeJsonDecode($produit['caracteristiques_json'] ?? null, []);
$caracteristiques = [];
foreach ($decoded_caracs as $k => $v) {
    $caracteristiques[] = ['cle' => $k, 'valeur' => $v];
}

$form = [
    'code' => $produit['code'] ?? '',
    'designation' => $produit['designation'] ?? '',
    'categorie_id' => $produit['categorie_id'] ?? '',
    'prix_unite' => $produit['prix_unite'] ?? '',
    'prix_gros' => $produit['prix_gros'] ?? '',
    'description' => $produit['description'] ?? '',
    'actif' => isset($produit['actif']) ? (int)$produit['actif'] : 1,
    'caracteristiques' => $caracteristiques,
    'galerie_images' => $decoded_galerie,
    'image_principale' => $produit['image_principale'] ?? null,
];

// Récupérer les catégories
$categories = $pdo->query('SELECT id, nom FROM catalogue_categories ORDER BY nom ASC')->fetchAll();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifierCsrf($_POST['csrf_token'] ?? '');

    $code = trim($_POST['code'] ?? '');
    $designation = trim($_POST['designation'] ?? '');
    $categorie_id = (int)($_POST['categorie_id'] ?? 0);
    $prix_unite = $_POST['prix_unite'] !== '' ? (float)$_POST['prix_unite'] : null;
    $prix_gros = $_POST['prix_gros'] !== '' ? (float)$_POST['prix_gros'] : null;
    $description = trim($_POST['description'] ?? '');
    $actif = isset($_POST['actif']) ? 1 : 0;

    $form['code'] = $code;
    $form['designation'] = $designation;
    $form['categorie_id'] = $categorie_id;
    $form['prix_unite'] = $prix_unite;
    $form['prix_gros'] = $prix_gros;
    $form['description'] = $description;
    $form['actif'] = $actif;

    if ($code === '') {
        $errors[] = 'Le code est obligatoire';
    }
    if ($designation === '') {
        $errors[] = 'La désignation est obligatoire';
    }
    if ($categorie_id <= 0) {
        $errors[] = 'La catégorie est obligatoire';
    }

    // Unicité code
    $stmt = $pdo->prepare('SELECT id FROM catalogue_produits WHERE code = ? AND id != ?');
    $stmt->execute([$code, $id]);
    if ($stmt->fetch()) {
        $errors[] = 'Ce code existe déjà';
    }

    // Slug unique
    $slug = generateSlug($designation);
    $stmt = $pdo->prepare('SELECT id FROM catalogue_produits WHERE slug = ? AND id != ?');
    $stmt->execute([$slug, $id]);
    if ($stmt->fetch()) {
        $slug = $slug . '-' . uniqid();
    }

    // Caractéristiques -> JSON
    $caracteristiques_json = null;
    $caracteristiques = [];
    if (!empty($_POST['caracteristiques']) && is_array($_POST['caracteristiques'])) {
        foreach ($_POST['caracteristiques'] as $item) {
            $cle = trim($item['cle'] ?? '');
            $val = trim($item['valeur'] ?? '');
            if ($cle !== '' && $val !== '') {
                $caracteristiques[$cle] = $val;
            }
        }
        if (!empty($caracteristiques)) {
            $caracteristiques_json = json_encode($caracteristiques, JSON_UNESCAPED_UNICODE);
        }
    }
    $form['caracteristiques'] = [];
    foreach ($caracteristiques as $k => $v) {
        $form['caracteristiques'][] = ['cle' => $k, 'valeur' => $v];
    }

    // Images
    $image_principale = $form['image_principale'];
    if (isset($_FILES['image_principale']) && $_FILES['image_principale']['error'] === UPLOAD_ERR_OK) {
        $upload = handleImageUpload($_FILES['image_principale'], 'catalogue');
        if ($upload['success']) {
            if ($image_principale) {
                $old_path = __DIR__ . '/../../uploads/catalogue/' . $image_principale;
                if (file_exists($old_path)) {
                    @unlink($old_path);
                }
            }
            $image_principale = $upload['filename'];
        } else {
            $errors[] = $upload['error'];
        }
    }
    $form['image_principale'] = $image_principale;

    // Galerie: conserver existant + ajouter nouveaux
    $galerie_images = safeJsonDecode($produit['galerie_images'] ?? '[]', []);
    if (isset($_FILES['galerie_images'])) {
        foreach ($_FILES['galerie_images']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['galerie_images']['error'][$key] === UPLOAD_ERR_OK) {
                $file = [
                    'name' => $_FILES['galerie_images']['name'][$key],
                    'type' => $_FILES['galerie_images']['type'][$key],
                    'tmp_name' => $tmp_name,
                    'error' => $_FILES['galerie_images']['error'][$key],
                    'size' => $_FILES['galerie_images']['size'][$key],
                ];
                $upload = handleImageUpload($file, 'catalogue');
                if ($upload['success']) {
                    $galerie_images[] = $upload['filename'];
                }
            }
        }
    }
    $form['galerie_images'] = $galerie_images;
    $galerie_json = json_encode($galerie_images);

    if (empty($errors)) {
        try {
            if ($is_edit) {
                $sql = 'UPDATE catalogue_produits SET code = ?, slug = ?, designation = ?, categorie_id = ?, prix_unite = ?, prix_gros = ?, description = ?, caracteristiques_json = ?, image_principale = ?, galerie_images = ?, actif = ?, updated_at = NOW() WHERE id = ?';
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $code,
                    $slug,
                    $designation,
                    $categorie_id,
                    $prix_unite,
                    $prix_gros,
                    $description,
                    $caracteristiques_json,
                    $image_principale,
                    $galerie_json,
                    $actif,
                    $id,
                ]);
                $_SESSION['success'] = 'Produit modifié avec succès';
            } else {
                $sql = 'INSERT INTO catalogue_produits (code, slug, designation, categorie_id, prix_unite, prix_gros, description, caracteristiques_json, image_principale, galerie_images, actif) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $code,
                    $slug,
                    $designation,
                    $categorie_id,
                    $prix_unite,
                    $prix_gros,
                    $description,
                    $caracteristiques_json,
                    $image_principale,
                    $galerie_json,
                    $actif,
                ]);
                $id = $pdo->lastInsertId();
                $_SESSION['success'] = 'Produit créé avec succès';
            }

            header('Location: ' . url_for('admin/catalogue/produit_edit.php?id=' . $id));
            exit;
        } catch (Exception $e) {
            $errors[] = 'Erreur: ' . $e->getMessage();
        }
    }

    if (!empty($errors)) {
        $_SESSION['error'] = implode('<br>', $errors);
    }
}

include __DIR__ . '/../../partials/header.php';
include __DIR__ . '/../../partials/sidebar.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
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
            <div class="d-flex gap-2">
                <a href="<?= url_for('admin/catalogue/produits.php') ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Retour
                </a>
                <button type="submit" form="form-produit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i> Enregistrer
                </button>
            </div>
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

        <form method="POST" enctype="multipart/form-data" id="form-produit">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken()) ?>">

            <div class="row">
                <div class="col-lg-8">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">Informations de base</h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Code <span class="text-danger">*</span></label>
                                    <input type="text" name="code" class="form-control" required value="<?= htmlspecialchars($form['code']) ?>" placeholder="Ex: PLQ-CTBX-18">
                                    <small class="text-muted">Identifiant unique du produit</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Catégorie <span class="text-danger">*</span></label>
                                    <select name="categorie_id" class="form-select" required>
                                        <option value="">-- Choisir --</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?= $cat['id'] ?>" <?= (int)$form['categorie_id'] === (int)$cat['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($cat['nom']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Désignation <span class="text-danger">*</span></label>
                                <input type="text" name="designation" class="form-control" required value="<?= htmlspecialchars($form['designation']) ?>" placeholder="Ex: Panneau CTBX 18 mm">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="4" placeholder="Description détaillée du produit..."><?= htmlspecialchars($form['description']) ?></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Prix Unitaire (FCFA)</label>
                                    <input type="number" name="prix_unite" class="form-control" step="0.01" value="<?= $form['prix_unite'] ?>" placeholder="Ex: 29500">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Prix Gros (FCFA)</label>
                                    <input type="number" name="prix_gros" class="form-control" step="0.01" value="<?= $form['prix_gros'] ?>" placeholder="Ex: 27500">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow-sm mb-4" id="section-images">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-images me-2"></i>
                                Images du produit
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-4 p-3 border rounded" style="background-color: #f8f9fa;">
                                <label class="form-label fw-bold">
                                    <i class="bi bi-image text-primary me-1"></i>
                                    Image principale
                                </label>
                                <?php if (!empty($form['image_principale'])): ?>
                                    <div class="mb-3">
                                        <?php $img_url = url_for('uploads/catalogue/' . $form['image_principale']); ?>
                                        <img src="<?= htmlspecialchars($img_url) ?>" alt="" class="img-thumbnail" style="max-width: 200px;">
                                        <p class="text-muted small mt-1 mb-0">
                                            <i class="bi bi-info-circle"></i> Image actuelle (remplacée si vous uploadez une nouvelle image)
                                        </p>
                                    </div>
                                <?php endif; ?>
                                <input type="file" name="image_principale" class="form-control form-control-lg" accept="image/*">
                                <small class="text-muted d-block mt-2">
                                    <i class="bi bi-file-earmark-image"></i> Formats: JPG, PNG, GIF, WEBP (max 5 MB)
                                </small>
                            </div>

                            <div class="p-3 border rounded" style="background-color: #f8f9fa;">
                                <label class="form-label fw-bold">
                                    <i class="bi bi-images text-primary me-1"></i>
                                    Galerie d'images
                                </label>
                                <?php if (!empty($form['galerie_images'])): ?>
                                    <div class="row mb-3">
                                        <?php foreach ($form['galerie_images'] as $img): ?>
                                            <div class="col-md-3 mb-2">
                                                <img src="<?= htmlspecialchars(url_for('uploads/catalogue/' . $img)) ?>" alt="" class="img-thumbnail w-100">
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <p class="text-muted small mb-2">
                                        <i class="bi bi-info-circle"></i> Images actuelles conservées sauf remplacement manuel
                                    </p>
                                <?php endif; ?>
                                <input type="file" name="galerie_images[]" class="form-control form-control-lg" accept="image/*" multiple>
                                <small class="text-muted d-block mt-2">
                                    <i class="bi bi-card-image"></i> Sélection multiple possible
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Caractéristiques</h5>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="add-carac">
                                <i class="bi bi-plus"></i> Ajouter
                            </button>
                        </div>
                        <div class="card-body">
                            <div id="caracteristiques-container">
                                <?php if (!empty($form['caracteristiques'])): ?>
                                    <?php foreach ($form['caracteristiques'] as $idx => $carac): ?>
                                        <div class="row mb-2 carac-item">
                                            <div class="col-md-5">
                                                <input type="text" name="caracteristiques[<?= $idx ?>][cle]" class="form-control form-control-sm" placeholder="Clé" value="<?= htmlspecialchars($carac['cle']) ?>">
                                            </div>
                                            <div class="col-md-6">
                                                <input type="text" name="caracteristiques[<?= $idx ?>][valeur]" class="form-control form-control-sm" placeholder="Valeur" value="<?= htmlspecialchars($carac['valeur']) ?>">
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
                </div>

                <div class="col-lg-4">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">Publication</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="actif" id="actif" <?= $form['actif'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="actif">Produit actif (visible dans le catalogue public)</label>
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
                </div>
            </div>

            </div>
        </form>
    </div>
</div>

<script>
let caracIndex = <?= count($form['caracteristiques']) ?>;
document.getElementById('add-carac').addEventListener('click', function() {
    const container = document.getElementById('caracteristiques-container');
    const emptyMsg = container.querySelector('p.text-muted');
    if (emptyMsg) emptyMsg.remove();

    const div = document.createElement('div');
    div.className = 'row mb-2 carac-item';
    div.innerHTML = `
        <div class="col-md-5">
            <input type="text" name="caracteristiques[${caracIndex}][cle]" class="form-control form-control-sm" placeholder="Clé">
        </div>
        <div class="col-md-6">
            <input type="text" name="caracteristiques[${caracIndex}][valeur]" class="form-control form-control-sm" placeholder="Valeur">
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
