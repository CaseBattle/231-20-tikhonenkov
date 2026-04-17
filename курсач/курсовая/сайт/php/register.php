<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($name === '' || $phone === '' || $email === '' || $password === '') {
        $error = 'Заполните все поля.';
    } else {
        $pdo = getPDO();
        $check = $pdo->prepare('SELECT id FROM users WHERE email = :email');
        $check->execute([':email' => $email]);

        if ($check->fetch()) {
            $error = 'Пользователь с таким email уже существует.';
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO users(name, phone, email, password, role) VALUES(:name, :phone, :email, :password, :role)'
            );
            $stmt->execute([
                ':name' => $name,
                ':phone' => $phone,
                ':email' => $email,
                ':password' => password_hash($password, PASSWORD_DEFAULT),
                ':role' => 'user',
            ]);
            $success = 'Регистрация успешна. Теперь войдите в систему.';
        }
    }
}
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация | GreenHome</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body class="auth-page">
<main class="auth-container">
    <h1>Регистрация</h1>
    <?php if ($error): ?><p class="alert error"><?= esc($error) ?></p><?php endif; ?>
    <?php if ($success): ?><p class="alert success"><?= esc($success) ?></p><?php endif; ?>
    <form method="post" class="auth-form">
        <label>Имя<input type="text" name="name" required></label>
        <label>Телефон<input type="text" name="phone" required></label>
        <label>Email<input type="email" name="email" required></label>
        <label>Пароль<input type="password" name="password" required></label>
        <button type="submit" class="btn primary">Зарегистрироваться</button>
    </form>
    <p>Уже есть аккаунт? <a href="login.php">Войти</a></p>
    <p><a href="../index.php">На главную</a></p>
</main>
</body>
</html>

