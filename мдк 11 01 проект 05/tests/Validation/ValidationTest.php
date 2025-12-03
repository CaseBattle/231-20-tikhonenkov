<?php

declare(strict_types=1);

namespace Tests\Validation;

use App\Validation\AuthValidator;
use BaseTestCase;

final class ValidationTest extends BaseTestCase
{
    // === 10 тестов валидации ===

    public function testValidRussianPhonePlusSeven(): void
    {
        $this->assertTrue(AuthValidator::isValidPhone('+79991234567'));
    }

    public function testValidRussianPhoneEight(): void
    {
        $this->assertTrue(AuthValidator::isValidPhone('89991234567'));
    }

    public function testValidInternationalPhone(): void
    {
        $this->assertTrue(AuthValidator::isValidPhone('+4915112345678'));
    }

    public function testInvalidPhoneTooShort(): void
    {
        $this->assertFalse(AuthValidator::isValidPhone('+7999'));
    }

    public function testInvalidPhoneLetters(): void
    {
        $this->assertFalse(AuthValidator::isValidPhone('+7ABC1234567'));
    }

    public function testValidPasswordStrong(): void
    {
        $this->assertTrue(AuthValidator::isValidPassword('Qwerty1!'));
    }

    public function testInvalidPasswordTooShort(): void
    {
        $this->assertFalse(AuthValidator::isValidPassword('Qw1!a'));
    }

    public function testInvalidPasswordNoUppercase(): void
    {
        $this->assertFalse(AuthValidator::isValidPassword('qwerty1!'));
    }

    public function testInvalidPasswordNoDigit(): void
    {
        $this->assertFalse(AuthValidator::isValidPassword('Qwerty!!'));
    }

    public function testPasswordConfirmation(): void
    {
        $this->assertTrue(
            AuthValidator::isPasswordConfirmationValid('Qwerty1!', 'Qwerty1!')
        );
        $this->assertFalse(
            AuthValidator::isPasswordConfirmationValid('Qwerty1!', 'Qwerty2!')
        );
    }
}




