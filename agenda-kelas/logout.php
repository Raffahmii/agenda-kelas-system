<?php
/**
 * Logout - Simple Version
 * Langsung hapus session dan redirect ke login
 */

session_start();

// Hapus semua session
session_destroy();

// Redirect ke halaman login
header("Location: index.php");
exit();
?>