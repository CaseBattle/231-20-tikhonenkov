<?php

declare(strict_types=1);

namespace App\Validation;

/**
 * Класс валидации данных форм аутентификации.
 */
class AuthValidator
{
    /**
     * Проверка формата телефона.
     *
     * Допускаются форматы:
     * - +7XXXXXXXXXX
     * - 8XXXXXXXXXX
     * - международные номера вида +XXXXXXXXXXXX...
     */
    public static function isValidPhone(string $phone): bool
    {
        $phone = trim($phone);

        // Допускаем пробелы, дефисы и скобки, но убираем их перед проверкой
        $normalized = preg_replace('/[^\d+]/', '', $phone) ?? '';

        // Формат +7XXXXXXXXXX
        if (preg_match('/^\+7\d{10}$/', $normalized)) {
            return true;
        }

        // Формат 8XXXXXXXXXX (российский)
        if (preg_match('/^8\d{10}$/', $normalized)) {
            return true;
        }

        // Международный формат: +[от 11 до 15 цифр]
        if (preg_match('/^\+\d{11,15}$/', $normalized)) {
            return true;
        }

        return false;
    }

    /**
     * Проверка сложности пароля.
     *
     * Требования (примерные, можно адаптировать под ваш проект 03):
     * - длина от 8 до 64 символов;
     * - хотя бы одна заглавная буква;
     * - хотя бы одна строчная буква;
     * - хотя бы одна цифра;
     * - хотя бы один специальный символ.
     */
    public static function isValidPassword(string $password): bool
    {
        $length = mb_strlen($password);
        if ($length < 8 || $length > 64) {
            return false;
        }

        if (!preg_match('/[A-ZА-Я]/u', $password)) {
            return false;
        }

        if (!preg_match('/[a-zа-я]/u', $password)) {
            return false;
        }

        if (!preg_match('/\d/', $password)) {
            return false;
        }

        if (!preg_match('/[\W_]/u', $password)) {
            return false;
        }

        return true;
    }

    /**
     * Проверка повторного ввода пароля.
     */
    public static function isPasswordConfirmationValid(string $password, string $confirmation): bool
    {
        return hash_equals($password, $confirmation);
    }
}




