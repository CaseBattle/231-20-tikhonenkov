<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

$propertyId = (int) ($_POST['property_id'] ?? 0);
$comment = trim($_POST['comment'] ?? '');
$userId = (int) $_SESSION['user']['id'];

if ($propertyId <= 0) {
    $_SESSION['flash_error'] = 'Некорректный объект.';
    header('Location: ../index.php');
    exit;
}

$pdo = getPDO();

$exists = $pdo->prepare('SELECT id FROM properties WHERE id = :id AND status = :status');
$exists->execute([':id' => $propertyId, ':status' => 'available']);
if (!$exists->fetch()) {
    $_SESSION['flash_error'] = 'Объект не найден или недоступен.';
    header('Location: ../index.php');
    exit;
}

$stmt = $pdo->prepare(
    'INSERT INTO requests(user_id, property_id, comment, status) VALUES(:user_id, :property_id, :comment, :status)'
);
$stmt->execute([
    ':user_id' => $userId,
    ':property_id' => $propertyId,
    ':comment' => $comment,
    ':status' => 'pending',
]);

$_SESSION['flash_success'] = 'Заявка успешно отправлена.';
header('Location: profile.php');
exit;

