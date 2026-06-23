<?php
/**
 * Database Configuration
 * File: config/database.php
 * Fungsi: Koneksi ke database MySQL menggunakan PDO (Prepared Statement)
 */

// Konfigurasi database
$host = 'localhost';
$dbname = 'agenda_kelas';
$username = 'root';
$password = '';

// Set timezone ke WIB (Asia/Jakarta)
date_default_timezone_set('Asia/Jakarta');

try {
    // Buat koneksi PDO
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    
    // Optional: uncomment untuk cek koneksi (debug)
    // echo "Koneksi database berhasil";
    
} catch(PDOException $e) {
    // Jika koneksi gagal, tampilkan pesan error (jangan tampilkan detail di production)
    die("Koneksi database gagal: " . $e->getMessage());
}
?>