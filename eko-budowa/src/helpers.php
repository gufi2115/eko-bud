<?php
declare(strict_types=1);

function e(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function is_https(): bool
{
    return (($_SERVER['HTTPS'] ?? '') === 'on')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
}

function client_ip(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    // Za zaufanym proxy (Caddy w sieci Dockera) bierzemy IP z X-Forwarded-For
    $isLocal = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    if ($isLocal && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $first = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
        if (filter_var($first, FILTER_VALIDATE_IP)) {
            return $first;
        }
    }
    return $ip;
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function flash(?string $msg = null, string $type = 'ok'): ?array
{
    if ($msg !== null) {
        $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
        return null;
    }
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

/** Limity zapytań: zwraca liczbę zdarzeń w oknie czasowym. */
function rate_count(string $bucket, string $ip, int $windowSec): int
{
    $st = db()->prepare('SELECT COUNT(*) FROM rate_limits WHERE bucket=? AND ip=? AND ts>?');
    $st->execute([$bucket, $ip, time() - $windowSec]);
    return (int)$st->fetchColumn();
}

function rate_add(string $bucket, string $ip): void
{
    db()->prepare('INSERT INTO rate_limits(bucket, ip, ts) VALUES(?,?,?)')->execute([$bucket, $ip, time()]);
    db()->prepare('DELETE FROM rate_limits WHERE ts<?')->execute([time() - 86400]);
}

function rate_clear(string $bucket, string $ip): void
{
    db()->prepare('DELETE FROM rate_limits WHERE bucket=? AND ip=?')->execute([$bucket, $ip]);
}
