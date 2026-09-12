<?php
// Hassas ayarları (iyzico anahtarları) veritabanında şifreli saklar.
// Anahtar: sunucu .env dosyasındaki APP_KEY (64 hex). Yoksa şifreleme kapalıdır.

function crypto_key() {
    $k = $_ENV['APP_KEY'] ?? getenv('APP_KEY') ?: '';
    if (strlen(trim($k)) < 16) return null;
    return substr(hash('sha256', trim($k), true), 0, 32);
}

function crypto_encrypt($plain) {
    $key = crypto_key();
    if (!$key || $plain === '' || $plain === null) return $plain;
    $iv = random_bytes(16);
    $ct = openssl_encrypt((string)$plain, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    if ($ct === false) return $plain;
    return 'enc:' . base64_encode($iv . $ct);
}

function crypto_decrypt($val) {
    if (!is_string($val) || !str_starts_with($val, 'enc:')) return $val; // eski açık değer
    $key = crypto_key();
    if (!$key) return '';
    $raw = base64_decode(substr($val, 4), true);
    if ($raw === false || strlen($raw) < 17) return '';
    $ct = openssl_decrypt(substr($raw, 16), 'AES-256-CBC', $key, OPENSSL_RAW_DATA, substr($raw, 0, 16));
    return $ct === false ? '' : $ct;
}
