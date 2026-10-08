<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/header.php';

$message = '';
$messageType = '';

function getSplashImages($pdo) {
    $active = $pdo->query("SELECT id, splash_bg, is_active FROM settings WHERE is_active = 1 AND splash_bg IS NOT NULL AND splash_bg <> '' ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);

    $allImgs = $pdo->query("SELECT id, splash_bg, is_active, created_at FROM settings WHERE splash_bg IS NOT NULL AND splash_bg <> '' ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

    return array('active' => $active, 'allImgs' => $allImgs);
}

$splashData = getSplashImages($pdo);
$activeImage = $splashData['active'];
$allImages = $splashData['allImgs'];

// ==================== POST: YENİ RESİM YÜKLE ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $file = isset($_FILES['bg']) ? $_FILES['bg'] : null;

    if ($file && !empty($file['name']) && $file['error'] === UPLOAD_ERR_OK) {
        $max_size = 5 * 1024 * 1024;

        // Gerçek MIME kontrolü
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowed_mime = array('image/jpeg', 'image/png', 'image/gif');

        if (!in_array($mime, $allowed_mime, true)) {
            $message = 'Sadece JPEG, PNG veya GIF resimleri yükleyebilirsiniz.';
            $messageType = 'danger';
        } elseif ($file['size'] > $max_size) {
            $message = 'Resim boyutu 5MB\'tan küçük olmalı.';
            $messageType = 'danger';
        } else {
            $ext_map = array('image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif');
            $ext = $ext_map[$mime];
            $new_file_name = 'splash_' . date('YmdHis') . '_' . substr(md5(uniqid(mt_rand(), true)), 0, 8) . '.' . $ext;
            $target_path = 'images/' . $new_file_name;

            if (move_uploaded_file($file['tmp_name'], $target_path)) {
                try {
                    $pdo->beginTransaction();
                    $pdo->query("UPDATE settings SET is_active = 0 WHERE splash_bg IS NOT NULL AND splash_bg <> ''");

                    $stmt = $pdo->prepare("INSERT INTO settings (splash_bg, is_active) VALUES (?, 1)");
                    $stmt->execute(array($target_path));
                    $pdo->commit();

                    $message = 'Фоновое изображение успешно обновлено!';
                    $messageType = 'success';

                    $splashData = getSplashImages($pdo);
                    $activeImage = $splashData['active'];
                    $allImages = $splashData['allImgs'];
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    if (file_exists($target_path)) unlink($target_path);
                    error_log("Resim yükleme hatası: " . $e->getMessage());
                    $message = 'Veritabanı işlemi sırasında bir hata oluştu.';
                    $messageType = 'danger';
                }
            } else {
                $message = 'Resim yüklenemedi. Dosya izinlerini kontrol edin.';
                $messageType = 'danger';
            }
        }
    } else {
        $message = 'Lütfen yüklenecek bir resim dosyası seçin.';
        $messageType = 'warning';
    }
}

// ==================== GET: ESKİ RESMİ AKTİF ET ====================
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['set_active'])) {
    $id_to_activate = (int)$_GET['set_active'];

    if ($id_to_activate > 0) {
        try {
            $pdo->beginTransaction();

            $stmtCheck = $pdo->prepare("SELECT id FROM settings WHERE id = ? AND splash_bg IS NOT NULL AND splash_bg <> '' LIMIT 1");
            $stmtCheck->execute(array($id_to_activate));
            $exists = $stmtCheck->fetch();

            if ($exists) {
                $pdo->query("UPDATE settings SET is_active = 0 WHERE splash_bg IS NOT NULL AND splash_bg <> ''");
                $stmtActivate = $pdo->prepare("UPDATE settings SET is_active = 1 WHERE id = ?");
                $stmtActivate->execute(array($id_to_activate));
                $pdo->commit();

                $_SESSION['flash_splash'] = array('msg' => 'Seçili resim aktif arka plan olarak ayarlandı!', 'type' => 'success');
            } else {
                $_SESSION['flash_splash'] = array('msg' => 'Geçersiz resim seçimi.', 'type' => 'warning');
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log("Resim aktif etme hatası: " . $e->getMessage());
            $_SESSION['flash_splash'] = array('msg' => 'Veritabanı hatası oluştu.', 'type' => 'danger');
        }
    }

    header("Location: panel_splash.php");
    exit();
}

// Flash mesajı
if (isset($_SESSION['flash_splash'])) {
    $message = $_SESSION['flash_splash']['msg'];
    $messageType = $_SESSION['flash_splash']['type'];
    unset($_SESSION['flash_splash']);
}
?>
<link href="assets/css/panel_splash.css" rel="stylesheet">

<style>
    /* Sayfa yüksekliğini sınırla */
    .splash-page {
        height: calc(100vh - 80px);
        overflow: hidden;
        padding: 10px 15px;
    }
    .splash-col {
        height: 100%;
        overflow-y: auto;
    }
    .img-thumb {
        width: 100px;
        height: 100px;
        object-fit: cover;
        border-radius: 6px;
        border: 2px solid transparent;
        transition: all 0.2s;
        cursor: pointer;
    }
    .img-thumb:hover {
        border-color: #0d6efd;
    }
    .img-thumb.selected {
        border-color: #198754;
        box-shadow: 0 0 0 3px rgba(25, 135, 84, 0.2);
    }
    .image-card {
        margin: 5px;
        position: relative;
    }
    .image-card .actions {
        display: flex;
        gap: 3px;
        justify-content: center;
        margin-top: 3px;
    }
    .active-preview {
        max-width: 100%;
        max-height: 200px;
        border-radius: 8px;
        border: 2px solid #198754;
    }
</style>

<div class="container-fluid splash-page">
    <h4 class="text-center mb-2">🎬 <?php echo htmlspecialchars(isset($lang['homepagead']) ? $lang['homepagead'] : 'Homepage'); ?></h4>

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $messageType; ?> py-2 text-center mb-2">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <div class="row g-3" style="height: calc(100% - 60px);">

        <!-- SOL SÜTUN: YÜKLEME + AKTİF ÖNİZLEME -->
        <div class="col-md-5 splash-col">
            <div class="card shadow-sm mb-2">
                <div class="card-body">
                    <h6 class="fw-bold mb-2">📤 <?php echo htmlspecialchars(isset($lang['bgresimsec']) ? $lang['bgresimsec'] : 'Yeni Resim Yükle'); ?></h6>
                    <form method="POST" enctype="multipart/form-data">
                        <input type="file" name="bg" id="bgFile" class="form-control form-control-sm mb-2" accept="image/jpeg,image/png,image/gif" required>
                        <small class="text-muted d-block mb-2"><?php echo htmlspecialchars(isset($lang['bgresimsecinfo']) ? $lang['bgresimsecinfo'] : 'Maks. 5MB — JPEG, PNG, GIF'); ?></small>
                        <button type="submit" name="save" class="btn btn-primary btn-sm w-100">
                            <?php echo htmlspecialchars(isset($lang['yukleaktifyap']) ? $lang['yukleaktifyap'] : 'Yükle ve Aktif Yap'); ?>
                        </button>
                    </form>
                </div>
            </div>

            <?php if (!empty($activeImage['splash_bg'])): ?>
                <div class="card shadow-sm">
                    <div class="card-body text-center">
                        <h6 class="fw-bold mb-2 text-success">✅ <?php echo htmlspecialchars(isset($lang['aktifresim']) ? $lang['aktifresim'] : 'Aktif Resim'); ?></h6>
                        <img src="<?php echo htmlspecialchars($activeImage['splash_bg']); ?>" class="active-preview" alt="Aktif Splash">
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- SAĞ SÜTUN: ESKİ RESİMLER -->
        <div class="col-md-7 splash-col">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h6 class="fw-bold mb-2">🖼️ <?php echo htmlspecialchars(isset($lang['dahaoncekiresimler']) ? $lang['dahaoncekiresimler'] : 'Daha Önceki Resimler'); ?>
                        <span class="badge bg-secondary"><?php echo count($allImages); ?></span>
                    </h6>

                    <?php if (count($allImages) === 0): ?>
                        <p class="text-muted text-center py-4">Henüz yüklenmiş resim yok.</p>
                    <?php else: ?>
                        <div class="d-flex flex-wrap">
                            <?php foreach ($allImages as $img): ?>
                                <div class="text-center image-card">
                                    <a href="?set_active=<?php echo (int)$img['id']; ?>" title="Aktif yap">
                                        <img src="<?php echo htmlspecialchars($img['splash_bg']); ?>"
                                             class="img-thumb<?php echo $img['is_active'] ? ' selected' : ''; ?>"
                                             alt="Splash">
                                    </a>
                                    <small class="text-muted d-block" style="font-size: 0.7rem;">
                                        <?php echo isset($img['created_at']) ? date('d.m.Y', strtotime($img['created_at'])) : ''; ?>
                                    </small>
                                    <div class="actions">
                                        <?php if ($img['is_active']): ?>
                                            <span class="badge bg-success"><?php echo htmlspecialchars(isset($lang['aktif']) ? $lang['aktif'] : 'Aktif'); ?></span>
                                        <?php else: ?>
                                            <a href="?set_active=<?php echo (int)$img['id']; ?>" class="btn btn-sm btn-outline-secondary py-0 px-1" style="font-size:0.7rem;">
                                                <?php echo htmlspecialchars(isset($lang['aktifyap']) ? $lang['aktifyap'] : 'Aktif Yap'); ?>
                                            </a>
                                        <?php endif; ?>
                                        <a href="delete_splash_image.php?id=<?php echo (int)$img['id']; ?>"
                                           class="btn btn-sm btn-danger py-0 px-1 confirm-delete"
                                           style="font-size:0.7rem;"
                                           onclick="return confirm('Bu resmi silmek istediğinize emin misiniz?');">
                                            <?php echo htmlspecialchars(isset($lang['sil']) ? $lang['sil'] : 'Sil'); ?>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</div>