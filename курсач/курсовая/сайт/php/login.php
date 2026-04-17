<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $stmt = getPDO()->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
        ];

        if ($user['role'] === 'admin') {
            header('Location: admin.php');
        } else {
            header('Location: ../index.php');
        }
        exit;
    }

    $error = 'Неверный email или пароль.';
}
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход | GreenHome</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body class="auth-page">
<main class="auth-container">
    <h1>Вход в аккаунт</h1>
    <?php if ($error): ?><p class="alert error"><?= esc($error) ?></p><?php endif; ?>
    <form method="post" class="auth-form">
        <label>Email<input type="email" name="email" required></label>
        <label>Пароль<input type="password" name="password" required></label>
        <button type="submit" class="btn primary">Войти</button>
    </form>
    <p>Нет аккаунта? <a href="register.php">Регистрация</a></p>
    <p><a href="../index.php">На главную</a></p>
</main>
</body>
</html>

