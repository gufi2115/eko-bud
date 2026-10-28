<?php
declare(strict_types=1);

const LOGIN_MAX_FAILS = 5;
const LOGIN_WINDOW = 900; // 15 minut

function admin_exists(): bool
{
    return (int)db()->query('SELECT COUNT(*) FROM admins')->fetchColumn() > 0;
}

function create_admin(string $login, string $password): void
{
    db()->prepare('INSERT INTO admins(login, password_hash) VALUES(?,?)')
        ->execute([$login, password_hash($password, PASSWORD_DEFAULT)]);
}

function login_blocked(): bool
{
    return rate_count('login', client_ip(), LOGIN_WINDOW) >= LOGIN_MAX_FAILS;
}

/** Zwraca null przy sukcesie albo komunikat błędu. */
function admin_attempt(string $login, string $password): ?string
{
    $ip = client_ip();
    if (login_blocked()) {
        return 'Zbyt wiele nieudanych prób. Spróbuj ponownie za 15 minut.';
    }
    $st = db()->prepare('SELECT id, password_hash FROM admins WHERE login=?');
    $st->execute([trim($login)]);
    $row = $st->fetch();
    // stały czas działania także dla nieistniejącego loginu
    $hash = $row['password_hash'] ?? '$2y$10$usesomesillystringforsalt$2y$10$abcdefghijklmnopqrstuv';
    if ($row && password_verify($password, $hash)) {
        rate_clear('login', $ip);
        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int)$row['id'];
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
        if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
            db()->prepare('UPDATE admins SET password_hash=? WHERE id=?')
                ->execute([password_hash($password, PASSWORD_DEFAULT), $row['id']]);
        }
        return null;
    }
    password_verify($password, $hash);
    rate_add('login', $ip);
    return 'Nieprawidłowy login lub hasło.';
}

function admin_logged_in(): bool
{
    return !empty($_SESSION['admin_id']);
}

function require_admin(): void
{
    if (!admin_exists()) {
        redirect('/admin/install.php');
    }
    if (!admin_logged_in()) {
        redirect('/admin/login.php');
    }
}

function admin_logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
