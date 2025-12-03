<?php

declare(strict_types=1);

namespace Tests\PasswordRecovery;

use App\Services\AuthService;
use BaseTestCase;
use DateInterval;
use DateTimeImmutable;
use PDO;
use RuntimeException;

final class PasswordRecoveryTest extends BaseTestCase
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

        self::$pdo->exec(
            'CREATE TABLE IF NOT EXISTS password_resets (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                code VARCHAR(32) NOT NULL,
                expires_at DATETIME NOT NULL,
                created_at DATETIME NOT NULL,
                INDEX (user_id),
                CONSTRAINT fk_password_resets_user_id FOREIGN KEY (user_id) REFERENCES users(id)
                    ON DELETE CASCADE
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

    private function getLatestReset(string $phone): ?array
    {
        $stmt = self::$pdo->prepare(
            'SELECT pr.* 
             FROM password_resets pr
             JOIN users u ON u.id = pr.user_id
             WHERE u.phone = :phone
             ORDER BY pr.created_at DESC
             LIMIT 1'
        );
        $stmt->execute(['phone' => $phone]);

        $row = $stmt->fetch();

        return $row ?: null;
    }

    // === 15 тестов восстановления пароля ===

    public function testGenerateResetCodeForExistingUser(): void
    {
        $phone = '+79991234000';
        $this->createUser($phone, 'Qwerty1!');

        $code = $this->auth->generateResetCode($phone, new DateInterval('PT10M'));

        $this->assertNotEmpty($code);
        $reset = $this->getLatestReset($phone);
        $this->assertNotNull($reset);
        $this->assertSame($code, $reset['code']);
    }

    public function testGenerateResetCodeForNonExistingUserFails(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Пользователь не найден');

        $this->auth->generateResetCode('+79999999999', new DateInterval('PT10M'));
    }

    public function testResetPasswordWithValidCode(): void
    {
        $phone = '+79991234001';
        $this->createUser($phone, 'OldPass1!');

        $code = $this->auth->generateResetCode($phone, new DateInterval('PT10M'));

        $this->auth->resetPassword($phone, $code, 'NewPass1!', 'NewPass1!');

        $stmt = self::$pdo->prepare('SELECT password_hash FROM users WHERE phone = :phone');
        $stmt->execute(['phone' => $phone]);
        $row = $stmt->fetch();

        $this->assertTrue(password_verify('NewPass1!', $row['password_hash']));
    }

    public function testResetPasswordFailsWithWrongCode(): void
    {
        $phone = '+79991234002';
        $this->createUser($phone, 'OldPass1!');
        $this->auth->generateResetCode($phone, new DateInterval('PT10M'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Неверный код восстановления');

        $this->auth->resetPassword($phone, '000000', 'NewPass1!', 'NewPass1!');
    }

    public function testResetPasswordFailsWhenCodeExpired(): void
    {
        $phone = '+79991234003';
        $this->createUser($phone, 'OldPass1!');

        // Создаём "просроченный" код вручную
        $userId = $this->createUser('+79991234999', 'Dummy1!');
        $userId = $userId; // just to avoid warnings

        $ttl = new DateInterval('PT1S');
        $code = $this->auth->generateResetCode($phone, $ttl);

        // Насильно сдвигаем время истечения назад
        $reset = $this->getLatestReset($phone);
        $expiredAt = (new DateTimeImmutable($reset['expires_at']))->sub(new DateInterval('PT2H'));
        $stmt = self::$pdo->prepare('UPDATE password_resets SET expires_at = :expires_at WHERE id = :id');
        $stmt->execute([
            'expires_at' => $expiredAt->format('Y-m-d H:i:s'),
            'id' => $reset['id'],
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Срок действия кода истёк');

        $this->auth->resetPassword($phone, $code, 'NewPass1!', 'NewPass1!');
    }

    public function testResetPasswordFailsWhenNoCode(): void
    {
        $phone = '+79991234004';
        $this->createUser($phone, 'OldPass1!');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Код восстановления не найден');

        $this->auth->resetPassword($phone, '123456', 'NewPass1!', 'NewPass1!');
    }

    public function testResetPasswordFailsForUnknownUser(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Пользователь не найден');

        $this->auth->resetPassword('+79999999999', '123456', 'NewPass1!', 'NewPass1!');
    }

    public function testResetPasswordFailsWithWeakNewPassword(): void
    {
        $phone = '+79991234005';
        $this->createUser($phone, 'OldPass1!');
        $code = $this->auth->generateResetCode($phone, new DateInterval('PT10M'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Неверный формат нового пароля');

        $this->auth->resetPassword($phone, $code, 'weak', 'weak');
    }

    public function testResetPasswordFailsWhenNewPasswordsDoNotMatch(): void
    {
        $phone = '+79991234006';
        $this->createUser($phone, 'OldPass1!');
        $code = $this->auth->generateResetCode($phone, new DateInterval('PT10M'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Пароли не совпадают');

        $this->auth->resetPassword($phone, $code, 'NewPass1!', 'OtherPass1!');
    }

    public function testMultipleResetCodesUseLatest(): void
    {
        $phone = '+79991234007';
        $this->createUser($phone, 'OldPass1!');

        $this->auth->generateResetCode($phone, new DateInterval('PT10M'));
        $latestCode = $this->auth->generateResetCode($phone, new DateInterval('PT10M'));

        $this->auth->resetPassword($phone, $latestCode, 'NewPass1!', 'NewPass1!');

        $stmt = self::$pdo->prepare('SELECT password_hash FROM users WHERE phone = :phone');
        $stmt->execute(['phone' => $phone]);
        $row = $stmt->fetch();

        $this->assertTrue(password_verify('NewPass1!', $row['password_hash']));
    }

    public function testResetCodeIsSixDigits(): void
    {
        $phone = '+79991234008';
        $this->createUser($phone, 'OldPass1!');

        $code = $this->auth->generateResetCode($phone, new DateInterval('PT10M'));

        $this->assertSame(6, strlen($code));
        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
    }

    public function testResetPasswordDoesNotChangeOtherUsers(): void
    {
        $phone1 = '+79991234009';
        $phone2 = '+79991234010';
        $this->createUser($phone1, 'OldPass1!');
        $this->createUser($phone2, 'OldPass2!');

        $code = $this->auth->generateResetCode($phone1, new DateInterval('PT10M'));

        $this->auth->resetPassword($phone1, $code, 'NewPass1!', 'NewPass1!');

        $stmt = self::$pdo->prepare('SELECT phone, password_hash FROM users ORDER BY phone');
        $stmt->execute();
        $rows = $stmt->fetchAll();

        $this->assertTrue(password_verify('NewPass1!', $rows[0]['password_hash']));
        $this->assertTrue(password_verify('OldPass2!', $rows[1]['password_hash']));
    }

    public function testGenerateResetCodeNotTooSlow(): void
    {
        $phone = '+79991234011';
        $this->createUser($phone, 'OldPass1!');

        $start = microtime(true);
        $this->auth->generateResetCode($phone, new DateInterval('PT10M'));
        $duration = microtime(true) - $start;

        $this->assertLessThan(1.0, $duration, 'Генерация кода не должна быть слишком медленной');
    }

    public function testResetPasswordNotTooSlow(): void
    {
        $phone = '+79991234012';
        $this->createUser($phone, 'OldPass1!');
        $code = $this->auth->generateResetCode($phone, new DateInterval('PT10M'));

        $start = microtime(true);
        $this->auth->resetPassword($phone, $code, 'NewPass1!', 'NewPass1!');
        $duration = microtime(true) - $start;

        $this->assertLessThan(1.0, $duration, 'Сброс пароля не должен быть слишком медленным');
    }
}




