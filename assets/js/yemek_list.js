$(document).ready(function () {
    // Kategori Başlıklarına Tıklama Olayı
    $('.list-group-item.has-subcategories .category-toggle-link').on('click', function (e) {
        e.preventDefault();
        e.stopPropagation();

        var $parentLi = $(this).closest('.list-group-item');

        // Bu LI'dan sonra gelen ve aynı seviyede olan tüm alt menüleri hedefle
        $parentLi.nextUntil('.list-group-item.meal-category-header, .list-group-item.meal-category-header-no-sub').filter('.list-group-submenu').slideToggle(200);

        // İkonu döndür
        $(this).find('.category-toggle-icon').toggleClass('rotated');
    });

    // Edit ve Delete butonlarının tıklama olayının yayılmasını engelle
    // Fiyat inputunun tıklama ve klavye olaylarını da engelle
    $('.list-group-item span .btn, .meal-price-input').on('click keydown', function (e) {
        e.stopPropagation();
    });

    // Fiyat inputunda ENTER tuşuna basıldığında veya input blur olduğunda (odak dışına çıkınca) güncelleme
    $('.meal-price-input').on('keypress', function (e) {
        if (e.which === 13) { // Enter tuşu
            $(this).blur(); // Odak dışına çıkarak updatePrice fonksiyonunu tetikle
        }
    }).on('blur', function () { // Input odak dışına çıktığında
        var $input = $(this);
        var newPrice = $input.val();
        var mealId = $input.closest('.meal-item').data('meal-id');

        // Fiyat değişmediyse bir şey yapma
        if (newPrice == $input.data('original-price')) {
            return;
        }

        $.ajax({
            url: 'update_meal_price.php',
            type: 'POST',
            dataType: 'json', // JSON olarak yanıt beklediğimizi belirtelim
            data: {
                id: mealId,
                price: newPrice
            },
            success: function (response) {
                if (response.success) {
                    $input.data('original-price', newPrice);
                    console.log('Fiyat güncellendi: ' + newPrice);
                    $input.css('background-color', '#d4edda').animate({ backgroundColor: '#ffffff' }, 1000);
                } else {
                    alert('Fiyat güncellenirken hata oluştu: ' + response.message);
                    $input.val($input.data('original-price'));
                    $input.css('background-color', '#f8d7da').animate({ backgroundColor: '#ffffff' }, 1000);
                }
            },
            error: function (xhr, status, error) {
                alert('AJAX hatası: ' + error);
                $input.val($input.data('original-price'));
                $input.css('background-color', '#f8d7da').animate({ backgroundColor: '#ffffff' }, 1000);
            }
        });
    }).each(function () {
        // Sayfa yüklendiğinde her inputun orijinal fiyatını sakla
        $(this).data('original-price', $(this).val());
    });

    // Aktif/Pasif Toggle Butonu Olayı
    $('.meal-status-toggle').change(function () {
        var $toggle = $(this);
        var mealId = $toggle.data('meal-id');
        var isActive = $toggle.prop('checked') ? 1 : 0; // true=1, false=0

        $.ajax({
            url: 'toggle_meal_status.php',
            type: 'POST',
            dataType: 'json',
            data: {
                id: mealId,
                is_active: isActive
            },
            success: function (response) {
                if (response.success) {
                    console.log('Yemek durumu güncellendi. ID: ' + mealId + ', Aktif: ' + isActive);
                } else {
                    alert('Yemek durumu güncellenirken hata oluştu: ' + response.message);
                    $toggle.bootstrapToggle('toggle'); // Hata olursa toggle'ı eski durumuna geri getir
                }
            },
            error: function (xhr, status, error) {
                alert('AJAX hatası: ' + error);
                $toggle.bootstrapToggle('toggle'); // Hata olursa toggle'ı eski durumuna geri getir
            }
        });
    });

    // Bootstrap Toggle elementlerini başlat
    // Bu kısım sayfa yüklendikten sonra çalışmalı ve tüm meal-status-toggle sınıflı inputları dönüştürmeli
    // Eğer hala "Off Off" gibi görünümler alıyorsanız, bu satırın çalıştığından emin olun.
    $('[data-toggle="toggle"]').bootstrapToggle();
});

$(document).ready(function () {
    // 1) İsme tıklanınca input açılır
    $('.editable-meal-name').on('click', function () {
        var $span = $(this);
        var $container = $span.closest('.meal-edit-name');
        var lang = $span.data('lang');
        $container.find('.editable-meal-name[data-lang="' + lang + '"]').hide();
        var $input = $container.find('.edit-meal-input[data-lang="' + lang + '"]');
        $input.val($span.text()).removeClass('d-none').focus().select();
    });

    // 2) input'tan çıkınca veya Enter'a basınca kayıt olur
    $('.edit-meal-input').on('blur', function () { saveMealName($(this)); });
    $('.edit-meal-input').on('keydown', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); saveMealName($(this)); }
        if (e.key === 'Escape') { $(this).addClass('d-none').siblings('.editable-meal-name[data-lang="' + $(this).data('lang') + '"]').show(); }
    });

    function saveMealName($input) {
        var newValue = $input.val();
        var lang = $input.data('lang');
        var $container = $input.closest('.meal-edit-name');
        var $row = $input.closest('.meal-item');
        var mealId = $row.data('meal-id');
        var $span = $container.find('.editable-meal-name[data-lang="' + lang + '"]');
        var oldValue = $span.text();
        if (newValue.trim() === oldValue.trim() || newValue.trim() === "") {
            $input.addClass('d-none');
            $span.show();
            return;
        }
        // AJAX gönder
        $.ajax({
            url: 'update_meal_name.php',
            type: 'POST',
            data: { id: mealId, lang: lang, value: newValue },
            success: function (res) {
                $span.text(newValue);
                $input.addClass('d-none');
                $span.show();
                $span.css('background', '#d4edda');
                setTimeout(() => { $span.css('background', 'none') }, 1000);
            },
            error: function () {
                alert('Kayıt hatası!');
                $input.addClass('d-none');
                $span.show();
            }
        });
    }
});

$(document).ready(function () {
    // ... (önceki scriptler)

    // Fiyat tıklanınca input açılır
    $(document).on('click', '.editable-meal-price', function (e) {
        e.stopPropagation();
        var $span = $(this);
        var $input = $span.next('.edit-meal-price-input');
        $span.addClass('d-none');
        $input.removeClass('d-none').focus().select();
    });

    // Fiyat inputunda blur veya enter ile kayıt (AJAX ile güncelle)
    $(document).on('blur', '.edit-meal-price-input', function () {
        savePriceEdit($(this));
    });

    $(document).on('keypress', '.edit-meal-price-input', function (e) {
        if (e.which === 13) {
            e.preventDefault();
            savePriceEdit($(this));
        }
    });

    function savePriceEdit($input) {
        var $span = $input.prev('.editable-meal-price');
        var newPrice = $input.val();
        var $mealRow = $input.closest('.meal-item');
        var mealId = $mealRow.data('meal-id');
        // AJAX ile fiyat güncelle
        $.ajax({
            url: 'update_meal_price.php',
            type: 'POST',
            dataType: 'json',
            data: { id: mealId, price: newPrice },
            success: function (response) {
                if (response.success) {
                    $span.text(newPrice);
                    $span.removeClass('d-none');
                    $input.addClass('d-none');
                    $span.css('background-color', '#d4edda').animate({ backgroundColor: '#fff' }, 1000);
                } else {
                    alert('Fiyat güncellenirken hata oluştu: ' + response.message);
                }
            },
            error: function () {
                alert('Bir hata oluştu!');
            }
        });
    }

    // Sayfanın herhangi bir yerine tıklayınca açık olan inputlar kapanır (kayıt etmeden)
    $(document).on('click', function (e) {
        $('.edit-meal-price-input').each(function () {
            var $input = $(this);
            if (!$input.hasClass('d-none')) {
                var $span = $input.prev('.editable-meal-price');
                $span.removeClass('d-none');
                $input.addClass('d-none');
            }
        });
    });
});