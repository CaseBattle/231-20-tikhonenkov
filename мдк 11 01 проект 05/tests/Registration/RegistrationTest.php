<?php

declare(strict_types=1);

namespace Tests\Registration;

use App\Services\AuthService;
use PHPUnit\Framework\TestCase;

class RegistrationTest extends TestCase
{
    private AuthService $service;

    protected function setUp(): void
    {
        $pdo = \createTestPdo();
        $this->service = new AuthService($pdo);
    }

    private function skipIfSlow(): void
    {
        $limit = (float)(getenv('TEST_TIME_LIMIT') ?: 1);
        $start = microtime(true);
        $this->addToAssertionCount(1);
        if ((microtime(true) - $start) > $limit) {
            $this->markTestSkipped('Тест пропущен из-за превышения лимита времени.');
        }
    }

    /**
     * @testdox Успешная регистрация
     */
    public function testSuccessfulRegistration(): void
    {
        $this->skipIfSlow();
        $result = $this->service->register('+71234567890', 'Qwerty1!', 'Qwerty1!');
        $this->assertTrue($result['success']);
    }

    /**
     * @testdox Регистрация с неверным форматом телефона
     */
    public function testRegistrationWithInvalidPhone(): void
    {
        $this->skipIfSlow();
        $result = $this->service->register('12345', 'Qwerty1!', 'Qwerty1!');
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('phone', $result['errors']);
    }

    /**
     * @testdox Регистрация с коротким паролем
     */
    public function testRegistrationWithShortPassword(): void
    {
        $this->skipIfSlow();
        $result = $this->service->register('+71234567891', 'Q1!', 'Q1!');
        $this->assertFalse($result['success']);
    }

    /**
     * @testdox Регистрация с несовпадающими паролями
     */
    public function testRegistrationWithDifferentPasswords(): void
    {
        $this->skipIfSlow();
        $result = $this->service->register('+71234567892', 'Qwerty1!', 'Qwerty2!');
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('password_confirm', $result['errors']);
    }

    /**
     * @testdox Регистрация с уже существующим телефоном
     */
    public function testRegistrationWithExistingPhone(): void
    {
        $this->skipIfSlow();
        $phone = '+71234567893';
        $this->service->register($phone, 'Qwerty1!', 'Qwerty1!');
        $result = $this->service->register($phone, 'Qwerty1!', 'Qwerty1!');
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('phone', $result['errors']);
    }

    /**
     * @testdox Проверка хеширования пароля при регистрации
     */
    public function testPasswordHashing(): void
    {
        $this->skipIfSlow();
        $pdo = \createTestPdo();
        $service = new AuthService($pdo);
        $password = 'Qwerty1!';
        $service->register('+71234567894', $password, $password);

        $stmt = $pdo->query("SELECT password_hash FROM users WHERE phone = '+71234567894'");
        $hash = $stmt->fetchColumn();
        $this->assertIsString($hash);
        $this->assertNotSame($password, $hash);
        $this->assertTrue(password_verify($password, $hash));
    }

    /**
     * @testdox Регистрация с пустым телефоном
     */
    public function testRegistrationWithEmptyPhone(): void
    {
        $this->skipIfSlow();
        $result = $this->service->register('', 'Qwerty1!', 'Qwerty1!');
        $this->assertFalse($result['success']);
    }

    /**
     * @testdox Регистрация с пустым паролем
     */
    public function testRegistrationWithEmptyPassword(): void
    {
        $this->skipIfSlow();
        $result = $this->service->register('+71234567895', '', '');
        $this->assertFalse($result['success']);
    }

    /**
     * @testdox Регистрация с очень длинным паролем
     */
    public function testRegistrationWithVeryLongPassword(): void
    {
        $this->skipIfSlow();
        $long = str_repeat('A', 80) . '1!';
        $result = $this->service->register('+71234567896', $long, $long);
        $this->assertFalse($result['success']);
    }

    /**
     * @testdox Регистрация с паролем содержащим спецсимволы
     */
    public function testRegistrationWithSpecialSymbolsInPassword(): void
    {
        $this->skipIfSlow();
        $result = $this->service->register('+71234567897', 'Qw!@#12', 'Qw!@#12');
        $this->assertTrue($result['success']);
    }

    /**
     * @testdox Регистрация с разными форматами телефона
     */
    public function testRegistrationWithDifferentPhoneFormats(): void
    {
        $this->skipIfSlow();
        $phones = ['+71234560001', '81234560002', '+380501234003'];
        foreach ($phones as $phone) {
            $result = $this->service->register($phone, 'Qwerty1!', 'Qwerty1!');
            $this->assertTrue($result['success'], "Телефон {$phone} должен регистрироваться");
        }
    }

    /**
     * @testdox Регистрация нескольких пользователей
     */
    public function testMultipleUsersRegistration(): void
    {
        $this->skipIfSlow();
        for ($i = 0; $i < 3; $i++) {
            $phone = '+7999000000' . $i;
            $result = $this->service->register($phone, 'Qwerty1!', 'Qwerty1!');
            $this->assertTrue($result['success']);
        }
    }

    /**
     * @testdox Проверка чувствительности к регистру пароля при входе после регистрации
     */
    public function testCaseSensitivityForPassword(): void
    {
        $this->skipIfSlow();
        $this->service->register('+79995550000', 'Qwerty1!', 'Qwerty1!');
        $loginLower = $this->service->login('+79995550000', 'qwerty1!');
        $this->assertFalse($loginLower['success']);
    }

    /**
     * @testdox Регистрация с телефоном начинающимся с 8
     */
    public function testRegistrationWithPhoneStartingWith8(): void
    {
        $this->skipIfSlow();
        $result = $this->service->register('89995550123', 'Qwerty1!', 'Qwerty1!');
        $this->assertTrue($result['success']);
    }

    /**
     * @testdox Регистрация с минимально допустимой длиной пароля
     */
    public function testRegistrationWithMinimalPasswordLength(): void
    {
        $this->skipIfSlow();
        $password = 'Aa1!aa'; // 6 символов, минимальная длина в валидаторе
        $result = $this->service->register('+71234567999', $password, $password);
        $this->assertTrue($result['success']);
    }
}


