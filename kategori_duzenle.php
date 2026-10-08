<?php
require_once __DIR__ . '/config.php';

// ==================== POST İŞLEMİ ====================
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cat_update']) && $id > 0) {
    $ru = trim(isset($_POST['cat_ru']) ? $_POST['cat_ru'] : '');
    $uz = trim(isset($_POST['cat_uz']) ? $_POST['cat_uz'] : '');
    $en = trim(isset($_POST['cat_en']) ? $_POST['cat_en'] : '');
    $parent_id_val = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;

    $image_path = null;
    if (!empty($_FILES['cat_image']['name']) && $_FILES['cat_image']['error'] === UPLOAD_ERR_OK) {
        $source = $_FILES['cat_image']['tmp_name'];
        $image_info = @getimagesize($source);

        if ($image_info) {
            $mime = $image_info['mime'];
            $new_filename = 'cat_' . uniqid() . '.jpg';
            $target_path = 'images/' . $new_filename;

            if (function_exists('imagecreatetruecolor') && ($mime === 'image/jpeg' || $mime === 'image/png')) {
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
                    imagejpeg($dst, $target_path, 80);
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
            $stmt = $pdo->prepare("UPDATE categories SET ru=?, uz=?, en=?, parent_id=?, image=? WHERE id=?");
            $stmt->execute(array($ru, $uz, $en, $parent_id_val, $image_path, $id));
        } else {
            $stmt = $pdo->prepare("UPDATE categories SET ru=?, uz=?, en=?, parent_id=? WHERE id=?");
            $stmt->execute(array($ru, $uz, $en, $parent_id_val, $id));
        }
        header('Location: kategori_duzenle.php?id=' . $id . '&saved=1');
        exit;
    } catch (PDOException $e) {
        error_log("Kategori güncelleme hatası: " . $e->getMessage());
        $error = isset($lang['error']) ? $lang['error'] : 'Ошибка';
    }
}

// ==================== SELECT ====================
$row = null;
if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute(array($id));
    $row = $stmt->fetch();
}

$cats = array();
if ($row) {
    $stmt = $pdo->prepare("SELECT id, ru FROM categories WHERE id != ? AND parent_id IS NULL ORDER BY ru ASC");
    $stmt->execute(array($id));
    $cats = $stmt->fetchAll();
}

// ==================== HTML ====================
require_once __DIR__ . '/header.php';
?>
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">

<div class="container-fluid" style="padding: 10px 20px;">
    <div class="card shadow-sm">
        <div class="card-body">

            <h4 class="card-title text-center mb-3">
                ✏️ <?php echo htmlspecialchars(isset($lang['editcategory']) ? $lang['editcategory'] : 'Редактировать категорию'); ?>
            </h4>

            <?php if (isset($_GET['saved'])): ?>
                <div class="alert alert-success text-center py-2">
                    <?php echo htmlspecialchars(isset($lang['updated']) ? $lang['updated'] : 'Обновлено'); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger text-center py-2"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if (!$row): ?>
                <div class="alert alert-danger text-center">
                    <?php echo htmlspecialchars(isset($lang['catnotfound']) ? $lang['catnotfound'] : 'Категория не найдена'); ?>
                </div>
            <?php else: ?>

            <form method="POST" enctype="multipart/form-data" action="kategori_duzenle.php?id=<?php echo $id; ?>">

                <!-- ÜST SIRA: 3 DİL YAN YANA -->
                <div class="row g-2 mb-2">

                    <div class="col-md-4">
                        <div class="border rounded p-2 h-100">
                            <div class="fw-bold small mb-1" style="color: #d52b1e;">RU</div>
                            <input required name="cat_ru" class="form-control form-control-sm" placeholder="Название (RU)" value="<?php echo htmlspecialchars(isset($row['ru']) ? $row['ru'] : ''); ?>">
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="border rounded p-2 h-100">
                            <div class="fw-bold small mb-1" style="color: #1eb53a;">UZ</div>
                            <input required name="cat_uz" class="form-control form-control-sm" placeholder="Kategoriya nomi (UZ)" value="<?php echo htmlspecialchars(isset($row['uz']) ? $row['uz'] : ''); ?>">
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="border rounded p-2 h-100">
                            <div class="fw-bold small mb-1" style="color: #012169;">EN</div>
                            <input name="cat_en" class="form-control form-control-sm" placeholder="Category name (EN)" value="<?php echo htmlspecialchars(isset($row['en']) ? $row['en'] : ''); ?>">
                        </div>
                    </div>

                </div>

                <!-- ALT SIRA: ÜST KATEGORİ + RESİM -->
                <div class="row g-2 mb-2">

                    <div class="col-md-6">
                        <div class="border rounded p-2 h-100">
                            <div class="fw-bold small mb-1">
                                <?php echo htmlspecialchars(isset($lang['parentcat']) ? $lang['parentcat'] : 'Родительская категория'); ?>
                            </div>
                            <select name="parent_id" id="parentCategorySelect" class="form-select form-select-sm">
                                <option value="">
                                    <?php echo htmlspecialchars(isset($lang['maincat']) ? $lang['maincat'] : 'Основная'); ?>
                                </option>
                                <?php foreach ($cats as $cat): ?>
                                    <option value="<?php echo (int)$cat['id']; ?>" <?php echo ($row['parent_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['ru']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="border rounded p-2 h-100">
                            <div class="fw-bold small mb-1">
                                <?php echo htmlspecialchars(isset($lang['catphoto']) ? $lang['catphoto'] : 'Фото категории'); ?>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <?php if (!empty($row['image'])): ?>
                                    <img src="<?php echo htmlspecialchars($row['image']); ?>" style="height: 38px; border-radius: 4px; border: 1px solid #ddd;">
                                <?php endif; ?>
                                <input type="file" name="cat_image" class="form-control form-control-sm" accept="image/jpeg,image/png">
                            </div>
                        </div>
                    </div>

                </div>

                <!-- BUTONLAR -->
                <div class="text-center">
                    <button type="submit" name="cat_update" class="btn btn-primary">
                        <?php echo htmlspecialchars(isset($lang['save']) ? $lang['save'] : 'Сохранить'); ?>
                    </button>
                    <a href="kategori_list.php" class="btn btn-secondary">
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
    if (document.getElementById('parentCategorySelect')) {
        new TomSelect('#parentCategorySelect', { create: false });
    }
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>