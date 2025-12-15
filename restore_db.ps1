$mysqlPath = "C:\xampp\mysql\bin\mysql.exe"
$sqlFile = "C:\xampp\htdocs\kms_app\kms_gestion.sql"
$dbName = "kms_gestion"
$dbUser = "root"

Write-Host "RESTAURATION BASE DE DONNEES KMS" -ForegroundColor Cyan
Write-Host "Source: kms_gestion.sql" -ForegroundColor Green
Write-Host ""

if (-not (Test-Path $sqlFile)) {
    Write-Host "ERREUR: Fichier SQL non trouve" -ForegroundColor Red
    exit 1
}

Write-Host "Etape 1: Suppression base existante..." -ForegroundColor Yellow
& $mysqlPath -u $dbUser -e "DROP DATABASE IF EXISTS $dbName;" 2>$null

Write-Host "Etape 2: Creation nouvelle base..." -ForegroundColor Yellow
& $mysqlPath -u $dbUser -e "CREATE DATABASE $dbName CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>$null

Write-Host "Etape 3: Import fichier SQL (en cours)..." -ForegroundColor Yellow
Get-Content $sqlFile -Raw | & $mysqlPath -u $dbUser $dbName 2>$null

Write-Host "Etape 4: Verification..." -ForegroundColor Yellow
& $mysqlPath -u $dbUser -e "SELECT COUNT(*) as tables_creees FROM information_schema.tables WHERE table_schema='$dbName';" 2>&1

Write-Host ""
Write-Host "OK - Restauration completee" -ForegroundColor Green
Write-Host "Base: $dbName" -ForegroundColor Cyan
