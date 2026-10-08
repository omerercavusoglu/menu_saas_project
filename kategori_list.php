<?php
require_once __DIR__ . '/config.php';

// Kategorileri çek
$categories = array();
try {
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY sort_order ASC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $categories[$row['parent_id']][] = $row;
    }
} catch (PDOException $e) {
    error_log("Kategori çekme hatası: " . $e->getMessage());
    $categories = array();
}

// Düz listeye çevir
function flatCategoryList($parent_id, $categories, $level = 0, &$result = array()) {
    if (!isset($categories[$parent_id])) return;
    foreach ($categories[$parent_id] as $row) {
        $row['level'] = $level;
        $result[] = $row;
        flatCategoryList($row['id'], $categories, $level + 1, $result);
    }
    return $result;
}

$flatCategories = array();
flatCategoryList(null, $categories, 0, $flatCategories);

// Seçili kategori
$selected_cat = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;
$selectedCategory = null;
$selectedMeals = array();

if ($selected_cat > 0) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute(array($selected_cat));
    $selectedCategory = $stmt->fetch();

    if ($selectedCategory) {
        $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE category_id = ? ORDER BY sort_order ASC, id ASC");
        $stmt->execute(array($selected_cat));
        $selectedMeals = $stmt->fetchAll();
    }
}
require_once __DIR__ . '/header.php';
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

<style>
    .kategori-page {
        height: calc(100vh - 80px);
        overflow: hidden;
        padding: 10px 15px;
    }
    .kategori-cols {
        height: calc(100% - 60px);
    }
    .kategori-col {
        height: 100%;
        overflow-y: auto;
    }

    /* SOL - KATEGORİ LİSTESİ */
    .cat-list-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 8px 10px;
        border-radius: 6px;
        margin-bottom: 4px;
        background: #fff;
        border: 1px solid #e9ecef;
        cursor: pointer;
        transition: all 0.15s;
        border-left: 3px solid transparent;
    }
    .cat-list-item:hover {
        background: #f8f9fa;
        box-shadow: 0 1px 4px rgba(0,0,0,0.05);
    }
    .cat-list-item.active {
        background: #e7f1ff;
        border-left-color: #0d6efd;
    }
    .cat-list-item .cat-name {
        flex: 1;
        font-size: 0.9rem;
        color: #212529;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .cat-list-item.active .cat-name {
        color: #0d6efd;
        font-weight: 600;
    }
    .cat-list-item .cat-actions {
        display: flex;
        gap: 3px;
        flex-shrink: 0;
    }
    .cat-list-item .cat-actions .btn {
        font-size: 0.7rem;
        padding: 2px 6px;
    }
    .cat-list-item .drag-handle {
        color: #adb5bd;
        cursor: grab;
        margin-right: 6px;
        font-size: 0.85rem;
    }
    .sub-item {
        padding-left: 25px !important;
    }
    .sub-item-2 {
        padding-left: 45px !important;
    }

    /* SAĞ - DETAY */
    .preview-thumb {
        width: 50px;
        height: 50px;
        object-fit: cover;
        border-radius: 4px;
        border: 1px solid #ddd;
    }
    .preview-thumb-empty {
        width: 50px;
        height: 50px;
        background: #f8f9fa;
        border-radius: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #adb5bd;
        border: 1px dashed #ddd;
    }
    .meal-preview-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px;
        border: 1px solid #e9ecef;
        border-radius: 6px;
        margin-bottom: 6px;
        font-size: 0.85rem;
    }
    .meal-preview-item .meal-name-p {
        flex: 1;
        min-width: 0;
    }
    .meal-preview-item .meal-name-p strong {
        display: block;
        font-size: 0.9rem;
    }
    .meal-preview-item .meal-price-p {
        font-weight: 700;
        color: #198754;
        flex-shrink: 0;
    }
    
        /* === KATEGORİ SIRALAMA KUTUSU === */
    .cat-sort-input {
        width: 42px;
        text-align: center;
        font-weight: 700;
        font-size: 0.75rem;
        padding: 2px 3px;
        border: 1px solid #ced4da;
        border-radius: 4px;
        background: #f8f9fa;
        flex-shrink: 0;
        transition: all 0.15s;
        margin-right: 6px;
    }
    .cat-sort-input:focus {
        outline: none;
        border-color: #0d6efd;
        background: #fff;
        box-shadow: 0 0 0 3px rgba(13,110,253,0.15);
    }
    .cat-sort-input.saving { background: #fff3cd; border-color: #ffc107; }
    .cat-sort-input.saved  { background: #d4edda; border-color: #198754; }
    .cat-sort-input.error  { background: #f8d7da; border-color: #dc3545; }

    .cat-move-btn {
        width: 22px;
        height: 22px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.65rem;
        border-radius: 4px;
        border: 1px solid #ced4da;
        background: #fff;
        color: #495057;
        cursor: pointer;
        transition: all 0.15s;
        margin-left: 2px;
    }
    .cat-move-btn:hover {
        background: #0d6efd;
        color: #fff;
        border-color: #0d6efd;
    }
    .cat-move-btn:disabled {
        opacity: 0.35;
        cursor: not-allowed;
    }
    .cat-list-item .cat-actions {
        display: flex;
        align-items: center;
        gap: 2px;
        flex-shrink: 0;
    }
</style>

<div class="container-fluid kategori-page">
    <h4 class="text-center mb-2">
        📁 <?php echo htmlspecialchars(isset($lang['catlist']) ? $lang['catlist'] : 'Kategori Listesi'); ?>
    </h4>

    <div class="row g-3 kategori-cols">

        <!-- SOL: KATEGORİ LİSTESİ -->
        <div class="col-md-6 kategori-col">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">📋 Tüm Kategoriler <span class="badge bg-secondary"><?php echo count($flatCategories); ?></span></h6>

                    <?php if (empty($flatCategories)): ?>
                        <p class="text-muted text-center py-4">Henüz kategori yok.</p>
                    <?php else: ?>
                        <div id="sortable-categories">
                            <?php foreach ($flatCategories as $cat): ?>
                                <?php
                                $levelClass = '';
                                if ($cat['level'] == 1) $levelClass = 'sub-item';
                                elseif ($cat['level'] >= 2) $levelClass = 'sub-item-2';

                                $isActive = ($cat['id'] == $selected_cat) ? ' active' : '';
                                ?>
                                <div class="cat-list-item <?php echo $levelClass . $isActive; ?>"
     data-cat-id="<?php echo (int)$cat['id']; ?>"
     data-parent-id="<?php echo (int)$cat['parent_id']; ?>"
     data-level="<?php echo (int)$cat['level']; ?>"
     data-sort-order="<?php echo (int)$cat['sort_order']; ?>">

    <!-- SIRA KUTUSU -->
    <input type="number"
           class="cat-sort-input"
           value="<?php echo (int)$cat['sort_order']; ?>"
           data-cat-id="<?php echo (int)$cat['id']; ?>"
           data-original="<?php echo (int)$cat['sort_order']; ?>"
           min="0"
           title="Порядок сортировки">

    <a href="?cat=<?php echo (int)$cat['id']; ?>" class="cat-name text-decoration-none" style="color: inherit;">
        <?php if ($cat['level'] > 0): ?>
            <i class="fa fa-level-up-alt fa-rotate-90 text-muted"></i>
        <?php endif; ?>
        <?php echo htmlspecialchars($cat['ru']); ?>
        <?php if (!$cat['is_active']): ?>
            <span class="badge bg-warning text-dark" style="font-size:0.6rem;">P</span>
        <?php endif; ?>
    </a>

    <span class="cat-actions">
        <!-- YUKARI -->
        <button type="button"
                class="cat-move-btn cat-move-up"
                data-cat-id="<?php echo (int)$cat['id']; ?>"
                title="Yukarı"
                onclick="moveCategoryUp(<?php echo (int)$cat['id']; ?>)">
            <i class="fa fa-chevron-up"></i>
        </button>

        <!-- AŞAĞI -->
        <button type="button"
                class="cat-move-btn cat-move-down"
                data-cat-id="<?php echo (int)$cat['id']; ?>"
                title="Aşağı"
                onclick="moveCategoryDown(<?php echo (int)$cat['id']; ?>)">
            <i class="fa fa-chevron-down"></i>
        </button>

        <!-- DÜZENLE -->
        <a href="kategori_duzenle.php?id=<?php echo (int)$cat['id']; ?>" class="btn btn-warning" title="Редактировать" style="font-size:0.7rem;padding:2px 6px;">
            <i class="fa fa-edit"></i>
        </a>
    </span>
</div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- SAĞ: SEÇİLİ KATEGORİ DETAYI + YEMEKLER -->
        <div class="col-md-6 kategori-col">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <?php if (!$selectedCategory): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="fa fa-hand-point-left fa-2x mb-2"></i>
                            <p>Выберите категорию слева.</p>
                        </div>
                    <?php else: ?>
                        <!-- Kategori başlığı -->
                        <h5 class="mb-1">
                            <?php echo htmlspecialchars($selectedCategory['ru']); ?>
                            <?php if ($selectedCategory['is_active']): ?>
                                <span class="badge bg-success">Aktif</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">Pasif</span>
                            <?php endif; ?>
                        </h5>
                        <div class="text-muted small mb-3">
                            <?php echo htmlspecialchars($selectedCategory['uz']); ?>
                            <?php if (!empty($selectedCategory['en'])): ?>
                                / <?php echo htmlspecialchars($selectedCategory['en']); ?>
                            <?php endif; ?>
                        </div>

                        <!-- Aksiyonlar -->
                        <div class="mb-3">
                            <a href="kategori_duzenle.php?id=<?php echo (int)$selectedCategory['id']; ?>" class="btn btn-warning btn-sm">
                                <i class="fa fa-edit"></i> Редактировать
                            </a>
                            <a href="yemek_list.php?cat=<?php echo (int)$selectedCategory['id']; ?>" class="btn btn-primary btn-sm">
                                <i class="fa fa-utensils"></i> Управление блюдами
                            </a>
                        </div>

                        <!-- Kategori görseli -->
                        <?php if (!empty($selectedCategory['image'])): ?>
                            <div class="mb-3">
                                <img src="<?php echo htmlspecialchars($selectedCategory['image']); ?>" 
                                     style="max-width: 100%; max-height: 150px; border-radius: 6px; border: 1px solid #ddd;">
                            </div>
                        <?php endif; ?>

                        <!-- Yemekler -->
                        <h6 class="fw-bold mb-2">
                            🍽️ Yemekler
                            <span class="badge bg-secondary"><?php echo count($selectedMeals); ?></span>
                        </h6>

                        <?php if (empty($selectedMeals)): ?>
                            <p class="text-muted small">Bu kategoride yemek yok.</p>
                        <?php else: ?>
                            <?php foreach ($selectedMeals as $m): ?>
                                <div class="meal-preview-item">
                                    <?php if (!empty($m['image'])): ?>
                                        <img src="<?php echo htmlspecialchars($m['image']); ?>" class="preview-thumb" alt="">
                                    <?php else: ?>
                                        <div class="preview-thumb-empty"><i class="fa fa-image"></i></div>
                                    <?php endif; ?>

                                    <div class="meal-name-p">
                                        <strong><?php echo htmlspecialchars($m['ru']); ?></strong>
                                        <small class="text-muted"><?php echo htmlspecialchars($m['uz']); ?></small>
                                    </div>

                                    <div class="meal-price-p"><?php echo number_format($m['price'], 0, '', ' '); ?> UZS</div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    /* ==========================================
       KATEGORİ SIRALAMA — Sıra Kutusu & Butonlar
       ========================================== */

    // Aynı parent_id ve aynı seviyedeki kategori kartlarını topla
    function getSiblingItems(catId) {
        var item = document.querySelector('.cat-list-item[data-cat-id="' + catId + '"]');
        if (!item) return [];

        var parentId = item.getAttribute('data-parent-id');
        var level    = item.getAttribute('data-level');

        return Array.prototype.slice.call(
            document.querySelectorAll(
                '.cat-list-item[data-parent-id="' + parentId + '"][data-level="' + level + '"]'
            )
        );
    }

    // Sıra kutularına yeni değerleri yaz + AJAX ile kaydet
    function saveCategoryOrder(items, changedInput) {
        // Yeni sort_order değerleri ata
        var updates = [];
        items.forEach(function (item, idx) {
            var input = item.querySelector('.cat-sort-input');
            if (input) {
                input.value = idx;
                updates.push({
                    id: parseInt(item.getAttribute('data-cat-id'), 10),
                    order: idx
                });
            }
        });

        // DOM sırasını da güncelle
        var container = items[0] ? items[0].parentElement : null;
        if (container) {
            items.forEach(function (item) { container.appendChild(item); });
        }

        // AJAX gönder
        var fd = new FormData();
        updates.forEach(function (u, i) {
            fd.append('order[' + i + '][id]', u.id);
            fd.append('order[' + i + '][order]', u.order);
        });

        if (changedInput) {
            changedInput.classList.add('saving');
            changedInput.classList.remove('saved', 'error');
        }

        fetch('sort_categories.php', { method: 'POST', body: fd })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (changedInput) {
                    changedInput.classList.remove('saving');
                    if (res && res.success) {
                        changedInput.classList.add('saved');
                        items.forEach(function (item) {
                            var inp = item.querySelector('.cat-sort-input');
                            if (inp) inp.setAttribute('data-original', inp.value);
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
                }
            })
            .catch(function () {
                if (changedInput) {
                    changedInput.classList.remove('saving');
                    changedInput.classList.add('error');
                    setTimeout(function () {
                        changedInput.classList.remove('error');
                    }, 1200);
                }
            });
    }

    // Sıra kutusuna bir şey yazıldığında
    document.addEventListener('blur', function (e) {
        var el = e.target;
        if (!el || !el.classList || !el.classList.contains('cat-sort-input')) return;

        var original = el.getAttribute('data-original');
        if (el.value === original) return;

        var catId = el.getAttribute('data-cat-id');
        var items = getSiblingItems(catId);
        if (items.length === 0) return;

        // Sırala: input değerine göre
        var newOrder = parseInt(el.value, 10);
        if (isNaN(newOrder)) newOrder = 0;
        if (newOrder < 0) newOrder = 0;
        if (newOrder > items.length - 1) newOrder = items.length - 1;

        // Değişen inputu istediği sıraya taşı
        var changedItem = el.closest('.cat-list-item');
        var others = items.filter(function (it) { return it !== changedItem; });

        var reordered = [];
        for (var i = 0; i < items.length; i++) {
            if (i === newOrder) reordered.push(changedItem);
            else reordered.push(others.shift());
        }

        saveCategoryOrder(reordered, el);
    }, true);

    // Enter'a basınca da kaydet
    document.addEventListener('keydown', function (e) {
        if (e.target && e.target.classList.contains('cat-sort-input')) {
            if (e.key === 'Enter') {
                e.preventDefault();
                e.target.blur();
            }
        }
    });

    /* ==========================================
       YUKARI / AŞAĞI BUTONLARI
       ========================================== */

    window.moveCategoryUp = function (catId) {
        var items = getSiblingItems(catId);
        var idx = items.findIndex(function (it) {
            return parseInt(it.getAttribute('data-cat-id'), 10) === catId;
        });
        if (idx <= 0) return; // Zaten en üstte

        // Bir öncekiyle yer değiştir
        var temp = items[idx];
        items[idx] = items[idx - 1];
        items[idx - 1] = temp;

        saveCategoryOrder(items, null);
    };

    window.moveCategoryDown = function (catId) {
        var items = getSiblingItems(catId);
        var idx = items.findIndex(function (it) {
            return parseInt(it.getAttribute('data-cat-id'), 10) === catId;
        });
        if (idx < 0 || idx >= items.length - 1) return; // Zaten en altta

        // Bir sonrakiyle yer değiştir
        var temp = items[idx];
        items[idx] = items[idx + 1];
        items[idx + 1] = temp;

        saveCategoryOrder(items, null);
    };

});
</script>