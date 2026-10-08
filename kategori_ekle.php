<?php
require_once __DIR__ . '/config.php';

// ==================== POST İŞLEMİ ====================
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cat_add'])) {
    $ru = trim(isset($_POST['cat_ru']) ? $_POST['cat_ru'] : '');
    $uz = trim(isset($_POST['cat_uz']) ? $_POST['cat_uz'] : '');
    $en = trim(isset($_POST['cat_en']) ? $_POST['cat_en'] : '');
    $parent_id_val = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;

    $image = '';
    if (!empty($_FILES['cat_image']['name']) && $_FILES['cat_image']['error'] === UPLOAD_ERR_OK) {
        $source = $_FILES['cat_image']['tmp_name'];
        $image_info = @getimagesize($source);

        if ($image_info) {
            $mime = $image_info['mime'];
            $new_filename = 'cat_' . uniqid() . '.jpg';
            $image_path = 'images/' . $new_filename;

            $allowed = array('image/jpeg', 'image/png');
            if (function_exists('imagecreatetruecolor') && in_array($mime, $allowed, true)) {
                list($width, $height) = $image_info;
                $max_w = 800; $max_h = 800;
                $ratio = $width / $height;

                if ($width > $max_w || $height > $max_h) {
                    if ($max_w / $max_h > $ratio) { $new_w = $max_h * $ratio; $new_h = $max_h; }
                    else { $new_w = $max_w; $new_h = $max_w / $ratio; }
                } else {
                    $new_w = $width; $new_h = $height;
                }

                $dst = imagecreatetruecolor($new_w, $new_h);
                $white = imagecolorallocate($dst, 255, 255, 255);
                imagefill($dst, 0, 0, $white);

                $src = ($mime === 'image/png') ? imagecreatefrompng($source) : imagecreatefromjpeg($source);

                if ($src) {
                    imagecopyresampled($dst, $src, 0, 0, 0, 0, $new_w, $new_h, $width, $height);
                    imagejpeg($dst, $image_path, 75);
                    imagedestroy($src);
                    imagedestroy($dst);
                    $image = $image_path;
                }
            } else {
                if (move_uploaded_file($source, $image_path)) {
                    $image = $image_path;
                }
            }
        }
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO categories (ru, uz, en, image, parent_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(array($ru, $uz, $en, $image, $parent_id_val));

        $message = isset($lang['added']) ? $lang['added'] : 'Добавлено';
        $messageType = 'success';
    } catch (PDOException $e) {
        error_log("Kategori ekleme hatası: " . $e->getMessage());
        $message = 'Kategori eklenirken hata oluştu.';
        $messageType = 'danger';
    }
}

// ==================== KATEGORİLERİ ÇEK ====================
$cats = $pdo->query("SELECT id, ru FROM categories WHERE parent_id IS NULL ORDER BY ru ASC")->fetchAll();

// ==================== HTML ====================
require_once __DIR__ . '/header.php';
?>
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">

<style>
    .kategori-ekle-page {
        height: calc(100vh - 80px);
        overflow: hidden;
        padding: 10px 15px;
    }
    .form-lang-box {
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 10px;
        height: 100%;
        background: #fff;
    }
    .form-lang-box .lang-title {
        font-weight: 700;
        font-size: 0.85rem;
        margin-bottom: 8px;
    }
    .form-lang-box.ru .lang-title { color: #d52b1e; }
    .form-lang-box.uz .lang-title { color: #1eb53a; }
    .form-lang-box.en .lang-title { color: #012169; }

    .image-preview-box {
        border: 2px dashed #ced4da;
        border-radius: 8px;
        padding: 15px;
        text-align: center;
        background: #f8f9fa;
        min-height: 150px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }
    .image-preview-box img {
        max-width: 100%;
        max-height: 110px;
        border-radius: 6px;
    }
    .image-preview-box .placeholder-icon {
        font-size: 2.2rem;
        color: #adb5bd;
    }
</style>

<div class="container-fluid kategori-ekle-page">
    <h4 class="text-center mb-2">
        ➕ <?php echo htmlspecialchars(isset($lang['addcategory']) ? $lang['addcategory'] : 'Добавить категорию'); ?>
    </h4>

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $messageType; ?> py-2 text-center mb-2">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" action="kategori_ekle.php">

        <!-- ÜST SIRA: 3 DİL + FOTO -->
        <div class="row g-2 mb-2">

            <div class="col-md-3">
                <div class="form-lang-box ru">
                    <div class="lang-title">🇷🇺 RU</div>
                    <input required name="cat_ru" class="form-control form-control-sm" placeholder="Название (RU)">
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-lang-box uz">
                    <div class="lang-title">🇺🇿 UZ</div>
                    <input required name="cat_uz" class="form-control form-control-sm" placeholder="Kategoriya nomi (UZ)">
                </div>
            </div>

            <div class="col-md-3">
                <div class="form-lang-box en">
                    <div class="lang-title">🇬🇧 EN</div>
                    <input name="cat_en" class="form-control form-control-sm" placeholder="Category name (EN)">
                </div>
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-bold mb-1">
                    📷 <?php echo htmlspecialchars(isset($lang['catphotoadd']) ? $lang['catphotoadd'] : 'Фото'); ?>
                </label>
                <div class="image-preview-box" id="imagePreviewBox">
                    <div class="placeholder-icon"><i class="fa fa-image"></i></div>
                    <p class="text-muted small mb-2 mt-2">Изображения нет</p>
                    <input type="file" name="cat_image" id="cat_image" class="form-control form-control-sm" accept="image/jpeg,image/png" style="max-width: 200px;">
                </div>
            </div>

        </div>

        <!-- ALT SIRA: ÜST KATEGORİ -->
        <div class="row g-2 mb-2">
            <div class="col-md-6">
                <label for="categorySelect" class="form-label small fw-bold mb-1">
                    📁 <?php echo htmlspecialchars(isset($lang['infocat']) ? $lang['infocat'] : 'Родительская категория'); ?>
                </label>
                <select name="parent_id" id="categorySelect" class="form-select">
                    <option value="">— <?php echo htmlspecialchars(isset($lang['maincat']) ? $lang['maincat'] : 'Основная'); ?> —</option>
                    <?php foreach ($cats as $cat): ?>
                        <option value="<?php echo (int)$cat['id']; ?>"><?php echo htmlspecialchars($cat['ru']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- BUTONLAR -->
        <div class="text-center">
            <button type="submit" name="cat_add" class="btn btn-primary">
                <i class="fa fa-plus"></i>
                <?php echo htmlspecialchars(isset($lang['addcategory']) ? $lang['addcategory'] : 'Добавить'); ?>
            </button>
            <a href="kategori_list.php" class="btn btn-secondary">
                <i class="fa fa-times"></i>
                <?php echo htmlspecialchars(isset($lang['cancel']) ? $lang['cancel'] : 'Отмена'); ?>
            </a>
        </div>

    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tom Select
    if (document.getElementById('categorySelect')) {
        new TomSelect('#categorySelect', { create: false });
    }

    // Foto önizleme
    var fileInput = document.getElementById('cat_image');
    var previewBox = document.getElementById('imagePreviewBox');

    if (fileInput) {
        fileInput.addEventListener('change', function() {
            var file = this.files[0];
            if (!file) return;

            var reader = new FileReader();
            reader.onload = function(e) {
                var html = ''
                    + '<img src="' + e.target.result + '" alt="Preview">'
                    + '<input type="file" name="cat_image" id="cat_image" class="form-control form-control-sm mt-2" accept="image/jpeg,image/png" style="max-width: 200px;">'
                    + '<p class="text-muted small mb-0 mt-1">' + file.name + '</p>';
                previewBox.innerHTML = html;

                // Yeni input'a listener bağla
                var newInput = previewBox.querySelector('input[type=file]');
                newInput.addEventListener('change', handleFileChange);
            };
            reader.readAsDataURL(file);
        });
    }

    // İsimli fonksiyon (recursive için)
    function handleFileChange() {
        var file = this.files[0];
        if (!file) return;
        var reader = new FileReader();
        reader.onload = function(e) {
            var html = ''
                + '<img src="' + e.target.result + '" alt="Preview">'
                + '<input type="file" name="cat_image" class="form-control form-control-sm mt-2" accept="image/jpeg,image/png" style="max-width: 200px;">'
                + '<p class="text-muted small mb-0 mt-1">' + file.name + '</p>';
            previewBox.innerHTML = html;
            var newInput = previewBox.querySelector('input[type=file]');
            newInput.addEventListener('change', handleFileChange);
        };
        reader.readAsDataURL(file);
    }

    // F5 koruması
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
});
</script>