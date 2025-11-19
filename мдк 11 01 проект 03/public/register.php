<?php

require_once __DIR__ . '/../includes/bootstrap.php';

if (is_logged_in()) {
    redirect('home.php');
}

$flash = get_flash('register');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $phone = sanitize_phone($_POST['phone'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm = trim($_POST['confirm_password'] ?? '');

    if ($firstName === '' || $lastName === '' || $phone === '' || $password === '' || $confirm === '') {
        set_flash('register', 'Заполните все поля.');
        redirect('register.php');
    }

    if ($password !== $confirm) {
        set_flash('register', 'Пароли не совпадают.');
        redirect('register.php');
    }

    if (strlen($password) < 6) {
        set_flash('register', 'Пароль должен содержать минимум 6 символов.');
        redirect('register.php');
    }

    try {
        $stmt = get_db()->prepare(
            'INSERT INTO users (phone, password, first_name, last_name) VALUES (:phone, :password, :first_name, :last_name)'
        );
        $stmt->execute([
            ':phone' => $phone,
            ':password' => password_hash($password, PASSWORD_DEFAULT),
            ':first_name' => $firstName,
            ':last_name' => $lastName,
        ]);

        set_flash('auth', 'Регистрация успешно завершена. Выполните вход.', 'success');
        redirect('index.php');
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            set_flash('register', 'Пользователь с таким номером уже существует.');
        } else {
            set_flash('register', 'Не удалось создать аккаунт. Попробуйте позже.');
        }
        redirect('register.php');
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Регистрация | Портфолио</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <div class="container">
        <div class="brand">
            <h1>Регистрация</h1>
            <p>Создайте доступ к личному портфолио</p>
        </div>
        <?= render_flash($flash); ?>
        <form method="post" action="register.php">
            <div>
                <label for="first_name">Имя</label>
                <input type="text" id="first_name" name="first_name" placeholder="Иван" required>
            </div>
            <div>
                <label for="last_name">Фамилия</label>
                <input type="text" id="last_name" name="last_name" placeholder="Иванов" required>
            </div>
            <div>
                <label for="phone">Номер телефона</label>
                <input type="tel" id="phone" name="phone" placeholder="+7 999 000 00 00" required>
            </div>
            <div>
                <label for="password">Пароль</label>
                <input type="password" id="password" name="password" minlength="6" required>
            </div>
            <div>
                <label for="confirm_password">Повторите пароль</label>
                <input type="password" id="confirm_password" name="confirm_password" minlength="6" required>
            </div>
            <button type="submit">Создать аккаунт</button>
        </form>
        <div class="links">
            <a href="index.php">Уже есть аккаунт? Войти</a>
        </div>
    </div>
</body>
</html>

