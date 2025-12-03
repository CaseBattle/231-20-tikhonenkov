<?php

declare(strict_types=1);

namespace Tests\Registration;

use App\Services\AuthService;
use BaseTestCase;
use PDO;
use RuntimeException;

final class RegistrationTest extends BaseTestCase
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
        // Минимальная схема для тестов регистрации
        self::$pdo->exec(
            'CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                phone VARCHAR(32) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                created_at DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;'
        );
    }

    // === 15 тестов регистрации ===

    public function testSuccessfulRegistration(): void
    {
        $id = $this->auth->register('+79991234567', 'Qwerty1!', 'Qwerty1!');
        $this->assertGreaterThan(0, $id);
    }

    public function testRegistrationFailsWithInvalidPhone(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Неверный формат телефона');
        $this->auth->register('12345', 'Qwerty1!', 'Qwerty1!');
    }

    public function testRegistrationFailsWithWeakPassword(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Неверный формат пароля');
        $this->auth->register('+79991234567', 'weak', 'weak');
    }

    public function testRegistrationFailsWhenPasswordsDoNotMatch(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Пароли не совпадают');
        $this->auth->register('+79991234567', 'Qwerty1!', 'Qwerty2!');
    }

    public function testPhoneMustBeUnique(): void
    {
        $this->auth->register('+79991234567', 'Qwerty1!', 'Qwerty1!');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Пользователь с таким телефоном уже существует');

        $this->auth->register('+79991234567', 'Qwerty1!', 'Qwerty1!');
    }

    public function testBorderPhoneWithSpacesAndDashes(): void
    {
        $id = $this->auth->register('+7 (999) 123-45-67', 'Qwerty1!', 'Qwerty1!');
        $this->assertGreaterThan(0, $id);
    }

    public function testPasswordMinLengthBoundary(): void
    {
        $password = 'Aa1!' . str_repeat('x', 4); // 8 символов
        $id = $this->auth->register('+79991234568', $password, $password);
        $this->assertGreaterThan(0, $id);
    }

    public function testPasswordTooLong(): void
    {
        $password = 'Aa1!' . str_repeat('x', 100);

        $this->expectException(RuntimeException::class);
        $this->auth->register('+79991234569', $password, $password);
    }

    public function testRegistrationStoresPasswordHash(): void
    {
        $phone = '+79991234570';
        $this->auth->register($phone, 'Qwerty1!', 'Qwerty1!');

        $stmt = self::$pdo->prepare('SELECT password_hash FROM users WHERE phone = :phone');
        $stmt->execute(['phone' => $phone]);
        $row = $stmt->fetch();

        $this->assertNotFalse($row);
        $this->assertTrue(password_verify('Qwerty1!', $row['password_hash']));
    }

    public function testRegistrationTrimsPhone(): void
    {
        $id = $this->auth->register('  +79991234571  ', 'Qwerty1!', 'Qwerty1!');
        $this->assertGreaterThan(0, $id);
    }

    public function testRegistrationWithInternationalPhone(): void
    {
        $id = $this->auth->register('+4915112345678', 'Qwerty1!', 'Qwerty1!');
        $this->assertGreaterThan(0, $id);
    }

    public function testRegistrationWithCyrillicPassword(): void
    {
        $password = 'Пароль1!';
        $id = $this->auth->register('+79991234572', $password, $password);
        $this->assertGreaterThan(0, $id);
    }

    public function testRegistrationFailsOnEmptyPassword(): void
    {
        $this->expectException(RuntimeException::class);
        $this->auth->register('+79991234573', '', '');
    }

    public function testRegistrationFailsOnEmptyPhone(): void
    {
        $this->expectException(RuntimeException::class);
        $this->auth->register('', 'Qwerty1!', 'Qwerty1!');
    }

    public function testRegistrationFailsOnNullLikePhone(): void
    {
        $this->expectException(RuntimeException::class);
        $this->auth->register('   ', 'Qwerty1!', 'Qwerty1!');
    }
}




