<?php
// config.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_GET['lang']) && in_array($_GET['lang'], array('ru','en','uz'), true)) {
    $_SESSION['lang'] = $_GET['lang'];
}
$lang_code = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'ru';
include __DIR__ . "/lang/{$lang_code}.php";

require_once __DIR__ . '/db.php';   // $pdo + $host, $db, $user, $pass

// ============================================================
// GEÇİCİ: mysqli uyumluluk katmanı
// Eski dosyalar $conn kullanıyor. Yeni dosyalar $pdo kullansın.
// Tüm dosyalar PDO'ya geçince bu bloğu sil.
// ============================================================
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Bağlantı hatası: " . $conn->connect_error);
}
$conn->set_charset($charset);