<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
requireAdmin();

$id = (int) ($_GET['id'] ?? 0);
$status = $_GET['status'] ?? '';

if ($id > 0 && in_array($status, ['approved', 'rejected'], true)) {
    $stmt = getPDO()->prepare('UPDATE requests SET status = :status WHERE id = :id');
    $stmt->execute([':status' => $status, ':id' => $id]);
}

header('Location: admin_requests.php');
exit;

