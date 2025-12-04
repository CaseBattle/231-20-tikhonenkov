<?php

declare(strict_types=1);

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Главное меню тестовой системы</title>
    <style>
        body {
            margin: 0;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: linear-gradient(135deg, #4b6cb7, #182848);
            color: #111827;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .card {
            background: #f9fafb;
            border-radius: 18px;
            padding: 28px 32px 30px;
            box-shadow: 0 18px 45px rgba(15, 23, 42, 0.35);
            text-align: center;
            max-width: 460px;
            width: 100%;
        }
        h1 {
            margin: 0 0 10px;
            font-size: 24px;
        }
        .subtitle {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 24px;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 28px;
            border-radius: 999px;
            background: linear-gradient(90deg, #6366f1, #8b5cf6);
            color: #ffffff;
            font-weight: 600;
            text-decoration: none;
            box-shadow: 0 12px 30px rgba(79, 70, 229, 0.45);
            transition: transform 0.1s ease-out, box-shadow 0.1s ease-out, filter 0.1s ease-out;
            font-size: 15px;
        }
        .btn:hover {
            transform: translateY(-1px);
            filter: brightness(1.03);
            box-shadow: 0 16px 40px rgba(79, 70, 229, 0.55);
        }
        .btn:active {
            transform: translateY(0);
            box-shadow: 0 8px 22px rgba(79, 70, 229, 0.4);
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>Система тестирования аутентификации</h1>
        <div class="subtitle">
            Учебный проект МДК 11.01 – автоматические тесты форм регистрации, входа и восстановления пароля.
        </div>
        <a class="btn" href="test_results.php?run=1">Перейти к тестам</a>
    </div>
</body>
</html>