<?php
/**
 * Session Configuration
 * File: config/session.php
 */

// Start session jika belum dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fungsi helper untuk cek login
function isLoggedIn() {
    return isset($_SESSION['id']) && isset($_SESSION['nama']) && isset($_SESSION['role']);
}

// Fungsi helper untuk cek role tertentu
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

// Fungsi untuk mendapatkan role user
function getUserRole() {
    return $_SESSION['role'] ?? null;
}

// Fungsi untuk mendapatkan nama user
function getUserName() {
    return $_SESSION['nama'] ?? 'Guest';
}

// Fungsi untuk mendapatkan ID user
function getUserId() {
    return $_SESSION['id'] ?? null;
}
?>