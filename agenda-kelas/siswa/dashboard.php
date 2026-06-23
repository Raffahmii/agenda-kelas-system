<?php
/**
 * Dashboard Siswa
 * File: siswa/dashboard.php
 * HANYA menampilkan data kehadiran yang sudah divalidasi (status = 'valid')
 */

require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

checkRoleAccess(['siswa']);

$page_title = 'Dashboard';
$page_subtitle = 'Ringkasan aktivitas dan kehadiran Anda';

$user_id = $_SESSION['id'];

// Ambil data siswa
$stmt = $pdo->prepare("
    SELECT s.*, k.nama_kelas 
    FROM siswa s
    JOIN kelas k ON s.kelas_id = k.id
    WHERE s.user_id = ?
");
$stmt->execute([$user_id]);
$siswa = $stmt->fetch();

if (!$siswa) {
    $siswa = [
        'nama_lengkap' => $_SESSION['nama'],
        'nis' => '-',
        'nomor_absen' => '-',
        'kelas_id' => 0,
        'nama_kelas' => '-'
    ];
}

// Ambil agenda terbaru (tidak perlu validasi)
$stmt = $pdo->prepare("
    SELECT * FROM agenda 
    WHERE tanggal >= CURDATE()
    ORDER BY tanggal ASC 
    LIMIT 5
");
$stmt->execute();
$agendaTerbaru = $stmt->fetchAll();

// Ambil riwayat kehadiran bulan ini (HANYA YANG SUDAH DIVALIDASI)
$bulan_ini = date('Y-m');
$stmt = $pdo->prepare("
    SELECT d.*, a.tanggal 
    FROM detail_absensi d
    JOIN absensi_harian a ON d.absensi_id = a.id
    JOIN validasi_qr v ON a.id = v.absensi_id
    WHERE d.siswa_id = ? AND a.tanggal LIKE ? AND v.status = 'valid'
    ORDER BY a.tanggal DESC
");
$stmt->execute([$siswa['id'], "$bulan_ini%"]);
$kehadiranBulanIni = $stmt->fetchAll();

// Hitung statistik kehadiran bulan ini
$stats = [
    'hadir' => 0, 'sakit' => 0, 'izin' => 0, 'dispen' => 0, 'alfa' => 0
];
foreach ($kehadiranBulanIni as $k) {
    $stats[$k['status']]++;
}
$totalKehadiran = array_sum($stats);
$persenHadir = $totalKehadiran > 0 ? round(($stats['hadir'] / $totalKehadiran) * 100) : 0;

// Ambil agenda hari ini
$stmt = $pdo->prepare("
    SELECT * FROM agenda 
    WHERE tanggal = CURDATE()
    ORDER BY created_at DESC
");
$stmt->execute();
$agendaHariIni = $stmt->fetchAll();

include '../includes/navbar.php';
include '../includes/sidebar.php';
?>

<div class="main-content">
    <div class="content-area">
        
        <!-- Welcome Banner -->
        <div class="welcome-card">
            <div class="welcome-icon">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <div class="welcome-text">
                <h2>Selamat Datang, <?php echo htmlspecialchars($siswa['nama_lengkap']); ?>!</h2>
                <p>Kelas <?php echo htmlspecialchars($siswa['nama_kelas']); ?> · NIS: <?php echo htmlspecialchars($siswa['nis']); ?> · No. Absen: <?php echo $siswa['nomor_absen']; ?></p>
            </div>
        </div>
        
        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $stats['hadir']; ?></div>
                    <div class="stat-label">Hadir</div>
                    <div class="stat-note">Bulan ini</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $persenHadir; ?>%</div>
                    <div class="stat-label">Tingkat Kehadiran</div>
                    <div class="stat-note">Bulan ini</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-calendar-week"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo count($agendaHariIni); ?></div>
                    <div class="stat-label">Agenda Hari Ini</div>
                    <div class="stat-note"><?php echo date('d F Y'); ?></div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo $totalKehadiran; ?></div>
                    <div class="stat-label">Total Kehadiran</div>
                    <div class="stat-note">Bulan ini</div>
                </div>
            </div>
        </div>
        
        <!-- Agenda Hari Ini -->
        <?php if (count($agendaHariIni) > 0): ?>
        <div class="section-card">
            <div class="section-header">
                <i class="fas fa-calendar-day"></i>
                <h3>Agenda Hari Ini</h3>
                <span class="date-badge"><?php echo date('d F Y'); ?></span>
            </div>
            <div class="section-body">
                <?php foreach ($agendaHariIni as $agenda): ?>
                <div class="agenda-item">
                    <div class="agenda-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="agenda-content">
                        <div class="agenda-title"><?php echo htmlspecialchars($agenda['judul']); ?></div>
                        <div class="agenda-desc"><?php echo htmlspecialchars(substr($agenda['deskripsi'] ?? '-', 0, 100)); ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Ringkasan Kehadiran Bulan Ini (HANYA YANG DIVALIDASI) -->
        <div class="section-card">
            <div class="section-header">
                <i class="fas fa-chart-simple"></i>
                <h3>Ringkasan Kehadiran Bulan Ini</h3>
                <span class="date-badge"><?php echo date('F Y'); ?></span>
            </div>
            <div class="section-body">
                <div class="status-list">
                    <div class="status-item <?php echo ($stats['hadir'] == max($stats)) ? 'is-highlight' : ''; ?>">
                        <div class="status-info">
                            <span class="status-name">Hadir</span>
                            <span class="status-count"><?php echo $stats['hadir']; ?> hari</span>
                        </div>
                        <div class="status-bar">
                            <div class="bar-fill <?php echo ($stats['hadir'] == max($stats)) ? 'bar-highlight' : 'bar-gray'; ?>" style="width: <?php echo $totalKehadiran > 0 ? ($stats['hadir'] / $totalKehadiran) * 100 : 0; ?>%"></div>
                        </div>
                    </div>
                    <div class="status-item">
                        <div class="status-info">
                            <span class="status-name">Sakit</span>
                            <span class="status-count"><?php echo $stats['sakit']; ?> hari</span>
                        </div>
                        <div class="status-bar">
                            <div class="bar-fill bar-gray" style="width: <?php echo $totalKehadiran > 0 ? ($stats['sakit'] / $totalKehadiran) * 100 : 0; ?>%"></div>
                        </div>
                    </div>
                    <div class="status-item">
                        <div class="status-info">
                            <span class="status-name">Izin</span>
                            <span class="status-count"><?php echo $stats['izin']; ?> hari</span>
                        </div>
                        <div class="status-bar">
                            <div class="bar-fill bar-gray" style="width: <?php echo $totalKehadiran > 0 ? ($stats['izin'] / $totalKehadiran) * 100 : 0; ?>%"></div>
                        </div>
                    </div>
                    <div class="status-item">
                        <div class="status-info">
                            <span class="status-name">Dispen</span>
                            <span class="status-count"><?php echo $stats['dispen']; ?> hari</span>
                        </div>
                        <div class="status-bar">
                            <div class="bar-fill bar-gray" style="width: <?php echo $totalKehadiran > 0 ? ($stats['dispen'] / $totalKehadiran) * 100 : 0; ?>%"></div>
                        </div>
                    </div>
                    <div class="status-item">
                        <div class="status-info">
                            <span class="status-name">Alfa</span>
                            <span class="status-count"><?php echo $stats['alfa']; ?> hari</span>
                        </div>
                        <div class="status-bar">
                            <div class="bar-fill bar-gray" style="width: <?php echo $totalKehadiran > 0 ? ($stats['alfa'] / $totalKehadiran) * 100 : 0; ?>%"></div>
                        </div>
                    </div>
                </div>
                
                <div class="summary-row">
                    <div class="summary-item">
                        <span class="summary-label">Total Hari Efektif</span>
                        <span class="summary-value"><?php echo $totalKehadiran; ?></span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Total Hadir</span>
                        <span class="summary-value"><?php echo $stats['hadir']; ?></span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Tidak Hadir</span>
                        <span class="summary-value"><?php echo $stats['sakit'] + $stats['izin'] + $stats['dispen'] + $stats['alfa']; ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        
        
        <!-- Agenda Terbaru -->
        <div class="table-card">
            <div class="table-header">
                <i class="fas fa-clipboard-list"></i>
                <h3>Agenda Terbaru</h3>
                <a href="agenda.php" class="link-view">Lihat semua</a>
            </div>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Judul Agenda</th>
                            <th>Deskripsi</th>
                            <th width="130">Tanggal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($agendaTerbaru) > 0): ?>
                            <?php foreach ($agendaTerbaru as $agenda): ?>
                            <tr>
                                <td class="title-cell">
                                    <strong><?php echo htmlspecialchars($agenda['judul']); ?></strong>
                                </td>
                                <td class="desc-cell">
                                    <?php echo htmlspecialchars(substr($agenda['deskripsi'] ?? '-', 0, 60)); ?>
                                </td>
                                <td>
                                    <span class="date-badge">
                                        <i class="fas fa-calendar-alt"></i>
                                        <?php echo formatTanggal($agenda['tanggal'], 'd F Y'); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="empty-row">
                                    <i class="fas fa-calendar-times"></i>
                                    <p>Belum ada agenda</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Quick Links -->
        <div class="quick-grid">
            <a href="agenda.php" class="quick-card">
                <i class="fas fa-calendar-week"></i>
                <span>Lihat Agenda</span>
            </a>
            <a href="riwayat_kehadiran.php" class="quick-card">
                <i class="fas fa-history"></i>
                <span>Riwayat Kehadiran</span>
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
    
    .agenda-item { display: flex; gap: 14px; padding: 12px 0; border-bottom: 1px solid #f1f5f9; }
    .agenda-item:last-child { border-bottom: none; }
    .agenda-icon i { font-size: 20px; color: #D4A000; }
    .agenda-content { flex: 1; }
    .agenda-title { font-size: 14px; font-weight: 600; color: #1e293b; margin-bottom: 4px; }
    .agenda-desc { font-size: 12px; color: #64748b; }
    
    .status-list { margin-bottom: 20px; }
    .status-item { margin-bottom: 16px; }
    .status-info { display: flex; justify-content: space-between; margin-bottom: 6px; font-size: 13px; }
    .status-name { color: #475569; }
    .status-count { color: #64748b; }
    .status-bar { height: 8px; background: #f1f5f9; overflow: hidden; }
    .bar-fill { height: 100%; }
    .bar-highlight { background: #22c55e; }
    .bar-gray { background: #cbd5e1; }
    .status-item.is-highlight .status-name { color: #22c55e; font-weight: 600; }
    
    .summary-row { display: flex; gap: 24px; padding-top: 16px; border-top: 1px solid #eef2f6; }
    .summary-item { flex: 1; text-align: center; }
    .summary-label { display: block; font-size: 11px; color: #64748b; margin-bottom: 4px; }
    .summary-value { font-size: 18px; font-weight: 700; color: #1e293b; }
    
    .info-note { background: #f8fafc; padding: 12px 16px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; font-size: 12px; color: #64748b; }
    .info-note i { color: #D4A000; }
    
    .table-card { background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.04); margin-bottom: 24px; }
    .table-header { display: flex; align-items: center; gap: 12px; padding: 16px 20px; border-bottom: 1px solid #eef2f6; }
    .table-header i { font-size: 18px; color: #D4A000; }
    .table-header h3 { font-size: 14px; font-weight: 600; color: #1e293b; margin: 0; flex: 1; }
    .link-view { font-size: 12px; color: #D4A000; text-decoration: none; }
    
    .data-table { width: 100%; border-collapse: collapse; }
    .data-table th { text-align: left; padding: 12px 16px; font-size: 12px; font-weight: 600; color: #475569; background: #f8fafc; border-bottom: 1px solid #eef2f6; }
    .data-table td { padding: 12px 16px; font-size: 13px; color: #334155; border-bottom: 1px solid #f1f5f9; }
    .title-cell strong { font-weight: 600; color: #1e293b; }
    .desc-cell { color: #64748b; }
    .date-badge { display: inline-flex; align-items: center; gap: 6px; font-size: 11px; color: #64748b; }
    .empty-row { text-align: center; padding: 40px !important; }
    .empty-row i { font-size: 40px; color: #cbd5e1; display: block; margin-bottom: 12px; }
    
    .quick-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
    .quick-card { background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.04); padding: 20px; text-align: center; text-decoration: none; transition: all 0.2s; }
    .quick-card:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.08); }
    .quick-card i { font-size: 28px; color: #D4A000; margin-bottom: 10px; display: block; }
    .quick-card span { font-size: 13px; font-weight: 500; color: #475569; }
    
    @media (max-width: 768px) {
        .stats-grid { grid-template-columns: repeat(2, 1fr); }
        .quick-grid { grid-template-columns: 1fr; }
        .summary-row { flex-direction: column; gap: 12px; }
        .welcome-card { text-align: center; justify-content: center; }
    }
</style>

<?php include '../includes/footer.php'; ?>