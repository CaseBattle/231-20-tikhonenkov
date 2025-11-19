<?php

require_once __DIR__ . '/../includes/bootstrap.php';

require_login();

$user = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>StayCity — аренда квартир</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body class="home-body">
    <div class="home">
        <header class="home-header">
            <div class="brand-mark">
                <span class="mark-dot"></span>
                <strong>StayCity</strong>
            </div>
            <nav class="home-nav">
                <a href="#">Новостройки</a>
                <a href="#">Долгосрочно</a>
                <a href="#">Посуточно</a>
                <a href="#">Ипотека</a>
            </nav>
            <div class="header-actions">
                <span class="user-pill">+<?= htmlspecialchars($user['phone'], ENT_QUOTES, 'UTF-8'); ?></span>
                <a class="primary-btn" href="portfolio.php">Личный кабинет</a>
                <form method="post" action="logout.php">
                    <button class="ghost-btn" type="submit">Выйти</button>
                </form>
            </div>
        </header>

        <section class="hero">
            <div>
                <p class="eyebrow">Выберите город мечты</p>
                <h1>Современные квартиры<br>для жизни и отдыха</h1>
                <p class="subtitle">
                    StayCity подбирает лучшую аренду: от смарт-апартаментов в центре Москвы
                    до панорамных видов на побережье Сочи.
                </p>
                <div class="search-panel">
                    <input type="text" placeholder="Город или район">
                    <input type="text" placeholder="Дата заселения">
                    <input type="text" placeholder="Срок аренды">
                    <button>Найти</button>
                </div>
                <div class="stats">
                    <div>
                        <strong>4 800+</strong>
                        <span>проверенных объектов</span>
                    </div>
                    <div>
                        <strong>72 города</strong>
                        <span>по всей России</span>
                    </div>
                    <div>
                        <strong>9.6 / 10</strong>
                        <span>оценка сервиса</span>
                    </div>
                </div>
            </div>
            <div class="hero-card">
                <img src="https://picsum.photos/520/360?random=21" alt="apartment">
                <div class="hero-card-body">
                    <div>
                        <h3>Skyline Loft</h3>
                        <p>Москва • Пресня • 54 м²</p>
                    </div>
                    <strong class="price">145 000 ₽/мес</strong>
                </div>
            </div>
        </section>

        <section class="featured">
            <div class="section-head">
                <h2>Топ предложения недели</h2>
                <a href="#">Смотреть все</a>
            </div>
            <div class="cards">
                <article class="card">
                    <img src="https://picsum.photos/420/280?random=11" alt="loft">
                    <div class="card-body">
                        <div class="badge">Посуточно</div>
                        <h3>Loft Botanica</h3>
                        <p>Санкт‑Петербург • Петроградка</p>
                        <div class="card-meta">
                            <span>2 спальни</span>
                            <span>68 м²</span>
                            <strong>9 800 ₽/сут</strong>
                        </div>
                    </div>
                </article>
                <article class="card">
                    <img src="https://picsum.photos/420/280?random=12" alt="sea view">
                    <div class="card-body">
                        <div class="badge">Долгосрочно</div>
                        <h3>Sea Breeze</h3>
                        <p>Сочи • Курортный район</p>
                        <div class="card-meta">
                            <span>3 спальни</span>
                            <span>102 м²</span>
                            <strong>120 000 ₽/мес</strong>
                        </div>
                    </div>
                </article>
                <article class="card">
                    <img src="https://picsum.photos/420/280?random=13" alt="studio">
                    <div class="card-body">
                        <div class="badge">Новостройка</div>
                        <h3>Nordic Studio</h3>
                        <p>Казань • Набережная Казанки</p>
                        <div class="card-meta">
                            <span>1 спальня</span>
                            <span>43 м²</span>
                            <strong>75 000 ₽/мес</strong>
                        </div>
                    </div>
                </article>
            </div>
        </section>

        <section class="cta">
            <div>
                <p class="eyebrow">Премиум сопровождение</p>
                <h2>Найдём квартиру, даже если её нет в объявлениях</h2>
                <p>Персональный эксперт, юридическое сопровождение и безопасные сделки.</p>
            </div>
            <button class="primary-btn">Оставить заявку</button>
        </section>
    </div>
</body>
</html>




