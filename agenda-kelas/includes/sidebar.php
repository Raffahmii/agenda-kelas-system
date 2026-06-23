<?php
/**
 * Sidebar Component - Agenda Kelas (Redesign Premium)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$current_page = basename($_SERVER['PHP_SELF']);
$user_role    = $_SESSION['role'] ?? '';
$user_name    = $_SESSION['nama'] ?? 'User';

$role_label_map = [
    'siswa'      => 'Siswa',
    'sekretaris' => 'Sekretaris',
    'guru'       => 'Guru',
    'walikelas'  => 'Wali Kelas',
];
$role_label = $role_label_map[$user_role] ?? ucfirst($user_role);
?>

<aside class="sidebar" id="sidebar">

    <!-- Logo -->
    <div class="sidebar-logo">
        <div class="sidebar-logo-icon">
            <i class="fas fa-calendar-alt"></i>
        </div>
        <span class="sidebar-logo-text">Agenda<span>Kelas</span></span>
    </div>

    <!-- User card -->
    <div class="sidebar-usercard">
        <div class="sidebar-user-avatar">
            <?php echo mb_strtoupper(mb_substr($user_name, 0, 1)); ?>
        </div>
        <div class="sidebar-user-info">
            <div class="sidebar-user-name"><?php echo htmlspecialchars($user_name); ?></div>
            <span class="sidebar-user-role"><?php echo htmlspecialchars($role_label); ?></span>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="sidebar-nav">

        <a href="dashboard.php" class="snav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt"></i>
            <span>Dashboard</span>
        </a>

        <?php if ($user_role == 'siswa'): ?>
            <div class="snav-divider">Kelas</div>
            <a href="agenda.php" class="snav-link <?php echo $current_page == 'agenda.php' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-week"></i>
                <span>Agenda Kelas</span>
            </a>
            <a href="riwayat_kehadiran.php" class="snav-link <?php echo $current_page == 'riwayat_kehadiran.php' ? 'active' : ''; ?>">
                <i class="fas fa-history"></i>
                <span>Riwayat Kehadiran</span>
            </a>

        <?php elseif ($user_role == 'sekretaris'): ?>
            <div class="snav-divider">Kelola Kelas</div>
            <a href="agenda.php" class="snav-link <?php echo in_array($current_page, ['agenda.php','agenda_tambah.php','agenda_edit.php']) ? 'active' : ''; ?>">
                <i class="fas fa-clipboard-list"></i>
                <span>Kelola Agenda</span>
            </a>
            <a href="absensi.php" class="snav-link <?php echo $current_page == 'absensi.php' ? 'active' : ''; ?>">
                <i class="fas fa-fingerprint"></i>
                <span>Kelola Kehadiran</span>
            </a>
            <div class="snav-divider">Validasi</div>
            <a href="generate_qr.php" class="snav-link <?php echo $current_page == 'generate_qr.php' ? 'active' : ''; ?>">
                <i class="fas fa-qrcode"></i>
                <span>Generate QR Code</span>
            </a>
            <a href="riwayat_validasi.php" class="snav-link <?php echo $current_page == 'riwayat_validasi.php' ? 'active' : ''; ?>">
                <i class="fas fa-history"></i>
                <span>Riwayat Validasi</span>
            </a>

        <?php elseif ($user_role == 'guru'): ?>
            <div class="snav-divider">Validasi</div>
            <a href="scan_qr.php" class="snav-link <?php echo $current_page == 'scan_qr.php' ? 'active' : ''; ?>">
                <i class="fas fa-camera"></i>
                <span>Scan QR Code</span>
            </a>
            <a href="validasi.php" class="snav-link <?php echo $current_page == 'validasi.php' ? 'active' : ''; ?>">
                <i class="fas fa-check-double"></i>
                <span>Validasi Kehadiran</span>
            </a>
            <a href="riwayat_validasi.php" class="snav-link <?php echo $current_page == 'riwayat_validasi.php' ? 'active' : ''; ?>">
                <i class="fas fa-history"></i>
                <span>Riwayat Validasi</span>
            </a>

        <?php elseif ($user_role == 'walikelas'): ?>
            <div class="snav-divider">Monitoring</div>
            <a href="monitoring.php" class="snav-link <?php echo $current_page == 'monitoring.php' ? 'active' : ''; ?>">
                <i class="fas fa-eye"></i>
                <span>Monitoring Kehadiran</span>
            </a>
            <a href="riwayat_kehadiran.php" class="snav-link <?php echo $current_page == 'riwayat_kehadiran.php' ? 'active' : ''; ?>">
                <i class="fas fa-history"></i>
                <span>Riwayat Kehadiran</span>
            </a>
            <div class="snav-divider">Laporan</div>
            <a href="statistik.php" class="snav-link <?php echo $current_page == 'statistik.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-bar"></i>
                <span>Statistik</span>
            </a>
            <a href="export.php" class="snav-link <?php echo $current_page == 'export.php' ? 'active' : ''; ?>">
                <i class="fas fa-download"></i>
                <span>Export Laporan</span>
            </a>
        <?php endif; ?>

        <div class="snav-divider">Akun</div>
        <a href="profile.php" class="snav-link <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
            <i class="fas fa-user-circle"></i>
            <span>Profil Saya</span>
        </a>

    </nav>

    <!-- Logout button at bottom -->
    <div class="sidebar-footer">
        <a href="../logout.php" class="snav-link snav-logout">
            <i class="fas fa-arrow-right-from-bracket"></i>
            <span>Keluar</span>
        </a>
    </div>

</aside>

<style>
    /* ── Sidebar tokens (in sync with navbar) ── */
    :root {
        --sb-w:          280px;
        --sb-bg:         #111827;
        --sb-border:     rgba(255,255,255,.07);
        --sb-text:       rgba(255,255,255,.55);
        --sb-text-hover: #ffffff;
        --sb-amber:      #D4A017;
        --sb-amber-soft: rgba(212,160,23,.12);
        --sb-amber-glow: rgba(212,160,23,.22);
        --sb-r:          10px;
    }

    /* ── Base ── */
    .sidebar {
        position: fixed;
        top: 0; left: 0;
        width: var(--sb-w);
        height: 100vh;
        background: var(--sb-bg);
        z-index: 100;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        transition: left .3s ease;
        font-family: 'DM Sans', 'Inter', sans-serif;
    }

    /* hide scrollbar on nav */
    .sidebar-nav::-webkit-scrollbar { width: 0; }
    .sidebar-nav { scrollbar-width: none; }

    /* ── Logo ── */
    .sidebar-logo {
        display: flex;
        align-items: center;
        gap: .75rem;
        padding: 1.5rem 1.4rem 1.25rem;
        border-bottom: 1px solid var(--sb-border);
        flex-shrink: 0;
    }

    .sidebar-logo-icon {
        width: 36px; height: 36px;
        background: var(--sb-amber-soft);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .sidebar-logo-icon i {
        font-size: 1.1rem;
        color: var(--sb-amber);
    }

    .sidebar-logo-text {
        font-family: 'DM Sans', sans-serif;
        font-size: 1.2rem;
        font-weight: 800;
        color: #fff;
        letter-spacing: -.02em;
    }

    .sidebar-logo-text span { color: var(--sb-amber); }

    /* ── User card ── */
    .sidebar-usercard {
        display: flex;
        align-items: center;
        gap: .75rem;
        padding: 1rem 1.4rem;
        border-bottom: 1px solid var(--sb-border);
        flex-shrink: 0;
    }

    .sidebar-user-avatar {
        width: 38px; height: 38px;
        background: var(--sb-amber-soft);
        border: 1.5px solid rgba(212,160,23,.35);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: 'DM Sans', sans-serif;
        font-size: .9rem;
        font-weight: 800;
        color: var(--sb-amber);
        flex-shrink: 0;
    }

    .sidebar-user-name {
        font-family: 'DM Sans', sans-serif;
        font-size: .85rem;
        font-weight: 700;
        color: #fff;
        line-height: 1.2;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 160px;
    }

    .sidebar-user-role {
        display: inline-block;
        font-size: .62rem;
        font-weight: 700;
        letter-spacing: .05em;
        text-transform: uppercase;
        background: var(--sb-amber-soft);
        color: var(--sb-amber);
        padding: .1rem .5rem;
        border-radius: 50px;
        margin-top: .15rem;
    }

    /* ── Nav ── */
    .sidebar-nav {
        flex: 1;
        overflow-y: auto;
        padding: .85rem 0 1rem;
    }

    .snav-divider {
        padding: .9rem 1.4rem .3rem;
        font-size: .63rem;
        font-weight: 700;
        letter-spacing: .1em;
        text-transform: uppercase;
        color: rgba(255,255,255,.25);
    }

    .snav-link {
        display: flex;
        align-items: center;
        gap: .7rem;
        padding: .6rem 1.4rem;
        color: var(--sb-text);
        text-decoration: none;
        font-size: .85rem;
        font-weight: 500;
        border-radius: 0;
        transition: background .18s, color .18s;
        position: relative;
        margin: .05rem 0;
    }

    .snav-link i {
        width: 18px;
        font-size: .9rem;
        text-align: center;
        flex-shrink: 0;
        transition: color .18s;
    }

    .snav-link:hover {
        background: var(--sb-amber-soft);
        color: var(--sb-text-hover);
    }

    .snav-link:hover i { color: var(--sb-amber); }

    .snav-link.active {
        background: var(--sb-amber-glow);
        color: #fff;
        font-weight: 600;
    }

    .snav-link.active i { color: var(--sb-amber); }

    /* active left indicator */
    .snav-link.active::before {
        content: '';
        position: absolute;
        left: 0; top: 50%;
        transform: translateY(-50%);
        width: 3px;
        height: 60%;
        background: var(--sb-amber);
        border-radius: 0 3px 3px 0;
    }

    /* ── Footer / Logout ── */
    .sidebar-footer {
        border-top: 1px solid var(--sb-border);
        padding: .6rem 0;
        flex-shrink: 0;
    }

    .snav-logout {
        color: rgba(255,255,255,.4) !important;
    }

    .snav-logout:hover {
        background: rgba(239,68,68,.1) !important;
        color: #FCA5A5 !important;
    }

    .snav-logout:hover i { color: #EF4444 !important; }

    /* ── Responsive ── */
    @media (max-width: 992px) {
        .sidebar { left: calc(-1 * var(--sb-w)); }
        .sidebar.active { left: 0; box-shadow: 8px 0 32px rgba(0,0,0,.3); }
    }
</style>

<script>
    const sidebarToggleBtn = document.getElementById('sidebarToggle');
    const sidebarEl = document.querySelector('.sidebar');
    if (sidebarToggleBtn && sidebarEl) {
        sidebarToggleBtn.addEventListener('click', () => {
            sidebarEl.classList.toggle('active');
        });
    }
</script>