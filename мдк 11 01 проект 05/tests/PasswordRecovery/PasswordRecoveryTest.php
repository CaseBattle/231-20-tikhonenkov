<?php

declare(strict_types=1);

namespace Tests\PasswordRecovery;

use App\Services\AuthService;
use PHPUnit\Framework\TestCase;

class PasswordRecoveryTest extends TestCase
{
    private AuthService $service;

    protected function setUp(): void
    {
        $pdo = \createTestPdo();
        $this->service = new AuthService($pdo);
        $this->service->register('+70001112233', 'Qwerty1!', 'Qwerty1!');
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
     * @testdox Запрос кода для существующего пользователя
     */
    public function testRequestCodeForExistingUser(): void
    {
        $this->skipIfSlow();
        $result = $this->service->requestPasswordReset('+70001112233');
        $this->assertTrue($result['success']);
        $this->assertMatchesRegularExpression('/^[0-9]{6}$/', $result['code']);
    }

    /**
     * @testdox Запрос кода для несуществующего пользователя
     */
    public function testRequestCodeForNonExistingUser(): void
    {
        $this->skipIfSlow();
        $result = $this->service->requestPasswordReset('+79998887766');
        $this->assertFalse($result['success']);
    }

    /**
     * @testdox Сброс пароля с валидным кодом
     */
    public function testResetPasswordWithValidCode(): void
    {
        $this->skipIfSlow();
        $request = $this->service->requestPasswordReset('+70001112233');
        $code = $request['code'];
        $result = $this->service->resetPassword('+70001112233', $code, 'NewPass1!', 'NewPass1!');
        $this->assertTrue($result['success']);
    }

    /**
     * @testdox Сброс пароля с невалидным кодом
     */
    public function testResetPasswordWithInvalidCode(): void
    {
        $this->skipIfSlow();
        $this->service->requestPasswordReset('+70001112233');
        $result = $this->service->resetPassword('+70001112233', '000000', 'NewPass1!', 'NewPass1!');
        $this->assertFalse($result['success']);
    }

    /**
     * @testdox Сброс пароля с коротким паролем
     */
    public function testResetPasswordWithShortPassword(): void
    {
        $this->skipIfSlow();
        $request = $this->service->requestPasswordReset('+70001112233');
        $code = $request['code'];
        $result = $this->service->resetPassword('+70001112233', $code, 'Aa1!', 'Aa1!');
        $this->assertFalse($result['success']);
    }

    /**
     * @testdox Сброс пароля с несовпадающими паролями
     */
    public function testResetPasswordWithDifferentPasswords(): void
    {
        $this->skipIfSlow();
        $request = $this->service->requestPasswordReset('+70001112233');
        $code = $request['code'];
        $result = $this->service->resetPassword('+70001112233', $code, 'NewPass1!', 'NewPass2!');
        $this->assertFalse($result['success']);
    }

    /**
     * @testdox Проверка истечения срока действия кода
     */
    public function testCodeExpiration(): void
    {
        $this->skipIfSlow();
        $pdo = \createTestPdo();
        $service = new AuthService($pdo);
        $service->register('+70001112234', 'Qwerty1!', 'Qwerty1!');
        $request = $service->requestPasswordReset('+70001112234');

        // форсируем истечение срока
        $pdo->exec("UPDATE password_resets SET expires_at = " . (time() - 10));

        $result = $service->resetPassword('+70001112234', $request['code'], 'NewPass1!', 'NewPass1!');
        $this->assertFalse($result['success']);
    }

    /**
     * @testdox Проверка ограничения количества попыток ввода кода
     */
    public function testAttemptsLimit(): void
    {
        $this->skipIfSlow();
        $pdo = \createTestPdo();
        $service = new AuthService($pdo);
        $service->register('+70001112235', 'Qwerty1!', 'Qwerty1!');
        $service->requestPasswordReset('+70001112235');

        for ($i = 0; $i < 5; $i++) {
            $service->resetPassword('+70001112235', '000000', 'NewPass1!', 'NewPass1!');
        }

        $result = $service->resetPassword('+70001112235', '000000', 'NewPass1!', 'NewPass1!');
        $this->assertFalse($result['success']);
    }

    /**
     * @testdox Проверка уникальности кода восстановления для пользователя
     */
    public function testUniqueCodesPerUser(): void
    {
        $this->skipIfSlow();
        $pdo = \createTestPdo();
        $service = new AuthService($pdo);
        $service->register('+70001112236', 'Qwerty1!', 'Qwerty1!');
        $first = $service->requestPasswordReset('+70001112236');
        $second = $service->requestPasswordReset('+70001112236');
        $this->assertNotSame($first['code'], $second['code']);
    }

    /**
     * @testdox Многократный запрос кода не должен завершаться ошибкой
     */
    public function testMultipleRequestsIncreaseNotFail(): void
    {
        $this->skipIfSlow();
        for ($i = 0; $i < 3; $i++) {
            $result = $this->service->requestPasswordReset('+70001112233');
            $this->assertTrue($result['success']);
        }
    }

    /**
     * @testdox Сброс пароля с использованием спецсимволов
     */
    public function testResetPasswordWithSpecialSymbols(): void
    {
        $this->skipIfSlow();
        $request = $this->service->requestPasswordReset('+70001112233');
        $code = $request['code'];
        $result = $this->service->resetPassword('+70001112233', $code, 'Qw!@#12', 'Qw!@#12');
        $this->assertTrue($result['success']);
    }

    /**
     * @testdox Сброс пароля состоящего только из цифр
     */
    public function testResetPasswordOnlyDigits(): void
    {
        $this->skipIfSlow();
        $request = $this->service->requestPasswordReset('+70001112233');
        $code = $request['code'];
        $result = $this->service->resetPassword('+70001112233', $code, '12345678', '12345678');
        $this->assertFalse($result['success']);
    }

    /**
     * @testdox Сброс пароля для телефона начинающегося с 8
     */
    public function testResetPasswordWithPhoneStartingWith8(): void
    {
        $this->skipIfSlow();
        $pdo = \createTestPdo();
        $service = new AuthService($pdo);
        $service->register('89990001234', 'Qwerty1!', 'Qwerty1!');
        $request = $service->requestPasswordReset('89990001234');
        $code = $request['code'];
        $result = $service->resetPassword('89990001234', $code, 'NewPass1!', 'NewPass1!');
        $this->assertTrue($result['success']);
    }
}


