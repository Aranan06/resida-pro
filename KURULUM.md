# RESIDA PRO – Kurulum Yönergesi v3.6
> Son güncelleme: 29.08.2026 | Ticari paket – 6 kalem tamamlandı

---

## 1) Sistem Gereksinimleri

| Bileşen | Minimum |
|---------|---------|
| PHP | 8.0+ (8.1 önerilir) |
| MySQL | 5.7+ / MariaDB 10.2+ |
| Web Server | Apache (mod_rewrite) veya Nginx |
| Uzantılar | pdo_mysql, mbstring, openssl, zip |
| Disk | 500MB + yedek alanı |

XAMPP/WAMP/MAMP veya cPanel/InfinityFree/VPS hepsi olur.

---

## 2) Dosyaları Yükleme

1. `RESIDA.zip` içindeki tüm dosyaları sunucuya atın:
   - cPanel → Dosya Yöneticisi → `public_html/` veya `htdocs/`
   - FTP ile aynı dizine yükleyin
2. Yapı şu şekilde olmalı:
```
RESIDA/
├─ index.php (giriş)
├─ landing.php (tanıtım & paketler)
├─ admin_panel.php / manager_panel.php / resident_panel.php
├─ includes/ (config.php, functions.php, PaymentGateway.php, auth.php)
├─ api/ (login.php, dues.php …)
├─ assets/ (css, img)
├─ uploads/receipts/ (yazılabilir olmalı)
├─ backups/ (yazılabilir olmalı)
├─ database.sql
├─ .env / .env.example
└─ kvkk.php, receipt.php, efatura.php, cron_*.php
```

---

## 3) Ortam Dosyası (.env)

```bash
cp .env.example .env   # Windows'ta kopyala-yapıştır
```

`.env` içini düzenleyin:

```ini
DB_HOST=localhost            # InfinityFree: sql311.infinityfree.com
DB_NAME=resida_pro           # database.sql'deki isim
DB_USER=root                 # InfinityFree: if0_41466500
DB_PASS=                    # InfinityFree: 11823579bA
DB_CHARSET=utf8mb4

SESSION_TIMEOUT=1800
APP_ENV=production
APP_DEBUG=false

# SENİN abonelik gelirin (yönetim sana öder)
RESIDA_BANK_NAME=Ziraat Bankası
RESIDA_BANK_IBAN=TR00 0000 0000 0000 0000 0000 00  # KENDİ IBAN'INI YAZ
RESIDA_BANK_HOLDER=RESIDA PRO

# iyzico – şirket sonrası canlı, şimdilik sandbox
IYZICO_API_KEY=sandbox-api-key
IYZICO_SECRET_KEY=sandbox-secret-key
IYZICO_BASE_URL=https://sandbox-api.iyzipay.com  # canlı: https://api.iyzipay.com

CRON_TOKEN=resida-cron-2026          # rastgele değiştir
EFATURA_INTEGRATOR=mock
```

> **UYARI:** `.env` asla GitHub'a gönderilmez (`.gitignore` içinde).

---

## 4) Veritabanı Kurulumu

### Seçenek A – phpMyAdmin
1. cPanel → phpMyAdmin → **Yeni veritabanı** → `resida_pro` oluştur (utf8mb4_unicode_ci)
2. **İçe aktar** → `database.sql` seç → Git
3. İçinde hazır gelir:
   - `sites`, `blocks`, `users`, `dues`, `expenses`, `announcements`, `events`
   - `subscription_plans` (3 paket), `site_subscriptions`, `payments`, `login_attempts`, `api_tokens`
   - Varsayılan admin: `admin / password` (hash)

### Seçenek B – Komut satırı
```bash
mysql -u root -p -e "CREATE DATABASE resida_pro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p resida_pro < database.sql
```

### Mevcut DB'yi güncelleme (eski kurulum)
`database.sql` sonundaki `ALTER TABLE ...` satırlarını phpMyAdmin → SQL sekmesinde tek tek çalıştırın:
```sql
ALTER TABLE sites ADD COLUMN max_residents INT NOT NULL DEFAULT 0;
ALTER TABLE sites ADD COLUMN iban VARCHAR(40) NULL;
ALTER TABLE sites ADD COLUMN bank_name VARCHAR(100) NULL;
ALTER TABLE sites ADD COLUMN iban_holder VARCHAR(200) NULL;
ALTER TABLE sites ADD COLUMN penalty_enabled TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE sites ADD COLUMN penalty_rate DECIMAL(5,2) NOT NULL DEFAULT 5.00;
ALTER TABLE sites ADD COLUMN penalty_grace_days INT NOT NULL DEFAULT 5;
ALTER TABLE users ADD COLUMN block_id INT NULL;
ALTER TABLE users ADD COLUMN kvkk_accepted_at TIMESTAMP NULL;
ALTER TABLE users ADD COLUMN kvkk_ip VARCHAR(45) NULL;
CREATE TABLE IF NOT EXISTS blocks (...); -- database.sql'den kopyala
CREATE TABLE IF NOT EXISTS api_tokens (...);
```

---

## 5) Klasör İzinleri

```bash
chmod 775 uploads uploads/receipts backups
# Windows'ta: Klasör → Özellikler → Güvenlik → Yazma izni ver
```

`uploads/.htaccess` ve `backups/.htaccess` zaten `Deny from all` ve `.php` engeli ile geliyor.

---

## 6) İlk Giriş

1. Tarayıcı: `https://siten.com/index.php` veya `http://localhost/RESIDA/index.php`
2. Giriş: **Kullanıcı:** `admin`  **Şifre:** `password`
3. **Hemen şifreyi değiştir** (admin → Yönetici Düzenle veya doğrudan DB'de).
4. Admin → **Siteler** → Yeni Site ekle (örn: Güneş Sitesi)
5. Admin → **Yöneticiler** → Siteye yönetici ata (örn: yonetici1)
6. Admin → **Paketler** → Mini/Standart/Pro fiyatlarını kontrol et
7. Admin → **Abonelikler** → Siteyi bir pakete aktifleştir (yönetim sana havale yapınca sen buradan onaylarsın)

---

## 7) Yönetici (Site Müdürü) Ayarları

Yönetici hesabıyla giriş → **Site Ayarları**:

- **Tahsilat Hesabı:** Sakinlerin aidat yatıracağı **siteye özel IBAN** (senin RESIDA IBAN'ın değil!). Boş ise sakin `IBAN tanımsız` görür.
- **Gecikme Faizi:** Switch ile aç/kapa, oran (0-50%), hoşgörü günü (0-60). Örn: %5, 5 gün → vade 05.05, 11.05'e kadar faiz yok, sonra her 30 günde %5.
- **Bloklar:** Çok bloklu site ise `Bloklar` sayfasından `A Blok`, `B Blok` ekle. Tek apartmanda boş bırak.

Sonra: **Sakinler** → Yeni Sakin ekle (KVKK kutusu zorunlu, blok seçmeli) → **Aidatlar** → Yıllık ücret tanımla, toplu veya tekil aidat oluştur.

---

## 8) Sakin Akışı

1. Sakin girişi → Dashboard'da **IBAN kartı** (yönetimin IBAN'ı) + **Aidat Özetim** tablosu
2. Faiz varsa satır sarı, `+150 ₺ faiz = 1.150 ₺` görünür
3. **Öde** → Havale yap → Dekont yükle → **Ödeme Bildir** (tutarı faiz dahil oluşturur)
4. Yönetici → **Ödemeler** → dekontu gör → **Onayla** (aidat `Ödendi`, makbuz oluşur) / **Reddet**
5. Sakin → **Makbuz İndir** (`receipt.php?id=123`) — yetki kontrollü, blok/faiz/IBAN detaylı profesyonel PDF (yazdır).

---

## 9) Cron (Otomatik İşler)

cPanel → Cron Jobs:

```cron
# Her ayın 1'i 09:00 otomatik aidat
0 9 1 * * /usr/bin/php /home/kullanici/public_html/cron_monthly_dues.php

# Her gün 03:00 yedek
0 3 * * * /usr/bin/php /home/kullanici/public_html/cron_backup.php

# Manuel tarayıcı tetikleme (InfinityFree cron yoksa)
https://siten.com/cron_monthly_dues.php?token=resida-cron-2026&year=2026&month=09
https://siten.com/cron_backup.php?token=resida-cron-2026
```

Test: `cron_monthly_dues.php?dry=1` → simülasyon.

---

## 10) Landing Page

- Tanıtım: `https://siten.com/landing.php` (paketler DB'den dinamik)
- Giriş: `https://siten.com/index.php`
- İstersen ana sayfayı landing yap: `.htaccess` → `DirectoryIndex landing.php index.php`
- Fiyatları değiştirmek: Admin → Paketler

---

## 11) Mobil API

Doküman: `api/README.md`

```bash
# Login
curl -X POST https://siten.com/api/login.php -H "Content-Type: application/json" \
  -d '{"username":"sakin1","password":"123"}' 
# → {"token":"...","user":{...}}

# Aidatlar (faiz dahil)
curl https://siten.com/api/dues.php -H "Authorization: Bearer TOKEN"

# Duyurular / Etkinlikler
curl https://siten.com/api/announcements.php -H "Authorization: Bearer TOKEN"

# Ödeme bildir
curl -X POST https://siten.com/api/pay.php -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" -d '{"due_id":5,"note":"havale yaptım"}'
```

Token 30 gün, 5 token/user sınırı, 10 hatalı login/15dk blok.

---

## 12) e-Fatura

`efatura.php?mode=info` → durum. Şirketsizken **mock**, entegratör anahtarı girince canlı UBL-TR 2.1 üretir. `.env` → `EFATURA_INTEGRATOR=foriba`.

---

## 13) Yedekleme & Geri Yükleme

- Otomatik: `backups/resida_*.zip` (DB + dekontlar), 30 gün sonra silinir.
- Manuel: `cron_backup.php?token=...` → indir linki.
- Geri yükleme: phpMyAdmin → İçe aktar → son `.sql` → `uploads/receipts` klasörünü zip'ten geri çıkar.

---

## 14) Güvenlik Kontrol Listesi

- [x] `.env` Git'te yok, `.gitignore` var
- [x] `admin / password` değiştirildi
- [x] `APP_DEBUG=false` canlıda
- [x] HTTPS aktif, `session.cookie_secure` otomatik
- [x] `receipt.php` yetki kontrollü, `api` Bearer zorunlu
- [x] KVKK metni `kvkk.php` özelleştirildi (avukata danış)
- [x] Brute-force 5 deneme/15dk (web) ve 10/15dk (API)
- [x] `backups` ve `uploads` dışa kapalı

---

## 15) Sorun Giderme

| Sorun | Çözüm |
|-------|-------|
| `Veritabanı Bağlantı Hatası` | `.env` DB bilgilerini ve `database.sql` içe aktarmayı kontrol et, `APP_DEBUG=true` yap detayı gör |
| `Blok eklenemiyor` | `sites` tablosunda kayıt var mı? `blocks` tablosu oluşmadıysa `manager_panel.php` bir kez aç (otomatik migrasyon) |
| Sakin `IBAN tanımsız` görüyor | Yönetici → Site Ayarları → IBAN gir |
| Faiz hesaplanmıyor | Site Ayarları → Gecikme Faizi → Açık ve oran >0 mı? Hoşgörü günü doldu mu? |
| Makbuz `403` | Giriş yapan kullanıcı o aidatın sahibi/müdürü değil |
| API `401` | `Authorization: Bearer TOKEN` header doğru mu? Token süresi 30 gün |
| Cron çalışmıyor | `CRON_TOKEN` eşleşiyor mu? PHP yolu `/usr/bin/php` mi? cPanel loguna bak |

---

## 16) Ticari Akış Özeti

- **Aidat:** Sakin → **Site IBAN'ına** havale → dekont → Yönetici onay → Aidat ödendi. Para sana gelmez.
- **Abonelik:** Yönetim → **Senin RESIDA IBAN'ına** (`.env` RESIDA_BANK_IBAN) aylık havale → Sen Admin → Abonelikler → süreyi uzat. Yakında iyzico otomatik.
- **Destek:** `info@residapro.com` (landing.php'de güncelle)

---

**Kurulum bitti!** Sorun yaşarsan `cron.log` ve phpMyAdmin'i kontrol et, gerekirse bu yönergeyi tekrar takip et.
