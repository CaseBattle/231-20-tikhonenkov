<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Требуется авторизация.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Метод не поддерживается.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$payload = json_decode(file_get_contents('php://input') ?: '{}', true);
$propertyId = (int) ($payload['property_id'] ?? $_POST['property_id'] ?? 0);
$userId = (int) $_SESSION['user']['id'];

if ($propertyId <= 0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Некорректный объект.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$check = getPDO()->prepare('SELECT id FROM properties WHERE id = :id LIMIT 1');
$check->execute([':id' => $propertyId]);
if (!$check->fetch()) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Объект не найден.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt = getPDO()->prepare(
    'INSERT OR IGNORE INTO favorites(user_id, property_id) VALUES(:user_id, :property_id)'
);
$stmt->execute([
    ':user_id' => $userId,
    ':property_id' => $propertyId,
]);

echo json_encode([
    'success' => true,
    'count' => getFavoriteCount($userId),
], JSON_UNESCAPED_UNICODE);

