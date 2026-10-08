<?php
// update_meal_price.php
// Bu dosya SADECE AJAX isteklerini işler ve JSON yanıtı döndürür.
// HTML veya başka bir çıktı içermemelidir.

include 'config.php'; // Yeni oluşturduğumuz config.php dosyasını dahil ediyoruz

header('Content-Type: application/json'); // JSON yanıtı döndüreceğiz

$response = array('success' => false, 'message' => '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id']) && isset($_POST['price'])) {
    $mealId = intval($_POST['id']);
    $newPrice = $conn->real_escape_string($_POST['price']);

    if (!is_numeric($newPrice) || $newPrice < 0) {
        $response['message'] = 'Geçersiz fiyat değeri.';
    } else {
        $query = "UPDATE menu_items SET price = '$newPrice' WHERE id = $mealId";

        if ($conn->query($query)) {
            $response['success'] = true;
            $response['message'] = 'Fiyat başarıyla güncellendi.';
        } else {
            $response['message'] = 'Veritabanı hatası: ' . $conn->error;
        }
    }
} else {
    $response['message'] = 'Geçersiz istek.';
}

echo json_encode($response);