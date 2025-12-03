<?php

declare(strict_types=1);

// Простая веб-страница для запуска PHPUnit-тестов и просмотра результатов.

$results = null;
$rawOutput = [];
$exitCode = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cmd = (PHP_OS_FAMILY === 'Windows')
        ? 'vendor\\bin\\phpunit --configuration phpunit.xml'
        : 'vendor/bin/phpunit --configuration phpunit.xml';

    $cmd .= ' --log-junit tests/logs/junit.xml';

    @mkdir(__DIR__ . '/tests/logs', 0777, true);

    exec($cmd . ' 2>&1', $rawOutput, $exitCode);

    $junitFile = __DIR__ . '/tests/logs/junit.xml';
    if (is_file($junitFile)) {
        $xml = @simplexml_load_file($junitFile);
        if ($xml !== false) {
            // Ищем первый testsuite с атрибутами (обычно это второй уровень вложенности)
            $mainSuite = null;
            if (isset($xml->testsuite)) {
                // Если есть вложенные testsuite, берём первый с атрибутами
                $suites = is_array($xml->testsuite) ? $xml->testsuite : [$xml->testsuite];
                foreach ($suites as $suite) {
                    if (isset($suite['tests']) && (int)$suite['tests'] > 0) {
                        $mainSuite = $suite;
                        break;
                    }
                    // Если внутри есть ещё testsuite, проверяем их
                    if (isset($suite->testsuite)) {
                        $innerSuites = is_array($suite->testsuite) ? $suite->testsuite : [$suite->testsuite];
                        foreach ($innerSuites as $innerSuite) {
                            if (isset($innerSuite['tests']) && (int)$innerSuite['tests'] > 0) {
                                $mainSuite = $innerSuite;
                                break 2;
                            }
                        }
                    }
                }
            }
            
            // Извлекаем статистику из найденного testsuite
            if ($mainSuite !== null) {
                $results = [
                    'tests' => (int)($mainSuite['tests'] ?? 0),
                    'failures' => (int)($mainSuite['failures'] ?? 0),
                    'errors' => (int)($mainSuite['errors'] ?? 0),
                    'skipped' => (int)($mainSuite['skipped'] ?? 0),
                    'time' => (float)($mainSuite['time'] ?? 0.0),
                    'cases' => [],
                ];
            } else {
                $results = [
                    'tests' => 0,
                    'failures' => 0,
                    'errors' => 0,
                    'skipped' => 0,
                    'time' => 0.0,
                    'cases' => [],
                ];
            }

            // Рекурсивно собираем все testcase из всех testsuite
            $collectTestCases = function($suite) use (&$collectTestCases, &$results) {
                // Если есть testcase на этом уровне
                if (isset($suite->testcase)) {
                    $cases = is_array($suite->testcase) ? $suite->testcase : [$suite->testcase];
                    foreach ($cases as $case) {
                        $status = 'success';
                        $message = '';

                        if (isset($case->failure)) {
                            $status = 'failure';
                            $message = (string)($case->failure['message'] ?? $case->failure);
                        } elseif (isset($case->error)) {
                            $status = 'error';
                            $message = (string)($case->error['message'] ?? $case->error);
                        } elseif (isset($case->skipped)) {
                            $status = 'skipped';
                            $message = (string)($case->skipped['message'] ?? $case->skipped);
                        }

                        $results['cases'][] = [
                            'class' => (string)($case['class'] ?? ''),
                            'name' => (string)($case['name'] ?? ''),
                            'time' => (float)($case['time'] ?? 0.0),
                            'status' => $status,
                            'message' => $message,
                        ];
                    }
                }
                
                // Рекурсивно обрабатываем вложенные testsuite
                if (isset($suite->testsuite)) {
                    $innerSuites = is_array($suite->testsuite) ? $suite->testsuite : [$suite->testsuite];
                    foreach ($innerSuites as $innerSuite) {
                        $collectTestCases($innerSuite);
                    }
                }
            };
            
            // Начинаем сбор с корневого элемента
            if (isset($xml->testsuite)) {
                $suites = is_array($xml->testsuite) ? $xml->testsuite : [$xml->testsuite];
                foreach ($suites as $suite) {
                    $collectTestCases($suite);
                }
            }
        } else {
            $error = 'Не удалось прочитать файл junit.xml.';
        }
    } else {
        $error = 'Файл junit.xml не найден. Проверьте конфигурацию PHPUnit.';
    }
}

function statusColor(string $status): string
{
    return match ($status) {
        'success' => '#d4edda',
        'failure' => '#f8d7da',
        'error'   => '#f8d7da',
        'skipped' => '#fff3cd',
        default   => '#ffffff',
    };
}

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Результаты тестирования форм аутентификации</title>
    <style>
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background-color: #f5f7fb;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 1100px;
            margin: 40px auto;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.06);
            padding: 24px 28px 32px;
        }

        h1 {
            font-size: 24px;
            margin-top: 0;
            margin-bottom: 8px;
        }

        .subtitle {
            color: #6c757d;
            margin-bottom: 24px;
        }

        .controls {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 24px;
        }

        button {
            background: #007bff;
            color: #ffffff;
            border: none;
            border-radius: 6px;
            padding: 10px 18px;
            font-size: 14px;
            cursor: pointer;
            transition: background-color 0.2s, transform 0.05s;
        }

        button:hover {
            background: #0069d9;
        }

        button:active {
            transform: translateY(1px);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-success {
            background: #d4edda;
            color: #155724;
        }

        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }

        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }

        .summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 12px;
            margin-bottom: 24px;
        }

        .summary-item {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 12px 14px;
        }

        .summary-label {
            font-size: 12px;
            color: #6c757d;
            margin-bottom: 4px;
        }

        .summary-value {
            font-size: 18px;
            font-weight: 600;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            border-radius: 8px;
            overflow: hidden;
            font-size: 13px;
        }

        th, td {
            padding: 8px 10px;
            text-align: left;
        }

        th {
            background: #f1f3f5;
            font-weight: 600;
            border-bottom: 1px solid #dee2e6;
        }

        tr:nth-child(even) td {
            background: #fafbfc;
        }

        .status-cell {
            font-weight: 600;
        }

        .status-success {
            color: #155724;
        }

        .status-failure,
        .status-error {
            color: #721c24;
        }

        .status-skipped {
            color: #856404;
        }

        .raw-output {
            margin-top: 24px;
            background: #0b1020;
            color: #e5e9f0;
            padding: 12px 14px;
            border-radius: 8px;
            font-family: "JetBrains Mono", ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            font-size: 12px;
            max-height: 260px;
            overflow: auto;
        }

        .error {
            margin-bottom: 16px;
            padding: 10px 12px;
            background: #f8d7da;
            color: #721c24;
            border-radius: 6px;
            font-size: 13px;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Тестирование форм аутентификации</h1>
    <div class="subtitle">
        Запуск автоматических тестов для форм регистрации, входа и восстановления пароля.
    </div>

    <form method="post" class="controls">
        <button type="submit">Запустить тесты</button>
        <?php if ($results): ?>
            <?php
            $failedCount = $results['failures'] + $results['errors'];
            $allOk = $failedCount === 0 && $results['tests'] > 0;
            ?>
            <span class="badge <?= $allOk ? 'badge-success' : 'badge-danger' ?>">
                <?= $allOk ? 'Все тесты успешно пройдены' : 'Есть ошибки в тестах' ?>
            </span>
        <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
            <span class="badge badge-warning">Нет данных о тестах</span>
        <?php endif; ?>
    </form>

    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if ($results): ?>
        <div class="summary">
            <div class="summary-item">
                <div class="summary-label">Всего тестов</div>
                <div class="summary-value"><?= $results['tests'] ?></div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Ошибки и падения</div>
                <div class="summary-value"><?= $results['failures'] + $results['errors'] ?></div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Пропущенные тесты</div>
                <div class="summary-value"><?= $results['skipped'] ?></div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Общее время</div>
                <div class="summary-value"><?= number_format($results['time'], 3, '.', ' ') ?> с</div>
            </div>
        </div>

        <table>
            <thead>
            <tr>
                <th>Класс</th>
                <th>Тест</th>
                <th>Статус</th>
                <th>Время, c</th>
                <th>Сообщение</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($results['cases'] as $case): ?>
                <?php $bg = statusColor($case['status']); ?>
                <tr style="background-color: <?= $bg ?>">
                    <td><?= htmlspecialchars($case['class'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($case['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                    <td class="status-cell status-<?= htmlspecialchars($case['status'], ENT_QUOTES, 'UTF-8') ?>">
                        <?php
                        echo match ($case['status']) {
                            'success' => 'Успешно',
                            'failure' => 'Провалено',
                            'error'   => 'Ошибка',
                            'skipped' => 'Пропущено',
                            default   => 'Неизвестно',
                        };
                        ?>
                    </td>
                    <td><?= number_format($case['time'], 3, '.', ' ') ?></td>
                    <td><?= htmlspecialchars($case['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <?php if ($rawOutput): ?>
        <div class="raw-output">
            <?php foreach ($rawOutput as $line): ?>
                <?= htmlspecialchars($line, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?><br>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
</body>
</html>




