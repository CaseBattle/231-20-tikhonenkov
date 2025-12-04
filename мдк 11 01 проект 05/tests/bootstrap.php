<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use PDO;

/**
 * Общая функция для получения PDO для тестовой БД.
 */
function createTestPdo(): PDO
{
    $dbPath = getenv('TEST_DB_PATH') ?: __DIR__ . '/database/test.sqlite';

    if (!is_dir(dirname($dbPath))) {
        mkdir(dirname($dbPath), 0777, true);
    }

    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Схема БД
    $schemaSql = file_get_contents(__DIR__ . '/database/schema.sql');
    $pdo->exec('PRAGMA foreign_keys = OFF;');
    $pdo->exec($schemaSql);
    $pdo->exec('PRAGMA foreign_keys = ON;');

    return $pdo;
}







