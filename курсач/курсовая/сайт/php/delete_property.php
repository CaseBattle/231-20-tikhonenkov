<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';
requireAdmin();

$id = (int) ($_GET['id'] ?? 0);
if ($id > 0) {
    $stmt = getPDO()->prepare('DELETE FROM properties WHERE id = :id');
    $stmt->execute([':id' => $id]);
}

header('Location: admin_properties.php');
exit;

