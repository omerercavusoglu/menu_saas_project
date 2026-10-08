<?php
//error_reporting(E_ALL);
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//ini_set('log_errors', 1);
//ini_set('error_log', __DIR__ . '/../storage/logs/php-error.log');

require_once __DIR__ . '/config.php';



// ==================== PROMO POPUP AYARLARI ====================

// Süreler saniye cinsinden

$promo_enabled       = true;   // true = açık, false = kapalı

$promo_first_delay   = 2;      // İlk gösterim: 2 saniye sonra

$promo_repeat_delay  = 120;    // Tekrar: 2 dakika (120 sn) sonra



$settings = $pdo->query("SELECT * FROM settings WHERE is_active=1 ORDER BY id DESC LIMIT 1")->fetch();

$splashBg = !empty($settings['splash_bg']) ? $settings['splash_bg'] : 'default.jpg';

$categories = $pdo->query("SELECT * FROM categories WHERE is_active=1 ORDER BY sort_order ASC")->fetchAll();



$menuItemsQuery = "SELECT id, category_id, ru, uz, en, price, image, description_ru, description_uz, description_en, sort_order 

                   FROM menu_items 

                   WHERE is_active = 1 

                   ORDER BY category_id ASC, sort_order ASC, id ASC";

$menuItems = $pdo->query($menuItemsQuery)->fetchAll();



?>

<!DOCTYPE html>

<html lang="ru">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1, user-scalable=no, viewport-fit=cover">

    <title>Меню</title>

    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">

    <meta http-equiv="Pragma" content="no-cache">

    <meta http-equiv="Expires" content="0">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <link rel="stylesheet" href="assets/css/menuu.css?v=<?= time() ?>">

    <meta name="theme-color" content="#000000">

    <!-- Favicon -->

<link rel="icon" type="image/png" href="images/giotto-logo.webp">

</head>

<body>



<!-- Logo + Dil (kaydırılır) -->

<header class="app-header">

    

    <!-- ⬇️ Sol üst: Bilgi butonu -->

<button class="header-info-btn" onclick="openInfoPopup()" aria-label="İletişim Bilgileri">

    <i class="bi bi-info-circle"></i>

</button>

    

    <div class="header-lang-top">

        <div class="lang-tabs">

            <button class="lang-tab" data-lang="uz" onclick="switchLang('uz')">UZ</button>

            <button class="lang-tab" data-lang="ru" onclick="switchLang('ru')">RU</button>

            <button class="lang-tab" data-lang="en" onclick="switchLang('en')">EN</button>

        </div>

    </div>

    <div class="header-title">

    <img src="images/giotto-logo.webp" alt="Giotto" class="giotto-logo" id="logoResetBtn" onclick="resetCartFromLogo()">

    <p class="subtitle" id="header-subtitle">Лучшие вкусы, которые мы отобрали для вас</p>

</div>



    <!-- Arama Kutusu -->

    <div class="search-box">

        <i class="bi bi-search search-icon"></i>

        <input type="text" id="searchInput" class="search-input" placeholder="Поиск по меню..." autocomplete="off">

        <button type="button" id="searchClear" class="search-clear" onclick="clearSearch()" style="display:none;">

            <i class="bi bi-x-lg"></i>

        </button>

    </div>

</header>



<!-- Kategori Şeridi (SABİT) -->

<nav class="category-bar" id="categoryBar"></nav>



<!-- Yemekler -->

<main class="products-container">

    <div id="prodRow" class="products-grid"></div>

</main>



<!-- Modal -->

<div id="meal-modal-overlay" class="meal-modal">

    <div class="modal-content" id="mealModalContent">

        <div class="modal-topbar">

            <div class="nav-counter" id="mealCounter">

                <span id="mealCategoryName">—</span>

                · <span id="mealPos">1 / 1</span>

            </div>

            <span id="meal-modal-close-btn" class="modal-close-btn">&times;</span>

        </div>



        <div class="modal-image-container">

            <button type="button" class="nav-arrow nav-arrow-left" id="prevMealBtn" aria-label="Önceki">

                <i class="bi bi-chevron-left"></i>

            </button>

            <img id="modal-meal-image" src="" alt="Food photo">

            <button type="button" class="nav-arrow nav-arrow-right" id="nextMealBtn" aria-label="Sonraki">

                <i class="bi bi-chevron-right"></i>

            </button>

        </div>



        <div class="photo-note">*подача может отличаться от фото</div>



        <div class="modal-info-container">

            <h2 id="modal-meal-name"></h2>

            <p id="modal-meal-description"></p>

            <div id="modal-meal-price"></div>



            <div class="modal-cart-action" id="modalCartAction">

    <!-- JS ile doldurulacak -->

</div>

        </div>

    </div>

</div>



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<?php include 'footer.php'; ?>



<script>

    if ('caches' in window) {

        caches.keys().then(names => {

            names.forEach(name => caches.delete(name));

            console.log('🧹 Eski cache temizlendi');

        });

    }



    // Verileri global'e koy

    window.GIOTTO_CATEGORIES = <?= json_encode($categories, JSON_UNESCAPED_UNICODE) ?>;

    window.GIOTTO_MENU_ITEMS = <?= json_encode($menuItems, JSON_UNESCAPED_UNICODE) ?>;

</script>



<script src="assets/js/menuu.js?v=<?= time() ?>"></script>

<!-- ============================================ -->

<!-- SEPET BUTONU (Sağ Alt Köşe) -->

<!-- ============================================ -->

<button class="cart-btn" id="cartBtn" onclick="openCart()" aria-label="Избранное">

    <i class="bi bi-star-fill"></i>

    <span class="cart-badge" id="cartBadge" style="display:none;">0</span>

</button>



<!-- ============================================ -->

<!-- SEPET MODAL -->

<!-- ============================================ -->

<div class="cart-modal" id="cartModal">

    <div class="cart-content">

        <div class="cart-header">

            <h3><i class="bi bi-star-fill"></i> Избранное</h3>

            <span class="cart-close" onclick="closeCart()">&times;</span>

        </div>



        <div class="cart-body" id="cartBody">

            <!-- JS ile doldurulacak -->

        </div>



        <div class="cart-footer">

            <div class="cart-total">

                <span>Итого:</span> <strong id="cartTotal">0 UZS</strong>

            </div>

            <div class="cart-actions">

    <button class="cart-btn-clear" onclick="clearCart()">

        <i class="bi bi-trash"></i> Убрать всё

    </button>

    <button class="cart-btn-qr" onclick="showOrderQR()">

        <i class="bi bi-qr-code"></i> Показать QR

    </button>

</div>

        </div>

    </div>

</div>

<!-- QR Kod Kütüphanesi -->

<script src="assets/js/qrcode.min.js"></script>



<!-- İLETİŞİM POPUP -->

<!-- ============================================ -->

<div id="info-popup" class="info-popup">

    <div class="info-popup-overlay" onclick="closeInfoPopup()"></div>

    <div class="info-popup-content">

        <div class="info-popup-header">

            <img src="images/giotto-logo.webp" alt="Giotto" class="info-popup-logo">

            <button class="info-popup-close" onclick="closeInfoPopup()" aria-label="Закрыть">&times;</button>

        </div>



        <div class="info-popup-body">



            <!-- Website

            <a href="https://giotto.uz" target="_blank" rel="noopener" class="info-item">

                <div class="info-item-icon">

                    <i class="bi bi-globe"></i>

                </div>

                <div class="info-item-text">

                    <span class="info-item-label">Веб-сайт</span>

                    <span class="info-item-value">giotto.uz</span>

                </div>

                <i class="bi bi-box-arrow-up-right info-item-arrow"></i>

           </a>-->



            <!-- Instagram -->

            <a href="https://instagram.com/giotto.uz" target="_blank" rel="noopener" class="info-item">

                <div class="info-item-icon">

                    <i class="bi bi-instagram"></i>

                </div>

                <div class="info-item-text">

                    <span class="info-item-label">Instagram</span>

                    <span class="info-item-value">@giotto.uz</span>

                </div>

                <i class="bi bi-box-arrow-up-right info-item-arrow"></i>

            </a>



            <!-- Telefon -->

            <a href="tel:+998901234567" class="info-item">

                <div class="info-item-icon">

                    <i class="bi bi-telephone-fill"></i>

                </div>

                <div class="info-item-text">

                    <span class="info-item-label">Телефон</span>

                    <span class="info-item-value">+998 97 144 77 57</span>

                </div>

                <i class="bi bi-box-arrow-up-right info-item-arrow"></i>

            </a>



            <!-- Adres -->

            <div class="info-item info-item-static">

                <div class="info-item-icon">

                    <i class="bi bi-geo-alt-fill"></i>

                </div>

                <div class="info-item-text">

                    <span class="info-item-label">Адрес</span>

                    <span class="info-item-value">Адрес: ул. Тараса Шевченко, 36А</span>

                </div>

            </div>



            <!-- Çalışma Saatleri -->

            <div class="info-item info-item-static">

                <div class="info-item-icon">

                    <i class="bi bi-clock-fill"></i>

                </div>

                <div class="info-item-text">

                    <span class="info-item-label">Часы работы</span>

                    <span class="info-item-value">Ежедневно: 10:00–01:00</span>

                </div>

            </div>



        </div>



        <div class="info-popup-footer">

            <p>© Giotto 2018</p>

        </div>

    </div>

</div>



<?php

// Splash dosyası gerçekten var mı?

$splashExists = !empty($splashBg) && file_exists($splashBg);

?>

<?php if ($splashExists): ?>

<div id="promo-popup" class="promo-popup">

    <div class="promo-overlay" onclick="closePromo()"></div>

    <div class="promo-content">

        <button class="promo-close" onclick="closePromo()" aria-label="Закрыть">&times;</button>

        <div class="promo-image">

            <img src="<?php echo htmlspecialchars($splashBg); ?>" alt="Акция" onerror="this.parentElement.style.display='none'">

        </div>

    </div>

</div>

<?php endif; ?>



<?php if (!empty($splashBg)): ?>

<!-- ============================================ -->

<!-- REKLAM POPUP -->

<!-- ============================================ -->

<div id="promo-popup" class="promo-popup">

    <div class="promo-overlay" onclick="closePromo()"></div>

    <div class="promo-content">

        <button class="promo-close" onclick="closePromo()" aria-label="Закрыть">&times;</button>

        <div class="promo-image">

            <img src="<?= htmlspecialchars($splashBg) ?>" alt="Акция">

        </div>

    </div>

</div>

<?php endif; ?>

</body>

</html>