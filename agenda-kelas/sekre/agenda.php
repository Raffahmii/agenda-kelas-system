<?php
/**
 * Kelola Agenda - Sekretaris
 */

require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

checkRoleAccess(['sekretaris']);

$page_title = 'Kelola Agenda';
$page_subtitle = 'Tambah, edit, dan hapus agenda kelas';

$userId = $_SESSION['id'];
$success = '';
$error = '';

// Handle hapus agenda
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    $check = $pdo->prepare("SELECT id FROM agenda WHERE id = ? AND dibuat_oleh = ?");
    $check->execute([$id, $userId]);
    
    if ($check->fetch()) {
        $delete = $pdo->prepare("DELETE FROM agenda WHERE id = ?");
        if ($delete->execute([$id])) {
            $success = 'Agenda berhasil dihapus!';
        } else {
            $error = 'Gagal menghapus agenda!';
        }
    } else {
        $error = 'Agenda tidak ditemukan!';
    }
}

// Ambil semua agenda
$stmt = $pdo->prepare("
    SELECT * FROM agenda 
    WHERE dibuat_oleh = ? 
    ORDER BY created_at DESC
");
$stmt->execute([$userId]);
$agendaList = $stmt->fetchAll();

include '../includes/navbar.php';
include '../includes/sidebar.php';
?>

<div class="main-content">
    <div class="content-area">
        
        <!-- Alert Notifikasi -->
        <?php if ($success): ?>
            <div class="alert-custom alert-success-custom">
                <i class="fas fa-check-circle"></i>
                <span><?php echo $success; ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert-custom alert-error-custom">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $error; ?></span>
            </div>
        <?php endif; ?>
        
        <!-- Header with Add Button -->
        <div class="page-header">
            <div class="header-left">
                <i class="fas fa-clipboard-list"></i>
                <div>
                    <h2>Kelola Agenda</h2>
                    <p>Semua agenda yang telah Anda buat</p>
                </div>
            </div>
            <a href="agenda_tambah.php" class="btn-primary">
                <i class="fas fa-plus-circle"></i> Tambah Agenda
            </a>
        </div>
        
        <!-- Agenda Table -->
        <div class="table-card">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Judul Agenda</th>
                            <th>Deskripsi</th>
                            <th>Tanggal</th>
                            <th>Dibuat</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($agendaList) > 0): ?>
                            <?php $no = 1; foreach ($agendaList as $agenda): ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td class="title-cell">
                                    <strong><?php echo htmlspecialchars($agenda['judul']); ?></strong>
                                </td>
                                <td class="desc-cell">
                                    <?php echo htmlspecialchars(substr($agenda['deskripsi'] ?? '-', 0, 60)); ?>
                                    <?php echo strlen($agenda['deskripsi'] ?? '') > 60 ? '...' : ''; ?>
                                </td>
                                <td>
                                    <span class="date-badge">
                                        <i class="fas fa-calendar-alt"></i>
                                        <?php echo formatTanggal($agenda['tanggal'], 'd F Y'); ?>
                                    </span>
                                </td>
                                <td class="date-cell">
                                    <?php echo formatTanggal($agenda['created_at'], 'd/m/Y H:i'); ?>
                                </td>
                                <td class="action-cell">
                                    <a href="agenda_edit.php?id=<?php echo $agenda['id']; ?>" class="btn-edit" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button onclick="confirmDelete(<?php echo $agenda['id']; ?>)" class="btn-delete" title="Hapus">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="empty-row">
                                    <i class="fas fa-calendar-times"></i>
                                    <p>Belum ada agenda</p>
                                    <a href="agenda_tambah.php" class="btn-small">Buat agenda pertama</a>
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
    .alert-custom {
        padding: 14px 20px;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        gap: 12px;
        font-weight: 500;
    }
    
    .alert-success-custom {
        background: #e8f5e9;
        color: #2e7d32;
        border-left: 4px solid #2e7d32;
    }
    
    .alert-error-custom {
        background: #ffebee;
        color: #c62828;
        border-left: 4px solid #c62828;
    }
    
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
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
    
    .btn-primary {
        background: #D4A000;
        color: white;
        padding: 10px 20px;
        text-decoration: none;
        font-size: 13px;
        font-weight: 500;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .btn-primary:hover {
        background: #b8860b;
        color: white;
    }
    
    .table-card {
        background: white;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        overflow-x: auto;
    }
    
    .data-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .data-table thead {
        background: #f8fafc;
        border-bottom: 1px solid #eef2f6;
    }
    
    .data-table th {
        text-align: left;
        padding: 14px 16px;
        font-size: 13px;
        font-weight: 600;
        color: #475569;
    }
    
    .data-table td {
        padding: 14px 16px;
        font-size: 13px;
        color: #334155;
        border-bottom: 1px solid #f1f5f9;
    }
    
    .data-table tbody tr:hover {
        background: #fafafc;
    }
    
    .title-cell strong {
        font-weight: 600;
        color: #1e293b;
    }
    
    .desc-cell {
        color: #64748b;
        max-width: 300px;
    }
    
    .date-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        color: #64748b;
    }
    
    .date-badge i {
        font-size: 11px;
        color: #94a3b8;
    }
    
    .date-cell {
        font-size: 12px;
        color: #64748b;
    }
    
    .action-cell {
        white-space: nowrap;
    }
    
    /* Icon aksi ukuran lebih besar dan warna abu */
    .btn-edit {
        background: none;
        border: none;
        padding: 8px 12px;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
    }
    
    .btn-edit i {
        font-size: 18px;
        color: #94a3b8;
        transition: color 0.2s;
    }
    
    .btn-edit:hover i {
        color: #3b82f6;
    }
    
    .btn-delete {
        background: none;
        border: none;
        padding: 8px 12px;
        cursor: pointer;
    }
    
    .btn-delete i {
        font-size: 18px;
        color: #94a3b8;
        transition: color 0.2s;
    }
    
    .btn-delete:hover i {
        color: #ef4444;
    }
    
    .empty-row {
        text-align: center;
        padding: 48px 20px !important;
    }
    
    .empty-row i {
        font-size: 48px;
        color: #cbd5e1;
        display: block;
        margin-bottom: 12px;
    }
    
    .empty-row p {
        color: #64748b;
        margin-bottom: 16px;
    }
    
    .btn-small {
        background: #f1f5f9;
        color: #475569;
        padding: 6px 16px;
        text-decoration: none;
        font-size: 12px;
        display: inline-block;
    }
    
    .btn-small:hover {
        background: #e2e8f0;
    }
    
    @media (max-width: 768px) {
        .page-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 16px;
        }
        
        .desc-cell {
            max-width: 200px;
        }
        
        .data-table th, 
        .data-table td {
            padding: 10px 12px;
        }
    }
</style>

<script>
    function confirmDelete(id) {
        Swal.fire({
            title: 'Hapus Agenda?',
            text: 'Data yang dihapus tidak dapat dikembalikan!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'agenda.php?delete=' + id;
            }
        });
    }
</script>

<?php include '../includes/footer.php'; ?>