-- =============================================
-- RESİDA PRO - Apartman Yönetim Sistemi
-- Veritabanı Şeması v2.0
-- =============================================
CREATE DATABASE IF NOT EXISTS resida_pro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE resida_pro;

-- Siteler (aidat IBAN'ı siteye özel + gecikme faizi + iyzico alt üye)
CREATE TABLE IF NOT EXISTS sites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    address VARCHAR(500) NULL,
    max_residents INT NOT NULL DEFAULT 0 COMMENT '0=Sınırsız',
    iban VARCHAR(40) NULL COMMENT 'Sakinlerin aidat yatıracağı IBAN (site yönetimi)',
    bank_name VARCHAR(100) NULL COMMENT 'Banka adı',
    iban_holder VARCHAR(200) NULL COMMENT 'Hesap sahibi (site yönetimi)',
    penalty_enabled TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Gecikme faizi açık mı',
    penalty_rate DECIMAL(5,2) NOT NULL DEFAULT 5.00 COMMENT 'Aylık gecikme oranı %',
    penalty_grace_days INT NOT NULL DEFAULT 5 COMMENT 'Vade sonrası hoşgörü günü',
    iyzico_submerchant_key VARCHAR(100) NULL COMMENT 'iyzico alt üye anahtarı (pazaryeri)',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Bloklar (site içi A/B/C Blok gibi)
CREATE TABLE IF NOT EXISTS blocks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    site_id INT NOT NULL,
    name VARCHAR(100) NOT NULL COMMENT 'Örn: A Blok',
    description VARCHAR(300) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE,
    UNIQUE KEY site_block (site_id, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Kullanıcılar (admin, yönetici, sakin)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','manager','resident') NOT NULL,
    name VARCHAR(200) NOT NULL,
    site_id INT NULL,
    block_id INT NULL COMMENT 'NULL = tek blok / blok yok',
    floor VARCHAR(20) NULL,
    apartment_no VARCHAR(50) NULL,
    address VARCHAR(500) NULL,
    phone VARCHAR(30) NULL,
    email VARCHAR(200) NULL,
    notes TEXT NULL,
    kvkk_accepted_at TIMESTAMP NULL COMMENT 'KVKK onayı',
    kvkk_ip VARCHAR(45) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE SET NULL,
    FOREIGN KEY (block_id) REFERENCES blocks(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Duyurular
CREATE TABLE IF NOT EXISTS announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    site_id INT NOT NULL,
    title VARCHAR(300) NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Etkinlikler
CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    site_id INT NOT NULL,
    title VARCHAR(300) NOT NULL,
    description TEXT NULL,
    event_date DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Aidatlar
CREATE TABLE IF NOT EXISTS dues (
    id INT AUTO_INCREMENT PRIMARY KEY,
    site_id INT NOT NULL,
    resident_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    due_date DATE NOT NULL,
    paid TINYINT(1) DEFAULT 0,
    paid_date DATE NULL,
    description VARCHAR(300) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE,
    FOREIGN KEY (resident_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Aidat Ayarları (yıllık/aylık ücret tanımı)
CREATE TABLE IF NOT EXISTS due_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    site_id INT NOT NULL,
    year INT NOT NULL,
    monthly_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY site_year (site_id, year),
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Giderler (VARCHAR yapıldı - dinamik kategori için)
CREATE TABLE IF NOT EXISTS expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    site_id INT NOT NULL,
    category VARCHAR(100) NOT NULL DEFAULT 'Diğer',
    title VARCHAR(300) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    expense_date DATE NOT NULL,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Login denemeleri (brute-force koruması)
CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip VARCHAR(45) NOT NULL,
    username VARCHAR(100) NULL,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    success TINYINT(1) DEFAULT 0,
    INDEX idx_ip_time (ip, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- TİCARİ ABONELİK SİSTEMİ (v3.0)
-- =========================================================

-- Abonelik Paketleri (Admin tanımlar)
CREATE TABLE IF NOT EXISTS subscription_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL COMMENT 'Mini/Standart/Pro',
    max_residents INT NOT NULL DEFAULT 20 COMMENT '0=Sınırsız',
    max_sites INT NOT NULL DEFAULT 1 COMMENT 'Admin için çoklu site izni',
    price_monthly DECIMAL(10,2) NOT NULL DEFAULT 0,
    price_yearly DECIMAL(10,2) NULL COMMENT 'Yıllık indirimli',
    features TEXT NULL COMMENT 'JSON: özellik listesi',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Site Abonelikleri (hangi site hangi pakette)
CREATE TABLE IF NOT EXISTS site_subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    site_id INT NOT NULL,
    plan_id INT NOT NULL,
    status ENUM('trial','active','past_due','cancelled','expired','pending') NOT NULL DEFAULT 'trial',
    trial_ends_at DATE NULL,
    current_period_start DATE NOT NULL,
    current_period_end DATE NOT NULL,
    auto_renew TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES subscription_plans(id) ON DELETE RESTRICT,
    INDEX idx_site_status (site_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ödemeler (Sakin aidat ödemesi + Site abonelik ödemesi ortak tablo)
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    site_id INT NOT NULL,
    user_id INT NULL COMMENT 'Sakin aidat ödemesi ise dolu',
    due_id INT NULL COMMENT 'Hangi aidat için',
    subscription_id INT NULL COMMENT 'Abonelik ödemesi ise dolu',
    gateway ENUM('manual','iyzico') NOT NULL DEFAULT 'manual',
    gateway_ref VARCHAR(200) NULL COMMENT 'iyzico paymentId / conversationId',
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
    receipt_path VARCHAR(500) NULL COMMENT 'Dekont dosya yolu',
    iban_last4 VARCHAR(10) NULL,
    note TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approved_at TIMESTAMP NULL,
    approved_by INT NULL,
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (due_id) REFERENCES dues(id) ON DELETE SET NULL,
    FOREIGN KEY (subscription_id) REFERENCES site_subscriptions(id) ON DELETE SET NULL,
    INDEX idx_site_status (site_id, status),
    INDEX idx_due (due_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- API Tokenları (mobil uygulama)
CREATE TABLE IF NOT EXISTS api_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(128) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    last_used_at DATETIME NULL,
    ip VARCHAR(45) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Bildirimler (in-app)
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    site_id INT NOT NULL,
    user_id INT NULL COMMENT 'NULL = tüm siteye',
    title VARCHAR(200) NOT NULL,
    body TEXT NOT NULL,
    type ENUM('announcement','due_reminder','payment','system') DEFAULT 'system',
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_read (user_id, is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Push Abonelikleri (Web Push)
CREATE TABLE IF NOT EXISTS push_subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    endpoint TEXT NOT NULL,
    p256dh TEXT NULL,
    auth TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Mevcut kurulumlar için migrasyon (manuel çalıştırın):
-- ALTER TABLE sites ADD COLUMN max_residents INT NOT NULL DEFAULT 0 COMMENT '0=Sınırsız';
-- ALTER TABLE sites ADD COLUMN iban VARCHAR(40) NULL COMMENT 'Site IBAN';
-- ALTER TABLE sites ADD COLUMN bank_name VARCHAR(100) NULL;
-- ALTER TABLE sites ADD COLUMN iban_holder VARCHAR(200) NULL;
-- ALTER TABLE sites ADD COLUMN penalty_enabled TINYINT(1) NOT NULL DEFAULT 0;
-- ALTER TABLE sites ADD COLUMN penalty_rate DECIMAL(5,2) NOT NULL DEFAULT 5.00;
-- ALTER TABLE sites ADD COLUMN penalty_grace_days INT NOT NULL DEFAULT 5;
-- ALTER TABLE sites ADD COLUMN iyzico_submerchant_key VARCHAR(100) NULL;
-- ALTER TABLE expenses MODIFY COLUMN category VARCHAR(100) NOT NULL DEFAULT 'Diğer';
-- CREATE TABLE IF NOT EXISTS blocks (id INT AUTO_INCREMENT PRIMARY KEY, site_id INT NOT NULL, name VARCHAR(100) NOT NULL, description VARCHAR(300) NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE, UNIQUE KEY site_block (site_id, name));
-- ALTER TABLE users ADD COLUMN block_id INT NULL COMMENT 'NULL = tek blok', ADD FOREIGN KEY (block_id) REFERENCES blocks(id) ON DELETE SET NULL;
-- ALTER TABLE users ADD COLUMN kvkk_accepted_at TIMESTAMP NULL, ADD COLUMN kvkk_ip VARCHAR(45) NULL;
-- CREATE TABLE IF NOT EXISTS api_tokens (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, token VARCHAR(128) NOT NULL UNIQUE, expires_at DATETIME NOT NULL, last_used_at DATETIME NULL, ip VARCHAR(45) NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE);

-- Varsayılan Planlar
INSERT IGNORE INTO subscription_plans (id, name, max_residents, max_sites, price_monthly, price_yearly, features) VALUES
(1, 'Mini', 20, 1, 149.00, 1490.00, '["20 daire","Temel aidat takibi","WhatsApp hatırlatma"]'),
(2, 'Standart', 100, 3, 349.00, 3490.00, '["100 daire","Otomatik aidat","Gider yönetimi","Excel rapor"]'),
(3, 'Pro', 0, 10, 599.00, 5990.00, '["Sınırsız daire","Öncelikli destek","Çoklu site","API erişimi"]');

-- Varsayılan admin kullanıcısı (şifre: password - İLK GİRİŞTE DEĞİŞTİRİN!)
INSERT IGNORE INTO users (username, password, role, name)
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Sistem Yöneticisi');
-- Landing CMS (site içeriği admin panelden yönetilir)
CREATE TABLE IF NOT EXISTS landing_settings (
    k VARCHAR(100) PRIMARY KEY,
    v TEXT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS landing_menu (
    id INT AUTO_INCREMENT PRIMARY KEY,
    label VARCHAR(100) NOT NULL,
    url VARCHAR(255) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS landing_faq (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question VARCHAR(255) NOT NULL,
    answer TEXT NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
-- Landing varsayılan içerik
INSERT IGNORE INTO landing_settings (k, v) VALUES
('hero_badge', '5 saniyede anla — Excel''i kapat'),
('hero_title_a', 'Apartman ve site yönetimini'),
('hero_title_b', 'Excel''den kurtarın.'),
('hero_subtitle', 'Aidat, gider, tahsilat ve sakin yönetimini tek panelden yönetin. Apartman yönetim programı arayan yöneticiler için sade, hızlı ve güvenilir çözüm.'),
('hero_primary_btn', 'Ücretsiz Başlayın'),
('hero_secondary_btn', 'İncele'),
('hero_note', 'Kredi kartı gerekmez • 10 dakikada kurulum • Aynı gün tahsilat'),
('problem_title', 'Excel, WhatsApp ve banka dekontları arasında kaybolmayın.'),
('problem_subtitle', 'Site yönetim programı kullanmayan yöneticiler her ay aynı sorunlarla uğraşıyor.'),
('solution_title', 'Yönetmeniz gereken her şey tek panelde.'),
('solution_subtitle', 'Aidat takip programı olarak günlük işlerinizi sadeleştirir.'),
('payment_title', 'Ödeme RESIDA''da tutulmaz.'),
('payment_text', 'Sakin kartla ödeme yaptığında para doğrudan sitenin belirlediği banka hesabına yönlendirilir. Para bizim hesabımızda beklemez.'),
('migration_title', 'Excel''den RESIDA''ya geçmek düşündüğünüzden kolay.'),
('migration_subtitle', 'Yıllardır kullandığınız verileri kaybetmeden RESIDA''ya geçin.'),
('screens_title', 'Aidatları, sakinleri ve raporları tek ekrandan yönetin.'),
('screens_subtitle', 'Modern SaaS tasarımıyla hazırlanmış yönetici paneli.'),
('screens_side_title', 'Sakinler de her şeyi telefonundan takip etsin.'),
('screens_side_text', 'Sakinler aidat borçlarını, ödemelerini, dekontlarını ve site duyurularını tek yerden takip edebilir.'),
('pricing_title', 'Size uygun paketi seçin'),
('pricing_subtitle', 'Mevcut fiyatlandırma korunur. İstediğiniz zaman yükseltebilirsiniz.'),
('pricing_note', 'Mini küçük apartmanlar • Standart orta büyüklükte siteler • Pro profesyonel site yönetimleri içindir.'),
('faq_title', 'Sık sorulan sorular'),
('faq_subtitle', 'Apartman yönetim programı hakkında merak edilenler.'),
('cta_title', 'Site yönetimini bugün kolaylaştırın.'),
('cta_text', 'Aidat, gider, tahsilat ve sakin yönetimini RESIDA ile tek panelden yönetin.'),
('cta_primary_btn', 'Ücretsiz Başlayın'),
('cta_box_title', '15 dakikada canlı tanıtım'),
('contact_email', 'info@residapro.com'),
('contact_phone', '0532 XXX XX XX'),
('footer_text', 'RESIDA PRO • Apartman ve site yönetim programı'),
('nav_logo', 'assets/img/resida-pro-logo2.png'),
('hero_image', ''),
('phone_image', '');
INSERT IGNORE INTO landing_menu (id, label, url, sort_order, is_active) VALUES
(1, 'Çözüm', '#cozum', 1, 1),
(2, 'Ödeme', '#odeme', 2, 1),
(3, 'Ekranlar', '#ekranlar', 3, 1),
(4, 'Fiyatlar', '#fiyatlar', 4, 1),
(5, 'SSS', '#sss', 5, 1);
INSERT IGNORE INTO landing_faq (id, question, answer, sort_order, is_active) VALUES
(1, 'RESIDA nedir?', 'RESIDA, apartman ve siteler için aidat takip programıdır. Aidat, gider, tahsilat, dekont, duyuru ve sakin yönetimini tek panelde toplar.', 1, 1),
(2, 'RESIDA ile ödeme nasıl alınır?', 'Sakin havale yapıp dekont yükler, yönetici tek dokunuşla onaylar. Kartla ödemede tutar doğrudan site hesabına yönlendirilir.', 2, 1),
(3, 'Para RESIDA''da tutuluyor mu?', 'Hayır. Ödeme RESIDA''da tutulmaz. Para doğrudan sitenin belirlediği banka hesabına gider.', 3, 1),
(4, 'Site IBAN''ı nasıl tanımlanıyor?', 'Yönetici panelinden siteye ait banka adı, IBAN ve hesap sahibi bir kez tanımlanır. Tüm ödemeler bu hesaba yönlendirilir.', 4, 1),
(5, 'Excel''deki bilgiler RESIDA''ya aktarılabilir mi?', 'Evet. Daire ve sakin listenizi mevcut Excel dosyanızdan alıp kısa sürede RESIDA''ya aktarabilirsiniz.', 5, 1),
(6, 'Sakinler sisteme nasıl giriş yapıyor?', 'Her sakine kullanıcı adı ve şifre oluşturulur. Sakinler borç, ödeme, dekont ve duyuruları kendi panelinden görür.', 6, 1),
(7, 'Mobil uygulama var mı?', 'Evet. Telefon ve tabletten uyumlu sakin paneli bulunur. Ana ekrana ekleyerek uygulama gibi kullanabilirsiniz.', 7, 1),
(8, 'İptal edebilir miyim?', 'Evet. İstediğiniz zaman iptal edebilirsiniz. Verileriniz yedeklenebilir.', 8, 1),
(9, 'Verilerim güvende mi?', 'Evet. KVKK uyumlu altyapı, güvenli giriş, yetkilendirme ve düzenli yedekleme kullanılır.', 9, 1),
(10, 'Gecikme faizi otomatik hesaplanıyor mu?', 'Evet. Yönetici oran ve süre tanımladıktan sonra gecikme faizi otomatik hesaplanır ve rapora yansır.', 10, 1);
-- Ziyaretci analitigi (KVKK uyumlu: ham IP saklanmaz, gunluk hash)
CREATE TABLE IF NOT EXISTS page_views (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    page VARCHAR(50) NOT NULL,
    visitor_hash CHAR(64) NOT NULL,
    referrer VARCHAR(500) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_page_time (page, created_at),
    INDEX idx_visitor (visitor_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;