<?php
require_once __DIR__ . '/config.php';

// ==================== PARAMETRE ====================
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    $_SESSION['flash_yemeksil'] = array('msg' => 'Geçersiz yemek ID.', 'type' => 'danger');
    header('Location: yemek_list.php');
    exit;
}

// ==================== YEMEĞİ ÇEK ====================
$stmt = $pdo->prepare("SELECT id, ru, category_id, image FROM menu_items WHERE id = ?");
$stmt->execute(array($id));
$food = $stmt->fetch();

if (!$food) {
    $_SESSION['flash_yemeksil'] = array('msg' => 'Böyle bir yemek bulunamadı.', 'type' => 'danger');
    header('Location: yemek_list.php');
    exit;
}

// ==================== SİLME ONAYI (POST) ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
    try {
        // Resmi de sil (opsiyonel)
        if (!empty($food['image']) && file_exists($food['image'])) {
            // Sadece images/ klasörü içindeyse sil (güvenlik)
            $realPath = realpath($food['image']);
            $imagesDir = realpath('images');
            if ($realPath !== false && $imagesDir !== false && strpos($realPath, $imagesDir) === 0) {
                @unlink($realPath);
            }
        }

        // Yemeği sil
        $stmt = $pdo->prepare("DELETE FROM menu_items WHERE id = ?");
        $stmt->execute(array($id));

        $_SESSION['flash_yemeksil'] = array('msg' => 'Yemek başarıyla silindi.', 'type' => 'success');

        // Silinen yemeğin kategorisine dön
        $redirect_cat = (int)$food['category_id'];
    } catch (PDOException $e) {
        error_log("Yemek silme hatası: " . $e->getMessage());
        $_SESSION['flash_yemeksil'] = array('msg' => 'Yemek silinirken hata oluştu.', 'type' => 'danger');
        $redirect_cat = 0;
    }

    $url = 'yemek_list.php';
    if ($redirect_cat > 0) {
        $url .= '?cat=' . $redirect_cat;
    }
    header('Location: ' . $url);
    exit;
}

// ==================== ONAY EKRANI (HTML) ====================
require_once __DIR__ . '/header.php';
?>

<div class="container" style="max-width: 600px; margin-top: 30px;">
    <div class="card shadow-sm border-danger">
        <div class="card-body text-center">

            <div style="font-size: 3rem; color: #dc3545;">
                <i class="fa fa-exclamation-triangle"></i>
            </div>

            <h4 class="mb-3">Yemek Sil</h4>

            <p class="mb-4">
                <strong><?php echo htmlspecialchars($food['ru']); ?></strong> adlı yemeği silmek istediğinizden emin misiniz?
                <br>
                <small class="text-muted">Bu işlem geri alınamaz.</small>
            </p>

            <form method="POST" action="yemek_sil.php?id=<?php echo (int)$id; ?>">
                <input type="hidden" name="confirm" value="1">
                <button type="submit" class="btn btn-danger">
                    <i class="fa fa-trash"></i> Evet, Sil
                </button>
                <a href="yemek_list.php<?php echo $food['category_id'] ? '?cat=' . (int)$food['category_id'] : ''; ?>" 
                   class="btn btn-secondary">
                    <i class="fa fa-times"></i> Vazgeç
                </a>
            </form>

        </div>
    </div>
</div>