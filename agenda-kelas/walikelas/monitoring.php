<?php
/**
 * Monitoring Kehadiran - Wali Kelas
 * File: walikelas/monitoring.php
 * HANYA menampilkan data kehadiran yang sudah divalidasi (status = 'valid')
 */

require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

checkRoleAccess(['walikelas']);

$page_title = 'Monitoring Kehadiran';
$page_subtitle = 'Pantau kehadiran siswa (Data Tervalidasi)';

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
$tanggal_mulai = isset($_GET['tanggal_mulai']) && $_GET['tanggal_mulai'] != '' ? $_GET['tanggal_mulai'] : null;
$tanggal_akhir = isset($_GET['tanggal_akhir']) && $_GET['tanggal_akhir'] != '' ? $_GET['tanggal_akhir'] : null;
$bulan = isset($_GET['bulan']) && $_GET['bulan'] != '' ? $_GET['bulan'] : date('m');
$tahun = isset($_GET['tahun']) && $_GET['tahun'] != '' ? $_GET['tahun'] : date('Y');

// Ambil semua siswa
$stmt = $pdo->prepare("
    SELECT s.*, u.nama as user_nama 
    FROM siswa s
    JOIN users u ON s.user_id = u.id
    WHERE s.kelas_id = ?
    ORDER BY s.nomor_absen ASC
");
$stmt->execute([$kelas['id']]);
$siswaList = $stmt->fetchAll();

// Ambil data kehadiran per siswa (HANYA YANG SUDAH DIVALIDASI - status = 'valid')
$kehadiranSiswa = [];
foreach ($siswaList as $siswa) {
    // Query dengan JOIN validasi_qr dan filter status = 'valid'
    $sql = "
        SELECT d.status, COUNT(*) as total
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
    
    $sql .= " GROUP BY d.status";
    
    $stmt2 = $pdo->prepare($sql);
    $stmt2->execute($params);
    $stats = [];
    foreach ($stmt2->fetchAll() as $row) {
        $stats[$row['status']] = $row['total'];
    }
    
    $total = array_sum($stats);
    $kehadiranSiswa[$siswa['id']] = [
        'hadir' => $stats['hadir'] ?? 0,
        'sakit' => $stats['sakit'] ?? 0,
        'izin' => $stats['izin'] ?? 0,
        'dispen' => $stats['dispen'] ?? 0,
        'alfa' => $stats['alfa'] ?? 0,
        'total' => $total,
        'persen' => $total > 0 ? round(($stats['hadir'] ?? 0) / $total * 100) : 0
    ];
}

// Hitung total keseluruhan
$totalHadir = array_sum(array_column($kehadiranSiswa, 'hadir'));
$totalSiswaData = count(array_filter($kehadiranSiswa, fn($k) => $k['total'] > 0));
$rataPersen = $totalSiswaData > 0 ? round(array_sum(array_column($kehadiranSiswa, 'persen')) / $totalSiswaData) : 0;

include '../includes/navbar.php';
include '../includes/sidebar.php';
?>

<div class="main-content">
    <div class="content-area">
        
        <!-- Header -->
        <div class="page-header">
            <div class="header-left">
                <i class="fas fa-eye"></i>
                <div>
                    <h2>Monitoring Kehadiran</h2>
                    <p>Kelas <?php echo htmlspecialchars($kelas['nama_kelas']); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Filter -->
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
                            <option value="">Pilih Bulan</option>
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
                            <option value="">Pilih Tahun</option>
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
                            <a href="monitoring.php" class="btn-reset">
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
                    echo "Menampilkan data dari " . formatTanggal($tanggal_mulai, 'd F Y') . " sampai " . formatTanggal($tanggal_akhir, 'd F Y');
                } elseif ($tanggal_mulai) {
                    echo "Menampilkan data dari " . formatTanggal($tanggal_mulai, 'd F Y') . " sampai sekarang";
                } elseif ($tanggal_akhir) {
                    echo "Menampilkan data sampai " . formatTanggal($tanggal_akhir, 'd F Y');
                } else {
                    echo "Menampilkan data bulan " . getNamaBulan($bulan) . " " . $tahun;
                }
                ?>
            </span>
        </div>
        
        <!-- Ringkasan -->
        <div class="summary-mini">
            <div class="summary-mini-item">
                <span class="summary-mini-label">Total Kehadiran</span>
                <span class="summary-mini-value"><?php echo array_sum(array_column($kehadiranSiswa, 'total')); ?></span>
            </div>
            <div class="summary-mini-item">
                <span class="summary-mini-label">Total Hadir</span>
                <span class="summary-mini-value" style="color: #166534;"><?php echo $totalHadir; ?></span>
            </div>
            <div class="summary-mini-item">
                <span class="summary-mini-label">Rata-rata Kehadiran</span>
                <span class="summary-mini-value"><?php echo $rataPersen; ?>%</span>
            </div>
        </div>
        
        <!-- Tabel Monitoring -->
        <div class="table-card">
            <div class="table-header">
                <i class="fas fa-users"></i>
                <h3>Data Kehadiran Siswa (Hanya Data Tervalidasi)</h3>
            </div>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th width="50">No</th>
                            <th width="70">Absen</th>
                            <th>NIS</th>
                            <th>Nama Siswa</th>
                            <th width="70">Hadir</th>
                            <th width="70">Sakit</th>
                            <th width="70">Izin</th>
                            <th width="70">Dispen</th>
                            <th width="70">Alfa</th>
                            <th width="80">Total</th>
                            <th width="100">Persentase</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($siswaList) > 0): ?>
                            <?php $no = 1; foreach ($siswaList as $siswa): 
                                $data = $kehadiranSiswa[$siswa['id']];
                                $persenColor = $data['persen'] >= 80 ? '#166534' : ($data['persen'] >= 60 ? '#854d0e' : '#991b1b');
                            ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo $siswa['nomor_absen']; ?></td>
                                <td><?php echo htmlspecialchars($siswa['nis']); ?></td>
                                <td class="name-cell">
                                    <strong><?php echo htmlspecialchars($siswa['nama_lengkap']); ?></strong>
                                </td>
                                <td class="hadir-cell"><?php echo $data['hadir']; ?></td>
                                <td class="sakit-cell"><?php echo $data['sakit']; ?></td>
                                <td class="izin-cell"><?php echo $data['izin']; ?></td>
                                <td class="dispen-cell"><?php echo $data['dispen']; ?></td>
                                <td class="alfa-cell"><?php echo $data['alfa']; ?></td>
                                <td class="total-cell"><?php echo $data['total']; ?></td>
                                <td class="persen-cell">
                                    <div class="progress-mini">
                                        <span class="progress-value" style="color: <?php echo $persenColor; ?>"><?php echo $data['persen']; ?>%</span>
                                        <div class="progress-bar-mini">
                                            <div class="progress-fill" style="width: <?php echo $data['persen']; ?>%; background: <?php echo $persenColor; ?>"></div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="11" class="empty-row">
                                    <i class="fas fa-users-slash"></i>
                                    <p>Belum ada data siswa</p>
                                </td>
                            </tr>
                        <?php endif; ?>
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
        min-width: 150px;
    }
    
    .filter-group label {
        display: block;
        font-size: 12px;
        font-weight: 500;
        color: #64748b;
        margin-bottom: 6px;
    }
    
    .or-label {
        color: #94a3b8;
        font-size: 12px;
        font-weight: 500;
        margin-bottom: 6px;
    }
    
    .filter-date, .filter-select {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #e2e8f0;
        font-size: 13px;
        font-family: 'Poppins', sans-serif;
        background: white;
    }
    
    .filter-date:focus, .filter-select:focus {
        outline: none;
        border-color: #D4A000;
    }
    
    .filter-select:disabled {
        background: #f8fafc;
        color: #94a3b8;
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
    
    .summary-mini {
        display: flex;
        gap: 16px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }
    
    .summary-mini-item {
        background: white;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        padding: 16px 24px;
        flex: 1;
        text-align: center;
    }
    
    .summary-mini-label {
        display: block;
        font-size: 11px;
        color: #94a3b8;
        margin-bottom: 6px;
    }
    
    .summary-mini-value {
        font-size: 24px;
        font-weight: 700;
        color: #1e293b;
    }
    
    .table-card {
        background: white;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        margin-bottom: 16px;
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
        text-align: center;
        padding: 12px 8px;
        font-size: 12px;
        font-weight: 600;
        color: #475569;
        background: #f8fafc;
        border-bottom: 1px solid #eef2f6;
    }
    
    .data-table td {
        text-align: center;
        padding: 10px 8px;
        font-size: 13px;
        color: #334155;
        border-bottom: 1px solid #f1f5f9;
    }
    
    .data-table td:first-child,
    .data-table th:first-child {
        text-align: left;
    }
    
    .data-table td:nth-child(4),
    .data-table th:nth-child(4) {
        text-align: left;
    }
    
    .name-cell strong {
        font-weight: 600;
        color: #1e293b;
    }
    
    .hadir-cell {
        color: #166534;
        font-weight: 600;
    }
    
    .sakit-cell {
        color: #854d0e;
    }
    
    .izin-cell {
        color: #1e40af;
    }
    
    .dispen-cell {
        color: #5b21b6;
    }
    
    .alfa-cell {
        color: #991b1b;
    }
    
    .total-cell {
        font-weight: 600;
        color: #1e293b;
    }
    
    .progress-mini {
        display: flex;
        align-items: center;
        gap: 8px;
        justify-content: center;
    }
    
    .progress-value {
        font-size: 12px;
        font-weight: 600;
        min-width: 40px;
    }
    
    .progress-bar-mini {
        width: 60px;
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
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 12px;
        color: #64748b;
    }
    
    .info-note i {
        color: #D4A000;
    }
    
    .legend-card {
        background: white;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        padding: 16px 20px;
        display: flex;
        align-items: center;
        gap: 24px;
        flex-wrap: wrap;
    }
    
    .legend-title {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        color: #64748b;
    }
    
    .legend-title i {
        color: #D4A000;
    }
    
    .legend-items {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
    }
    
    .legend-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 12px;
        color: #475569;
    }
    
    .legend-color {
        width: 16px;
        height: 16px;
    }
    
    .empty-row {
        text-align: center;
        padding: 48px !important;
    }
    
    .empty-row i {
        font-size: 48px;
        color: #cbd5e1;
        display: block;
        margin-bottom: 12px;
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
        
        .summary-mini {
            flex-direction: column;
        }
        
        .data-table {
            font-size: 11px;
        }
        
        .data-table th, .data-table td {
            padding: 8px 4px;
        }
        
        .progress-bar-mini {
            width: 40px;
        }
    }
</style>

<script>
    // Disable bulan & tahun filter jika tanggal diisi
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