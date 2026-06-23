<?php
require_once '../config/database.php';
require_once '../config/session.php';

$token = isset($_GET['token']) ? $_GET['token'] : '';

// Ambil data
$stmt = $pdo->prepare("SELECT * FROM validasi_qr WHERE qr_token = ?");
$stmt->execute([$token]);
$data = $stmt->fetch();

echo "<h2>Debug Validasi</h2>";
echo "<p>Token: " . htmlspecialchars($token) . "</p>";
echo "<p>Data ditemukan: " . ($data ? "YA" : "TIDAK") . "</p>";

if ($data) {
    echo "<p>Status saat ini: " . $data['status'] . "</p>";
    echo "<p>ID Validasi: " . $data['id'] . "</p>";
}

// Proses POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    echo "<hr>";
    echo "<h3>Proses POST Diterima!</h3>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    $action = $_POST['action'];
    $user_id = $_SESSION['id'];
    
    if ($action == 'valid') {
        $status = 'valid';
        $message = 'Berhasil divalidasi!';
    } else {
        $status = 'ditolak';
        $message = 'Ditolak!';
    }
    
    $update = $pdo->prepare("UPDATE validasi_qr SET status = ?, validated_by = ?, validated_at = NOW() WHERE id = ?");
    if ($update->execute([$status, $user_id, $data['id']])) {
        echo "<p style='color: green;'>✅ UPDATE BERHASIL! Status berubah menjadi: $status</p>";
        echo "<p>Redirecting to dashboard...</p>";
        echo "<meta http-equiv='refresh' content='2;url=dashboard.php'>";
    } else {
        echo "<p style='color: red;'>❌ UPDATE GAGAL!</p>";
        echo "<pre>";
        print_r($update->errorInfo());
        echo "</pre>";
    }
}
?>

<style>
    body { font-family: Arial; padding: 20px; }
    .btn { padding: 10px 20px; margin: 5px; cursor: pointer; border: none; color: white; }
    .btn-valid { background: green; }
    .btn-reject { background: red; }
    .form-group { margin-bottom: 15px; }
    textarea { width: 300px; height: 80px; }
</style>

<form method="POST">
    <div class="form-group">
        <label>Catatan:</label><br>
        <textarea name="catatan" placeholder="Opsional"></textarea>
    </div>
    <button type="submit" name="action" value="valid" class="btn btn-valid">✓ Validasi Data</button>
    <button type="submit" name="action" value="ditolak" class="btn btn-reject">✗ Tolak Data</button>
</form>

<p><strong>Petunjuk:</strong> Klik salah satu tombol di atas, lihat apakah ada pesan "Proses POST Diterima!" dan "UPDATE BERHASIL".</p>

<p><a href="scan_qr.php">← Kembali ke Scan QR</a></p>