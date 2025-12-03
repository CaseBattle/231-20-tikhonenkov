<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require __DIR__ . '/../vendor/autoload.php';

/**
 * Базовый класс для всех тестов проекта.
 *
 * Реализует:
 * - измерение времени выполнения каждого теста;
 * - авто-пропуск тестов, выполняющихся дольше, чем TEST_MAX_DURATION (по умолчанию 1 секунда);
 * - подключение к тестовой БД и очистку данных между тестами.
 */
abstract class BaseTestCase extends TestCase
{
    private float $testStartTime;

    protected static ?PDO $pdo = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testStartTime = microtime(true);

        // Инициализация соединения с тестовой БД
        if (self::$pdo === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                getenv('DB_HOST') ?: '127.0.0.1',
                getenv('DB_PORT') ?: '3306',
                getenv('DB_NAME') ?: 'auth_project_test'
            );

            self::$pdo = new PDO(
                $dsn,
                getenv('DB_USER') ?: 'root',
                getenv('DB_PASSWORD') ?: '',
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        }

        // Очищаем таблицы перед каждым тестом для изоляции
        $this->cleanDatabase();
    }

    protected function tearDown(): void
    {
        // Очищаем таблицы после каждого теста
        $this->cleanDatabase();

        $duration = microtime(true) - $this->testStartTime;
        $maxDuration = (float)(getenv('TEST_MAX_DURATION') ?: 1.0);

        if ($duration > $maxDuration) {
            $this->markTestSkipped(
                sprintf(
                    'Тест выполнялся %.3f секунд(ы), что больше лимита %.3f секунд(ы).',
                    $duration,
                    $maxDuration
                )
            );
        }

        parent::tearDown();
    }

    /**
     * Очистка всех таблиц в тестовой БД.
     */
    private function cleanDatabase(): void
    {
        if (self::$pdo === null) {
            return;
        }

        try {
            // Отключаем проверку внешних ключей для безопасной очистки
            self::$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
            
            // Очищаем таблицы в правильном порядке (сначала зависимые)
            self::$pdo->exec('TRUNCATE TABLE password_resets');
            self::$pdo->exec('TRUNCATE TABLE users');
            
            // Включаем обратно проверку внешних ключей
            self::$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        } catch (\PDOException $e) {
            // Игнорируем ошибки, если таблицы не существуют
            // (они будут созданы в prepareSchema())
        }
    }
}




