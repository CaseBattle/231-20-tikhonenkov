<?php

namespace App\Services;

use App\Validation\AuthValidator;
use PDO;

/**
 * Класс, имитирующий систему аутентификации.
 * В продакшн-проекте вместо SQLite может использоваться любая БД.
 */
class AuthService
{
    private PDO $pdo;
    private AuthValidator $validator;

    public function __construct(PDO $pdo, ?AuthValidator $validator = null)
    {
        $this->pdo = $pdo;
        $this->validator = $validator ?? new AuthValidator();
    }

    /**
     * Регистрация нового пользователя.
     */
    public function register(string $phone, string $password, string $passwordConfirm): array
    {
        $errors = $this->validator->validateRegistration($phone, $password, $passwordConfirm);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        // Проверка уникальности телефона
        $stmt = $this->pdo->prepare('SELECT id FROM users WHERE phone = :phone');
        $stmt->execute(['phone' => $phone]);
        if ($stmt->fetch()) {
            return ['success' => false, 'errors' => ['phone' => 'Пользователь с таким телефоном уже существует']];
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $this->pdo->prepare('INSERT INTO users (phone, password_hash) VALUES (:phone, :hash)');
        $stmt->execute(['phone' => $phone, 'hash' => $hash]);

        return ['success' => true, 'errors' => []];
    }

    /**
     * Вход пользователя.
     */
    public function login(string $phone, string $password): array
    {
        $errors = $this->validator->validateLogin($phone, $password);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $stmt = $this->pdo->prepare('SELECT id, password_hash FROM users WHERE phone = :phone');
        $stmt->execute(['phone' => $phone]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return ['success' => false, 'errors' => ['phone' => 'Пользователь не найден']];
        }

        if (!password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'errors' => ['password' => 'Неверный пароль']];
        }

        return ['success' => true, 'errors' => []];
    }

    /**
     * Запрос кода восстановления пароля.
     */
    public function requestPasswordReset(string $phone): array
    {
        $errors = $this->validator->validatePhone($phone);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => ['phone' => $errors['phone'] ?? 'Некорректный телефон']];
        }

        $stmt = $this->pdo->prepare('SELECT id FROM users WHERE phone = :phone');
        $stmt->execute(['phone' => $phone]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return ['success' => false, 'errors' => ['phone' => 'Пользователь не найден']];
        }

        $code = random_int(100000, 999999);
        $expiresAt = time() + 300; // 5 минут

        // Удаляем старые коды
        $delete = $this->pdo->prepare('DELETE FROM password_resets WHERE user_id = :user_id');
        $delete->execute(['user_id' => $user['id']]);

        $insert = $this->pdo->prepare(
            'INSERT INTO password_resets (user_id, code, expires_at, attempts) VALUES (:user_id, :code, :expires_at, 0)'
        );
        $insert->execute([
            'user_id' => $user['id'],
            'code' => (string)$code,
            'expires_at' => $expiresAt,
        ]);

        return ['success' => true, 'code' => (string)$code];
    }

    /**
     * Сброс пароля по коду восстановления.
     */
    public function resetPassword(string $phone, string $code, string $password, string $passwordConfirm): array
    {
        $errors = $this->validator->validatePasswordReset($password, $passwordConfirm);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $stmt = $this->pdo->prepare('SELECT id FROM users WHERE phone = :phone');
        $stmt->execute(['phone' => $phone]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return ['success' => false, 'errors' => ['phone' => 'Пользователь не найден']];
        }

        $resetStmt = $this->pdo->prepare(
            'SELECT id, code, expires_at, attempts FROM password_resets WHERE user_id = :user_id'
        );
        $resetStmt->execute(['user_id' => $user['id']]);
        $reset = $resetStmt->fetch(PDO::FETCH_ASSOC);

        if (!$reset) {
            return ['success' => false, 'errors' => ['code' => 'Код восстановления не запрошен']];
        }

        // Лимит попыток
        if ((int)$reset['attempts'] >= 5) {
            return ['success' => false, 'errors' => ['code' => 'Превышено количество попыток']];
        }

        // Проверка времени действия
        if ((int)$reset['expires_at'] < time()) {
            return ['success' => false, 'errors' => ['code' => 'Срок действия кода истёк']];
        }

        // Проверка кода
        if ($reset['code'] !== $code) {
            $update = $this->pdo->prepare(
                'UPDATE password_resets SET attempts = attempts + 1 WHERE id = :id'
            );
            $update->execute(['id' => $reset['id']]);

            return ['success' => false, 'errors' => ['code' => 'Неверный код']];
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $updateUser = $this->pdo->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
        $updateUser->execute(['hash' => $hash, 'id' => $user['id']]);

        // Удаляем использованный код
        $delete = $this->pdo->prepare('DELETE FROM password_resets WHERE id = :id');
        $delete->execute(['id' => $reset['id']]);

        return ['success' => true, 'errors' => []];
    }
}







