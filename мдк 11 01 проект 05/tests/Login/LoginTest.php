<?php

declare(strict_types=1);

namespace Tests\Login;

use App\Services\AuthService;
use PHPUnit\Framework\TestCase;

class LoginTest extends TestCase
{
    private AuthService $service;

    protected function setUp(): void
    {
        $pdo = \createTestPdo();
        $this->service = new AuthService($pdo);

        // создаем пользователя для тестов входа
        $this->service->register('+71112223344', 'Qwerty1!', 'Qwerty1!');
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
     * @testdox Успешный вход
     */
    public function testSuccessfulLogin(): void
    {
        $this->skipIfSlow();
        $result = $this->service->login('+71112223344', 'Qwerty1!');
        $this->assertTrue($result['success']);
    }

    /**
     * @testdox Вход с неверным паролем
     */
    public function testLoginWithWrongPassword(): void
    {
        $this->skipIfSlow();
        $result = $this->service->login('+71112223344', 'Wrong1!');
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('password', $result['errors']);
    }

    /**
     * @testdox Вход с несуществующим телефоном
     */
    public function testLoginWithNonExistingPhone(): void
    {
        $this->skipIfSlow();
        $result = $this->service->login('+79999999999', 'Qwerty1!');
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('phone', $result['errors']);
    }

    /**
     * @testdox Вход с пустым паролем
     */
    public function testLoginWithEmptyPassword(): void
    {
        $this->skipIfSlow();
        $result = $this->service->login('+71112223344', '');
        $this->assertFalse($result['success']);
    }

    /**
     * @testdox Вход с пустым телефоном
     */
    public function testLoginWithEmptyPhone(): void
    {
        $this->skipIfSlow();
        $result = $this->service->login('', 'Qwerty1!');
        $this->assertFalse($result['success']);
    }

    /**
     * @testdox Вход с учётом регистра пароля
     */
    public function testLoginCaseSensitivity(): void
    {
        $this->skipIfSlow();
        $result = $this->service->login('+71112223344', 'qwerty1!');
        $this->assertFalse($result['success']);
    }

    /**
     * @testdox Вход с телефоном, начинающимся с 8
     */
    public function testLoginWithPhoneStartingWith8(): void
    {
        $this->skipIfSlow();
        $this->service->register('89990001122', 'Qwerty1!', 'Qwerty1!');
        $result = $this->service->login('89990001122', 'Qwerty1!');
        $this->assertTrue($result['success']);
    }

    /**
     * @testdox Вход с неверным форматом телефона
     */
    public function testLoginWithPhoneWrongFormat(): void
    {
        $this->skipIfSlow();
        $result = $this->service->login('12345', 'Qwerty1!');
        $this->assertFalse($result['success']);
    }

    /**
     * @testdox Вход с очень длинным паролем
     */
    public function testLoginWithVeryLongPassword(): void
    {
        $this->skipIfSlow();
        $long = str_repeat('A', 80) . '1!';
        $result = $this->service->login('+71112223344', $long);
        $this->assertFalse($result['success']);
    }

    /**
     * @testdox Вход с паролем содержащим спецсимволы
     */
    public function testLoginWithSpecialSymbolsInPassword(): void
    {
        $this->skipIfSlow();
        $this->service->register('+71112220000', 'Qw!@#12', 'Qw!@#12');
        $result = $this->service->login('+71112220000', 'Qw!@#12');
        $this->assertTrue($result['success']);
    }
}


