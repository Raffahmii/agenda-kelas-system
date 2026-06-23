<?php
/**
 * Authentication Check
 * File: includes/auth.php
 * Fungsi: Memeriksa apakah user sudah login dan memiliki akses sesuai role
 */

// Load session configuration
require_once __DIR__ . '/../config/session.php';

// Cek apakah user sudah login
if (!isLoggedIn()) {
    // Redirect ke halaman login
    header("Location: ../login.php");
    exit();
}

// Fungsi untuk cek akses role (dipanggil di masing-masing folder role)
function checkRoleAccess($allowedRoles = []) {
    if (empty($allowedRoles)) {
        return true;
    }
    
    if (!in_array($_SESSION['role'], $allowedRoles)) {
        // Redirect ke dashboard sesuai role masing-masing
        $dashboardPath = '';
        switch ($_SESSION['role']) {
            case 'siswa':
                $dashboardPath = '../siswa/dashboard.php';
                break;
            case 'sekretaris':
                $dashboardPath = '../sekre/dashboard.php';
                break;
            case 'guru':
                $dashboardPath = '../guru/dashboard.php';
                break;
            case 'walikelas':
                $dashboardPath = '../walikelas/dashboard.php';
                break;
            default:
                $dashboardPath = '../index.php';
        }
        
        header("Location: $dashboardPath");
        exit();
    }
    return true;
}

// Fungsi untuk mendapatkan data user yang sedang login
function getCurrentUser($pdo) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['id']]);
    return $stmt->fetch();
}
?>