/**
 * Agenda Kelas - QR Scanner
 * File: assets/js/qr-scan.js
 * Fungsi untuk scanning QR Code menggunakan HTML5 QR Code
 * (Butuh library html5-qrcode)
 */

// ============================================
// QR SCANNER INITIALIZATION
// ============================================
let html5QrCode = null;

function initQrScanner(elementId, onSuccessCallback, onErrorCallback) {
    const element = document.getElementById(elementId);
    if (!element) {
        console.error('Element not found: ' + elementId);
        return;
    }
    
    html5QrCode = new Html5Qrcode(elementId);
    
    const config = {
        fps: 10,
        qrbox: { width: 250, height: 250 },
        aspectRatio: 1.0
    };
    
    html5QrCode.start(
        { facingMode: "environment" }, // Use rear camera
        config,
        (decodedText, decodedResult) => {
            // Success callback
            if (onSuccessCallback) {
                onSuccessCallback(decodedText, decodedResult);
            }
            stopQrScanner();
        },
        (errorMessage) => {
            // Error callback (optional, silent fail)
            if (onErrorCallback) {
                onErrorCallback(errorMessage);
            }
        }
    ).catch((err) => {
        console.error('Failed to start scanner: ', err);
        if (onErrorCallback) {
            onErrorCallback('Gagal memulai kamera: ' + err);
        }
    });
}

function stopQrScanner() {
    if (html5QrCode && html5QrCode.isScanning) {
        html5QrCode.stop().then(() => {
            console.log('QR Scanner stopped');
        }).catch((err) => {
            console.error('Failed to stop scanner: ', err);
        });
    }
}

// ============================================
// VALIDATE QR CODE
// ============================================
function validateQrCode(qrToken, validateUrl) {
    Swal.fire({
        title: 'Validasi QR Code',
        text: 'Apakah Anda yakin ingin memvalidasi kehadiran ini?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#FFD65A',
        cancelButtonColor: '#6c6f78',
        confirmButtonText: 'Ya, Validasi',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading
            Swal.fire({
                title: 'Memproses...',
                text: 'Sedang memvalidasi data',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Submit validation
            fetch(validateUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ qr_token: qrToken })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Validasi Berhasil!',
                        text: data.message,
                        confirmButtonColor: '#FFD65A'
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Validasi Gagal',
                        text: data.message,
                        confirmButtonColor: '#FFD65A'
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Terjadi kesalahan pada server',
                    confirmButtonColor: '#FFD65A'
                });
            });
        }
    });
}

// ============================================
// GENERATE QR CODE DISPLAY
// ============================================
function displayQrCode(elementId, qrValue, size = 200) {
    const element = document.getElementById(elementId);
    if (!element) return;
    
    // Clear previous content
    element.innerHTML = '';
    
    // Create QR code using QRCode.js
    new QRCode(element, {
        text: qrValue,
        width: size,
        height: size,
        colorDark: "#1a1a2e",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.H
    });
}

// ============================================
// DOWNLOAD QR CODE AS IMAGE
// ============================================
function downloadQrCode(elementId, filename = 'qr-code.png') {
    const element = document.getElementById(elementId);
    if (!element) return;
    
    const canvas = element.querySelector('canvas');
    if (canvas) {
        const link = document.createElement('a');
        link.download = filename;
        link.href = canvas.toDataURL('image/png');
        link.click();
    }
}