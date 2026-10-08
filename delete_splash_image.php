<?php
require_once __DIR__ . '/db.php';

// Güvenlik: Sadece GET isteği ve geçerli bir 'id' parametresiyle gelindiğinde işlem yap
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $imageId = (int)$_GET['id']; // Tamsayıya dönüştürerek güvenliği artır

    if ($imageId > 0) {
        try {
            $pdo->beginTransaction();

            // 1. Silinecek resmin dosya yolunu al ve aktif olup olmadığını kontrol et
            $stmt = $pdo->prepare("SELECT splash_bg, is_active FROM settings WHERE id = ? AND splash_bg IS NOT NULL AND splash_bg <> '' LIMIT 1");
            $stmt->execute(array($imageId));
            $imageToDelete = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($imageToDelete) {
                $filePath = $imageToDelete['splash_bg'];
                $wasActive = (bool)$imageToDelete['is_active'];

                // 2. Veritabanından kaydı sil
                $stmtDelete = $pdo->prepare("DELETE FROM settings WHERE id = ?");
                $stmtDelete->execute(array($imageId));

                // 3. Sunucudaki fiziksel dosyayı sil
                if (file_exists($filePath) && is_file($filePath)) {
                    unlink($filePath);
                }

                // 4. Eğer silinen resim aktif olan resim idiyse, yeni bir aktif resim ata veya varsayılan duruma geç
                if ($wasActive) {
                    // Mevcut diğer resimlerden en yenisini bul ve aktif yap
                    $stmtNewActive = $pdo->query("SELECT id FROM settings WHERE splash_bg IS NOT NULL AND splash_bg <> '' ORDER BY id DESC LIMIT 1");
                    $newActive = $stmtNewActive->fetch(PDO::FETCH_ASSOC);

                    if ($newActive) {
                        // Yeni bir resim varsa onu aktif yap
                        $stmtUpdate = $pdo->prepare("UPDATE settings SET is_active = 1 WHERE id = ?");
                        $stmtUpdate->execute(array($newActive['id']));
                    } else {
                        // Hiç resim kalmadıysa veya varsayılan bir ayar varsa onu ayarla
                        // Örneğin: $pdo->query("UPDATE settings SET is_active = 1 WHERE id = [DEFAULT_SPLASH_ID]");
                        // Şu anki senaryoda bir varsayılan splash yoksa, hiçbiri aktif olmaz.
                        // Kullanıcıya bilgi vermek gerekebilir.
                    }
                }
                
                $pdo->commit();
                // Başarı mesajıyla panele geri yönlendir
                header("Location: panel_splash.php?message=delete_success");
                exit();

            } else {
                // Resim bulunamadı veya geçersiz kimlik
                header("Location: panel_splash.php?message=delete_notfound");
                exit();
            }

        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Resim silme hatası: " . $e->getMessage());
            // Hata mesajıyla panele geri yönlendir
            header("Location: panel_splash.php?message=delete_error");
            exit();
        }
    }
}

// Geçersiz istek durumunda panele geri yönlendir
header("Location: panel_splash.php?message=delete_invalid_request");
exit();
?>