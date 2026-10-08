<?php
require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

// Yetki kontrolü
if (!isset($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
    http_response_code(401);
    echo json_encode(array('success' => false, 'message' => 'Unauthorized'));
    exit;
}

// İki mod destekleniyor:
// 1) Tek tek: id + order
// 2) Toplu: order[] = {id, order}
$updates = array();

if (isset($_POST['id'], $_POST['order'])) {
    // Tekli güncelleme
    $updates[] = array('id' => (int)$_POST['id'], 'order' => (int)$_POST['order']);
} elseif (isset($_POST['order']) && is_array($_POST['order'])) {
    // Toplu güncelleme
    foreach ($_POST['order'] as $item) {
        if (is_array($item) && isset($item['id'], $item['order'])) {
            $updates[] = array('id' => (int)$item['id'], 'order' => (int)$item['order']);
        }
    }
} else {
    http_response_code(400);
    echo json_encode(array('success' => false, 'message' => 'Invalid request'));
    exit;
}

if (empty($updates)) {
    echo json_encode(array('success' => false, 'message' => 'No valid data'));
    exit;
}

try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("UPDATE categories SET sort_order = ? WHERE id = ?");

    foreach ($updates as $u) {
        if ($u['id'] > 0) {
            $stmt->execute(array($u['order'], $u['id']));
        }
    }

    $pdo->commit();
    echo json_encode(array('success' => true));
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("sort_categories hatası: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(array('success' => false, 'message' => 'DB error'));
}