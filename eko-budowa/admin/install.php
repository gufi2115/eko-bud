<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/bootstrap.php';

header('Cache-Control: no-store');
run_migrations(); // tworzy tabele i wypełnia danymi startowymi (jeśli puste)

if (admin_exists()) {
    redirect('/admin/login.php'); // kreator zablokowany po instalacji
}

$error = '';
$login = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $login = trim((string)($_POST['login'] ?? ''));
    $p1 = (string)($_POST['password'] ?? '');
    $p2 = (string)($_POST['password2'] ?? '');
    if (mb_strlen($login) < 3) {
        $error = 'Login musi mieć co najmniej 3 znaki.';
    } elseif (mb_strlen($p1) < 10) {
        $error = 'Hasło musi mieć co najmniej 10 znaków.';
    } elseif ($p1 !== $p2) {
        $error = 'Hasła nie są takie same.';
    } else {
        create_admin($login, $p1);
        flash('Instalacja zakończona. Zaloguj się.');
        redirect('/admin/login.php');
    }
}
?><!doctype html>
<html lang="pl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex">
<title>Instalacja – panel administratora</title>
<style>
body{font-family:system-ui,sans-serif;background:#ecfdf5;margin:0;display:grid;place-items:center;min-height:100vh;padding:1rem}
main{background:#fff;max-width:420px;width:100%;padding:2rem;border-radius:12px;box-shadow:0 4px 24px #0002}
h1{color:#064e3b;font-size:1.4rem;margin-top:0}label{display:block;margin:1rem 0 .3rem;font-weight:600}
input{width:100%;box-sizing:border-box;padding:.8rem;border:1px solid #9ca3af;border-radius:8px;font-size:1rem}
input:focus,button:focus{outline:3px solid #10b981;outline-offset:2px}
button{margin-top:1.5rem;width:100%;padding:.9rem;background:#064e3b;color:#fff;border:0;border-radius:8px;font-size:1rem;cursor:pointer}
.err{background:#fee2e2;color:#991b1b;padding:.7rem;border-radius:8px}small{color:#4b5563}
</style>
</head>
<body>
<main>
  <h1>Witaj! Ustaw dostęp do panelu</h1>
  <p>Baza została przygotowana i wypełniona danymi firmy. Ustaw login i hasło administratora.</p>
  <?php if ($error): ?><p class="err" role="alert"><?= e($error) ?></p><?php endif; ?>
  <form method="post" autocomplete="off">
    <?= csrf_field() ?>
    <label for="login">Login</label>
    <input id="login" name="login" value="<?= e($login) ?>" required minlength="3" autocomplete="username">
    <label for="password">Hasło</label>
    <input id="password" name="password" type="password" required minlength="10" autocomplete="new-password">
    <small>Minimum 10 znaków.</small>
    <label for="password2">Powtórz hasło</label>
    <input id="password2" name="password2" type="password" required minlength="10" autocomplete="new-password">
    <button type="submit">Zakończ instalację</button>
  </form>
</main>
</body>
</html>
