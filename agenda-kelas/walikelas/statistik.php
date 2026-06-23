<?php
/**
 * Statistik Kehadiran - Wali Kelas
 * File: walikelas/statistik.php
 * HANYA menampilkan data kehadiran yang sudah divalidasi (status = 'valid')
 */

require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

checkRoleAccess(['walikelas']);

$page_title = 'Statistik Kehadiran';
$page_subtitle = 'Grafik dan analisis kehadiran';

$user_id = $_SESSION['id'];

// Ambil kelas yang diampu
$stmt = $pdo->prepare("
    SELECT * FROM kelas WHERE walikelas_id = ?
");
$stmt->execute([$user_id]);
$kelas = $stmt->fetch();

if (!$kelas) {
    $kelas = ['id' => 0, 'nama_kelas' => 'Belum ada kelas'];
}

// Filter
$bulan_filter = isset($_GET['bulan']) && $_GET['bulan'] != '' ? $_GET['bulan'] : null;
$tahun = isset($_GET['tahun']) && $_GET['tahun'] != '' ? $_GET['tahun'] : date('Y');

// Jika bulan dipilih, tampilkan data bulan tersebut, jika tidak tampilkan statistik tahunan
$isBulanSelected = !is_null($bulan_filter);

// Statistik berdasarkan filter (HANYA YANG SUDAH DIVALIDASI)
if ($isBulanSelected) {
    // Statistik untuk bulan tertentu
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(CASE WHEN d.status = 'hadir' THEN 1 END) as hadir,
            COUNT(CASE WHEN d.status = 'sakit' THEN 1 END) as sakit,
            COUNT(CASE WHEN d.status = 'izin' THEN 1 END) as izin,
            COUNT(CASE WHEN d.status = 'dispen' THEN 1 END) as dispen,
            COUNT(CASE WHEN d.status = 'alfa' THEN 1 END) as alfa,
            COUNT(*) as total
        FROM detail_absensi d
        JOIN absensi_harian a ON d.absensi_id = a.id
        JOIN validasi_qr v ON a.id = v.absensi_id
        JOIN siswa s ON d.siswa_id = s.id
        WHERE s.kelas_id = ? AND MONTH(a.tanggal) = ? AND YEAR(a.tanggal) = ? AND v.status = 'valid'
    ");
    $stmt->execute([$kelas['id'], $bulan_filter, $tahun]);
    $statistik = $stmt->fetch();
    
    $total = $statistik['total'];
    $persenHadir = $total > 0 ? round(($statistik['hadir'] / $total) * 100) : 0;
    
    // Statistik per siswa untuk bulan tersebut (HANYA YANG DIVALIDASI)
    $stmt = $pdo->prepare("
        SELECT 
            s.id, s.nama_lengkap, s.nis, s.nomor_absen,
            COUNT(d.id) as total_absen,
            SUM(CASE WHEN d.status = 'hadir' THEN 1 ELSE 0 END) as total_hadir
        FROM siswa s
        LEFT JOIN detail_absensi d ON s.id = d.siswa_id
        LEFT JOIN absensi_harian a ON d.absensi_id = a.id
        LEFT JOIN validasi_qr v ON a.id = v.absensi_id AND v.status = 'valid'
        WHERE s.kelas_id = ? AND (MONTH(a.tanggal) = ? OR a.tanggal IS NULL) AND (YEAR(a.tanggal) = ? OR a.tanggal IS NULL)
        GROUP BY s.id
        ORDER BY total_hadir DESC
    ");
    $stmt->execute([$kelas['id'], $bulan_filter, $tahun]);
    $siswaStats = $stmt->fetchAll();
    
} else {
    // Statistik tahunan (HANYA YANG DIVALIDASI)
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(CASE WHEN d.status = 'hadir' THEN 1 END) as hadir,
            COUNT(CASE WHEN d.status = 'sakit' THEN 1 END) as sakit,
            COUNT(CASE WHEN d.status = 'izin' THEN 1 END) as izin,
            COUNT(CASE WHEN d.status = 'dispen' THEN 1 END) as dispen,
            COUNT(CASE WHEN d.status = 'alfa' THEN 1 END) as alfa,
            COUNT(*) as total
        FROM detail_absensi d
        JOIN absensi_harian a ON d.absensi_id = a.id
        JOIN validasi_qr v ON a.id = v.absensi_id
        JOIN siswa s ON d.siswa_id = s.id
        WHERE s.kelas_id = ? AND YEAR(a.tanggal) = ? AND v.status = 'valid'
    ");
    $stmt->execute([$kelas['id'], $tahun]);
    $statistik = $stmt->fetch();
    
    $total = $statistik['total'];
    $persenHadir = $total > 0 ? round(($statistik['hadir'] / $total) * 100) : 0;
    
    // Statistik per siswa untuk tahun tersebut (HANYA YANG DIVALIDASI)
    $stmt = $pdo->prepare("
        SELECT 
            s.id, s.nama_lengkap, s.nis, s.nomor_absen,
            COUNT(d.id) as total_absen,
            SUM(CASE WHEN d.status = 'hadir' THEN 1 ELSE 0 END) as total_hadir
        FROM siswa s
        LEFT JOIN detail_absensi d ON s.id = d.siswa_id
        LEFT JOIN absensi_harian a ON d.absensi_id = a.id
        LEFT JOIN validasi_qr v ON a.id = v.absensi_id AND v.status = 'valid'
        WHERE s.kelas_id = ? AND (YEAR(a.tanggal) = ? OR a.tanggal IS NULL)
        GROUP BY s.id
        ORDER BY total_hadir DESC
    ");
    $stmt->execute([$kelas['id'], $tahun]);
    $siswaStats = $stmt->fetchAll();
}

// Hitung persentase per siswa
foreach ($siswaStats as &$siswa) {
    $siswa['persen'] = $siswa['total_absen'] > 0 ? round(($siswa['total_hadir'] / $siswa['total_absen']) * 100) : 0;
}

// Split jadi terbaik dan terendah
$siswaTerbaik = array_slice($siswaStats, 0, 5);
$siswaTerendah = array_reverse(array_slice($siswaStats, -5));

// Statistik per bulan untuk chart (hanya jika tidak filter bulan)
$statistikBulanan = [];
if (!$isBulanSelected) {
    for ($i = 1; $i <= 12; $i++) {
        $bulan = sprintf("%02d", $i);
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(CASE WHEN d.status = 'hadir' THEN 1 END) as hadir,
                COUNT(CASE WHEN d.status != 'hadir' THEN 1 END) as tidak_hadir
            FROM detail_absensi d
            JOIN absensi_harian a ON d.absensi_id = a.id
            JOIN validasi_qr v ON a.id = v.absensi_id
            JOIN siswa s ON d.siswa_id = s.id
            WHERE s.kelas_id = ? AND MONTH(a.tanggal) = ? AND YEAR(a.tanggal) = ? AND v.status = 'valid'
        ");
        $stmt->execute([$kelas['id'], $i, $tahun]);
        $data = $stmt->fetch();
        $statistikBulanan[$bulan] = [
            'hadir' => $data['hadir'] ?? 0,
            'tidak_hadir' => $data['tidak_hadir'] ?? 0
        ];
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
                <i class="fas fa-chart-bar"></i>
                <div>
                    <h2>Statistik Kehadiran</h2>
                    <p>Kelas <?php echo htmlspecialchars($kelas['nama_kelas']); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Filter -->
        <div class="filter-card">
            <form method="GET" action="" class="filter-form">
                <div class="filter-row">
                    <div class="filter-group">
                        <label>Pilih Bulan</label>
                        <select name="bulan" class="filter-select">
                            <option value="">-- Pilih Bulan (Tampilkan Tahun) --</option>
                            <option value="01" <?php echo $bulan_filter == '01' ? 'selected' : ''; ?>>Januari</option>
                            <option value="02" <?php echo $bulan_filter == '02' ? 'selected' : ''; ?>>Februari</option>
                            <option value="03" <?php echo $bulan_filter == '03' ? 'selected' : ''; ?>>Maret</option>
                            <option value="04" <?php echo $bulan_filter == '04' ? 'selected' : ''; ?>>April</option>
                            <option value="05" <?php echo $bulan_filter == '05' ? 'selected' : ''; ?>>Mei</option>
                            <option value="06" <?php echo $bulan_filter == '06' ? 'selected' : ''; ?>>Juni</option>
                            <option value="07" <?php echo $bulan_filter == '07' ? 'selected' : ''; ?>>Juli</option>
                            <option value="08" <?php echo $bulan_filter == '08' ? 'selected' : ''; ?>>Agustus</option>
                            <option value="09" <?php echo $bulan_filter == '09' ? 'selected' : ''; ?>>September</option>
                            <option value="10" <?php echo $bulan_filter == '10' ? 'selected' : ''; ?>>Oktober</option>
                            <option value="11" <?php echo $bulan_filter == '11' ? 'selected' : ''; ?>>November</option>
                            <option value="12" <?php echo $bulan_filter == '12' ? 'selected' : ''; ?>>Desember</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Tahun</label>
                        <select name="tahun" class="filter-select">
                            <?php for ($y = date('Y'); $y >= date('Y') - 2; $y--): ?>
                                <option value="<?php echo $y; ?>" <?php echo $tahun == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="filter-group filter-actions">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn-filter">
                            <i class="fas fa-search"></i> Tampilkan
                        </button>
                        <a href="statistik.php" class="btn-reset">
                            <i class="fas fa-undo-alt"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Info Periode -->
        <div class="info-periode">
            <i class="fas fa-info-circle"></i>
            <span>
                <?php 
                if ($isBulanSelected) {
                    echo "Menampilkan data statistik tervalidasi bulan " . getNamaBulan($bulan_filter) . " " . $tahun;
                } else {
                    echo "Menampilkan data statistik tervalidasi tahun " . $tahun;
                }
                ?>
            </span>
        </div>
        
        <!-- Ringkasan Statistik -->
        <div class="stats-summary-card">
            <div class="summary-header">
                <i class="fas fa-chart-pie"></i>
                <h3>Ringkasan Kehadiran</h3>
            </div>
            <div class="summary-body">
                <div class="summary-stats">
                    <div class="summary-stat">
                        <span class="stat-value"><?php echo $total; ?></span>
                        <span class="stat-label">Total Kehadiran</span>
                    </div>
                    <div class="summary-stat">
                        <span class="stat-value" style="color: #166534;"><?php echo $statistik['hadir']; ?></span>
                        <span class="stat-label">Hadir</span>
                    </div>
                    <div class="summary-stat">
                        <span class="stat-value" style="color: #854d0e;"><?php echo $statistik['sakit']; ?></span>
                        <span class="stat-label">Sakit</span>
                    </div>
                    <div class="summary-stat">
                        <span class="stat-value" style="color: #1e40af;"><?php echo $statistik['izin']; ?></span>
                        <span class="stat-label">Izin</span>
                    </div>
                    <div class="summary-stat">
                        <span class="stat-value" style="color: #5b21b6;"><?php echo $statistik['dispen']; ?></span>
                        <span class="stat-label">Dispen</span>
                    </div>
                    <div class="summary-stat">
                        <span class="stat-value" style="color: #991b1b;"><?php echo $statistik['alfa']; ?></span>
                        <span class="stat-label">Alfa</span>
                    </div>
                </div>
                
                <div class="progress-year">
                    <div class="progress-label">
                        <span>Tingkat Kehadiran</span>
                        <span class="progress-percent"><?php echo $persenHadir; ?>%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $persenHadir; ?>%; background: <?php echo $persenHadir >= 80 ? '#22c55e' : ($persenHadir >= 60 ? '#eab308' : '#ef4444'); ?>"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Grafik (hanya jika tidak filter bulan) -->
        <?php if (!$isBulanSelected && $kelas['id'] > 0): ?>
        <div class="chart-card">
            <div class="chart-header">
                <i class="fas fa-chart-line"></i>
                <h3>Grafik Kehadiran per Bulan - Tahun <?php echo $tahun; ?></h3>
            </div>
            <div class="chart-body">
                <div class="chart-container">
                    <canvas id="attendanceChart" style="width: 100%; height: 300px;"></canvas>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Statistik Per Siswa -->
        <div class="two-columns">
            <div class="table-card">
                <div class="table-header">
                    <i class="fas fa-trophy"></i>
                    <h3>Siswa dengan Kehadiran Terbaik</h3>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th width="40">No</th>
                                <th>Nama Siswa</th>
                                <th>NIS</th>
                                <th width="100">Kehadiran</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($siswaTerbaik) > 0): ?>
                                <?php $no = 1; foreach ($siswaTerbaik as $siswa): ?>
                                    <?php if ($siswa['total_absen'] > 0): ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td class="name-cell">
                                            <strong><?php echo htmlspecialchars($siswa['nama_lengkap']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($siswa['nis']); ?></td>
                                        <td>
                                            <div class="progress-mini">
                                                <span class="progress-value" style="color: #166534;"><?php echo $siswa['persen']; ?>%</span>
                                                <div class="progress-bar-mini">
                                                    <div class="progress-fill" style="width: <?php echo $siswa['persen']; ?>%; background: #22c55e;"></div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="empty-row">Belum ada data tervalidasi</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="table-card">
                <div class="table-header">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3>Siswa dengan Kehadiran Terendah</h3>
                </div>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th width="40">No</th>
                                <th>Nama Siswa</th>
                                <th>NIS</th>
                                <th width="100">Kehadiran</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($siswaTerendah) > 0): ?>
                                <?php $no = 1; foreach ($siswaTerendah as $siswa): ?>
                                    <?php if ($siswa['total_absen'] > 0): ?>
                                    <tr>
                                        <td><?php echo $no++; ?></td>
                                        <td class="name-cell">
                                            <strong><?php echo htmlspecialchars($siswa['nama_lengkap']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($siswa['nis']); ?></td>
                                        <td>
                                            <div class="progress-mini">
                                                <span class="progress-value" style="color: <?php echo $siswa['persen'] < 50 ? '#991b1b' : ($siswa['persen'] < 75 ? '#854d0e' : '#166534'); ?>"><?php echo $siswa['persen']; ?>%</span>
                                                <div class="progress-bar-mini">
                                                    <div class="progress-fill" style="width: <?php echo $siswa['persen']; ?>%; background: <?php echo $siswa['persen'] < 50 ? '#ef4444' : ($siswa['persen'] < 75 ? '#eab308' : '#22c55e'); ?>"></div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="empty-row">Belum ada data tervalidasi</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
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
    
    .filter-card {
        background: white;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        padding: 20px 24px;
        margin-bottom: 16px;
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
    
    .filter-select {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #e2e8f0;
        font-size: 13px;
        font-family: 'Poppins', sans-serif;
        background: white;
    }
    
    .filter-select:focus {
        outline: none;
        border-color: #D4A000;
    }
    
    .filter-actions {
        display: flex;
        gap: 10px;
        flex: 0 0 auto;
    }
    
    .btn-filter {
        background: #D4A000;
        color: white;
        padding: 10px 20px;
        border: none;
        font-size: 13px;
        font-weight: 500;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .btn-filter:hover {
        background: #b8860b;
    }
    
    .btn-reset {
        background: #f1f5f9;
        color: #475569;
        padding: 10px 20px;
        text-decoration: none;
        font-size: 13px;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .btn-reset:hover {
        background: #e2e8f0;
    }
    
    .info-periode {
        background: #f8fafc;
        padding: 12px 16px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 13px;
        color: #475569;
    }
    
    .info-periode i {
        color: #D4A000;
    }
    
    .stats-summary-card {
        background: white;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        margin-bottom: 20px;
    }
    
    .summary-header {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px 20px;
        border-bottom: 1px solid #eef2f6;
    }
    
    .summary-header i {
        font-size: 18px;
        color: #D4A000;
    }
    
    .summary-header h3 {
        font-size: 14px;
        font-weight: 600;
        color: #1e293b;
        margin: 0;
    }
    
    .summary-body {
        padding: 20px;
    }
    
    .summary-stats {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        margin-bottom: 24px;
        justify-content: space-around;
    }
    
    .summary-stat {
        text-align: center;
        flex: 1;
    }
    
    .summary-stat .stat-value {
        font-size: 32px;
        font-weight: 700;
        color: #1e293b;
    }
    
    .summary-stat .stat-label {
        font-size: 12px;
        color: #64748b;
        margin-top: 4px;
    }
    
    .progress-year {
        padding-top: 16px;
        border-top: 1px solid #eef2f6;
    }
    
    .progress-label {
        display: flex;
        justify-content: space-between;
        margin-bottom: 8px;
        font-size: 13px;
        font-weight: 500;
        color: #475569;
    }
    
    .progress-percent {
        font-weight: 700;
    }
    
    .progress-bar {
        height: 10px;
        background: #f1f5f9;
        overflow: hidden;
    }
    
    .progress-fill {
        height: 100%;
    }
    
    .chart-card {
        background: white;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        margin-bottom: 20px;
    }
    
    .chart-header {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px 20px;
        border-bottom: 1px solid #eef2f6;
    }
    
    .chart-header i {
        font-size: 18px;
        color: #D4A000;
    }
    
    .chart-header h3 {
        font-size: 14px;
        font-weight: 600;
        color: #1e293b;
        margin: 0;
    }
    
    .chart-body {
        padding: 20px;
    }
    
    .two-columns {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
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
    
    .name-cell strong {
        font-weight: 600;
        color: #1e293b;
    }
    
    .progress-mini {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .progress-value {
        font-size: 12px;
        font-weight: 600;
        min-width: 40px;
    }
    
    .progress-bar-mini {
        flex: 1;
        height: 6px;
        background: #e2e8f0;
        overflow: hidden;
    }
    
    .progress-fill {
        height: 100%;
    }
    
    .info-note {
        background: #f8fafc;
        padding: 12px 16px;
        margin-top: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 12px;
        color: #64748b;
    }
    
    .info-note i {
        color: #D4A000;
    }
    
    .empty-row {
        text-align: center;
        padding: 30px !important;
        color: #94a3b8;
    }
    
    @media (max-width: 768px) {
        .filter-row {
            flex-direction: column;
        }
        
        .filter-group {
            width: 100%;
        }
        
        .filter-actions {
            width: 100%;
        }
        
        .btn-filter, .btn-reset {
            flex: 1;
            text-align: center;
        }
        
        .two-columns {
            grid-template-columns: 1fr;
        }
        
        .summary-stats {
            flex-wrap: wrap;
        }
        
        .summary-stat {
            min-width: 80px;
        }
    }
</style>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<?php if (!$isBulanSelected && $kelas['id'] > 0): ?>
<script>
    const bulanLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    const hadirData = [
        <?php echo $statistikBulanan['01']['hadir'] ?? 0; ?>,
        <?php echo $statistikBulanan['02']['hadir'] ?? 0; ?>,
        <?php echo $statistikBulanan['03']['hadir'] ?? 0; ?>,
        <?php echo $statistikBulanan['04']['hadir'] ?? 0; ?>,
        <?php echo $statistikBulanan['05']['hadir'] ?? 0; ?>,
        <?php echo $statistikBulanan['06']['hadir'] ?? 0; ?>,
        <?php echo $statistikBulanan['07']['hadir'] ?? 0; ?>,
        <?php echo $statistikBulanan['08']['hadir'] ?? 0; ?>,
        <?php echo $statistikBulanan['09']['hadir'] ?? 0; ?>,
        <?php echo $statistikBulanan['10']['hadir'] ?? 0; ?>,
        <?php echo $statistikBulanan['11']['hadir'] ?? 0; ?>,
        <?php echo $statistikBulanan['12']['hadir'] ?? 0; ?>
    ];
    
    const tidakHadirData = [
        <?php echo $statistikBulanan['01']['tidak_hadir'] ?? 0; ?>,
        <?php echo $statistikBulanan['02']['tidak_hadir'] ?? 0; ?>,
        <?php echo $statistikBulanan['03']['tidak_hadir'] ?? 0; ?>,
        <?php echo $statistikBulanan['04']['tidak_hadir'] ?? 0; ?>,
        <?php echo $statistikBulanan['05']['tidak_hadir'] ?? 0; ?>,
        <?php echo $statistikBulanan['06']['tidak_hadir'] ?? 0; ?>,
        <?php echo $statistikBulanan['07']['tidak_hadir'] ?? 0; ?>,
        <?php echo $statistikBulanan['08']['tidak_hadir'] ?? 0; ?>,
        <?php echo $statistikBulanan['09']['tidak_hadir'] ?? 0; ?>,
        <?php echo $statistikBulanan['10']['tidak_hadir'] ?? 0; ?>,
        <?php echo $statistikBulanan['11']['tidak_hadir'] ?? 0; ?>,
        <?php echo $statistikBulanan['12']['tidak_hadir'] ?? 0; ?>
    ];
    
    const ctx = document.getElementById('attendanceChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: bulanLabels,
            datasets: [
                {
                    label: 'Hadir (Tervalidasi)',
                    data: hadirData,
                    backgroundColor: '#22c55e',
                    borderRadius: 0,
                    barPercentage: 0.6
                },
                {
                    label: 'Tidak Hadir (Tervalidasi)',
                    data: tidakHadirData,
                    backgroundColor: '#ef4444',
                    borderRadius: 0,
                    barPercentage: 0.6
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'top',
                    labels: { font: { family: 'Poppins', size: 11 } }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.raw;
                        }
                    }
                }
            },
            scales: {
                y: { beginAtZero: true, grid: { color: '#e2e8f0' }, title: { display: true, text: 'Jumlah', font: { size: 11 } } },
                x: { grid: { display: false }, title: { display: true, text: 'Bulan', font: { size: 11 } } }
            }
        }
    });
</script>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>

<?php
function getNamaBulan($bulan) {
    $nama = [
        '01' => 'Januari', '02' => 'Februari', '03' => 'Maret',
        '04' => 'April', '05' => 'Mei', '06' => 'Juni',
        '07' => 'Juli', '08' => 'Agustus', '09' => 'September',
        '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
    ];
    return $nama[$bulan] ?? '';
}
?>