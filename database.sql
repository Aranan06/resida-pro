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