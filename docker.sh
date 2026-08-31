#!/bin/bash
# RESIDA PRO - Docker komut dosyasi (Linux/Mac)
case "$1" in
  up) docker compose up -d && echo "Web: http://localhost:8080 | phpMyAdmin: http://localhost:8081" ;;
  down) docker compose down ;;
  restart) docker compose down && docker compose up -d ;;
  logs) docker logs -f resida-app ;;
  ps) docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}" ;;
  backup) curl -s "http://localhost:8080/cron_backup.php?token=resida-cron-2026" && ls -lh backups/*.zip 2>/dev/null ;;
  clean) read -p "Veritabani silinsin mi? (e/h): " c; [[ $c == "e" ]] && docker compose down -v ;;
  *) echo "Kullanim: ./docker.sh {up|down|restart|logs|ps|backup|clean}" ;;
esac
