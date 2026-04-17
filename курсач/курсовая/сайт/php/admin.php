<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
requireAdmin();
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель | GreenHome</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
<header class="topbar">
    <div class="container nav">
        <a href="../index.php" class="logo">GreenHome</a>
        <nav>
            <a href="../index.php">Сайт</a>
            <a href="logout.php">Выйти</a>
        </nav>
    </div>
</header>

<main class="container section">
    <h1>Админ-панель</h1>
    <div class="dashboard-grid">
        <a href="admin_properties.php" class="card dashboard-card">
            <h3>Управление объявлениями</h3>
            <p>Добавление и удаление объектов недвижимости.</p>
        </a>
        <a href="admin_requests.php" class="card dashboard-card">
            <h3>Заявки пользователей</h3>
            <p>Одобрение или отклонение заявок.</p>
        </a>
        <a href="admin_feedback.php" class="card dashboard-card">
            <h3>Обратная связь</h3>
            <p>Просмотр сообщений с главной страницы.</p>
        </a>
    </div>
</main>
</body>
</html>

