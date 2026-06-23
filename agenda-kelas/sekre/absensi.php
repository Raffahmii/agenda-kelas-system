<?php
/**
 * Kelola Kehadiran - Sekretaris
 * File: sekre/absensi.php
 */

require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

checkRoleAccess(['sekretaris']);

$page_title = 'Kelola Kehadiran';
$page_subtitle = 'Input dan kelola absensi harian siswa';

$userId = $_SESSION['id'];
$success = '';
$error = '';
$justSaved = false;
$savedAbsensiId = null;

// Handle hapus validasi QR (batalkan)
if (isset($_GET['cancel_qr']) && is_numeric($_GET['cancel_qr'])) {
    $validasi_id = $_GET['cancel_qr'];
    
    // Cek kepemilikan validasi (via absensi)
    $stmt = $pdo->prepare("
        DELETE v FROM validasi_qr v
        JOIN absensi_harian a ON v.absensi_id = a.id
        WHERE v.id = ? AND a.dibuat_oleh = ?
    ");
    if ($stmt->execute([$validasi_id, $userId])) {
        $success = 'QR Code berhasil dibatalkan!';
    } else {
        $error = 'Gagal membatalkan QR Code!';
    }
}

// Ambil daftar kelas yang pernah dibuat absensinya oleh sekretaris ini
// TAPI jika belum pernah buat absensi, tampilkan semua kelas
$stmt = $pdo->prepare("
    SELECT DISTINCT k.id, k.nama_kelas 
    FROM kelas k
    LEFT JOIN absensi_harian a ON k.id = a.kelas_id AND a.dibuat_oleh = ?
    ORDER BY k.nama_kelas
");
$stmt->execute([$userId]);
$kelasList = $stmt->fetchAll();

// Jika tidak ada kelas yang memiliki absensi, ambil semua kelas
if (count($kelasList) == 0) {
    $stmt = $pdo->prepare("SELECT id, nama_kelas FROM kelas ORDER BY nama_kelas");
    $stmt->execute();
    $kelasList = $stmt->fetchAll();
}

// Parameter filter
$kelas_id = $_GET['kelas_id'] ?? ($kelasList[0]['id'] ?? 0);
$tanggal = $_GET['tanggal'] ?? date('Y-m-d');

// Ambil data absensi hari ini
$absensi = null;
$siswaList = [];
$detailAbsensi = [];

if ($kelas_id) {
    // Cek apakah sudah ada absensi untuk kelas & tanggal ini
    $stmt = $pdo->prepare("
        SELECT * FROM absensi_harian 
        WHERE kelas_id = ? AND tanggal = ? AND dibuat_oleh = ?
    ");
    $stmt->execute([$kelas_id, $tanggal, $userId]);
    $absensi = $stmt->fetch();
    
    // Ambil semua siswa di kelas
    $stmt = $pdo->prepare("
        SELECT s.*, u.nama as user_nama 
        FROM siswa s
        JOIN users u ON s.user_id = u.id
        WHERE s.kelas_id = ? 
        ORDER BY s.nomor_absen ASC
    ");
    $stmt->execute([$kelas_id]);
    $siswaList = $stmt->fetchAll();
    
    // Jika sudah ada absensi, ambil detail status
    if ($absensi) {
        $stmt = $pdo->prepare("
            SELECT * FROM detail_absensi 
            WHERE absensi_id = ?
        ");
        $stmt->execute([$absensi['id']]);
        foreach ($stmt->fetchAll() as $detail) {
            $detailAbsensi[$detail['siswa_id']] = $detail;
        }
    }
}

// Proses simpan absensi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_absensi'])) {
    $kelas_id_post = $_POST['kelas_id'];
    $tanggal_post = $_POST['tanggal'];
    $status = $_POST['status'] ?? [];
    $keterangan = $_POST['keterangan'] ?? [];
    
    try {
        $pdo->beginTransaction();
        
        // Cek apakah sudah ada absensi
        $stmt = $pdo->prepare("
            SELECT id FROM absensi_harian 
            WHERE kelas_id = ? AND tanggal = ? AND dibuat_oleh = ?
        ");
        $stmt->execute([$kelas_id_post, $tanggal_post, $userId]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            $absensi_id = $existing['id'];
            // Update existing
            foreach ($status as $siswa_id => $stat) {
                $ket = $keterangan[$siswa_id] ?? null;
                $stmt = $pdo->prepare("
                    UPDATE detail_absensi 
                    SET status = ?, keterangan = ? 
                    WHERE absensi_id = ? AND siswa_id = ?
                ");
                $stmt->execute([$stat, $ket, $absensi_id, $siswa_id]);
            }
            $success = 'Absensi berhasil diperbarui!';
            $justSaved = true;
            $savedAbsensiId = $absensi_id;
        } else {
            // Buat absensi baru
            $stmt = $pdo->prepare("
                INSERT INTO absensi_harian (kelas_id, tanggal, dibuat_oleh, created_at) 
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$kelas_id_post, $tanggal_post, $userId]);
            $absensi_id = $pdo->lastInsertId();
            
            // Insert detail untuk semua siswa
            $stmt = $pdo->prepare("
                INSERT INTO detail_absensi (absensi_id, siswa_id, status, keterangan, created_at) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            
            foreach ($siswaList as $siswa) {
                $siswa_id = $siswa['id'];
                $stat = $status[$siswa_id] ?? 'hadir';
                $ket = $keterangan[$siswa_id] ?? null;
                $stmt->execute([$absensi_id, $siswa_id, $stat, $ket]);
            }
            $success = 'Absensi berhasil disimpan!';
            $justSaved = true;
            $savedAbsensiId = $absensi_id;
        }
        
        $pdo->commit();
        
        // Refresh data absensi
        $stmt = $pdo->prepare("
            SELECT * FROM absensi_harian 
            WHERE id = ?
        ");
        $stmt->execute([$absensi_id]);
        $absensi = $stmt->fetch();
        
        // Refresh detail absensi
        $stmt = $pdo->prepare("SELECT * FROM detail_absensi WHERE absensi_id = ?");
        $stmt->execute([$absensi_id]);
        $detailAbsensi = [];
        foreach ($stmt->fetchAll() as $detail) {
            $detailAbsensi[$detail['siswa_id']] = $detail;
        }
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = 'Gagal menyimpan absensi: ' . $e->getMessage();
    }
}

// Proses Generate QR - LANGSUNG REDIRECT
if (isset($_GET['generate_qr']) && $absensi) {
    header("Location: generate_qr.php?absensi_id=" . $absensi['id']);
    exit();
}

include '../includes/navbar.php';
include '../includes/sidebar.php';
?>

<div class="main-content">
    <div class="content-area">
        
        <!-- Alert Notifikasi -->
        <?php if ($success): ?>
            <div class="alert-custom alert-success-custom">
                <i class="fas fa-check-circle"></i>
                <span><?php echo $success; ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert-custom alert-error-custom">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $error; ?></span>
            </div>
        <?php endif; ?>
        
        <!-- Header -->
        <div class="page-header">
            <div class="header-left">
                <i class="fas fa-fingerprint"></i>
                <div>
                    <h2>Kelola Kehadiran</h2>
                    <p>Input absensi harian siswa</p>
                </div>
            </div>
        </div>
        
        <!-- Filter Section -->
        <div class="filter-card">
            <form method="GET" action="" class="filter-form">
                <div class="filter-row">
                    <div class="filter-group">
                        <label>Pilih Kelas</label>
                        <select name="kelas_id" class="filter-select" onchange="this.form.submit()">
                            <option value="">-- Pilih Kelas --</option>
                            <?php foreach ($kelasList as $kelas): ?>
                                <option value="<?php echo $kelas['id']; ?>" <?php echo $kelas_id == $kelas['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($kelas['nama_kelas']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Tanggal Absensi</label>
                        <input type="date" name="tanggal" class="filter-date" value="<?php echo $tanggal; ?>" onchange="this.form.submit()">
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Form Absensi -->
        <?php if ($kelas_id && count($siswaList) > 0): ?>
            <div class="form-card">
                <form method="POST" id="absensiForm">
                    <input type="hidden" name="kelas_id" value="<?php echo $kelas_id; ?>">
                    <input type="hidden" name="tanggal" value="<?php echo $tanggal; ?>">
                    
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th width="40">No</th>
                                    <th width="70">No. Absen</th>
                                    <th>NIS</th>
                                    <th>Nama Siswa</th>
                                    <th width="130">Status</th>
                                    <th width="200">Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1; foreach ($siswaList as $siswa): 
                                    $currentStatus = $detailAbsensi[$siswa['id']]['status'] ?? 'hadir';
                                    $currentKeterangan = $detailAbsensi[$siswa['id']]['keterangan'] ?? '';
                                ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td><?php echo $siswa['nomor_absen']; ?></td>
                                    <td><?php echo htmlspecialchars($siswa['nis']); ?></td>
                                    <td class="siswa-name">
                                        <strong><?php echo htmlspecialchars($siswa['nama_lengkap']); ?></strong>
                                    </td>
                                    <td>
                                        <select name="status[<?php echo $siswa['id']; ?>]" class="status-select">
                                            <option value="hadir" <?php echo $currentStatus == 'hadir' ? 'selected' : ''; ?>>Hadir</option>
                                            <option value="sakit" <?php echo $currentStatus == 'sakit' ? 'selected' : ''; ?>>Sakit</option>
                                            <option value="izin" <?php echo $currentStatus == 'izin' ? 'selected' : ''; ?>>Izin</option>
                                            <option value="dispen" <?php echo $currentStatus == 'dispen' ? 'selected' : ''; ?>>Dispen</option>
                                            <option value="alfa" <?php echo $currentStatus == 'alfa' ? 'selected' : ''; ?>>Alfa</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" name="keterangan[<?php echo $siswa['id']; ?>]" class="keterangan-input" placeholder="Keterangan (opsional)" value="<?php echo htmlspecialchars($currentKeterangan); ?>">
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="save_absensi" class="btn-primary">
                            <i class="fas fa-save"></i> Simpan Absensi
                        </button>
                        <button type="button" class="btn-reset" onclick="resetToDefault()">
                            <i class="fas fa-undo-alt"></i> Reset ke Hadir
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Tombol Generate QR - LANGSUNG REDIRECT ke generate_qr.php -->
            <?php if ($absensi): ?>
            <div class="generate-qr-card">
                <div class="generate-qr-content">
                    <div class="generate-icon">
                        <i class="fas fa-qrcode"></i>
                    </div>
                    <div class="generate-text">
                        <h4>Buat QR Code Validasi</h4>
                        <p>Generate QR Code untuk divalidasi oleh guru</p>
                    </div>
                    <a href="?kelas_id=<?php echo $kelas_id; ?>&tanggal=<?php echo $tanggal; ?>&generate_qr=1" class="btn-generate">
                        <i class="fas fa-qrcode"></i> Generate QR Code
                    </a>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Info Status Validasi - DENGAN TOMBOL BATALKAN -->
            <?php if ($absensi): 
                $stmt = $pdo->prepare("SELECT * FROM validasi_qr WHERE absensi_id = ?");
                $stmt->execute([$absensi['id']]);
                $qrData = $stmt->fetch();
            ?>
                <?php if ($qrData): ?>
                <div class="qr-info-card">
                    <div class="qr-info-header">
                        <i class="fas fa-qrcode"></i>
                        <h3>Status Validasi QR</h3>
                        <?php if ($qrData['status'] == 'pending'): ?>
                            <div class="qr-actions">
                                <a href="?kelas_id=<?php echo $kelas_id; ?>&tanggal=<?php echo $tanggal; ?>&generate_qr=1" class="btn-regenerate">
                                    <i class="fas fa-sync-alt"></i> Regenerate
                                </a>
                                <button onclick="cancelValidation(<?php echo $qrData['id']; ?>)" class="btn-cancel">
                                    <i class="fas fa-times"></i> Batalkan
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="qr-info-body">
                        <div class="qr-status">
                            <span class="qr-label">Status:</span>
                            <span class="qr-badge <?php echo $qrData['status']; ?>">
                                <?php 
                                $statusText = [
                                    'pending' => 'Menunggu Validasi',
                                    'valid' => 'Tervalidasi ✓',
                                    'ditolak' => 'Ditolak ✗'
                                ];
                                echo $statusText[$qrData['status']] ?? ucfirst($qrData['status']);
                                ?>
                            </span>
                        </div>
                        <?php if ($qrData['status'] == 'pending'): ?>
                            <div class="qr-warning">
                                <i class="fas fa-info-circle"></i>
                                <span>QR Code sedang menunggu validasi oleh guru.</span>
                            </div>
                        <?php endif; ?>
                        <?php if ($qrData['validated_at']): ?>
                        <div class="qr-info">
                            <span class="qr-label">Divalidasi oleh:</span>
                            <span><?php 
                                $stmt2 = $pdo->prepare("SELECT nama FROM users WHERE id = ?");
                                $stmt2->execute([$qrData['validated_by']]);
                                $validator = $stmt2->fetch();
                                echo htmlspecialchars($validator['nama'] ?? 'Unknown');
                            ?></span>
                        </div>
                        <div class="qr-info">
                            <span class="qr-label">Tanggal validasi:</span>
                            <span><?php echo formatDateTime($qrData['validated_at']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            <?php endif; ?>
            
        <?php elseif ($kelas_id && count($siswaList) == 0): ?>
            <div class="empty-card">
                <i class="fas fa-users-slash"></i>
                <p>Belum ada siswa di kelas ini</p>
            </div>
        <?php elseif (count($kelasList) == 0): ?>
            <div class="empty-card">
                <i class="fas fa-school"></i>
                <p>Belum ada data absensi</p>
                <p class="text-muted small">Silakan buat absensi terlebih dahulu</p>
            </div>
        <?php endif; ?>
        
    </div>
</div>

<style>
    .alert-custom {
        padding: 14px 20px;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        gap: 12px;
        font-weight: 500;
    }
    
    .alert-success-custom {
        background: #e8f5e9;
        color: #2e7d32;
        border-left: 4px solid #2e7d32;
    }
    
    .alert-error-custom {
        background: #ffebee;
        color: #c62828;
        border-left: 4px solid #c62828;
    }
    
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
        padding: 0 4px;
    }
    
    .header-left {
        display: flex;
        align-items: center;
        gap: 16px;
    }
    
    .header-left i {
        font-size: 40px;
        color: #D4A000;
    }
    
    .header-left h2 {
        font-size: 20px;
        font-weight: 600;
        color: #1e293b;
        margin: 0 0 4px;
    }
    
    .header-left p {
        font-size: 13px;
        color: #64748b;
        margin: 0;
    }
    
    .filter-card {
        background: white;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        padding: 20px 24px;
        margin-bottom: 24px;
    }
    
    .filter-row {
        display: flex;
        gap: 20px;
        align-items: flex-end;
        flex-wrap: wrap;
    }
    
    .filter-group {
        flex: 1;
        min-width: 180px;
    }
    
    .filter-group label {
        display: block;
        font-size: 12px;
        font-weight: 500;
        color: #64748b;
        margin-bottom: 6px;
    }
    
    .filter-select, .filter-date {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #e2e8f0;
        font-size: 13px;
        font-family: 'Poppins', sans-serif;
        background: white;
    }
    
    .filter-select:focus, .filter-date:focus {
        outline: none;
        border-color: #D4A000;
    }
    
    .form-card {
        background: white;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        overflow-x: auto;
    }
    
    .data-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .data-table thead {
        background: #f8fafc;
        border-bottom: 1px solid #eef2f6;
    }
    
    .data-table th {
        text-align: left;
        padding: 14px 12px;
        font-size: 13px;
        font-weight: 600;
        color: #475569;
    }
    
    .data-table td {
        padding: 12px;
        font-size: 13px;
        color: #334155;
        border-bottom: 1px solid #f1f5f9;
    }
    
    .data-table tbody tr:hover {
        background: #fafafc;
    }
    
    .siswa-name strong {
        font-weight: 500;
        color: #1e293b;
    }
    
    .status-select {
        padding: 8px 10px;
        border: 1px solid #e2e8f0;
        font-size: 12px;
        font-family: 'Poppins', sans-serif;
        background: white;
        cursor: pointer;
    }
    
    .status-select:focus {
        outline: none;
        border-color: #D4A000;
    }
    
    .keterangan-input {
        width: 100%;
        padding: 8px 10px;
        border: 1px solid #e2e8f0;
        font-size: 12px;
        font-family: 'Poppins', sans-serif;
    }
    
    .keterangan-input:focus {
        outline: none;
        border-color: #D4A000;
    }
    
    .form-actions {
        display: flex;
        gap: 12px;
        padding: 20px 24px;
        border-top: 1px solid #eef2f6;
        background: white;
    }
    
    .btn-primary {
        background: #D4A000;
        color: white;
        padding: 10px 24px;
        border: none;
        font-size: 13px;
        font-weight: 500;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .btn-primary:hover {
        background: #b8860b;
    }
    
    .btn-reset {
        background: #f1f5f9;
        color: #475569;
        padding: 10px 24px;
        border: none;
        font-size: 13px;
        font-weight: 500;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .btn-reset:hover {
        background: #e2e8f0;
    }
    
    .generate-qr-card {
        background: linear-gradient(135deg, #fef9e7 0%, #fff8e7 100%);
        border: 1px solid #D4A000;
        margin-top: 24px;
    }
    
    .generate-qr-content {
        display: flex;
        align-items: center;
        gap: 20px;
        padding: 20px 24px;
        flex-wrap: wrap;
    }
    
    .generate-icon i {
        font-size: 48px;
        color: #D4A000;
    }
    
    .generate-text {
        flex: 1;
    }
    
    .generate-text h4 {
        font-size: 16px;
        font-weight: 600;
        color: #1e293b;
        margin: 0 0 4px;
    }
    
    .generate-text p {
        font-size: 13px;
        color: #64748b;
        margin: 0;
    }
    
    .btn-generate {
        background: #D4A000;
        color: white;
        padding: 12px 24px;
        text-decoration: none;
        font-size: 14px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        transition: all 0.2s;
    }
    
    .btn-generate:hover {
        background: #b8860b;
        transform: translateY(-1px);
    }
    
    .qr-info-card {
        background: white;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        margin-top: 16px;
    }
    
    .qr-info-header {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px 20px;
        border-bottom: 1px solid #eef2f6;
    }
    
    .qr-info-header i {
        font-size: 20px;
        color: #D4A000;
    }
    
    .qr-info-header h3 {
        font-size: 14px;
        font-weight: 600;
        color: #1e293b;
        margin: 0;
        flex: 1;
    }
    
    .qr-actions {
        display: flex;
        gap: 10px;
    }
    
    .btn-regenerate {
        background: #f1f5f9;
        color: #475569;
        padding: 6px 12px;
        text-decoration: none;
        font-size: 11px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    
    .btn-regenerate:hover {
        background: #e2e8f0;
    }
    
    .btn-cancel {
        background: #fee2e2;
        color: #991b1b;
        padding: 6px 12px;
        border: none;
        font-size: 11px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    
    .btn-cancel:hover {
        background: #fecaca;
    }
    
    .qr-info-body {
        padding: 20px;
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        align-items: center;
    }
    
    .qr-status, .qr-info, .qr-warning {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 13px;
    }
    
    .qr-warning {
        background: #fef9c3;
        padding: 8px 12px;
        color: #854d0e;
    }
    
    .qr-label {
        color: #64748b;
        font-weight: 500;
    }
    
    .qr-badge {
        padding: 4px 12px;
        font-size: 12px;
        font-weight: 500;
    }
    
    .qr-badge.pending {
        background: #fef9c3;
        color: #854d0e;
    }
    
    .qr-badge.valid {
        background: #dcfce7;
        color: #166534;
    }
    
    .qr-badge.ditolak {
        background: #fee2e2;
        color: #991b1b;
    }
    
    .empty-card {
        background: white;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        padding: 48px 20px;
        text-align: center;
    }
    
    .empty-card i {
        font-size: 48px;
        color: #cbd5e1;
        display: block;
        margin-bottom: 16px;
    }
    
    .empty-card p {
        color: #64748b;
        margin-bottom: 16px;
    }
    
    .text-muted {
        color: #94a3b8;
    }
    
    .small {
        font-size: 12px;
    }
    
    @media (max-width: 768px) {
        .filter-row {
            flex-direction: column;
            gap: 12px;
        }
        
        .filter-group {
            width: 100%;
        }
        
        .form-actions {
            flex-direction: column;
        }
        
        .btn-primary, .btn-reset {
            justify-content: center;
        }
        
        .generate-qr-content {
            flex-direction: column;
            text-align: center;
        }
        
        .btn-generate {
            width: 100%;
            justify-content: center;
        }
        
        .qr-info-header {
            flex-wrap: wrap;
        }
        
        .qr-actions {
            width: 100%;
            justify-content: flex-end;
        }
        
        .qr-info-body {
            flex-direction: column;
            align-items: flex-start;
        }
    }
</style>

<script>
    function resetToDefault() {
        const selects = document.querySelectorAll('.status-select');
        const inputs = document.querySelectorAll('.keterangan-input');
        
        selects.forEach(select => {
            select.value = 'hadir';
        });
        
        inputs.forEach(input => {
            input.value = '';
        });
    }
    
    function cancelValidation(validasiId) {
        Swal.fire({
            title: 'Batalkan Validasi?',
            text: 'QR Code akan dihapus dan tidak bisa digunakan untuk validasi. Apakah Anda yakin?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Batalkan!',
            cancelButtonText: 'Tidak'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '?cancel_qr=' + validasiId + '&kelas_id=<?php echo $kelas_id; ?>&tanggal=<?php echo $tanggal; ?>';
            }
        });
    }
</script>

<?php include '../includes/footer.php'; ?>