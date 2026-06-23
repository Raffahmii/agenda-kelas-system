<?php
/**
 * Scan QR Code - Guru
 * File: guru/scan_qr.php
 */

require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

checkRoleAccess(['guru']);

$page_title = 'Scan QR Code';
$page_subtitle = 'Scan QR Code untuk validasi kehadiran';

include '../includes/navbar.php';
include '../includes/sidebar.php';
?>

<div class="main-content">
    <div class="content-area">
        
        <!-- Header -->
        <div class="page-header">
            <div class="header-left">
                <i class="fas fa-qrcode"></i>
                <div>
                    <h2>Belum Punya QR Code ?</h2>
                    <p>Validasi kehadiran siswa dengan QR Code</p>
                </div>
            </div>
        </div>
        
        <!-- Info Card -->
        <div class="info-card-primary">
            <div class="info-icon">
                <i class="fas fa-info-circle"></i>
            </div>
            <div class="info-content">
                <h3>Solusi Ke-1</h3>
                <p>Silakan minta QR Code kepada <strong>Sekretaris Kelas</strong>. Sekretaris akan membuat absensi harian dan menghasilkan QR Code yang bisa discan.</p>
                <div class="info-steps">
                    <div class="step"><span class="step-number">1</span><span>Sekretaris buat absensi</span></div>
                    <div class="step"><span class="step-number">2</span><span>Sekretaris generate QR Code</span></div>
                    <div class="step"><span class="step-number">3</span><span>Guru scan QR Code di sini</span></div>
                </div>
            </div>
        </div>


        <div class="info-card-primary">
            <div class="info-icon">
                <i class="fas fa-info-circle"></i>
            </div>
            <div class="info-content">
                <h3>Solusi Ke-2</h3>
                <p>Silakan Check pada halaman <strong>Dashboard</strong>.</p>
                <div class="info-steps">
                    <div class="step"><span class="step-number">1</span><span>Sekretaris buat absensi</span></div>
                    <div class="step"><span class="step-number">2</span><span>Sekretaris generate QR Code</span></div>
                    <div class="step"><span class="step-number">3</span><span>Masuk Ke Tab Dashboard.</span></div>
                    <div class="step"><span class="step-number">4</span><span>Check Pada Bagian "Perlu Divalidasi"</span></div>
                    <div class="step"><span class="step-number">5</span><span>Pilih Kelas Yang Akan Di Validasi.</span></div>
                </div>
            </div>
        </div>
        
        <!-- Methods Grid -->
        <div class="methods-grid">
            <!-- Method 1: Scan via Kamera -->
            <div class="method-card">
                <div class="method-header">
                    <i class="fas fa-camera"></i>
                    <h3>Scan dengan Kamera</h3>
                </div>
                <div class="method-body">
                    <p>Gunakan kamera perangkat untuk memindai QR Code</p>
                    <button type="button" id="startCameraBtn" class="btn-camera">
                        <i class="fas fa-play"></i> Buka Kamera
                    </button>
                    <button type="button" id="stopCameraBtn" class="btn-stop" style="display: none;">
                        <i class="fas fa-stop"></i> Tutup Kamera
                    </button>
                    <div id="qr-reader-container" style="display: none; margin-top: 20px;">
                        <div id="qr-reader" class="qr-reader"></div>
                        <div id="qr-reader-results"></div>
                    </div>
                    
                </div>
            </div>
            
            <!-- Method 2: Upload File QR -->
            <div class="method-card">
                <div class="method-header">
                    <i class="fas fa-upload"></i>
                    <h3>Upload File QR</h3>
                </div>
                <div class="method-body">
                    <p>Upload gambar QR Code yang sudah disimpan (JPG, PNG)</p>
                    <input type="file" id="qr-upload" accept="image/*" class="file-input">
                    <div id="upload-result" class="upload-result"></div>
                    <div id="manual-token-section" style="margin-top: 20px; display: none;">
                        <hr>
                        <p class="text-muted small">Token dari QR:</p>
                        <div id="detected-token" class="detected-token"></div>
                        <button id="go-validasi-btn" class="btn-go-validasi" style="display: none;">
                            <i class="fas fa-arrow-right"></i> Lanjutkan Validasi
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Manual Input Token -->
        <div class="manual-card">
            <div class="manual-header">
                <i class="fas fa-keyboard"></i>
                <h3>Atau Masukkan Token Manual</h3>
                <p>Jika QR Code tidak bisa discan, masukkan token secara manual / check Dashboard</p>
            </div>
            <div class="manual-body">
                <form method="GET" action="validasi.php" class="manual-form">
                    <div class="form-group">
                        <input type="text" name="token" class="token-input" placeholder="Masukkan token QR Code (contoh: b6dd691964b170e4ef76d000c0812c3b)" autocomplete="off">
                    </div>
                    <button type="submit" class="btn-manual">
                        <i class="fas fa-arrow-right"></i> Validasi Sekarang
                    </button>
                </form>
            </div>
        </div>
        
    </div>
</div>

<!-- HTML5 QR Code Library -->
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

<style>
    .page-header { margin-bottom: 24px; padding: 0 4px; }
    .header-left { display: flex; align-items: center; gap: 16px; }
    .header-left i { font-size: 40px; color: #D4A000; }
    .header-left h2 { font-size: 20px; font-weight: 600; color: #1e293b; margin: 0 0 4px; }
    .header-left p { font-size: 13px; color: #64748b; margin: 0; }
    
    .info-card-primary {
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        padding: 24px 28px;
        margin-bottom: 24px;
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
    }
    .info-icon i { font-size: 48px; color: #D4A000; }
    .info-content { flex: 1; }
    .info-content h3 { font-size: 18px; font-weight: 600; color: white; margin: 0 0 8px; }
    .info-content p { font-size: 13px; color: #94a3b8; margin-bottom: 16px; }
    .info-steps { display: flex; gap: 20px; flex-wrap: wrap; }
    .step { display: flex; align-items: center; gap: 8px; background: rgba(255,255,255,0.1); padding: 6px 14px; }
    .step-number { width: 24px; height: 24px; background: #D4A000; color: #1e293b; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; }
    .step span:last-child { font-size: 12px; color: #cbd5e1; }
    
    .methods-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px; }
    .method-card { background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
    .method-header { display: flex; align-items: center; gap: 12px; padding: 16px 20px; border-bottom: 1px solid #eef2f6; }
    .method-header i { font-size: 24px; color: #D4A000; }
    .method-header h3 { font-size: 16px; font-weight: 600; color: #1e293b; margin: 0; }
    .method-body { padding: 20px; text-align: center; }
    .method-body p { font-size: 13px; color: #64748b; margin-bottom: 16px; }
    
    .btn-camera { background: #D4A000; color: white; padding: 10px 24px; border: none; font-size: 13px; font-weight: 500; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
    .btn-camera:hover { background: #b8860b; }
    .btn-stop { background: #ef4444; color: white; padding: 10px 24px; border: none; font-size: 13px; font-weight: 500; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
    .btn-stop:hover { background: #dc2626; }
    
    .qr-reader { width: 100%; max-width: 400px; margin: 0 auto; border: 2px solid #e2e8f0; }
    .qr-reader video { width: 100%; height: auto; }
    
    .file-input { width: 100%; padding: 10px; border: 1px solid #e2e8f0; font-size: 13px; font-family: 'Poppins', sans-serif; cursor: pointer; }
    .upload-result { margin-top: 16px; font-size: 13px; }
    .upload-result.success { background: #dcfce7; color: #166534; padding: 10px; border-radius: 6px; }
    .upload-result.error { background: #fee2e2; color: #991b1b; padding: 10px; border-radius: 6px; }
    .upload-result.loading { background: #fef9c3; color: #854d0e; padding: 10px; border-radius: 6px; }
    .detected-token { font-family: monospace; background: #f1f5f9; padding: 10px; word-break: break-all; margin: 10px 0; }
    .btn-go-validasi { background: #D4A000; color: white; padding: 10px 20px; border: none; font-size: 13px; font-weight: 500; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; margin-top: 10px; }
    .btn-go-validasi:hover { background: #b8860b; }
    
    .manual-card { background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
    .manual-header { display: flex; align-items: center; gap: 12px; padding: 16px 20px; border-bottom: 1px solid #eef2f6; }
    .manual-header i { font-size: 20px; color: #D4A000; }
    .manual-header h3 { font-size: 14px; font-weight: 600; color: #1e293b; margin: 0; }
    .manual-header p { font-size: 12px; color: #64748b; margin: 0; margin-left: auto; }
    .manual-body { padding: 20px; }
    .manual-form { display: flex; gap: 16px; align-items: center; flex-wrap: wrap; }
    .form-group { flex: 1; }
    .token-input { width: 100%; padding: 12px 16px; border: 1px solid #e2e8f0; font-size: 14px; font-family: 'Poppins', sans-serif; border-radius: 6px; }
    .token-input:focus { outline: none; border-color: #D4A000; }
    .btn-manual { background: #D4A000; color: white; padding: 12px 24px; border: none; font-size: 13px; font-weight: 500; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; border-radius: 6px; }
    .btn-manual:hover { background: #b8860b; }
    .text-muted { color: #94a3b8; }
    .small { font-size: 12px; }
    
    @media (max-width: 768px) {
        .methods-grid { grid-template-columns: 1fr; }
        .manual-form { flex-direction: column; }
        .btn-manual { width: 100%; justify-content: center; }
        .info-steps { flex-direction: column; gap: 10px; }
        .info-card-primary { text-align: center; justify-content: center; }
    }
</style>

<script>
    // ========== KAMERA SCAN ==========
    let html5QrCode = null;
    let isScanning = false;
    
    const startCameraBtn = document.getElementById('startCameraBtn');
    const stopCameraBtn = document.getElementById('stopCameraBtn');
    const qrReaderContainer = document.getElementById('qr-reader-container');
    
    startCameraBtn.addEventListener('click', async () => {
        qrReaderContainer.style.display = 'block';
        startCameraBtn.style.display = 'none';
        stopCameraBtn.style.display = 'inline-flex';
        
        html5QrCode = new Html5Qrcode("qr-reader");
        
        const config = {
            fps: 10,
            qrbox: { width: 250, height: 250 },
            aspectRatio: 1.0
        };
        
        try {
            await html5QrCode.start({ facingMode: "environment" }, config, (decodedText) => {
                if (html5QrCode && html5QrCode.isScanning) {
                    html5QrCode.stop();
                    isScanning = false;
                }
                window.location.href = `validasi.php?token=${encodeURIComponent(decodedText)}`;
            });
            isScanning = true;
        } catch (err) {
            Swal.fire({
                icon: 'error',
                title: 'Gagal Akses Kamera',
                text: 'Pastikan Anda memberikan izin akses kamera',
                confirmButtonColor: '#D4A000'
            });
            qrReaderContainer.style.display = 'none';
            startCameraBtn.style.display = 'inline-flex';
            stopCameraBtn.style.display = 'none';
        }
    });
    
    stopCameraBtn.addEventListener('click', async () => {
        if (html5QrCode && html5QrCode.isScanning) {
            await html5QrCode.stop();
            isScanning = false;
        }
        qrReaderContainer.style.display = 'none';
        startCameraBtn.style.display = 'inline-flex';
        stopCameraBtn.style.display = 'none';
    });
    
    // ========== UPLOAD FILE QR - METODE BARU ==========
    const qrUpload = document.getElementById('qr-upload');
    const uploadResult = document.getElementById('upload-result');
    const manualTokenSection = document.getElementById('manual-token-section');
    const detectedTokenDiv = document.getElementById('detected-token');
    const goValidasiBtn = document.getElementById('go-validasi-btn');
    
    let detectedTokenValue = '';
    
    qrUpload.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;
        
        uploadResult.innerHTML = `<div class="upload-result loading"><i class="fas fa-spinner fa-spin"></i> Membaca gambar...</div>`;
        manualTokenSection.style.display = 'none';
        
        const reader = new FileReader();
        reader.onload = function(event) {
            const imgData = event.target.result;
            
            // Buat image element untuk ditampilkan (opsional)
            const img = new Image();
            img.onload = function() {
                // Gunakan library yang sama untuk scan
                const tempScanner = new Html5Qrcode("temp-qr-scanner");
                tempScanner.scanImage(imgData)
                    .then(decodedText => {
                        detectedTokenValue = decodedText;
                        uploadResult.innerHTML = `<div class="upload-result success">
                            <i class="fas fa-check-circle"></i> QR Code berhasil dibaca!
                        </div>`;
                        detectedTokenDiv.innerHTML = `<strong>Token terdeteksi:</strong><br><code>${decodedText}</code>`;
                        manualTokenSection.style.display = 'block';
                        goValidasiBtn.style.display = 'inline-flex';
                    })
                    .catch(err => {
                        console.error("Error scanning:", err);
                        uploadResult.innerHTML = `<div class="upload-result error">
                            <i class="fas fa-exclamation-circle"></i> Tidak dapat membaca QR Code dari gambar.<br>
                            <strong>Solusi:</strong> Pastikan gambar QR jelas, atau gunakan input token manual di bawah.
                        </div>`;
                        manualTokenSection.style.display = 'none';
                    });
            };
            img.src = imgData;
        };
        reader.readAsDataURL(file);
    });
    
    goValidasiBtn.addEventListener('click', function() {
        window.location.href = `validasi.php?token=${encodeURIComponent(detectedTokenValue)}`;
    });
</script>

<!-- Hidden div untuk temporary scanner -->
<div id="temp-qr-scanner" style="display: none;"></div>

<?php include '../includes/footer.php'; ?>