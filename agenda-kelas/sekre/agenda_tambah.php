<?php
/**
 * Tambah Agenda - Sekretaris
 */

require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

checkRoleAccess(['sekretaris']);

$page_title = 'Tambah Agenda';
$page_subtitle = 'Buat agenda kelas baru';

$userId = $_SESSION['id'];
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $judul = trim($_POST['judul']);
    $deskripsi = trim($_POST['deskripsi']);
    $tanggal = $_POST['tanggal'];
    
    if (empty($judul)) {
        $error = 'Judul agenda harus diisi!';
    } elseif (empty($tanggal)) {
        $error = 'Tanggal harus diisi!';
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO agenda (judul, deskripsi, tanggal, dibuat_oleh, created_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        if ($stmt->execute([$judul, $deskripsi, $tanggal, $userId])) {
            $success = 'Agenda berhasil ditambahkan!';
            echo "<script>setTimeout(() => { window.location.href = 'agenda.php'; }, 1500);</script>";
        } else {
            $error = 'Gagal menambahkan agenda!';
        }
    }
}

include '../includes/navbar.php';
include '../includes/sidebar.php';
?>

<div class="main-content">
    <div class="content-area">
        
        <!-- Alert Notifikasi -->
        <?php if ($success): ?>
            <div class="alert-custom alert-success-custom">
                <i class="fas fa-check-circle"></i>
                <span><?php echo $success; ?> Redirecting...</span>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert-custom alert-error-custom">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $error; ?></span>
            </div>
        <?php endif; ?>
        
        <!-- Header -->
        <div class="page-header">
            <div class="header-left">
                <i class="fas fa-plus-circle"></i>
                <div>
                    <h2>Tambah Agenda</h2>
                    <p>Buat agenda kelas baru</p>
                </div>
            </div>
            <a href="agenda.php" class="btn-secondary">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
        
        <!-- Form Card -->
        <div class="form-card">
            <form method="POST">
                <div class="form-group">
                    <label>Judul Agenda <span class="required">*</span></label>
                    <div class="input-icon">
                        <i class="fas fa-heading"></i>
                        <input type="text" name="judul" placeholder="Masukkan judul agenda" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Deskripsi</label>
                    <div class="input-icon">
                        <i class="fas fa-align-left"></i>
                        <textarea name="deskripsi" rows="5" placeholder="Masukkan deskripsi agenda (opsional)"></textarea>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Tanggal <span class="required">*</span></label>
                        <div class="input-icon">
                            <i class="fas fa-calendar-alt"></i>
                            <input type="date" name="tanggal" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Simpan Agenda
                    </button>
                    <a href="agenda.php" class="btn-outline">Batal</a>
                </div>
            </form>
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
        font-size: 32px;
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
        border: none;
        font-size: 13px;
        font-weight: 500;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .btn-primary:hover {
        background: #b8860b;
    }
    
    .btn-secondary {
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
    
    .btn-secondary:hover {
        background: #e2e8f0;
    }
    
    .btn-outline {
        background: transparent;
        border: 1px solid #cbd5e1;
        color: #475569;
        padding: 10px 20px;
        text-decoration: none;
        font-size: 13px;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .btn-outline:hover {
        background: #f8fafc;
    }
    
    .form-card {
        background: white;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        padding: 28px;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-group label {
        display: block;
        font-size: 13px;
        font-weight: 500;
        color: #1e293b;
        margin-bottom: 8px;
    }
    
    .required {
        color: #ef4444;
    }
    
    .input-icon {
        position: relative;
    }
    
    .input-icon i {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 14px;
    }
    
    .input-icon input,
    .input-icon textarea {
        width: 100%;
        padding: 10px 12px 10px 38px;
        border: 1px solid #e2e8f0;
        font-size: 13px;
        font-family: 'Poppins', sans-serif;
    }
    
    .input-icon textarea {
        padding-top: 10px;
        resize: vertical;
    }
    
    .input-icon input:focus,
    .input-icon textarea:focus {
        outline: none;
        border-color: #D4A000;
    }
    
    .form-row {
        display: grid;
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .form-actions {
        display: flex;
        gap: 12px;
        margin-top: 24px;
        padding-top: 20px;
        border-top: 1px solid #eef2f6;
    }
    
    @media (max-width: 768px) {
        .form-card {
            padding: 20px;
        }
        
        .page-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 16px;
        }
        
        .form-actions {
            flex-direction: column;
        }
        
        .form-actions .btn-primary,
        .form-actions .btn-outline {
            text-align: center;
            justify-content: center;
        }
    }
</style>

<?php include '../includes/footer.php'; ?>