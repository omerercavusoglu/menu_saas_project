<?php
include 'db.php';
header('Content-Type: application/json; charset=utf-8');

if (isset($_POST['meal_ids']) && is_array($_POST['meal_ids'])) {
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("UPDATE menu_items SET sort_order=? WHERE id=?");
        foreach ($_POST['meal_ids'] as $sort => $meal_id) {
            $stmt->execute([(int)$sort, (int)$meal_id]);
        }
        $pdo->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'meal_ids yok']);
}