<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
requireAdmin();

$rows = getPDO()->query('SELECT * FROM feedback ORDER BY created_at DESC')->fetchAll();
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Обратная связь | Админ</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
<main class="container section">
    <h1>Обратная связь</h1>
    <p><a href="admin.php">← Назад в админку</a></p>
    <div class="table-wrap card">
        <table>
            <thead>
                <tr>
                    <th>Имя</th>
                    <th>Email</th>
                    <th>Сообщение</th>
                    <th>Дата</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="4">Сообщений пока нет.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $item): ?>
                    <tr>
                        <td><?= esc($item['name']) ?></td>
                        <td><?= esc($item['email']) ?></td>
                        <td><?= esc($item['message']) ?></td>
                        <td><?= esc($item['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>
</body>
</html>

