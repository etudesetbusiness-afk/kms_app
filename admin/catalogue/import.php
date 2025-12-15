<?php
/**
 * admin/catalogue/import.php
 * Import de produits depuis un fichier Excel/CSV
 */
require_once __DIR__ . '/../../security.php';

exigerConnexion();
exigerPermission('PRODUITS_CREER');

global $pdo;

$step = $_GET['step'] ?? '1';
$errors = [];
$success = '';
$preview = [];
$categories = [];

// Récupérer les catégories pour le dropdown
$stmt = $pdo->query("SELECT id, nom FROM catalogue_categories ORDER BY nom ASC");
$categories = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérifier le token CSRF
    verifierCsrf($_POST['csrf_token'] ?? '');
    
    $action = $_POST['action'] ?? '';
    
    // Étape 1: Upload du fichier
    if ($action === 'upload' && $step === '1') {
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Veuillez sélectionner un fichier valide";
        } else {
            $file = $_FILES['file'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            if (!in_array($ext, ['csv', 'xlsx', 'xls'])) {
                $errors[] = "Format non supporté. Utilisez CSV, XLSX ou XLS";
            } elseif ($file['size'] > 10 * 1024 * 1024) {
                $errors[] = "Fichier trop volumineux (max 10 MB)";
            } else {
                // Sauvegarder temporairement
                $tmpfile = sys_get_temp_dir() . '/' . uniqid('kms_import_') . '.' . $ext;
                if (move_uploaded_file($file['tmp_name'], $tmpfile)) {
                    $_SESSION['import_tmpfile'] = $tmpfile;
                    $_SESSION['import_filename'] = $file['name'];
                    $_SESSION['import_ext'] = $ext;
                    
                    // Redirection vers étape 2 (aperçu)
                    header('Location: ' . url_for('admin/catalogue/import.php?step=2'));
                    exit;
                } else {
                    $errors[] = "Erreur lors de l'upload du fichier";
                }
            }
        }
    }
    
    // Étape 2: Validation et aperçu
    if ($action === 'preview' && $step === '2') {
        // Charger le fichier temporaire
        $tmpfile = $_SESSION['import_tmpfile'] ?? null;
        $ext = $_SESSION['import_ext'] ?? null;
        
        if (!$tmpfile || !file_exists($tmpfile)) {
            $errors[] = "Fichier temporaire non trouvé";
        } else {
            // Parser le fichier
            if ($ext === 'csv') {
                $preview = parseCSV($tmpfile);
            } else {
                $preview = parseExcel($tmpfile);
            }
            
            if (empty($preview)) {
                $errors[] = "Le fichier est vide ou format non reconnu";
            }
        }
    }
    
    // Étape 3: Import final
    if ($action === 'import' && $step === '3') {
        $tmpfile = $_SESSION['import_tmpfile'] ?? null;
        $ext = $_SESSION['import_ext'] ?? null;
        
        if (!$tmpfile || !file_exists($tmpfile)) {
            $errors[] = "Fichier temporaire non trouvé";
        } else {
            // Parser et insérer en BD
            $rows = ($ext === 'csv') ? parseCSV($tmpfile) : parseExcel($tmpfile);
            $result = importProducts($rows, $pdo);
            
            if ($result['success']) {
                $success = "✓ " . $result['count'] . " produit(s) importé(s) avec succès";
                // Nettoyer
                @unlink($tmpfile);
                unset($_SESSION['import_tmpfile']);
                unset($_SESSION['import_filename']);
                unset($_SESSION['import_ext']);
                // Redirection vers liste produits
                $_SESSION['success'] = $success;
                header('Location: ' . url_for('admin/catalogue/produits.php'));
                exit;
            } else {
                $errors = array_merge($errors, $result['errors']);
            }
        }
    }
}

// Parser CSV simple
function parseCSV($filepath) {
    $data = [];
    if (($handle = fopen($filepath, 'r')) !== false) {
        $headers = null;
        $row_num = 0;
        
        while (($row = fgetcsv($handle, 1000, ",")) !== false) {
            $row_num++;
            
            if ($row_num === 1) {
                // Headers
                $headers = array_map('trim', $row);
                continue;
            }
            
            if ($headers) {
                $item = array_combine($headers, $row) ?: [];
                $data[] = $item;
            }
        }
        
        fclose($handle);
    }
    
    return $data;
}

// Parser Excel simple (lit le ZIP et le XML)
function parseExcel($filepath) {
    $data = [];
    
    // Décompresser le fichier XLSX
    $zip = new ZipArchive();
    if ($zip->open($filepath) === true) {
        // Lire sheet1.xml
        $xml_content = $zip->getFromName('xl/worksheets/sheet1.xml');
        
        if ($xml_content) {
            $xml = simplexml_load_string($xml_content);
            
            // Récupérer les rows
            $rows = $xml->sheetData->row;
            $headers = null;
            $row_num = 0;
            
            foreach ($rows as $row) {
                $row_num++;
                $cells = [];
                
                foreach ($row->c as $cell) {
                    $value = '';
                    
                    // Récupérer la valeur
                    if ($cell->v) {
                        $value = (string)$cell->v;
                    } elseif ($cell->f) {
                        $value = (string)$cell->f;
                    }
                    
                    $cells[] = trim($value);
                }
                
                if ($row_num === 1) {
                    $headers = $cells;
                    continue;
                }
                
                if ($headers && count($cells) > 0) {
                    $item = array_combine($headers, array_pad($cells, count($headers), '')) ?: [];
                    $data[] = $item;
                }
            }
        }
        
        $zip->close();
    }
    
    return $data;
}

// Importer les produits en BD
function importProducts($rows, $pdo) {
    $count = 0;
    $errors = [];
    
    foreach ($rows as $idx => $row) {
        try {
            // Validation minimale
            $code = trim($row['code'] ?? $row['Code'] ?? $row['CODE'] ?? '');
            $designation = trim($row['designation'] ?? $row['Designation'] ?? $row['DESIGNATION'] ?? '');
            $categorie_id = trim($row['categorie_id'] ?? $row['Catégorie'] ?? $row['CATEGORIE'] ?? '1');
            $prix_unite = trim($row['prix_unite'] ?? $row['Prix'] ?? $row['PRIX'] ?? '0');
            
            if (empty($code) || empty($designation)) {
                $errors[] = "Ligne " . ($idx + 2) . ": Code et Désignation obligatoires";
                continue;
            }
            
            // Vérifier l'unicité du code
            $stmt = $pdo->prepare("SELECT id FROM catalogue_produits WHERE code = ?");
            $stmt->execute([$code]);
            if ($stmt->fetch()) {
                $errors[] = "Ligne " . ($idx + 2) . ": Code '$code' déjà existant";
                continue;
            }
            
            // Générer slug
            $slug = strtolower(str_replace([' ', 'é', 'è', 'ê', 'à', 'â', 'ù'], ['-', 'e', 'e', 'e', 'a', 'a', 'u'], $designation));
            $slug = preg_replace('/[^a-z0-9-]/', '', $slug);
            
            // Vérifier l'unicité du slug
            $stmt = $pdo->prepare("SELECT id FROM catalogue_produits WHERE slug = ?");
            $stmt->execute([$slug]);
            if ($stmt->fetch()) {
                $slug .= '-' . uniqid();
            }
            
            // Insérer
            $stmt = $pdo->prepare("
                INSERT INTO catalogue_produits 
                (code, designation, slug, categorie_id, prix_unite, prix_gros, actif, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, 1, NOW(), NOW())
            ");
            
            $stmt->execute([
                $code,
                $designation,
                $slug,
                $categorie_id ?: 1,
                !empty($prix_unite) ? (float)str_replace(',', '.', $prix_unite) : null,
                null
            ]);
            
            $count++;
        } catch (Exception $e) {
            $errors[] = "Ligne " . ($idx + 2) . ": " . $e->getMessage();
        }
    }
    
    return [
        'success' => $count > 0,
        'count' => $count,
        'errors' => $errors
    ];
}

include __DIR__ . '/../../partials/header.php';
include __DIR__ . '/../../partials/sidebar.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-0">📥 Import Produits</h2>
                <p class="text-muted mb-0">Importer une liste de produits depuis Excel ou CSV</p>
            </div>
            <a href="<?= url_for('admin/catalogue/produits.php') ?>" class="btn btn-secondary">← Retour</a>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger" role="alert">
                <h5>❌ Erreurs</h5>
                <ul class="mb-0">
                    <?php foreach ($errors as $err): ?>
                        <li><?= htmlspecialchars($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success" role="alert">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <?php if ($step === '1'): ?>
                    <!-- ÉTAPE 1: UPLOAD -->
                    <h4 class="mb-3">Étape 1/3 - Sélectionner un fichier</h4>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken()) ?>">
                        <input type="hidden" name="action" value="upload">
                        
                        <div class="mb-3">
                            <label class="form-label"><strong>Fichier (CSV, XLSX ou XLS)</strong></label>
                            <input type="file" name="file" class="form-control" accept=".csv,.xlsx,.xls" required>
                            <small class="text-muted">
                                Taille max: 10 MB.<br>
                                <strong>Format attendu:</strong> code | designation | categorie_id | prix_unite
                            </small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Continuer →</button>
                    </form>
                    
                    <div class="mt-4 pt-4 border-top">
                        <h5>📋 Format attendu</h5>
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>code</th>
                                    <th>designation</th>
                                    <th>categorie_id</th>
                                    <th>prix_unite</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>CODE-001</td>
                                    <td>Produit exemple</td>
                                    <td>1</td>
                                    <td>1500.50</td>
                                </tr>
                                <tr>
                                    <td>CODE-002</td>
                                    <td>Autre produit</td>
                                    <td>2</td>
                                    <td>2000.00</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                <?php elseif ($step === '2' && !empty($preview)): ?>
                    <!-- ÉTAPE 2: APERÇU -->
                    <h4 class="mb-3">Étape 2/3 - Aperçu des données</h4>
                    
                    <div class="alert alert-info">
                        <strong><?= count($preview) ?> ligne(s)</strong> détectées dans le fichier
                    </div>
                    
                    <div class="table-responsive mb-3">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <?php foreach (array_keys($preview[0]) as $header): ?>
                                        <th><?= htmlspecialchars($header) ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($preview, 0, 10) as $idx => $row): ?>
                                    <tr>
                                        <td><?= $idx + 1 ?></td>
                                        <?php foreach ($row as $value): ?>
                                            <td><?= htmlspecialchars(substr($value, 0, 50)) ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if (count($preview) > 10): ?>
                        <small class="text-muted">+ <?= count($preview) - 10 ?> autres lignes</small>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken()) ?>">
                        <input type="hidden" name="action" value="preview">
                        <input type="hidden" name="step" value="2">
                        
                        <a href="<?= url_for('admin/catalogue/import.php?step=1') ?>" class="btn btn-secondary">← Précédent</a>
                        <button type="submit" class="btn btn-primary" name="action" value="import" formaction="<?= url_for('admin/catalogue/import.php?step=3') ?>" formmethod="POST">Continuer →</button>
                    </form>

                <?php elseif ($step === '3'): ?>
                    <!-- ÉTAPE 3: CONFIRMATION -->
                    <h4 class="mb-3">Étape 3/3 - Confirmation</h4>
                    
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken()) ?>">
                        <input type="hidden" name="action" value="import">
                        <input type="hidden" name="step" value="3">
                        
                        <div class="alert alert-warning">
                            <strong>⚠️ Attention:</strong> Assurez-vous que les données sont correctes avant de continuer.<br>
                            Les produits avec un code existant seront ignorés.
                        </div>
                        
                        <a href="<?= url_for('admin/catalogue/import.php?step=1') ?>" class="btn btn-secondary">← Recommencer</a>
                        <button type="submit" class="btn btn-danger">Importer les produits</button>
                    </form>

                <?php else: ?>
                    <p class="text-muted">Erreur: aucun fichier en cours de traitement.</p>
                    <a href="<?= url_for('admin/catalogue/import.php?step=1') ?>" class="btn btn-primary">Recommencer</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../partials/footer.php'; ?>
