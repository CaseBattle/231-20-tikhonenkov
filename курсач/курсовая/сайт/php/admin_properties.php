<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
requireAdmin();

$rows = getPDO()->query('SELECT * FROM properties ORDER BY id DESC')->fetchAll();
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Объявления | Админ</title>
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
<main class="container section">
    <h1>Управление объявлениями</h1>
    <p><a href="admin.php">← Назад в админку</a></p>

    <section class="card">
        <h2>Добавить объект</h2>
        <form method="post" action="add_property.php" class="grid-form">
            <input type="text" name="title" placeholder="Название объекта" required>
            <input type="text" name="address" placeholder="Адрес" required>
            <input type="text" name="district" placeholder="Район" required>
            <input type="number" name="price" placeholder="Цена" required>
            <select name="type" required>
                <option value="rent">Аренда</option>
                <option value="sale">Продажа</option>
            </select>
            <p class="muted form-hint">Аренда → заявка и онлайн-бронирование. Продажа → заявка и связь с менеджером.</p>
            <input type="number" name="rooms" placeholder="Количество комнат" min="1" required>
            <input type="text" name="area" placeholder="Площадь (например, 54 м²)" required>
            <input type="text" name="floor" placeholder="Этаж (например, 5/9)" required>
            <input type="text" name="phone" placeholder="Телефон для связи" required>
            <select name="payment_type" required>
                <option value="cash">Наличными</option>
                <option value="online">Онлайн</option>
                <option value="both">Наличными и онлайн</option>
            </select>
            <p class="muted form-hint">Для аренды влияет на онлайн-бронирование, для продажи показывается как способ расчета.</p>
            <textarea name="description" placeholder="Описание объекта"></textarea>
            <input type="text" name="image" placeholder="Фото: путь к изображению (опционально)">
            <select name="status" required>
                <option value="available">available</option>
                <option value="reserved">reserved</option>
                <option value="rented">rented</option>
                <option value="sold">sold</option>
            </select>
            <button class="btn primary" type="submit">Добавить</button>
        </form>
    </section>

    <section class="card">
        <h2>Список объектов</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Название</th>
                        <th>Район</th>
                        <th>Тип</th>
                        <th>Цена</th>
                        <th>Оплата</th>
                        <th>Комнаты</th>
                        <th>Площадь</th>
                        <th>Статус</th>
                        <th>Сценарий для пользователя</th>
                        <th>Действие</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $item): ?>
                    <tr>
                        <td><?= (int) $item['id'] ?></td>
                        <td><?= esc($item['title']) ?></td>
                        <td><?= esc((string) ($item['district'] ?? '')) ?></td>
                        <td><?= $item['type'] === 'rent' ? 'Аренда' : 'Продажа' ?></td>
                        <td><?= number_format((float) $item['price'], 0, ',', ' ') ?> ₽</td>
                        <td>
                            <?php
                                $paymentLabel = 'Наличными и онлайн';
                                if (($item['payment_type'] ?? '') === 'cash') {
                                    $paymentLabel = 'Наличными';
                                } elseif (($item['payment_type'] ?? '') === 'online') {
                                    $paymentLabel = 'Онлайн';
                                }
                                echo esc($paymentLabel);
                            ?>
                        </td>
                        <td><?= (int) ($item['rooms'] ?? 0) ?></td>
                        <td><?= esc((string) ($item['area'] ?? '')) ?></td>
                        <td><span class="status <?= esc($item['status']) ?>"><?= esc($item['status']) ?></span></td>
                        <td><?= $item['type'] === 'rent' ? 'Заявка / Бронирование' : 'Заявка / Связь с менеджером' ?></td>
                        <td>
                            <a class="btn danger small" href="delete_property.php?id=<?= (int) $item['id'] ?>">Удалить</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
</body>
</html>

