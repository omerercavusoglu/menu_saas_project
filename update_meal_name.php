<?php
require_once __DIR__ . '/db.php';
$id = intval($_POST['id']);
$lang = $_POST['lang'];
$value = trim($_POST['value']);

$allowed = ['ru','uz','en'];
if (!in_array($lang, $allowed)) exit;
$stmt = $pdo->prepare("UPDATE menu_items SET `$lang` = ? WHERE id = ?");
$stmt->execute([$value, $id]);
echo 'OK';
?>
