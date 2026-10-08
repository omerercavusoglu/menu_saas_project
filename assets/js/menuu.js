/* ==========================================

   ZOOM ENGELLEME

   ========================================== */

document.addEventListener('gesturestart', function (e) { e.preventDefault(); });

document.addEventListener('gesturechange', function (e) { e.preventDefault(); });

document.addEventListener('gestureend', function (e) { e.preventDefault(); });



let lastTouchEnd = 0;

document.addEventListener('touchend', function (e) {

    const now = Date.now();

    if (now - lastTouchEnd <= 300) {

        e.preventDefault();

    }

    lastTouchEnd = now;

}, { passive: false });



document.addEventListener('touchmove', function (e) {

    if (e.touches.length > 1) {

        e.preventDefault();

    }

}, { passive: false });



/* ==========================================

   VERİLER

   ========================================== */

const allCategories = window.GIOTTO_CATEGORIES || [];

const allItems = window.GIOTTO_MENU_ITEMS || [];





function applyCategoryTheme(cat) {

    const root = document.documentElement;



    // 🎯 TEK RENK MODU — Tüm kategoriler aynı renk

    const FIXED_COLOR = 'rgb(26, 39, 57)';   // #1A2739



    root.style.setProperty('--cat-color', FIXED_COLOR);

    root.style.setProperty('--cat-color-deep', FIXED_COLOR);

    root.style.setProperty('--cat-color-darker', FIXED_COLOR);

    root.style.setProperty('--cat-color-lighter', FIXED_COLOR);

}



/* ==========================================

   DİL

   ========================================== */

let lang = 'ru';

const supportedLangs = ['ru', 'uz', 'en'];



const langTexts = {

    ru: {

        subtitle: 'Лучшие вкусы, которые мы отобрали для вас',

        all: 'Все',

        empty: 'В этой категории нет блюд.',

        searchPlaceholder: 'Поиск по меню...',

        searchEmpty: 'Ничего не найдено',



        cart: 'Избранное',

        cartEmpty: 'Вы ещё ничего не выбрали',

        cartEmptyHint: 'Нажмите на звёздочку на карточке блюда', 

        cartTotal: 'Итого',

        cartClear: 'Убрать всё',

        cartClearConfirm: 'Убрать все выбранные блюда?',

        cartClose: 'Закрыть',

        cartAdd: 'Выбрать',

        cartInCart: 'Выбрано',

        cartAdded: 'добавлено в избранное',

        cartRemoved: 'убрано из избранного', 

        cartRemove: 'Убрать',

        cartQty: 'шт.'

    },

    uz: {

        subtitle: 'Siz uchun tanlagan eng mazali taomlar',

        all: 'Barchasi',

        empty: 'Bu kategoriyada taom yo\'q.',

        searchPlaceholder: 'Menyuda qidirish...',

        searchEmpty: 'Hech narsa topilmadi',



        cart: 'Tanlanganlar',

        cartEmpty: 'Siz hali hech narsa tanlamadingiz',

        cartEmptyHint: 'Taom kartasidagi yulduzchani bosing',

        cartTotal: 'Jami',

        cartClear: 'Hammasini o\'chirish',

        cartClearConfirm: 'Tanlangan barcha taomlar o\'chirilsinmi?',

        cartClose: 'Yopish',

        cartAdd: 'Tanlash',

        cartInCart: 'Tanlandi',

        cartAdded: 'tanlanganlarga qo\'shildi',

        cartRemoved: 'tanlanganlardan o\'chirildi',

        cartRemove: 'O\'chirish',

        cartQty: 'dona'

    },

    en: {

        subtitle: 'The finest flavors we have selected for you',

        all: 'All',

        empty: 'No dishes in this category.',

        searchPlaceholder: 'Search menu...',

        searchEmpty: 'Nothing found',



        cart: 'My Picks',

        cartEmpty: 'You haven\'t picked anything yet',

        cartEmptyHint: 'Click the star on a dish card',

        cartTotal: 'Total',

        cartClear: 'Remove all',

        cartClearConfirm: 'Remove all picked dishes?',

        cartClose: 'Close',

        cartAdd: 'Pick',

        cartInCart: 'Picked',

        cartAdded: 'added to picks',

        cartRemoved: 'removed from picks', 

        cartRemove: 'Remove',

        cartQty: 'pcs'

    }

};



function catName(cat) {

    if (!cat) return '';

    if (cat[lang]) return cat[lang];

    if (cat.ru) return cat.ru;

    if (cat.uz) return cat.uz;

    if (cat.en) return cat.en;

    return '';

}



function itemName(item) {

    if (!item) return '';

    if (item[lang]) return item[lang];

    if (item.ru) return item.ru;

    if (item.uz) return item.uz;

    if (item.en) return item.en;

    return '';

}



function itemDesc(item) {

    if (!item) return '';

    return item['description_' + lang] || item.description_ru || item.description_uz || item.description_en || '';

}



function formatPrice(price) {

    if (!price && price !== 0) return '';

    return Number(price).toLocaleString('ru-RU').replace(/,/g, ' ') + ' UZS';

}



/* ==========================================

   KATEGORİ ŞERİDİ

   ========================================== */

let activeCategoryId = null;

let searchQuery = '';



function renderCategoryBar() {

    const bar = document.getElementById('categoryBar');

    if (!bar) return;



    const T = langTexts[lang] || langTexts['ru'];



    const rootCats = allCategories

        .filter(c => c.parent_id === null || c.parent_id === undefined || c.parent_id === '' || c.parent_id === 0 || c.parent_id === '0')

        .sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0) || a.id - b.id);



    let html = '';



    html += `<button class="category-chip category-chip-all" data-id="all" onclick="selectCategory('all')">

        <i class="bi bi-grid-3x3-gap-fill"></i> ${T.all}

    </button>`;



    rootCats.forEach(parentCat => {

        const subCats = allCategories

            .filter(c => String(c.parent_id) === String(parentCat.id))

            .sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0) || a.id - b.id);



        const hasOwnItems = allItems.some(i => String(i.category_id) === String(parentCat.id));



        if (hasOwnItems || subCats.length === 0) {

            html += `<button class="category-chip" data-id="${parentCat.id}" onclick="selectCategory('${parentCat.id}')">${catName(parentCat)}</button>`;

        }



        subCats.forEach(subCat => {

            html += `<button class="category-chip category-chip-sub" data-id="${subCat.id}" onclick="selectCategory('${subCat.id}')">

                ${!hasOwnItems ? '' : `<span class="sub-parent">${catName(parentCat)}</span><span class="sub-sep">›</span>`}

                <span class="sub-name">${catName(subCat)}</span>

            </button>`;

        });

    });



    bar.innerHTML = html;



    if (activeCategoryId && document.querySelector(`.category-chip[data-id="${activeCategoryId}"]`)) {

        selectCategory(activeCategoryId);

    } else if (rootCats.length > 0) {

        selectCategory(rootCats[0].id);

    } else {

        selectCategory('all');

    }

}



function selectCategory(catId) {

    activeCategoryId = catId;

    searchQuery = '';



    if (catId === 'all') {

        applyCategoryTheme(null);

    } else {

        const cat = allCategories.find(c => String(c.id) === String(catId));

        applyCategoryTheme(cat);

    }



    const searchInput = document.getElementById('searchInput');

    if (searchInput) searchInput.value = '';

    const clearBtn = document.getElementById('searchClear');

    if (clearBtn) clearBtn.style.display = 'none';



    document.querySelectorAll('.category-chip').forEach(chip => {

        chip.classList.toggle('active', String(chip.dataset.id) === String(catId));

    });



    const activeChip = document.querySelector(`.category-chip[data-id="${catId}"]`);

    if (activeChip) {

        activeChip.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });

    }



    if (catId === 'all') {

        renderAllProducts();

    } else {

        renderProducts(catId);

    }

}



/* ==========================================

   YEMEK LİSTESİ — ORTAK KART OLUŞTURUCU

   ========================================== */

function buildProductCard(item) {

    const cart = loadCart();

    const cartItem = cart.find(c => String(c.id) === String(item.id));

    const qty = cartItem ? (cartItem.qty || 1) : 0;

    const inCart = qty > 0;

    const T = langTexts[lang] || langTexts['ru'];



    let actionHtml = '';



    if (inCart) {

        actionHtml = `

            <div class="card-qty-control" onclick="event.stopPropagation();">

                <button class="card-qty-btn card-qty-minus" onclick="changeQty(${item.id}, -1); event.stopPropagation();">

                    −

                </button>

                <span class="card-qty-num">${qty}</span>

                <button class="card-qty-btn card-qty-plus" onclick="changeQty(${item.id}, 1); event.stopPropagation();">

                    +

                </button>

            </div>

        `;

    } else {

        actionHtml = `

            <button class="cart-add-btn" onclick="addToCart(${item.id}, event)" title="${T.cartAdd}">

                <i class="bi bi-star-fill"></i>

            </button>

        `;

    }



    return `

        <div class="product-card ${inCart ? 'in-cart' : ''}" onclick="openMealModal(${item.id})">

            <img src="${item.image}" alt="${itemName(item)}">

            ${actionHtml}

            <div class="product-info">

                <h3 class="product-name">${itemName(item)}</h3>

                <p class="product-desc">${itemDesc(item)}</p>

                <p class="product-price">${formatPrice(item.price)}</p>

            </div>

        </div>

    `;

}



function renderProducts(catId) {

    const grid = document.getElementById('prodRow');

    if (!grid) return;



    const T = langTexts[lang] || langTexts['ru'];



    const subCats = allCategories.filter(c => String(c.parent_id) === String(catId));

    const subIds = subCats.map(c => String(c.id));



    const filtered = allItems

        .filter(i => String(i.category_id) === String(catId) || subIds.includes(String(i.category_id)))

        .sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0) || a.id - b.id);



    if (filtered.length === 0) {

        grid.innerHTML = `<p style="grid-column:1/-1;text-align:center;color:#fff;opacity:0.6;padding:40px;">${T.empty}</p>`;

        return;

    }



    grid.innerHTML = filtered.map(buildProductCard).join('');

}



function renderAllProducts() {

    const grid = document.getElementById('prodRow');

    if (!grid) return;



    const T = langTexts[lang] || langTexts['ru'];



    const filtered = [...allItems].sort((a, b) =>

        (a.sort_order || 0) - (b.sort_order || 0) || a.id - b.id

    );



    if (filtered.length === 0) {

        grid.innerHTML = `<p style="grid-column:1/-1;text-align:center;color:#fff;opacity:0.6;padding:40px;">${T.empty}</p>`;

        return;

    }



    grid.innerHTML = filtered.map(buildProductCard).join('');

}



/* ==========================================

   ARAMA

   ========================================== */

function performSearch(query) {

    searchQuery = (query || '').trim().toLowerCase();

    const grid = document.getElementById('prodRow');

    if (!grid) return;



    const T = langTexts[lang] || langTexts['ru'];

    const clearBtn = document.getElementById('searchClear');



    if (searchQuery === '') {

        if (clearBtn) clearBtn.style.display = 'none';

        if (activeCategoryId === 'all') {

            renderAllProducts();

        } else {

            renderProducts(activeCategoryId);

        }

        return;

    }



    if (clearBtn) clearBtn.style.display = 'flex';



    const filtered = allItems.filter(item => {

        const nameRu = (item.ru || '').toLowerCase();

        const nameUz = (item.uz || '').toLowerCase();

        const nameEn = (item.en || '').toLowerCase();

        const descRu = (item.description_ru || '').toLowerCase();

        const descUz = (item.description_uz || '').toLowerCase();

        const descEn = (item.description_en || '').toLowerCase();



        return nameRu.includes(searchQuery) ||

               nameUz.includes(searchQuery) ||

               nameEn.includes(searchQuery) ||

               descRu.includes(searchQuery) ||

               descUz.includes(searchQuery) ||

               descEn.includes(searchQuery);

    }).sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0) || a.id - b.id);



    if (filtered.length === 0) {

        grid.innerHTML = `<p style="grid-column:1/-1;text-align:center;color:#fff;opacity:0.6;padding:40px;">${T.searchEmpty}</p>`;

        return;

    }



    grid.innerHTML = filtered.map(buildProductCard).join('');

}



window.clearSearch = function () {

    const input = document.getElementById('searchInput');

    if (input) input.value = '';

    performSearch('');

    if (input) input.focus();

};



/* ==========================================

   MODAL NAVİGASYONU

   ========================================== */

const mealModal = document.getElementById('meal-modal-overlay');

const mealModalCloseBtn = document.getElementById('meal-modal-close-btn');



const swipeState = {

    currentIndex: 0,

    mealList: [],

    touchStartX: 0,

    touchStartY: 0,

    touchCurrentX: 0,

    isSwiping: false,

    direction: null

};



let currentModalMealId = null;



function buildMealList() {

    let filtered;



    if (searchQuery) {

        filtered = allItems.filter(item => {

            const q = searchQuery;

            return (item.ru || '').toLowerCase().includes(q) ||

                   (item.uz || '').toLowerCase().includes(q) ||

                   (item.en || '').toLowerCase().includes(q) ||

                   (item.description_ru || '').toLowerCase().includes(q) ||

                   (item.description_uz || '').toLowerCase().includes(q) ||

                   (item.description_en || '').toLowerCase().includes(q);

        });

    } else if (activeCategoryId === 'all' || !activeCategoryId) {

        filtered = [...allItems];

    } else {

        const subCats = allCategories.filter(c => String(c.parent_id) === String(activeCategoryId));

        const subIds = subCats.map(c => String(c.id));



        filtered = allItems.filter(i =>

            String(i.category_id) === String(activeCategoryId) ||

            subIds.includes(String(i.category_id))

        );

    }



    filtered.sort((a, b) => (a.sort_order || 0) - (b.sort_order || 0) || a.id - b.id);

    swipeState.mealList = filtered;

}



function fillModalWithMeal(meal) {

    document.getElementById('modal-meal-image').src = meal.image;

    document.getElementById('modal-meal-name').textContent = itemName(meal);

    document.getElementById('modal-meal-description').textContent = itemDesc(meal);

    document.getElementById('modal-meal-price').textContent = formatPrice(meal.price);



    currentModalMealId = meal.id;

    updateModalCartButton();

}



function updateMealNav() {

    const posEl = document.getElementById('mealPos');

    const catEl = document.getElementById('mealCategoryName');

    const prevBtn = document.getElementById('prevMealBtn');

    const nextBtn = document.getElementById('nextMealBtn');



    const total = swipeState.mealList.length;

    const current = swipeState.currentIndex + 1;

    const meal = swipeState.mealList[swipeState.currentIndex];



    if (posEl) posEl.textContent = current + ' / ' + total;



    if (catEl && meal) {

        const cat = allCategories.find(c => String(c.id) === String(meal.category_id));

        catEl.textContent = cat ? catName(cat) : '—';

    }



    if (prevBtn) prevBtn.classList.toggle('disabled', swipeState.currentIndex <= 0);

    if (nextBtn) nextBtn.classList.toggle('disabled', swipeState.currentIndex >= total - 1);

}



function navigateMeal(direction) {

    const newIndex = swipeState.currentIndex + direction;

    if (newIndex < 0 || newIndex >= swipeState.mealList.length) return false;



    swipeState.currentIndex = newIndex;

    fillModalWithMeal(swipeState.mealList[newIndex]);

    updateMealNav();

    return true;

}



window.openMealModal = function (mealId) {

    buildMealList();



    swipeState.currentIndex = swipeState.mealList.findIndex(

        i => String(i.id) === String(mealId)

    );

    if (swipeState.currentIndex < 0) swipeState.currentIndex = 0;



    const meal = swipeState.mealList[swipeState.currentIndex];

    if (!meal) return;



    fillModalWithMeal(meal);

    mealModal.style.display = 'flex';

    updateMealNav();

};



function closeMealModal() {

    mealModal.style.display = 'none';

    currentModalMealId = null;

}



if (mealModalCloseBtn) {

    mealModalCloseBtn.addEventListener('click', closeMealModal);

}

if (mealModal) {

    mealModal.addEventListener('click', (e) => { if (e.target === mealModal) closeMealModal(); });

}



const prevBtnEl = document.getElementById('prevMealBtn');

const nextBtnEl = document.getElementById('nextMealBtn');

if (prevBtnEl) {

    prevBtnEl.addEventListener('click', (e) => {

        e.stopPropagation();

        navigateMeal(-1);

    });

}

if (nextBtnEl) {

    nextBtnEl.addEventListener('click', (e) => {

        e.stopPropagation();

        navigateMeal(1);

    });

}



document.addEventListener('keydown', (e) => {

    if (!mealModal || mealModal.style.display !== 'flex') return;

    if (e.key === 'ArrowLeft')  navigateMeal(-1);

    if (e.key === 'ArrowRight') navigateMeal(1);

    if (e.key === 'Escape')     closeMealModal();

});



/* Swipe */

(function initSwipe() {

    const modalContent = document.getElementById('mealModalContent');

    if (!modalContent) return;



    const SWIPE_THRESHOLD = 60;



    modalContent.addEventListener('touchstart', (e) => {

        if (e.touches.length !== 1) return;

        if (e.target.closest('.nav-arrow') || e.target.closest('.modal-close-btn')) return;



        swipeState.touchStartX = e.touches[0].clientX;

        swipeState.touchStartY = e.touches[0].clientY;

        swipeState.touchCurrentX = swipeState.touchStartX;

        swipeState.isSwiping = true;

        swipeState.direction = null;

    }, { passive: true });



    modalContent.addEventListener('touchmove', (e) => {

        if (!swipeState.isSwiping || e.touches.length !== 1) return;



        const deltaX = e.touches[0].clientX - swipeState.touchStartX;

        const deltaY = e.touches[0].clientY - swipeState.touchStartY;



        if (!swipeState.direction && (Math.abs(deltaX) > 8 || Math.abs(deltaY) > 8)) {

            swipeState.direction = Math.abs(deltaX) > Math.abs(deltaY) ? 'horizontal' : 'vertical';

        }



        if (swipeState.direction === 'horizontal') {

            e.preventDefault();

            swipeState.touchCurrentX = e.touches[0].clientX;

        }

    }, { passive: false });



    modalContent.addEventListener('touchend', () => {

        if (!swipeState.isSwiping) return;

        swipeState.isSwiping = false;



        const deltaX = swipeState.touchCurrentX - swipeState.touchStartX;



        if (swipeState.direction !== 'horizontal') return;



        if (Math.abs(deltaX) > SWIPE_THRESHOLD) {

            navigateMeal(deltaX < 0 ? 1 : -1);

        }

        swipeState.direction = null;

    }, { passive: true });

})();



/* ==========================================

   SEPET SİSTEMİ

   ========================================== */

const CART_KEY = 'giotto_cart';



function loadCart() {

    try {

        const raw = localStorage.getItem(CART_KEY);

        return raw ? JSON.parse(raw) : [];

    } catch (e) {

        return [];

    }

}



function saveCart(cart) {

    try {

        localStorage.setItem(CART_KEY, JSON.stringify(cart));

    } catch (e) {

        console.warn('Sepet kaydedilemedi:', e);

    }

}



window.addToCart = function (mealId, event) {

    if (event) {

        event.stopPropagation();

        event.preventDefault();

    }



    const meal = allItems.find(i => String(i.id) === String(mealId));

    if (!meal) return;



    const cart = loadCart();

    const existingIndex = cart.findIndex(c => String(c.id) === String(mealId));



    if (existingIndex !== -1) {

        // Zaten seçiliyse → çıkar

        cart.splice(existingIndex, 1);

        saveCart(cart);

        updateCartBadge();

        showCartToastRemove(itemName(meal));

        refreshCardActions();   // ← DEĞİŞTİ

    } else {

        // Yeni → ekle

        cart.push({ id: meal.id, qty: 1 });

        saveCart(cart);

        updateCartBadge();

        showCartToast(itemName(meal));

        refreshCardActions();   // ← DEĞİŞTİ

    }

};



window.removeFromCart = function (mealId) {

    let cart = loadCart();

    cart = cart.filter(c => String(c.id) !== String(mealId));

    saveCart(cart);

    updateCartBadge();

    renderCartModal();



    if (String(currentModalMealId) === String(mealId)) {

        updateModalCartButton();

    }

    refreshCardActions();   // ← DEĞİŞTİ

};



window.changeQty = function (mealId, delta) {

    const cart = loadCart();

    const item = cart.find(c => String(c.id) === String(mealId));

    if (!item) return;



    item.qty = (item.qty || 1) + delta;



    if (item.qty <= 0) {

        // 🎯 0'a düşerse → tamamen kaldır → yıldıza dön

        removeFromCart(mealId);

        refreshCardActions();   // ← Kartı yenile

        return;

    }



    saveCart(cart);

    updateCartBadge();

    renderCartModal();



    // 🎯 Modal'daki butonu güncelle

    if (String(currentModalMealId) === String(mealId)) {

        updateModalCartButton();

    }



    // 🎯 Kart üzerindeki adet göstergesini güncelle

    refreshCardActions();

};



/* ==========================================

   🎯 KART AKSİYONLARINI YENİLE

   ========================================== */

function refreshCardActions() {

    const cart = loadCart();



    document.querySelectorAll('.product-card').forEach(card => {

        const onclickAttr = card.getAttribute('onclick') || '';

        const match = onclickAttr.match(/openMealModal\((\d+)\)/);

        if (!match) return;



        const itemId = match[1];

        const cartItem = cart.find(c => String(c.id) === String(itemId));

        const qty = cartItem ? (cartItem.qty || 1) : 0;



        // Eski aksiyon alanını sil

        const oldAction = card.querySelector('.cart-add-btn, .card-qty-control');

        if (oldAction) oldAction.remove();



        // Eski rozeti sil

        const oldBadge = card.querySelector('.card-cart-badge');

        if (oldBadge) oldBadge.remove();



        // Yeni aksiyon HTML'i

        let actionHtml = '';

        if (qty > 0) {

            actionHtml = `

                <div class="card-qty-control" onclick="event.stopPropagation();">

                    <button class="card-qty-btn card-qty-minus" onclick="changeQty(${itemId}, -1); event.stopPropagation();">−</button>

                    <span class="card-qty-num">${qty}</span>

                    <button class="card-qty-btn card-qty-plus" onclick="changeQty(${itemId}, 1); event.stopPropagation();">+</button>

                </div>

            `;

        } else {

            const T = langTexts[lang] || langTexts['ru'];

            actionHtml = `

                <button class="cart-add-btn" onclick="addToCart(${itemId}, event)" title="${T.cartAdd}">

                    <i class="bi bi-star-fill"></i>

                </button>

            `;

        }



        // Kart içine ekle (resimden sonra)

        const img = card.querySelector('img');

        if (img) {

            img.insertAdjacentHTML('afterend', actionHtml);

        }



        // 🎯 ROZET EKLEME KISMI KALDIRILDI

        // Sadece in-cart sınıfı güncelle

        if (qty > 0) {

            card.classList.add('in-cart');

        } else {

            card.classList.remove('in-cart');

        }

    });

}



window.clearCart = function () {

    const T = langTexts[lang] || langTexts['ru'];

    if (!confirm(T.cartClearConfirm)) return;

    localStorage.removeItem(CART_KEY);

    updateCartBadge();

    renderCartModal();

    refreshCardActions();   // ← DEĞİŞTİ

};



function updateCartBadge() {

    const cart = loadCart();

    const badge = document.getElementById('cartBadge');

    const cartBtn = document.getElementById('cartBtn');       // ← YENİ

    if (!badge || !cartBtn) return;                            // ← YENİ



    const totalItems = cart.reduce((sum, c) => sum + (c.qty || 1), 0);

    const oldValue = badge.textContent;



    if (totalItems > 0) {

        // 🎯 Ürün varsa: sepet butonunu göster

        cartBtn.style.display = 'flex';                         // ← YENİ

        cartBtn.classList.add('visible');                       // ← YENİ



        badge.textContent = totalItems;

        badge.style.display = 'flex';



        if (oldValue !== String(totalItems)) {

            badge.classList.remove('pop');

            void badge.offsetWidth;

            badge.classList.add('pop');

            setTimeout(() => badge.classList.remove('pop'), 300);

        }

    } else {

        // 🎯 Ürün yoksa: sepet butonunu gizle

        cartBtn.style.display = 'none';                         // ← YENİ

        cartBtn.classList.remove('visible');                    // ← YENİ

        badge.style.display = 'none';

    }

}



window.openCart = function () {

    renderCartModal();

    const modal = document.getElementById('cartModal');

    if (modal) modal.style.display = 'flex';

};



window.closeCart = function () {

    const modal = document.getElementById('cartModal');

    if (modal) modal.style.display = 'none';

};



function renderCartModal() {

    const body = document.getElementById('cartBody');

    const totalEl = document.getElementById('cartTotal');

    if (!body) return;



    const T = langTexts[lang] || langTexts['ru'];

    const cart = loadCart();



    if (cart.length === 0) {

        body.innerHTML = `

            <div class="cart-empty">

                <i class="bi bi-star"></i>

                <p>${T.cartEmpty}</p>

                <small>${T.cartEmptyHint}</small>

            </div>

        `;

        if (totalEl) totalEl.textContent = '0 UZS';

        document.querySelector('.cart-footer')?.classList.add('empty');

        updateCartModalTexts();

        return;

    }



    // 🎯 YENİ EKLENEN SATIR: Dolu listede "empty" sınıfını kaldır

    document.querySelector('.cart-footer')?.classList.remove('empty');



    let html = '';

    let total = 0;



    cart.forEach(c => {

        const meal = allItems.find(i => String(i.id) === String(c.id));

        if (!meal) return;



        const qty = c.qty || 1;

        const price = Number(meal.price) || 0;

        total += price * qty;



        html += `

            <div class="cart-item">

                <img src="${meal.image}" alt="${itemName(meal)}" class="cart-item-img">

                <div class="cart-item-info">

                    <div class="cart-item-name">${itemName(meal)}</div>

                    <div class="cart-item-price">${formatPrice(meal.price)}</div>

                </div>

                <div class="cart-item-qty">

                    <button class="qty-btn" onclick="changeQty(${meal.id}, -1)">−</button>

                    <span class="qty-num">${qty}</span>

                    <button class="qty-btn" onclick="changeQty(${meal.id}, 1)">+</button>

                </div>

                <button class="cart-item-remove" onclick="removeFromCart(${meal.id})" title="${T.cartRemove}">

                    <i class="bi bi-trash"></i>

                </button>

            </div>

        `;

    });



    body.innerHTML = html;



    if (totalEl) {

        totalEl.textContent = total.toLocaleString('ru-RU').replace(/,/g, ' ') + ' UZS';

    }



    updateCartModalTexts();

}



function showCartToast(mealName) {

    const T = langTexts[lang] || langTexts['ru'];

    const oldToast = document.querySelector('.cart-toast');

    if (oldToast) oldToast.remove();



    const toast = document.createElement('div');

    toast.className = 'cart-toast';

    toast.innerHTML = `<i class="bi bi-check-circle-fill"></i> <span>${mealName} ${T.cartAdded}</span>`;

    document.body.appendChild(toast);



    setTimeout(() => toast.classList.add('show'), 10);

    setTimeout(() => {

        toast.classList.remove('show');

        setTimeout(() => toast.remove(), 300);

    }, 2000);

}



function showCartToastRemove(mealName) {

    const T = langTexts[lang] || langTexts['ru'];

    const oldToast = document.querySelector('.cart-toast');

    if (oldToast) oldToast.remove();



    const toast = document.createElement('div');

    toast.className = 'cart-toast cart-toast-remove';       // ← Farklı sınıf

    toast.innerHTML = `<i class="bi bi-x-circle-fill"></i> <span>${mealName} ${T.cartRemoved}</span>`;

    document.body.appendChild(toast);



    setTimeout(() => toast.classList.add('show'), 10);

    setTimeout(() => {

        toast.classList.remove('show');

        setTimeout(() => toast.remove(), 300);

    }, 2000);

}



window.addToCartFromModal = function () {

    if (!currentModalMealId) return;

    addToCart(currentModalMealId, null);   

    updateModalCartButton();

};



function updateModalCartButton() {
    const container = document.getElementById('modalCartAction');
    if (!container) return;
    if (!currentModalMealId) return;

    const T = langTexts[lang] || langTexts['ru'];
    const cart = loadCart();
    const item = cart.find(c => String(c.id) === String(currentModalMealId));
    const qty = item ? (item.qty || 1) : 0;
    
    // 🎯 Sepette toplam ürün var mı?
    const totalInCart = cart.reduce((sum, c) => sum + (c.qty || 1), 0);
    const hasAnyInCart = totalInCart > 0;

    let qtyOrAddHtml = '';
    
    if (qty > 0) {
        // 🎯 Seçiliyse → [− ② +]
        qtyOrAddHtml = `
            <div class="modal-qty-control">
                <button class="modal-qty-btn modal-qty-minus" onclick="changeQtyFromModal(-1)">
                    −
                </button>
                <span class="modal-qty-num">${qty}</span>
                <button class="modal-qty-btn modal-qty-plus" onclick="changeQtyFromModal(1)">
                    +
                </button>
            </div>
        `;
    } else {
        // 🎯 Seçilmediyse → [⭐ Выбрать]
        qtyOrAddHtml = `
            <button class="modal-add-to-cart" onclick="addToCartFromModal()">
                <i class="bi bi-star-fill"></i>
                <span>${T.cartAdd}</span>
            </button>
        `;
    }

    // 🎯 YENİ: Sepette ürün varsa "Sepete Git" butonunu ekle
    let gotoCartHtml = '';
    if (hasAnyInCart) {
        gotoCartHtml = `
            <button class="modal-goto-cart-btn" onclick="goToCartFromModal()">
                <i class="bi bi-bag-check-fill"></i>
                <span>${T.cart} (${totalInCart})</span>
            </button>
        `;
    }

    container.innerHTML = qtyOrAddHtml + gotoCartHtml;
}



function updateCartModalTexts() {

    const T = langTexts[lang] || langTexts['ru'];



    const titleEl = document.querySelector('.cart-header h3');

    if (titleEl) titleEl.innerHTML = `<i class="bi bi-star-fill"></i> ${T.cart}`;



    const totalLabel = document.querySelector('.cart-total span');

    if (totalLabel) totalLabel.textContent = T.cartTotal + ':';



    const clearBtn = document.querySelector('.cart-btn-clear');

    if (clearBtn) clearBtn.innerHTML = `<i class="bi bi-trash"></i> ${T.cartClear}`;



    const closeBtn = document.querySelector('.cart-btn-close');

    if (closeBtn) closeBtn.textContent = T.cartClose;

}



/* ==========================================

   DİL DEĞİŞTİRME

   ========================================== */

window.switchLang = function (newLang) {

    if (!supportedLangs.includes(newLang)) return;

    lang = newLang;



    document.querySelectorAll('.lang-tab').forEach(tab => {

        tab.classList.toggle('active', tab.dataset.lang === newLang);

    });



    const subtitleEl = document.getElementById('header-subtitle');

    if (subtitleEl) subtitleEl.textContent = (langTexts[newLang] || langTexts['ru']).subtitle;



    const searchInput = document.getElementById('searchInput');

    if (searchInput) {

        searchInput.placeholder = (langTexts[newLang] || langTexts['ru']).searchPlaceholder;

    }



    renderCategoryBar();

    updateCartModalTexts();

    updateModalCartButton();



    const cartModalEl = document.getElementById('cartModal');

    if (cartModalEl && cartModalEl.style.display === 'flex') {

        renderCartModal();

    }



    if (mealModal && mealModal.style.display === 'flex') {

        const meal = swipeState.mealList[swipeState.currentIndex];

        if (meal) fillModalWithMeal(meal);

        updateMealNav();

    }

};



/* ==========================================

   BAŞLAT

   ========================================== */

document.addEventListener('DOMContentLoaded', function () {

    document.querySelectorAll('.lang-tab').forEach(tab => {

        tab.classList.toggle('active', tab.dataset.lang === lang);

    });



    const subtitleEl = document.getElementById('header-subtitle');

    if (subtitleEl) subtitleEl.textContent = (langTexts[lang] || langTexts['ru']).subtitle;



    const searchInput = document.getElementById('searchInput');

    if (searchInput) {

        searchInput.placeholder = (langTexts[lang] || langTexts['ru']).searchPlaceholder;



        let searchTimer = null;

        searchInput.addEventListener('input', function (e) {

            clearTimeout(searchTimer);

            searchTimer = setTimeout(() => {

                performSearch(e.target.value);

            }, 250);

        });

    }



    document.addEventListener('keydown', function (e) {

        if (e.key === 'Escape') {

            const searchInput = document.getElementById('searchInput');

            if (searchInput && searchInput.value) {

                window.clearSearch();

            }

        }

    });



    renderCategoryBar();

    updateCartBadge();

});



/* buradan başlıyor qr kod */



/* ==========================================

   QR KOD İLE ANLIK SİPARİŞ

   (Masa no yok — garson masayı biliyor)

   ========================================== */

window.showOrderQR = function () {

    const cart = loadCart();

    if (cart.length === 0) {

        alert('Сначала выберите блюда');

        return;

    }



    // Liste verisini hazırla (masa no YOK)

    const items = [];

    cart.forEach(c => {

        const meal = allItems.find(i => String(i.id) === String(c.id));

        if (!meal) return;

        items.push({

            n: itemName(meal),       // name

            q: c.qty || 1            // quantity

        });

    });



    // QR verisi — masa no yok

    const qrData = {

        i: items,

        ts: Math.floor(Date.now() / 1000)

    };



    // JSON → Base64

    const jsonStr = JSON.stringify(qrData);

    const encoded = btoa(unescape(encodeURIComponent(jsonStr)));



    // Güvenli URL — aynı klasördeki order_view.html

    const SERVER_BASE = 'https://shevshenko.menu-giotto.uz/';

    const qrUrl = SERVER_BASE + 'order_view.html#d=' + encoded;



    // Modal'ı göster

    showQRModal(qrUrl, items.length);

};



/* ==========================================

   🎯 LOGOYA TIKLAYINCA SEPETİ TEMİZLE

   (Sadece offline tablet için)

   ========================================== */

window.resetCartFromLogo = function () {

    // 🎯 Sadece offline modda çalış

    if (location.protocol !== 'file:') {

        return;

    }



    const cart = loadCart();

    if (cart.length === 0) {

        showLogoToast('Корзина уже пуста');

        return;

    }



    localStorage.removeItem(CART_KEY);

    updateCartBadge();

    renderCartModal();

    refreshCardActions();

    closeMealModal();

    closeCart();

    closeQRModal();

    showLogoToast('✓ Корзина очищена');

};



/* ==========================================

   KÜÇÜK BİLDİRİM (LOGO İÇİN)

   ========================================== */

function showLogoToast(message) {

    // Varsa eski toast'ı sil

    const oldToast = document.querySelector('.logo-toast');

    if (oldToast) oldToast.remove();



    const toast = document.createElement('div');

    toast.className = 'logo-toast';

    toast.innerHTML = `<i class="bi bi-check-circle-fill"></i> <span>${message}</span>`;

    document.body.appendChild(toast);



    setTimeout(() => toast.classList.add('show'), 10);

    setTimeout(() => {

        toast.classList.remove('show');

        setTimeout(() => toast.remove(), 300);

    }, 1500);

}



function showQRModal(qrUrl, itemCount) {

    // Varsa eski modal'ı sil

    document.getElementById('qr-modal')?.remove();



    // 🎯 Sepetteki ürünleri al

    const cart = loadCart();

    const items = [];

    cart.forEach(c => {

        const meal = allItems.find(i => String(i.id) === String(c.id));

        if (!meal) return;

        items.push({

            name: itemName(meal),

            qty: c.qty || 1

        });

    });



    // 🎯 Ürün listesi HTML'i

    let itemsListHtml = '';

    if (items.length > 0) {

        itemsListHtml = items.map(item => `

            <div class="qr-item">

                <span class="qr-item-qty">${item.qty}</span>

                <span class="qr-item-name">${escapeHtml(item.name)}</span>

            </div>

        `).join('');

    }



    const modal = document.createElement('div');

    modal.id = 'qr-modal';

    modal.innerHTML = `

        <div class="qr-modal-overlay">

            <div class="qr-modal-content">

                <h3>📱 Покажите QR официанту</h3>

                <p class="qr-sub">Пусть официант отсканирует QR</p>



                <div id="qr-code-container"></div>



                <div class="qr-info">

                    <i class="bi bi-star-fill"></i>

                    <span class="qr-count">Выбрано блюд: <strong>${itemCount}</strong></span>

                </div>



                <!-- 🎯 YENİ: Ürün önizleme listesi -->

                ${items.length > 0 ? `

                <div class="qr-items-preview">

                    ${itemsListHtml}

                </div>

                ` : ''}

                <button onclick="closeQRModal(); openCart();" class="qr-edit-btn">

    ← Изменить выбор

</button>

                <button onclick="closeQRModal()" class="qr-close-btn">Закрыть</button>

            </div>

        </div>

    `;

    document.body.appendChild(modal);



    // QR kod üret

    new QRCode(document.getElementById('qr-code-container'), {

        text: qrUrl,

        width: 200,

        height: 200,

        colorDark: '#000',

        colorLight: '#fff',

        correctLevel: QRCode.CorrectLevel.L

    });

}



/* 🎯 HTML escape */

function escapeHtml(str) {

    const div = document.createElement('div');

    div.textContent = str;

    return div.innerHTML;

}



window.closeQRModal = function () {

    document.getElementById('qr-modal')?.remove();

};



/* burada bitiyor qr kod */





/* ==========================================

   MODAL'DAN ADET DEĞİŞTİR

   ========================================== */

window.changeQtyFromModal = function (delta) {

    if (!currentModalMealId) return;



    changeQty(currentModalMealId, delta);

    updateModalCartButton();

};



/* ==========================================

   İLETİŞİM POPUP

   ========================================== */

window.openInfoPopup = function () {

    const popup = document.getElementById('info-popup');

    if (!popup) return;

    popup.classList.add('visible');

    document.body.style.overflow = 'hidden';

};



window.closeInfoPopup = function () {

    const popup = document.getElementById('info-popup');

    if (!popup) return;

    popup.classList.remove('visible');

    document.body.style.overflow = '';

};



/* ESC ile kapat */

document.addEventListener('keydown', function (e) {

    if (e.key === 'Escape') {

        closeInfoPopup();

    }

});



/* ==========================================

   REKLAM POPUP — DÖNGÜSEL GÖSTERİM

   Her 2 dakikada bir tekrar göster

   ========================================== */

/* ==========================================

   REKLAM POPUP — DÖNGÜSEL GÖSTERİM

   Her 2 dakikada bir tekrar göster

   ========================================== */

document.addEventListener('DOMContentLoaded', function () {

    const popup = document.getElementById('promo-popup');

    if (!popup) return;



    // Resim yüklenemezse popup'ı hemen kaldır

    const img = popup.querySelector('img');

    if (img) {

        // Zaten yüklenememişse

        if (img.complete && img.naturalWidth === 0) {

            popup.remove();

            return;

        }

        // Sonradan hata olursa

        img.addEventListener('error', function() {

            popup.remove();

        });

    }



    const FIRST_DELAY = 2000;       // İlk gösterim: 2 saniye sonra

    const REPEAT_DELAY = 120000;    // Tekrar: 2 dakika sonra



    let popupTimer = null;

    let isFirstShow = true;



    function showPopup() {

        // Başka modal açıksa gösterme

        const mealModalOpen = document.getElementById('meal-modal-overlay')?.style.display === 'flex';

        const cartModalOpen = document.getElementById('cartModal')?.style.display === 'flex';

        const qrModalOpen = document.getElementById('qr-modal');



        if (mealModalOpen || cartModalOpen || qrModalOpen) {

            popupTimer = setTimeout(showPopup, 30000);

            return;

        }



        popup.classList.add('visible');

        document.body.style.overflow = 'hidden';

        isFirstShow = false;

    }



    function scheduleNext() {

        if (popupTimer) clearTimeout(popupTimer);

        popupTimer = setTimeout(showPopup, REPEAT_DELAY);

    }



    popupTimer = setTimeout(showPopup, FIRST_DELAY);



    window.closePromo = function () {

        popup.classList.remove('visible');

        document.body.style.overflow = '';

        scheduleNext();

    };



    window.resetPromo = function () {

        if (popupTimer) clearTimeout(popupTimer);

        popupTimer = setTimeout(showPopup, FIRST_DELAY);

    };

});

/* ==========================================
   MODAL'DAN SEPETE GİT
   ========================================== */
window.goToCartFromModal = function () {
    // 1) Önce meal modal'ı kapat
    closeMealModal();
    
    // 2) Sonra sepet modalını aç
    // (Küçük bir gecikme ile animasyon çakışmasını önle)
    setTimeout(function() {
        openCart();
    }, 200);
};