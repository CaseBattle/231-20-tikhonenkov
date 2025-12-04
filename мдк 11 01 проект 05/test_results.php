<?php

declare(strict_types=1);

// Запуск PHPUnit и красивый интерфейс с группами тестов и временем выполнения.

$startTime = microtime(true);

$composerAutoload = __DIR__ . '/vendor/autoload.php';
if (!file_exists($composerAutoload)) {
    $error = 'Сначала выполните установку зависимостей: <code>composer install</code>.';
}

$results = null;

if (empty($error) && ($_GET['run'] ?? '') === '1') {
    $logFile   = __DIR__ . '/tests/logs/testdox.txt';
    $junitFile = __DIR__ . '/tests/logs/junit.xml';

    if (!is_dir(__DIR__ . '/tests/logs')) {
        mkdir(__DIR__ . '/tests/logs', 0777, true);
    }

    if (file_exists($logFile)) {
        unlink($logFile);
    }
    if (file_exists($junitFile)) {
        unlink($junitFile);
    }

    $command = escapeshellcmd(PHP_BINARY) . ' ' .
        escapeshellarg(__DIR__ . '/vendor/bin/phpunit') .
        ' --testdox';

    $output   = [];
    $exitCode = 0;
    exec($command . ' 2>&1', $output, $exitCode);

    $results = [
        'raw_output' => implode("\n", $output),
        'exit_code'  => $exitCode,
        'tests'      => [],
        'total'      => 0,
        'passed'     => 0,
        'failed'     => 0,
        'errors'     => 0,
        'skipped'    => 0,
    ];

    // Времена тестов из junit.xml (по порядку)
    $testTimes = [];
    if (file_exists($junitFile)) {
        $xmlStr = file_get_contents($junitFile);
        if ($xmlStr !== false &&
            preg_match_all('/<testcase\b[^>]*time="([\d.]+)"/', $xmlStr, $matches)
        ) {
            foreach ($matches[1] as $t) {
                $testTimes[] = (float)$t;
            }
        }
    }

    if (file_exists($logFile)) {
        $lines        = file($logFile, FILE_IGNORE_NEW_LINES);
        $currentGroup = 'Общие тесты';

        foreach ($lines as $line) {
            if ($line === '' || preg_match('/^Test Suite/', $line)) {
                continue;
            }

            // Заголовки групп: "Login (Tests\Login\Login)" и т.п.
            if (preg_match('/^([^(]+)\s+\(Tests\\\\(.+)\)$/', trim($line), $m)) {
                $title = trim($m[1]);
                switch ($title) {
                    case 'Login':
                        $currentGroup = 'Тесты входа';
                        break;
                    case 'Password Recovery':
                        $currentGroup = 'Тесты восстановления пароля';
                        break;
                    case 'Registration':
                        $currentGroup = 'Тесты регистрации';
                        break;
                    case 'Validation':
                        $currentGroup = 'Тесты валидации';
                        break;
                    default:
                        $currentGroup = 'Тесты общие';
                        break;
                }
                continue;
            }

            // Строки тестов: "[x] Успешный вход"
            if (preg_match('/^\s*\[\s*(.+?)\s*\]\s+(.+)$/u', $line, $m)) {
                $statusLabel = trim($m[1]);
                $name        = trim($m[2]);

                // [x] — passed, [S] — skipped, [F] — failed, [E] — error
                $status = 'passed';
                if (in_array($statusLabel, ['S', 's'], true)) {
                    $status = 'skipped';
                    $results['skipped']++;
                } elseif (in_array($statusLabel, ['F', 'f'], true)) {
                    $status = 'failed';
                    $results['failed']++;
                } elseif (in_array($statusLabel, ['E', 'e'], true)) {
                    $status = 'error';
                    $results['errors']++;
                } else {
                    $results['passed']++;
                }

                $time = null;
                if (!empty($testTimes)) {
                    $time = array_shift($testTimes);
                }

                $results['tests'][] = [
                    'group'  => $currentGroup,
                    'name'   => $name,
                    'status' => $status,
                    'time'   => $time,
                ];

                $results['total']++;
            }
        }
    }

    $duration = microtime(true) - $startTime;
} else {
    $duration = 0;
}

function statusClass(string $status): string
{
    switch ($status) {
        case 'passed':
            return 'status-passed';
        case 'failed':
            return 'status-failed';
        case 'error':
            return 'status-error';
        case 'skipped':
        default:
            return 'status-skipped';
    }
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Результаты тестов аутентификации</title>
    <style>
        body {
            margin: 0;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: linear-gradient(135deg, #4b6cb7, #182848);
            color: #222;
        }
        .page {
            max-width: 1100px;
            margin: 0 auto;
            padding: 24px 16px 40px;
        }
        .card {
            background: #f7f9fc;
            border-radius: 18px;
            box-shadow: 0 18px 45px rgba(15, 23, 42, 0.25);
            padding: 28px 32px 32px;
        }
        h1 {
            margin: 0 0 8px;
            font-size: 26px;
            text-align: center;
        }
        .subtitle {
            text-align: center;
            color: #6b7280;
            margin-bottom: 20px;
        }
        .run-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            border-radius: 999px;
            padding: 10px 22px;
            background: linear-gradient(90deg, #6366f1, #8b5cf6);
            color: #fff;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 12px 30px rgba(79, 70, 229, 0.45);
            transition: transform 0.1s ease-out, box-shadow 0.1s ease-out, filter 0.1s ease-out;
            font-size: 15px;
        }
        .run-btn:hover {
            transform: translateY(-1px);
            filter: brightness(1.03);
            box-shadow: 0 16px 40px rgba(79, 70, 229, 0.55);
        }
        .run-btn:active {
            transform: translateY(0);
            box-shadow: 0 8px 22px rgba(79, 70, 229, 0.4);
        }
        .toolbar {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }
        .stats-row {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 18px;
        }
        .stat-card {
            padding: 14px 12px;
            border-radius: 14px;
            text-align: center;
            color: #fff;
            font-size: 13px;
        }
        .stat-label {
            opacity: 0.9;
            margin-bottom: 4px;
        }
        .stat-value {
            font-size: 22px;
            font-weight: 700;
        }
        .stat-all { background: linear-gradient(135deg, #6366f1, #4f46e5); }
        .stat-pass { background: linear-gradient(135deg, #22c55e, #16a34a); }
        .stat-fail { background: linear-gradient(135deg, #f97316, #ea580c); }
        .stat-error { background: linear-gradient(135deg, #ef4444, #b91c1c); }
        .stat-skip { background: linear-gradient(135deg, #9ca3af, #6b7280); }

        .alert {
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 13px;
            margin-bottom: 18px;
        }
        .alert-success {
            background: #e0fbe5;
            border: 1px solid #4ade80;
            color: #14532d;
        }
        .alert-error {
            background: #fee2e2;
            border: 1px solid #f87171;
            color: #7f1d1d;
        }

        .groups {
            margin-top: 8px;
            background: #eef2ff;
            border-radius: 14px;
            padding: 10px 10px 4px;
        }
        .group {
            margin-bottom: 10px;
            background: #f9fafb;
            border-radius: 12px;
            overflow: hidden;
        }
        .group-header {
            padding: 8px 14px;
            font-weight: 600;
            font-size: 14px;
            background: #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .group-meta {
            font-size: 12px;
            color: #4b5563;
        }
        .group-body {
            padding: 6px 0;
        }
        .test-row {
            display: flex;
            align-items: center;
            padding: 6px 14px;
            font-size: 13px;
        }
        .test-row:nth-child(odd) {
            background: #f9fafb;
        }
        .test-row:nth-child(even) {
            background: #f3f4f6;
        }
        .test-name {
            flex: 1 1 auto;
        }
        .test-status {
            margin-left: 12px;
            font-size: 12px;
            font-weight: 600;
            padding: 2px 10px;
            border-radius: 999px;
            white-space: nowrap;
        }
        .status-passed {
            background: #bbf7d0;
            color: #166534;
        }
        .status-failed {
            background: #fed7aa;
            color: #9a3412;
        }
        .status-error {
            background: #fecaca;
            color: #b91c1c;
        }
        .status-skipped {
            background: #e5e7eb;
            color: #374151;
        }
        .footer-link {
            font-size: 13px;
            margin-top: 14px;
        }
        .footer-link a {
            color: #4f46e5;
            text-decoration: none;
        }
        .footer-link a:hover {
            text-decoration: underline;
        }
        @media (max-width: 768px) {
            .card { padding: 20px 16px 22px; }
            .stats-row { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="card">
            <h1>Результаты тестов аутентификации</h1>
            <div class="subtitle">Регистрация, вход и восстановление пароля</div>

            <div class="toolbar">
                <form method="get">
                    <input type="hidden" name="run" value="1">
                    <button class="run-btn" type="submit">Запустить тесты</button>
                </form>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <?= $error ?>
                </div>
            <?php elseif ($results): ?>
                <div class="alert alert-success">
                    Тесты выполнены. Время: <strong><?= number_format($duration, 3) ?> сек</strong>.
                </div>

                <div class="stats-row">
                    <div class="stat-card stat-all">
                        <div class="stat-label">Всего тестов</div>
                        <div class="stat-value"><?= $results['total'] ?></div>
                    </div>
                    <div class="stat-card stat-pass">
                        <div class="stat-label">Пройдено</div>
                        <div class="stat-value"><?= $results['passed'] ?></div>
                    </div>
                    <div class="stat-card stat-fail">
                        <div class="stat-label">Провалено</div>
                        <div class="stat-value"><?= $results['failed'] ?></div>
                    </div>
                    <div class="stat-card stat-error">
                        <div class="stat-label">Ошибок</div>
                        <div class="stat-value"><?= $results['errors'] ?></div>
                    </div>
                    <div class="stat-card stat-skip">
                        <div class="stat-label">Пропущено</div>
                        <div class="stat-value"><?= $results['skipped'] ?></div>
                    </div>
                </div>

                <?php
                $groups = [];
                foreach ($results['tests'] as $test) {
                    $groups[$test['group']][] = $test;
                }

                $groupStats = [];
                foreach ($groups as $groupName => $tests) {
                    $count = count($tests);
                    $time  = 0.0;
                    foreach ($tests as $t) {
                        if ($t['time'] !== null) {
                            $time += (float)$t['time'];
                        }
                    }
                    $groupStats[$groupName] = ['count' => $count, 'time' => $time];
                }
                ?>

                <div class="groups">
                    <?php foreach ($groups as $groupName => $tests): ?>
                        <div class="group">
                            <div class="group-header">
                                <span><?= htmlspecialchars($groupName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
                                <span class="group-meta">
                                    Тестов: <?= $groupStats[$groupName]['count'] ?>
                                    | Время: <?= number_format($groupStats[$groupName]['time'], 3) ?>c
                                </span>
                            </div>
                            <div class="group-body">
                                <?php foreach ($tests as $test): ?>
                                    <div class="test-row">
                                        <div class="test-name">
                                            <?= htmlspecialchars($test['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                                        </div>
                                        <div class="test-status <?= statusClass($test['status']) ?>">
                                            <?php
                                            switch ($test['status']) {
                                                case 'passed': echo '✓ пройден'; break;
                                                case 'failed': echo '✕ провален'; break;
                                                case 'error':  echo '⚠ ошибка'; break;
                                                case 'skipped':default: echo '⏳ пропущен'; break;
                                            }
                                            if (isset($test['time']) && $test['time'] !== null) {
                                                echo ' · ' . number_format((float)$test['time'], 3) . ' c';
                                            }
                                            ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-success">
                    Нажмите кнопку «Запустить тесты», чтобы выполнить автоматические проверки форм аутентификации.
                </div>
            <?php endif; ?>

            <div class="footer-link">
                ← <a href="./">Вернуться на главную (если интегрировано в основной проект)</a>
            </div>
        </div>
    </div>
</body>
</html>
