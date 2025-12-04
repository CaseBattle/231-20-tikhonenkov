<?php

namespace App\Validation;

class AuthValidator
{
    private const MIN_PASSWORD_LENGTH = 6;
    private const MAX_PASSWORD_LENGTH = 64;

    /**
     * Проверка телефона (форматы: +7, 8, международный).
     */
    public function validatePhone(string $phone): array
    {
        $errors = [];
        $trimmed = trim($phone);

        if ($trimmed === '') {
            $errors['phone'] = 'Телефон обязателен';
            return $errors;
        }

        // Разрешаем форматы: +7XXXXXXXXXX, 8XXXXXXXXXX, 7XXXXXXXXXX, международный +XXXXXXXXXXX...
        $normalized = preg_replace('/[\s\-()]/', '', $trimmed);

        if (!preg_match('/^\+?[0-9]{10,15}$/', $normalized)) {
            $errors['phone'] = 'Некорректный формат телефона';
        }

        return $errors;
    }

    /**
     * Базовая валидация пароля.
     */
    public function validatePassword(string $password): array
    {
        $errors = [];

        if ($password === '') {
            $errors['password'] = 'Пароль обязателен';
            return $errors;
        }

        $length = mb_strlen($password);
        if ($length < self::MIN_PASSWORD_LENGTH) {
            $errors['password'] = 'Пароль слишком короткий';
        } elseif ($length > self::MAX_PASSWORD_LENGTH) {
            $errors['password'] = 'Пароль слишком длинный';
        }

        if (!preg_match('/[0-9]/', $password)) {
            $errors['password_digits'] = 'Пароль должен содержать цифры';
        }

        if (!preg_match('/[A-ZА-Я]/u', $password)) {
            $errors['password_upper'] = 'Пароль должен содержать заглавные буквы';
        }

        if (!preg_match('/[a-zа-я]/u', $password)) {
            $errors['password_lower'] = 'Пароль должен содержать строчные буквы';
        }

        if (!preg_match('/[\W_]/u', $password)) {
            $errors['password_special'] = 'Пароль должен содержать спецсимволы';
        }

        return $errors;
    }

    public function validateRegistration(string $phone, string $password, string $passwordConfirm): array
    {
        $errors = $this->validatePhone($phone);
        $passwordErrors = $this->validatePassword($password);
        $errors = array_merge($errors, $passwordErrors);

        if ($password !== $passwordConfirm) {
            $errors['password_confirm'] = 'Пароли не совпадают';
        }

        return $errors;
    }

    public function validateLogin(string $phone, string $password): array
    {
        $errors = $this->validatePhone($phone);
        if ($password === '') {
            $errors['password'] = 'Пароль обязателен';
        }
        return $errors;
    }

    public function validatePasswordReset(string $password, string $passwordConfirm): array
    {
        $errors = $this->validatePassword($password);
        if ($password !== $passwordConfirm) {
            $errors['password_confirm'] = 'Пароли не совпадают';
        }
        return $errors;
    }
}







