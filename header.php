<?php
require_once __DIR__ . '/config.php';

$current_page = basename($_SERVER['PHP_SELF']);

if ($current_page !== 'giris.php') {
    if (!isset($_SESSION['user_id'])) {
        header('Location: giris.php');
        exit;
    }
    if (empty($_SESSION['is_admin'])) {
        header('Location: giris.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang_code); ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($lang['panel']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            margin: 0;
            padding-top: 70px;
        }

        .navbar-custom {
            height: 70px;
            background-color: rgb(26, 39, 57);
        }

        .navbar-custom .nav-link,
        .navbar-custom .navbar-brand {
            color: #fff;
        }

        .navbar-custom .nav-link:hover,
        .navbar-custom .navbar-brand:hover {
            color: #ddd;
        }

        .logo-area {
            width: 180px;
        }

        .logo-img {
            height: 50px;
            width: auto;
            background-color: white;
            display: inline-block;
            border-radius: 6px;
        }

        .content {
            padding: 20px;
        }

        .menu-icon {
            font-size: 1.4rem;
            text-decoration: none;
        }

        .menu-icon:hover {
            color: #ffc107;
        }

        /* DİL DROPDOWN */
        .lang-dropdown .dropdown-toggle {
            border-radius: 20px;
            padding: 4px 12px;
            font-size: 0.85rem;
        }
        .lang-dropdown .dropdown-toggle::after {
            margin-left: 5px;
        }
        .lang-dropdown .dropdown-menu {
            min-width: 170px;
            font-size: 0.9rem;
        }
        .lang-dropdown .dropdown-item {
            padding: 6px 12px;
        }
        .lang-dropdown .dropdown-item.active {
            background-color: #0d6efd;
            color: #fff;
        }
        .lang-dropdown .dropdown-item img {
            border: 1px solid #eee;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark navbar-custom fixed-top">
    <div class="container-fluid">

        <a class="navbar-brand logo-area" href="tablet_dash.php">
            <img src="./images/icon-512.png" alt="Logo" class="logo-img">
        </a>

        <div class="collapse navbar-collapse justify-content-center">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-4">
                <li class="nav-item"><a class="nav-link" href="kategori_ekle.php">➕ <?php echo htmlspecialchars($lang['addcategory']); ?></a></li>
                <li class="nav-item"><a class="nav-link" href="kategori_list.php">📁 <?php echo htmlspecialchars($lang['catlist']); ?></a></li>
                <li class="nav-item"><a class="nav-link" href="yemek_ekle.php">🍽️ <?php echo htmlspecialchars($lang['adddish']); ?></a></li>
                <li class="nav-item"><a class="nav-link" href="yemek_list.php">📜 <?php echo htmlspecialchars($lang['dishlist']); ?></a></li>
                <li class="nav-item"><a class="nav-link" href="panel_splash.php">🎬 <?php echo htmlspecialchars($lang['addmanager']); ?></a></li>
            </ul>
        </div>

        <div class="d-flex align-items-center gap-3">
            <a class="nav-link menu-icon" href="export_offline_panel.php?download=1" title="Обновить меню">⚡</a>
            <a class="nav-link menu-icon" href="qrcode.php" title="Menü QR Kodu">
                <i class="fas fa-qrcode"></i>
            </a>
            <a class="nav-link menu-icon" href="/" target="_blank" title="Menu"><i class="fas fa-hamburger"></i></a>
            <a class="nav-link menu-icon" href="translate.php" target="_blank" title="Çeviri"><i class="fas fa-language"></i></a>
            <a class="nav-link menu-icon" href="omer.php" target="_blank" title="Çeviri"><i class="fas fa-user"></i></a>

            <!-- DİL DROPDOWN -->
            <div class="dropdown lang-dropdown">
                <button class="btn btn-sm btn-outline-light dropdown-toggle d-flex align-items-center gap-2" 
                        type="button" id="langDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <img src="./flags/<?php 
                        echo $lang_code === 'ru' ? 'ru' : ($lang_code === 'uz' ? 'uz' : 'gb'); 
                    ?>.png" alt="<?php echo htmlspecialchars(strtoupper($lang_code)); ?>" 
                         style="height:20px; border-radius:2px;">
                    <span><?php echo htmlspecialchars(strtoupper($lang_code)); ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="langDropdown">
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2 <?php echo $lang_code === 'ru' ? 'active' : ''; ?>" href="?lang=ru">
                            <img src="./flags/ru.png" alt="RU" style="height:20px; border-radius:2px;">
                            <span>🇷🇺 Русский</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2 <?php echo $lang_code === 'uz' ? 'active' : ''; ?>" href="?lang=uz">
                            <img src="./flags/uz.png" alt="UZ" style="height:20px; border-radius:2px;">
                            <span>🇺🇿 O'zbekcha</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2 <?php echo $lang_code === 'en' ? 'active' : ''; ?>" href="?lang=en">
                            <img src="./flags/gb.png" alt="EN" style="height:20px; border-radius:2px;">
                            <span>🇬🇧 English</span>
                        </a>
                    </li>
                </ul>
            </div>

            <a href="cikis.php" class="btn btn-danger btn-sm"><?php echo htmlspecialchars($lang['cikis']); ?></a>
        </div>

    </div>
</nav>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<div class="content">