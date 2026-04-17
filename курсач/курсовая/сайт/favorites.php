<?php
declare(strict_types=1);
require_once __DIR__ . '/php/config.php';
requireAuth();

function paymentLabel(string $paymentType): string
{
    return match ($paymentType) {
        'cash' => 'Наличными',
        'online' => 'Онлайн',
        default => 'Наличными и онлайн',
    };
}

$userId = (int) $_SESSION['user']['id'];
$favoriteCount = getFavoriteCount($userId);
$favoriteIds = getFavoritePropertyIds($userId);

$stmt = getPDO()->prepare("
    SELECT p.*
    FROM favorites f
    JOIN properties p ON p.id = f.property_id
    WHERE f.user_id = :user_id
    ORDER BY f.created_at DESC
");
$stmt->execute([':user_id' => $userId]);
$properties = $stmt->fetchAll();
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Избранное | GreenHome</title>
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
            <a href="index.php">Главная</a>
            <a href="favorites.php" class="favorites-link">Избранное (<span class="js-favorites-count"><?= $favoriteCount ?></span>)</a>
            <a href="php/profile.php">Профиль</a>
            <a href="php/logout.php">Выйти</a>
        </nav>
    </div>
</header>

<main class="container section">
    <h1>Избранные объявления</h1>

    <?php if (!$properties): ?>
        <section class="card">
            <p>У вас пока нет избранных объявлений.</p>
            <a class="btn" href="index.php#properties">Перейти к объявлениям</a>
        </section>
    <?php else: ?>
        <section class="property-grid">
            <?php foreach ($properties as $property): ?>
                <article class="card property-card js-property-card is-visible">
                    <img src="<?= esc($property['image']) ?>" alt="<?= esc($property['title']) ?>" class="property-image">
                    <div class="property-content">
                        <button
                            class="favorite-btn is-active js-favorite-btn"
                            data-property-id="<?= (int) $property['id'] ?>"
                            data-auth="1"
                            aria-label="Убрать из избранного"
                            type="button"
                        >❤</button>
                        <h3><?= esc($property['title']) ?></h3>
                        <p class="muted"><?= esc($property['address']) ?></p>
                        <p class="muted"><?= esc((string) ($property['district'] ?? '')) ?> район</p>
                        <p><strong><?= number_format((float) $property['price'], 0, ',', ' ') ?> ₽</strong></p>
                        <p class="badge"><?= $property['type'] === 'rent' ? 'Аренда' : 'Продажа' ?></p>
                        <div class="property-short-info">
                            <span><?= (int) ($property['rooms'] ?? 0) ?> комн.</span>
                            <span><?= esc((string) ($property['area'] ?? '')) ?></span>
                            <span><?= esc((string) ($property['floor'] ?? '')) ?> этаж</span>
                        </div>
                        <p class="muted">Оплата: <strong><?= esc(paymentLabel((string) ($property['payment_type'] ?? 'both'))) ?></strong></p>
                        <p class="muted">Статус: <span class="status <?= esc($property['status']) ?>"><?= esc($property['status']) ?></span></p>
                        <p class="property-actions">
                            <a class="btn" href="property.php?id=<?= (int) $property['id'] ?>">Подробнее</a>
                        </p>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
</main>
<script src="js/script.js"></script>
</body>
</html>

