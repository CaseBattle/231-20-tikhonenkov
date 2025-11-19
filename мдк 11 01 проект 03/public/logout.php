<?php

require_once __DIR__ . '/../includes/bootstrap.php';

session_unset();
session_destroy();

session_start();
set_flash('auth', 'Вы вышли из аккаунта.', 'success');
redirect('index.php');

