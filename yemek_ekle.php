<?php
require_once __DIR__ . '/config.php';

// ==================== POST İŞLEMİ ====================
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['food_add'])) {
    $ru = trim(isset($_POST['ru']) ? $_POST['ru'] : '');
    $uz = trim(isset($_POST['uz']) ? $_POST['uz'] : '');
    $en = trim(isset($_POST['en']) ? $_POST['en'] : '');
    $description_ru = trim(isset($_POST['description_ru']) ? $_POST['description_ru'] : '');
    $description_uz = trim(isset($_POST['description_uz']) ? $_POST['description_uz'] : '');
    $description_en = trim(isset($_POST['description_en']) ? $_POST['description_en'] : '');
    $price = trim(isset($_POST['price']) ? $_POST['price'] : '');

    // İlk kategoriyi al
    $category_id = 0;
    if (isset($_POST['category']) && is_array($_POST['category']) && !empty($_POST['category'])) {
        $category_id = (int)$_POST['category'][0];
    }

    $image_path = '';
    if (!empty($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $source = $_FILES['image']['tmp_name'];
        $image_info = @getimagesize($source);

        if ($image_info) {
            $mime = $image_info['mime'];
            $new_filename = 'food_' . uniqid() . '.jpg';
            $target_path = 'images/' . $new_filename;

            if ($mime === 'image/jpeg' || $mime === 'image/png') {
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
                    imagejpeg($dst, $target_path, 75);
                    imagedestroy($src);
                    imagedestroy($dst);
                    $image_path = $target_path;
                }
            } else {
                if (move_uploaded_file($source, $target_path)) {
                    $image_path = $target_path;
                }
            }
        }
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO menu_items (category_id, ru, uz, en, price, image, description_ru, description_uz, description_en) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute(array($category_id, $ru, $uz, $en, $price, $image_path, $description_ru, $description_uz, $description_en));

        $message = isset($lang['added']) ? $lang['added'] : 'Добавлено';
        $messageType = 'success';
    } catch (PDOException $e) {
        error_log("Yemek ekleme hatası: " . $e->getMessage());
        $message = 'Yemek eklenirken hata oluştu.';
        $messageType = 'danger';
    }
}

// ==================== KATEGORİLERİ ÇEK ====================
$allCategories = $pdo->query("SELECT id, ru, parent_id FROM categories ORDER BY ru ASC")->fetchAll();
$catsByParent = array();
foreach ($allCategories as $c) {
    $catsByParent[$c['parent_id']][] = $c;
}

// ==================== HTML ====================
require_once __DIR__ . '/header.php';

function printCatsCheckbox($parent_id, $catsByParent, $prefix = '') {
    if (!isset($catsByParent[$parent_id])) return;
    foreach ($catsByParent[$parent_id] as $cat) {
        echo '<div style="margin-left: ' . ($prefix ? '25px' : '5px') . '; margin-bottom: 6px;">';
        echo '<label style="display: inline-flex; align-items: center; cursor: pointer; user-select: none;">';
        echo '<input type="checkbox" name="category[]" value="' . (int)$cat['id'] . '" style="width: 16px; height: 16px; margin-right: 8px;">';
        echo '<span>' . htmlspecialchars($prefix . $cat['ru']) . '</span>';
        echo '</label>';
        echo '</div>';
        printCatsCheckbox($cat['id'], $catsByParent, $prefix . '— ');
    }
}
?>
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">

<style>
    .yemek-ekle-page {
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
        display: flex;
        align-items: center;
        gap: 5px;
    }
    .form-lang-box input,
    .form-lang-box textarea {
        font-size: 0.85rem;
    }
    .form-lang-box textarea {
        resize: vertical;
        min-height: 70px;
    }
    .form-lang-box.ru .lang-title { color: #d52b1e; }
    .form-lang-box.uz .lang-title { color: #1eb53a; }
    .form-lang-box.en .lang-title { color: #012169; }

    .cat-checkbox-box {
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 12px;
        height: 200px;
        overflow-y: auto;
        background: #fff;
    }
    .cat-checkbox-box label {
        font-size: 0.9rem;
    }
    .cat-checkbox-box::-webkit-scrollbar {
        width: 8px;
    }
    .cat-checkbox-box::-webkit-scrollbar-thumb {
        background: #ced4da;
        border-radius: 4px;
    }

    .image-preview-box {
        border: 2px dashed #ced4da;
        border-radius: 8px;
        padding: 15px;
        text-align: center;
        background: #f8f9fa;
        min-height: 200px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }
    .image-preview-box img {
        max-width: 100%;
        max-height: 150px;
        border-radius: 6px;
    }
    .image-preview-box .placeholder-icon {
        font-size: 2.5rem;
        color: #adb5bd;
    }
</style>

<div class="container-fluid yemek-ekle-page">
    <h4 class="text-center mb-2">
        🍽️ <?php echo htmlspecialchars(isset($lang['addfood']) ? $lang['addfood'] : 'Добавить блюдо'); ?>
    </h4>

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $messageType; ?> py-2 text-center mb-2">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" action="yemek_ekle.php">

        <!-- 3 DİL YAN YANA -->
        <div class="row g-2 mb-2">

            <div class="col-md-4">
                <div class="form-lang-box ru">
                    <div class="lang-title">🇷🇺 RU — Название и описание</div>
                    <input name="ru" class="form-control form-control-sm mb-2" placeholder="Название (RU)" required>
                    <textarea name="description_ru" class="form-control form-control-sm" placeholder="Описание (RU)"></textarea>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-lang-box uz">
                    <div class="lang-title">🇺🇿 UZ — Nomi va ta'rifi</div>
                    <input name="uz" class="form-control form-control-sm mb-2" placeholder="Taom nomi (UZ)" required>
                    <textarea name="description_uz" class="form-control form-control-sm" placeholder="Ta'rif (UZ)"></textarea>
                </div>
            </div>

            <div class="col-md-4">
                <div class="form-lang-box en">
                    <div class="lang-title">🇬🇧 EN — Name & Description</div>
                    <input name="en" class="form-control form-control-sm mb-2" placeholder="Dish name (EN)">
                    <textarea name="description_en" class="form-control form-control-sm" placeholder="Description (EN)"></textarea>
                </div>
            </div>

        </div>

        <!-- FİYAT + KATEGORİ + FOTO YAN YANA -->
        <div class="row g-2 mb-2">

            <!-- Fiyat -->
            <div class="col-md-3">
                <label class="form-label small fw-bold mb-1">
                    💰 <?php echo htmlspecialchars(isset($lang['foodprice']) ? $lang['foodprice'] : 'Цена (UZS)'); ?>
                </label>
                <input type="number" step="0.01" name="price" class="form-control" placeholder="Narxi (UZS)" required>
            </div>

            <!-- Kategoriler -->
            <div class="col-md-5">
                <label class="form-label small fw-bold mb-1">
                    📁 <?php echo htmlspecialchars(isset($lang['selectcategory']) ? $lang['selectcategory'] : 'Категории'); ?>
                </label>
                <div class="cat-checkbox-box">
                    <?php printCatsCheckbox(null, $catsByParent); ?>
                </div>
            </div>

            <!-- Fotoğraf -->
            <div class="col-md-4">
                <label class="form-label small fw-bold mb-1">
                    📷 <?php echo htmlspecialchars(isset($lang['foodphotoadd']) ? $lang['foodphotoadd'] : 'Фото блюда'); ?>
                </label>
                <div class="image-preview-box" id="imagePreviewBox">
                    <div class="placeholder-icon">
                        <i class="fa fa-image"></i>
                    </div>
                    <p class="text-muted small mb-2 mt-2">Изображение не выбрано</p>
                    <input type="file" name="image" id="food_image" class="form-control form-control-sm" accept="image/jpeg,image/png" style="max-width: 250px;">
                </div>
            </div>

        </div>

        <!-- BUTONLAR -->
        <div class="text-center">
            <button type="submit" name="food_add" class="btn btn-primary">
                <i class="fa fa-plus"></i>
                <?php echo htmlspecialchars(isset($lang['add']) ? $lang['add'] : 'Добавить'); ?>
            </button>
            <a href="yemek_list.php" class="btn btn-secondary">
                <i class="fa fa-times"></i>
                <?php echo htmlspecialchars(isset($lang['cancel']) ? $lang['cancel'] : 'Отмена'); ?>
            </a>
        </div>

    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var fileInput = document.getElementById('food_image');
    var previewBox = document.getElementById('imagePreviewBox');

    if (fileInput) {
        fileInput.addEventListener('change', function() {
            var file = this.files[0];
            if (!file) return;

            var reader = new FileReader();
            reader.onload = function(e) {
                previewBox.innerHTML = ''
                    + '<img src="' + e.target.result + '" alt="Preview">'
                    + '<input type="file" name="image" id="food_image" class="form-control form-control-sm mt-2" accept="image/jpeg,image/png" style="max-width: 250px;">'
                    + '<p class="text-muted small mb-0 mt-1">' + file.name + '</p>';

                // Yeni input'a listener ekle
                var newInput = previewBox.querySelector('input[type=file]');
                newInput.addEventListener('change', arguments.callee);
                // Not: arguments.callee strict mode'da çalışmaz ama burada strict yok
            };
            reader.readAsDataURL(file);
        });
    }

    // F5 koruması
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
});
</script>