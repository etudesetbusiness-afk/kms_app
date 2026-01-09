<?php
// terrain/reporting/list.php - Liste des rapports hebdomadaires terrain
require_once __DIR__ . '/../../security.php';
exigerConnexion();
exigerPermission('CLIENTS_CREER');

global $pdo;

$utilisateur  = utilisateurConnecte();
$userId       = (int)$utilisateur['id'];
$csrfToken    = getCsrfToken();
$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError   = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// Règles de visibilité par rôle
$voirTout = peutVoirToutesProspections();

// Liste des commerciaux pour filtre Admin/Direction
$commerciaux = [];
if ($voirTout) {
    $stmtComm = $pdo->query("
        SELECT u.id, u.nom_complet 
        FROM utilisateurs u
        JOIN utilisateur_role ur ON u.id = ur.utilisateur_id
        JOIN roles r ON ur.role_id = r.id
        WHERE r.code IN ('TERRAIN','SHOWROOM','ADMIN') AND u.actif = 1
        ORDER BY u.nom_complet
    ");
    $commerciaux = $stmtComm->fetchAll(PDO::FETCH_ASSOC);
}

// Filtres
$commercialFiltre = $_GET['commercial_id'] ?? '';
$semaineFiltre    = $_GET['semaine'] ?? '';
$statutFiltre     = $_GET['statut'] ?? '';
$anneeFiltre      = $_GET['annee'] ?? date('Y');

// Construction de la requête avec visibilité
if ($voirTout) {
    $where  = [];
    $params = [];
    if ($commercialFiltre !== '') {
        $where[] = "rt.commercial_id = ?";
        $params[] = (int)$commercialFiltre;
    }
} else {
    // Commercial : uniquement ses propres rapports
    $where  = ["rt.commercial_id = ?"];
    $params = [$userId];
}

if ($semaineFiltre !== '') {
    $where[] = "rt.numero_semaine = ?";
    $params[] = (int)$semaineFiltre;
}
if ($anneeFiltre !== '') {
    $where[] = "rt.annee = ?";
    $params[] = (int)$anneeFiltre;
}
if ($statutFiltre !== '') {
    $where[] = "rt.statut = ?";
    $params[] = $statutFiltre;
}

$whereSql = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "
    SELECT rt.*, 
           u.nom_complet AS commercial_nom,
           (SELECT COUNT(*) FROM reporting_terrain_journal rtj WHERE rtj.reporting_id = rt.id) AS nb_jours,
           (SELECT SUM(ca_realise) FROM reporting_terrain_journal rtj WHERE rtj.reporting_id = rt.id) AS total_ca
    FROM reporting_terrain rt
    LEFT JOIN utilisateurs u ON rt.commercial_id = u.id
    {$whereSql}
    ORDER BY rt.annee DESC, rt.numero_semaine DESC, rt.created_at DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rapports = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcul de la semaine courante
$semaineActuelle = (int)date('W');
$anneeActuelle   = (int)date('Y');

// Vérifier si rapport de la semaine courante existe pour l'utilisateur
$stmtCheck = $pdo->prepare("
    SELECT id FROM reporting_terrain 
    WHERE commercial_id = ? AND annee = ? AND numero_semaine = ?
");
$stmtCheck->execute([$userId, $anneeActuelle, $semaineActuelle]);
$rapportSemaineCourante = $stmtCheck->fetch(PDO::FETCH_ASSOC);

// Statuts disponibles
$statuts = [
    'BROUILLON'  => ['label' => 'Brouillon', 'badge' => 'secondary'],
    'SOUMIS'     => ['label' => 'Soumis', 'badge' => 'primary'],
    'VALIDE'     => ['label' => 'Validé', 'badge' => 'success'],
    'REJETE'     => ['label' => 'Rejeté', 'badge' => 'danger'],
];

require_once __DIR__ . '/../../templates/header.php';
?>

<div class="container-fluid py-3">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-1"><i class="bi bi-journal-text me-2"></i>Rapports Hebdomadaires Terrain</h4>
            <small class="text-muted">
                <?php if ($voirTout): ?>
                    Tous les rapports des commerciaux
                <?php else: ?>
                    Mes rapports hebdomadaires
                <?php endif; ?>
            </small>
        </div>
        <div>
            <?php if (!$rapportSemaineCourante): ?>
                <a href="<?= url_for('terrain/reporting/edit.php?semaine=' . $semaineActuelle . '&annee=' . $anneeActuelle) ?>" 
                   class="btn btn-success">
                    <i class="bi bi-plus-lg me-1"></i>Nouveau rapport S<?= $semaineActuelle ?>
                </a>
            <?php else: ?>
                <a href="<?= url_for('terrain/reporting/edit.php?id=' . $rapportSemaineCourante['id']) ?>" 
                   class="btn btn-outline-primary">
                    <i class="bi bi-pencil me-1"></i>Continuer S<?= $semaineActuelle ?>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Flash Messages -->
    <?php if ($flashSuccess): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($flashSuccess) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if ($flashError): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($flashError) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Filtres -->
    <div class="card mb-3">
        <div class="card-body py-2">
            <form method="get" class="row g-2 align-items-end">
                <?php if ($voirTout): ?>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Commercial</label>
                    <select name="commercial_id" class="form-select form-select-sm">
                        <option value="">Tous</option>
                        <?php foreach ($commerciaux as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= $commercialFiltre == $c['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['nom_complet']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Année</label>
                    <select name="annee" class="form-select form-select-sm">
                        <?php for ($y = $anneeActuelle; $y >= $anneeActuelle - 2; $y--): ?>
                            <option value="<?= $y ?>" <?= $anneeFiltre == $y ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Semaine</label>
                    <select name="semaine" class="form-select form-select-sm">
                        <option value="">Toutes</option>
                        <?php for ($w = 52; $w >= 1; $w--): ?>
                            <option value="<?= $w ?>" <?= $semaineFiltre == $w ? 'selected' : '' ?>>
                                S<?= $w ?><?= $w == $semaineActuelle && $anneeFiltre == $anneeActuelle ? ' (actuelle)' : '' ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Statut</label>
                    <select name="statut" class="form-select form-select-sm">
                        <option value="">Tous</option>
                        <?php foreach ($statuts as $code => $info): ?>
                            <option value="<?= $code ?>" <?= $statutFiltre === $code ? 'selected' : '' ?>>
                                <?= $info['label'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="bi bi-funnel me-1"></i>Filtrer
                    </button>
                    <a href="<?= url_for('terrain/reporting/list.php') ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-x-lg"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Liste des rapports -->
    <div class="card">
        <div class="card-header bg-white py-2">
            <strong><?= count($rapports) ?></strong> rapport(s)
        </div>
        <?php if (empty($rapports)): ?>
            <div class="card-body text-center text-muted py-5">
                <i class="bi bi-journal-x display-4 mb-3 d-block"></i>
                <p>Aucun rapport trouvé.</p>
                <a href="<?= url_for('terrain/reporting/edit.php?semaine=' . $semaineActuelle . '&annee=' . $anneeActuelle) ?>" 
                   class="btn btn-success">
                    <i class="bi bi-plus-lg me-1"></i>Créer mon premier rapport
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Semaine</th>
                            <?php if ($voirTout): ?>
                                <th>Commercial</th>
                            <?php endif; ?>
                            <th>Période</th>
                            <th class="text-center">Jours</th>
                            <th class="text-end">CA Réalisé</th>
                            <th class="text-center">Statut</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rapports as $r): ?>
                            <?php
                            $statutInfo = $statuts[$r['statut']] ?? ['label' => $r['statut'], 'badge' => 'secondary'];
                            $canEdit = ($r['statut'] === 'BROUILLON' || $r['statut'] === 'REJETE') 
                                        && ($voirTout || $r['commercial_id'] == $userId);
                            ?>
                            <tr>
                                <td>
                                    <strong>S<?= $r['numero_semaine'] ?></strong>
                                    <small class="text-muted">/<?= $r['annee'] ?></small>
                                </td>
                                <?php if ($voirTout): ?>
                                    <td><?= htmlspecialchars($r['commercial_nom'] ?? 'N/A') ?></td>
                                <?php endif; ?>
                                <td>
                                    <small>
                                        <?= $r['date_debut'] ? date('d/m', strtotime($r['date_debut'])) : '?' ?>
                                        → <?= $r['date_fin'] ? date('d/m', strtotime($r['date_fin'])) : '?' ?>
                                    </small>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark"><?= (int)$r['nb_jours'] ?>/6</span>
                                </td>
                                <td class="text-end">
                                    <?php if ($r['total_ca']): ?>
                                        <strong><?= number_format((float)$r['total_ca'], 0, ',', ' ') ?></strong> F
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-<?= $statutInfo['badge'] ?>">
                                        <?= $statutInfo['label'] ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= url_for('terrain/reporting/view.php?id=' . $r['id']) ?>" 
                                           class="btn btn-outline-secondary" title="Voir">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if ($canEdit): ?>
                                            <a href="<?= url_for('terrain/reporting/edit.php?id=' . $r['id']) ?>" 
                                               class="btn btn-outline-primary" title="Modifier">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="<?= url_for('terrain/reporting/pdf.php?id=' . $r['id']) ?>" 
                                           class="btn btn-outline-dark" title="PDF" target="_blank">
                                            <i class="bi bi-file-pdf"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Légende -->
    <div class="mt-3">
        <small class="text-muted">
            <i class="bi bi-info-circle me-1"></i>
            Les rapports en <span class="badge bg-secondary">Brouillon</span> peuvent être modifiés jusqu'à leur soumission.
            <?php if ($voirTout): ?>
                Les rapports <span class="badge bg-primary">Soumis</span> sont en attente de validation par la direction.
            <?php endif; ?>
        </small>
    </div>
</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
