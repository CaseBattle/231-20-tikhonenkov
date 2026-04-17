<?php
declare(strict_types=1);
require_once __DIR__ . '/php/config.php';

function paymentLabel(string $paymentType): string
{
    return match ($paymentType) {
        'cash' => 'Наличными',
        'online' => 'Онлайн',
        default => 'Наличными и онлайн',
    };
}

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: index.php');
    exit;
}

$stmt = getPDO()->prepare('SELECT * FROM properties WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $id]);
$property = $stmt->fetch();

if (!$property) {
    http_response_code(404);
}

$isRent = $property && ($property['type'] ?? '') === 'rent';
$isOnlineBookingAvailable = $property && in_array(($property['payment_type'] ?? 'both'), ['online', 'both'], true);
$isAuth = isLoggedIn();
$favoriteCount = $isAuth ? getFavoriteCount((int) $_SESSION['user']['id']) : 0;
$favoriteIds = $isAuth ? getFavoritePropertyIds((int) $_SESSION['user']['id']) : [];
$isFavorite = $property ? in_array((int) $property['id'], $favoriteIds, true) : false;
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $property ? esc($property['title']) : 'Объект не найден' ?> | GreenHome</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
<header class="topbar">
    <div class="container nav">
        <a href="index.php" class="logo-wrap">
            <img src="images/logo.svg" alt="GreenHome" class="logo-img">
            <span class="logo">GreenHome</span>
        </a>
        <nav>
            <a href="index.php#properties">Каталог</a>
            <?php if (isLoggedIn()): ?>
                <?php if (isAdmin()): ?>
                    <a href="php/admin.php">Админка</a>
                <?php else: ?>
                    <a href="favorites.php" class="favorites-link">Избранное (<span class="js-favorites-count"><?= $favoriteCount ?></span>)</a>
                    <a href="php/profile.php">Профиль</a>
                <?php endif; ?>
                <a href="php/logout.php">Выйти</a>
            <?php else: ?>
                <a href="favorites.php" class="favorites-link">Избранное (<span class="js-favorites-count">0</span>)</a>
                <a href="php/login.php">Вход</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main class="container section">
    <?php if (!$property): ?>
        <section class="card">
            <h1>Объект не найден</h1>
            <p>Запрошенное объявление отсутствует или было удалено.</p>
            <a href="index.php" class="btn">Назад на главную</a>
        </section>
    <?php else: ?>
        <a href="index.php#properties" class="btn">← Назад в каталог</a>

        <section class="property-layout section">
            <article class="card property-main-card">
                <img src="<?= esc($property['image']) ?>" alt="<?= esc($property['title']) ?>" class="property-detail-image">
                <div class="property-detail-content">
                    <button
                        class="favorite-btn property-favorite-btn <?= $isFavorite ? 'is-active' : '' ?> js-favorite-btn"
                        data-property-id="<?= (int) $property['id'] ?>"
                        data-auth="<?= $isAuth ? '1' : '0' ?>"
                        aria-label="Избранное"
                        type="button"
                    >
                        <?= $isFavorite ? '❤' : '♡' ?>
                    </button>
                    <h1><?= esc($property['title']) ?></h1>
                    <p class="muted"><?= esc($property['address']) ?></p>
                    <p>
                        <span class="badge"><?= $property['type'] === 'rent' ? 'Аренда' : 'Продажа' ?></span>
                        <span class="status <?= esc($property['status']) ?>"><?= esc($property['status']) ?></span>
                    </p>
                    <p class="detail-price"><?= number_format((float) $property['price'], 0, ',', ' ') ?> ₽</p>
                    <p><strong>Способ оплаты:</strong> <?= esc(paymentLabel((string) ($property['payment_type'] ?? 'both'))) ?></p>
                    <?php if (($property['payment_type'] ?? 'both') === 'cash'): ?>
                        <p class="muted">Для данного объекта доступна оплата наличными.</p>
                    <?php else: ?>
                        <p class="payment-badge">Доступна онлайн-оплата</p>
                    <?php endif; ?>

                    <div class="property-short-info">
                        <span><?= (int) ($property['rooms'] ?? 0) ?> комнаты</span>
                        <span><?= esc((string) ($property['area'] ?? '—')) ?></span>
                        <span><?= esc((string) ($property['floor'] ?? '—')) ?> этаж</span>
                        <span><?= esc((string) ($property['district'] ?? '—')) ?> район</span>
                    </div>

                    <?php if (isLoggedIn() && !isAdmin()): ?>
                        <form method="post" action="php/add_request.php" class="request-form section">
                            <input type="hidden" name="property_id" value="<?= (int) $property['id'] ?>">
                            <textarea name="comment" placeholder="Комментарий к заявке (необязательно)"></textarea>
                            <button type="submit" class="btn primary">Подать заявку</button>
                        </form>
                    <?php elseif (!isLoggedIn()): ?>
                        <p><a href="php/login.php">Войдите</a>, чтобы подать заявку.</p>
                    <?php endif; ?>
                    <div class="property-actions section">
                        <?php if ($isRent): ?>
                            <button
                                type="button"
                                class="btn warning js-demo-action"
                                data-alert-message="Демо-режим: онлайн-бронирование пока недоступно"
                            >
                                Забронировать онлайн
                            </button>
                            <?php if ($isOnlineBookingAvailable): ?>
                                <span class="payment-badge">Доступна онлайн-бронь</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <a href="#property-chat" class="btn">Связаться с менеджером</a>
                        <?php endif; ?>
                    </div>
                </div>
            </article>

            <aside class="card property-side-card">
                <h3>Краткая информация</h3>
                <ul class="plain-list">
                    <li><strong>Тип:</strong> <?= $property['type'] === 'rent' ? 'Аренда' : 'Продажа' ?></li>
                    <li><strong>Район:</strong> <?= esc((string) ($property['district'] ?? '—')) ?></li>
                    <li><strong>Комнаты:</strong> <?= (int) ($property['rooms'] ?? 0) ?></li>
                    <li><strong>Площадь:</strong> <?= esc((string) ($property['area'] ?? '—')) ?></li>
                    <li><strong>Этаж:</strong> <?= esc((string) ($property['floor'] ?? '—')) ?></li>
                    <li><strong>Оплата:</strong> <?= esc(paymentLabel((string) ($property['payment_type'] ?? 'both'))) ?></li>
                    <li><strong>Статус:</strong> <?= esc($property['status']) ?></li>
                </ul>
                <h3>Связь по объекту</h3>
                <p><strong>Телефон:</strong> <a href="tel:<?= esc($property['phone']) ?>"><?= esc($property['phone']) ?></a></p>
            </aside>
        </section>

        <section class="card section">
            <h2>Описание</h2>
            <p><?= nl2br(esc((string) ($property['description'] ?? 'Описание пока не добавлено.'))) ?></p>
        </section>

        <section class="card section chat-card" id="property-chat">
            <h2><?= $isRent ? 'Чат по бронированию' : 'Чат с менеджером' ?></h2>
            <div class="chat-messages">
                <?php if ($isRent): ?>
                    <div class="chat-bubble incoming">Здравствуйте, можно ли забронировать квартиру на следующую неделю?</div>
                    <div class="chat-bubble outgoing">Добрый день, да, объект пока доступен.</div>
                    <div class="chat-bubble incoming">Подскажите, можно ли внести бронь онлайн?</div>
                    <div class="chat-bubble outgoing">Да, такая функция будет доступна после полной интеграции сервиса.</div>
                <?php else: ?>
                    <div class="chat-bubble incoming">Здравствуйте, объект ещё актуален?</div>
                    <div class="chat-bubble outgoing">Добрый день, да, квартира ещё в продаже.</div>
                    <div class="chat-bubble incoming">Можно ли договориться о просмотре?</div>
                    <div class="chat-bubble outgoing">Да, просмотр возможен по согласованию.</div>
                <?php endif; ?>
            </div>
            <form class="chat-form js-demo-chat" data-alert-message="Демо-режим: сообщение не отправлено">
                <input type="text" name="chat_message" placeholder="Введите сообщение..." required>
                <button type="submit" class="btn primary">Отправить</button>
            </form>
            <p class="muted chat-note">Демо-режим: переписка пока не сохраняется.</p>
        </section>
    <?php endif; ?>
</main>

<footer class="footer">
    <div class="container">
        <p>© <?= date('Y') ?> GreenHome. Сервис аренды и продажи недвижимости.</p>
    </div>
</footer>
<script src="js/script.js"></script>
</body>
</html>

