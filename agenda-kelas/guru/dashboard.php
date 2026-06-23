<?php
/**
 * Dashboard Guru
 * File: guru/dashboard.php
 */

require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

checkRoleAccess(['guru']);

$page_title = 'Dashboard';
$page_subtitle = 'Validasi kehadiran dan pantau absensi';

$user_id = $_SESSION['id'];

// Tambahkan di bagian atas, setelah session_start()


// Ambil data guru (dari users)
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$guru = $stmt->fetch();

// Total validasi yang perlu dilakukan (pending)
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total 
    FROM validasi_qr 
    WHERE status = 'pending'
");
$stmt->execute();
$pendingValidasi = $stmt->fetch()['total'];

// Total validasi yang sudah dilakukan
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total 
    FROM validasi_qr 
    WHERE status IN ('valid', 'ditolak')
");
$stmt->execute();
$totalValidasi = $stmt->fetch()['total'];

// Total validasi yang berhasil (valid)
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total 
    FROM validasi_qr 
    WHERE status = 'valid'
");
$stmt->execute();
$validValidasi = $stmt->fetch()['total'];

// Total validasi yang ditolak
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total 
    FROM validasi_qr 
    WHERE status = 'ditolak'
");
$stmt->execute();
$ditolakValidasi = $stmt->fetch()['total'];

// Ambil daftar validasi terbaru yang perlu divalidasi
$stmt = $pdo->prepare("
    SELECT v.*, a.tanggal, k.nama_kelas,
           (SELECT COUNT(*) FROM detail_absensi WHERE absensi_id = a.id) as total_siswa
    FROM validasi_qr v
    JOIN absensi_harian a ON v.absensi_id = a.id
    JOIN kelas k ON a.kelas_id = k.id
    WHERE v.status = 'pending'
    ORDER BY v.created_at DESC
    LIMIT 5
");
$stmt->execute();
$pendingList = $stmt->fetchAll();

// Ambil riwayat validasi terbaru
$stmt = $pdo->prepare("
    SELECT v.*, a.tanggal, k.nama_kelas,
           (SELECT COUNT(*) FROM detail_absensi WHERE absensi_id = a.id) as total_siswa
    FROM validasi_qr v
    JOIN absensi_harian a ON v.absensi_id = a.id
    JOIN kelas k ON a.kelas_id = k.id
    WHERE v.status IN ('valid', 'ditolak')
    ORDER BY v.validated_at DESC
    LIMIT 5
");

if (isset($_SESSION['alert_success'])) {
    echo "<div style='background: #dcfce7; color: #166534; padding: 12px 20px; margin-bottom: 20px; border-left: 4px solid #22c55e;'>
            <i class='fas fa-check-circle'></i> " . $_SESSION['alert_success'] . "
          </div>";
    unset($_SESSION['alert_success']);
}

$stmt->execute();
$riwayatTerbaru = $stmt->fetchAll();

include '../includes/navbar.php';
include '../includes/sidebar.php';
?>

<div class="main-content">
    <div class="content-area">
        
        <!-- Welcome Banner -->
        <div class="welcome-card">
            <div class="welcome-icon">
                <i class="fas fa-chalkboard-user"></i>
            </div>
            <div class="welcome-text">
                <h2>Selamat Datang, <?php echo htmlspecialchars($guru['nama']); ?>!</h2>
                <p>Silakan validasi kehadiran siswa dengan scan QR Code</p>
            </div>
            <a href="scan_qr.php" class="btn-scan">
                <i class="fas fa-camera"></i> Scan QR Sekarang
            </a>
        </div>
        
        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $pendingValidasi; ?></div>
                    <div class="stat-label">Menunggu Validasi</div>
                    <div class="stat-note">Perlu di scan</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $validValidasi; ?></div>
                    <div class="stat-label">Tervalidasi</div>
                    <div class="stat-note">Berhasil</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $ditolakValidasi; ?></div>
                    <div class="stat-label">Ditolak</div>
                    <div class="stat-note">Tidak valid</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-qrcode"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $totalValidasi; ?></div>
                    <div class="stat-label">Total Validasi</div>
                    <div class="stat-note">Keseluruhan</div>
                </div>
            </div>
        </div>
        
        <!-- Dua Kolom -->
        <div class="two-columns">
            <!-- Perlu Divalidasi -->
            <div class="table-card">
                <div class="table-header">
                    <i class="fas fa-clock"></i>
                    <h3>Perlu Divalidasi</h3>
                    <a href="validasi.php" class="link-view">Lihat semua</a>
                </div>
                <div class="table-responsive">
                    <?php if (count($pendingList) > 0): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Kelas</th>
                                <th>Tanggal</th>
                                <th>Siswa</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingList as $item): ?>
                            <tr>
                                <td class="kelas-cell"><?php echo htmlspecialchars($item['nama_kelas']); ?></td>
                                <td><?php echo formatTanggal($item['tanggal'], 'd/m/Y'); ?></td>
                                <td><?php echo $item['total_siswa']; ?> siswa</td>
                                <td class="action-cell">
                                    <a href="validasi.php?token=<?php echo $item['qr_token']; ?>" class="btn-validate">
                                        <i class="fas fa-check-circle"></i> Validasi
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-check-circle"></i>
                        <p>Tidak ada data yang perlu divalidasi</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Riwayat Validasi Terbaru -->
            <div class="table-card">
                <div class="table-header">
                    <i class="fas fa-history"></i>
                    <h3>Riwayat Validasi Terbaru</h3>
                    <a href="riwayat_validasi.php" class="link-view">Lihat semua</a>
                </div>
                <div class="table-responsive">
                    <?php if (count($riwayatTerbaru) > 0): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Kelas</th>
                                <th>Tanggal</th>
                                <th>Status</th>
                                <th>Tgl Validasi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($riwayatTerbaru as $item): ?>
                            <tr>
                                <td class="kelas-cell"><?php echo htmlspecialchars($item['nama_kelas']); ?></td>
                                <td><?php echo formatTanggal($item['tanggal'], 'd/m/Y'); ?></td>
                                <td>
                                    <span class="status-badge <?php echo $item['status']; ?>">
                                        <?php echo $item['status'] == 'valid' ? 'Tervalidasi' : 'Ditolak'; ?>
                                    </span>
                                </td>
                                <td><?php echo $item['validated_at'] ? formatDateTime($item['validated_at']) : '-'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-history"></i>
                        <p>Belum ada riwayat validasi</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="quick-grid">
            <a href="scan_qr.php" class="quick-card">
                <i class="fas fa-camera"></i>
                <span>Scan QR Code</span>
            </a>
            <a href="validasi.php" class="quick-card">
                <i class="fas fa-check-double"></i>
                <span>Validasi Kehadiran</span>
            </a>
            <a href="riwayat_validasi.php" class="quick-card">
                <i class="fas fa-history"></i>
                <span>Riwayat Validasi</span>
            </a>
            <a href="profile.php" class="quick-card">
                <i class="fas fa-user-cog"></i>
                <span>Profil Saya</span>
            </a>
        </div>
        
    </div>
</div>

<style>
    .welcome-card {
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        padding: 24px 28px;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        gap: 20px;
        flex-wrap: wrap;
    }
    
    .welcome-icon i {
        font-size: 48px;
        color: #D4A000;
    }
    
    .welcome-text {
        flex: 1;
    }
    
    .welcome-text h2 {
        font-size: 18px;
        font-weight: 600;
        color: white;
        margin: 0 0 6px;
    }
    
    .welcome-text p {
        font-size: 13px;
        color: #94a3b8;
        margin: 0;
    }
    
    .btn-scan {
        background: #D4A000;
        color: white;
        padding: 10px 24px;
        text-decoration: none;
        font-size: 13px;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .btn-scan:hover {
        background: #b8860b;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        margin-bottom: 24px;
    }
    
    .stat-card {
        background: white;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 16px;
    }
    
    .stat-icon {
        width: 52px;
        height: 52px;
        background: rgba(212, 160, 0, 0.1);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .stat-icon i {
        font-size: 24px;
        color: #D4A000;
    }
    
    .stat-content {
        flex: 1;
    }
    
    .stat-value {
        font-size: 28px;
        font-weight: 700;
        color: #1e293b;
    }
    
    .stat-label {
        font-size: 13px;
        color: #64748b;
    }
    
    .stat-note {
        font-size: 11px;
        color: #94a3b8;
        margin-top: 4px;
    }
    
    .two-columns {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 24px;
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
        flex: 1;
    }
    
    .link-view {
        font-size: 12px;
        color: #D4A000;
        text-decoration: none;
    }
    
    .data-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .data-table th {
        text-align: left;
        padding: 12px 16px;
        font-size: 12px;
        font-weight: 600;
        color: #475569;
        background: #f8fafc;
        border-bottom: 1px solid #eef2f6;
    }
    
    .data-table td {
        padding: 12px 16px;
        font-size: 13px;
        color: #334155;
        border-bottom: 1px solid #f1f5f9;
    }
    
    .kelas-cell strong {
        font-weight: 600;
        color: #1e293b;
    }
    
    .action-cell {
        white-space: nowrap;
    }
    
    .btn-validate {
        background: #D4A000;
        color: white;
        padding: 6px 12px;
        text-decoration: none;
        font-size: 11px;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    
    .btn-validate:hover {
        background: #b8860b;
    }
    
    .status-badge {
        display: inline-block;
        padding: 4px 12px;
        font-size: 11px;
        font-weight: 500;
    }
    
    .status-badge.valid {
        background: #dcfce7;
        color: #166534;
    }
    
    .status-badge.ditolak {
        background: #fee2e2;
        color: #991b1b;
    }
    
    .status-badge.pending {
        background: #fef9c3;
        color: #854d0e;
    }
    
    .empty-state {
        text-align: center;
        padding: 40px 20px;
    }
    
    .empty-state i {
        font-size: 40px;
        color: #cbd5e1;
        display: block;
        margin-bottom: 12px;
    }
    
    .empty-state p {
        color: #64748b;
        font-size: 13px;
    }
    
    .quick-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
    }
    
    .quick-card {
        background: white;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        padding: 20px;
        text-align: center;
        text-decoration: none;
    }
    
    .quick-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
    }
    
    .quick-card i {
        font-size: 28px;
        color: #D4A000;
        margin-bottom: 10px;
        display: block;
    }
    
    .quick-card span {
        font-size: 13px;
        font-weight: 500;
        color: #475569;
    }
    
    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .two-columns {
            grid-template-columns: 1fr;
        }
        
        .quick-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .welcome-card {
            flex-direction: column;
            text-align: center;
        }
        
        .btn-scan {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<?php include '../includes/footer.php'; ?>