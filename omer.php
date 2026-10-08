<?php
require_once __DIR__ . '/config.php';

// GÜVENLİK: Sadece giriş yapmış admin görebilsin
if (!isset($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
    header('Location: giris.php');
    exit;
}

$directory = 'images/';
$message = "";

// Korunan özel dosyalar/kalıplar
$protectedFiles = array('icon-192.png', 'icon-512.png');

// --- SİLME İŞLEMİ ---
if (isset($_POST['delete_unused'])) {
    $files_to_delete = json_decode($_POST['files_to_delete'], true);
    $count = 0;
    if (!empty($files_to_delete) && is_array($files_to_delete)) {
        foreach ($files_to_delete as $file) {
            $filename = basename($file);

            // Güvenlik: Silme anında da korunan dosya kontrolü
            $isProtected = (strpos($filename, 'splash') !== false || in_array($filename, $protectedFiles, true));

            // Güvenlik: Sadece images/ klasörü içindeki dosyalar silinsin
            $realPath = realpath($file);
            $imagesDir = realpath($directory);
            $isInsideImages = ($realPath !== false && $imagesDir !== false && strpos($realPath, $imagesDir) === 0);

            if ($isInsideImages && file_exists($file) && !$isProtected) {
                if (unlink($file)) { $count++; }
            }
        }
        $message = '<div class="alert alert-success">Toplam '.$count.' adet gereksiz resim başarıyla silindi.</div>';
    }
}

// --- VERİ TOPLAMA ---
$filesInFolder = array();
if (is_dir($directory)) {
    $files = scandir($directory);
    foreach ($files as $file) {
        $path = $directory . $file;
        if (is_file($path) && in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), array('jpg', 'jpeg', 'png', 'webp'))) {
            $filesInFolder[] = $path;
        }
    }
}

// --- DB'DEN KULLANILAN RESİMLER ---
$dbImages = array();

$stmt = $pdo->query("SELECT image FROM menu_items WHERE image != ''");
while ($row = $stmt->fetch()) {
    $dbImages[] = $row['image'];
}

$stmt = $pdo->query("SELECT image FROM categories WHERE image != ''");
while ($row = $stmt->fetch()) {
    $dbImages[] = $row['image'];
}

$dbImages = array_unique($dbImages);

// --- KARŞILAŞTIRMA VE FİLTRELEME ---
$usedFiles = array_intersect($filesInFolder, $dbImages);
$rawUnusedFiles = array_diff($filesInFolder, $dbImages);

$unusedFiles = array();
foreach ($rawUnusedFiles as $file) {
    $filename = basename($file);

    $isProtected = (strpos($filename, 'splash') !== false || in_array($filename, $protectedFiles, true));

    if (!$isProtected) {
        $unusedFiles[] = $file;
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Giotto Resim Yönetimi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .img-preview { width: 50px; height: 50px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd; }
        .scroll-area { max-height: 500px; overflow-y: auto; }
    </style>
</head>
<body class="bg-light p-4">

<div class="container bg-white p-4 shadow-sm rounded">
    <h3 class="text-center mb-4">🖼️️ Панель для очистки папок с фотографии</h3>

    <?php echo $message; ?>

    <div class="row">
        <div class="col-md-6">
            <div class="card border-success">
                <div class="card-header bg-success text-white">
                    <span>✅ Kullanılan Resimler (<?php echo count($usedFiles); ?>)</span>
                </div>
                <div class="card-body scroll-area">
                    <table class="table table-sm align-middle">
                        <thead><tr><th>Önizleme</th><th>Dosya Yolu</th></tr></thead>
                        <tbody>
                            <?php foreach($usedFiles as $file): ?>
                            <tr>
                                <td><img src="<?php echo htmlspecialchars($file); ?>" class="img-preview"></td>
                                <td class="small text-muted"><?php echo htmlspecialchars($file); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
    <div class="card border-danger">
        <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span>🗑️ Не нужные фотки (<?php echo count($unusedFiles); ?>)</span>

            <?php if(!empty($unusedFiles)): ?>
            <div class="d-flex gap-2 align-items-center">
                <div class="form-check form-check-inline m-0">
                    <input class="form-check-input" type="checkbox" id="selectAll">
                    <label class="form-check-label text-white small" for="selectAll">Выбрать все</label>
                </div>
                <button type="button" id="deleteSelectedBtn" class="btn btn-sm btn-light text-danger fw-bold">
                    Выбранные удалить
                </button>
            </div>
            <?php endif; ?>
        </div>
        <div class="card-body scroll-area">
            <?php if(empty($unusedFiles)): ?>
                <div class="py-5 text-center">
                    <h5 class="text-success">Отлично! Больше не нужно удалять лишние файлы.</h5>
                    <small class="text-muted">(Sistem dosyaları, splash ve ikon görselleri filtrelenmiştir.)</small>
                </div>
            <?php else: ?>
                <form method="POST" id="deleteForm"
                      onsubmit="return prepareDelete();">
                    <input type="hidden" name="files_to_delete" id="filesToDeleteInput" value="">
                    <input type="hidden" name="delete_unused" value="1">

                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th style="width:30px;"></th>
                                <th>Önizleme</th>
                                <th>Dosya Yolu</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($unusedFiles as $file): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" class="form-check-input file-checkbox"
                                           value="<?php echo htmlspecialchars($file, ENT_QUOTES); ?>">
                                </td>
                                <td><img src="<?php echo htmlspecialchars($file); ?>" class="img-preview"></td>
                                <td class="small text-muted text-start"><?php echo htmlspecialchars($file); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
    </div>
</div>
<script>
// Tümünü seç / kaldır
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('selectAll');
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            document.querySelectorAll('.file-checkbox').forEach(function(cb) {
                cb.checked = selectAll.checked;
            });
        });
    }

    // Seçilenleri sil butonu
    const btn = document.getElementById('deleteSelectedBtn');
    if (btn) {
        btn.addEventListener('click', function() {
            const selected = [];
            document.querySelectorAll('.file-checkbox:checked').forEach(function(cb) {
                selected.push(cb.value);
            });

            if (selected.length === 0) {
                alert('Lütfen silmek istediğiniz dosyaları seçin.');
                return;
            }

            if (!confirm(selected.length + ' adet dosya silinecek. Emin misiniz?')) {
                return;
            }

            document.getElementById('filesToDeleteInput').value = JSON.stringify(selected);
            document.getElementById('deleteForm').submit();
        });
    }
});
</script>
</body>
</html>