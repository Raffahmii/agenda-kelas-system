<?php
/**
 * Sekretaris Dashboard
 * File: sekre/dashboard.php
 * HANYA menampilkan statistik kehadiran yang sudah divalidasi (status = 'valid')
 */

require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

checkRoleAccess(['sekretaris']);

$page_title = 'Dashboard Sekretaris';
$page_subtitle = 'Kelola agenda dan kehadiran kelas (Statistik Tervalidasi)';

$userId = $_SESSION['id'];

// Total agenda bulan ini
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total 
    FROM agenda 
    WHERE dibuat_oleh = ? 
    AND MONTH(created_at) = MONTH(CURRENT_DATE()) 
    AND YEAR(created_at) = YEAR(CURRENT_DATE())
");
$stmt->execute([$userId]);
$totalAgendaBulanIni = $stmt->fetch()['total'];

// Total agenda keseluruhan
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM agenda WHERE dibuat_oleh = ?");
$stmt->execute([$userId]);
$totalAgenda = $stmt->fetch()['total'];

// Absensi hari ini (HANYA YANG SUDAH DIVALIDASI)
$tanggalHariIni = date('Y-m-d');
$stmt = $pdo->prepare("
    SELECT a.*, k.nama_kelas,
        COUNT(DISTINCT d.id) as total_siswa,
        SUM(CASE WHEN d.status = 'hadir' THEN 1 ELSE 0 END) as total_hadir,
        SUM(CASE WHEN d.status = 'sakit' THEN 1 ELSE 0 END) as total_sakit,
        SUM(CASE WHEN d.status = 'izin' THEN 1 ELSE 0 END) as total_izin,
        SUM(CASE WHEN d.status = 'dispen' THEN 1 ELSE 0 END) as total_dispen,
        SUM(CASE WHEN d.status = 'alfa' THEN 1 ELSE 0 END) as total_alfa
    FROM absensi_harian a
    JOIN kelas k ON a.kelas_id = k.id
    JOIN detail_absensi d ON a.id = d.absensi_id
    JOIN validasi_qr v ON a.id = v.absensi_id
    WHERE a.tanggal = ? AND v.status = 'valid'
    GROUP BY a.id
    ORDER BY a.created_at DESC
    LIMIT 1
");
$stmt->execute([$tanggalHariIni]);
$absensiHariIni = $stmt->fetch();

// Total pending validasi (untuk sekretaris, tetap lihat semua pending)
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total 
    FROM validasi_qr v
    JOIN absensi_harian a ON v.absensi_id = a.id
    WHERE v.status = 'pending'
");
$stmt->execute();
$totalPendingValidasi = $stmt->fetch()['total'];

// Agenda terbaru
$stmt = $pdo->prepare("
    SELECT * FROM agenda 
    WHERE dibuat_oleh = ? 
    ORDER BY created_at DESC 
    LIMIT 5
");
$stmt->execute([$userId]);
$agendaTerbaru = $stmt->fetchAll();

// Aktivitas absensi terbaru (HANYA YANG SUDAH DIVALIDASI)
$stmt = $pdo->prepare("
    SELECT d.*, s.nama_lengkap, s.nis, a.tanggal, k.nama_kelas
    FROM detail_absensi d
    JOIN siswa s ON d.siswa_id = s.id
    JOIN absensi_harian a ON d.absensi_id = a.id
    JOIN kelas k ON a.kelas_id = k.id
    JOIN validasi_qr v ON a.id = v.absensi_id
    WHERE a.dibuat_oleh = ? AND v.status = 'valid'
    ORDER BY d.created_at DESC
    LIMIT 5
");
$stmt->execute([$userId]);
$aktivitasTerbaru = $stmt->fetchAll();

include '../includes/navbar.php';
include '../includes/sidebar.php';
?>

<!-- Google Fonts: DM Sans + Inter -->
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700;9..40,800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

<style>
    /* ── Design Tokens ── */
    :root {
        --amber:       #D4A017;
        --amber-hover: #B8880E;
        --amber-soft:  #FBF0CC;
        --amber-mid:   #E8C247;
        --dark:        #111827;
        --ink:         #1F2937;
        --muted:       #6B7280;
        --border:      #E5E7EB;
        --surface:     #FFFFFF;
        --canvas:      #F9FAFB;
        --r-sm: 10px;
        --r-md: 16px;
        --r-lg: 20px;
        --r-xl: 28px;
        --shadow-xs: 0 1px 3px rgba(0,0,0,.06);
        --shadow-sm: 0 2px 8px rgba(0,0,0,.07);
        --shadow-md: 0 6px 20px rgba(0,0,0,.09);
        --shadow-lg: 0 14px 36px rgba(0,0,0,.11);
    }

    body, .main-content {
        font-family: 'Inter', sans-serif;
        background: var(--canvas);
        color: var(--ink);
    }

    h1,h2,h3,h4,h5,h6,.dm {
        font-family: 'DM Sans', sans-serif;
    }

    /* ── Welcome Banner ── */
    .welcome-banner {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--r-xl);
        padding: 1.75rem 2rem;
        margin-bottom: 1.5rem;
        box-shadow: var(--shadow-xs);
        position: relative;
        overflow: hidden;
    }

    .welcome-banner::before {
        content: '';
        position: absolute;
        top: -40px; right: -40px;
        width: 180px; height: 180px;
        background: radial-gradient(circle, rgba(212,160,23,.12), transparent 65%);
        border-radius: 50%;
        pointer-events: none;
    }

    .welcome-banner h4 {
        font-family: 'DM Sans', sans-serif;
        font-weight: 700;
        font-size: 1.25rem;
        color: var(--dark);
        margin-bottom: .35rem;
    }

    .welcome-banner p {
        font-size: .875rem;
        color: var(--muted);
    }

    /* ── Stat Cards ── */
    .stat-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--r-lg);
        padding: 1.5rem;
        box-shadow: var(--shadow-xs);
        transition: transform .25s ease, box-shadow .25s ease, border-color .25s ease;
        position: relative;
        overflow: hidden;
    }

    .stat-card::after {
        content: '';
        position: absolute;
        bottom: 0; left: 0; right: 0;
        height: 3px;
        background: linear-gradient(90deg, var(--amber), var(--amber-mid));
        transform: scaleX(0);
        transform-origin: left;
        transition: transform .3s ease;
    }

    .stat-card:hover::after { transform: scaleX(1); }

    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: var(--shadow-md);
        border-color: rgba(212,160,23,.35);
    }

    .stat-icon {
        width: 46px; height: 46px;
        background: var(--amber-soft);
        border-radius: var(--r-sm);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 1rem;
    }

    .stat-icon i {
        font-size: 1.25rem;
        color: var(--amber-hover);
    }

    .stat-value {
        font-family: 'DM Sans', sans-serif;
        font-size: 2rem;
        font-weight: 800;
        color: var(--dark);
        letter-spacing: -.03em;
        line-height: 1;
        margin-bottom: .3rem;
    }

    .stat-label {
        font-size: .8rem;
        font-weight: 500;
        color: var(--muted);
        margin-bottom: .5rem;
    }

    .stat-trend {
        font-size: .72rem;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: .3rem;
    }

    /* ── Content Cards ── */
    .card-custom {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--r-xl);
        box-shadow: var(--shadow-xs);
        overflow: hidden;
    }

    .card-header-custom {
        padding: 1.1rem 1.5rem;
        border-bottom: 1px solid var(--border);
        background: var(--surface);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .card-header-custom h5 {
        font-family: 'DM Sans', sans-serif;
        font-weight: 700;
        font-size: .95rem;
        color: var(--dark);
        margin: 0;
        display: flex;
        align-items: center;
        gap: .5rem;
    }

    .card-header-custom h5 i { color: var(--amber); }

    /* ── Attendance Status Row ── */
    .attendance-grid {
        display: grid;
        grid-template-columns: repeat(6, 1fr);
        gap: .75rem;
        padding: 1.5rem;
    }

    @media (max-width: 768px) {
        .attendance-grid { grid-template-columns: repeat(3, 1fr); }
    }

    @media (max-width: 480px) {
        .attendance-grid { grid-template-columns: repeat(2, 1fr); }
    }

    .att-stat-box {
        background: var(--canvas);
        border: 1px solid var(--border);
        border-radius: var(--r-md);
        padding: 1rem .75rem;
        text-align: center;
        transition: transform .2s, box-shadow .2s;
    }

    .att-stat-box:hover {
        transform: translateY(-3px);
        box-shadow: var(--shadow-sm);
    }

    .att-stat-num {
        font-family: 'DM Sans', sans-serif;
        font-size: 1.75rem;
        font-weight: 800;
        line-height: 1;
        margin-bottom: .3rem;
    }

    .att-stat-lbl {
        font-size: .72rem;
        font-weight: 600;
        color: var(--muted);
        text-transform: uppercase;
        letter-spacing: .05em;
    }

    .att-hadir  .att-stat-num { color: #16A34A; }
    .att-sakit  .att-stat-num { color: #CA8A04; }
    .att-izin   .att-stat-num { color: #2563EB; }
    .att-dispen .att-stat-num { color: #7C3AED; }
    .att-alfa   .att-stat-num { color: #DC2626; }
    .att-total  .att-stat-num { color: var(--dark); }

    .att-hadir  { border-top: 3px solid #22C55E; }
    .att-sakit  { border-top: 3px solid #EAB308; }
    .att-izin   { border-top: 3px solid #3B82F6; }
    .att-dispen { border-top: 3px solid #8B5CF6; }
    .att-alfa   { border-top: 3px solid #EF4444; }
    .att-total  { border-top: 3px solid var(--amber); }

    /* ── Progress Bar ── */
    .progress-row {
        padding: 0 1.5rem 1.5rem;
    }

    .progress-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: .4rem;
        font-size: .78rem;
        color: var(--muted);
        font-weight: 500;
    }

    .progress-meta span:last-child {
        font-family: 'DM Sans', sans-serif;
        font-weight: 700;
        color: var(--amber-hover);
    }

    .progress-track {
        height: 7px;
        background: var(--border);
        border-radius: 99px;
        overflow: hidden;
    }

    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, var(--amber), var(--amber-mid));
        border-radius: 99px;
        transition: width .6s ease;
    }

    /* ── List Items ── */
    .list-group-item {
        border-left: none !important;
        border-right: none !important;
        border-color: var(--border) !important;
        padding: .85rem 1.5rem;
        transition: background .15s;
    }

    .list-group-item:first-child { border-top: none !important; }

    .list-group-item:hover { background: var(--canvas); }

    .list-item-title {
        font-family: 'DM Sans', sans-serif;
        font-size: .875rem;
        font-weight: 600;
        color: var(--dark);
        margin-bottom: .2rem;
    }

    .list-item-meta {
        font-size: .75rem;
        color: var(--muted);
        display: flex;
        align-items: center;
        gap: .5rem;
        flex-wrap: wrap;
    }

    .list-item-meta i { color: var(--amber); font-size: .7rem; }

    .date-badge {
        font-size: .7rem;
        font-weight: 600;
        background: var(--amber-soft);
        color: var(--amber-hover);
        padding: .2rem .6rem;
        border-radius: 50px;
        white-space: nowrap;
    }

    /* ── Status Badges ── */
    .badge-hadir  { background: #DCFCE7; color: #15803D; }
    .badge-sakit  { background: #FEF9C3; color: #A16207; }
    .badge-izin   { background: #DBEAFE; color: #1D4ED8; }
    .badge-dispen { background: #EDE9FE; color: #6D28D9; }
    .badge-alfa   { background: #FEE2E2; color: #B91C1C; }

    .status-pill {
        font-size: .65rem;
        font-weight: 700;
        padding: .2rem .6rem;
        border-radius: 50px;
        text-transform: capitalize;
    }

    /* ── Quick Actions ── */
    .quick-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: .75rem;
        padding: 1.25rem 1.5rem;
    }

    @media (max-width: 768px) { .quick-grid { grid-template-columns: repeat(2, 1fr); } }

    .quick-action {
        background: var(--canvas);
        border: 1px solid var(--border);
        border-radius: var(--r-md);
        padding: 1.25rem 1rem;
        text-decoration: none;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: .6rem;
        color: var(--ink);
        transition: all .25s ease;
    }

    .quick-action:hover {
        background: var(--amber-soft);
        border-color: rgba(212,160,23,.4);
        color: var(--dark);
        transform: translateY(-3px);
        box-shadow: var(--shadow-sm);
    }

    .quick-action i {
        font-size: 1.4rem;
        color: var(--amber-hover);
    }

    .quick-action span {
        font-size: .78rem;
        font-weight: 600;
        text-align: center;
    }

    /* ── Buttons ── */
    .btn-primary-custom {
        font-family: 'DM Sans', sans-serif;
        font-weight: 700;
        font-size: .83rem;
        background: var(--amber);
        color: var(--dark);
        border: none;
        padding: .55rem 1.25rem;
        border-radius: var(--r-sm);
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        transition: background .2s, transform .2s, box-shadow .2s;
    }

    .btn-primary-custom:hover {
        background: var(--amber-hover);
        color: var(--dark);
        transform: translateY(-1px);
        box-shadow: 0 4px 14px rgba(212,160,23,.3);
    }

    .btn-outline-custom {
        font-family: 'DM Sans', sans-serif;
        font-weight: 600;
        font-size: .75rem;
        background: transparent;
        border: 1.5px solid var(--amber);
        color: var(--amber-hover);
        padding: .3rem .85rem;
        border-radius: var(--r-sm);
        text-decoration: none;
        transition: all .2s;
        display: inline-flex;
        align-items: center;
    }

    .btn-outline-custom:hover {
        background: var(--amber);
        color: var(--dark);
    }

    /* ── Empty State ── */
    .empty-state {
        text-align: center;
        padding: 3rem 1.5rem;
    }

    .empty-state i {
        font-size: 2.5rem;
        color: var(--border);
        margin-bottom: .75rem;
        display: block;
    }

    .empty-state p {
        font-size: .85rem;
        color: var(--muted);
        margin-bottom: 1rem;
    }

    /* ── Section spacing ── */
    .content-area { padding: 1.5rem; }

    .section-gap { margin-top: 1.5rem; }
</style>

<div class="main-content">
    <div class="content-area">

        <!-- Welcome Banner -->
        <div class="welcome-banner">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h4>Selamat Datang, <?php echo htmlspecialchars($_SESSION['nama']); ?>!</h4>
                    <p class="mb-0">
                        <i class="fas fa-calendar-day me-1" style="color:var(--amber);"></i>
                        <?php echo formatTanggal($tanggalHariIni, 'd F Y'); ?>
                    </p>
                </div>
                <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                    <a href="absensi.php" class="btn-primary-custom">
                        <i class="fas fa-fingerprint"></i>Buat Absensi Hari Ini
                    </a>
                </div>
            </div>
        </div>

        <!-- Stat Cards -->
        <div class="row g-3">
            <div class="col-md-3 col-sm-6" data-aos="fade-up" data-aos-delay="60">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-calendar-alt"></i></div>
                    <div class="stat-value"><?php echo $totalAgendaBulanIni; ?></div>
                    <div class="stat-label">Agenda Bulan Ini</div>
                    <div class="stat-trend text-success">
                        <i class="fas fa-chart-line"></i> Total <?php echo $totalAgenda; ?> agenda
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6" data-aos="fade-up" data-aos-delay="120">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                    <div class="stat-value"><?php echo $absensiHariIni ? $absensiHariIni['total_siswa'] : 0; ?></div>
                    <div class="stat-label">Total Siswa</div>
                    <div class="stat-trend text-info">
                        <i class="fas fa-calendar-day"></i> Absensi <?php echo formatTanggal($tanggalHariIni, 'd/m/Y'); ?>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6" data-aos="fade-up" data-aos-delay="180">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-value">
                        <?php
                        $persenHadir = $absensiHariIni && $absensiHariIni['total_siswa'] > 0
                            ? round(($absensiHariIni['total_hadir'] / $absensiHariIni['total_siswa']) * 100)
                            : 0;
                        echo $persenHadir; ?>%
                    </div>
                    <div class="stat-label">Tingkat Kehadiran</div>
                    <div class="stat-trend text-success">
                        <i class="fas fa-arrow-up"></i> <?php echo $persenHadir; ?>% hadir hari ini
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6" data-aos="fade-up" data-aos-delay="240">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-qrcode"></i></div>
                    <div class="stat-value"><?php echo $totalPendingValidasi; ?></div>
                    <div class="stat-label">Pending Validasi</div>
                    <div class="stat-trend text-warning">
                        <i class="fas fa-clock"></i> Menunggu validasi guru
                    </div>
                </div>
            </div>
        </div>

        <!-- Attendance Status Today -->
        <?php if ($absensiHariIni): ?>
        <div class="section-gap" data-aos="fade-up">
            <div class="card-custom">
                <div class="card-header-custom">
                    <h5><i class="fas fa-chart-pie"></i> Status Kehadiran — <?php echo formatTanggal($tanggalHariIni, 'd F Y'); ?></h5>
                    <span style="font-size:.78rem;color:var(--muted);">Kelas: <?php echo htmlspecialchars($absensiHariIni['nama_kelas']); ?></span>
                </div>
                <div class="attendance-grid">
                    <div class="att-stat-box att-hadir">
                        <div class="att-stat-num"><?php echo $absensiHariIni['total_hadir']; ?></div>
                        <div class="att-stat-lbl">Hadir</div>
                    </div>
                    <div class="att-stat-box att-sakit">
                        <div class="att-stat-num"><?php echo $absensiHariIni['total_sakit']; ?></div>
                        <div class="att-stat-lbl">Sakit</div>
                    </div>
                    <div class="att-stat-box att-izin">
                        <div class="att-stat-num"><?php echo $absensiHariIni['total_izin']; ?></div>
                        <div class="att-stat-lbl">Izin</div>
                    </div>
                    <div class="att-stat-box att-dispen">
                        <div class="att-stat-num"><?php echo $absensiHariIni['total_dispen']; ?></div>
                        <div class="att-stat-lbl">Dispen</div>
                    </div>
                    <div class="att-stat-box att-alfa">
                        <div class="att-stat-num"><?php echo $absensiHariIni['total_alfa']; ?></div>
                        <div class="att-stat-lbl">Alfa</div>
                    </div>
                    <div class="att-stat-box att-total">
                        <div class="att-stat-num"><?php echo $absensiHariIni['total_siswa']; ?></div>
                        <div class="att-stat-lbl">Total</div>
                    </div>
                </div>
                <div class="progress-row">
                    <div class="progress-meta">
                        <span>Tingkat Kehadiran Hari Ini</span>
                        <span><?php echo $persenHadir; ?>%</span>
                    </div>
                    <div class="progress-track">
                        <div class="progress-fill" style="width: <?php echo $persenHadir; ?>%;"></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Agenda Terbaru & Aktivitas Terbaru -->
        <div class="row g-3 section-gap">
            <!-- Agenda -->
            <div class="col-lg-6" data-aos="fade-up" data-aos-delay="60">
                <div class="card-custom" style="height:100%;">
                    <div class="card-header-custom">
                        <h5><i class="fas fa-clipboard-list"></i> Agenda Terbaru</h5>
                        <a href="agenda.php" class="btn-outline-custom">Lihat Semua</a>
                    </div>
                    <div class="card-body p-0">
                        <?php if (count($agendaTerbaru) > 0): ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($agendaTerbaru as $agenda): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="list-item-title"><?php echo htmlspecialchars($agenda['judul']); ?></div>
                                        <div class="list-item-meta">
                                            <i class="far fa-calendar-alt"></i>
                                            <?php echo formatTanggal($agenda['tanggal'], 'd F Y'); ?>
                                        </div>
                                    </div>
                                    <span class="date-badge"><?php echo formatTanggal($agenda['created_at'], 'd/m/Y'); ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-calendar-times"></i>
                                <p>Belum ada agenda yang dibuat.</p>
                                <a href="agenda_tambah.php" class="btn-primary-custom">
                                    </i> Buat Agenda
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Aktivitas Absensi -->
            <div class="col-lg-6" data-aos="fade-up" data-aos-delay="120">
                <div class="card-custom" style="height:100%;">
                    <div class="card-header-custom">
                        <h5><i class="fas fa-history"></i> Aktivitas Absensi Terbaru</h5>
                        <a href="absensi.php" class="btn-outline-custom">Lihat Semua</a>
                    </div>
                    <div class="card-body p-0">
                        <?php if (count($aktivitasTerbaru) > 0): ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($aktivitasTerbaru as $aktivitas): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <div class="list-item-title d-flex align-items-center gap-2">
                                                <?php echo htmlspecialchars($aktivitas['nama_lengkap']); ?>
                                                <span class="status-pill badge-<?php echo $aktivitas['status']; ?>">
                                                    <?php echo ucfirst($aktivitas['status']); ?>
                                                </span>
                                            </div>
                                            <div class="list-item-meta">
                                                <span style="color:var(--muted);"><?php echo $aktivitas['nis']; ?></span>
                                                <i class="fas fa-circle" style="font-size:.35rem;"></i>
                                                <i class="fas fa-calendar-day"></i>
                                                <?php echo formatTanggal($aktivitas['tanggal'], 'd F Y'); ?>
                                                <i class="fas fa-circle" style="font-size:.35rem;"></i>
                                                <i class="fas fa-building"></i>
                                                <?php echo htmlspecialchars($aktivitas['nama_kelas']); ?>
                                            </div>
                                        </div>
                                        <span style="font-size:.7rem;color:var(--muted);white-space:nowrap;padding-left:.5rem;">
                                            <?php echo formatWaktu($aktivitas['created_at']); ?>
                                        </span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-user-check"></i>
                                <p>Belum ada aktivitas absensi tervalidasi.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="section-gap" data-aos="fade-up">
            <div class="card-custom">
                <div class="card-header-custom">
                    <h5><i class="fas fa-bolt"></i> Akses Cepat</h5>
                </div>
                <div class="quick-grid">
                    <a href="agenda_tambah.php" class="quick-action">
                        <i class="fas fa-plus-circle"></i>
                        <span>Tambah Agenda</span>
                    </a>
                    <a href="absensi.php" class="quick-action">
                        <i class="fas fa-fingerprint"></i>
                        <span>Kelola Absensi</span>
                    </a>
                    <a href="riwayat_validasi.php" class="quick-action">
                        <i class="fas fa-qrcode"></i>
                        <span>Riwayat Validasi</span>
                    </a>
                    <a href="profile.php" class="quick-action">
                        <i class="fas fa-user-cog"></i>
                        <span>Profil Saya</span>
                    </a>
                </div>
            </div>
        </div>

    </div>
</div>

<?php include '../includes/footer.php'; ?>