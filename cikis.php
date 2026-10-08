<?php
session_start(); // Session'ı başlat

// Tüm session verilerini temizle
$_SESSION = array();

// Session'ı yok et
session_destroy();

// Giriş sayfasına yönlendir
header('Location: giris.php');
exit;
?>