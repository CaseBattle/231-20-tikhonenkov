<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
requireAdmin();

$rows = getPDO()->query("
    SELECT r.id, r.comment, r.status, r.created_at,
           u.name AS user_name, u.email AS user_email,
           p.title AS property_title
    FROM requests r
    JOIN users u ON u.id = r.user_id
    JOIN properties p ON p.id = r.property_id
    ORDER BY r.created_at DESC
")->fetchAll();
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Заявки | Админ</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
<main class="container section">
    <h1>Заявки пользователей</h1>
    <p><a href="admin.php">← Назад в админку</a></p>
    <div class="table-wrap card">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Пользователь</th>
                    <th>Объект</th>
                    <th>Комментарий</th>
                    <th>Статус</th>
                    <th>Дата</th>
                    <th>Действия</th>
                    <th>Переписка</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= (int) $r['id'] ?></td>
                    <td><?= esc($r['user_name']) ?><br><small><?= esc($r['user_email']) ?></small></td>
                    <td><?= esc($r['property_title']) ?></td>
                    <td><?= esc((string) $r['comment']) ?></td>
                    <td><span class="status <?= esc(requestStatusClass($r['status'])) ?>"><?= esc(requestStatusLabel($r['status'])) ?></span></td>
                    <td><?= esc($r['created_at']) ?></td>
                    <td>
                        <a class="btn success small" href="approve_request.php?id=<?= (int) $r['id'] ?>&status=approved">Одобрить</a>
                        <a class="btn warning small" href="approve_request.php?id=<?= (int) $r['id'] ?>&status=rejected">Отклонить</a>
                    </td>
                    <td class="request-chat-cell">
                        <section class="card chat-card compact">
                            <h3 class="chat-title">Чат с пользователем</h3>
                            <div class="chat-messages compact">
                                <div class="chat-bubble incoming compact">Добрый день, можно узнать подробнее по объекту?</div>
                                <div class="chat-bubble outgoing compact">Здравствуйте, да, квартира свободна.</div>
                                <div class="chat-bubble incoming compact">Подскажите, включены ли коммунальные услуги?</div>
                                <div class="chat-bubble outgoing compact">Частично включены, уточнение при просмотре.</div>
                            </div>
                            <form class="chat-form js-demo-chat" data-alert-message="Функция чата будет доступна в следующей версии">
                                <input type="text" name="chat_message_admin_<?= (int) $r['id'] ?>" placeholder="Введите сообщение..." required>
                                <button type="submit" class="btn primary">Отправить</button>
                            </form>
                        </section>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>
</body>
</html>

