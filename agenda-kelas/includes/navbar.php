<?php
/**
 * Navbar Component - Agenda Kelas (Redesign Premium)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user_name = $_SESSION['nama'] ?? 'User';
$user_role = $_SESSION['role'] ?? '';

$current_file = basename($_SERVER['PHP_SELF']);
$page_title = 'Dashboard';
$page_subtitle = 'Ringkasan aktivitas hari ini';

$page_mapping = [
    'dashboard.php'          => ['Dashboard',             'Ringkasan aktivitas dan statistik',       'fas fa-tachometer-alt'],
    'agenda.php'             => ['Kelola Agenda',          'Tambah, edit, dan hapus agenda kelas',    'fas fa-clipboard-list'],
    'agenda_tambah.php'      => ['Tambah Agenda',          'Buat agenda kelas baru',                  'fas fa-plus-circle'],
    'agenda_edit.php'        => ['Edit Agenda',            'Ubah data agenda kelas',                  'fas fa-edit'],
    'absensi.php'            => ['Kelola Kehadiran',       'Input dan kelola absensi harian siswa',   'fas fa-fingerprint'],
    'generate_qr.php'        => ['Generate QR Code',       'Buat QR Code untuk validasi',             'fas fa-qrcode'],
    'riwayat_validasi.php'   => ['Riwayat Validasi',       'Lihat history validasi QR',               'fas fa-history'],
    'profile.php'            => ['Profil Saya',            'Kelola informasi profil Anda',            'fas fa-user-circle'],
    'riwayat_kehadiran.php'  => ['Riwayat Kehadiran',      'Lihat history kehadiran Anda',            'fas fa-history'],
    'scan_qr.php'            => ['Scan QR Code',           'Scan QR untuk validasi kehadiran',        'fas fa-camera'],
    'validasi.php'           => ['Validasi Kehadiran',     'Validasi data kehadiran siswa',           'fas fa-check-double'],
    'monitoring.php'         => ['Monitoring Kehadiran',   'Pantau kehadiran siswa',                  'fas fa-eye'],
    'statistik.php'          => ['Statistik Kehadiran',    'Grafik dan analisis kehadiran',           'fas fa-chart-bar'],
    'export.php'             => ['Export Laporan',         'Export data ke PDF/Excel',                'fas fa-download'],
];

$page_icon = 'fas fa-tachometer-alt';
if (isset($page_mapping[$current_file])) {
    $page_title    = $page_mapping[$current_file][0];
    $page_subtitle = $page_mapping[$current_file][1];
    $page_icon     = $page_mapping[$current_file][2];
}

$default_avatar = '../assets/img/avatar.png';
if (!file_exists($default_avatar)) {
    $default_avatar = 'https://ui-avatars.com/api/?background=D4A017&color=111827&bold=true&name=' . urlencode($user_name);
}

$role_label_map = [
    'siswa'       => 'Siswa',
    'sekretaris'  => 'Sekretaris',
    'guru'        => 'Guru',
    'walikelas'   => 'Wali Kelas',
];
$role_label = $role_label_map[$user_role] ?? ucfirst($user_role);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> — AgendaKelas</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts: DM Sans + Inter -->
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700;9..40,800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- AOS -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        /* ── Tokens ── */
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
            --sidebar-w:   280px;
            --topbar-h:    82px;
            --r-sm:  10px;
            --r-md:  16px;
            --r-lg:  20px;
            --r-xl:  28px;
            --shadow-xs: 0 1px 3px rgba(0,0,0,.06);
            --shadow-sm: 0 2px 8px rgba(0,0,0,.07);
            --shadow-md: 0 6px 20px rgba(0,0,0,.09);
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--canvas);
            color: var(--ink);
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }

        h1,h2,h3,h4,h5,h6 { font-family: 'DM Sans', sans-serif; }

        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: var(--canvas); }
        ::-webkit-scrollbar-thumb { background: var(--amber); border-radius: 6px; }

        /* ══════════════════════════════
           TOPBAR
        ══════════════════════════════ */
        .topbar {
            position: fixed;
            top: 0;
            right: 0;
            left: var(--sidebar-w);
            height: var(--topbar-h);
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.75rem;
            z-index: 99;
            transition: left .3s ease;
        }

        /* Left: breadcrumb + page title */
        .topbar-left {
            display: flex;
            align-items: center;
            gap: 1rem;
            min-width: 0;
        }

        .topbar-icon {
            width: 42px;
            height: 42px;
            background: var(--amber-soft);
            border-radius: var(--r-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .topbar-icon i {
            font-size: 1.15rem;
            color: var(--amber-hover);
        }

        .topbar-titles { min-width: 0; }

        .topbar-title {
            font-family: 'DM Sans', sans-serif;
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--dark);
            line-height: 1.2;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .topbar-sub {
            font-size: .72rem;
            color: var(--muted);
            margin-top: .1rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Right: profile area */
        .topbar-right {
            display: flex;
            align-items: center;
            gap: .85rem;
            flex-shrink: 0;
        }

        /* Date chip */
        .date-chip {
            display: flex;
            align-items: center;
            gap: .45rem;
            background: var(--canvas);
            border: 1px solid var(--border);
            border-radius: 50px;
            padding: .35rem .85rem;
            font-size: .73rem;
            font-weight: 600;
            color: var(--muted);
            font-family: 'DM Sans', sans-serif;
            white-space: nowrap;
        }

        .date-chip i { color: var(--amber); font-size: .7rem; }

        /* Profile trigger */
        .profile-trigger {
            display: flex;
            align-items: center;
            gap: .65rem;
            cursor: pointer;
            position: relative;
            padding: .35rem .65rem .35rem .35rem;
            border-radius: 50px;
            border: 1px solid var(--border);
            background: var(--surface);
            transition: border-color .2s, box-shadow .2s;
            user-select: none;
        }

        .profile-trigger:hover {
            border-color: rgba(212,160,23,.5);
            box-shadow: 0 0 0 3px rgba(212,160,23,.1);
        }

        .profile-avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            object-fit: cover;
            background: var(--amber-soft);
            flex-shrink: 0;
        }

        .profile-meta { line-height: 1.2; }

        .profile-name {
            font-family: 'DM Sans', sans-serif;
            font-size: .82rem;
            font-weight: 700;
            color: var(--dark);
        }

        .profile-role-tag {
            display: inline-block;
            font-size: .62rem;
            font-weight: 700;
            letter-spacing: .05em;
            text-transform: uppercase;
            background: var(--amber-soft);
            color: var(--amber-hover);
            padding: .08rem .45rem;
            border-radius: 50px;
        }

        .profile-chevron {
            font-size: .65rem;
            color: var(--muted);
            transition: transform .25s;
        }

        /* Dropdown */
        .profile-dropdown {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--r-lg);
            box-shadow: var(--shadow-md);
            min-width: 210px;
            padding: .5rem;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-6px);
            transition: opacity .2s ease, transform .2s ease, visibility .2s;
            z-index: 200;
        }

        .profile-trigger:hover .profile-dropdown,
        .profile-trigger:focus-within .profile-dropdown {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .profile-trigger:hover .profile-chevron {
            transform: rotate(180deg);
        }

        /* Dropdown header */
        .dd-header {
            display: flex;
            align-items: center;
            gap: .65rem;
            padding: .75rem .85rem;
            background: var(--canvas);
            border-radius: var(--r-sm);
            margin-bottom: .35rem;
        }

        .dd-header img {
            width: 38px; height: 38px;
            border-radius: 50%;
            object-fit: cover;
        }

        .dd-header-name {
            font-family: 'DM Sans', sans-serif;
            font-size: .83rem;
            font-weight: 700;
            color: var(--dark);
            line-height: 1.2;
        }

        .dd-header-role {
            font-size: .68rem;
            color: var(--muted);
        }

        .dd-item {
            display: flex;
            align-items: center;
            gap: .6rem;
            padding: .6rem .85rem;
            border-radius: var(--r-sm);
            color: var(--ink);
            text-decoration: none;
            font-size: .82rem;
            font-weight: 500;
            transition: background .15s, color .15s;
        }

        .dd-item i {
            width: 16px;
            font-size: .8rem;
            color: var(--muted);
            transition: color .15s;
            text-align: center;
        }

        .dd-item:hover {
            background: var(--amber-soft);
            color: var(--dark);
        }

        .dd-item:hover i { color: var(--amber-hover); }

        .dd-item.danger { color: #DC2626; }
        .dd-item.danger i { color: #FCA5A5; }
        .dd-item.danger:hover { background: #FEE2E2; color: #B91C1C; }
        .dd-item.danger:hover i { color: #EF4444; }

        .dd-divider {
            height: 1px;
            background: var(--border);
            margin: .35rem 0;
        }

        /* ══════════════════════════════
           MAIN WRAPPER
        ══════════════════════════════ */
        .main-wrapper {
            margin-left: var(--sidebar-w);
            padding-top: var(--topbar-h);
            min-height: 100vh;
            transition: margin-left .3s ease;
        }

        .content-container {
            padding: 1.75rem;
        }

        /* ══════════════════════════════
           RESPONSIVE
        ══════════════════════════════ */
        @media (max-width: 992px) {
            .topbar { left: 0; }
            .main-wrapper { margin-left: 0; }
            .date-chip { display: none; }
        }

        @media (max-width: 576px) {
            .topbar { padding: 0 1rem; }
            .topbar-sub { display: none; }
            .profile-meta { display: none; }
            .profile-chevron { display: none; }
            .profile-trigger { padding: .3rem; border: none; background: transparent; box-shadow: none; }
            .content-container { padding: 1rem; }
        }
    </style>
</head>
<body>

<!-- ══════════ TOPBAR ══════════ -->
<div class="topbar" id="topbar">

    <!-- Left: page identity -->
    <div class="topbar-left">
        <div class="topbar-icon">
            <i class="<?php echo $page_icon; ?>"></i>
        </div>
        <div class="topbar-titles">
            <div class="topbar-title"><?php echo htmlspecialchars($page_title); ?></div>
            <div class="topbar-sub"><?php echo htmlspecialchars($page_subtitle); ?></div>
        </div>
    </div>

    <!-- Right: date + profile -->
    <div class="topbar-right">

        <!-- Date chip -->
        <div class="date-chip">
            <i class="fas fa-calendar-day"></i>
            <span id="topbar-date"></span>
        </div>

        <!-- Profile trigger + dropdown -->
        <div class="profile-trigger" tabindex="0">
            <img src="<?php echo $default_avatar; ?>" alt="Avatar" class="profile-avatar">
            <div class="profile-meta">
                <div class="profile-name"><?php echo htmlspecialchars($user_name); ?></div>
                <span class="profile-role-tag"><?php echo htmlspecialchars($role_label); ?></span>
            </div>
            <i class="fas fa-chevron-down profile-chevron"></i>

            <!-- Dropdown -->
            <div class="profile-dropdown">
                <div class="dd-header">
                    <img src="<?php echo $default_avatar; ?>" alt="Avatar">
                    <div>
                        <div class="dd-header-name"><?php echo htmlspecialchars($user_name); ?></div>
                        <div class="dd-header-role"><?php echo htmlspecialchars($role_label); ?></div>
                    </div>
                </div>

                <a href="dashboard.php" class="dd-item">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
                <a href="profile.php" class="dd-item">
                    <i class="fas fa-user"></i>
                    Profil Saya
                </a>

                <div class="dd-divider"></div>

                <a href="../logout.php" class="dd-item danger">
                    <i class="fas fa-arrow-right-from-bracket"></i>
                    Keluar
                </a>
            </div>
        </div>

    </div>
</div>

<!-- Main wrapper opened here — closed by footer.php -->
<div class="main-wrapper">
    <div class="content-container">

<script>
    // Live date chip
    (function () {
        const el = document.getElementById('topbar-date');
        if (!el) return;
        const days = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
        const months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
        function tick() {
            const d = new Date();
            el.textContent = days[d.getDay()] + ', ' + d.getDate() + ' ' + months[d.getMonth()] + ' ' + d.getFullYear();
        }
        tick();
        setInterval(tick, 60000);
    })();
</script>

<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>AOS.init({ duration: 600, once: true, offset: 40 });</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>