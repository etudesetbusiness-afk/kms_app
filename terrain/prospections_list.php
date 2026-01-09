<?php
// terrain/prospections_list.php - Refonte CRM prospections terrain
require_once __DIR__ . '/../security.php';
exigerConnexion();
exigerPermission('CLIENTS_CREER');

global $pdo;

// Vérifie l'existence d'une table avant de l'utiliser (pour éviter les erreurs en cas de migration manquante)
function tableExiste(PDO $pdo, string $table): bool {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?");
    $stmt->execute([$table]);
    return (int)$stmt->fetchColumn() > 0;
}

// Vérifie l'existence d'une colonne dans une table
function colonneExiste(PDO $pdo, string $table, string $colonne): bool {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?");
    $stmt->execute([$table, $colonne]);
    return (int)$stmt->fetchColumn() > 0;
}

// Normalise un téléphone Cameroun (retourne 9 chiffres ou null)
function normaliserTelephone(string $tel): ?string {
    $digits = preg_replace('/\D+/', '', $tel);
    if ($digits === null) {
        return null;
    }
    if (str_starts_with($digits, '237')) {
        $digits = substr($digits, 3);
    }
    if (strlen($digits) > 9) {
        $digits = substr($digits, -9);
    }
    return strlen($digits) === 9 ? $digits : null;
}

$utilisateur    = utilisateurConnecte();
$commercialId   = (int)$utilisateur['id'];
$csrfToken      = getCsrfToken();
$flashSuccess   = $_SESSION['flash_success'] ?? null;
unset($_SESSION['flash_success']);

$hasProspectRelances = tableExiste($pdo, 'prospect_relances');
$hasProspectNotes    = tableExiste($pdo, 'prospect_notes');

// Colonnes requises par la refonte CRM
$colonnesRequises = [
    'statut_crm', 'tag_activite', 'date_relance', 'canal_relance', 'message_relance',
    'telephone', 'email', 'latitude', 'longitude', 'adresse_gps', 'resultat', 'prochaine_etape'
];
$colonnesManquantes = [];
foreach ($colonnesRequises as $col) {
    if (!colonneExiste($pdo, 'prospections_terrain', $col)) {
        $colonnesManquantes[] = $col;
    }
}
$schemaIncomplet = !empty($colonnesManquantes);

$dateDebut      = $_GET['date_debut'] ?? '';
$dateFin        = $_GET['date_fin'] ?? '';
$statutFiltre   = $_GET['statut_crm'] ?? '';
$zoneFiltre     = trim($_GET['zone'] ?? '');
$tagFiltre      = $_GET['tag_activite'] ?? '';
$relanceRetard  = isset($_GET['relances_retard']) ? (bool)$_GET['relances_retard'] : false;

// Liste des commerciaux pour filtre Admin/Direction
$commerciaux = [];
if (peutVoirToutesProspections()) {
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

$erreurs = [];

// On bloque seulement la soumission si le schéma est incomplet, mais on évite de doubler les messages en lecture seule
if ($schemaIncomplet && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $erreurs[] = "Schéma incomplet : colonnes manquantes sur prospections_terrain (" . implode(', ', $colonnesManquantes) . "). Exécutez la migration CRM (ex: 004_prospections_crm.sql).";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$schemaIncomplet) {
    $csrf = $_POST['csrf_token'] ?? '';
    verifierCsrf($csrf);

    $prospectNom     = trim($_POST['prospect_nom'] ?? '');
    $telephoneInput  = $_POST['telephone'] ?? '';
    $telephone       = normaliserTelephone($telephoneInput);
    $email           = trim($_POST['email'] ?? '');
    $secteur         = trim($_POST['secteur'] ?? '');
    $besoinIdentifie = trim($_POST['besoin_identifie'] ?? '');
    $actionMenee     = trim($_POST['action_menee'] ?? '');
    $resultat        = trim($_POST['resultat'] ?? '');
    $prochaineEtape  = trim($_POST['prochaine_etape'] ?? '');
    $statutCrm       = $_POST['statut_crm'] ?? 'PROSPECT';
    $tagActivite     = $_POST['tag_activite'] ?? null;
    $dateRelance     = $_POST['date_relance'] ?? null;
    $canalRelance    = $_POST['canal_relance'] ?? null;
    $messageRelance  = trim($_POST['message_relance'] ?? '');
    $latitude        = $_POST['latitude'] ?? null;
    $longitude       = $_POST['longitude'] ?? null;
    $adresseGps      = trim($_POST['adresse_gps'] ?? '');
    $dateProspection = date('Y-m-d');
    $heureProspection= date('H:i:s');

    if ($prospectNom === '') {
        $erreurs[] = "Le nom du prospect est obligatoire.";
    }
    if ($secteur === '') {
        $erreurs[] = "La zone/secteur est obligatoire.";
    }
    if ($telephone === null) {
        $erreurs[] = "Téléphone invalide : 9 chiffres requis (Cameroun).";
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreurs[] = "Adresse email invalide.";
    }
    if ($statutCrm === '') {
        $statutCrm = 'PROSPECT';
    }

    if ($telephone && empty($erreurs)) {
        $stmtDup = $pdo->prepare("SELECT COUNT(*) FROM prospections_terrain WHERE telephone = ?");
        $stmtDup->execute([$telephone]);
        $exists = (int)$stmtDup->fetchColumn();
        if ($exists > 0) {
            $erreurs[] = "Ce numéro de téléphone est déjà enregistré dans les prospections.";
        }
    }

    if (empty($erreurs)) {
        $stmtIns = $pdo->prepare("
            INSERT INTO prospections_terrain (
                date_prospection, heure_prospection, prospect_nom, secteur,
                telephone, email, besoin_identifie, action_menee, resultat,
                prochaine_etape, statut_crm, tag_activite, date_relance,
                canal_relance, message_relance, latitude, longitude,
                adresse_gps, client_id, commercial_id
            ) VALUES (
                :date_prospection, :heure_prospection, :prospect_nom, :secteur,
                :telephone, :email, :besoin_identifie, :action_menee, :resultat,
                :prochaine_etape, :statut_crm, :tag_activite, :date_relance,
                :canal_relance, :message_relance, :latitude, :longitude,
                :adresse_gps, NULL, :commercial_id
            )
        ");
        $stmtIns->execute([
            'date_prospection'  => $dateProspection,
            'heure_prospection' => $heureProspection,
            'prospect_nom'      => $prospectNom,
            'secteur'           => $secteur,
            'telephone'         => $telephone,
            'email'             => $email ?: null,
            'besoin_identifie'  => $besoinIdentifie ?: null,
            'action_menee'      => $actionMenee ?: null,
            'resultat'          => $resultat ?: null,
            'prochaine_etape'   => $prochaineEtape ?: null,
            'statut_crm'        => $statutCrm,
            'tag_activite'      => $tagActivite ?: null,
            'date_relance'      => $dateRelance ?: null,
            'canal_relance'     => $canalRelance ?: null,
            'message_relance'   => $messageRelance ?: null,
            'latitude'          => $latitude ?: null,
            'longitude'         => $longitude ?: null,
            'adresse_gps'       => $adresseGps ?: null,
            'commercial_id'     => $commercialId
        ]);

        $_SESSION['flash_success'] = "Prospection enregistrée avec succès.";
        header('Location: ' . url_for('terrain/prospections_list.php'));
        exit;
    }
}

// Règles de visibilité par rôle
// - ADMIN/DIRECTION : voient toutes les prospections
// - Autres (TERRAIN) : voient uniquement leurs propres prospections
$voirTout = peutVoirToutesProspections();
$commercialFiltre = $_GET['commercial_id'] ?? '';

if ($voirTout) {
    // Admin/Direction peuvent filtrer par commercial ou voir tout
    $where  = [];
    $params = [];
    if ($commercialFiltre !== '') {
        $where[] = "pt.commercial_id = ?";
        $params[] = (int)$commercialFiltre;
    }
} else {
    // Commercial : uniquement ses propres prospections
    $where  = ["pt.commercial_id = ?"];
    $params = [$commercialId];
}

if ($dateDebut !== '') {
    $where[] = "pt.date_prospection >= ?";
    $params[] = $dateDebut;
}
if ($dateFin !== '') {
    $where[] = "pt.date_prospection <= ?";
    $params[] = $dateFin;
}
if ($statutFiltre !== '') {
    $where[] = "pt.statut_crm = ?";
    $params[] = $statutFiltre;
}
if ($zoneFiltre !== '') {
    $where[] = "pt.secteur LIKE ?";
    $params[] = '%' . $zoneFiltre . '%';
}
if ($tagFiltre !== '') {
    $where[] = "pt.tag_activite = ?";
    $params[] = $tagFiltre;
}
if ($relanceRetard && $hasProspectRelances) {
    $where[] = "EXISTS (SELECT 1 FROM prospect_relances pr WHERE pr.prospection_id = pt.id AND pr.statut = 'A_FAIRE' AND pr.date_relance_prevue < CURDATE())";
}

$whereSql = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';
$prospections = [];
$sql = null;

if (!$schemaIncomplet) {
    $relanceSelect = "NULL AS prochaine_relance, NULL AS prochain_canal, 0 AS relances_retard";
    if ($hasProspectRelances) {
        $relanceSelect = "
            (
                SELECT date_relance_prevue FROM prospect_relances pr
                WHERE pr.prospection_id = pt.id AND pr.statut = 'A_FAIRE'
                ORDER BY pr.date_relance_prevue ASC
                LIMIT 1
            ) AS prochaine_relance,
            (
                SELECT canal FROM prospect_relances pr
                WHERE pr.prospection_id = pt.id AND pr.statut = 'A_FAIRE'
                ORDER BY pr.date_relance_prevue ASC
                LIMIT 1
            ) AS prochain_canal,
            (
                SELECT COUNT(*) FROM prospect_relances pr
                WHERE pr.prospection_id = pt.id AND pr.statut = 'A_FAIRE' AND pr.date_relance_prevue < CURDATE()
            ) AS relances_retard";
    }

    $notesSelect = $hasProspectNotes
        ? "(SELECT COUNT(*) FROM prospect_notes pn WHERE pn.prospection_id = pt.id) AS nb_notes"
        : "0 AS nb_notes";

    $sql = "
        SELECT pt.*, $relanceSelect, $notesSelect
        FROM prospections_terrain pt
        $whereSql
        ORDER BY pt.date_prospection DESC, pt.heure_prospection DESC, pt.id DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $prospections = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if (!$schemaIncomplet && isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="prospections_terrain.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Date', 'Heure', 'Nom', 'Téléphone', 'Email', 'Secteur', 'Statut CRM', 'Résultat', 'Prochaine étape', 'Prochaine relance', 'Canal relance']);
    foreach ($prospections as $p) {
        fputcsv($out, [
            $p['date_prospection'],
            $p['heure_prospection'],
            $p['prospect_nom'],
            '+237' . $p['telephone'],
            $p['email'],
            $p['secteur'],
            $p['statut_crm'],
            $p['resultat'],
            $p['prochaine_etape'],
            $p['prochaine_relance'],
            $p['prochain_canal']
        ]);
    }
    fclose($out);
    exit;
}

$stats = ['total_today' => 0, 'devis_emis' => 0, 'commandes' => 0, 'total_filtre' => 0];
if (!$schemaIncomplet) {
    // Construire la clause WHERE pour les stats du jour
    $statsWhereParts = [];
    if (!empty($where)) {
        $statsWhereParts = $where;
    }
    $statsWhereParts[] = "pt.date_prospection = CURDATE()";
    $statsWhereSql = 'WHERE ' . implode(' AND ', $statsWhereParts);
    
    $stmtStats = $pdo->prepare("
        SELECT 
            COUNT(*) AS total_today,
            SUM(CASE WHEN statut_crm = 'DEVIS_EMIS' THEN 1 ELSE 0 END) AS devis_emis,
            SUM(CASE WHEN statut_crm = 'COMMANDE_OBTENUE' THEN 1 ELSE 0 END) AS commandes,
            COUNT(*) AS total_filtre
        FROM prospections_terrain pt
        $statsWhereSql
    ");
    $stmtStats->execute($params);
    $stats = $stmtStats->fetch(PDO::FETCH_ASSOC) ?: $stats;
}

// KPI sur l'ensemble des résultats filtrés
$kpiFiltre = [
    'total_filtre' => count($prospections),
    'relances_retard' => 0,
    'prospects_chauds' => 0,
    'commandes_filtre' => 0,
    'conversion_commandes' => 0.0
];

if (!$schemaIncomplet) {
    foreach ($prospections as $p) {
        $kpiFiltre['relances_retard'] += (int)($p['relances_retard'] ?? 0);
        if (in_array($p['statut_crm'], ['PROSPECT_CHAUD', 'INTERESSE'], true)) {
            $kpiFiltre['prospects_chauds']++;
        }
        if ($p['statut_crm'] === 'COMMANDE_OBTENUE') {
            $kpiFiltre['commandes_filtre']++;
        }
    }

    if ($kpiFiltre['total_filtre'] > 0) {
        $kpiFiltre['conversion_commandes'] = round(($kpiFiltre['commandes_filtre'] / $kpiFiltre['total_filtre']) * 100, 1);
    }
}

include __DIR__ . '/../partials/header.php';
include __DIR__ . '/../partials/sidebar.php';
?>

<style>
.container-fluid { max-width: 1400px; }
.section-title { font-size: 1.25rem; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 0.5rem; }
.badge-soft { padding: 0.35rem 0.7rem; border-radius: 999px; font-weight: 600; font-size: 0.85rem; }
.card-shadow { box-shadow: 0 2px 10px rgba(0,0,0,0.08); border: 1px solid #e2e8f0; }
.prospect-card { border-left: 4px solid #4f46e5; }
.action-chip { border: 1px solid #e5e7eb; border-radius: 12px; padding: 0.35rem 0.65rem; display: inline-flex; align-items: center; gap: 0.35rem; font-size: 0.9rem; }
.quick-actions .btn { min-width: 44px; }
</style>

<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-1"><i class="bi bi-geo-alt-fill me-2"></i>Prospections terrain (CRM)</h1>
            <p class="text-muted small mb-0">Commercial : <?= htmlspecialchars($utilisateur['nom_complet']) ?></p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= url_for('terrain/prospections_list.php?export=csv') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-download"></i> Export CSV</a>
            <button class="btn btn-outline-primary btn-sm" onclick="window.print()"><i class="bi bi-printer"></i> Imprimer</button>
        </div>
    </div>

    <?php if ($flashSuccess): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= htmlspecialchars($flashSuccess) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($erreurs)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <strong>Erreurs :</strong>
            <ul class="mb-0 mt-2">
                <?php foreach ($erreurs as $err): ?>
                    <li><?= htmlspecialchars($err) ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($schemaIncomplet): ?>
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle"></i> Schéma incomplet : colonnes manquantes sur prospections_terrain (<?= htmlspecialchars(implode(', ', $colonnesManquantes)) ?>). Merci d'exécuter la migration CRM (ex : 004_prospections_crm.sql) avant d'utiliser la page.
        </div>
    <?php endif; ?>

    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card card-shadow text-center">
                <div class="card-body">
                    <div class="text-muted">Prospections aujourd'hui</div>
                    <div class="h3 mb-0"><?= (int)$stats['total_today'] ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-shadow text-center">
                <div class="card-body">
                    <div class="text-muted">Devis émis</div>
                    <div class="h3 mb-0"><?= (int)$stats['devis_emis'] ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-shadow text-center">
                <div class="card-body">
                    <div class="text-muted">Commandes obtenues</div>
                    <div class="h3 mb-0"><?= (int)$stats['commandes'] ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-shadow text-center">
                <div class="card-body">
                    <div class="text-muted">Relances en retard</div>
                    <div class="h3 mb-0"><?= (int)$kpiFiltre['relances_retard'] ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card card-shadow text-center">
                <div class="card-body">
                    <div class="text-muted">Prospects filtrés</div>
                    <div class="h3 mb-0"><?= (int)$kpiFiltre['total_filtre'] ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-shadow text-center">
                <div class="card-body">
                    <div class="text-muted">Conversion commandes</div>
                    <div class="h3 mb-0"><?= number_format($kpiFiltre['conversion_commandes'], 1) ?>%</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-shadow text-center">
                <div class="card-body">
                    <div class="text-muted">Prospects chauds</div>
                    <div class="h3 mb-0"><?= (int)$kpiFiltre['prospects_chauds'] ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-shadow mb-4">
        <div class="card-header bg-primary text-white"><strong><i class="bi bi-plus-circle"></i> Nouvelle prospection (20-30 sec)</strong></div>
        <div class="card-body">
            <form method="post" id="prospectionForm">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="latitude" id="latitude">
                <input type="hidden" name="longitude" id="longitude">
                <input type="hidden" name="adresse_gps" id="adresse_gps">

                <div class="accordion" id="accordeonProspect">
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingEssentiel">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEssentiel" aria-expanded="true">
                                1. Essentiel (obligatoire)
                            </button>
                        </h2>
                        <div id="collapseEssentiel" class="accordion-collapse collapse show" data-bs-parent="#accordeonProspect">
                            <div class="accordion-body row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Nom du prospect *</label>
                                    <input type="text" name="prospect_nom" class="form-control" required placeholder="Nom complet">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Téléphone (9 chiffres) *</label>
                                    <input type="tel" name="telephone" id="telephone" class="form-control" required pattern="[0-9 +]{9,15}" placeholder="695123456">
                                    <div class="form-text">Validation automatique Cameroun.</div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Secteur / Zone *</label>
                                    <input type="text" name="secteur" id="secteur" class="form-control" required placeholder="Ex: Bonabéri">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingDetails">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDetails">
                                2. Détails (optionnel)
                            </button>
                        </h2>
                        <div id="collapseDetails" class="accordion-collapse collapse" data-bs-parent="#accordeonProspect">
                            <div class="accordion-body row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" class="form-control" placeholder="prospect@mail.com">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Tag activité</label>
                                    <select name="tag_activite" class="form-select">
                                        <option value="">-- Choisir --</option>
                                        <option value="QUINCAILLERIE">Quincaillerie</option>
                                        <option value="MENUISERIE">Menuiserie</option>
                                        <option value="AUTRE">Autre</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Statut CRM</label>
                                    <select name="statut_crm" class="form-select">
                                        <option value="PROSPECT">Prospect</option>
                                        <option value="INTERESSE">Intéressé</option>
                                        <option value="PROSPECT_CHAUD">Prospect chaud</option>
                                        <option value="DEVIS_DEMANDE">Devis demandé</option>
                                        <option value="DEVIS_EMIS">Devis émis</option>
                                        <option value="COMMANDE_OBTENUE">Commande obtenue</option>
                                        <option value="CLIENT_ACTIF">Client actif</option>
                                        <option value="FIDELISATION">Fidélisation</option>
                                        <option value="PERDU">Perdu</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Besoin identifié</label>
                                    <textarea name="besoin_identifie" class="form-control" rows="2" placeholder="Besoin du prospect"></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Action menée</label>
                                    <textarea name="action_menee" class="form-control" rows="2" placeholder="Action réalisée"></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Résultat</label>
                                    <select name="resultat" class="form-select">
                                        <option value="">-- Sélectionner --</option>
                                        <option value="Interesse">Intéressé</option>
                                        <option value="Devis demande">Devis demandé</option>
                                        <option value="Devis emis">Devis émis</option>
                                        <option value="Commande obtenue">Commande obtenue</option>
                                        <option value="Pas interesse">Pas intéressé</option>
                                        <option value="A rappeler">À rappeler plus tard</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Prochaine étape</label>
                                    <textarea name="prochaine_etape" class="form-control" rows="2" placeholder="Suite à donner"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingRelance">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseRelance">
                                3. Relance (optionnel)
                            </button>
                        </h2>
                        <div id="collapseRelance" class="accordion-collapse collapse" data-bs-parent="#accordeonProspect">
                            <div class="accordion-body row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Date de relance</label>
                                    <input type="date" name="date_relance" class="form-control">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Canal</label>
                                    <select name="canal_relance" class="form-select">
                                        <option value="">-- Choisir --</option>
                                        <option value="WHATSAPP">WhatsApp</option>
                                        <option value="APPEL">Appel</option>
                                        <option value="SMS">SMS</option>
                                        <option value="EMAIL">Email</option>
                                        <option value="VISITE">Visite</option>
                                    </select>
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <button type="button" class="btn btn-outline-success w-100" id="btnGeoloc"><i class="bi bi-geo-alt"></i> Géolocaliser</button>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Message relance</label>
                                    <textarea name="message_relance" class="form-control" rows="2" placeholder="Note pour la relance"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-3 d-grid d-md-flex justify-content-md-end gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Enregistrer</button>
                    <button type="reset" class="btn btn-outline-secondary">Réinitialiser</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card card-shadow mb-4">
        <div class="card-header"><strong><i class="bi bi-funnel"></i> Filtres</strong></div>
        <div class="card-body">
            <form class="row g-3">
                <?php if ($voirTout): ?>
                <div class="col-md-2">
                    <label class="form-label">Commercial</label>
                    <select name="commercial_id" class="form-select">
                        <option value="">Tous</option>
                        <?php foreach ($commerciaux as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= $commercialFiltre == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['nom_complet']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div class="col-md-2">
                    <label class="form-label">Du</label>
                    <input type="date" name="date_debut" class="form-control" value="<?= htmlspecialchars($dateDebut) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Au</label>
                    <input type="date" name="date_fin" class="form-control" value="<?= htmlspecialchars($dateFin) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Statut CRM</label>
                    <select name="statut_crm" class="form-select">
                        <option value="">Tous</option>
                        <option value="PROSPECT" <?= $statutFiltre==='PROSPECT'?'selected':'' ?>>Prospect</option>
                        <option value="INTERESSE" <?= $statutFiltre==='INTERESSE'?'selected':'' ?>>Intéressé</option>
                        <option value="PROSPECT_CHAUD" <?= $statutFiltre==='PROSPECT_CHAUD'?'selected':'' ?>>Prospect chaud</option>
                        <option value="DEVIS_DEMANDE" <?= $statutFiltre==='DEVIS_DEMANDE'?'selected':'' ?>>Devis demandé</option>
                        <option value="DEVIS_EMIS" <?= $statutFiltre==='DEVIS_EMIS'?'selected':'' ?>>Devis émis</option>
                        <option value="COMMANDE_OBTENUE" <?= $statutFiltre==='COMMANDE_OBTENUE'?'selected':'' ?>>Commande obtenue</option>
                        <option value="CLIENT_ACTIF" <?= $statutFiltre==='CLIENT_ACTIF'?'selected':'' ?>>Client actif</option>
                        <option value="FIDELISATION" <?= $statutFiltre==='FIDELISATION'?'selected':'' ?>>Fidélisation</option>
                        <option value="PERDU" <?= $statutFiltre==='PERDU'?'selected':'' ?>>Perdu</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Tag activité</label>
                    <select name="tag_activite" class="form-select">
                        <option value="">Tous</option>
                        <option value="QUINCAILLERIE" <?= $tagFiltre==='QUINCAILLERIE'?'selected':'' ?>>Quincaillerie</option>
                        <option value="MENUISERIE" <?= $tagFiltre==='MENUISERIE'?'selected':'' ?>>Menuiserie</option>
                        <option value="AUTRE" <?= $tagFiltre==='AUTRE'?'selected':'' ?>>Autre</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Zone / Secteur</label>
                    <input type="text" name="zone" class="form-control" placeholder="Ex: Bonabéri" value="<?= htmlspecialchars($zoneFiltre) ?>">
                </div>
                <div class="col-md-1 d-flex align-items-center">
                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" id="relances_retard" name="relances_retard" value="1" <?= $relanceRetard?'checked':'' ?>>
                        <label class="form-check-label" for="relances_retard">Relances en retard</label>
                    </div>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Filtrer</button>
                    <a href="<?= url_for('terrain/prospections_list.php') ?>" class="btn btn-outline-secondary">Réinitialiser</a>
                </div>
            </form>
        </div>
    </div>

    <div class="mb-3">
        <h5 class="section-title"><i class="bi bi-list-ul"></i> Historique <span class="badge bg-primary"><?= count($prospections) ?></span></h5>
        <?php if (empty($prospections)): ?>
            <div class="card card-shadow">
                <div class="card-body text-center text-muted">Aucune prospection pour les filtres sélectionnés.</div>
            </div>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($prospections as $p): ?>
                    <?php 
                        $badgeColor = 'secondary';
                        $statut = $p['statut_crm'];
                        if (in_array($statut, ['PROSPECT','INTERESSE'])) { $badgeColor = 'warning'; }
                        if (in_array($statut, ['DEVIS_DEMANDE','DEVIS_EMIS'])) { $badgeColor = 'info'; }
                        if (in_array($statut, ['COMMANDE_OBTENUE','CLIENT_ACTIF','FIDELISATION'])) { $badgeColor = 'success'; }
                        if ($statut === 'PERDU') { $badgeColor = 'danger'; }
                        $telAffichage = $p['telephone'] ? '+237 ' . chunk_split($p['telephone'], 3, ' ') : 'N/A';
                        $telLien = $p['telephone'] ? '+237' . $p['telephone'] : '';
                    ?>
                    <div class="col-12">
                        <div class="card card-shadow prospect-card p-3">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <div class="fw-bold h5 mb-0"><?= htmlspecialchars($p['prospect_nom']) ?></div>
                                    <div class="text-muted small">Le <?= date('d/m/Y', strtotime($p['date_prospection'])) ?> à <?= date('H:i', strtotime($p['heure_prospection'])) ?></div>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge badge-soft bg-<?= $badgeColor ?> text-white"><?= str_replace('_',' ', $p['statut_crm']) ?></span>
                                    <a href="<?= url_for('terrain/prospect_detail.php?id=' . (int)$p['id']) ?>" class="btn btn-outline-primary btn-sm">Fiche CRM</a>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-3">
                                    <div class="action-chip"><i class="bi bi-telephone"></i> <?= htmlspecialchars($telAffichage) ?></div>
                                    <?php if ($p['email']): ?>
                                        <div class="action-chip mt-2"><i class="bi bi-envelope"></i> <?= htmlspecialchars($p['email']) ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-muted small">Secteur</div>
                                    <div class="fw-semibold"><?= htmlspecialchars($p['secteur']) ?></div>
                                    <?php if ($p['tag_activite']): ?>
                                        <span class="badge bg-light text-dark mt-1">Tag: <?= $p['tag_activite'] ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-muted small">Prochaine relance</div>
                                    <?php if ($p['prochaine_relance']): ?>
                                        <div class="fw-semibold"><?= date('d/m/Y', strtotime($p['prochaine_relance'])) ?> (<?= $p['prochain_canal'] ?>)</div>
                                    <?php else: ?>
                                        <span class="text-muted">Aucune</span>
                                    <?php endif; ?>
                                    <?php if ((int)$p['relances_retard'] > 0): ?>
                                        <div class="text-danger small mt-1"><i class="bi bi-exclamation-triangle"></i> Relances en retard : <?= (int)$p['relances_retard'] ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-3">
                                    <?php if ($p['adresse_gps']): ?>
                                        <a class="action-chip" target="_blank" href="https://www.google.com/maps?q=<?= $p['latitude'] ?>,<?= $p['longitude'] ?>">
                                            <i class="bi bi-geo-alt"></i> Voir carte
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($p['besoin_identifie']): ?>
                                        <div class="text-muted small mt-2">Besoin</div>
                                        <div class="fw-semibold small"><?= nl2br(htmlspecialchars($p['besoin_identifie'])) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="mt-3 quick-actions d-flex flex-wrap gap-2">
                                <?php if ($telLien): ?>
                                    <a class="btn btn-outline-primary btn-sm" href="tel:<?= $telLien ?>"><i class="bi bi-telephone"></i></a>
                                    <a class="btn btn-outline-success btn-sm" target="_blank" href="https://wa.me/237<?= $p['telephone'] ?>"><i class="bi bi-whatsapp"></i></a>
                                <?php endif; ?>
                                <?php if ($p['email']): ?>
                                    <a class="btn btn-outline-info btn-sm" href="mailto:<?= htmlspecialchars($p['email']) ?>"><i class="bi bi-envelope"></i></a>
                                <?php endif; ?>
                                <a class="btn btn-outline-secondary btn-sm" href="<?= url_for('terrain/prospect_detail.php?id=' . (int)$p['id']) ?>"><i class="bi bi-journal-text"></i></a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
const btnGeoloc = document.getElementById('btnGeoloc');
if (btnGeoloc) {
    btnGeoloc.addEventListener('click', () => {
        if (!navigator.geolocation) {
            alert('Géolocalisation non supportée.');
            return;
        }
        btnGeoloc.disabled = true;
        btnGeoloc.innerText = 'Recherche position...';
        navigator.geolocation.getCurrentPosition((pos) => {
            document.getElementById('latitude').value = pos.coords.latitude;
            document.getElementById('longitude').value = pos.coords.longitude;
            btnGeoloc.innerText = 'Position capturée ✓';
        }, (err) => {
            alert('Géolocalisation impossible: ' + err.message);
            btnGeoloc.innerText = 'Géolocaliser';
            btnGeoloc.disabled = false;
        }, { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 });
    });
}

const telInput = document.getElementById('telephone');
if (telInput) {
    telInput.addEventListener('blur', () => {
        const digits = (telInput.value || '').replace(/\D+/g, '');
        if (digits.length < 9) {
            telInput.setCustomValidity('Téléphone : 9 chiffres requis.');
        } else {
            telInput.setCustomValidity('');
        }
    });
}
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>
