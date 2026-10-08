<footer class="site-footer">
    <div class="footer-content">
        <div class="footer-line">
    <i class="bi bi-c-circle"></i>
    <span id="version-trigger" class="footer-clickable">Giotto 2025</span>
    <span class="footer-sep">·</span>
    <a href="https://omer.uz" target="_blank" class="footer-link">Omer.uz</a>
</div>
        <div class="footer-line footer-meta">
            <span id="clear-cache-trigger" class="footer-clickable footer-version">v3.1.6</span>
            <span class="footer-sep">·</span>
            <a href="app-debug.apk" target="_blank" class="footer-link footer-app">
    📱 Android APP
</a>
        </div>
    </div>
</footer>

<script>
    // 'clear-cache-trigger' ID'li elemente tıklandığında Android uygulamasında cache temizle
    document.getElementById('clear-cache-trigger').addEventListener('click', function() {
        if (typeof Android !== "undefined" && Android !== null) {
            console.log("Gizli tetikleyici aktive edildi. Veri temizleme fonksiyonu çağrılıyor.");
            Android.clearAppData();
        } else {
            console.log("Bu özellik yalnızca Android uygulamasında çalışır.");
        }
    });
</script>