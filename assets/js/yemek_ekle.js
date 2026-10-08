document.addEventListener("DOMContentLoaded", function () {
    // Eğer halihazırda başlatılmış bir TomSelect varsa önce onu temizle
    var selectEl = document.querySelector("#categorySelect");
    if (selectEl && selectEl.tomselect) {
        selectEl.tomselect.destroy();
    }

    // Çoklu seçimi destekleyen yeni TomSelect ayarı
    new TomSelect("#categorySelect", {
        plugins: ['remove_button'], // Seçilen kategorileri çarpı tuşuyla silmeyi sağlar
        maxItems: null,             // Sınırsız kategori seçimine izin verir
        allowEmptyOption: true,
        create: false
    });
});