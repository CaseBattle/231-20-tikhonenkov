<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

const DB_PATH = __DIR__ . '/../database.db';

function getPDO(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $pdo = new PDO('sqlite:' . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec('PRAGMA foreign_keys = ON');
    }

    return $pdo;
}

function isLoggedIn(): bool
{
    return isset($_SESSION['user']);
}

function isAdmin(): bool
{
    return isLoggedIn() && ($_SESSION['user']['role'] ?? '') === 'admin';
}

function requireAuth(): void
{
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function requireAdmin(): void
{
    if (!isAdmin()) {
        header('Location: ../index.php');
        exit;
    }
}

function esc(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function requestStatusLabel(string $status): string
{
    return match ($status) {
        'pending' => 'Ожидание',
        'approved' => 'Одобрена',
        'deleted' => 'Удалена',
        'rejected' => 'Отклонена',
        default => $status,
    };
}

function requestStatusClass(string $status): string
{
    return match ($status) {
        'pending' => 'pending',
        'approved' => 'approved',
        'deleted' => 'deleted',
        'rejected' => 'rejected',
        default => '',
    };
}

function getFavoritePropertyIds(int $userId): array
{
    $stmt = getPDO()->prepare('SELECT property_id FROM favorites WHERE user_id = :user_id');
    $stmt->execute([':user_id' => $userId]);
    return array_map('intval', array_column($stmt->fetchAll(), 'property_id'));
}

function getFavoriteCount(int $userId): int
{
    $stmt = getPDO()->prepare('SELECT COUNT(*) FROM favorites WHERE user_id = :user_id');
    $stmt->execute([':user_id' => $userId]);
    return (int) $stmt->fetchColumn();
}

