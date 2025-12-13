@echo off
REM ========================================
REM MENU GESTION DONNEES DEMO KMS
REM ========================================

:MENU
cls
echo.
echo ========================================
echo   DONNEES DEMO - KMS GESTION
echo ========================================
echo.
echo   1. Generer des donnees de demo
echo   2. Nettoyer les donnees demo
echo   3. Voir les statistiques
echo   4. Quitter
echo.
echo ========================================
echo.

set /p choice="Votre choix (1-4): "

if "%choice%"=="1" goto GENERER
if "%choice%"=="2" goto NETTOYER
if "%choice%"=="3" goto STATS
if "%choice%"=="4" goto FIN

echo Choix invalide!
timeout /t 2 >nul
goto MENU

:GENERER
cls
echo.
echo === GENERATION DONNEES DEMO ===
echo.
php generer_donnees_demo_final.php
echo.
pause
goto MENU

:NETTOYER
cls
echo.
echo === NETTOYAGE DONNEES DEMO ===
echo.
php nettoyer_donnees_demo.php
echo.
pause
goto MENU

:STATS
cls
echo.
echo === STATISTIQUES BASE DE DONNEES ===
echo.
C:\xampp\mysql\bin\mysql.exe -u root kms_gestion -e "SELECT 'Clients' as Module, COUNT(*) as Total FROM clients UNION SELECT 'Produits', COUNT(*) FROM produits UNION SELECT 'Devis', COUNT(*) FROM devis UNION SELECT 'Ventes', COUNT(*) FROM ventes UNION SELECT 'Livraisons', COUNT(*) FROM bons_livraison UNION SELECT 'Mouvements stock', COUNT(*) FROM stocks_mouvements UNION SELECT 'Encaissements', COUNT(*) FROM caisse_journal;"
echo.
pause
goto MENU

:FIN
echo.
echo Au revoir!
timeout /t 1 >nul
exit
