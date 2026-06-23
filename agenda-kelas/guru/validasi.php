<?php
/**
 * Validasi Kehadiran - Guru
 * Dengan SweetAlert2 - TETAP WORK
 */

require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

checkRoleAccess(['guru']);

$page_title = 'Validasi Kehadiran';
$page_subtitle = 'Validasi atau tolak kehadiran siswa';

$user_id = $_SESSION['id'];
$token = isset($_GET['token']) ? $_GET['token'] : '';
$error = '';
$success = '';
$validasiData = null;

if (empty($token)) {
    header("Location: scan_qr.php");
    exit();
}

// Ambil data validasi berdasarkan token
$stmt = $pdo->prepare("
    SELECT v.*, a.tanggal, a.kelas_id, a.dibuat_oleh,
           k.nama_kelas,
           u.nama as sekretaris_nama
    FROM validasi_qr v
    JOIN absensi_harian a ON v.absensi_id = a.id
    JOIN kelas k ON a.kelas_id = k.id
    JOIN users u ON a.dibuat_oleh = u.id
    WHERE v.qr_token = ?
");
$stmt->execute([$token]);
$validasiData = $stmt->fetch();

if (!$validasiData) {
    $error = 'QR Code tidak valid!';
}

// Cek apakah sudah divalidasi
if ($validasiData && $validasiData['status'] != 'pending') {
    $info = $validasiData['status'] == 'valid' ? 'sudah tervalidasi' : 'sudah ditolak';
    $error = "QR Code ini {$info} pada " . formatDateTime($validasiData['validated_at']);
}

// Ambil detail absensi siswa
$detailSiswa = [];
if ($validasiData && $validasiData['status'] == 'pending') {
    $stmt = $pdo->prepare("
        SELECT d.*, s.nama_lengkap, s.nis, s.nomor_absen
        FROM detail_absensi d
        JOIN siswa s ON d.siswa_id = s.id
        WHERE d.absensi_id = ?
        ORDER BY s.nomor_absen ASC
    ");
    $stmt->execute([$validasiData['absensi_id']]);
    $detailSiswa = $stmt->fetchAll();
    
    // Hitung statistik
    $stats = ['hadir' => 0, 'sakit' => 0, 'izin' => 0, 'dispen' => 0, 'alfa' => 0];
    foreach ($detailSiswa as $d) {
        $stats[$d['status']]++;
    }
    $totalSiswa = count($detailSiswa);
}

// PROSES VALIDASI
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'valid') {
        $status = 'valid';
        $message = 'Kehadiran berhasil divalidasi!';
        
        $update = $pdo->prepare("UPDATE validasi_qr SET status = ?, validated_by = ?, validated_at = NOW() WHERE id = ?");
        if ($update->execute([$status, $user_id, $validasiData['id']])) {
            $_SESSION['alert_success'] = $message;
            header("Location: dashboard.php");
            exit();
        } else {
            $error = 'Gagal validasi!';
        }
        
    } elseif ($action == 'ditolak') {
        $status = 'ditolak';
        $message = 'Kehadiran ditolak!';
        
        $update = $pdo->prepare("UPDATE validasi_qr SET status = ?, validated_by = ?, validated_at = NOW() WHERE id = ?");
        if ($update->execute([$status, $user_id, $validasiData['id']])) {
            $_SESSION['alert_success'] = $message;
            header("Location: dashboard.php");
            exit();
        } else {
            $error = 'Gagal menolak!';
        }
    }
}

include '../includes/navbar.php';
include '../includes/sidebar.php';
?>

<div class="main-content">
    <div class="content-area">
        
        <!-- Header -->
        <div class="page-header">
            <div class="header-left">
                <i class="fas fa-check-double"></i>
                <div>
                    <h2>Validasi Kehadiran</h2>
                    <p>Validasi atau tolak kehadiran siswa</p>
                </div>
            </div>
            <a href="scan_qr.php" class="btn-scan-again">
                <i class="fas fa-qrcode"></i> Scan QR Lainnya
            </a>
        </div>
        
        <!-- Alert Error -->
        <?php if ($error): ?>
            <div class="alert-custom alert-error-custom">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $error; ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($validasiData && $validasiData['status'] == 'pending'): ?>
        
        <!-- Info Validasi -->
        <div class="info-card">
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Kelas</span>
                    <span class="info-value"><?php echo htmlspecialchars($validasiData['nama_kelas']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Tanggal Absensi</span>
                    <span class="info-value"><?php echo formatTanggal($validasiData['tanggal'], 'd F Y'); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Total Siswa</span>
                    <span class="info-value"><?php echo $totalSiswa; ?> siswa</span>
                </div>
            </div>
        </div>
        
        <!-- Ringkasan Kehadiran -->
        <div class="summary-card">
            <div class="summary-header">
                <i class="fas fa-chart-simple"></i>
                <h3>Ringkasan Kehadiran</h3>
            </div>
            <div class="summary-body">
                <div class="stats-summary">
                    <div class="stat-item">
                        <span class="stat-value" style="color: #166534;"><?php echo $stats['hadir']; ?></span>
                        <span class="stat-label">Hadir</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value" style="color: #854d0e;"><?php echo $stats['sakit']; ?></span>
                        <span class="stat-label">Sakit</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value" style="color: #1e40af;"><?php echo $stats['izin']; ?></span>
                        <span class="stat-label">Izin</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value" style="color: #5b21b6;"><?php echo $stats['dispen']; ?></span>
                        <span class="stat-label">Dispen</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-value" style="color: #991b1b;"><?php echo $stats['alfa']; ?></span>
                        <span class="stat-label">Alfa</span>
                    </div>
                </div>
                
                <div class="persen-info">
                    <span class="persen-label">Tingkat Kehadiran:</span>
                    <span class="persen-value"><?php echo $totalSiswa > 0 ? round(($stats['hadir'] / $totalSiswa) * 100) : 0; ?>%</span>
                </div>
            </div>
        </div>
        
        <!-- Tabel Siswa -->
        <div class="table-card">
            <div class="table-header">
                <i class="fas fa-users"></i>
                <h3>Daftar Kehadiran Siswa</h3>
            </div>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th width="50">No</th>
                            <th width="70">Absen</th>
                            <th>NIS</th>
                            <th>Nama Siswa</th>
                            <th>Status</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; foreach ($detailSiswa as $siswa): ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><?php echo $siswa['nomor_absen']; ?></td>
                            <td><?php echo htmlspecialchars($siswa['nis']); ?></td>
                            <td class="name-cell">
                                <strong><?php echo htmlspecialchars($siswa['nama_lengkap']); ?></strong>
                            </td>
                            <td>
                                <span class="status-badge <?php echo $siswa['status']; ?>">
                                    <i class="fas <?php 
                                        echo $siswa['status'] == 'hadir' ? 'fa-check-circle' : 
                                            ($siswa['status'] == 'sakit' ? 'fa-thermometer-half' : 
                                            ($siswa['status'] == 'izin' ? 'fa-envelope' : 
                                            ($siswa['status'] == 'dispen' ? 'fa-file-alt' : 'fa-times-circle'))); 
                                    ?>"></i>
                                    <?php echo ucfirst($siswa['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($siswa['keterangan'] ?: '-'); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Form Validasi dengan SweetAlert2 -->
        <div class="action-card">
            <div class="action-header">
                <i class="fas fa-gavel"></i>
                <h3>Konfirmasi Validasi</h3>
                <p>Silakan konfirmasi apakah data kehadiran ini valid atau tidak</p>
            </div>
            <div class="action-body">
                <form method="POST" class="action-form" id="validasiForm">
                    <div class="form-group">
                        <label>Catatan (Opsional)</label>
                        <textarea name="catatan" class="form-textarea" placeholder="Tambahkan catatan jika diperlukan..."></textarea>
                    </div>
                    <div class="action-buttons">
                        <button type="button" class="btn-valid" id="btnValid">
                            <i class="fas fa-check-circle"></i> Validasi Data
                        </button>
                        <button type="button" class="btn-reject" id="btnTolak">
                            <i class="fas fa-times-circle"></i> Tolak Data
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <?php elseif ($validasiData && $validasiData['status'] != 'pending'): ?>
        
        <!-- Hasil Validasi -->
        <div class="result-card">
            <div class="result-header <?php echo $validasiData['status']; ?>">
                <i class="fas <?php echo $validasiData['status'] == 'valid' ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
                <h3><?php echo $validasiData['status'] == 'valid' ? 'Validasi Berhasil' : 'Validasi Ditolak'; ?></h3>
            </div>
            <div class="result-body">
                <div class="result-grid">
                    <div class="result-item">
                        <span class="result-label">Kelas</span>
                        <span class="result-value"><?php echo htmlspecialchars($validasiData['nama_kelas']); ?></span>
                    </div>
                    <div class="result-item">
                        <span class="result-label">Tanggal Absensi</span>
                        <span class="result-value"><?php echo formatTanggal($validasiData['tanggal'], 'd F Y'); ?></span>
                    </div>
                    <div class="result-item">
                        <span class="result-label">Status Validasi</span>
                        <span class="result-badge <?php echo $validasiData['status']; ?>">
                            <?php echo $validasiData['status'] == 'valid' ? 'Tervalidasi' : 'Ditolak'; ?>
                        </span>
                    </div>
                    <div class="result-item">
                        <span class="result-label">Waktu Validasi</span>
                        <span class="result-value"><?php echo formatDateTime($validasiData['validated_at']); ?></span>
                    </div>
                </div>
                <div class="result-actions">
                    <a href="scan_qr.php" class="btn-scan-again">
                        <i class="fas fa-qrcode"></i> Scan QR Lainnya
                    </a>
                    <a href="dashboard.php" class="btn-history">
                        <i class="fas fa-tachometer-alt"></i> Ke Dashboard
                    </a>
                </div>
            </div>
        </div>
        
        <?php endif; ?>
        
    </div>
</div>

<style>
    .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
    .header-left { display: flex; align-items: center; gap: 16px; }
    .header-left i { font-size: 40px; color: #D4A000; }
    .header-left h2 { font-size: 20px; font-weight: 600; color: #1e293b; margin: 0 0 4px; }
    .header-left p { font-size: 13px; color: #64748b; margin: 0; }
    
    .btn-scan-again { background: #f1f5f9; color: #475569; padding: 10px 20px; text-decoration: none; font-size: 13px; font-weight: 500; display: inline-flex; align-items: center; gap: 8px; }
    .btn-scan-again:hover { background: #e2e8f0; }
    
    .alert-custom { padding: 14px 20px; margin-bottom: 24px; display: flex; align-items: center; gap: 12px; }
    .alert-error-custom { background: #ffebee; color: #c62828; border-left: 4px solid #c62828; }
    
    .info-card { background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.04); padding: 20px 24px; margin-bottom: 20px; }
    .info-grid { display: flex; flex-wrap: wrap; gap: 24px; }
    .info-item { flex: 1; min-width: 150px; }
    .info-label { display: block; font-size: 11px; color: #94a3b8; text-transform: uppercase; margin-bottom: 4px; }
    .info-value { font-size: 14px; font-weight: 600; color: #1e293b; }
    
    .summary-card { background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.04); margin-bottom: 20px; }
    .summary-header { display: flex; align-items: center; gap: 12px; padding: 16px 20px; border-bottom: 1px solid #eef2f6; }
    .summary-header i { font-size: 18px; color: #D4A000; }
    .summary-header h3 { font-size: 14px; font-weight: 600; color: #1e293b; margin: 0; }
    .summary-body { padding: 20px; }
    
    .stats-summary { display: flex; flex-wrap: wrap; gap: 16px; margin-bottom: 20px; justify-content: space-around; }
    .stat-item { text-align: center; flex: 1; }
    .stat-value { font-size: 28px; font-weight: 700; }
    .stat-label { font-size: 11px; color: #64748b; margin-top: 4px; }
    .persen-info { text-align: center; padding-top: 16px; border-top: 1px solid #eef2f6; }
    .persen-label { font-size: 13px; color: #64748b; }
    .persen-value { font-size: 18px; font-weight: 700; color: #1e293b; margin-left: 8px; }
    
    .table-card { background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.04); margin-bottom: 20px; }
    .table-header { display: flex; align-items: center; gap: 12px; padding: 16px 20px; border-bottom: 1px solid #eef2f6; }
    .table-header i { font-size: 18px; color: #D4A000; }
    .table-header h3 { font-size: 14px; font-weight: 600; color: #1e293b; margin: 0; }
    
    .data-table { width: 100%; border-collapse: collapse; }
    .data-table th { text-align: left; padding: 12px 16px; font-size: 12px; font-weight: 600; color: #475569; background: #f8fafc; border-bottom: 1px solid #eef2f6; }
    .data-table td { padding: 12px 16px; font-size: 13px; color: #334155; border-bottom: 1px solid #f1f5f9; }
    .name-cell strong { font-weight: 600; color: #1e293b; }
    
    .status-badge { display: inline-flex; align-items: center; gap: 8px; padding: 4px 12px; font-size: 12px; font-weight: 500; }
    .status-badge.hadir { background: #dcfce7; color: #166534; }
    .status-badge.sakit { background: #fef9c3; color: #854d0e; }
    .status-badge.izin { background: #dbeafe; color: #1e40af; }
    .status-badge.dispen { background: #ede9fe; color: #5b21b6; }
    .status-badge.alfa { background: #fee2e2; color: #991b1b; }
    
    .action-card { background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.04); margin-bottom: 20px; }
    .action-header { text-align: center; padding: 20px; border-bottom: 1px solid #eef2f6; }
    .action-header i { font-size: 32px; color: #D4A000; margin-bottom: 8px; display: block; }
    .action-header h3 { font-size: 16px; font-weight: 600; color: #1e293b; margin: 0 0 4px; }
    .action-header p { font-size: 13px; color: #64748b; margin: 0; }
    
    .action-body { padding: 24px; }
    .action-form .form-group { margin-bottom: 20px; }
    .action-form label { display: block; font-size: 13px; font-weight: 500; color: #1e293b; margin-bottom: 8px; }
    .form-textarea { width: 100%; padding: 12px; border: 1px solid #e2e8f0; font-size: 13px; font-family: 'Poppins', sans-serif; resize: vertical; }
    .form-textarea:focus { outline: none; border-color: #D4A000; }
    
    .action-buttons { display: flex; gap: 16px; }
    .btn-valid { flex: 1; background: #22c55e; color: white; padding: 12px 20px; border: none; font-size: 14px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 8px; }
    .btn-valid:hover { background: #16a34a; }
    .btn-reject { flex: 1; background: #ef4444; color: white; padding: 12px 20px; border: none; font-size: 14px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 8px; }
    .btn-reject:hover { background: #dc2626; }
    
    .result-card { background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.04); margin-bottom: 20px; }
    .result-header { text-align: center; padding: 30px 20px; }
    .result-header.valid { background: #dcfce7; }
    .result-header.ditolak { background: #fee2e2; }
    .result-header i { font-size: 56px; margin-bottom: 12px; display: block; }
    .result-header.valid i { color: #166534; }
    .result-header.ditolak i { color: #991b1b; }
    .result-header h3 { font-size: 20px; font-weight: 700; margin: 0; }
    .result-header.valid h3 { color: #166534; }
    .result-header.ditolak h3 { color: #991b1b; }
    .result-body { padding: 24px; }
    .result-grid { display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 24px; padding-bottom: 20px; border-bottom: 1px solid #eef2f6; }
    .result-item { flex: 1; min-width: 150px; }
    .result-label { display: block; font-size: 11px; color: #94a3b8; text-transform: uppercase; margin-bottom: 4px; }
    .result-value { font-size: 14px; font-weight: 600; color: #1e293b; }
    .result-badge { display: inline-block; padding: 4px 12px; font-size: 12px; font-weight: 500; }
    .result-badge.valid { background: #dcfce7; color: #166534; }
    .result-badge.ditolak { background: #fee2e2; color: #991b1b; }
    .result-actions { display: flex; gap: 16px; justify-content: center; }
    .btn-history { background: #f1f5f9; color: #475569; padding: 10px 20px; text-decoration: none; font-size: 13px; font-weight: 500; display: inline-flex; align-items: center; gap: 8px; }
    .btn-history:hover { background: #e2e8f0; }
    
    @media (max-width: 768px) {
        .page-header { flex-direction: column; align-items: flex-start; gap: 16px; }
        .action-buttons { flex-direction: column; }
        .stats-summary { gap: 12px; }
        .stat-value { font-size: 22px; }
        .result-actions { flex-direction: column; }
        .result-actions a { justify-content: center; }
    }
</style>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    const form = document.getElementById('validasiForm');
    
    // Tombol Validasi
    document.getElementById('btnValid').addEventListener('click', function() {
        Swal.fire({
            title: 'Validasi Kehadiran',
            text: 'Apakah data kehadiran ini sudah sesuai dan valid?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#22c55e',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Validasi!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                // Submit form dengan action valid
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'action';
                input.value = 'valid';
                form.appendChild(input);
                form.submit();
            }
        });
    });
    
    // Tombol Tolak
    document.getElementById('btnTolak').addEventListener('click', function() {
        Swal.fire({
            title: 'Tolak Validasi',
            text: 'Apakah Anda yakin ingin menolak data kehadiran ini?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Tolak!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                // Submit form dengan action ditolak
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'action';
                input.value = 'ditolak';
                form.appendChild(input);
                form.submit();
            }
        });
    });
</script>

<?php include '../includes/footer.php'; ?>