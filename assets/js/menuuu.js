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
        searchEmpty: 'Ничего не найдено'
    },
    uz: {
        subtitle: 'Siz uchun tanlagan eng mazali taomlar',
        all: 'Barchasi',
        empty: 'Bu kategoriyada taom yo\'q.',
        searchPlaceholder: 'Menyuda qidirish...',
        searchEmpty: 'Hech narsa topilmadi'
    },
    en: {
        subtitle: 'The finest flavors we have selected for you',
        all: 'All',
        empty: 'No dishes in this category.',
        searchPlaceholder: 'Search menu...',
        searchEmpty: 'Nothing found'
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

    // Arama kutusunu temizle
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
   YEMEK LİSTESİ
   ========================================== */
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
        grid.innerHTML = `<p style="grid-column:1/-1;text-align:center;color:#666;padding:40px;">${T.empty}</p>`;
        return;
    }

    grid.innerHTML = filtered.map(item => `
        <div class="product-card" onclick="openMealModal(${item.id})">
            <img src="${item.image}" alt="${itemName(item)}">
            <div class="product-info">
                <h3 class="product-name">${itemName(item)}</h3>
                <p class="product-desc">${itemDesc(item)}</p>
                <p class="product-price">${formatPrice(item.price)}</p>
            </div>
        </div>
    `).join('');
}

function renderAllProducts() {
    const grid = document.getElementById('prodRow');
    if (!grid) return;

    const T = langTexts[lang] || langTexts['ru'];

    const filtered = [...allItems].sort((a, b) =>
        (a.sort_order || 0) - (b.sort_order || 0) || a.id - b.id
    );

    if (filtered.length === 0) {
        grid.innerHTML = `<p style="grid-column:1/-1;text-align:center;color:#666;padding:40px;">${T.empty}</p>`;
        return;
    }

    grid.innerHTML = filtered.map(item => `
        <div class="product-card" onclick="openMealModal(${item.id})">
            <img src="${item.image}" alt="${itemName(item)}">
            <div class="product-info">
                <h3 class="product-name">${itemName(item)}</h3>
                <p class="product-desc">${itemDesc(item)}</p>
                <p class="product-price">${formatPrice(item.price)}</p>
            </div>
        </div>
    `).join('');
}

/* ==========================================
   ARAMA (LIVE SEARCH)
   ========================================== */
function performSearch(query) {
    searchQuery = (query || '').trim().toLowerCase();
    const grid = document.getElementById('prodRow');
    if (!grid) return;

    const T = langTexts[lang] || langTexts['ru'];
    const clearBtn = document.getElementById('searchClear');

    if (searchQuery === '') {
        if (clearBtn) clearBtn.style.display = 'none';
        // Aktif kategoriye dön
        if (activeCategoryId === 'all') {
            renderAllProducts();
        } else {
            renderProducts(activeCategoryId);
        }
        return;
    }

    if (clearBtn) clearBtn.style.display = 'flex';

    // Tüm dillerde + açıklamalarda ara
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
        grid.innerHTML = `<p style="grid-column:1/-1;text-align:center;color:#666;padding:40px;">${T.searchEmpty}</p>`;
        return;
    }

    grid.innerHTML = filtered.map(item => `
        <div class="product-card" onclick="openMealModal(${item.id})">
            <img src="${item.image}" alt="${itemName(item)}">
            <div class="product-info">
                <h3 class="product-name">${itemName(item)}</h3>
                <p class="product-desc">${itemDesc(item)}</p>
                <p class="product-price">${formatPrice(item.price)}</p>
            </div>
        </div>
    `).join('');
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

function buildMealList() {
    let filtered;

    if (searchQuery) {
        // Arama aktifse arama sonuçları üzerinde gezin
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

    // Arama placeholder'ı güncelle
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.placeholder = (langTexts[newLang] || langTexts['ru']).searchPlaceholder;
    }

    renderCategoryBar();

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

    // Arama kutusu olayları
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.placeholder = (langTexts[lang] || langTexts['ru']).searchPlaceholder;

        let searchTimer = null;
        searchInput.addEventListener('input', function (e) {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => {
                performSearch(e.target.value);
            }, 250); // 250ms debounce
        });
    }

    // ESC ile aramayı temizle
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            const searchInput = document.getElementById('searchInput');
            if (searchInput && searchInput.value) {
                window.clearSearch();
            }
        }
    });

    renderCategoryBar();
});