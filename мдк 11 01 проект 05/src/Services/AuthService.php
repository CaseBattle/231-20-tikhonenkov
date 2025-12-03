<?php

declare(strict_types=1);

namespace App\Services;

use App\Validation\AuthValidator;
use PDO;
use RuntimeException;

/**
 * Упрощённый сервис аутентификации для тестов.
 *
 * В реальном проекте вы можете заменить этот класс своим сервисом
 * из проекта «мдк 11 01 проект 03», а тесты адаптировать под него.
 */
class AuthService
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    /**
     * Регистрация пользователя.
     *
     * @throws RuntimeException при ошибках валидации или уникальности телефона
     */
    public function register(string $phone, string $password, string $passwordConfirmation): int
    {
        // Обрезаем пробелы в телефоне
        $phone = trim($phone);
        
        if (!AuthValidator::isValidPhone($phone)) {
            throw new RuntimeException('Неверный формат телефона');
        }

        if (!AuthValidator::isValidPassword($password)) {
            throw new RuntimeException('Неверный формат пароля');
        }

        if (!AuthValidator::isPasswordConfirmationValid($password, $passwordConfirmation)) {
            throw new RuntimeException('Пароли не совпадают');
        }

        // Проверяем уникальность телефона
        $stmt = $this->pdo->prepare('SELECT id FROM users WHERE phone = :phone LIMIT 1');
        $stmt->execute(['phone' => $phone]);
        if ($stmt->fetch()) {
            throw new RuntimeException('Пользователь с таким телефоном уже существует');
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $this->pdo->prepare(
            'INSERT INTO users (phone, password_hash, created_at) VALUES (:phone, :password_hash, NOW())'
        );
        $stmt->execute([
            'phone' => $phone,
            'password_hash' => $hash,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Аутентификация по телефону и паролю.
     *
     * Возвращает ID пользователя при успехе.
     *
     * @throws RuntimeException при ошибках
     */
    public function login(string $phone, string $password): int
    {
        // Обрезаем пробелы в телефоне
        $phone = trim($phone);
        
        $stmt = $this->pdo->prepare('SELECT id, password_hash FROM users WHERE phone = :phone LIMIT 1');
        $stmt->execute(['phone' => $phone]);

        $row = $stmt->fetch();
        if (!$row) {
            throw new RuntimeException('Пользователь не найден');
        }

        // Проверяем чувствительность к регистру
        if (!password_verify($password, $row['password_hash'])) {
            throw new RuntimeException('Неверный пароль');
        }

        return (int)$row['id'];
    }

    /**
     * Инициация восстановления пароля.
     *
     * Генерирует и сохраняет код в таблицу password_resets.
     *
     * @return string Сгенерированный код
     */
    public function generateResetCode(string $phone, \DateInterval $ttl): string
    {
        // Обрезаем пробелы в телефоне
        $phone = trim($phone);
        
        $stmt = $this->pdo->prepare('SELECT id FROM users WHERE phone = :phone LIMIT 1');
        $stmt->execute(['phone' => $phone]);
        $user = $stmt->fetch();

        if (!$user) {
            throw new RuntimeException('Пользователь не найден');
        }

        $code = random_int(100000, 999999);

        $expiresAt = (new \DateTimeImmutable('now'))
            ->add($ttl)
            ->format('Y-m-d H:i:s');

        $stmt = $this->pdo->prepare(
            'INSERT INTO password_resets (user_id, code, expires_at, created_at) 
             VALUES (:user_id, :code, :expires_at, NOW())'
        );
        $stmt->execute([
            'user_id' => $user['id'],
            'code' => (string)$code,
            'expires_at' => $expiresAt,
        ]);

        return (string)$code;
    }

    /**
     * Сброс пароля по коду.
     */
    public function resetPassword(string $phone, string $code, string $newPassword, string $newPasswordConfirmation): void
    {
        // Обрезаем пробелы в телефоне
        $phone = trim($phone);
        
        if (!AuthValidator::isValidPassword($newPassword)) {
            throw new RuntimeException('Неверный формат нового пароля');
        }

        if (!AuthValidator::isPasswordConfirmationValid($newPassword, $newPasswordConfirmation)) {
            throw new RuntimeException('Пароли не совпадают');
        }

        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare('SELECT id FROM users WHERE phone = :phone LIMIT 1');
            $stmt->execute(['phone' => $phone]);
            $user = $stmt->fetch();

            if (!$user) {
                throw new RuntimeException('Пользователь не найден');
            }

            $stmt = $this->pdo->prepare(
                'SELECT id, code, expires_at 
                 FROM password_resets 
                 WHERE user_id = :user_id 
                 ORDER BY id DESC 
                 LIMIT 1'
            );
            $stmt->execute(['user_id' => $user['id']]);
            $reset = $stmt->fetch();

            if (!$reset) {
                throw new RuntimeException('Код восстановления не найден');
            }

            if (!hash_equals($reset['code'], $code)) {
                throw new RuntimeException('Неверный код восстановления');
            }

            $now = new \DateTimeImmutable('now');
            $expiresAt = new \DateTimeImmutable($reset['expires_at']);

            if ($now > $expiresAt) {
                throw new RuntimeException('Срок действия кода истёк');
            }

            $hash = password_hash($newPassword, PASSWORD_DEFAULT);

            $stmt = $this->pdo->prepare(
                'UPDATE users SET password_hash = :password_hash WHERE id = :id'
            );
            $stmt->execute([
                'password_hash' => $hash,
                'id' => $user['id'],
            ]);

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}




