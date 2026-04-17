<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
requireAuth();

$pdo = getPDO();
$userId = (int) $_SESSION['user']['id'];
$favoriteCount = getFavoriteCount($userId);

$stmt = $pdo->prepare("
    SELECT r.id, r.comment, r.status, r.created_at, p.title, p.address, p.price, p.type
    FROM requests r
    JOIN properties p ON p.id = r.property_id
    WHERE r.user_id = :user_id
    ORDER BY r.created_at DESC
");
$stmt->execute([':user_id' => $userId]);
$requests = $stmt->fetchAll();
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет | GreenHome</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
<header class="topbar">
    <div class="container nav">
        <a href="../index.php" class="logo">GreenHome</a>
        <nav>
            <a href="../index.php">Главная</a>
            <a href="../favorites.php" class="favorites-link">Избранное (<span class="js-favorites-count"><?= $favoriteCount ?></span>)</a>
            <a href="logout.php">Выйти</a>
        </nav>
    </div>
</header>

<main class="container section">
    <h1>Личный кабинет</h1>
    <div class="card profile-box">
        <img src="../images/profile.svg" alt="Профиль" class="profile-icon">
        <div>
            <p><strong>Имя:</strong> <?= esc($_SESSION['user']['name']) ?></p>
            <p><strong>Email:</strong> <?= esc($_SESSION['user']['email']) ?></p>
        </div>
    </div>

    <h2>Мои заявки</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Объект</th>
                    <th>Тип</th>
                    <th>Цена</th>
                    <th>Комментарий</th>
                    <th>Статус</th>
                    <th>Дата</th>
                    <th>Действие</th>
                    <th>Переписка</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$requests): ?>
                <tr><td colspan="8">Заявок пока нет.</td></tr>
            <?php else: ?>
                <?php foreach ($requests as $item): ?>
                    <tr>
                        <td><?= esc($item['title']) ?><br><small><?= esc($item['address']) ?></small></td>
                        <td><?= $item['type'] === 'rent' ? 'Аренда' : 'Продажа' ?></td>
                        <td><?= number_format((float) $item['price'], 0, ',', ' ') ?> ₽</td>
                        <td>
                            <?= esc((string) ($item['comment'] ?? '')) ?>
                            <br>
                            <small class="muted">
                                <?= $item['type'] === 'rent'
                                    ? 'Тип обращения: бронирование / аренда'
                                    : 'Тип обращения: консультация / покупка' ?>
                            </small>
                        </td>
                        <td><span class="status <?= esc(requestStatusClass($item['status'])) ?>"><?= esc(requestStatusLabel($item['status'])) ?></span></td>
                        <td><?= esc($item['created_at']) ?></td>
                        <td>
                            <?php if ($item['status'] === 'pending'): ?>
                                <a class="btn danger small" href="delete_request.php?id=<?= (int) $item['id'] ?>">Отменить</a>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td class="request-chat-cell">
                            <section class="card chat-card compact">
                                <h3 class="chat-title">Чат по заявке</h3>
                                <div class="chat-messages compact">
                                    <?php if ($item['type'] === 'rent'): ?>
                                        <div class="chat-bubble incoming compact">Здравствуйте, можно ли забронировать квартиру на следующую неделю?</div>
                                        <div class="chat-bubble outgoing compact">Добрый день, да, объект пока доступен.</div>
                                        <div class="chat-bubble incoming compact">Подскажите, можно ли внести бронь онлайн?</div>
                                        <div class="chat-bubble outgoing compact">Да, такая функция будет доступна после полной интеграции сервиса.</div>
                                    <?php else: ?>
                                        <div class="chat-bubble incoming compact">Здравствуйте, объект ещё актуален?</div>
                                        <div class="chat-bubble outgoing compact">Добрый день, да, квартира ещё в продаже.</div>
                                        <div class="chat-bubble incoming compact">Можно ли договориться о просмотре?</div>
                                        <div class="chat-bubble outgoing compact">Да, просмотр возможен по согласованию.</div>
                                    <?php endif; ?>
                                </div>
                                <form class="chat-form js-demo-chat" data-alert-message="Демо-режим: сообщение не отправлено">
                                    <input type="text" name="chat_message_user_<?= (int) $item['id'] ?>" placeholder="Введите сообщение..." required>
                                    <button type="submit" class="btn primary">Отправить</button>
                                </form>
                            </section>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>
</body>
</html>

