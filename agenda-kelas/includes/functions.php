<?php
/**
 * Global Functions
 * File: includes/functions.php
 * Fungsi: Kumpulan fungsi helper untuk seluruh sistem
 */

// Format tanggal Indonesia
function formatTanggal($tanggal, $format = 'd F Y') {
    if (empty($tanggal)) return '-';
    
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    
    $timestamp = strtotime($tanggal);
    $hari = date('d', $timestamp);
    $bln = (int)date('m', $timestamp);
    $tahun = date('Y', $timestamp);
    
    if ($format == 'd F Y') {
        return $hari . ' ' . $bulan[$bln] . ' ' . $tahun;
    } elseif ($format == 'd/m/Y') {
        return date('d/m/Y', $timestamp);
    } elseif ($format == 'Y-m-d') {
        return date('Y-m-d', $timestamp);
    }
    
    return date($format, $timestamp);
}

// Format waktu
function formatWaktu($waktu) {
    if (empty($waktu)) return '-';
    return date('H:i', strtotime($waktu));
}

// Format datetime lengkap
function formatDateTime($datetime) {
    if (empty($datetime)) return '-';
    return date('d/m/Y H:i', strtotime($datetime));
}

// Generate random string
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}

// Upload file
function uploadFile($file, $targetDir, $allowedTypes = ['jpg', 'jpeg', 'png', 'gif']) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Gagal upload file'];
    }
    
    $fileName = $file['name'];
    $fileTmp = $file['tmp_name'];
    $fileSize = $file['size'];
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    // Cek ekstensi
    if (!in_array($fileExt, $allowedTypes)) {
        return ['success' => false, 'message' => 'Tipe file tidak diizinkan'];
    }
    
    // Cek ukuran (max 2MB)
    if ($fileSize > 2 * 1024 * 1024) {
        return ['success' => false, 'message' => 'Ukuran file maksimal 2MB'];
    }
    
    // Buat nama unik
    $newFileName = time() . '_' . generateRandomString(6) . '.' . $fileExt;
    $destination = $targetDir . '/' . $newFileName;
    
    // Buat folder jika belum ada
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    if (move_uploaded_file($fileTmp, $destination)) {
        return ['success' => true, 'filename' => $newFileName, 'path' => $destination];
    }
    
    return ['success' => false, 'message' => 'Gagal menyimpan file'];
}

// Delete file
function deleteFile($filePath) {
    if (file_exists($filePath)) {
        return unlink($filePath);
    }
    return false;
}

// Generate QR Token
function generateQrToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

// Get status badge HTML
function getStatusBadge($status) {
    $badges = [
        'hadir' => '<span class="badge-status badge-hadir"><i class="fas fa-check-circle me-1"></i> Hadir</span>',
        'sakit' => '<span class="badge-status badge-sakit"><i class="fas fa-thermometer-half me-1"></i> Sakit</span>',
        'izin' => '<span class="badge-status badge-izin"><i class="fas fa-envelope me-1"></i> Izin</span>',
        'dispen' => '<span class="badge-status badge-dispen"><i class="fas fa-file-alt me-1"></i> Dispen</span>',
        'alfa' => '<span class="badge-status badge-alfa"><i class="fas fa-times-circle me-1"></i> Alfa</span>'
    ];
    
    return $badges[$status] ?? '<span class="badge-status">' . $status . '</span>';
}

// Get validation status badge
function getValidationBadge($status) {
    $badges = [
        'pending' => '<span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i> Pending</span>',
        'valid' => '<span class="badge bg-success"><i class="fas fa-check me-1"></i> Valid</span>',
        'ditolak' => '<span class="badge bg-danger"><i class="fas fa-times me-1"></i> Ditolak</span>'
    ];
    
    return $badges[$status] ?? '<span class="badge bg-secondary">' . $status . '</span>';
}

// Get kelas by wali kelas
function getKelasByWaliKelas($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT * FROM kelas WHERE walikelas_id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

// Get siswa by kelas
function getSiswaByKelas($pdo, $kelasId) {
    $stmt = $pdo->prepare("
        SELECT s.*, u.nama as user_nama 
        FROM siswa s 
        JOIN users u ON s.user_id = u.id 
        WHERE s.kelas_id = ? 
        ORDER BY s.nomor_absen ASC
    ");
    $stmt->execute([$kelasId]);
    return $stmt->fetchAll();
}

// Get absensi hari ini by kelas
function getAbsensiHariIni($pdo, $kelasId) {
    $tanggal = date('Y-m-d');
    $stmt = $pdo->prepare("
        SELECT a.*, v.status as validasi_status 
        FROM absensi_harian a
        LEFT JOIN validasi_qr v ON a.id = v.absensi_id
        WHERE a.kelas_id = ? AND a.tanggal = ?
    ");
    $stmt->execute([$kelasId, $tanggal]);
    return $stmt->fetch();
}

// Hitung persentase kehadiran
function hitungPersentase($hadir, $total) {
    if ($total == 0) return 0;
    return round(($hadir / $total) * 100);
}

// Log activity (opsional)
function logActivity($pdo, $userId, $activity) {
    $stmt = $pdo->prepare("INSERT INTO log_aktivitas (user_id, aktivitas, created_at) VALUES (?, ?, NOW())");
    return $stmt->execute([$userId, $activity]);
}

// Cek apakah user sudah absen hari ini
function cekAbsenHariIni($pdo, $siswaId) {
    $tanggal = date('Y-m-d');
    $stmt = $pdo->prepare("
        SELECT d.* FROM detail_absensi d
        JOIN absensi_harian a ON d.absensi_id = a.id
        WHERE d.siswa_id = ? AND a.tanggal = ?
    ");
    $stmt->execute([$siswaId, $tanggal]);
    return $stmt->fetch();
}

// Redirect dengan pesan SweetAlert
function redirectWithAlert($url, $type, $message) {
    $_SESSION['alert_type'] = $type;
    $_SESSION['alert_message'] = $message;
    header("Location: $url");
    exit();
}

// Tampilkan alert jika ada
function showAlert() {
    if (isset($_SESSION['alert_type']) && isset($_SESSION['alert_message'])) {
        $type = $_SESSION['alert_type'];
        $message = $_SESSION['alert_message'];
        unset($_SESSION['alert_type']);
        unset($_SESSION['alert_message']);
        
        echo "<script>
            Swal.fire({
                icon: '$type',
                title: '" . ($type == 'success' ? 'Berhasil!' : ($type == 'error' ? 'Gagal!' : 'Info')) . "',
                text: '$message',
                confirmButtonColor: '#FFD65A'
            });
        </script>";
    }
}

// Generate Excel (menggunakan header)
function exportToExcel($data, $filename, $headers) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
    
    echo '<table border="1">';
    echo '<tr>';
    foreach ($headers as $header) {
        echo '<th>' . $header . '</th>';
    }
    echo '</tr>';
    
    foreach ($data as $row) {
        echo '<tr>';
        foreach ($row as $cell) {
            echo '<td>' . $cell . '</td>';
        }
        echo '</tr>';
    }
    echo '</table>';
    exit();
}
?>