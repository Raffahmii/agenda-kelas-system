<?php
/**
 * Generate QR Code - Sekretaris
 * File: sekre/generate_qr.php
 * Tanpa tombol kembali
 */

require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

checkRoleAccess(['sekretaris']);

$page_title = 'QR Code Validasi';
$page_subtitle = 'QR Code untuk validasi guru';

$userId = $_SESSION['id'];
$absensi_id = $_GET['absensi_id'] ?? 0;

// Ambil data absensi
$stmt = $pdo->prepare("
    SELECT a.*, k.nama_kelas 
    FROM absensi_harian a
    JOIN kelas k ON a.kelas_id = k.id
    WHERE a.id = ? AND a.dibuat_oleh = ?
");
$stmt->execute([$absensi_id, $userId]);
$absensi = $stmt->fetch();

if (!$absensi) {
    header("Location: absensi.php");
    exit();
}

// Ambil detail absensi
$stmt = $pdo->prepare("
    SELECT d.*, s.nama_lengkap, s.nis, s.nomor_absen
    FROM detail_absensi d
    JOIN siswa s ON d.siswa_id = s.id
    WHERE d.absensi_id = ?
    ORDER BY s.nomor_absen ASC
");
$stmt->execute([$absensi_id]);
$detailAbsensi = $stmt->fetchAll();

// Hitung statistik
$stats = [
    'hadir' => 0, 'sakit' => 0, 'izin' => 0, 'dispen' => 0, 'alfa' => 0
];
foreach ($detailAbsensi as $d) {
    $stats[$d['status']]++;
}
$total = array_sum($stats);

// Cek atau buat QR token
$stmt = $pdo->prepare("
    SELECT * FROM validasi_qr WHERE absensi_id = ?
");
$stmt->execute([$absensi_id]);
$qrData = $stmt->fetch();

if (!$qrData) {
    $qr_token = generateQrToken();
    $stmt = $pdo->prepare("
        INSERT INTO validasi_qr (absensi_id, qr_token, status, created_at) 
        VALUES (?, ?, 'pending', NOW())
    ");
    $stmt->execute([$absensi_id, $qr_token]);
    $qrData = ['qr_token' => $qr_token, 'status' => 'pending'];
}

// URL untuk scan QR
$qr_url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME'], 2) . "/guru/validasi.php?token=" . $qrData['qr_token'];

include '../includes/navbar.php';
include '../includes/sidebar.php';
?>

<div class="main-content">
    <div class="content-area">
        
        <!-- Header - Tanpa Tombol Kembali -->
        <div class="page-header">
            <div class="header-left">
                <i class="fas fa-qrcode"></i>
                <div>
                    <h2>QR Code Validasi</h2>
                    <p>Silakan tunjukkan QR ini kepada guru untuk divalidasi</p>
                </div>
            </div>
        </div>
        
        <!-- Info Absensi -->
        <div class="info-card">
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Kelas</span>
                    <span class="info-value"><?php echo htmlspecialchars($absensi['nama_kelas']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Tanggal</span>
                    <span class="info-value"><?php echo formatTanggal($absensi['tanggal'], 'd F Y'); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Total Siswa</span>
                    <span class="info-value"><?php echo $total; ?> siswa</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Status Validasi</span>
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
            </div>
        </div>
        
        <!-- Statistik Kehadiran -->
        <div class="stats-mini">
            <div class="stat-mini">
                <span class="stat-label">Hadir</span>
                <span class="stat-count"><?php echo $stats['hadir']; ?></span>
            </div>
            <div class="stat-mini">
                <span class="stat-label">Sakit</span>
                <span class="stat-count"><?php echo $stats['sakit']; ?></span>
            </div>
            <div class="stat-mini">
                <span class="stat-label">Izin</span>
                <span class="stat-count"><?php echo $stats['izin']; ?></span>
            </div>
            <div class="stat-mini">
                <span class="stat-label">Dispen</span>
                <span class="stat-count"><?php echo $stats['dispen']; ?></span>
            </div>
            <div class="stat-mini">
                <span class="stat-label">Alfa</span>
                <span class="stat-count"><?php echo $stats['alfa']; ?></span>
            </div>
        </div>
        
        <!-- QR Code Display -->
        <div class="qr-card">
            <div class="qr-header">
                <i class="fas fa-qrcode"></i>
                <h3>Scan QR Code</h3>
                <p>Guru dapat memindai QR ini menggunakan halaman Scan QR di akun Guru</p>
            </div>
            <div class="qr-body">
                <div id="qrcode" class="qrcode-container"></div>
                <div class="qr-token">
                    <span>Token: <?php echo $qrData['qr_token']; ?></span>
                    <button onclick="copyToken()" class="btn-copy">
                        <i class="fas fa-copy"></i> Copy
                    </button>
                </div>
            </div>
            
        </div>
        
        <!-- Daftar Siswa -->
        <div class="table-card">
            <div class="table-header">
                <i class="fas fa-users"></i>
                <h3>Daftar Kehadiran Siswa</h3>
            </div>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th width="40">No</th>
                            <th width="70">Absen</th>
                            <th>NIS</th>
                            <th>Nama Siswa</th>
                            <th width="100">Status</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; foreach ($detailAbsensi as $d): ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><?php echo $d['nomor_absen']; ?></td>
                            <td><?php echo htmlspecialchars($d['nis']); ?></td>
                            <td><?php echo htmlspecialchars($d['nama_lengkap']); ?></td>
                            <td>
                                <span class="status-badge <?php echo $d['status']; ?>">
                                    <?php echo ucfirst($d['status']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($d['keterangan'] ?: '-'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
    </div>
</div>

<style>
    .page-header {
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
    
    .info-card {
        background: white;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        padding: 20px 24px;
        margin-bottom: 20px;
    }
    
    .info-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 24px;
    }
    
    .info-item {
        flex: 1;
        min-width: 150px;
    }
    
    .info-label {
        display: block;
        font-size: 11px;
        color: #94a3b8;
        text-transform: uppercase;
        margin-bottom: 4px;
    }
    
    .info-value {
        font-size: 14px;
        font-weight: 600;
        color: #1e293b;
    }
    
    .qr-badge {
        display: inline-block;
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
    
    .stats-mini {
        display: flex;
        gap: 16px;
        margin-bottom: 24px;
        flex-wrap: wrap;
    }
    
    .stat-mini {
        background: white;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        padding: 12px 20px;
        flex: 1;
        text-align: center;
    }
    
    .stat-mini .stat-label {
        display: block;
        font-size: 11px;
        color: #94a3b8;
        margin-bottom: 4px;
    }
    
    .stat-mini .stat-count {
        font-size: 20px;
        font-weight: 700;
        color: #1e293b;
    }
    
    .qr-card {
        background: white;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        text-align: center;
        margin-bottom: 24px;
    }
    
    .qr-header {
        padding: 20px;
        border-bottom: 1px solid #eef2f6;
    }
    
    .qr-header i {
        font-size: 32px;
        color: #D4A000;
        margin-bottom: 8px;
    }
    
    .qr-header h3 {
        font-size: 16px;
        font-weight: 600;
        color: #1e293b;
        margin: 0 0 4px;
    }
    
    .qr-header p {
        font-size: 12px;
        color: #64748b;
        margin: 0;
    }
    
    .qr-body {
        padding: 30px;
    }
    
    .qrcode-container {
        display: flex;
        justify-content: center;
        margin-bottom: 20px;
    }
    
    .qr-token {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        background: #f8fafc;
        padding: 10px 16px;
        display: inline-flex;
        margin: 0 auto;
    }
    
    .qr-token span {
        font-family: monospace;
        font-size: 12px;
        color: #475569;
    }
    
    .btn-copy {
        background: none;
        border: none;
        color: #D4A000;
        cursor: pointer;
        font-size: 12px;
    }
    
    .btn-copy:hover {
        color: #b8860b;
    }
    
    .qr-footer {
        padding: 16px 20px;
        border-top: 1px solid #eef2f6;
        background: #fafafc;
    }
    
    .table-card {
        background: white;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    }
    
    .table-header {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px 20px;
        border-bottom: 1px solid #eef2f6;
    }
    
    .table-header i {
        font-size: 18px;
        color: #D4A000;
    }
    
    .table-header h3 {
        font-size: 14px;
        font-weight: 600;
        color: #1e293b;
        margin: 0;
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
        padding: 12px;
        font-size: 12px;
        font-weight: 600;
        color: #475569;
    }
    
    .data-table td {
        padding: 10px 12px;
        font-size: 12px;
        color: #334155;
        border-bottom: 1px solid #f1f5f9;
    }
    
    .data-table tbody tr:hover {
        background: #fafafc;
    }
    
    .status-badge {
        display: inline-block;
        padding: 2px 8px;
        font-size: 10px;
        font-weight: 500;
    }
    
    .status-badge.hadir {
        background: #dcfce7;
        color: #166534;
    }
    
    .status-badge.sakit {
        background: #fef9c3;
        color: #854d0e;
    }
    
    .status-badge.izin {
        background: #dbeafe;
        color: #1e40af;
    }
    
    .status-badge.dispen {
        background: #ede9fe;
        color: #5b21b6;
    }
    
    .status-badge.alfa {
        background: #fee2e2;
        color: #991b1b;
    }
    
    .text-muted {
        color: #94a3b8;
    }
    
    .small {
        font-size: 11px;
    }
    
    @media (max-width: 768px) {
        .info-grid {
            flex-direction: column;
            gap: 12px;
        }
        
        .stats-mini {
            flex-direction: column;
        }
        
        .qr-body {
            padding: 20px;
        }
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
    new QRCode(document.getElementById("qrcode"), {
        text: "<?php echo $qr_url; ?>",
        width: 200,
        height: 200,
        colorDark: "#1e293b",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.H
    });
    
    function copyToken() {
        const token = "<?php echo $qrData['qr_token']; ?>";
        navigator.clipboard.writeText(token);
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: 'Token berhasil disalin',
            timer: 1500,
            showConfirmButton: false
        });
    }
</script>

<?php include '../includes/footer.php'; ?>