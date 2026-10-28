<?php
declare(strict_types=1);

define('ROOT', dirname(__DIR__));
const DATA_DIR = ROOT . '/data';
const UPLOAD_DIR = ROOT . '/uploads';

function env(string $key, string $default = ''): string
{
    static $file = null;
    if ($file === null) { // fallback: plik .env (uruchamianie lokalne bez Dockera)
        $file = [];
        if (is_readable(ROOT . '/.env')) {
            foreach (file(ROOT . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                if ($line[0] !== '#' && str_contains($line, '=')) {
                    [$k, $val] = explode('=', $line, 2);
                    $file[trim($k)] = trim($val, " \t\"'");
                }
            }
        }
    }
    $v = getenv($key);
    if ($v === false || $v === '') {
        $v = $file[$key] ?? '';
    }
    return $v === '' ? $default : $v;
}

mb_internal_encoding('UTF-8');
date_default_timezone_set('Europe/Warsaw');

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/images.php';
require_once __DIR__ . '/mailer.php';
require_once __DIR__ . '/google.php';

if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
    session_name('ekosid');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
