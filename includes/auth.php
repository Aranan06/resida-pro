<?php
// includes/auth.php
require_once __DIR__ . '/config.php';

function requireLogin() {
    if (!isset($_SESSION['user'])) {
        header('Location: index.php');
        exit;
    }
}

function currentUser() {
    return $_SESSION['user'] ?? null;
}

function isAdmin() {
    $u = currentUser();
    return $u && $u['role'] === 'admin';
}

function isManager() {
    $u = currentUser();
    return $u && $u['role'] === 'manager';
}

function isResident() {
    $u = currentUser();
    return $u && $u['role'] === 'resident';
}

requireLogin();
$user = currentUser();
?>