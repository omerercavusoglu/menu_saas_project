// PHP'den gelen veriler (önce index.php'de window.* olarak tanımlanacak!)
const allCategories = window.GIOTTO_CATEGORIES || [];
const allItems = window.GIOTTO_MENU_ITEMS || [];

// UYGULAMA MANTIĞI (Aşağıda senin kodlarının devamı!)
const langBtn = document.querySelector('.lang-btn');
let lang = 'ru';
const supportedLangs = ['ru', 'uz', 'en'];
const langFlags = { ru: '🇷🇺', uz: '🇺🇿', en: '🇬🇧' };
let currentView = { id: null, parentId: null };
const backBtn = document.getElementById('backBtn');
langBtn.textContent = langFlags[lang] + ' ' + lang.toUpperCase();

// ... Aşağıya senin diğer kodların tamamen aynı şekilde devam eder!
// buildCategories, buildProducts, window.goBack, window.toggleLang, window.startWithLang vs...

// Yardımcı fonksiyonlar
function getCategoriesByParent(parentId) {
    return allCategories.filter(cat => (cat.parent_id === null || cat.parent_id === undefined ? null : String(cat.parent_id)) == (parentId === null ? null : String(parentId)));
}

function updateBackButtonText() {
    const backText = { ru: '← Назад', uz: '← Orqaga', en: '← Go back' };
    backBtn.textContent = backText[lang];
}

function updateBackButton(parentId) {
    const backBar = document.getElementById('backBar');
    if (parentId !== null) {
        updateBackButtonText();
        backBtn.setAttribute('onclick', 'goBack()');
        backBar.classList.remove('d-none');
    } else {
        const splashBackText = { ru: '← Главный экран', uz: '← Bosh ekran', en: '← Main Screen' };
        backBtn.textContent = splashBackText[lang];
        backBtn.setAttribute('onclick', 'goToSplash()');
        backBar.classList.remove('d-none');
    }
}

function buildCategories(parentId = null) {
    const cats = getCategoriesByParent(parentId);
    document.getElementById('catRow').innerHTML = cats.map((cat, index) => `
        <div class="col-6 col-sm-4 col-md-3">
            <div class="cat-card item-animate" style="animation-delay: ${index * 100}ms;" onclick="showCategory('${cat.id}')">
                <img src="${cat.image}" alt="${cat[lang]}"><div class="overlay">${cat[lang]}</div>
            </div>
        </div>`).join('');
    document.getElementById('catRow').classList.remove('d-none');
    document.getElementById('prodRow').classList.add('d-none');
    updateBackButton(parentId);
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function buildProducts(catId) {
    document.getElementById('catRow').classList.add('d-none');
    document.getElementById('prodRow').classList.remove('d-none');
    updateBackButton(catId);
    const filtered = allItems.filter(i => i.category_id == catId);
    const pr = document.getElementById('prodRow');
    pr.innerHTML = filtered.map((i, index) => `
        <div class="col-4 col-md-4 mb-4" onclick="openMealModal(${i.id})">
            <div class="card bg-dark text-white h-100 item-animate" style="cursor: pointer; animation-delay: ${index * 100}ms;">
                <img src="${i.image}" class="menu-img" alt="${i[lang]}">
                <div class="card-body text-center d-flex flex-column justify-content-between">
                    <h6 class="mb-2">${i[lang]}</h6>
                    <p class="fs-6 fw-bold">${Number(i.price).toLocaleString('ru-RU').replace(/,/g, ' ')} UZS</p>
                </div>
            </div>
        </div>`).join('');
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Global fonksiyonlar
window.goToSplash = function () {
    const mainDiv = document.getElementById('main');
    const splashDiv = document.getElementById('splash');
    mainDiv.classList.remove('fade-in');
    mainDiv.classList.add('fade-out');
    document.body.classList.remove('lang-visible');
    setTimeout(() => {
        mainDiv.classList.add('d-none');
        splashDiv.classList.remove('d-none', 'fade-out');
        langBtn.classList.add('d-none');
    }, 600);
};

window.showCategory = function (catId) {
    const category = allCategories.find(c => c.id == catId);
    if (!category) return;
    currentView = { id: category.id, parentId: category.parent_id };
    const subCats = getCategoriesByParent(catId);
    (subCats.length > 0) ? buildCategories(catId) : buildProducts(catId);
};

window.goBack = function () {
    const parentCategoryToShow = allCategories.find(c => String(c.id) === String(currentView.parentId));
    if (parentCategoryToShow) {
        showCategory(parentCategoryToShow.id);
    } else {
        currentView = { id: null, parentId: null };
        buildCategories();
    }
};

window.toggleLang = function () {
    let currentIndex = supportedLangs.indexOf(lang);
    lang = supportedLangs[(currentIndex + 1) % supportedLangs.length];
    langBtn.textContent = langFlags[lang] + ' ' + lang.toUpperCase();
    if (currentView.id === null) {
        const splashBackText = { ru: '← Главный экран', uz: '← Bosh ekran', en: '← Main Screen' };
        document.getElementById('backBtn').textContent = splashBackText[lang];
    } else {
        updateBackButtonText();
    }
    if (currentView.id) {
        const currentCatHasProducts = getCategoriesByParent(currentView.id).length === 0;
        if (currentCatHasProducts) buildProducts(currentView.id);
        else buildCategories(currentView.id);
    } else {
        buildCategories();
    }
};

window.startWithLang = function (selectedLang) {
    lang = selectedLang;
    langBtn.textContent = langFlags[lang] + ' ' + lang.toUpperCase();
    const splash = document.getElementById('splash');
    splash.classList.add('fade-out');
    setTimeout(() => {
        splash.classList.add('d-none');
        const mainDiv = document.getElementById('main');
        mainDiv.classList.remove('d-none');
        mainDiv.classList.add('fade-in');
        document.body.classList.add('lang-visible');
        buildCategories();
        langBtn.classList.remove('d-none');
    }, 600);
};

// YEMEK DETAY MODAL KODLARI
const mealModal = document.getElementById('meal-modal-overlay');
const mealModalCloseBtn = mealModal.querySelector('.modal-close-btn');
window.openMealModal = function (mealId) {
    const meal = allItems.find(item => String(item.id) === String(mealId));
    if (!meal) return;
    document.getElementById('modal-meal-image').src = meal.image;
    document.getElementById('modal-meal-name').textContent = meal[lang];
    const description = meal['description_' + lang] || meal.description_ru || '';
    document.getElementById('modal-meal-description').textContent = description;
    const formattedPrice = Number(meal.price).toLocaleString('ru-RU').replace(/,/g, ' ') + ' UZS';
    document.getElementById('modal-meal-price').textContent = formattedPrice;
    mealModal.style.display = 'flex';
};
function closeMealModal() { mealModal.style.display = 'none'; }
mealModalCloseBtn.addEventListener('click', closeMealModal);
mealModal.addEventListener('click', (e) => { if (e.target === mealModal) closeMealModal(); });

// STATUS MODAL KODLARI
const openStatusBtn = document.getElementById('version-trigger');
if (openStatusBtn) {
    const modalOverlay = document.getElementById('status-modal-overlay');
    const closeStatusBtn = modalOverlay.querySelector('#status-modal-close-btn');
    const iframe = document.getElementById('status-iframe');
    function openStatusModal() { iframe.src = 'status.html'; modalOverlay.style.display = 'flex'; }
    function closeStatusModal() { modalOverlay.style.display = 'none'; iframe.src = 'about:blank'; }
    openStatusBtn.addEventListener('click', function (e) { e.preventDefault(); openStatusModal(); });
    closeStatusBtn.addEventListener('click', closeStatusModal);
    modalOverlay.addEventListener('click', function (e) { if (e.target === modalOverlay) closeStatusModal(); });
}

// SERVICE WORKER VE TABLET PING SİSTEMİ
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/sw.js', { scope: '/' })
        .then(() => console.log("✅ SW kaydedilmek üzere gönderildi."))
        .catch(err => console.error("❌ SW kayıt hatası:", err));

    navigator.serviceWorker.addEventListener('message', event => {
        const data = event.data;
        if (data && (data.type === 'complete' || data.type === 'info')) {
            if (!window.pingSentForThisSession) {
                sendPing(data.cacheName);
                window.pingSentForThisSession = true;
            }
        }
    });
}

function getOrCreateTabletUUID() {
    let uuid = localStorage.getItem('tabletUUID');
    if (!uuid) {
        uuid = 'tablet-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
        localStorage.setItem('tabletUUID', uuid);
    }
    return uuid;
}

function getBranchFromURL() {
    const pathParts = window.location.pathname.split('/').filter(part => part !== '');
    if (pathParts.length > 0) {
        return pathParts[0];
    }
    return 'merkez';
}

function sendPing(cacheName) {
    const pingData = {
        uuid: getOrCreateTabletUUID(),
        version: cacheName || 'unknown',
        branch: getBranchFromURL(),
        deviceInfo: navigator.userAgent
    };

    fetch('ping.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(pingData)
    })
        .then(response => {
            if (!response.ok) throw new Error('Ping response was not ok.');
            return response.json();
        })
        .then(data => {
            if (data.status === 'success') {
                console.log(`✅ Tablet check-in başarılı: UUID=${pingData.uuid}, Şube=${pingData.branch}, Versiyon=${pingData.version}`);
            }
        })
        .catch(error => {
            console.warn('❌ Check-in gönderilemedi (muhtemelen offline):', error);
        });
}
