<?php
/**
 * Agenda Kelas - Siswa
 * File: siswa/agenda.php
 */

require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

checkRoleAccess(['siswa']);

$page_title = 'Agenda Kelas';
$page_subtitle = 'Lihat agenda kegiatan kelas';

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

// Filter parameters
$tanggal_mulai = isset($_GET['tanggal_mulai']) && $_GET['tanggal_mulai'] != '' ? $_GET['tanggal_mulai'] : null;
$tanggal_akhir = isset($_GET['tanggal_akhir']) && $_GET['tanggal_akhir'] != '' ? $_GET['tanggal_akhir'] : null;
$bulan = isset($_GET['bulan']) && $_GET['bulan'] != '' ? $_GET['bulan'] : date('m');
$tahun = isset($_GET['tahun']) && $_GET['tahun'] != '' ? $_GET['tahun'] : date('Y');

// Ambil agenda berdasarkan filter
$sql = "SELECT * FROM agenda WHERE 1=1";
$params = [];

if ($tanggal_mulai && $tanggal_akhir) {
    $sql .= " AND tanggal BETWEEN ? AND ?";
    $params[] = $tanggal_mulai;
    $params[] = $tanggal_akhir;
} elseif ($tanggal_mulai) {
    $sql .= " AND tanggal >= ?";
    $params[] = $tanggal_mulai;
} elseif ($tanggal_akhir) {
    $sql .= " AND tanggal <= ?";
    $params[] = $tanggal_akhir;
} else {
    $sql .= " AND MONTH(tanggal) = ? AND YEAR(tanggal) = ?";
    $params[] = $bulan;
    $params[] = $tahun;
}

$sql .= " ORDER BY tanggal DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$agendaList = $stmt->fetchAll();

// Ambil agenda hari ini
$stmt = $pdo->prepare("
    SELECT * FROM agenda 
    WHERE tanggal = CURDATE()
    ORDER BY created_at DESC
");
$stmt->execute();
$agendaHariIni = $stmt->fetchAll();

// Ambil agenda mendatang
$stmt = $pdo->prepare("
    SELECT * FROM agenda 
    WHERE tanggal > CURDATE()
    ORDER BY tanggal ASC
    LIMIT 5
");
$stmt->execute();
$agendaMendatang = $stmt->fetchAll();

include '../includes/navbar.php';
include '../includes/sidebar.php';
?>

<div class="main-content">
    <div class="content-area">
        
        <!-- Header -->
        <div class="page-header">
            <div class="header-left">
                <i class="fas fa-calendar-alt"></i>
                <div>
                    <h2>Agenda Kelas</h2>
                    <p>Semua agenda kegiatan kelas <?php echo htmlspecialchars($siswa['nama_kelas'] ?? ''); ?></p>
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
                            <a href="agenda.php" class="btn-reset">
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
                    echo "Menampilkan agenda dari " . formatTanggal($tanggal_mulai, 'd F Y') . " sampai " . formatTanggal($tanggal_akhir, 'd F Y');
                } elseif ($tanggal_mulai) {
                    echo "Menampilkan agenda dari " . formatTanggal($tanggal_mulai, 'd F Y') . " sampai sekarang";
                } elseif ($tanggal_akhir) {
                    echo "Menampilkan agenda sampai " . formatTanggal($tanggal_akhir, 'd F Y');
                } else {
                    echo "Menampilkan agenda bulan " . getNamaBulan($bulan) . " " . $tahun;
                }
                ?>
            </span>
        </div>
        
        <!-- Agenda Hari Ini -->
        <?php if (count($agendaHariIni) > 0): ?>
        <div class="section-card highlight">
            <div class="section-header">
                <i class="fas fa-star"></i>
                <h3>Agenda Hari Ini</h3>
                <span class="date-badge"><?php echo date('d F Y'); ?></span>
            </div>
            <div class="section-body">
                <?php foreach ($agendaHariIni as $agenda): ?>
                <div class="agenda-card">
                    <div class="agenda-date">
                        <span class="day"><?php echo date('d', strtotime($agenda['tanggal'])); ?></span>
                        <span class="month"><?php echo date('M', strtotime($agenda['tanggal'])); ?></span>
                    </div>
                    <div class="agenda-detail">
                        <h4><?php echo htmlspecialchars($agenda['judul']); ?></h4>
                        <p><?php echo htmlspecialchars($agenda['deskripsi'] ?? 'Tidak ada deskripsi'); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- List Agenda -->
        <div class="section-card">
            <div class="section-header">
                <i class="fas fa-calendar-week"></i>
                <h3>Daftar Agenda</h3>
            </div>
            <div class="section-body">
                <?php if (count($agendaList) > 0): ?>
                    <?php foreach ($agendaList as $agenda): ?>
                    <div class="agenda-card <?php echo strtotime($agenda['tanggal']) < time() ? 'past' : ''; ?>">
                        <div class="agenda-date">
                            <span class="day"><?php echo date('d', strtotime($agenda['tanggal'])); ?></span>
                            <span class="month"><?php echo date('M', strtotime($agenda['tanggal'])); ?></span>
                        </div>
                        <div class="agenda-detail">
                            <div class="agenda-header">
                                <h4><?php echo htmlspecialchars($agenda['judul']); ?></h4>
                                <?php if (strtotime($agenda['tanggal']) == strtotime(date('Y-m-d'))): ?>
                                    <span class="badge-today">Hari Ini</span>
                                <?php elseif (strtotime($agenda['tanggal']) < time()): ?>
                                    <span class="badge-past">Lewat</span>
                                <?php else: ?>
                                    <span class="badge-upcoming">Mendatang</span>
                                <?php endif; ?>
                            </div>
                            <p><?php echo htmlspecialchars($agenda['deskripsi'] ?? 'Tidak ada deskripsi'); ?></p>
                            <a href="detail_agenda.php?id=<?php echo $agenda['id']; ?>" class="btn-detail">
                                <i class="fas fa-eye"></i> Lihat Detail
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-calendar-times"></i>
                        <p>Tidak ada agenda pada periode yang dipilih</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Agenda Mendatang -->
        <?php if (count($agendaMendatang) > 0 && !$tanggal_mulai && !$tanggal_akhir): ?>
        <div class="table-card">
            <div class="table-header">
                <i class="fas fa-clock"></i>
                <h3>Agenda Mendatang</h3>
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
                        <?php foreach ($agendaMendatang as $agenda): ?>
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
                    </tbody>
                </table>
            </div>
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
    
    .section-card { background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.04); margin-bottom: 24px; }
    .section-card.highlight { border-left: 4px solid #D4A000; }
    .section-header { display: flex; align-items: center; gap: 12px; padding: 16px 20px; border-bottom: 1px solid #eef2f6; }
    .section-header i { font-size: 20px; color: #D4A000; }
    .section-header h3 { font-size: 14px; font-weight: 600; color: #1e293b; margin: 0; flex: 1; }
    .date-badge { font-size: 12px; color: #64748b; background: #f8fafc; padding: 4px 12px; }
    .section-body { padding: 20px; }
    
    .agenda-card { display: flex; gap: 20px; padding: 16px; border-bottom: 1px solid #f1f5f9; }
    .agenda-card:last-child { border-bottom: none; }
    .agenda-card.past { opacity: 0.7; }
    .agenda-date { text-align: center; min-width: 60px; background: #f8fafc; padding: 8px 12px; }
    .agenda-date .day { display: block; font-size: 24px; font-weight: 700; color: #1e293b; line-height: 1; }
    .agenda-date .month { display: block; font-size: 11px; color: #64748b; text-transform: uppercase; }
    .agenda-detail { flex: 1; }
    .agenda-header { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 8px; }
    .agenda-header h4 { font-size: 15px; font-weight: 600; color: #1e293b; margin: 0; }
    .agenda-detail p { font-size: 13px; color: #64748b; margin: 0 0 12px; line-height: 1.5; }
    .btn-detail { background: #f1f5f9; color: #475569; padding: 6px 12px; text-decoration: none; font-size: 11px; display: inline-flex; align-items: center; gap: 6px; }
    .btn-detail:hover { background: #e2e8f0; }
    
    .badge-today { background: #D4A000; color: white; padding: 2px 10px; font-size: 10px; font-weight: 600; }
    .badge-upcoming { background: #e2e8f0; color: #475569; padding: 2px 10px; font-size: 10px; font-weight: 600; }
    .badge-past { background: #f1f5f9; color: #94a3b8; padding: 2px 10px; font-size: 10px; font-weight: 600; }
    
    .table-card { background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.04); margin-bottom: 24px; }
    .table-header { display: flex; align-items: center; gap: 12px; padding: 16px 20px; border-bottom: 1px solid #eef2f6; }
    .table-header i { font-size: 18px; color: #D4A000; }
    .table-header h3 { font-size: 14px; font-weight: 600; color: #1e293b; margin: 0; }
    .data-table { width: 100%; border-collapse: collapse; }
    .data-table th { text-align: left; padding: 12px 16px; font-size: 12px; font-weight: 600; color: #475569; background: #f8fafc; border-bottom: 1px solid #eef2f6; }
    .data-table td { padding: 12px 16px; font-size: 13px; color: #334155; border-bottom: 1px solid #f1f5f9; }
    .title-cell strong { font-weight: 600; color: #1e293b; }
    .desc-cell { color: #64748b; }
    .date-badge { display: inline-flex; align-items: center; gap: 6px; font-size: 11px; color: #64748b; }
    
    .empty-state { text-align: center; padding: 40px; }
    .empty-state i { font-size: 48px; color: #cbd5e1; display: block; margin-bottom: 12px; }
    .empty-state p { color: #64748b; }
    
    @media (max-width: 768px) {
        .filter-row { flex-direction: column; }
        .filter-group { width: 100%; }
        .action-buttons { width: 100%; }
        .btn-filter, .btn-reset { flex: 1; justify-content: center; }
        .agenda-card { flex-direction: column; align-items: center; text-align: center; }
        .agenda-header { justify-content: center; }
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