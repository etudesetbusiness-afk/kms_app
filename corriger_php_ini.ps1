# Script de correction automatique du php.ini
# KMS Gestion - 15 Décembre 2025

$phpIniPath = "C:\xampp\php\php.ini"

Write-Host "╔═══════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║     CORRECTION AUTOMATIQUE PHP.INI                       ║" -ForegroundColor Cyan
Write-Host "╚═══════════════════════════════════════════════════════════╝" -ForegroundColor Cyan
Write-Host ""

# Vérifier si le fichier existe
if (-not (Test-Path $phpIniPath)) {
    Write-Host "❌ Fichier php.ini non trouvé: $phpIniPath" -ForegroundColor Red
    Write-Host ""
    Write-Host "Chemins alternatifs à vérifier:" -ForegroundColor Yellow
    Write-Host "  - C:\xampp\php\php.ini" -ForegroundColor Yellow
    Write-Host "  - C:\php\php.ini" -ForegroundColor Yellow
    Write-Host "  - C:\Program Files\PHP\php.ini" -ForegroundColor Yellow
    Write-Host ""
    
    # Demander le chemin manuellement
    $customPath = Read-Host "Entrez le chemin complet de php.ini (ou appuyez sur Entrée pour annuler)"
    
    if ($customPath -and (Test-Path $customPath)) {
        $phpIniPath = $customPath
    } else {
        Write-Host "❌ Opération annulée" -ForegroundColor Red
        exit 1
    }
}

Write-Host "📂 Fichier trouvé: $phpIniPath" -ForegroundColor Green
Write-Host ""

# Créer un backup
$backupPath = "$phpIniPath.backup.$(Get-Date -Format 'yyyyMMdd_HHmmss')"
Write-Host "💾 Création du backup..." -ForegroundColor Yellow
Copy-Item $phpIniPath $backupPath -Force
Write-Host "   ✅ Backup créé: $backupPath" -ForegroundColor Green
Write-Host ""

# Lire le contenu
$content = Get-Content $phpIniPath -Raw

# Afficher la configuration actuelle
Write-Host "📊 Configuration actuelle:" -ForegroundColor Yellow
$currentLine = $content | Select-String -Pattern '^error_reporting\s*=.*$' | Select-Object -First 1
if ($currentLine) {
    Write-Host "   $($currentLine.Line)" -ForegroundColor Gray
} else {
    Write-Host "   (non trouvée dans le fichier)" -ForegroundColor Gray
}
Write-Host ""

# Demander confirmation
Write-Host "🔧 Modifications à appliquer:" -ForegroundColor Cyan
Write-Host "   Remplacer: error_reporting = E_ALL" -ForegroundColor Red
Write-Host "   Par:       error_reporting = E_ALL & ~E_NOTICE" -ForegroundColor Green
Write-Host ""

$confirmation = Read-Host "Continuer? (O/N)"

if ($confirmation -ne 'O' -and $confirmation -ne 'o') {
    Write-Host "❌ Opération annulée" -ForegroundColor Red
    exit 0
}

# Appliquer les modifications
Write-Host ""
Write-Host "⚙️  Application des modifications..." -ForegroundColor Yellow

# Remplacer error_reporting
$modified = $false

# Pattern 1: error_reporting = E_ALL
if ($content -match 'error_reporting\s*=\s*E_ALL\s*(\r?\n)') {
    $content = $content -replace '(error_reporting\s*=\s*)E_ALL\s*(\r?\n)', "`$1E_ALL & ~E_NOTICE`$2"
    $modified = $true
    Write-Host "   ✅ Ligne modifiée: error_reporting = E_ALL & ~E_NOTICE" -ForegroundColor Green
}

# Pattern 2: error_reporting = 32767 (E_ALL en numérique)
if ($content -match 'error_reporting\s*=\s*32767\s*(\r?\n)') {
    $content = $content -replace '(error_reporting\s*=\s*)32767\s*(\r?\n)', "`$1E_ALL & ~E_NOTICE`$2"
    $modified = $true
    Write-Host "   ✅ Ligne modifiée: error_reporting = E_ALL & ~E_NOTICE (depuis 32767)" -ForegroundColor Green
}

# Pattern 3: error_reporting = 22527 (valeur actuelle détectée)
if ($content -match 'error_reporting\s*=\s*22527\s*(\r?\n)') {
    $content = $content -replace '(error_reporting\s*=\s*)22527\s*(\r?\n)', "`$1E_ALL & ~E_NOTICE`$2"
    $modified = $true
    Write-Host "   ✅ Ligne modifiée: error_reporting = E_ALL & ~E_NOTICE (depuis 22527)" -ForegroundColor Green
}

if (-not $modified) {
    Write-Host "   ⚠️  Aucune ligne correspondante trouvée. Ajout en fin de section." -ForegroundColor Yellow
    
    # Chercher la section [PHP] et ajouter après
    if ($content -match '\[PHP\]') {
        $content = $content -replace '(\[PHP\]\r?\n)', "`$1`nerror_reporting = E_ALL & ~E_NOTICE`n"
        $modified = $true
        Write-Host "   ✅ Ligne ajoutée dans la section [PHP]" -ForegroundColor Green
    }
}

# Sauvegarder les modifications
if ($modified) {
    Set-Content -Path $phpIniPath -Value $content -NoNewline
    Write-Host ""
    Write-Host "✅ php.ini modifié avec succès!" -ForegroundColor Green
} else {
    Write-Host ""
    Write-Host "❌ Impossible de modifier le fichier automatiquement" -ForegroundColor Red
    Write-Host "   Veuillez modifier manuellement la ligne:" -ForegroundColor Yellow
    Write-Host "   error_reporting = E_ALL & ~E_NOTICE" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "══════════════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host ""
Write-Host "⚠️  IMPORTANT: REDÉMARRER APACHE" -ForegroundColor Yellow -BackgroundColor DarkRed
Write-Host ""
Write-Host "Pour appliquer les changements:" -ForegroundColor Yellow
Write-Host "  1. Ouvrir XAMPP Control Panel" -ForegroundColor White
Write-Host "  2. Cliquer sur 'Stop' pour Apache" -ForegroundColor White
Write-Host "  3. Cliquer sur 'Start' pour Apache" -ForegroundColor White
Write-Host ""
Write-Host "══════════════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host ""

# Proposer de vérifier la configuration
Write-Host "Voulez-vous vérifier la nouvelle configuration? (O/N)" -ForegroundColor Cyan
$verify = Read-Host

if ($verify -eq 'O' -or $verify -eq 'o') {
    Write-Host ""
    Write-Host "⏳ Lancement de la vérification..." -ForegroundColor Yellow
    Write-Host ""
    
    # Relancer le test après modifications (si Apache redémarré)
    Write-Host "Note: Apache doit être redémarré pour que les changements soient visibles" -ForegroundColor Gray
    Write-Host "Exécutez cette commande après redémarrage:" -ForegroundColor Yellow
    Write-Host "  php test_php_config.php" -ForegroundColor White
}

Write-Host ""
Write-Host "✅ Script terminé" -ForegroundColor Green
Write-Host ""
