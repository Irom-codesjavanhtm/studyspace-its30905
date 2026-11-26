<?php
/**
 * Application entry: config, session, and shared includes for every page.
 * Not a CSS/UI framework — styling is in assets/css/style.css only.
 */
declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));

$GLOBALS['config'] = require APP_ROOT . '/config.php';
date_default_timezone_set($GLOBALS['config']['timezone']);

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/queries.php';
require_once __DIR__ . '/validation.php';
require_once __DIR__ . '/indent.php';
require_once __DIR__ . '/components.php';

user_ensure_admin();