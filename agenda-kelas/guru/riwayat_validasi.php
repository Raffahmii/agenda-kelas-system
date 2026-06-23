<?php
/**
 * Riwayat Validasi - Guru
 * File: guru/riwayat_validasi.php
 */

require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

checkRoleAccess(['guru']);

$page_title = 'Riwayat Validasi';
$page_subtitle = 'History validasi QR Code yang telah dilakukan';

$user_id = $_SESSION['id'];

// Filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$limit = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Build query
$query = "
    SELECT v.*, a.tanggal, a.kelas_id, k.nama_kelas,
           (SELECT COUNT(*) FROM detail_absensi WHERE absensi_id = a.id) as total_siswa
    FROM validasi_qr v
    JOIN absensi_harian a ON v.absensi_id = a.id
    JOIN kelas k ON a.kelas_id = k.id
    WHERE 1=1
";

$countQuery = "
    SELECT COUNT(*) as total
    FROM validasi_qr v
    JOIN absensi_harian a ON v.absensi_id = a.id
    JOIN kelas k ON a.kelas_id = k.id
    WHERE 1=1
";

$params = [];

if ($status_filter) {
    $query .= " AND v.status = ?";
    $countQuery .= " AND v.status = ?";
    $params[] = $status_filter;
}

if ($search) {
    $query .= " AND (k.nama_kelas LIKE ? OR v.qr_token LIKE ?)";
    $countQuery .= " AND (k.nama_kelas LIKE ? OR v.qr_token LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$query .= " ORDER BY v.validated_at DESC LIMIT $limit OFFSET $offset";

// Get total count
$stmt = $pdo->prepare($countQuery);
$stmt->execute($params);
$totalData = $stmt->fetch()['total'];
$totalPages = ceil($totalData / $limit);

// Get data
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$riwayatList = $stmt->fetchAll();

// Get status counts for filter
$stmt = $pdo->prepare("
    SELECT v.status, COUNT(*) as total
    FROM validasi_qr v
    JOIN absensi_harian a ON v.absensi_id = a.id
    GROUP BY v.status
");
$stmt->execute();
$statusCounts = [];
foreach ($stmt->fetchAll() as $sc) {
    $statusCounts[$sc['status']] = $sc['total'];
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
                    <h2>Riwayat Validasi</h2>
                    <p>History validasi QR Code yang telah dilakukan</p>
                </div>
            </div>
        </div>
        
        <!-- Filter Card -->
        <div class="filter-card">
            <form method="GET" action="" class="filter-form">
                <div class="filter-row">
                    <div class="filter-group">
                        <label>Status</label>
                        <select name="status" class="filter-select">
                            <option value="">Semua Status</option>
                            <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Menunggu (<?php echo $statusCounts['pending'] ?? 0; ?>)</option>
                            <option value="valid" <?php echo $status_filter == 'valid' ? 'selected' : ''; ?>>Tervalidasi (<?php echo $statusCounts['valid'] ?? 0; ?>)</option>
                            <option value="ditolak" <?php echo $status_filter == 'ditolak' ? 'selected' : ''; ?>>Ditolak (<?php echo $statusCounts['ditolak'] ?? 0; ?>)</option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Cari</label>
                        <input type="text" name="search" class="filter-search" placeholder="Cari kelas atau token..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="filter-group filter-actions">
                        <label>&nbsp;</label>
                        <div class="action-buttons">
                            <button type="submit" class="btn-filter">
                                <i class="fas fa-search"></i> Filter
                            </button>
                            <a href="riwayat_validasi.php" class="btn-reset">
                                <i class="fas fa-undo-alt"></i> Reset
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Stats Mini -->
        <div class="stats-mini">
            <div class="stat-mini">
                <span class="stat-label">Total Validasi</span>
                <span class="stat-count"><?php echo $totalData; ?></span>
            </div>
            <div class="stat-mini">
                <span class="stat-label">Menunggu</span>
                <span class="stat-count" style="color: #854d0e;"><?php echo $statusCounts['pending'] ?? 0; ?></span>
            </div>
            <div class="stat-mini">
                <span class="stat-label">Tervalidasi</span>
                <span class="stat-count" style="color: #166534;"><?php echo $statusCounts['valid'] ?? 0; ?></span>
            </div>
            <div class="stat-mini">
                <span class="stat-label">Ditolak</span>
                <span class="stat-count" style="color: #991b1b;"><?php echo $statusCounts['ditolak'] ?? 0; ?></span>
            </div>
        </div>
        
        <!-- Table Card -->
        <div class="table-card">
            <div class="table-header">
                <i class="fas fa-list"></i>
                <h3>Data Validasi</h3>
                <span class="total-record"><?php echo $totalData; ?> record</span>
            </div>
            <div class="table-responsive">
                <?php if (count($riwayatList) > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th width="50">No</th>
                            <th>Kelas</th>
                            <th>Tanggal</th>
                            <th>QR Token</th>
                            <th>Siswa</th>
                            <th>Status</th>
                            <th>Validasi Pada</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = $offset + 1; foreach ($riwayatList as $row): ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td class="kelas-cell">
                                <strong><?php echo htmlspecialchars($row['nama_kelas']); ?></strong>
                            </td>
                            <td><?php echo formatTanggal($row['tanggal'], 'd F Y'); ?></td>
                            <td>
                                <code class="token-code"><?php echo substr($row['qr_token'], 0, 16); ?>...</code>
                            </td>
                            <td><?php echo $row['total_siswa']; ?> siswa</td>
                            <td>
                                <span class="status-badge <?php echo $row['status']; ?>">
                                    <?php 
                                    $statusText = [
                                        'pending' => 'Menunggu',
                                        'valid' => 'Tervalidasi',
                                        'ditolak' => 'Ditolak'
                                    ];
                                    echo $statusText[$row['status']] ?? ucfirst($row['status']);
                                    ?>
                                </span>
                            </td>
                            <td class="date-cell">
                                <?php echo $row['validated_at'] ? formatDateTime($row['validated_at']) : '-'; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="empty-data">
                    <i class="fas fa-history"></i>
                    <p>Belum ada data validasi</p>
                    <p class="text-muted small">Silakan scan QR Code untuk memulai validasi</p>
                    <a href="scan_qr.php" class="btn-scan-empty">Scan QR Sekarang</a>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="pagination-container">
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&status=<?php echo urlencode($status_filter); ?>&search=<?php echo urlencode($search); ?>" class="page-link">
                            <i class="fas fa-chevron-left"></i> Sebelumnya
                        </a>
                    <?php endif; ?>
                    
                    <span class="page-info">
                        Halaman <?php echo $page; ?> dari <?php echo $totalPages; ?>
                    </span>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&status=<?php echo urlencode($status_filter); ?>&search=<?php echo urlencode($search); ?>" class="page-link">
                            Selanjutnya <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
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
        margin-bottom: 20px;
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
    
    .filter-select, .filter-search {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #e2e8f0;
        font-size: 13px;
        font-family: 'Poppins', sans-serif;
        background: white;
    }
    
    .filter-select:focus, .filter-search:focus {
        outline: none;
        border-color: #D4A000;
    }
    
    .filter-actions {
        flex: 0 0 auto;
    }
    
    .action-buttons {
        display: flex;
        gap: 10px;
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
    
    .stats-mini {
        display: flex;
        gap: 16px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }
    
    .stat-mini {
        background: white;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        padding: 16px 24px;
        flex: 1;
        text-align: center;
    }
    
    .stat-mini .stat-label {
        display: block;
        font-size: 11px;
        color: #94a3b8;
        text-transform: uppercase;
        margin-bottom: 6px;
    }
    
    .stat-mini .stat-count {
        font-size: 28px;
        font-weight: 700;
        color: #1e293b;
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
    
    .total-record {
        font-size: 12px;
        color: #64748b;
        background: #f1f5f9;
        padding: 4px 10px;
    }
    
    .data-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .data-table th {
        text-align: left;
        padding: 14px 16px;
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
    
    .data-table tbody tr:hover {
        background: #fafafc;
    }
    
    .kelas-cell strong {
        font-weight: 600;
        color: #1e293b;
    }
    
    .token-code {
        font-family: monospace;
        font-size: 11px;
        background: #f8fafc;
        padding: 2px 6px;
        color: #475569;
    }
    
    .status-badge {
        display: inline-block;
        padding: 4px 12px;
        font-size: 11px;
        font-weight: 500;
    }
    
    .status-badge.pending {
        background: #fef9c3;
        color: #854d0e;
    }
    
    .status-badge.valid {
        background: #dcfce7;
        color: #166534;
    }
    
    .status-badge.ditolak {
        background: #fee2e2;
        color: #991b1b;
    }
    
    .date-cell {
        font-size: 12px;
        color: #64748b;
    }
    
    .pagination-container {
        padding: 16px 24px;
        border-top: 1px solid #eef2f6;
        display: flex;
        justify-content: flex-end;
    }
    
    .pagination {
        display: flex;
        align-items: center;
        gap: 16px;
    }
    
    .page-link {
        background: #f1f5f9;
        color: #475569;
        padding: 8px 16px;
        text-decoration: none;
        font-size: 12px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    
    .page-link:hover {
        background: #e2e8f0;
    }
    
    .page-info {
        font-size: 12px;
        color: #64748b;
    }
    
    .empty-data {
        text-align: center;
        padding: 60px 20px;
    }
    
    .empty-data i {
        font-size: 56px;
        color: #cbd5e1;
        display: block;
        margin-bottom: 16px;
    }
    
    .empty-data p {
        color: #64748b;
        margin-bottom: 8px;
    }
    
    .btn-scan-empty {
        background: #D4A000;
        color: white;
        padding: 8px 24px;
        text-decoration: none;
        font-size: 13px;
        font-weight: 500;
        display: inline-block;
        margin-top: 12px;
    }
    
    .btn-scan-empty:hover {
        background: #b8860b;
    }
    
    .info-card {
        background: #f8fafc;
        padding: 16px 20px;
        margin-top: 20px;
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
    
    .text-muted {
        color: #94a3b8;
    }
    
    .small {
        font-size: 12px;
    }
    
    @media (max-width: 768px) {
        .filter-row {
            flex-direction: column;
        }
        
        .filter-group {
            width: 100%;
        }
        
        .action-buttons {
            width: 100%;
        }
        
        .btn-filter, .btn-reset {
            flex: 1;
            justify-content: center;
        }
        
        .stats-mini {
            flex-direction: column;
        }
        
        .data-table th, 
        .data-table td {
            padding: 10px 12px;
        }
        
        .pagination-container {
            justify-content: center;
        }
    }
</style>

<?php include '../includes/footer.php'; ?>