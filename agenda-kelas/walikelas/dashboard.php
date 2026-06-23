<?php
/**
 * Dashboard Wali Kelas
 * File: walikelas/dashboard.php
 * HANYA menampilkan data kehadiran yang sudah divalidasi (status = 'valid')
 */

require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

checkRoleAccess(['walikelas']);

$page_title = 'Dashboard';
$page_subtitle = 'Monitoring kehadiran siswa (Data Tervalidasi)';

$user_id = $_SESSION['id'];

// Ambil kelas yang diampu oleh wali kelas
$stmt = $pdo->prepare("
    SELECT * FROM kelas WHERE walikelas_id = ?
");
$stmt->execute([$user_id]);
$kelas = $stmt->fetch();

if (!$kelas) {
    $kelas = ['id' => 0, 'nama_kelas' => 'Belum ada kelas'];
}

// Ambil semua siswa di kelas
$stmt = $pdo->prepare("
    SELECT s.*, u.nama as user_nama 
    FROM siswa s
    JOIN users u ON s.user_id = u.id
    WHERE s.kelas_id = ?
    ORDER BY s.nomor_absen ASC
");
$stmt->execute([$kelas['id']]);
$siswaList = $stmt->fetchAll();
$totalSiswa = count($siswaList);

// Ambil absensi hari ini (HANYA YANG SUDAH DIVALIDASI)
$tanggalHariIni = date('Y-m-d');
$stmt = $pdo->prepare("
    SELECT a.*, 
        COUNT(DISTINCT d.id) as total_siswa,
        SUM(CASE WHEN d.status = 'hadir' THEN 1 ELSE 0 END) as total_hadir,
        SUM(CASE WHEN d.status = 'sakit' THEN 1 ELSE 0 END) as total_sakit,
        SUM(CASE WHEN d.status = 'izin' THEN 1 ELSE 0 END) as total_izin,
        SUM(CASE WHEN d.status = 'dispen' THEN 1 ELSE 0 END) as total_dispen,
        SUM(CASE WHEN d.status = 'alfa' THEN 1 ELSE 0 END) as total_alfa
    FROM absensi_harian a
    JOIN detail_absensi d ON a.id = d.absensi_id
    JOIN validasi_qr v ON a.id = v.absensi_id
    WHERE a.kelas_id = ? AND a.tanggal = ? AND v.status = 'valid'
    GROUP BY a.id
    ORDER BY a.created_at DESC
    LIMIT 1
");
$stmt->execute([$kelas['id'], $tanggalHariIni]);
$absensiHariIni = $stmt->fetch();

// Hitung persentase kehadiran
$persenHadir = 0;
if ($absensiHariIni && $absensiHariIni['total_siswa'] > 0) {
    $persenHadir = round(($absensiHariIni['total_hadir'] / $absensiHariIni['total_siswa']) * 100);
}

// Ambil statistik kehadiran bulan ini (HANYA YANG SUDAH DIVALIDASI)
$bulan_ini = date('Y-m');
$stmt = $pdo->prepare("
    SELECT d.status, COUNT(*) as total
    FROM detail_absensi d
    JOIN absensi_harian a ON d.absensi_id = a.id
    JOIN validasi_qr v ON a.id = v.absensi_id
    WHERE a.kelas_id = ? AND a.tanggal LIKE ? AND v.status = 'valid'
    GROUP BY d.status
");
$stmt->execute([$kelas['id'], "$bulan_ini%"]);
$statistikBulan = [];
foreach ($stmt->fetchAll() as $row) {
    $statistikBulan[$row['status']] = $row['total'];
}

$totalBulan = array_sum($statistikBulan);
$persenHadirBulan = $totalBulan > 0 ? round(($statistikBulan['hadir'] ?? 0) / $totalBulan * 100) : 0;

// Ambil 5 siswa dengan kehadiran terendah (HANYA YANG SUDAH DIVALIDASI)
$stmt = $pdo->prepare("
    SELECT s.nama_lengkap, s.nis, s.nomor_absen,
        COUNT(d.id) as total_absen,
        SUM(CASE WHEN d.status = 'hadir' THEN 1 ELSE 0 END) as total_hadir
    FROM siswa s
    LEFT JOIN detail_absensi d ON s.id = d.siswa_id
    LEFT JOIN absensi_harian a ON d.absensi_id = a.id
    LEFT JOIN validasi_qr v ON a.id = v.absensi_id AND v.status = 'valid'
    WHERE s.kelas_id = ?
    GROUP BY s.id
    ORDER BY total_hadir ASC
    LIMIT 5
");
$stmt->execute([$kelas['id']]);
$siswaRendah = $stmt->fetchAll();

// Ambil agenda terbaru
$stmt = $pdo->prepare("
    SELECT * FROM agenda 
    WHERE tanggal >= CURDATE()
    ORDER BY tanggal ASC 
    LIMIT 5
");
$stmt->execute();
$agendaTerbaru = $stmt->fetchAll();

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
                <h2>Selamat Datang, Wali Kelas <?php echo htmlspecialchars($kelas['nama_kelas']); ?></h2>
                <p>Menampilkan data kehadiran yang sudah tervalidasi oleh guru</p>
            </div>
        </div>
        
        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $totalSiswa; ?></div>
                    <div class="stat-label">Total Siswa</div>
                    <div class="stat-note">Kelas <?php echo htmlspecialchars($kelas['nama_kelas']); ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $absensiHariIni ? $absensiHariIni['total_hadir'] : 0; ?> / <?php echo $totalSiswa; ?></div>
                    <div class="stat-label">Kehadiran Hari Ini</div>
                    <div class="stat-note"><?php echo $persenHadir; ?>% hadir</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $persenHadirBulan; ?>%</div>
                    <div class="stat-label">Kehadiran Bulan Ini</div>
                    <div class="stat-note"><?php echo date('F Y'); ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $totalBulan - ($statistikBulan['hadir'] ?? 0); ?></div>
                    <div class="stat-label">Tidak Hadir</div>
                    <div class="stat-note">Bulan ini</div>
                </div>
            </div>
        </div>
        
        <!-- Kehadiran Hari Ini -->
        <div class="section-card">
            <div class="section-header">
                <i class="fas fa-chart-simple"></i>
                <h3>Kehadiran Hari Ini (Tervalidasi)</h3>
                <span class="date-badge"><?php echo formatTanggal($tanggalHariIni, 'd F Y'); ?></span>
            </div>
            <div class="section-body">
                <?php if ($absensiHariIni): ?>
                <div class="status-list">
                    <div class="status-item">
                        <div class="status-info">
                            <span class="status-name">Hadir</span>
                            <span class="status-count"><?php echo $absensiHariIni['total_hadir']; ?> siswa</span>
                        </div>
                        <div class="status-bar">
                            <div class="bar-fill bar-hadir" style="width: <?php echo $persenHadir; ?>%"></div>
                        </div>
                    </div>
                    <div class="status-item">
                        <div class="status-info">
                            <span class="status-name">Sakit</span>
                            <span class="status-count"><?php echo $absensiHariIni['total_sakit']; ?> siswa</span>
                        </div>
                        <div class="status-bar">
                            <div class="bar-fill bar-gray" style="width: <?php echo $absensiHariIni['total_siswa'] > 0 ? round(($absensiHariIni['total_sakit'] / $absensiHariIni['total_siswa']) * 100) : 0; ?>%"></div>
                        </div>
                    </div>
                    <div class="status-item">
                        <div class="status-info">
                            <span class="status-name">Izin</span>
                            <span class="status-count"><?php echo $absensiHariIni['total_izin']; ?> siswa</span>
                        </div>
                        <div class="status-bar">
                            <div class="bar-fill bar-gray" style="width: <?php echo $absensiHariIni['total_siswa'] > 0 ? round(($absensiHariIni['total_izin'] / $absensiHariIni['total_siswa']) * 100) : 0; ?>%"></div>
                        </div>
                    </div>
                    <div class="status-item">
                        <div class="status-info">
                            <span class="status-name">Dispen</span>
                            <span class="status-count"><?php echo $absensiHariIni['total_dispen']; ?> siswa</span>
                        </div>
                        <div class="status-bar">
                            <div class="bar-fill bar-gray" style="width: <?php echo $absensiHariIni['total_siswa'] > 0 ? round(($absensiHariIni['total_dispen'] / $absensiHariIni['total_siswa']) * 100) : 0; ?>%"></div>
                        </div>
                    </div>
                    <div class="status-item">
                        <div class="status-info">
                            <span class="status-name">Alfa</span>
                            <span class="status-count"><?php echo $absensiHariIni['total_alfa']; ?> siswa</span>
                        </div>
                        <div class="status-bar">
                            <div class="bar-fill bar-gray" style="width: <?php echo $absensiHariIni['total_siswa'] > 0 ? round(($absensiHariIni['total_alfa'] / $absensiHariIni['total_siswa']) * 100) : 0; ?>%"></div>
                        </div>
                    </div>
                </div>
                
                <div class="summary-row">
                    <div class="summary-item">
                        <span class="summary-label">Total Siswa</span>
                        <span class="summary-value"><?php echo $absensiHariIni['total_siswa']; ?></span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Total Hadir</span>
                        <span class="summary-value"><?php echo $absensiHariIni['total_hadir']; ?></span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Persentase</span>
                        <span class="summary-value"><?php echo $persenHadir; ?>%</span>
                    </div>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-day"></i>
                    <p>Belum ada absensi tervalidasi untuk hari ini</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Dua Kolom -->
        <div class="two-columns">
            <!-- Siswa dengan Kehadiran Rendah -->
            <div class="table-card">
                <div class="table-header">
                    <i class="fas fa-chart-line"></i>
                    <h3>Siswa dengan Kehadiran Rendah</h3>
                    <a href="monitoring.php" class="link-view">Detail</a>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Siswa</th>
                                <th>NIS</th>
                                <th>Kehadiran</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($siswaRendah) > 0): ?>
                                <?php $no = 1; foreach ($siswaRendah as $siswa): 
                                    $persen = $siswa['total_absen'] > 0 ? round(($siswa['total_hadir'] / $siswa['total_absen']) * 100) : 0;
                                ?>
                                <tr>
                                    <td><?php echo $no++; ?></td>
                                    <td class="name-cell">
                                        <strong><?php echo htmlspecialchars($siswa['nama_lengkap']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($siswa['nis']); ?></td>
                                    <td>
                                        <div class="progress-mini">
                                            <span class="progress-value"><?php echo $persen; ?>%</span>
                                            <div class="progress-bar-mini">
                                                <div class="progress-fill" style="width: <?php echo $persen; ?>%; background: <?php echo $persen < 50 ? '#ef4444' : ($persen < 75 ? '#eab308' : '#22c55e'); ?>"></div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="empty-row">
                                        <p>Belum ada data kehadiran tervalidasi</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Agenda Terbaru -->
            <div class="table-card">
                <div class="table-header">
                    <i class="fas fa-clipboard-list"></i>
                    <h3>Agenda Terbaru</h3>
                    <a href="../sekre/agenda.php" class="link-view">Lihat semua</a>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Judul</th>
                                <th width="120">Tanggal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($agendaTerbaru) > 0): ?>
                                <?php foreach ($agendaTerbaru as $agenda): ?>
                                <tr>
                                    <td class="title-cell">
                                        <strong><?php echo htmlspecialchars($agenda['judul']); ?></strong>
                                    </td>
                                    <td>
                                        <span class="date-badge">
                                            <i class="fas fa-calendar-alt"></i>
                                            <?php echo formatTanggal($agenda['tanggal'], 'd/m/Y'); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="2" class="empty-row">
                                        <p>Belum ada agenda</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="quick-grid">
            <a href="monitoring.php" class="quick-card">
                <i class="fas fa-eye"></i>
                <span>Monitoring Kehadiran</span>
            </a>
            <a href="riwayat_kehadiran.php" class="quick-card">
                <i class="fas fa-history"></i>
                <span>Riwayat Kehadiran</span>
            </a>
            <a href="statistik.php" class="quick-card">
                <i class="fas fa-chart-bar"></i>
                <span>Statistik</span>
            </a>
            <a href="export.php" class="quick-card">
                <i class="fas fa-download"></i>
                <span>Export Laporan</span>
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
    .welcome-icon i { font-size: 48px; color: #D4A000; }
    .welcome-text h2 { font-size: 18px; font-weight: 600; color: white; margin: 0 0 6px; }
    .welcome-text p { font-size: 13px; color: #94a3b8; margin: 0; }
    
    .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 24px; }
    .stat-card { background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.04); padding: 20px; display: flex; align-items: center; gap: 16px; }
    .stat-icon { width: 52px; height: 52px; background: rgba(212,160,0,0.1); display: flex; align-items: center; justify-content: center; }
    .stat-icon i { font-size: 24px; color: #D4A000; }
    .stat-content { flex: 1; }
    .stat-value { font-size: 28px; font-weight: 700; color: #1e293b; }
    .stat-label { font-size: 13px; color: #64748b; }
    .stat-note { font-size: 11px; color: #94a3b8; margin-top: 4px; }
    
    .section-card { background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.04); margin-bottom: 24px; }
    .section-header { display: flex; align-items: center; gap: 12px; padding: 16px 20px; border-bottom: 1px solid #eef2f6; }
    .section-header i { font-size: 20px; color: #D4A000; }
    .section-header h3 { font-size: 14px; font-weight: 600; color: #1e293b; margin: 0; flex: 1; }
    .date-badge { font-size: 12px; color: #64748b; background: #f8fafc; padding: 4px 12px; }
    .section-body { padding: 20px; }
    
    .status-list { margin-bottom: 20px; }
    .status-item { margin-bottom: 16px; }
    .status-info { display: flex; justify-content: space-between; margin-bottom: 6px; font-size: 13px; }
    .status-name { color: #475569; }
    .status-count { color: #64748b; }
    .status-bar { height: 8px; background: #f1f5f9; overflow: hidden; }
    .bar-fill { height: 100%; }
    .bar-hadir { background: #22c55e; }
    .bar-gray { background: #cbd5e1; }
    
    .summary-row { display: flex; gap: 24px; padding-top: 16px; border-top: 1px solid #eef2f6; }
    .summary-item { flex: 1; text-align: center; }
    .summary-label { display: block; font-size: 11px; color: #64748b; margin-bottom: 4px; }
    .summary-value { font-size: 18px; font-weight: 700; color: #1e293b; }
    
    .two-columns { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px; }
    .table-card { background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
    .table-header { display: flex; align-items: center; gap: 12px; padding: 16px 20px; border-bottom: 1px solid #eef2f6; }
    .table-header i { font-size: 18px; color: #D4A000; }
    .table-header h3 { font-size: 14px; font-weight: 600; color: #1e293b; margin: 0; flex: 1; }
    .link-view { font-size: 12px; color: #D4A000; text-decoration: none; }
    
    .data-table { width: 100%; border-collapse: collapse; }
    .data-table th { text-align: left; padding: 12px 16px; font-size: 12px; font-weight: 600; color: #475569; background: #f8fafc; border-bottom: 1px solid #eef2f6; }
    .data-table td { padding: 12px 16px; font-size: 13px; color: #334155; border-bottom: 1px solid #f1f5f9; }
    .name-cell strong { font-weight: 600; color: #1e293b; }
    
    .progress-mini { display: flex; align-items: center; gap: 10px; }
    .progress-value { font-size: 12px; font-weight: 600; min-width: 35px; }
    .progress-bar-mini { flex: 1; height: 6px; background: #e2e8f0; overflow: hidden; }
    .progress-fill { height: 100%; }
    
    .date-badge { display: inline-flex; align-items: center; gap: 6px; font-size: 11px; color: #64748b; }
    .empty-state { text-align: center; padding: 40px; }
    .empty-state i { font-size: 40px; color: #cbd5e1; display: block; margin-bottom: 12px; }
    .empty-row { text-align: center; padding: 30px !important; }
    
    .quick-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
    .quick-card { background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.04); padding: 20px; text-align: center; text-decoration: none; transition: all 0.2s; }
    .quick-card:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.08); }
    .quick-card i { font-size: 28px; color: #D4A000; margin-bottom: 10px; display: block; }
    .quick-card span { font-size: 13px; font-weight: 500; color: #475569; }
    
    @media (max-width: 768px) {
        .stats-grid { grid-template-columns: repeat(2, 1fr); }
        .two-columns { grid-template-columns: 1fr; }
        .quick-grid { grid-template-columns: repeat(2, 1fr); }
        .summary-row { flex-direction: column; gap: 12px; }
    }
</style>

<?php include '../includes/footer.php'; ?>