<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function redirect(string $path): void
{
    header("Location: {$path}");
    exit;
}

function set_flash(string $key, string $message, string $type = 'error'): void
{
    $_SESSION['flash'][$key] = ['message' => $message, 'type' => $type];
}

function get_flash(string $key): ?array
{
    if (!isset($_SESSION['flash'][$key])) {
        return null;
    }

    $value = $_SESSION['flash'][$key];
    unset($_SESSION['flash'][$key]);

    return $value;
}

function is_logged_in(): bool
{
    return isset($_SESSION['user']);
}

function require_login(): void
{
    if (!is_logged_in()) {
        set_flash('auth', 'Пожалуйста, войдите, чтобы продолжить.');
        redirect('index.php');
    }
}

function sanitize_phone(string $phone): string
{
    return preg_replace('/\D+/', '', $phone) ?? '';
}

function render_flash(?array $flash): string
{
    if (!$flash) {
        return '';
    }

    $class = $flash['type'] === 'success' ? 'alert-success' : 'alert-error';
    $message = htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8');

    return "<div class=\"alert {$class}\">{$message}</div>";
}

