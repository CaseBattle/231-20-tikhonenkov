<?php

declare(strict_types=1);

function get_db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $databaseDir = __DIR__ . '/../data';
    if (!is_dir($databaseDir)) {
        mkdir($databaseDir, 0775, true);
    }

    $databasePath = $databaseDir . '/database.db';
    $dsn = 'sqlite:' . $databasePath;

    $pdo = new PDO($dsn);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            phone TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )'
    );

    $columnsStmt = $pdo->query('PRAGMA table_info(users)');
    $columnNames = [];
    while ($column = $columnsStmt->fetch()) {
        if (isset($column['name'])) {
            $columnNames[] = $column['name'];
        }
    }

    $columnsToAdd = [
        'first_name' => "ALTER TABLE users ADD COLUMN first_name TEXT DEFAULT ''",
        'last_name' => "ALTER TABLE users ADD COLUMN last_name TEXT DEFAULT ''",
    ];

    foreach ($columnsToAdd as $column => $ddl) {
        if (!in_array($column, $columnNames, true)) {
            $pdo->exec($ddl);
        }
    }

    return $pdo;
}

