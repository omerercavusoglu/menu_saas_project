<?php
require_once __DIR__ . '/config.php';

// ==================== POST İŞLEMLERİ ====================
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['meal_id'], $_POST['meal_action'])) {
    $meal_id = (int)$_POST['meal_id'];
    $is_active = ($_POST['meal_action'] === 'activate') ? 1 : 0;
    $pdo->prepare("UPDATE menu_items SET is_active=? WHERE id=?")->execute(array($is_active, $meal_id));
    $_SESSION['flash_yemeklist'] = array('msg' => $is_active ? 'Блюдо готово.
' : 'Блюдо готово.', 'type' => $is_active ? 'success' : 'secondary');
    header('Location: yemek_list.php?cat=' . (int)(isset($_POST['cat_id']) ? $_POST['cat_id'] : 0));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['move_id'], $_POST['move'])) {
    $move_id = (int)$_POST['move_id'];
    $move = $_POST['move'] === 'up' ? 'up' : 'down';

    $stmt = $pdo->prepare("SELECT sort_order FROM categories WHERE id = ?");
    $stmt->execute(array($move_id));
    $current = (int)$stmt->fetchColumn();

    if ($move === 'up') {
        $stmt = $pdo->prepare("SELECT id, sort_order FROM categories WHERE sort_order < ? ORDER BY sort_order DESC LIMIT 1");
    } else {
        $stmt = $pdo->prepare("SELECT id, sort_order FROM categories WHERE sort_order > ? ORDER BY sort_order ASC LIMIT 1");
    }
    $stmt->execute(array($current));
    $other = $stmt->fetch();

    if ($other) {
        $pdo->prepare("UPDATE categories SET sort_order = ? WHERE id = ?")->execute(array($other['sort_order'], $move_id));
        $pdo->prepare("UPDATE categories SET sort_order = ? WHERE id = ?")->execute(array($current, $other['id']));
    }
    header('Location: yemek_list.php?cat=' . (int)(isset($_POST['cat_id']) ? $_POST['cat_id'] : 0));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cat_id_action'], $_POST['action'])) {
    $cat_id = (int)$_POST['cat_id_action'];
    $is_active = ($_POST['action'] === 'activate') ? 1 : 0;
    $pdo->prepare("UPDATE categories SET is_active=? WHERE id=?")->execute(array($is_active, $cat_id));
    $_SESSION['flash_yemeklist'] = array('msg' => $is_active ? 'Kategori aktifleştirildi.' : 'Kategori pasifleştirildi.', 'type' => $is_active ? 'success' : 'secondary');
    header('Location: yemek_list.php?cat=' . $cat_id);
    exit;
}

if (isset($_SESSION['flash_yemeklist'])) {
    $message = $_SESSION['flash_yemeklist']['msg'];
    $messageType = $_SESSION['flash_yemeklist']['type'];
    unset($_SESSION['flash_yemeklist']);
}

// ==================== VERİ ÇEK ====================
$selected_cat = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;

$categoriesRaw = $pdo->query("SELECT id, ru, uz, en, parent_id, is_active, sort_order FROM categories ORDER BY sort_order ASC")->fetchAll();

$catsById = array();
$catsByParent = array();
foreach ($categoriesRaw as $cat) {
    $catsById[$cat['id']] = $cat;
    $catsByParent[$cat['parent_id']][] = $cat;
}

$selectedCategory = ($selected_cat > 0 && isset($catsById[$selected_cat])) ? $catsById[$selected_cat] : null;

if (!$selectedCategory && !empty($categoriesRaw)) {
    foreach ($categoriesRaw as $c) {
        if ($c['parent_id'] === null || $c['parent_id'] == 0) {
            $selectedCategory = $c;
            $selected_cat = (int)$c['id'];
            break;
        }
    }
}

// Seçili kategorinin yemekleri
$meals = array();
if ($selected_cat > 0) {
    $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE category_id = ? ORDER BY sort_order ASC, id ASC");
    $stmt->execute(array($selected_cat));
    $meals = $stmt->fetchAll();
}

// TÜM yemekler (arama için JSON'a gömülecek)
$allMeals = $pdo->query("
    SELECT m.id, m.category_id, m.ru, m.uz, m.en, m.price, m.image, m.is_active, m.sort_order,
           c.ru AS cat_ru
    FROM menu_items m
    LEFT JOIN categories c ON m.category_id = c.id
    ORDER BY m.id ASC
")->fetchAll();

require_once __DIR__ . '/header.php';
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

<style>
    .yemek-page { height: calc(100vh - 80px); overflow: hidden; padding: 10px 15px; }
    .yemek-cols { height: calc(100% - 60px); }
    .yemek-col {
    height: calc(100vh - 140px);   /* ← buraya dikkat */
    overflow-y: auto;
}

    .cat-menu { list-style: none; padding: 0; margin: 0; }
    .cat-menu li { margin-bottom: 4px; }
    .cat-menu a {
        display: flex; align-items: center; justify-content: space-between;
        padding: 8px 10px; border-radius: 6px; text-decoration: none;
        color: #333; font-size: 0.9rem; transition: all 0.15s;
        border-left: 3px solid transparent;
    }
    .cat-menu a:hover { background: #f0f4f8; }
    .cat-menu a.active { background: #e7f1ff; border-left-color: #0d6efd; color: #0d6efd; font-weight: 600; }
    .cat-menu a .cat-name { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .cat-menu .sub-item a { padding-left: 25px; font-size: 0.85rem; }

    .meal-card {
        display: flex; align-items: center; gap: 12px;
        padding: 10px; border: 1px solid #e9ecef; border-radius: 8px;
        margin-bottom: 8px; background: #fff; transition: box-shadow 0.15s;
    }
    .meal-card:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
    .meal-card.inactive { opacity: 0.6; }
    .meal-thumb {
        width: 60px; height: 60px; object-fit: cover;
        border-radius: 6px; border: 1px solid #ddd; flex-shrink: 0;
    }
    .meal-thumb-empty {
        width: 60px; height: 60px; background: #f8f9fa; border-radius: 6px;
        display: flex; align-items: center; justify-content: center;
        color: #adb5bd; border: 1px dashed #ddd; flex-shrink: 0;
    }
    .meal-info { flex: 1; min-width: 0; }
    .meal-name { font-weight: 600; font-size: 0.95rem; color: #212529; margin-bottom: 2px; }
    .meal-name-en { font-size: 0.8rem; color: #6c757d; margin-bottom: 4px; }
    .meal-cat-badge { font-size: 0.7rem; color: #0d6efd; background: #e7f1ff; padding: 1px 6px; border-radius: 8px; display: inline-block; margin-left: 4px; }
    .meal-price { font-weight: 700; color: #198754; font-size: 0.95rem; }
    .meal-actions { display: flex; flex-direction: column; gap: 4px; flex-shrink: 0; }
    .meal-actions .btn { font-size: 0.75rem; padding: 3px 8px; white-space: nowrap; }
    .cat-header {
        display: flex; justify-content: space-between; align-items: center;
        margin-bottom: 12px; padding-bottom: 10px; border-bottom: 2px solid #e9ecef;
    }
    .cat-header h5 { margin: 0; font-size: 1.1rem; }
    .cat-actions { display: flex; gap: 4px; }
    .cat-actions .btn { font-size: 0.75rem; padding: 3px 8px; }
    .search-wrap { position: relative; }
    .search-wrap .search-icon {
        position: absolute; left: 10px; top: 50%; transform: translateY(-50%);
        color: #adb5bd; pointer-events: none;
    }
    .search-wrap input { padding-left: 32px; }
    .search-wrap .clear-btn {
        position: absolute; right: 6px; top: 50%; transform: translateY(-50%);
        border: none; background: transparent; color: #adb5bd;
        cursor: pointer; padding: 2px 6px; display: none;
    }
    .search-wrap .clear-btn:hover { color: #dc3545; }
    .search-wrap input.has-value + .clear-btn { display: block; }
        /* === SIRALAMA KUTUSU STİLLERİ === */
    .meal-sort-input {
        width: 55px;
        text-align: center;
        font-weight: 700;
        font-size: 0.85rem;
        padding: 2px 4px;
        border: 1px solid #ced4da;
        border-radius: 5px;
        background: #f8f9fa;
        flex-shrink: 0;
        transition: all 0.15s;
    }
    .meal-sort-input:focus {
        outline: none;
        border-color: #0d6efd;
        background: #fff;
        box-shadow: 0 0 0 3px rgba(13,110,253,0.15);
    }
    .meal-sort-input.saving {
        background: #fff3cd;
        border-color: #ffc107;
    }
    .meal-sort-input.saved {
        background: #d4edda;
        border-color: #198754;
    }
    .meal-sort-input.error {
        background: #f8d7da;
        border-color: #dc3545;
    }
    
        /* Arama sonuçlarında sıralama kutusu gizli */
    #searchResults .meal-sort-input {
        display: none !important;
    }
</style>

<div class="container-fluid yemek-page">
    <h4 class="text-center mb-2">
        📜 <?php echo htmlspecialchars(isset($lang['menulist']) ? $lang['menulist'] : 'Yemek Listesi'); ?>

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $messageType; ?> py-2 text-center mb-2">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="row g-3 yemek-cols">

        <!-- SOL: KATEGORİ MENÜSÜ -->
        <div class="col-md-3 yemek-col">
            <div class="card shadow-sm h-100">
                <div class="card-body p-2">
                    <h6 class="fw-bold mb-2 px-2">📁 Категории</h6>
                    <ul class="cat-menu" id="catMenu">
                        <?php
                        function renderCatMenu($parent_id, $catsByParent, $selected_cat, $level = 0) {
                            if (!isset($catsByParent[$parent_id])) return;
                            foreach ($catsByParent[$parent_id] as $cat) {
                                $isActive = ($cat['id'] == $selected_cat) ? ' active' : '';
                                $catLabel = htmlspecialchars($cat['ru']);
                                $activeBadge = $cat['is_active'] ? '' : ' <span class="badge bg-warning text-dark" style="font-size:0.6rem;">Не в меню</span>';
                                $subClass = $level > 0 ? 'sub-item' : '';

                                echo '<li class="' . $subClass . '">';
                                echo '<a href="?cat=' . (int)$cat['id'] . '" class="' . $isActive . '" data-cat-id="' . (int)$cat['id'] . '">';
                                echo '<span class="cat-name">' . ($level > 0 ? '↳ ' : '') . $catLabel . $activeBadge . '</span>';
                                echo '</a>';
                                echo '</li>';

                                renderCatMenu($cat['id'], $catsByParent, $selected_cat, $level + 1);
                            }
                        }
                        renderCatMenu(null, $catsByParent, $selected_cat);
                        ?>
                    </ul>
                </div>
            </div>
        </div>

        <!-- SAĞ: YEMEKLER -->
        <div class="col-md-9 yemek-col">
            <div class="card shadow-sm h-100">
                <div class="card-body">

                    <!-- ARAMA KUTUSU (her zaman görünür) -->
                    <div class="search-wrap mb-2">
                        <i class="fa fa-search search-icon"></i>
                        <input type="text" id="mealSearch" class="form-control" 
                               placeholder="Выполнить поиск по всем блюдам... (по названию или цене)" autocomplete="off">
                        <button type="button" class="clear-btn" id="clearSearch" title="Очистить">✕</button>
                    </div>

                    <!-- KATEGORİ BAŞLIĞI (arama yokken görünür) -->
                    <div id="catHeaderBlock">
                        <?php if ($selectedCategory): ?>
                            <div class="cat-header">
                                <div>
                                    <h5>
                                        <?php echo htmlspecialchars($selectedCategory['ru']); ?>
                                        <?php if ($selectedCategory['is_active']): ?>
                                            <span class="badge bg-success">В меню</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">Не в меню</span>
                                        <?php endif; ?>
                                    </h5>
                                    <small class="text-muted">
                                        <?php echo htmlspecialchars($selectedCategory['uz']); ?>
                                        <?php if (!empty($selectedCategory['en'])): ?>
                                            / <?php echo htmlspecialchars($selectedCategory['en']); ?>
                                        <?php endif; ?>
                                    </small>
                                </div>
                                <div class="cat-actions">
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="move_id" value="<?php echo (int)$selectedCategory['id']; ?>">
                                        <input type="hidden" name="move" value="up">
                                        <input type="hidden" name="cat_id" value="<?php echo (int)$selected_cat; ?>">
                                        <button class="btn btn-outline-secondary" type="submit" title="Yukarı"><i class="fa fa-arrow-up"></i></button>
                                    </form>
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="move_id" value="<?php echo (int)$selectedCategory['id']; ?>">
                                        <input type="hidden" name="move" value="down">
                                        <input type="hidden" name="cat_id" value="<?php echo (int)$selected_cat; ?>">
                                        <button class="btn btn-outline-secondary" type="submit" title="Aşağı"><i class="fa fa-arrow-down"></i></button>
                                    </form>

                                    <?php if ($selectedCategory['is_active']): ?>
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="cat_id_action" value="<?php echo (int)$selectedCategory['id']; ?>">
                                            <input type="hidden" name="action" value="deactivate">
                                            <button class="btn btn-secondary" type="submit">Pasif Yap</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="cat_id_action" value="<?php echo (int)$selectedCategory['id']; ?>">
                                            <input type="hidden" name="action" value="activate">
                                            <button class="btn btn-success" type="submit">Aktif Yap</button>
                                        </form>
                                    <?php endif; ?>

                                    <a href="kategori_duzenle.php?id=<?php echo (int)$selectedCategory['id']; ?>" class="btn btn-info">
                                        <i class="fa fa-edit"></i> редактировать
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- SAYAÇ -->
                    <div class="text-muted small mb-2" id="mealCount">
                        <?php echo count($meals); ?> yemek
                    </div>

                    <!-- YEMEK LİSTESİ (arama yokken seçili kategori) -->
                    <div id="mealList">
                        <?php if (empty($meals)): ?>
                            <p class="text-muted text-center py-5">Bu kategoride yemek yok.</p>
                        <?php else: ?>
                            <?php foreach ($meals as $meal): ?>
    <div class="meal-card <?php echo $meal['is_active'] ? '' : 'inactive'; ?>">

        <!-- SIRA KUTUSU -->
        <input type="number"
               class="meal-sort-input"
               value="<?php echo (int)$meal['sort_order']; ?>"
               data-meal-id="<?php echo (int)$meal['id']; ?>"
               data-original="<?php echo (int)$meal['sort_order']; ?>"
               min="0"
               title="Sıra numarası">

        <?php if (!empty($meal['image'])): ?>
                                        <img src="<?php echo htmlspecialchars($meal['image']); ?>" class="meal-thumb" alt="">
                                    <?php else: ?>
                                        <div class="meal-thumb-empty"><i class="fa fa-image"></i></div>
                                    <?php endif; ?>

                                    <div class="meal-info">
                                        <div class="meal-name"><?php echo htmlspecialchars($meal['ru']); ?></div>
                                        <div class="meal-name-en">
                                            <?php echo htmlspecialchars($meal['uz']); ?>
                                            <?php if (!empty($meal['en'])): ?>
                                                / <?php echo htmlspecialchars($meal['en']); ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="meal-price"><?php echo number_format($meal['price'], 0, '', ' '); ?> UZS</div>
                                    </div>

                                    <div class="meal-actions">
                                        <?php if ($meal['is_active']): ?>
                                            <form method="post">
                                                <input type="hidden" name="meal_id" value="<?php echo (int)$meal['id']; ?>">
                                                <input type="hidden" name="meal_action" value="deactivate">
                                                <input type="hidden" name="cat_id" value="<?php echo (int)$selected_cat; ?>">
                                                <button class="btn btn-secondary w-100" type="submit">Pasif</button>
                                            </form>
                                        <?php else: ?>
                                            <form method="post">
                                                <input type="hidden" name="meal_id" value="<?php echo (int)$meal['id']; ?>">
                                                <input type="hidden" name="meal_action" value="activate">
                                                <input type="hidden" name="cat_id" value="<?php echo (int)$selected_cat; ?>">
                                                <button class="btn btn-success w-100" type="submit">Aktif</button>
                                            </form>
                                        <?php endif; ?>

                                        <a href="yemek_duzenle.php?id=<?php echo (int)$meal['id']; ?>" class="btn btn-warning w-100">
                                            <i class="fa fa-edit"></i> редактировать
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- ARAMA SONUÇLARI (JS ile doldurulacak) -->
                    <div id="searchResults"></div>

                    <p id="noResultMsg" class="text-muted text-center py-4" style="display:none;">
                        Sonuç bulunamadı.
                    </p>

                </div>
            </div>
        </div>

    </div>
</div>

<script>
window.ALL_MEALS = <?php echo json_encode($allMeals, JSON_UNESCAPED_UNICODE); ?>;
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var searchInput = document.getElementById('mealSearch');
    var clearBtn = document.getElementById('clearSearch');
    var catHeader = document.getElementById('catHeaderBlock');
    var mealList = document.getElementById('mealList');
    var searchResults = document.getElementById('searchResults');
    var countEl = document.getElementById('mealCount');
    var noResultEl = document.getElementById('noResultMsg');
    var catMenu = document.getElementById('catMenu');

    var allMeals = window.ALL_MEALS || [];
    var originalCount = mealList.querySelectorAll('.meal-card').length;
    var originalCountText = countEl.textContent;

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        return String(str)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    function formatPrice(p) {
        var num = parseFloat(p) || 0;
        return num.toLocaleString('ru-RU').replace(/,/g, ' ');
    }

        function buildMealCard(meal, showCat) {
        var img = meal.image
            ? '<img src="' + escapeHtml(meal.image) + '" class="meal-thumb" alt="">'
            : '<div class="meal-thumb-empty"><i class="fa fa-image"></i></div>';

        var catBadge = (showCat && meal.cat_ru)
            ? '<span class="meal-cat-badge">' + escapeHtml(meal.cat_ru) + '</span>'
            : '';

        var isActive = parseInt(meal.is_active, 10) === 1;
        var inactiveClass = isActive ? '' : ' inactive';
        var enPart = meal.en ? ' / ' + escapeHtml(meal.en) : '';
        var catId = parseInt(meal.category_id, 10) || 0;
        var mealId = parseInt(meal.id, 10);
        var sortOrder = parseInt(meal.sort_order, 10) || 0;

        var sortBox = '<input type="number" class="meal-sort-input"'
            + ' value="' + sortOrder + '"'
            + ' data-meal-id="' + mealId + '"'
            + ' data-original="' + sortOrder + '"'
            + ' min="0" title="Sıra numarası">';

        var toggleBtn;
        if (isActive) {
            toggleBtn = '<form method="post">'
                + '<input type="hidden" name="meal_id" value="' + mealId + '">'
                + '<input type="hidden" name="meal_action" value="deactivate">'
                + '<input type="hidden" name="cat_id" value="' + catId + '">'
                + '<button class="btn btn-secondary w-100" type="submit">Pasif</button>'
                + '</form>';
        } else {
            toggleBtn = '<form method="post">'
                + '<input type="hidden" name="meal_id" value="' + mealId + '">'
                + '<input type="hidden" name="meal_action" value="activate">'
                + '<input type="hidden" name="cat_id" value="' + catId + '">'
                + '<button class="btn btn-success w-100" type="submit">Aktif</button>'
                + '</form>';
        }

        return '<div class="meal-card' + inactiveClass + '">'
            + sortBox
            + img
            + '<div class="meal-info">'
            +   '<div class="meal-name">' + escapeHtml(meal.ru) + catBadge + '</div>'
            +   '<div class="meal-name-en">' + escapeHtml(meal.uz) + enPart + '</div>'
            +   '<div class="meal-price">' + formatPrice(meal.price) + ' UZS</div>'
            + '</div>'
            + '<div class="meal-actions">'
            +   toggleBtn
            +   '<a href="yemek_duzenle.php?id=' + mealId + '" class="btn btn-warning w-100"><i class="fa fa-edit"></i> редактировать</a>'
            + '</div>'
            + '</div>';
    }

    function resetView() {
        catHeader.style.display = '';
        mealList.style.display = '';
        searchResults.style.display = 'none';
        searchResults.innerHTML = '';
        countEl.textContent = originalCountText;
        noResultEl.style.display = 'none';
        catMenu.querySelectorAll('a').forEach(function(a) { a.style.pointerEvents = ''; });
    }

    function doSearch() {
        var q = searchInput.value.toLowerCase().trim();

        // Temizle butonu
        if (q === '') {
            clearBtn.style.display = 'none';
            searchInput.classList.remove('has-value');
        } else {
            clearBtn.style.display = '';
            searchInput.classList.add('has-value');
        }

        // Boşsa normale dön
        if (q === '') {
            resetView();
            return;
        }

        // Arama modu: kategori başlığı ve mevcut liste gizle
        catHeader.style.display = 'none';
        mealList.style.display = 'none';

        // Filtrele
        var matches = allMeals.filter(function(m) {
            var haystack = (
                (m.ru || '') + ' ' +
                (m.uz || '') + ' ' +
                (m.en || '') + ' ' +
                (m.cat_ru || '') + ' ' +
                (m.price || '')
            ).toLowerCase();
            return haystack.indexOf(q) !== -1;
        });

        // Render
        if (matches.length === 0) {
            searchResults.innerHTML = '';
            searchResults.style.display = 'none';
            noResultEl.style.display = '';
        } else {
            var html = '<div class="mb-2 fw-bold small text-primary">Arama sonuçları (' + matches.length + ')</div>';
            matches.forEach(function(m) {
                html += buildMealCard(m, true);
            });
            searchResults.innerHTML = html;
            searchResults.style.display = '';
            noResultEl.style.display = 'none';
        }

        countEl.textContent = matches.length + ' sonuç';
    }

    searchInput.addEventListener('input', doSearch);

    clearBtn.addEventListener('click', function() {
        searchInput.value = '';
        searchInput.focus();
        doSearch();
    });

    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            this.value = '';
            doSearch();
        }
    });

    // Ctrl+K veya / ile odaklan
    document.addEventListener('keydown', function(e) {
        var tag = document.activeElement.tagName;
        if (tag === 'INPUT' || tag === 'TEXTAREA') return;
        if ((e.ctrlKey && e.key === 'k') || e.key === '/') {
            e.preventDefault();
            searchInput.focus();
            searchInput.select();
        }
    });

    searchInput.focus();
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    function collectInputs() {
        return Array.prototype.slice.call(
            document.querySelectorAll('.meal-sort-input')
        );
    }

    function saveOrderFromInput(changedInput) {
        var card = changedInput.closest('.meal-card');
        if (!card) return;

        var container = card.parentElement;
        var cards = Array.prototype.slice.call(
            container.querySelectorAll('.meal-card')
        );

        var changedVal = parseInt(changedInput.value, 10);
        if (isNaN(changedVal)) changedVal = 0;

        cards.sort(function (a, b) {
            var ai = a.querySelector('.meal-sort-input');
            var bi = b.querySelector('.meal-sort-input');
            if (!ai || !bi) return 0;
            var av = (ai === changedInput) ? changedVal : (parseInt(ai.value, 10) || 0);
            var bv = (bi === changedInput) ? changedVal : (parseInt(bi.value, 10) || 0);
            return av - bv;
        });

        cards.forEach(function (c, idx) {
            var inp = c.querySelector('.meal-sort-input');
            if (inp) inp.value = idx;
        });

        cards.forEach(function (c) { container.appendChild(c); });

        var ids = cards.map(function (c) {
            var inp = c.querySelector('.meal-sort-input');
            return inp ? inp.getAttribute('data-meal-id') : null;
        }).filter(Boolean);

        if (ids.length === 0) return;

        changedInput.classList.add('saving');
        changedInput.classList.remove('saved', 'error');

        var fd = new FormData();
        ids.forEach(function (id, i) { fd.append('meal_ids[' + i + ']', id); });

        fetch('sort_meals.php', { method: 'POST', body: fd })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                changedInput.classList.remove('saving');
                if (res && res.success) {
                    changedInput.classList.add('saved');
                    collectInputs().forEach(function (inp) {
                        inp.setAttribute('data-original', inp.value);
                    });
                    setTimeout(function () {
                        changedInput.classList.remove('saved');
                    }, 800);
                } else {
                    changedInput.classList.add('error');
                    setTimeout(function () {
                        changedInput.classList.remove('error');
                    }, 1200);
                }
            })
            .catch(function () {
                changedInput.classList.remove('saving');
                changedInput.classList.add('error');
                setTimeout(function () {
                    changedInput.classList.remove('error');
                }, 1200);
            });
    }

    document.addEventListener('keydown', function (e) {
        if (e.target && e.target.classList.contains('meal-sort-input')) {
            if (e.key === 'Enter') {
                e.preventDefault();
                e.target.blur();
            }
        }
    });

    document.addEventListener('blur', function (e) {
        var el = e.target;
        if (!el || !el.classList || !el.classList.contains('meal-sort-input')) return;

        var original = el.getAttribute('data-original');
        if (el.value === original) return;

        saveOrderFromInput(el);
    }, true);

    document.addEventListener('keydown', function (e) {
        var el = e.target;
        if (!el || !el.classList || !el.classList.contains('meal-sort-input')) return;
        if (e.key === 'ArrowUp') {
            el.value = Math.max(0, (parseInt(el.value, 10) || 0) - 1);
            el.blur();
        } else if (e.key === 'ArrowDown') {
            el.value = (parseInt(el.value, 10) || 0) + 1;
            el.blur();
        }
    });
});
</script>