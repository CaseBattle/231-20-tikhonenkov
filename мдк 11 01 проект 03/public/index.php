<?php

require_once __DIR__ . '/../includes/bootstrap.php';

if (is_logged_in()) {
    redirect('home.php');
}

$flash = get_flash('auth');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = sanitize_phone($_POST['phone'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($phone === '' || $password === '') {
        set_flash('auth', 'Введите номер телефона и пароль.');
        redirect('index.php');
    }

    $stmt = get_db()->prepare('SELECT id, phone, password, first_name, last_name FROM users WHERE phone = :phone');
    $stmt->execute([':phone' => $phone]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = [
            'id' => $user['id'],
            'phone' => $user['phone'],
            'first_name' => $user['first_name'] ?? '',
            'last_name' => $user['last_name'] ?? '',
        ];
        redirect('home.php');
    }

    set_flash('auth', 'Неверный номер телефона или пароль.');
    redirect('index.php');
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Вход | Портфолио</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <div class="container">
        <div class="brand">
            <h1>Панель входа</h1>
            <p>Авторизуйтесь для доступа к портфолио</p>
        </div>
        <?= render_flash($flash); ?>
        <form method="post" action="index.php">
            <div>
                <label for="phone">Номер телефона</label>
                <input type="tel" id="phone" name="phone" placeholder="+7 999 000 00 00" required>
            </div>
            <div>
                <label for="password">Пароль</label>
                <input type="password" id="password" name="password" placeholder="••••••••" required>
            </div>
            <button type="submit">Войти</button>
        </form>
        <div class="links">
            <a href="register.php">Создать учетную запись</a>
            <a href="reset_password.php">Забыли пароль?</a>
        </div>
    </div>
</body>
</html>

