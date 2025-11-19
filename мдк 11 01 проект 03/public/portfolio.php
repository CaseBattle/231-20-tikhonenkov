<?php

require_once __DIR__ . '/../includes/bootstrap.php';

require_login();

$user = $_SESSION['user'];
$firstName = trim($user['first_name'] ?? '');
$lastName = trim($user['last_name'] ?? '');
$fullName = trim($firstName . ' ' . $lastName);
if ($fullName === '') {
    $fullName = 'Никита Тихоненков';
}

$initials = '';
$initialsSource = [];
if ($firstName !== '') {
    $initialsSource[] = $firstName;
}
if ($lastName !== '') {
    $initialsSource[] = $lastName;
}

if ($initialsSource) {
    $getLetter = static function (string $value): string {
        if (function_exists('mb_substr')) {
            return (string) mb_substr($value, 0, 1, 'UTF-8');
        }
        return substr($value, 0, 1);
    };

    $initials = '';
    foreach ($initialsSource as $value) {
        $initials .= $getLetter($value);
    }

    if (function_exists('mb_strtoupper')) {
        $initials = mb_strtoupper($initials, 'UTF-8');
    } else {
        $initials = strtoupper($initials);
    }
}

if ($initials === '') {
    $initials = 'НТ';
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Личное портфолио</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body class="dashboard-body">
    <div class="dashboard">
        <aside class="dashboard-sidebar">
            <div class="brand-mark">
                <span class="mark-dot"></span>
                <strong>StayCity PRO</strong>
            </div>
            <nav class="sidebar-nav">
                <a class="active" href="#">Главная</a>
                <a href="#">Избранное</a>
                <a href="#">Мои объявления</a>
                <a href="#">Платежи и счета</a>
                <a href="#">Поддержка</a>
            </nav>
            <div class="support-widget">
                <p>Нужна помощь?</p>
                <strong>8 (800) 707‑77‑07</strong>
                <span>Ежедневно с 9:00 до 21:00</span>
            </div>
        </aside>
        <main class="dashboard-main">
            <div class="dashboard-top">
                <div class="user-card">
                    <div class="avatar"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8'); ?></div>
                    <div>
                        <h1><?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?></h1>
                        <p>+<?= htmlspecialchars($user['phone'], ENT_QUOTES, 'UTF-8'); ?> · Product Designer</p>
                        <div class="user-links">
                            <span>Почта: rc_4y@mail.ru</span>
                            <span>Telegram: @solevoiruslovedeagle</span>
                        </div>
                    </div>
                </div>
                <div class="top-actions">
                    <a class="ghost-btn" href="home.php">Каталог</a>
                    <button class="primary-btn">Разместить объявление</button>
                    <form method="post" action="logout.php">
                        <button class="ghost-btn" type="submit">Выйти</button>
                    </form>
                </div>
            </div>

            <section class="dashboard-stats">
                <article>
                    <p>Активные объявления</p>
                    <strong>3</strong>
                    <span>2 на модерации</span>
                </article>
                <article>
                    <p>Новые отклики</p>
                    <strong>14</strong>
                    <span>5 за последние 24 часа</span>
                </article>
                <article>
                    <p>Сборы за месяц</p>
                    <strong>185 000 ₽</strong>
                    <span>+18% к прошлому месяцу</span>
                </article>
            </section>

            <section class="dashboard-section">
                <div class="section-head">
                    <h2>Избранные квартиры</h2>
                    <a href="#">Управлять</a>
                </div>
                <div class="cards">
                    <article class="card listing-card">
                        <img src="https://picsum.photos/420/280?random=31" alt="listing">
                        <div class="card-body">
                            <div>
                                <h3>Loft Botanica</h3>
                                <p>Санкт‑Петербург, Петроградка</p>
                            </div>
                            <div class="listing-meta">
                                <span>3 комнаты · 86 м²</span>
                                <strong>160 000 ₽/мес</strong>
                            </div>
                            <div class="card-actions">
                                <button>Перейти к объявлению</button>
                                <button class="ghost-btn">Редактировать</button>
                            </div>
                        </div>
                    </article>
                    <article class="card listing-card">
                        <img src="https://picsum.photos/420/280?random=32" alt="listing">
                        <div class="card-body">
                            <div>
                                <h3>Skyline Terrace</h3>
                                <p>Москва, Пресня</p>
                            </div>
                            <div class="listing-meta">
                                <span>2 комнаты · 62 м²</span>
                                <strong>190 000 ₽/мес</strong>
                            </div>
                            <div class="card-actions">
                                <button>Перейти к объявлению</button>
                                <button class="ghost-btn">Архивировать</button>
                            </div>
                        </div>
                    </article>
                </div>
            </section>

            <section class="dashboard-section grid">
                <div class="activity">
                    <div class="section-head">
                        <h2>Активность</h2>
                        <a href="#">История</a>
                    </div>
                    <ul>
                        <li>
                            <strong>Новый отклик</strong>
                            <span>Loft Botanica · 10:14</span>
                        </li>
                        <li>
                            <strong>Статус обновлён</strong>
                            <span>Skyline Terrace прошёл модерацию · вчера</span>
                        </li>
                        <li>
                            <strong>Платёж получен</strong>
                            <span>Аренда Nordic Studio · 18 ноября</span>
                        </li>
                    </ul>
                </div>
                <div class="support-card">
                    <h2>Поддержка StayCity</h2>
                    <p>Персональный менеджер свяжется с вами в течение 15 минут.</p>
                    <button class="primary-btn">Написать менеджеру</button>
                </div>
            </section>
        </main>
    </div>
</body>
</html>

