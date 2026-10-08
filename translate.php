<?php
require_once __DIR__ . '/config.php';   // session + $pdo

// Kategorileri çek (id, ru)
$categories = $pdo->query("SELECT id, ru FROM categories ORDER BY ru")->fetchAll(PDO::FETCH_ASSOC);

// Seçili kategori
$selected_category = isset($_GET['cat']) ? intval($_GET['cat']) : 0;

// Kayıt işlemi
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['translations'])) {
    foreach ($_POST['translations'] as $id => $descs) {
        $description_ru = trim($descs['description_ru']);
        $description_uz = trim($descs['description_uz']);
        $description_en = trim($descs['description_en']);
        $stmt = $pdo->prepare("UPDATE menu_items SET description_ru = ?, description_uz = ?, description_en = ? WHERE id = ?");
        $stmt->execute([
            $description_ru,
            $description_uz,
            $description_en,
            $id
        ]);
    }
    $success = true;
}

// Yemekleri seçili kategoriye göre çek
$where = $selected_category > 0 ? "WHERE category_id = $selected_category" : "";
$items = $pdo->query("SELECT id, ru, uz, en, description_ru, description_uz, description_en FROM menu_items $where ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/header.php';
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Yemek Açıklamalarını Düzenle</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(135deg, #f0f4f7, #ffffff);
      min-height: 100vh;
      padding-top: 70px;
    }
    .navbar {
      position: fixed;
      top: 0;
      width: 100%;
      z-index: 1000;
      background-color: #004085;
    }
    .navbar-brand {
      color: #fff;
      font-weight: bold;
    }
    .main-container {
      max-width: 1200px;
      margin: auto;
    }
    .item {
      background: #ffffff;
      padding: 20px;
      margin-bottom: 20px;
      border-radius: 8px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.05);
      transition: box-shadow 0.3s;
    }
    .item:hover {
      box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }
    .food-title {
      font-size: 1.2rem;
      font-weight: 600;
      color: #004085;
    }
    .form-tabs {
      margin-top: 10px;
    }
    .form-tabs .nav-link {
      font-weight: 500;
    }
    .save-btn-sticky {
      position: sticky;
      bottom: 0;
      z-index: 10;
      padding: 15px;
      background: #f7f7f9;
      border-top: 1px solid #ddd;
      text-align: right;
    }
    textarea {
      resize: vertical;
      min-height: 70px;
    }
  </style>
</head>

<body>
  <div class="container-fluid main-container">
    <h2 class="mb-4">Редактировать описания блюд</h2>


    
    <form method="get" class="mb-4">
        <div class="row g-2 align-items-end">
            <div class="col-md-6">
                <label class="form-label" for="cat">Фильтр по категории:</label>
                <select class="form-select" name="cat" id="cat" onchange="this.form.submit()">
                    <option value="0">Все категории</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= ($cat['id'] == $selected_category ? 'selected' : '') ?>>
                            <?= htmlspecialchars($cat['ru']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </form>
    
    
    <?php if ($success): ?>
        <div class="alert alert-success text-center">Сохранено успешно!</div>
    <?php endif; ?>

    <?php if (count($items)): ?>
    <form method="post" autocomplete="off">
        <?php foreach ($items as $item): ?>
            <div class="item mb-3">
                <div class="food-title mb-2">
                    <?= htmlspecialchars($item['ru']) ?> / <?= htmlspecialchars($item['uz']) ?> / <?= htmlspecialchars($item['en']) ?>
                </div>
                <label class="desc-label">RU Açıklama:</label>
                <textarea class="form-control mb-2" name="translations[<?= $item['id'] ?>][description_ru]"><?= htmlspecialchars($item['description_ru']) ?></textarea>
                <label class="desc-label">UZ Açıklama:</label>
                <textarea class="form-control mb-2" name="translations[<?= $item['id'] ?>][description_uz]"><?= htmlspecialchars($item['description_uz']) ?></textarea>
                <label class="desc-label">EN Açıklama:</label>
                <textarea class="form-control" name="translations[<?= $item['id'] ?>][description_en]"><?= htmlspecialchars($item['description_en']) ?></textarea>
            <button type="submit" class="btn btn-outline-primary w-100">💾 Сохранить</button>

            </div>
        <?php endforeach; ?>
        <button type="submit" class="btn btn-primary save-btn mt-3">Сохранить все</button>
    </form>
    <?php else: ?>
        <div class="alert alert-info mt-4">В этой категории не найдено ни одного продукта.</div>
    <?php endif; ?>
</div>
</body>
</html>
