<?php
declare(strict_types=1);

/** Minimalny klient SMTP (SSL na 465 lub STARTTLS na 587/25, AUTH LOGIN). Zwraca null = OK albo treść błędu. */
function mail_send(string $to, string $subject, string $body, string $replyTo = ''): ?string
{
    $host = env('SMTP_HOST');
    $port = (int)env('SMTP_PORT', '587');
    $user = env('SMTP_USER');
    $pass = env('SMTP_PASS');
    if ($host === '') {
        return 'SMTP nie jest skonfigurowany (SMTP_HOST).';
    }
    $clean = static fn(string $s): string => trim(str_replace(["\r", "\n", '<', '>'], '', $s));
    $from = str_contains($user, '@') ? $user : env('MAIL_TO');
    $to = $clean($to);
    $replyTo = $clean($replyTo);

    $fp = null;
    try {
        $fp = @stream_socket_client(($port === 465 ? 'ssl://' : 'tcp://') . "$host:$port", $en, $es, 15);
        if (!$fp) {
            throw new RuntimeException("Brak połączenia z $host:$port ($es)");
        }
        stream_set_timeout($fp, 15);
        smtp_cmd($fp, '', [220]);
        $name = parse_url(env('SITE_URL', 'http://localhost'), PHP_URL_HOST) ?: 'localhost';
        smtp_cmd($fp, "EHLO $name", [250]);
        if ($port !== 465 && $user !== '') {
            smtp_cmd($fp, 'STARTTLS', [220]);
            if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT)) {
                throw new RuntimeException('Nie udało się włączyć TLS.');
            }
            smtp_cmd($fp, "EHLO $name", [250]);
        }
        if ($user !== '') {
            smtp_cmd($fp, 'AUTH LOGIN', [334]);
            smtp_cmd($fp, base64_encode($user), [334]);
            smtp_cmd($fp, base64_encode($pass), [235]);
        }
        smtp_cmd($fp, "MAIL FROM:<$from>", [250]);
        smtp_cmd($fp, "RCPT TO:<$to>", [250, 251]);
        smtp_cmd($fp, 'DATA', [354]);

        $h = [
            'Date: ' . date('r'),
            'From: =?UTF-8?B?' . base64_encode(setting('company_name', 'Strona WWW')) . "?= <$from>",
            "To: <$to>",
            'Subject: =?UTF-8?B?' . base64_encode($clean($subject)) . '?=',
            'Message-ID: <' . bin2hex(random_bytes(8)) . "@$name>",
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: base64',
        ];
        if ($replyTo !== '' && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
            $h[] = "Reply-To: <$replyTo>";
        }
        $msg = implode("\r\n", $h) . "\r\n\r\n" . chunk_split(base64_encode($body), 76, "\r\n") . "\r\n.";
        smtp_cmd($fp, $msg, [250]);
        smtp_cmd($fp, 'QUIT', [221]);
        fclose($fp);
        return null;
    } catch (Throwable $t) {
        if (is_resource($fp)) {
            fclose($fp);
        }
        error_log('[mail] ' . $t->getMessage());
        return $t->getMessage();
    }
}

function smtp_cmd($fp, string $cmd, array $ok): string
{
    if ($cmd !== '') {
        fwrite($fp, $cmd . "\r\n");
    }
    $data = '';
    while (($line = fgets($fp, 515)) !== false) {
        $data .= $line;
        if (strlen($line) < 4 || $line[3] === ' ') {
            break;
        }
    }
    if (!in_array((int)substr($data, 0, 3), $ok, true)) {
        throw new RuntimeException('SMTP: ' . trim($data));
    }
    return $data;
}
