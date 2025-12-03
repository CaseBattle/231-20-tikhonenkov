<?php

declare(strict_types=1);

namespace Tests\Login;

use App\Services\AuthService;
use BaseTestCase;
use PDO;
use RuntimeException;

final class LoginTest extends BaseTestCase
{
    private AuthService $auth;

    protected function setUp(): void
    {
        parent::setUp();
        $this->auth = new AuthService(self::$pdo ?? new PDO('sqlite::memory:'));
        $this->prepareSchema();
    }

    private function prepareSchema(): void
    {
        self::$pdo->exec(
            'CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                phone VARCHAR(32) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                created_at DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;'
        );
    }

    private function createUser(string $phone, string $password): int
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = self::$pdo->prepare(
            'INSERT INTO users (phone, password_hash, created_at) VALUES (:phone, :password_hash, NOW())'
        );
        $stmt->execute([
            'phone' => $phone,
            'password_hash' => $hash,
        ]);

        return (int)self::$pdo->lastInsertId();
    }

    // === 10 тестов входа ===

    public function testSuccessfulLogin(): void
    {
        $phone = '+79991230000';
        $password = 'Qwerty1!';
        $userId = $this->createUser($phone, $password);

        $loggedInId = $this->auth->login($phone, $password);

        $this->assertSame($userId, $loggedInId);
    }

    public function testLoginFailsWithWrongPassword(): void
    {
        $phone = '+79991230001';
        $this->createUser($phone, 'Qwerty1!');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Неверный пароль');

        $this->auth->login($phone, 'WrongPass1!');
    }

    public function testLoginFailsWhenUserDoesNotExist(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Пользователь не найден');

        $this->auth->login('+79991239999', 'Qwerty1!');
    }

    public function testLoginCaseSensitivePassword(): void
    {
        $phone = '+79991230002';
        $this->createUser($phone, 'Qwerty1!');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Неверный пароль');

        $this->auth->login($phone, 'qwerty1!');
    }

    public function testLoginWithTrimmedPhone(): void
    {
        $phone = '+79991230003';
        $password = 'Qwerty1!';
        $userId = $this->createUser($phone, $password);

        $loggedInId = $this->auth->login('  +79991230003  ', $password);

        $this->assertSame($userId, $loggedInId);
    }

    public function testLoginWithInternationalPhone(): void
    {
        $phone = '+4915112345678';
        $password = 'Qwerty1!';
        $userId = $this->createUser($phone, $password);

        $loggedInId = $this->auth->login($phone, $password);

        $this->assertSame($userId, $loggedInId);
    }

    public function testLoginFailsWithEmptyPassword(): void
    {
        $phone = '+79991230004';
        $this->createUser($phone, 'Qwerty1!');

        $this->expectException(RuntimeException::class);
        $this->auth->login($phone, '');
    }

    public function testLoginFailsWithEmptyPhone(): void
    {
        $this->expectException(RuntimeException::class);
        $this->auth->login('', 'Qwerty1!');
    }

    public function testLoginFailsWithNullLikePhone(): void
    {
        $this->expectException(RuntimeException::class);
        $this->auth->login('   ', 'Qwerty1!');
    }

    public function testLoginMultipleTimes(): void
    {
        $phone = '+79991230005';
        $password = 'Qwerty1!';
        $userId = $this->createUser($phone, $password);

        $this->assertSame($userId, $this->auth->login($phone, $password));
        $this->assertSame($userId, $this->auth->login($phone, $password));
    }
}




