<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
requireAuth();

$pdo = getPDO();
$userId = (int) $_SESSION['user']['id'];

if (isAdmin()) {
    $rows = $pdo->query("
        SELECT r.*, u.name AS user_name, p.title AS property_title
        FROM requests r
        JOIN users u ON u.id = r.user_id
        JOIN properties p ON p.id = r.property_id
        ORDER BY r.created_at DESC
    ")->fetchAll();
} else {
    $stmt = $pdo->prepare("
        SELECT r.*, p.title AS property_title
        FROM requests r
        JOIN properties p ON p.id = r.property_id
        WHERE r.user_id = :user_id
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([':user_id' => $userId]);
    $rows = $stmt->fetchAll();
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

