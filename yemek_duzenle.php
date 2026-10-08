<?php
require_once __DIR__ . '/config.php';

// ==================== POST İŞLEMİ ====================
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['food_update']) && $id > 0) {
    $ru = trim(isset($_POST['ru']) ? $_POST['ru'] : '');
    $uz = trim(isset($_POST['uz']) ? $_POST['uz'] : '');
    $en = trim(isset($_POST['en']) ? $_POST['en'] : '');
    $description_ru = trim(isset($_POST['description_ru']) ? $_POST['description_ru'] : '');
    $description_uz = trim(isset($_POST['description_uz']) ? $_POST['description_uz'] : '');
    $description_en = trim(isset($_POST['description_en']) ? $_POST['description_en'] : '');
    $price = trim(isset($_POST['price']) ? $_POST['price'] : '');
    $category_id = isset($_POST['category']) ? (int)$_POST['category'] : 0;

    $image_path = null;
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
        if ($image_path) {
            $stmt = $pdo->prepare("UPDATE menu_items SET category_id=?, ru=?, uz=?, en=?, price=?, description_ru=?, description_uz=?, description_en=?, image=? WHERE id=?");
            $stmt->execute(array($category_id, $ru, $uz, $en, $price, $description_ru, $description_uz, $description_en, $image_path, $id));
        } else {
            $stmt = $pdo->prepare("UPDATE menu_items SET category_id=?, ru=?, uz=?, en=?, price=?, description_ru=?, description_uz=?, description_en=? WHERE id=?");
            $stmt->execute(array($category_id, $ru, $uz, $en, $price, $description_ru, $description_uz, $description_en, $id));
        }
        $message = 'Блюдо успешно обновлено.';
        $messageType = 'success';
    } catch (PDOException $e) {
        error_log("Yemek güncelleme hatası: " . $e->getMessage());
        $message = 'Во время обновления произошла ошибка.';
        $messageType = 'danger';
    }
}

// ==================== YEMEK VERİSİNİ ÇEK ====================
$row = null;
if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE id = ?");
    $stmt->execute(array($id));
    $row = $stmt->fetch();
}

// ==================== KATEGORİLERİ ÇEK ====================
$allCategories = $pdo->query("SELECT id, ru, parent_id FROM categories ORDER BY ru ASC")->fetchAll();
$catsByParent = array();
foreach ($allCategories as $c) {
    $catsByParent[$c['parent_id']][] = $c;
}

// ==================== HTML ====================
require_once __DIR__ . '/header.php';

function printCatsForEditSelect($parent_id, $catsByParent, $prefix = '', $sel = 0) {
    if (!isset($catsByParent[$parent_id])) return;
    foreach ($catsByParent[$parent_id] as $cat) {
        $selected = ($cat['id'] == $sel) ? 'selected' : '';
        echo '<option value="' . (int)$cat['id'] . '" ' . $selected . '>' . htmlspecialchars($prefix . $cat['ru']) . '</option>';
        printCatsForEditSelect($cat['id'], $catsByParent, $prefix . '— ', $sel);
    }
}
?>
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">

<div class="container-fluid" style="padding: 10px 20px;">
    <div class="card shadow-sm">
        <div class="card-body">

            <h4 class="card-title text-center mb-3">
                ✏️ <?php echo htmlspecialchars(isset($lang['editfood']) ? $lang['editfood'] : 'Редактировать блюдо'); ?>
            </h4>

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $messageType; ?> text-center py-2"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <?php if (!$row): ?>
                <div class="alert alert-danger text-center">Yemek bulunamadı veya geçersiz ID.</div>
            <?php else: ?>

            <form method="POST" enctype="multipart/form-data" action="yemek_duzenle.php?id=<?php echo $id; ?>">

                <!-- ÜST SIRA: Kategori + Fiyat + Resim -->
                <div class="row g-2 mb-2">
                    <div class="col-md-4">
                        <label class="form-label small mb-1"><?php echo htmlspecialchars(isset($lang['selectcategory']) ? $lang['selectcategory'] : 'Категория'); ?></label>
                        <select name="category" id="categorySelect" class="form-select form-select-sm" required>
                            <option value=""><?php echo htmlspecialchars(isset($lang['selectcategory']) ? $lang['selectcategory'] : 'Выберите'); ?></option>
                            <?php printCatsForEditSelect(null, $catsByParent, '', $row['category_id']); ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-1"><?php echo htmlspecialchars(isset($lang['foodprice']) ? $lang['foodprice'] : 'Цена'); ?></label>
                        <input type="number" step="0.01" name="price" class="form-control form-control-sm" value="<?php echo htmlspecialchars(isset($row['price']) ? $row['price'] : ''); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small mb-1"><?php echo htmlspecialchars(isset($lang['foodphoto']) ? $lang['foodphoto'] : 'Фото'); ?></label>
                        <div class="d-flex align-items-center gap-2">
                            <?php if (!empty($row['image'])): ?>
                                <img src="<?php echo htmlspecialchars($row['image']); ?>" style="height: 35px; border-radius: 4px; border: 1px solid #ddd;">
                            <?php endif; ?>
                            <input type="file" name="image" class="form-control form-control-sm" accept="image/jpeg,image/png">
                        </div>
                    </div>
                </div>

                <!-- ALT SIRA: 3 DİL YAN YANA -->
                <div class="row g-2 mb-2">

                    <div class="col-md-4">
                        <div class="border rounded p-2 h-100">
                            <div class="fw-bold small mb-1" style="color: #d52b1e;">RU</div>
                            <input name="ru" class="form-control form-control-sm mb-1" placeholder="Название (RU)" value="<?php echo htmlspecialchars(isset($row['ru']) ? $row['ru'] : ''); ?>" required>
                            <textarea name="description_ru" class="form-control form-control-sm" placeholder="Описание (RU)" rows="4"><?php echo htmlspecialchars(isset($row['description_ru']) ? $row['description_ru'] : ''); ?></textarea>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="border rounded p-2 h-100">
                            <div class="fw-bold small mb-1" style="color: #1eb53a;">UZ</div>
                            <input name="uz" class="form-control form-control-sm mb-1" placeholder="Taom nomi (UZ)" value="<?php echo htmlspecialchars(isset($row['uz']) ? $row['uz'] : ''); ?>" required>
                            <textarea name="description_uz" class="form-control form-control-sm" placeholder="Ta'rif (UZ)" rows="4"><?php echo htmlspecialchars(isset($row['description_uz']) ? $row['description_uz'] : ''); ?></textarea>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="border rounded p-2 h-100">
                            <div class="fw-bold small mb-1" style="color: #012169;">EN</div>
                            <input name="en" class="form-control form-control-sm mb-1" placeholder="Dish name (EN)" value="<?php echo htmlspecialchars(isset($row['en']) ? $row['en'] : ''); ?>">
                            <textarea name="description_en" class="form-control form-control-sm" placeholder="Description (EN)" rows="4"><?php echo htmlspecialchars(isset($row['description_en']) ? $row['description_en'] : ''); ?></textarea>
                        </div>
                    </div>

                </div>

                <!-- BUTONLAR -->
                <div class="text-center">
                    <button type="submit" name="food_update" class="btn btn-primary">
                        <?php echo htmlspecialchars(isset($lang['savechanges']) ? $lang['savechanges'] : 'Сохранить'); ?>
                    </button>
                    <a href="yemek_list.php" class="btn btn-secondary">
                        <?php echo htmlspecialchars(isset($lang['cancel']) ? $lang['cancel'] : 'Отмена'); ?>
                    </a>
                </div>

            </form>

            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('categorySelect')) {
        new TomSelect('#categorySelect', { create: false });
    }
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
});
</script>