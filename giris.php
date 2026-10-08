<?php
require_once __DIR__ . '/config.php';

// Zaten giriş yapılmışsa panele gönder
if (isset($_SESSION['user_id'])) {
    header('Location: tablet_dash.php');
    exit;
}

$error = '';

// ===== FORM GÖNDERİMİ (POST) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['username']) && !empty($_POST['password'])) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute(array($_POST['username']));
        $user = $stmt->fetch();

        if ($user && password_verify($_POST['password'], $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['is_admin'] = $user['is_admin'];
            header('Location: tablet_dash.php');
            exit;
        } else {
            $error = 'Kullanıcı adı veya şifre hatalı!';
        }
    } else {
        $error = 'Lütfen tüm alanları doldurun.';
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="assets/css/giris.css" rel="stylesheet" />
    <title>Giotto - Login page</title>
</head>
<body>
    <div class="login-card">
        <h1>Admin Panel Login</h1>
        <form action="giris.php" method="POST">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn-login">Login</button>
        </form>
        <?php if (!empty($error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
    </div>
</body>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const params = new URLSearchParams(window.location.search);
    const u = params.get('username');
    const p = params.get('password');
    if (u && p) {
        document.getElementById('username').value = u;
        document.getElementById('password').value = p;
        document.querySelector('form').submit();
    }
});
</script>
</html>