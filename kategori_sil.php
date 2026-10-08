<?php
require_once __DIR__ . '/config.php';

// ==================== PARAMETRE ====================
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    $_SESSION['flash_kategorisil'] = array('msg' => 'Geçersiz kategori ID.', 'type' => 'danger');
    header('Location: kategori_list.php');
    exit;
}

// ==================== KATEGORİYİ ÇEK ====================
$stmt = $pdo->prepare("SELECT id, ru FROM categories WHERE id = ?");
$stmt->execute(array($id));
$cat = $stmt->fetch();

if (!$cat) {
    $_SESSION['flash_kategorisil'] = array('msg' => 'Böyle bir kategori bulunamadı.', 'type' => 'danger');
    header('Location: kategori_list.php');
    exit;
}

// ==================== SİLME ONAYI (POST) ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
    // Alt kategori var mı?
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE parent_id = ?");
    $stmt->execute(array($id));
    $subCount = (int)$stmt->fetchColumn();

    if ($subCount > 0) {
        $_SESSION['flash_kategorisil'] = array(
            'msg' => 'Bu kategorinin ' . $subCount . ' alt kategorisi var. Önce onları silmelisiniz.',
            'type' => 'danger'
        );
        header('Location: kategori_list.php');
        exit;
    }

    // Bu kategoride yemek var mı? (opsiyonel kontrol)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM menu_items WHERE category_id = ?");
    $stmt->execute(array($id));
    $mealCount = (int)$stmt->fetchColumn();

    if ($mealCount > 0) {
        $_SESSION['flash_kategorisil'] = array(
            'msg' => 'Bu kategoride ' . $mealCount . ' yemek var. Önce yemekleri silmelisiniz.',
            'type' => 'danger'
        );
        header('Location: kategori_list.php');
        exit;
    }

    // Sil
    try {
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute(array($id));

        $_SESSION['flash_kategorisil'] = array('msg' => 'Kategori başarıyla silindi.', 'type' => 'success');
    } catch (PDOException $e) {
        error_log("Kategori silme hatası: " . $e->getMessage());
        $_SESSION['flash_kategorisil'] = array('msg' => 'Kategori silinirken hata oluştu.', 'type' => 'danger');
    }

    header('Location: kategori_list.php');
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

            <h4 class="mb-3">Kategori Sil</h4>

            <p class="mb-4">
                <strong><?php echo htmlspecialchars($cat['ru']); ?></strong> adlı kategoriyi silmek istediğinizden emin misiniz?
            </p>

            <form method="POST" action="kategori_sil.php?id=<?php echo (int)$id; ?>">
                <input type="hidden" name="confirm" value="1">
                <button type="submit" class="btn btn-danger">
                    <i class="fa fa-trash"></i> Evet, Sil
                </button>
                <a href="kategori_list.php" class="btn btn-secondary">
                    <i class="fa fa-times"></i> Vazgeç
                </a>
            </form>

        </div>
    </div>
</div>