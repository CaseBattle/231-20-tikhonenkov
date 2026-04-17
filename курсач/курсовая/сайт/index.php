<?php
declare(strict_types=1);
require_once __DIR__ . '/php/config.php';

$pdo = getPDO();
$isAuth = isLoggedIn();
$favoriteCount = $isAuth ? getFavoriteCount((int) $_SESSION['user']['id']) : 0;
$favoriteIds = $isAuth ? getFavoritePropertyIds((int) $_SESSION['user']['id']) : [];

function paymentLabel(string $paymentType): string
{
    return match ($paymentType) {
        'cash' => 'Наличными',
        'online' => 'Онлайн',
        default => 'Наличными и онлайн',
    };
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['feedback_form'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($name !== '' && $email !== '' && $message !== '') {
        $stmt = $pdo->prepare('INSERT INTO feedback(name, email, message) VALUES(:name, :email, :message)');
        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':message' => $message,
        ]);
        $_SESSION['flash_success'] = 'Спасибо! Сообщение отправлено.';
        header('Location: index.php#feedback');
        exit;
    }
    $_SESSION['flash_error'] = 'Заполните все поля формы обратной связи.';
    header('Location: index.php#feedback');
    exit;
}

$titleSearch = trim($_GET['title'] ?? '');
$addressSearch = trim($_GET['address'] ?? '');
$district = trim($_GET['district'] ?? '');
$type = trim($_GET['type'] ?? '');
$minPrice = trim($_GET['min_price'] ?? '');
$maxPrice = trim($_GET['max_price'] ?? '');
$rooms = trim($_GET['rooms'] ?? '');
$area = trim($_GET['area'] ?? '');
$floor = trim($_GET['floor'] ?? '');
$paymentType = trim($_GET['payment_type'] ?? '');
$status = trim($_GET['status'] ?? '');

$sql = 'SELECT * FROM properties WHERE 1=1';
$params = [];

if ($titleSearch !== '') {
    $sql .= ' AND title LIKE :title';
    $params[':title'] = '%' . $titleSearch . '%';
}
if ($addressSearch !== '') {
    $sql .= ' AND address LIKE :address';
    $params[':address'] = '%' . $addressSearch . '%';
}
if ($district !== '') {
    $sql .= ' AND district LIKE :district';
    $params[':district'] = '%' . $district . '%';
}
if (in_array($type, ['rent', 'sale'], true)) {
    $sql .= ' AND type = :type';
    $params[':type'] = $type;
}
if (is_numeric($minPrice)) {
    $sql .= ' AND price >= :min_price';
    $params[':min_price'] = (float) $minPrice;
}
if (is_numeric($maxPrice)) {
    $sql .= ' AND price <= :max_price';
    $params[':max_price'] = (float) $maxPrice;
}
if ($rooms !== '' && ctype_digit($rooms)) {
    $sql .= ' AND rooms = :rooms';
    $params[':rooms'] = (int) $rooms;
}
if ($area !== '') {
    $sql .= ' AND area LIKE :area';
    $params[':area'] = '%' . $area . '%';
}
if ($floor !== '') {
    $sql .= ' AND floor LIKE :floor';
    $params[':floor'] = '%' . $floor . '%';
}
if (in_array($paymentType, ['cash', 'online', 'both'], true)) {
    $sql .= ' AND payment_type = :payment_type';
    $params[':payment_type'] = $paymentType;
}
if (in_array($status, ['available', 'reserved', 'rented', 'sold'], true)) {
    $sql .= ' AND status = :status';
    $params[':status'] = $status;
}

$sql .= ' ORDER BY id DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$properties = $stmt->fetchAll();

$flashSuccess = $_SESSION['flash_success'] ?? '';
$flashError = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_success'], $_SESSION['flash_error']);
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GreenHome — Аренда и продажа недвижимости</title>
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
            <a href="#properties">Объявления</a>
            <a href="#feedback">Обратная связь</a>
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
                <a href="php/register.php">Регистрация</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<section class="hero">
    <div class="container">
        <h1>Аренда и покупка недвижимости легко и безопасно</h1>
        <p>Тысячи актуальных объявлений по всей России. Найдите квартиру, дом или апартаменты своей мечты.</p>
        <a href="#properties" class="btn primary">Смотреть объявления</a>
    </div>
</section>

<main class="container section">
    <?php if ($flashSuccess): ?><p class="alert success"><?= esc($flashSuccess) ?></p><?php endif; ?>
    <?php if ($flashError): ?><p class="alert error"><?= esc($flashError) ?></p><?php endif; ?>

    <section class="card search-box">
        <h2>Фильтр объявлений</h2>
        <form method="get" class="filter-form">
            <input type="text" name="title" placeholder="Поиск по названию" value="<?= esc($titleSearch) ?>">
            <input type="text" name="address" placeholder="Поиск по адресу" value="<?= esc($addressSearch) ?>">
            <input type="text" name="district" placeholder="Район" value="<?= esc($district) ?>">
            <select name="type">
                <option value="">Тип сделки</option>
                <option value="rent" <?= $type === 'rent' ? 'selected' : '' ?>>Аренда</option>
                <option value="sale" <?= $type === 'sale' ? 'selected' : '' ?>>Продажа</option>
            </select>
            <input type="number" name="min_price" placeholder="Мин. цена" value="<?= esc($minPrice) ?>">
            <input type="number" name="max_price" placeholder="Макс. цена" value="<?= esc($maxPrice) ?>">
            <input type="number" name="rooms" placeholder="Комнаты" min="1" value="<?= esc($rooms) ?>">
            <input type="text" name="area" placeholder="Площадь (например, 54)" value="<?= esc($area) ?>">
            <input type="text" name="floor" placeholder="Этаж (например, 5/9)" value="<?= esc($floor) ?>">
            <select name="payment_type">
                <option value="">Способ оплаты</option>
                <option value="cash" <?= $paymentType === 'cash' ? 'selected' : '' ?>>Наличные</option>
                <option value="online" <?= $paymentType === 'online' ? 'selected' : '' ?>>Онлайн</option>
                <option value="both" <?= $paymentType === 'both' ? 'selected' : '' ?>>Наличные и онлайн</option>
            </select>
            <select name="status">
                <option value="">Статус объекта</option>
                <option value="available" <?= $status === 'available' ? 'selected' : '' ?>>available</option>
                <option value="reserved" <?= $status === 'reserved' ? 'selected' : '' ?>>reserved</option>
                <option value="rented" <?= $status === 'rented' ? 'selected' : '' ?>>rented</option>
                <option value="sold" <?= $status === 'sold' ? 'selected' : '' ?>>sold</option>
            </select>
            <div class="filter-actions">
                <button type="submit" class="btn primary">Применить</button>
                <a href="index.php#properties" class="btn">Сбросить</a>
            </div>
        </form>
    </section>

    <section id="properties" class="section">
        <h2>Список объявлений</h2>
        <div class="property-grid js-properties-grid" data-batch-size="8">
            <?php if (!$properties): ?>
                <p>По вашему запросу ничего не найдено.</p>
            <?php endif; ?>
            <?php foreach ($properties as $property): ?>
                <article class="card property-card js-property-card">
                    <img src="<?= esc($property['image']) ?>" alt="<?= esc($property['title']) ?>" class="property-image">
                    <div class="property-content">
                        <button
                            class="favorite-btn <?= in_array((int) $property['id'], $favoriteIds, true) ? 'is-active' : '' ?> js-favorite-btn"
                            data-property-id="<?= (int) $property['id'] ?>"
                            data-auth="<?= $isAuth ? '1' : '0' ?>"
                            aria-label="Избранное"
                            type="button"
                        >
                            <?= in_array((int) $property['id'], $favoriteIds, true) ? '❤' : '♡' ?>
                        </button>
                        <h3><?= esc($property['title']) ?></h3>
                        <p class="muted"><?= esc($property['address']) ?></p>
                        <p class="muted"><?= esc((string) ($property['district'] ?? '')) ?> район</p>
                        <p><strong><?= number_format((float) $property['price'], 0, ',', ' ') ?> ₽</strong></p>
                        <p class="badge"><?= $property['type'] === 'rent' ? 'Аренда' : 'Продажа' ?></p>
                        <div class="property-short-info">
                            <span><?= (int) ($property['rooms'] ?? 0) ?> комн.</span>
                            <span><?= esc((string) ($property['area'] ?? '')) ?></span>
                            <span><?= esc((string) ($property['floor'] ?? '')) ?> этаж</span>
                            <span><?= esc((string) ($property['district'] ?? '')) ?> район</span>
                        </div>
                        <p class="muted">
                            <?= $property['type'] === 'rent'
                                ? 'Можно забронировать'
                                : 'Консультация менеджера' ?>
                        </p>
                        <p class="muted">Оплата: <strong><?= esc(paymentLabel((string) ($property['payment_type'] ?? 'both'))) ?></strong></p>
                        <p class="muted">Статус: <span class="status <?= esc($property['status']) ?>"><?= esc($property['status']) ?></span></p>
                        <p class="property-actions">
                            <a class="btn" href="property.php?id=<?= (int) $property['id'] ?>">Подробнее</a>
                        </p>

                        <?php if (isLoggedIn() && !isAdmin()): ?>
                            <form method="post" action="php/add_request.php" class="request-form">
                                <input type="hidden" name="property_id" value="<?= (int) $property['id'] ?>">
                                <textarea name="comment" placeholder="Комментарий к заявке (необязательно)"></textarea>
                                <button type="submit" class="btn primary">Подать заявку</button>
                            </form>
                        <?php elseif (!isLoggedIn()): ?>
                            <p><a href="php/login.php">Войдите</a>, чтобы подать заявку.</p>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <?php if (count($properties) > 8): ?>
            <div class="load-more-wrap">
                <button class="btn primary js-load-more" type="button">Показать ещё</button>
            </div>
        <?php endif; ?>
    </section>

    <section id="feedback" class="section card">
        <h2>Форма обратной связи</h2>
        <form method="post" class="feedback-form">
            <input type="hidden" name="feedback_form" value="1">
            <input type="text" name="name" placeholder="Ваше имя" required>
            <input type="email" name="email" placeholder="Email" required>
            <textarea name="message" placeholder="Ваше сообщение" required></textarea>
            <button type="submit" class="btn primary">Отправить</button>
        </form>
    </section>
</main>

<footer class="footer">
    <div class="container">
        <p>© <?= date('Y') ?> GreenHome. Сервис аренды и продажи недвижимости.</p>
    </div>
</footer>
<script src="js/script.js"></script>
</body>
</html>

