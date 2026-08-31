# RESIDA PRO — VDS Kurulum ve Yayın Rehberi

**Sunucu:** VDS L-TR 8GB (Datacasa/İstanbul) — 4 vCPU Xeon / 8GB DDR4 ECC / 100GB NVMe (60+40) / 10Gbit Sınırsız — Ubuntu 24.04 Noble
**IP:** `31.57.77.165` — **Staging:** `http://31.57.77.165:8080` (`/landing.php`, `/index.php`, `:8081` phpMyAdmin)
**Repo:** `https://github.com/Aranan06/resida-pro.git` (Private → Public yapıldı, sonra tekrar Private + Deploy Key)

---

## 1) VDS İlk Kurulum (bir kez)

```bash
# SSH: ssh root@31.57.77.165  (şifre: 11823579bAa.)
apt update && apt install -y docker.io docker-compose-plugin git nginx certbot python3-certbot-nginx
systemctl enable --now docker
docker --version  # 29.1.3
docker compose version  # v5.5.0
```

## 2) Kodu Çek ve Ayağa Kaldır

```bash
cd /opt && rm -rf resida
git clone https://github.com/Aranan06/resida-pro.git resida
cd resida
# .env.production zaten var, gerekirse düzenle:
# DB_HOST=db, DB_NAME=resida_pro, DB_USER=resida, DB_PASS=resida123
docker compose up -d
docker ps  # resida-app, resida-db (healthy), resida-phpmyadmin Up olmalı
curl -I http://localhost:8080/          # 200
curl -I http://localhost:8080/landing.php  # 200
```

> **Not:** `docker-compose.yml` ilk ayağa kalkışta `docker-php-ext-install pdo pdo_mysql mysqli` derler → 60-90 sn sürer, `docker logs resida-app --tail 20` ile `Apache configured -- resuming normal operations` görünene kadar bekle.

## 3) Geliştirme Akışı (lokal → staging → prod)

**Lokal (Windows):**
```powershell
cd "C:\Users\BURAK\Desktop\RESIDA"
# değişiklik yap
git add .
git commit -m "feat: myplan odeme"
git push origin main
```

**Staging (VDS):**
```bash
cd /opt/resida
git pull origin main
docker compose up -d
# test: http://31.57.77.165:8080
```

**Prod (domain bağlayınca):** aynı, sadece `git pull` sonrası `docker compose up -d` yeterli. `.env` Git’e gitmez (`.gitignore`).

## 4) Domain Bağlama

1. Domain firmasından DNS:
   ```
   A  @    31.57.77.165
   A  www  31.57.77.165
   # veya Cloudflare’de turuncu bulut açık
   ```
2. VDS’te Nginx reverse proxy + SSL:
   ```bash
   # /etc/nginx/sites-available/resida
   server {
     server_name residapro.com www.residapro.com;
     location / { proxy_pass http://127.0.0.1:8080; proxy_set_header Host $host; proxy_set_header X-Real-IP $remote_addr; }
   }
   ln -s /etc/nginx/sites-available/resida /etc/nginx/sites-enabled/
   nginx -t && systemctl reload nginx
   certbot --nginx -d residapro.com -d www.residapro.com
   ```
3. `.env.production` → `APP_URL=https://residapro.com`, `IYZICO_BASE_URL=https://api.iyzipay.com`

## 5) PWA — Android/iPhone Uygulama Gibi

- Logo: `assets/img/apple-touch-icon.png` → `resida-pro-logo2.png` + `icon-192/512.png` hepsi aynı (33KB, iPhone ile eş)
- `manifest.json` `display: standalone`, `service-worker.js` `CACHE=resida-v7`
- **Android http IP’de kısayol olur:** `chrome://flags` → `Insecure origins treated as secure` → `http://31.57.77.165:8080` → Enabled → Relaunch → **Uygulamayı Yükle** rozetsiz standalone olur. **Canlı https://’te** tek tıkla uygulama gibi kurulur (landing’de `installBtn` hep görünür, yoksa sarı kutu yönerge çıkar).
- iPhone: Paylaş → Ana Ekrana Ekle

## 6) Ödeme — Havale / iyzico

- `site_subscriptions.status` ENUM’a `pending` eklendi (`database.sql:151`, canlı DB’de `ALTER TABLE resida_pro.site_subscriptions ...`)
- `manager_panel.php:164` `pay_subscription_iyzico` artık `IyzicoGateway::createPayment(array $data)` ile array çağırıyor; placeholder anahtarda `Kartla ödeme şu an aktif değil... havale kullanın` döner.
- Manuel havale → `payments` pending → admin onay → `site_subscriptions` active.

## 7) Yedek

VDS’te ek disk 100GB, snapshot yok — günlük yedek şart:
```bash
# /opt/resida/cron_backup.php her gece 03:00
crontab -e
0 3 * * * curl -s http://localhost:8080/cron_backup.php?token=resida-cron-2026 >/dev/null
# + rclone ile S3’e (DO Spaces/Backblaze) senkron
```

## 8) Hızlı Komutlar

```bash
docker ps; docker logs resida-app --tail 40
docker compose down && docker compose up -d
git pull; docker compose up -d
certbot renew --dry-run
```

**Staging linkleri:** `http://31.57.77.165:8080/landing.php` | `http://31.57.77.165:8080/index.php` | `http://31.57.77.165:8081` (phpMyAdmin)
