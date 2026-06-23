<?php
/**
 * Riwayat Kehadiran - Siswa
 * File: siswa/riwayat_kehadiran.php
 * HANYA menampilkan data kehadiran yang sudah divalidasi (status = 'valid')
 */

require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

checkRoleAccess(['siswa']);

$page_title = 'Riwayat Kehadiran';
$page_subtitle = 'Lihat history kehadiran Anda';

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
    header("Location: dashboard.php");
    exit();
}

// Filter parameters
$tanggal_mulai = isset($_GET['tanggal_mulai']) && $_GET['tanggal_mulai'] != '' ? $_GET['tanggal_mulai'] : null;
$tanggal_akhir = isset($_GET['tanggal_akhir']) && $_GET['tanggal_akhir'] != '' ? $_GET['tanggal_akhir'] : null;
$bulan = isset($_GET['bulan']) && $_GET['bulan'] != '' ? $_GET['bulan'] : date('m');
$tahun = isset($_GET['tahun']) && $_GET['tahun'] != '' ? $_GET['tahun'] : date('Y');

// Ambil riwayat kehadiran (HANYA YANG SUDAH DIVALIDASI)
$sql = "
    SELECT d.*, a.tanggal 
    FROM detail_absensi d
    JOIN absensi_harian a ON d.absensi_id = a.id
    JOIN validasi_qr v ON a.id = v.absensi_id
    WHERE d.siswa_id = ? AND v.status = 'valid'
";
$params = [$siswa['id']];

if ($tanggal_mulai && $tanggal_akhir) {
    $sql .= " AND a.tanggal BETWEEN ? AND ?";
    $params[] = $tanggal_mulai;
    $params[] = $tanggal_akhir;
} elseif ($tanggal_mulai) {
    $sql .= " AND a.tanggal >= ?";
    $params[] = $tanggal_mulai;
} elseif ($tanggal_akhir) {
    $sql .= " AND a.tanggal <= ?";
    $params[] = $tanggal_akhir;
} else {
    $sql .= " AND MONTH(a.tanggal) = ? AND YEAR(a.tanggal) = ?";
    $params[] = $bulan;
    $params[] = $tahun;
}

$sql .= " ORDER BY a.tanggal DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$riwayat = $stmt->fetchAll();

// Hitung statistik
$stats = [
    'hadir' => 0, 'sakit' => 0, 'izin' => 0, 'dispen' => 0, 'alfa' => 0
];
foreach ($riwayat as $r) {
    $stats[$r['status']]++;
}
$total = array_sum($stats);
$persenHadir = $total > 0 ? round(($stats['hadir'] / $total) * 100) : 0;

include '../includes/navbar.php';
include '../includes/sidebar.php';
?>

<div class="main-content">
    <div class="content-area">
        
        <!-- Header -->
        <div class="page-header">
            <div class="header-left">
                <i class="fas fa-history"></i>
                <div>
                    <h2>Riwayat Kehadiran</h2>
                    <p>History kehadiran Anda</p>
                </div>
            </div>
        </div>
        
        <!-- Info Siswa -->
        <div class="info-card">
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Nama Siswa</span>
                    <span class="info-value"><?php echo htmlspecialchars($siswa['nama_lengkap']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Kelas</span>
                    <span class="info-value"><?php echo htmlspecialchars($siswa['nama_kelas']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">NIS</span>
                    <span class="info-value"><?php echo htmlspecialchars($siswa['nis']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">No. Absen</span>
                    <span class="info-value"><?php echo $siswa['nomor_absen']; ?></span>
                </div>
            </div>
        </div>
        
        <!-- Filter Tanggal -->
        <div class="filter-card">
            <form method="GET" action="" class="filter-form" id="filterForm">
                <div class="filter-row">
                    <div class="filter-group">
                        <label>Tanggal Mulai</label>
                        <input type="date" name="tanggal_mulai" class="filter-date" value="<?php echo $tanggal_mulai; ?>">
                    </div>
                    <div class="filter-group">
                        <label>Tanggal Akhir</label>
                        <input type="date" name="tanggal_akhir" class="filter-date" value="<?php echo $tanggal_akhir; ?>">
                    </div>
                    <div class="filter-group">
                        <label class="or-label">Atau</label>
                    </div>
                    <div class="filter-group">
                        <label>Bulan</label>
                        <select name="bulan" class="filter-select" id="bulanSelect" <?php echo ($tanggal_mulai || $tanggal_akhir) ? 'disabled' : ''; ?>>
                            <option value="01" <?php echo $bulan == '01' ? 'selected' : ''; ?>>Januari</option>
                            <option value="02" <?php echo $bulan == '02' ? 'selected' : ''; ?>>Februari</option>
                            <option value="03" <?php echo $bulan == '03' ? 'selected' : ''; ?>>Maret</option>
                            <option value="04" <?php echo $bulan == '04' ? 'selected' : ''; ?>>April</option>
                            <option value="05" <?php echo $bulan == '05' ? 'selected' : ''; ?>>Mei</option>
                            <option value="06" <?php echo $bulan == '06' ? 'selected' : ''; ?>>Juni</option>
                            <option value="07" <?php echo $bulan == '07' ? 'selected' : ''; ?>>Juli</option>
                            <option value="08" <?php echo $bulan == '08' ? 'selected' : ''; ?>>Agustus</option>
                            <option value="09" <?php echo $bulan == '09' ? 'selected' : ''; ?>>September</option>
                            <option value="10" <?php echo $bulan == '10' ? 'selected' : ''; ?>>Oktober</option>
                            <option value="11" <?php echo $bulan == '11' ? 'selected' : ''; ?>>November</option>
                            <option value="12" <?php echo $bulan == '12' ? 'selected' : ''; ?>>Desember</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Tahun</label>
                        <select name="tahun" class="filter-select" id="tahunSelect" <?php echo ($tanggal_mulai || $tanggal_akhir) ? 'disabled' : ''; ?>>
                            <?php for ($y = date('Y'); $y >= date('Y') - 2; $y--): ?>
                                <option value="<?php echo $y; ?>" <?php echo $tahun == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="filter-group filter-actions">
                        <label>&nbsp;</label>
                        <div class="action-buttons">
                            <button type="submit" class="btn-filter">
                                <i class="fas fa-search"></i> Filter
                            </button>
                            <a href="riwayat_kehadiran.php" class="btn-reset">
                                <i class="fas fa-undo-alt"></i> Reset
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Info Periode -->
        <div class="info-periode">
            <i class="fas fa-info-circle"></i>
            <span>
                <?php 
                if ($tanggal_mulai && $tanggal_akhir) {
                    echo "Menampilkan data tervalidasi dari " . formatTanggal($tanggal_mulai, 'd F Y') . " sampai " . formatTanggal($tanggal_akhir, 'd F Y');
                } elseif ($tanggal_mulai) {
                    echo "Menampilkan data tervalidasi dari " . formatTanggal($tanggal_mulai, 'd F Y') . " sampai sekarang";
                } elseif ($tanggal_akhir) {
                    echo "Menampilkan data tervalidasi sampai " . formatTanggal($tanggal_akhir, 'd F Y');
                } else {
                    echo "Menampilkan data tervalidasi bulan " . getNamaBulan($bulan) . " " . $tahun;
                }
                ?>
            </span>
        </div>
        
        <!-- Ringkasan Kehadiran -->
        <div class="summary-card">
            <div class="summary-header">
                <i class="fas fa-chart-pie"></i>
                <h3>Ringkasan Kehadiran</h3>
            </div>
            <div class="summary-body">
                <div class="stats-summary">
                    <div class="stat-item">
                        <span class="stat-value"><?php echo $total; ?></span>
                        <span class="stat-label">Total Hari</span>
                    </div>
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
                
                <div class="progress-ringkasan">
                    <div class="progress-label">
                        <span>Tingkat Kehadiran</span>
                        <span><?php echo $persenHadir; ?>%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $persenHadir; ?>%; background: <?php echo $persenHadir >= 80 ? '#22c55e' : ($persenHadir >= 60 ? '#eab308' : '#ef4444'); ?>"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tabel Riwayat -->
        <div class="table-card">
            <div class="table-header">
                <i class="fas fa-list"></i>
                <h3>Detail Kehadiran</h3>
                <span class="total-record"><?php echo count($riwayat); ?> record</span>
            </div>
            <div class="table-responsive">
                <?php if (count($riwayat) > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th width="50">No</th>
                            <th>Tanggal</th>
                            <th>Hari</th>
                            <th>Status</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; foreach ($riwayat as $r): 
                            $hari = date('l', strtotime($r['tanggal']));
                            $hariIndo = '';
                            switch ($hari) {
                                case 'Monday': $hariIndo = 'Senin'; break;
                                case 'Tuesday': $hariIndo = 'Selasa'; break;
                                case 'Wednesday': $hariIndo = 'Rabu'; break;
                                case 'Thursday': $hariIndo = 'Kamis'; break;
                                case 'Friday': $hariIndo = 'Jumat'; break;
                                case 'Saturday': $hariIndo = 'Sabtu'; break;
                                case 'Sunday': $hariIndo = 'Minggu'; break;
                                default: $hariIndo = $hari;
                            }
                        ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td class="date-cell">
                                <span class="date-badge">
                                    <i class="fas fa-calendar-alt"></i>
                                    <?php echo formatTanggal($r['tanggal'], 'd F Y'); ?>
                                </span>
                            </td>
                            <td class="day-cell"><?php echo $hariIndo; ?></td>
                            <td>
                                <span class="status-badge <?php echo $r['status']; ?>">
                                    <i class="fas <?php 
                                        echo $r['status'] == 'hadir' ? 'fa-check-circle' : 
                                            ($r['status'] == 'sakit' ? 'fa-thermometer-half' : 
                                            ($r['status'] == 'izin' ? 'fa-envelope' : 
                                            ($r['status'] == 'dispen' ? 'fa-file-alt' : 'fa-times-circle'))); 
                                    ?>"></i>
                                    <?php echo ucfirst($r['status']); ?>
                                </span>
                            </td>
                            <td class="keterangan-cell">
                                <?php echo htmlspecialchars($r['keterangan'] ?: '-'); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="empty-data">
                    <i class="fas fa-calendar-times"></i>
                    <p>Tidak ada data kehadiran tervalidasi untuk periode yang dipilih</p>
                    <p class="text-muted small">Pastikan absensi sudah divalidasi oleh guru</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
      
        
    </div>
</div>

<style>
    .page-header { margin-bottom: 24px; padding: 0 4px; }
    .header-left { display: flex; align-items: center; gap: 16px; }
    .header-left i { font-size: 40px; color: #D4A000; }
    .header-left h2 { font-size: 20px; font-weight: 600; color: #1e293b; margin: 0 0 4px; }
    .header-left p { font-size: 13px; color: #64748b; margin: 0; }
    
    .info-card { background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.04); padding: 20px 24px; margin-bottom: 20px; }
    .info-grid { display: flex; flex-wrap: wrap; gap: 24px; }
    .info-item { flex: 1; min-width: 150px; }
    .info-label { display: block; font-size: 11px; color: #94a3b8; text-transform: uppercase; margin-bottom: 4px; }
    .info-value { font-size: 14px; font-weight: 600; color: #1e293b; }
    
    .filter-card { background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.04); padding: 20px 24px; margin-bottom: 16px; }
    .filter-row { display: flex; gap: 20px; align-items: flex-end; flex-wrap: wrap; }
    .filter-group { flex: 1; min-width: 150px; }
    .filter-group label { display: block; font-size: 12px; font-weight: 500; color: #64748b; margin-bottom: 6px; }
    .or-label { color: #94a3b8; font-size: 12px; font-weight: 500; margin-bottom: 6px; }
    .filter-date, .filter-select { width: 100%; padding: 10px 12px; border: 1px solid #e2e8f0; font-size: 13px; font-family: 'Poppins', sans-serif; background: white; }
    .filter-date:focus, .filter-select:focus { outline: none; border-color: #D4A000; }
    .filter-select:disabled { background: #f8fafc; color: #94a3b8; }
    .filter-actions { flex: 0 0 auto; }
    .action-buttons { display: flex; gap: 10px; }
    .btn-filter { background: #D4A000; color: white; padding: 10px 20px; border: none; font-size: 13px; font-weight: 500; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
    .btn-filter:hover { background: #b8860b; }
    .btn-reset { background: #f1f5f9; color: #475569; padding: 10px 20px; text-decoration: none; font-size: 13px; font-weight: 500; display: inline-flex; align-items: center; gap: 8px; }
    .btn-reset:hover { background: #e2e8f0; }
    
    .info-periode { background: #f8fafc; padding: 12px 16px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; font-size: 13px; color: #475569; }
    .info-periode i { color: #D4A000; }
    
    .summary-card { background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.04); margin-bottom: 20px; }
    .summary-header { display: flex; align-items: center; gap: 12px; padding: 16px 20px; border-bottom: 1px solid #eef2f6; }
    .summary-header i { font-size: 18px; color: #D4A000; }
    .summary-header h3 { font-size: 14px; font-weight: 600; color: #1e293b; margin: 0; }
    .summary-body { padding: 20px; }
    
    .stats-summary { display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 24px; justify-content: space-around; }
    .stat-item { text-align: center; flex: 1; }
    .stat-value { font-size: 28px; font-weight: 700; color: #1e293b; }
    .stat-label { font-size: 11px; color: #64748b; margin-top: 4px; }
    
    .progress-ringkasan { padding-top: 16px; border-top: 1px solid #eef2f6; }
    .progress-label { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 13px; font-weight: 500; color: #475569; }
    .progress-bar { height: 10px; background: #f1f5f9; overflow: hidden; }
    .progress-fill { height: 100%; }
    
    .table-card { background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
    .table-header { display: flex; align-items: center; gap: 12px; padding: 16px 20px; border-bottom: 1px solid #eef2f6; }
    .table-header i { font-size: 18px; color: #D4A000; }
    .table-header h3 { font-size: 14px; font-weight: 600; color: #1e293b; margin: 0; flex: 1; }
    .total-record { font-size: 12px; color: #64748b; background: #f1f5f9; padding: 4px 10px; }
    
    .data-table { width: 100%; border-collapse: collapse; }
    .data-table th { text-align: left; padding: 14px 16px; font-size: 12px; font-weight: 600; color: #475569; background: #f8fafc; border-bottom: 1px solid #eef2f6; }
    .data-table td { padding: 12px 16px; font-size: 13px; color: #334155; border-bottom: 1px solid #f1f5f9; }
    
    .date-badge { display: inline-flex; align-items: center; gap: 8px; font-size: 13px; }
    .day-cell { color: #64748b; font-size: 12px; }
    
    .status-badge { display: inline-flex; align-items: center; gap: 8px; padding: 4px 12px; font-size: 12px; font-weight: 500; }
    .status-badge.hadir { background: #dcfce7; color: #166534; }
    .status-badge.sakit { background: #fef9c3; color: #854d0e; }
    .status-badge.izin { background: #dbeafe; color: #1e40af; }
    .status-badge.dispen { background: #ede9fe; color: #5b21b6; }
    .status-badge.alfa { background: #fee2e2; color: #991b1b; }
    .keterangan-cell { color: #64748b; max-width: 250px; }
    
    .info-note { background: #f8fafc; padding: 12px 16px; margin-top: 20px; display: flex; align-items: center; gap: 10px; font-size: 12px; color: #64748b; }
    .info-note i { color: #D4A000; }
    
    .empty-data { text-align: center; padding: 60px 20px; }
    .empty-data i { font-size: 56px; color: #cbd5e1; display: block; margin-bottom: 16px; }
    .empty-data p { color: #64748b; }
    .text-muted { color: #94a3b8; }
    .small { font-size: 12px; }
    
    @media (max-width: 768px) {
        .filter-row { flex-direction: column; }
        .filter-group { width: 100%; }
        .action-buttons { width: 100%; }
        .btn-filter, .btn-reset { flex: 1; justify-content: center; }
        .stats-summary { flex-wrap: wrap; gap: 12px; }
        .stat-value { font-size: 22px; }
        .data-table th, .data-table td { padding: 10px 12px; }
        .status-badge { white-space: nowrap; }
        .info-grid { flex-direction: column; gap: 12px; }
    }
</style>

<script>
    const tanggalMulai = document.querySelector('input[name="tanggal_mulai"]');
    const tanggalAkhir = document.querySelector('input[name="tanggal_akhir"]');
    const bulanSelect = document.getElementById('bulanSelect');
    const tahunSelect = document.getElementById('tahunSelect');
    
    function toggleBulanTahun() {
        const isDateFilled = (tanggalMulai && tanggalMulai.value !== '') || (tanggalAkhir && tanggalAkhir.value !== '');
        if (bulanSelect) bulanSelect.disabled = isDateFilled;
        if (tahunSelect) tahunSelect.disabled = isDateFilled;
    }
    
    if (tanggalMulai && tanggalAkhir) {
        tanggalMulai.addEventListener('change', toggleBulanTahun);
        tanggalAkhir.addEventListener('change', toggleBulanTahun);
        toggleBulanTahun();
    }
</script>

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