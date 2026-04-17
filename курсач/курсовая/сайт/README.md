# GreenHome — курсовой проект

Учебный веб-сервис аренды и продажи недвижимости на чистом стеке:

- Frontend: HTML5, CSS3, JavaScript (vanilla)
- Backend: PHP 8+
- Database: SQLite (`database.db`)

## Структура проекта

```text
/css/styles.css
/js/script.js
/images/logo.svg
/images/profile.svg
/images/demo-property.jpg
/php/config.php
/php/init_db.php
/php/login.php
/php/register.php
/php/logout.php
/php/profile.php
/php/requests.php
/php/add_request.php
/php/admin.php
/php/admin_properties.php
/php/admin_requests.php
/php/admin_feedback.php
/php/delete_request.php
/php/approve_request.php
/php/add_property.php
/php/delete_property.php
/index.php
/database.db
```

## Запуск

1. Убедитесь, что установлен PHP 8+.
2. В корне проекта выполните:

```bash
php php/init_db.php
```

3. Запустите локальный сервер:

```bash
php -S localhost:8000
```

4. Откройте [http://localhost:8000](http://localhost:8000).

## Данные по умолчанию

Администратор создается автоматически:

- Email: `admin@mail.com`
- Пароль: `admin123`

## Основной функционал

- Регистрация и авторизация пользователей
- Личный кабинет пользователя с заявками и статусами
- Подача заявок на объявления
- Админ-панель:
  - управление объявлениями
  - обработка заявок (одобрить/отклонить)
  - просмотр обратной связи
- Фильтрация и поиск объектов на главной странице

