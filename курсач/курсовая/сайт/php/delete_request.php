<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
requireAuth();

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: profile.php');
    exit;
}

$pdo = getPDO();

if (isAdmin()) {
    $stmt = $pdo->prepare('DELETE FROM requests WHERE id = :id');
    $stmt->execute([':id' => $id]);
    header('Location: admin_requests.php');
    exit;
}

$stmt = $pdo->prepare('DELETE FROM requests WHERE id = :id AND user_id = :user_id AND status = :status');
$stmt->execute([
    ':id' => $id,
    ':user_id' => (int) $_SESSION['user']['id'],
    ':status' => 'pending',
]);

header('Location: profile.php');
exit;

