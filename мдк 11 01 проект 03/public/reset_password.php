<?php

require_once __DIR__ . '/../includes/bootstrap.php';

$flash = get_flash('reset');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = sanitize_phone($_POST['phone'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm = trim($_POST['confirm_password'] ?? '');

    if ($phone === '' || $password === '' || $confirm === '') {
        set_flash('reset', 'Все поля обязательны для заполнения.');
        redirect('reset_password.php');
    }

    if ($password !== $confirm) {
        set_flash('reset', 'Пароли должны совпадать.');
        redirect('reset_password.php');
    }

    if (strlen($password) < 6) {
        set_flash('reset', 'Новый пароль должен содержать минимум 6 символов.');
        redirect('reset_password.php');
    }

    $stmt = get_db()->prepare('SELECT id FROM users WHERE phone = :phone');
    $stmt->execute([':phone' => $phone]);
    $user = $stmt->fetch();

    if (!$user) {
        set_flash('reset', 'Аккаунт с указанным номером не найден.');
        redirect('reset_password.php');
    }

    $update = get_db()->prepare(
        'UPDATE users SET password = :password, updated_at = CURRENT_TIMESTAMP WHERE id = :id'
    );
    $update->execute([
        ':password' => password_hash($password, PASSWORD_DEFAULT),
        ':id' => $user['id'],
    ]);

    set_flash('auth', 'Пароль обновлён. Выполните вход.', 'success');
    redirect('index.php');
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Сброс пароля</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <div class="container">
        <div class="brand">
            <h1>Восстановление пароля</h1>
            <p>Укажите номер телефона и новый пароль</p>
        </div>
        <?= render_flash($flash); ?>
        <form method="post" action="reset_password.php">
            <div>
                <label for="phone">Номер телефона</label>
                <input type="tel" id="phone" name="phone" placeholder="+7 999 000 00 00" required>
            </div>
            <div>
                <label for="password">Новый пароль</label>
                <input type="password" id="password" name="password" minlength="6" required>
            </div>
            <div>
                <label for="confirm_password">Подтверждение пароля</label>
                <input type="password" id="confirm_password" name="confirm_password" minlength="6" required>
            </div>
            <p class="reset-note">Пароль можно сбросить только для уже зарегистрированного телефона.</p>
            <button type="submit">Обновить пароль</button>
        </form>
        <div class="links">
            <a href="index.php">Вернуться к входу</a>
        </div>
    </div>
</body>
</html>

