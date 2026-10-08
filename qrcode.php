<?php
require_once __DIR__ . '/config.php';

// Menü URL'sini otomatik al
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$menu_url = $protocol . '://' . $host . '/';

// İstersen sabit URL de kullanabilirsin:
// $menu_url = 'https://burger.menu-giotto.uz';

// QR kod boyutu (piksel)
$qr_size = 280;

// Sayfa başlığı
$page_title = 'QR Kod';

require_once __DIR__ . '/header.php';
?>

<style>
    .qr-wrapper {
        min-height: calc(100vh - 80px);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 20px;
        text-align: center;
    }
    .qr-card {
        background: #fff;
        padding: 30px;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        display: inline-block;
    }
    #qrcode-container {
        background: white;
        border-radius: 8px;
        padding: 10px;
        display: inline-block;
        margin-bottom: 20px;
    }
    #qrcode-container canvas,
    #qrcode-container img {
        display: block;
    }
    .qr-url {
        font-family: 'Courier New', monospace;
        color: #6c757d;
        font-size: 0.9rem;
        word-break: break-all;
        max-width: 300px;
        margin: 0 auto 15px auto;
    }
    .qr-actions {
        display: flex;
        gap: 8px;
        justify-content: center;
        flex-wrap: wrap;
    }
    .qr-title {
        font-size: 1.3rem;
        font-weight: 600;
        color: #212529;
        margin-bottom: 5px;
    }
    .qr-subtitle {
        color: #6c757d;
        font-size: 0.9rem;
        margin-bottom: 25px;
    }
    @media print {
        body * { visibility: hidden; }
        .qr-wrapper, .qr-wrapper * { visibility: visible; }
        .qr-wrapper { position: absolute; left: 0; top: 0; width: 100%; }
        .qr-actions { display: none; }
    }
</style>

<div class="qr-wrapper">
    <div class="qr-title">
        <i class="fas fa-qrcode"></i>  QR-код меню

    </div>
    <div class="qr-subtitle">Клиенты могут ознакомиться с меню, отсканировав его с помощью своих телефонов
</div>

    <div class="qr-card">
        <div id="qrcode-container"></div>

        <div class="qr-url"><?php echo htmlspecialchars($menu_url); ?></div>

        <div class="qr-actions">
            <button type="button" class="btn btn-primary btn-sm" onclick="printQR()">
                <i class="fas fa-print"></i> Печать
            </button>
            <button type="button" class="btn btn-success btn-sm" onclick="downloadQR()">
                <i class="fas fa-download"></i> Скачать (PNG)
            </button>
            <a href="<?php echo htmlspecialchars($menu_url); ?>" target="_blank" class="btn btn-secondary btn-sm">
                <i class="fas fa-external-link-alt"></i> Открыть меню
            </a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/gh/davidshimjs/qrcodejs/qrcode.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var qrContainer = document.getElementById('qrcode-container');
    if (!qrContainer) return;

    new QRCode(qrContainer, {
        text: "<?php echo htmlspecialchars($menu_url); ?>",
        width: <?php echo (int)$qr_size; ?>,
        height: <?php echo (int)$qr_size; ?>,
        colorDark: "#000000",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.H
    });
});

function printQR() {
    window.print();
}

function downloadQR() {
    // QR canvas'ı al
    var canvas = document.querySelector('#qrcode-container canvas');
    if (!canvas) {
        // Bazı tarayıcılar img olarak render eder
        var img = document.querySelector('#qrcode-container img');
        if (img) {
            var link = document.createElement('a');
            link.download = 'menu-qr.png';
            link.href = img.src;
            link.click();
        }
        return;
    }

    // Canvas'ı PNG olarak indir
    var link = document.createElement('a');
    link.download = 'menu-qr.png';
    link.href = canvas.toDataURL('image/png');
    link.click();
}
</script>