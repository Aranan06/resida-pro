@echo off
chcp 65001 >nul
title RESIDA PRO - Docker

if "%1"=="" goto menu
if "%1"=="up" goto up
if "%1"=="down" goto down
if "%1"=="restart" goto restart
if "%1"=="logs" goto logs
if "%1"=="ps" goto ps
if "%1"=="backup" goto backup
if "%1"=="clean" goto clean
goto menu

:menu
echo.
echo  ===== RESIDA PRO Docker =====
echo  1 - Baslat (up -d)
echo  2 - Durdur (down)
echo  3 - Yeniden Baslat (restart)
echo  4 - Loglari Gor (logs -f)
echo  5 - Durum (ps)
echo  6 - Yedek Al (cron_backup)
echo  7 - Temizle (down -v)
echo  0 - Cikis
echo.
set /p sec="Secim (0-7): "
if "%sec%"=="1" goto up
if "%sec%"=="2" goto down
if "%sec%"=="3" goto restart
if "%sec%"=="4" goto logs
if "%sec%"=="5" goto ps
if "%sec%"=="6" goto backup
if "%sec%"=="7" goto clean
if "%sec%"=="0" exit /b
goto menu

:up
echo [RESIDA] Baslatiliyor...
docker compose up -d
echo.
echo  Web: http://localhost:8080  (veya http://192.168.1.194:8080 telefonda)
echo  phpMyAdmin: http://localhost:8081  (resida / resida123)
echo.
pause
goto menu

:down
docker compose down
pause
goto menu

:restart
docker compose down
docker compose up -d
pause
goto menu

:logs
docker logs -f resida-app
pause
goto menu

:ps
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"
pause
goto menu

:backup
echo [RESIDA] Yedek aliniyor...
curl -s "http://localhost:8080/cron_backup.php?token=resida-cron-2026" >nul
echo Yedekler: backups\ klasoru
dir /b backups\resida*.zip 2>nul
pause
goto menu

:clean
echo DIKKAT: Veritabani silinecek!
set /p onayla="Emin misin? (e/h): "
if /I "%onayla%"=="e" docker compose down -v
pause
goto menu
