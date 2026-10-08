<?php

// db.php - Merkezi PDO bağlantısı (PHP 5.6 uyumlu)

$host    = 'localhost';

$db      = 'localhost';

$user    = 'root';

$pass    = '';

$charset = 'utf8mb4';



$dsn = "mysql:host=$host;dbname=$db;charset=$charset";



$options = array(

    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,

    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

    PDO::ATTR_EMULATE_PREPARES   => false,

);



try {

    $pdo = new PDO($dsn, $user, $pass, $options);

} catch (PDOException $e) {

    $requestUri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';

    $xrw        = isset($_SERVER['HTTP_X_REQUESTED_WITH']) ? $_SERVER['HTTP_X_REQUESTED_WITH'] : '';



    $isAjax = ($xrw === 'XMLHttpRequest') || (strpos($requestUri, 'update_meal_price.php') !== false);



    if ($isAjax) {

        header('Content-Type: application/json');

        echo json_encode(array('success' => false, 'message' => 'Veritabanı bağlantı hatası'));

        exit();

    }

    die("Veritabanı bağlantı hatası: " . $e->getMessage());

}
