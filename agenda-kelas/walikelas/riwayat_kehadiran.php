<?php
/**
 * Riwayat Kehadiran - Wali Kelas
 * File: walikelas/riwayat_kehadiran.php
 * HANYA menampilkan data kehadiran yang sudah divalidasi (status = 'valid')
 */

require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

checkRoleAccess(['walikelas']);

$page_title = 'Riwayat Kehadiran';
$page_subtitle = 'Lihat history kehadiran siswa';

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

// Filter parameters
$siswa_id = isset($_GET['siswa_id']) ? (int)$_GET['siswa_id'] : 0;
$tanggal_mulai = isset($_GET['tanggal_mulai']) && $_GET['tanggal_mulai'] != '' ? $_GET['tanggal_mulai'] : null;
$tanggal_akhir = isset($_GET['tanggal_akhir']) && $_GET['tanggal_akhir'] != '' ? $_GET['tanggal_akhir'] : null;
$bulan = isset($_GET['bulan']) && $_GET['bulan'] != '' ? $_GET['bulan'] : date('m');
$tahun = isset($_GET['tahun']) && $_GET['tahun'] != '' ? $_GET['tahun'] : date('Y');

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

// Jika tidak ada siswa dipilih dan ada siswa, ambil yang pertama
if ($siswa_id == 0 && count($siswaList) > 0) {
    $siswa_id = $siswaList[0]['id'];
}

// Ambil data siswa yang dipilih
$siswaSelected = null;
foreach ($siswaList as $s) {
    if ($s['id'] == $siswa_id) {
        $siswaSelected = $s;
        break;
    }
}

// Ambil riwayat kehadiran siswa yang dipilih (HANYA YANG SUDAH DIVALIDASI)
$riwayat = [];
$stats = ['hadir' => 0, 'sakit' => 0, 'izin' => 0, 'dispen' => 0, 'alfa' => 0];
$total = 0;
$persenHadir = 0;

if ($siswaSelected) {
    $sql = "
        SELECT d.status, d.keterangan, a.tanggal 
        FROM detail_absensi d
        JOIN absensi_harian a ON d.absensi_id = a.id
        JOIN validasi_qr v ON a.id = v.absensi_id
        WHERE d.siswa_id = ? AND v.status = 'valid'
    ";
    $params = [$siswa_id];
    
    // Apply filters
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
    foreach ($riwayat as $r) {
        $stats[$r['status']]++;
    }
    $total = array_sum($stats);
    $persenHadir = $total > 0 ? round(($stats['hadir'] / $total) * 100) : 0;
}

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
                    <p>Kelas <?php echo htmlspecialchars($kelas['nama_kelas']); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Filter Card -->
        <div class="filter-card">
            <form method="GET" action="" class="filter-form" id="filterForm">
                <div class="filter-row">
                    <div class="filter-group">
                        <label>Pilih Siswa <span class="required">*</span></label>
                        <select name="siswa_id" class="filter-select" required>
                            <option value="">-- Pilih Siswa --</option>
                            <?php foreach ($siswaList as $siswa): ?>
                                <option value="<?php echo $siswa['id']; ?>" <?php echo $siswa_id == $siswa['id'] ? 'selected' : ''; ?>>
                                    <?php echo $siswa['nomor_absen']; ?> - <?php echo htmlspecialchars($siswa['nama_lengkap']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Tanggal Mulai</label>
                        <input type="date" name="tanggal_mulai" class="filter-date" value="<?php echo $tanggal_mulai; ?>">
                    </div>
                    <div class="filter-group">
                        <label>Tanggal Akhir</label>
                        <input type="date" name="tanggal_akhir" class="filter-date" value="<?php echo $tanggal_akhir; ?>">
                    </div>
                </div>
                
                <div class="filter-row filter-row-or">
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
                                <i class="fas fa-search"></i> Tampilkan
                            </button>
                            <a href="riwayat_kehadiran.php" class="btn-reset">
                                <i class="fas fa-undo-alt"></i> Reset
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        
        <?php if ($siswaSelected): ?>
        
        <!-- Info Periode -->
        <div class="info-periode">
            <i class="fas fa-calendar-alt"></i>
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
        
        <!-- Info Siswa Card -->
        <div class="info-card">
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Nama Lengkap</span>
                    <span class="info-value"><?php echo htmlspecialchars($siswaSelected['nama_lengkap']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">NIS</span>
                    <span class="info-value"><?php echo htmlspecialchars($siswaSelected['nis']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Nomor Absen</span>
                    <span class="info-value"><?php echo $siswaSelected['nomor_absen']; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Kelas</span>
                    <span class="info-value"><?php echo htmlspecialchars($kelas['nama_kelas']); ?></span>
                </div>
            </div>
        </div>
        
        <!-- Ringkasan Kehadiran Card -->
        <div class="summary-card">
            <div class="summary-header">
                <i class="fas fa-chart-pie"></i>
                <h3>Ringkasan Kehadiran</h3>
            </div>
            <div class="summary-body">
                <div class="stats-summary">
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $total; ?></div>
                        <div class="stat-label">Total Hari</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value" style="color: #166534;"><?php echo $stats['hadir']; ?></div>
                        <div class="stat-label">Hadir</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value" style="color: #854d0e;"><?php echo $stats['sakit']; ?></div>
                        <div class="stat-label">Sakit</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value" style="color: #1e40af;"><?php echo $stats['izin']; ?></div>
                        <div class="stat-label">Izin</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value" style="color: #5b21b6;"><?php echo $stats['dispen']; ?></div>
                        <div class="stat-label">Dispen</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value" style="color: #991b1b;"><?php echo $stats['alfa']; ?></div>
                        <div class="stat-label">Alfa</div>
                    </div>
                </div>
                
                <div class="progress-section">
                    <div class="progress-label">
                        <span>Tingkat Kehadiran</span>
                        <span class="progress-percent"><?php echo $persenHadir; ?>%</span>
                    </div>
                    <div class="progress-bar-container">
                        <div class="progress-bar-fill" style="width: <?php echo $persenHadir; ?>%; background: <?php echo $persenHadir >= 80 ? '#22c55e' : ($persenHadir >= 60 ? '#eab308' : '#ef4444'); ?>"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tabel Riwayat -->
        <div class="table-card">
            <div class="table-header">
                <i class="fas fa-list-ul"></i>
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
        
        
        
        <?php elseif (count($siswaList) == 0): ?>
        <div class="empty-card">
            <i class="fas fa-users-slash"></i>
            <p>Belum ada data siswa di kelas ini</p>
            <p class="text-muted small">Silakan tambahkan siswa terlebih dahulu</p>
        </div>
        <?php else: ?>
        <div class="empty-card">
            <i class="fas fa-user-graduate"></i>
            <p>Silakan pilih siswa terlebih dahulu</p>
            <p class="text-muted small">Pilih siswa dari dropdown di atas untuk melihat riwayat kehadirannya</p>
        </div>
        <?php endif; ?>
        
    </div>
</div>

<style>
    .page-header { margin-bottom: 24px; padding: 0 4px; }
    .header-left { display: flex; align-items: center; gap: 16px; }
    .header-left i { font-size: 40px; color: #D4A000; }
    .header-left h2 { font-size: 20px; font-weight: 600; color: #1e293b; margin: 0 0 4px; }
    .header-left p { font-size: 13px; color: #64748b; margin: 0; }
    
    .filter-card { background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.04); padding: 20px 24px; margin-bottom: 16px; }
    .filter-row { display: flex; gap: 20px; align-items: flex-end; flex-wrap: wrap; margin-bottom: 16px; }
    .filter-row:last-child { margin-bottom: 0; }
    .filter-row-or { border-top: 1px dashed #e2e8f0; padding-top: 16px; margin-top: 8px; }
    .or-label { color: #94a3b8; font-size: 12px; font-weight: 500; margin-bottom: 6px; }
    .filter-group { flex: 1; min-width: 160px; }
    .filter-group label { display: block; font-size: 12px; font-weight: 500; color: #64748b; margin-bottom: 6px; }
    .required { color: #ef4444; }
    .filter-select, .filter-date { width: 100%; padding: 10px 12px; border: 1px solid #e2e8f0; font-size: 13px; font-family: 'Poppins', sans-serif; background: white; }
    .filter-select:focus, .filter-date:focus { outline: none; border-color: #D4A000; }
    .filter-select:disabled { background: #f8fafc; color: #94a3b8; }
    .filter-actions { flex: 0 0 auto; }
    .action-buttons { display: flex; gap: 10px; }
    .btn-filter { background: #D4A000; color: white; padding: 10px 20px; border: none; font-size: 13px; font-weight: 500; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
    .btn-filter:hover { background: #b8860b; }
    .btn-reset { background: #f1f5f9; color: #475569; padding: 10px 20px; text-decoration: none; font-size: 13px; font-weight: 500; display: inline-flex; align-items: center; gap: 8px; }
    .btn-reset:hover { background: #e2e8f0; }
    
    .info-periode { background: #f8fafc; padding: 12px 16px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; font-size: 13px; color: #475569; }
    .info-periode i { color: #D4A000; }
    
    .info-card { background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.04); padding: 20px 24px; margin-bottom: 20px; }
    .info-grid { display: flex; flex-wrap: wrap; gap: 24px; }
    .info-item { flex: 1; min-width: 150px; }
    .info-label { display: block; font-size: 11px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
    .info-value { font-size: 14px; font-weight: 600; color: #1e293b; }
    
    .summary-card { background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.04); margin-bottom: 20px; }
    .summary-header { display: flex; align-items: center; gap: 12px; padding: 16px 20px; border-bottom: 1px solid #eef2f6; }
    .summary-header i { font-size: 18px; color: #D4A000; }
    .summary-header h3 { font-size: 14px; font-weight: 600; color: #1e293b; margin: 0; }
    .summary-body { padding: 20px; }
    
    .stats-summary { display: flex; flex-wrap: wrap; gap: 16px; margin-bottom: 24px; justify-content: space-around; }
    .stat-item { text-align: center; flex: 1; min-width: 70px; }
    .stat-value { font-size: 28px; font-weight: 700; color: #1e293b; }
    .stat-label { font-size: 11px; color: #64748b; margin-top: 4px; }
    
    .progress-section { padding-top: 16px; border-top: 1px solid #eef2f6; }
    .progress-label { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 13px; font-weight: 500; color: #475569; }
    .progress-percent { font-weight: 700; }
    .progress-bar-container { height: 8px; background: #f1f5f9; overflow: hidden; }
    .progress-bar-fill { height: 100%; }
    
    .table-card { background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
    .table-header { display: flex; align-items: center; gap: 12px; padding: 16px 20px; border-bottom: 1px solid #eef2f6; }
    .table-header i { font-size: 18px; color: #D4A000; }
    .table-header h3 { font-size: 14px; font-weight: 600; color: #1e293b; margin: 0; flex: 1; }
    .total-record { font-size: 12px; color: #64748b; background: #f1f5f9; padding: 4px 10px; }
    
    .data-table { width: 100%; border-collapse: collapse; }
    .data-table th { text-align: left; padding: 14px 16px; font-size: 12px; font-weight: 600; color: #475569; background: #f8fafc; border-bottom: 1px solid #eef2f6; }
    .data-table td { padding: 12px 16px; font-size: 13px; color: #334155; border-bottom: 1px solid #f1f5f9; }
    .data-table tbody tr:hover { background: #fafafc; }
    
    .date-badge { display: inline-flex; align-items: center; gap: 8px; font-size: 13px; }
    .day-cell { color: #64748b; font-size: 12px; }
    
    .status-badge { display: inline-flex; align-items: center; gap: 8px; padding: 4px 12px; font-size: 12px; font-weight: 500; }
    .status-badge.hadir { background: #dcfce7; color: #166534; }
    .status-badge.sakit { background: #fef9c3; color: #854d0e; }
    .status-badge.izin { background: #dbeafe; color: #1e40af; }
    .status-badge.dispen { background: #ede9fe; color: #5b21b6; }
    .status-badge.alfa { background: #fee2e2; color: #991b1b; }
    .keterangan-cell { color: #64748b; max-width: 250px; word-break: break-word; }
    
    .info-note { background: #f8fafc; padding: 12px 16px; margin-top: 20px; display: flex; align-items: center; gap: 10px; font-size: 12px; color: #64748b; }
    .info-note i { color: #D4A000; }
    
    .empty-card { background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.04); padding: 60px 20px; text-align: center; }
    .empty-card i { font-size: 56px; color: #cbd5e1; display: block; margin-bottom: 16px; }
    .empty-card p { color: #64748b; font-size: 14px; margin-bottom: 8px; }
    
    .empty-data { text-align: center; padding: 60px 20px; }
    .empty-data i { font-size: 56px; color: #cbd5e1; display: block; margin-bottom: 16px; }
    .empty-data p { color: #64748b; margin-bottom: 8px; }
    .text-muted { color: #94a3b8; }
    .small { font-size: 12px; }
    
    @media (max-width: 768px) {
        .filter-row { flex-direction: column; gap: 12px; }
        .filter-group { width: 100%; }
        .action-buttons { width: 100%; }
        .btn-filter, .btn-reset { flex: 1; justify-content: center; }
        .stats-summary { gap: 12px; }
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