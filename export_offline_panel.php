<?php
/**
 * Offline Menü ZIP Paketi Oluşturucu
 * Генератор офлайн-пакета меню
 */

// ============ ŞİФРЕ KORUMASI ============
session_start();
$PASSWORD = '145366'; // ⚠️ İЗМЕНИТЕ НА СВОЙ ПАРОЛЬ

if (!isset($_SESSION['offline_export_ok'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pw'])) {
        if ($_POST['pw'] === $PASSWORD) {
            $_SESSION['offline_export_ok'] = true;
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        } else {
            $error = 'Неверный пароль!';
        }
    }
    ?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <title>Офлайн-пакет — Вход</title>
        <style>
            body { background: #1d222b; color: #eee; font-family: system-ui, -apple-system, sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0;}
            .box { background: #222; padding: 40px; border-radius: 16px; box-shadow: 0 8px 24px #0006; width: 320px; text-align: center;}
            h2 { color: #0df; margin-top: 0; }
            input[type=password] { width: 100%; padding: 12px; margin: 12px 0; border: 1px solid #444; background: #111; color: #fff; border-radius: 8px; box-sizing: border-box; font-size: 1em;}
            button { width: 100%; padding: 14px; background: #007bff; color: #fff; border: none; border-radius: 8px; font-size: 1.1em; cursor: pointer; font-family: inherit;}
            button:hover { background: #0056b3;}
            .error { color: #f66; margin-top: 12px;}
        </style>
    </head>
    <body>
        <div class="box">
            <h2>🔒 Офлайн-пакет</h2>
            <form method="post">
                <input type="password" name="pw" placeholder="Пароль" required autofocus>
                <button type="submit">Войти</button>
            </form>
            <?php if (isset($error)) echo '<div class="error">' . htmlspecialchars($error) . '</div>'; ?>
        </div>
    </body>
    </html>
    <?php
    exit;
}
// ============ /ŞİФРЕ KORUMASI ============


// ============ ZIP OLUŞTURMA ============
if (isset($_GET['download']) && $_GET['download'] == 1) {
    include 'db.php';

    // 1. Получаем данные
    $categories = $pdo->query("SELECT * FROM categories WHERE is_active=1 ORDER BY sort_order ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
    $menuItems = $pdo->query("SELECT id, category_id, ru, uz, en, price, image, description_ru, description_uz, description_en, sort_order 
                          FROM menu_items WHERE is_active = 1 ORDER BY category_id ASC, sort_order ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);

    $settings = $pdo->query("SELECT * FROM settings WHERE is_active=1 ORDER BY id DESC LIMIT 1")->fetch();
    $splashBg = !empty($settings['splash_bg']) ? $settings['splash_bg'] : '';

    // 2. Читаем шаблон
    if (!file_exists('index_template.html')) {
        die('ОШИБКА: файл index_template.html не найден!');
    }
    $template = file_get_contents('index_template.html');
    $template = str_replace('[[CATEGORIES_JSON]]', json_encode($categories, JSON_UNESCAPED_UNICODE), $template);
    $template = str_replace('[[MENU_ITEMS_JSON]]', json_encode($menuItems, JSON_UNESCAPED_UNICODE), $template);
    $template = str_replace('[[SPLASH_BG]]', htmlspecialchars($splashBg), $template);

    // 3. Временная папка
    $tempDir = __DIR__ . '/temp_offline_' . uniqid();
    if (!mkdir($tempDir, 0755, true)) {
        die('ОШИБКА: не удалось создать временную папку!');
    }
    file_put_contents($tempDir . '/index.html', $template);

    // 4. Создаём ZIP (без копирования — добавляем напрямую)
    $zipFile = __DIR__ . '/offline_menu.zip';
    if (file_exists($zipFile)) unlink($zipFile);

    $zip = new ZipArchive;
    if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
        die('ОШИБКА: не удалось открыть ZIP-файл!');
    }

    // Добавляем index.html
    $zip->addFile($tempDir . '/index.html', 'index.html');

    // 🎯 Добавляем order_view.html (страница для сканирования QR)
    if (file_exists('order_view.html')) {
        $zip->addFile('order_view.html', 'order_view.html');
    } else {
        error_log("ВНИМАНИЕ: order_view.html не найден, не добавлен в ZIP!");
    }

    // Добавляем папку assets
    if (is_dir('assets')) {
        $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('assets', RecursiveDirectoryIterator::SKIP_DOTS));
        foreach ($rii as $file) {
            if (!$file->isDir()) {
                $path = $file->getPathname();
                $localPath = str_replace('\\', '/', $path);
                $zip->addFile($path, $localPath);
            }
        }
    }

    // Добавляем папку images
    if (is_dir('images')) {
        $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator('images', RecursiveDirectoryIterator::SKIP_DOTS));
        foreach ($rii as $file) {
            if (!$file->isDir()) {
                $path = $file->getPathname();
                $localPath = str_replace('\\', '/', $path);
                $zip->addFile($path, $localPath);
            }
        }
    }

    $zip->close();

    // 5. Уборка
    @unlink($tempDir . '/index.html');
    @rmdir($tempDir);

    // 6. Сообщение об успехе
    $zipSize = round(filesize($zipFile) / 1024 / 1024, 2);
    ?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <title>Пакет готов</title>
        <style>
            body { background: #1d222b; color: #eee; font-family: system-ui, -apple-system, sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0;}
            .box { background: #222; padding: 40px; border-radius: 16px; box-shadow: 0 8px 24px #0006; width: 420px; text-align: center;}
            h2 { color: #0f6; margin-top: 0; }
            .size { color: #0df; font-size: 1.3em; margin: 16px 0; }
            .btn { display: inline-block; padding: 14px 28px; background: #007bff; color: #fff; text-decoration: none; border-radius: 8px; font-size: 1.1em; margin-top: 16px; transition: background 0.2s;}
            .btn:hover { background: #0056b3;}
            .btn-secondary { background: #555; margin-left: 10px;}
            .btn-secondary:hover { background: #333;}
        </style>
    </head>
    <body>
        <div class="box">
            <h2>✅ Пакет готов!</h2>
            <div class="size">📦 offline_menu.zip — <?= $zipSize ?> МБ</div>
            <p style="color:#888;font-size:0.9em;">Приложение для Android скачает и установит этот файл.</p>
            <a class="btn" href="offline_menu.zip" target="_blank">📥 Скачать</a>
            <a class="btn btn-secondary" href="export_offline_panel.php">← Назад</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}
// ============ /ZIP OLUŞTURMA ============
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Генератор офлайн-пакета меню</title>
    <style>
        body { background: #1d222b; color: #eee; font-family: system-ui, -apple-system, sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0;}
        .panel { background: #222; padding: 40px 50px; border-radius: 16px; box-shadow: 0 8px 24px #0006; text-align: center;}
        h2 { margin-bottom: 24px; color: #0df;}
        p { color: #888; }
        button { padding: 16px 32px; font-size: 1.2em; border-radius: 8px; border: none; background: #007bff; color: #fff; cursor: pointer; font-family: inherit; transition: background 0.2s;}
        button:hover { background: #0056b3;}
        .logout { background: #c33; margin-top: 20px; font-size: 0.85em; padding: 8px 16px;}
        .logout:hover { background: #a22;}
    </style>
</head>
<body>
    <div class="panel">
        <h2>📦 Офлайн-пакет меню</h2>
        <p>Этот пакет будет скачан и установлен приложением для Android.</p>
        <form method="get">
            <button type="submit" name="download" value="1">🚀 Создать пакет</button>
        </form>
        <form method="post" action="?logout=1" style="margin-top: 20px;">
            <button type="submit" class="logout" onclick="window.location.href='tablet_dash.php'; return false;">🚪 Выйти</button>
        </form>
    </div>
</body>
</html>
<?php
// Çıkış
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}
?>