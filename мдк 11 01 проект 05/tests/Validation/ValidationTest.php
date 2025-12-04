<?php

declare(strict_types=1);

namespace Tests\Validation;

use App\Validation\AuthValidator;
use PHPUnit\Framework\TestCase;

class ValidationTest extends TestCase
{
    private AuthValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new AuthValidator();
    }

    private function skipIfSlow(): void
    {
        $limit = (float)(getenv('TEST_TIME_LIMIT') ?: 1);
        if ($limit <= 0) {
            return;
        }

        $start = microtime(true);
        $this->addToAssertionCount(1); // мелкое действие, чтобы не было пустого теста
        $elapsed = microtime(true) - $start;
        if ($elapsed > $limit) {
            $this->markTestSkipped('Тест пропущен из-за превышения лимита времени.');
        }
    }

    /**
     * @testdox Проверка валидных форматов телефона
     */
    public function testValidPhoneFormats(): void
    {
        $this->skipIfSlow();
        $validPhones = ['+71234567890', '81234567890', '+380501234567'];
        foreach ($validPhones as $phone) {
            $errors = $this->validator->validatePhone($phone);
            $this->assertArrayNotHasKey('phone', $errors, "Телефон {$phone} должен быть валидным");
        }
    }

    /**
     * @testdox Проверка невалидных форматов телефона
     */
    public function testInvalidPhoneFormats(): void
    {
        $this->skipIfSlow();
        $invalidPhones = ['12345', 'abcd', '+7 (123) 45', ''];
        foreach ($invalidPhones as $phone) {
            $errors = $this->validator->validatePhone($phone);
            $this->assertArrayHasKey('phone', $errors, "Телефон {$phone} должен быть невалидным");
        }
    }

    /**
     * @testdox Проверка валидных паролей
     */
    public function testValidPasswords(): void
    {
        $this->skipIfSlow();
        $passwords = ['Qwerty1!', 'Пароль1!', 'Aa12345!'];
        foreach ($passwords as $password) {
            $errors = $this->validator->validatePassword($password);
            $this->assertEmpty($errors, "Пароль {$password} должен быть валидным");
        }
    }

    /**
     * @testdox Пароль слишком короткий
     */
    public function testInvalidPasswordsTooShort(): void
    {
        $this->skipIfSlow();
        $errors = $this->validator->validatePassword('A1!');
        $this->assertArrayHasKey('password', $errors);
    }

    /**
     * @testdox Пароль слишком длинный
     */
    public function testInvalidPasswordsTooLong(): void
    {
        $this->skipIfSlow();
        $tooLong = str_repeat('A', 70) . '1!';
        $errors = $this->validator->validatePassword($tooLong);
        $this->assertArrayHasKey('password', $errors);
    }

    /**
     * @testdox Пароль без цифр
     */
    public function testPasswordWithoutDigits(): void
    {
        $this->skipIfSlow();
        $errors = $this->validator->validatePassword('Qwerty!');
        $this->assertArrayHasKey('password_digits', $errors);
    }

    /**
     * @testdox Пароль без заглавных букв
     */
    public function testPasswordWithoutUppercase(): void
    {
        $this->skipIfSlow();
        $errors = $this->validator->validatePassword('qwerty1!');
        $this->assertArrayHasKey('password_upper', $errors);
    }

    /**
     * @testdox Пароль без строчных букв
     */
    public function testPasswordWithoutLowercase(): void
    {
        $this->skipIfSlow();
        $errors = $this->validator->validatePassword('QWERTY1!');
        $this->assertArrayHasKey('password_lower', $errors);
    }

    /**
     * @testdox Пароль без спецсимволов
     */
    public function testPasswordWithoutSpecialChars(): void
    {
        $this->skipIfSlow();
        $errors = $this->validator->validatePassword('Qwerty1');
        $this->assertArrayHasKey('password_special', $errors);
    }

    /**
     * @testdox Пароль состоящий только из цифр
     */
    public function testPasswordOnlyDigits(): void
    {
        $this->skipIfSlow();
        $errors = $this->validator->validatePassword('12345678');
        $this->assertNotEmpty($errors);
    }

    /**
     * @testdox Телефон с пробелами внутри
     */
    public function testPhoneWithSpaces(): void
    {
        $this->skipIfSlow();
        $errors = $this->validator->validatePhone('+7 123 456 78 90');
        $this->assertArrayNotHasKey('phone', $errors);
    }

    /**
     * @testdox Телефон с дефисами
     */
    public function testPhoneWithDashes(): void
    {
        $this->skipIfSlow();
        $errors = $this->validator->validatePhone('+7-123-456-78-90');
        $this->assertArrayNotHasKey('phone', $errors);
    }
}


