<?php
/**
 * Detail Agenda - Siswa
 * File: siswa/detail_agenda.php
 */

require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

checkRoleAccess(['siswa']);

$page_title = 'Detail Agenda';
$page_subtitle = 'Lihat detail agenda kelas';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Ambil detail agenda
$stmt = $pdo->prepare("
    SELECT * FROM agenda 
    WHERE id = ?
");
$stmt->execute([$id]);
$agenda = $stmt->fetch();

if (!$agenda) {
    header("Location: agenda.php");
    exit();
}

include '../includes/navbar.php';
include '../includes/sidebar.php';
?>

<div class="main-content">
    <div class="content-area">
        
        <!-- Header dengan Back Button -->
        <div class="page-header">
            <div class="header-left">
                <a href="agenda.php" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Kembali
                </a>
                <div class="header-divider"></div>
                <i class="fas fa-file-alt"></i>
                <div>
                    <h2>Detail Agenda</h2>
                    <p>Informasi lengkap agenda kelas</p>
                </div>
            </div>
        </div>
        
        <!-- Detail Card -->
        <div class="detail-card">
            <div class="detail-header">
                <div class="detail-date">
                    <span class="date-day"><?php echo date('d', strtotime($agenda['tanggal'])); ?></span>
                    <span class="date-month"><?php echo date('F', strtotime($agenda['tanggal'])); ?></span>
                    <span class="date-year"><?php echo date('Y', strtotime($agenda['tanggal'])); ?></span>
                </div>
                <div class="detail-title">
                    <h1><?php echo htmlspecialchars($agenda['judul']); ?></h1>
                    <div class="meta-info">
                        <span class="meta-item">
                            <i class="fas fa-calendar-alt"></i>
                            <?php echo formatTanggal($agenda['tanggal'], 'd F Y'); ?>
                        </span>
                        <span class="meta-item">
                            <i class="fas fa-clock"></i>
                            Dibuat: <?php echo formatDateTime($agenda['created_at']); ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="detail-body">
                <div class="detail-section">
                    <h3><i class="fas fa-align-left"></i> Deskripsi Agenda</h3>
                    <div class="deskripsi-content">
                        <?php if (!empty($agenda['deskripsi'])): ?>
                            <p><?php echo nl2br(htmlspecialchars($agenda['deskripsi'])); ?></p>
                        <?php else: ?>
                            <p class="text-muted">Tidak ada deskripsi untuk agenda ini.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="detail-footer">
                <div class="info-note">
                    <i class="fas fa-info-circle"></i>
                    <span>Agenda ini dibuat untuk seluruh siswa kelas Anda.</span>
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
        flex-wrap: wrap;
    }
    
    .btn-back {
        background: #f1f5f9;
        color: #475569;
        padding: 8px 16px;
        text-decoration: none;
        font-size: 13px;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .btn-back:hover {
        background: #e2e8f0;
    }
    
    .header-divider {
        width: 1px;
        height: 30px;
        background: #e2e8f0;
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
    
    .detail-card {
        background: white;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    }
    
    .detail-header {
        display: flex;
        gap: 24px;
        padding: 28px;
        border-bottom: 1px solid #eef2f6;
        flex-wrap: wrap;
    }
    
    .detail-date {
        text-align: center;
        background: #f8fafc;
        padding: 16px 24px;
        min-width: 100px;
    }
    
    .detail-date .date-day {
        display: block;
        font-size: 36px;
        font-weight: 700;
        color: #D4A000;
        line-height: 1;
    }
    
    .detail-date .date-month {
        display: block;
        font-size: 14px;
        font-weight: 600;
        color: #1e293b;
        margin-top: 4px;
    }
    
    .detail-date .date-year {
        display: block;
        font-size: 12px;
        color: #64748b;
    }
    
    .detail-title {
        flex: 1;
    }
    
    .detail-title h1 {
        font-size: 22px;
        font-weight: 700;
        color: #1e293b;
        margin: 0 0 12px;
    }
    
    .meta-info {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
    }
    
    .meta-item {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        color: #64748b;
    }
    
    .meta-item i {
        font-size: 14px;
        color: #94a3b8;
    }
    
    .detail-body {
        padding: 28px;
    }
    
    .detail-section {
        margin-bottom: 28px;
    }
    
    .detail-section:last-child {
        margin-bottom: 0;
    }
    
    .detail-section h3 {
        font-size: 16px;
        font-weight: 600;
        color: #1e293b;
        margin: 0 0 16px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .detail-section h3 i {
        font-size: 18px;
        color: #D4A000;
    }
    
    .deskripsi-content {
        background: #f8fafc;
        padding: 20px;
        line-height: 1.7;
        color: #334155;
        font-size: 14px;
    }
    
    .deskripsi-content p {
        margin: 0;
    }
    
    .text-muted {
        color: #94a3b8;
    }
    
    .detail-footer {
        padding: 20px 28px;
        border-top: 1px solid #eef2f6;
        background: #fafafc;
    }
    
    .info-note {
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 13px;
        color: #64748b;
    }
    
    .info-note i {
        font-size: 18px;
        color: #D4A000;
    }
    
    @media (max-width: 768px) {
        .detail-header {
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        
        .detail-title {
            text-align: center;
        }
        
        .meta-info {
            justify-content: center;
        }
        
        .detail-body {
            padding: 20px;
        }
        
        .detail-section h3 {
            justify-content: center;
        }
        
        .deskripsi-content {
            text-align: justify;
        }
        
        .info-note {
            justify-content: center;
            text-align: center;
            flex-wrap: wrap;
        }
    }
</style>

<?php include '../includes/footer.php'; ?>