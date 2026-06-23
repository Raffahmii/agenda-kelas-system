<?php
/**
 * Export Laporan - Wali Kelas
 * File: walikelas/export.php
 * HANYA mengekspor data kehadiran yang sudah divalidasi (status = 'valid')
 */

require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

checkRoleAccess(['walikelas']);

$page_title = 'Export Laporan';
$page_subtitle = 'Export data kehadiran';

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
$bulan = isset($_GET['bulan']) ? $_GET['bulan'] : date('m');
$tahun = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');
$format = isset($_GET['format']) ? $_GET['format'] : '';

// Jika export dipilih
if ($format && $kelas['id'] > 0) {
    // Ambil data siswa
    $stmt = $pdo->prepare("
        SELECT s.*, u.nama as user_nama 
        FROM siswa s
        JOIN users u ON s.user_id = u.id
        WHERE s.kelas_id = ?
        ORDER BY s.nomor_absen ASC
    ");
    $stmt->execute([$kelas['id']]);
    $siswaList = $stmt->fetchAll();
    
    // Ambil data kehadiran (HANYA YANG SUDAH DIVALIDASI)
    $kehadiranData = [];
    foreach ($siswaList as $siswa) {
        $stmt = $pdo->prepare("
            SELECT d.status, COUNT(*) as total
            FROM detail_absensi d
            JOIN absensi_harian a ON d.absensi_id = a.id
            JOIN validasi_qr v ON a.id = v.absensi_id
            WHERE d.siswa_id = ? AND MONTH(a.tanggal) = ? AND YEAR(a.tanggal) = ? AND v.status = 'valid'
            GROUP BY d.status
        ");
        $stmt->execute([$siswa['id'], $bulan, $tahun]);
        $stats = [];
        foreach ($stmt->fetchAll() as $row) {
            $stats[$row['status']] = $row['total'];
        }
        
        $total = array_sum($stats);
        $kehadiranData[] = [
            'no_absen' => $siswa['nomor_absen'],
            'nis' => $siswa['nis'],
            'nama' => $siswa['nama_lengkap'],
            'hadir' => $stats['hadir'] ?? 0,
            'sakit' => $stats['sakit'] ?? 0,
            'izin' => $stats['izin'] ?? 0,
            'dispen' => $stats['dispen'] ?? 0,
            'alfa' => $stats['alfa'] ?? 0,
            'total' => $total,
            'persen' => $total > 0 ? round(($stats['hadir'] ?? 0) / $total * 100) : 0
        ];
    }
    
    // Export ke CSV/Excel
    if ($format == 'excel') {
        $filename = "laporan_kehadiran_tervalidasi_{$kelas['nama_kelas']}_{$bulan}_{$tahun}";
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        
        $output = fopen('php://output', 'w');
        // Add UTF-8 BOM for Excel compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        fputcsv($output, ['No. Absen', 'NIS', 'Nama Siswa', 'Hadir', 'Sakit', 'Izin', 'Dispen', 'Alfa', 'Total', 'Persentase (%)']);
        
        foreach ($kehadiranData as $data) {
            fputcsv($output, [
                $data['no_absen'],
                $data['nis'],
                $data['nama'],
                $data['hadir'],
                $data['sakit'],
                $data['izin'],
                $data['dispen'],
                $data['alfa'],
                $data['total'],
                $data['persen']
            ]);
        }
        fclose($output);
        exit();
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
                <i class="fas fa-download"></i>
                <div>
                    <h2>Export Laporan</h2>
                    <p>Kelas <?php echo htmlspecialchars($kelas['nama_kelas']); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Export Card -->
        <div class="export-card">
            <div class="export-icon">
                <i class="fas fa-file-excel"></i>
            </div>
            <div class="export-content">
                <h3>Export Data Kehadiran</h3>
                <p>Export data kehadiran siswa yang sudah divalidasi ke dalam format Excel (CSV). Data yang ditolak tidak akan diekspor.</p>
                
                <form method="GET" action="" class="export-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Pilih Bulan</label>
                            <select name="bulan" class="form-select">
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
                        <div class="form-group">
                            <label>Pilih Tahun</label>
                            <select name="tahun" class="form-select">
                                <?php for ($y = date('Y'); $y >= date('Y') - 2; $y--): ?>
                                    <option value="<?php echo $y; ?>" <?php echo $tahun == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="submit" name="format" value="excel" class="btn-export">
                                <i class="fas fa-download"></i> Export ke Excel
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
            
        
        <!-- Preview Data -->
        <div class="preview-card">
            <div class="preview-header">
                <i class="fas fa-table"></i>
                <h3>Preview Data</h3>
                <span class="preview-note">Data yang akan diexport</span>
            </div>
            <div class="preview-body">
                <?php if ($kelas['id'] > 0): 
                    // Ambil preview data
                    $stmt = $pdo->prepare("
                        SELECT s.nama_lengkap, s.nis, s.nomor_absen
                        FROM siswa s
                        WHERE s.kelas_id = ?
                        ORDER BY s.nomor_absen ASC
                        LIMIT 10
                    ");
                    $stmt->execute([$kelas['id']]);
                    $previewSiswa = $stmt->fetchAll();
                ?>
                    <div class="preview-list">
                        <div class="preview-item preview-header-item">
                            <span class="preview-no">No</span>
                            <span class="preview-absen">Absen</span>
                            <span class="preview-nis">NIS</span>
                            <span class="preview-nama">Nama Siswa</span>
                        </div>
                        <?php $no = 1; foreach ($previewSiswa as $siswa): ?>
                        <div class="preview-item">
                            <span class="preview-no"><?php echo $no++; ?></span>
                            <span class="preview-absen"><?php echo $siswa['nomor_absen']; ?></span>
                            <span class="preview-nis"><?php echo htmlspecialchars($siswa['nis']); ?></span>
                            <span class="preview-nama"><?php echo htmlspecialchars($siswa['nama_lengkap']); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($previewSiswa) >= 10): ?>
                    <div class="preview-more">
                        <span>...dan siswa lainnya</span>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Info status validasi -->
                    <div class="preview-info">
                        <i class="fas fa-check-circle"></i>
                        <span>Data yang diekspor hanya kehadiran yang sudah <strong>divalidasi</strong> oleh guru. Data yang ditolak tidak termasuk.</span>
                    </div>
                <?php else: ?>
                    <div class="empty-preview">
                        <i class="fas fa-school"></i>
                        <p>Belum ada data kelas</p>
                    </div>
                <?php endif; ?>
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
    
    .export-card {
        background: white;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        display: flex;
        gap: 28px;
        padding: 32px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }
    
    .export-icon i {
        font-size: 64px;
        color: #D4A000;
    }
    
    .export-content {
        flex: 1;
    }
    
    .export-content h3 {
        font-size: 18px;
        font-weight: 600;
        color: #1e293b;
        margin: 0 0 8px;
    }
    
    .export-content p {
        font-size: 13px;
        color: #64748b;
        margin: 0 0 20px;
    }
    
    .export-form {
        max-width: 500px;
    }
    
    .form-row {
        display: flex;
        gap: 16px;
        align-items: flex-end;
        flex-wrap: wrap;
    }
    
    .form-group {
        flex: 1;
    }
    
    .form-group label {
        display: block;
        font-size: 12px;
        font-weight: 500;
        color: #64748b;
        margin-bottom: 6px;
    }
    
    .form-select {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #e2e8f0;
        font-size: 13px;
        font-family: 'Poppins', sans-serif;
        background: white;
    }
    
    .form-select:focus {
        outline: none;
        border-color: #D4A000;
    }
    
    .btn-export {
        background: #D4A000;
        color: white;
        padding: 10px 24px;
        border: none;
        font-size: 13px;
        font-weight: 500;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .btn-export:hover {
        background: #b8860b;
    }
    
    .info-card {
        background: #f8fafc;
        padding: 16px 20px;
        margin-bottom: 20px;
    }
    
    .info-content {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .info-content i {
        font-size: 20px;
        color: #D4A000;
    }
    
    .preview-card {
        background: white;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    }
    
    .preview-header {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px 20px;
        border-bottom: 1px solid #eef2f6;
    }
    
    .preview-header i {
        font-size: 18px;
        color: #D4A000;
    }
    
    .preview-header h3 {
        font-size: 14px;
        font-weight: 600;
        color: #1e293b;
        margin: 0;
        flex: 1;
    }
    
    .preview-note {
        font-size: 11px;
        color: #94a3b8;
    }
    
    .preview-body {
        padding: 20px;
    }
    
    .preview-list {
        width: 100%;
    }
    
    .preview-item {
        display: flex;
        gap: 16px;
        padding: 10px 0;
        border-bottom: 1px solid #f1f5f9;
        font-size: 13px;
    }
    
    .preview-header-item {
        font-weight: 600;
        color: #475569;
        border-bottom: 2px solid #eef2f6;
        padding-bottom: 8px;
    }
    
    .preview-no {
        width: 40px;
    }
    
    .preview-absen {
        width: 60px;
    }
    
    .preview-nis {
        width: 120px;
    }
    
    .preview-nama {
        flex: 1;
    }
    
    .preview-more {
        text-align: center;
        padding: 12px;
        color: #94a3b8;
        font-size: 12px;
    }
    
    .preview-info {
        background: #e8f5e9;
        padding: 12px 16px;
        margin-top: 16px;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 12px;
        color: #2e7d32;
    }
    
    .preview-info i {
        font-size: 16px;
    }
    
    .empty-preview {
        text-align: center;
        padding: 40px;
    }
    
    .empty-preview i {
        font-size: 48px;
        color: #cbd5e1;
        display: block;
        margin-bottom: 12px;
    }
    
    .text-muted {
        color: #94a3b8;
    }
    
    .small {
        font-size: 12px;
    }
    
    @media (max-width: 768px) {
        .export-card {
            text-align: center;
            justify-content: center;
        }
        
        .form-row {
            flex-direction: column;
        }
        
        .preview-item {
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .preview-no, .preview-absen, .preview-nis {
            width: auto;
        }
    }
</style>

<?php include '../includes/footer.php'; ?>