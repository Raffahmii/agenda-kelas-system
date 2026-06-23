<?php
/**
 * Login Page - Agenda Kelas (Plain Text Version)
 */

require_once 'config/database.php';
require_once 'config/session.php';

// Jika sudah login, redirect
if (isLoggedIn()) {
    $role = $_SESSION['role'];
    switch ($role) {
        case 'siswa': header("Location: siswa/dashboard.php"); break;
        case 'sekretaris': header("Location: sekre/dashboard.php"); break;
        case 'guru': header("Location: guru/dashboard.php"); break;
        case 'walikelas': header("Location: walikelas/dashboard.php"); break;
        default: header("Location: index.php");
    }
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Email dan password harus diisi';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            // PASSWORD PLAIN TEXT - LANGSUNG BANDINGKAN
            if ($user && $password == $user['password']) {
                $_SESSION['id'] = $user['id'];
                $_SESSION['nama'] = $user['nama'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['email'] = $user['email'];
                
                switch ($user['role']) {
                    case 'siswa': $redirect = 'siswa/dashboard.php'; break;
                    case 'sekretaris': $redirect = 'sekre/dashboard.php'; break;
                    case 'guru': $redirect = 'guru/dashboard.php'; break;
                    case 'walikelas': $redirect = 'walikelas/dashboard.php'; break;
                    default: $redirect = 'index.php';
                }
                
                header("Location: $redirect");
                exit();
            } else {
                $error = 'Email atau password salah';
            }
        } catch (PDOException $e) {
            $error = 'Terjadi kesalahan, silakan coba lagi';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Agenda Kelas</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            background: #e8edf2;
        }
        
        /* Background dengan pattern dots + grid */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: 
                linear-gradient(#cbd5e1 1px, transparent 1px),
                linear-gradient(90deg, #cbd5e1 1px, transparent 1px),
                radial-gradient(circle at 2px 2px, rgba(255, 214, 90, 0.3) 1.5px, transparent 1.5px);
            background-size: 40px 40px, 40px 40px, 20px 20px;
            background-position: center center;
            z-index: 0;
        }
        
        body::after {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 30% 40%, rgba(255, 214, 90, 0.12), rgba(0, 0, 0, 0.02) 70%);
            z-index: 0;
        }
        
        .blur-circle {
            position: fixed;
            border-radius: 50%;
            filter: blur(60px);
            z-index: 0;
            pointer-events: none;
        }
        
        .blur-1 {
            width: 300px;
            height: 300px;
            background: rgba(255, 214, 90, 0.25);
            top: -100px;
            right: -100px;
        }
        
        .blur-2 {
            width: 250px;
            height: 250px;
            background: rgba(255, 214, 90, 0.2);
            bottom: -80px;
            left: -80px;
        }
        
        .blur-3 {
            width: 180px;
            height: 180px;
            background: rgba(255, 200, 100, 0.15);
            top: 50%;
            left: 20%;
        }
        
        .login-wrapper {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 440px;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(35px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .login-card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(0px);
            border-radius: 28px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            animation: fadeInUp 0.5s ease-out;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 1px solid rgba(255, 214, 90, 0.2);
        }
        
        .login-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 30px 60px -12px rgba(0, 0, 0, 0.35);
        }
        
        .login-header {
            background: white;
            padding: 34px 32px 24px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 214, 90, 0.3);
            position: relative;
        }
        
        .login-header::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 50%;
            transform: translateX(-50%);
            width: 70px;
            height: 3px;
            background: #FFD65A;
            border-radius: 3px;
        }
        
        .logo-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(145deg, #FFD65A 0%, #FFC107 100%);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            box-shadow: 0 8px 20px rgba(255, 214, 90, 0.3);
        }
        
        .logo-icon i {
            font-size: 34px;
            color: #1a1a2e;
        }
        
        .login-header h2 {
            font-size: 1.7rem;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 6px;
        }
        
        .login-header p {
            font-size: 0.85rem;
            color: #6c6f78;
            margin: 0;
        }
        
        .login-body {
            padding: 34px;
            background: white;
        }
        
        .form-group {
            margin-bottom: 22px;
        }
        
        .form-label {
            font-weight: 500;
            font-size: 0.85rem;
            color: #333;
            margin-bottom: 8px;
            display: block;
        }
        
        .input-wrapper {
            position: relative;
        }
        
        .input-wrapper i:not(.toggle-password) {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #a0a5b0;
            font-size: 1rem;
            transition: color 0.2s ease;
            pointer-events: none;
        }
        
        .form-control {
            width: 100%;
            padding: 13px 15px 13px 45px;
            border: 1.5px solid #e2e6ea;
            border-radius: 16px;
            font-size: 0.9rem;
            font-family: 'Poppins', sans-serif;
            transition: all 0.2s ease;
            background: #fefefe;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #FFD65A;
            background: white;
            box-shadow: 0 0 0 4px rgba(255, 214, 90, 0.15);
        }
        
        .form-control:focus + i {
            color: #FFD65A;
        }
        
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #a0a5b0;
            font-size: 1rem;
            transition: color 0.2s ease;
            z-index: 2;
        }
        
        .toggle-password:hover {
            color: #FFD65A;
        }
        
        .btn-login {
            width: 100%;
            padding: 13px;
            background: linear-gradient(145deg, #FFD65A 0%, #FFC107 100%);
            border: none;
            border-radius: 16px;
            color: #1a1a2e;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.25s ease;
            margin-top: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 4px 12px rgba(255, 214, 90, 0.3);
        }
        
        .btn-login:hover {
            background: linear-gradient(145deg, #e8c84a 0%, #e6a800 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(255, 214, 90, 0.4);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .alert-custom {
            background: #fff5f5;
            color: #e53e3e;
            padding: 12px 16px;
            border-radius: 14px;
            font-size: 0.8rem;
            margin-bottom: 24px;
            border-left: 3px solid #e53e3e;
        }
        
        .back-link {
            text-align: center;
            margin-top: 28px;
            padding-top: 20px;
            border-top: 1px solid #eff2f6;
        }
        
        .back-link a {
            color: #8f94a1;
            text-decoration: none;
            font-size: 0.85rem;
            transition: color 0.2s ease;
        }
        
        .back-link a:hover {
            color: #FFD65A;
        }
        
        .demo-credentials {
            background: #f8fafc;
            border-radius: 16px;
            padding: 16px;
            margin-top: 20px;
            border: 1px solid #e2e8f0;
        }
        
        .demo-credentials h6 {
            font-weight: 700;
            margin-bottom: 12px;
            color: #1e293b;
        }
        
        .demo-credentials .cred-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 6px 0;
            font-size: 0.8rem;
            border-bottom: 1px dashed #e2e8f0;
        }
        
        .demo-credentials .cred-item:last-child {
            border-bottom: none;
        }
        
        .demo-credentials .badge-role {
            background: #FFD65A;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            color: #1e293b;
        }
        
        @media (max-width: 480px) {
            .login-body {
                padding: 28px 24px;
            }
            .login-header {
                padding: 28px 24px 20px;
            }
            .logo-icon {
                width: 60px;
                height: 60px;
            }
            .logo-icon i {
                font-size: 28px;
            }
        }
    </style>
</head>
<body>
    <!-- Blur decoration circles -->
    <div class="blur-circle blur-1"></div>
    <div class="blur-circle blur-2"></div>
    <div class="blur-circle blur-3"></div>
    
    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-header">
                <div class="logo-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <h2>Agenda Kelas</h2>
                <p>Silakan masuk ke akun Anda</p>
            </div>
            
            <div class="login-body">
                <?php if ($error): ?>
                    <div class="alert-custom">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <div class="input-wrapper">
                            <i class="fas fa-envelope"></i>
                            <input type="email" class="form-control" name="email" placeholder="Masukkan email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock"></i>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Masukkan password" required>
                            <i class="fas fa-eye toggle-password" id="togglePassword"></i>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-login">
                        <i class="fas fa-sign-in-alt"></i>
                        <span>Masuk</span>
                    </button>
                </form>
                
                
                
                <div class="back-link">
                    <a href="index.php">
                        <i class="fas fa-arrow-left me-2"></i>Kembali ke Beranda
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
        
        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', function() {
                const btn = document.querySelector('.btn-login');
                if (btn) {
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Memproses...</span>';
                    btn.disabled = true;
                }
            });
        }
    </script>
</body>
</html>