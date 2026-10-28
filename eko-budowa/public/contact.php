<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');

function out(bool $ok, string $msg, int $code = 200): never
{
    http_response_code($code);
    echo json_encode(['ok' => $ok, 'msg' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    out(false, 'Niedozwolona metoda.', 405);
}
csrf_check();
if (trim((string)($_POST['website'] ?? '')) !== '') { // honeypot
    out(true, 'Dziękujemy! Odezwiemy się najszybciej, jak to możliwe.');
}
$ip = client_ip();
if (rate_count('contact', $ip, 3600) >= 5) {
    out(false, 'Wysłano zbyt wiele zapytań. Spróbuj później lub zadzwoń.', 429);
}

$name = trim((string)($_POST['name'] ?? ''));
$phone = trim((string)($_POST['phone'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$type = trim((string)($_POST['type'] ?? ''));
$msg = trim((string)($_POST['message'] ?? ''));

if (mb_strlen($name) < 2 || mb_strlen($name) > 100) out(false, 'Podaj imię.', 422);
if ($phone === '' && $email === '') out(false, 'Podaj telefon lub e-mail, abyśmy mogli odpowiedzieć.', 422);
if ($phone !== '' && !preg_match('/^[0-9 +()\-]{7,20}$/', $phone)) out(false, 'Nieprawidłowy numer telefonu.', 422);
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) out(false, 'Nieprawidłowy adres e-mail.', 422);
if (mb_strlen($msg) < 5 || mb_strlen($msg) > 3000) out(false, 'Wiadomość powinna mieć od 5 do 3000 znaków.', 422);
$type = mb_substr($type, 0, 100);

db()->prepare('INSERT INTO messages(name, phone, email, type, message, ip) VALUES(?,?,?,?,?,?)')
    ->execute([$name, $phone, $email, $type, $msg, $ip]);
rate_add('contact', $ip);

$body = "Nowe zapytanie ze strony\n\nImię: $name\nTelefon: $phone\nE-mail: $email\nRodzaj remontu: $type\n\nWiadomość:\n$msg\n";
mail_send(env('MAIL_TO', setting('email')), 'Nowe zapytanie ze strony – ' . $name, $body, $email);

out(true, 'Dziękujemy! Wiadomość została wysłana. Odezwiemy się najszybciej, jak to możliwe.');
