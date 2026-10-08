<?php
require_once __DIR__ . '/config.php';  // $pdo, $lang, session hazır
require_once __DIR__ . '/header.php';  // HTML başlıyor

// --- Aktif dil sütunu ---
$active_lang = 'en';
if (isset($lang_code) && in_array($lang_code, array('ru', 'uz', 'en'))) {
    $active_lang = $lang_code;
}

// --- İstatistikler ---
$totalCategories  = (int) $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$activeCategories = (int) $pdo->query("SELECT COUNT(*) FROM categories WHERE is_active = 1")->fetchColumn();
$totalDishes      = (int) $pdo->query("SELECT COUNT(*) FROM menu_items")->fetchColumn();
$activeDishes     = (int) $pdo->query("SELECT COUNT(*) FROM menu_items WHERE is_active = 1")->fetchColumn();

$avgDishesPerCategory = 0;
if ($totalCategories > 0) {
    $avgDishesPerCategory = round($totalDishes / $totalCategories, 1);
}

$lastDish = $pdo->query("
    SELECT id, `$active_lang` AS title
    FROM menu_items
    ORDER BY id DESC
    LIMIT 1
")->fetch();

$recentCategories = $pdo->query("
    SELECT id, `$active_lang` AS title, is_active
    FROM categories
    ORDER BY id DESC
    LIMIT 5
")->fetchAll();

$recentDishes = $pdo->query("
    SELECT mi.id, mi.`$active_lang` AS title, mi.price, mi.is_active,
           c.`$active_lang` AS category_title
    FROM menu_items mi
    LEFT JOIN categories c ON c.id = mi.category_id
    ORDER BY mi.id DESC
    LIMIT 5
")->fetchAll();

// Başlık için güvenli değişken
$panelTitle = isset($lang['panel']) ? $lang['panel'] : 'Yönetim Paneli';
?>

<style>
    body { background: #f7f8fa; }
    .dash-title { font-weight: 700; color: #212529; }
    .stat-card {
        background: #fff; border: none; border-radius: 14px;
        box-shadow: 0 1px 3px rgba(0,0,0,.06);
        padding: 1.25rem 1.4rem; height: 100%;
    }
    .stat-card .icon {
        width: 48px; height: 48px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.3rem;
    }
    .stat-card .label {
        font-size: .8rem; color: #6c757d; text-transform: uppercase;
        letter-spacing: .5px; font-weight: 600;
    }
    .stat-card .number { font-size: 1.9rem; font-weight: 700; color: #212529; line-height: 1.1; }
    .stat-card .sub { font-size: .78rem; color: #adb5bd; }
    .info-box {
        background: #fff; border-radius: 14px;
        box-shadow: 0 1px 3px rgba(0,0,0,.06);
        padding: 1.25rem 1.4rem;
    }
    .info-box h6 {
        font-weight: 700; font-size: .9rem; color: #495057;
        margin-bottom: 1rem; text-transform: uppercase; letter-spacing: .5px;
    }
    .mini-list { list-style: none; padding: 0; margin: 0; }
    .mini-list li {
        display: flex; justify-content: space-between; align-items: center;
        padding: .55rem 0; border-bottom: 1px dashed #e9ecef; font-size: .9rem;
    }
    .mini-list li:last-child { border-bottom: none; }
    .mini-list .title { color: #212529; font-weight: 500; }
    .mini-list .meta { font-size: .75rem; color: #adb5bd; }
    .dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; margin-right: 5px; }
    .dot-on  { background: #28a745; }
    .dot-off { background: #adb5bd; }
</style>

<div class="content">
    <div class="container-fluid py-3">

        <!-- Başlık -->
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h1 class="h3 dash-title mb-0">
                    <i class="fas fa-tachometer-alt text-primary"></i>
                    <?php echo htmlspecialchars($panelTitle); ?>
                </h1>
                <small class="text-muted">Система меню Giotto</small>
            </div>
            <div class="text-end">
                <div class="text-muted small">
                    <i class="far fa-calendar"></i> <?php echo date('d.m.Y'); ?>
                    &nbsp;·&nbsp;
                    <i class="far fa-clock"></i> <?php echo date('H:i'); ?>
                </div>
                <span class="badge bg-secondary mt-1">Dil: <?php echo strtoupper($active_lang); ?></span>
            </div>
        </div>

        <!-- İstatistik Kartları -->
        <div class="row g-3 mb-4">
            <div class="col-lg-3 col-md-6">
                <div class="stat-card d-flex align-items-center gap-3">
                    <div class="icon bg-primary bg-opacity-10 text-primary">
                        <i class="fas fa-folder"></i>
                    </div>
                    <div>
                        <div class="label">Категории</div>
                        <div class="number"><?php echo $totalCategories; ?></div>
                        <div class="sub"><?php echo $activeCategories; ?> aktif</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card d-flex align-items-center gap-3">
                    <div class="icon bg-success bg-opacity-10 text-success">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <div>
                        <div class="label">Блюда</div>
                        <div class="number"><?php echo $totalDishes; ?></div>
                        <div class="sub"><?php echo $activeDishes; ?> aktif</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card d-flex align-items-center gap-3">
                    <div class="icon bg-warning bg-opacity-10 text-warning">
                        <i class="fas fa-chart-simple"></i>
                    </div>
                    <div>
                        <div class="label">Среднее</div>
                        <div class="number"><?php echo $avgDishesPerCategory; ?></div>
                        <div class="sub">блюда / категории</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card d-flex align-items-center gap-3">
                    <div class="icon bg-info bg-opacity-10 text-info">
                        <i class="fas fa-language"></i>
                    </div>
                    <div>
                        <div class="label">Языки</div>
                        <div class="number">3</div>
                        <div class="sub">RU · UZ · EN</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Son Eklenenler -->
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="info-box">
                    <h6><i class="fas fa-clock-rotate-left text-primary"></i> Последние добавленные категории</h6>
                    <?php if (empty($recentCategories)): ?>
                        <p class="text-muted mb-0">Записей нет.</p>
                    <?php else: ?>
                        <ul class="mini-list">
                            <?php foreach ($recentCategories as $c): ?>
                                <li>
                                    <div>
                                        <span class="dot <?php echo $c['is_active'] ? 'dot-on' : 'dot-off'; ?>"></span>
                                        <span class="title"><?php echo htmlspecialchars($c['title'] ? $c['title'] : '(boş)'); ?></span>
                                    </div>
                                    <span class="meta">#<?php echo (int)$c['id']; ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-md-6">
                <div class="info-box">
                    <h6><i class="fas fa-clock-rotate-left text-success"></i> Последние добавленные блюда</h6>
                    <?php if (empty($recentDishes)): ?>
                        <p class="text-muted mb-0">Записей нет.</p>
                    <?php else: ?>
                        <ul class="mini-list">
                            <?php foreach ($recentDishes as $d): ?>
                                <li>
                                    <div>
                                        <span class="dot <?php echo $d['is_active'] ? 'dot-on' : 'dot-off'; ?>"></span>
                                        <span class="title"><?php echo htmlspecialchars($d['title'] ? $d['title'] : '(boş)'); ?></span>
                                        <?php if (!empty($d['category_title'])): ?>
                                            <span class="meta">· <?php echo htmlspecialchars($d['category_title']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <span class="meta"><?php echo $d['price'] ? htmlspecialchars($d['price']) : ''; ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Sistem Özeti -->
        <div class="row g-3">
            <div class="col-12">
                <div class="info-box">
                    <h6><i class="fas fa-circle-info text-secondary"></i> Обзор системы</h6>
                    <div class="row text-center">
                        <div class="col-md-3 col-6 mb-2">
                            <div class="text-muted small">Общее количество</div>
                            <div class="fs-4 fw-bold"><?php echo $totalDishes; ?></div>
                        </div>
                        <div class="col-md-3 col-6 mb-2">
                            <div class="text-muted small">Активный</div>
                            <div class="fs-4 fw-bold text-success"><?php echo $activeDishes; ?></div>
                        </div>
                        <div class="col-md-3 col-6 mb-2">
                            <div class="text-muted small">Пасивный</div>
                            <div class="fs-4 fw-bold text-secondary"><?php echo $totalDishes - $activeDishes; ?></div>
                        </div>
                        <div class="col-md-3 col-6 mb-2">
                            <div class="text-muted small">Последние добавленные</div>
                            <div class="fs-6 fw-bold text-truncate" title="<?php echo htmlspecialchars($lastDish ? $lastDish['title'] : '-'); ?>">
                                <?php echo htmlspecialchars($lastDish ? $lastDish['title'] : '-'); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>