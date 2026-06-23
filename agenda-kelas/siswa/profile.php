<?php
/**
 * Profile Page - Desain Anti Mainstream
 * Tetap elegan dengan warna #FFD65A sebagai aksen utama
 */

require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

checkRoleAccess(['siswa', 'sekretaris', 'guru', 'walikelas']);

$user_id = $_SESSION['id'];
$user_role = $_SESSION['role'];
$success = '';
$error = '';

// Ambil data user
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Ambil data siswa
$siswa = null;
if ($user_role == 'siswa') {
    $stmt = $pdo->prepare("SELECT * FROM siswa WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $siswa = $stmt->fetch();
}

// Proses Update Profile
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profile'])) {
        $nama = trim($_POST['nama']);
        $email = trim($_POST['email']);
        
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $check->execute([$email, $user_id]);
        if ($check->fetch()) {
            $error = 'Email sudah digunakan!';
        } else {
            $update = $pdo->prepare("UPDATE users SET nama = ?, email = ? WHERE id = ?");
            if ($update->execute([$nama, $email, $user_id])) {
                $_SESSION['nama'] = $nama;
                $_SESSION['email'] = $email;
                $success = 'Profil berhasil diperbarui!';
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch();
            } else {
                $error = 'Gagal memperbarui profil!';
            }
        }
    }
    
    // Update Password
    elseif (isset($_POST['update_password'])) {
        $current = $_POST['current_password'];
        $new = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];
        
        if ($current != $user['password']) {
            $error = 'Password lama salah!';
        } elseif (strlen($new) < 4) {
            $error = 'Password minimal 4 karakter!';
        } elseif ($new != $confirm) {
            $error = 'Konfirmasi password tidak cocok!';
        } else {
            $update = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            if ($update->execute([$new, $user_id])) {
                $success = 'Password berhasil diubah!';
            } else {
                $error = 'Gagal mengubah password!';
            }
        }
    }
    
    // Upload Foto
    elseif (isset($_POST['upload_foto']) && isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
        $target_dir = "../uploads/profile/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        
        $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (!in_array($ext, $allowed)) {
            $error = 'Format tidak diizinkan! (JPG, PNG, GIF)';
        } elseif ($_FILES['foto']['size'] > 2 * 1024 * 1024) {
            $error = 'Max 2MB!';
        } else {
            // Hapus foto lama
            if ($siswa && !empty($siswa['foto']) && file_exists("../" . $siswa['foto'])) {
                unlink("../" . $siswa['foto']);
            }
            
            $new_name = time() . '_' . $user_id . '.' . $ext;
            $target_file = $target_dir . $new_name;
            $db_path = "uploads/profile/" . $new_name;
            
            if (move_uploaded_file($_FILES['foto']['tmp_name'], $target_file)) {
                if ($user_role == 'siswa') {
                    $update = $pdo->prepare("UPDATE siswa SET foto = ? WHERE user_id = ?");
                    $update->execute([$db_path, $user_id]);
                } else {
                    $_SESSION['foto'] = $db_path;
                }
                $success = 'Foto berhasil diupload!';
                
                // Refresh data siswa
                $stmt = $pdo->prepare("SELECT * FROM siswa WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $siswa = $stmt->fetch();
            } else {
                $error = 'Gagal upload foto!';
            }
        }
    }
}

// Get foto path
$foto_path = '../assets/img/avatar.png';
if ($user_role == 'siswa' && $siswa && !empty($siswa['foto']) && file_exists('../' . $siswa['foto'])) {
    $foto_path = '../' . $siswa['foto'];
} elseif (isset($_SESSION['foto']) && !empty($_SESSION['foto']) && file_exists('../' . $_SESSION['foto'])) {
    $foto_path = '../' . $_SESSION['foto'];
}

$page_title = 'Profil Saya';
$page_subtitle = 'Kelola informasi akun Anda';

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
        
        <!-- Hero Profile Section -->
        <div class="profile-hero">
            <div class="profile-hero-cover"></div>
            <div class="profile-hero-avatar">
                <img src="<?php echo $foto_path; ?>" alt="Avatar" id="profileAvatar">
                <div class="avatar-edit" onclick="document.getElementById('fotoFile').click();">
                    <i class="fas fa-camera"></i>
                </div>
                <form method="POST" enctype="multipart/form-data" id="fotoForm" style="display: none;">
                    <input type="file" name="foto" id="fotoFile" accept="image/*">
                    <input type="submit" name="upload_foto" value="1">
                </form>
            </div>
            <div class="profile-hero-info">
                <h2><?php echo htmlspecialchars($user['nama']); ?></h2>
                <p class="role-badge"><?php echo ucfirst($user['role']); ?></p>
                <p class="email-info"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?></p>
            </div>
        </div>
        
        <!-- Main Content Cards -->
        <div class="profile-grid">
            <!-- Card Kiri - Info Dasar -->
            <div class="profile-card">
                <div class="card-header-custom">
                    <i class="fas fa-user-astronaut"></i>
                    <h3>Informasi Dasar</h3>
                </div>
                <div class="card-body-custom">
                    <form method="POST">
                        <div class="form-group-custom">
                            <label>Nama Lengkap</label>
                            <div class="input-icon">
                                <i class="fas fa-user"></i>
                                <input type="text" name="nama" value="<?php echo htmlspecialchars($user['nama']); ?>" required>
                            </div>
                        </div>
                        <div class="form-group-custom">
                            <label>Alamat Email</label>
                            <div class="input-icon">
                                <i class="fas fa-envelope"></i>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group-custom">
                                <label>Role Akun</label>
                                <div class="input-icon">
                                    <i class="fas fa-shield-alt"></i>
                                    <input type="text" value="<?php echo ucfirst($user['role']); ?>" disabled>
                                </div>
                            </div>
                            <div class="form-group-custom">
                                <label>Bergabung Sejak</label>
                                <div class="input-icon">
                                    <i class="fas fa-calendar-alt"></i>
                                    <input type="text" value="<?php echo formatTanggal($user['created_at'], 'd F Y'); ?>" disabled>
                                </div>
                            </div>
                        </div>
                        <button type="submit" name="update_profile" class="btn-primary-custom">
                            <i class="fas fa-save"></i> Simpan Perubahan
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Card Kanan Atas - Ganti Password -->
            <div class="profile-card">
                <div class="card-header-custom">
                    <i class="fas fa-key"></i>
                    <h3>Keamanan Akun</h3>
                </div>
                <div class="card-body-custom">
                    <form method="POST">
                        <div class="form-group-custom">
                            <label>Password Saat Ini</label>
                            <div class="input-icon">
                                <i class="fas fa-lock"></i>
                                <input type="password" name="current_password" placeholder="Masukkan password saat ini" required>
                            </div>
                        </div>
                        <div class="form-group-custom">
                            <label>Password Baru</label>
                            <div class="input-icon">
                                <i class="fas fa-key"></i>
                                <input type="password" name="new_password" placeholder="Minimal 4 karakter" minlength="4" required>
                            </div>
                        </div>
                        <div class="form-group-custom">
                            <label>Konfirmasi Password Baru</label>
                            <div class="input-icon">
                                <i class="fas fa-check-circle"></i>
                                <input type="password" name="confirm_password" placeholder="Ketik ulang password baru" required>
                            </div>
                        </div>
                        <button type="submit" name="update_password" class="btn-warning-custom">
                            <i class="fas fa-sync-alt"></i> Ubah Password
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Card Kanan Bawah - Data Siswa (khusus siswa) -->
            <?php if ($user_role == 'siswa' && $siswa): ?>
            <div class="profile-card full-width">
                <div class="card-header-custom">
                    <i class="fas fa-graduation-cap"></i>
                    <h3>Data Akademik</h3>
                </div>
                <div class="card-body-custom">
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">NIS</span>
                            <span class="info-value"><?php echo htmlspecialchars($siswa['nis']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Nomor Absen</span>
                            <span class="info-value"><?php echo $siswa['nomor_absen']; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Jenis Kelamin</span>
                            <span class="info-value"><?php echo $siswa['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan'; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Tempat Lahir</span>
                            <span class="info-value"><?php echo htmlspecialchars($siswa['tempat_lahir'] ?: '-'); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Tanggal Lahir</span>
                            <span class="info-value"><?php echo $siswa['tanggal_lahir'] ? formatTanggal($siswa['tanggal_lahir'], 'd F Y') : '-'; ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">No. Handphone</span>
                            <span class="info-value"><?php echo htmlspecialchars($siswa['no_hp'] ?: '-'); ?></span>
                        </div>
                        <div class="info-item full">
                            <span class="info-label">Alamat</span>
                            <span class="info-value"><?php echo htmlspecialchars($siswa['alamat'] ?: '-'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
    </div>
</div>

<style>
    /* ========== ALERT STYLE ========== */
    .alert-custom {
        padding: 14px 20px;
        border-radius: 16px;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        gap: 12px;
        font-weight: 500;
        animation: slideDown 0.3s ease;
    }
    
    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
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
    
    /* ========== PROFILE HERO ========== */
    .profile-hero {
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        border-radius: 28px;
        margin-bottom: 32px;
        position: relative;
        overflow: hidden;
    }
    
    .profile-hero-cover {
        height: 120px;
        background: linear-gradient(90deg, #FFD65A 0%, #e6c04a 100%);
        opacity: 0.15;
    }
    
    .profile-hero-avatar {
        position: relative;
        width: 120px;
        height: 120px;
        margin: -60px auto 0;
        border-radius: 60px;
        background: #1e293b;
        padding: 4px;
    }
    
    .profile-hero-avatar img {
        width: 100%;
        height: 100%;
        border-radius: 60px;
        object-fit: cover;
        border: 3px solid #FFD65A;
    }
    
    .avatar-edit {
        position: absolute;
        bottom: 5px;
        right: 5px;
        width: 34px;
        height: 34px;
        background: #FFD65A;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
        box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    }
    
    .avatar-edit:hover {
        transform: scale(1.05);
        background: #e6c04a;
    }
    
    .avatar-edit i {
        font-size: 16px;
        color: #1e293b;
    }
    
    .profile-hero-info {
        text-align: center;
        padding: 16px 24px 24px;
    }
    
    .profile-hero-info h2 {
        font-size: 1.6rem;
        font-weight: 700;
        color: white;
        margin: 0 0 8px;
    }
    
    .role-badge {
        display: inline-block;
        background: rgba(255, 214, 90, 0.2);
        color: #FFD65A;
        padding: 4px 16px;
        border-radius: 30px;
        font-size: 0.75rem;
        font-weight: 600;
        margin-bottom: 12px;
    }
    
    .email-info {
        color: #94a3b8;
        font-size: 0.85rem;
        margin: 0;
    }
    
    .email-info i {
        margin-right: 8px;
        color: #FFD65A;
    }
    
    /* ========== PROFILE GRID ========== */
    .profile-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 24px;
    }
    
    .profile-card {
        background: white;
        border-radius: 24px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        transition: all 0.3s;
    }
    
    .profile-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
    }
    
    .full-width {
        grid-column: span 2;
    }
    
    .card-header-custom {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 20px 24px;
        border-bottom: 2px solid #FFD65A;
    }
    
    .card-header-custom i {
        font-size: 24px;
        color: #FFD65A;
    }
    
    .card-header-custom h3 {
        font-size: 1.1rem;
        font-weight: 600;
        color: #1e293b;
        margin: 0;
    }
    
    .card-body-custom {
        padding: 24px;
    }
    
    /* Form Elements */
    .form-group-custom {
        margin-bottom: 20px;
    }
    
    .form-group-custom label {
        display: block;
        font-size: 0.8rem;
        font-weight: 500;
        color: #64748b;
        margin-bottom: 8px;
    }
    
    .input-icon {
        position: relative;
    }
    
    .input-icon i {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 1rem;
    }
    
    .input-icon input {
        width: 100%;
        padding: 12px 14px 12px 42px;
        border: 1.5px solid #e2e8f0;
        border-radius: 14px;
        font-family: 'Poppins', sans-serif;
        font-size: 0.9rem;
        transition: all 0.2s;
    }
    
    .input-icon input:focus {
        outline: none;
        border-color: #FFD65A;
        box-shadow: 0 0 0 3px rgba(255, 214, 90, 0.2);
    }
    
    .input-icon input:disabled {
        background: #f8fafc;
        color: #64748b;
    }
    
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }
    
    /* Buttons */
    .btn-primary-custom {
        background: #FFD65A;
        border: none;
        padding: 12px 24px;
        border-radius: 14px;
        font-weight: 600;
        color: #1e293b;
        font-family: 'Poppins', sans-serif;
        transition: all 0.2s;
        cursor: pointer;
        width: 100%;
    }
    
    .btn-primary-custom:hover {
        background: #e6c04a;
        transform: translateY(-1px);
    }
    
    .btn-warning-custom {
        background: #ef4444;
        border: none;
        padding: 12px 24px;
        border-radius: 14px;
        font-weight: 600;
        color: white;
        font-family: 'Poppins', sans-serif;
        transition: all 0.2s;
        cursor: pointer;
        width: 100%;
    }
    
    .btn-warning-custom:hover {
        background: #dc2626;
        transform: translateY(-1px);
    }
    
    /* Info Grid untuk Data Siswa */
    .info-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
    }
    
    .info-item {
        border-bottom: 1px dashed #e2e8f0;
        padding-bottom: 8px;
    }
    
    .info-item.full {
        grid-column: span 2;
    }
    
    .info-label {
        display: block;
        font-size: 0.7rem;
        color: #94a3b8;
        margin-bottom: 4px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .info-value {
        font-size: 0.95rem;
        font-weight: 500;
        color: #1e293b;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .profile-grid {
            grid-template-columns: 1fr;
        }
        .full-width {
            grid-column: span 1;
        }
        .form-row {
            grid-template-columns: 1fr;
        }
        .profile-hero-info h2 {
            font-size: 1.2rem;
        }
        .info-grid {
            grid-template-columns: 1fr;
        }
        .info-item.full {
            grid-column: span 1;
        }
    }
</style>

<script>
    // Auto submit form ketika file dipilih
    document.getElementById('fotoFile').addEventListener('change', function() {
        if (this.files.length > 0) {
            document.getElementById('fotoForm').submit();
        }
    });
    
    // Preview foto sebelum submit
    const fotoFile = document.getElementById('fotoFile');
    const profileAvatar = document.getElementById('profileAvatar');
    
    fotoFile.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(event) {
                profileAvatar.src = event.target.result;
            };
            reader.readAsDataURL(file);
        }
    });
</script>

<?php include '../includes/footer.php'; ?>